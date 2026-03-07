<?php
/**
 * Settings manager.
 *
 * This class defines plugin settings, both in dashboard or in front-end.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Settings {
  public function pn_customers_manager_get_options() {
    $pn_customers_manager_options = [];

    // System section
    $pn_customers_manager_options['pn_customers_manager_system_section_start'] = [
      'id' => 'pn_customers_manager_system_section_start',
      'section' => 'start',
      'label' => __('System', 'pn-customers-manager'),
      'description' => __('General plugin settings and configuration.', 'pn-customers-manager'),
    ];

    foreach (PN_CUSTOMERS_MANAGER_CPTS as $pn_customers_manager_cpt_key => $pn_customers_manager_cpt_value) {
      $pn_customers_manager_options['pn_customers_manager_' . $pn_customers_manager_cpt_key . '_slug'] = [
        'id' => 'pn_customers_manager_' . $pn_customers_manager_cpt_key . '_slug',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => sprintf(
          /* translators: %s: Post type name */
          __('%s slug', 'pn-customers-manager'),
          $pn_customers_manager_cpt_value
        ),
        'placeholder' => sprintf(
          /* translators: %s: Post type name */
          __('%s slug', 'pn-customers-manager'),
          $pn_customers_manager_cpt_value
        ),
        'description' => sprintf(
          /* translators: %1$s: Post type name, %2$s: Archive URL, %3$s: Archive URL, %4$s: Single post URL */
          __('This option sets the slug of the %1$s archive page, and the %1$s pages. By default they will be:', 'pn-customers-manager'),
          $pn_customers_manager_cpt_value
        ) . '<br><a href="' . esc_url(home_url('/' . $pn_customers_manager_cpt_key . '-slug')) . '" target="_blank">' . esc_url(home_url('/' . $pn_customers_manager_cpt_key . '-slug')) . '</a><br>' . esc_url(home_url('/' . $pn_customers_manager_cpt_key . '-slug/' . $pn_customers_manager_cpt_key)),
      ];
    }

    $pn_customers_manager_options['pn_customers_manager_options_remove'] = [
      'id' => 'pn_customers_manager_options_remove',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Remove plugin options on deactivation', 'pn-customers-manager'),
      'description' => __('If you activate this option the plugin will remove all options on deactivation. Please, be careful. This process cannot be undone.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_system_section_end'] = [
      'id' => 'pn_customers_manager_system_section_end',
      'section' => 'end',
    ];

    // Color customization section
    $pn_customers_manager_options['pn_customers_manager_colors_section_start'] = [
      'id' => 'pn_customers_manager_colors_section_start',
      'section' => 'start',
      'label' => __('Colors', 'pn-customers-manager'),
      'description' => __('Customize the colors used throughout the plugin by modifying the CSS root variables.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_color_main'] = [
      'id' => 'pn_customers_manager_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Color', 'pn-customers-manager'),
      'description' => __('Primary color used for text, backgrounds, and borders (default: #0000aa)', 'pn-customers-manager'),
      'value' => '#0000aa',
    ];

    $pn_customers_manager_options['pn_customers_manager_bg_color_main'] = [
      'id' => 'pn_customers_manager_bg_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Background Color', 'pn-customers-manager'),
      'description' => __('Primary background color (default: #0000aa)', 'pn-customers-manager'),
      'value' => '#0000aa',
    ];

    $pn_customers_manager_options['pn_customers_manager_border_color_main'] = [
      'id' => 'pn_customers_manager_border_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Border Color', 'pn-customers-manager'),
      'description' => __('Primary border color (default: #0000aa)', 'pn-customers-manager'),
      'value' => '#0000aa',
    ];

    $pn_customers_manager_options['pn_customers_manager_color_main_alt'] = [
      'id' => 'pn_customers_manager_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Main Color', 'pn-customers-manager'),
      'description' => __('Alternative color for text, backgrounds, and borders (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $pn_customers_manager_options['pn_customers_manager_bg_color_main_alt'] = [
      'id' => 'pn_customers_manager_bg_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Background Color', 'pn-customers-manager'),
      'description' => __('Alternative background color (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $pn_customers_manager_options['pn_customers_manager_border_color_main_alt'] = [
      'id' => 'pn_customers_manager_border_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Border Color', 'pn-customers-manager'),
      'description' => __('Alternative border color (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $pn_customers_manager_options['pn_customers_manager_color_main_blue'] = [
      'id' => 'pn_customers_manager_color_main_blue',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Blue Color', 'pn-customers-manager'),
      'description' => __('Blue accent color (default: #6e6eff)', 'pn-customers-manager'),
      'value' => '#6e6eff',
    ];

    $pn_customers_manager_options['pn_customers_manager_color_main_grey'] = [
      'id' => 'pn_customers_manager_color_main_grey',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Grey Color', 'pn-customers-manager'),
      'description' => __('Grey color for backgrounds (default: #f5f5f5)', 'pn-customers-manager'),
      'value' => '#f5f5f5',
    ];

    $pn_customers_manager_options['pn_customers_manager_colors_section_end'] = [
      'id' => 'pn_customers_manager_colors_section_end',
      'section' => 'end',
    ];

    // Commercial section
    $pn_customers_manager_options['pn_customers_manager_commercial_section_start'] = [
      'id' => 'pn_customers_manager_commercial_section_start',
      'section' => 'start',
      'label' => __('Comercial', 'pn-customers-manager'),
      'description' => __('Configuracion del sistema de agentes comerciales.', 'pn-customers-manager'),
    ];

    $pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
    $page_options = ['' => __('-- Seleccionar pagina --', 'pn-customers-manager')];
    if (!empty($pages)) {
      foreach ($pages as $page) {
        $page_options[$page->ID] = $page->post_title;
      }
    }

    $pn_customers_manager_options['pn_customers_manager_commercial_crm_page'] = [
      'id' => 'pn_customers_manager_commercial_crm_page',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'select',
      'options' => $page_options,
      'label' => __('Pagina del CRM', 'pn-customers-manager'),
      'description' => __('Selecciona la pagina del CRM a la que accederan los agentes comerciales aprobados.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_commercial_section_end'] = [
      'id' => 'pn_customers_manager_commercial_section_end',
      'section' => 'end',
    ];

    // Email Campaigns section
    $email_campaigns_options = PN_CUSTOMERS_MANAGER_Email_Campaigns::get_settings_section();
    $pn_customers_manager_options = array_merge($pn_customers_manager_options, $email_campaigns_options);

    // Referral section
    $pn_customers_manager_options['pn_customers_manager_referral_section_start'] = [
      'id' => 'pn_customers_manager_referral_section_start',
      'section' => 'start',
      'label' => __('Referidos', 'pn-customers-manager'),
      'description' => __('Configuracion del sistema de referidos y codigo QR.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_enabled'] = [
      'id' => 'pn_customers_manager_referral_enabled',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'value' => 'on',
      'label' => __('Activar sistema de referidos', 'pn-customers-manager'),
      'description' => __('Activa el sistema de referidos con codigo QR para que los usuarios puedan invitar a otros. Los administradores siempre veran el panel de referidos aunque esta opcion este desactivada.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_show_ranking'] = [
      'id' => 'pn_customers_manager_referral_show_ranking',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Mostrar ranking de principales referentes', 'pn-customers-manager'),
      'description' => __('Muestra el listado de principales referentes en el panel de referidos.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_share_text'] = [
      'id' => 'pn_customers_manager_referral_share_text',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Texto para compartir enlace de referido', 'pn-customers-manager'),
      'description' => __('Texto por defecto que acompana al enlace de referido al compartir en redes sociales. El enlace se anade automaticamente al final. Los usuarios pueden personalizar su propio texto desde su panel.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_qr_branding'] = [
      'id' => 'pn_customers_manager_referral_qr_branding',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'image',
      'label' => __('Logo para codigo QR', 'pn-customers-manager'),
      'description' => __('Selecciona una imagen que se mostrara en el centro del codigo QR. Recomendado: cuadrada, minimo 80x80px.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_reminder_max_sends'] = [
      'id' => 'pn_customers_manager_referral_reminder_max_sends',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '3',
      'label' => __('Numero maximo de recordatorios por referido', 'pn-customers-manager'),
      'description' => __('Cantidad maxima de emails de recordatorio que se enviaran a cada referido pendiente. Establecer en 0 para desactivar los recordatorios.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_reminder_frequency'] = [
      'id' => 'pn_customers_manager_referral_reminder_frequency',
      'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
      'input' => 'select',
      'options' => [
        '1'  => __('Cada 1 dia', 'pn-customers-manager'),
        '2'  => __('Cada 2 dias', 'pn-customers-manager'),
        '3'  => __('Cada 3 dias', 'pn-customers-manager'),
        '5'  => __('Cada 5 dias', 'pn-customers-manager'),
        '7'  => __('Cada 7 dias', 'pn-customers-manager'),
        '14' => __('Cada 14 dias', 'pn-customers-manager'),
        '30' => __('Cada 30 dias', 'pn-customers-manager'),
      ],
      'value' => '7',
      'label' => __('Frecuencia de recordatorios', 'pn-customers-manager'),
      'description' => __('Intervalo de dias entre cada recordatorio enviado a referidos pendientes.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_bizcard_phrases'] = [
      'id' => 'pn_customers_manager_referral_bizcard_phrases',
      'input' => 'html_multi',
      'label' => __('Frases para tarjeta de visita', 'pn-customers-manager'),
      'description' => __('Frases predefinidas que los usuarios pueden seleccionar para el reverso de su tarjeta de visita.', 'pn-customers-manager'),
      'html_multi_fields' => [
        [
          'id'          => 'pn_customers_manager_referral_bizcard_phrase_text',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'text',
          'label'       => __('Frase', 'pn-customers-manager'),
          'placeholder' => __('Escribe una frase inspiradora...', 'pn-customers-manager'),
        ],
      ],
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_section_end'] = [
      'id' => 'pn_customers_manager_referral_section_end',
      'section' => 'end',
    ];

    // User Roles section
    $pn_customers_manager_options['pn_customers_manager_roles_section_start'] = [
      'id' => 'pn_customers_manager_roles_section_start',
      'section' => 'start',
      'label' => __('Roles de usuario', 'pn-customers-manager'),
      'description' => __('Gestiona los roles de usuario del plugin. Puedes asignar o eliminar roles a los usuarios registrados.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_role_selector_manager'] = [
      'id' => 'pn_customers_manager_role_selector_manager',
      'input' => 'user_role_selector',
      'label' => __('PN Customers Manager', 'pn-customers-manager'),
      'role' => 'pn_customers_manager_role_manager',
      'role_label' => __('PN Customers Manager', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_role_selector_client'] = [
      'id' => 'pn_customers_manager_role_selector_client',
      'input' => 'user_role_selector',
      'label' => __('Client - PN', 'pn-customers-manager'),
      'role' => 'pn_customers_manager_role_client',
      'role_label' => __('Client - PN', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_role_selector_commercial'] = [
      'id' => 'pn_customers_manager_role_selector_commercial',
      'input' => 'user_role_selector',
      'label' => __('Comercial - PN', 'pn-customers-manager'),
      'role' => 'pn_customers_manager_role_commercial',
      'role_label' => __('Comercial - PN', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_roles_section_end'] = [
      'id' => 'pn_customers_manager_roles_section_end',
      'section' => 'end',
    ];

    $pn_customers_manager_options['pn_customers_manager_nonce'] = [
      'id' => 'pn_customers_manager_nonce',
      'input' => 'input',
      'type' => 'nonce',
    ];
    $pn_customers_manager_options['pn_customers_manager_submit'] = [
      'id' => 'pn_customers_manager_submit',
      'input' => 'input',
      'type' => 'submit',
      'value' => __('Save options', 'pn-customers-manager'),
    ];

    return $pn_customers_manager_options;
  }

	/**
	 * Administrator menu.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_admin_menu() {
    // Determine the capability to use for the main menu
    // Use the first available capability, or manage_options as fallback
    $menu_cap = 'manage_options';
    if (current_user_can('edit_pn_cm_funnel')) {
      $menu_cap = 'edit_pn_cm_funnel';
    } elseif (current_user_can('edit_pn_cm_organization')) {
      $menu_cap = 'edit_pn_cm_organization';
    }
    
    // Check if user has any of the required capabilities
    $has_cap = current_user_can('edit_pn_cm_funnel') || current_user_can('edit_pn_cm_organization') || current_user_can('manage_options');
    
    if (!$has_cap) {
      return;
    }

    add_menu_page(
      esc_html__('PN Customers Manager', 'pn-customers-manager'), 
      esc_html__('PN Customers Manager', 'pn-customers-manager'), 
      $menu_cap, 
      'pn_customers_manager_options', 
      [$this, 'pn_customers_manager_options'], 
      esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/pn-customers-manager-menu-icon.svg'),
    );
		
    add_submenu_page(
      'pn_customers_manager_options',
      esc_html__('Settings', 'pn-customers-manager'), 
      esc_html__('Settings', 'pn-customers-manager'), 
      $menu_cap, 
      'pn_customers_manager_options', 
      [$this, 'pn_customers_manager_options'], 
    );

    // Add Funnels submenu (only if user has the capability)
    if (current_user_can('edit_pn_cm_funnel')) {
      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Funnels', 'pn-customers-manager'),
        esc_html__('Funnels', 'pn-customers-manager'),
        'edit_pn_cm_funnel',
        'edit.php?post_type=pn_cm_funnel'
      );
    }

    // Add Organizations submenu (only if user has the capability)
    if (current_user_can('edit_pn_cm_organization')) {
      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Organizations', 'pn-customers-manager'),
        esc_html__('Organizations', 'pn-customers-manager'),
        'edit_pn_cm_organization',
        'edit.php?post_type=pn_cm_organization'
      );
    }

    // Add Commercial Agents submenu
    if (current_user_can('manage_options')) {
      $pending_commercial = PN_CUSTOMERS_MANAGER_Commercial::get_pending_count();
      $commercial_badge = $pending_commercial > 0
        ? ' <span class="awaiting-mod pn-customers-manager-menu-badge">' . esc_html($pending_commercial) . '</span>'
        : '';

      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Agentes Comerciales', 'pn-customers-manager'),
        esc_html__('Agentes Comerciales', 'pn-customers-manager') . $commercial_badge,
        'manage_options',
        'pn_customers_manager_commercial_agents',
        ['PN_CUSTOMERS_MANAGER_Commercial', 'render_admin_commercial_agents']
      );
    }

    // Add Contact Messages submenu
    if (current_user_can('manage_options')) {
      $unread = PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count();
      $badge  = $unread > 0
        ? ' <span class="awaiting-mod pn-customers-manager-menu-badge">' . esc_html($unread) . '</span>'
        : '';

      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Mensajes', 'pn-customers-manager'),
        esc_html__('Mensajes', 'pn-customers-manager') . $badge,
        'manage_options',
        'pn_customers_manager_contact_messages',
        ['PN_CUSTOMERS_MANAGER_Contact_Messages', 'render_page']
      );
    }
	}

	public function pn_customers_manager_options() {
    $organization_page = self::pn_customers_manager_find_organization_page();
	  ?>
	    <div class="pn-customers-manager-options pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mt-50 pn-customers-manager-mb-50">
        <img src="<?php echo esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/banner-1544x500.png'); ?>" alt="<?php esc_html_e('Plugin main Banner', 'pn-customers-manager'); ?>" title="<?php esc_html_e('Plugin main Banner', 'pn-customers-manager'); ?>" class="pn-customers-manager-width-100-percent pn-customers-manager-border-radius-20 pn-customers-manager-mb-30">
        <h1 class="pn-customers-manager-mb-30"><?php esc_html_e('PN Customers Manager Settings', 'pn-customers-manager'); ?></h1>
        <?php if (!$organization_page): ?>
          <div class="pn-customers-manager-options-fields pn-customers-manager-mb-30">
            <div class="pn-customers-manager-p-30">
              <p class="pn-customers-manager-mb-15">
                <?php esc_html_e('No page with the Organizations block has been detected on your site. Click the button below to automatically create a new page with the Organizations block already inserted. Once the page is created, you will be redirected to its editor so you can review it and publish it or make any changes you need.', 'pn-customers-manager'); ?>
              </p>
              <button type="button" id="pn-customers-manager-create-organization-page" class="pn-customers-manager-btn">
                <?php esc_html_e('Create Organizations page', 'pn-customers-manager'); ?>
              </button>
            </div>
          </div>
          <script>
            document.getElementById('pn-customers-manager-create-organization-page').addEventListener('click', function() {
              var btn = this;
              btn.disabled = true;
              btn.textContent = '<?php echo esc_js(__('Creating page...', 'pn-customers-manager')); ?>';

              var data = new FormData();
              data.append('action', 'pn_customers_manager_ajax');
              data.append('pn_customers_manager_ajax_type', 'pn_cm_create_organization_page');
              data.append('pn_customers_manager_ajax_nonce', '<?php echo esc_js(wp_create_nonce('pn-customers-manager-nonce')); ?>');

              fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                body: data
              })
              .then(function(response) { return response.json(); })
              .then(function(result) {
                if (result.error_key === '' && result.redirect_url) {
                  window.location.href = result.redirect_url;
                } else {
                  btn.disabled = false;
                  btn.textContent = '<?php echo esc_js(__('Create Organizations page', 'pn-customers-manager')); ?>';
                  alert(result.error_content || '<?php echo esc_js(__('An error occurred while creating the page.', 'pn-customers-manager')); ?>');
                }
              })
              .catch(function() {
                btn.disabled = false;
                btn.textContent = '<?php echo esc_js(__('Create Organizations page', 'pn-customers-manager')); ?>';
                alert('<?php echo esc_js(__('An error occurred while creating the page.', 'pn-customers-manager')); ?>');
              });
            });
          </script>
        <?php endif; ?>
        <div class="pn-customers-manager-options-fields pn-customers-manager-mb-30">
          <form action="" method="post" id="pn-customers-manager-form-setting" class="pn-customers-manager-form pn-customers-manager-p-30">
          <?php
            $options = self::pn_customers_manager_get_options();

            foreach ($options as $pn_customers_manager_option) {
              PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_option, 'option', 0, 0, 'half');
            }
          ?>
          </form>
        </div>
      </div>
	  <?php
	}

  public static function pn_customers_manager_find_organization_page() {
    $pages = get_posts([
      'post_type'   => 'page',
      'post_status' => ['publish', 'draft', 'private'],
      'numberposts' => -1,
      'fields'      => 'ids',
    ]);

    foreach ($pages as $page_id) {
      $content = get_post_field('post_content', $page_id);

      if (
        has_shortcode($content, 'pn-customers-manager-organization-list') ||
        strpos($content, '<!-- wp:pn-customers-manager/organization-list') !== false
      ) {
        return $page_id;
      }
    }

    return false;
  }

  public function pn_customers_manager_activated_plugin($plugin) {
    if($plugin == 'pn-customers-manager/pn-customers-manager.php') {
      if (get_option('pn_customers_manager_pages_funnel') && get_option('pn_customers_manager_url_main')) {
        if (!get_transient('pn_customers_manager_just_activated') && !defined('DOING_AJAX')) {
          set_transient('pn_customers_manager_just_activated', true, 30);
        }
      }
    }
  }

  public function pn_customers_manager_check_activation() {
    // Only run in admin and not during AJAX requests
    if (!is_admin() || defined('DOING_AJAX')) {
      return;
    }

    // Check if we're already in the redirection process
    if (get_option('pn_customers_manager_redirecting')) {
      delete_option('pn_customers_manager_redirecting');
      return;
    }

    if (get_transient('pn_customers_manager_just_activated')) {
      $target_url = admin_url('admin.php?page=pn_customers_manager_options');
      
      if ($target_url) {
        // Mark that we're in the redirection process
        update_option('pn_customers_manager_redirecting', true);
        
        // Remove the transient
        delete_transient('pn_customers_manager_just_activated');
        
        // Redirect and exit
        wp_safe_redirect(esc_url($target_url));
        exit;
      }
    }
  }

  /**
   * Adds the Settings link to the plugin list
   */
  public function pn_customers_manager_plugin_action_links($links) {
      $settings_link = '<a href="admin.php?page=pn_customers_manager_options">' . esc_html__('Settings', 'pn-customers-manager') . '</a>';
      array_unshift($links, $settings_link);
      
      return $links;
  }

}