<?php
/**
 * Instagram AI Integration.
 *
 * Handles Instagram Messenger API webhook, OpenAI GPT integration,
 * conversation management and admin UI for viewing chats.
 *
 * @since   1.0.60
 * @package pn-customers-manager
 */

class PN_CUSTOMERS_MANAGER_Instagram_AI {

  use PN_CM_AI_Chat_Common;

  /**
   * REST API namespace.
   */
  const REST_NAMESPACE = 'pn-cm/v1';

  /* ================================================================
   * PLATFORM CONFIG (trait abstract implementations)
   * ================================================================ */

  protected static function platform_prefix()            { return 'ig_'; }
  protected static function conversations_table_suffix()  { return 'pn_cm_instagram_conversations'; }
  protected static function platform_display_name()       { return 'Instagram'; }
  protected static function brand_color()                 { return '#C13584'; }
  protected static function email_type()                  { return 'pn_cm_instagram_order'; }
  protected static function log_channel()                 { return 'instagram-ai'; }
  protected static function node_subtype()                { return 'instagram_ai'; }
  protected static function supports_native_images()      { return false; }
  protected static function get_identifier_field()        { return 'ig_user_id'; }
  protected static function get_identifier_label()        { return __('Instagram User', 'pn-customers-manager'); }
  protected static function get_identifier_value($conversation) { return $conversation->ig_user_id ?? ''; }

  protected static function get_formatting_rules() {
    return "FORMATTING RULES: You are responding via Instagram DMs. "
      . "Use plain text only. Instagram DMs do NOT support bold, italic or any special formatting.\n"
      . "- Do NOT use Markdown syntax (no **, no _, no #, no ```).\n"
      . "- Do NOT use WhatsApp formatting (no *bold*, no _italic_).\n"
      . "- Links: paste the plain URL directly (e.g. https://example.com). "
      . "NEVER use Markdown link syntax like [text](url).\n"
      . "CRITICAL: ALWAYS copy URLs exactly as they appear in the product catalog or reference data. "
      . "NEVER correct, fix, modify or rewrite any part of a URL, even if it appears to contain a typo. "
      . "The URLs are machine-generated and any modification will break them.";
  }

  protected static function get_image_rules($include_images) {
    // Instagram does not support native image sending
    return '';
  }

  /* ================================================================
   * REST ROUTES
   * ================================================================ */

  public static function register_routes() {
    register_rest_route(self::REST_NAMESPACE, '/instagram/webhook', [
      [
        'methods'             => 'GET',
        'callback'            => [__CLASS__, 'handle_webhook_verify'],
        'permission_callback' => '__return_true',
      ],
      [
        'methods'             => 'POST',
        'callback'            => [__CLASS__, 'handle_webhook_message'],
        'permission_callback' => '__return_true',
      ],
    ]);
  }

  /* ================================================================
   * WEBHOOK: Verification (GET)
   * ================================================================ */

  public static function handle_webhook_verify($request) {
    $mode      = isset($_GET['hub_mode'])         ? sanitize_text_field(wp_unslash($_GET['hub_mode']))         : '';
    $token     = isset($_GET['hub_verify_token'])  ? sanitize_text_field(wp_unslash($_GET['hub_verify_token']))  : '';
    $challenge = isset($_GET['hub_challenge'])     ? sanitize_text_field(wp_unslash($_GET['hub_challenge']))     : '';

    if (empty($mode)) {
      $mode      = isset($_GET['hub.mode'])         ? sanitize_text_field(wp_unslash($_GET['hub.mode']))         : '';
      $token     = isset($_GET['hub.verify_token'])  ? sanitize_text_field(wp_unslash($_GET['hub.verify_token']))  : '';
      $challenge = isset($_GET['hub.challenge'])     ? sanitize_text_field(wp_unslash($_GET['hub.challenge']))     : '';
    }

    $stored_token = get_option('pn_customers_manager_instagram_verify_token', '');

    self::log('Webhook verify attempt — mode=' . $mode . ' token_match=' . ($token === $stored_token ? 'yes' : 'no'));

    if ($mode === 'subscribe' && $token === $stored_token && !empty($stored_token)) {
      status_header(200);
      header('Content-Type: text/plain');
      echo esc_html($challenge);
      exit;
    }

    return new WP_Error('forbidden', 'Verification failed', ['status' => 403]);
  }

