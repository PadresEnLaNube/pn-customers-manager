<?php
/**
 * Platform shortcodes.
 *
 * This class defines all shortcodes of the platform.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Shortcodes {
	/**
	 * Manage the shortcodes in the platform.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_test($atts) {
    $a = extract(shortcode_atts([
      'user_id' => 0,
      'post_id' => 0,
    ], $atts));

    
	}

  public function pn_customers_manager_call_to_action($atts) {
    // echo do_shortcode('[pn-customers-manager-call-to-action pn_customers_manager_call_to_action_icon="error_outline" pn_customers_manager_call_to_action_title="' . esc_html(__('Default title', 'pn-customers-manager')) . '" pn_customers_manager_call_to_action_content="' . esc_html(__('Default content', 'pn-customers-manager')) . '" pn_customers_manager_call_to_action_button_link="#" pn_customers_manager_call_to_action_button_text="' . esc_html(__('Button text', 'pn-customers-manager')) . '" pn_customers_manager_call_to_action_button_class="pn-customers-manager-class"]');
    $a = extract(shortcode_atts(array(
      'pn_customers_manager_call_to_action_class' => '',
      'pn_customers_manager_call_to_action_icon' => '',
      'pn_customers_manager_call_to_action_title' => '',
      'pn_customers_manager_call_to_action_content' => '',
      'pn_customers_manager_call_to_action_button_link' => '#',
      'pn_customers_manager_call_to_action_button_text' => '',
      'pn_customers_manager_call_to_action_button_class' => '',
      'pn_customers_manager_call_to_action_button_data_key' => '',
      'pn_customers_manager_call_to_action_button_data_value' => '',
      'pn_customers_manager_call_to_action_button_blank' => 0,
    ), $atts));

    ob_start();
    ?>
      <div class="pn-customers-manager-call-to-action pn-customers-manager-text-align-center pn-customers-manager-pt-30 pn-customers-manager-pb-50 <?php echo esc_attr($pn_customers_manager_call_to_action_class); ?>">
        <div class="pn-customers-manager-call-to-action-icon">
          <i class="material-icons-outlined pn-customers-manager-font-size-75 pn-customers-manager-color-main-0"><?php echo esc_html($pn_customers_manager_call_to_action_icon); ?></i>
        </div>

        <h4 class="pn-customers-manager-call-to-action-title pn-customers-manager-text-align-center pn-customers-manager-mt-10 pn-customers-manager-mb-20"><?php echo esc_html($pn_customers_manager_call_to_action_title); ?></h4>
        
        <?php if (!empty($pn_customers_manager_call_to_action_content)): ?>
          <p class="pn-customers-manager-text-align-center"><?php echo wp_kses_post($pn_customers_manager_call_to_action_content); ?></p>
        <?php endif ?>

        <?php if (!empty($pn_customers_manager_call_to_action_button_text)): ?>
          <div class="pn-customers-manager-text-align-center pn-customers-manager-mt-20">
            <a class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-customers-manager-margin-auto <?php echo esc_attr($pn_customers_manager_call_to_action_button_class); ?>" <?php echo ($pn_customers_manager_call_to_action_button_blank) ? 'target="_blank"' : ''; ?> href="<?php echo esc_url($pn_customers_manager_call_to_action_button_link); ?>" <?php echo (!empty($pn_customers_manager_call_to_action_button_data_key) && !empty($pn_customers_manager_call_to_action_button_data_value)) ? esc_attr($pn_customers_manager_call_to_action_button_data_key) . '="' . esc_attr($pn_customers_manager_call_to_action_button_data_value) . '"' : ''; ?>><?php echo esc_html($pn_customers_manager_call_to_action_button_text); ?></a>
          </div>
        <?php endif ?>
      </div>
    <?php 
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
  }

  /**
   * Button shortcode.
   *
   * Usage: [pn-customers-manager-button text="Buy now" url="https://..." style="solid" color="#0000aa" size="medium"]
   *
   * @param array $atts
   * @return string
   */
  public function pn_customers_manager_button($atts) {
    $atts = shortcode_atts([
      'text'          => '',
      'url'           => '#',
      'target_blank'  => 0,
      'nofollow'      => 0,
      'style'         => 'solid',
      'color'         => '',
      'text_color'    => '',
      'size'          => 'medium',
      'align'         => 'center',
      'icon'          => '',
      'icon_position' => 'left',
      'use_image'     => 0,
      'image'         => '',
    ], $atts);

    // Size class map
    $size_classes = [
      'small'  => 'pn-cm-btn-sm',
      'medium' => 'pn-cm-btn-md',
      'large'  => 'pn-cm-btn-lg',
    ];
    $size_class = isset($size_classes[$atts['size']]) ? $size_classes[$atts['size']] : 'pn-cm-btn-md';

    // Style class map
    $style_classes = [
      'solid'       => 'pn-cm-btn-solid',
      'outline'     => 'pn-cm-btn-outline',
      'transparent' => 'pn-cm-btn-transparent',
    ];
    $style_class = isset($style_classes[$atts['style']]) ? $style_classes[$atts['style']] : 'pn-cm-btn-solid';

    // Inline styles
    $inline = '';
    if (!empty($atts['color'])) {
      if ($atts['style'] === 'outline') {
        $inline .= 'border-color:' . esc_attr($atts['color']) . ';color:' . esc_attr($atts['color']) . ';';
      } elseif ($atts['style'] !== 'transparent') {
        $inline .= 'background-color:' . esc_attr($atts['color']) . ';';
      }
    }
    if (!empty($atts['text_color'])) {
      $inline .= 'color:' . esc_attr($atts['text_color']) . ';';
    }

    // Link attributes
    $target = !empty($atts['target_blank']) && $atts['target_blank'] !== '0' ? ' target="_blank"' : '';
    $rel    = !empty($atts['nofollow']) && $atts['nofollow'] !== '0' ? ' rel="nofollow"' : '';

    // Alignment
    $align = in_array($atts['align'], ['left', 'center', 'right'], true) ? $atts['align'] : 'center';

    ob_start();
    ?>
    <div class="pn-cm-btn-wrap" style="text-align:<?php echo esc_attr($align); ?>">
      <a href="<?php echo esc_url($atts['url']); ?>" class="pn-cm-btn <?php echo esc_attr($style_class . ' ' . $size_class); ?>"<?php echo $target . $rel; ?><?php echo $inline ? ' style="' . esc_attr($inline) . '"' : ''; ?>>
        <?php if (!empty($atts['use_image']) && $atts['use_image'] !== '0' && !empty($atts['image'])): ?>
          <?php echo wp_get_attachment_image(absint($atts['image']), 'medium', false, ['class' => 'pn-cm-btn-img']); ?>
        <?php else: ?>
          <?php if (!empty($atts['icon']) && $atts['icon_position'] === 'left'): ?>
            <i class="material-icons-outlined"><?php echo esc_html($atts['icon']); ?></i>
          <?php endif; ?>
          <span><?php echo esc_html($atts['text']); ?></span>
          <?php if (!empty($atts['icon']) && $atts['icon_position'] === 'right'): ?>
            <i class="material-icons-outlined"><?php echo esc_html($atts['icon']); ?></i>
          <?php endif; ?>
        <?php endif; ?>
      </a>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Client onboarding form shortcode.
   *
   * @param array $atts
   * @return string
   */
  public function pn_customers_manager_client_form($atts = []) {
    return PN_CUSTOMERS_MANAGER_Client_Form::render_form($atts);
  }

  /**
   * Contact form shortcode.
   *
   * @param array $atts
   * @return string
   */
  public function pn_customers_manager_contact_form($atts = []) {
    return PN_CUSTOMERS_MANAGER_Contact_Form::render_form($atts);
  }

  /**
   * Register the Gutenberg block for the button shortcode.
   */
  public static function register_button_block() {
    if (!function_exists('register_block_type')) {
      return;
    }

    wp_register_script(
      'pn-customers-manager-button-block',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-button.js',
      ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'],
      defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0',
      true
    );

    register_block_type('pn-customers-manager/button', [
      'editor_script'   => 'pn-customers-manager-button-block',
      'render_callback' => [__CLASS__, 'render_button_block'],
      'attributes'      => [
        'text'         => ['type' => 'string', 'default' => ''],
        'url'          => ['type' => 'string', 'default' => '#'],
        'targetBlank'  => ['type' => 'boolean', 'default' => false],
        'nofollow'     => ['type' => 'boolean', 'default' => false],
        'style'        => ['type' => 'string', 'default' => 'solid'],
        'color'        => ['type' => 'string', 'default' => ''],
        'textColor'    => ['type' => 'string', 'default' => ''],
        'size'         => ['type' => 'string', 'default' => 'medium'],
        'align'        => ['type' => 'string', 'default' => 'center'],
        'icon'         => ['type' => 'string', 'default' => ''],
        'iconPosition' => ['type' => 'string', 'default' => 'left'],
      ],
    ]);
  }

  /**
   * Server-side render callback for the button block.
   *
   * @param array $attributes Block attributes.
   * @return string
   */
  public static function render_button_block($attributes) {
    $map = [
      'text'         => 'text',
      'url'          => 'url',
      'targetBlank'  => 'target_blank',
      'nofollow'     => 'nofollow',
      'style'        => 'style',
      'color'        => 'color',
      'textColor'    => 'text_color',
      'size'         => 'size',
      'align'        => 'align',
      'icon'         => 'icon',
      'iconPosition' => 'icon_position',
    ];

    $parts = [];
    foreach ($map as $block_key => $sc_key) {
      if (!isset($attributes[$block_key])) {
        continue;
      }
      $val = $attributes[$block_key];
      if (is_bool($val)) {
        $val = $val ? '1' : '0';
      }
      if ($val === '' || $val === '0') {
        continue;
      }
      $parts[] = $sc_key . '="' . esc_attr($val) . '"';
    }

    $shortcode = '[pn-customers-manager-button ' . implode(' ', $parts) . ']';
    return do_shortcode($shortcode);
  }

  /**
   * Shortcode: WhatsApp AI panel (admin only).
   *
   * Usage: [pn-customers-manager-whatsapp-ai]
   */
  public function pn_customers_manager_whatsapp_ai($atts) {
    if (!current_user_can('manage_options')) {
      return '';
    }

    self::enqueue_conversations_front_assets();

    $result = PN_CUSTOMERS_MANAGER_WhatsApp_AI::get_conversations('', 1, 200);

    ob_start();
    self::render_conversations_front('whatsapp', $result, __('WhatsApp AI', 'pn-customers-manager'), 'psychology');
    return ob_get_clean();
  }

  /**
   * Shortcode: Instagram AI panel (admin only).
   *
   * Usage: [pn-customers-manager-instagram-ai]
   */
  public function pn_customers_manager_instagram_ai($atts) {
    if (!current_user_can('manage_options')) {
      return '';
    }

    self::enqueue_conversations_front_assets();

    $result = PN_CUSTOMERS_MANAGER_Instagram_AI::get_conversations('', 1, 200);

    ob_start();
    self::render_conversations_front('instagram', $result, __('Instagram AI', 'pn-customers-manager'), 'photo_camera');
    return ob_get_clean();
  }

  /**
   * Enqueue front-end assets for conversation shortcodes.
   */
  private static function enqueue_conversations_front_assets() {
    static $enqueued = false;
    if ($enqueued) return;
    $enqueued = true;

    $ver = defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0';

    wp_enqueue_style(
      'pn-cm-conversations-front',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-cm-conversations-front.css',
      [],
      $ver
    );

    wp_enqueue_style(
      'pn-customers-manager-material-icons',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css',
      [],
      $ver
    );

    // Ensure plugin popup system is available
    wp_enqueue_style(
      'pn-customers-manager-popups',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css',
      [],
      $ver
    );

    wp_enqueue_script(
      'pn-customers-manager-popups',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js',
      ['jquery'],
      $ver,
      true
    );

    wp_enqueue_script(
      'pn-cm-conversations-front',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-conversations-front.js',
      ['jquery', 'pn-customers-manager-popups'],
      $ver,
      true
    );

    wp_localize_script('pn-cm-conversations-front', 'pnCmConvFrontI18n', [
      'confirmDelete' => __('Delete this conversation?', 'pn-customers-manager'),
      'closed'        => __('Closed', 'pn-customers-manager'),
      'active'        => __('Active', 'pn-customers-manager'),
    ]);
  }

  /**
   * Render the front-end card-based conversation list with popup modal.
   *
   * @param string $platform  'whatsapp' or 'instagram'
   * @param array  $result    ['items'=>[...], 'pages'=>N, 'page'=>N]
   * @param string $title     Heading text
   * @param string $icon      Material icon name
   */
  private static function render_conversations_front($platform, $result, $title, $icon) {
    $ajax_url = admin_url('admin-ajax.php');
    $nonce    = wp_create_nonce('pn-customers-manager-nonce');
    $is_wa    = $platform === 'whatsapp';
    ?>
    <div class="pn-cm-conv-front" data-platform="<?php echo esc_attr($platform); ?>" data-ajax-url="<?php echo esc_url($ajax_url); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">

      <div class="pn-cm-conv-front-header">
        <h2>
          <span class="material-icons-outlined"><?php echo esc_html($icon); ?></span>
          <?php echo esc_html($title); ?>
        </h2>
      </div>

      <div class="pn-cm-conv-front-filters">
        <button type="button" class="pn-cm-conv-filter-btn active" data-status="">
          <?php esc_html_e('All', 'pn-customers-manager'); ?>
        </button>
        <button type="button" class="pn-cm-conv-filter-btn" data-status="active">
          <?php esc_html_e('Active', 'pn-customers-manager'); ?>
        </button>
        <button type="button" class="pn-cm-conv-filter-btn" data-status="closed">
          <?php esc_html_e('Closed', 'pn-customers-manager'); ?>
        </button>
      </div>

      <?php if (empty($result['items'])): ?>
        <div class="pn-cm-conv-front-empty">
          <span class="material-icons-outlined">chat_bubble_outline</span>
          <p><?php esc_html_e('No conversations yet.', 'pn-customers-manager'); ?></p>
        </div>
      <?php else: ?>
        <div class="pn-cm-conv-cards">
          <?php foreach ($result['items'] as $conv):
            $msgs       = json_decode($conv->messages, true) ?: [];
            $last_msg   = !empty($msgs) ? end($msgs) : null;
            $last_text  = $last_msg ? wp_trim_words($last_msg['content'], 12, '...') : '';
            $funnel_title = $conv->funnel_id ? get_the_title($conv->funnel_id) : '';
            $identifier = $is_wa ? $conv->phone_number : (isset($conv->ig_user_id) ? $conv->ig_user_id : '');
            $name       = $conv->contact_name ?: $identifier;
          ?>
            <div class="pn-cm-conv-card" data-conv-id="<?php echo esc_attr($conv->id); ?>" data-status="<?php echo esc_attr($conv->status); ?>">
              <div class="pn-cm-conv-card-body pn-cm-conv-card-view" data-conv-id="<?php echo esc_attr($conv->id); ?>">
                <div class="pn-cm-conv-card-top">
                  <span class="pn-cm-conv-card-name"><?php echo esc_html($name); ?></span>
                  <span class="pn-cm-conv-status pn-cm-conv-status-<?php echo esc_attr($conv->status); ?>">
                    <?php echo $conv->status === 'active' ? esc_html__('Active', 'pn-customers-manager') : esc_html__('Closed', 'pn-customers-manager'); ?>
                  </span>
                </div>
                <?php if ($name !== $identifier && !empty($identifier)): ?>
                  <span class="pn-cm-conv-card-identifier"><?php echo esc_html($identifier); ?></span>
                <?php endif; ?>
                <?php if (!empty($last_text)): ?>
                  <span class="pn-cm-conv-card-last-msg"><?php echo esc_html($last_text); ?></span>
                <?php endif; ?>
                <div class="pn-cm-conv-card-bottom">
                  <?php if (!empty($funnel_title)): ?>
                    <span class="pn-cm-conv-card-funnel">
                      <span class="material-icons-outlined">account_tree</span>
                      <?php echo esc_html($funnel_title); ?>
                    </span>
                  <?php endif; ?>
                  <span class="pn-cm-conv-card-date"><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conv->updated_at))); ?></span>
                </div>
              </div>
              <div class="pn-cm-conv-card-actions">
                <?php if ($conv->status === 'active'): ?>
                  <button type="button" class="pn-cm-conv-card-action pn-cm-conv-close-btn" data-conv-id="<?php echo esc_attr($conv->id); ?>" title="<?php esc_attr_e('Close', 'pn-customers-manager'); ?>">
                    <span class="material-icons-outlined">close</span>
                  </button>
                <?php endif; ?>
                <button type="button" class="pn-cm-conv-card-action pn-cm-conv-delete-btn" data-conv-id="<?php echo esc_attr($conv->id); ?>" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
                  <span class="material-icons-outlined">delete</span>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Popup (uses plugin popup manager) -->
      <?php
        $popup_id = 'pn-cm-conv-popup-' . esc_attr($platform);
      ?>
      <div id="<?php echo $popup_id; ?>" class="pn-customers-manager-popup pn-customers-manager-popup-size-large pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-id="<?php echo $popup_id; ?>">
        <div class="pn-customers-manager-popup-overlay"></div>
        <div class="pn-customers-manager-popup-content pn-cm-conv-popup-content">
          <div class="pn-cm-conv-popup-body"></div>
        </div>
      </div>
    </div>
    <?php
  }

}