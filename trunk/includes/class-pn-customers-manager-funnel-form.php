<?php
/**
 * Funnel Form helper.
 *
 * Renders dynamic forms defined in the Funnel Builder (shortcode + Gutenberg block),
 * handles AJAX submissions with Akismet integration and email notifications.
 *
 * @since   1.2.19
 * @package pn-customers-manager
 */

class PN_CUSTOMERS_MANAGER_Funnel_Form {

  /**
   * Plugin slug.
   *
   * @var string
   */
  private $plugin_name;

  /**
   * Plugin version.
   *
   * @var string
   */
  private $version;

  public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version     = $version;
  }

  /**
   * Register the Gutenberg block.
   */
  public function register_block() {
    if (!function_exists('register_block_type')) {
      return;
    }

    wp_register_script(
      'pn-customers-manager-funnel-form-block',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-funnel-form.js',
      ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
      $this->version,
      true
    );

    if (function_exists('wp_set_script_translations')) {
      wp_set_script_translations('pn-customers-manager-funnel-form-block', 'pn-customers-manager');
    }

    wp_localize_script('pn-customers-manager-funnel-form-block', 'pnCmFunnelFormBlock', [
      'funnels' => self::get_funnels_with_forms(),
    ]);

    register_block_type('pn-customers-manager/funnel-form', [
      'editor_script'   => 'pn-customers-manager-funnel-form-block',
      'render_callback' => [__CLASS__, 'render_block'],
      'attributes'      => [
        'funnelId' => [
          'type'    => 'number',
          'default' => 0,
        ],
        'nodeId' => [
          'type'    => 'string',
          'default' => '',
        ],
      ],
    ]);
  }

  /**
   * Render callback for the Gutenberg block.
   *
   * @param array $attributes Block attributes.
   * @return string
   */
  public static function render_block($attributes = []) {
    $atts = [
      'funnel_id' => isset($attributes['funnelId']) ? $attributes['funnelId'] : 0,
      'node_id'   => isset($attributes['nodeId']) ? $attributes['nodeId'] : '',
    ];

    return self::render_form($atts);
  }

  /**
   * Render the public funnel form (used by shortcode and block).
   *
   * @param array $atts Shortcode attributes.
   * @return string
   */
  public static function render_form($atts = []) {
    $defaults = [
      'funnel_id' => 0,
      'node_id'   => '',
    ];

    $atts = shortcode_atts($defaults, $atts, 'pn-customers-manager-funnel-form');
    $funnel_id = absint($atts['funnel_id']);
    $node_id   = sanitize_text_field($atts['node_id']);

    if (empty($funnel_id) || empty($node_id)) {
      return '';
    }

    $config = PN_CUSTOMERS_MANAGER_Funnel_Builder::get_form_node_config($funnel_id, $node_id);
    if (!$config) {
      return '';
    }

    $form_fields   = isset($config['form_fields']) ? $config['form_fields'] : [];
    $form_title    = isset($config['form_title']) ? $config['form_title'] : '';
    $form_desc     = isset($config['form_description']) ? $config['form_description'] : '';
    $submit_text   = isset($config['form_submit_text']) && $config['form_submit_text'] !== '' ? $config['form_submit_text'] : __('Enviar', 'pn-customers-manager');
    $success_msg   = isset($config['form_success_message']) && $config['form_success_message'] !== '' ? $config['form_success_message'] : __('Formulario enviado correctamente. Gracias.', 'pn-customers-manager');
    $honeypot      = !empty($config['form_honeypot']);

    $display_mode  = isset($config['form_display_mode']) && in_array($config['form_display_mode'], ['basic', 'sequential', 'fullscreen'], true)
                     ? $config['form_display_mode'] : 'basic';
    $fullscreen_bg = isset($config['form_fullscreen_bg']) && $config['form_fullscreen_bg'] !== ''
                     ? sanitize_hex_color($config['form_fullscreen_bg']) : '#ffffff';
    $is_stepped    = ($display_mode !== 'basic');

    if (empty($form_fields)) {
      return '';
    }

    $fields   = self::build_field_definitions($form_fields);
    $form_uid = 'pn-cm-funnel-form-' . $funnel_id . '-' . esc_attr($node_id) . '-' . wp_rand(1000, 99999);
    $nonce    = wp_create_nonce('pn-customers-manager-nonce');
    $ajax_url = admin_url('admin-ajax.php');

    // Check if any field is a file upload to add enctype
    $has_file_field = false;
    foreach ($form_fields as $ff) {
      if (isset($ff['type']) && $ff['type'] === 'file') {
        $has_file_field = true;
        break;
      }
    }

    // Enqueue the main plugin CSS so form wrapper classes are styled on any page
    if (!wp_style_is('pn-customers-manager', 'enqueued')) {
      wp_enqueue_style('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], PN_CUSTOMERS_MANAGER_VERSION, 'all');
    }

    ob_start();
    ?>
    <div class="pn-cm-funnel-form-wrap">
      <?php if (!empty($form_title)): ?>
        <h3 class="pn-cm-funnel-form-title"><?php echo esc_html($form_title); ?></h3>
      <?php endif; ?>

      <?php if (!empty($form_desc)): ?>
        <p class="pn-cm-funnel-form-description"><?php echo esc_html($form_desc); ?></p>
      <?php endif; ?>

      <form id="<?php echo esc_attr($form_uid); ?>" class="pn-customers-manager-form pn-cm-funnel-form" autocomplete="off" novalidate<?php echo $has_file_field ? ' enctype="multipart/form-data"' : ''; ?> data-ajax-url="<?php echo esc_attr($ajax_url); ?>" data-nonce="<?php echo esc_attr($nonce); ?>" data-success-message="<?php echo esc_attr($success_msg); ?>" data-display-mode="<?php echo esc_attr($display_mode); ?>"<?php if ($display_mode === 'fullscreen'): ?> data-fullscreen-bg="<?php echo esc_attr($fullscreen_bg); ?>"<?php endif; ?>>
        <?php $step_index = 0; ?>
        <?php foreach ($fields as $field): ?>
          <?php if ($is_stepped): ?>
            <div class="pn-cm-funnel-form-step" data-step="<?php echo esc_attr($step_index); ?>"<?php echo $step_index > 0 ? ' style="display:none"' : ''; ?>>
          <?php endif; ?>
          <?php
            ob_start();
            PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0, 0, 'full');
            $field_html = ob_get_clean();
            echo wp_kses($field_html, PN_CUSTOMERS_MANAGER_KSES);
          ?>
          <?php if ($is_stepped): ?>
            </div>
          <?php endif; ?>
          <?php $step_index++; ?>
        <?php endforeach; ?>

        <input type="hidden" name="pn_cm_funnel_id" value="<?php echo esc_attr($funnel_id); ?>" />
        <input type="hidden" name="pn_cm_node_id" value="<?php echo esc_attr($node_id); ?>" />

        <?php if ($honeypot): ?>
          <div style="position:absolute;left:-9999px" aria-hidden="true">
            <input type="text" name="pn_cm_ff_website" value="" tabindex="-1" autocomplete="off" />
          </div>
        <?php endif; ?>

        <input type="hidden" name="pn_customers_manager_ajax_nonce" value="<?php echo esc_attr($nonce); ?>" />

        <div class="pn-customers-manager-text-align-right">
          <button type="submit" class="pn-customers-manager-btn"><?php echo esc_html($submit_text); ?></button>
        </div>

        <div class="pn-cm-funnel-form-feedback" role="alert"></div>
      </form>
    </div>

    <?php
    static $script_enqueued = false;
    if (!$script_enqueued) {
      wp_enqueue_script(
        'pn-customers-manager-funnel-form-handler',
        PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-funnel-form-handler.js',
        [],
        PN_CUSTOMERS_MANAGER_VERSION,
        true
      );

      wp_localize_script('pn-customers-manager-funnel-form-handler', 'pnCmFunnelForm', [
        'requiredFields'  => __('Por favor, completa todos los campos obligatorios.', 'pn-customers-manager'),
        'invalidEmail'    => __('Introduce un correo electrónico válido.', 'pn-customers-manager'),
        'sending'         => __('Enviando...', 'pn-customers-manager'),
        'success'         => __('Formulario enviado correctamente. Gracias.', 'pn-customers-manager'),
        'genericError'    => __('Ha ocurrido un error. Inténtalo de nuevo.', 'pn-customers-manager'),
        'connectionError' => __('Error de conexión. Inténtalo de nuevo.', 'pn-customers-manager'),
        'next'            => __('Siguiente', 'pn-customers-manager'),
        'back'            => __('Atrás', 'pn-customers-manager'),
        'stepOf'          => __('de', 'pn-customers-manager'),
        'closeForm'       => __('Cerrar', 'pn-customers-manager'),
      ]);

      $script_enqueued = true;
    }
    ?>
    <?php
    return ob_get_clean();
  }

  /**
   * Convert stored form_fields array to basepn field definitions.
   *
   * @param array $form_fields
   * @return array
   */
  public static function build_field_definitions($form_fields) {
    $fields = [];

    foreach ($form_fields as $field) {
      $id          = isset($field['id']) ? sanitize_key($field['id']) : '';
      $label       = isset($field['label']) ? $field['label'] : '';
      $type        = isset($field['type']) ? $field['type'] : 'text';
      $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
      $required    = !empty($field['required']);
      $options_raw = isset($field['options']) ? $field['options'] : '';
      $content     = isset($field['content']) ? $field['content'] : '';

      if (empty($id)) continue;

      $def = [
        'id'          => $id,
        'label'       => $label,
        'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'required'    => $required,
        'placeholder' => $placeholder,
      ];

      switch ($type) {
        case 'textarea':
          $def['input'] = 'textarea';
          break;

        case 'select':
          $def['input']   = 'select';
          $def['type']    = 'select';
          $def['options'] = self::parse_options($options_raw);
          break;

        case 'checkbox':
          $def['input'] = 'input';
          $def['type']  = 'checkbox';
          break;

        case 'radio':
          $def['input']   = 'select';
          $def['type']    = 'select';
          $def['options'] = self::parse_options($options_raw);
          break;

        case 'info':
          $info_html = '';
          if (!empty($content)) {
            $info_html = '<p style="margin:0">' . wp_kses_post(nl2br(esc_html($content))) . '</p>';
          }
          $def = [
            'id'           => $id,
            'label'        => $label,
            'input'        => 'html',
            'html_content' => $info_html,
          ];
          break;

        case 'file':
          $def['input']  = 'input';
          $def['type']   = 'file';
          $def['upload'] = true;
          if (!empty($placeholder)) {
            $def['accept'] = $placeholder;
          }
          $def['placeholder'] = '';
          break;

        default:
          // text, email, number, tel, url, date
          $def['input'] = 'input';
          $def['type']  = in_array($type, ['text', 'email', 'number', 'tel', 'url', 'date'], true) ? $type : 'text';
          break;
      }

      $fields[] = $def;
    }

    return $fields;
  }

  /**
   * Parse "value|label\nvalue2|label2" into ['value' => 'label', ...].
   *
   * @param string $str
   * @return array
   */
  public static function parse_options($input) {
    $options = [];
    if (empty($input)) return $options;

    // New array format: [{value: "...", label: "..."}, ...]
    if (is_array($input)) {
      foreach ($input as $item) {
        if (is_array($item) && isset($item['value'])) {
          $val   = (string) $item['value'];
          $label = isset($item['label']) ? (string) $item['label'] : $val;
          if ($val === '' && $label === '') continue;
          $options[$val] = $label;
        }
      }
      return $options;
    }

    // Legacy string format: "value|label\n..."
    if (!is_string($input)) return $options;

    $lines = preg_split('/\r?\n/', trim($input));
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') continue;
      if (strpos($line, '|') !== false) {
        list($val, $label) = explode('|', $line, 2);
        $options[trim($val)] = trim($label);
      } else {
        $options[$line] = $line;
      }
    }

    return $options;
  }

  /**
   * Handle AJAX form submission.
   */
  public static function handle_submission() {
    $funnel_id = isset($_POST['pn_cm_funnel_id']) ? absint($_POST['pn_cm_funnel_id']) : 0;
    $node_id   = isset($_POST['pn_cm_node_id']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_node_id'])) : '';

    if (empty($funnel_id) || empty($node_id)) {
      echo wp_json_encode([
        'error_key'     => 'pn_cm_ff_missing_ids',
        'error_content' => esc_html__('Invalid form.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $config = PN_CUSTOMERS_MANAGER_Funnel_Builder::get_form_node_config($funnel_id, $node_id);
    if (!$config) {
      echo wp_json_encode([
        'error_key'     => 'pn_cm_ff_not_found',
        'error_content' => esc_html__('Formulario no encontrado.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $form_fields     = isset($config['form_fields']) ? $config['form_fields'] : [];
    $form_title      = isset($config['form_title']) ? $config['form_title'] : '';
    $recipient_email = isset($config['form_recipient_email']) ? sanitize_email($config['form_recipient_email']) : '';
    $honeypot        = !empty($config['form_honeypot']);

    // Honeypot check
    if ($honeypot) {
      $honey_val = isset($_POST['pn_cm_ff_website']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_ff_website'])) : '';
      if (!empty($honey_val)) {
        echo wp_json_encode(['error_key' => '']);
        exit;
      }
    }

    // Validate required fields and collect submitted data
    $submitted_data = [];
    $sender_email   = '';

    foreach ($form_fields as $field) {
      $fid      = isset($field['id']) ? sanitize_key($field['id']) : '';
      $type     = isset($field['type']) ? $field['type'] : 'text';
      $required = !empty($field['required']);
      $label    = isset($field['label']) ? $field['label'] : $fid;

      if (empty($fid)) continue;

      // Info fields are display-only, skip them
      if ($type === 'info') continue;

      // File upload handling
      if ($type === 'file') {
        $value = '';
        if (!empty($_FILES[$fid]) && $_FILES[$fid]['error'] === UPLOAD_ERR_OK) {
          // Validate file extension against accepted types
          $accept_str = isset($field['placeholder']) ? $field['placeholder'] : '';
          if (!empty($accept_str)) {
            $accepted = array_map('trim', explode(',', $accept_str));
            $file_ext = '.' . strtolower(pathinfo($_FILES[$fid]['name'], PATHINFO_EXTENSION));
            $file_mime = mime_content_type($_FILES[$fid]['tmp_name']);
            $allowed = false;
            foreach ($accepted as $acc) {
              $acc = strtolower(trim($acc));
              if ($acc === '') continue;
              // Normalize: if it looks like an extension without dot, prepend dot
              if ($acc[0] !== '.' && strpos($acc, '/') === false) {
                $acc = '.' . $acc;
              }
              // Check extension match (e.g. .pdf) or MIME wildcard (e.g. image/*)
              if ($acc === $file_ext) {
                $allowed = true;
                break;
              }
              if (strpos($acc, '/') !== false && $file_mime) {
                if ($acc === $file_mime) {
                  $allowed = true;
                  break;
                }
                // Handle wildcards like image/*
                if (substr($acc, -2) === '/*' && strpos($file_mime, substr($acc, 0, -1)) === 0) {
                  $allowed = true;
                  break;
                }
              }
            }
            if (!$allowed) {
              echo wp_json_encode([
                'error_key'     => 'pn_cm_ff_invalid_file_type',
                'error_content' => sprintf(
                  /* translators: %s: accepted file types */
                  esc_html__('Tipo de archivo no permitido. Tipos aceptados: %s', 'pn-customers-manager'),
                  esc_html($accept_str)
                ),
              ]);
              exit;
            }
          }

          require_once ABSPATH . 'wp-admin/includes/file.php';
          $upload = wp_handle_upload($_FILES[$fid], ['test_form' => false]);
          if (!empty($upload['url'])) {
            $value = $upload['url'];
          }
        }

        if ($required && $value === '') {
          echo wp_json_encode([
            'error_key'     => 'pn_cm_ff_missing_fields',
            'error_content' => esc_html__('Por favor, completa todos los campos obligatorios.', 'pn-customers-manager'),
          ]);
          exit;
        }

        $submitted_data[] = [
          'id'    => $fid,
          'label' => $label,
          'type'  => $type,
          'value' => $value,
        ];
        continue;
      }

      $raw_value = isset($_POST[$fid]) ? wp_unslash($_POST[$fid]) : '';

      // Sanitize by type
      switch ($type) {
        case 'email':
          $value = sanitize_email($raw_value);
          if (empty($sender_email) && !empty($value)) {
            $sender_email = $value;
          }
          break;
        case 'textarea':
          $value = sanitize_textarea_field($raw_value);
          break;
        case 'url':
          $value = esc_url_raw($raw_value);
          break;
        case 'number':
          $value = is_numeric($raw_value) ? $raw_value : '';
          break;
        default:
          $value = sanitize_text_field($raw_value);
          break;
      }

      // Required validation
      if ($required && $value === '') {
        echo wp_json_encode([
          'error_key'     => 'pn_cm_ff_missing_fields',
          'error_content' => esc_html__('Por favor, completa todos los campos obligatorios.', 'pn-customers-manager'),
        ]);
        exit;
      }

      $submitted_data[] = [
        'id'    => $fid,
        'label' => $label,
        'type'  => $type,
        'value' => $value,
      ];
    }

    // Email validation
    if (!empty($sender_email) && !is_email($sender_email)) {
      echo wp_json_encode([
        'error_key'     => 'pn_cm_ff_invalid_email',
        'error_content' => esc_html__('Please enter a valid email address.', 'pn-customers-manager'),
      ]);
      exit;
    }

    $source_url   = isset($_POST['pn_cm_ff_source_url']) ? esc_url_raw(wp_unslash($_POST['pn_cm_ff_source_url'])) : '';
    $source_title = isset($_POST['pn_cm_ff_source_title']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_ff_source_title'])) : '';

    // Akismet bot detection
    $is_spam = 0;
    $akismet_enabled = get_option('pn_customers_manager_akismet_enabled') === 'on';
    $akismet_discard = get_option('pn_customers_manager_akismet_discard') === 'on';

    // Build a content string from all submitted data for Akismet
    $content_for_akismet = '';
    foreach ($submitted_data as $datum) {
      $content_for_akismet .= $datum['label'] . ': ' . $datum['value'] . "\n";
    }

    if ($akismet_enabled && class_exists('Akismet') && method_exists('Akismet', 'get_api_key') && Akismet::get_api_key()) {
      $akismet_params = [
        'blog'                 => home_url(),
        'user_ip'              => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent'           => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'referrer'             => sanitize_text_field($_SERVER['HTTP_REFERER'] ?? ''),
        'permalink'            => $source_url,
        'comment_type'         => 'contact-form',
        'comment_author_email' => $sender_email,
        'comment_content'      => $content_for_akismet,
        'blog_lang'            => get_locale(),
        'blog_charset'         => get_bloginfo('charset'),
      ];

      try {
        $akismet_response = Akismet::http_post(http_build_query($akismet_params), 'comment-check');

        if (is_array($akismet_response) && isset($akismet_response[1])) {
          $akismet_body = trim((string) $akismet_response[1]);

          $is_discard = false;
          if (isset($akismet_response[0]) && is_array($akismet_response[0])) {
            foreach ($akismet_response[0] as $h_key => $h_val) {
              if (strtolower((string) $h_key) === 'x-akismet-pro-tip' && strtolower(trim((string) $h_val)) === 'discard') {
                $is_discard = true;
                break;
              }
            }
          }

          if ($akismet_body === 'true') {
            $is_spam = 1;

            if ($is_discard && $akismet_discard) {
              echo wp_json_encode(['error_key' => '']);
              exit;
            }
          }
        }
      } catch (\Exception $e) {
        $is_spam = 0;
      }
    }

    // Save to database
    global $wpdb;
    $user_id = get_current_user_id();
    $wpdb->insert(
      $wpdb->prefix . 'pn_cm_funnel_form_submissions',
      [
        'funnel_id'      => $funnel_id,
        'node_id'        => $node_id,
        'form_title'     => $form_title,
        'submitted_data' => wp_json_encode($submitted_data),
        'sender_email'   => $sender_email,
        'source_url'     => $source_url,
        'source_title'   => $source_title,
        'ip_address'     => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_id'        => $user_id,
        'is_read'        => 0,
        'is_spam'        => $is_spam,
        'created_at'     => current_time('mysql'),
      ],
      ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s']
    );

    // Do not send notification emails for spam
    if ($is_spam) {
      echo wp_json_encode(['error_key' => '']);
      exit;
    }

    // Determine recipient
    $to = !empty($recipient_email) && is_email($recipient_email) ? $recipient_email : get_option('admin_email');

    $site_name = get_option('blogname', '');
    if (empty($site_name)) {
      $site_name = wp_parse_url(home_url(), PHP_URL_HOST);
    }

    $subject = !empty($form_title)
      ? sprintf('[%s] %s', $site_name, $form_title)
      : sprintf('[%s] %s', $site_name, esc_html__('Nueva entrada de formulario', 'pn-customers-manager'));

    // Build email body
    $html_body = '';
    $plain_body = '';
    foreach ($submitted_data as $datum) {
      $html_body  .= sprintf('<p><strong>%s:</strong> %s</p>', esc_html($datum['label']), esc_html($datum['value']));
      $plain_body .= sprintf("%s: %s\n", $datum['label'], $datum['value']);
    }

    if (!empty($source_url)) {
      $html_body  .= sprintf('<p><strong>%s:</strong> %s</p>', esc_html__('Page', 'pn-customers-manager'), esc_html($source_url));
      $plain_body .= sprintf("%s: %s\n", __('Página', 'pn-customers-manager'), $source_url);
    }

    // Try sending via mailpn if available, otherwise fallback to wp_mail
    if (class_exists('MAILPN_Mailing')) {
      $mailing = new MAILPN_Mailing();
      $sent = $mailing->mailpn_sender([
        'mailpn_user_to' => $to,
        'mailpn_subject' => $subject,
        'mailpn_type'    => 'pn_cm_funnel_form',
      ], $html_body);
    } else {
      $headers = ['Content-Type: text/plain; charset=UTF-8'];
      if (!empty($sender_email)) {
        $headers[] = sprintf('Reply-To: %s', $sender_email);
      }
      $sent = wp_mail($to, $subject, $plain_body, $headers);
    }

    if (!$sent) {
      echo wp_json_encode([
        'error_key'     => 'pn_cm_ff_send_failed',
        'error_content' => esc_html__('Could not send the message. Please try again later.', 'pn-customers-manager'),
      ]);
      exit;
    }

    echo wp_json_encode(['error_key' => '']);
    exit;
  }

  /**
   * Query all funnels and find form nodes, returning structured data
   * for the Gutenberg block selector.
   *
   * @return array
   */
  public static function get_funnels_with_forms() {
    $funnels = get_posts([
      'post_type'      => 'pn_cm_funnel',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
      'orderby'        => 'title',
      'order'          => 'ASC',
    ]);

    $result = [];

    foreach ($funnels as $funnel) {
      $canvas = get_post_meta($funnel->ID, PN_CUSTOMERS_MANAGER_Funnel_Builder::CANVAS_META_KEY, true);
      if (empty($canvas)) continue;

      $data = json_decode($canvas, true);
      if (empty($data) || empty($data['nodes'])) continue;

      $form_nodes = [];
      foreach ($data['nodes'] as $node) {
        if (isset($node['subtype']) && $node['subtype'] === 'form') {
          $config = isset($node['config']) ? $node['config'] : [];
          $form_nodes[] = [
            'nodeId' => isset($node['id']) ? $node['id'] : '',
            'label'  => isset($config['form_title']) && $config['form_title'] !== '' ? $config['form_title'] : (isset($node['label']) ? $node['label'] : __('Form', 'pn-customers-manager')),
          ];
        }
      }

      if (!empty($form_nodes)) {
        $result[] = [
          'funnelId'    => $funnel->ID,
          'funnelTitle' => $funnel->post_title,
          'formNodes'   => $form_nodes,
        ];
      }
    }

    return $result;
  }
}