  /* ================================================================
   * WEBHOOK: Incoming message (POST)
   * ================================================================ */

  public static function handle_webhook_message($request) {
    $body = $request->get_json_params();

    self::log('Webhook POST received — payload: ' . wp_json_encode($body));

    if (empty($body['entry'])) {
      self::log('Webhook POST — no entry found');
      return new WP_REST_Response(['status' => 'no_entry'], 200);
    }

    $pending_messages = [];

    foreach ($body['entry'] as $entry) {
      if (empty($entry['messaging'])) {
        continue;
      }

      foreach ($entry['messaging'] as $event) {
        if (empty($event['message']) || empty($event['message']['text'])) {
          continue;
        }

        // Skip echo messages (messages sent by the page itself)
        if (!empty($event['message']['is_echo'])) {
          continue;
        }

        $sender_id = isset($event['sender']['id']) ? sanitize_text_field($event['sender']['id']) : '';
        $text      = sanitize_text_field($event['message']['text']);
        $mid       = isset($event['message']['mid']) ? sanitize_text_field($event['message']['mid']) : '';

        if (empty($sender_id) || empty($text)) {
          continue;
        }

        // Deduplicate
        if (!empty($mid)) {
          $transient_key = 'pn_cm_ig_msg_' . md5($mid);
          if (get_transient($transient_key)) {
            self::log('Webhook — SKIPPING duplicate message mid=' . $mid);
            continue;
          }
          set_transient($transient_key, 1, 300);
        }

        self::log('Webhook — message from: ' . $sender_id . ' text: ' . mb_substr($text, 0, 50));

        $pending_messages[] = [
          'sender_id' => $sender_id,
          'text'      => $text,
        ];
      }
    }

    if (!empty($pending_messages)) {
      add_action('shutdown', function () use ($pending_messages) {
        if (function_exists('fastcgi_finish_request')) {
          fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
          litespeed_finish_request();
        }

        foreach ($pending_messages as $msg) {
          PN_CUSTOMERS_MANAGER_Instagram_AI::deferred_process_message(
            $msg['sender_id'],
            $msg['text']
          );
        }
      });
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
  }

  public static function deferred_process_message($sender_id, $text) {
    self::process_incoming_message($sender_id, $text);
  }

  /* ================================================================
   * MESSAGE PROCESSING
   * ================================================================ */

  private static function process_incoming_message($sender_id, $text) {
    self::log('Processing message — sender_id=' . $sender_id . ' text=' . mb_substr($text, 0, 50));

    $conversation = self::get_or_create_conversation($sender_id);

    if (!$conversation) {
      self::log('ERROR — could not get/create conversation for ' . $sender_id);
      return;
    }

    self::log('Conversation ID=' . $conversation->id . ' funnel_id=' . $conversation->funnel_id . ' node_id=' . $conversation->node_id);

    $messages   = json_decode($conversation->messages, true) ?: [];
    $messages[] = [
      'role'      => 'user',
      'content'   => $text,
      'timestamp' => current_time('mysql'),
    ];

    // Read node config
    $node_config = self::get_node_config($conversation);
    if (empty($node_config)) {
      $ig_node = self::find_ai_node();
      if ($ig_node && !empty($ig_node['config'])) {
        $node_config = $ig_node['config'];
      }
    }

    $require_postal = !empty($node_config['ig_require_postal_code']);
    self::log('process_incoming_message — require_postal=' . ($require_postal ? 'YES' : 'NO')
      . ' raw_value=' . var_export($node_config['ig_require_postal_code'] ?? null, true)
      . ' node_config_keys=' . implode(',', array_keys($node_config)));

    $system_prompt = self::build_enriched_system_prompt($conversation);

    self::log('FINAL system_prompt length=' . mb_strlen($system_prompt) . ' preview=' . mb_substr($system_prompt, 0, 300));

    // Resolve AI model (node_config > global_option > conversation)
    $ai_model = self::resolve_ai_model($node_config, $conversation);
    self::sync_ai_model_to_db($ai_model, $conversation);

    $temperature = self::get_conversation_temperature($conversation);

    $openai_messages = self::build_openai_messages_with_gap_markers($messages, $system_prompt);

    // Try to answer shipping questions directly with WooCommerce, bypassing
    // the model. This only kicks in when the "Use WooCommerce shipping zones"
    // checkbox is enabled, the customer has shown shipping intent, and a
    // postal code is present in the conversation.
    $direct_shipping = self::try_build_direct_shipping_response($messages, $node_config);

    $is_fallback = false;
    if ($direct_shipping !== null) {
      $ai_response = $direct_shipping;
      self::log('Answered with direct WC shipping response (model skipped).');
    } else {
      // Pre-compute shipping zone match for any detected postal code and
      // inject the result as a hint on the last user message, so the model
      // cannot pick the wrong zone or re-ask for the code.
      $openai_messages = self::maybe_inject_shipping_hint($openai_messages);

      $ai_response = self::call_openai($openai_messages, $ai_model, $temperature);

      if ($ai_response === false) {
        $ai_response = self::get_error_fallback_message($node_config);
        $is_fallback = true;
        self::log('call_openai returned false — using configured error fallback message');
      }
    }

    if ($is_fallback) {
      // Static error message — skip all post-processing that is meant for
      // model output and send it as-is (only light Markdown conversion).
      $ai_response = self::markdown_to_instagram($ai_response);
    } else {
      // Post-process response
      $ai_response = self::sanitize_system_prompt_leak($ai_response);
      $ai_response = self::enforce_postal_code_rule($ai_response, $messages, $require_postal, $conversation);
      $ai_response = self::markdown_to_instagram($ai_response);
      $ai_response = self::strip_hallucinated_image_urls($ai_response);
      $ai_response = self::validate_response_urls($ai_response);
      $ai_response = self::detect_and_notify_order($ai_response, $conversation, $messages, $node_config);
      $ai_response = self::detect_and_notify_special_order($ai_response, $conversation, $messages, $node_config);
    }

    $messages[] = [
      'role'      => 'assistant',
      'content'   => $ai_response,
      'timestamp' => current_time('mysql'),
    ];

    self::update_conversation_messages($conversation->id, $messages);

    self::send_instagram_message($sender_id, $ai_response);
  }

  /* ================================================================
   * MARKDOWN -> INSTAGRAM FORMATTING
   * ================================================================ */

  private static function markdown_to_instagram($text) {
    // Markdown links [text](url) → text: url
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '$1: $2', $text);
    // Bold: **text** or __text__ → just text
    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
    $text = preg_replace('/__(.+?)__/', '$1', $text);
    // Headings: ### text → text
    $text = preg_replace('/^#{1,6}\s+(.+)$/m', '$1', $text);
    // Inline code: `text` → text
    $text = preg_replace('/`([^`]+)`/', '$1', $text);

    return $text;
  }

