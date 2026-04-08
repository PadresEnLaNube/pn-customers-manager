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

  /**
   * REST API namespace.
   */
  const REST_NAMESPACE = 'pn-cm/v1';

  /**
   * Register REST API routes for the Instagram webhook.
   */
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

  /**
   * Handle incoming Instagram message from Meta webhook.
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

  /**
   * Public wrapper for process_incoming_message (called from shutdown hook).
   *
   * @param string $sender_id
   * @param string $text
   */
  public static function deferred_process_message($sender_id, $text) {
    self::process_incoming_message($sender_id, $text);
  }

  /* ================================================================
   * MESSAGE PROCESSING
   * ================================================================ */

  /**
   * Process a single incoming Instagram message.
   *
   * @param string $sender_id
   * @param string $text
   */
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

    // Read node config once for postal code rule
    $node_config = self::get_node_config($conversation);
    if (empty($node_config)) {
      $ig_node = self::find_instagram_ai_node();
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

    // Use model from funnel config (always up-to-date), fall back to conversation, then global option
    $ai_model = !empty($node_config['ig_ai_model'])
      ? $node_config['ig_ai_model']
      : (!empty($conversation->ai_model)
        ? $conversation->ai_model
        : get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini'));
    self::log('process_incoming_message — ai_model=' . $ai_model
      . ' (source=' . (!empty($node_config['ig_ai_model']) ? 'node_config' : (!empty($conversation->ai_model) ? 'conversation' : 'global_option')) . ')'
      . ' node_config[ig_ai_model]=' . var_export($node_config['ig_ai_model'] ?? null, true)
      . ' conversation->ai_model=' . var_export($conversation->ai_model ?? null, true));

    // Sync model to conversation DB if funnel config changed it
    if (!empty($node_config['ig_ai_model']) && $node_config['ig_ai_model'] !== ($conversation->ai_model ?? '')) {
      global $wpdb;
      $wpdb->update(
        $wpdb->prefix . 'pn_cm_instagram_conversations',
        ['ai_model' => $node_config['ig_ai_model']],
        ['id' => $conversation->id]
      );
      self::log('process_incoming_message — synced ai_model to DB: ' . $node_config['ig_ai_model']);
    }

    $temperature = self::get_conversation_temperature($conversation);

    $openai_messages = [];

    if (!empty($system_prompt)) {
      $openai_messages[] = [
        'role'    => 'system',
        'content' => $system_prompt,
      ];
    }

    foreach ($messages as $msg) {
      $openai_messages[] = [
        'role'    => $msg['role'],
        'content' => $msg['content'],
      ];
    }

    $ai_response = self::call_openai($openai_messages, $ai_model, $temperature);

    if ($ai_response === false) {
      $ai_response = 'Sorry, I cannot respond right now. Please try again later.';
    }

    // Strip system prompt fragments the model may have leaked into the response
    $ai_response = self::sanitize_system_prompt_leak($ai_response);

    // Enforce postal code rule: if enabled and no postal code received yet, override the response
    $ai_response = self::enforce_postal_code_rule($ai_response, $messages, $require_postal, $conversation);

    $ai_response = self::markdown_to_instagram($ai_response);

    // Strip fabricated image URLs the AI may have hallucinated
    $ai_response = self::strip_hallucinated_image_urls($ai_response);

    // Validate URLs in the response — remove broken links (404, etc.)
    $ai_response = self::validate_response_urls($ai_response);

    $messages[] = [
      'role'      => 'assistant',
      'content'   => $ai_response,
      'timestamp' => current_time('mysql'),
    ];

    self::update_conversation_messages($conversation->id, $messages);

    self::send_instagram_message($sender_id, $ai_response);
  }

  /**
   * Build an enriched system prompt for Instagram AI.
   *
   * @param object $conversation
   * @return string
   */
  private static function build_enriched_system_prompt($conversation) {
    $base_prompt = get_option('pn_customers_manager_whatsapp_system_prompt', '');

    self::log('build_enriched_system_prompt — global prompt length=' . mb_strlen($base_prompt));

    $node_config = self::get_node_config($conversation);

    self::log('build_enriched_system_prompt — get_node_config returned ' . (empty($node_config) ? 'EMPTY' : count($node_config) . ' keys: ' . implode(',', array_keys($node_config))));

    if (empty($node_config)) {
      self::log('build_enriched_system_prompt — node_config empty, searching for instagram_ai node...');
      $ig_node = self::find_instagram_ai_node();
      if ($ig_node && !empty($ig_node['config'])) {
        $node_config = $ig_node['config'];

        global $wpdb;
        $wpdb->update(
          $wpdb->prefix . 'pn_cm_instagram_conversations',
          [
            'funnel_id' => $ig_node['funnel_id'],
            'node_id'   => $ig_node['node_id'],
          ],
          ['id' => $conversation->id]
        );

        if (!empty($ig_node['config']['ig_ai_model'])) {
          $wpdb->update(
            $wpdb->prefix . 'pn_cm_instagram_conversations',
            ['ai_model' => $ig_node['config']['ig_ai_model']],
            ['id' => $conversation->id]
          );
        }

        self::log('build_enriched_system_prompt — auto-linked conversation to funnel=' . $ig_node['funnel_id'] . ' node=' . $ig_node['node_id']);
      }
    }

    if (!empty($node_config['ig_system_prompt'])) {
      $base_prompt = $node_config['ig_system_prompt'];
      self::log('build_enriched_system_prompt — using node-level prompt override');
    }

    $parts = [];

    // Current date and time
    $tz        = wp_timezone();
    $now       = new DateTimeImmutable('now', $tz);
    $day_num   = (int) $now->format('N');
    $day_names = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
    $day_type  = $day_num <= 5 ? 'weekday (lunes-viernes)' : ($day_num === 6 ? 'sábado' : 'domingo');
    $parts[]   = "CURRENT DATE AND TIME: " . $day_names[$day_num] . ", " . wp_date('j F Y, H:i', null, $tz)
      . " (" . wp_timezone_string() . "). Day type: " . $day_type . ".";

    // Instagram formatting rules (plain text only)
    $parts[] = "FORMATTING RULES: You are responding via Instagram DMs. "
      . "Use plain text only. Instagram DMs do NOT support bold, italic or any special formatting.\n"
      . "- Do NOT use Markdown syntax (no **, no _, no #, no ```).\n"
      . "- Do NOT use WhatsApp formatting (no *bold*, no _italic_).\n"
      . "- Links: paste the plain URL directly (e.g. https://example.com). "
      . "NEVER use Markdown link syntax like [text](url).\n"
      . "CRITICAL: ALWAYS copy URLs exactly as they appear in the product catalog or reference data. "
      . "NEVER correct, fix, modify or rewrite any part of a URL, even if it appears to contain a typo. "
      . "The URLs are machine-generated and any modification will break them.";

    // Postal code requirement (top-level behavioral instruction)
    $require_postal = !empty($node_config['ig_require_postal_code']);
    if ($require_postal) {
      $parts[] = "MANDATORY RULE — POSTAL CODE REQUIRED FOR SHIPPING:\n"
        . "You are FORBIDDEN from giving any shipping cost, delivery estimate or delivery confirmation unless the customer has provided their POSTAL CODE in this conversation.\n"
        . "If the customer asks about shipping, delivery, or sending a product and you do NOT yet have their postal code, your ONLY response about shipping must be to ask for their postal code. Example: \"To check if we deliver to your area and the exact shipping cost, could you tell me your postal code?\"\n"
        . "Do NOT say \"we deliver to [location]\" or \"shipping costs X€\" without having the postal code first.\n"
        . "This rule overrides everything else. No exceptions.";
    }

    if (!empty($base_prompt)) {
      $parts[] = $base_prompt;
    }

    // Structured business context fields
    $strip_html = function ($html) {
      $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $html = preg_replace('/<\/(p|div|li|tr|h[1-6])>/i', "\n", $html);
      $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
      $text = wp_strip_all_tags($html);
      $text = preg_replace('/[^\S\n]+/', ' ', $text);
      $text = preg_replace('/\n{3,}/', "\n\n", $text);
      $text = implode("\n", array_map('trim', explode("\n", $text)));
      return trim($text);
    };

    $company_info = isset($node_config['ig_company_info']) ? $strip_html($node_config['ig_company_info']) : '';
    if (!empty($company_info)) {
      $parts[] = "COMPANY INFORMATION:\n" . $company_info;
    }

    // WooCommerce shipping zones (auto-generated)
    $use_wc_shipping = !empty($node_config['ig_wc_shipping_zones'])
      && ($node_config['ig_wc_shipping_zones'] === true || $node_config['ig_wc_shipping_zones'] === 'on' || $node_config['ig_wc_shipping_zones'] === '1');

    $wc_shipping_context = '';
    if ($use_wc_shipping) {
      $wc_shipping_context = self::get_woo_shipping_zones_context();
    }

    $shipping_info = isset($node_config['ig_shipping_info']) ? $strip_html($node_config['ig_shipping_info']) : '';

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

    $schedule_info = isset($node_config['ig_schedule_info']) ? $strip_html($node_config['ig_schedule_info']) : '';
    if (!empty($schedule_info)) {
      $parts[] = "OPENING HOURS:\n" . $schedule_info;
    }

    $knowledge = isset($node_config['ig_knowledge_base']) ? $strip_html($node_config['ig_knowledge_base']) : '';
    if (!empty($knowledge)) {
      $parts[] = "REFERENCE INFORMATION:\n" . $knowledge;
    }

    // WooCommerce products
    $include_woo = !empty($node_config['ig_include_woo']);
    if ($include_woo) {
      $include_variations = !empty($node_config['ig_include_woo_variations']);
      $add_to_cart_links = !empty($node_config['ig_include_woo_add_to_cart']);
      $woo_context = self::get_woo_products_context($include_variations, $add_to_cart_links);
      if (!empty($woo_context)) {
        $link_instruction = $add_to_cart_links
          ? 'when a user asks about a product ALWAYS include the "Buy link" so they can add it to cart directly'
          : 'when a user asks about a product ALWAYS include the "Product link" so they can visit the product page';
        $image_instruction = '. IMAGES: NEVER include image URLs in your responses. NEVER output markdown image syntax like ![alt](url). NEVER invent URLs ending in .jpg, .jpeg, .png, .webp or any image extension. You do not have access to product images';
        $parts[] = "PRODUCT CATALOG (use this data to answer questions about products, prices, availability; {$link_instruction}{$image_instruction}):\n" . $woo_context;
      }
    }

    // Blog posts
    $include_posts = !empty($node_config['ig_include_posts']);
    if ($include_posts) {
      $posts_context = self::get_posts_context();
      if (!empty($posts_context)) {
        $parts[] = "BLOG ARTICLES (use this content to answer user questions; share the URL when relevant):\n" . $posts_context;
      }
    }

    // Pages
    $include_pages = !empty($node_config['ig_include_pages']);
    if ($include_pages) {
      $pages_context = self::get_pages_context();
      if (!empty($pages_context)) {
        $parts[] = "WEBSITE PAGES (use this content to answer user questions; share the URL when relevant):\n" . $pages_context;
      }
    }

    // Mandatory style rules
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
   * Get node config from a conversation's linked funnel node.
   *
   * @param object $conversation
   * @return array
   */
  private static function get_node_config($conversation) {
    if (empty($conversation->funnel_id) || empty($conversation->node_id)) {
      return [];
    }

    $canvas_data = get_post_meta($conversation->funnel_id, 'pn_cm_funnel_canvas', true);
    if (!$canvas_data) {
      return [];
    }

    $data = json_decode($canvas_data, true);
    if (!$data || !isset($data['nodes'])) {
      return [];
    }

    foreach ($data['nodes'] as $node) {
      if ($node['id'] === $conversation->node_id && isset($node['config'])) {
        return $node['config'];
      }
    }

    return [];
  }

  /**
   * Find the first Instagram AI node across all published funnels.
   *
   * @return array|null
   */
  private static function find_instagram_ai_node() {
    $funnels = get_posts([
      'post_type'      => 'pn_cm_funnel',
      'post_status'    => ['publish', 'draft', 'private'],
      'posts_per_page' => -1,
      'fields'         => 'ids',
    ]);

    if (empty($funnels)) {
      return null;
    }

    foreach ($funnels as $funnel_id) {
      $canvas_data = get_post_meta($funnel_id, 'pn_cm_funnel_canvas', true);
      if (!$canvas_data) {
        continue;
      }

      $data = json_decode($canvas_data, true);
      if (!$data || !isset($data['nodes'])) {
        continue;
      }

      foreach ($data['nodes'] as $node) {
        $subtype = isset($node['subtype']) ? $node['subtype'] : '';

        if ($subtype === 'instagram_ai') {
          $config = isset($node['config']) ? $node['config'] : [];
          return [
            'funnel_id' => $funnel_id,
            'node_id'   => $node['id'],
            'config'    => $config,
          ];
        }
      }
    }

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
        $method_type  = $method->id;
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
   * Build a text summary of WooCommerce products.
   *
   * @param bool $include_variations
   * @param bool $add_to_cart_links
   * @return string
   */
  private static function get_woo_products_context($include_variations = false, $add_to_cart_links = false) {
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

    $shop_url   = get_permalink(wc_get_page_id('shop'));
    $link_label = $add_to_cart_links ? 'Buy link' : 'Product link';

    $blocks = [];
    foreach ($products as $product) {
      $product_id = $product->get_id();

      if ($add_to_cart_links) {
        $product_url = $shop_url ? add_query_arg('add-to-cart', $product_id, $shop_url) : home_url('?add-to-cart=' . $product_id);
      } else {
        $product_url = get_permalink($product_id);
      }

      $block  = '---' . "\n";
      $block .= 'Product: ' . $product->get_name() . "\n";
      $block .= 'ID: ' . $product_id . "\n";

      $price = $product->get_price();
      if ($price) {
        $block .= 'Price: ' . strip_tags(wc_price($price));
        if ($product->is_on_sale()) {
          $block .= ' (before: ' . strip_tags(wc_price($product->get_regular_price())) . ')';
        }
        $block .= "\n";
      }

      if ($include_variations && $product->is_type('variable')) {
        $variations = $product->get_available_variations();
        foreach (array_slice($variations, 0, 15) as $v) {
          $var_id = $v['variation_id'];
          $attrs  = [];

          if ($add_to_cart_links) {
            $query_args = [
              'add-to-cart'  => $product_id,
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

          $var_price = !empty($v['display_price']) ? strip_tags(wc_price($v['display_price'])) : '';
          $block .= '  Variation ID ' . $var_id . ' — ' . implode(', ', $attrs);
          if ($var_price) {
            $block .= ' — ' . $var_price;
          }
          $block .= ' — ' . $link_label . ': ' . $var_url . "\n";
        }
      }

      $block .= $link_label . ': ' . $product_url . "\n";
      $blocks[] = $block;
    }

    return implode("\n", $blocks);
  }

  /**
   * Build a text summary of published blog posts.
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

      $block  = '---' . "\n";
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
   * Build a text summary of published pages.
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

      $block  = '---' . "\n";
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
   * Get temperature for a conversation.
   *
   * @param object $conversation
   * @return float
   */
  private static function get_conversation_temperature($conversation) {
    if ($conversation->funnel_id && $conversation->node_id) {
      $canvas_data = get_post_meta($conversation->funnel_id, 'pn_cm_funnel_canvas', true);
      if ($canvas_data) {
        $data = json_decode($canvas_data, true);
        if ($data && isset($data['nodes'])) {
          foreach ($data['nodes'] as $node) {
            if ($node['id'] === $conversation->node_id && isset($node['config']['ig_temperature'])) {
              return (float) $node['config']['ig_temperature'];
            }
          }
        }
      }
    }

    return (float) get_option('pn_customers_manager_whatsapp_temperature', 0.7);
  }

  /* ================================================================
   * MARKDOWN -> INSTAGRAM FORMATTING
   * ================================================================ */

  /**
   * Strip Markdown formatting for Instagram DMs (plain text only).
   *
   * @param string $text
   * @return string
   */
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

  /**
   * Remove system prompt fragments that the model may have leaked into its response.
   */
  private static function sanitize_system_prompt_leak($text) {
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
   * Self-sufficient: re-reads funnel config as safety net if $require_postal is false.
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
        $ig_node = self::find_instagram_ai_node();
        if ($ig_node && !empty($ig_node['config'])) {
          $nc = $ig_node['config'];
        }
      }
      if (!empty($nc['ig_require_postal_code'])) {
        $require_postal = true;
        self::log('enforce_postal_code_rule — SAFETY NET activated: found ig_require_postal_code='
          . var_export($nc['ig_require_postal_code'], true) . ' → require_postal=YES');
      } else {
        self::log('enforce_postal_code_rule — safety net: ig_require_postal_code not set or falsy'
          . ' config_keys=' . ($nc ? implode(',', array_keys($nc)) : 'EMPTY'));
      }
    }

    if (!$require_postal) {
      return $text;
    }

    // Check if the AI response mentions shipping costs or delivery confirmation
    $shipping_keywords = '/(\d+[\.,]?\d*\s*€|envío|envio|entrega a domicilio|coste de envío|gastos de envío|hacemos envíos|realizamos envíos|shipping|delivery cost)/iu';
    if (!preg_match($shipping_keywords, $text, $kw_match)) {
      self::log('enforce_postal_code_rule — no shipping keywords found in response, skipping');
      return $text;
    }
    self::log('enforce_postal_code_rule — shipping keyword matched: "' . $kw_match[0] . '"');

    // Check all user messages for a postal code (Spanish: 5 digits starting with 0-5)
    $postal_found = false;
    foreach ($messages as $msg) {
      if ($msg['role'] !== 'user') {
        continue;
      }
      if (preg_match('/\b[0-5]\d{4}\b/', $msg['content'])) {
        $postal_found = true;
        self::log('enforce_postal_code_rule — postal code found in user message: "' . $msg['content'] . '"');
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
      // Only keep if it doesn't mention shipping prices
      if (!preg_match('/\d+[\.,]?\d*\s*€/u', $candidate)) {
        $first_sentence = $candidate . "\n\n";
      }
    }

    return $first_sentence . 'Para poder indicarte el coste exacto de envío, ¿podrías facilitarme tu código postal?';
  }

  /**
   * Validate URLs in AI response — repair or remove broken links.
   */
  private static function validate_response_urls($text) {
    if (!preg_match_all('/(https?:\/\/[^\s,;)\]]+)/i', $text, $matches)) {
      return $text;
    }

    $urls      = array_unique($matches[1]);
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

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
        continue;
      }

      $code = wp_remote_retrieve_response_code($response);
      if ($code < 400) {
        continue;
      }

      self::log('validate_response_urls — broken link (HTTP ' . $code . '): ' . $url);

      $repaired = false;
      if (!empty($product_base)) {
        $url_path     = wp_parse_url($url, PHP_URL_PATH);
        $base_pattern = '#/' . preg_quote($product_base, '#') . '/([a-z0-9\-]+)/?$#i';
        if ($url_path && preg_match($base_pattern, $url_path, $slug_match)) {
          $correct_url = self::find_closest_product_url($slug_match[1]);
          if ($correct_url && $correct_url !== $url) {
            $text = str_replace($url, $correct_url, $text);
            $repaired = true;
            self::log('validate_response_urls — repaired: ' . $url . ' → ' . $correct_url);
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
   */
  private static function find_closest_product_url($broken_slug) {
    global $wpdb;

    $exact = get_page_by_path($broken_slug, OBJECT, 'product');
    if ($exact && $exact->post_status === 'publish') {
      return get_permalink($exact->ID);
    }

    $prefix     = substr($broken_slug, 0, 8);
    $candidates = $wpdb->get_results($wpdb->prepare(
      "SELECT ID, post_name FROM {$wpdb->posts}
       WHERE post_type = 'product' AND post_status = 'publish'
       AND post_name LIKE %s LIMIT 20",
      $wpdb->esc_like($prefix) . '%'
    ));

    if (empty($candidates)) {
      return false;
    }

    $best_id       = 0;
    $best_distance = PHP_INT_MAX;
    foreach ($candidates as $c) {
      $d = levenshtein($broken_slug, $c->post_name);
      if ($d < $best_distance) {
        $best_distance = $d;
        $best_id       = $c->ID;
      }
    }

    $max_distance = max(3, (int) (strlen($broken_slug) * 0.3));
    if ($best_distance <= $max_distance && $best_id) {
      return get_permalink($best_id);
    }

    return false;
  }

  /**
   * Remove hallucinated image URLs from AI response.
   */
  private static function strip_hallucinated_image_urls($text) {
    $original = $text;
    $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $text);
    $text = preg_replace('/^\s*https?:\/\/\S+\.(?:jpg|jpeg|png|gif|webp|svg|bmp|tiff)\b\S*\s*$/mi', '', $text);
    $text = preg_replace('/:\s*https?:\/\/\S+\.(?:jpg|jpeg|png|gif|webp|svg|bmp|tiff)\b\S*/i', '.', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = trim($text);
    if ($text !== $original) {
      self::log('strip_hallucinated_image_urls — removed fabricated image URLs from AI response');
    }
    return $text;
  }

  /* ================================================================
   * OPENAI API
   * ================================================================ */

  /**
   * Call OpenAI Chat Completions API (shared credentials with WhatsApp).
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
      return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['choices'][0]['message']['content'])) {
      return $body['choices'][0]['message']['content'];
    }

    return false;
  }

  /* ================================================================
   * INSTAGRAM MESSENGER API
   * ================================================================ */

  /**
   * Send a text message via Instagram Messenger API.
   *
   * @param string $to   Recipient IGSID.
   * @param string $text Message text.
   * @return bool
   */
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

  /**
   * Get or create a conversation for the given Instagram user ID.
   *
   * @param string $ig_user_id
   * @param string $contact_name
   * @return object|null
   */
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

    $ig_node = self::find_instagram_ai_node();
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

  /**
   * Update messages JSON for a conversation.
   *
   * @param int   $conversation_id
   * @param array $messages
   */
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

  /**
   * Close a conversation.
   *
   * @param int $conversation_id
   */
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
   * Delete a conversation.
   *
   * @param int $conversation_id
   */
  public static function delete_conversation($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

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

  /**
   * Get a single conversation by ID.
   *
   * @param int $id
   * @return object|null
   */
  public static function get_conversation($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_instagram_conversations';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
  }

  /**
   * Get count of active conversations (for menu badge).
   *
   * @return int
   */
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

  /**
   * Render the Instagram AI admin page.
   */
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

  /**
   * Test Instagram API by sending a test message.
   *
   * @param string $ig_user_id
   * @return array
   */
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

  /**
   * Check for recently received Instagram messages (webhook reception test).
   *
   * @param string $since
   * @return array
   */
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

  /**
   * Get conversations list for the funnel builder popup.
   *
   * @return array
   */
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
      'ig_user'   => $conv->ig_user_id,
      'name'      => $conv->contact_name,
      'status'    => $conv->status,
      'model'     => $conv->ai_model,
      'messages'  => $messages,
    ];
  }

  /**
   * Enqueue admin CSS and JS for the Instagram AI page.
   */
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

  /* ================================================================
   * LOGGING
   * ================================================================ */

  /**
   * Log a message to the Instagram AI debug log.
   *
   * @param string $message
   */
  private static function log($message) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
      return;
    }

    $upload_dir = wp_upload_dir();
    $log_dir    = $upload_dir['basedir'] . '/pn-cm-logs';

    if (!file_exists($log_dir)) {
      wp_mkdir_p($log_dir);
      file_put_contents($log_dir . '/.htaccess', 'deny from all');
    }

    $log_file = $log_dir . '/instagram-ai.log';
    $time     = current_time('Y-m-d H:i:s');
    $line     = '[' . $time . '] ' . $message . "\n";

    if (file_exists($log_file) && filesize($log_file) > 1048576) {
      $contents = file_get_contents($log_file);
      file_put_contents($log_file, substr($contents, -524288));
    }

    file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
  }
}
