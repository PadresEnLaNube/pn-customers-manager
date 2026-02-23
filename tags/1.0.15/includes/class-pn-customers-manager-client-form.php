<?php
/**
 * Client registration form helper.
 *
 * Builds the public form, registers the Gutenberg block and
 * stores submissions as WordPress users with the client role.
 *
 * @since 1.1.0
 * @package pn-customers-manager
 */

class PN_CUSTOMERS_MANAGER_Client_Form {
  const FORM_IDENTIFIER = 'pn_customers_manager_client_form';
  const FORM_SUBTYPE    = 'pn_customers_manager_client_new';
  const CLIENT_ROLE     = 'pn_customers_manager_role_client';

  /**
   * Plugin slug (used for assets).
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
   * Get available organization fields for the public form.
   * Filters out fields that shouldn't be shown in public forms.
   *
   * @return array
   */
  private static function get_available_organization_fields() {
    if (!class_exists('PN_CUSTOMERS_MANAGER_Post_Type_organization')) {
      return [];
    }

    $organization_post_type = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
    $all_fields = $organization_post_type->pn_cm_organization_get_fields_meta(0);

    $available_fields = [];

    // Fields to exclude from public form
    $excluded_fields = [
      'pn_cm_organization_section_basic_start',
      'pn_cm_organization_section_basic_end',
      'pn_cm_organization_section_advanced_start',
      'pn_cm_organization_section_advanced_end',
      'pn_cm_organization_section_funnel_start',
      'pn_cm_organization_section_funnel_end',
      'pn_cm_organization_contacts_block', // HTML field
      'pn_cm_organization_owner', // Requires user selection
      'pn_cm_organization_collaborators', // Requires user selection
      'pn_cm_organization_funnel_id', // Requires funnel selection
      'pn_cm_organization_funnel_stage', // Requires funnel selection
      'pn_cm_organization_funnel_status', // Requires funnel selection
      'pn_cm_organization_last_contact_date', // Internal field
      'pn_cm_organization_last_contact_channel', // Internal field
      'pn_cm_organization_next_action', // Internal field
      'pn_cm_organization_form', // Hidden field
      'pn_customers_manager_ajax_nonce', // Nonce field
    ];

    foreach ($all_fields as $field_id => $field_config) {
      // Skip excluded fields
      if (in_array($field_id, $excluded_fields, true)) {
        continue;
      }

      // Skip section markers
      if (!empty($field_config['section'])) {
        continue;
      }

      // Skip HTML fields
      if (!empty($field_config['input']) && $field_config['input'] === 'html') {
        continue;
      }

      // Only include fields with a label (user-facing fields)
      if (empty($field_config['label'])) {
        continue;
      }

      $available_fields[] = [
        'id'    => $field_id,
        'label' => $field_config['label'],
      ];
    }

    return $available_fields;
  }

  /**
   * Register Gutenberg block assets.
   */
  public function register_block() {
    if (!function_exists('register_block_type')) {
      return;
    }

    wp_register_script(
      'pn-customers-manager-client-form-block',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-client-form.js',
      ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
      $this->version,
      true
    );

    if (function_exists('wp_set_script_translations')) {
      wp_set_script_translations('pn-customers-manager-client-form-block', 'pn-customers-manager');
    }

    // Localize script with organization fields from PHP
    $organization_fields = self::get_available_organization_fields();
    wp_localize_script(
      'pn-customers-manager-client-form-block',
      'pnCustomersManagerOrganizationFields',
      $organization_fields
    );

    register_block_type('pn-customers-manager/client-form', [
      'editor_script'   => 'pn-customers-manager-client-form-block',
      'render_callback' => [__CLASS__, 'render_block'],
      'attributes'      => [
        'showTitle'   => [
          'type'    => 'boolean',
          'default' => true,
        ],
        'title'       => [
          'type'    => 'string',
          'default' => '',
        ],
        'description' => [
          'type'    => 'string',
          'default' => '',
        ],
        'formId'      => [
          'type'    => 'string',
          'default' => '',
        ],
        'organizationFields' => [
          'type'    => 'array',
          'default' => [],
        ],
      ],
    ]);
  }

