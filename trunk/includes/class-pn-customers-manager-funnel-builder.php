<?php
/**
 * Visual Funnel Builder.
 *
 * Renders a drag-and-drop canvas for building funnels visually.
 * Each funnel post stores its canvas layout as JSON in post meta.
 *
 * @since   1.0.41
 * @package pn-customers-manager
 */

class PN_CUSTOMERS_MANAGER_Funnel_Builder {

  /**
   * Meta key for storing canvas JSON data.
   */
  const CANVAS_META_KEY = 'pn_cm_funnel_canvas';

  /**
   * Render the funnel builder admin page.
   */
  public static function render_page() {
    if (!current_user_can('edit_pn_cm_funnel')) {
      wp_die(esc_html__('You do not have permission to access this page.', 'pn-customers-manager'));
    }

    $funnel_id = isset($_GET['funnel_id']) ? absint($_GET['funnel_id']) : 0;
    $funnel    = $funnel_id ? get_post($funnel_id) : null;

    // Load all funnels for the selector
    $funnels = get_posts([
      'post_type'      => 'pn_cm_funnel',
      'posts_per_page' => -1,
      'orderby'        => 'title',
      'order'          => 'ASC',
      'post_status'    => ['publish', 'draft', 'private'],
    ]);

    $canvas_data = '';
    if ($funnel) {
      $canvas_data = get_post_meta($funnel_id, self::CANVAS_META_KEY, true);
      if (!$canvas_data) {
        $canvas_data = '';
      }
    }

    $nonce    = wp_create_nonce('pn-customers-manager-nonce');
    $ajax_url = admin_url('admin-ajax.php');

    ?>
    <div class="pn-cm-fb-wrap">
      <!-- Top bar -->
      <div class="pn-cm-fb-topbar">
        <div class="pn-cm-fb-topbar-left">
          <span class="material-icons-outlined pn-cm-fb-topbar-icon">account_tree</span>
          <span class="pn-cm-fb-topbar-title"><?php esc_html_e('Funnel Builder', 'pn-customers-manager'); ?></span>
          <select id="pn-cm-fb-funnel-select" class="pn-cm-fb-select">
            <option value=""><?php esc_html_e('-- Select funnel --', 'pn-customers-manager'); ?></option>
            <?php foreach ($funnels as $f): ?>
              <option value="<?php echo esc_attr($f->ID); ?>" <?php selected($funnel_id, $f->ID); ?>>
                <?php echo esc_html($f->post_title); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="pn-cm-fb-topbar-right">
          <button type="button" id="pn-cm-fb-btn-save" class="pn-cm-fb-btn pn-cm-fb-btn-primary" <?php echo !$funnel ? 'disabled' : ''; ?>>
            <span class="material-icons-outlined">save</span>
            <?php esc_html_e('Save', 'pn-customers-manager'); ?>
          </button>
        </div>
      </div>

      <div class="pn-cm-fb-body">
        <!-- Left sidebar: Element palette -->
        <div class="pn-cm-fb-sidebar-left" id="pn-cm-fb-sidebar-left">
          <div class="pn-cm-fb-sidebar-section">
            <div class="pn-cm-fb-sidebar-heading"><?php esc_html_e('Traffic', 'pn-customers-manager'); ?></div>
            <div class="pn-cm-fb-element" draggable="true" data-type="traffic_source" data-subtype="facebook_ads">
              <span class="material-icons-outlined">campaign</span>
              <span>Facebook Ads</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="traffic_source" data-subtype="google_ads">
              <span class="material-icons-outlined">ads_click</span>
              <span>Google Ads</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="traffic_source" data-subtype="email">
              <span class="material-icons-outlined">email</span>
              <span>Email</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="traffic_source" data-subtype="social_organic">
              <span class="material-icons-outlined">share</span>
              <span><?php esc_html_e('Organic social', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="traffic_source" data-subtype="seo">
              <span class="material-icons-outlined">search</span>
              <span>SEO</span>
            </div>
          </div>

          <div class="pn-cm-fb-sidebar-section">
            <div class="pn-cm-fb-sidebar-heading"><?php esc_html_e('Landing Pages', 'pn-customers-manager'); ?></div>
            <div class="pn-cm-fb-element" draggable="true" data-type="landing_page" data-subtype="opt_in">
              <span class="material-icons-outlined">web</span>
              <span>Opt-in</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="landing_page" data-subtype="webinar">
              <span class="material-icons-outlined">videocam</span>
              <span>Webinar</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="landing_page" data-subtype="sales_page">
              <span class="material-icons-outlined">storefront</span>
              <span><?php esc_html_e('Sales page', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="landing_page" data-subtype="squeeze">
              <span class="material-icons-outlined">description</span>
              <span>Squeeze</span>
            </div>
          </div>

          <div class="pn-cm-fb-sidebar-section">
            <div class="pn-cm-fb-sidebar-heading"><?php esc_html_e('Checkout', 'pn-customers-manager'); ?></div>
            <div class="pn-cm-fb-element" draggable="true" data-type="checkout" data-subtype="order_form">
              <span class="material-icons-outlined">shopping_cart</span>
              <span><?php esc_html_e('Order form', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="checkout" data-subtype="upsell">
              <span class="material-icons-outlined">trending_up</span>
              <span>Upsell</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="checkout" data-subtype="downsell">
              <span class="material-icons-outlined">trending_down</span>
              <span>Downsell</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="checkout" data-subtype="thank_you">
              <span class="material-icons-outlined">celebration</span>
              <span><?php esc_html_e('Thank you', 'pn-customers-manager'); ?></span>
            </div>
          </div>

          <div class="pn-cm-fb-sidebar-section">
            <div class="pn-cm-fb-sidebar-heading"><?php esc_html_e('Follow-up', 'pn-customers-manager'); ?></div>
            <div class="pn-cm-fb-element" draggable="true" data-type="follow_up" data-subtype="email_sequence">
              <span class="material-icons-outlined">forward_to_inbox</span>
              <span><?php esc_html_e('Email sequence', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="follow_up" data-subtype="retargeting">
              <span class="material-icons-outlined">replay</span>
              <span>Retargeting</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="follow_up" data-subtype="automation">
              <span class="material-icons-outlined">smart_toy</span>
              <span><?php esc_html_e('Automation', 'pn-customers-manager'); ?></span>
            </div>
          </div>

          <div class="pn-cm-fb-sidebar-section">
            <div class="pn-cm-fb-sidebar-heading"><?php esc_html_e('Communication', 'pn-customers-manager'); ?></div>
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="whatsapp">
              <span class="material-icons-outlined">chat</span>
              <span>WhatsApp</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="whatsapp_ai">
              <span class="material-icons-outlined">psychology</span>
              <span>WhatsApp AI</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="instagram_ai">
              <span class="material-icons-outlined">photo_camera</span>
              <span>Instagram AI</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="chatbot">
              <span class="material-icons-outlined">forum</span>
              <span>Chatbot</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="live_chat">
              <span class="material-icons-outlined">support_agent</span>
              <span><?php esc_html_e('Live chat', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="sms">
              <span class="material-icons-outlined">sms</span>
              <span>SMS</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="call">
              <span class="material-icons-outlined">call</span>
              <span><?php esc_html_e('Call', 'pn-customers-manager'); ?></span>
            </div>
          </div>

          <div class="pn-cm-fb-sidebar-section">
            <div class="pn-cm-fb-sidebar-heading"><?php esc_html_e('Content', 'pn-customers-manager'); ?></div>
            <div class="pn-cm-fb-element" draggable="true" data-type="content" data-subtype="blog_post">
              <span class="material-icons-outlined">article</span>
              <span><?php esc_html_e('Blog / Article', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="content" data-subtype="video_content">
              <span class="material-icons-outlined">play_circle</span>
              <span><?php esc_html_e('Video', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="content" data-subtype="lead_magnet">
              <span class="material-icons-outlined">card_giftcard</span>
              <span>Lead Magnet</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="content" data-subtype="form">
              <span class="material-icons-outlined">assignment</span>
              <span><?php esc_html_e('Form', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="content" data-subtype="button">
              <span class="material-icons-outlined">smart_button</span>
              <span><?php esc_html_e('Button', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="content" data-subtype="cta">
              <span class="material-icons-outlined">touch_app</span>
              <span><?php esc_html_e('Call to action', 'pn-customers-manager'); ?></span>
            </div>
          </div>
        </div>

        <!-- Center: Canvas -->
        <div class="pn-cm-fb-canvas-wrap" id="pn-cm-fb-canvas-wrap">
          <?php if (!$funnel): ?>
            <div class="pn-cm-fb-canvas-empty">
              <span class="material-icons-outlined">account_tree</span>
              <p><?php esc_html_e('Select a funnel to start designing', 'pn-customers-manager'); ?></p>
            </div>
          <?php endif; ?>
          <!-- Sidebar toggle -->
          <button type="button" id="pn-cm-fb-sidebar-toggle" class="pn-cm-fb-sidebar-toggle" title="<?php esc_attr_e('Toggle blocks panel', 'pn-customers-manager'); ?>">
            <span class="material-icons-outlined">chevron_left</span>
          </button>
          <!-- Zoom controls -->
          <div class="pn-cm-fb-zoom-controls">
            <button type="button" id="pn-cm-fb-zoom-out" class="pn-cm-fb-zoom-btn" title="<?php esc_attr_e('Zoom out', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">remove</span>
            </button>
            <span class="pn-cm-fb-zoom-level" id="pn-cm-fb-zoom-level">100%</span>
            <button type="button" id="pn-cm-fb-zoom-in" class="pn-cm-fb-zoom-btn" title="<?php esc_attr_e('Zoom in', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">add</span>
            </button>
            <button type="button" id="pn-cm-fb-zoom-reset" class="pn-cm-fb-zoom-btn" title="<?php esc_attr_e('Reset view', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">center_focus_strong</span>
            </button>
          </div>
          <!-- Zoom / pan layer -->
          <div class="pn-cm-fb-zoom-layer" id="pn-cm-fb-zoom-layer">
            <svg class="pn-cm-fb-canvas-svg" id="pn-cm-fb-canvas-svg"></svg>
            <div class="pn-cm-fb-canvas" id="pn-cm-fb-canvas"></div>
          </div>
        </div>

        <!-- Right sidebar: Properties -->
        <div class="pn-cm-fb-sidebar-right" id="pn-cm-fb-sidebar-right">
          <div class="pn-cm-fb-props-empty">
            <span class="material-icons-outlined">touch_app</span>
            <p><?php esc_html_e('Select an element to see its properties', 'pn-customers-manager'); ?></p>
          </div>
          <div class="pn-cm-fb-props-panel" id="pn-cm-fb-props-panel" style="display:none;">
            <div class="pn-cm-fb-sidebar-heading"><?php esc_html_e('Properties', 'pn-customers-manager'); ?></div>
            <div class="pn-cm-fb-prop-group">
              <label class="pn-cm-fb-prop-label"><?php esc_html_e('Name', 'pn-customers-manager'); ?></label>
              <input type="text" id="pn-cm-fb-prop-label" class="pn-cm-fb-prop-input" />
            </div>

            <button type="button" id="pn-cm-fb-btn-settings" class="pn-cm-fb-btn pn-cm-fb-btn-settings">
              <span class="material-icons-outlined">settings</span>
              <?php esc_html_e('Settings', 'pn-customers-manager'); ?>
            </button>

            <!-- WhatsApp conversations button (shown for whatsapp/whatsapp_ai nodes) -->
            <div id="pn-cm-fb-wa-chat-btn-wrap" style="display:none;">
              <hr class="pn-cm-fb-prop-divider">
              <button type="button" id="pn-cm-fb-wa-chat-btn" class="pn-cm-fb-btn pn-cm-fb-btn-wa-chat">
                <span class="material-icons-outlined">chat</span>
                <?php esc_html_e('View conversations', 'pn-customers-manager'); ?>
              </button>
            </div>

            <!-- Instagram conversations button (shown for instagram_ai nodes) -->
            <div id="pn-cm-fb-ig-chat-btn-wrap" style="display:none;">
              <hr class="pn-cm-fb-prop-divider">
              <button type="button" id="pn-cm-fb-ig-chat-btn" class="pn-cm-fb-btn pn-cm-fb-btn-ig-chat">
                <span class="material-icons-outlined">photo_camera</span>
                <?php esc_html_e('View conversations', 'pn-customers-manager'); ?>
              </button>
            </div>

            <hr class="pn-cm-fb-prop-divider">
            <button type="button" id="pn-cm-fb-btn-delete-node" class="pn-cm-fb-btn pn-cm-fb-btn-danger">
              <span class="material-icons-outlined">delete</span>
              <?php esc_html_e('Delete', 'pn-customers-manager'); ?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- WhatsApp Chat Popup -->
    <div id="pn-cm-fb-wa-popup" class="pn-cm-fb-wa-popup" style="display:none;">
      <div class="pn-cm-fb-wa-popup-overlay"></div>
      <div class="pn-cm-fb-wa-popup-container">
        <!-- Header -->
        <div class="pn-cm-fb-wa-popup-header">
          <button type="button" id="pn-cm-fb-wa-popup-back" class="pn-cm-fb-wa-popup-back" style="display:none;">
            <span class="material-icons-outlined">arrow_back</span>
          </button>
          <span class="material-icons-outlined pn-cm-fb-wa-popup-icon">chat</span>
          <span id="pn-cm-fb-wa-popup-title" class="pn-cm-fb-wa-popup-title"><?php esc_html_e('WhatsApp Conversations', 'pn-customers-manager'); ?></span>
          <button type="button" id="pn-cm-fb-wa-popup-close" class="pn-cm-fb-wa-popup-close">
            <span class="material-icons-outlined">close</span>
          </button>
        </div>
        <!-- Body -->
        <div id="pn-cm-fb-wa-popup-body" class="pn-cm-fb-wa-popup-body">
          <div class="pn-cm-fb-wa-popup-loading">
            <span class="material-icons-outlined pn-cm-fb-wa-spin">sync</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Instagram Chat Popup -->
    <div id="pn-cm-fb-ig-popup" class="pn-cm-fb-ig-popup" style="display:none;">
      <div class="pn-cm-fb-ig-popup-overlay"></div>
      <div class="pn-cm-fb-ig-popup-container">
        <!-- Header -->
        <div class="pn-cm-fb-ig-popup-header">
          <button type="button" id="pn-cm-fb-ig-popup-back" class="pn-cm-fb-ig-popup-back" style="display:none;">
            <span class="material-icons-outlined">arrow_back</span>
          </button>
          <span class="material-icons-outlined pn-cm-fb-ig-popup-icon">photo_camera</span>
          <span id="pn-cm-fb-ig-popup-title" class="pn-cm-fb-ig-popup-title"><?php esc_html_e('Instagram Conversations', 'pn-customers-manager'); ?></span>
          <button type="button" id="pn-cm-fb-ig-popup-close" class="pn-cm-fb-ig-popup-close">
            <span class="material-icons-outlined">close</span>
          </button>
        </div>
        <!-- Body -->
        <div id="pn-cm-fb-ig-popup-body" class="pn-cm-fb-ig-popup-body">
          <div class="pn-cm-fb-ig-popup-loading">
            <span class="material-icons-outlined pn-cm-fb-ig-spin">sync</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Popup overlay (shared) -->
    <div class="pn-customers-manager-popup-overlay pn-customers-manager-display-none-soft"></div>

    <!-- Settings Popup (generic plugin popup) -->
    <div id="pn-cm-fb-settings-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-disable-esc="true" data-pn-customers-manager-popup-disable-overlay-close="true">
      <div class="pn-customers-manager-p-30">
        <div style="display:flex;align-items:center;justify-content:space-between;" class="pn-customers-manager-mb-20">
          <h3 class="pn-customers-manager-mt-0 pn-customers-manager-mb-0"><?php esc_html_e('Settings', 'pn-customers-manager'); ?></h3>
          <button type="button" id="pn-cm-fb-settings-popup-close" class="pn-customers-manager-popup-close-wrapper"><i class="material-icons-outlined">close</i></button>
        </div>
        <div class="pn-customers-manager-popup-content">
          <div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>
        </div>
      </div>
    </div>

    <?php

    // Enqueue styles
    wp_enqueue_style(
      'pn-customers-manager-funnel-builder',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-funnel-builder.css',
      [],
      PN_CUSTOMERS_MANAGER_VERSION
    );

    // Enqueue script
    wp_enqueue_script(
      'pn-customers-manager-funnel-builder',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-funnel-builder.js',
      [],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );

    // Check API configuration status
    $whatsapp_token  = get_option('pn_customers_manager_whatsapp_access_token', '');
    $whatsapp_phone  = get_option('pn_customers_manager_whatsapp_phone_number_id', '');
    $openai_key      = get_option('pn_customers_manager_whatsapp_openai_key', '');
    $instagram_token = get_option('pn_customers_manager_instagram_access_token', '');
    $instagram_page  = get_option('pn_customers_manager_instagram_page_id', '');

    $settings_url = admin_url('admin.php?page=pn-customers-manager');

    wp_localize_script('pn-customers-manager-funnel-builder', 'pnCmFunnelBuilder', [
      'ajaxUrl'    => $ajax_url,
      'nonce'      => $nonce,
      'funnelId'   => $funnel_id,
      'canvasData' => $canvas_data,
      'adminUrl'   => admin_url('admin.php'),
      'wooActive'  => class_exists('WooCommerce'),
      'apiStatus'  => [
        'whatsapp'  => !empty($whatsapp_token) && !empty($whatsapp_phone),
        'openai'    => !empty($openai_key),
        'instagram' => !empty($instagram_token) && !empty($instagram_page),
      ],
      'apiLinks'   => [
        'whatsapp'  => $settings_url . '&section=pn_customers_manager_api_section_start.pn_customers_manager_whatsapp_section_start',
        'openai'    => $settings_url . '&section=pn_customers_manager_api_section_start.pn_customers_manager_openai_section_start',
        'instagram' => $settings_url . '&section=pn_customers_manager_api_section_start.pn_customers_manager_instagram_section_start',
      ],
      'i18n'       => [
        'saved'              => __('Funnel saved successfully.', 'pn-customers-manager'),
        'saveError'          => __('Error saving funnel.', 'pn-customers-manager'),
        'confirmDelete'      => __('Delete this element?', 'pn-customers-manager'),
        'market'             => __('Market', 'pn-customers-manager'),
        'conversion'         => __('Conversion', 'pn-customers-manager'),
        'noFunnel'           => __('Select a funnel first.', 'pn-customers-manager'),
        'unsavedChanges'     => __('You have unsaved changes. Do you want to continue?', 'pn-customers-manager'),
        'apiNotConfigured'   => __('API not configured', 'pn-customers-manager'),
        'configureApi'       => __('Configure API', 'pn-customers-manager'),
        'conversations'      => __('WhatsApp Conversations', 'pn-customers-manager'),
        'noConversations'    => __('No conversations yet.', 'pn-customers-manager'),
        'messages'           => __('messages', 'pn-customers-manager'),
        'active'             => __('Active', 'pn-customers-manager'),
        'closed'             => __('Closed', 'pn-customers-manager'),
        'loadError'          => __('Error loading conversations.', 'pn-customers-manager'),
        'settings'           => __('Settings', 'pn-customers-manager'),
        'loadingSettings'    => __('Loading settings...', 'pn-customers-manager'),
        'settingsError'      => __('Error loading settings.', 'pn-customers-manager'),
        'igConversations'    => __('Instagram Conversations', 'pn-customers-manager'),
        'igNoConversations'  => __('No Instagram conversations yet.', 'pn-customers-manager'),
      ],
    ]);
  }

  /**
   * AJAX: Save funnel canvas data.
   *
   * @param int $funnel_id
   */
  public static function ajax_save_canvas($funnel_id) {
    if (empty($funnel_id)) {
      echo wp_json_encode([
        'error_key'     => 'missing_funnel_id',
        'error_content' => esc_html__('No funnel ID provided.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $funnel = get_post($funnel_id);
    if (!$funnel || $funnel->post_type !== 'pn_cm_funnel') {
      echo wp_json_encode([
        'error_key'     => 'invalid_funnel',
        'error_content' => esc_html__('Funnel not found.', 'pn-customers-manager'),
      ]);
      exit;
    }

    if (!current_user_can('edit_pn_cm_funnel', $funnel_id)) {
      echo wp_json_encode([
        'error_key'     => 'no_permission',
        'error_content' => esc_html__('You do not have permission to edit this funnel.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $canvas_json = isset($_POST['canvas_data']) ? wp_unslash($_POST['canvas_data']) : '';

    // Validate JSON
    $decoded = json_decode($canvas_json, true);
    if ($canvas_json !== '' && $decoded === null) {
      echo wp_json_encode([
        'error_key'     => 'invalid_json',
        'error_content' => esc_html__('Invalid canvas data.', 'pn-customers-manager'),
      ]);
      exit;
    }

    update_post_meta($funnel_id, self::CANVAS_META_KEY, $canvas_json);

    echo wp_json_encode([
      'error_key' => '',
      'success'   => true,
    ]);
    exit;
  }

  /**
   * AJAX: Load funnel canvas data.
   *
   * @param int $funnel_id
   */
  public static function ajax_load_canvas($funnel_id) {
    if (empty($funnel_id)) {
      echo wp_json_encode([
        'error_key'     => 'missing_funnel_id',
        'error_content' => esc_html__('No funnel ID provided.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $funnel = get_post($funnel_id);
    if (!$funnel || $funnel->post_type !== 'pn_cm_funnel') {
      echo wp_json_encode([
        'error_key'     => 'invalid_funnel',
        'error_content' => esc_html__('Funnel not found.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $canvas_data = get_post_meta($funnel_id, self::CANVAS_META_KEY, true);

    echo wp_json_encode([
      'error_key'   => '',
      'canvas_data' => $canvas_data ?: '',
      'title'       => $funnel->post_title,
    ]);
    exit;
  }

  /**
   * Get the settings field definitions for a given node subtype.
   *
   * @param string $subtype
   * @return array
   */
  public static function get_node_settings_fields($subtype) {
    $fields = [];

    // Common fields for all subtypes
    $fields[] = [
      'id'          => 'url',
      'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input'       => 'input',
      'type'        => 'url',
      'label'       => 'URL',
      'placeholder' => 'https://',
    ];
    $fields[] = [
      'id'          => 'notes',
      'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input'       => 'textarea',
      'label'       => __('Notes', 'pn-customers-manager'),
    ];
    $fields[] = [
      'id'          => 'api_endpoint',
      'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input'       => 'input',
      'type'        => 'url',
      'label'       => __('API Endpoint', 'pn-customers-manager'),
      'placeholder' => 'https://api...',
    ];

    // Button specific fields
    if ($subtype === 'button') {
      $fields[] = [
        'id'           => 'btn_heading_settings',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Button settings', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'btn_text',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'text',
        'label'       => __('Button text', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'btn_url',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'url',
        'label'       => __('Destination URL', 'pn-customers-manager'),
        'placeholder' => 'https://',
      ];
      $fields[] = [
        'id'     => 'btn_target_blank',
        'class'  => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'  => 'input',
        'type'   => 'checkbox',
        'label'  => __('Open in new tab', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'     => 'btn_nofollow',
        'class'  => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'  => 'input',
        'type'   => 'checkbox',
        'label'  => __('Add rel=nofollow', 'pn-customers-manager'),
      ];

      // Appearance
      $fields[] = [
        'id'           => 'btn_heading_appearance',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Appearance', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'      => 'btn_style',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'type'    => 'select',
        'label'   => __('Style', 'pn-customers-manager'),
        'options' => [
          'solid'       => __('Solid', 'pn-customers-manager'),
          'outline'     => __('Outline', 'pn-customers-manager'),
          'transparent' => __('Transparent', 'pn-customers-manager'),
        ],
      ];
      $fields[] = [
        'id'    => 'btn_color',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type'  => 'color',
        'label' => __('Background color', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'    => 'btn_text_color',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type'  => 'color',
        'label' => __('Text color', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'      => 'btn_size',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'type'    => 'select',
        'label'   => __('Size', 'pn-customers-manager'),
        'options' => [
          'small'  => __('Small', 'pn-customers-manager'),
          'medium' => __('Medium', 'pn-customers-manager'),
          'large'  => __('Large', 'pn-customers-manager'),
        ],
      ];
      $fields[] = [
        'id'      => 'btn_align',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'type'    => 'select',
        'label'   => __('Alignment', 'pn-customers-manager'),
        'options' => [
          'left'   => __('Left', 'pn-customers-manager'),
          'center' => __('Center', 'pn-customers-manager'),
          'right'  => __('Right', 'pn-customers-manager'),
        ],
      ];

      // Icon
      $fields[] = [
        'id'           => 'btn_heading_icon',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Icon', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'btn_icon',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'text',
        'label'       => __('Material Icon name', 'pn-customers-manager'),
        'description' => __('e.g. shopping_cart, arrow_forward. See fonts.google.com/icons', 'pn-customers-manager'),
        'parent'      => 'this',
      ];
      $fields[] = [
        'id'            => 'btn_icon_position',
        'class'         => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'         => 'select',
        'type'          => 'select',
        'label'         => __('Icon position', 'pn-customers-manager'),
        'options'       => [
          'left'  => __('Left', 'pn-customers-manager'),
          'right' => __('Right', 'pn-customers-manager'),
        ],
        'parent'        => 'btn_icon',
        'parent_option' => 'dependent',
      ];

      // Image
      $fields[] = [
        'id'           => 'btn_heading_image',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Image', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'     => 'btn_use_image',
        'class'  => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'  => 'input',
        'type'   => 'checkbox',
        'label'  => __('Use image instead of text', 'pn-customers-manager'),
        'parent' => 'this',
      ];
      $fields[] = [
        'id'            => 'btn_image',
        'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'         => 'image',
        'label'         => __('Button image', 'pn-customers-manager'),
        'parent'        => 'btn_use_image',
        'parent_option' => 'on',
      ];

      // Shortcode preview
      $fields[] = [
        'id'           => 'btn_shortcode_preview',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Shortcode', 'pn-customers-manager') . '</h4>'
          . '<div style="display:flex;gap:8px;align-items:center">'
          . '<input type="text" id="pn-cm-fb-btn-shortcode" class="pn-customers-manager-input pn-customers-manager-width-80-percent" readonly style="margin-bottom:0;font-size:12px;font-family:monospace" value="" />'
          . '<button type="button" id="pn-cm-fb-btn-shortcode-copy" class="pn-customers-manager-btn pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="" style="white-space:nowrap;padding:10px 16px;font-size:13px" title="' . esc_attr__('Copy', 'pn-customers-manager') . '"><i class="material-icons-outlined" style="font-size:18px;vertical-align:middle">content_copy</i></button>'
          . '</div>',
      ];
    }

    // WhatsApp AI specific fields
    if ($subtype === 'whatsapp_ai') {
      $fields[] = [
        'id'           => 'wa_heading_ai',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('WhatsApp AI', 'pn-customers-manager') . '</h4>',
      ];

      $fields[] = [
        'id'          => 'wa_system_prompt',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('System prompt', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Override the global prompt for this node...', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'    => 'wa_company_info',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => __('Company information', 'pn-customers-manager'),
        'description' => __('Name, description, address, contact details, etc.', 'pn-customers-manager'),
      ];
      if (class_exists('WooCommerce')) {
        $fields[] = [
          'id'          => 'wa_wc_shipping_zones',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('Use WooCommerce shipping zones', 'pn-customers-manager'),
          'description' => __('When a customer asks about shipping and provides a postal code, the plugin bypasses the AI model and uses WooCommerce\'s own shipping functions to match the correct zone and calculate the available methods and prices. The response is sent directly to the customer, ensuring accurate rates and preventing the model from picking the wrong zone or re-asking for the postal code. Configured shipping zones are also passed to the AI model as fallback context for any other shipping-related questions.', 'pn-customers-manager'),
          'parent'      => 'this',
        ];
        $fields[] = [
          'id'          => 'wa_require_postal_code',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('Always request postal code', 'pn-customers-manager'),
          'description' => __('The AI will always ask for the postal code before quoting a shipping cost.', 'pn-customers-manager'),
          'parent'      => 'this',
        ];
      }
      $fields[] = [
        'id'    => 'wa_shipping_info',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => __('Additional shipping information', 'pn-customers-manager'),
        'description' => __('Extra shipping details: delivery times, special conditions, etc.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'    => 'wa_schedule_info',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => __('Opening hours', 'pn-customers-manager'),
        'description' => __('Business hours, weekdays, weekends, holidays, etc.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'wa_knowledge_base',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'editor',
        'label'       => __('Knowledge base', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Content the AI will use to respond: business info, hours, policies, prices, etc.', 'pn-customers-manager'),
        'description' => __('Additional information the AI will use as reference to respond.', 'pn-customers-manager'),
      ];

      $fields[] = [
        'id'           => 'wa_heading_auto',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Include automatic content', 'pn-customers-manager') . '</h4>',
      ];

      if (class_exists('WooCommerce')) {
        $fields[] = [
          'id'          => 'wa_include_woo',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('WooCommerce products', 'pn-customers-manager'),
          'description' => __('Includes product name, price and purchase link.', 'pn-customers-manager'),
          'parent'      => 'this',
        ];
        $fields[] = [
          'id'            => 'wa_include_woo_variations',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Include product variations', 'pn-customers-manager'),
          'description'   => __('Adds all variations with their attributes, prices and direct purchase links. Warning: this can significantly increase AI API call costs.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'wa_include_woo_add_to_cart',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Direct add to cart links', 'pn-customers-manager'),
          'description'   => __('Product links will add the product to the cart directly. If disabled, links will point to the product page.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'wa_include_woo_images',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Send product images', 'pn-customers-manager'),
          'description'   => __('When a customer asks about a product, send its photos via WhatsApp.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'wa_include_woo_categories',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Include product categories', 'pn-customers-manager'),
          'description'   => __('Adds product categories so the AI can classify and reason about product types.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'wa_include_woo_tags',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Include product tags', 'pn-customers-manager'),
          'description'   => __('Adds product tags so the AI can identify product features and characteristics.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'wa_enable_recommendations',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Guided product recommendations', 'pn-customers-manager'),
          'description'   => __('The AI will ask qualifying questions (budget, preferences) before recommending products.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
        ];
      }

      $fields[] = [
        'id'            => 'wa_enable_chat_orders',
        'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'         => 'input',
        'type'          => 'checkbox',
        'label'         => __('Accept orders via chat', 'pn-customers-manager'),
        'description'   => __('Allow the AI to confirm orders during WhatsApp conversations and send an email notification.', 'pn-customers-manager'),
        'parent'        => 'this',
      ];
      // Build user options for the multi-select (platform users who will receive order notifications)
      $wa_chat_orders_user_options = [];
      $wa_chat_orders_all_users    = get_users([
        'fields'  => ['ID', 'display_name', 'user_email'],
        'orderby' => 'display_name',
        'order'   => 'ASC',
      ]);
      foreach ($wa_chat_orders_all_users as $wa_chat_orders_user) {
        if (empty($wa_chat_orders_user->user_email)) {
          continue;
        }
        $wa_chat_orders_user_options[$wa_chat_orders_user->ID] = $wa_chat_orders_user->display_name . ' (' . $wa_chat_orders_user->user_email . ')';
      }

      $fields[] = [
        'id'            => 'wa_chat_orders_users',
        'class'         => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'         => 'select',
        'multiple'      => true,
        'label'         => __('Order notification recipients (platform users)', 'pn-customers-manager'),
        'description'   => __('Select platform users who will receive order notifications by email. Leave empty to use only external emails or the site admin email.', 'pn-customers-manager'),
        'options'       => $wa_chat_orders_user_options,
        'parent'        => 'wa_enable_chat_orders',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'                => 'wa_chat_orders_external_emails_wrapper',
        'class'             => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'             => 'html_multi',
        'label'             => __('Order notification recipients (external emails)', 'pn-customers-manager'),
        'description'       => __('Add email addresses for people who are not registered users on this site.', 'pn-customers-manager'),
        'html_multi_fields' => [
          [
            'id'          => 'wa_chat_orders_external_emails',
            'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
            'input'       => 'input',
            'type'        => 'email',
            'placeholder' => 'email@example.com',
          ],
        ],
        'parent'        => 'wa_enable_chat_orders',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'            => 'wa_chat_orders_payment_link',
        'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'         => 'input',
        'type'          => 'checkbox',
        'label'         => __('Send payment link on order confirmation', 'pn-customers-manager'),
        'description'   => __('When an order is confirmed via chat, append a WooCommerce cart link that automatically adds the chosen products so the customer can pay. Requires WooCommerce.', 'pn-customers-manager'),
        'parent'        => 'wa_enable_chat_orders',
        'parent_option' => 'on',
      ];

      $fields[] = [
        'id'          => 'wa_include_posts',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Blog posts', 'pn-customers-manager'),
        'description' => __('Includes titles and excerpts of published posts.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'wa_include_pages',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Website pages', 'pn-customers-manager'),
        'description' => __('Includes titles and excerpts of published pages.', 'pn-customers-manager'),
      ];

      $fields[] = [
        'id'           => 'wa_divider_model',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee">',
      ];

      $fields[] = [
        'id'      => 'wa_ai_model',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'type'    => 'select',
        'label'   => __('AI Model', 'pn-customers-manager'),
        'options' => [
          ''             => __('-- Use global --', 'pn-customers-manager'),
          'gpt-4o'       => 'GPT-4o',
          'gpt-4o-mini'  => 'GPT-4o Mini',
          'gpt-4-turbo'  => 'GPT-4 Turbo',
        ],
      ];
      $fields[] = [
        'id'          => 'wa_temperature',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'number',
        'label'       => __('Temperature', 'pn-customers-manager'),
        'min'         => 0,
        'max'         => 2,
        'step'        => 0.1,
        'placeholder' => '0.7',
      ];
      $fields[] = [
        'id'          => 'wa_welcome_message',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Welcome message', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Message sent when starting the conversation...', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'wa_error_fallback_message',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Error fallback message', 'pn-customers-manager'),
        'description' => __('Message sent to the customer when the AI model cannot respond (API error, timeout, invalid key, etc.). Leave empty to use the default message.', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Lo siento, en este momento no puedo responder. Por favor, inténtalo de nuevo más tarde.', 'pn-customers-manager'),
      ];
    }

    // Instagram AI specific fields
    if ($subtype === 'instagram_ai') {
      $fields[] = [
        'id'           => 'ig_heading_ai',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Instagram AI', 'pn-customers-manager') . '</h4>',
      ];

      $fields[] = [
        'id'          => 'ig_system_prompt',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('System prompt', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Override the global prompt for this node...', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'    => 'ig_company_info',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => __('Company information', 'pn-customers-manager'),
        'description' => __('Name, description, address, contact details, etc.', 'pn-customers-manager'),
      ];
      if (class_exists('WooCommerce')) {
        $fields[] = [
          'id'          => 'ig_wc_shipping_zones',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('Use WooCommerce shipping zones', 'pn-customers-manager'),
          'description' => __('When a customer asks about shipping and provides a postal code, the plugin bypasses the AI model and uses WooCommerce\'s own shipping functions to match the correct zone and calculate the available methods and prices. The response is sent directly to the customer, ensuring accurate rates and preventing the model from picking the wrong zone or re-asking for the postal code. Configured shipping zones are also passed to the AI model as fallback context for any other shipping-related questions.', 'pn-customers-manager'),
          'parent'      => 'this',
        ];
        $fields[] = [
          'id'          => 'ig_require_postal_code',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('Always request postal code', 'pn-customers-manager'),
          'description' => __('The AI will always ask for the postal code before quoting a shipping cost.', 'pn-customers-manager'),
          'parent'      => 'this',
        ];
      }
      $fields[] = [
        'id'    => 'ig_shipping_info',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => __('Additional shipping information', 'pn-customers-manager'),
        'description' => __('Extra shipping details: delivery times, special conditions, etc.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'    => 'ig_schedule_info',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => __('Opening hours', 'pn-customers-manager'),
        'description' => __('Business hours, weekdays, weekends, holidays, etc.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'ig_knowledge_base',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'editor',
        'label'       => __('Knowledge base', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Content the AI will use to respond: business info, hours, policies, prices, etc.', 'pn-customers-manager'),
        'description' => __('Additional information the AI will use as reference to respond.', 'pn-customers-manager'),
      ];

      $fields[] = [
        'id'           => 'ig_heading_auto',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Include automatic content', 'pn-customers-manager') . '</h4>',
      ];

      if (class_exists('WooCommerce')) {
        $fields[] = [
          'id'          => 'ig_include_woo',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('WooCommerce products', 'pn-customers-manager'),
          'description' => __('Includes product name, price and purchase link.', 'pn-customers-manager'),
          'parent'      => 'this',
        ];
        $fields[] = [
          'id'            => 'ig_include_woo_variations',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Include product variations', 'pn-customers-manager'),
          'description'   => __('Adds all variations with their attributes, prices and direct purchase links. Warning: this can significantly increase AI API call costs.', 'pn-customers-manager'),
          'parent'        => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'ig_include_woo_add_to_cart',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Direct add to cart links', 'pn-customers-manager'),
          'description'   => __('Product links will add the product to the cart directly. If disabled, links will point to the product page.', 'pn-customers-manager'),
          'parent'        => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'ig_include_woo_categories',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Include product categories', 'pn-customers-manager'),
          'description'   => __('Adds product categories so the AI can classify and reason about product types.', 'pn-customers-manager'),
          'parent'        => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'ig_include_woo_tags',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Include product tags', 'pn-customers-manager'),
          'description'   => __('Adds product tags so the AI can identify product features and characteristics.', 'pn-customers-manager'),
          'parent'        => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'ig_enable_recommendations',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Guided product recommendations', 'pn-customers-manager'),
          'description'   => __('The AI will ask qualifying questions (budget, preferences) before recommending products.', 'pn-customers-manager'),
          'parent'        => 'ig_include_woo',
          'parent_option' => 'on',
        ];
      }

      $fields[] = [
        'id'          => 'ig_include_posts',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Blog posts', 'pn-customers-manager'),
        'description' => __('Includes titles and excerpts of published posts.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'ig_include_pages',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Website pages', 'pn-customers-manager'),
        'description' => __('Includes titles and excerpts of published pages.', 'pn-customers-manager'),
      ];

      $fields[] = [
        'id'           => 'ig_divider_model',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee">',
      ];

      $fields[] = [
        'id'      => 'ig_ai_model',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'type'    => 'select',
        'label'   => __('AI Model', 'pn-customers-manager'),
        'options' => [
          ''             => __('-- Use global --', 'pn-customers-manager'),
          'gpt-4o'       => 'GPT-4o',
          'gpt-4o-mini'  => 'GPT-4o Mini',
          'gpt-4-turbo'  => 'GPT-4 Turbo',
        ],
      ];
      $fields[] = [
        'id'          => 'ig_temperature',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'number',
        'label'       => __('Temperature', 'pn-customers-manager'),
        'min'         => 0,
        'max'         => 2,
        'step'        => 0.1,
        'placeholder' => '0.7',
      ];
      $fields[] = [
        'id'          => 'ig_welcome_message',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Welcome message', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Message sent when starting the conversation...', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'ig_error_fallback_message',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Error fallback message', 'pn-customers-manager'),
        'description' => __('Message sent to the customer when the AI model cannot respond (API error, timeout, invalid key, etc.). Leave empty to use the default message.', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Lo siento, en este momento no puedo responder. Por favor, inténtalo de nuevo más tarde.', 'pn-customers-manager'),
      ];
    }

    return $fields;
  }

  /**
   * Render the settings popup fields HTML for a given subtype.
   * Uses the plugin Forms class for consistent field rendering.
   *
   * @param string $subtype
   * @return string
   */
  public static function render_settings_popup_fields($subtype) {
    $fields = self::get_node_settings_fields($subtype);

    // Extended kses to allow form elements inside html fields (admin-only, developer-controlled content)
    $allowed_html = wp_kses_allowed_html('post');
    $allowed_html['input']  = [
      'type' => true, 'id' => true, 'class' => true, 'style' => true,
      'value' => true, 'readonly' => true, 'name' => true, 'placeholder' => true, 'title' => true,
    ];
    $allowed_html['button'] = [
      'type' => true, 'id' => true, 'class' => true, 'style' => true, 'title' => true,
    ];

    ob_start();
    foreach ($fields as $field) {
      // HTML-only fields (dividers, headings): output directly
      if (isset($field['input']) && $field['input'] === 'html') {
        echo wp_kses($field['html_content'], $allowed_html);
        continue;
      }

      PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder(
        $field,
        'post',  // type context (post meta, but with id=0 returns empty)
        0,       // no post ID — fields will be empty, JS populates them
        0,       // not disabled
        'half'   // half-width layout
      );
    }
    // Save button at the end
    echo '<div class="pn-customers-manager-mt-20 pn-customers-manager-text-align-right">';
    echo '<button type="button" id="pn-cm-fb-settings-popup-save" class="pn-customers-manager-btn pn-customers-manager-btn-primary">';
    echo esc_html__('Save', 'pn-customers-manager');
    echo '</button>';
    echo '</div>';

    return ob_get_clean();
  }
}
