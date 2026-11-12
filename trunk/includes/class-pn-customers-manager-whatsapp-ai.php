<?php
/**
 * WhatsApp AI Integration.
 *
 * Handles WhatsApp Business Platform webhook, OpenAI GPT integration,
 * conversation management and admin UI for viewing chats.
 *
 * @since   1.0.42
 * @package pn-customers-manager
 */

class PN_CUSTOMERS_MANAGER_WhatsApp_AI {

  use PN_CM_AI_Chat_Common;

  /**
   * REST API namespace.
   */
  const REST_NAMESPACE = 'pn-cm/v1';

  /* ================================================================
   * PLATFORM CONFIG (trait abstract implementations)
   * ================================================================ */

  protected static function platform_prefix()            { return 'wa_'; }
  protected static function conversations_table_suffix()  { return 'pn_cm_whatsapp_conversations'; }
  protected static function platform_display_name()       { return 'WhatsApp'; }
  protected static function brand_color()                 { return '#25D366'; }
  protected static function email_type()                  { return 'pn_cm_whatsapp_order'; }
  protected static function log_channel()                 { return 'whatsapp-ai'; }
  protected static function debug_log_option()             { return 'pn_customers_manager_whatsapp_debug_log'; }
  protected static function node_subtype()                { return 'whatsapp_ai'; }
  protected static function supports_native_images()      { return true; }
  protected static function get_identifier_field()        { return 'phone_number'; }
  protected static function get_identifier_label()        { return __('Phone', 'pn-customers-manager'); }
  protected static function get_identifier_value($conversation) { return $conversation->phone_number ?? ''; }

  protected static function get_formatting_rules() {
    return "FORMATTING RULES: You are responding via WhatsApp. "
      . "Do NOT use Markdown syntax. Use WhatsApp formatting instead:\n"
      . "- Bold: *text*\n"
      . "- Italic: _text_\n"
      . "- Strikethrough: ~text~\n"
      . "- Monospace: ```text```\n"
      . "- Links: paste the plain URL directly (e.g. https://example.com). "
      . "NEVER use Markdown link syntax like [text](url).\n"
      . "CRITICAL: ALWAYS copy URLs exactly as they appear in the product catalog or reference data. "
      . "NEVER correct, fix, modify or rewrite any part of a URL, even if it appears to contain a typo. "
      . "The URLs are machine-generated and any modification will break them.\n\n"
      . "RESPONSE STYLE — NATURAL AND COHESIVE COMMUNICATION:\n"
      . "- When the customer asks multiple questions or brings up several related topics, provide ONE unified, natural response that addresses everything together in a logical flow.\n"
      . "- DO NOT respond to each point separately with disconnected answers. Instead, weave all information together into a coherent, conversational response.\n"
      . "- Think of your response as a natural conversation — group related information, provide context, and create smooth transitions between topics.\n"
      . "- Example: If a customer asks about product features, shipping, and price all at once, integrate all that information in a single well-structured message rather than giving three separate mini-answers.\n"
      . "- Prioritize clarity and natural flow over rigid point-by-point responses. Your goal is to sound helpful and conversational, not robotic.";
  }

  protected static function get_image_rules($include_images) {
    if ($include_images) {
      return "PRODUCT IMAGES RULES:\n"
        . "- Each product in the catalog has an Image tag like [PRODUCT_IMAGES:ID].\n"
        . "- When you mention or recommend a specific product, ALWAYS include its Image tag in your response. The system will send ONE main photo as a native WhatsApp image.\n"
        . "- Example: if recommending product ID 5052, write [PRODUCT_IMAGES:5052] somewhere in your message.\n"
        . "- You can include multiple Image tags if recommending several products.\n"
        . "- If the customer asks to see MORE photos of a product whose main image was already sent, use [PRODUCT_MORE_IMAGES:ID] to send the remaining gallery photos.\n"
        . "- NEVER use [PRODUCT_MORE_IMAGES:ID] unless the customer explicitly asks for more photos.\n"
        . "- NEVER invent image URLs. NEVER use markdown image syntax ![](). NEVER write URLs ending in .jpg/.png/.webp.\n"
        . "- The ONLY way to show images is [PRODUCT_IMAGES:ID] or [PRODUCT_MORE_IMAGES:ID] with the exact ID from the catalog.";
    }

    return "PRODUCT IMAGES RULES:\n"
      . "- The system automatically sends ONE product image as a WhatsApp photo when you mention a product URL.\n"
      . "- If the customer asks to see a product image, you can also include [PRODUCT_IMAGES:ID] (replacing ID with the product's numeric ID) in your response. The system will send ONE main photo.\n"
      . "- If the customer asks to see MORE photos of a product whose main image was already sent, use [PRODUCT_MORE_IMAGES:ID] to send the remaining gallery photos.\n"
      . "- NEVER use [PRODUCT_MORE_IMAGES:ID] unless the customer explicitly asks for more photos.\n"
      . "- NEVER invent image URLs. NEVER use markdown image syntax ![](). NEVER write URLs ending in .jpg/.png/.webp.\n"
      . "- The ONLY way to show images is via product URLs (automatic), [PRODUCT_IMAGES:ID] or [PRODUCT_MORE_IMAGES:ID].";
  }

  /* ================================================================
   * REST ROUTES
   * ================================================================ */

  /**
   * Early webhook handler — runs at init (priority 1), before REST API processing.
   * Intercepts GET verification requests to avoid REST API output buffering issues.
   */
  public static function early_webhook_verify() {
    if (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
      return;
    }

    $request_uri = $_SERVER['REQUEST_URI'];

    $is_webhook = (
      strpos($request_uri, '/wp-json/pn-cm/v1/whatsapp/webhook') !== false ||
      (strpos($request_uri, 'rest_route') !== false && strpos($request_uri, 'pn-cm/v1/whatsapp/webhook') !== false)
    );

    if (!$is_webhook) {
      return;
    }

    $mode      = isset($_GET['hub_mode'])         ? sanitize_text_field(wp_unslash($_GET['hub_mode']))         : '';
    $token     = isset($_GET['hub_verify_token'])  ? sanitize_text_field(wp_unslash($_GET['hub_verify_token']))  : '';
    $challenge = isset($_GET['hub_challenge'])     ? sanitize_text_field(wp_unslash($_GET['hub_challenge']))     : '';

    if (empty($mode)) {
      return;
    }

    $stored_token = get_option('pn_customers_manager_whatsapp_verify_token', '');

    self::log('Webhook verify — mode=' . $mode . ' token_match=' . ($token === $stored_token ? 'yes' : 'no'));

    if ($mode === 'subscribe' && $token === $stored_token && !empty($stored_token)) {
      while (ob_get_level()) { ob_end_clean(); }
      $safe_challenge = esc_html($challenge);
      http_response_code(200);
      header('Content-Type: text/plain; charset=utf-8');
      header('Content-Length: ' . strlen($safe_challenge));
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      echo $safe_challenge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above via esc_html().
      exit;
    }

    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Verification failed';
    exit;
  }

  /**
   * Register REST API routes for the WhatsApp webhook.
   */
  public static function register_routes() {
    register_rest_route(self::REST_NAMESPACE, '/whatsapp/webhook', [
      [
        'methods'             => 'GET',
        'callback'            => [__CLASS__, 'handle_webhook_verify'],
        'permission_callback' => '__return_true',
        'args'                => [
          'hub_mode'         => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
          'hub_verify_token' => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
          'hub_challenge'    => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
        ],
      ],
      [
        'methods'             => 'POST',
        'callback'            => [__CLASS__, 'handle_webhook_message'],
        'permission_callback' => '__return_true',
      ],
    ]);

  }

  /**
   * Handle Meta webhook verification challenge (REST API fallback).
   */
  public static function handle_webhook_verify($request) {
    $mode      = $request->get_param('hub_mode')         ?: '';
    $token     = $request->get_param('hub_verify_token')  ?: '';
    $challenge = $request->get_param('hub_challenge')     ?: '';

    $stored_token = get_option('pn_customers_manager_whatsapp_verify_token', '');

    if ($mode === 'subscribe' && $token === $stored_token && !empty($stored_token)) {
      status_header(200);
      header('Content-Type: text/plain; charset=utf-8');
      echo esc_html($challenge);
      exit;
    }

    return new WP_Error('forbidden', 'Verification failed', ['status' => 403]);
  }

  /* ================================================================
   * WEBHOOK: Incoming message (POST)
   * ================================================================ */

