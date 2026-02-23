<?php
/**
 * Contact form helper.
 *
 * Renders a public contact form (shortcode + Gutenberg block),
 * sends emails via wp_mail() and includes honeypot anti-spam.
 *
 * @since 1.0.6
 * @package pn-customers-manager
 */

class PN_CUSTOMERS_MANAGER_Contact_Form {

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
      'pn-customers-manager-contact-form-block',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-contact-form.js',
      ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
      $this->version,
      true
    );

    if (function_exists('wp_set_script_translations')) {
      wp_set_script_translations('pn-customers-manager-contact-form-block', 'pn-customers-manager');
    }

    register_block_type('pn-customers-manager/contact-form', [
      'editor_script'   => 'pn-customers-manager-contact-form-block',
      'render_callback' => [__CLASS__, 'render_block'],
      'attributes'      => [
        'showTitle' => [
          'type'    => 'boolean',
          'default' => false,
        ],
        'title' => [
          'type'    => 'string',
          'default' => '',
        ],
        'description' => [
          'type'    => 'string',
          'default' => '',
        ],
        'recipientEmail' => [
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
    $args = [
      'show_title'      => $attributes['showTitle'] ?? true,
      'title'           => $attributes['title'] ?? '',
      'description'     => $attributes['description'] ?? '',
      'recipient_email' => $attributes['recipientEmail'] ?? '',
    ];

    return self::render_form($args);
  }

  /**
   * Build field definitions for the contact form.
   *
   * @return array[]
   */
  private static function get_fields($recipient_email = '') {
    $fields = [];

    $fields[] = [
      'id'          => 'contact_name',
      'label'       => esc_html__('Nombre', 'pn-customers-manager'),
      'input'       => 'input',
      'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'type'        => 'text',
      'required'    => true,
      'placeholder' => esc_html__('Tu nombre', 'pn-customers-manager'),
    ];

    $fields[] = [
      'id'          => 'contact_email',
      'label'       => esc_html__('Correo electrónico', 'pn-customers-manager'),
      'input'       => 'input',
      'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'type'        => 'email',
      'required'    => true,
      'placeholder' => esc_html__('tu@email.com', 'pn-customers-manager'),
    ];

    $fields[] = [
      'id'          => 'contact_subject',
      'label'       => esc_html__('Asunto', 'pn-customers-manager'),
      'input'       => 'input',
      'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'type'        => 'text',
      'placeholder' => esc_html__('Asunto del mensaje', 'pn-customers-manager'),
    ];

    $fields[] = [
      'id'          => 'contact_message',
      'label'       => esc_html__('Mensaje', 'pn-customers-manager'),
      'input'       => 'textarea',
      'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'required'    => true,
      'placeholder' => esc_html__('Escribe tu mensaje aquí…', 'pn-customers-manager'),
    ];

    // Honeypot (hidden via CSS)
    $fields[] = [
      'id'    => 'contact_website',
      'input' => 'input',
      'type'  => 'hidden',
      'value' => '',
    ];

    // Recipient email (hidden)
    $fields[] = [
      'id'    => 'contact_recipient_email',
      'input' => 'input',
      'type'  => 'hidden',
      'value' => $recipient_email,
    ];

    // Nonce
    $fields[] = [
      'id'    => 'pn_customers_manager_ajax_nonce',
      'input' => 'input',
      'type'  => 'nonce',
    ];

    return $fields;
  }

  /**
   * Render the public contact form (used by shortcode and block).
   *
   * @param array $atts Shortcode attributes.
   * @return string
   */
  public static function render_form($atts = []) {
    $defaults = [
      'show_title'      => false,
      'title'           => '',
      'description'     => '',
      'recipient_email' => '',
    ];

    $atts = shortcode_atts($defaults, $atts, 'pn-customers-manager-contact-form');
    $atts['show_title'] = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $atts['show_title'] = is_null($atts['show_title']) ? $defaults['show_title'] : $atts['show_title'];

    $form_id = 'pn-customers-manager-contact-form-' . wp_rand(1000, 99999);
    $nonce   = wp_create_nonce('pn-customers-manager-nonce');
    $ajax_url = admin_url('admin-ajax.php');
    $fields  = self::get_fields(sanitize_email($atts['recipient_email']));

    ob_start();
    ?>
    <div class="pn-customers-manager-contact-form-wrap">
      <?php if (!empty($atts['show_title'])): ?>
        <h3 class="pn-customers-manager-client-form-title"><?php echo esc_html($atts['title']); ?></h3>
      <?php endif; ?>

      <?php if (!empty($atts['description'])): ?>
        <p class="pn-customers-manager-client-form-description"><?php echo esc_html($atts['description']); ?></p>
      <?php endif; ?>

      <form id="<?php echo esc_attr($form_id); ?>" class="pn-customers-manager-form pn-customers-manager-contact-form" autocomplete="off" novalidate>
        <?php foreach ($fields as $field): ?>
          <?php
            ob_start();
            PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0, 0, 'full');
            $field_html = ob_get_clean();
            echo wp_kses($field_html, PN_CUSTOMERS_MANAGER_KSES);
          ?>
        <?php endforeach; ?>

        <div class="pn-customers-manager-text-align-right">
          <button type="submit" class="pn-customers-manager-btn"><?php esc_html_e('Enviar mensaje', 'pn-customers-manager'); ?></button>
        </div>

        <div class="pn-customers-manager-cf-feedback" role="alert"></div>
      </form>
    </div>

    <script>
    (function() {
      var form = document.getElementById('<?php echo esc_js($form_id); ?>');
      if (!form) return;

      form.addEventListener('submit', function(e) {
        e.preventDefault();

        var feedback = form.querySelector('.pn-customers-manager-cf-feedback');
        var btn = form.querySelector('button[type="submit"]');

        // Client-side validation
        var name = form.querySelector('[name="contact_name"]').value.trim();
        var email = form.querySelector('[name="contact_email"]').value.trim();
        var message = form.querySelector('[name="contact_message"]').value.trim();

        if (!name || !email || !message) {
          feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-error';
          feedback.textContent = '<?php echo esc_js(__('Por favor, completa todos los campos obligatorios.', 'pn-customers-manager')); ?>';
          return;
        }

        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-error';
          feedback.textContent = '<?php echo esc_js(__('Introduce un correo electrónico válido.', 'pn-customers-manager')); ?>';
          return;
        }

        btn.disabled = true;
        feedback.className = 'pn-customers-manager-cf-feedback';
        feedback.textContent = '<?php echo esc_js(__('Enviando...', 'pn-customers-manager')); ?>';

        var fd = new FormData(form);
        fd.append('action', 'pn_customers_manager_ajax_nopriv');
        fd.append('pn_customers_manager_ajax_nopriv_type', 'pn_cm_contact_send');
        fd.append('pn_customers_manager_ajax_nopriv_nonce', '<?php echo esc_js($nonce); ?>');
        fd.append('contact_source_url', window.location.href);
        fd.append('contact_source_title', document.title);

        fetch('<?php echo esc_js($ajax_url); ?>', {
          method: 'POST',
          credentials: 'same-origin',
          body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          btn.disabled = false;
          if (!data.error_key || data.error_key === '') {
            feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-success';
            feedback.textContent = '<?php echo esc_js(__('Mensaje enviado correctamente. Gracias por contactarnos.', 'pn-customers-manager')); ?>';
            form.reset();
          } else {
            feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-error';
            feedback.textContent = data.error_content || '<?php echo esc_js(__('Ha ocurrido un error. Inténtalo de nuevo.', 'pn-customers-manager')); ?>';
          }
        })
        .catch(function() {
          btn.disabled = false;
          feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-error';
          feedback.textContent = '<?php echo esc_js(__('Error de conexión. Inténtalo de nuevo.', 'pn-customers-manager')); ?>';
        });
      });
    })();
    </script>
    <?php
    return ob_get_clean();
  }
}
