<?php
/**
 * Shared AI Chat functionality.
 *
 * Common logic used by WhatsApp AI and Instagram AI integrations:
 * system prompt building, WooCommerce integration, postal code enforcement,
 * order detection, OpenAI API, URL validation, and logging.
 *
 * @since   1.0.61
 * @package pn-customers-manager
 */

trait PN_CM_AI_Chat_Common {

  /* ================================================================
   * ABSTRACT METHODS — each platform must implement these
   * ================================================================ */

  /** Config key prefix, e.g. 'wa_' or 'ig_'. */
  abstract protected static function platform_prefix();

  /** DB table suffix, e.g. 'pn_cm_whatsapp_conversations'. */
  abstract protected static function conversations_table_suffix();

  /** Human-readable name, e.g. 'WhatsApp' or 'Instagram'. */
  abstract protected static function platform_display_name();

  /** Brand colour for emails, e.g. '#25D366'. */
  abstract protected static function brand_color();

  /** Email type identifier, e.g. 'pn_cm_whatsapp_order'. */
  abstract protected static function email_type();

  /** Log file name without extension, e.g. 'whatsapp-ai'. */
  abstract protected static function log_channel();

  /** Funnel node subtype, e.g. 'whatsapp_ai'. */
  abstract protected static function node_subtype();

  /** Return the FORMATTING RULES text for this platform. */
  abstract protected static function get_formatting_rules();

  /** Return PRODUCT IMAGES RULES text (or empty string). */
  abstract protected static function get_image_rules($include_images);

  /** Whether the platform can send native images (WhatsApp yes, Instagram no). */
  abstract protected static function supports_native_images();

  /** DB column for the user identifier, e.g. 'phone_number' or 'ig_user_id'. */
  abstract protected static function get_identifier_field();

  /** Label for the identifier shown in emails, e.g. 'Phone' or 'Instagram User'. */
  abstract protected static function get_identifier_label();

  /** Extract the identifier value from a conversation row. */
  abstract protected static function get_identifier_value($conversation);

  /* ================================================================
   * LOGGING
   * ================================================================ */

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

    $log_file = $log_dir . '/' . static::log_channel() . '.log';
    $time     = current_time('Y-m-d H:i:s');
    $line     = '[' . $time . '] ' . $message . "\n";

    if (file_exists($log_file) && filesize($log_file) > 1048576) {
      $contents = file_get_contents($log_file);
      file_put_contents($log_file, substr($contents, -524288));
    }