  /**
   * Handle incoming WhatsApp message from Meta webhook.
   *
   * @param WP_REST_Request $request
   * @return WP_REST_Response
   */
  public static function handle_webhook_message($request) {
    // Process any overdue scheduled templates on every incoming webhook.
    // This ensures templates fire even when WP Cron is unreliable.
    self::process_pending_template_events();

    $body = $request->get_json_params();

    self::log('Webhook POST received — payload: ' . wp_json_encode($body));

    if (empty($body['entry'])) {
      self::log('Webhook POST — no entry found');
      return new WP_REST_Response(['status' => 'no_entry'], 200);
    }

    $pending_messages = [];

    foreach ($body['entry'] as $entry) {
      if (empty($entry['changes'])) {
        continue;
      }

      foreach ($entry['changes'] as $change) {
        if (isset($change['value']['statuses'])) {
          self::log('Webhook — status update received (delivery/read receipt)');
        }

        if (empty($change['value']['messages'])) {
          continue;
        }

        $contacts = isset($change['value']['contacts']) ? $change['value']['contacts'] : [];

        foreach ($change['value']['messages'] as $message) {
          $msg_type = isset($message['type']) ? $message['type'] : '';
          self::log('Webhook — message type: ' . ($msg_type ?: 'unknown') . ' from: ' . ($message['from'] ?? 'unknown'));

          if (!in_array($msg_type, ['text', 'audio'], true)) {
            continue;
          }

          // Deduplicate
          $msg_id = isset($message['id']) ? sanitize_text_field($message['id']) : '';
          if (!empty($msg_id)) {
            $transient_key = 'pn_cm_wa_msg_' . md5($msg_id);
            if (get_transient($transient_key)) {
              self::log('Webhook — SKIPPING duplicate message id=' . $msg_id);
              continue;
            }
            set_transient($transient_key, 1, 300);
          }

          $from         = sanitize_text_field($message['from']);
          $contact_name = '';

          foreach ($contacts as $contact) {
            if (isset($contact['wa_id']) && $contact['wa_id'] === $from) {
              $contact_name = isset($contact['profile']['name'])
                ? sanitize_text_field($contact['profile']['name'])
                : '';
              break;
            }
          }

          if ($msg_type === 'audio') {
            if (empty(get_option('pn_customers_manager_audio_transcription_enabled', ''))) {
              self::log('Webhook — audio message ignored (transcription disabled)');
              continue;
            }

            $pending_messages[] = [
              'from'               => $from,
              'text'               => '',
              'contact_name'       => $contact_name,
              'audio_media_id'     => isset($message['audio']['id']) ? sanitize_text_field($message['audio']['id']) : '',
              'audio_mime'         => isset($message['audio']['mime_type']) ? sanitize_text_field($message['audio']['mime_type']) : 'audio/ogg',
              'is_reply'           => !empty($message['context']),
              'quoted_context_id'  => isset($message['context']['id']) ? sanitize_text_field($message['context']['id']) : '',
            ];
          } else {
            $pending_messages[] = [
              'from'               => $from,
              'text'               => sanitize_text_field($message['text']['body']),
              'contact_name'       => $contact_name,
              'is_reply'           => !empty($message['context']),
              'quoted_context_id'  => isset($message['context']['id']) ? sanitize_text_field($message['context']['id']) : '',
            ];
          }
        }
      }
    }

    if (!empty($pending_messages)) {
      add_action('shutdown', function () use ($pending_messages) {
        // Ensure PHP keeps running after the client disconnects.
        ignore_user_abort(true);
        set_time_limit(120);

        if (function_exists('fastcgi_finish_request')) {
          fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
          litespeed_finish_request();
        } else {
          // Flush output for non-FPM environments.
          if (ob_get_level()) {
            ob_end_flush();
          }
          flush();
        }

        foreach ($pending_messages as $msg) {
          PN_CUSTOMERS_MANAGER_WhatsApp_AI::deferred_process_message(
            $msg['from'],
            $msg['text'],
            $msg['contact_name'],
            !empty($msg['audio_media_id']) ? $msg['audio_media_id'] : '',
            isset($msg['audio_mime']) ? $msg['audio_mime'] : '',
            !empty($msg['is_reply']),
            isset($msg['quoted_context_id']) ? $msg['quoted_context_id'] : ''
          );
        }
      });
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
  }

  /* ================================================================
   * RESPONSE DELAY & MESSAGE BUFFERING
   *
   * To make the bot feel more human, we introduce a 10-second delay
   * before processing a message.  If the customer sends additional
   * messages during this window the timer resets and all messages are
   * processed together as a single AI request.
   * ================================================================ */

  /** Delay in seconds before processing buffered messages. */
  private static $response_delay = 10;

  /**
   * Buffer a message for a phone number and set/reset the response deadline.
   *
   * Uses wp_options with direct $wpdb queries to ensure cross-process visibility.
   *
   * @param string $phone
   * @param array  $msg  Message data (text, contact_name, audio_*, is_reply).
   */
  private static function buffer_message($phone, $msg) {
    global $wpdb;

    $hash         = md5($phone);
    $buffer_key   = 'pn_cm_wa_buf_' . $hash;
    $deadline_key = 'pn_cm_wa_dl_' . $hash;
    $fallback_key = 'pn_cm_wa_fb_' . $hash;

    // Read buffer directly from DB, bypassing all cache layers.
    $raw = $wpdb->get_var($wpdb->prepare(
      "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
      $buffer_key
    ));
    $buffer = $raw ? maybe_unserialize($raw) : [];
    if (!is_array($buffer)) {
      $buffer = [];
    }
    $buffer[] = $msg;

    // Upsert buffer.
    $serialized = maybe_serialize($buffer);
    $buf_result = $wpdb->query($wpdb->prepare(
      "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')
       ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
      $buffer_key,
      $serialized
    ));

    // Upsert deadline (reset on every new message).
    $new_deadline = time() + self::$response_delay;
    $dl_result = $wpdb->query($wpdb->prepare(
      "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')
       ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
      $deadline_key,
      $new_deadline
    ));

    // Verify the buffer was actually persisted.
    $verify = $wpdb->get_var($wpdb->prepare(
      "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
      $buffer_key
    ));
    $verify_count = 0;
    if ($verify) {
      $verify_data = maybe_unserialize($verify);
      $verify_count = is_array($verify_data) ? count($verify_data) : 0;
    }

    self::log('buffer_message — phone=' . $phone . ' buffer_size=' . count($buffer) . ' deadline=' . $new_deadline . ' buf_write=' . var_export($buf_result, true) . ' dl_write=' . var_export($dl_result, true) . ' verify_count=' . $verify_count);

    if ($verify_count === 0) {
      self::log('buffer_message — WARNING: buffer verification FAILED, retrying with DELETE+INSERT. option_key=' . $buffer_key . ' last_error=' . $wpdb->last_error);

      // Retry strategy 1: delete any stale rows, then insert fresh.
      $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name = %s",
        $buffer_key
      ));
      $retry_result = $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
        $buffer_key,
        $serialized
      ));

      // Verify again.
      $verify2 = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
        $buffer_key
      ));
      $v2_count = 0;
      if ($verify2) {
        $v2_data = maybe_unserialize($verify2);
        $v2_count = is_array($v2_data) ? count($v2_data) : 0;
      }

      self::log('buffer_message — retry result=' . var_export($retry_result, true) . ' verify2_count=' . $v2_count);

      if ($v2_count === 0) {
        // Retry strategy 2: use WordPress update_option API as last resort.
        self::log('buffer_message — retry also failed, using update_option fallback');
        delete_option($buffer_key);
        update_option($buffer_key, $buffer, false);
      }
    }

    // Always store a fallback copy via transient so wait_and_process_buffer
    // can recover the messages if the primary buffer is lost.
    $existing_fb = get_transient($fallback_key);
    $fb_buffer   = (is_array($existing_fb) && !empty($existing_fb)) ? $existing_fb : [];
    $fb_buffer[] = $msg;
    set_transient($fallback_key, $fb_buffer, self::$response_delay * 6);
  }

  /**
   * Acquire a MySQL-level lock for the given phone number.
   * Uses GET_LOCK which is truly atomic across all PHP processes.
   *
   * @param  string $phone
   * @return bool   true if lock was acquired.
   */
  private static function acquire_buffer_lock($phone) {
    global $wpdb;
    $lock_name = 'pn_cm_wa_' . md5($phone);
    // Timeout 0 = non-blocking, return immediately if lock is held.
    $result = $wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, 0)", $lock_name));
    return (int) $result === 1;
  }

  /**
   * Release the MySQL-level lock for a phone number.
   *
   * @param string $phone
   */
  private static function release_buffer_lock($phone) {
    global $wpdb;
    $lock_name = 'pn_cm_wa_' . md5($phone);
    $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));
  }

  /**
   * Wait until the deadline expires (checking every second for resets),
   * then collect all buffered messages and process them as one.
   *
   * Reads the deadline directly from the database on each iteration
   * to detect resets made by other webhook processes.
   *
   * @param string $phone
   */
  private static function wait_and_process_buffer($phone) {
    global $wpdb;

    $hash         = md5($phone);
    $deadline_key = 'pn_cm_wa_dl_' . $hash;
    $buffer_key   = 'pn_cm_wa_buf_' . $hash;

    $max_wait = self::$response_delay * 6; // absolute safety cap
    $waited   = 0;

    self::log('wait_and_process_buffer — starting wait loop for phone=' . $phone);

    while ($waited < $max_wait) {
      // Read deadline directly from DB — no cache, no transient API.
      $deadline = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
        $deadline_key
      ));

      if ($deadline === null) {
        self::log('wait_and_process_buffer — deadline gone, breaking. phone=' . $phone);
        break;
      }

      $remaining = (int) $deadline - time();
      if ($remaining <= 0) {
        self::log('wait_and_process_buffer — deadline reached after ' . $waited . 's. phone=' . $phone);
        break;
      }

      sleep(1);
      $waited++;
    }

    // Collect buffer directly from DB.
    $raw = $wpdb->get_var($wpdb->prepare(
      "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
      $buffer_key
    ));
    $buffer = $raw ? maybe_unserialize($raw) : [];

    $fallback_key = 'pn_cm_wa_fb_' . $hash;

    if (!is_array($buffer) || empty($buffer)) {
      // Check if the option row exists at all.
      $row_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
        $buffer_key
      ));
      self::log('wait_and_process_buffer — buffer empty after wait, phone=' . $phone . ' raw_type=' . gettype($raw) . ' raw_len=' . strlen((string) $raw) . ' row_exists=' . $row_exists . ' last_error=' . $wpdb->last_error);

      // Try to recover from fallback transient.
      $fb = get_transient($fallback_key);
      if (is_array($fb) && !empty($fb)) {
        self::log('wait_and_process_buffer — RECOVERED ' . count($fb) . ' message(s) from fallback transient, phone=' . $phone);
        $buffer = $fb;
        delete_transient($fallback_key);
      } else {
        // Also try reading the buffer via WordPress API (in case direct DB missed it).
        $wp_buf = get_option($buffer_key, []);
        if (is_array($wp_buf) && !empty($wp_buf)) {
          self::log('wait_and_process_buffer — RECOVERED ' . count($wp_buf) . ' message(s) via get_option, phone=' . $phone);
          $buffer = $wp_buf;
          delete_option($buffer_key);
          delete_transient($fallback_key);
        } else {
          self::log('wait_and_process_buffer — all recovery attempts failed, phone=' . $phone);
          self::release_buffer_lock($phone);
          return;
        }
      }
    }

    // Clear buffer, deadline, and fallback from DB.
    $wpdb->query($wpdb->prepare(
      "DELETE FROM {$wpdb->options} WHERE option_name IN (%s, %s)",
      $buffer_key,
      $deadline_key
    ));
    delete_transient($fallback_key);

    self::log('wait_and_process_buffer — processing ' . count($buffer) . ' buffered message(s) for phone=' . $phone);

    // If only one message, process normally.
    if (count($buffer) === 1) {
      $msg = $buffer[0];
      self::process_incoming_message(
        $phone,
        $msg['text'],
        $msg['contact_name'],
        !empty($msg['audio_media_id']) ? $msg['audio_media_id'] : '',
        isset($msg['audio_mime']) ? $msg['audio_mime'] : '',
        !empty($msg['is_reply']),
        isset($msg['quoted_context_id']) ? $msg['quoted_context_id'] : ''
      );
    } else {
      // Multiple messages: combine texts and process as one.
      self::process_buffered_messages($phone, $buffer);
    }

    self::release_buffer_lock($phone);
  }

  /**
   * Process multiple buffered messages as a single AI request.
   *
   * Combines all user texts into the conversation history so the AI
   * sees every message and produces one comprehensive response.
   *
   * @param string $phone
   * @param array  $buffer Array of message data arrays.
   */
  private static function process_buffered_messages($phone, $buffer) {
    // Use the contact_name from the most recent message.
    $contact_name = '';
    $is_reply     = false;
    foreach ($buffer as $msg) {
      if (!empty($msg['contact_name'])) {
        $contact_name = $msg['contact_name'];
      }
      if (!empty($msg['is_reply'])) {
        $is_reply = true;
      }
    }

    // Collect all text parts (transcribe audio if needed) and their quoted context IDs.
    $texts = [];
    $text_quoted_ids = [];
    foreach ($buffer as $msg) {
      $t = $msg['text'];

      // Handle audio transcription if needed.
      if (!empty($msg['audio_media_id'])) {
        $audio_data = self::download_whatsapp_media($msg['audio_media_id']);
        if ($audio_data !== false) {
          $language = get_option('pn_customers_manager_transcription_language', '');
          $t = self::transcribe_audio($audio_data, $msg['audio_mime'] ?? 'audio/ogg', $language);
        }
        if (empty($t)) {
          continue; // Skip failed transcriptions.
        }
      }

      if (!empty($t)) {
        $texts[] = $t;
        $text_quoted_ids[] = isset($msg['quoted_context_id']) ? $msg['quoted_context_id'] : '';
      }
    }

    if (empty($texts)) {
      self::log('process_buffered_messages — no text to process after transcription');
      return;
    }

    // Get or create conversation (use the first text for welcome-message logic).
    $first_text = $texts[0];
    $conversation = self::get_or_create_conversation($phone, $contact_name, $is_reply, $first_text);
    if (!$conversation) {
      self::log('ERROR — could not get/create conversation for ' . $phone);
      return;
    }

    $node_config = self::get_node_config($conversation);
    if (empty($node_config)) {
      $wa_node = self::find_ai_node();
      if ($wa_node && !empty($wa_node['config'])) {
        $node_config = $wa_node['config'];
      }
    }

    // Add all user messages to history.
    $messages = json_decode($conversation->messages, true) ?: [];
    $buffered_quoted_texts = [];
    foreach ($texts as $idx => $t) {
      $quoted_text = '';
      $qid = isset($text_quoted_ids[$idx]) ? $text_quoted_ids[$idx] : '';
      if (!empty($qid)) {
        $quoted_text = self::find_message_by_wamid($messages, $qid);
        if (!empty($quoted_text)) {
          self::log('[CONVERSATION] QUOTING (buffered): ' . mb_substr($quoted_text, 0, 200));
        }
      }
      self::log('[CONVERSATION] USER (buffered): ' . $t);
      $user_entry = [
        'role'      => 'user',
        'content'   => $t,
        'timestamp' => current_time('mysql'),
      ];
      if (!empty($quoted_text)) {
        $user_entry['quoted_text'] = $quoted_text;
        $buffered_quoted_texts[$idx] = $quoted_text;
      }
      $messages[] = $user_entry;
    }

    // Skip greeting logic: if the only message is a simple greeting and welcome
    // was already sent, save and return (same logic as single-message path).
    if (count($texts) === 1) {
      $has_assistant_message = false;
      $user_message_count    = 0;
      foreach ($messages as $m) {
        if (isset($m['role']) && $m['role'] === 'user') $user_message_count++;
        if (isset($m['role']) && $m['role'] === 'assistant') $has_assistant_message = true;
      }
      if ($user_message_count === 1 && $has_assistant_message) {
        $normalized = mb_strtolower(trim($texts[0]));
        $greetings  = ['hola', 'holi', 'hi', 'hello', 'hey', 'buenos días', 'buenas tardes', 'buenas noches', 'buenos dias', 'buenas', 'saludos'];
        if (in_array($normalized, $greetings, true)) {
          self::update_conversation_messages($conversation->id, $messages);
          self::log('Skipping AI response — simple greeting detected and welcome message already sent (buffered path)');
          return;
        }
      }
    }

    // Off-hours handling: check before calling AI to save costs
    if (!empty($node_config['wa_schedule_enabled'])) {
      $user_tz = self::resolve_and_store_user_timezone($phone);
      if (!self::is_within_schedule($node_config, $user_tz)) {
        $is_client_initiated = self::is_client_initiated_conversation($messages);

        // Business-initiated conversations: always allow bot to respond.
        if (!$is_client_initiated) {
          self::log('Off-hours but business-initiated conversation — allowing bot to respond normally (buffered)');
        }
        // Option 1: Allow bot when client initiates
        elseif (!empty($node_config['wa_schedule_allow_client_initiated'])) {
          self::log('Off-hours but client initiated conversation — allowing bot to respond normally (buffered)');
        }
        // Option 2: Send custom off-hours message
        elseif (!empty($node_config['wa_schedule_offhours_message_enabled'])) {
          $offhours_msg = trim($node_config['wa_schedule_offhours_message'] ?? '');
          if (!empty($offhours_msg) && !self::offhours_message_already_sent($messages)) {
            self::log('Off-hours — sending custom off-hours message (buffered)');
            $messages[] = [
              'role'      => 'assistant',
              'content'   => $offhours_msg,
              'timestamp' => current_time('mysql'),
              'offhours'  => true,
              'auto_sent' => true,
            ];
            self::update_conversation_messages($conversation->id, $messages);
            self::send_whatsapp_message($phone, $offhours_msg);
            return;
          }
          self::update_conversation_messages($conversation->id, $messages);
          return;
        }
        // Neither option: existing behavior (AI processes, then queues)
      }
    }

    $require_postal = !empty($node_config['wa_require_postal_code']);
    $system_prompt  = self::build_enriched_system_prompt($conversation);
    $ai_model       = self::resolve_ai_model($node_config, $conversation);
    self::sync_ai_model_to_db($ai_model, $conversation);
    $temperature    = self::get_conversation_temperature($conversation);

    // Inject quoted context into user messages for the AI
    $ai_messages = $messages;
    if (!empty($buffered_quoted_texts)) {
      // Walk backward through ai_messages to find the user entries that correspond to our buffered texts
      $user_idx = count($texts) - 1;
      for ($i = count($ai_messages) - 1; $i >= 0 && $user_idx >= 0; $i--) {
        if (($ai_messages[$i]['role'] ?? '') === 'user') {
          if (isset($buffered_quoted_texts[$user_idx])) {
            $ai_messages[$i]['content'] = '[Quoting: "' . mb_substr($buffered_quoted_texts[$user_idx], 0, 500) . '"] ' . $ai_messages[$i]['content'];
          }
          $user_idx--;
        }
      }
    }

    $openai_messages = self::build_openai_messages_with_gap_markers($ai_messages, $system_prompt);

    self::log('[CONVERSATION] ── AI REQUEST (buffered, ' . count($texts) . ' user messages) ── model=' . $ai_model);

    $direct_shipping = self::try_build_direct_shipping_response($messages, $node_config);

    $is_fallback = false;
    if ($direct_shipping !== null) {
      $ai_response = $direct_shipping;
    } else {
      $openai_messages = self::maybe_inject_shipping_hint($openai_messages);
      $ai_response = self::call_openai($openai_messages, $ai_model, $temperature);

      if ($ai_response === false) {
        $ai_response = self::get_error_fallback_message($node_config);
        $is_fallback = true;
      } else {
        self::log('[CONVERSATION] ── RAW AI RESPONSE ──');
        self::log('[CONVERSATION] ' . $ai_response);
        self::log('[CONVERSATION] ── END RAW AI RESPONSE ──');
      }
    }

    // Post-processing (same as single-message path).
    $sent_images = [];
    if ($is_fallback) {
      $ai_response = self::markdown_to_whatsapp($ai_response);
    } else {
      $ai_response = self::strip_deepseek_think_tags($ai_response);
      $ai_response = self::sanitize_system_prompt_leak($ai_response);
      $ai_response = self::remove_duplicate_greetings($ai_response, $messages);
      $ai_response = self::enforce_postal_code_rule($ai_response, $messages, $require_postal, $conversation);
      $ai_response = self::markdown_to_whatsapp($ai_response);
      $ai_response = self::strip_hallucinated_image_urls($ai_response);
      $ai_response = self::validate_response_urls($ai_response);
      $ai_response = self::detect_and_notify_order($ai_response, $conversation, $messages, $node_config);
      $ai_response = self::detect_and_notify_special_order($ai_response, $conversation, $messages, $node_config);
      $ai_response = self::detect_and_notify_escalation($ai_response, $conversation, $messages, $node_config);

      // Extract and process follow-up tags
      $followup_keys = self::extract_followup_tags($ai_response);
      $ai_response   = self::strip_followup_tags($ai_response);
      if (!empty($followup_keys)) {
        self::create_followup_tasks($followup_keys, $phone, $contact_name, $node_config, 'wa_');
      }

      $already_sent_ids      = [];
      $already_sent_more_ids = [];
      foreach ($messages as $m) {
        if (!empty($m['images'])) {
          foreach ($m['images'] as $img) {
            if (!empty($img['product_id'])) {
              $pid = (int) $img['product_id'];
              if (!empty($img['is_gallery'])) {
                $already_sent_more_ids[] = $pid;
              } else {
                $already_sent_ids[] = $pid;
              }
            }
          }
        }
      }

      // Auto-inject image tags (same logic as single-message path).
      $woo_active = class_exists('WooCommerce');
      $recommendations_disabled = !empty($node_config['wa_disable_recommendations']);
      if (!$woo_active || $recommendations_disabled) {
        $ai_response = self::auto_inject_product_image_tags($ai_response);
      } elseif (!preg_match('/\[PRODUCT_IMAGES:\d+\]/', $ai_response)) {
        $site_url     = home_url();
        $product_base = 'product';
        $permalinks   = (array) get_option('woocommerce_permalinks', []);
        if (!empty($permalinks['product_base'])) {
          $product_base = trim($permalinks['product_base'], '/');
        }
        $url_count = preg_match_all(
          '#' . preg_quote($site_url, '#') . '/' . preg_quote($product_base, '#') . '/[a-z0-9\-]+/?#i',
          $ai_response
        );
        if ($url_count >= 1 && $url_count <= 2) {
          $ai_response = self::auto_inject_product_image_tags($ai_response);
        }
      }

      // Determine if message should be sent now or queued.
      $send_now = self::is_within_schedule($node_config, self::resolve_and_store_user_timezone($phone));

      if ($send_now) {
        $ai_response = self::extract_and_send_product_images($ai_response, $phone, $sent_images, $already_sent_ids);
        $ai_response = self::extract_and_send_more_product_images($ai_response, $phone, $sent_images, $already_sent_more_ids);
      } else {
        $extracted   = self::extract_product_images_data_only($ai_response, $already_sent_ids, $already_sent_more_ids);
        $ai_response = $extracted['text'];
        $sent_images = $extracted['images'];
      }
    }

    // Store assistant message.
    $stored_content = $ai_response;
    if (!empty($sent_images)) {
      $image_names = array_unique(array_column($sent_images, 'product_name'));
      $stored_content .= "\n[Se enviaron imágenes de: " . implode(', ', $image_names) . "]";
    }
    $assistant_msg = [
      'role'      => 'assistant',
      'content'   => $stored_content,
      'timestamp' => current_time('mysql'),
      'auto_sent' => true,
    ];
    if (!empty($sent_images)) {
      $assistant_msg['images'] = $sent_images;
    }
    $messages[] = $assistant_msg;

    self::update_conversation_messages($conversation->id, $messages);

    // Send or queue.
    if (!isset($send_now)) {
      $send_now = self::is_within_schedule($node_config, self::resolve_and_store_user_timezone($phone));
    }
    if ($send_now) {
      self::log('[CONVERSATION] ── FINAL RESPONSE SENT (buffered) ──');
      self::log('[CONVERSATION] ' . $ai_response);
      if (!empty($sent_images)) {
        self::log('[CONVERSATION] + Images sent: ' . implode(', ', array_unique(array_column($sent_images, 'product_name'))));
      }
      self::log('[CONVERSATION] ── END ──');
      $wamid = self::send_whatsapp_message($phone, $ai_response);
      if (!empty($wamid) && is_string($wamid)) {
        $last_idx = count($messages) - 1;
        if ($last_idx >= 0 && ($messages[$last_idx]['role'] ?? '') === 'assistant') {
          $messages[$last_idx]['wamid'] = $wamid;
          self::update_conversation_messages($conversation->id, $messages);
        }
      }
    } else {
      self::log('[CONVERSATION] ── RESPONSE QUEUED (buffered) ──');
      self::log('[CONVERSATION] ' . $ai_response);
      self::log('[CONVERSATION] ── END ──');
      self::maybe_send_or_queue($phone, $ai_response, $conversation->id, $node_config, $sent_images);
    }

    // Sync to GHL if integration is active
    $combined_text = implode("\n", $texts);
    self::sync_message_to_ghl($phone, $combined_text, $ai_response);
  }

  /**
   * Public wrapper for process_incoming_message (called from shutdown hook).
   * Now routes through the message buffer for response delay.
   *
   * @param string $phone
   * @param string $text
   * @param string $contact_name
   */
  public static function deferred_process_message($phone, $text, $contact_name, $audio_media_id = '', $audio_mime = '', $is_reply = false, $quoted_context_id = '') {
    $msg = [
      'text'              => $text,
      'contact_name'      => $contact_name,
      'audio_media_id'    => $audio_media_id,
      'audio_mime'        => $audio_mime,
      'is_reply'          => $is_reply,
      'quoted_context_id' => $quoted_context_id,
    ];

    // Buffer the message and (re)set the response deadline.
    self::buffer_message($phone, $msg);

    // Try to acquire the processing lock for this phone number.
    // Only one process should be sleeping/waiting per phone.
    if (self::acquire_buffer_lock($phone)) {
      self::wait_and_process_buffer($phone);
    } else {
      self::log('deferred_process_message — lock already held for phone=' . $phone . ', message buffered only');
    }
  }

  /* ================================================================
   * MESSAGE PROCESSING
   * ================================================================ */

  /**
   * Process a single incoming WhatsApp message.
   *
   * @param string $phone
   * @param string $text
   * @param string $contact_name
   */
  private static function process_incoming_message($phone, $text, $contact_name, $audio_media_id = '', $audio_mime = '', $is_reply = false, $quoted_context_id = '') {
    $is_audio = !empty($audio_media_id);
    self::log('Processing message — phone=' . $phone . ' contact=' . $contact_name . ($is_audio ? ' (audio)' : '') . ($is_reply ? ' (reply)' : ''));

    $conversation = self::get_or_create_conversation($phone, $contact_name, $is_reply, $text);

    if (!$conversation) {
      self::log('ERROR — could not get/create conversation for ' . $phone);
      return;
    }

    self::log('Conversation ID=' . $conversation->id . ' funnel_id=' . $conversation->funnel_id . ' node_id=' . $conversation->node_id);

    if (self::is_user_ai_paused($phone)) {
      self::log('process_incoming_message — AI PAUSED for phone=' . $phone . ', skipping');
      return;
    }

    // Read node config once
    $node_config = self::get_node_config($conversation);
    if (empty($node_config)) {
      $wa_node = self::find_ai_node();
      if ($wa_node && !empty($wa_node['config'])) {
        $node_config = $wa_node['config'];
      }
    }

    // Handle audio: check node-level enable, download, and transcribe
    if ($is_audio) {
      if (empty($node_config['wa_audio_transcription_enabled'])) {
        self::log('process_incoming_message — audio transcription not enabled for this node, skipping');
        return;
      }

      $audio_data = self::download_whatsapp_media($audio_media_id);
      if ($audio_data === false) {
        self::log_always('process_incoming_message — failed to download audio media_id=' . $audio_media_id);
        return;
      }

      // Use node-level provider/language if set, fall back to global settings
      $provider = !empty($node_config['wa_transcription_provider'])
        ? $node_config['wa_transcription_provider']
        : null;
      $language = !empty($node_config['wa_transcription_language'])
        ? $node_config['wa_transcription_language']
        : get_option('pn_customers_manager_transcription_language', '');

      $text = self::transcribe_audio($audio_data, $audio_mime, $language, $provider);
      if (empty($text)) {
        self::log_always('process_incoming_message — audio transcription failed or empty for media_id=' . $audio_media_id);
        return;
      }

      self::log_always('[CONVERSATION] AUDIO TRANSCRIBED: ' . $text);
    }

    self::log('[CONVERSATION] ── INCOMING MESSAGE ──');
    self::log('[CONVERSATION] FROM: ' . $phone . ' (' . $contact_name . ')');

    // Resolve quoted text from reply context
    $messages   = json_decode($conversation->messages, true) ?: [];
    $quoted_text = '';
    if (!empty($quoted_context_id)) {
      $quoted_text = self::find_message_by_wamid($messages, $quoted_context_id);
      if (!empty($quoted_text)) {
        self::log('[CONVERSATION] QUOTING: ' . mb_substr($quoted_text, 0, 200));
      }
    }

    self::log('[CONVERSATION] USER: ' . $text);

    // Add user message to history
    $user_entry = [
      'role'      => 'user',
      'content'   => $text,
      'timestamp' => current_time('mysql'),
    ];
    if (!empty($quoted_text)) {
      $user_entry['quoted_text'] = $quoted_text;
    }
    $messages[] = $user_entry;

    // Skip AI processing if this is just a greeting and welcome message already sent
    $user_message_count = 0;
    $has_assistant_message = false;
    foreach ($messages as $msg) {
      if (isset($msg['role']) && $msg['role'] === 'user') {
        $user_message_count++;
      }
      if (isset($msg['role']) && $msg['role'] === 'assistant') {
        $has_assistant_message = true;
      }
    }

    // If this is the first user message, it's just a simple greeting, and welcome was already sent
    if ($user_message_count === 1 && $has_assistant_message) {
      $normalized_text = mb_strtolower(trim($text));
      $simple_greetings = ['hola', 'holi', 'hi', 'hello', 'hey', 'buenos días', 'buenas tardes', 'buenas noches', 'buenos dias', 'buenas', 'saludos'];

      if (in_array($normalized_text, $simple_greetings, true)) {
        // Just a greeting - welcome message already sent, so don't respond again
        global $wpdb;
        $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';
        $wpdb->update($table, [
          'messages'   => wp_json_encode($messages),
          'updated_at' => current_time('mysql'),
        ], ['id' => $conversation->id]);

        self::log('Skipping AI response — simple greeting detected and welcome message already sent');
        return;
      }
    }

    // Off-hours handling: check before calling AI to save costs
    if (!empty($node_config['wa_schedule_enabled'])) {
      $user_tz = self::resolve_and_store_user_timezone($phone);
      if (!self::is_within_schedule($node_config, $user_tz)) {
        $is_client_initiated = self::is_client_initiated_conversation($messages);

        // Business-initiated conversations: always allow bot to respond.
        // When a worker opened this conversation (sent a template or
        // manual message first), the bot should handle all subsequent
        // client replies regardless of schedule restrictions.
        if (!$is_client_initiated) {
          self::log('Off-hours but business-initiated conversation — allowing bot to respond normally');
          // Fall through to normal processing
        }
        // Option 1: Allow bot when client initiates
        elseif (!empty($node_config['wa_schedule_allow_client_initiated'])) {
          self::log('Off-hours but client initiated conversation — allowing bot to respond normally');
          // Fall through to normal processing
        }
        // Option 2: Send custom off-hours message
        elseif (!empty($node_config['wa_schedule_offhours_message_enabled'])) {
          $offhours_msg = trim($node_config['wa_schedule_offhours_message'] ?? '');
          if (!empty($offhours_msg) && !self::offhours_message_already_sent($messages)) {
            self::log('Off-hours — sending custom off-hours message');
            $messages[] = [
              'role'      => 'assistant',
              'content'   => $offhours_msg,
              'timestamp' => current_time('mysql'),
              'offhours'  => true,
            ];
            self::update_conversation_messages($conversation->id, $messages);
            self::send_whatsapp_message($phone, $offhours_msg);
            return;
          } elseif (self::offhours_message_already_sent($messages)) {
            self::log('Off-hours — custom message already sent, queuing');
          }
          // Queue via existing logic (don't call AI)
          self::update_conversation_messages($conversation->id, $messages);
          return;
        }
        // Neither option: existing behavior (AI processes, then queues)
      }
    }

    $require_postal = !empty($node_config['wa_require_postal_code']);
    self::log('process_incoming_message — require_postal=' . ($require_postal ? 'YES' : 'NO')
      . ' raw_value=' . var_export($node_config['wa_require_postal_code'] ?? null, true)
      . ' node_config_keys=' . implode(',', array_keys($node_config)));

    // Build system prompt with enriched context
    $system_prompt = self::build_enriched_system_prompt($conversation);

    self::log('[CONVERSATION] ── SYSTEM PROMPT (' . mb_strlen($system_prompt) . ' chars) ──');
    self::log('[CONVERSATION] ' . $system_prompt);
    self::log('[CONVERSATION] ── END SYSTEM PROMPT ──');

    // Determine AI model (trait helper)
    $ai_model = self::resolve_ai_model($node_config, $conversation);
    self::sync_ai_model_to_db($ai_model, $conversation);

    $temperature = self::get_conversation_temperature($conversation);

    // Inject quoted context into the last user message for the AI
    $ai_messages = $messages;
    if (!empty($quoted_text)) {
      $last_idx = count($ai_messages) - 1;
      if ($last_idx >= 0 && ($ai_messages[$last_idx]['role'] ?? '') === 'user') {
        $ai_messages[$last_idx]['content'] = '[Quoting: "' . mb_substr($quoted_text, 0, 500) . '"] ' . $ai_messages[$last_idx]['content'];
      }
    }

    $openai_messages = self::build_openai_messages_with_gap_markers($ai_messages, $system_prompt);

    // Log full conversation history sent to AI
    self::log('[CONVERSATION] ── MESSAGE HISTORY (' . count($openai_messages) . ' messages) ──');
    foreach ($openai_messages as $idx => $om) {
      $role = $om['role'] ?? '?';
      $content = is_string($om['content'] ?? null) ? $om['content'] : wp_json_encode($om['content'] ?? '');
      self::log('[CONVERSATION] [' . $idx . '] ' . strtoupper($role) . ': ' . $content);
    }
    self::log('[CONVERSATION] ── END MESSAGE HISTORY ──');

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

      // Call OpenAI
      self::log('[CONVERSATION] ── AI REQUEST ── model=' . $ai_model . ' temperature=' . $temperature . ' provider=' . self::resolve_provider_for_model($ai_model)['label']);
      $ai_response = self::call_openai($openai_messages, $ai_model, $temperature);

      if ($ai_response === false) {
        $ai_response = self::get_error_fallback_message($node_config);
        $is_fallback = true;
        self::log('[CONVERSATION] AI CALL FAILED — using error fallback message');
      } else {
        self::log('[CONVERSATION] ── RAW AI RESPONSE ──');
        self::log('[CONVERSATION] ' . $ai_response);
        self::log('[CONVERSATION] ── END RAW AI RESPONSE ──');
      }
    }

    $sent_images = [];
    if ($is_fallback) {
      // Static error message — skip all post-processing that is meant for
      // model output (postal code enforcement, order detection, image
      // extraction, URL validation, etc.) and send it as-is.
      $ai_response = self::markdown_to_whatsapp($ai_response);
    } else {
      // ── IMAGE DEBUG: raw AI response ──
      self::log_image_debug('[IMG-DEBUG] ====== IMAGE PIPELINE START (model=' . $ai_model . ') ======');
      self::log_image_debug('[IMG-DEBUG] RAW AI response (first 1000 chars): ' . mb_substr($ai_response, 0, 1000));
      $has_think_tags   = (stripos($ai_response, '<think>') !== false);
      $has_image_tags   = (bool) preg_match('/\[PRODUCT_IMAGES:\d+\]/', $ai_response);
      $has_more_tags    = (bool) preg_match('/\[PRODUCT_MORE_IMAGES:\d+\]/', $ai_response);
      $has_md_images    = (bool) preg_match('/!\[[^\]]*\]\([^)]+\)/', $ai_response);
      $has_image_urls   = (bool) preg_match('/https?:\/\/\S+\.(?:jpg|jpeg|png|gif|webp)\b/i', $ai_response);
      self::log_image_debug('[IMG-DEBUG] RAW flags: <think>=' . ($has_think_tags ? 'YES' : 'no')
        . ' [PRODUCT_IMAGES]=' . ($has_image_tags ? 'YES' : 'no')
        . ' [PRODUCT_MORE_IMAGES]=' . ($has_more_tags ? 'YES' : 'no')
        . ' ![md_images]=' . ($has_md_images ? 'YES' : 'no')
        . ' image_URLs=' . ($has_image_urls ? 'YES' : 'no'));

      // Strip DeepSeek <think>…</think> reasoning blocks FIRST so that
      // image tags inside the thinking section are discarded and the
      // thinking text does not reach the customer.
      $ai_response = self::strip_deepseek_think_tags($ai_response);

      // Strip system prompt fragments the model may have leaked
      $ai_response = self::sanitize_system_prompt_leak($ai_response);

      // Remove duplicate greetings if this is not the first message
      $ai_response = self::remove_duplicate_greetings($ai_response, $messages);

      // Enforce postal code rule
      $ai_response = self::enforce_postal_code_rule($ai_response, $messages, $require_postal, $conversation);

      // Convert Markdown formatting to WhatsApp formatting
      $ai_response = self::markdown_to_whatsapp($ai_response);

      // Strip fabricated image URLs
      $pre_strip = $ai_response;
      $ai_response = self::strip_hallucinated_image_urls($ai_response);
      if ($ai_response !== $pre_strip) {
        self::log_image_debug('[IMG-DEBUG] strip_hallucinated_image_urls CHANGED the response — diff length: ' . (strlen($pre_strip) - strlen($ai_response)));
      }

      // ── IMAGE DEBUG: after cleanup, before image processing ──
      $has_image_tags_after = (bool) preg_match('/\[PRODUCT_IMAGES:\d+\]/', $ai_response);
      $has_more_tags_after  = (bool) preg_match('/\[PRODUCT_MORE_IMAGES:\d+\]/', $ai_response);
      self::log_image_debug('[IMG-DEBUG] AFTER CLEANUP: [PRODUCT_IMAGES]=' . ($has_image_tags_after ? 'YES' : 'no')
        . ' [PRODUCT_MORE_IMAGES]=' . ($has_more_tags_after ? 'YES' : 'no'));
      if ($has_image_tags_after) {
        preg_match_all('/\[PRODUCT_IMAGES:(\d+)\]/', $ai_response, $dbg_m);
        self::log_image_debug('[IMG-DEBUG] PRODUCT_IMAGES IDs found: ' . implode(', ', $dbg_m[1]));
      }

      // Validate URLs in the response
      $ai_response = self::validate_response_urls($ai_response);

      // Detect order confirmation tag and send email notification
      $ai_response = self::detect_and_notify_order($ai_response, $conversation, $messages, $node_config);

      // Detect special order tag and forward by email
      $ai_response = self::detect_and_notify_special_order($ai_response, $conversation, $messages, $node_config);
      $ai_response = self::detect_and_notify_escalation($ai_response, $conversation, $messages, $node_config);

      // Extract and process follow-up tags
      $followup_keys = self::extract_followup_tags($ai_response);
      $ai_response   = self::strip_followup_tags($ai_response);
      if (!empty($followup_keys)) {
        self::create_followup_tasks($followup_keys, $phone, $contact_name, $node_config, 'wa_');
      }

      // Collect product IDs whose images were already sent in this
      // conversation so we never send the same photo twice.
      // Separate main-image sends from gallery sends.
      $already_sent_ids      = [];
      $already_sent_more_ids = [];
      foreach ($messages as $msg) {
        if (!empty($msg['images'])) {
          foreach ($msg['images'] as $img) {
            if (!empty($img['product_id'])) {
              $pid = (int) $img['product_id'];
              $already_sent_ids[] = $pid;
              if (!empty($img['type']) && $img['type'] === 'gallery') {
                $already_sent_more_ids[] = $pid;
              }
            }
          }
        }
      }
      $already_sent_ids      = array_unique($already_sent_ids);
      $already_sent_more_ids = array_unique($already_sent_more_ids);

      self::log_image_debug('[IMG-DEBUG] already_sent_ids=' . wp_json_encode($already_sent_ids)
        . ' already_sent_more_ids=' . wp_json_encode($already_sent_more_ids));

      // Auto-inject image tags for products mentioned by URL.
      // When the recommendation protocol is active the AI should control
      // image delivery (Step 3). However GPT-4o-mini often forgets the
      // [PRODUCT_IMAGES:ID] tag when showing a specific product. We
      // auto-inject when the response mentions only 1-2 product URLs
      // (specific showcase) but skip when 3+ URLs appear (listing phase).
      $woo_active = !empty($node_config['wa_include_woo']);
      $recommendations_disabled = !empty($node_config['wa_disable_recommendations']);
      self::log_image_debug('[IMG-DEBUG] AUTO-INJECT check: woo_active=' . ($woo_active ? 'YES' : 'no')
        . ' recommendations_disabled=' . ($recommendations_disabled ? 'YES' : 'no'));

      if (!$woo_active || $recommendations_disabled) {
        self::log_image_debug('[IMG-DEBUG] AUTO-INJECT: running auto_inject (woo inactive or recommendations disabled)');
        $ai_response = self::auto_inject_product_image_tags($ai_response);
      } elseif (!preg_match('/\[PRODUCT_IMAGES:\d+\]/', $ai_response)) {
        // Recommendation mode is active but the AI forgot the image tag.
        // Count product URLs: inject only for 1-2 products (showcase),
        // not for 3+ (listing).
        $site_url      = home_url();
        $product_base  = 'product';
        $permalinks    = (array) get_option('woocommerce_permalinks', []);
        if (!empty($permalinks['product_base'])) {
          $product_base = trim($permalinks['product_base'], '/');
        }
        $url_count = preg_match_all(
          '#' . preg_quote($site_url, '#') . '/' . preg_quote($product_base, '#') . '/[a-z0-9\-]+/?#i',
          $ai_response
        );
        self::log_image_debug('[IMG-DEBUG] AUTO-INJECT: AI forgot image tags, product_url_count=' . $url_count);
        if ($url_count >= 1 && $url_count <= 2) {
          self::log_image_debug('[IMG-DEBUG] AUTO-INJECT: injecting for ' . $url_count . ' product URLs');
          $ai_response = self::auto_inject_product_image_tags($ai_response);
        } else {
          self::log_image_debug('[IMG-DEBUG] AUTO-INJECT: SKIPPED (url_count=' . $url_count . ', need 1-2)');
        }
      } else {
        self::log_image_debug('[IMG-DEBUG] AUTO-INJECT: SKIPPED (AI already included [PRODUCT_IMAGES] tags)');
      }

      // ── IMAGE DEBUG: final state before extraction ──
      $final_img_tags = (bool) preg_match('/\[PRODUCT_IMAGES:\d+\]/', $ai_response);
      self::log_image_debug('[IMG-DEBUG] PRE-EXTRACTION: has [PRODUCT_IMAGES]=' . ($final_img_tags ? 'YES' : 'no'));
      if ($final_img_tags) {
        preg_match_all('/\[PRODUCT_IMAGES:(\d+)\]/', $ai_response, $dbg_final);
        self::log_image_debug('[IMG-DEBUG] PRE-EXTRACTION IDs: ' . implode(', ', $dbg_final[1]));
      }

      // Determine if message should be sent now or queued
      $send_now = self::is_within_schedule($node_config, self::resolve_and_store_user_timezone($phone));

      if ($send_now) {
        // Extract and send product images immediately (before sending text).
        // Pass already-sent IDs so images are not re-sent within the
        // same conversation.
        $ai_response = self::extract_and_send_product_images($ai_response, $phone, $sent_images, $already_sent_ids);

        // Extract and send additional gallery images when user asks for more.
        $ai_response = self::extract_and_send_more_product_images($ai_response, $phone, $sent_images, $already_sent_more_ids);
      } else {
        // Extract image data without sending (will be queued)
        $extracted = self::extract_product_images_data_only($ai_response, $already_sent_ids, $already_sent_more_ids);
        $ai_response   = $extracted['text'];
        $sent_images   = $extracted['images'];
      }

      self::log_image_debug('[IMG-DEBUG] RESULT: sent_images_count=' . count($sent_images)
        . ' sent_images=' . wp_json_encode(array_map(function($img) {
          return ['id' => $img['product_id'] ?? '?', 'name' => $img['product_name'] ?? '?', 'type' => $img['type'] ?? '?'];
        }, $sent_images)));
      self::log_image_debug('[IMG-DEBUG] ====== IMAGE PIPELINE END ======');
    }

    // Add assistant message to history
    $stored_content = $ai_response;
    if (!empty($sent_images)) {
      $image_names = array_unique(array_column($sent_images, 'product_name'));
      $stored_content .= "\n[Se enviaron imágenes de: " . implode(', ', $image_names) . "]";
    }
    $assistant_msg = [
      'role'      => 'assistant',
      'content'   => $stored_content,
      'timestamp' => current_time('mysql'),
      'auto_sent' => true,
    ];
    if (!empty($sent_images)) {
      $assistant_msg['images'] = $sent_images;
    }
    $messages[] = $assistant_msg;

    // Update conversation in DB
    self::update_conversation_messages($conversation->id, $messages);

    // Send or queue response
    if (!isset($send_now)) {
      $send_now = self::is_within_schedule($node_config, self::resolve_and_store_user_timezone($phone));
    }

    if ($send_now) {
      self::log('[CONVERSATION] ── FINAL RESPONSE SENT ──');
      self::log('[CONVERSATION] ' . $ai_response);
      if (!empty($sent_images)) {
        self::log('[CONVERSATION] + Images sent: ' . implode(', ', array_unique(array_column($sent_images, 'product_name'))));
      }
      self::log('[CONVERSATION] ── END ──');
      $wamid = self::send_whatsapp_message($phone, $ai_response);
      if (!empty($wamid) && is_string($wamid)) {
        // Store wamid in the last assistant message for reply context tracking
        $last_idx = count($messages) - 1;
        if ($last_idx >= 0 && ($messages[$last_idx]['role'] ?? '') === 'assistant') {
          $messages[$last_idx]['wamid'] = $wamid;
          self::update_conversation_messages($conversation->id, $messages);
        }
      }
    } else {
      self::log('[CONVERSATION] ── RESPONSE QUEUED ──');
      self::log('[CONVERSATION] ' . $ai_response);
      self::log('[CONVERSATION] ── END ──');
      self::maybe_send_or_queue($phone, $ai_response, $conversation->id, $node_config, $sent_images);
    }

    // Sync to GHL if integration is active
    self::sync_message_to_ghl($phone, $text, $ai_response);
  }

  /* ================================================================
   * MARKDOWN → WHATSAPP FORMATTING
   * ================================================================ */

  /**
   * Convert Markdown formatting from OpenAI to WhatsApp-compatible formatting.
   *
   * @param string $text
   * @return string
   */
  private static function markdown_to_whatsapp($text) {
    // Markdown links [text](url) → text: url
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '$1: $2', $text);

    // Bold: **text** or __text__ → *text*
    $text = preg_replace('/\*\*(.+?)\*\*/', '*$1*', $text);
    $text = preg_replace('/__(.+?)__/', '*$1*', $text);

    // Headings: ### text → *text*
    $text = preg_replace('/^#{1,6}\s+(.+)$/m', '*$1*', $text);

    return $text;
  }

  /* ================================================================
   * WHATSAPP CLOUD API
   * ================================================================ */

  /**
   * Send a text message via WhatsApp Cloud API.
   *
   * @param string $to    Recipient phone number.
   * @param string $text  Message text.
   * @return string|false  The wamid of the sent message on success, or false on failure.
   */
  private static function send_whatsapp_message($to, $text) {
    $access_token   = get_option('pn_customers_manager_whatsapp_access_token', '');
    $phone_id       = get_option('pn_customers_manager_whatsapp_phone_number_id', '');

    if (empty($access_token) || empty($phone_id)) {
      self::log('send_whatsapp_message — missing token or phone_id');
      return false;
    }

    $url = 'https://graph.facebook.com/v21.0/' . $phone_id . '/messages';

    $payload = [
      'messaging_product' => 'whatsapp',
      'recipient_type'    => 'individual',
      'to'                => $to,
      'type'              => 'text',
      'text'              => [
        'preview_url' => false,
        'body'        => $text,
      ],
    ];

    self::log('send_whatsapp_message — to=' . $to . ' payload=' . wp_json_encode($payload));

    $response = wp_remote_post($url, [
      'timeout' => 30,
      'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
      ],
      'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
      self::log('send_whatsapp_message — WP_Error: ' . $response->get_error_message());
      return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    self::log('send_whatsapp_message — response code=' . $code . ' body=' . $body);

    if ($code < 200 || $code >= 300) {
      return false;
    }

    // Extract wamid from Meta's response
    $data = json_decode($body, true);
    $wamid = isset($data['messages'][0]['id']) ? $data['messages'][0]['id'] : '';

    return $wamid ?: true;
  }

  /**
   * Send a WhatsApp template message via the Meta Cloud API.
   *
   * @param string $to            Recipient phone number.
   * @param string $template_name Template name as registered in Meta Business Suite.
   * @param string $language_code BCP 47 language code (e.g. es, en, ca).
   * @param array  $components    Optional template components for variable substitution.
   * @return string|false The wamid on success, or false on failure.
   */
  public static function send_whatsapp_template($to, $template_name, $language_code, $components = []) {
    $access_token = get_option('pn_customers_manager_whatsapp_access_token', '');
    $phone_id     = get_option('pn_customers_manager_whatsapp_phone_number_id', '');

    if (empty($access_token) || empty($phone_id)) {
      self::log('send_whatsapp_template — missing token or phone_id');
      return false;
    }

    $url = 'https://graph.facebook.com/v21.0/' . $phone_id . '/messages';

    $template_data = [
      'name'     => $template_name,
      'language' => [
        'code' => $language_code,
      ],
    ];

    if (!empty($components)) {
      $template_data['components'] = $components;
    }

    $payload = [
      'messaging_product' => 'whatsapp',
      'recipient_type'    => 'individual',
      'to'                => $to,
      'type'              => 'template',
      'template'          => $template_data,
    ];

    self::log('send_whatsapp_template — to=' . $to . ' template=' . $template_name . ' lang=' . $language_code);

    $response = wp_remote_post($url, [
      'timeout' => 30,
      'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
      ],
      'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
      self::log('send_whatsapp_template — WP_Error: ' . $response->get_error_message());
      return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    self::log('send_whatsapp_template — response code=' . $code . ' body=' . $body);

    if ($code < 200 || $code >= 300) {
      return false;
    }

    $data  = json_decode($body, true);
    $wamid = isset($data['messages'][0]['id']) ? $data['messages'][0]['id'] : '';

    return $wamid ?: true;
  }

  /**
   * Helper: check if a configuration flag is truthy (boolean true, 'on', '1', 1, 'true', 'yes').
   *
   * @param mixed $value
   * @return bool
   */
  public static function is_truthy($value) {
    if (is_bool($value)) {
      return $value;
    }
    if (is_string($value)) {
      $v = strtolower(trim($value));
      return in_array($v, ['on', '1', 'true', 'yes'], true);
    }
    if (is_numeric($value)) {
      return intval($value) > 0;
    }
    return !empty($value);
  }

  /**
   * Helper: check if Meta message templates are enabled in a node config.
   *
   * Checks both wa_meta_templates_enabled and whether templates are configured.
   *
   * @param array $config
   * @return bool
   */
  public static function is_meta_templates_enabled($config) {
    if (empty($config) || !is_array($config)) {
      return false;
    }
    $flag = $config['wa_meta_templates_enabled'] ?? false;
    if (self::is_truthy($flag)) {
      return true;
    }
    // Check if multi-templates array is defined and has non-empty entries
    if (!empty($config['wa_meta_template_name'])) {
      $names = (array) $config['wa_meta_template_name'];
      foreach ($names as $n) {
        if (!empty(trim((string) $n))) {
          return true;
        }
      }
    }
    // Legacy single template check
    $legacy_flag = $config['wa_template_enabled'] ?? false;
    if (self::is_truthy($legacy_flag) && !empty($config['wa_template_name'])) {
      return true;
    }
    return false;
  }

  /**
   * Initiate a WhatsApp conversation with template message(s) for a new lead.
   *
   * Supports both the legacy single-template config (wa_template_enabled /
   * wa_template_name / wa_template_language) and the new multi-template system
   * (wa_meta_templates_enabled + parallel arrays). When the new config is
   * present it takes precedence; otherwise falls back to the legacy fields.
   *
   * @param string $phone        Recipient phone number.
   * @param string $contact_name Optional contact name.
   * @return bool True on success (at least one template sent or scheduled), false otherwise.
   */
  public static function initiate_conversation_with_template($phone, $contact_name = '') {
    $node_data = self::find_ai_node();
    if (!$node_data) {
      self::log('[TEMPLATE] No WhatsApp AI node found');
      return false;
    }

    $config    = $node_data['config'];
    $funnel_id = $node_data['funnel_id'];
    $node_id   = $node_data['node_id'];

    // ── Determine which template system to use ──
    $use_multi = self::is_meta_templates_enabled($config);

    if ($use_multi) {
      return self::initiate_multi_templates($phone, $contact_name, $config, $funnel_id, $node_id);
    }

    // ── Legacy single-template fallback ──
    $enabled       = !empty($config['wa_template_enabled']) && self::is_truthy($config['wa_template_enabled']);
    $template_name = !empty($config['wa_template_name']) ? $config['wa_template_name'] : '';
    $language_code = !empty($config['wa_template_language']) ? $config['wa_template_language'] : '';

    if (!$enabled || empty($template_name) || empty($language_code)) {
      self::log('[TEMPLATE] Template not enabled or missing name/language');
      return false;
    }

    return self::send_and_create_conversation($phone, $contact_name, $template_name, $language_code, $config, $funnel_id, $node_id);
  }

  /**
   * Handle multi-template initiation for a new lead.
   *
   * Iterates over the configured templates and dispatches each according to
   * its trigger: on_registration (immediate), after_delay (scheduled),
   * on_inactivity (scheduled with response check), manual (skip).
   *
   * @param string $phone        Recipient phone number.
   * @param string $contact_name Contact name.
   * @param array  $config       Node configuration.
   * @param int    $funnel_id    Funnel post ID.
   * @param string $node_id      Node identifier.
   * @return bool True if at least one template was sent or scheduled.
   */
  private static function initiate_multi_templates($phone, $contact_name, $config, $funnel_id, $node_id) {
    $names        = isset($config['wa_meta_template_name'])        ? (array) $config['wa_meta_template_name']        : [];
    $languages    = isset($config['wa_meta_template_language'])    ? (array) $config['wa_meta_template_language']    : [];
    $triggers     = isset($config['wa_meta_template_trigger'])     ? (array) $config['wa_meta_template_trigger']     : [];
    $delays       = isset($config['wa_meta_template_delay'])       ? (array) $config['wa_meta_template_delay']       : [];
    $delay_units  = isset($config['wa_meta_template_delay_unit'])  ? (array) $config['wa_meta_template_delay_unit']  : [];
    $use_vars     = isset($config['wa_meta_template_use_vars'])    ? (array) $config['wa_meta_template_use_vars']    : [];
    $vars_mode    = isset($config['wa_meta_template_vars_mode'])   ? (array) $config['wa_meta_template_vars_mode']   : [];
    $vars_config  = isset($config['wa_meta_template_vars_config']) ? (array) $config['wa_meta_template_vars_config'] : [];
    $descriptions = isset($config['wa_meta_template_description']) ? (array) $config['wa_meta_template_description'] : [];

    if (empty($names)) {
      self::log('[TEMPLATE] Multi-template enabled but no templates configured');
      return false;
    }

    $count                = count($names);
    $any_sent             = false;
    $any_scheduled        = false;
    $conversation_created = false;

    for ($i = 0; $i < $count; $i++) {
      $tpl_name   = isset($names[$i])        ? trim($names[$i])        : '';
      $tpl_lang   = isset($languages[$i])    ? trim($languages[$i])    : '';
      $trigger    = isset($triggers[$i])     ? trim($triggers[$i])     : 'manual';
      $delay      = isset($delays[$i])       ? intval($delays[$i])     : 0;
      $delay_unit = isset($delay_units[$i])  ? trim($delay_units[$i])  : 'minutes';
      $tpl_desc   = isset($descriptions[$i]) ? trim($descriptions[$i]) : '';

      // Parse variables for this template row
      $use_vars_enabled = isset($use_vars[$i]) && self::is_truthy($use_vars[$i]);
      $tpl_vars = [];
      if ($use_vars_enabled) {
        $mode        = isset($vars_mode[$i])   ? trim($vars_mode[$i])   : 'numbers';
        $config_text = isset($vars_config[$i]) ? trim($vars_config[$i]) : '';
        $tpl_vars    = self::parse_template_variables($config_text, $mode);
      }

      if (empty($tpl_name) || empty($tpl_lang)) {
        self::log('[TEMPLATE] Skipping template row ' . $i . ' — missing name or language');
        continue;
      }

      switch ($trigger) {
        case 'on_registration':
          // Send immediately and create conversation if not yet created.
          if (!$conversation_created) {
            $result = self::send_and_create_conversation($phone, $contact_name, $tpl_name, $tpl_lang, $config, $funnel_id, $node_id, $tpl_vars, $tpl_desc);
            if ($result) {
              $conversation_created = true;
              $any_sent             = true;
            }
          } else {
            // Conversation exists — just send the template.
            $components = [];
            if (!empty($tpl_vars)) {
              $values     = self::get_user_variable_values($phone, $tpl_vars);
              $components = self::build_template_components($values);
            }
            $wamid = self::send_whatsapp_template($phone, $tpl_name, $tpl_lang, $components);
            if ($wamid) {
              self::log_always('[CONVERSATION] TEMPLATE to ' . $phone . ': ' . $tpl_name . ' (' . $tpl_lang . ')');
              self::append_template_to_conversation($phone, $tpl_name, is_string($wamid) ? $wamid : '', !empty($tpl_desc) ? $tpl_desc : '[Template: ' . $tpl_name . ']');
              $any_sent = true;
            }
          }
          break;

        case 'after_delay':
        case 'on_inactivity':
          $delay_seconds = self::compute_delay_seconds($delay, $delay_unit);
          if ($delay_seconds < 1) {
            $delay_seconds = 60; // Minimum 1 minute fallback.
          }
          $scheduled_at = time();
          $event_time   = $scheduled_at + $delay_seconds;
          $event_args   = [$phone, $tpl_name, $tpl_lang, $contact_name, $trigger, $scheduled_at, $tpl_vars, $tpl_desc];

          // Always store in our own queue (primary). This is reliable regardless
          // of WP Cron health. The queue is processed on every webhook, init hook,
          // and GHL/Zoho webhook — so delivery is near-instant once fire_at arrives.
          self::queue_pending_template($event_time, $event_args);

          // Also try WP Cron as a secondary trigger, but don't depend on it.
          $scheduled_ok = wp_schedule_single_event($event_time, 'pn_cm_send_scheduled_wa_template', $event_args, true);
          if (is_wp_error($scheduled_ok) || $scheduled_ok === false) {
            self::log('[TEMPLATE] Queued template "' . $tpl_name . '" for ' . $phone . ' in ' . $delay . ' ' . $delay_unit . ' (fires at ' . wp_date('Y-m-d H:i:s', $event_time) . ') — WP Cron unavailable, queue-only');
          } else {
            self::log('[TEMPLATE] Queued + cron-scheduled template "' . $tpl_name . '" for ' . $phone . ' in ' . $delay . ' ' . $delay_unit . ' (fires at ' . wp_date('Y-m-d H:i:s', $event_time) . ')');
          }
          $any_sent      = true;
          $any_scheduled = true;
          break;

        case 'manual':
        default:
          self::log('[TEMPLATE] Skipping manual template "' . $tpl_name . '" for ' . $phone);
          break;
      }
    }

    // If no immediate template was sent but we scheduled some, create the
    // conversation record if one does not already exist so the scheduled templates have context.
    if ($any_sent && !$conversation_created) {
      global $wpdb;
      $table    = $wpdb->prefix . 'pn_cm_whatsapp_conversations';
      $phone_norm = ltrim($phone, '+');
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE REPLACE(phone_number, '+', '') = %s AND status = 'active' LIMIT 1",
        $phone_norm
      ));
      if (!$existing) {
        $first_desc = !empty($descriptions[0]) ? trim($descriptions[0]) : (!empty($config['wa_welcome_message']) ? self::strip_html_to_plain_text($config['wa_welcome_message']) : '[Scheduled templates]');
        self::create_conversation_record($phone, $contact_name, $first_desc, '', $config, $funnel_id, $node_id, true);
      }
    }

    // Ensure scheduled templates fire on time by triggering WP Cron.
    // This is critical in webhook-driven flows where there may be no front-end
    // page visits to trigger WP Cron naturally (wp-cron.php).
    // When DISABLE_WP_CRON is true, the site relies on a real system cron
    // (crontab), which typically runs every minute — no action needed.
    if ($any_scheduled) {
      spawn_cron();
    }

    return $any_sent;
  }

  /**
   * Convert a delay amount + unit into seconds.
   *
   * @param int    $amount Delay amount.
   * @param string $unit   One of: minutes, hours, days.
   * @return int Delay in seconds.
   */
  private static function compute_delay_seconds($amount, $unit) {
    $amount = max(0, intval($amount));
    switch ($unit) {
      case 'hours':
        return $amount * HOUR_IN_SECONDS;
      case 'days':
        return $amount * DAY_IN_SECONDS;
      case 'minutes':
      default:
        return $amount * MINUTE_IN_SECONDS;
    }
  }

  /**
   * Send a template and create the conversation record.
   *
   * Shared helper used by both legacy single-template and multi-template flows.
   *
   * @param string $phone             Recipient phone number.
   * @param string $contact_name      Contact name.
   * @param string $template_name     Template name.
   * @param string $language_code     Language code.
   * @param array  $config            Node configuration.
   * @param int    $funnel_id         Funnel post ID.
   * @param string $node_id           Node identifier.
   * @param array  $template_variables Personalization variables.
   * @param string $template_desc     Template description or preview text.
   * @return bool True on success.
   */
  private static function send_and_create_conversation($phone, $contact_name, $template_name, $language_code, $config, $funnel_id, $node_id, $template_variables = [], $template_desc = '') {
    global $wpdb;
    $table    = $wpdb->prefix . 'pn_cm_whatsapp_conversations';
    $phone_norm = ltrim($phone, '+');
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT id FROM {$table} WHERE REPLACE(phone_number, '+', '') = %s AND status = 'active' LIMIT 1",
      $phone_norm
    ));

    // Build components from template variables if configured.
    $components = [];
    if (!empty($template_variables) && is_array($template_variables)) {
      $values     = self::get_user_variable_values($phone, $template_variables);
      $components = self::build_template_components($values);
    }

    $first_content = !empty($template_desc) ? $template_desc : (!empty($config['wa_welcome_message']) ? self::strip_html_to_plain_text($config['wa_welcome_message']) : '[Template: ' . $template_name . ']');

    if ($existing) {
      self::log('[TEMPLATE] Active conversation #' . $existing . ' already exists for ' . $phone . ' — sending template and appending to conversation');
      $wamid = self::send_whatsapp_template($phone, $template_name, $language_code, $components);
      if ($wamid) {
        self::log_always('[CONVERSATION] TEMPLATE to ' . $phone . ': ' . $template_name . ' (' . $language_code . ')');
        self::append_template_to_conversation($phone, $template_name, is_string($wamid) ? $wamid : '', $first_content);
        return true;
      }
      return false;
    }

    // Send the template message.
    $wamid = self::send_whatsapp_template($phone, $template_name, $language_code, $components);
    if (!$wamid) {
      self::log('[TEMPLATE] Failed to send template to ' . $phone);
      return false;
    }

    return self::create_conversation_record($phone, $contact_name, $first_content, is_string($wamid) ? $wamid : '', $config, $funnel_id, $node_id, false);
  }

  /**
   * Create a conversation record in the database.
   *
   * @param string $phone            Recipient phone number.
   * @param string $contact_name     Contact name.
   * @param string $first_content    First message content.
   * @param string $wamid            WhatsApp message ID (empty if not yet sent).
   * @param array  $config           Node configuration.
   * @param int    $funnel_id        Funnel post ID.
   * @param string $node_id          Node identifier.
   * @param bool   $is_scheduled_only True if the template was only scheduled, not sent yet.
   * @return bool
   */
  private static function create_conversation_record($phone, $contact_name, $first_content, $wamid, $config, $funnel_id, $node_id, $is_scheduled_only = false) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    // Normalize phone: strip leading '+' for consistent storage.
    $phone = ltrim($phone, '+');

    $messages   = [];
    $messages[] = [
      'role'      => 'assistant',
      'content'   => $first_content,
      'wamid'     => $wamid,
      'timestamp' => current_time('mysql'),
      'auto_sent' => true,
      'pending'   => $is_scheduled_only, // True if template is scheduled but not yet sent.
    ];

    // Resolve system prompt and AI model from node config or global defaults.
    $system_prompt = !empty($config['wa_system_prompt'])
      ? $config['wa_system_prompt']
      : get_option('pn_customers_manager_whatsapp_system_prompt', '');
    $ai_model = !empty($config['wa_ai_model'])
      ? $config['wa_ai_model']
      : get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');

    $wpdb->insert($table, [
      'phone_number'  => $phone,
      'contact_name'  => $contact_name,
      'funnel_id'     => $funnel_id,
      'node_id'       => $node_id,
      'messages'      => wp_json_encode($messages),
      'system_prompt' => $system_prompt,
      'ai_model'      => $ai_model,
      'status'        => 'active',
      'created_at'    => current_time('mysql'),
      'updated_at'    => current_time('mysql'),
    ]);

    $id = $wpdb->insert_id;
    if (!$id) {
      self::log('[TEMPLATE] Failed to create conversation for ' . $phone);
      return false;
    }

    if ($is_scheduled_only) {
      self::log_always('[CONVERSATION] ── TEMPLATE SCHEDULED (pending delivery) ──');
      self::log_always('[CONVERSATION] TEMPLATE PENDING for ' . $phone . ': ' . $first_content);
    } else {
      self::log_always('[CONVERSATION] ── TEMPLATE SENT ──');
      self::log_always('[CONVERSATION] TEMPLATE to ' . $phone . ': ' . $first_content);
    }

    return true;
  }

  /**
   * Parse template variables from config JSON.
   *
   * Format: JSON array of objects: [{"key":"1","field":"display_name"},{"key":"nombre","field":"display_name"}]
   * - Numbers mode: keys are numbers "1", "2", "3" (sorted numerically)
   * - Names mode: keys are custom names (order preserved)
   *
   * @param string $config_json  JSON string with variables config.
   * @param string $mode         Mode: 'numbers' or 'names'.
   * @return array Array of WP field names to extract values from, in order.
   */
  private static function parse_template_variables($config_json, $mode = 'numbers') {
    if (empty($config_json)) {
      return [];
    }

    $vars_data = json_decode($config_json, true);
    if (!is_array($vars_data)) {
      return [];
    }

    $mapping = [];
    $ordered_fields = [];

    // For numbers mode, we need to sort by numeric key
    if ($mode === 'numbers') {
      $temp = [];
      foreach ($vars_data as $var) {
        if (empty($var['key']) || empty($var['field'])) {
          continue;
        }
        $numeric_key = intval($var['key']);
        $temp[$numeric_key] = $var['field'];
        $mapping[$var['key']] = $var['field'];
      }
      ksort($temp);
      $ordered_fields = array_values($temp);
    } else {
      // For names mode, preserve the order in the array
      foreach ($vars_data as $var) {
        if (empty($var['key']) || empty($var['field'])) {
          continue;
        }
        $ordered_fields[] = $var['field'];
        $mapping[$var['key']] = $var['field'];
      }
    }

    self::log('[TEMPLATE VARS] Mode: ' . $mode . ', Mapping: ' . wp_json_encode($mapping) . ', Ordered: ' . implode(', ', $ordered_fields));

    return $ordered_fields;
  }

  /**
   * Get user data values for template variables.
   *
   * @param string $phone     Phone number to find the user.
   * @param array  $variables Array of variable keys (display_name, first_name, etc.).
   * @return array Array of values in the same order as $variables.
   */
  private static function get_user_variable_values($phone, $variables) {
    if (empty($variables) || !is_array($variables)) {
      return [];
    }

    // Find user by phone number.
    global $wpdb;
    $user_id = $wpdb->get_var($wpdb->prepare(
      "SELECT user_id FROM {$wpdb->usermeta}
       WHERE meta_key = 'phone'
       AND REPLACE(REPLACE(REPLACE(meta_value, '+', ''), ' ', ''), '-', '') LIKE %s
       LIMIT 1",
      '%' . $wpdb->esc_like(preg_replace('/[^\d]/', '', $phone)) . '%'
    ));

    if (!$user_id) {
      self::log('[TEMPLATE VARS] User not found for phone: ' . $phone);
      return array_fill(0, count($variables), '');
    }

    $user = get_userdata($user_id);
    if (!$user) {
      return array_fill(0, count($variables), '');
    }

    $values = [];
    foreach ($variables as $var_key) {
      switch ($var_key) {
        case 'display_name':
          $values[] = $user->display_name;
          break;
        case 'first_name':
          $values[] = $user->first_name;
          break;
        case 'last_name':
          $values[] = $user->last_name;
          break;
        case 'email':
          $values[] = $user->user_email;
          break;
        case 'phone':
          $values[] = get_user_meta($user_id, 'phone', true);
          break;
        case 'user_login':
          $values[] = $user->user_login;
          break;
        default:
          $values[] = '';
          break;
      }
    }

    self::log('[TEMPLATE VARS] Resolved for phone ' . $phone . ': ' . wp_json_encode(array_combine($variables, $values)));
    return $values;
  }

  /**
   * Build template components array from variable values.
   *
   * @param array $values Array of variable values.
   * @return array Components array for WhatsApp API.
   */
  private static function build_template_components($values) {
    if (empty($values)) {
      return [];
    }

    $parameters = [];
    foreach ($values as $value) {
      $parameters[] = [
        'type' => 'text',
        'text' => (string) $value,
      ];
    }

    return [
      [
        'type'       => 'body',
        'parameters' => $parameters,
      ],
    ];
  }

  /**
   * Handle a scheduled template send (cron callback).
   *
   * Hooked to `pn_cm_send_scheduled_wa_template`. For `after_delay` triggers,
   * sends the template immediately. For `on_inactivity` triggers, checks
   * whether the client has shown any activity since the template was scheduled
   * (WhatsApp messages, conversation updates, GHL/CRM actions, etc.);
   * if the client was active, the template is skipped.
   *
   * @param string $phone              Recipient phone number.
   * @param string $template_name      Template name.
   * @param string $language_code      Language code.
   * @param string $contact_name       Contact name.
   * @param string $trigger_type       Trigger type (after_delay or on_inactivity).
   * @param int    $scheduled_at       Unix timestamp when the template was scheduled (0 for legacy).
   * @param array  $template_variables Array of variable keys for template personalization.
   */
  public static function send_scheduled_template($phone, $template_name, $language_code, $contact_name, $trigger_type, $scheduled_at = 0, $template_variables = [], $template_desc = '') {
    self::log('[TEMPLATE] Scheduled template firing: trigger=' . $trigger_type . ' template=' . $template_name . ' phone=' . $phone);

    // Deduplication: since templates are stored in both WP Cron and the fallback
    // queue, prevent sending the same template twice. Use a short-lived transient
    // keyed on phone + template + scheduled_at.
    $dedup_key = 'pn_cm_tpl_sent_' . md5($phone . $template_name . intval($scheduled_at));
    if (get_transient($dedup_key)) {
      self::log('[TEMPLATE] Skipping duplicate send for "' . $template_name . '" to ' . $phone . ' (already sent)');
      return;
    }
    set_transient($dedup_key, 1, 3600); // 1 hour dedup window.

    if ($trigger_type === 'on_inactivity') {
      // Build a threshold timestamp in the same format as current_time('mysql')
      // used by the conversations table (site-local timezone).
      $scheduled_at = intval($scheduled_at);
      $threshold_time = $scheduled_at > 0
        ? wp_date('Y-m-d H:i:s', $scheduled_at)
        : '';

      global $wpdb;
      $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';
      $phone_normalized = ltrim($phone, '+');
      $row   = $wpdb->get_row($wpdb->prepare(
        "SELECT messages, updated_at FROM {$table} WHERE REPLACE(phone_number, '+', '') = %s AND status = 'active' ORDER BY updated_at DESC LIMIT 1",
        $phone_normalized
      ));

      if ($row) {
        if ($threshold_time) {
          // Check for user messages sent AFTER the template was scheduled.
          // This detects WhatsApp replies from the client.
          $messages = !empty($row->messages) ? json_decode($row->messages, true) : [];
          if (is_array($messages)) {
            foreach ($messages as $msg) {
              if (!isset($msg['role']) || $msg['role'] !== 'user') {
                continue;
              }
              $msg_time = isset($msg['timestamp']) ? $msg['timestamp'] : '';
              if ($msg_time && $msg_time > $threshold_time) {
                self::log('[TEMPLATE] Client ' . $phone . ' sent a WA message after scheduling — skipping on_inactivity template "' . $template_name . '"');
                return;
              }
            }
          }

          // Check the WP user registration or modification date for the
          // associated phone number. This detects external activity such as
          // GHL webhook sign-ups, CRM contact updates, Zoho sync, etc.
          $user = get_users([
            'meta_key'   => 'pn_cm_phone',
            'meta_value' => $phone,
            'number'     => 1,
          ]);
          if (!empty($user)) {
            $wp_user = $user[0];
            // user_registered is stored in UTC; convert threshold to UTC for comparison.
            $threshold_utc = $scheduled_at > 0 ? gmdate('Y-m-d H:i:s', $scheduled_at) : '';
            if ($threshold_utc && $wp_user->user_registered > $threshold_utc) {
              self::log('[TEMPLATE] Client ' . $phone . ' was registered/updated after scheduling (' . $wp_user->user_registered . ') — skipping on_inactivity template "' . $template_name . '"');
              return;
            }
            // Also check profile last-modified meta (set by GHL, Zoho, CRM, etc.)
            $profile_modified = get_user_meta($wp_user->ID, 'pn_cm_profile_updated', true);
            if ($profile_modified && $profile_modified > $threshold_time) {
              self::log('[TEMPLATE] Client ' . $phone . ' has recent CRM activity (' . $profile_modified . ') — skipping on_inactivity template "' . $template_name . '"');
              return;
            }
          }
        } else {
          // Legacy fallback (no $scheduled_at): skip if any user message exists.
          $messages = !empty($row->messages) ? json_decode($row->messages, true) : [];
          if (is_array($messages)) {
            foreach ($messages as $msg) {
              if (isset($msg['role']) && $msg['role'] === 'user') {
                self::log('[TEMPLATE] Client ' . $phone . ' has responded — skipping on_inactivity template "' . $template_name . '"');
                return;
              }
            }
          }
        }
      }
    }

    // Build components from template variables if configured.
    $components = [];
    if (!empty($template_variables) && is_array($template_variables)) {
      $values = self::get_user_variable_values($phone, $template_variables);
      $components = self::build_template_components($values);
    }

    // Send the template.
    $wamid = self::send_whatsapp_template($phone, $template_name, $language_code, $components);
    if ($wamid) {
      self::log_always('[CONVERSATION] SCHEDULED TEMPLATE to ' . $phone . ': ' . $template_name . ' (' . $language_code . ') trigger=' . $trigger_type);
      $content = !empty($template_desc) ? $template_desc : '[Template: ' . $template_name . ']';
      self::append_template_to_conversation($phone, $template_name, is_string($wamid) ? $wamid : '', $content);
    } else {
      self::log('[TEMPLATE] Failed to send scheduled template "' . $template_name . '" to ' . $phone);
    }
  }

  /**
   * Process any overdue scheduled template events directly.
   *
   * This acts as a fallback for WP Cron: if DISABLE_WP_CRON is true, or if
   * the site has low traffic and WP Cron never fires, this method manually
   * checks the cron array and fires any past-due template events.
   *
   * Call this from webhook handlers and other entry points to ensure
   * templates are sent even when WP Cron is unreliable.
   */
  public static function process_pending_template_events() {
    $fired = 0;

    // 1. Process overdue WP Cron events.
    $crons = _get_cron_array();
    if (is_array($crons) && !empty($crons)) {
      $gmt_time = microtime(true);
      $hook     = 'pn_cm_send_scheduled_wa_template';

      foreach ($crons as $timestamp => $cronhooks) {
        if ($timestamp > $gmt_time) {
          break;
        }
        if (!isset($cronhooks[$hook])) {
          continue;
        }

        foreach ($cronhooks[$hook] as $hash => $event) {
          wp_unschedule_event($timestamp, $hook, $event['args']);
          do_action_ref_array($hook, $event['args']);
          $fired++;
        }
      }
    }

    // 2. Process our fallback queue (templates that wp_schedule_single_event refused).
    $queue = get_option('pn_cm_wa_pending_templates', []);
    if (is_array($queue) && !empty($queue)) {
      $now       = time();
      $remaining = [];

      foreach ($queue as $item) {
        $fire_at = isset($item['fire_at']) ? (int) $item['fire_at'] : 0;
        $args    = isset($item['args'])    ? $item['args']          : [];

        if ($fire_at <= $now && !empty($args)) {
          self::log('[TEMPLATE] Firing fallback-queued template for phone=' . ($args[0] ?? '?'));
          call_user_func_array([__CLASS__, 'send_scheduled_template'], $args);
          $fired++;
        } else {
          $remaining[] = $item;
        }
      }

      if (count($remaining) !== count($queue)) {
        update_option('pn_cm_wa_pending_templates', $remaining, false);
      }
    }

    if ($fired > 0) {
      self::log('[TEMPLATE] process_pending_template_events fired ' . $fired . ' overdue event(s)');
    }
  }

  /**
   * Store a template in our fallback queue when wp_schedule_single_event fails.
   *
   * @param int   $fire_at   Unix timestamp when the template should fire.
   * @param array $args      Arguments for send_scheduled_template().
   */
  private static function queue_pending_template($fire_at, $args) {
    $queue = get_option('pn_cm_wa_pending_templates', []);
    if (!is_array($queue)) {
      $queue = [];
    }

    $queue[] = [
      'fire_at' => (int) $fire_at,
      'args'    => $args,
    ];

    update_option('pn_cm_wa_pending_templates', $queue, false);
    self::log('[TEMPLATE] Stored in fallback queue — fire_at=' . wp_date('Y-m-d H:i:s', $fire_at) . ' phone=' . ($args[0] ?? '?'));
  }

  /**
   * Append or update a sent template message in an existing conversation record.
   *
   * @param string $phone
   * @param string $template_name
   * @param string $wamid
   * @param string $content
   * @return bool
   */
  private static function append_template_to_conversation($phone, $template_name, $wamid = '', $content = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';
    $phone_normalized = ltrim($phone, '+');
    $conv = $wpdb->get_row($wpdb->prepare(
      "SELECT id, messages FROM {$table} WHERE REPLACE(phone_number, '+', '') = %s AND status = 'active' ORDER BY id DESC LIMIT 1",
      $phone_normalized
    ));
    if (!$conv) {
      return false;
    }
    $msgs = !empty($conv->messages) ? json_decode($conv->messages, true) : [];
    if (!is_array($msgs)) {
      $msgs = [];
    }
    $text = !empty($content) ? $content : '[Template: ' . $template_name . ']';

    // If there is an existing placeholder/pending message without a wamid, replace it.
    // This handles messages created when the template was scheduled but not yet sent.
    $updated = false;
    foreach ($msgs as $idx => $m) {
      if (empty($m['wamid']) && isset($m['role']) && $m['role'] === 'assistant') {
        $is_placeholder = (empty($m['content']) || strpos($m['content'], '[Scheduled') !== false);
        $is_pending     = (!empty($m['pending']));
        $is_auto        = (!empty($m['auto_sent']));
        if ($is_placeholder || $is_pending || $is_auto) {
          $msgs[$idx]['content']   = $text;
          $msgs[$idx]['wamid']     = $wamid;
          $msgs[$idx]['timestamp'] = current_time('mysql');
          $msgs[$idx]['pending']   = false;
          $updated = true;
          break;
        }
      }
    }
    if (!$updated) {
      $msgs[] = [
        'role'      => 'assistant',
        'content'   => $text,
        'wamid'     => $wamid,
        'timestamp' => current_time('mysql'),
        'auto_sent' => true,
      ];
    }
    self::update_conversation_messages($conv->id, $msgs);
    return true;
  }

  /**
   * Find a message in conversation history by its WhatsApp message ID (wamid).
   *
   * @param array  $messages Conversation message history.
   * @param string $wamid    The WhatsApp message ID to search for.
   * @return string The message content, or empty string if not found.
   */
  private static function find_message_by_wamid($messages, $wamid) {
    if (empty($wamid) || empty($messages)) {
      return '';
    }
    foreach ($messages as $msg) {
      if (isset($msg['wamid']) && $msg['wamid'] === $wamid && !empty($msg['content'])) {
        return $msg['content'];
      }
    }
    return '';
  }

  /**
   * Sync a WhatsApp message exchange to GHL as a contact note.
   *
   * @param string $phone       Customer phone number.
   * @param string $user_text   The customer's message.
   * @param string $ai_response The AI's response.
   */
  private static function sync_message_to_ghl($phone, $user_text, $ai_response) {
    if (!class_exists('PN_CUSTOMERS_MANAGER_GHL')) {
      return;
    }
    if (empty(get_option('pn_customers_manager_ghl_api_key', ''))) {
      return;
    }

    $ghl_id = PN_CUSTOMERS_MANAGER_GHL::find_ghl_contact_id_by_phone($phone);
    if (empty($ghl_id)) {
      return;
    }

    $wa_sync     = get_option('pn_customers_manager_ghl_wa_sync', '') === 'on';
    $provider_id = get_option('pn_customers_manager_ghl_conversation_provider_id', '');

    if ($wa_sync && !empty($provider_id)) {
      // Use Conversations API — bypasses sync_direction check by design.
      PN_CUSTOMERS_MANAGER_GHL::add_conversation_message($ghl_id, $user_text, 'inbound');
      if (!empty($ai_response)) {
        PN_CUSTOMERS_MANAGER_GHL::add_conversation_message($ghl_id, $ai_response, 'outbound');
      }
    } else {
      // Fallback to notes.
      $note = "WhatsApp\n\n"
        . "Customer:\n" . $user_text . "\n\n"
        . "AI Response:\n" . $ai_response;
      PN_CUSTOMERS_MANAGER_GHL::create_note($ghl_id, $note);
    }
  }

  /**
   * Send an image message via WhatsApp Cloud API.
   *
   * @param string $to        Recipient phone number.
   * @param string $image_url Public URL of the image.
   * @param string $caption   Optional caption text.
   * @return bool
   */
  private static function send_whatsapp_image($to, $image_url, $caption = '') {
    $access_token = get_option('pn_customers_manager_whatsapp_access_token', '');
    $phone_id     = get_option('pn_customers_manager_whatsapp_phone_number_id', '');

    if (empty($access_token) || empty($phone_id)) {
      self::log('send_whatsapp_image — missing token or phone_id');
      return false;
    }

    $media_id = self::upload_media_to_whatsapp($image_url, $access_token, $phone_id);

    if (!$media_id) {
      self::log('send_whatsapp_image — media upload failed, skipping image');
      return false;
    }

    $url = 'https://graph.facebook.com/v21.0/' . $phone_id . '/messages';

    $image_data = ['id' => $media_id];
    if (!empty($caption)) {
      $image_data['caption'] = $caption;
    }

    $payload = [
      'messaging_product' => 'whatsapp',
      'recipient_type'    => 'individual',
      'to'                => $to,
      'type'              => 'image',
      'image'             => $image_data,
    ];

    self::log('send_whatsapp_image — to=' . $to . ' media_id=' . $media_id);

    $response = wp_remote_post($url, [
      'timeout' => 30,
      'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
      ],
      'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
      self::log('send_whatsapp_image — WP_Error: ' . $response->get_error_message());
      return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    self::log('send_whatsapp_image — response code=' . $code . ' body=' . $body);

    return $code >= 200 && $code < 300;
  }

  /**
   * Upload an image to WhatsApp's media API and return the media ID.
   *
   * @param string $image_url    Public URL or local path of the image.
   * @param string $access_token WhatsApp API access token.
   * @param string $phone_id     WhatsApp phone number ID.
   * @return string|false        Media ID on success, false on failure.
   */
  private static function upload_media_to_whatsapp($image_url, $access_token, $phone_id) {
    $attachment_id = attachment_url_to_postid($image_url);
    if ($attachment_id) {
      $file_path = get_attached_file($attachment_id);
    } else {
      $file_path = false;
    }

    if (!$file_path || !file_exists($file_path)) {
      self::log('upload_media_to_whatsapp — downloading from URL: ' . $image_url);
      $tmp = download_url($image_url, 30);
      if (is_wp_error($tmp)) {
        self::log('upload_media_to_whatsapp — download failed: ' . $tmp->get_error_message());
        return false;
      }
      $file_path = $tmp;
      $is_temp = true;
    } else {
      $is_temp = false;
      self::log('upload_media_to_whatsapp — using local file: ' . $file_path);
    }

    $mime_type = wp_check_filetype($file_path)['type'];
    if (empty($mime_type)) {
      $mime_type = 'image/jpeg';
    }

    $boundary = wp_generate_password(24, false);
    $body = '';
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="messaging_product"' . "\r\n\r\n";
    $body .= 'whatsapp' . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="type"' . "\r\n\r\n";
    $body .= $mime_type . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file_path) . '"' . "\r\n";
    $body .= 'Content-Type: ' . $mime_type . "\r\n\r\n";
    $body .= file_get_contents($file_path) . "\r\n";
    $body .= '--' . $boundary . '--' . "\r\n";

    if ($is_temp) {
      wp_delete_file($file_path);
    }

    $upload_url = 'https://graph.facebook.com/v21.0/' . $phone_id . '/media';

    $response = wp_remote_post($upload_url, [
      'timeout' => 60,
      'headers' => [
        'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
        'Authorization' => 'Bearer ' . $access_token,
      ],
      'body' => $body,
    ]);

    if (is_wp_error($response)) {
      self::log('upload_media_to_whatsapp — WP_Error: ' . $response->get_error_message());
      return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $resp_body = wp_remote_retrieve_body($response);
    self::log('upload_media_to_whatsapp — response code=' . $code . ' body=' . $resp_body);

    if ($code < 200 || $code >= 300) {
      return false;
    }

    $data = json_decode($resp_body, true);
    return !empty($data['id']) ? $data['id'] : false;
  }

  /* ================================================================
   * WHATSAPP IMAGE HANDLING
   * ================================================================ */

  /**
   * Auto-inject [PRODUCT_IMAGES:id] tags for products mentioned by URL
   * but missing an explicit image tag.
   *
   * @param string $text AI response text.
   * @return string      Text with image tags injected where needed.
   */
  private static function auto_inject_product_image_tags($text) {
    if (!class_exists('WooCommerce')) {
      return $text;
    }

    $site_url = home_url();

    $product_base = 'product';
    $permalinks = (array) get_option('woocommerce_permalinks', []);
    if (!empty($permalinks['product_base'])) {
      $product_base = trim($permalinks['product_base'], '/');
    }

    $product_ids_found = [];

    // Strategy 1: Find product permalink URLs
    if (preg_match_all('#' . preg_quote($site_url, '#') . '/' . preg_quote($product_base, '#') . '/([a-z0-9\-]+)/?#i', $text, $matches)) {
      foreach ($matches[1] as $slug) {
        $product_obj = get_page_by_path($slug, OBJECT, 'product');
        $product_id  = $product_obj ? $product_obj->ID : 0;

        if (!$product_id) {
          $correct_url = self::find_closest_product_url($slug);
          if ($correct_url) {
            $product_id = url_to_postid($correct_url);
          }
        }

        if ($product_id) {
          $product_ids_found[$product_id] = 'slug:' . $slug;
        }
      }
    }

    // Strategy 2: Find add-to-cart URLs
    if (preg_match_all('/[?&]add-to-cart=(\d+)/i', $text, $atc_matches)) {
      foreach ($atc_matches[1] as $pid) {
        $pid = (int) $pid;
        if ($pid > 0 && get_post_type($pid) === 'product') {
          $product_ids_found[$pid] = 'add-to-cart';
        }
      }
    }

    if (empty($product_ids_found)) {
      return $text;
    }

    $injected = 0;
    foreach ($product_ids_found as $product_id => $source) {
      if (strpos($text, '[PRODUCT_IMAGES:' . $product_id . ']') !== false
        || strpos($text, '[PRODUCT_MORE_IMAGES:' . $product_id . ']') !== false) {
        continue;
      }

      if (!get_post_thumbnail_id($product_id)) {
        continue;
      }

      $text .= "\n[PRODUCT_IMAGES:" . $product_id . ']';
      $injected++;
      self::log('auto_inject_product_image_tags — injected [PRODUCT_IMAGES:' . $product_id . '] for product_id=' . $product_id . ' (source=' . $source . ')');
    }

    if ($injected > 0) {
      $text = trim($text);
    }

    return $text;
  }

  /**
   * Extract [PRODUCT_IMAGES:id] tags from AI response, send product images
   * via WhatsApp, and return the cleaned text.
   *
   * @param string $text        AI response text that may contain tags.
   * @param string $phone       Recipient phone number.
   * @param array  &$sent_images Reference array to collect sent image URLs.
   * @return string              Text with tags removed.
   */
  private static function extract_and_send_product_images($text, $phone, &$sent_images = [], $already_sent_ids = []) {
    if (preg_match_all('/\[PRODUCT_IMAGES:(\d+)\]/', $text, $matches)) {
      self::log_image_debug('[IMG-DEBUG] extract_and_send_product_images — found ' . count($matches[1]) . ' tag(s): ' . implode(', ', $matches[1]));

      foreach ($matches[1] as $product_id) {
        $product_id = (int) $product_id;

        // If main image was already sent, promote to [PRODUCT_MORE_IMAGES]
        // so the gallery handler picks it up.
        if (in_array($product_id, $already_sent_ids, true)) {
          self::log_image_debug('[IMG-DEBUG] product ' . $product_id . ' already sent in conversation, promoting to PRODUCT_MORE_IMAGES');
          $text = str_replace('[PRODUCT_IMAGES:' . $product_id . ']', '[PRODUCT_MORE_IMAGES:' . $product_id . ']', $text);
          continue;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
          self::log_image_debug('[IMG-DEBUG] product ' . $product_id . ' — wc_get_product() returned NULL (product not found or not WC product)');
          continue;
        }

        $product_name = $product->get_name();

        // Send only the main (featured) image on first mention.
        $thumb_id = get_post_thumbnail_id($product_id);
        if (!$thumb_id) {
          self::log_image_debug('[IMG-DEBUG] product ' . $product_id . ' (' . $product_name . ') — NO featured image (thumb_id=0)');
          continue;
        }

        $main_url = wp_get_attachment_url($thumb_id);
        if (!$main_url) {
          self::log_image_debug('[IMG-DEBUG] product ' . $product_id . ' (' . $product_name . ') — wp_get_attachment_url returned false for thumb_id=' . $thumb_id);
          continue;
        }

        self::log_image_debug('[IMG-DEBUG] product ' . $product_id . ' (' . $product_name . ') — SENDING image url=' . $main_url);

        $send_result = self::send_whatsapp_image($phone, $main_url, $product_name);
        self::log_image_debug('[IMG-DEBUG] product ' . $product_id . ' — send_whatsapp_image result=' . ($send_result ? 'OK' : 'FAILED'));

        $sent_images[] = [
          'url'          => $main_url,
          'product_name' => $product_name,
          'product_id'   => $product_id,
          'type'         => 'main',
        ];
      }

      // Remove all tags from the text
      $text = preg_replace('/\s*\[PRODUCT_IMAGES:\d+\]/', '', $text);
      $text = trim($text);
    } else {
      self::log_image_debug('[IMG-DEBUG] extract_and_send_product_images — NO [PRODUCT_IMAGES:*] tags found in response');
    }

    return $text;
  }

  /**
   * Extract [PRODUCT_MORE_IMAGES:id] tags from AI response, send gallery
   * images via WhatsApp, and return the cleaned text.
   *
   * @param string $text              AI response text that may contain tags.
   * @param string $phone             Recipient phone number.
   * @param array  &$sent_images      Reference array to collect sent image data.
   * @param array  $already_sent_more Already-sent gallery product IDs.
   * @return string                   Text with tags removed.
   */
  private static function extract_and_send_more_product_images($text, $phone, &$sent_images = [], $already_sent_more = []) {
    if (!preg_match_all('/\[PRODUCT_MORE_IMAGES:(\d+)\]/', $text, $matches)) {
      return $text;
    }

    $unique_ids = array_unique($matches[1]);

    foreach ($unique_ids as $product_id) {
      $product_id = (int) $product_id;

      if (in_array($product_id, $already_sent_more, true)) {
        self::log('extract_and_send_more_product_images — product ' . $product_id . ' gallery already sent, skipping');
        continue;
      }

      $product = wc_get_product($product_id);
      if (!$product) {
        self::log('extract_and_send_more_product_images — product ' . $product_id . ' not found');
        continue;
      }

      $product_name = $product->get_name();
      $gallery_ids  = $product->get_gallery_image_ids();

      if (empty($gallery_ids)) {
        self::log('extract_and_send_more_product_images — product ' . $product_id . ' has no gallery images');
        continue;
      }

      $count = 0;
      foreach ($gallery_ids as $gallery_id) {
        $gallery_url = wp_get_attachment_url($gallery_id);
        if (!$gallery_url) {
          continue;
        }
        self::send_whatsapp_image($phone, $gallery_url, $product_name);
        $sent_images[] = [
          'url'          => $gallery_url,
          'product_name' => $product_name,
          'product_id'   => $product_id,
          'type'         => 'gallery',
        ];
        $count++;
      }

      self::log('extract_and_send_more_product_images — product ' . $product_id . ' (' . $product_name . ') — sent ' . $count . ' gallery images');
    }

    $text = preg_replace('/\s*\[PRODUCT_MORE_IMAGES:\d+\]/', '', $text);
    return trim($text);
  }

  /* ================================================================
   * CONVERSATION CRUD
   * ================================================================ */

  /**
   * Get or create a conversation for the given phone number.
   *
   * @param string $phone
   * @param string $contact_name
   * @return object|null
   */
  private static function get_or_create_conversation($phone, $contact_name = '', $is_reply = false, $text = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    // Normalize phone: strip leading '+' so we match both formats.
    $phone_normalized = ltrim($phone, '+');

    $conversation = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE REPLACE(phone_number, '+', '') = %s AND status = 'active' ORDER BY updated_at DESC LIMIT 1",
      $phone_normalized
    ));

    if ($conversation) {
      if (!empty($contact_name) && empty($conversation->contact_name)) {
        $wpdb->update($table, ['contact_name' => $contact_name], ['id' => $conversation->id]);
        $conversation->contact_name = $contact_name;
      }
      // Ensure a CRM organization exists for this phone (idempotent).
      self::sync_organization_for_phone($phone, $contact_name);
      return $conversation;
    }

    $system_prompt = get_option('pn_customers_manager_whatsapp_system_prompt', '');
    $ai_model      = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');
    $funnel_id     = 0;
    $node_id       = '';

    $wa_node     = self::find_ai_node();
    $welcome_msg = '';

    if ($wa_node) {
      $funnel_id = $wa_node['funnel_id'];
      $node_id   = $wa_node['node_id'];

      if (!empty($wa_node['config']['wa_system_prompt'])) {
        $system_prompt = $wa_node['config']['wa_system_prompt'];
      }
      if (!empty($wa_node['config']['wa_ai_model'])) {
        $ai_model = $wa_node['config']['wa_ai_model'];
      }
      if (!empty($wa_node['config']['wa_welcome_message'])) {
        $welcome_msg = $wa_node['config']['wa_welcome_message'];
      }

      self::log('Auto-linked conversation to funnel=' . $funnel_id . ' node=' . $node_id);
    }

    // Send or queue welcome message if configured.
    // Skip sending (but still store in history) when:
    //   - The message is a reply to a manually-sent message (is_reply), OR
    //   - The customer's first message is NOT a simple greeting, meaning they
    //     are likely responding to a manually-sent welcome message.
    $messages = [];
    if (!empty($welcome_msg)) {
      $welcome_msg = self::strip_html_to_plain_text($welcome_msg);
      $messages[] = [
        'role'      => 'assistant',
        'content'   => $welcome_msg,
        'timestamp' => current_time('mysql'),
        'auto_sent' => true,
      ];

      $skip_sending_welcome = false;

      if ($is_reply) {
        $skip_sending_welcome = true;
        self::log('[CONVERSATION] Skipping welcome send — message is a reply to a previous message');
      } else {
        // Check if the incoming text looks like a response to an already-sent
        // welcome rather than a cold first-contact greeting.  When admins send
        // the welcome message manually (before the bot template is ready),
        // the customer's first message will be a substantive reply, not a
        // simple greeting.  In that case we store the welcome in history (so
        // the AI has context) but do NOT send it over WhatsApp to avoid a
        // duplicate.
        $incoming_text = $text;
        if (empty($incoming_text) && !empty($contact_name)) {
          // Audio or empty — treat as non-greeting, skip welcome send
          $skip_sending_welcome = true;
          self::log('[CONVERSATION] Skipping welcome send — incoming message is empty/audio');
        } else {
          $normalized = mb_strtolower(trim($incoming_text));
          $simple_greetings = ['hola', 'holi', 'hi', 'hello', 'hey', 'buenos días', 'buenas tardes', 'buenas noches', 'buenos dias', 'buenas', 'saludos', 'que tal', 'qué tal'];
          if (!in_array($normalized, $simple_greetings, true)) {
            // Not a simple greeting — customer is likely replying to a
            // manually-sent welcome.  Store welcome in history but don't send.
            $skip_sending_welcome = true;
            self::log('[CONVERSATION] Skipping welcome send — first message is substantive, likely replying to manual welcome');
          }
        }
      }

      if (!$skip_sending_welcome) {
        $wa_node_config = $wa_node ? ($wa_node['config'] ?? []) : [];
        self::maybe_send_or_queue($phone, $welcome_msg, 0, $wa_node_config);
        self::log('[CONVERSATION] ── NEW CONVERSATION ──');
        self::log('[CONVERSATION] WELCOME SENT to ' . $phone . ' (' . $contact_name . '): ' . $welcome_msg);
      } else {
        self::log('[CONVERSATION] ── NEW CONVERSATION (welcome stored but NOT sent) ──');
        self::log('[CONVERSATION] WELCOME STORED (not sent) for ' . $phone . ' (' . $contact_name . '): ' . $welcome_msg);
      }
    }

    $wpdb->insert($table, [
      'phone_number'  => $phone,
      'contact_name'  => $contact_name,
      'funnel_id'     => $funnel_id,
      'node_id'       => $node_id,
      'messages'      => wp_json_encode($messages),
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

    // Sync CRM organization so the lead appears in the Clients dashboard.
    self::sync_organization_for_phone($phone, $contact_name);

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
  }

  /**
   * Find or create a pn_cm_organization for a phone number so that
   * WhatsApp leads automatically appear in the CRM Clients dashboard.
   *
   * If an organization with this phone already exists, update its name
   * when the organization has no title and a contact_name is available.
   *
   * @param string $phone        Phone number (as stored in WA conversations).
   * @param string $contact_name Contact name from WhatsApp profile.
   * @return int|false Organization post ID, or false on failure.
   */
  private static function sync_organization_for_phone($phone, $contact_name = '') {
    if (empty($phone)) {
      return false;
    }

    $phone_clean = preg_replace('/[^\d]/', '', $phone);
    if (empty($phone_clean)) {
      return false;
    }

    // Prevent race conditions: use a transient lock so concurrent requests
    // for the same phone number do not both create an organization.
    $lock_key = 'pn_cm_sync_org_' . $phone_clean;
    if (get_transient($lock_key)) {
      // Another request is creating the org right now — wait briefly and retry lookup.
      usleep(500000); // 0.5 seconds
    }
    set_transient($lock_key, 1, 30); // 30-second lock

    // Search for an existing organization by phone using direct SQL with
    // character stripping so that stored formats like "+34 651 045 739",
    // "+34651045739", or "34651045739" all match the same digits.
    global $wpdb;
    $org_id = $wpdb->get_var($wpdb->prepare(
      "SELECT p.ID FROM {$wpdb->posts} p
       INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
       WHERE p.post_type = 'pn_cm_organization'
         AND p.post_status = 'publish'
         AND pm.meta_key = 'pn_cm_organization_phone'
         AND REPLACE(REPLACE(REPLACE(REPLACE(pm.meta_value, '+', ''), ' ', ''), '-', ''), '.', '') LIKE %s
       ORDER BY p.ID DESC
       LIMIT 1",
      '%' . $wpdb->esc_like($phone_clean) . '%'
    ));

    if ($org_id) {
      $org_id = (int) $org_id;
      // Update name if org has a placeholder or empty title and we have a real name.
      if (!empty($contact_name)) {
        $org = get_post($org_id);
        if ($org && (empty($org->post_title) || $org->post_title === $phone || $org->post_title === $phone_clean)) {
          wp_update_post(['ID' => $org_id, 'post_title' => $contact_name]);
          self::log('[SYNC] Updated organization #' . $org_id . ' title to: ' . $contact_name);
        }
      }
      delete_transient($lock_key);
      return $org_id;
    }

    // Check if a WP user already exists with this phone number.
    // GHL/Zoho webhooks may have already created a user contact for this phone.
    // In that case, create an organization linked to the existing user instead of
    // a standalone duplicate.
    $existing_user_id = $wpdb->get_var($wpdb->prepare(
      "SELECT user_id FROM {$wpdb->usermeta}
       WHERE meta_key = 'phone'
       AND REPLACE(REPLACE(REPLACE(REPLACE(meta_value, '+', ''), ' ', ''), '-', ''), '.', '') LIKE %s
       LIMIT 1",
      '%' . $wpdb->esc_like($phone_clean) . '%'
    ));

    if (!$existing_user_id) {
      $existing_user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta}
         WHERE meta_key = 'billing_phone'
         AND REPLACE(REPLACE(REPLACE(REPLACE(meta_value, '+', ''), ' ', ''), '-', ''), '.', '') LIKE %s
         LIMIT 1",
        '%' . $wpdb->esc_like($phone_clean) . '%'
      ));
    }

    if ($existing_user_id) {
      $existing_user_id = (int) $existing_user_id;
      $user = get_userdata($existing_user_id);

      // Check if this user already has an organization linked.
      $linked_orgs = get_posts([
        'post_type'      => 'pn_cm_organization',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_key'       => 'pn_cm_organization_contact',
        'meta_value'     => $existing_user_id,
        'fields'         => 'ids',
      ]);

      if (!empty($linked_orgs)) {
        $org_id = $linked_orgs[0];
        self::log('[SYNC] Found existing org #' . $org_id . ' linked to user #' . $existing_user_id . ' for phone ' . $phone);
        delete_transient($lock_key);
        return $org_id;
      }

      // Create an organization linked to the existing user.
      $org_title = !empty($contact_name) ? $contact_name : ($user ? trim($user->first_name . ' ' . $user->last_name) : $phone);
      if (empty(trim($org_title))) {
        $org_title = $user ? $user->display_name : $phone;
      }

      $org_id = wp_insert_post([
        'post_type'   => 'pn_cm_organization',
        'post_title'  => $org_title,
        'post_status' => 'publish',
      ]);

      if (!is_wp_error($org_id) && $org_id) {
        update_post_meta($org_id, 'pn_cm_organization_phone', $phone);
        update_post_meta($org_id, 'pn_cm_organization_contact', $existing_user_id);
        if ($user && $user->user_email) {
          update_post_meta($org_id, 'pn_cm_organization_email', $user->user_email);
        }
        update_post_meta($org_id, 'pn_cm_client_source', 'whatsapp');
        if (class_exists('PN_CUSTOMERS_MANAGER_Clients')) {
          // Copy stage from user if set, otherwise use default.
          $user_stage = get_user_meta($existing_user_id, 'pn_cm_client_stage', true);
          $stage = !empty($user_stage) ? $user_stage : PN_CUSTOMERS_MANAGER_Clients::get_default_stage();
          update_post_meta($org_id, 'pn_cm_organization_client_stage', $stage);
        }
        // Copy GHL/Zoho IDs if present.
        $ghl_id = get_user_meta($existing_user_id, 'pn_cm_ghl_contact_id', true);
        if (!empty($ghl_id)) {
          update_post_meta($org_id, 'pn_cm_organization_ghl_id', $ghl_id);
        }
        $zoho_id = get_user_meta($existing_user_id, 'pn_cm_zoho_contact_id', true);
        if (!empty($zoho_id)) {
          update_post_meta($org_id, 'pn_cm_organization_zoho_id', $zoho_id);
        }

        self::log('[SYNC] Created organization #' . $org_id . ' linked to existing user #' . $existing_user_id . ' for phone ' . $phone);
        delete_transient($lock_key);
        return $org_id;
      }
    }

    // No organization or user found — create one.
    $title = !empty($contact_name) ? $contact_name : $phone;

    $org_id = wp_insert_post([
      'post_type'   => 'pn_cm_organization',
      'post_title'  => $title,
      'post_status' => 'publish',
    ]);

    if (is_wp_error($org_id) || !$org_id) {
      self::log('[SYNC] Failed to create organization for phone ' . $phone);
      delete_transient($lock_key);
      return false;
    }

    update_post_meta($org_id, 'pn_cm_organization_phone', $phone);
    update_post_meta($org_id, 'pn_cm_client_source', 'whatsapp');

    // Set default stage.
    if (class_exists('PN_CUSTOMERS_MANAGER_Clients')) {
      update_post_meta($org_id, 'pn_cm_organization_client_stage', PN_CUSTOMERS_MANAGER_Clients::get_default_stage());
    }

    self::log('[SYNC] Created organization #' . $org_id . ' (' . $title . ') for phone ' . $phone);

    delete_transient($lock_key);
    return $org_id;
  }

  /**
   * Create a conversation linked to a specific funnel node.
   *
   * @param string $phone
   * @param string $contact_name
   * @param int    $funnel_id
   * @param string $node_id
   * @return object|null
   */
  public static function create_conversation_for_node($phone, $contact_name, $funnel_id, $node_id) {
    // Normalize phone: strip leading '+' so it matches the webhook format (digits only).
    $phone = ltrim($phone, '+');

    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $system_prompt = get_option('pn_customers_manager_whatsapp_system_prompt', '');
    $ai_model      = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');
    $welcome_msg   = '';

    $node_config_data = [];
    $canvas_data = get_post_meta($funnel_id, 'pn_cm_funnel_canvas', true);
    if ($canvas_data) {
      $data = json_decode($canvas_data, true);
      if ($data && isset($data['nodes'])) {
        foreach ($data['nodes'] as $node) {
          if ($node['id'] === $node_id && isset($node['config'])) {
            $node_config_data = $node['config'];
            if (!empty($node['config']['wa_system_prompt'])) {
              $system_prompt = $node['config']['wa_system_prompt'];
            }
            if (!empty($node['config']['wa_ai_model'])) {
              $ai_model = $node['config']['wa_ai_model'];
            }
            if (!empty($node['config']['wa_welcome_message'])) {
              $welcome_msg = $node['config']['wa_welcome_message'];
            }
            break;
          }
        }
      }
    }

    $meta_templates_enabled = self::is_meta_templates_enabled($node_config_data);

    if ($meta_templates_enabled) {
      self::log('[CONVERSATION] ── NEW REGISTRATION / NODE CONTACT: ' . $phone . ' ──');
      self::log('[CONVERSATION] Meta templates enabled — skipping plain-text welcome (Meta policy requires template outside 24h window). Initiating template system.');

      self::initiate_multi_templates($phone, $contact_name, $node_config_data, $funnel_id, $node_id);

      // Sync CRM organization so the lead appears in the Clients dashboard.
      self::sync_organization_for_phone($phone, $contact_name);

      $phone_norm = ltrim($phone, '+');
      return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE REPLACE(phone_number, '+', '') = %s ORDER BY id DESC LIMIT 1",
        $phone_norm
      ));
    }

    // When Meta templates are not enabled:
    // In WhatsApp Cloud API, sending a cold plain-text message to a customer without an active
    // 24h window is strictly rejected with error 131047. We store the welcome message in conversation
    // history so the bot knows the welcome context when the customer replies, but do NOT attempt
    // to send free-form text to Meta.
    $messages = [];
    if (!empty($welcome_msg)) {
      $welcome_msg = self::strip_html_to_plain_text($welcome_msg);
      $messages[] = [
        'role'      => 'assistant',
        'content'   => $welcome_msg,
        'timestamp' => current_time('mysql'),
      ];
      self::log('[CONVERSATION] ── NEW CONVERSATION (registration) ──');
      self::log('[CONVERSATION] Welcome message stored in history for ' . $phone . ', but NOT sent as text (Meta requires an approved template to initiate contact outside 24h window)');
    }

    $wpdb->insert($table, [
      'phone_number'  => $phone,
      'contact_name'  => $contact_name,
      'funnel_id'     => $funnel_id,
      'node_id'       => $node_id,
      'messages'      => wp_json_encode($messages),
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

    // Sync CRM organization so the lead appears in the Clients dashboard.
    self::sync_organization_for_phone($phone, $contact_name);

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
  }

  private static function update_conversation_messages($conversation_id, $messages) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $wpdb->update(
      $table,
      [
        'messages'   => wp_json_encode($messages),
        'updated_at' => current_time('mysql'),
      ],
      ['id' => $conversation_id]
    );
  }

  /**
   * Close a conversation.
   *
   * @param int $conversation_id
   */
  public static function close_conversation($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

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
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

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

  /**
   * Delete a conversation.
   *
   * @param int $conversation_id
   */
  public static function delete_conversation($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $wpdb->delete($table, ['id' => $conversation_id]);
  }

  /**
   * Get all conversations with pagination.
   *
   * @param string $status
   * @param int    $page
   * @param int    $per_page
   * @return array
   */
  public static function get_conversations($status = '', $page = 1, $per_page = 20) {
    global $wpdb;
    $table  = $wpdb->prefix . 'pn_cm_whatsapp_conversations';
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

  /**
   * Get a single conversation by ID.
   *
   * @param int $id
   * @return object|null
   */
  public static function get_conversation($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
  }

  /**
   * Get count of active conversations (for menu badge).
   *
   * @return int
   */
  public static function get_active_count() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return 0;
    }

    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
  }

  /* ================================================================
   * ADMIN PAGE
   * ================================================================ */

  /**
   * Public wrapper for close_conversation (used from AJAX).
   */
  public static function close_conversation_public($conv_id) {
    self::close_conversation($conv_id);
  }

  /**
   * Public wrapper for delete_conversation (used from AJAX).
   */
  public static function delete_conversation_public($conv_id) {
    self::delete_conversation($conv_id);
  }

  /**
   * Public wrapper for reset_conversation (used from AJAX).
   */
  public static function reset_conversation_public($conv_id) {
    self::reset_conversation($conv_id);
  }

  /**
   * AJAX: return conversation detail HTML for front-end popup.
   */
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
        <?php echo esc_html($conv->contact_name ?: $conv->phone_number); ?>
        <span class="pn-cm-wa-status pn-cm-wa-status-<?php echo esc_attr($conv->status); ?>">
          <?php echo $conv->status === 'active' ? esc_html__('Active', 'pn-customers-manager') : esc_html__('Closed', 'pn-customers-manager'); ?>
        </span>
      </div>
      <button type="button" class="pn-cm-wa-popup-close"><span class="material-icons-outlined">close</span></button>
    </div>
    <div class="pn-cm-wa-conv-meta">
      <span><strong><?php esc_html_e('Phone:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($conv->phone_number); ?></span>
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
                <div class="pn-cm-wa-msg-content">
                  <?php
                    // Escape HTML but convert URLs to clickable links
                    $content = esc_html($msg['content']);
                    $content = make_clickable($content);
                    echo wp_kses_post(nl2br($content));
                  ?>
                </div>
              <?php endif; ?>
              <div class="pn-cm-wa-msg-time">
                <?php
                  if (isset($msg['timestamp'])) {
                    $local_time = get_date_from_gmt($msg['timestamp']);
                    echo esc_html(date_i18n(get_option('time_format'), strtotime($local_time)));
                  }
                ?>
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
      <button type="button" class="pn-cm-wa-action-btn" data-action="reset" data-conv-id="<?php echo esc_attr($conv->id); ?>">
        <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">restart_alt</span>
        <?php esc_html_e('Reset', 'pn-customers-manager'); ?>
      </button>
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

      if (!wp_verify_nonce(isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '', 'pn_cm_wa_action')) {
        wp_die(esc_html__('Action not allowed.', 'pn-customers-manager'));
      }

      $action = sanitize_text_field(wp_unslash($_GET['action']));

      if ($action === 'close' && $conv_id) {
        self::close_conversation($conv_id);
      } elseif ($action === 'delete' && $conv_id) {
        self::delete_conversation($conv_id);
      }

      wp_safe_redirect(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai'));
      exit;
    }

    $view_id = isset($_GET['view']) ? absint($_GET['view']) : 0;

    if ($view_id) {
      self::render_conversation_detail($view_id);
      return;
    }

    // List view
    $status  = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
    $page    = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $result  = self::get_conversations($status, $page);

    ?>
    <div class="wrap pn-cm-wa-admin-wrap">
      <h1 class="wp-heading-inline">
        <span class="material-icons-outlined" style="vertical-align:middle;margin-right:8px;">psychology</span>
        <?php esc_html_e('WhatsApp AI', 'pn-customers-manager'); ?>
      </h1>

      <div class="pn-cm-wa-filters">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai')); ?>"
           class="pn-cm-wa-filter-btn <?php echo $status === '' ? 'active' : ''; ?>">
          <?php esc_html_e('All', 'pn-customers-manager'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai&status=active')); ?>"
           class="pn-cm-wa-filter-btn <?php echo $status === 'active' ? 'active' : ''; ?>">
          <?php esc_html_e('Active', 'pn-customers-manager'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai&status=closed')); ?>"
           class="pn-cm-wa-filter-btn <?php echo $status === 'closed' ? 'active' : ''; ?>">
          <?php esc_html_e('Closed', 'pn-customers-manager'); ?>
        </a>
      </div>

      <?php if (empty($result['items'])): ?>
        <div class="pn-cm-wa-empty">
          <span class="material-icons-outlined">chat_bubble_outline</span>
          <p><?php esc_html_e('No conversations yet.', 'pn-customers-manager'); ?></p>
        </div>
      <?php else: ?>
        <table class="wp-list-table widefat fixed striped pn-cm-wa-table">
          <thead>
            <tr>
              <th><?php esc_html_e('Phone', 'pn-customers-manager'); ?></th>
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
                  <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai&view=' . $conv->id)); ?>" class="pn-cm-wa-conv-link">
                    <?php echo esc_html($conv->phone_number); ?>
                  </a>
                </td>
                <td><?php echo esc_html($conv->contact_name ?: '-'); ?></td>
                <td><?php echo esc_html($funnel_title); ?></td>
                <td class="pn-cm-wa-last-msg"><?php echo esc_html($last_text); ?></td>
                <td>
                  <span class="pn-cm-wa-status pn-cm-wa-status-<?php echo esc_attr($conv->status); ?>">
                    <?php echo $conv->status === 'active' ? esc_html__('Active', 'pn-customers-manager') : esc_html__('Closed', 'pn-customers-manager'); ?>
                  </span>
                </td>
                <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conv->updated_at))); ?></td>
                <td>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai&view=' . $conv->id)); ?>" class="button button-small">
                    <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">visibility</span>
                  </a>
                  <?php if ($conv->status === 'active'): ?>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai&action=close&conv_id=' . $conv->id), 'pn_cm_wa_action')); ?>" class="button button-small">
                      <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">close</span>
                    </a>
                  <?php endif; ?>
                  <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai&action=delete&conv_id=' . $conv->id), 'pn_cm_wa_action')); ?>"
                     class="button button-small pn-cm-wa-btn-delete"
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
              $base_url = admin_url('admin.php?page=pn_customers_manager_whatsapp_ai');
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

  /**
   * Render conversation detail (chat view).
   *
   * @param int $conv_id
   */
  private static function render_conversation_detail($conv_id) {
    $conv = self::get_conversation($conv_id);

    if (!$conv) {
      echo '<div class="wrap"><p>' . esc_html__('Conversation not found.', 'pn-customers-manager') . '</p></div>';
      return;
    }

    $messages     = json_decode($conv->messages, true) ?: [];
    $funnel_title = $conv->funnel_id ? get_the_title($conv->funnel_id) : '-';

    ?>
    <div class="wrap pn-cm-wa-admin-wrap">
      <h1 class="wp-heading-inline">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai')); ?>" class="pn-cm-wa-back">
          <span class="material-icons-outlined">arrow_back</span>
        </a>
        <?php echo esc_html($conv->contact_name ?: $conv->phone_number); ?>
        <span class="pn-cm-wa-status pn-cm-wa-status-<?php echo esc_attr($conv->status); ?>">
          <?php echo $conv->status === 'active' ? esc_html__('Active', 'pn-customers-manager') : esc_html__('Closed', 'pn-customers-manager'); ?>
        </span>
      </h1>

      <div class="pn-cm-wa-conv-meta">
        <span><strong><?php esc_html_e('Phone:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($conv->phone_number); ?></span>
        <span><strong><?php esc_html_e('Funnel:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($funnel_title); ?></span>
        <span><strong><?php esc_html_e('Model:', 'pn-customers-manager'); ?></strong> <?php echo esc_html($conv->ai_model); ?></span>
        <span><strong><?php esc_html_e('Created:', 'pn-customers-manager'); ?></strong> <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conv->created_at))); ?></span>
      </div>

      <div class="pn-cm-wa-chat" id="pn-cm-wa-chat">
        <?php if (empty($messages)): ?>
          <div class="pn-cm-wa-chat-empty">
            <span class="material-icons-outlined">chat_bubble_outline</span>
            <p><?php esc_html_e('No messages in this conversation.', 'pn-customers-manager'); ?></p>
          </div>
        <?php else: ?>
          <?php foreach ($messages as $msg): ?>
            <div class="pn-cm-wa-msg pn-cm-wa-msg-<?php echo esc_attr($msg['role']); ?>">
              <div class="pn-cm-wa-msg-bubble">
                <?php if (!empty($msg['quoted_text'])): ?>
                  <div class="pn-cm-wa-msg-quote"><?php echo esc_html(mb_substr($msg['quoted_text'], 0, 300)); ?></div>
                <?php endif; ?>
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
                  <?php
                    if (isset($msg['timestamp'])) {
                      $local_time = get_date_from_gmt($msg['timestamp']);
                      echo esc_html(date_i18n(get_option('time_format'), strtotime($local_time)));
                    }
                  ?>
                  <?php if ($msg['role'] === 'assistant'): ?>
                    <?php if (!empty($msg['auto_sent'])): ?>
                      <span class="material-icons-outlined pn-cm-wa-msg-icon" title="<?php echo esc_attr__('Automated bot message', 'pn-customers-manager'); ?>">smart_toy</span>
                    <?php else: ?>
                      <span class="material-icons-outlined pn-cm-wa-msg-icon" title="<?php echo esc_attr__('Manual message', 'pn-customers-manager'); ?>">person</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="pn-cm-wa-conv-actions">
        <?php if ($conv->status === 'active'): ?>
          <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai&action=close&conv_id=' . $conv->id), 'pn_cm_wa_action')); ?>" class="button">
            <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">close</span>
            <?php esc_html_e('Close conversation', 'pn-customers-manager'); ?>
          </a>
        <?php endif; ?>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pn_customers_manager_whatsapp_ai&action=delete&conv_id=' . $conv->id), 'pn_cm_wa_action')); ?>"
           class="button pn-cm-wa-btn-delete"
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

  /**
   * Test OpenAI API connection.
   *
   * @return array
   */
  public static function ajax_test_openai() {
    $model       = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');
    $temperature = (float) get_option('pn_customers_manager_whatsapp_temperature', 0.7);
    $provider    = self::resolve_provider_for_model($model);
    $api_key     = get_option($provider['key_option'], '');

    if (empty($api_key)) {
      return [
        'error_key'     => 'no_api_key',
        'error_content' => sprintf(
          /* translators: %s: provider name (e.g. "OpenAI", "DeepSeek") */
          esc_html__('%s API Key has not been configured.', 'pn-customers-manager'),
          $provider['label']
        ),
      ];
    }

    $messages = [
      ['role' => 'system', 'content' => 'Answer in a single short sentence.'],
      ['role' => 'user', 'content' => 'Say "Connection successful" and the name of the model you are.'],
    ];

    $response = self::call_openai($messages, $model, $temperature);

    if ($response === false) {
      return [
        'error_key'     => 'openai_error',
        'error_content' => sprintf(
          /* translators: %s: provider name */
          esc_html__('Could not connect to %s. Verify the API Key and that you have available credit.', 'pn-customers-manager'),
          $provider['label']
        ),
      ];
    }

    return [
      'error_key' => '',
      'message'   => $response,
      'model'     => $model,
    ];
  }

  /**
   * Test WhatsApp API by sending a test message.
   *
   * @param string $phone
   * @return array
   */
  public static function ajax_test_whatsapp($phone) {
    if (empty($phone)) {
      return [
        'error_key'     => 'no_phone',
        'error_content' => esc_html__('Enter a test phone number.', 'pn-customers-manager'),
      ];
    }

    $access_token = get_option('pn_customers_manager_whatsapp_access_token', '');
    $phone_id     = get_option('pn_customers_manager_whatsapp_phone_number_id', '');

    if (empty($access_token)) {
      return [
        'error_key'     => 'no_token',
        'error_content' => esc_html__('WhatsApp Access Token has not been configured.', 'pn-customers-manager'),
      ];
    }

    if (empty($phone_id)) {
      return [
        'error_key'     => 'no_phone_id',
        'error_content' => esc_html__('Phone Number ID has not been configured.', 'pn-customers-manager'),
      ];
    }

    $test_message = __('This is a test message from PN Customers Manager. If you receive it, the WhatsApp integration is working correctly.', 'pn-customers-manager');

    $success = self::send_whatsapp_message($phone, $test_message);

    if (!$success) {
      return [
        'error_key'     => 'send_failed',
        'error_content' => esc_html__('Could not send the message. Verify the Access Token, Phone Number ID and that the destination number is valid.', 'pn-customers-manager'),
      ];
    }

    // Log the test message in a conversation
    $conversation = self::get_or_create_conversation($phone, '');
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

  /**
   * Check for recently received WhatsApp messages (webhook reception test).
   *
   * @param string $since  'start' to begin listening, or datetime string.
   * @return array
   */
  public static function ajax_test_webhook_receive($since) {
    if ($since === 'start') {
      $server_time = current_time('mysql');
      self::log('[RECEIVE-TEST] Start listening — server_time=' . $server_time);
      return [
        'found'       => false,
        'server_time' => $server_time,
      ];
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return [
        'error_key'     => 'no_table',
        'error_content' => esc_html__('Conversations table does not exist. Deactivate and reactivate the plugin.', 'pn-customers-manager'),
      ];
    }

    // Search all recent conversations (not just updated_at >= since, because the
    // conversation may have been created before listening started but received a
    // new message after). Check the last 10 conversations for any user message
    // with a timestamp >= since.
    $rows = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY updated_at DESC LIMIT 10"
    ));

    if (empty($rows)) {
      return ['found' => false];
    }

    foreach ($rows as $row) {
      $messages = json_decode($row->messages, true) ?: [];

      foreach ($messages as $msg) {
        if (
          isset($msg['role']) && $msg['role'] === 'user' &&
          isset($msg['timestamp']) && $msg['timestamp'] >= $since
        ) {
          self::log('[RECEIVE-TEST] Found message from ' . $row->phone_number . ': ' . $msg['content']);
          return [
            'found'   => true,
            'phone'   => $row->phone_number,
            'name'    => $row->contact_name,
            'message' => $msg['content'],
            'time'    => $msg['timestamp'],
          ];
        }
      }
    }

    return ['found' => false];
  }

  /**
   * Get conversations list for the funnel builder popup.
   *
   * @return array
   */
  public static function ajax_get_conversations_list() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return ['error_key' => '', 'conversations' => []];
    }

    $rows = $wpdb->get_results(
      "SELECT id, phone_number, contact_name, status, ai_model, created_at, updated_at, messages
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
        'phone'      => $row->phone_number,
        'name'       => $row->contact_name,
        'status'     => $row->status,
        'last_msg'   => $last_txt,
        'msg_count'  => count($msgs),
        'updated_at' => $row->updated_at,
      ];
    }

    return ['error_key' => '', 'conversations' => $conversations];
  }

  /**
   * Get a single conversation's messages for the funnel builder popup.
   *
   * @param int $conv_id
   * @return array
   */
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
      'phone'     => $conv->phone_number,
      'name'      => $conv->contact_name,
      'status'    => $conv->status,
      'model'     => $conv->ai_model,
      'messages'  => $messages,
    ];
  }

  /**
   * Enqueue admin CSS and JS for the WhatsApp IA page.
   */
  private static function enqueue_admin_assets() {
    wp_enqueue_style(
      'pn-customers-manager-whatsapp-ai',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-whatsapp-ai.css',
      [],
      PN_CUSTOMERS_MANAGER_VERSION
    );

    wp_enqueue_script(
      'pn-customers-manager-whatsapp-ai',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-whatsapp-ai.js',
      [],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );
  }

  /* ================================================================
   * IMAGE EXTRACTION (without sending)
   * ================================================================ */

  /**
   * Extract product image data from AI response text and strip image tags,
   * WITHOUT sending images via WhatsApp. Used when deferring sends.
   *
   * @param  string $text              AI response text with image tags
   * @param  array  $already_sent_ids  Product IDs whose main image was already sent
   * @param  array  $already_sent_more Product IDs whose gallery was already sent
   * @return array  ['text' => cleaned text, 'images' => array of image data]
   */
  private static function extract_product_images_data_only($text, $already_sent_ids = [], $already_sent_more = []) {
    $images = [];

    // Extract main product images
    if (preg_match_all('/\[PRODUCT_IMAGES:(\d+)\]/', $text, $matches)) {
      foreach ($matches[1] as $product_id) {
        $product_id = (int) $product_id;

        if (in_array($product_id, $already_sent_ids, true)) {
          $text = str_replace('[PRODUCT_IMAGES:' . $product_id . ']', '[PRODUCT_MORE_IMAGES:' . $product_id . ']', $text);
          continue;
        }

        if (!function_exists('wc_get_product')) continue;
        $product = wc_get_product($product_id);
        if (!$product) continue;

        $thumb_id = get_post_thumbnail_id($product_id);
        if (!$thumb_id) continue;

        $main_url = wp_get_attachment_url($thumb_id);
        if (!$main_url) continue;

        $images[] = [
          'url'          => $main_url,
          'product_name' => $product->get_name(),
          'product_id'   => $product_id,
          'type'         => 'main',
        ];
      }
      $text = preg_replace('/\s*\[PRODUCT_IMAGES:\d+\]/', '', $text);
    }

    // Extract gallery images
    if (preg_match_all('/\[PRODUCT_MORE_IMAGES:(\d+)\]/', $text, $matches)) {
      $unique_ids = array_unique($matches[1]);
      foreach ($unique_ids as $product_id) {
        $product_id = (int) $product_id;
        if (in_array($product_id, $already_sent_more, true)) continue;
        if (!function_exists('wc_get_product')) continue;

        $product = wc_get_product($product_id);
        if (!$product) continue;

        $gallery_ids = $product->get_gallery_image_ids();
        if (empty($gallery_ids)) continue;

        foreach ($gallery_ids as $gallery_id) {
          $gallery_url = wp_get_attachment_url($gallery_id);
          if (!$gallery_url) continue;
          $images[] = [
            'url'          => $gallery_url,
            'product_name' => $product->get_name(),
            'product_id'   => $product_id,
            'type'         => 'gallery',
          ];
        }
      }
      $text = preg_replace('/\s*\[PRODUCT_MORE_IMAGES:\d+\]/', '', $text);
    }

    return ['text' => trim($text), 'images' => $images];
  }

  /* ================================================================
   * MESSAGE SCHEDULING
   * ================================================================ */

  /**
   * Country calling-code → IANA timezone lookup.
   *
   * @param  string $phone  E.164 phone number (e.g. +34612345678)
   * @return string|null     IANA timezone or null if no match
   */
  private static function get_timezone_from_phone($phone) {
    $map = [
      '1'    => 'America/New_York',
      '7'    => 'Europe/Moscow',
      '20'   => 'Africa/Cairo',
      '27'   => 'Africa/Johannesburg',
      '30'   => 'Europe/Athens',
      '31'   => 'Europe/Amsterdam',
      '32'   => 'Europe/Brussels',
      '33'   => 'Europe/Paris',
      '34'   => 'Europe/Madrid',
      '36'   => 'Europe/Budapest',
      '39'   => 'Europe/Rome',
      '40'   => 'Europe/Bucharest',
      '41'   => 'Europe/Zurich',
      '43'   => 'Europe/Vienna',
      '44'   => 'Europe/London',
      '45'   => 'Europe/Copenhagen',
      '46'   => 'Europe/Stockholm',
      '47'   => 'Europe/Oslo',
      '48'   => 'Europe/Warsaw',
      '49'   => 'Europe/Berlin',
      '51'   => 'America/Lima',
      '52'   => 'America/Mexico_City',
      '53'   => 'America/Havana',
      '54'   => 'America/Argentina/Buenos_Aires',
      '55'   => 'America/Sao_Paulo',
      '56'   => 'America/Santiago',
      '57'   => 'America/Bogota',
      '58'   => 'America/Caracas',
      '60'   => 'Asia/Kuala_Lumpur',
      '61'   => 'Australia/Sydney',
      '62'   => 'Asia/Jakarta',
      '63'   => 'Asia/Manila',
      '64'   => 'Pacific/Auckland',
      '65'   => 'Asia/Singapore',
      '66'   => 'Asia/Bangkok',
      '81'   => 'Asia/Tokyo',
      '82'   => 'Asia/Seoul',
      '84'   => 'Asia/Ho_Chi_Minh',
      '86'   => 'Asia/Shanghai',
      '90'   => 'Europe/Istanbul',
      '91'   => 'Asia/Kolkata',
      '92'   => 'Asia/Karachi',
      '93'   => 'Asia/Kabul',
      '94'   => 'Asia/Colombo',
      '95'   => 'Asia/Yangon',
      '98'   => 'Asia/Tehran',
      '212'  => 'Africa/Casablanca',
      '213'  => 'Africa/Algiers',
      '216'  => 'Africa/Tunis',
      '218'  => 'Africa/Tripoli',
      '220'  => 'Africa/Banjul',
      '221'  => 'Africa/Dakar',
      '233'  => 'Africa/Accra',
      '234'  => 'Africa/Lagos',
      '254'  => 'Africa/Nairobi',
      '255'  => 'Africa/Dar_es_Salaam',
      '256'  => 'Africa/Kampala',
      '260'  => 'Africa/Lusaka',
      '263'  => 'Africa/Harare',
      '351'  => 'Europe/Lisbon',
      '352'  => 'Europe/Luxembourg',
      '353'  => 'Europe/Dublin',
      '354'  => 'Atlantic/Reykjavik',
      '355'  => 'Europe/Tirane',
      '356'  => 'Europe/Malta',
      '357'  => 'Asia/Nicosia',
      '358'  => 'Europe/Helsinki',
      '359'  => 'Europe/Sofia',
      '370'  => 'Europe/Vilnius',
      '371'  => 'Europe/Riga',
      '372'  => 'Europe/Tallinn',
      '380'  => 'Europe/Kiev',
      '381'  => 'Europe/Belgrade',
      '385'  => 'Europe/Zagreb',
      '386'  => 'Europe/Ljubljana',
      '420'  => 'Europe/Prague',
      '421'  => 'Europe/Bratislava',
      '502'  => 'America/Guatemala',
      '503'  => 'America/El_Salvador',
      '504'  => 'America/Tegucigalpa',
      '505'  => 'America/Managua',
      '506'  => 'America/Costa_Rica',
      '507'  => 'America/Panama',
      '509'  => 'America/Port-au-Prince',
      '591'  => 'America/La_Paz',
      '593'  => 'America/Guayaquil',
      '595'  => 'America/Asuncion',
      '598'  => 'America/Montevideo',
      '880'  => 'Asia/Dhaka',
      '886'  => 'Asia/Taipei',
      '960'  => 'Indian/Maldives',
      '961'  => 'Asia/Beirut',
      '962'  => 'Asia/Amman',
      '963'  => 'Asia/Damascus',
      '964'  => 'Asia/Baghdad',
      '965'  => 'Asia/Kuwait',
      '966'  => 'Asia/Riyadh',
      '967'  => 'Asia/Aden',
      '968'  => 'Asia/Muscat',
      '970'  => 'Asia/Gaza',
      '971'  => 'Asia/Dubai',
      '972'  => 'Asia/Jerusalem',
      '973'  => 'Asia/Bahrain',
      '974'  => 'Asia/Qatar',
      '976'  => 'Asia/Ulaanbaatar',
      '977'  => 'Asia/Kathmandu',
      '992'  => 'Asia/Dushanbe',
      '993'  => 'Asia/Ashgabat',
      '994'  => 'Asia/Baku',
      '995'  => 'Asia/Tbilisi',
      '996'  => 'Asia/Bishkek',
      '998'  => 'Asia/Tashkent',
    ];

    $clean = ltrim($phone, '+');

    // Try 3-digit, 2-digit, 1-digit prefix (longest match first)
    for ($len = 3; $len >= 1; $len--) {
      $prefix = substr($clean, 0, $len);
      if (isset($map[$prefix])) {
        return $map[$prefix];
      }
    }

    return null;
  }

  /**
   * Resolve timezone for a phone number and store it as user meta.
   *
   * @param  string $phone
   * @return string  IANA timezone (falls back to WP site timezone)
   */
  private static function resolve_and_store_user_timezone($phone) {
    // Try to find existing WP user by phone
    $users = get_users([
      'meta_key'   => 'billing_phone',
      'meta_value' => $phone,
      'number'     => 1,
      'fields'     => 'ID',
    ]);

    if (empty($users)) {
      // Also try without leading +
      $clean_phone = ltrim($phone, '+');
      $users = get_users([
        'meta_key'   => 'billing_phone',
        'meta_value' => $clean_phone,
        'number'     => 1,
        'fields'     => 'ID',
      ]);
    }

    $user_id = !empty($users) ? (int) $users[0] : 0;

    // Check if user already has a timezone stored
    if ($user_id) {
      $existing_tz = get_user_meta($user_id, 'userspn_timezone', true);
      if (!empty($existing_tz)) {
        return $existing_tz;
      }
    }

    // Resolve from phone prefix
    $timezone = self::get_timezone_from_phone($phone);

    // Fallback to WP site timezone
    if (!$timezone) {
      $timezone = wp_timezone_string();
      if (empty($timezone) || substr($timezone, 0, 3) === 'UTC') {
        $timezone = 'UTC';
      }
    }

    // Store on user if found
    if ($user_id) {
      update_user_meta($user_id, 'userspn_timezone', $timezone);
    }

    return $timezone;
  }

  /**
   * Check if the current time (in the user's timezone) falls within the node's schedule.
   *
   * @param  array  $node_config    Node configuration array
   * @param  string $user_timezone  IANA timezone string
   * @return bool
   */
  private static function is_within_schedule($node_config, $user_timezone) {
    if (empty($node_config['wa_schedule_enabled'])) {
      return true; // No schedule configured = always allowed
    }

    $schedule_days = $node_config['wa_schedule_days'] ?? '';
    if (is_string($schedule_days)) {
      $schedule_days = json_decode($schedule_days, true);
    }

    if (empty($schedule_days) || !is_array($schedule_days)) {
      return true; // No schedule data = always allowed
    }

    try {
      $tz  = new DateTimeZone($user_timezone);
      $now = new DateTime('now', $tz);
    } catch (Exception $e) {
      return true; // Invalid timezone = don't block
    }

    // Day of week: 1=Monday ... 7=Sunday (ISO-8601)
    $day_index = (int) $now->format('N') - 1; // 0=Monday ... 6=Sunday
    $day_keys  = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    $day_key   = $day_keys[$day_index];

    if (!isset($schedule_days[$day_key]) || empty($schedule_days[$day_key]['enabled'])) {
      return false; // Day not enabled
    }

    $from = $schedule_days[$day_key]['from'] ?? '00:00';
    $to   = $schedule_days[$day_key]['to'] ?? '23:59';

    $current_time = $now->format('H:i');

    return ($current_time >= $from && $current_time <= $to);
  }

  /**
   * Check if the conversation was initiated by the client.
   * Returns true if the first non-system message is from 'user'.
   *
   * @param  array $messages Conversation messages array.
   * @return bool
   */
  private static function is_client_initiated_conversation($messages) {
    foreach ($messages as $m) {
      $role = $m['role'] ?? '';
      if ($role === 'system') continue;
      return $role === 'user';
    }
    return true; // Empty conversation = client is initiating
  }

  /**
   * Check if an off-hours message was already sent in this conversation.
   *
   * @param  array $messages Conversation messages array.
   * @return bool
   */
  private static function offhours_message_already_sent($messages) {
    foreach ($messages as $m) {
      if (!empty($m['offhours'])) {
        return true;
      }
    }
    return false;
  }

  /**
   * Create CRM tasks from follow-up tags extracted from AI responses.
   *
   * @param array  $keys         List of follow-up keys found in the AI response.
   * @param string $phone        Customer phone number.
   * @param string $contact_name Customer display name.
   * @param array  $node_config  Node configuration.
   * @param string $prefix       Config prefix ('wa_' or 'ig_').
   */
  private static function create_followup_tasks($keys, $phone, $contact_name, $node_config, $prefix) {
    if (!post_type_exists('pn_tasks_task')) {
      self::log('FOLLOWUP — pn_tasks_task CPT does not exist, skipping task creation');
      return;
    }

    $client = self::resolve_crm_client($phone);
    if (!$client) {
      self::log('FOLLOWUP — could not resolve CRM client for phone=' . $phone . ', skipping');
      return;
    }

    $raw = $node_config[$prefix . 'followup_listeners'] ?? '';
    if (is_string($raw)) {
      $raw = json_decode($raw, true);
    }
    if (!is_array($raw)) {
      return;
    }

    // Build key → listener map
    $listeners = [];
    foreach ($raw as $l) {
      $k = trim($l[$prefix . 'followup_key'] ?? '');
      if ($k) {
        $listeners[$k] = $l;
      }
    }

    foreach ($keys as $key) {
      if (!isset($listeners[$key])) {
        self::log('FOLLOWUP — key "' . $key . '" not found in listeners, skipping');
        continue;
      }
      $listener = $listeners[$key];
      $label = trim($listener[$prefix . 'followup_label'] ?? $key);
      $delay = max(1, intval($listener[$prefix . 'followup_delay'] ?? 24));
      $unit  = $listener[$prefix . 'followup_delay_unit'] ?? 'hours';

      // Calculate due date
      $due = new DateTime('now', wp_timezone());
      switch ($unit) {
        case 'days':   $due->modify("+{$delay} days"); break;
        case 'weeks':  $due->modify("+{$delay} weeks"); break;
        case 'months': $due->modify("+{$delay} months"); break;
        default:       $due->modify("+{$delay} hours"); break;
      }

      $post_id = wp_insert_post([
        'post_type'    => 'pn_tasks_task',
        // translators: %s is the follow-up action label.
        'post_title'   => sprintf(__('Follow-up: %s', 'pn-customers-manager'), $label),
        // translators: %1$s is the contact name, %2$s is the follow-up action label.
        'post_content' => sprintf(__('Automatic follow-up for %1$s. Action: %2$s', 'pn-customers-manager'), $contact_name, $label),
        'post_status'  => 'publish',
      ]);

      if (!is_wp_error($post_id)) {
        update_post_meta($post_id, 'pn_cm_linked_client', $client['type'] . '_' . $client['id']);
        update_post_meta($post_id, 'pn_tasks_manager_task_date', $due->format('Y-m-d'));
        update_post_meta($post_id, 'pn_tasks_manager_task_time', $due->format('H:i'));
        self::log('FOLLOWUP — created task #' . $post_id . ' for key=' . $key . ' client=' . $client['type'] . '_' . $client['id'] . ' due=' . $due->format('Y-m-d H:i'));
      } else {
        self::log('FOLLOWUP — failed to create task for key=' . $key . ': ' . $post_id->get_error_message());
      }
    }
  }

  /**
   * Resolve a phone number to a CRM client (user or organization).
   *
   * @param  string $phone Phone number to look up.
   * @return array|null  Array with 'type' and 'id', or null if not found.
   */
  private static function resolve_crm_client($phone) {
    // Try user by billing_phone
    $users = get_users([
      'meta_key'   => 'billing_phone',
      'meta_value' => $phone,
      'number'     => 1,
      'fields'     => 'ID',
    ]);
    if (empty($users)) {
      $users = get_users([
        'meta_key'   => 'billing_phone',
        'meta_value' => ltrim($phone, '+'),
        'number'     => 1,
        'fields'     => 'ID',
      ]);
    }
    if (!empty($users)) {
      return ['type' => 'user', 'id' => (int) $users[0]];
    }

    // Try organization by phone meta
    $orgs = get_posts([
      'post_type'      => 'pn_cm_organization',
      'meta_key'       => 'pn_cm_organization_phone',
      'meta_value'     => $phone,
      'posts_per_page' => 1,
      'fields'         => 'ids',
    ]);
    if (empty($orgs)) {
      $orgs = get_posts([
        'post_type'      => 'pn_cm_organization',
        'meta_key'       => 'pn_cm_organization_phone',
        'meta_value'     => ltrim($phone, '+'),
        'posts_per_page' => 1,
        'fields'         => 'ids',
      ]);
    }
    if (!empty($orgs)) {
      return ['type' => 'organization', 'id' => (int) $orgs[0]];
    }

    return null;
  }

  /**
   * Find the next allowed schedule window and return it as a UTC DateTime.
   *
   * @param  array  $node_config
   * @param  string $user_timezone
   * @return DateTime  UTC DateTime of next allowed send time
   */
  private static function get_next_schedule_window($node_config, $user_timezone) {
    $schedule_days = $node_config['wa_schedule_days'] ?? '';
    if (is_string($schedule_days)) {
      $schedule_days = json_decode($schedule_days, true);
    }

    $day_keys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    try {
      $tz  = new DateTimeZone($user_timezone);
      $now = new DateTime('now', $tz);
    } catch (Exception $e) {
      // Fallback: schedule for 5 minutes from now in UTC
      return new DateTime('+5 minutes', new DateTimeZone('UTC'));
    }

    // Check today first (if we're before the "from" time), then next 7 days
    for ($offset = 0; $offset <= 7; $offset++) {
      $check_date  = clone $now;
      $check_date->modify('+' . $offset . ' days');
      $day_index   = (int) $check_date->format('N') - 1;
      $day_key     = $day_keys[$day_index];

      if (!isset($schedule_days[$day_key]) || empty($schedule_days[$day_key]['enabled'])) {
        continue;
      }

      $from = $schedule_days[$day_key]['from'] ?? '09:00';

      // Build the candidate DateTime in user's timezone
      $candidate = clone $check_date;
      list($h, $m) = explode(':', $from);
      $candidate->setTime((int) $h, (int) $m, 0);

      // For today, only use if the "from" time is still in the future
      if ($offset === 0 && $candidate <= $now) {
        continue;
      }

      // Convert to UTC
      $candidate->setTimezone(new DateTimeZone('UTC'));
      return $candidate;
    }

    // No window found in next 7 days — schedule 24h from now as fallback
    $fallback = new DateTime('+24 hours', new DateTimeZone('UTC'));
    return $fallback;
  }

  /**
   * Send a message immediately or queue it for later delivery.
   *
   * @param  string $phone
   * @param  string $text
   * @param  int    $conversation_id
   * @param  array  $node_config
   * @param  array  $images_data      Optional array of image data to send
   * @return bool   True if sent immediately, false if queued
   */
  private static function maybe_send_or_queue($phone, $text, $conversation_id, $node_config, $images_data = []) {
    // If no schedule is enabled, send immediately
    if (empty($node_config['wa_schedule_enabled'])) {
      self::send_message_with_images($phone, $text, $images_data);
      return true;
    }

    $user_timezone = self::resolve_and_store_user_timezone($phone);

    if (self::is_within_schedule($node_config, $user_timezone)) {
      self::send_message_with_images($phone, $text, $images_data);
      return true;
    }

    // Queue the message
    $scheduled_for = self::get_next_schedule_window($node_config, $user_timezone);

    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_message_queue';

    $wpdb->insert($table, [
      'phone_number'    => $phone,
      'message_text'    => $text,
      'message_images'  => !empty($images_data) ? wp_json_encode($images_data) : null,
      'conversation_id' => $conversation_id,
      'funnel_id'       => 0,
      'node_id'         => '',
      'scheduled_for'   => $scheduled_for->format('Y-m-d H:i:s'),
      'status'          => 'pending',
      'attempts'        => 0,
      'created_at'      => current_time('mysql', true), // UTC
    ]);

    self::log('Message queued for ' . $phone . ' — scheduled_for=' . $scheduled_for->format('Y-m-d H:i:s') . ' UTC (user_tz=' . $user_timezone . ')');

    return false;
  }

  /**
   * Send a text message and any associated images.
   *
   * @param string $phone
   * @param string $text
   * @param array  $images_data
   */
  private static function send_message_with_images($phone, $text, $images_data = []) {
    // Send images first (if any)
    if (!empty($images_data)) {
      foreach ($images_data as $img) {
        if (!empty($img['url'])) {
          self::send_whatsapp_image($phone, $img['url'], $img['product_name'] ?? '');
        }
      }
    }

    // Send text
    self::send_whatsapp_message($phone, $text);
  }

  /**
   * Process the message queue (cron handler).
   * Sends pending messages whose scheduled_for has passed.
   */
  public static function process_message_queue() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_message_queue';

    $now_utc = gmdate('Y-m-d H:i:s');

    $messages = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table} WHERE status = 'pending' AND scheduled_for <= %s ORDER BY scheduled_for ASC LIMIT 50",
      $now_utc
    ));

    if (empty($messages)) {
      return;
    }

    self::log('[QUEUE] Processing ' . count($messages) . ' queued messages');

    foreach ($messages as $msg) {
      $images_data = [];
      if (!empty($msg->message_images)) {
        $images_data = json_decode($msg->message_images, true) ?: [];
      }

      // Send images first
      if (!empty($images_data)) {
        foreach ($images_data as $img) {
          if (!empty($img['url'])) {
            self::send_whatsapp_image($msg->phone_number, $img['url'], $img['product_name'] ?? '');
          }
        }
      }

      // Send text
      $success = self::send_whatsapp_message($msg->phone_number, $msg->message_text);

      if ($success) {
        $wpdb->update($table, [
          'status'  => 'sent',
          'sent_at' => gmdate('Y-m-d H:i:s'),
        ], ['id' => $msg->id]);

        self::log('[QUEUE] Sent message id=' . $msg->id . ' to ' . $msg->phone_number);
      } else {
        $attempts = (int) $msg->attempts + 1;
        $new_status = $attempts >= 3 ? 'failed' : 'pending';

        $wpdb->update($table, [
          'status'        => $new_status,
          'attempts'      => $attempts,
          'error_message' => 'Send failed on attempt ' . $attempts,
        ], ['id' => $msg->id]);

        self::log('[QUEUE] Failed message id=' . $msg->id . ' to ' . $msg->phone_number . ' (attempt ' . $attempts . '/3)');
      }
    }
  }
}
