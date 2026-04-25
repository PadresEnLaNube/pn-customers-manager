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
      . "The URLs are machine-generated and any modification will break them.";
  }

  protected static function get_image_rules($include_images) {
    if ($include_images) {
      return "PRODUCT IMAGES RULES:\n"
        . "- Each product in the catalog has an Image tag like [PRODUCT_IMAGES:ID].\n"
        . "- When you mention or recommend a specific product, ALWAYS include its Image tag in your response. The system will replace it with the actual photo sent as a native WhatsApp image.\n"
        . "- Example: if recommending product ID 5052, write [PRODUCT_IMAGES:5052] somewhere in your message.\n"
        . "- You can include multiple Image tags if recommending several products.\n"
        . "- NEVER invent image URLs. NEVER use markdown image syntax ![](). NEVER write URLs ending in .jpg/.png/.webp.\n"
        . "- The ONLY way to show images is [PRODUCT_IMAGES:ID] with the exact ID from the catalog.";
    }

    return "PRODUCT IMAGES RULES:\n"
      . "- The system automatically sends product images as WhatsApp photos when you mention a product URL.\n"
      . "- If the customer asks to see a product image, you can also include [PRODUCT_IMAGES:ID] (replacing ID with the product's numeric ID) in your response. The system will send the actual photo.\n"
      . "- NEVER invent image URLs. NEVER use markdown image syntax ![](). NEVER write URLs ending in .jpg/.png/.webp.\n"
      . "- The ONLY way to show images is via product URLs (automatic) or [PRODUCT_IMAGES:ID].";
  }

  /* ================================================================
   * REST ROUTES
   * ================================================================ */

  /**
   * Register REST API routes for the WhatsApp webhook.
   */
  public static function register_routes() {
    register_rest_route(self::REST_NAMESPACE, '/whatsapp/webhook', [
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

  /**
   * Handle Meta webhook verification challenge.
   *
   * @param WP_REST_Request $request
   */
  public static function handle_webhook_verify($request) {
    $mode      = isset($_GET['hub_mode'])         ? sanitize_text_field(wp_unslash($_GET['hub_mode']))         : '';
    $token     = isset($_GET['hub_verify_token'])  ? sanitize_text_field(wp_unslash($_GET['hub_verify_token']))  : '';
    $challenge = isset($_GET['hub_challenge'])     ? sanitize_text_field(wp_unslash($_GET['hub_challenge']))     : '';

    if (empty($mode)) {
      $mode      = isset($_GET['hub.mode'])         ? sanitize_text_field(wp_unslash($_GET['hub.mode']))         : '';
      $token     = isset($_GET['hub.verify_token'])  ? sanitize_text_field(wp_unslash($_GET['hub.verify_token']))  : '';
      $challenge = isset($_GET['hub.challenge'])     ? sanitize_text_field(wp_unslash($_GET['hub.challenge']))     : '';
    }

    $stored_token = get_option('pn_customers_manager_whatsapp_verify_token', '');

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

  /**
   * Handle incoming WhatsApp message from Meta webhook.
   *
   * @param WP_REST_Request $request
   * @return WP_REST_Response
   */
  public static function handle_webhook_message($request) {
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
          self::log('Webhook — message type: ' . ($message['type'] ?? 'unknown') . ' from: ' . ($message['from'] ?? 'unknown'));

          if (!isset($message['type']) || $message['type'] !== 'text') {
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
          $text         = sanitize_text_field($message['text']['body']);
          $contact_name = '';

          foreach ($contacts as $contact) {
            if (isset($contact['wa_id']) && $contact['wa_id'] === $from) {
              $contact_name = isset($contact['profile']['name'])
                ? sanitize_text_field($contact['profile']['name'])
                : '';
              break;
            }
          }

          $pending_messages[] = [
            'from'         => $from,
            'text'         => $text,
            'contact_name' => $contact_name,
          ];
        }
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
          PN_CUSTOMERS_MANAGER_WhatsApp_AI::deferred_process_message(
            $msg['from'],
            $msg['text'],
            $msg['contact_name']
          );
        }
      });
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
  }

  /**
   * Public wrapper for process_incoming_message (called from shutdown hook).
   *
   * @param string $phone
   * @param string $text
   * @param string $contact_name
   */
  public static function deferred_process_message($phone, $text, $contact_name) {
    self::process_incoming_message($phone, $text, $contact_name);
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
  private static function process_incoming_message($phone, $text, $contact_name) {
    self::log('Processing message — phone=' . $phone . ' text=' . mb_substr($text, 0, 50));

    $conversation = self::get_or_create_conversation($phone, $contact_name);

    if (!$conversation) {
      self::log('ERROR — could not get/create conversation for ' . $phone);
      return;
    }

    self::log('Conversation ID=' . $conversation->id . ' funnel_id=' . $conversation->funnel_id . ' node_id=' . $conversation->node_id);

    // Add user message to history
    $messages   = json_decode($conversation->messages, true) ?: [];
    $messages[] = [
      'role'      => 'user',
      'content'   => $text,
      'timestamp' => current_time('mysql'),
    ];

    // Read node config once
    $node_config = self::get_node_config($conversation);
    if (empty($node_config)) {
      $wa_node = self::find_ai_node();
      if ($wa_node && !empty($wa_node['config'])) {
        $node_config = $wa_node['config'];
      }
    }

    $require_postal = !empty($node_config['wa_require_postal_code']);
    self::log('process_incoming_message — require_postal=' . ($require_postal ? 'YES' : 'NO')
      . ' raw_value=' . var_export($node_config['wa_require_postal_code'] ?? null, true)
      . ' node_config_keys=' . implode(',', array_keys($node_config)));

    // Build system prompt with enriched context
    $system_prompt = self::build_enriched_system_prompt($conversation);

    self::log('FINAL system_prompt length=' . mb_strlen($system_prompt) . ' preview=' . mb_substr($system_prompt, 0, 300));

    // Determine AI model (trait helper)
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

      // Call OpenAI
      $ai_response = self::call_openai($openai_messages, $ai_model, $temperature);

      if ($ai_response === false) {
        $ai_response = self::get_error_fallback_message($node_config);
        $is_fallback = true;
        self::log('call_openai returned false — using configured error fallback message');
      }
    }

    $sent_images = [];
    if ($is_fallback) {
      // Static error message — skip all post-processing that is meant for
      // model output (postal code enforcement, order detection, image
      // extraction, URL validation, etc.) and send it as-is.
      $ai_response = self::markdown_to_whatsapp($ai_response);
    } else {
      // Strip system prompt fragments the model may have leaked
      $ai_response = self::sanitize_system_prompt_leak($ai_response);

      // Enforce postal code rule
      $ai_response = self::enforce_postal_code_rule($ai_response, $messages, $require_postal, $conversation);

      // Convert Markdown formatting to WhatsApp formatting
      $ai_response = self::markdown_to_whatsapp($ai_response);

      // Strip fabricated image URLs
      $ai_response = self::strip_hallucinated_image_urls($ai_response);

      // Validate URLs in the response
      $ai_response = self::validate_response_urls($ai_response);

      // Detect order confirmation tag and send email notification
      $ai_response = self::detect_and_notify_order($ai_response, $conversation, $messages, $node_config);

      // Detect special order tag and forward by email
      $ai_response = self::detect_and_notify_special_order($ai_response, $conversation, $messages, $node_config);

      // Collect product IDs whose images were already sent in this
      // conversation so we never send the same photo twice.
      $already_sent_ids = [];
      foreach ($messages as $msg) {
        if (!empty($msg['images'])) {
          foreach ($msg['images'] as $img) {
            if (!empty($img['product_id'])) {
              $already_sent_ids[] = (int) $img['product_id'];
            }
          }
        }
      }
      $already_sent_ids = array_unique($already_sent_ids);

      // Auto-inject image tags for products mentioned by URL.
      // When the recommendation protocol is active the AI should control
      // image delivery (Step 3). However GPT-4o-mini often forgets the
      // [PRODUCT_IMAGES:ID] tag when showing a specific product. We
      // auto-inject when the response mentions only 1-2 product URLs
      // (specific showcase) but skip when 3+ URLs appear (listing phase).
      $woo_active = !empty($node_config['wa_include_woo']);
      $recommendations_disabled = !empty($node_config['wa_disable_recommendations']);
      if (!$woo_active || $recommendations_disabled) {
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
        if ($url_count >= 1 && $url_count <= 2) {
          $ai_response = self::auto_inject_product_image_tags($ai_response);
        }
      }

      // Extract and send product images (before sending text).
      // Pass already-sent IDs so images are not re-sent within the
      // same conversation.
      $ai_response = self::extract_and_send_product_images($ai_response, $phone, $sent_images, $already_sent_ids);
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
    ];
    if (!empty($sent_images)) {
      $assistant_msg['images'] = $sent_images;
    }
    $messages[] = $assistant_msg;

    // Update conversation in DB
    self::update_conversation_messages($conversation->id, $messages);

    // Send response via WhatsApp
    self::log('FINAL RESPONSE TO SEND: ' . mb_substr($ai_response, 0, 300));
    self::send_whatsapp_message($phone, $ai_response);
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
   * @return bool
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

    return $code >= 200 && $code < 300;
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
      @unlink($file_path);
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
      if (strpos($text, '[PRODUCT_IMAGES:' . $product_id . ']') !== false) {
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
      foreach ($matches[1] as $product_id) {
        $product_id = (int) $product_id;

        // Skip products whose images were already sent in this conversation.
        if (in_array($product_id, $already_sent_ids, true)) {
          self::log('extract_and_send_product_images — product ' . $product_id . ' already sent in conversation, skipping');
          continue;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
          self::log('extract_and_send_product_images — product ' . $product_id . ' not found');
          continue;
        }

        $product_name = $product->get_name();
        $image_urls   = [];

        // Main image
        $thumb_id = get_post_thumbnail_id($product_id);
        if ($thumb_id) {
          $main_url = wp_get_attachment_url($thumb_id);
          if ($main_url) {
            $image_urls[] = $main_url;
          }
        }

        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $gallery_id) {
          if (count($image_urls) >= 3) {
            break;
          }
          $gallery_url = wp_get_attachment_url($gallery_id);
          if ($gallery_url) {
            $image_urls[] = $gallery_url;
          }
        }

        self::log('extract_and_send_product_images — product ' . $product_id . ' (' . $product_name . ') — ' . count($image_urls) . ' images');

        foreach ($image_urls as $url) {
          self::send_whatsapp_image($phone, $url, $product_name);
          $sent_images[] = [
            'url'          => $url,
            'product_name' => $product_name,
            'product_id'   => $product_id,
          ];
        }
      }

      // Remove all tags from the text
      $text = preg_replace('/\s*\[PRODUCT_IMAGES:\d+\]/', '', $text);
      $text = trim($text);
    }

    return $text;
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
  private static function get_or_create_conversation($phone, $contact_name = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $conversation = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE phone_number = %s AND status = 'active' ORDER BY updated_at DESC LIMIT 1",
      $phone
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

    $wa_node = self::find_ai_node();
    if ($wa_node) {
      $funnel_id = $wa_node['funnel_id'];
      $node_id   = $wa_node['node_id'];

      if (!empty($wa_node['config']['wa_system_prompt'])) {
        $system_prompt = $wa_node['config']['wa_system_prompt'];
      }
      if (!empty($wa_node['config']['wa_ai_model'])) {
        $ai_model = $wa_node['config']['wa_ai_model'];
      }

      self::log('Auto-linked conversation to funnel=' . $funnel_id . ' node=' . $node_id);
    }

    $wpdb->insert($table, [
      'phone_number'  => $phone,
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
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $system_prompt = get_option('pn_customers_manager_whatsapp_system_prompt', '');
    $ai_model      = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');
    $welcome_msg   = '';

    $canvas_data = get_post_meta($funnel_id, 'pn_cm_funnel_canvas', true);
    if ($canvas_data) {
      $data = json_decode($canvas_data, true);
      if ($data && isset($data['nodes'])) {
        foreach ($data['nodes'] as $node) {
          if ($node['id'] === $node_id && isset($node['config'])) {
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

    $messages = [];
    if (!empty($welcome_msg)) {
      // The welcome message may contain HTML from a WYSIWYG editor.
      $welcome_msg = self::strip_html_to_plain_text($welcome_msg);

      $messages[] = [
        'role'      => 'assistant',
        'content'   => $welcome_msg,
        'timestamp' => current_time('mysql'),
      ];
      self::send_whatsapp_message($phone, $welcome_msg);
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

    return $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE id = %d",
      $id
    ));
  }

  /**
   * Update messages JSON for a conversation.
   *
   * @param int   $conversation_id
   * @param array $messages
   */
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
    $api_key = get_option('pn_customers_manager_whatsapp_openai_key', '');

    if (empty($api_key)) {
      return [
        'error_key'     => 'no_api_key',
        'error_content' => esc_html__('OpenAI API Key has not been configured.', 'pn-customers-manager'),
      ];
    }

    $model       = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');
    $temperature = (float) get_option('pn_customers_manager_whatsapp_temperature', 0.7);

    $messages = [
      ['role' => 'system', 'content' => 'Answer in a single short sentence.'],
      ['role' => 'user', 'content' => 'Say "Connection successful" and the name of the model you are.'],
    ];

    $response = self::call_openai($messages, $model, $temperature);

    if ($response === false) {
      return [
        'error_key'     => 'openai_error',
        'error_content' => esc_html__('Could not connect to OpenAI. Verify the API Key and that you have available credit.', 'pn-customers-manager'),
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
      return [
        'found'      => false,
        'server_time' => current_time('mysql'),
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

    $row = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE updated_at >= %s ORDER BY updated_at DESC LIMIT 1",
      $since
    ));

    if (!$row) {
      return [
        'found' => false,
      ];
    }

    $messages = json_decode($row->messages, true) ?: [];
    $found_msg = null;

    foreach ($messages as $msg) {
      if ($msg['role'] === 'user' && isset($msg['timestamp']) && $msg['timestamp'] >= $since) {
        $found_msg = $msg;
      }
    }

    if (!$found_msg) {
      return [
        'found' => false,
      ];
    }

    return [
      'found'   => true,
      'phone'   => $row->phone_number,
      'name'    => $row->contact_name,
      'message' => $found_msg['content'],
      'time'    => $found_msg['timestamp'],
    ];
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
}