  /* ================================================================
   * INSTAGRAM MESSENGER API
   * ================================================================ */

  private static function send_instagram_message($to, $text) {
    $access_token = get_option('pn_customers_manager_instagram_access_token', '');
    $page_id      = get_option('pn_customers_manager_instagram_page_id', '');

    if (empty($access_token) || empty($page_id)) {
      self::log('send_instagram_message — missing token or page_id');
      return false;
    }

    $url = 'https://graph.facebook.com/v21.0/' . $page_id . '/messages';

    $payload = [
      'recipient' => ['id' => $to],
      'message'   => ['text' => $text],
    ];

    self::log('send_instagram_message — to=' . $to . ' payload=' . wp_json_encode($payload));

    $response = wp_remote_post($url, [
      'timeout' => 30,
      'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
      ],
      'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
      self::log('send_instagram_message — WP_Error: ' . $response->get_error_message());
      return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    self::log('send_instagram_message — response code=' . $code . ' body=' . $body);

    return $code >= 200 && $code < 300;
  }

  /* ================================================================
   * CONVERSATION CRUD
   * ================================================================ */

  private static function get_or_create_conversation($ig_user_id, $contact_name = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    $conversation = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE ig_user_id = %s AND status = 'active' ORDER BY updated_at DESC LIMIT 1",
      $ig_user_id
    ));

