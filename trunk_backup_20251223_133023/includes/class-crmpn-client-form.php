<?php
/**
 * Client registration form helper.
 *
 * Builds the public form, registers the Gutenberg block and
 * stores submissions as WordPress users with the client role.
 *
 * @since 1.1.0
 * @package CRMPN
 */

class CUSTOMERS_MANAGER_PN_Client_Form {
  const FORM_IDENTIFIER = 'customers_manager_pn_client_form';
  const FORM_SUBTYPE    = 'customers_manager_pn_client_new';
  const CLIENT_ROLE     = 'customers_manager_pn_role_client';

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
    if (!class_exists('CUSTOMERS_MANAGER_PN_Post_Type_organization')) {
      return [];
    }

    $organization_post_type = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
    $all_fields = $organization_post_type->customers_manager_pn_organization_get_fields_meta(0);

    $available_fields = [];

    // Fields to exclude from public form
    $excluded_fields = [
      'customers_manager_pn_organization_section_basic_start',
      'customers_manager_pn_organization_section_basic_end',
      'customers_manager_pn_organization_section_advanced_start',
      'customers_manager_pn_organization_section_advanced_end',
      'customers_manager_pn_organization_section_funnel_start',
      'customers_manager_pn_organization_section_funnel_end',
      'customers_manager_pn_organization_contacts_block', // HTML field
      'customers_manager_pn_organization_owner', // Requires user selection
      'customers_manager_pn_organization_collaborators', // Requires user selection
      'customers_manager_pn_organization_funnel_id', // Requires funnel selection
      'customers_manager_pn_organization_funnel_stage', // Requires funnel selection
      'customers_manager_pn_organization_funnel_status', // Requires funnel selection
      'customers_manager_pn_organization_last_contact_date', // Internal field
      'customers_manager_pn_organization_last_contact_channel', // Internal field
      'customers_manager_pn_organization_next_action', // Internal field
      'customers_manager_pn_organization_form', // Hidden field
      'customers_manager_pn_ajax_nonce', // Nonce field
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
      'customers-manager-pn-client-form-block',
      CUSTOMERS_MANAGER_PN_URL . 'assets/js/blocks/customers-manager-pn-client-form.js',
      ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
      $this->version,
      true
    );

    if (function_exists('wp_set_script_translations')) {
      wp_set_script_translations('customers-manager-pn-client-form-block', 'customers-manager-pn');
    }

    // Localize script with organization fields from PHP
    $organization_fields = self::get_available_organization_fields();
    wp_localize_script(
      'customers-manager-pn-client-form-block',
      'crmpnOrganizationFields',
      $organization_fields
    );