  /**
   * Hook into pn_customers_manager_form_save to persist client data.
   *
   * @param int         $element_id   Element identifier (unused for this form).
   * @param array       $key_value    Submitted values.
   * @param string      $form_type    Type of form (user/post/option).
   * @param string      $form_subtype Subtype identifier.
   */
  public function handle_form_save($element_id, $key_value, $form_type, $form_subtype) {
    if ($form_type !== 'user' || $form_subtype !== self::FORM_SUBTYPE) {
      return;
    }

    if (empty($key_value['pn_customers_manager_client_form_identifier']) || $key_value['pn_customers_manager_client_form_identifier'] !== self::FORM_IDENTIFIER) {
      return;
    }

    $first_name = sanitize_text_field($key_value['pn_customers_manager_client_first_name'] ?? '');
    $last_name  = sanitize_text_field($key_value['pn_customers_manager_client_last_name'] ?? '');
    $email      = sanitize_email($key_value['pn_customers_manager_client_email'] ?? '');
    $phone      = sanitize_text_field($key_value['pn_customers_manager_client_phone'] ?? '');
    $company    = sanitize_text_field($key_value['pn_customers_manager_client_company'] ?? '');
    $job_title  = sanitize_text_field($key_value['pn_customers_manager_client_job_title'] ?? '');
    $stage      = sanitize_text_field($key_value['pn_customers_manager_client_stage'] ?? '');
    $lead_src   = sanitize_text_field($key_value['pn_customers_manager_client_lead_source'] ?? '');
    $notes      = sanitize_textarea_field($key_value['pn_customers_manager_client_notes'] ?? '');
    $industry   = sanitize_text_field($key_value['pn_customers_manager_client_industry'] ?? '');
    $revenue    = sanitize_text_field($key_value['pn_customers_manager_client_annual_revenue'] ?? '');
    $team_size  = sanitize_text_field($key_value['pn_customers_manager_client_team_size'] ?? '');
    $website    = esc_url_raw($key_value['pn_customers_manager_client_website'] ?? '');
    $country    = sanitize_text_field($key_value['pn_customers_manager_client_country'] ?? '');
    $city       = sanitize_text_field($key_value['pn_customers_manager_client_city'] ?? '');
    $address    = sanitize_text_field($key_value['pn_customers_manager_client_address'] ?? '');
    $postal     = sanitize_text_field($key_value['pn_customers_manager_client_postal_code'] ?? '');
    $lead_score = sanitize_text_field($key_value['pn_customers_manager_client_lead_score'] ?? '');
    $interests  = sanitize_textarea_field($key_value['pn_customers_manager_client_interests'] ?? '');
    $tags_raw   = sanitize_text_field($key_value['pn_customers_manager_client_tags'] ?? '');
    $password   = $key_value['pn_customers_manager_client_password'] ?? '';
    $consent    = !empty($key_value['pn_customers_manager_client_consent']) && $key_value['pn_customers_manager_client_consent'] === 'on';

    if (empty($first_name) || empty($last_name) || empty($email)) {
      wp_send_json([
        'error_key'     => 'pn_customers_manager_client_form_missing_data',
        'error_content' => esc_html__('Nombre, apellidos y correo electrónico son obligatorios.', 'pn-customers-manager'),
      ]);
    }

    if (!is_email($email)) {
      wp_send_json([
        'error_key'     => 'pn_customers_manager_client_form_email_invalid',
        'error_content' => esc_html__('Introduce un correo electrónico válido.', 'pn-customers-manager'),
      ]);
    }

    $existing_user_id = email_exists($email);
    $user_id          = 0;

    if ($existing_user_id) {
      $user_id = $existing_user_id;
      $update  = [
        'ID'         => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'display_name' => trim($first_name . ' ' . $last_name),
      ];

      $updated = wp_update_user($update);

      if (is_wp_error($updated)) {
        wp_send_json([
          'error_key'     => 'pn_customers_manager_client_form_user_error',
          'error_content' => esc_html__('No se pudo actualizar el usuario existente.', 'pn-customers-manager'),
        ]);
      }
    } else {
      $user_login = sanitize_user(sanitize_title($first_name . '-' . $last_name));
      if (empty($user_login)) {
        $user_login = sanitize_user('pn-customers-manager-client-' . wp_generate_password(6, false));
      }

      while (username_exists($user_login)) {
        $user_login = sanitize_user($user_login . '-' . wp_rand(100, 999));
      }

      $final_password = !empty($password) ? $password : wp_generate_password(12, true);

      $user_id = wp_insert_user([
        'user_login'   => $user_login,
        'user_pass'    => $final_password,
        'user_email'   => $email,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => trim($first_name . ' ' . $last_name),
        'role'         => self::CLIENT_ROLE,
      ]);

      if (is_wp_error($user_id)) {
        wp_send_json([
          'error_key'     => 'pn_customers_manager_client_form_user_error',
          'error_content' => esc_html__('No se pudo crear el usuario del cliente.', 'pn-customers-manager'),
        ]);
      }
    }

    $user = new WP_User($user_id);
    if ($user && !in_array(self::CLIENT_ROLE, $user->roles, true)) {
      $user->add_role(self::CLIENT_ROLE);
    }

    $meta_values = [
      'pn_customers_manager_client_phone'        => $phone,
      'pn_customers_manager_client_company'      => $company,
      'pn_customers_manager_client_job_title'    => $job_title,
      'pn_customers_manager_client_stage'        => $stage,
      'pn_customers_manager_client_lead_source'  => $lead_src,
      'pn_customers_manager_client_notes'        => $notes,
      'pn_customers_manager_client_industry'     => $industry,
      'pn_customers_manager_client_annual_revenue' => $revenue,
      'pn_customers_manager_client_team_size'    => $team_size,
      'pn_customers_manager_client_website'      => $website,
      'pn_customers_manager_client_country'      => $country,
      'pn_customers_manager_client_city'         => $city,
      'pn_customers_manager_client_address'      => $address,
      'pn_customers_manager_client_postal_code'  => $postal,
      'pn_customers_manager_client_lead_score'   => $lead_score,
      'pn_customers_manager_client_interests'    => $interests,
      'pn_customers_manager_client_consent'      => $consent ? 'yes' : 'no',
    ];

    foreach ($meta_values as $meta_key => $meta_value) {
      update_user_meta($user_id, $meta_key, $meta_value);
    }

    if (!empty($tags_raw)) {
      $tags = array_values(array_filter(array_map('trim', explode(',', $tags_raw))));
      update_user_meta($user_id, 'pn_customers_manager_client_tags', $tags);
    }
  }

