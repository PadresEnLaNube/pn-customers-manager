<?php
/**
 * Settings manager.
 *
 * This class defines plugin settings, both in dashboard or in front-end.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Settings {
  public function customers_manager_pn_get_options() {
    $customers_manager_pn_options = [];
    
    foreach (CUSTOMERS_MANAGER_PN_CPTS as $customers_manager_pn_cpt) {
      $customers_manager_pn_options['customers_manager_pn_' . $customers_manager_pn_cpt . '_slug'] = [
        'id' => 'customers_manager_pn_' . $customers_manager_pn_cpt . '_slug',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => __($customers_manager_pn_cpt . ' slug', 'customers-manager-pn'),
        'placeholder' => __($customers_manager_pn_cpt . ' slug', 'customers-manager-pn'),
        'description' => __('This option sets the slug of the ' . $customers_manager_pn_cpt . ' archive page, and the ' . $customers_manager_pn_cpt . ' pages. By default they will be:', 'customers-manager-pn') . '<br><a href="' . esc_url(home_url('/' . $customers_manager_pn_cpt . '-slug')) . '" target="_blank">' . esc_url(home_url('/' . $customers_manager_pn_cpt . '-slug')) . '</a><br>' . esc_url(home_url('/' . $customers_manager_pn_cpt . '-slug/' . $customers_manager_pn_cpt)),
      ];
    }

    $customers_manager_pn_options['customers_manager_pn_options_remove'] = [
      'id' => 'customers_manager_pn_options_remove',
      'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Remove plugin options on deactivation', 'customers-manager-pn'),
      'description' => __('If you activate this option the plugin will remove all options on deactivation. Please, be careful. This process cannot be undone.', 'customers-manager-pn'),
    ];
    $customers_manager_pn_options['customers_manager_pn_nonce'] = [
      'id' => 'customers_manager_pn_nonce',
      'input' => 'input',
      'type' => 'nonce',
    ];
    $customers_manager_pn_options['customers_manager_pn_submit'] = [
      'id' => 'customers_manager_pn_submit',
      'input' => 'input',
      'type' => 'submit',
      'value' => __('Save options', 'customers-manager-pn'),
    ];

    return $customers_manager_pn_options;
  }

	/**
	 * Administrator menu.
	 *
	 * @since    1.0.0
	 */
	public function customers_manager_pn_admin_menu() {
    add_menu_page(
      esc_html__('PN Customers Manager', 'customers-manager-pn'), 
      esc_html__('PN Customers Manager', 'customers-manager-pn'), 
      'edit_customers_manager_pn_funnel', 
      'customers_manager_pn_options', 
      [$this, 'customers_manager_pn_options'], 
      esc_url(CUSTOMERS_MANAGER_PN_URL . 'assets/media/customers-manager-pn-menu-icon.svg'),
    );
		
    add_submenu_page(
      'customers_manager_pn_options',
      esc_html__('Settings', 'customers-manager-pn'), 
      esc_html__('Settings', 'customers-manager-pn'), 
      'edit_customers_manager_pn_funnel', 
      'customers_manager_pn_options', 
      [$this, 'customers_manager_pn_options'], 
    );

    // CPTs already appear automatically as submenus (show_in_menu => customers_manager_pn_options).
    // The explicit submenu entries were removed to avoid duplicates.
	}

	public function customers_manager_pn_options() {
	  ?>
	    <div class="customers-manager-pn-options customers-manager-pn-max-width-1000 customers-manager-pn-margin-auto customers-manager-pn-mt-50 customers-manager-pn-mb-50">
        <img src="<?php echo esc_url(CUSTOMERS_MANAGER_PN_URL . 'assets/media/banner-1544x500.png'); ?>" alt="<?php esc_html_e('Plugin main Banner', 'customers-manager-pn'); ?>" title="<?php esc_html_e('Plugin main Banner', 'customers-manager-pn'); ?>" class="customers-manager-pn-width-100-percent customers-manager-pn-border-radius-20 customers-manager-pn-mb-30">
        <h1 class="customers-manager-pn-mb-30"><?php esc_html_e('PN Customers Manager Settings', 'customers-manager-pn'); ?></h1>
        <div class="customers-manager-pn-options-fields customers-manager-pn-mb-30">
          <form action="" method="post" id="customers-manager-pn-form-setting" class="customers-manager-pn-form customers-manager-pn-p-30">
          <?php 
            $options = self::customers_manager_pn_get_options();

            foreach ($options as $customers_manager_pn_option) {
              CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_option, 'option', 0, 0, 'half');
            }
          ?>
          </form> 
        </div>
      </div>
	  <?php
	}

  public function customers_manager_pn_activated_plugin($plugin) {
    if($plugin == 'crmpn/crmpn.php') {
      if (get_option('customers_manager_pn_pages_funnel') && get_option('customers_manager_pn_url_main')) {
        if (!get_transient('customers_manager_pn_just_activated') && !defined('DOING_AJAX')) {
          set_transient('customers_manager_pn_just_activated', true, 30);
        }
      }
    }
  }

  public function customers_manager_pn_check_activation() {
    // Only run in admin and not during AJAX requests
    if (!is_admin() || defined('DOING_AJAX')) {
      return;
    }

    // Check if we're already in the redirection process
    if (get_option('customers_manager_pn_redirecting')) {
      delete_option('customers_manager_pn_redirecting');
      return;
    }

    if (get_transient('customers_manager_pn_just_activated')) {
      $target_url = admin_url('admin.php?page=customers_manager_pn_options');
      
      if ($target_url) {
        // Mark that we're in the redirection process
        update_option('customers_manager_pn_redirecting', true);
        
        // Remove the transient
        delete_transient('customers_manager_pn_just_activated');
        
        // Redirect and exit
        wp_safe_redirect(esc_url($target_url));
        exit;
      }
    }
  }

  /**
   * Adds the Settings link to the plugin list
   */
  public function customers_manager_pn_plugin_action_links($links) {
      $settings_link = '<a href="admin.php?page=customers_manager_pn_options">' . esc_html__('Settings', 'customers-manager-pn') . '</a>';
      array_unshift($links, $settings_link);
      
      return $links;
  }

}