    register_block_type('crmpn/client-form', [
      'editor_script'   => 'customers-manager-pn-client-form-block',
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
   * Hook into customers_manager_pn_form_save to persist client data.
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

    if (empty($key_value['customers_manager_pn_client_form_identifier']) || $key_value['customers_manager_pn_client_form_identifier'] !== self::FORM_IDENTIFIER) {
      return;
    }

    $first_name = sanitize_text_field($key_value['customers_manager_pn_client_first_name'] ?? '');
    $last_name  = sanitize_text_field($key_value['customers_manager_pn_client_last_name'] ?? '');
    $email      = sanitize_email($key_value['customers_manager_pn_client_email'] ?? '');
    $phone      = sanitize_text_field($key_value['customers_manager_pn_client_phone'] ?? '');
    $company    = sanitize_text_field($key_value['customers_manager_pn_client_company'] ?? '');
    $job_title  = sanitize_text_field($key_value['customers_manager_pn_client_job_title'] ?? '');
    $stage      = sanitize_text_field($key_value['customers_manager_pn_client_stage'] ?? '');
    $lead_src   = sanitize_text_field($key_value['customers_manager_pn_client_lead_source'] ?? '');
    $notes      = sanitize_textarea_field($key_value['customers_manager_pn_client_notes'] ?? '');
    $industry   = sanitize_text_field($key_value['customers_manager_pn_client_industry'] ?? '');
    $revenue    = sanitize_text_field($key_value['customers_manager_pn_client_annual_revenue'] ?? '');
    $team_size  = sanitize_text_field($key_value['customers_manager_pn_client_team_size'] ?? '');
    $website    = esc_url_raw($key_value['customers_manager_pn_client_website'] ?? '');
    $country    = sanitize_text_field($key_value['customers_manager_pn_client_country'] ?? '');
    $city       = sanitize_text_field($key_value['customers_manager_pn_client_city'] ?? '');
    $address    = sanitize_text_field($key_value['customers_manager_pn_client_address'] ?? '');
    $postal     = sanitize_text_field($key_value['customers_manager_pn_client_postal_code'] ?? '');
    $lead_score = sanitize_text_field($key_value['customers_manager_pn_client_lead_score'] ?? '');
    $interests  = sanitize_textarea_field($key_value['customers_manager_pn_client_interests'] ?? '');
    $tags_raw   = sanitize_text_field($key_value['customers_manager_pn_client_tags'] ?? '');
    $password   = $key_value['customers_manager_pn_client_password'] ?? '';
    $consent    = !empty($key_value['customers_manager_pn_client_consent']) && $key_value['customers_manager_pn_client_consent'] === 'on';

    if (empty($first_name) || empty($last_name) || empty($email)) {
      wp_send_json([
        'error_key'     => 'customers_manager_pn_client_form_missing_data',
        'error_content' => esc_html__('Nombre, apellidos y correo electrónico son obligatorios.', 'customers-manager-pn'),
      ]);
    }

    if (!is_email($email)) {
      wp_send_json([
        'error_key'     => 'customers_manager_pn_client_form_email_invalid',
        'error_content' => esc_html__('Introduce un correo electrónico válido.', 'customers-manager-pn'),
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
          'error_key'     => 'customers_manager_pn_client_form_user_error',
          'error_content' => esc_html__('No se pudo actualizar el usuario existente.', 'customers-manager-pn'),
        ]);
      }
    } else {
      $user_login = sanitize_user(sanitize_title($first_name . '-' . $last_name));
      if (empty($user_login)) {
        $user_login = sanitize_user('customers-manager-pn-client-' . wp_generate_password(6, false));
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
          'error_key'     => 'customers_manager_pn_client_form_user_error',
          'error_content' => esc_html__('No se pudo crear el usuario del cliente.', 'customers-manager-pn'),
        ]);
      }
    }

    $user = new WP_User($user_id);
    if ($user && !in_array(self::CLIENT_ROLE, $user->roles, true)) {
      $user->add_role(self::CLIENT_ROLE);
    }

    $meta_values = [
      'customers_manager_pn_client_phone'        => $phone,
      'customers_manager_pn_client_company'      => $company,
      'customers_manager_pn_client_job_title'    => $job_title,
      'customers_manager_pn_client_stage'        => $stage,
      'customers_manager_pn_client_lead_source'  => $lead_src,
      'customers_manager_pn_client_notes'        => $notes,
      'customers_manager_pn_client_industry'     => $industry,
      'customers_manager_pn_client_annual_revenue' => $revenue,
      'customers_manager_pn_client_team_size'    => $team_size,
      'customers_manager_pn_client_website'      => $website,
      'customers_manager_pn_client_country'      => $country,
      'customers_manager_pn_client_city'         => $city,
      'customers_manager_pn_client_address'      => $address,
      'customers_manager_pn_client_postal_code'  => $postal,
      'customers_manager_pn_client_lead_score'   => $lead_score,
      'customers_manager_pn_client_interests'    => $interests,
      'customers_manager_pn_client_consent'      => $consent ? 'yes' : 'no',
    ];

    foreach ($meta_values as $meta_key => $meta_value) {
      update_user_meta($user_id, $meta_key, $meta_value);
    }

    if (!empty($tags_raw)) {
      $tags = array_values(array_filter(array_map('trim', explode(',', $tags_raw))));
      update_user_meta($user_id, 'customers_manager_pn_client_tags', $tags);
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
      'form_id'             => 'customers-manager-pn-client-form-' . wp_rand(1000, 99999),
      'show_title'          => true,
      'title'               => esc_html__('Alta de organización', 'customers-manager-pn'),
      'description'         => esc_html__('Completa los datos para registrar una nueva organización en el CRM.', 'customers-manager-pn'),
      'organization_fields' => [],
    ];

    $atts = shortcode_atts($defaults, $atts, 'customers-manager-pn-client-form');
    $atts['show_title'] = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $atts['show_title'] = is_null($atts['show_title']) ? $defaults['show_title'] : $atts['show_title'];

    $form_id            = sanitize_html_class($atts['form_id']);
    $selected_org_fields = array_filter(array_map('sanitize_text_field', (array) $atts['organization_fields']));
    $fields             = self::get_fields($selected_org_fields);

    ob_start();
    ?>
    <div class="customers-manager-pn-client-form-wrapper">
      <?php if (!empty($atts['show_title'])): ?>
        <h3 class="customers-manager-pn-client-form-title"><?php echo esc_html($atts['title']); ?></h3>
      <?php endif; ?>

      <?php if (!empty($atts['description'])): ?>
        <p class="customers-manager-pn-client-form-description"><?php echo esc_html($atts['description']); ?></p>
      <?php endif; ?>

      <form id="<?php echo esc_attr($form_id); ?>" class="customers-manager-pn-form customers-manager-pn-client-form" method="post" novalidate>
        <?php foreach ($fields as $field): ?>
          <?php echo wp_kses(CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_input_wrapper_builder($field, 'post', 0, 0, 'full'), CUSTOMERS_MANAGER_PN_KSES); ?>
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
      'id'          => 'customers_manager_pn_organization_title',
      'label'       => esc_html__('Título de la organización', 'customers-manager-pn'),
      'input'       => 'input',
      'type'        => 'text',
      'required'    => true,
      'placeholder' => esc_html__('Nombre de la organización', 'customers-manager-pn'),
    ];

    $fields[] = [
      'id'          => 'customers_manager_pn_organization_description',
      'label'       => esc_html__('Descripción de la organización', 'customers-manager-pn'),
      'input'       => 'textarea',
      'required'    => true,
      'placeholder' => esc_html__('Describe brevemente la organización…', 'customers-manager-pn'),
    ];

    // Si no hay campos seleccionados, solo mostramos título y descripción.
    $selected_org_fields = array_unique(array_filter($selected_org_fields));

    // Mapa de campos públicos permitidos basados en el CPT Organization.
    $allowed_meta_fields = [
      'customers_manager_pn_organization_legal_name',
      'customers_manager_pn_organization_trade_name',
      'customers_manager_pn_organization_segment',
      'customers_manager_pn_organization_industry',
      'customers_manager_pn_organization_team_size',
      'customers_manager_pn_organization_annual_revenue',
      'customers_manager_pn_organization_phone',
      'customers_manager_pn_organization_email',
      'customers_manager_pn_organization_website',
      'customers_manager_pn_organization_linkedin',
      'customers_manager_pn_organization_country',
      'customers_manager_pn_organization_region',
      'customers_manager_pn_organization_city',
      'customers_manager_pn_organization_address',
      'customers_manager_pn_organization_postal_code',
      'customers_manager_pn_organization_lead_source',
      'customers_manager_pn_organization_lifecycle_stage',
      'customers_manager_pn_organization_priority',
      'customers_manager_pn_organization_health',
      'customers_manager_pn_organization_lead_score',
      'customers_manager_pn_organization_billing_email',
      'customers_manager_pn_organization_billing_phone',
      'customers_manager_pn_organization_billing_address',
      'customers_manager_pn_organization_tags',
      'customers_manager_pn_organization_notes',
    ];

    if (!empty($selected_org_fields)) {
      $allowed_meta_fields = array_values(array_intersect($allowed_meta_fields, $selected_org_fields));
    }

    if (!empty($allowed_meta_fields) && class_exists('CUSTOMERS_MANAGER_PN_Post_Type_organization')) {
      $organization_cpt = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
      $all_meta_fields  = $organization_cpt->customers_manager_pn_organization_get_fields_meta(0);

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
      'id'    => 'customers_manager_pn_organization_form',
      'input' => 'input',
      'type'  => 'hidden',
      'value' => '1',
    ];

    $fields[] = [
      'id'    => 'customers_manager_pn_ajax_nonce',
      'input' => 'input',
      'type'  => 'nonce',
    ];

    // Botón de envío: crea una nueva organización (post_new) del CPT customers_manager_pn_organization.
    $fields[] = [
      'id'        => 'customers_manager_pn_organization_form_submit',
      'input'     => 'input',
      'type'      => 'submit',
      'value'     => esc_html__('Crear organización', 'customers-manager-pn'),
      'subtype'   => 'post_new',
      'post_type' => 'customers_manager_pn_organization',
    ];

    return $fields;
  }
}

