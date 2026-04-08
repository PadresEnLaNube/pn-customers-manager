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

  /**
   * REST API namespace.
   */
  const REST_NAMESPACE = 'pn-cm/v1';

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
   * Meta sends hub.mode, hub.verify_token, hub.challenge as GET params.
   * It expects the challenge returned as PLAIN TEXT (not JSON).
   *
   * @param WP_REST_Request $request
   */
  public static function handle_webhook_verify($request) {
    // Meta sends params with dots: hub.mode, hub.verify_token, hub.challenge
    // WordPress may or may not convert dots to underscores, so check both.
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
      // Meta expects the challenge as PLAIN TEXT, not JSON-encoded.
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

    // Collect messages to process — return 200 to Meta immediately, then process
    $pending_messages = [];

    foreach ($body['entry'] as $entry) {
      if (empty($entry['changes'])) {
        continue;
      }

      foreach ($entry['changes'] as $change) {
        // Handle status updates (message delivered/read) — just acknowledge
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

          // Deduplicate: skip if this message ID was already processed
          $msg_id = isset($message['id']) ? sanitize_text_field($message['id']) : '';
          if (!empty($msg_id)) {
            $transient_key = 'pn_cm_wa_msg_' . md5($msg_id);
            if (get_transient($transient_key)) {
              self::log('Webhook — SKIPPING duplicate message id=' . $msg_id);
              continue;
            }
            // Mark as processed (keep for 5 minutes to catch retries)
            set_transient($transient_key, 1, 300);
          }

          $from         = sanitize_text_field($message['from']);
          $text         = sanitize_text_field($message['text']['body']);
          $contact_name = '';

          // Try to get contact name from the webhook payload
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

    // Schedule deferred processing: send 200 to Meta first, then process messages
    if (!empty($pending_messages)) {
      add_action('shutdown', function () use ($pending_messages) {
        // Flush response to Meta before processing (prevents retries and frees the connection)
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

    // Find or create conversation
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

    // Read node config once for both system prompt and postal code rule
    $node_config = self::get_node_config($conversation);
    if (empty($node_config)) {
      $wa_node = self::find_whatsapp_ai_node();
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

    // Determine AI model. Priority: funnel node config > global option (always fresh) > conversation (stale fallback).
    // The global option must take priority over the conversation value because the conversation
    // stores the model at creation time and never updates when the admin changes the setting.
    $global_ai_model = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');
    if (!empty($node_config['wa_ai_model'])) {
      $ai_model    = $node_config['wa_ai_model'];
      $model_source = 'node_config';
    } elseif (!empty($global_ai_model)) {
      $ai_model    = $global_ai_model;
      $model_source = 'global_option';
    } else {
      $ai_model    = !empty($conversation->ai_model) ? $conversation->ai_model : 'gpt-4o-mini';
      $model_source = 'conversation';
    }
    self::log('process_incoming_message — ai_model=' . $ai_model
      . ' (source=' . $model_source . ')'
      . ' node_config[wa_ai_model]=' . var_export($node_config['wa_ai_model'] ?? null, true)
      . ' global_option=' . var_export($global_ai_model, true)
      . ' conversation->ai_model=' . var_export($conversation->ai_model ?? null, true));

    // Sync model to conversation DB if the effective model changed
    if ($ai_model !== ($conversation->ai_model ?? '')) {
      global $wpdb;
      $wpdb->update(
        $wpdb->prefix . 'pn_cm_whatsapp_conversations',
        ['ai_model' => $ai_model],
        ['id' => $conversation->id]
      );
      self::log('process_incoming_message — synced ai_model to DB: ' . $ai_model);
    }

    $temperature = self::get_conversation_temperature($conversation);

    $openai_messages = [];

    if (!empty($system_prompt)) {
      $openai_messages[] = [
        'role'    => 'system',
        'content' => $system_prompt,
      ];
    }

    // Add conversation history (only role + content for OpenAI)
    foreach ($messages as $msg) {
      $openai_messages[] = [
        'role'    => $msg['role'],
        'content' => $msg['content'],
      ];
    }

    // Call OpenAI
    $ai_response = self::call_openai($openai_messages, $ai_model, $temperature);

    if ($ai_response === false) {
      $ai_response = 'Sorry, I cannot respond right now. Please try again later.';
    }

    // Strip system prompt fragments the model may have leaked into the response
    $ai_response = self::sanitize_system_prompt_leak($ai_response);

    // Enforce postal code rule: if enabled and no postal code received yet, override the response
    $ai_response = self::enforce_postal_code_rule($ai_response, $messages, $require_postal, $conversation);

    // Convert Markdown formatting to WhatsApp formatting
    $ai_response = self::markdown_to_whatsapp($ai_response);

    // Strip fabricated image URLs the AI may have hallucinated (markdown images, bare image URLs)
    $ai_response = self::strip_hallucinated_image_urls($ai_response);

    // Validate URLs in the response — remove broken links (404, etc.)
    $ai_response = self::validate_response_urls($ai_response);

    // Detect order confirmation tag and send email notification
    $ai_response = self::detect_and_notify_order($ai_response, $conversation, $messages, $node_config);

    // Auto-inject image tags for products mentioned by URL but missing [PRODUCT_IMAGES:id]
    $ai_response = self::auto_inject_product_image_tags($ai_response);

    // Extract and send product images (before sending text)
    $sent_images = [];
    $ai_response = self::extract_and_send_product_images($ai_response, $phone, $sent_images);

    // Add assistant message to history (include image URLs if any were sent)
    // Annotate the stored content so the AI knows images were sent in follow-up turns
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

  /**
   * Build an enriched system prompt that includes:
   * - Base system prompt (node or global)
   * - Knowledge base content
   * - WooCommerce products (if enabled)
   * - Blog posts (if enabled)
   * - Pages (if enabled)
   *
   * @param object $conversation
   * @return string
   */
  private static function build_enriched_system_prompt($conversation) {
    // Always read the CURRENT global prompt (not the stale value stored in the conversation)
    $base_prompt = get_option('pn_customers_manager_whatsapp_system_prompt', '');

    self::log('build_enriched_system_prompt — global prompt length=' . mb_strlen($base_prompt));

    // Get node config if available
    $node_config = self::get_node_config($conversation);

    self::log('build_enriched_system_prompt — get_node_config returned ' . (empty($node_config) ? 'EMPTY' : count($node_config) . ' keys: ' . implode(',', array_keys($node_config))));

    // If node config is empty, always try to find a whatsapp_ai node dynamically
    if (empty($node_config)) {
      self::log('build_enriched_system_prompt — node_config empty, searching for whatsapp_ai node...');
      $wa_node = self::find_whatsapp_ai_node();
      if ($wa_node && !empty($wa_node['config'])) {
        $node_config = $wa_node['config'];

        // Update the conversation to link it for future messages
        global $wpdb;
        $wpdb->update(
          $wpdb->prefix . 'pn_cm_whatsapp_conversations',
          [
            'funnel_id' => $wa_node['funnel_id'],
            'node_id'   => $wa_node['node_id'],
          ],
          ['id' => $conversation->id]
        );

        if (!empty($wa_node['config']['wa_ai_model'])) {
          $wpdb->update(
            $wpdb->prefix . 'pn_cm_whatsapp_conversations',
            ['ai_model' => $wa_node['config']['wa_ai_model']],
            ['id' => $conversation->id]
          );
        }

        self::log('build_enriched_system_prompt — auto-linked conversation to funnel=' . $wa_node['funnel_id'] . ' node=' . $wa_node['node_id'] . ' config_keys=' . implode(',', array_keys($node_config)));
      } else {
        self::log('build_enriched_system_prompt — find_whatsapp_ai_node returned ' . ($wa_node ? 'node with empty config' : 'null'));
      }
    }

    // Node-level prompt overrides global prompt if set
    if (!empty($node_config['wa_system_prompt'])) {
      $base_prompt = $node_config['wa_system_prompt'];
      self::log('build_enriched_system_prompt — using node-level prompt override (' . mb_strlen($base_prompt) . ' chars)');
    }

    self::log('build_enriched_system_prompt — funnel_id=' . $conversation->funnel_id
      . ' node_id=' . $conversation->node_id
      . ' node_config_keys=' . implode(',', array_keys($node_config))
      . ' has_base_prompt=' . (!empty($base_prompt) ? 'yes(' . mb_strlen($base_prompt) . ')' : 'no'));

    $parts = [];

    // 0. Current date and time (explicit day type so the AI can match schedules)
    $tz        = wp_timezone();
    $now       = new DateTimeImmutable('now', $tz);
    $day_num   = (int) $now->format('N'); // 1=Monday … 7=Sunday
    $day_names = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
    $day_type  = $day_num <= 5 ? 'weekday (lunes-viernes)' : ($day_num === 6 ? 'sábado' : 'domingo');
    $parts[]   = "CURRENT DATE AND TIME: " . $day_names[$day_num] . ", " . wp_date('j F Y, H:i', null, $tz)
      . " (" . wp_timezone_string() . "). Day type: " . $day_type . ".";

    // 1. WhatsApp formatting rules (always included)
    $parts[] = "FORMATTING RULES: You are responding via WhatsApp. "
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

    // Postal code requirement (top-level behavioral instruction)
    $require_postal = !empty($node_config['wa_require_postal_code']);
    if ($require_postal) {
      $parts[] = "MANDATORY RULE — POSTAL CODE REQUIRED FOR SHIPPING:\n"
        . "You CAN freely show product information, prices, images, and purchase links at any time — that is NOT affected by this rule.\n"
        . "However, you are FORBIDDEN from giving any SHIPPING cost, DELIVERY estimate, or DELIVERY confirmation unless the customer has provided their POSTAL CODE.\n"
        . "If the customer asks about shipping or delivery and you do NOT have a postal code, ask for it. Example: \"Para poder indicarte si realizamos envíos a tu zona y el coste exacto, ¿podrías facilitarme tu código postal?\"\n"
        . "IMPORTANT: A postal code provided earlier for a DIFFERENT address does NOT count. Each new shipping inquiry requires its own postal code.\n"
        . "Do NOT say \"we deliver to [location]\" or \"shipping costs X€\" without having the postal code first.";
    }

    // Order acceptance via chat (configured per funnel node)
    $enable_chat_orders = !empty($node_config['wa_enable_chat_orders']);

    if ($enable_chat_orders) {
      $parts[] = "ORDER ACCEPTANCE PROTOCOL:\n"
        . "You CAN accept orders through this chat. When the customer EXPLICITLY confirms they want to place an order "
        . "(e.g. \"sí, preparadlo\", \"quiero hacer el pedido\", \"confirmo el pedido\", \"adelante con el pedido\"), "
        . "you MUST include the tag [PEDIDO_CONFIRMADO] somewhere in your response (the system will strip it before the customer sees it).\n"
        . "IMPORTANT RULES:\n"
        . "- ONLY use [PEDIDO_CONFIRMADO] when there is an EXPLICIT order confirmation, NEVER on casual browsing or product questions.\n"
        . "- Include [PEDIDO_CONFIRMADO] exactly ONCE per confirmed order.\n"
        . "- In the same message, confirm the order to the customer naturally (e.g. \"Perfecto, preparamos tu pedido.\").\n"
        . "- Make sure you have gathered the necessary details (products, quantities) before confirming.";
    } else {
      $parts[] = "ORDER POLICY:\n"
        . "You CANNOT accept or confirm orders through this chat. "
        . "If a customer wants to place an order, politely redirect them to call by phone or use the contact methods available on the website.\n"
        . "NEVER include the tag [PEDIDO_CONFIRMADO] in your responses.";
    }

    // 1. Base prompt
    if (!empty($base_prompt)) {
      $parts[] = $base_prompt;
    }

    // 2. Structured business context fields
    $strip_html = function ($html) {
      // Decode HTML entities first (&nbsp; &amp; etc.)
      $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      // Block-level tags → newlines
      $html = preg_replace('/<\/(p|div|li|tr|h[1-6])>/i', "\n", $html);
      $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
      // Strip remaining tags
      $text = wp_strip_all_tags($html);
      // Normalise whitespace: collapse runs of spaces/tabs on same line
      $text = preg_replace('/[^\S\n]+/', ' ', $text);
      // Collapse 3+ newlines into 2
      $text = preg_replace('/\n{3,}/', "\n\n", $text);
      // Trim each line
      $text = implode("\n", array_map('trim', explode("\n", $text)));
      return trim($text);
    };

    $company_info = isset($node_config['wa_company_info']) ? $strip_html($node_config['wa_company_info']) : '';
    if (!empty($company_info)) {
      $parts[] = "COMPANY INFORMATION:\n" . $company_info;
    }

    // WooCommerce shipping zones (auto-generated)
    $use_wc_shipping = !empty($node_config['wa_wc_shipping_zones'])
      && ($node_config['wa_wc_shipping_zones'] === true || $node_config['wa_wc_shipping_zones'] === 'on' || $node_config['wa_wc_shipping_zones'] === '1');

    $wc_shipping_context = '';
    if ($use_wc_shipping) {
      $wc_shipping_context = self::get_woo_shipping_zones_context();
    }

    $shipping_info = isset($node_config['wa_shipping_info']) ? $strip_html($node_config['wa_shipping_info']) : '';

    if (!empty($wc_shipping_context) || !empty($shipping_info)) {
      $shipping_block = "SHIPPING ZONES AND PRICES (reference data — use to answer once you have the customer's postal code):\n";

      if (!empty($wc_shipping_context)) {
        $shipping_block .= $wc_shipping_context;
      }
      if (!empty($shipping_info)) {
        if (!empty($wc_shipping_context)) {
          $shipping_block .= "\n\nADDITIONAL SHIPPING NOTES:\n";
        }
        $shipping_block .= $shipping_info;
      }

      $parts[] = $shipping_block;
    }

    $schedule_info = isset($node_config['wa_schedule_info']) ? $strip_html($node_config['wa_schedule_info']) : '';
    if (!empty($schedule_info)) {
      $parts[] = "OPENING HOURS:\n" . $schedule_info;
    }

    $knowledge = isset($node_config['wa_knowledge_base']) ? $strip_html($node_config['wa_knowledge_base']) : '';
    if (!empty($knowledge)) {
      $parts[] = "REFERENCE INFORMATION:\n" . $knowledge;
      self::log('build_enriched_system_prompt — added knowledge base (' . mb_strlen($knowledge) . ' chars)');
    }

    // 4. WooCommerce products (configured per funnel node)
    $resolve_node_flag = function ($key) use ($node_config) {
      if (!array_key_exists($key, $node_config)) {
        return false;
      }
      $v = $node_config[$key];
      return $v === true || $v === 'on' || $v === '1' || $v === 1;
    };

    $include_woo = $resolve_node_flag('wa_include_woo');
    if ($include_woo) {
      $include_variations = $resolve_node_flag('wa_include_woo_variations');
      $add_to_cart_links  = $resolve_node_flag('wa_include_woo_add_to_cart');
      $include_woo_images = $resolve_node_flag('wa_include_woo_images');

      self::log('build_enriched_system_prompt — WooCommerce flags:'
        . ' add_to_cart_links=' . ($add_to_cart_links ? 'YES' : 'NO')
        . ' include_woo_images=' . ($include_woo_images ? 'YES' : 'NO'));

      $woo_context = self::get_woo_products_context($include_variations, $add_to_cart_links, $include_woo_images);
      if (!empty($woo_context)) {
        $link_instruction = $add_to_cart_links
          ? 'when a user asks about a product ALWAYS include the "Buy link" so they can add it to cart directly'
          : 'when a user asks about a product ALWAYS include the "Product link" so they can visit the product page';
        $parts[] = "PRODUCT CATALOG (use this data to answer questions about products, prices, availability; {$link_instruction}):\n" . $woo_context;

        // Image rules as a separate, prominent section so the model doesn't miss them
        if ($include_woo_images) {
          $parts[] = "PRODUCT IMAGES RULES:\n"
            . "- Each product in the catalog has an Image tag like [PRODUCT_IMAGES:ID].\n"
            . "- When you mention or recommend a specific product, ALWAYS include its Image tag in your response. The system will replace it with the actual photo sent as a native WhatsApp image.\n"
            . "- Example: if recommending product ID 5052, write [PRODUCT_IMAGES:5052] somewhere in your message.\n"
            . "- You can include multiple Image tags if recommending several products.\n"
            . "- NEVER invent image URLs. NEVER use markdown image syntax ![](). NEVER write URLs ending in .jpg/.png/.webp.\n"
            . "- The ONLY way to show images is [PRODUCT_IMAGES:ID] with the exact ID from the catalog.";
        } else {
          $parts[] = "PRODUCT IMAGES RULES:\n"
            . "- The system automatically sends product images as WhatsApp photos when you mention a product URL.\n"
            . "- If the customer asks to see a product image, you can also include [PRODUCT_IMAGES:ID] (replacing ID with the product's numeric ID) in your response. The system will send the actual photo.\n"
            . "- NEVER invent image URLs. NEVER use markdown image syntax ![](). NEVER write URLs ending in .jpg/.png/.webp.\n"
            . "- The ONLY way to show images is via product URLs (automatic) or [PRODUCT_IMAGES:ID].";
        }
        self::log('build_enriched_system_prompt — added WooCommerce products (variations=' . ($include_variations ? 'yes' : 'no') . ', add_to_cart_links=' . ($add_to_cart_links ? 'yes' : 'no') . ', images=' . ($include_woo_images ? 'yes' : 'no') . ')');
      }
    }

    // 5. Blog posts
    $include_posts = !empty($node_config['wa_include_posts']);
    if ($include_posts) {
      $posts_context = self::get_posts_context();
      if (!empty($posts_context)) {
        $parts[] = "BLOG ARTICLES (use this content to answer user questions; share the URL when relevant):\n" . $posts_context;
        self::log('build_enriched_system_prompt — added blog posts');
      }
    }

    // 6. Pages
    $include_pages = !empty($node_config['wa_include_pages']);
    if ($include_pages) {
      $pages_context = self::get_pages_context();
      if (!empty($pages_context)) {
        $parts[] = "WEBSITE PAGES (use this content to answer user questions; share the URL when relevant):\n" . $pages_context;
        self::log('build_enriched_system_prompt — added pages');
      }
    }

    // MANDATORY style rules — placed last so they override any conflicting instruction above
    $parts[] = "MANDATORY STYLE RULES (override everything above):\n"
      . "- NEVER end your messages with filler phrases. Forbidden examples: "
      . "\"¡Estoy aquí para ayudarte!\", \"No dudes en preguntar\", "
      . "\"Si necesitas más información\", \"Si tienes alguna otra pregunta\", "
      . "\"¡Estaré encantado de ayudarte!\", \"No dudes en contactarnos\", "
      . "\"Estoy a tu disposición\", or ANY variation of these.\n"
      . "- Simply answer the question and stop. Do not add a closing sentence.\n"
      . "- Keep responses short and direct. One or two sentences when possible.\n"
      . "- Sound like a real person texting, not like a corporate chatbot.";

    $final = implode("\n\n", $parts);
    self::log('build_enriched_system_prompt — total parts=' . count($parts) . ' total_length=' . mb_strlen($final));

    return $final;
  }

  /**
   * Get node config array from a conversation's linked funnel node.
   *
   * @param object $conversation
   * @return array
   */
  private static function get_node_config($conversation) {
    if (empty($conversation->funnel_id) || empty($conversation->node_id)) {
      self::log('get_node_config — funnel_id or node_id empty (funnel_id=' . ($conversation->funnel_id ?? 'null') . ' node_id=' . ($conversation->node_id ?? 'null') . ')');
      return [];
    }

    $canvas_data = get_post_meta($conversation->funnel_id, 'pn_cm_funnel_canvas', true);
    if (!$canvas_data) {
      self::log('get_node_config — no canvas data for funnel ' . $conversation->funnel_id);
      return [];
    }

    $data = json_decode($canvas_data, true);
    if (!$data || !isset($data['nodes'])) {
      self::log('get_node_config — invalid JSON or no nodes key for funnel ' . $conversation->funnel_id);
      return [];
    }

    self::log('get_node_config — funnel ' . $conversation->funnel_id . ' has ' . count($data['nodes']) . ' nodes, looking for node_id=' . $conversation->node_id);

    foreach ($data['nodes'] as $node) {
      if ($node['id'] === $conversation->node_id && isset($node['config'])) {
        self::log('get_node_config — FOUND node ' . $node['id'] . ' with config keys: ' . implode(',', array_keys($node['config'])));
        return $node['config'];
      }
    }

    self::log('get_node_config — node ' . $conversation->node_id . ' NOT FOUND in canvas');
    return [];
  }

  /**
   * Find the first WhatsApp AI node across all published funnels.
   *
   * Searches all pn_cm_funnel posts for a node with type 'whatsapp_ai'.
   * Returns the funnel_id, node_id and node config if found.
   *
   * @return array|null  ['funnel_id' => int, 'node_id' => string, 'config' => array] or null
   */
  private static function find_whatsapp_ai_node() {
    $funnels = get_posts([
      'post_type'      => 'pn_cm_funnel',
      'post_status'    => ['publish', 'draft', 'private'],
      'posts_per_page' => -1,
      'fields'         => 'ids',
    ]);

    self::log('find_whatsapp_ai_node — found ' . count($funnels) . ' funnels');

    if (empty($funnels)) {
      self::log('find_whatsapp_ai_node — no funnels found');
      return null;
    }

    foreach ($funnels as $funnel_id) {
      $canvas_data = get_post_meta($funnel_id, 'pn_cm_funnel_canvas', true);
      if (!$canvas_data) {
        self::log('find_whatsapp_ai_node — funnel ' . $funnel_id . ' has no canvas data');
        continue;
      }

      $data = json_decode($canvas_data, true);
      if (!$data || !isset($data['nodes'])) {
        self::log('find_whatsapp_ai_node — funnel ' . $funnel_id . ' has invalid canvas JSON or no nodes');
        continue;
      }

      self::log('find_whatsapp_ai_node — funnel ' . $funnel_id . ' has ' . count($data['nodes']) . ' nodes');

      foreach ($data['nodes'] as $node) {
        $subtype = isset($node['subtype']) ? $node['subtype'] : '';
        $type    = isset($node['type']) ? $node['type'] : '';
        self::log('find_whatsapp_ai_node — node ' . ($node['id'] ?? '?') . ' type=' . $type . ' subtype=' . $subtype);

        if ($subtype === 'whatsapp_ai') {
          $config = isset($node['config']) ? $node['config'] : [];
          self::log('find_whatsapp_ai_node — FOUND whatsapp_ai node! funnel=' . $funnel_id
            . ' node=' . $node['id']
            . ' config_keys=' . implode(',', array_keys($config)));
          return [
            'funnel_id' => $funnel_id,
            'node_id'   => $node['id'],
            'config'    => $config,
          ];
        }
      }
    }

    self::log('find_whatsapp_ai_node — no whatsapp_ai node found in any funnel');
    return null;
  }

  /**
   * Build a structured text summary of WooCommerce shipping zones for the AI context.
   * Includes zone names, regions, shipping methods and exact costs.
   *
   * @return string Plain-text shipping zones data, or empty string.
   */
  private static function get_woo_shipping_zones_context() {
    if (!class_exists('WooCommerce') || !class_exists('WC_Shipping_Zones')) {
      return '';
    }

    $zones  = \WC_Shipping_Zones::get_zones();
    $blocks = [];

    // Also include the "Rest of the World" zone (ID 0)
    $rest_zone  = new \WC_Shipping_Zone(0);
    $all_zones  = $zones;
    $all_zones[0] = [
      'id'               => 0,
      'zone_name'        => $rest_zone->get_zone_name(),
      'zone_locations'   => $rest_zone->get_zone_locations(),
      'shipping_methods' => $rest_zone->get_shipping_methods(true),
    ];

    foreach ($all_zones as $zone_data) {
      $zone_id = $zone_data['id'] ?? 0;

      if ($zone_id === 0) {
        $zone_obj = $rest_zone;
      } else {
        $zone_obj = new \WC_Shipping_Zone($zone_id);
      }

      $zone_name = $zone_obj->get_zone_name();
      $locations = $zone_obj->get_zone_locations();
      $methods   = $zone_obj->get_shipping_methods(true); // true = enabled only

      if (empty($methods)) {
        continue;
      }

      // Build region list
      $region_names = [];
      foreach ($locations as $location) {
        $loc_code = $location->code;
        $loc_type = $location->type;

        if ($loc_type === 'country') {
          $countries = WC()->countries->get_countries();
          $region_names[] = $countries[$loc_code] ?? $loc_code;
        } elseif ($loc_type === 'state') {
          $parts_loc = explode(':', $loc_code);
          $country_code = $parts_loc[0];
          $state_code   = $parts_loc[1] ?? '';
          $countries = WC()->countries->get_countries();
          $states    = WC()->countries->get_states($country_code);
          $country_name = $countries[$country_code] ?? $country_code;
          $state_name   = $states[$state_code] ?? $state_code;
          $region_names[] = $state_name . ' (' . $country_name . ')';
        } elseif ($loc_type === 'postcode') {
          $region_names[] = __('Postcode', 'pn-customers-manager') . ': ' . $loc_code;
        } elseif ($loc_type === 'continent') {
          $continents = WC()->countries->get_continents();
          $region_names[] = $continents[$loc_code]['name'] ?? $loc_code;
        }
      }

      $block = '--- SHIPPING ZONE: ' . $zone_name . " ---\n";

      if (!empty($region_names)) {
        $block .= 'Regions: ' . implode(', ', $region_names) . "\n";
      } elseif ($zone_id === 0) {
        $block .= "Regions: All locations not covered by other zones\n";
      }

      $block .= "Available shipping methods:\n";

      foreach ($methods as $method) {
        $method_title = $method->get_title();
        $method_type  = $method->id; // flat_rate, free_shipping, local_pickup, etc.
        $settings     = $method->instance_settings;

        $line = '  - ' . $method_title;

        if ($method_type === 'free_shipping') {
          $line .= ': FREE';
          $min_amount = $settings['min_amount'] ?? '';
          $requires   = $settings['requires'] ?? '';
          if (!empty($min_amount) && $requires !== '') {
            $req_label = '';
            switch ($requires) {
              case 'min_amount':
                $req_label = __('minimum order', 'pn-customers-manager');
                break;
              case 'coupon':
                $req_label = __('valid coupon', 'pn-customers-manager');
                break;
              case 'both':
                $req_label = __('minimum order + coupon', 'pn-customers-manager');
                break;
              case 'either':
                $req_label = __('minimum order or coupon', 'pn-customers-manager');
                break;
            }
            if (!empty($req_label)) {
              $line .= ' (requires ' . $req_label;
              if (in_array($requires, ['min_amount', 'both', 'either'], true) && !empty($min_amount)) {
                $line .= ' of ' . html_entity_decode(strip_tags(wc_price($min_amount)), ENT_QUOTES, 'UTF-8');
              }
              $line .= ')';
            }
          }
        } elseif ($method_type === 'flat_rate') {
          $cost = $settings['cost'] ?? '';
          if ($cost !== '') {
            $line .= ': ' . html_entity_decode(strip_tags(wc_price($cost)), ENT_QUOTES, 'UTF-8');
          }
          // Check for shipping classes costs
          if (!empty($settings)) {
            $class_costs = [];
            foreach ($settings as $key => $val) {
              if (strpos($key, 'class_cost_') === 0 && $val !== '') {
                $class_id   = str_replace('class_cost_', '', $key);
                $term       = get_term((int) $class_id, 'product_shipping_class');
                $class_name = ($term && !is_wp_error($term)) ? $term->name : $class_id;
                $class_costs[] = $class_name . ': ' . html_entity_decode(strip_tags(wc_price($val)), ENT_QUOTES, 'UTF-8');
              }
            }
            if (!empty($class_costs)) {
              $line .= ' (+ per shipping class: ' . implode(', ', $class_costs) . ')';
            }
          }
          $no_class_cost = $settings['no_class_cost'] ?? '';
          if ($no_class_cost !== '' && $no_class_cost !== ($settings['cost'] ?? '')) {
            $line .= ' [no class: ' . html_entity_decode(strip_tags(wc_price($no_class_cost)), ENT_QUOTES, 'UTF-8') . ']';
          }
        } elseif ($method_type === 'local_pickup') {
          $cost = $settings['cost'] ?? '';
          $line .= $cost !== '' && (float) $cost > 0
            ? ': ' . html_entity_decode(strip_tags(wc_price($cost)), ENT_QUOTES, 'UTF-8')
            : ': FREE';
        } else {
          // Generic fallback for third-party methods
          $cost = $settings['cost'] ?? '';
          if ($cost !== '') {
            $line .= ': ' . html_entity_decode(strip_tags(wc_price($cost)), ENT_QUOTES, 'UTF-8');
          }
        }

        $block .= $line . "\n";
      }

      $blocks[] = trim($block);
    }

    if (empty($blocks)) {
      return '';
    }

    return implode("\n\n", $blocks);
  }

  /**
   * Build a text summary of WooCommerce products for the AI context.
   *
   * @param bool $include_variations Whether to include product variations.
   * @param bool $add_to_cart_links  Whether links should add to cart (true) or point to product page (false).
   * @return string
   */
  private static function get_woo_products_context($include_variations = false, $add_to_cart_links = false, $include_images = false) {
    if (!class_exists('WooCommerce') && !function_exists('wc_get_products')) {
      return '';
    }

    $products = wc_get_products([
      'status'  => 'publish',
      'limit'   => 50,
      'orderby' => 'total_sales',
      'order'   => 'DESC',
    ]);

    if (empty($products)) {
      return '';
    }

    $shop_url = get_permalink(wc_get_page_id('shop'));
    $link_label = $add_to_cart_links ? 'Buy link' : 'Product link';

    $blocks = [];
    foreach ($products as $product) {
      $product_id = $product->get_id();

      if ($add_to_cart_links) {
        $product_url = $shop_url ? add_query_arg('add-to-cart', $product_id, $shop_url) : home_url('?add-to-cart=' . $product_id);
      } else {
        $product_url = get_permalink($product_id);
      }

      $block = '---' . "\n";
      $block .= 'Product: ' . $product->get_name() . "\n";
      $block .= 'ID: ' . $product_id . "\n";

      // Price
      if ($product->is_type('variable')) {
        $min_price = $product->get_variation_price('min', true);
        $max_price = $product->get_variation_price('max', true);
        if ($min_price) {
          if ($min_price !== $max_price) {
            $block .= 'Price: from ' . html_entity_decode(strip_tags(wc_price($min_price)), ENT_QUOTES, 'UTF-8') . "\n";
          } else {
            $block .= 'Price: ' . html_entity_decode(strip_tags(wc_price($min_price)), ENT_QUOTES, 'UTF-8') . "\n";
          }
        }
      } else {
        $price = $product->get_price();
        if ($price) {
          $block .= 'Price: ' . html_entity_decode(strip_tags(wc_price($price)), ENT_QUOTES, 'UTF-8');
          if ($product->is_on_sale()) {
            $block .= ' (before: ' . html_entity_decode(strip_tags(wc_price($product->get_regular_price())), ENT_QUOTES, 'UTF-8') . ')';
          }
          $block .= "\n";
        }
      }

      // Variations (variable products) — only if enabled
      if ($include_variations && $product->is_type('variable')) {
        $variations = $product->get_available_variations();
        foreach (array_slice($variations, 0, 15) as $v) {
          $var_id = $v['variation_id'];
          $attrs = [];

          if ($add_to_cart_links) {
            $query_args = [
              'add-to-cart' => $product_id,
              'variation_id' => $var_id,
            ];
            foreach ($v['attributes'] as $attr_name => $attr_val) {
              $query_args[$attr_name] = $attr_val;
              $clean_name = str_replace('attribute_', '', $attr_name);
              $clean_name = str_replace(['pa_', '-', '_'], ['', ' ', ' '], $clean_name);
              $attrs[] = ucfirst($clean_name) . ': ' . $attr_val;
            }
            $var_url = add_query_arg($query_args, $shop_url ?: home_url('/'));
          } else {
            foreach ($v['attributes'] as $attr_name => $attr_val) {
              $clean_name = str_replace('attribute_', '', $attr_name);
              $clean_name = str_replace(['pa_', '-', '_'], ['', ' ', ' '], $clean_name);
              $attrs[] = ucfirst($clean_name) . ': ' . $attr_val;
            }
            $var_url = get_permalink($product_id);
          }

          $var_price = !empty($v['display_price']) ? html_entity_decode(strip_tags(wc_price($v['display_price'])), ENT_QUOTES, 'UTF-8') : '';
          $block .= '  Variation ID ' . $var_id . ' — ' . implode(', ', $attrs);
          if ($var_price) {
            $block .= ' — ' . $var_price;
          }
          $block .= ' — ' . $link_label . ': ' . $var_url . "\n";
        }
      }

      $block .= $link_label . ': ' . $product_url . "\n";

      if ($include_images) {
        $block .= 'Image tag: [PRODUCT_IMAGES:' . $product_id . ']' . "\n";
      }

      $blocks[] = $block;
    }

    return implode("\n", $blocks);
  }

  /**
   * Build a text summary of published blog posts for the AI context.
   *
   * @return string
   */
  private static function get_posts_context() {
    $posts = get_posts([
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'posts_per_page' => 30,
      'orderby'        => 'date',
      'order'          => 'DESC',
    ]);

    if (empty($posts)) {
      return '';
    }

    $blocks = [];
    foreach ($posts as $post) {
      $content = wp_strip_all_tags($post->post_content);
      if (mb_strlen($content) > 800) {
        $content = mb_substr($content, 0, 800) . '...';
      }

      $block = '---' . "\n";
      $block .= 'Title: ' . $post->post_title . "\n";
      $block .= 'URL: ' . get_permalink($post->ID) . "\n";
      $block .= 'Date: ' . get_the_date('', $post) . "\n";
      if (!empty($content)) {
        $block .= 'Content: ' . $content . "\n";
      }

      $blocks[] = $block;
    }

    return implode("\n", $blocks);
  }

  /**
   * Build a text summary of published pages for the AI context.
   *
   * @return string
   */
  private static function get_pages_context() {
    $pages = get_posts([
      'post_type'      => 'page',
      'post_status'    => 'publish',
      'posts_per_page' => 30,
      'orderby'        => 'menu_order',
      'order'          => 'ASC',
    ]);

    if (empty($pages)) {
      return '';
    }

    $blocks = [];
    foreach ($pages as $page) {
      $content = wp_strip_all_tags($page->post_content);
      if (mb_strlen($content) > 800) {
        $content = mb_substr($content, 0, 800) . '...';
      }

      $block = '---' . "\n";
      $block .= 'Title: ' . $page->post_title . "\n";
      $block .= 'URL: ' . get_permalink($page->ID) . "\n";
      if (!empty(trim($content))) {
        $block .= 'Content: ' . $content . "\n";
      }

      $blocks[] = $block;
    }

    return implode("\n", $blocks);
  }

  /**
   * Get temperature for a conversation (node override or global default).
   *
   * @param object $conversation
   * @return float
   */
  private static function get_conversation_temperature($conversation) {
    // Check if the conversation has node-level config
    if ($conversation->funnel_id && $conversation->node_id) {
      $canvas_data = get_post_meta($conversation->funnel_id, 'pn_cm_funnel_canvas', true);
      if ($canvas_data) {
        $data = json_decode($canvas_data, true);
        if ($data && isset($data['nodes'])) {
          foreach ($data['nodes'] as $node) {
            if ($node['id'] === $conversation->node_id && isset($node['config']['wa_temperature'])) {
              return (float) $node['config']['wa_temperature'];
            }
          }
        }
      }
    }

    return (float) get_option('pn_customers_manager_whatsapp_temperature', 0.7);
  }

  /* ================================================================
   * MARKDOWN → WHATSAPP FORMATTING
   * ================================================================ */

  /**
   * Convert Markdown formatting from OpenAI to WhatsApp-compatible formatting.
   *
   * - **bold** → *bold*
   * - __bold__ → *bold*
   * - [text](url) → text: url
   * - ### headings → *headings*
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
   * OPENAI API
   * ================================================================ */

  /**
   * Call OpenAI Chat Completions API.
   *
   * @param array  $messages
   * @param string $model
   * @param float  $temperature
   * @return string|false
   */
  private static function call_openai($messages, $model = 'gpt-4o-mini', $temperature = 0.7) {
    $api_key = get_option('pn_customers_manager_whatsapp_openai_key', '');

    if (empty($api_key)) {
      return false;
    }

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
      'timeout' => 30,
      'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
      ],
      'body' => wp_json_encode([
        'model'       => $model,
        'messages'    => $messages,
        'temperature' => $temperature,
      ]),
    ]);

    if (is_wp_error($response)) {
      self::log('call_openai — WP_Error: ' . $response->get_error_message());
      return false;
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $body_raw  = wp_remote_retrieve_body($response);
    $body      = json_decode($body_raw, true);

    if (isset($body['choices'][0]['message']['content'])) {
      return $body['choices'][0]['message']['content'];
    }

    // Log the failure details for debugging
    $error_msg = isset($body['error']['message']) ? $body['error']['message'] : 'unknown';
    $error_type = isset($body['error']['type']) ? $body['error']['type'] : 'unknown';
    self::log('call_openai — FAILED model=' . $model . ' http=' . $http_code
      . ' error_type=' . $error_type . ' error_msg=' . $error_msg);

    return false;
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

    // Upload image to WhatsApp media API first (more reliable than link approach)
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
    // Get local file path from URL
    $attachment_id = attachment_url_to_postid($image_url);
    if ($attachment_id) {
      $file_path = get_attached_file($attachment_id);
    } else {
      $file_path = false;
    }

    if (!$file_path || !file_exists($file_path)) {
      // Fallback: download from URL
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

  /**
   * Remove system prompt fragments that the model may have leaked into its response.
   *
   * @param string $text AI response text.
   * @return string      Cleaned text.
   */
  private static function sanitize_system_prompt_leak($text) {
    // Detect common system prompt markers and remove everything from the first marker onward
    $markers = [
      'CURRENT DATE AND TIME:',
      'FORMATTING RULES:',
      'MANDATORY RULE',
      'COMPANY INFORMATION:',
      'SHIPPING ZONES AND PRICES',
      'SHIPPING INFORMATION:',
      'OPENING HOURS:',
      'REFERENCE INFORMATION:',
      'PRODUCT CATALOG',
      'PRODUCT IMAGES RULES:',
      'MANDATORY STYLE RULES',
    ];

    foreach ($markers as $marker) {
      $pos = strpos($text, $marker);
      if ($pos !== false) {
        $clean = trim(substr($text, 0, $pos));
        if (!empty($clean)) {
          self::log('sanitize_system_prompt_leak — stripped system prompt leak at "' . $marker . '"');
          return $clean;
        }
      }
    }

    return $text;
  }

  /**
   * Enforce postal code requirement at code level.
   * If require_postal is true and no postal code has been provided in the
   * conversation history, override any AI response that talks about
   * shipping/delivery with a forced postal code request.
   *
   * The function is self-sufficient: if $require_postal is false but
   * $conversation is provided, it re-reads the funnel config directly
   * as a safety net to prevent false negatives.
   *
   * @param string      $text           AI response text.
   * @param array       $messages       Conversation messages history.
   * @param bool        $require_postal Whether the postal code rule is active.
   * @param object|null $conversation   Conversation DB row (optional safety net).
   * @return string                     Original or overridden text.
   */
  private static function enforce_postal_code_rule($text, $messages, $require_postal, $conversation = null) {
    self::log('enforce_postal_code_rule — called, require_postal=' . ($require_postal ? 'YES' : 'NO'));

    // Safety net: if $require_postal is false, double-check by reading config directly
    if (!$require_postal && $conversation) {
      self::log('enforce_postal_code_rule — require_postal is NO, re-reading config as safety net...');
      $nc = self::get_node_config($conversation);
      if (empty($nc)) {
        $wa_node = self::find_whatsapp_ai_node();
        if ($wa_node && !empty($wa_node['config'])) {
          $nc = $wa_node['config'];
        }
      }
      if (!empty($nc['wa_require_postal_code'])) {
        $require_postal = true;
        self::log('enforce_postal_code_rule — SAFETY NET activated: found wa_require_postal_code='
          . var_export($nc['wa_require_postal_code'], true) . ' → require_postal=YES');
      } else {
        self::log('enforce_postal_code_rule — safety net: wa_require_postal_code not set or falsy'
          . ' config_keys=' . ($nc ? implode(',', array_keys($nc)) : 'EMPTY'));
      }
    }

    if (!$require_postal) {
      return $text;
    }

    // Check if the AI response mentions shipping/delivery — NOT generic product prices
    $shipping_keywords = '/(env[ií]o(?!\s+de\s+flores)|coste\s+de\s+env[ií]o|gastos\s+de\s+env[ií]o|entrega\s+a\s+domicilio|hacemos\s+env[ií]os|realizamos\s+env[ií]os|podemos\s+enviar|enviamos\s+a\b|entregamos\s+en|repartimos|shipping|delivery\s+cost|\d+[\.,]?\d*\s*€[^.]*env[ií]o|env[ií]o[^.]*\d+[\.,]?\d*\s*€)/iu';
    if (!preg_match($shipping_keywords, $text, $kw_match)) {
      self::log('enforce_postal_code_rule — no shipping keywords found in response, skipping');
      return $text;
    }
    self::log('enforce_postal_code_rule — shipping keyword matched: "' . $kw_match[0] . '"');

    // Check RECENT user messages for a postal code (Spanish: 5 digits starting with 0-5).
    // Scan from newest to oldest. If we find a NEW shipping inquiry (user asking about
    // a different address/destination) BEFORE finding a postal code, the old CP is stale
    // and we must ask again.
    $postal_found    = false;
    $shipping_inquiry = '/(?:'
      . 'enviar?\s+(?:a\s+|flores\s+a\s+)?(?:la\s+)?calle'       // enviar a la calle...
      . '|enviar?\s+a\b'                                          // enviar a...
      . '|entregar?\s+en'                                         // entregar en...
      . '|envío\s+a\b'                                            // envío a...
      . '|env[ií](?:o|ar|áis|ais)\b.*(?:calle|pueblo|ciudad|zona|direcci[oó]n)' // enviar...calle/pueblo/etc
      . '|(?:y\s+)?(?:a\s+)?(?:la\s+)?calle\s+\w+'               // (y) (a) (la) calle Serrano
      . '|(?:y\s+)?al?\s+pueblo\s+(?:de\s+)?\w+'                  // (y) al pueblo de X
      . '|pod(?:r[ií]a|éis|ríais)\s+enviar'                       // podríais enviar
      . '|llega\s+a\b|lleg[aá]is\s+a\b'                           // llega a / llegáis a
      . ')/iu';
    $recent_messages  = array_slice($messages, -12); // last ~6 exchanges (user+assistant)

    // Walk backwards: newest message first
    for ($i = count($recent_messages) - 1; $i >= 0; $i--) {
      $msg = $recent_messages[$i];
      if ($msg['role'] !== 'user') {
        continue;
      }
      // If this user message contains a postal code → found it
      if (preg_match('/\b[0-5]\d{4}\b/', $msg['content'])) {
        $postal_found = true;
        self::log('enforce_postal_code_rule — postal code found in recent user message: "' . $msg['content'] . '"');
        break;
      }
      // If this user message is a new shipping inquiry (mentions address/destination),
      // any older postal code is stale — stop searching
      if (preg_match($shipping_inquiry, $msg['content'])) {
        self::log('enforce_postal_code_rule — new shipping inquiry found BEFORE any postal code: "' . $msg['content'] . '" — CP is stale');
        break;
      }
    }

    if ($postal_found) {
      return $text;
    }

    // No postal code yet but AI is giving shipping info — override response
    self::log('enforce_postal_code_rule — OVERRIDING response (no postal code yet). Original: ' . mb_substr($text, 0, 200));

    // Keep any non-shipping part of the response and append the postal code request
    // Try to extract the first sentence if it's a greeting or product info
    $first_sentence = '';
    if (preg_match('/^(.+?[.!?])\s/u', $text, $m)) {
      $candidate = $m[1];
      // Reject if it mentions shipping prices OR confirms delivery capability
      $has_price    = preg_match('/\d+[\.,]?\d*\s*€/u', $candidate);
      $has_delivery = preg_match('/(?:podemos\s+enviar|enviamos|hacemos\s+env[ií]os?|realizamos\s+env[ií]os?|s[ií]\s*,?\s*(?:se\s+)?(?:puede|podemos|hacemos)|entregamos|repartimos|llegar[aá]|llegamos)/iu', $candidate);
      if (!$has_price && !$has_delivery) {
        $first_sentence = $candidate . "\n\n";
      } else {
        self::log('enforce_postal_code_rule — first sentence rejected (price=' . ($has_price ? 'YES' : 'NO') . ', delivery=' . ($has_delivery ? 'YES' : 'NO') . '): "' . $candidate . '"');
      }
    }

    return $first_sentence . 'Para poder indicarte el coste exacto de envío, ¿podrías facilitarme tu código postal?';
  }

  /**
   * Validate URLs in AI response — repair broken product URLs or remove them.
   *
   * When a product URL returns 404 (e.g. the AI modified the slug), this
   * tries to find the closest matching product by slug similarity and
   * replaces the broken URL with the correct permalink.
   *
   * @param string $text AI response text.
   * @return string      Text with repaired/cleaned URLs.
   */
  private static function validate_response_urls($text) {
    if (!preg_match_all('/(https?:\/\/[^\s,;)\]]+)/i', $text, $matches)) {
      return $text;
    }

    $urls      = array_unique($matches[1]);
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

    // Resolve WooCommerce product base path (e.g. "producto", "product")
    $product_base = '';
    if (class_exists('WooCommerce')) {
      $permalinks   = (array) get_option('woocommerce_permalinks', []);
      $product_base = !empty($permalinks['product_base']) ? trim($permalinks['product_base'], '/') : 'product';
    }

    foreach ($urls as $url) {
      $url_host = wp_parse_url($url, PHP_URL_HOST);
      if (!$url_host || $url_host !== $site_host) {
        continue;
      }

      $response = wp_remote_head($url, [
        'timeout'     => 4,
        'redirection' => 3,
        'sslverify'   => false,
      ]);

      if (is_wp_error($response)) {
        self::log('validate_response_urls — error checking ' . $url . ': ' . $response->get_error_message());
        continue;
      }

      $code = wp_remote_retrieve_response_code($response);

      if ($code < 400) {
        continue; // URL is fine
      }

      self::log('validate_response_urls — broken link (HTTP ' . $code . '): ' . $url);

      // Try to repair product URLs by finding the closest matching product slug
      $repaired = false;
      if (!empty($product_base)) {
        $url_path = wp_parse_url($url, PHP_URL_PATH);
        $base_pattern = '#/' . preg_quote($product_base, '#') . '/([a-z0-9\-]+)/?$#i';
        if ($url_path && preg_match($base_pattern, $url_path, $slug_match)) {
          $broken_slug = $slug_match[1];
          $correct_url = self::find_closest_product_url($broken_slug);
          if ($correct_url && $correct_url !== $url) {
            $text = str_replace($url, $correct_url, $text);
            $repaired = true;
            self::log('validate_response_urls — repaired product URL: ' . $url . ' → ' . $correct_url);
          }
        }
      }

      if (!$repaired) {
        $text = str_replace($url, '', $text);
        self::log('validate_response_urls — removed unrepairable URL: ' . $url);
      }
    }

    $text = preg_replace('/\(\s*\)/', '', $text);
    $text = preg_replace('/  +/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    return trim($text);
  }

  /**
   * Find the closest matching published product URL for a broken slug.
   * Uses Levenshtein distance to match similar slugs.
   *
   * @param string $broken_slug The broken/modified slug.
   * @return string|false       Correct permalink or false.
   */
  private static function find_closest_product_url($broken_slug) {
    global $wpdb;

    // First try exact match (should not happen since it was a 404, but just in case)
    $exact = get_page_by_path($broken_slug, OBJECT, 'product');
    if ($exact && $exact->post_status === 'publish') {
      return get_permalink($exact->ID);
    }

    // Get candidate slugs — products whose slug shares at least the first 8 chars
    $prefix = substr($broken_slug, 0, 8);
    $candidates = $wpdb->get_results($wpdb->prepare(
      "SELECT ID, post_name FROM {$wpdb->posts}
       WHERE post_type = 'product' AND post_status = 'publish'
       AND post_name LIKE %s
       LIMIT 20",
      $wpdb->esc_like($prefix) . '%'
    ));

    if (empty($candidates)) {
      return false;
    }

    $best_id       = 0;
    $best_distance = PHP_INT_MAX;

    foreach ($candidates as $candidate) {
      $distance = levenshtein($broken_slug, $candidate->post_name);
      if ($distance < $best_distance) {
        $best_distance = $distance;
        $best_id       = $candidate->ID;
      }
    }

    // Only accept if the distance is reasonable (max ~30% of slug length)
    $max_distance = max(3, (int) (strlen($broken_slug) * 0.3));
    if ($best_distance <= $max_distance && $best_id) {
      return get_permalink($best_id);
    }

    return false;
  }

  /**
   * Remove hallucinated image URLs from AI response.
   *
   * @param string $text AI response text.
   * @return string      Cleaned text.
   */
  private static function strip_hallucinated_image_urls($text) {
    $original = $text;

    // Remove markdown image syntax: ![alt text](url)
    $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $text);

    // Remove bare image URLs on their own line (http(s)://...image-extension)
    $text = preg_replace('/^\s*https?:\/\/\S+\.(?:jpg|jpeg|png|gif|webp|svg|bmp|tiff)\b\S*\s*$/mi', '', $text);

    // Remove inline image URLs preceded by a colon or whitespace (e.g. "Aquí tienes: https://...jpg")
    $text = preg_replace('/:\s*https?:\/\/\S+\.(?:jpg|jpeg|png|gif|webp|svg|bmp|tiff)\b\S*/i', '.', $text);

    // Clean up multiple blank lines left behind
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = trim($text);

    if ($text !== $original) {
      self::log('strip_hallucinated_image_urls — removed fabricated image URLs from AI response');
    }

    return $text;
  }

  /**
   * Auto-inject [PRODUCT_IMAGES:id] tags for products mentioned by URL
   * but missing an explicit image tag. This acts as a safety net when
   * the AI omits the tag despite instructions.
   *
   * @param string $text AI response text.
   * @return string      Text with image tags injected where needed.
   */
  private static function auto_inject_product_image_tags($text) {
    if (!class_exists('WooCommerce')) {
      return $text;
    }

    $site_url = home_url();

    // Get the WooCommerce product permalink base (e.g. "producto", "product", "shop")
    $product_base = 'product';
    $permalinks = (array) get_option('woocommerce_permalinks', []);
    if (!empty($permalinks['product_base'])) {
      $product_base = trim($permalinks['product_base'], '/');
    }

    $product_ids_found = [];

    // Strategy 1: Find product permalink URLs (e.g. /producto/ramo-de-flores-colorido/)
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

    // Strategy 2: Find add-to-cart URLs (e.g. ?add-to-cart=4063)
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
      // Skip if already has an image tag for this product
      if (strpos($text, '[PRODUCT_IMAGES:' . $product_id . ']') !== false) {
        continue;
      }

      // Only inject if product has a featured image
      if (!get_post_thumbnail_id($product_id)) {
        continue;
      }

      // Append tag at the end of the text
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
   * @param array  &$sent_images Reference array to collect sent image URLs for history storage.
   * @return string              Text with tags removed.
   */
  private static function extract_and_send_product_images($text, $phone, &$sent_images = []) {
    if (preg_match_all('/\[PRODUCT_IMAGES:(\d+)\]/', $text, $matches)) {
      foreach ($matches[1] as $product_id) {
        $product_id = (int) $product_id;
        $product    = wc_get_product($product_id);

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

    // Find active conversation for this phone
    $conversation = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE phone_number = %s AND status = 'active' ORDER BY updated_at DESC LIMIT 1",
      $phone
    ));

    if ($conversation) {
      // Update contact name if provided and empty
      if (!empty($contact_name) && empty($conversation->contact_name)) {
        $wpdb->update($table, ['contact_name' => $contact_name], ['id' => $conversation->id]);
        $conversation->contact_name = $contact_name;
      }
      return $conversation;
    }

    // Determine system prompt and AI config from funnel node if possible
    $system_prompt = get_option('pn_customers_manager_whatsapp_system_prompt', '');
    $ai_model      = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');
    $funnel_id     = 0;
    $node_id       = '';

    // Auto-detect a WhatsApp AI funnel node to link the conversation
    $wa_node = self::find_whatsapp_ai_node();
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

    // Create new conversation
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

    // Get node config from funnel canvas
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
      $messages[] = [
        'role'      => 'assistant',
        'content'   => $welcome_msg,
        'timestamp' => current_time('mysql'),
      ];
      // Send welcome message
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

    // Check if table exists first
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
   * Render the WhatsApp IA admin page.
   */
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

    // Determine if viewing a single conversation
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

  /**
   * Enqueue admin CSS and JS for the WhatsApp IA page.
   */
  /* ================================================================
   * TEST METHODS (called from Settings via AJAX)
   * ================================================================ */

  /**
   * Test OpenAI API connection.
   *
   * Sends a simple prompt and returns the response or error.
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
   * If $since is 'start', records the current server time and returns it.
   * Otherwise looks for user messages received after $since.
   *
   * @param string $since  'start' to begin listening, or datetime string.
   * @return array
   */
  public static function ajax_test_webhook_receive($since) {
    // If 'start', return the current server time so the client can use it
    if ($since === 'start') {
      return [
        'found'      => false,
        'server_time' => current_time('mysql'),
      ];
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return [
        'error_key'     => 'no_table',
        'error_content' => esc_html__('Conversations table does not exist. Deactivate and reactivate the plugin.', 'pn-customers-manager'),
      ];
    }

    // Find conversations updated after the given timestamp
    $row = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE updated_at >= %s ORDER BY updated_at DESC LIMIT 1",
      $since
    ));

    if (!$row) {
      return [
        'found' => false,
      ];
    }

    // Check for a user message in this conversation after $since
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

  /* ================================================================
   * LOGGING
   * ================================================================ */

  /**
   * Log a message to the WhatsApp AI debug log.
   *
   * @param string $message
   */
  /**
   * Detect [PEDIDO_CONFIRMADO] tag in AI response and send order notification email.
   *
   * @param string $ai_response
   * @param object $conversation
   * @param array  $messages
   * @param array  $node_config
   * @return string Cleaned response with tag stripped.
   */
  private static function detect_and_notify_order($ai_response, $conversation, $messages, $node_config) {
    if (strpos($ai_response, '[PEDIDO_CONFIRMADO]') === false) {
      return $ai_response;
    }

    self::log('detect_and_notify_order — tag detected in response');

    // Check whether orders are enabled (funnel node config only)
    if (empty($node_config['wa_enable_chat_orders'])) {
      self::log('detect_and_notify_order — SECURITY: tag found but chat orders are DISABLED. Stripping tag.');
      return str_replace('[PEDIDO_CONFIRMADO]', '', $ai_response);
    }

    // Resolve notification email (node config > admin_email fallback)
    $email = '';
    if (!empty($node_config['wa_chat_orders_email'])) {
      $email = sanitize_email($node_config['wa_chat_orders_email']);
    }
    if (empty($email)) {
      $email = get_option('admin_email');
    }

    self::log('detect_and_notify_order — sending notification to: ' . $email);

    // Build order context from recent messages
    $recent   = array_slice($messages, -10);
    $excerpt  = '';
    foreach ($recent as $msg) {
      $role   = ($msg['role'] === 'user') ? '👤 Cliente' : '🤖 Asistente';
      $content = $msg['content'];
      // Strip internal annotations like [Se enviaron imágenes de: ...]
      $content = preg_replace('/\[Se enviaron imágenes de:[^\]]*\]/', '', $content);
      $content = trim($content);
      if ($content !== '') {
        $excerpt .= $role . ': ' . $content . "\n\n";
      }
    }

    // Extract product details from conversation text
    $full_text = implode("\n", array_column($recent, 'content'));
    $products  = self::extract_order_details($full_text);

    // Build email
    $customer_name  = !empty($conversation->contact_name) ? $conversation->contact_name : __('Unknown', 'pn-customers-manager');
    $customer_phone = !empty($conversation->phone_number) ? $conversation->phone_number : __('Unknown', 'pn-customers-manager');
    $order_date     = current_time('d/m/Y H:i');
    $site_name      = get_bloginfo('name');

    $subject = sprintf(
      /* translators: %s: site name */
      __('[%s] New order via WhatsApp', 'pn-customers-manager'),
      $site_name
    );

    $html  = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">';
    $html .= '<h2 style="color:#25D366;">' . esc_html__('New order via WhatsApp', 'pn-customers-manager') . '</h2>';
    $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">';
    $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html__('Customer', 'pn-customers-manager') . '</td>';
    $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($customer_name) . '</td></tr>';
    $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html__('Phone', 'pn-customers-manager') . '</td>';
    $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($customer_phone) . '</td></tr>';
    $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html__('Date', 'pn-customers-manager') . '</td>';
    $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($order_date) . '</td></tr>';
    $html .= '</table>';

    if (!empty($products)) {
      $html .= '<h3>' . esc_html__('Products mentioned', 'pn-customers-manager') . '</h3>';
      $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">';
      $html .= '<tr style="background:#f5f5f5;">';
      $html .= '<th style="padding:8px;text-align:left;border-bottom:2px solid #ddd;">' . esc_html__('Product', 'pn-customers-manager') . '</th>';
      $html .= '<th style="padding:8px;text-align:right;border-bottom:2px solid #ddd;">' . esc_html__('Price', 'pn-customers-manager') . '</th></tr>';
      foreach ($products as $product) {
        $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($product['name']) . '</td>';
        $html .= '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">' . esc_html($product['price']) . '</td></tr>';
      }
      $html .= '</table>';
    }

    $html .= '<h3>' . esc_html__('Conversation excerpt', 'pn-customers-manager') . '</h3>';
    $html .= '<div style="background:#f9f9f9;padding:15px;border-radius:8px;white-space:pre-wrap;font-size:13px;line-height:1.5;">';
    $html .= esc_html($excerpt);
    $html .= '</div>';
    $html .= '</div>';

    // Send email (prefer MAILPN if available, fallback to wp_mail)
    if (class_exists('MAILPN_Mailing')) {
      try {
        $mailing = new \MAILPN_Mailing();
        $sent = $mailing->mailpn_sender([
          'mailpn_user_to' => $email,
          'mailpn_subject' => $subject,
          'mailpn_type'    => 'pn_cm_whatsapp_order',
        ], $html);
        self::log('detect_and_notify_order — sent via MAILPN to ' . $email . ' result=' . var_export($sent, true));
      } catch (\Exception $e) {
        self::log('detect_and_notify_order — MAILPN error: ' . $e->getMessage() . ', falling back to wp_mail');
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent = wp_mail($email, $subject, $html, $headers);
        self::log('detect_and_notify_order — wp_mail fallback result=' . ($sent ? 'OK' : 'FAILED'));
      }
    } else {
      $headers = ['Content-Type: text/html; charset=UTF-8'];
      $sent = wp_mail($email, $subject, $html, $headers);
      self::log('detect_and_notify_order — sent via wp_mail to ' . $email . ' result=' . ($sent ? 'OK' : 'FAILED'));
    }

    // Strip tag from visible response
    $ai_response = str_replace('[PEDIDO_CONFIRMADO]', '', $ai_response);
    $ai_response = trim($ai_response);

    return $ai_response;
  }

  /**
   * Extract product details (name, price) from conversation text by matching WooCommerce URLs.
   *
   * @param string $text Conversation text to scan.
   * @return array List of ['name' => ..., 'price' => ...] entries.
   */
  private static function extract_order_details($text) {
    $products = [];
    $seen_ids = [];

    if (!function_exists('wc_get_product')) {
      return $products;
    }

    // Match add-to-cart URLs: ?add-to-cart=ID
    if (preg_match_all('/[?&]add-to-cart=(\d+)/', $text, $matches)) {
      foreach ($matches[1] as $product_id) {
        $product_id = (int) $product_id;
        if (isset($seen_ids[$product_id])) {
          continue;
        }
        $product = wc_get_product($product_id);
        if ($product) {
          $seen_ids[$product_id] = true;
          $products[] = [
            'name'  => $product->get_name(),
            'price' => strip_tags(wc_price($product->get_price())),
          ];
        }
      }
    }

    // Match product permalink URLs
    $site_url = preg_quote(home_url('/'), '/');
    if (preg_match_all('/' . $site_url . '[^\s\]]+/', $text, $url_matches)) {
      foreach ($url_matches[0] as $url) {
        // Skip if already found via add-to-cart
        if (strpos($url, 'add-to-cart=') !== false) {
          continue;
        }
        $product_id = url_to_postid($url);
        if ($product_id && !isset($seen_ids[$product_id]) && get_post_type($product_id) === 'product') {
          $product = wc_get_product($product_id);
          if ($product) {
            $seen_ids[$product_id] = true;
            $products[] = [
              'name'  => $product->get_name(),
              'price' => strip_tags(wc_price($product->get_price())),
            ];
          }
        }
      }
    }

    // Match [PRODUCT_IMAGES:ID] tags
    if (preg_match_all('/\[PRODUCT_IMAGES:(\d+)\]/', $text, $tag_matches)) {
      foreach ($tag_matches[1] as $product_id) {
        $product_id = (int) $product_id;
        if (isset($seen_ids[$product_id])) {
          continue;
        }
        $product = wc_get_product($product_id);
        if ($product) {
          $seen_ids[$product_id] = true;
          $products[] = [
            'name'  => $product->get_name(),
            'price' => strip_tags(wc_price($product->get_price())),
          ];
        }
      }
    }

    return $products;
  }

  private static function log($message) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
      return;
    }

    $upload_dir = wp_upload_dir();
    $log_dir    = $upload_dir['basedir'] . '/pn-cm-logs';

    if (!file_exists($log_dir)) {
      wp_mkdir_p($log_dir);
      // Protect with .htaccess
      file_put_contents($log_dir . '/.htaccess', 'deny from all');
    }

    $log_file = $log_dir . '/whatsapp-ai.log';
    $time     = current_time('Y-m-d H:i:s');
    $line     = '[' . $time . '] ' . $message . "\n";

    // Keep log file under 1MB
    if (file_exists($log_file) && filesize($log_file) > 1048576) {
      $contents = file_get_contents($log_file);
      file_put_contents($log_file, substr($contents, -524288));
    }

    file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
  }
}