  /**
   * Render callback for the Gutenberg block.
   *
   * @param array $attributes Block attributes.
   * @return string
   */
  public static function render_block($attributes = []) {
    $args = [
      'show_title'          => $attributes['showTitle'] ?? true,
      'title'               => $attributes['title'] ?? '',
      'description'         => $attributes['description'] ?? '',
      'form_id'             => $attributes['formId'] ?? '',
      'organization_fields' => $attributes['organizationFields'] ?? [],
    ];

    return self::render_form($args);
  }

  /**
   * Render the public client registration form (used by shortcode and block).
   *
   * @param array $atts
   * @return string
   */
  public static function render_form($atts = []) {
    $defaults = [
      'form_id'             => 'pn-customers-manager-client-form-' . wp_rand(1000, 99999),
      'show_title'          => true,
      'title'               => esc_html__('Alta de organización', 'pn-customers-manager'),
      'description'         => esc_html__('Completa los datos para registrar una nueva organización en el CRM.', 'pn-customers-manager'),
      'organization_fields' => [],
    ];

    $atts = shortcode_atts($defaults, $atts, 'pn-customers-manager-client-form');
    $atts['show_title'] = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $atts['show_title'] = is_null($atts['show_title']) ? $defaults['show_title'] : $atts['show_title'];

    $form_id            = sanitize_html_class($atts['form_id']);
    $selected_org_fields = array_filter(array_map('sanitize_text_field', (array) $atts['organization_fields']));
    $fields             = self::get_fields($selected_org_fields);

    ob_start();
    ?>
    <div class="pn-customers-manager-client-form-wrapper">
      <?php if (!empty($atts['show_title'])): ?>
        <h3 class="pn-customers-manager-client-form-title"><?php echo esc_html($atts['title']); ?></h3>
      <?php endif; ?>

      <?php if (!empty($atts['description'])): ?>
        <p class="pn-customers-manager-client-form-description"><?php echo esc_html($atts['description']); ?></p>
      <?php endif; ?>

      <form id="<?php echo esc_attr($form_id); ?>" class="pn-customers-manager-form pn-customers-manager-client-form" method="post" novalidate>
        <?php foreach ($fields as $field): ?>
          <?php
            ob_start();
            PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0, 0, 'full');
            $field_html = ob_get_clean();
            echo wp_kses($field_html, PN_CUSTOMERS_MANAGER_KSES);
          ?>
        <?php endforeach; ?>
      </form>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Build field configuration for the client form.
   *
   * @return array[]
   */
  private static function get_fields($selected_org_fields = []) {
    $fields = [];

    // Campos básicos obligatorios: título y descripción de la organización.
    $fields[] = [
      'id'          => 'pn_cm_organization_title',
      'label'       => esc_html__('Título de la organización', 'pn-customers-manager'),
      'input'       => 'input',
      'type'        => 'text',
      'required'    => true,
      'placeholder' => esc_html__('Nombre de la organización', 'pn-customers-manager'),
    ];

    $fields[] = [
      'id'          => 'pn_cm_organization_description',
      'label'       => esc_html__('Descripción de la organización', 'pn-customers-manager'),
      'input'       => 'textarea',
      'required'    => true,
      'placeholder' => esc_html__('Describe brevemente la organización…', 'pn-customers-manager'),
    ];

    // Si no hay campos seleccionados, solo mostramos título y descripción.
    $selected_org_fields = array_unique(array_filter($selected_org_fields));

    // Mapa de campos públicos permitidos basados en el CPT Organization.
    $allowed_meta_fields = [
      'pn_cm_organization_legal_name',
      'pn_cm_organization_trade_name',
      'pn_cm_organization_segment',
      'pn_cm_organization_industry',
      'pn_cm_organization_team_size',
      'pn_cm_organization_annual_revenue',
      'pn_cm_organization_phone',
      'pn_cm_organization_email',
      'pn_cm_organization_website',
      'pn_cm_organization_linkedin',
      'pn_cm_organization_country',
      'pn_cm_organization_region',
      'pn_cm_organization_city',
      'pn_cm_organization_address',
      'pn_cm_organization_postal_code',
      'pn_cm_organization_lead_source',
      'pn_cm_organization_lifecycle_stage',
      'pn_cm_organization_priority',
      'pn_cm_organization_health',
      'pn_cm_organization_lead_score',
      'pn_cm_organization_billing_email',
      'pn_cm_organization_billing_phone',
      'pn_cm_organization_billing_address',
      'pn_cm_organization_tags',
      'pn_cm_organization_notes',
    ];

    if (!empty($selected_org_fields)) {
      $allowed_meta_fields = array_values(array_intersect($allowed_meta_fields, $selected_org_fields));
    }

    if (!empty($allowed_meta_fields) && class_exists('PN_CUSTOMERS_MANAGER_Post_Type_organization')) {
      $organization_cpt = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
      $all_meta_fields  = $organization_cpt->pn_cm_organization_get_fields_meta(0);

      foreach ($all_meta_fields as $meta_field) {
        if (
          empty($meta_field['id']) ||
          !in_array($meta_field['id'], $allowed_meta_fields, true)
        ) {
          continue;
        }

        // No mostramos secciones ni HTML internos en el formulario público.
        if (!empty($meta_field['section']) || (isset($meta_field['input']) && $meta_field['input'] === 'html')) {
          continue;
        }

        $fields[] = $meta_field;
      }
    }

    // Campos ocultos necesarios para el sistema de formularios.
    $fields[] = [
      'id'    => 'pn_cm_organization_form',
      'input' => 'input',
      'type'  => 'hidden',
      'value' => '1',
    ];

    $fields[] = [
      'id'    => 'pn_customers_manager_ajax_nonce',
      'input' => 'input',
      'type'  => 'nonce',
    ];

    // Botón de envío: crea una nueva organización (post_new) del CPT pn_cm_organization.
    $fields[] = [
      'id'        => 'pn_cm_organization_form_submit',
      'input'     => 'input',
      'type'      => 'submit',
      'value'     => esc_html__('Crear organización', 'pn-customers-manager'),
      'subtype'   => 'post_new',
      'post_type' => 'pn_cm_organization',
    ];

    return $fields;
  }
}