    if ($conversation) {
      if (!empty($contact_name) && empty($conversation->contact_name)) {
        $wpdb->update($table, ['contact_name' => $contact_name], ['id' => $conversation->id]);
        $conversation->contact_name = $contact_name;
      }
      return $conversation;
    }

    $system_prompt = get_option('pn_customers_manager_whatsapp_system_prompt', '');
    $ai_model      = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');
    $funnel_id     = 0;
    $node_id       = '';

    $ig_node = self::find_ai_node();
    if ($ig_node) {
      $funnel_id = $ig_node['funnel_id'];
      $node_id   = $ig_node['node_id'];

      if (!empty($ig_node['config']['ig_system_prompt'])) {
        $system_prompt = $ig_node['config']['ig_system_prompt'];
      }
      if (!empty($ig_node['config']['ig_ai_model'])) {
        $ai_model = $ig_node['config']['ig_ai_model'];
      }

      self::log('Auto-linked conversation to funnel=' . $funnel_id . ' node=' . $node_id);
    }

    $wpdb->insert($table, [
      'ig_user_id'    => $ig_user_id,
      'contact_name'  => $contact_name,
      'funnel_id'     => $funnel_id,
      'node_id'       => $node_id,
      'messages'      => '[]',
      'system_prompt' => $system_prompt,
      'ai_model'      => $ai_model,
      'status'        => 'active',
      'created_at'    => current_time('mysql'),
      'updated_at'    => current_time('mysql'),
    ]);