    file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
  }

  /* ================================================================
   * NODE CONFIG
   * ================================================================ */

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

  private static function find_ai_node() {
    $funnels = get_posts([
      'post_type'      => 'pn_cm_funnel',
      'post_status'    => ['publish', 'draft', 'private'],
      'posts_per_page' => -1,
      'fields'         => 'ids',
    ]);

    if (empty($funnels)) {
      return null;
    }

    $target_subtype = static::node_subtype();

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

        if ($subtype === $target_subtype) {
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

  /* ================================================================
   * ERROR FALLBACK MESSAGE
   * ================================================================ */

  /**
   * Return the message to show the customer when the AI model fails.
   *
   * Priority: node setting ({prefix}error_fallback_message) > Spanish default.
   *
   * @param array $node_config
   * @return string
   */
  protected static function get_error_fallback_message($node_config) {
    $prefix  = static::platform_prefix();
    $key     = $prefix . 'error_fallback_message';
    $default = __('Lo siento, en este momento no puedo responder. Por favor, inténtalo de nuevo más tarde.', 'pn-customers-manager');

    if (!empty($node_config[$key]) && is_string($node_config[$key])) {
      $configured = trim($node_config[$key]);
      if ($configured !== '') {
        return self::strip_html_to_plain_text($configured);
      }
    }

    return $default;
  }

  /**
   * Convert HTML from a WYSIWYG editor to plain text suitable for
   * WhatsApp / Instagram. Preserves paragraph breaks as newlines.
   *
   * @param string $html
   * @return string
   */
  protected static function strip_html_to_plain_text($html) {
    $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n", $html);
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = wp_strip_all_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
  }

  /* ================================================================
   * AI MODEL RESOLUTION
   * ================================================================ */

  /**
   * Determine AI model with correct priority: node_config > global_option > conversation.
   *
   * @param array  $node_config
   * @param object $conversation
   * @return string The resolved model name.
   */
  protected static function resolve_ai_model($node_config, $conversation) {
    $prefix          = static::platform_prefix();
    $global_ai_model = get_option('pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini');

    if (!empty($node_config[$prefix . 'ai_model'])) {
      $ai_model     = $node_config[$prefix . 'ai_model'];
      $model_source = 'node_config';
    } elseif (!empty($global_ai_model)) {
      $ai_model     = $global_ai_model;
      $model_source = 'global_option';
    } else {
      $ai_model     = !empty($conversation->ai_model) ? $conversation->ai_model : 'gpt-4o-mini';
      $model_source = 'conversation';
    }

    self::log('resolve_ai_model — ai_model=' . $ai_model
      . ' (source=' . $model_source . ')'
      . ' node_config[' . $prefix . 'ai_model]=' . var_export($node_config[$prefix . 'ai_model'] ?? null, true)
      . ' global_option=' . var_export($global_ai_model, true)
      . ' conversation->ai_model=' . var_export($conversation->ai_model ?? null, true));

    return $ai_model;
  }

  /**
   * Sync the effective AI model to the conversation DB row if it changed.
   *
   * @param string $ai_model
   * @param object $conversation
   */
  protected static function sync_ai_model_to_db($ai_model, $conversation) {
    if ($ai_model !== ($conversation->ai_model ?? '')) {
      global $wpdb;
      $wpdb->update(
        $wpdb->prefix . static::conversations_table_suffix(),
        ['ai_model' => $ai_model],
        ['id' => $conversation->id]
      );
      self::log('sync_ai_model_to_db — synced ai_model to DB: ' . $ai_model);
    }
  }

  /* ================================================================
   * SYSTEM PROMPT
   * ================================================================ */

  private static function build_enriched_system_prompt($conversation) {
    $prefix      = static::platform_prefix();
    $base_prompt = get_option('pn_customers_manager_whatsapp_system_prompt', '');

    self::log('build_enriched_system_prompt — global prompt length=' . mb_strlen($base_prompt));

    $node_config = self::get_node_config($conversation);

    self::log('build_enriched_system_prompt — get_node_config returned ' . (empty($node_config) ? 'EMPTY' : count($node_config) . ' keys: ' . implode(',', array_keys($node_config))));

    if (empty($node_config)) {
      self::log('build_enriched_system_prompt — node_config empty, searching for AI node...');
      $ai_node = self::find_ai_node();
      if ($ai_node && !empty($ai_node['config'])) {
        $node_config = $ai_node['config'];

        global $wpdb;
        $wpdb->update(
          $wpdb->prefix . static::conversations_table_suffix(),
          [
            'funnel_id' => $ai_node['funnel_id'],
            'node_id'   => $ai_node['node_id'],
          ],
          ['id' => $conversation->id]
        );

        if (!empty($ai_node['config'][$prefix . 'ai_model'])) {
          $wpdb->update(
            $wpdb->prefix . static::conversations_table_suffix(),
            ['ai_model' => $ai_node['config'][$prefix . 'ai_model']],
            ['id' => $conversation->id]
          );
        }

        self::log('build_enriched_system_prompt — auto-linked conversation to funnel=' . $ai_node['funnel_id'] . ' node=' . $ai_node['node_id']);
      }
    }

    if (!empty($node_config[$prefix . 'system_prompt'])) {
      $base_prompt = $node_config[$prefix . 'system_prompt'];
      self::log('build_enriched_system_prompt — using node-level prompt override');
    }

    $parts = [];

    // 1. Current date and time
    $tz        = wp_timezone();
    $now       = new DateTimeImmutable('now', $tz);
    $day_num   = (int) $now->format('N');
    $day_names = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
    $day_type  = $day_num <= 5 ? 'weekday (lunes-viernes)' : ($day_num === 6 ? 'sábado' : 'domingo');
    // Build a mini-calendar of the next 7 days so the model can correctly
    // map dates to day names without hallucinating.
    $upcoming = [];
    for ($d = 0; $d <= 7; $d++) {
      $future   = $now->modify('+' . $d . ' days');
      $fday_num = (int) $future->format('N');
      $fday_type = $fday_num <= 5 ? 'weekday' : ($fday_num === 6 ? 'sábado' : 'domingo');
      $label     = $d === 0 ? 'TODAY' : ($d === 1 ? 'TOMORROW' : '');
      $upcoming[] = '- ' . $day_names[$fday_num] . ' ' . $future->format('j M')
        . ' (' . $fday_type . ')' . ($label ? ' ← ' . $label : '');
    }

    $parts[] = "CURRENT DATE AND TIME: " . $day_names[$day_num] . ", " . wp_date('j F Y, H:i', null, $tz)
      . " (" . wp_timezone_string() . "). Day type: " . $day_type . ".\n"
      . "UPCOMING DAYS (use this to determine the day of the week for any date — NEVER guess):\n"
      . implode("\n", $upcoming);

    // 2. Platform formatting rules
    $parts[] = static::get_formatting_rules();

    // 3. Postal code requirement
    $require_postal = !empty($node_config[$prefix . 'require_postal_code']);
    if ($require_postal) {
      $parts[] = "MANDATORY RULE — POSTAL CODE REQUIRED FOR SHIPPING:\n"
        . "You CAN freely show product information, prices, images, and purchase links at any time — that is NOT affected by this rule.\n"
        . "However, you are FORBIDDEN from giving any SHIPPING cost, DELIVERY estimate, or DELIVERY confirmation unless the customer has provided their POSTAL CODE.\n"
        . "BEFORE asking the customer for a postal code, you MUST scan the customer's CURRENT message AND their previous messages for any 5-digit number that looks like a postal code (e.g. \"28080\", \"08001\", \"46015\"). If one is present — even embedded inside another question such as \"¿hacéis envíos al 28080?\" or \"mi CP es 28080\" — treat it as the customer's postal code and USE it directly to answer. Do NOT ask for it again when it has already been given.\n"
        . "Only ask for the postal code when the customer is asking about shipping/delivery AND no postal code appears anywhere in the conversation. Example: \"Para poder indicarte si realizamos envíos a tu zona y el coste exacto, ¿podrías facilitarme tu código postal?\"\n"
        . "TIMING: NEVER ask for the postal code while you are in the middle of the product recommendation flow (showing products, sharing photos, discussing preferences, or helping the customer choose). Complete the entire product selection process FIRST. Only ask for the postal code AFTER the customer has chosen a product and explicitly asks about shipping/delivery costs, or when you need to collect delivery information.\n"
        . "IMPORTANT: A postal code provided earlier for a DIFFERENT address does NOT count. If the customer later asks about shipping to a new place without giving a new postal code, ask again.\n"
        . "Do NOT say \"we deliver to [location]\" or \"shipping costs X€\" without having the postal code first.";
    }

    // 4. Order acceptance protocol
    $enable_chat_orders    = !empty($node_config[$prefix . 'enable_chat_orders']);
    $enable_special_orders = !empty($node_config[$prefix . 'enable_special_orders']);
    $custom_order_redirect = trim(get_option('pn_customers_manager_order_redirect_message', ''));
    $order_redirect_instruction = !empty($custom_order_redirect)
      ? 'redirect the customer with this exact message: "' . $custom_order_redirect . '"'
      : 'politely redirect them to call by phone or use the contact methods available on the website';

    // Build the complex/special order instruction depending on whether special order forwarding is enabled.
    if ($enable_special_orders) {
      $complex_order_block = "SPECIAL ORDER FORWARDING (for requests that CANNOT be fulfilled with a simple purchase link):\n"
        . "When the customer's request is complex (bulk/wholesale orders, B2B proposals, custom products, orders that require a formal quote), do NOT redirect them away. Instead, follow this flow:\n"
        . "  1. Collect ALL relevant details from the customer naturally in conversation: what they need, quantities, any special requirements, their name, contact email or phone, and delivery preferences.\n"
        . "  2. Once you have enough details, present a clear summary of their request and ask them to confirm that the details are correct.\n"
        . "  3. ONLY after the customer explicitly confirms (e.g. \"sí\", \"correcto\", \"adelante\"), include the hidden tag [PEDIDO_ESPECIAL] at the END of your response message. The system will automatically strip this tag before the customer sees it and will forward the full conversation by email to the sales team.\n"
        . "  4. In the SAME message where you include [PEDIDO_ESPECIAL], tell the customer that their request has been forwarded to the team and someone will contact them shortly.\n"
        . "CRITICAL RULES:\n"
        . "- You MUST include [PEDIDO_ESPECIAL] exactly ONCE after the customer confirms. Do NOT forget this tag — without it the email will NOT be sent.\n"
        . "- NEVER include [PEDIDO_ESPECIAL] without the customer's explicit confirmation.\n"
        . "- Place the tag at the very end of your message, after all visible text.\n";
    } else {
      $complex_order_block = "COMPLEX ORDER REDIRECT:\n"
        . "If the customer's request CANNOT be fulfilled with a simple purchase link (e.g. bulk/wholesale orders, B2B proposals, custom products, orders that require a formal quote), do NOT attempt to process the order through this chat. Instead, " . $order_redirect_instruction . ".\n";
    }

    if ($enable_chat_orders) {
      $order_protocol = "ORDER ACCEPTANCE PROTOCOL (three mandatory steps):\n"
        . "You CAN accept orders through this chat, but ONLY following this exact three-step flow. NEVER skip any step.\n"
        . "\n"
        . $complex_order_block . "\n"
        . "\n"
        . "STEP 1 — DELIVERY INFORMATION COLLECTION:\n"
        . "Before showing any order summary, you MUST collect the following delivery details from the customer:\n"
        . "  • Customer name (who will receive the order).\n"
        . "  • Postal code" . ($require_postal ? " (MANDATORY — you already have a rule about this)" : "") . ".\n"
        . "  • Delivery address (street, number, city).\n"
        . "  • Phone number (if not already known from this chat).\n"
        . "Ask for the missing details naturally in flowing prose — do NOT use numbered lists or bullet points for these questions. For example, say something like: \"Para proceder con el pedido, ¿podrías darme tu nombre y código postal?\" instead of listing them as 1., 2., 3. If the customer has ALREADY provided some of these details earlier in the conversation (e.g. a postal code when asking about shipping), do NOT ask again — reuse what you already know.\n"
        . "Do NOT proceed to Step 2 until you have at least: name, postal code, and address.\n"
        . "EXCEPTION: If the customer explicitly says they will pick up in store (no shipping needed), you may skip postal code and address, but still collect the customer name.\n"
        . "\n"
        . "STEP 2 — ORDER SUMMARY AND EXPLICIT CONFIRMATION REQUEST:\n"
        . "Once you have the delivery details AND the chosen products, send a clear summary and ask the customer to confirm. Do NOT include any [PEDIDO_CONFIRMADO] tag in this message.\n"
        . "The summary MUST include:\n"
        . "  • The list of chosen products with their quantities and prices (only the products the customer actually picked — never include products that were just shown as options or suggestions).\n"
        . "  • Delivery details: name, address, postal code (or \"pickup in store\").\n"
        . "  • Shipping method and cost (or pickup), if already known.\n"
        . "  • Estimated total (products + shipping), if it can be computed.\n"
        . "  • A direct question asking the customer to confirm the order (e.g. \"¿Confirmas el pedido?\" / \"Do you confirm the order?\").\n"
        . "Do NOT invent quantities — if the customer did not specify a quantity, assume 1 and state it explicitly.\n"
        . "\n"
        . "STEP 3 — FINAL CONFIRMATION TAG:\n"
        . "ONLY after the customer EXPLICITLY accepts the summary (e.g. \"sí\", \"confirmo\", \"perfecto, adelante\", \"sí, preparadlo\", \"yes\", \"confirm\"), send a short confirmation message AND include the tag [PEDIDO_CONFIRMADO:IDS] in that message, where IDS is a comma-separated list of the WooCommerce product IDs that the customer actually confirmed (ONLY those). Use the product IDs shown in the PRODUCT CATALOG section of this prompt.\n"
        . "Example: if the customer confirmed two products with IDs 123 and 456, include exactly: [PEDIDO_CONFIRMADO:123,456]\n"
        . "The system will strip this tag before the customer sees it.\n"
        . "\n"
        . "IMPORTANT RULES:\n"
        . "- NEVER skip Step 1. You MUST have the customer's delivery information before showing the order summary.\n"
        . "- NEVER emit [PEDIDO_CONFIRMADO] without first showing a summary (Step 2) and getting an explicit confirmation in a PREVIOUS message.\n"
        . "- NEVER include product IDs of items the customer did not explicitly choose (do NOT include items you merely suggested or listed as options).\n"
        . "- Include the tag exactly ONCE per confirmed order. After you have emitted [PEDIDO_CONFIRMADO:...] for an order, NEVER emit it again in the same conversation, even if the customer provides additional information afterwards.\n"
        . "- If the customer asks to modify the order after the summary but before confirming, update the summary and ask again — do NOT emit the tag yet.\n"
        . "- If you cannot determine the exact product IDs, ask the customer to clarify which product they want before sending the summary.";
      $parts[] = $order_protocol;
    } else {
      $order_policy = "ORDER POLICY:\n"
        . "You CANNOT process or manage full orders through this chat (no delivery details collection, no order summaries, no confirmation flows).\n"
        . "However, when a customer expresses interest in a product or confirms they want it, you MUST ALWAYS provide the product link (Buy link or Product link from the catalog) so they can purchase directly through the platform. For example: \"You can buy it directly here: [URL]\". This is your PRIMARY way to help customers complete a purchase.\n"
        . "If the customer needs additional assistance beyond the purchase link (e.g. custom orders, special requests, complex delivery questions), " . $order_redirect_instruction . ".\n"
        . "NEVER include the tag [PEDIDO_CONFIRMADO] or [PEDIDO_CONFIRMADO:...] in your responses.";
      if ($enable_special_orders) {
        $order_policy .= "\n\n" . $complex_order_block;
      }
      $parts[] = $order_policy;
    }

    // 5. Base prompt
    if (!empty($base_prompt)) {
      $parts[] = $base_prompt;
    }

    // 6. Structured business context fields
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

    $company_info = isset($node_config[$prefix . 'company_info']) ? $strip_html($node_config[$prefix . 'company_info']) : '';
    if (!empty($company_info)) {
      $parts[] = "COMPANY INFORMATION:\n" . $company_info;
    }

    // WooCommerce shipping zones (auto-generated)
    $use_wc_shipping = !empty($node_config[$prefix . 'wc_shipping_zones'])
      && ($node_config[$prefix . 'wc_shipping_zones'] === true || $node_config[$prefix . 'wc_shipping_zones'] === 'on' || $node_config[$prefix . 'wc_shipping_zones'] === '1');

    $wc_shipping_context = '';
    if ($use_wc_shipping) {
      $wc_shipping_context = self::get_woo_shipping_zones_context();
    }

    $shipping_info = isset($node_config[$prefix . 'shipping_info']) ? $strip_html($node_config[$prefix . 'shipping_info']) : '';

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

    $schedule_info = isset($node_config[$prefix . 'schedule_info']) ? $strip_html($node_config[$prefix . 'schedule_info']) : '';
    if (!empty($schedule_info)) {
      $current_time_str = wp_date('H:i', null, $tz);
      $current_day_name = $day_names[$day_num];
      $parts[] = "OPENING HOURS:\n" . $schedule_info
        . "\n\nMANDATORY — OPEN/CLOSED CHECK:\n"
        . "Right now it is " . $current_day_name . " at " . $current_time_str . ".\n"
        . "Step 1: Find today's schedule in the OPENING HOURS above. Identify the opening time and closing time for " . $current_day_name . ".\n"
        . "Step 2: Compare the current time (" . $current_time_str . ") against that range.\n"
        . "  - If " . $current_time_str . " >= opening time AND " . $current_time_str . " < closing time → the store is OPEN.\n"
        . "  - Otherwise → the store is CLOSED.\n"
        . "Step 3: If the customer asks about visiting, availability, or if the store is open, respond based on this check.\n"
        . "IMPORTANT: Do NOT guess or assume. Follow the comparison strictly. If the store closes at 20:00 and the current time is 15:17, the store IS open.";
    }

    $knowledge = isset($node_config[$prefix . 'knowledge_base']) ? $strip_html($node_config[$prefix . 'knowledge_base']) : '';
    if (!empty($knowledge)) {
      $parts[] = "REFERENCE INFORMATION:\n" . $knowledge;
    }

    // 7. WooCommerce products
    $resolve_node_flag = function ($key) use ($node_config) {
      if (!array_key_exists($key, $node_config)) {
        return false;
      }
      $v = $node_config[$key];
      return $v === true || $v === 'on' || $v === '1' || $v === 1;
    };

    $include_woo = $resolve_node_flag($prefix . 'include_woo');
    if ($include_woo) {
      $include_variations  = $resolve_node_flag($prefix . 'include_woo_variations');
      $add_to_cart_links   = $resolve_node_flag($prefix . 'include_woo_add_to_cart');
      $include_woo_images  = $resolve_node_flag($prefix . 'include_woo_images') && static::supports_native_images();
      $include_categories  = $resolve_node_flag($prefix . 'include_woo_categories');
      $include_tags        = $resolve_node_flag($prefix . 'include_woo_tags');

      // Category filter: array of term IDs selected by the user (empty = all)
      $categories_filter = [];
      if ($include_categories && !empty($node_config[$prefix . 'woo_categories_filter'])) {
        $raw_filter = $node_config[$prefix . 'woo_categories_filter'];
        if (is_array($raw_filter)) {
          $categories_filter = array_map('intval', array_filter($raw_filter));
        } elseif (is_string($raw_filter)) {
          $categories_filter = array_map('intval', array_filter(explode(',', $raw_filter)));
        }
      }

      self::log('build_enriched_system_prompt — WooCommerce flags:'
        . ' add_to_cart_links=' . ($add_to_cart_links ? 'YES' : 'NO')
        . ' include_woo_images=' . ($include_woo_images ? 'YES' : 'NO')
        . ' include_categories=' . ($include_categories ? 'YES' : 'NO')
        . ' include_tags=' . ($include_tags ? 'YES' : 'NO')
        . ' categories_filter=' . (!empty($categories_filter) ? implode(',', $categories_filter) : 'ALL'));

      $woo_context = self::get_woo_products_context($include_variations, $add_to_cart_links, $include_woo_images, $include_categories, $include_tags, $categories_filter);
      // Recommendations are ON by default when WooCommerce is active.
      // Only disabled if the node explicitly opts out.
      $disable_recommendations = $resolve_node_flag($prefix . 'disable_recommendations');
      $enable_recommendations = !$disable_recommendations;
      if (!empty($woo_context)) {
        if ($enable_recommendations) {
          $link_label = $add_to_cart_links ? 'Buy link' : 'Product link';
          $link_instruction = 'include the "' . $link_label . '" ONLY when showing individual product details to a customer who has already chosen a product — NOT during the guided recommendation flow (see GUIDED PRODUCT RECOMMENDATION PROTOCOL below)';
        } else {
          $link_instruction = $add_to_cart_links
            ? 'when a user asks about a product ALWAYS include the "Buy link" so they can add it to cart directly'
            : 'when a user asks about a product ALWAYS include the "Product link" so they can visit the product page';
        }

        if ($include_variations) {
          $parts[] = "CRITICAL — PRODUCT VARIATIONS RULE:\n"
            . "Products marked with [HAS VARIATIONS — ask customer to choose] in the catalog below have multiple options (e.g. different sizes, colors, with/without accessories). "
            . "When a customer selects one of these products, you MUST:\n"
            . "1. Tell the customer that this product has several options available.\n"
            . "2. List ALL the available variations with their attributes and prices.\n"
            . "3. Ask the customer which variation they prefer.\n"
            . "4. ONLY after the customer picks a variation, provide the purchase link.\n"
            . "NEVER skip this step. NEVER provide a purchase link for a product marked [HAS VARIATIONS] without first showing the options and letting the customer choose.";
        }

        if (static::supports_native_images()) {
          $parts[] = "PRODUCT CATALOG (use this data to answer questions about products, prices, availability; {$link_instruction}):\n" . $woo_context;
        } else {
          $image_instruction = '. IMAGES: NEVER include image URLs in your responses. NEVER output markdown image syntax like ![alt](url). NEVER invent URLs ending in .jpg, .jpeg, .png, .webp or any image extension. You do not have access to product images';
          $parts[] = "PRODUCT CATALOG (use this data to answer questions about products, prices, availability; {$link_instruction}{$image_instruction}):\n" . $woo_context;
        }

        $image_rules = static::get_image_rules($include_woo_images);
        if (!empty($image_rules)) {
          if ($enable_recommendations) {
            $image_rules .= "\n- IMPORTANT OVERRIDE: When the GUIDED PRODUCT RECOMMENDATION PROTOCOL is active, do NOT include [PRODUCT_IMAGES:ID] tags during Steps 1 or 2. Only include them in Step 3 after the customer has seen the text-only list and explicitly asked for photos.";
          }
          $parts[] = $image_rules;
        }

        self::log('build_enriched_system_prompt — added WooCommerce products (variations=' . ($include_variations ? 'yes' : 'no') . ', add_to_cart_links=' . ($add_to_cart_links ? 'yes' : 'no') . ', images=' . ($include_woo_images ? 'yes' : 'no') . ', categories=' . ($include_categories ? 'yes' : 'no') . ', tags=' . ($include_tags ? 'yes' : 'no') . ', recommendations=' . ($enable_recommendations ? 'YES' : 'NO') . ')');

        // 7b. Product recommendation protocol
        if ($enable_recommendations) {
          $taxonomy_summary = self::get_woo_taxonomy_summary($include_categories, $include_tags, $categories_filter);
          $recommendation_protocol = self::build_recommendation_protocol(
            $include_categories, $include_tags, $include_woo_images, $taxonomy_summary
          );
          $parts[] = $recommendation_protocol;
          self::log('build_enriched_system_prompt — added recommendation protocol'
            . ' (categories=' . count($taxonomy_summary['categories'])
            . ', tags=' . count($taxonomy_summary['tags'])
            . ', images=' . ($include_woo_images ? 'YES' : 'NO') . ')');
        }
      }
    }

    // 8. Blog posts
    $include_posts = !empty($node_config[$prefix . 'include_posts']);
    if ($include_posts) {
      $posts_context = self::get_posts_context();
      if (!empty($posts_context)) {
        $parts[] = "BLOG ARTICLES (use this content to answer user questions; share the URL when relevant):\n" . $posts_context;
        self::log('build_enriched_system_prompt — added blog posts');
      }
    }

    // 9. Pages
    $include_pages = !empty($node_config[$prefix . 'include_pages']);
    if ($include_pages) {
      $pages_context = self::get_pages_context();
      if (!empty($pages_context)) {
        $parts[] = "WEBSITE PAGES (use this content to answer user questions; share the URL when relevant):\n" . $pages_context;
        self::log('build_enriched_system_prompt — added pages');
      }
    }

    // 10. Mandatory style rules
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

  /* ================================================================
   * WOOCOMMERCE PRODUCTS
   * ================================================================ */

  private static function get_woo_products_context($include_variations = false, $add_to_cart_links = false, $include_images = false, $include_categories = false, $include_tags = false, $categories_filter = []) {
    if (!class_exists('WooCommerce') && !function_exists('wc_get_products')) {
      return '';
    }

    $query_args = [
      'status'  => 'publish',
      'limit'   => 50,
      'orderby' => 'total_sales',
      'order'   => 'DESC',
    ];

    if (!empty($categories_filter)) {
      $query_args['category'] = array_map(function ($term_id) {
        $term = get_term($term_id, 'product_cat');
        return ($term && !is_wp_error($term)) ? $term->slug : '';
      }, $categories_filter);
      $query_args['category'] = array_filter($query_args['category']);
    }

    $products = wc_get_products($query_args);

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

      $has_variations = $include_variations && $product->is_type('variable');

      $block  = '---' . "\n";
      $block .= 'Product: ' . $product->get_name() . ($has_variations ? '  [HAS VARIATIONS — ask customer to choose]' : '') . "\n";
      $block .= 'ID: ' . $product_id . "\n";

      // Price — handle variable products with price ranges
      if ($product->is_type('variable')) {
        $min_price = $product->get_variation_price('min', true);
        $max_price = $product->get_variation_price('max', true);
        if ($min_price) {
          if ($min_price !== $max_price) {
            $block .= 'Min. price: ' . html_entity_decode(strip_tags(wc_price($min_price)), ENT_QUOTES, 'UTF-8') . "\n";
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

      // Categories
      if ($include_categories) {
        $cat_terms = get_the_terms($product_id, 'product_cat');
        if (!empty($cat_terms) && !is_wp_error($cat_terms)) {
          $block .= 'Categories: ' . implode(', ', wp_list_pluck($cat_terms, 'name')) . "\n";
        }
      }

      // Tags
      if ($include_tags) {
        $tag_terms = get_the_terms($product_id, 'product_tag');
        if (!empty($tag_terms) && !is_wp_error($tag_terms)) {
          $tag_names = wp_list_pluck($tag_terms, 'name');
          $block .= 'Tags: ' . implode(', ', $tag_names) . "\n";
        }
      }

      // Variations
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

  /* ================================================================
   * WOOCOMMERCE TAXONOMY SUMMARY (for recommendation protocol)
   * ================================================================ */

  /**
   * Get unique category and tag names from the top-selling products.
   *
   * Uses the same query as get_woo_products_context() — WP object cache
   * prevents a real duplicate DB hit.
   */
  private static function get_woo_taxonomy_summary($include_categories = false, $include_tags = false, $categories_filter = []) {
    $result = ['categories' => [], 'tags' => []];

    if (!$include_categories && !$include_tags) {
      return $result;
    }

    if (!class_exists('WooCommerce') && !function_exists('wc_get_products')) {
      return $result;
    }

    $query_args = [
      'status'  => 'publish',
      'limit'   => 50,
      'orderby' => 'total_sales',
      'order'   => 'DESC',
    ];

    if (!empty($categories_filter)) {
      $query_args['category'] = array_map(function ($term_id) {
        $term = get_term($term_id, 'product_cat');
        return ($term && !is_wp_error($term)) ? $term->slug : '';
      }, $categories_filter);
      $query_args['category'] = array_filter($query_args['category']);
    }

    $products = wc_get_products($query_args);

    if (empty($products)) {
      return $result;
    }

    $cat_names = [];
    $tag_names = [];

    foreach ($products as $product) {
      if ($include_categories) {
        $cats = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
        if (!is_wp_error($cats)) {
          foreach ($cats as $name) {
            $cat_names[$name] = true;
          }
        }
      }
      if ($include_tags) {
        $tags = wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names']);
        if (!is_wp_error($tags)) {
          foreach ($tags as $name) {
            $tag_names[$name] = true;
          }
        }
      }
    }

    $result['categories'] = array_keys($cat_names);
    sort($result['categories']);
    $result['tags'] = array_keys($tag_names);
    sort($result['tags']);

    return $result;
  }

  /* ================================================================
   * GUIDED PRODUCT RECOMMENDATION PROTOCOL
   * ================================================================ */

  /**
   * Build the multi-step recommendation protocol prompt block.
   */
  private static function build_recommendation_protocol($include_categories, $include_tags, $include_images, $taxonomy_summary) {
    $lines = [];
    $lines[] = '=== GUIDED PRODUCT RECOMMENDATION PROTOCOL ===';
    $lines[] = 'IMPORTANT: This protocol OVERRIDES any other product-link or image instructions when it applies.';
    $lines[] = 'When the customer asks a GENERIC product question, follow these steps IN ORDER. Do NOT skip steps.';
    $lines[] = 'A question is GENERIC when it refers to a product category, type, occasion, or general intent — NOT a specific product by its exact name.';
    $lines[] = 'Examples of GENERIC questions: "what do you have?", "I\'m looking for a gift", "show me seasonal flowers", "I want a bouquet", "send me photos of your products", "what flowers do you recommend?".';
    $lines[] = 'Even if the customer says "send photos" or "show me images" in their first message, you MUST follow the steps below first.';
    $lines[] = '';

    // Step 1 — Qualifying questions
    $lines[] = 'STEP 1 — QUALIFYING QUESTIONS (ask one question per message, in a natural conversational way):';
    $lines[] = 'a) BUDGET: Always ask the customer about their approximate budget or price range.';

    if ($include_categories && !empty($taxonomy_summary['categories'])) {
      $lines[] = 'b) CATEGORY / TYPE: Ask what type of product they are looking for. Available categories: ' . implode(', ', $taxonomy_summary['categories']) . '.';
    }

    if ($include_tags && !empty($taxonomy_summary['tags'])) {
      $step_letter = ($include_categories && !empty($taxonomy_summary['categories'])) ? 'c' : 'b';
      $lines[] = $step_letter . ') FEATURES / STYLE: Ask about preferred features or style. Available tags: ' . implode(', ', $taxonomy_summary['tags']) . '.';
    }

    $lines[] = 'ADAPTIVE RULE: If the customer already provided some of this information in their message, do NOT re-ask it. Skip directly to the next unanswered question, or proceed to Step 2 if you have enough information to make a good recommendation.';
    $lines[] = '';

    // Step 2 — Filtered product list
    $lines[] = 'STEP 2 — FILTERED PRODUCT LIST (text only, NO links, NO images):';
    $lines[] = '- Present a maximum of 5 products that match the customer\'s criteria.';
    $lines[] = '- For each product show ONLY: Name + Price + one short line explaining why it matches.';
    $lines[] = '- FORBIDDEN in this step: product URLs, product links, buy links, [PRODUCT_IMAGES:ID] tags, or any image reference.';
    $lines[] = '- Then ask the customer which ones interest them or if they want to see more options.';
    $lines[] = '';

    // Step 3 — conditional
    if ($include_images) {
      $lines[] = 'STEP 3 — OFFER PHOTOS (only after Step 2 is complete):';
      $lines[] = '- After showing the filtered list in Step 2, ask the customer if they would like to see photos of any of the recommended products.';
      $lines[] = '- Only include [PRODUCT_IMAGES:ID] tags AFTER the customer has seen the text-only list AND explicitly accepts or asks to see photos in a SUBSEQUENT message.';
      $lines[] = '- When sending photos, also include the product URL/link.';
      $lines[] = '- If the customer asked for photos in their INITIAL message, acknowledge it but still complete Steps 1-2 first, then offer photos.';
    } else {
      $lines[] = 'STEP 3 — NEXT STEPS:';
      $lines[] = '- After showing the filtered list, ask the customer if any product interests them.';
      $lines[] = '- When the customer picks a product, provide its URL/link.';
    }
    $lines[] = '';

    // Exception
    $lines[] = 'EXCEPTION: If the customer asks about a SPECIFIC product using its EXACT NAME from the catalog (e.g. "how much is JARRON CUERDA CON FLORES?", "tell me about CESTON CON FLORES DE TEMPORADA"), answer directly with the product information and link. Do NOT start the guided flow. Asking for a product CATEGORY (e.g. "seasonal flowers", "bouquets") is NOT a specific product name — that is a GENERIC query and must follow the protocol.';
    $lines[] = '';

    // Rules
    $lines[] = 'RULES:';
    $lines[] = '- NEVER include [PRODUCT_IMAGES:ID] tags during Steps 1 or 2.';
    $lines[] = '- Recommend a maximum of 5-6 products per interaction.';
    $lines[] = '- Ask qualifying questions one at a time, not all at once.';
    $lines[] = '- NEVER ask for the postal code during the recommendation flow (Steps 1-3). Postal code is ONLY relevant for shipping/delivery AFTER the customer has selected a product.';
    $lines[] = '=== END RECOMMENDATION PROTOCOL ===';

    return implode("\n", $lines);
  }

  /* ================================================================
   * WOOCOMMERCE SHIPPING ZONES
   * ================================================================ */

  /**
   * Convert a WooCommerce shipping-zone postcode pattern into a
   * human/AI-readable form.
   *
   * WooCommerce supports three pattern types inside `$location->code`:
   *   - Exact match:  "28050"
   *   - Wildcard:     "280*"       (any postcode starting with "280")
   *   - Range:        "28000...28099"
   *
   * LLMs often fail to interpret "280*" as "28000-28099", so we expand
   * wildcards into an explicit numeric range whenever the prefix is
   * purely numeric (covers ES, FR, DE, IT, PT, US… — all 5-digit or
   * similar numeric postcodes). For non-numeric prefixes (e.g. UK
   * "SW1*") the original pattern is kept.
   */
  private static function format_shipping_postcode($loc_code) {
    $loc_code = trim((string) $loc_code);
    if ($loc_code === '') {
      return '';
    }

    // Range syntax: "28000...28099" → "28000-28099".
    if (strpos($loc_code, '...') !== false) {
      $parts = explode('...', $loc_code, 2);
      $start = trim($parts[0]);
      $end   = trim($parts[1] ?? '');
      if ($start !== '' && $end !== '') {
        return $start . '-' . $end;
      }
      return $loc_code;
    }

    // Wildcard syntax: "280*" → "28000-28099" (expand to a 5-digit range).
    if (strpos($loc_code, '*') !== false) {
      $prefix = rtrim($loc_code, '*');
      if ($prefix === '') {
        return $loc_code;
      }
      if (ctype_digit($prefix)) {
        $length = 5; // Standard length for most numeric postcodes.
        if (strlen($prefix) >= $length) {
          return $prefix;
        }
        $pad   = $length - strlen($prefix);
        $start = $prefix . str_repeat('0', $pad);
        $end   = $prefix . str_repeat('9', $pad);
        return $start . '-' . $end;
      }
      // Non-numeric prefix (e.g. UK "SW1*"): keep pattern but make it explicit.
      return $prefix . '… (any postcode starting with ' . $prefix . ')';
    }

    return $loc_code;
  }

  private static function get_woo_shipping_zones_context() {
    if (!class_exists('WooCommerce') || !class_exists('WC_Shipping_Zones')) {
      return '';
    }

    $zones  = \WC_Shipping_Zones::get_zones();
    $blocks = [];

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
      $methods   = $zone_obj->get_shipping_methods(true);

      if (empty($methods)) {
        continue;
      }

      $region_names = [];
      $postcodes    = [];
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
          $formatted_pc = self::format_shipping_postcode($loc_code);
          if ($formatted_pc !== '') {
            $postcodes[] = $formatted_pc;
          }
        } elseif ($loc_type === 'continent') {
          $continents = WC()->countries->get_continents();
          $region_names[] = $continents[$loc_code]['name'] ?? $loc_code;
        }
      }

      if (!empty($postcodes)) {
        $postcodes      = array_values(array_unique($postcodes));
        $region_names[] = __('Postal codes', 'pn-customers-manager') . ': ' . implode(', ', $postcodes);
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

  /* ================================================================
   * POSTAL-CODE → SHIPPING-ZONE PRE-MATCHING
   * ================================================================
   *
   * LLMs are unreliable at matching postal codes against numeric ranges
   * or wildcards ("280*", "28000...28099"), and often pick the wrong zone
   * when two zones share the same region. To avoid that, we detect any
   * postal code in the latest user messages, match it against WooCommerce
   * shipping zones in PHP, and inject a pre-computed hint alongside the
   * last user message before sending it to OpenAI.
   *
   * The hint is ephemeral: it is NOT persisted to the conversation
   * history, only added to the payload sent to the model.
   */

  /**
   * If a postal code appears in recent user messages, append a
   * pre-computed shipping-zone hint to the last user message.
   *
   * @param array $openai_messages Messages about to be sent to OpenAI.
   * @return array                 Possibly-modified messages array.
   */
  private static function maybe_inject_shipping_hint($openai_messages) {
    if (!class_exists('WC_Shipping_Zones')) {
      return $openai_messages;
    }

    // Only inject the hint when the LAST user message contains a postal code.
    // This prevents re-injecting shipping context when the customer is
    // selecting a shipping option (e.g. "El envío") rather than asking a
    // new shipping question. Using find_recent_user_postal_code (10 msgs back)
    // caused GPT to loop on shipping info instead of advancing to the order.
    $postal_code = self::find_postal_code_in_last_user_message($openai_messages);
    if (!$postal_code) {
      return $openai_messages;
    }

    $hint = self::build_shipping_hint_for_postcode($postal_code);
    if (!$hint) {
      return $openai_messages;
    }

    // Append the hint to the last user message so the model sees it in
    // context of the current question.
    for ($i = count($openai_messages) - 1; $i >= 0; $i--) {
      if (($openai_messages[$i]['role'] ?? '') === 'user') {
        $openai_messages[$i]['content'] = ($openai_messages[$i]['content'] ?? '') . "\n\n" . $hint;
        break;
      }
    }

    return $openai_messages;
  }

  /**
   * Return the most recent 5-digit postal code written by the user
   * within the last ~10 messages, or null if none is found.
   */
  private static function find_recent_user_postal_code($openai_messages) {
    $recent = array_slice($openai_messages, -10);
    for ($i = count($recent) - 1; $i >= 0; $i--) {
      $msg = $recent[$i];
      if (($msg['role'] ?? '') !== 'user') {
        continue;
      }
      $content = is_string($msg['content'] ?? null) ? $msg['content'] : '';
      if (preg_match('/\b(\d{5})\b/', $content, $m)) {
        return $m[1];
      }
    }
    return null;
  }

  /**
   * Walk WooCommerce shipping zones in priority order and build a plain-
   * text hint describing which zone applies to the given postal code and
   * which shipping methods are available there.
   *
   * Returns null if no zone can be matched with confidence.
   */
  private static function build_shipping_hint_for_postcode($postal_code) {
    $zones = \WC_Shipping_Zones::get_zones();

    // Append the "rest of the world" zone last so explicit zones win.
    $rest_zone = new \WC_Shipping_Zone(0);
    $zones[] = [
      'id'               => 0,
      'zone_name'        => $rest_zone->get_zone_name(),
      'zone_locations'   => $rest_zone->get_zone_locations(),
      'shipping_methods' => $rest_zone->get_shipping_methods(true),
    ];

    $fallback_zone = null; // First zone without postcode filter, used as fallback.

    foreach ($zones as $zone_data) {
      $zone_id  = $zone_data['id'] ?? 0;
      $zone_obj = ($zone_id === 0) ? $rest_zone : new \WC_Shipping_Zone($zone_id);

      $locations = $zone_obj->get_zone_locations();
      $methods   = $zone_obj->get_shipping_methods(true);

      if (empty($methods)) {
        continue;
      }

      $has_postcode_filter = false;
      $postcode_matches    = false;
      foreach ($locations as $location) {
        if ($location->type !== 'postcode') {
          continue;
        }
        $has_postcode_filter = true;
        if (self::postcode_matches_pattern($postal_code, $location->code)) {
          $postcode_matches = true;
          break;
        }
      }

      if ($has_postcode_filter) {
        if ($postcode_matches) {
          return self::format_shipping_hint($postal_code, $zone_obj, $methods);
        }
        // Zone restricts by postcode but this one is not included → skip.
        continue;
      }

      // Zone has no postcode filter. Remember as a fallback but keep looking
      // for a more specific postcode-restricted zone.
      if ($fallback_zone === null) {
        $fallback_zone = [$zone_obj, $methods];
      }
    }

    if ($fallback_zone !== null) {
      return self::format_shipping_hint($postal_code, $fallback_zone[0], $fallback_zone[1]);
    }

    return null;
  }

  /**
   * Check whether $postcode matches a single WooCommerce postcode
   * pattern (exact, wildcard "280*", or range "28000...28099").
   */
  private static function postcode_matches_pattern($postcode, $pattern) {
    $postcode = trim((string) $postcode);
    $pattern  = trim((string) $pattern);
    if ($postcode === '' || $pattern === '') {
      return false;
    }

    if ($postcode === $pattern) {
      return true;
    }

    // Range: "28000...28099"
    if (strpos($pattern, '...') !== false) {
      $parts = explode('...', $pattern, 2);
      $start = trim($parts[0]);
      $end   = trim($parts[1] ?? '');
      if ($start === '' || $end === '') {
        return false;
      }
      // Compare as strings (works for equal-length numeric codes) and
      // as integers when possible to be tolerant of leading zeros.
      if (ctype_digit($postcode) && ctype_digit($start) && ctype_digit($end)) {
        $pc_i = (int) $postcode;
        return $pc_i >= (int) $start && $pc_i <= (int) $end;
      }
      return strcmp($postcode, $start) >= 0 && strcmp($postcode, $end) <= 0;
    }

    // Wildcard: "280*"
    if (strpos($pattern, '*') !== false) {
      $prefix = rtrim($pattern, '*');
      if ($prefix === '') {
        return true;
      }
      return strncmp($postcode, $prefix, strlen($prefix)) === 0;
    }

    return false;
  }

  /**
   * Format a short, LLM-friendly shipping hint string for the matched
   * zone and its methods.
   */
  private static function format_shipping_hint($postal_code, $zone_obj, $methods) {
    $zone_name = $zone_obj->get_zone_name();
    $method_lines = [];
    foreach ($methods as $method) {
      $method_lines[] = '  - ' . self::describe_shipping_method($method);
    }

    $hint  = "[PRE-CALCULATED SHIPPING INFO — USE THIS, DO NOT ASK FOR THE POSTAL CODE AGAIN]\n";
    $hint .= 'Detected customer postal code: ' . $postal_code . "\n";
    $hint .= 'Matching shipping zone: ' . $zone_name . "\n";
    $hint .= "Available shipping methods for this zone:\n";
    $hint .= implode("\n", $method_lines) . "\n";
    $hint .= 'Answer the customer directly with the cheapest (or most convenient) option from the list above. Do NOT quote any other zone.';

    return $hint;
  }

  /**
   * Describe a single WC_Shipping_Method in a compact human-readable way.
   * Mirrors (in simplified form) the rendering done by
   * get_woo_shipping_zones_context().
   */
  private static function describe_shipping_method($method) {
    $title    = $method->get_title();
    $type     = $method->id;
    $settings = $method->instance_settings;

    if ($type === 'free_shipping') {
      return $title . ': FREE';
    }

    if ($type === 'local_pickup') {
      $cost = $settings['cost'] ?? '';
      return $title . ($cost !== '' && (float) $cost > 0
        ? ': ' . html_entity_decode(strip_tags(wc_price($cost)), ENT_QUOTES, 'UTF-8')
        : ': FREE');
    }

    if ($type === 'flat_rate') {
      $cost = $settings['cost'] ?? '';
      if ($cost !== '') {
        return $title . ': ' . html_entity_decode(strip_tags(wc_price($cost)), ENT_QUOTES, 'UTF-8');
      }
      return $title;
    }

    $cost = $settings['cost'] ?? '';
    if ($cost !== '') {
      return $title . ': ' . html_entity_decode(strip_tags(wc_price($cost)), ENT_QUOTES, 'UTF-8');
    }
    return $title;
  }

  /* ================================================================
   * DIRECT WOOCOMMERCE SHIPPING RESPONSE
   * ----------------------------------------------------------------
   * When the "Use WooCommerce shipping zones" checkbox is enabled
   * and the customer is asking a shipping question with a postal
   * code, skip the AI model entirely and answer with a deterministic
   * response built from WooCommerce's own shipping functions.
   * ================================================================ */

  /**
   * Try to build a complete, ready-to-send shipping response directly
   * from WooCommerce, without calling OpenAI.
   *
   * @param array $messages    Full conversation messages (role + content).
   * @param array $node_config Funnel node config.
   * @return string|null       Response text, or null to fall back to the model.
   */
  private static function try_build_direct_shipping_response($messages, $node_config) {
    if (!class_exists('WooCommerce') || !class_exists('WC_Shipping_Zones')) {
      return null;
    }

    $prefix = static::platform_prefix();

    // Require the "Use WooCommerce shipping zones" checkbox to be ON.
    $raw_flag = $node_config[$prefix . 'wc_shipping_zones'] ?? null;
    $enabled  = ($raw_flag === true || $raw_flag === 'on' || $raw_flag === '1' || $raw_flag === 1);
    if (!$enabled) {
      return null;
    }

    // Only consider a postal code that appears in the LAST user message.
    // This prevents reusing a stale postcode from an earlier shipping
    // flow when the customer is now asking a brand-new question.
    $postal_code = self::find_postal_code_in_last_user_message($messages);
    if (!$postal_code) {
      return null;
    }

    // The flow must actually be about shipping. Accepts either an explicit
    // shipping keyword in the last user message or a bare postcode answer
    // right after the assistant has asked for it.
    if (!self::conversation_has_shipping_intent($messages)) {
      return null;
    }

    // Build a minimal WC package so WC can compute the rates exactly like
    // it would at checkout.
    $base_country = '';
    if (function_exists('WC')) {
      $wc = WC();
      if (isset($wc->countries) && is_object($wc->countries) && method_exists($wc->countries, 'get_base_country')) {
        $base_country = (string) $wc->countries->get_base_country();
      }
    }
    if ($base_country === '') {
      $base_country = 'ES';
    }

    $package = [
      'contents'        => [],
      'contents_cost'   => 0,
      'applied_coupons' => [],
      'user'            => ['ID' => 0],
      'destination'     => [
        'country'   => $base_country,
        'state'     => '',
        'postcode'  => $postal_code,
        'city'      => '',
        'address'   => '',
        'address_2' => '',
      ],
    ];

    // Primary: ask WC which zone applies to this package.
    try {
      $zone = \WC_Shipping_Zones::get_zone_matching_package($package);
    } catch (\Throwable $e) {
      self::log('try_build_direct_shipping_response — WC zone match failed: ' . $e->getMessage());
      return null;
    }
    if (!$zone) {
      return null;
    }

    $rates = self::get_wc_rates_for_zone($zone, $package);

    // Fallback: if the zone WC picked has no methods/rates (happens when
    // a broad zone like "España" is ordered above a specific one like
    // "Madrid" and has no methods enabled), search the other zones whose
    // postcode rules also match the customer's postcode and pick the
    // first one that actually returns rates.
    $used_fallback_zone = false;
    if (empty($rates)) {
      $fallback = self::find_fallback_zone_with_rates($package, (int) $zone->get_id());
      if ($fallback !== null) {
        $zone  = $fallback['zone'];
        $rates = $fallback['rates'];
        $used_fallback_zone = true;
      }
    }

    $lang = self::detect_conversation_language($messages);

    $response = self::render_direct_shipping_response_from_rates($postal_code, $zone, $rates, $lang);

    self::log(sprintf(
      'try_build_direct_shipping_response — postcode=%s zone="%s" rates=%d lang=%s fallback=%s',
      $postal_code,
      $zone->get_zone_name(),
      is_array($rates) ? count($rates) : 0,
      $lang,
      $used_fallback_zone ? 'yes' : 'no'
    ));

    return $response;
  }

  /**
   * Return the 5-digit postal code found in the very last user message,
   * or null if the last user message does not contain one. We intentionally
   * do NOT look further back so that a new question without a postcode
   * does not inherit one from an earlier flow.
   */
  private static function find_postal_code_in_last_user_message($messages) {
    for ($i = count($messages) - 1; $i >= 0; $i--) {
      $msg = $messages[$i];
      if (($msg['role'] ?? '') !== 'user') {
        continue;
      }
      $content = is_string($msg['content'] ?? null) ? $msg['content'] : '';
      if (preg_match('/\b(\d{5})\b/', $content, $m)) {
        return $m[1];
      }
      // Stop at the first user message we see (the last one chronologically).
      return null;
    }
    return null;
  }

  /**
   * True when:
   *  - The last user message mentions shipping/delivery keywords, OR
   *  - The last assistant message explicitly asked for a postal code
   *    (so a bare "28055" response from the customer is treated as a
   *    shipping-flow answer).
   */
  private static function conversation_has_shipping_intent($messages) {
    $last_user       = null;
    $last_assistant  = null;
    for ($i = count($messages) - 1; $i >= 0; $i--) {
      $role = $messages[$i]['role'] ?? '';
      $text = is_string($messages[$i]['content'] ?? null) ? $messages[$i]['content'] : '';
      if ($last_user === null && $role === 'user') {
        $last_user = $text;
      } elseif ($last_assistant === null && $role === 'assistant') {
        $last_assistant = $text;
      }
      if ($last_user !== null && $last_assistant !== null) {
        break;
      }
    }

    $shipping_keywords = [
      // Spanish
      'envío', 'envio', 'envíos', 'envios', 'enviar', 'enviais', 'enviáis',
      'entrega', 'entregar', 'entregas', 'reparto', 'repartir', 'repartís', 'repartis',
      'mandar', 'mandáis', 'mandais', 'mandan',
      'hacéis envíos', 'haceis envios',
      // English
      'shipping', 'ship to', 'ship ', 'shipment',
      'delivery', 'deliver', 'deliveries',
      'send to', 'dispatch',
    ];

    $contains_any = function ($text, $needles) {
      $text = mb_strtolower((string) $text);
      if ($text === '') {
        return false;
      }
      $padded = ' ' . $text . ' ';
      foreach ($needles as $needle) {
        if (mb_strpos($padded, $needle) !== false) {
          return true;
        }
      }
      return false;
    };

    // Direct: the current user message is explicitly about shipping.
    if ($contains_any($last_user, $shipping_keywords)) {
      return true;
    }

    // Indirect: the assistant just asked the customer for a postal code,
    // so any numeric-only / brief follow-up should be interpreted as a
    // shipping answer.
    if ($last_assistant !== null) {
      $cp_question_markers = [
        'código postal', 'codigo postal', 'c. p.', 'c.p.',
        'postal code', 'postcode', 'zip code',
      ];
      if ($contains_any($last_assistant, $cp_question_markers)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Run WC's proper rate calculation for a given zone and package.
   * Returns an associative array of rate_id => WC_Shipping_Rate.
   *
   * This mirrors what WC_Shipping::calculate_shipping_for_package() does
   * internally, so the result matches the rates the customer would see
   * at checkout for that zone.
   */
  private static function get_wc_rates_for_zone($zone, $package) {
    $rates = [];
    if (!$zone || !method_exists($zone, 'get_shipping_methods')) {
      return $rates;
    }

    $methods = $zone->get_shipping_methods(true, 'values');
    if (!is_array($methods) && !($methods instanceof \Traversable)) {
      $methods = [];
    }

    foreach ($methods as $method) {
      if (!is_object($method)) {
        continue;
      }

      $method_rates = null;

      // Primary: WC's own rate resolver (handles caching and context).
      if (method_exists($method, 'get_rates_for_package')) {
        try {
          $method_rates = @$method->get_rates_for_package($package);
        } catch (\Throwable $e) {
          $method_rates = null;
        }
      }

      // Fallback: call calculate_shipping directly, then pick up $method->rates.
      if (empty($method_rates) && method_exists($method, 'calculate_shipping')) {
        $method->rates = [];
        try {
          @$method->calculate_shipping($package);
          $method_rates = $method->rates;
        } catch (\Throwable $e) {
          $method_rates = [];
        }
      }

      if (!empty($method_rates) && is_array($method_rates)) {
        foreach ($method_rates as $rate) {
          if (!is_object($rate)) {
            continue;
          }
          $rate_id = method_exists($rate, 'get_id') ? $rate->get_id() : spl_object_hash($rate);
          $rates[$rate_id] = $rate;
        }
      }
    }

    return $rates;
  }

  /**
   * Search all configured zones (plus the rest-of-world fallback) for a
   * zone whose postcode rules match the package's postcode AND which
   * actually returns rates. This is only used when the primary zone
   * picked by WC has no rates, to rescue misordered zone configurations.
   *
   * @param array $package
   * @param int   $exclude_zone_id Primary zone that already failed.
   * @return array{zone:\WC_Shipping_Zone,rates:array}|null
   */
  private static function find_fallback_zone_with_rates($package, $exclude_zone_id) {
    $postal_code = $package['destination']['postcode'] ?? '';
    if ($postal_code === '') {
      return null;
    }

    $zones_index = \WC_Shipping_Zones::get_zones();

    // Candidate list: all configured zones + rest-of-world zone 0.
    $candidates = [];
    foreach ($zones_index as $zone_data) {
      $zone_id = isset($zone_data['id']) ? (int) $zone_data['id'] : 0;
      $candidates[$zone_id] = new \WC_Shipping_Zone($zone_id);
    }
    if (!isset($candidates[0])) {
      $candidates[0] = new \WC_Shipping_Zone(0);
    }

    // Prefer zones whose postcode rules explicitly include the customer's
    // postcode; only fall back to zones without postcode rules.
    $strict_matches = [];
    $loose_matches  = [];

    foreach ($candidates as $zone_id => $zone) {
      if ((int) $zone_id === $exclude_zone_id) {
        continue;
      }

      $methods = $zone->get_shipping_methods(true);
      if (empty($methods)) {
        continue;
      }

      $locations           = $zone->get_zone_locations();
      $has_postcode_filter = false;
      $postcode_matches    = false;
      foreach ($locations as $location) {
        if ($location->type !== 'postcode') {
          continue;
        }
        $has_postcode_filter = true;
        if (self::postcode_matches_pattern($postal_code, $location->code)) {
          $postcode_matches = true;
          break;
        }
      }

      if ($has_postcode_filter) {
        if ($postcode_matches) {
          $strict_matches[] = $zone;
        }
        // Zone restricts by postcode and this one is not included → skip.
        continue;
      }

      // Zone has no postcode filter → use only as a loose fallback.
      $loose_matches[] = $zone;
    }

    foreach (array_merge($strict_matches, $loose_matches) as $zone) {
      $rates = self::get_wc_rates_for_zone($zone, $package);
      if (!empty($rates)) {
        return ['zone' => $zone, 'rates' => $rates];
      }
    }

    return null;
  }

  /**
   * Detect whether the conversation is in English; default to Spanish.
   *
   * @return string 'en' or 'es'
   */

  /* ================================================================
   * TIME-GAP MARKERS
   * ----------------------------------------------------------------
   * When a significant time gap (>1 hour) exists between consecutive
   * messages, inject a system message so the model does not blindly
   * continue the previous conversation topic.
   * ================================================================ */

  /**
   * Build the OpenAI messages array from the internal $messages list,
   * injecting time-gap markers when there is a pause of more than 1 hour.
   *
   * @param array  $messages      Internal messages (role, content, timestamp).
   * @param string $system_prompt System prompt to prepend (may be empty).
   * @return array OpenAI-compatible messages array.
   */
  protected static function build_openai_messages_with_gap_markers($messages, $system_prompt = '') {
    $openai_messages = [];

    if (!empty($system_prompt)) {
      $openai_messages[] = [
        'role'    => 'system',
        'content' => $system_prompt,
      ];
    }

    $gap_threshold = 3600; // 1 hour in seconds
    $prev_timestamp = null;

    foreach ($messages as $msg) {
      $curr_timestamp = !empty($msg['timestamp']) ? strtotime($msg['timestamp']) : null;

      if ($prev_timestamp && $curr_timestamp && ($curr_timestamp - $prev_timestamp) > $gap_threshold) {
        $hours = round(($curr_timestamp - $prev_timestamp) / 3600, 1);
        $openai_messages[] = [
          'role'    => 'system',
          'content' => '[TIME GAP: ' . $hours . ' hours have passed since the last message. '
            . 'The customer may be starting a new topic. Do NOT proactively continue the previous conversation thread '
            . '(shipping, products, postal codes, orders, etc.). Respond naturally to what the customer says next. '
            . 'If they send a greeting, greet them back and ask how you can help — do NOT reference previous topics.]',
        ];
      }

      $prev_timestamp = $curr_timestamp;

      $content = $msg['content'];

      // Strip "[Se enviaron imágenes de: …]" markers from stored assistant
      // messages so the model does not learn to hallucinate them instead of
      // using the real [PRODUCT_IMAGES:ID] tags.
      if ($msg['role'] === 'assistant') {
        $content = preg_replace('/\s*\[Se\s+envi(?:[óo]|aron)\s+im[áa]gen(?:es)?\s*(?:de)?\s*:\s*[^\]]*\]/iu', '', $content);
        $content = trim($content);
      }

      $openai_messages[] = [
        'role'    => $msg['role'],
        'content' => $content,
      ];
    }

    return $openai_messages;
  }

  private static function detect_conversation_language($messages) {
    $recent = array_slice($messages, -4);
    $text   = '';
    foreach ($recent as $msg) {
      if (($msg['role'] ?? '') !== 'user') {
        continue;
      }
      $text .= ' ' . (is_string($msg['content'] ?? null) ? $msg['content'] : '');
    }
    $text = mb_strtolower($text);
    if ($text === '') {
      return 'es';
    }

    $padded = ' ' . $text . ' ';

    $es_words = [
      ' el ', ' la ', ' los ', ' las ', ' un ', ' una ', ' de ', ' en ',
      ' y ', ' es ', ' hola', ' cuánto', ' cuanto', ' qué ', ' que ',
      ' para ', ' por ', ' con ', ' sí', ' gracias', ' hacéis', ' haceis',
      'envío', 'envio', 'código', 'codigo', 'entrega',
    ];
    $en_words = [
      ' the ', ' a ', ' an ', ' of ', ' in ', ' and ', ' is ', ' hi ',
      ' hello', ' how ', ' much ', ' what ', ' for ', ' to ', ' with ',
      ' yes ', ' thanks', ' please', 'shipping', 'delivery', 'ship ',
      ' cost', ' deliver',
    ];

    $es_score = 0;
    foreach ($es_words as $w) {
      $es_score += substr_count($padded, $w);
    }
    $en_score = 0;
    foreach ($en_words as $w) {
      $en_score += substr_count($padded, $w);
    }

    return ($en_score > $es_score) ? 'en' : 'es';
  }

  /**
   * Build the final customer-facing shipping response from a set of
   * pre-calculated WC_Shipping_Rate objects.
   *
   * The rates have already been computed by WooCommerce via
   * get_wc_rates_for_zone() so this method only formats them.
   */
  private static function render_direct_shipping_response_from_rates($postal_code, $zone_obj, $rates, $lang = 'es') {
    $zone_name = (string) $zone_obj->get_zone_name();
    $zone_id   = (int) $zone_obj->get_id();
    $is_rest   = ($zone_id === 0);

    // No rates at all → we really cannot ship there.
    if (empty($rates)) {
      if ($lang === 'en') {
        return "I'm sorry, we don't currently ship to postal code {$postal_code}. "
          . "If you need another delivery option, let us know and we'll check what we can do.";
      }
      return "Lo sentimos, actualmente no realizamos envíos al código postal {$postal_code}. "
        . "Si necesitas otra opción de entrega, cuéntanoslo y vemos qué podemos hacer.";
    }

    // One line per rate, formatted with WC's own price helper and currency.
    $lines = [];
    foreach ($rates as $rate) {
      if (!is_object($rate)) {
        continue;
      }

      $label = method_exists($rate, 'get_label') ? (string) $rate->get_label() : '';
      if ($label === '' && method_exists($rate, 'get_method_id')) {
        $label = (string) $rate->get_method_id();
      }

      $cost = 0.0;
      if (method_exists($rate, 'get_cost')) {
        $cost = (float) $rate->get_cost();
      }
      if (method_exists($rate, 'get_shipping_tax')) {
        $cost += (float) $rate->get_shipping_tax();
      }

      if ($cost <= 0) {
        $lines[] = '- ' . $label . ': ' . ($lang === 'en' ? 'FREE' : 'GRATIS');
      } else {
        $priced = html_entity_decode(strip_tags(wc_price($cost)), ENT_QUOTES, 'UTF-8');
        $lines[] = '- ' . $label . ': ' . $priced;
      }
    }

    if (empty($lines)) {
      if ($lang === 'en') {
        return "I'm sorry, we don't currently ship to postal code {$postal_code}. "
          . "If you need another delivery option, let us know and we'll check what we can do.";
      }
      return "Lo sentimos, actualmente no realizamos envíos al código postal {$postal_code}. "
        . "Si necesitas otra opción de entrega, cuéntanoslo y vemos qué podemos hacer.";
    }

    if ($lang === 'en') {
      $header = "Yes! We ship to postal code {$postal_code}";
      if (!$is_rest && $zone_name !== '') {
        $header .= " (zone: {$zone_name})";
      }
      $header .= ". These are the available shipping options:\n";
      $footer  = "\n\nWould you like to go ahead with any of these options?";
      return $header . implode("\n", $lines) . $footer;
    }

    // Default: Spanish
    $header = "¡Sí! Realizamos envíos al código postal {$postal_code}";
    if (!$is_rest && $zone_name !== '') {
      $header .= " (zona: {$zone_name})";
    }
    $header .= ". Estas son las opciones de envío disponibles:\n";
    $footer  = "\n\n¿Te interesa alguna de estas opciones?";
    return $header . implode("\n", $lines) . $footer;
  }

  /* ================================================================
   * BLOG POSTS & PAGES CONTEXT
   * ================================================================ */

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

  /* ================================================================
   * CONVERSATION TEMPERATURE
   * ================================================================ */

  private static function get_conversation_temperature($conversation) {
    $prefix = static::platform_prefix();

    if ($conversation->funnel_id && $conversation->node_id) {
      $canvas_data = get_post_meta($conversation->funnel_id, 'pn_cm_funnel_canvas', true);
      if ($canvas_data) {
        $data = json_decode($canvas_data, true);
        if ($data && isset($data['nodes'])) {
          foreach ($data['nodes'] as $node) {
            if ($node['id'] === $conversation->node_id && isset($node['config'][$prefix . 'temperature'])) {
              return (float) $node['config'][$prefix . 'temperature'];
            }
          }
        }
      }
    }

    return (float) get_option('pn_customers_manager_whatsapp_temperature', 0.7);
  }

  /* ================================================================
   * OPENAI API
   * ================================================================ */

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

    $error_msg  = isset($body['error']['message']) ? $body['error']['message'] : 'unknown';
    $error_type = isset($body['error']['type']) ? $body['error']['type'] : 'unknown';
    self::log('call_openai — FAILED model=' . $model . ' http=' . $http_code
      . ' error_type=' . $error_type . ' error_msg=' . $error_msg);

    return false;
  }

  /* ================================================================
   * RESPONSE POST-PROCESSING
   * ================================================================ */

  private static function sanitize_system_prompt_leak($text) {
    $markers = [
      'CURRENT DATE AND TIME:',
      'FORMATTING RULES:',
      'MANDATORY RULE',
      'ORDER ACCEPTANCE PROTOCOL:',
      'ORDER POLICY:',
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

  private static function enforce_postal_code_rule($text, $messages, $require_postal, $conversation = null) {
    self::log('enforce_postal_code_rule — called, require_postal=' . ($require_postal ? 'YES' : 'NO'));

    $prefix = static::platform_prefix();

    // Safety net: if $require_postal is false, double-check by reading config directly
    if (!$require_postal && $conversation) {
      self::log('enforce_postal_code_rule — require_postal is NO, re-reading config as safety net...');
      $nc = self::get_node_config($conversation);
      if (empty($nc)) {
        $ai_node = self::find_ai_node();
        if ($ai_node && !empty($ai_node['config'])) {
          $nc = $ai_node['config'];
        }
      }
      if (!empty($nc[$prefix . 'require_postal_code'])) {
        $require_postal = true;
        self::log('enforce_postal_code_rule — SAFETY NET activated: found ' . $prefix . 'require_postal_code='
          . var_export($nc[$prefix . 'require_postal_code'], true) . ' → require_postal=YES');
      } else {
        self::log('enforce_postal_code_rule — safety net: ' . $prefix . 'require_postal_code not set or falsy'
          . ' config_keys=' . ($nc ? implode(',', array_keys($nc)) : 'EMPTY'));
      }
    }

    if (!$require_postal) {
      return $text;
    }

    // Skip enforcement when the AI is sending product images (recommendation protocol Step 3)
    if (preg_match('/\[PRODUCT_IMAGES:\d+\]/', $text)) {
      self::log('enforce_postal_code_rule — response contains [PRODUCT_IMAGES] tags (recommendation flow), skipping');
      return $text;
    }

    // Refined regex that avoids false positives
    // NOTE: bare "envío/envio" is NOT matched — it's too ambiguous (verb "I send" vs noun "shipping").
    // Only match compound phrases clearly about shipping costs/delivery.
    $shipping_keywords = '/(env[ií]o\s+(?:gratuito|gratis|express|urgente|est[áa]ndar|nacional|internacional|incluido|a\s+domicilio)|opciones?\s+de\s+env[ií]o|zona\s+de\s+env[ií]o|tarifas?\s+de\s+env[ií]o|precio\s+(?:del?\s+)?env[ií]o|coste\s+(?:del?\s+)?env[ií]o|gastos?\s+de\s+env[ií]o|entrega\s+a\s+domicilio|hacemos\s+env[ií]os|realizamos\s+env[ií]os|podemos\s+enviar|enviamos\s+a\b|entregamos\s+en|repartimos|shipping|delivery\s+cost|\d+[\.,]?\d*\s*€[^.]*env[ií]o|env[ií]o[^.]*\d+[\.,]?\d*\s*€)/iu';
    if (!preg_match($shipping_keywords, $text, $kw_match)) {
      self::log('enforce_postal_code_rule — no shipping keywords found in response, skipping');
      return $text;
    }
    self::log('enforce_postal_code_rule — shipping keyword matched: "' . $kw_match[0] . '"');

    // Check recent user messages for a postal code (Spanish: 5 digits starting with 0-5)
    // Walk backwards — detect stale CPs from new shipping inquiries
    $postal_found     = false;
    $shipping_inquiry = '/(?:'
      . 'enviar?\s+(?:a\s+|flores\s+a\s+)?(?:la\s+)?calle'
      . '|enviar?\s+a\s+(?!casa\b|domicilio\b)\w+'
      . '|entregar?\s+en\s+(?!casa\b|domicilio\b|mi\b)\w+'
      . '|envío\s+a\s+(?!casa\b|domicilio\b|mi\b)\w+'
      . '|env[ií](?:o|ar|áis|ais)\b.*(?:calle|pueblo|ciudad|zona|direcci[oó]n)'
      . '|(?:y\s+)?(?:a\s+)?(?:la\s+)?calle\s+\w+'
      . '|(?:y\s+)?al?\s+pueblo\s+(?:de\s+)?\w+'
      . '|pod(?:r[ií]a|éis|ríais)\s+enviar'
      . '|llega\s+a\b|lleg[aá]is\s+a\b'
      . ')/iu';
    $recent_messages = array_slice($messages, -12);

    for ($i = count($recent_messages) - 1; $i >= 0; $i--) {
      $msg = $recent_messages[$i];
      if ($msg['role'] !== 'user') {
        continue;
      }
      if (preg_match('/\b[0-5]\d{4}\b/', $msg['content'])) {
        $postal_found = true;
        self::log('enforce_postal_code_rule — postal code found in recent user message: "' . $msg['content'] . '"');
        break;
      }
      if (preg_match($shipping_inquiry, $msg['content'])) {
        self::log('enforce_postal_code_rule — new shipping inquiry found BEFORE any postal code: "' . $msg['content'] . '" — CP is stale');
        break;
      }
    }

    if ($postal_found) {
      return $text;
    }

    // No postal code — override response
    self::log('enforce_postal_code_rule — OVERRIDING response (no postal code yet). Original: ' . mb_substr($text, 0, 200));

    $first_sentence = '';
    if (preg_match('/^(.+?[.!?])\s/u', $text, $m)) {
      $candidate    = $m[1];
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

  private static function strip_hallucinated_image_urls($text) {
    $original = $text;
    $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $text);
    $text = preg_replace('/^\s*https?:\/\/\S+\.(?:jpg|jpeg|png|gif|webp|svg|bmp|tiff)\b\S*\s*$/mi', '', $text);
    $text = preg_replace('/:\s*https?:\/\/\S+\.(?:jpg|jpeg|png|gif|webp|svg|bmp|tiff)\b\S*/i', '.', $text);

    // Strip hallucinated image-delivery markers the model may copy from
    // conversation history (e.g. "[Se envió imagen de: Product]").
    $text = preg_replace('/\s*\[Se\s+envi[óo]\s+im[áa]gen(?:es)?\s*(?:de)?\s*:\s*[^\]]*\]/iu', '', $text);
    $text = preg_replace('/\s*\[Se\s+enviaron\s+im[áa]gen(?:es)?\s*(?:de)?\s*:\s*[^\]]*\]/iu', '', $text);

    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = trim($text);
    if ($text !== $original) {
      self::log('strip_hallucinated_image_urls — removed fabricated image URLs from AI response');
    }
    return $text;
  }

  /* ================================================================
   * ORDER DETECTION & NOTIFICATION
   * ================================================================ */

  /**
   * Build a compact shipping summary for the notification email from the
   * conversation messages. Returns null when we cannot confidently resolve
   * a postal code → zone → methods path (e.g. WooCommerce not available,
   * no postal code in the conversation, or no methods configured).
   *
   * Shape:
   *   [
   *     'postal_code' => '28080',
   *     'zone_name'   => 'Madrid',
   *     'methods'     => [
   *       ['title' => 'Local delivery', 'cost' => '5,00 €'],
   *       ['title' => 'Pickup',         'cost' => 'Gratis'],
   *     ],
   *   ]
   */
  private static function build_chat_order_shipping_info($messages) {
    if (!class_exists('WooCommerce') || !class_exists('WC_Shipping_Zones')) {
      return null;
    }

    $postal_code = self::find_recent_user_postal_code($messages);
    if (empty($postal_code)) {
      return null;
    }

    // Build a minimal WC package so WC can compute the rates exactly like
    // it would at checkout. Mirrors try_build_direct_shipping_response().
    $base_country = '';
    if (function_exists('WC')) {
      $wc = WC();
      if (isset($wc->countries) && is_object($wc->countries) && method_exists($wc->countries, 'get_base_country')) {
        $base_country = (string) $wc->countries->get_base_country();
      }
    }
    if ($base_country === '') {
      $base_country = 'ES';
    }

    $package = [
      'contents'        => [],
      'contents_cost'   => 0,
      'applied_coupons' => [],
      'user'            => ['ID' => 0],
      'destination'     => [
        'country'   => $base_country,
        'state'     => '',
        'postcode'  => $postal_code,
        'city'      => '',
        'address'   => '',
        'address_2' => '',
      ],
    ];

    try {
      $zone = \WC_Shipping_Zones::get_zone_matching_package($package);
    } catch (\Throwable $e) {
      self::log('build_chat_order_shipping_info — WC zone match failed: ' . $e->getMessage());
      return null;
    }
    if (!$zone) {
      return null;
    }

    $rates = self::get_wc_rates_for_zone($zone, $package);

    // Same safety net used by the direct shipping response: if the primary
    // zone has no rates, look for a fallback zone whose rules still match.
    if (empty($rates)) {
      $fallback = self::find_fallback_zone_with_rates($package, (int) $zone->get_id());
      if ($fallback !== null) {
        $zone  = $fallback['zone'];
        $rates = $fallback['rates'];
      }
    }

    $methods = [];

    if (!empty($rates)) {
      foreach ($rates as $rate) {
        if (!is_object($rate)) {
          continue;
        }
        $title = method_exists($rate, 'get_label') ? (string) $rate->get_label() : '';
        if ($title === '' && method_exists($rate, 'get_method_id')) {
          $title = (string) $rate->get_method_id();
        }
        $cost = method_exists($rate, 'get_cost') ? (float) $rate->get_cost() : 0.0;
        if (method_exists($rate, 'get_shipping_tax')) {
          $cost += (float) $rate->get_shipping_tax();
        }
        $methods[] = [
          'title' => $title,
          'cost'  => ($cost > 0)
            ? html_entity_decode(strip_tags(wc_price($cost)), ENT_QUOTES, 'UTF-8')
            : __('Free', 'pn-customers-manager'),
        ];
      }
    }

    // Fallback: if the zone returned no rates at all (nothing shippable to
    // that postcode), still expose the zone methods with their static cost
    // so the email is not completely silent about shipping.
    if (empty($methods)) {
      $zone_methods = $zone->get_shipping_methods(true);
      if (!empty($zone_methods)) {
        foreach ($zone_methods as $method) {
          if (!is_object($method)) {
            continue;
          }
          $desc = self::describe_shipping_method($method);
          // describe_shipping_method returns "Title: Price" or "Title: FREE".
          $parts = explode(':', $desc, 2);
          $title = trim($parts[0] ?? $desc);
          $cost  = isset($parts[1]) ? trim($parts[1]) : '';
          if ($cost === '' || strcasecmp($cost, 'FREE') === 0) {
            $cost = __('Free', 'pn-customers-manager');
          }
          $methods[] = [
            'title' => $title,
            'cost'  => $cost,
          ];
        }
      }
    }

    return [
      'postal_code' => $postal_code,
      'zone_name'   => (string) $zone->get_zone_name(),
      'methods'     => $methods,
    ];
  }

  private static function detect_and_notify_order($ai_response, $conversation, $messages, $node_config) {
    // Accept both new (with IDs) and legacy tag formats.
    $tag_regex = '/\[PEDIDO_CONFIRMADO(?::([\d,\s]*))?\]/';
    if (!preg_match($tag_regex, $ai_response, $tag_match)) {
      return $ai_response;
    }

    $prefix = static::platform_prefix();

    self::log('detect_and_notify_order — tag detected in response: ' . $tag_match[0]);

    // Guard: prevent duplicate order emails for the same conversation.
    // The AI may emit the tag more than once across turns (e.g. before and
    // after the postal code is provided). Only the first one should trigger
    // the email; subsequent occurrences within the TTL window are stripped
    // silently so only one notification is ever sent per order.
    $transient_key = 'pn_cm_order_notified_' . $conversation->id;
    if (get_transient($transient_key)) {
      self::log('detect_and_notify_order — SKIPPING duplicate: order email already sent for conversation ' . $conversation->id);
      $ai_response = preg_replace($tag_regex, '', $ai_response);
      return trim($ai_response);
    }

    if (empty($node_config[$prefix . 'enable_chat_orders'])) {
      self::log('detect_and_notify_order — SECURITY: tag found but chat orders are DISABLED. Stripping tag.');
      return preg_replace($tag_regex, '', $ai_response);
    }

    // Parse product IDs from the tag (preferred source of truth).
    $confirmed_ids = [];
    if (!empty($tag_match[1])) {
      $raw_ids = preg_split('/[,\s]+/', trim($tag_match[1]));
      foreach ($raw_ids as $raw_id) {
        $raw_id = (int) $raw_id;
        if ($raw_id > 0) {
          $confirmed_ids[] = $raw_id;
        }
      }
      $confirmed_ids = array_values(array_unique($confirmed_ids));
    }

    // Resolve notification recipients.
    // Sources (merged, deduped):
    //   1. Platform users selected in the node settings (by user ID).
    //   2. External email addresses added manually in the node settings.
    //   3. Legacy single `chat_orders_email` field (for backwards compatibility).
    //   4. Site admin email (fallback only, if all of the above are empty).
    $recipients = [];

    // 1) Platform user IDs → emails
    if (!empty($node_config[$prefix . 'chat_orders_users'])) {
      $user_ids = $node_config[$prefix . 'chat_orders_users'];
      if (!is_array($user_ids)) {
        $user_ids = [$user_ids];
      }
      foreach ($user_ids as $uid) {
        $uid = (int) $uid;
        if ($uid <= 0) {
          continue;
        }
        $user = get_userdata($uid);
        if ($user && !empty($user->user_email)) {
          $maybe = sanitize_email($user->user_email);
          if (!empty($maybe)) {
            $recipients[] = $maybe;
          }
        }
      }
    }

    // 2) External emails (html_multi rows)
    if (!empty($node_config[$prefix . 'chat_orders_external_emails'])) {
      $external = $node_config[$prefix . 'chat_orders_external_emails'];
      if (!is_array($external)) {
        $external = [$external];
      }
      foreach ($external as $ext_email) {
        if (!is_string($ext_email)) {
          continue;
        }
        $ext_email = trim($ext_email);
        if ($ext_email === '') {
          continue;
        }
        $maybe = sanitize_email($ext_email);
        if (!empty($maybe)) {
          $recipients[] = $maybe;
        }
      }
    }

    // 3) Legacy single-email field (kept for backwards compatibility with older configs)
    if (!empty($node_config[$prefix . 'chat_orders_email'])) {
      $legacy = sanitize_email($node_config[$prefix . 'chat_orders_email']);
      if (!empty($legacy)) {
        $recipients[] = $legacy;
      }
    }

    // Deduplicate (case-insensitive)
    $seen       = [];
    $recipients = array_filter($recipients, function ($addr) use (&$seen) {
      $key = strtolower($addr);
      if (isset($seen[$key])) {
        return false;
      }
      $seen[$key] = true;
      return true;
    });
    $recipients = array_values($recipients);

    // 4) Fallback to site admin email if nothing configured
    if (empty($recipients)) {
      $recipients[] = get_option('admin_email');
    }

    self::log('detect_and_notify_order — sending notification to: ' . implode(', ', $recipients));

    // Build order context from recent messages
    $recent  = array_slice($messages, -10);
    $excerpt = '';
    foreach ($recent as $msg) {
      $role    = ($msg['role'] === 'user') ? '👤 Cliente' : '🤖 Asistente';
      $content = $msg['content'];
      $content = preg_replace('/\[Se enviaron imágenes de:[^\]]*\]/', '', $content);
      $content = trim($content);
      if ($content === '') {
        continue;
      }

      // Format the stored mysql timestamp into a human-friendly marker.
      $time_marker = '';
      if (!empty($msg['timestamp'])) {
        $ts = strtotime($msg['timestamp']);
        if ($ts !== false) {
          $time_marker = '[' . date_i18n('d/m/Y H:i', $ts) . '] ';
        }
      }

      $excerpt .= $time_marker . $role . ': ' . $content . "\n\n";
    }

    // Resolve product details. Prefer the IDs declared by the model in the tag;
    // fall back to scanning the recent conversation only if the model did not
    // include any (legacy behavior).
    $products = [];
    if (!empty($confirmed_ids) && function_exists('wc_get_product')) {
      foreach ($confirmed_ids as $cid) {
        $product = wc_get_product($cid);
        if ($product) {
          $products[] = [
            'id'    => $cid,
            'name'  => $product->get_name(),
            'price' => strip_tags(wc_price($product->get_price())),
          ];
        }
      }
      self::log('detect_and_notify_order — resolved ' . count($products) . ' product(s) from tag IDs');
    } else {
      $full_text = implode("\n", array_column($recent, 'content'));
      $products  = self::extract_order_details($full_text);
      self::log('detect_and_notify_order — tag had no IDs, fell back to conversation scan (' . count($products) . ' products)');
    }

    // Build email
    $platform_name  = static::platform_display_name();
    $color          = static::brand_color();
    $customer_name  = !empty($conversation->contact_name) ? $conversation->contact_name : __('Unknown', 'pn-customers-manager');
    $customer_id    = static::get_identifier_value($conversation) ?: __('Unknown', 'pn-customers-manager');
    $id_label       = static::get_identifier_label();
    $order_date     = current_time('d/m/Y H:i');
    $site_name      = get_bloginfo('name');

    $subject = sprintf(
      /* translators: 1: site name, 2: platform name */
      __('[%1$s] New order via %2$s', 'pn-customers-manager'),
      $site_name,
      $platform_name
    );

    $html  = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">';
    $html .= '<h2 style="color:' . esc_attr($color) . ';">' . sprintf(esc_html__('New order via %s', 'pn-customers-manager'), esc_html($platform_name)) . '</h2>';
    $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">';
    $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html__('Customer', 'pn-customers-manager') . '</td>';
    $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($customer_name) . '</td></tr>';
    $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html($id_label) . '</td>';
    $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($customer_id) . '</td></tr>';
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

    // Shipping information (postal code → zone → available methods with prices).
    $shipping_info = self::build_chat_order_shipping_info($messages);
    if (!empty($shipping_info)) {
      $html .= '<h3>' . esc_html__('Shipping information', 'pn-customers-manager') . '</h3>';
      $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">';
      $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;width:40%;">' . esc_html__('Postal code', 'pn-customers-manager') . '</td>';
      $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($shipping_info['postal_code']) . '</td></tr>';
      if (!empty($shipping_info['zone_name'])) {
        $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html__('Shipping zone', 'pn-customers-manager') . '</td>';
        $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($shipping_info['zone_name']) . '</td></tr>';
      }
      $html .= '</table>';

      if (!empty($shipping_info['methods'])) {
        $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">';
        $html .= '<tr style="background:#f5f5f5;">';
        $html .= '<th style="padding:8px;text-align:left;border-bottom:2px solid #ddd;">' . esc_html__('Shipping method', 'pn-customers-manager') . '</th>';
        $html .= '<th style="padding:8px;text-align:right;border-bottom:2px solid #ddd;">' . esc_html__('Cost', 'pn-customers-manager') . '</th></tr>';
        foreach ($shipping_info['methods'] as $method) {
          $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($method['title']) . '</td>';
          $html .= '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">' . esc_html($method['cost']) . '</td></tr>';
        }
        $html .= '</table>';
      } else {
        $html .= '<p style="margin-bottom:20px;color:#a00;">' . esc_html__('No shipping methods available for this postal code.', 'pn-customers-manager') . '</p>';
      }
    }

    $html .= '<h3>' . esc_html__('Conversation excerpt', 'pn-customers-manager') . '</h3>';
    $html .= '<div style="background:#f9f9f9;padding:15px;border-radius:8px;white-space:pre-wrap;font-size:13px;line-height:1.5;">';
    $html .= esc_html($excerpt);
    $html .= '</div>';
    $html .= '</div>';

    // Send email to every resolved recipient.
    //
    // We reuse a single MAILPN_Mailing instance across the loop. Creating a
    // new instance per iteration is dangerous because MAILPN_Mailing::mailpn_sender()
    // re-registers an `add_action('phpmailer_init', [$this, 'mailpn_configure_smtp'])`
    // bound to that specific `$this`. With a fresh instance every iteration, the
    // action queue grows by one on every send, all bound to DIFFERENT objects,
    // and they never get removed. On subsequent wp_mail() calls every stored
    // callback fires in sequence, which in turn reconfigures the global PHPMailer
    // repeatedly and — with SMTP enabled — can leave the connection in a bad
    // state, so only the first send actually gets delivered.
    $mailing      = class_exists('MAILPN_Mailing') ? new \MAILPN_Mailing() : null;
    $total        = count($recipients);
    $i            = 0;
    self::log('detect_and_notify_order — dispatching to ' . $total . ' recipient(s): ' . implode(', ', $recipients));

    foreach ($recipients as $recipient_email) {
      $i++;
      $recipient_email = sanitize_email($recipient_email);
      if (empty($recipient_email) || !is_email($recipient_email)) {
        self::log(sprintf('detect_and_notify_order — [%d/%d] SKIP invalid recipient "%s"', $i, $total, $recipient_email));
        continue;
      }

      $sent = false;

      if ($mailing !== null) {
        try {
          $sent = $mailing->mailpn_sender([
            'mailpn_user_to' => $recipient_email,
            'mailpn_subject' => $subject,
            'mailpn_type'    => static::email_type(),
          ], $html);
          self::log(sprintf('detect_and_notify_order — [%d/%d] MAILPN to %s result=%s', $i, $total, $recipient_email, var_export($sent, true)));
        } catch (\Exception $e) {
          self::log(sprintf('detect_and_notify_order — [%d/%d] MAILPN exception for %s: %s', $i, $total, $recipient_email, $e->getMessage()));
          $sent = false;
        }
      }

      // Fallback: if MAILPN was not available, or returned false / threw,
      // send directly with wp_mail so this recipient is not silently dropped.
      if (!$sent) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent    = wp_mail($recipient_email, $subject, $html, $headers);
        self::log(sprintf('detect_and_notify_order — [%d/%d] wp_mail fallback to %s result=%s', $i, $total, $recipient_email, $sent ? 'OK' : 'FAILED'));
      }
    }

    // Mark this conversation so subsequent [PEDIDO_CONFIRMADO] occurrences
    // within the next hour are treated as duplicates and suppressed.
    set_transient($transient_key, time(), HOUR_IN_SECONDS);

    // Strip tag from visible response (supports both [PEDIDO_CONFIRMADO] and [PEDIDO_CONFIRMADO:1,2,3])
    $ai_response = preg_replace($tag_regex, '', $ai_response);
    $ai_response = trim($ai_response);

    // Append a WooCommerce payment link if enabled.
    if (!empty($node_config[$prefix . 'chat_orders_payment_link']) && !empty($products)) {
      $payment_url = self::build_chat_order_payment_url($products);
      if (!empty($payment_url)) {
        $lang       = self::detect_conversation_language($messages);
        $intro_line = ($lang === 'en')
          ? 'You can complete your payment here:'
          : 'Puedes completar tu pago aquí:';
        $ai_response = rtrim($ai_response) . "\n\n" . $intro_line . "\n" . $payment_url;
        self::log('detect_and_notify_order — appended payment link: ' . $payment_url);
      } else {
        self::log('detect_and_notify_order — payment link enabled but could not build URL (no product IDs)');
      }
    }

    return $ai_response;
  }

  private static function detect_and_notify_special_order($ai_response, $conversation, $messages, $node_config) {
    $tag_regex = '/\[PEDIDO_ESPECIAL\]/';
    if (!preg_match($tag_regex, $ai_response)) {
      return $ai_response;
    }

    $prefix = static::platform_prefix();

    self::log('detect_and_notify_special_order — tag detected in response');

    // Guard: prevent duplicate emails for the same conversation.
    $transient_key = 'pn_cm_special_order_notified_' . $conversation->id;
    if (get_transient($transient_key)) {
      self::log('detect_and_notify_special_order — SKIPPING duplicate: email already sent for conversation ' . $conversation->id);
      $ai_response = preg_replace($tag_regex, '', $ai_response);
      return trim($ai_response);
    }

    if (empty($node_config[$prefix . 'enable_special_orders'])) {
      self::log('detect_and_notify_special_order — SECURITY: tag found but special orders are DISABLED. Stripping tag.');
      return preg_replace($tag_regex, '', $ai_response);
    }

    // Resolve notification recipients.
    $recipients = [];

    // 1) Platform user IDs → emails
    if (!empty($node_config[$prefix . 'special_orders_users'])) {
      $user_ids = $node_config[$prefix . 'special_orders_users'];
      if (!is_array($user_ids)) {
        $user_ids = [$user_ids];
      }
      foreach ($user_ids as $uid) {
        $uid = (int) $uid;
        if ($uid <= 0) {
          continue;
        }
        $user = get_userdata($uid);
        if ($user && !empty($user->user_email)) {
          $maybe = sanitize_email($user->user_email);
          if (!empty($maybe)) {
            $recipients[] = $maybe;
          }
        }
      }
    }

    // 2) External emails (html_multi rows)
    if (!empty($node_config[$prefix . 'special_orders_external_emails'])) {
      $external = $node_config[$prefix . 'special_orders_external_emails'];
      if (!is_array($external)) {
        $external = [$external];
      }
      foreach ($external as $ext_email) {
        if (!is_string($ext_email)) {
          continue;
        }
        $ext_email = trim($ext_email);
        if ($ext_email === '') {
          continue;
        }
        $maybe = sanitize_email($ext_email);
        if (!empty($maybe)) {
          $recipients[] = $maybe;
        }
      }
    }

    // Deduplicate (case-insensitive)
    $seen       = [];
    $recipients = array_filter($recipients, function ($addr) use (&$seen) {
      $key = strtolower($addr);
      if (isset($seen[$key])) {
        return false;
      }
      $seen[$key] = true;
      return true;
    });
    $recipients = array_values($recipients);

    // Fallback to site admin email if nothing configured
    if (empty($recipients)) {
      $recipients[] = get_option('admin_email');
    }

    self::log('detect_and_notify_special_order — sending notification to: ' . implode(', ', $recipients));

    // Build conversation excerpt from recent messages
    $recent  = array_slice($messages, -15);
    $excerpt = '';
    foreach ($recent as $msg) {
      $role    = ($msg['role'] === 'user') ? '👤 Cliente' : '🤖 Asistente';
      $content = $msg['content'];
      $content = preg_replace('/\[Se enviaron imágenes de:[^\]]*\]/', '', $content);
      $content = trim($content);
      if ($content === '') {
        continue;
      }

      $time_marker = '';
      if (!empty($msg['timestamp'])) {
        $ts = strtotime($msg['timestamp']);
        if ($ts !== false) {
          $time_marker = '[' . date_i18n('d/m/Y H:i', $ts) . '] ';
        }
      }

      $excerpt .= $time_marker . $role . ': ' . $content . "\n\n";
    }

    // Build email
    $platform_name = static::platform_display_name();
    $color         = static::brand_color();
    $customer_name = !empty($conversation->contact_name) ? $conversation->contact_name : __('Unknown', 'pn-customers-manager');
    $customer_id   = static::get_identifier_value($conversation) ?: __('Unknown', 'pn-customers-manager');
    $id_label      = static::get_identifier_label();
    $order_date    = current_time('d/m/Y H:i');
    $site_name     = get_bloginfo('name');

    $subject = sprintf(
      /* translators: 1: site name, 2: platform name */
      __('[%1$s] Special order request via %2$s', 'pn-customers-manager'),
      $site_name,
      $platform_name
    );

    $html  = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">';
    $html .= '<h2 style="color:' . esc_attr($color) . ';">' . sprintf(esc_html__('Special order request via %s', 'pn-customers-manager'), esc_html($platform_name)) . '</h2>';
    $html .= '<p style="margin-bottom:20px;color:#555;">' . esc_html__('A customer has made a request that requires special attention (B2B, bulk, custom products, special shipping, etc.).', 'pn-customers-manager') . '</p>';
    $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">';
    $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html__('Customer', 'pn-customers-manager') . '</td>';
    $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($customer_name) . '</td></tr>';
    $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html($id_label) . '</td>';
    $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($customer_id) . '</td></tr>';
    $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html__('Date', 'pn-customers-manager') . '</td>';
    $html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($order_date) . '</td></tr>';
    $html .= '</table>';

    $html .= '<h3>' . esc_html__('Conversation excerpt', 'pn-customers-manager') . '</h3>';
    $html .= '<div style="background:#f9f9f9;padding:15px;border-radius:8px;white-space:pre-wrap;font-size:13px;line-height:1.5;">';
    $html .= esc_html($excerpt);
    $html .= '</div>';
    $html .= '</div>';

    // Send email
    $mailing = class_exists('MAILPN_Mailing') ? new \MAILPN_Mailing() : null;
    $total   = count($recipients);
    $i       = 0;
    self::log('detect_and_notify_special_order — dispatching to ' . $total . ' recipient(s): ' . implode(', ', $recipients));

    foreach ($recipients as $recipient_email) {
      $i++;
      $recipient_email = sanitize_email($recipient_email);
      if (empty($recipient_email) || !is_email($recipient_email)) {
        self::log(sprintf('detect_and_notify_special_order — [%d/%d] SKIP invalid recipient "%s"', $i, $total, $recipient_email));
        continue;
      }

      $sent = false;

      if ($mailing !== null) {
        try {
          $sent = $mailing->mailpn_sender([
            'mailpn_user_to' => $recipient_email,
            'mailpn_subject' => $subject,
            'mailpn_type'    => static::email_type(),
          ], $html);
          self::log(sprintf('detect_and_notify_special_order — [%d/%d] MAILPN to %s result=%s', $i, $total, $recipient_email, var_export($sent, true)));
        } catch (\Exception $e) {
          self::log(sprintf('detect_and_notify_special_order — [%d/%d] MAILPN exception for %s: %s', $i, $total, $recipient_email, $e->getMessage()));
          $sent = false;
        }
      }

      if (!$sent) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent    = wp_mail($recipient_email, $subject, $html, $headers);
        self::log(sprintf('detect_and_notify_special_order — [%d/%d] wp_mail fallback to %s result=%s', $i, $total, $recipient_email, $sent ? 'OK' : 'FAILED'));
      }
    }

    // Mark this conversation so subsequent tags are suppressed.
    set_transient($transient_key, time(), HOUR_IN_SECONDS);

    // Strip tag from visible response
    $ai_response = preg_replace($tag_regex, '', $ai_response);
    $ai_response = trim($ai_response);

    return $ai_response;
  }

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
            'id'    => $product_id,
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
        if (strpos($url, 'add-to-cart=') !== false) {
          continue;
        }
        $product_id = url_to_postid($url);
        if ($product_id && !isset($seen_ids[$product_id]) && get_post_type($product_id) === 'product') {
          $product = wc_get_product($product_id);
          if ($product) {
            $seen_ids[$product_id] = true;
            $products[] = [
              'id'    => $product_id,
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
            'id'    => $product_id,
            'name'  => $product->get_name(),
            'price' => strip_tags(wc_price($product->get_price())),
          ];
        }
      }
    }

    return $products;
  }

  /**
   * Build a WooCommerce cart URL that auto-adds the given products.
   *
   * Uses the standard `?add-to-cart=ID` query parameter. When multiple
   * products are provided, comma-separates the IDs so WooCommerce adds
   * them all in a single request (supported natively by WC).
   *
   * @param array $products Array of products as returned by extract_order_details (must include 'id').
   * @return string Full cart URL or empty string on failure.
   */
  private static function build_chat_order_payment_url($products) {
    if (!function_exists('wc_get_cart_url')) {
      return '';
    }
    if (empty($products) || !is_array($products)) {
      return '';
    }

    $ids = [];
    foreach ($products as $product) {
      if (!empty($product['id'])) {
        $ids[] = (int) $product['id'];
      }
    }
    $ids = array_values(array_unique(array_filter($ids)));
    if (empty($ids)) {
      return '';
    }

    $cart_url = wc_get_cart_url();
    if (empty($cart_url)) {
      return '';
    }

    return add_query_arg('add-to-cart', implode(',', $ids), $cart_url);
  }
}
