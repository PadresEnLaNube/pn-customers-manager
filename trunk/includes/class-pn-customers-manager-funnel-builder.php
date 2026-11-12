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
      } elseif (json_decode($canvas_data, true) === null) {
        $repaired = preg_replace_callback('/[\x00-\x1f\x7f]/', function($m) {
          $c = $m[0];
          if ($c === "\n") return '\\n';
          if ($c === "\r") return '\\r';
          if ($c === "\t") return '\\t';
          return sprintf('\\u%04x', ord($c));
        }, $canvas_data);
        if (json_decode($repaired, true) !== null) {
          $canvas_data = $repaired;
        }
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
          <span class="pn-cm-fb-topbar-title"><?php esc_html_e('Funnels', 'pn-customers-manager'); ?></span>
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
          <button type="button" id="pn-cm-fb-btn-create" class="pn-cm-fb-topbar-icon-btn" title="<?php esc_attr_e('New funnel', 'pn-customers-manager'); ?>">
            <span class="material-icons-outlined">add</span>
          </button>
          <button type="button" id="pn-cm-fb-btn-rename" class="pn-cm-fb-topbar-icon-btn" title="<?php esc_attr_e('Rename funnel', 'pn-customers-manager'); ?>" <?php echo !$funnel ? 'disabled' : ''; ?>>
            <span class="material-icons-outlined">edit</span>
          </button>
          <button type="button" id="pn-cm-fb-btn-delete" class="pn-cm-fb-topbar-icon-btn pn-cm-fb-topbar-icon-btn--danger" title="<?php esc_attr_e('Delete funnel', 'pn-customers-manager'); ?>" <?php echo !$funnel ? 'disabled' : ''; ?>>
            <span class="material-icons-outlined">delete_outline</span>
          </button>
          <button type="button" id="pn-cm-fb-btn-save" class="pn-cm-fb-topbar-icon-btn pn-cm-fb-topbar-icon-btn--primary" title="<?php esc_attr_e('Save', 'pn-customers-manager'); ?>" <?php echo !$funnel ? 'disabled' : ''; ?>>
            <span class="material-icons-outlined">save</span>
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
            <div class="pn-cm-fb-sidebar-heading"><?php esc_html_e('Automations', 'pn-customers-manager'); ?></div>
            <div class="pn-cm-fb-element" draggable="true" data-type="follow_up" data-subtype="ghl_new_contact">
              <span class="material-icons-outlined">webhook</span>
              <span><?php esc_html_e('GHL New Contact', 'pn-customers-manager'); ?></span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="follow_up" data-subtype="zoho_new_contact">
              <span class="material-icons-outlined">webhook</span>
              <span><?php esc_html_e('Zoho New Contact', 'pn-customers-manager'); ?></span>
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
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="send_email">
              <span class="material-icons-outlined">email</span>
              <span>Email</span>
            </div>
            <div class="pn-cm-fb-element" draggable="true" data-type="communication" data-subtype="email_sequence">
              <span class="material-icons-outlined">forward_to_inbox</span>
              <span><?php esc_html_e('Email sequence', 'pn-customers-manager'); ?></span>
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
            <button type="button" id="pn-cm-fb-multi-select" class="pn-cm-fb-zoom-btn" title="<?php esc_attr_e('Multi-select', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">select_all</span>
            </button>
            <span class="pn-cm-fb-zoom-separator"></span>
            <button type="button" id="pn-cm-fb-undo" class="pn-cm-fb-zoom-btn" title="<?php esc_attr_e('Undo', 'pn-customers-manager'); ?> (Ctrl+Z)" disabled>
              <span class="material-icons-outlined">undo</span>
            </button>
            <button type="button" id="pn-cm-fb-redo" class="pn-cm-fb-zoom-btn" title="<?php esc_attr_e('Redo', 'pn-customers-manager'); ?> (Ctrl+Shift+Z)" disabled>
              <span class="material-icons-outlined">redo</span>
            </button>
            <span class="pn-cm-fb-zoom-separator"></span>
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

            <!-- Shortcode quick-copy (shown for button/form nodes) -->
            <div id="pn-cm-fb-props-shortcode-wrap" style="display:none;">
              <div class="pn-cm-fb-prop-group">
                <label class="pn-cm-fb-prop-label"><?php esc_html_e('Shortcode', 'pn-customers-manager'); ?></label>
                <div class="pn-cm-fb-props-shortcode-row">
                  <input type="text" id="pn-cm-fb-props-shortcode" class="pn-cm-fb-prop-input pn-cm-fb-props-shortcode-input" readonly />
                  <button type="button" id="pn-cm-fb-props-shortcode-copy" class="pn-cm-fb-btn pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="" title="<?php esc_attr_e('Copy', 'pn-customers-manager'); ?>">
                    <span class="material-icons-outlined">content_copy</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Webhook URL quick-copy (shown for ghl/zoho nodes) -->
            <div id="pn-cm-fb-props-webhook-wrap" style="display:none;">
              <div class="pn-cm-fb-prop-group">
                <label class="pn-cm-fb-prop-label"><?php esc_html_e('Webhook URL', 'pn-customers-manager'); ?></label>
                <div class="pn-cm-fb-props-shortcode-row">
                  <input type="text" id="pn-cm-fb-props-webhook-url" class="pn-cm-fb-prop-input pn-cm-fb-props-shortcode-input" readonly />
                  <button type="button" id="pn-cm-fb-props-webhook-copy" class="pn-cm-fb-btn pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="" title="<?php esc_attr_e('Copy', 'pn-customers-manager'); ?>">
                    <span class="material-icons-outlined">content_copy</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Form submissions button (shown for form nodes) -->
            <div id="pn-cm-fb-form-submissions-btn-wrap" style="display:none;">
              <hr class="pn-cm-fb-prop-divider">
              <button type="button" id="pn-cm-fb-form-submissions-btn" class="pn-cm-fb-btn pn-cm-fb-btn-form-submissions">
                <span class="material-icons-outlined">assignment</span>
                <?php esc_html_e('View submissions', 'pn-customers-manager'); ?>
              </button>
            </div>

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
    <div id="pn-cm-fb-wa-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-large pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-disable-esc="true" data-pn-customers-manager-popup-disable-overlay-close="true">
      <div class="pn-customers-manager-p-30 pn-cm-fb-conv-popup-inner">
        <div class="pn-cm-fb-conv-popup-header pn-customers-manager-mb-20">
          <button type="button" id="pn-cm-fb-wa-popup-back" class="pn-cm-fb-conv-popup-back" style="display:none;"><span class="material-icons-outlined">arrow_back</span></button>
          <span class="material-icons-outlined pn-cm-fb-conv-popup-icon">chat</span>
          <h3 id="pn-cm-fb-wa-popup-title" class="pn-customers-manager-mt-0 pn-customers-manager-mb-0"><?php esc_html_e('WhatsApp Conversations', 'pn-customers-manager'); ?></h3>
          <button type="button" id="pn-cm-fb-wa-popup-close" class="pn-customers-manager-popup-close-wrapper"><i class="material-icons-outlined">close</i></button>
        </div>
        <div id="pn-cm-fb-wa-popup-body" class="pn-cm-fb-conv-popup-body">
          <div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>
        </div>
      </div>
    </div>

    <!-- Instagram Chat Popup -->
    <div id="pn-cm-fb-ig-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-large pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-disable-esc="true" data-pn-customers-manager-popup-disable-overlay-close="true">
      <div class="pn-customers-manager-p-30 pn-cm-fb-conv-popup-inner">
        <div class="pn-cm-fb-conv-popup-header pn-customers-manager-mb-20">
          <button type="button" id="pn-cm-fb-ig-popup-back" class="pn-cm-fb-conv-popup-back" style="display:none;"><span class="material-icons-outlined">arrow_back</span></button>
          <span class="material-icons-outlined pn-cm-fb-conv-popup-icon">photo_camera</span>
          <h3 id="pn-cm-fb-ig-popup-title" class="pn-customers-manager-mt-0 pn-customers-manager-mb-0"><?php esc_html_e('Instagram Conversations', 'pn-customers-manager'); ?></h3>
          <button type="button" id="pn-cm-fb-ig-popup-close" class="pn-customers-manager-popup-close-wrapper"><i class="material-icons-outlined">close</i></button>
        </div>
        <div id="pn-cm-fb-ig-popup-body" class="pn-cm-fb-conv-popup-body">
          <div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>
        </div>
      </div>
    </div>

    <!-- Popup overlay (shared) -->
    <div class="pn-customers-manager-popup-overlay pn-customers-manager-display-none-soft"></div>

    <!-- Settings Popup (generic plugin popup) -->
    <div id="pn-cm-fb-settings-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-full-size pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-disable-esc="true" data-pn-customers-manager-popup-disable-overlay-close="true">
      <div class="pn-customers-manager-p-30">
        <div style="display:flex;align-items:center;justify-content:space-between;" class="pn-customers-manager-mb-20">
          <h3 id="pn-cm-fb-settings-popup-title" class="pn-customers-manager-mt-0 pn-customers-manager-mb-0"><?php esc_html_e('Settings', 'pn-customers-manager'); ?></h3>
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
    $instagram_token = get_option('pn_customers_manager_instagram_access_token', '');
    $instagram_page  = get_option('pn_customers_manager_instagram_page_id', '');

    // Check if any AI provider has a configured key
    $has_ai_key = false;
    if (method_exists('PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'get_ai_providers')) {
      foreach (PN_CUSTOMERS_MANAGER_WhatsApp_AI::get_ai_providers() as $provider) {
        if (!empty(get_option($provider['key_option'], ''))) {
          $has_ai_key = true;
          break;
        }
      }
    } else {
      $has_ai_key = !empty(get_option('pn_customers_manager_openai_api_key', ''));
    }

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
        'openai'    => $has_ai_key,
        'instagram' => !empty($instagram_token) && !empty($instagram_page),
        'ghl'       => !empty(get_option('pn_customers_manager_ghl_api_key', '')),
        'zoho'      => class_exists('PN_CUSTOMERS_MANAGER_Zoho') && PN_CUSTOMERS_MANAGER_Zoho::is_connected(),
      ],
      'apiLinks'   => [
        'whatsapp'  => $settings_url . '&section=pn_customers_manager_api_section_start.pn_customers_manager_whatsapp_section_start',
        'openai'    => $settings_url . '&section=pn_customers_manager_api_section_start.pn_customers_manager_openai_section_start',
        'instagram' => $settings_url . '&section=pn_customers_manager_api_section_start.pn_customers_manager_instagram_section_start',
        'ghl'       => $settings_url . '&section=pn_customers_manager_api_section_start.pn_customers_manager_ghl_section_start',
        'zoho'      => $settings_url . '&section=pn_customers_manager_api_section_start.pn_customers_manager_zoho_section_start',
      ],
      'webhookUrls' => [
        'ghl'  => class_exists('PN_CUSTOMERS_MANAGER_GHL') ? PN_CUSTOMERS_MANAGER_GHL::get_webhook_url() : '',
        'zoho' => class_exists('PN_CUSTOMERS_MANAGER_Zoho') ? PN_CUSTOMERS_MANAGER_Zoho::get_webhook_url() : '',
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
        'close'              => __('Close', 'pn-customers-manager'),
        'reset'              => __('Reset', 'pn-customers-manager'),
        'deleteLabel'        => __('Delete', 'pn-customers-manager'),
        'confirmDeleteConv'  => __('Delete this conversation?', 'pn-customers-manager'),
        'confirmReset'       => __('Reset this conversation? All messages will be cleared.', 'pn-customers-manager'),
        'funnelNameRequired' => __('Funnel name is required.', 'pn-customers-manager'),
        'createError'        => __('Error creating funnel.', 'pn-customers-manager'),
        'renamePrompt'       => __('Enter the new funnel name:', 'pn-customers-manager'),
        'renameSuccess'      => __('Funnel renamed successfully.', 'pn-customers-manager'),
        'renameError'        => __('Error renaming funnel.', 'pn-customers-manager'),
        'newFunnelPrompt'    => __('Enter the name for the new funnel:', 'pn-customers-manager'),
        'deleteFunnelConfirm'=> __('Are you sure you want to delete this funnel? This action cannot be undone.', 'pn-customers-manager'),
        'deleteError'        => __('Error deleting funnel.', 'pn-customers-manager'),
        'deleteSuccess'      => __('Funnel deleted successfully.', 'pn-customers-manager'),
        'formFieldLabel'     => __('Label', 'pn-customers-manager'),
        'formFieldType'      => __('Type', 'pn-customers-manager'),
        'formFieldPlaceholder' => __('Placeholder', 'pn-customers-manager'),
        'formFieldRequired'  => __('Required', 'pn-customers-manager'),
        'formFieldOptions'   => __('Options', 'pn-customers-manager'),
        'formFieldRemove'    => __('Remove', 'pn-customers-manager'),
        'formFieldContent'   => __('Content / Instructions', 'pn-customers-manager'),
        'formFieldAcceptTypes' => __('Accepted types (.pdf, .jpg...)', 'pn-customers-manager'),
        'formAddField'       => __('Add field', 'pn-customers-manager'),
        'formOptValue'       => __('Value', 'pn-customers-manager'),
        'formOptLabel'       => __('Label', 'pn-customers-manager'),
        'formAddOption'      => __('Add option', 'pn-customers-manager'),
        // translators: %d is the number of selected elements to delete
        'confirmDeleteMulti' => __('Delete %d selected elements?', 'pn-customers-manager'),
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

    // Re-encode through wp_json_encode with UNESCAPED_UNICODE to preserve
    // emojis and other multi-byte characters properly.
    if ($decoded !== null) {
      $canvas_json = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    update_post_meta($funnel_id, self::CANVAS_META_KEY, wp_slash($canvas_json));

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
    if (!empty($canvas_data) && json_decode($canvas_data, true) === null) {
      $repaired = preg_replace_callback('/[\x00-\x1f\x7f]/', function($m) {
        $c = $m[0];
        if ($c === "\n") return '\\n';
        if ($c === "\r") return '\\r';
        if ($c === "\t") return '\\t';
        return sprintf('\\u%04x', ord($c));
      }, $canvas_data);
      if (json_decode($repaired, true) !== null) {
        $canvas_data = $repaired;
      }
    }

    echo wp_json_encode([
      'error_key'   => '',
      'canvas_data' => $canvas_data ?: '',
      'title'       => $funnel->post_title,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
  }

  /**
   * AJAX: Create new funnel.
   */
  public static function ajax_create_funnel() {
    if (!current_user_can('edit_pn_cm_funnel')) {
      echo wp_json_encode([
        'error_key'     => 'no_permission',
        'error_content' => esc_html__('You do not have permission to create funnels.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $funnel_name = isset($_POST['funnel_name']) ? sanitize_text_field(wp_unslash($_POST['funnel_name'])) : '';

    if (empty($funnel_name)) {
      echo wp_json_encode([
        'error_key'     => 'missing_name',
        'error_content' => esc_html__('Funnel name is required.', 'pn-customers-manager'),
      ]);
      exit;
    }

    // Create the funnel post
    $funnel_id = wp_insert_post([
      'post_type'   => 'pn_cm_funnel',
      'post_title'  => $funnel_name,
      'post_status' => 'publish',
    ]);

    if (is_wp_error($funnel_id)) {
      echo wp_json_encode([
        'error_key'     => 'creation_failed',
        'error_content' => esc_html__('Failed to create funnel.', 'pn-customers-manager'),
      ]);
      exit;
    }

    echo wp_json_encode([
      'error_key'  => '',
      'success'    => true,
      'funnel_id'  => $funnel_id,
      'funnel_url' => admin_url('admin.php?page=pn_customers_manager_funnel_builder&funnel_id=' . $funnel_id),
    ]);
    exit;
  }

  /**
   * AJAX: Delete funnel.
   */
  public static function ajax_delete_funnel() {
    if (!current_user_can('delete_pn_cm_funnel')) {
      echo wp_json_encode([
        'error_key'     => 'no_permission',
        'error_content' => esc_html__('You do not have permission to delete funnels.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $funnel_id = isset($_POST['funnel_id']) ? intval($_POST['funnel_id']) : 0;

    if (!$funnel_id) {
      echo wp_json_encode([
        'error_key'     => 'missing_id',
        'error_content' => esc_html__('Funnel ID is required.', 'pn-customers-manager'),
      ]);
      exit;
    }

    // Verify the funnel exists and is the correct post type
    $funnel = get_post($funnel_id);
    if (!$funnel || $funnel->post_type !== 'pn_cm_funnel') {
      echo wp_json_encode([
        'error_key'     => 'invalid_funnel',
        'error_content' => esc_html__('Invalid funnel.', 'pn-customers-manager'),
      ]);
      exit;
    }

    // Delete the funnel
    $result = wp_delete_post($funnel_id, true);

    if (!$result) {
      echo wp_json_encode([
        'error_key'     => 'deletion_failed',
        'error_content' => esc_html__('Failed to delete funnel.', 'pn-customers-manager'),
      ]);
      exit;
    }

    echo wp_json_encode([
      'error_key' => '',
      'success'   => true,
    ]);
    exit;
  }

  /**
   * AJAX: Rename funnel.
   */
  public static function ajax_rename_funnel() {
    if (!current_user_can('edit_pn_cm_funnel')) {
      echo wp_json_encode([
        'error_key'     => 'no_permission',
        'error_content' => esc_html__('You do not have permission to edit funnels.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $funnel_id   = isset($_POST['funnel_id']) ? intval($_POST['funnel_id']) : 0;
    $funnel_name = isset($_POST['funnel_name']) ? sanitize_text_field(wp_unslash($_POST['funnel_name'])) : '';

    if (!$funnel_id || empty($funnel_name)) {
      echo wp_json_encode([
        'error_key'     => 'missing_data',
        'error_content' => esc_html__('Funnel ID and name are required.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $funnel = get_post($funnel_id);
    if (!$funnel || $funnel->post_type !== 'pn_cm_funnel') {
      echo wp_json_encode([
        'error_key'     => 'invalid_funnel',
        'error_content' => esc_html__('Invalid funnel.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $result = wp_update_post([
      'ID'         => $funnel_id,
      'post_title' => $funnel_name,
    ]);

    if (is_wp_error($result)) {
      echo wp_json_encode([
        'error_key'     => 'rename_failed',
        'error_content' => esc_html__('Failed to rename funnel.', 'pn-customers-manager'),
      ]);
      exit;
    }

    echo wp_json_encode([
      'error_key'   => '',
      'success'     => true,
      'funnel_name' => $funnel_name,
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
      'id'          => 'notes',
      'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input'       => 'textarea',
      'label'       => __('Notes', 'pn-customers-manager'),
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

    // Form specific fields
    if ($subtype === 'form') {
      $fields[] = [
        'id'           => 'form_heading_settings',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Form settings', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'form_title',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'text',
        'label'       => __('Form title', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'form_description',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Form description', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'form_submit_text',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'text',
        'label'       => __('Submit button text', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Submit', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'form_success_message',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'text',
        'label'       => __('Success message', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'form_recipient_email',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'email',
        'label'       => __('Recipient email', 'pn-customers-manager'),
        'placeholder' => 'admin@example.com',
      ];
      $fields[] = [
        'id'     => 'form_honeypot',
        'class'  => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'  => 'input',
        'type'   => 'checkbox',
        'label'  => __('Enable honeypot anti-spam', 'pn-customers-manager'),
      ];

      $fields[] = [
        'id'           => 'form_display_heading',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Display mode', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'      => 'form_display_mode',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'type'    => 'select',
        'label'   => __('Form style', 'pn-customers-manager'),
        'options' => [
          'basic'      => __('Basic', 'pn-customers-manager'),
          'sequential' => __('Sequential', 'pn-customers-manager'),
          'fullscreen' => __('Full screen (Typeform)', 'pn-customers-manager'),
        ],
      ];
      $fields[] = [
        'id'          => 'form_fullscreen_bg',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'color',
        'label'       => __('Background color', 'pn-customers-manager'),
      ];

      // Form fields editor container
      $fields[] = [
        'id'           => 'form_fields_editor',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Form fields', 'pn-customers-manager') . '</h4>'
          . '<div id="pn-cm-fb-form-fields-editor">'
          . '<div class="pn-cm-fb-form-fields-list"></div>'
          . '<button type="button" class="pn-cm-fb-form-add-field-btn pn-cm-fb-btn pn-cm-fb-btn-transparent"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">add</span> ' . esc_html__('Add field', 'pn-customers-manager') . '</button>'
          . '</div>',
      ];

      // Shortcode preview
      $fields[] = [
        'id'           => 'form_shortcode_preview',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Shortcode', 'pn-customers-manager') . '</h4>'
          . '<div style="display:flex;gap:8px;align-items:center">'
          . '<input type="text" id="pn-cm-fb-form-shortcode" class="pn-customers-manager-input pn-customers-manager-width-80-percent" readonly style="margin-bottom:0;font-size:12px;font-family:monospace" value="" />'
          . '<button type="button" id="pn-cm-fb-form-shortcode-copy" class="pn-customers-manager-btn pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="" style="white-space:nowrap;padding:10px 16px;font-size:13px" title="' . esc_attr__('Copy', 'pn-customers-manager') . '"><i class="material-icons-outlined" style="font-size:18px;vertical-align:middle">content_copy</i></button>'
          . '</div>',
      ];
    }

    // GHL New Contact specific fields
    if ($subtype === 'ghl_new_contact') {
      $fields[] = [
        'id'           => 'ghl_heading_settings',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('GHL New Contact', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'           => 'ghl_webhook_url_display',
        'input'        => 'html',
        'html_content' => '<div style="margin-bottom:12px"><label class="pn-customers-manager-label">' . esc_html__('Webhook URL', 'pn-customers-manager') . '</label>'
          . '<div style="display:flex;gap:8px;align-items:center">'
          . '<input type="text" class="pn-customers-manager-input pn-customers-manager-width-80-percent" readonly style="margin-bottom:0;font-size:12px;font-family:monospace" value="' . esc_attr(class_exists('PN_CUSTOMERS_MANAGER_GHL') ? PN_CUSTOMERS_MANAGER_GHL::get_webhook_url() : '') . '" />'
          . '<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="' . esc_attr(class_exists('PN_CUSTOMERS_MANAGER_GHL') ? PN_CUSTOMERS_MANAGER_GHL::get_webhook_url() : '') . '" style="white-space:nowrap;padding:10px 16px;font-size:13px" title="' . esc_attr__('Copy', 'pn-customers-manager') . '"><i class="material-icons-outlined" style="font-size:18px;vertical-align:middle">content_copy</i></button>'
          . '</div>'
          . '<p class="description" style="margin-top:4px">' . esc_html__('Configure this URL as a webhook in your GHL account to receive new contacts.', 'pn-customers-manager') . '</p>'
          . '</div>',
      ];
      $fields[] = [
        'id'     => 'ghl_save_to_wp',
        'class'  => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'  => 'input',
        'type'   => 'checkbox',
        'label'  => __('Save contact to WordPress', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'     => 'ghl_send_email',
        'class'  => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'  => 'input',
        'type'   => 'checkbox',
        'label'  => __('Send notification email', 'pn-customers-manager'),
        'parent' => 'this',
      ];
      $fields[] = [
        'id'              => 'ghl_notify_email',
        'class'           => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'           => 'input',
        'type'            => 'email',
        'label'           => __('Notification email', 'pn-customers-manager'),
        'placeholder'     => 'admin@example.com',
        'parent'          => 'ghl_send_email',
        'parent_option'   => 'on',
      ];
      $fields[] = [
        'id'              => 'ghl_notify_subject',
        'class'           => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'           => 'input',
        'type'            => 'text',
        'label'           => __('Email subject', 'pn-customers-manager'),
        'placeholder'     => __('New GHL Contact: {name}', 'pn-customers-manager'),
        'description'     => __('Use {name} as a placeholder for the contact name.', 'pn-customers-manager'),
        'parent'          => 'ghl_send_email',
        'parent_option'   => 'on',
      ];
    }

    // Zoho New Contact specific fields
    if ($subtype === 'zoho_new_contact') {
      $fields[] = [
        'id'           => 'zoho_heading_settings',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Zoho New Contact', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'           => 'zoho_webhook_url_display',
        'input'        => 'html',
        'html_content' => '<div style="margin-bottom:12px"><label class="pn-customers-manager-label">' . esc_html__('Webhook URL', 'pn-customers-manager') . '</label>'
          . '<div style="display:flex;gap:8px;align-items:center">'
          . '<input type="text" class="pn-customers-manager-input pn-customers-manager-width-80-percent" readonly style="margin-bottom:0;font-size:12px;font-family:monospace" value="' . esc_attr(class_exists('PN_CUSTOMERS_MANAGER_Zoho') ? PN_CUSTOMERS_MANAGER_Zoho::get_webhook_url() : '') . '" />'
          . '<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="' . esc_attr(class_exists('PN_CUSTOMERS_MANAGER_Zoho') ? PN_CUSTOMERS_MANAGER_Zoho::get_webhook_url() : '') . '" style="white-space:nowrap;padding:10px 16px;font-size:13px" title="' . esc_attr__('Copy', 'pn-customers-manager') . '"><i class="material-icons-outlined" style="font-size:18px;vertical-align:middle">content_copy</i></button>'
          . '</div>'
          . '<p class="description" style="margin-top:4px">' . esc_html__('Configure this URL as a webhook in your Zoho CRM account to receive new contacts.', 'pn-customers-manager') . '</p>'
          . '</div>',
      ];
      $fields[] = [
        'id'     => 'zoho_save_to_wp',
        'class'  => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'  => 'input',
        'type'   => 'checkbox',
        'label'  => __('Save contact to WordPress', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'     => 'zoho_send_email',
        'class'  => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'  => 'input',
        'type'   => 'checkbox',
        'label'  => __('Send notification email', 'pn-customers-manager'),
        'parent' => 'this',
      ];
      $fields[] = [
        'id'              => 'zoho_notify_email',
        'class'           => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'           => 'input',
        'type'            => 'email',
        'label'           => __('Notification email', 'pn-customers-manager'),
        'placeholder'     => 'admin@example.com',
        'parent'          => 'zoho_send_email',
        'parent_option'   => 'on',
      ];
      $fields[] = [
        'id'              => 'zoho_notify_subject',
        'class'           => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'           => 'input',
        'type'            => 'text',
        'label'           => __('Email subject', 'pn-customers-manager'),
        'placeholder'     => __('New Zoho Contact: {name}', 'pn-customers-manager'),
        'description'     => __('Use {name} as a placeholder for the contact name.', 'pn-customers-manager'),
        'parent'          => 'zoho_send_email',
        'parent_option'   => 'on',
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
        'placeholder' => esc_attr__('Sorry, I cannot respond right now. Please try again later.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'wa_system_prompt',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('System prompt', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Override the global prompt for this node...', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'wa_assistant_personality',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Assistant personality', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Override the global personality for this node...', 'pn-customers-manager'),
        'description' => __('Tone, emotional style, language register, and personality traits.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'           => 'wa_heading_template',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Meta message templates', 'pn-customers-manager') . '</h4><p style="margin:0 0 8px;font-size:12px;color:#666">' . sprintf(
          /* translators: %s: URL to Meta Business Suite template manager */
          __('Send pre-approved WhatsApp template messages with configurable triggers. Manage your templates in <a href="%s" target="_blank">Meta Business Suite</a>.', 'pn-customers-manager'),
          'https://business.facebook.com/latest/whatsapp_manager/message_templates'
        ) . '</p>',
      ];
      $fields[] = [
        'id'          => 'wa_meta_templates_enabled',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Enable Meta message templates', 'pn-customers-manager'),
        'description' => __('Configure one or more pre-approved templates with different trigger conditions.', 'pn-customers-manager'),
        'parent'      => 'this',
      ];
      $fields[] = [
        'id'                => 'wa_meta_templates',
        'class'             => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'             => 'html_multi',
        'label'             => __('Templates', 'pn-customers-manager'),
        'description'       => __('Each row is a pre-approved template. Set the trigger to control when it is sent automatically.', 'pn-customers-manager'),
        'parent'            => 'wa_meta_templates_enabled',
        'parent_option'     => 'on',
        'html_multi_fields' => [
          [
            'id'          => 'wa_meta_template_name',
            'class'       => 'pn-customers-manager-input',
            'input'       => 'input',
            'type'        => 'text',
            'label'       => __('Template name', 'pn-customers-manager'),
            'placeholder' => 'true_atalaya_wa_welcome',
          ],
          [
            'id'          => 'wa_meta_template_language',
            'class'       => 'pn-customers-manager-input',
            'input'       => 'input',
            'type'        => 'text',
            'label'       => __('Language code', 'pn-customers-manager'),
            'placeholder' => 'es',
          ],
          [
            'id'          => 'wa_meta_template_description',
            'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
            'input'       => 'textarea',
            'label'       => __('Description / preview', 'pn-customers-manager'),
            'placeholder' => __('What this template says (for your reference)', 'pn-customers-manager'),
          ],
          [
            'id'    => 'wa_meta_template_use_vars',
            'class' => 'pn-customers-manager-checkbox',
            'input' => 'checkbox',
            'label' => __('Use dynamic variables', 'pn-customers-manager'),
          ],
          [
            'id'      => 'wa_meta_template_vars_mode',
            'class'   => 'pn-customers-manager-select',
            'input'   => 'select',
            'label'   => __('Variables mode', 'pn-customers-manager'),
            'parent'        => 'wa_meta_template_use_vars',
            'parent_option' => 'on',
            'options' => [
              'numbers' => __('By numbers ({{1}}, {{2}}, {{3}}...)', 'pn-customers-manager'),
              'names'   => __('By names ({{display_name}}, {{email}}...)', 'pn-customers-manager'),
            ],
          ],
          [
            'id'          => 'wa_meta_template_vars_config',
            'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
            'input'       => 'template_vars_builder',
            'label'       => __('Variables configuration', 'pn-customers-manager'),
            'description' => __('Configure the variables for this template. The order matters: variables will be sent to Meta in the order shown below.', 'pn-customers-manager'),
            'parent'        => 'wa_meta_template_use_vars',
            'parent_option' => 'on',
          ],
          [
            'id'      => 'wa_meta_template_trigger',
            'class'   => 'pn-customers-manager-select',
            'input'   => 'select',
            'type'    => 'select',
            'label'   => __('Trigger', 'pn-customers-manager'),
            'options' => [
              'on_registration' => __('Immediately on registration', 'pn-customers-manager'),
              'after_delay'     => __('After a delay from registration', 'pn-customers-manager'),
              'on_inactivity'   => __('On client inactivity', 'pn-customers-manager'),
              'manual'          => __('Manual only', 'pn-customers-manager'),
            ],
          ],
          [
            'id'          => 'wa_meta_template_delay',
            'class'       => 'pn-customers-manager-input pn-cm-template-delay-field',
            'input'       => 'input',
            'type'        => 'number',
            'label'       => __('Delay amount', 'pn-customers-manager'),
            'placeholder' => '30',
            'min'         => 1,
          ],
          [
            'id'      => 'wa_meta_template_delay_unit',
            'class'   => 'pn-customers-manager-select pn-cm-template-delay-field',
            'input'   => 'select',
            'type'    => 'select',
            'label'   => __('Delay unit', 'pn-customers-manager'),
            'options' => [
              'minutes' => __('Minutes', 'pn-customers-manager'),
              'hours'   => __('Hours', 'pn-customers-manager'),
              'days'    => __('Days', 'pn-customers-manager'),
            ],
          ],
        ],
      ];

      $fields[] = [
        'id'          => 'wa_audio_transcription_enabled',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Enable audio transcription', 'pn-customers-manager'),
        'description' => __('Automatically transcribe voice notes and process them as text messages.', 'pn-customers-manager'),
        'parent'      => 'this',
      ];

      $transcription_providers = PN_CUSTOMERS_MANAGER_WhatsApp_AI::get_transcription_providers();
      $provider_options = [];
      foreach ($transcription_providers as $key => $p) {
        $provider_options[$key] = $p['label'];
      }
      $fields[] = [
        'id'            => 'wa_transcription_provider',
        'class'         => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'         => 'select',
        'type'          => 'select',
        'label'         => __('Transcription provider', 'pn-customers-manager'),
        'options'       => $provider_options,
        'parent'        => 'wa_audio_transcription_enabled',
        'parent_option' => 'on',
      ];
      // Per-provider API key warnings (only rendered when the key is missing)
      $settings_url = add_query_arg([
        'page'    => 'pn_customers_manager_options',
        'section' => 'pn_customers_manager_api_parent_section_start.pn_customers_manager_openai_section_start',
      ], admin_url('admin.php'));
      $wa_key_warning = '<div style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:6px;background:#fef3c7;color:#92400e;font-size:13px">'
        . '<span class="material-icons-outlined" style="font-size:16px">warning</span>'
        . '%s — '
        . '<a href="' . esc_url($settings_url) . '" target="_blank" style="color:#92400e;font-weight:500">'
        . esc_html__('Configure in Settings', 'pn-customers-manager') . '</a></div>';

      foreach ($transcription_providers as $pkey => $prov) {
        $key_value = get_option($prov['key_option'], '');
        if (empty($key_value)) {
          $fields[] = [
            'id'            => 'wa_transcription_warn_' . $pkey,
            'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
            'input'         => 'html',
            'html_content'  => sprintf($wa_key_warning, sprintf(
              /* translators: %s: provider name */
              esc_html__('%s API key not configured', 'pn-customers-manager'),
              esc_html($prov['label'])
            )),
            'parent'        => 'wa_transcription_provider',
            'parent_option' => $pkey,
          ];
        }
      }

      $fields[] = [
        'id'            => 'wa_transcription_language',
        'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'         => 'input',
        'type'          => 'text',
        'label'         => __('Transcription language', 'pn-customers-manager'),
        'placeholder'   => 'es',
        'description'   => __('ISO 639-1 code (e.g. <code>es</code>, <code>en</code>). Optional — improves accuracy.', 'pn-customers-manager'),
        'parent'        => 'wa_audio_transcription_enabled',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'            => 'wa_transcription_settings_link',
        'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'         => 'html',
        'html_content'  => '<a href="' . esc_url($settings_url) . '" target="_blank" style="display:inline-flex;align-items:center;gap:4px;font-size:13px;color:var(--pn-customers-manager-color-main,#4f46e5)">'
          . '<span class="material-icons-outlined" style="font-size:16px">settings</span>'
          . esc_html__('Configure API keys in Settings', 'pn-customers-manager')
          . '</a>',
        'parent'        => 'wa_audio_transcription_enabled',
        'parent_option' => 'on',
      ];

      // ── Context ──
      $fields[] = [
        'id'           => 'wa_heading_context',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Context', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'    => 'wa_company_info',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => __('Company information', 'pn-customers-manager'),
        'description' => __('Name, description, address, contact details, etc.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'wa_knowledge_base',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'editor',
        'label'       => __('Knowledge base', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Content the AI will use to respond: business info, hours, policies, prices, etc.', 'pn-customers-manager'),
        'description' => __('Additional information the AI will use as reference to respond.', 'pn-customers-manager'),
      ];

      // ── Follow-up tracking ──
      $fields[] = [
        'id'           => 'wa_heading_followup',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Follow-up tracking', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'wa_enable_followup_tracking',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Enable follow-up tracking', 'pn-customers-manager'),
        'description' => __('The AI will detect patterns in the conversation that require a follow-up action (e.g. watch a video, write a review, check a document) and tag them with a hidden marker so the system can schedule a reminder.', 'pn-customers-manager'),
        'parent'      => 'this',
      ];
      $fields[] = [
        'id'                => 'wa_followup_listeners',
        'class'             => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'             => 'html_multi',
        'label'             => __('Follow-up listeners', 'pn-customers-manager'),
        'description'       => __('Define the actions the AI should detect. Each listener has a key (used internally), a label describing the action, and a delay before the reminder is sent.', 'pn-customers-manager'),
        'parent'            => 'wa_enable_followup_tracking',
        'parent_option'     => 'on',
        'html_multi_fields' => [
          [
            'id'          => 'wa_followup_key',
            'class'       => 'pn-customers-manager-input',
            'input'       => 'input',
            'type'        => 'text',
            'label'       => __('Key', 'pn-customers-manager'),
            'placeholder' => __('e.g. watch_video', 'pn-customers-manager'),
          ],
          [
            'id'          => 'wa_followup_label',
            'class'       => 'pn-customers-manager-input',
            'input'       => 'input',
            'type'        => 'text',
            'label'       => __('Description', 'pn-customers-manager'),
            'placeholder' => __('e.g. Customer should watch the tutorial video', 'pn-customers-manager'),
          ],
          [
            'id'          => 'wa_followup_delay',
            'class'       => 'pn-customers-manager-input',
            'input'       => 'input',
            'type'        => 'number',
            'label'       => __('Delay', 'pn-customers-manager'),
            'placeholder' => '24',
            'min'         => 1,
          ],
          [
            'id'      => 'wa_followup_delay_unit',
            'class'   => 'pn-customers-manager-select',
            'input'   => 'select',
            'type'    => 'select',
            'label'   => __('Unit', 'pn-customers-manager'),
            'options' => [
              'hours'  => __('Hours', 'pn-customers-manager'),
              'days'   => __('Days', 'pn-customers-manager'),
              'weeks'  => __('Weeks', 'pn-customers-manager'),
              'months' => __('Months', 'pn-customers-manager'),
            ],
          ],
        ],
      ];

      // ── Bot schedule ──
      $fields[] = [
        'id'           => 'wa_heading_schedule',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Bot schedule', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'wa_schedule_enabled',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Enable message scheduling', 'pn-customers-manager'),
        'description' => __('When enabled, bot messages will only be sent during the configured time windows. Messages outside these windows are queued and delivered at the next allowed slot.', 'pn-customers-manager'),
        'parent'      => 'this',
      ];
      $fields[] = [
        'id'           => 'wa_schedule_days',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => self::render_schedule_days_html(),
        'parent'        => 'wa_schedule_enabled',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'          => 'wa_schedule_offhours_message_enabled',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Send custom message outside scheduled hours', 'pn-customers-manager'),
        'description' => __('When enabled, the bot will send a custom message to clients who initiate a conversation outside the scheduled hours, instead of staying silent.', 'pn-customers-manager'),
        'parent'        => 'wa_schedule_enabled',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'          => 'wa_schedule_offhours_message',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Off-hours message', 'pn-customers-manager'),
        'placeholder' => __('e.g. Thanks for reaching out! We are currently outside our business hours. We will get back to you as soon as possible.', 'pn-customers-manager'),
        'parent'        => 'wa_schedule_offhours_message_enabled',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'          => 'wa_schedule_allow_client_initiated',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Allow bot to respond when client initiates', 'pn-customers-manager'),
        'description' => __('When enabled, the bot responds normally outside scheduled hours if it is the client who initiates the conversation. Mutually exclusive with the custom off-hours message option.', 'pn-customers-manager'),
        'parent'        => 'wa_schedule_enabled',
        'parent_option' => 'on',
      ];

      // ── CRM sync ──
      $fields[] = [
        'id'           => 'wa_heading_crm_sync',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('CRM sync', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'wa_sync_ghl',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Sync with GoHighLevel', 'pn-customers-manager'),
        'description' => __('Save conversation interactions, extracted tasks, and follow-up reminders to GoHighLevel via the API. Requires a valid GHL API key in the Integration settings.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'wa_sync_zoho',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Sync with Zoho CRM', 'pn-customers-manager'),
        'description' => __('Save conversation interactions, extracted tasks, and follow-up reminders to Zoho CRM via the API. Requires a valid Zoho connection in the Integration settings.', 'pn-customers-manager'),
      ];

      // ── Include automatic content ──
      $fields[] = [
        'id'           => 'wa_heading_auto',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Include automatic content', 'pn-customers-manager') . '</h4>',
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
        // Build WooCommerce category options for the multi-select filter
        $wa_woo_cat_options = [];
        $wa_woo_cats = get_terms([
          'taxonomy'   => 'product_cat',
          'hide_empty' => true,
          'orderby'    => 'name',
          'order'      => 'ASC',
        ]);
        if (!is_wp_error($wa_woo_cats)) {
          foreach ($wa_woo_cats as $wa_woo_cat) {
            $wa_woo_cat_options[$wa_woo_cat->term_id] = $wa_woo_cat->name . ' (' . $wa_woo_cat->count . ')';
          }
        }
        $fields[] = [
          'id'            => 'wa_woo_categories_filter',
          'class'         => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
          'input'         => 'select',
          'multiple'      => true,
          'label'         => __('Filter by categories', 'pn-customers-manager'),
          'description'   => __('Select which product categories to include. Leave empty to include all categories.', 'pn-customers-manager'),
          'options'       => $wa_woo_cat_options,
          'parent'        => 'wa_include_woo_categories',
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
          'id'            => 'wa_disable_recommendations',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Disable guided recommendations', 'pn-customers-manager'),
          'description'   => __('By default, the AI asks qualifying questions (budget, preferences) before recommending products. Check this to skip the guided flow and show products immediately.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'wa_enable_chat_orders',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Accept orders via chat', 'pn-customers-manager'),
          'description'   => __('Allow the AI to confirm orders during WhatsApp conversations and send an email notification.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
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
          'id'            => 'wa_enable_special_orders',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Forward special orders by email', 'pn-customers-manager'),
          'description'   => __('When the AI detects a request that cannot be fulfilled with a simple purchase link (B2B, bulk, custom products, special shipping), it will collect the details and forward them by email to the selected recipients.', 'pn-customers-manager'),
          'parent'        => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $wa_special_orders_user_options = [];
        $wa_special_orders_all_users    = get_users([
          'fields'  => ['ID', 'display_name', 'user_email'],
          'orderby' => 'display_name',
          'order'   => 'ASC',
        ]);
        foreach ($wa_special_orders_all_users as $wa_special_orders_user) {
          if (empty($wa_special_orders_user->user_email)) {
            continue;
          }
          $wa_special_orders_user_options[$wa_special_orders_user->ID] = $wa_special_orders_user->display_name . ' (' . $wa_special_orders_user->user_email . ')';
        }
        $fields[] = [
          'id'            => 'wa_special_orders_users',
          'class'         => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
          'input'         => 'select',
          'multiple'      => true,
          'label'         => __('Special order recipients (platform users)', 'pn-customers-manager'),
          'description'   => __('Select platform users who will receive special order notifications by email. Leave empty to use only external emails or the site admin email.', 'pn-customers-manager'),
          'options'       => $wa_special_orders_user_options,
          'parent'        => 'wa_enable_special_orders',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'                => 'wa_special_orders_external_emails_wrapper',
          'class'             => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'             => 'html_multi',
          'label'             => __('Special order recipients (external emails)', 'pn-customers-manager'),
          'description'       => __('Add email addresses for people who are not registered users on this site.', 'pn-customers-manager'),
          'html_multi_fields' => [
            [
              'id'          => 'wa_special_orders_external_emails',
              'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
              'input'       => 'input',
              'type'        => 'email',
              'placeholder' => 'email@example.com',
            ],
          ],
          'parent'        => 'wa_enable_special_orders',
          'parent_option' => 'on',
        ];
        // Shipping fields (under WooCommerce products)
        $fields[] = [
          'id'          => 'wa_wc_shipping_zones',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('Use WooCommerce shipping zones', 'pn-customers-manager'),
          'description' => __('When a customer asks about shipping and provides a postal code, the plugin bypasses the AI model and uses WooCommerce\'s own shipping functions to match the correct zone and calculate the available methods and prices. The response is sent directly to the customer, ensuring accurate rates and preventing the model from picking the wrong zone or re-asking for the postal code. Configured shipping zones are also passed to the AI model as fallback context for any other shipping-related questions.', 'pn-customers-manager'),
          'parent'      => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'          => 'wa_require_postal_code',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('Always request postal code', 'pn-customers-manager'),
          'description' => __('The AI will always ask for the postal code before quoting a shipping cost.', 'pn-customers-manager'),
          'parent'      => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'    => 'wa_shipping_info',
          'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input' => 'editor',
          'label' => __('Additional shipping information', 'pn-customers-manager'),
          'description' => __('Extra shipping details: delivery times, special conditions, etc.', 'pn-customers-manager'),
          'parent'      => 'wa_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'          => 'wa_schedule_info',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'editor',
          'label'       => __('Opening hours', 'pn-customers-manager'),
          'description' => __('Business hours, weekdays, weekends, holidays, etc.', 'pn-customers-manager'),
          'parent'      => 'wa_include_woo',
          'parent_option' => 'on',
        ];
      }

      // ── Model configuration ──
      $fields[] = [
        'id'           => 'wa_divider_model',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Model configuration', 'pn-customers-manager') . '</h4>',
      ];

      $fields[] = [
        'id'      => 'wa_ai_model',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'type'    => 'select',
        'label'   => __('AI Model', 'pn-customers-manager'),
        'options' => PN_CUSTOMERS_MANAGER_WhatsApp_AI::get_all_model_options(true),
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
        'placeholder' => esc_attr__('Sorry, I cannot respond right now. Please try again later.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'ig_system_prompt',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('System prompt', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Override the global prompt for this node...', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'ig_assistant_personality',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Assistant personality', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Override the global personality for this node...', 'pn-customers-manager'),
        'description' => __('Tone, emotional style, language register, and personality traits.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'ig_audio_transcription_enabled',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Enable audio transcription', 'pn-customers-manager'),
        'description' => __('Automatically transcribe voice notes and process them as text messages.', 'pn-customers-manager'),
        'parent'      => 'this',
      ];

      if (!isset($transcription_providers)) {
        $transcription_providers = PN_CUSTOMERS_MANAGER_WhatsApp_AI::get_transcription_providers();
        $provider_options = [];
        foreach ($transcription_providers as $key => $p) {
          $provider_options[$key] = $p['label'];
        }
      }
      $fields[] = [
        'id'            => 'ig_transcription_provider',
        'class'         => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'         => 'select',
        'type'          => 'select',
        'label'         => __('Transcription provider', 'pn-customers-manager'),
        'options'       => $provider_options,
        'parent'        => 'ig_audio_transcription_enabled',
        'parent_option' => 'on',
      ];

      $ig_key_warning = '<div style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:6px;background:#fef3c7;color:#92400e;font-size:13px">'
        . '<span class="material-icons-outlined" style="font-size:16px">warning</span>'
        . '%s — '
        . '<a href="' . esc_url($settings_url) . '" target="_blank" style="color:#92400e;font-weight:500">'
        . esc_html__('Configure in Settings', 'pn-customers-manager') . '</a></div>';

      foreach ($transcription_providers as $pkey => $prov) {
        $key_value = get_option($prov['key_option'], '');
        if (empty($key_value)) {
          $fields[] = [
            'id'            => 'ig_transcription_warn_' . $pkey,
            'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
            'input'         => 'html',
            'html_content'  => sprintf($ig_key_warning, sprintf(
              /* translators: %s: provider name */
              esc_html__('%s API key not configured', 'pn-customers-manager'),
              esc_html($prov['label'])
            )),
            'parent'        => 'ig_transcription_provider',
            'parent_option' => $pkey,
          ];
        }
      }

      $fields[] = [
        'id'            => 'ig_transcription_language',
        'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'         => 'input',
        'type'          => 'text',
        'label'         => __('Transcription language', 'pn-customers-manager'),
        'placeholder'   => 'es',
        'description'   => __('ISO 639-1 code (e.g. <code>es</code>, <code>en</code>). Optional — improves accuracy.', 'pn-customers-manager'),
        'parent'        => 'ig_audio_transcription_enabled',
        'parent_option' => 'on',
      ];
      if (!isset($settings_url)) {
        $settings_url = add_query_arg([
          'page'    => 'pn_customers_manager_options',
          'section' => 'pn_customers_manager_api_parent_section_start.pn_customers_manager_openai_section_start',
        ], admin_url('admin.php'));
      }
      $fields[] = [
        'id'            => 'ig_transcription_settings_link',
        'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'         => 'html',
        'html_content'  => '<a href="' . esc_url($settings_url) . '" target="_blank" style="display:inline-flex;align-items:center;gap:4px;font-size:13px;color:var(--pn-customers-manager-color-main,#4f46e5)">'
          . '<span class="material-icons-outlined" style="font-size:16px">settings</span>'
          . esc_html__('Configure API keys in Settings', 'pn-customers-manager')
          . '</a>',
        'parent'        => 'ig_audio_transcription_enabled',
        'parent_option' => 'on',
      ];

      // ── Context ──
      $fields[] = [
        'id'           => 'ig_heading_context',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Context', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'    => 'ig_company_info',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => __('Company information', 'pn-customers-manager'),
        'description' => __('Name, description, address, contact details, etc.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'ig_knowledge_base',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'editor',
        'label'       => __('Knowledge base', 'pn-customers-manager'),
        'placeholder' => esc_attr__('Content the AI will use to respond: business info, hours, policies, prices, etc.', 'pn-customers-manager'),
        'description' => __('Additional information the AI will use as reference to respond.', 'pn-customers-manager'),
      ];

      // ── Follow-up tracking ──
      $fields[] = [
        'id'           => 'ig_heading_followup',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Follow-up tracking', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'ig_enable_followup_tracking',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Enable follow-up tracking', 'pn-customers-manager'),
        'description' => __('The AI will detect patterns in the conversation that require a follow-up action (e.g. watch a video, write a review, check a document) and tag them with a hidden marker so the system can schedule a reminder.', 'pn-customers-manager'),
        'parent'      => 'this',
      ];
      $fields[] = [
        'id'                => 'ig_followup_listeners',
        'class'             => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'             => 'html_multi',
        'label'             => __('Follow-up listeners', 'pn-customers-manager'),
        'description'       => __('Define the actions the AI should detect. Each listener has a key (used internally), a label describing the action, and a delay before the reminder is sent.', 'pn-customers-manager'),
        'parent'            => 'ig_enable_followup_tracking',
        'parent_option'     => 'on',
        'html_multi_fields' => [
          [
            'id'          => 'ig_followup_key',
            'class'       => 'pn-customers-manager-input',
            'input'       => 'input',
            'type'        => 'text',
            'label'       => __('Key', 'pn-customers-manager'),
            'placeholder' => __('e.g. watch_video', 'pn-customers-manager'),
          ],
          [
            'id'          => 'ig_followup_label',
            'class'       => 'pn-customers-manager-input',
            'input'       => 'input',
            'type'        => 'text',
            'label'       => __('Description', 'pn-customers-manager'),
            'placeholder' => __('e.g. Customer should watch the tutorial video', 'pn-customers-manager'),
          ],
          [
            'id'          => 'ig_followup_delay',
            'class'       => 'pn-customers-manager-input',
            'input'       => 'input',
            'type'        => 'number',
            'label'       => __('Delay', 'pn-customers-manager'),
            'placeholder' => '24',
            'min'         => 1,
          ],
          [
            'id'      => 'ig_followup_delay_unit',
            'class'   => 'pn-customers-manager-select',
            'input'   => 'select',
            'type'    => 'select',
            'label'   => __('Unit', 'pn-customers-manager'),
            'options' => [
              'hours'  => __('Hours', 'pn-customers-manager'),
              'days'   => __('Days', 'pn-customers-manager'),
              'weeks'  => __('Weeks', 'pn-customers-manager'),
              'months' => __('Months', 'pn-customers-manager'),
            ],
          ],
        ],
      ];

      // ── Bot schedule ──
      $fields[] = [
        'id'           => 'ig_heading_schedule',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Bot schedule', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'ig_schedule_enabled',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Enable message scheduling', 'pn-customers-manager'),
        'description' => __('When enabled, bot messages will only be sent during the configured time windows.', 'pn-customers-manager'),
        'parent'      => 'this',
      ];
      $fields[] = [
        'id'           => 'ig_schedule_days',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => self::render_schedule_days_html('ig'),
        'parent'        => 'ig_schedule_enabled',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'          => 'ig_schedule_offhours_message_enabled',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Send custom message outside scheduled hours', 'pn-customers-manager'),
        'description' => __('When enabled, the bot will send a custom message to clients who initiate a conversation outside the scheduled hours, instead of staying silent.', 'pn-customers-manager'),
        'parent'        => 'ig_schedule_enabled',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'          => 'ig_schedule_offhours_message',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Off-hours message', 'pn-customers-manager'),
        'placeholder' => __('e.g. Thanks for reaching out! We are currently outside our business hours. We will get back to you as soon as possible.', 'pn-customers-manager'),
        'parent'        => 'ig_schedule_offhours_message_enabled',
        'parent_option' => 'on',
      ];
      $fields[] = [
        'id'          => 'ig_schedule_allow_client_initiated',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Allow bot to respond when client initiates', 'pn-customers-manager'),
        'description' => __('When enabled, the bot responds normally outside scheduled hours if it is the client who initiates the conversation. Mutually exclusive with the custom off-hours message option.', 'pn-customers-manager'),
        'parent'        => 'ig_schedule_enabled',
        'parent_option' => 'on',
      ];

      // ── CRM sync ──
      $fields[] = [
        'id'           => 'ig_heading_crm_sync',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('CRM sync', 'pn-customers-manager') . '</h4>',
      ];
      $fields[] = [
        'id'          => 'ig_sync_ghl',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Sync with GoHighLevel', 'pn-customers-manager'),
        'description' => __('Save conversation interactions, extracted tasks, and follow-up reminders to GoHighLevel via the API. Requires a valid GHL API key in the Integration settings.', 'pn-customers-manager'),
      ];
      $fields[] = [
        'id'          => 'ig_sync_zoho',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'checkbox',
        'label'       => __('Sync with Zoho CRM', 'pn-customers-manager'),
        'description' => __('Save conversation interactions, extracted tasks, and follow-up reminders to Zoho CRM via the API. Requires a valid Zoho connection in the Integration settings.', 'pn-customers-manager'),
      ];

      // ── Include automatic content ──
      $fields[] = [
        'id'           => 'ig_heading_auto',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Include automatic content', 'pn-customers-manager') . '</h4>',
      ];

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
        $ig_woo_cat_options = [];
        $ig_woo_cats = get_terms([
          'taxonomy'   => 'product_cat',
          'hide_empty' => true,
          'orderby'    => 'name',
          'order'      => 'ASC',
        ]);
        if (!is_wp_error($ig_woo_cats)) {
          foreach ($ig_woo_cats as $ig_woo_cat) {
            $ig_woo_cat_options[$ig_woo_cat->term_id] = $ig_woo_cat->name . ' (' . $ig_woo_cat->count . ')';
          }
        }
        $fields[] = [
          'id'            => 'ig_woo_categories_filter',
          'class'         => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
          'input'         => 'select',
          'multiple'      => true,
          'label'         => __('Filter by categories', 'pn-customers-manager'),
          'description'   => __('Select which product categories to include. Leave empty to include all categories.', 'pn-customers-manager'),
          'options'       => $ig_woo_cat_options,
          'parent'        => 'ig_include_woo_categories',
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
          'id'            => 'ig_disable_recommendations',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Disable guided recommendations', 'pn-customers-manager'),
          'description'   => __('By default, the AI asks qualifying questions (budget, preferences) before recommending products. Check this to skip the guided flow and show products immediately.', 'pn-customers-manager'),
          'parent'        => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'            => 'ig_enable_special_orders',
          'class'         => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'         => 'input',
          'type'          => 'checkbox',
          'label'         => __('Forward special orders by email', 'pn-customers-manager'),
          'description'   => __('When the AI detects a request that cannot be fulfilled with a simple purchase link (B2B, bulk, custom products, special shipping), it will collect the details and forward them by email to the selected recipients.', 'pn-customers-manager'),
          'parent'        => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $ig_special_orders_user_options = [];
        $ig_special_orders_all_users    = get_users([
          'fields'  => ['ID', 'display_name', 'user_email'],
          'orderby' => 'display_name',
          'order'   => 'ASC',
        ]);
        foreach ($ig_special_orders_all_users as $ig_special_orders_user) {
          if (empty($ig_special_orders_user->user_email)) {
            continue;
          }
          $ig_special_orders_user_options[$ig_special_orders_user->ID] = $ig_special_orders_user->display_name . ' (' . $ig_special_orders_user->user_email . ')';
        }
        $fields[] = [
          'id'            => 'ig_special_orders_users',
          'class'         => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
          'input'         => 'select',
          'multiple'      => true,
          'label'         => __('Special order recipients (platform users)', 'pn-customers-manager'),
          'description'   => __('Select platform users who will receive special order notifications by email. Leave empty to use only external emails or the site admin email.', 'pn-customers-manager'),
          'options'       => $ig_special_orders_user_options,
          'parent'        => 'ig_enable_special_orders',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'                => 'ig_special_orders_external_emails_wrapper',
          'class'             => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'             => 'html_multi',
          'label'             => __('Special order recipients (external emails)', 'pn-customers-manager'),
          'description'       => __('Add email addresses for people who are not registered users on this site.', 'pn-customers-manager'),
          'html_multi_fields' => [
            [
              'id'          => 'ig_special_orders_external_emails',
              'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
              'input'       => 'input',
              'type'        => 'email',
              'placeholder' => 'email@example.com',
            ],
          ],
          'parent'        => 'ig_enable_special_orders',
          'parent_option' => 'on',
        ];
        // Shipping fields (under WooCommerce products)
        $fields[] = [
          'id'          => 'ig_wc_shipping_zones',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('Use WooCommerce shipping zones', 'pn-customers-manager'),
          'description' => __('When a customer asks about shipping and provides a postal code, the plugin bypasses the AI model and uses WooCommerce\'s own shipping functions to match the correct zone and calculate the available methods and prices. The response is sent directly to the customer, ensuring accurate rates and preventing the model from picking the wrong zone or re-asking for the postal code. Configured shipping zones are also passed to the AI model as fallback context for any other shipping-related questions.', 'pn-customers-manager'),
          'parent'      => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'          => 'ig_require_postal_code',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'checkbox',
          'label'       => __('Always request postal code', 'pn-customers-manager'),
          'description' => __('The AI will always ask for the postal code before quoting a shipping cost.', 'pn-customers-manager'),
          'parent'      => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'    => 'ig_shipping_info',
          'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input' => 'editor',
          'label' => __('Additional shipping information', 'pn-customers-manager'),
          'description' => __('Extra shipping details: delivery times, special conditions, etc.', 'pn-customers-manager'),
          'parent'      => 'ig_include_woo',
          'parent_option' => 'on',
        ];
        $fields[] = [
          'id'          => 'ig_schedule_info',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'editor',
          'label'       => __('Opening hours', 'pn-customers-manager'),
          'description' => __('Business hours, weekdays, weekends, holidays, etc.', 'pn-customers-manager'),
          'parent'      => 'ig_include_woo',
          'parent_option' => 'on',
        ];
      }

      // ── Model configuration ──
      $fields[] = [
        'id'           => 'ig_divider_model',
        'class'        => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Model configuration', 'pn-customers-manager') . '</h4>',
      ];

      $fields[] = [
        'id'      => 'ig_ai_model',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'type'    => 'select',
        'label'   => __('AI Model', 'pn-customers-manager'),
        'options' => PN_CUSTOMERS_MANAGER_WhatsApp_AI::get_all_model_options(true),
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
    }

    // Send Email (communication node) specific fields
    if ($subtype === 'send_email') {
      $fields[] = [
        'id'           => 'es_heading_settings',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Email', 'pn-customers-manager') . '</h4>',
      ];

      $mailpn_active = class_exists('PN_CUSTOMERS_MANAGER_Email_Campaigns') && PN_CUSTOMERS_MANAGER_Email_Campaigns::is_mailpn_active();

      if ($mailpn_active) {
        $fields[] = [
          'id'      => 'es_send_mode',
          'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
          'input'   => 'select',
          'type'    => 'select',
          'label'   => __('Send mode', 'pn-customers-manager'),
          'parent'  => 'this',
          'options' => [
            'mailpn_template' => __('MAILPN template', 'pn-customers-manager'),
            'manual'          => __('Manual', 'pn-customers-manager'),
          ],
        ];

        $templates = PN_CUSTOMERS_MANAGER_Email_Campaigns::get_mail_templates();
        $template_options = ['' => '-- ' . __('Select template', 'pn-customers-manager') . ' --'];
        foreach ($templates as $tid => $title) {
          $template_options[$tid] = $title;
        }

        $fields[] = [
          'id'      => 'es_mailpn_template',
          'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
          'input'   => 'select',
          'type'    => 'select',
          'label'   => __('Template', 'pn-customers-manager'),
          'options' => $template_options,
          'parent'  => 'es_send_mode',
          'parent_option' => 'mailpn_template',
        ];
      }

      $manual_parent = $mailpn_active ? ['parent' => 'es_send_mode', 'parent_option' => 'manual'] : [];

      $fields[] = array_merge([
        'id'          => 'es_from_name',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'text',
        'label'       => __('From name', 'pn-customers-manager'),
        'placeholder' => get_bloginfo('name'),
      ], $manual_parent);

      $fields[] = array_merge([
        'id'          => 'es_from_email',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'email',
        'label'       => __('From email', 'pn-customers-manager'),
        'placeholder' => get_option('admin_email'),
      ], $manual_parent);

      $fields[] = array_merge([
        'id'          => 'es_subject',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'text',
        'label'       => __('Email subject', 'pn-customers-manager'),
        'placeholder' => __('Welcome, {name}!', 'pn-customers-manager'),
      ], $manual_parent);

      $fields[] = array_merge([
        'id'          => 'es_body',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'textarea',
        'label'       => __('Email body', 'pn-customers-manager'),
        'placeholder' => __('Write the email content here...', 'pn-customers-manager'),
      ], $manual_parent);

      $fields[] = [
        'id'           => 'es_placeholders_info',
        'input'        => 'html',
        'html_content' => '<p class="description" style="margin-top:0">'
          . esc_html__('Available placeholders:', 'pn-customers-manager')
          . ' <code>{name}</code>, <code>{email}</code>, <code>{phone}</code></p>',
      ];
    }

    // Email Sequence (communication node) specific fields
    if ($subtype === 'email_sequence') {
      $fields[] = [
        'id'           => 'es_heading_settings',
        'input'        => 'html',
        'html_content' => '<hr style="margin:16px 0;border:none;border-top:1px solid #eee"><h4 style="margin:0 0 8px">' . esc_html__('Email sequence', 'pn-customers-manager') . '</h4>',
      ];

      $mailpn_active = class_exists('PN_CUSTOMERS_MANAGER_Email_Campaigns') && PN_CUSTOMERS_MANAGER_Email_Campaigns::is_mailpn_active();

      if ($mailpn_active) {
        $fields[] = [
          'id'      => 'es_send_mode',
          'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
          'input'   => 'select',
          'type'    => 'select',
          'label'   => __('Send mode', 'pn-customers-manager'),
          'parent'  => 'this',
          'options' => [
            'mailpn_template' => __('MAILPN template', 'pn-customers-manager'),
            'manual'          => __('Manual', 'pn-customers-manager'),
          ],
        ];

        $templates = PN_CUSTOMERS_MANAGER_Email_Campaigns::get_mail_templates();
        $template_options = ['' => '-- ' . __('Select template', 'pn-customers-manager') . ' --'];
        foreach ($templates as $tid => $title) {
          $template_options[$tid] = $title;
        }

        $fields[] = [
          'id'    => 'es_mailpn_templates_multi',
          'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input' => 'html_multi',
          'label' => __('Templates', 'pn-customers-manager'),
          'parent'        => 'es_send_mode',
          'parent_option' => 'mailpn_template',
          'html_multi_fields' => [
            [
              'id'      => 'es_mailpn_template_id',
              'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
              'input'   => 'select',
              'type'    => 'select',
              'label'   => __('Template', 'pn-customers-manager'),
              'options' => $template_options,
            ],
          ],
        ];
      }

      $manual_parent = $mailpn_active ? ['parent' => 'es_send_mode', 'parent_option' => 'manual'] : [];

      $fields[] = array_merge([
        'id'          => 'es_from_name',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'text',
        'label'       => __('From name', 'pn-customers-manager'),
        'placeholder' => get_bloginfo('name'),
      ], $manual_parent);

      $fields[] = array_merge([
        'id'          => 'es_from_email',
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input'       => 'input',
        'type'        => 'email',
        'label'       => __('From email', 'pn-customers-manager'),
        'placeholder' => get_option('admin_email'),
      ], $manual_parent);

      $fields[] = array_merge([
        'id'    => 'es_emails_multi',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'html_multi',
        'label' => __('Emails', 'pn-customers-manager'),
        'html_multi_fields' => [
          [
            'id'          => 'es_email_subject',
            'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
            'input'       => 'input',
            'type'        => 'text',
            'label'       => __('Subject', 'pn-customers-manager'),
            'placeholder' => __('Welcome, {name}!', 'pn-customers-manager'),
          ],
          [
            'id'          => 'es_email_body',
            'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
            'input'       => 'textarea',
            'label'       => __('Body', 'pn-customers-manager'),
            'placeholder' => __('Write the email content here...', 'pn-customers-manager'),
          ],
        ],
      ], $manual_parent);

      $fields[] = [
        'id'           => 'es_placeholders_info',
        'input'        => 'html',
        'html_content' => '<p class="description" style="margin-top:0">'
          . esc_html__('Available placeholders:', 'pn-customers-manager')
          . ' <code>{name}</code>, <code>{email}</code>, <code>{phone}</code></p>',
      ];
    }

    return $fields;
  }

  /**
   * Render the schedule days grid HTML for the bot schedule settings.
   *
   * @return string
   */
  private static function render_schedule_days_html($prefix = 'wa') {
    $days = [
      'mon' => __('Mon', 'pn-customers-manager'),
      'tue' => __('Tue', 'pn-customers-manager'),
      'wed' => __('Wed', 'pn-customers-manager'),
      'thu' => __('Thu', 'pn-customers-manager'),
      'fri' => __('Fri', 'pn-customers-manager'),
      'sat' => __('Sat', 'pn-customers-manager'),
      'sun' => __('Sun', 'pn-customers-manager'),
    ];

    $field_id = esc_attr($prefix . '_schedule_days');

    $html = '<div class="pn-cm-schedule-grid" data-prefix="' . esc_attr($prefix) . '" style="margin-top:8px">';
    $html .= '<input type="hidden" name="' . $field_id . '" id="' . $field_id . '" value="" />';

    foreach ($days as $key => $label) {
      $html .= '<div class="pn-cm-schedule-day-row" data-day="' . esc_attr($key) . '">';
      $html .= '<label class="pn-cm-schedule-day-btn">';
      $html .= '<input type="checkbox" class="pn-cm-schedule-day-toggle" data-day="' . esc_attr($key) . '" />';
      $html .= '<span>' . esc_html($label) . '</span>';
      $html .= '</label>';
      $html .= '<input type="time" class="pn-cm-schedule-from" data-day="' . esc_attr($key) . '" value="09:00" />';
      $html .= '<span class="pn-cm-schedule-separator">–</span>';
      $html .= '<input type="time" class="pn-cm-schedule-to" data-day="' . esc_attr($key) . '" value="20:00" />';
      $html .= '</div>';
    }

    $html .= '<p style="margin:8px 0 0;color:#666;font-size:12px">' . esc_html__('Times are in the contact\'s local timezone, inferred from their phone country code.', 'pn-customers-manager') . '</p>';
    $html .= '</div>';

    return $html;
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
      'checked' => true, 'data-day' => true,
    ];
    $allowed_html['label'] = [
      'class' => true, 'style' => true, 'for' => true,
    ];
    $allowed_html['button'] = [
      'type' => true, 'id' => true, 'class' => true, 'style' => true, 'title' => true,
    ];
    // Allow data attributes on div for schedule grid
    if (isset($allowed_html['div'])) {
      $allowed_html['div']['data-prefix'] = true;
      $allowed_html['div']['data-day']    = true;
    } else {
      $allowed_html['div'] = [
        'class' => true, 'id' => true, 'style' => true,
        'data-prefix' => true, 'data-day' => true,
      ];
    }

    ob_start();
    foreach ($fields as $field) {
      // HTML-only fields without parent (dividers, headings): output directly
      if (isset($field['input']) && $field['input'] === 'html' && empty($field['parent'])) {
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

  /**
   * Load a form node's config from canvas JSON.
   *
   * @param int    $funnel_id
   * @param string $node_id
   * @return array|null
   */
  public static function get_form_node_config($funnel_id, $node_id) {
    $canvas = get_post_meta($funnel_id, self::CANVAS_META_KEY, true);
    if (empty($canvas)) return null;

    $data = json_decode($canvas, true);
    if (empty($data) || empty($data['nodes'])) return null;

    foreach ($data['nodes'] as $node) {
      if (isset($node['id']) && (string) $node['id'] === (string) $node_id && isset($node['subtype']) && $node['subtype'] === 'form') {
        return isset($node['config']) ? $node['config'] : [];
      }
    }

    return null;
  }
}