    $id = $wpdb->insert_id;
    if (!$id) {
      return null;
    }

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
  }

  private static function update_conversation_messages($conversation_id, $messages) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    $wpdb->update(
      $table,
      [
        'messages'   => wp_json_encode($messages),
        'updated_at' => current_time('mysql'),
      ],
      ['id' => $conversation_id]
    );
  }

  public static function close_conversation($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    $wpdb->update(
      $table,
      ['status' => 'closed', 'updated_at' => current_time('mysql')],
      ['id' => $conversation_id]
    );
  }

  /**
   * Reset a conversation: wipe its message history so the next incoming
   * message triggers a fresh AI call with a newly built system prompt.
   * The row itself, contact data and funnel/node binding are preserved.
   *
   * @param int $conversation_id
   */
  public static function reset_conversation($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    $wpdb->update(
      $table,
      [
        'messages'   => '[]',
        'status'     => 'active',
        'updated_at' => current_time('mysql'),
      ],
      ['id' => $conversation_id]
    );
  }

  public static function delete_conversation($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    $wpdb->delete($table, ['id' => $conversation_id]);
  }

  public static function get_conversations($status = '', $page = 1, $per_page = 20) {
    global $wpdb;
    $table  = $wpdb->prefix . 'pn_cm_instagram_conversations';
    $offset = ($page - 1) * $per_page;

    $where = '';
    if ($status) {
      $where = $wpdb->prepare(' WHERE status = %s', $status);
    }

    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}{$where}");

    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$table}{$where} ORDER BY updated_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
      )
    );

    return [
      'items'    => $rows,
      'total'    => $total,
      'pages'    => ceil($total / $per_page),
      'page'     => $page,
      'per_page' => $per_page,
    ];
  }

  public static function get_conversation($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
  }

  public static function get_active_count() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return 0;
    }

    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
  }

  /* ================================================================
   * ADMIN PAGE
   * ================================================================ */

  public static function close_conversation_public($conv_id) {
    self::close_conversation($conv_id);
  }

  public static function delete_conversation_public($conv_id) {
    self::delete_conversation($conv_id);
  }

  public static function reset_conversation_public($conv_id) {
    self::reset_conversation($conv_id);
  }

  public static function ajax_get_conversation_detail_html($conv_id) {
    if (!$conv_id) {
      return ['error_key' => 'invalid_id'];
    }

    $conv = self::get_conversation($conv_id);
    if (!$conv) {
      return ['error_key' => 'not_found'];
    }

    $messages     = json_decode($conv->messages, true) ?: [];
    $funnel_title = $conv->funnel_id ? get_the_title($conv->funnel_id) : '-';

    ob_start();
    ?>
    <div class="pn-cm-wa-popup-header">
      <div class="pn-cm-wa-popup-title">
        <?php echo esc_html($conv->contact_name ?: $conv->ig_user_id); ?>
        <span class="pn-cm-wa-status pn-cm-wa-status-<?php echo esc_attr($conv->status); ?>">
          <?php echo $conv->status === 'active' ? esc_html__('Active', 'pn-customers-manager') : esc_html__('Closed', 'pn-customers-manager'); ?>
        </span>
      </div>
      <button type="button" class="pn-cm-wa-popup-close"><span class="material-icons-outlined">close</span></button>
    </div>
    <div class="pn-cm-wa-conv-meta">
      <span><strong><?php esc_html_e('Instagram User:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($conv->ig_user_id); ?></span>
      <span><strong><?php esc_html_e('Funnel:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($funnel_title); ?></span>
      <span><strong><?php esc_html_e('Model:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($conv->ai_model); ?></span>
      <span><strong><?php esc_html_e('Created:', 'pn-customers-manager'); ?></strong> <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conv->created_at))); ?></span>
    </div>
    <div class="pn-cm-wa-chat">
      <?php if (empty($messages)): ?>
        <div class="pn-cm-wa-chat-empty">
          <span class="material-icons-outlined">chat_bubble_outline</span>
          <p><?php esc_html_e('No messages in this conversation.', 'pn-customers-manager'); ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($messages as $msg): ?>
          <div class="pn-cm-wa-msg pn-cm-wa-msg-<?php echo esc_attr($msg['role']); ?>">
            <div class="pn-cm-wa-msg-bubble">
              <?php if (!empty($msg['images'])): ?>
                <div class="pn-cm-wa-msg-images">
                  <?php foreach ($msg['images'] as $img): ?>
                    <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($img['product_name'] ?? ''); ?>" loading="lazy">
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($msg['content'])): ?>
                <div class="pn-cm-wa-msg-content"><?php echo nl2br(esc_html($msg['content'])); ?></div>
              <?php endif; ?>
              <div class="pn-cm-wa-msg-time">
                <?php echo isset($msg['timestamp']) ? esc_html(wp_date(get_option('time_format'), strtotime($msg['timestamp']))) : ''; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="pn-cm-wa-conv-actions">
      <?php if ($conv->status === 'active'): ?>
        <button type="button" class="pn-cm-wa-action-btn" data-action="close" data-conv-id="<?php echo esc_attr($conv->id); ?>">
          <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">close</span>
          <?php esc_html_e('Close conversation', 'pn-customers-manager'); ?>
        </button>
      <?php endif; ?>
      <button type="button" class="pn-cm-wa-action-btn pn-cm-wa-btn-delete" data-action="delete" data-conv-id="<?php echo esc_attr($conv->id); ?>">
        <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">delete</span>
        <?php esc_html_e('Delete', 'pn-customers-manager'); ?>
      </button>
    </div>
    <?php
    $html = ob_get_clean();

    return ['error_key' => '', 'html' => $html];
  }

  public static function render_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to access this page.', 'pn-customers-manager'));
    }

    // Handle actions
    if (isset($_GET['action']) && isset($_GET['conv_id'])) {
      $conv_id = absint($_GET['conv_id']);

      if (!wp_verify_nonce(isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '', 'pn_cm_ig_action')) {
        wp_die(esc_html__('Action not allowed.', 'pn-customers-manager'));
      }

      $action = sanitize_text_field(wp_unslash($_GET['action']));

      if ($action === 'close' && $conv_id) {
        self::close_conversation($conv_id);
      } elseif ($action === 'delete' && $conv_id) {
        self::delete_conversation($conv_id);
      }

      wp_safe_redirect(admin_url('admin.php?page=pn_customers_manager_instagram_ai'));
      exit;
    }

    $view_id = isset($_GET['view']) ? absint($_GET['view']) : 0;

    if ($view_id) {
      self::render_conversation_detail($view_id);
      return;
    }

    $status  = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
    $page    = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $result  = self::get_conversations($status, $page);

    ?>
    <div class="wrap pn-cm-ig-admin-wrap">
      <h1 class="wp-heading-inline">
        <span class="material-icons-outlined" style="vertical-align:middle;margin-right:8px;">photo_camera</span>
        <?php esc_html_e('Instagram AI', 'pn-customers-manager'); ?>
      </h1>

      <div class="pn-cm-ig-filters">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai')); ?>"
           class="pn-cm-ig-filter-btn <?php echo $status === '' ? 'active' : ''; ?>">
          <?php esc_html_e('All', 'pn-customers-manager'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai&status=active')); ?>"
           class="pn-cm-ig-filter-btn <?php echo $status === 'active' ? 'active' : ''; ?>">
          <?php esc_html_e('Active', 'pn-customers-manager'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai&status=closed')); ?>"
           class="pn-cm-ig-filter-btn <?php echo $status === 'closed' ? 'active' : ''; ?>">
          <?php esc_html_e('Closed', 'pn-customers-manager'); ?>
        </a>
      </div>

      <?php if (empty($result['items'])): ?>
        <div class="pn-cm-ig-empty">
          <span class="material-icons-outlined">chat_bubble_outline</span>
          <p><?php esc_html_e('No conversations yet.', 'pn-customers-manager'); ?></p>
        </div>
      <?php else: ?>
        <table class="wp-list-table widefat fixed striped pn-cm-ig-table">
          <thead>
            <tr>
              <th><?php esc_html_e('Instagram User', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Name', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Funnel', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Last message', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Status', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
              <th><?php esc_html_e('Actions', 'pn-customers-manager'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($result['items'] as $conv):
              $msgs       = json_decode($conv->messages, true) ?: [];
              $last_msg   = !empty($msgs) ? end($msgs) : null;
              $last_text  = $last_msg ? wp_trim_words($last_msg['content'], 10, '...') : '-';
              $funnel_title = $conv->funnel_id ? get_the_title($conv->funnel_id) : '-';
            ?>
              <tr>
                <td>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai&view=' . $conv->id)); ?>" class="pn-cm-ig-conv-link">
                    <?php echo esc_html($conv->ig_user_id); ?>
                  </a>
                </td>
                <td><?php echo esc_html($conv->contact_name ?: '-'); ?></td>
                <td><?php echo esc_html($funnel_title); ?></td>
                <td class="pn-cm-ig-last-msg"><?php echo esc_html($last_text); ?></td>
                <td>
                  <span class="pn-cm-ig-status pn-cm-ig-status-<?php echo esc_attr($conv->status); ?>">
                    <?php echo $conv->status === 'active' ? esc_html__('Active', 'pn-customers-manager') : esc_html__('Closed', 'pn-customers-manager'); ?>
                  </span>
                </td>
                <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conv->updated_at))); ?></td>
                <td>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai&view=' . $conv->id)); ?>" class="button button-small">
                    <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">visibility</span>
                  </a>
                  <?php if ($conv->status === 'active'): ?>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai&action=close&conv_id=' . $conv->id), 'pn_cm_ig_action')); ?>" class="button button-small">
                      <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">close</span>
                    </a>
                  <?php endif; ?>
                  <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai&action=delete&conv_id=' . $conv->id), 'pn_cm_ig_action')); ?>"
                     class="button button-small pn-cm-ig-btn-delete"
                     onclick="return confirm('<?php echo esc_js(__('Delete this conversation?', 'pn-customers-manager')); ?>');">
                    <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">delete</span>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($result['pages'] > 1): ?>
          <div class="tablenav bottom">
            <div class="tablenav-pages">
              <?php
              $base_url = admin_url('admin.php?page=pn_customers_manager_instagram_ai');
              if ($status) {
                $base_url .= '&status=' . urlencode($status);
              }
              echo wp_kses_post(paginate_links([
                'base'    => $base_url . '%_%',
                'format'  => '&paged=%#%',
                'current' => $result['page'],
                'total'   => $result['pages'],
              ]));
              ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php

    self::enqueue_admin_assets();
  }

  private static function render_conversation_detail($conv_id) {
    $conv = self::get_conversation($conv_id);

    if (!$conv) {
      echo '<div class="wrap"><p>' . esc_html__('Conversation not found.', 'pn-customers-manager') . '</p></div>';
      return;
    }

    $messages     = json_decode($conv->messages, true) ?: [];
    $funnel_title = $conv->funnel_id ? get_the_title($conv->funnel_id) : '-';

    ?>
    <div class="wrap pn-cm-ig-admin-wrap">
      <h1 class="wp-heading-inline">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai')); ?>" class="pn-cm-ig-back">
          <span class="material-icons-outlined">arrow_back</span>
        </a>
        <?php echo esc_html($conv->contact_name ?: $conv->ig_user_id); ?>
        <span class="pn-cm-ig-status pn-cm-ig-status-<?php echo esc_attr($conv->status); ?>">
          <?php echo $conv->status === 'active' ? esc_html__('Active', 'pn-customers-manager') : esc_html__('Closed', 'pn-customers-manager'); ?>
        </span>
      </h1>

      <div class="pn-cm-ig-conv-meta">
        <span><strong><?php esc_html_e('Instagram User:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($conv->ig_user_id); ?></span>
        <span><strong><?php esc_html_e('Funnel:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($funnel_title); ?></span>
        <span><strong><?php esc_html_e('Model:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($conv->ai_model); ?></span>
        <span><strong><?php esc_html_e('Created:', 'pn-customers-manager'); ?></strong> <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conv->created_at))); ?></span>
      </div>

      <div class="pn-cm-ig-chat" id="pn-cm-ig-chat">
        <?php if (empty($messages)): ?>
          <div class="pn-cm-ig-chat-empty">
            <span class="material-icons-outlined">chat_bubble_outline</span>
            <p><?php esc_html_e('No messages in this conversation.', 'pn-customers-manager'); ?></p>
          </div>
        <?php else: ?>
          <?php foreach ($messages as $msg): ?>
            <div class="pn-cm-ig-msg pn-cm-ig-msg-<?php echo esc_attr($msg['role']); ?>">
              <div class="pn-cm-ig-msg-bubble">
                <?php if (!empty($msg['images'])): ?>
                  <div class="pn-cm-wa-msg-images">
                    <?php foreach ($msg['images'] as $img): ?>
                      <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($img['product_name'] ?? ''); ?>" loading="lazy">
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($msg['content'])): ?>
                  <div class="pn-cm-ig-msg-content"><?php echo nl2br(esc_html($msg['content'])); ?></div>
                <?php endif; ?>
                <div class="pn-cm-ig-msg-time">
                  <?php echo isset($msg['timestamp']) ? esc_html(wp_date(get_option('time_format'), strtotime($msg['timestamp']))) : ''; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="pn-cm-ig-conv-actions">
        <?php if ($conv->status === 'active'): ?>
          <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai&action=close&conv_id=' . $conv->id), 'pn_cm_ig_action')); ?>" class="button">
            <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">close</span>
            <?php esc_html_e('Close conversation', 'pn-customers-manager'); ?>
          </a>
        <?php endif; ?>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pn_customers_manager_instagram_ai&action=delete&conv_id=' . $conv->id), 'pn_cm_ig_action')); ?>"
           class="button pn-cm-ig-btn-delete"
           onclick="return confirm('<?php echo esc_js(__('Delete this conversation?', 'pn-customers-manager')); ?>');">
          <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">delete</span>
          <?php esc_html_e('Delete', 'pn-customers-manager'); ?>
        </a>
      </div>
    </div>
    <?php

    self::enqueue_admin_assets();
  }

  /* ================================================================
   * TEST METHODS (called from Settings via AJAX)
   * ================================================================ */

  public static function ajax_test_instagram($ig_user_id) {
    if (empty($ig_user_id)) {
      return [
        'error_key'     => 'no_ig_id',
        'error_content' => esc_html__('Enter a test Instagram User ID (IGSID).', 'pn-customers-manager'),
      ];
    }

    $access_token = get_option('pn_customers_manager_instagram_access_token', '');
    $page_id      = get_option('pn_customers_manager_instagram_page_id', '');

    if (empty($access_token)) {
      return [
        'error_key'     => 'no_token',
        'error_content' => esc_html__('Instagram Access Token has not been configured.', 'pn-customers-manager'),
      ];
    }

    if (empty($page_id)) {
      return [
        'error_key'     => 'no_page_id',
        'error_content' => esc_html__('Instagram Page ID has not been configured.', 'pn-customers-manager'),
      ];
    }

    $test_message = __('This is a test message from PN Customers Manager. If you receive it, the Instagram integration is working correctly.', 'pn-customers-manager');

    $success = self::send_instagram_message($ig_user_id, $test_message);

    if (!$success) {
      return [
        'error_key'     => 'send_failed',
        'error_content' => esc_html__('Could not send the message. Verify the Access Token, Page ID and that the IGSID is valid.', 'pn-customers-manager'),
      ];
    }

    $conversation = self::get_or_create_conversation($ig_user_id);
    if ($conversation) {
      $messages   = json_decode($conversation->messages, true) ?: [];
      $messages[] = [
        'role'      => 'assistant',
        'content'   => $test_message,
        'timestamp' => current_time('mysql'),
      ];
      self::update_conversation_messages($conversation->id, $messages);
    }

    return [
      'error_key' => '',
      'message'   => esc_html__('Test message sent successfully.', 'pn-customers-manager'),
    ];
  }

  public static function ajax_test_webhook_receive($since) {
    if ($since === 'start') {
      return [
        'found'       => false,
        'server_time' => current_time('mysql'),
      ];
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return [
        'error_key'     => 'no_table',
        'error_content' => esc_html__('Conversations table does not exist. Deactivate and reactivate the plugin.', 'pn-customers-manager'),
      ];
    }

    $row = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE updated_at >= %s ORDER BY updated_at DESC LIMIT 1",
      $since
    ));

    if (!$row) {
      return ['found' => false];
    }

    $messages  = json_decode($row->messages, true) ?: [];
    $found_msg = null;

    foreach ($messages as $msg) {
      if ($msg['role'] === 'user' && isset($msg['timestamp']) && $msg['timestamp'] >= $since) {
        $found_msg = $msg;
      }
    }

    if (!$found_msg) {
      return ['found' => false];
    }

    return [
      'found'   => true,
      'ig_user' => $row->ig_user_id,
      'name'    => $row->contact_name,
      'message' => $found_msg['content'],
      'time'    => $found_msg['timestamp'],
    ];
  }

  public static function ajax_get_conversations_list() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return ['error_key' => '', 'conversations' => []];
    }

    $rows = $wpdb->get_results(
      "SELECT id, ig_user_id, contact_name, status, ai_model, created_at, updated_at, messages
       FROM {$table}
       ORDER BY updated_at DESC
       LIMIT 50"
    );

    $conversations = [];
    foreach ($rows as $row) {
      $msgs     = json_decode($row->messages, true) ?: [];
      $last     = !empty($msgs) ? end($msgs) : null;
      $last_txt = $last ? mb_substr($last['content'], 0, 60) : '';

      $conversations[] = [
        'id'         => (int) $row->id,
        'ig_user'    => $row->ig_user_id,
        'name'       => $row->contact_name,
        'status'     => $row->status,
        'last_msg'   => $last_txt,
        'msg_count'  => count($msgs),
        'updated_at' => $row->updated_at,
      ];
    }

    return ['error_key' => '', 'conversations' => $conversations];
  }

  public static function ajax_get_conversation_messages($conv_id) {
    $conv = self::get_conversation($conv_id);

    if (!$conv) {
      return [
        'error_key'     => 'not_found',
        'error_content' => esc_html__('Conversation not found.', 'pn-customers-manager'),
      ];
    }

    $messages = json_decode($conv->messages, true) ?: [];

    return [
      'error_key' => '',
      'ig_user'   => $conv->ig_user_id,
      'name'      => $conv->contact_name,
      'status'    => $conv->status,
      'model'     => $conv->ai_model,
      'messages'  => $messages,
    ];
  }

  private static function enqueue_admin_assets() {
    wp_enqueue_style(
      'pn-customers-manager-instagram-ai',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-instagram-ai.css',
      [],
      PN_CUSTOMERS_MANAGER_VERSION
    );

    wp_enqueue_script(
      'pn-customers-manager-instagram-ai',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-instagram-ai.js',
      [],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );
  }
}
