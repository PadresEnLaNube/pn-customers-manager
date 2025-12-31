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
  public function PN_CUSTOMERS_MANAGER_get_options() {
    $PN_CUSTOMERS_MANAGER_options = [];
    
    foreach (PN_CUSTOMERS_MANAGER_CPTS as $PN_CUSTOMERS_MANAGER_cpt) {
      $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_' . $PN_CUSTOMERS_MANAGER_cpt . '_slug'] = [
        'id' => 'PN_CUSTOMERS_MANAGER_' . $PN_CUSTOMERS_MANAGER_cpt . '_slug',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => sprintf(
          /* translators: %s: Post type name */
          __('%s slug', 'pn-customers-manager'),
          $PN_CUSTOMERS_MANAGER_cpt
        ),
        'placeholder' => sprintf(
          /* translators: %s: Post type name */
          __('%s slug', 'pn-customers-manager'),
          $PN_CUSTOMERS_MANAGER_cpt
        ),
        'description' => sprintf(
          /* translators: %1$s: Post type name, %2$s: Archive URL, %3$s: Archive URL, %4$s: Single post URL */
          __('This option sets the slug of the %1$s archive page, and the %1$s pages. By default they will be:', 'pn-customers-manager'),
          $PN_CUSTOMERS_MANAGER_cpt
        ) . '<br><a href="' . esc_url(home_url('/' . $PN_CUSTOMERS_MANAGER_cpt . '-slug')) . '" target="_blank">' . esc_url(home_url('/' . $PN_CUSTOMERS_MANAGER_cpt . '-slug')) . '</a><br>' . esc_url(home_url('/' . $PN_CUSTOMERS_MANAGER_cpt . '-slug/' . $PN_CUSTOMERS_MANAGER_cpt)),
      ];
    }

    // Color customization section
    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_colors_section_start'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_colors_section_start',
      'section' => 'start',
      'label' => __('Color Customization', 'pn-customers-manager'),
      'description' => __('Customize the colors used throughout the plugin by modifying the CSS root variables.', 'pn-customers-manager'),
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_color_main'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Color', 'pn-customers-manager'),
      'description' => __('Primary color used for text, backgrounds, and borders (default: #d45500)', 'pn-customers-manager'),
      'value' => '#d45500',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_bg_color_main'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_bg_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Background Color', 'pn-customers-manager'),
      'description' => __('Primary background color (default: #d45500)', 'pn-customers-manager'),
      'value' => '#d45500',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_border_color_main'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_border_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Border Color', 'pn-customers-manager'),
      'description' => __('Primary border color (default: #d45500)', 'pn-customers-manager'),
      'value' => '#d45500',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_color_main_alt'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Main Color', 'pn-customers-manager'),
      'description' => __('Alternative color for text, backgrounds, and borders (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_bg_color_main_alt'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_bg_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Background Color', 'pn-customers-manager'),
      'description' => __('Alternative background color (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_border_color_main_alt'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_border_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Border Color', 'pn-customers-manager'),
      'description' => __('Alternative border color (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_color_main_blue'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_color_main_blue',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Blue Color', 'pn-customers-manager'),
      'description' => __('Blue accent color (default: #6e6eff)', 'pn-customers-manager'),
      'value' => '#6e6eff',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_color_main_grey'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_color_main_grey',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Grey Color', 'pn-customers-manager'),
      'description' => __('Grey color for backgrounds (default: #f5f5f5)', 'pn-customers-manager'),
      'value' => '#f5f5f5',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_colors_section_end'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_colors_section_end',
      'section' => 'end',
    ];

    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_options_remove'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_options_remove',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Remove plugin options on deactivation', 'pn-customers-manager'),
      'description' => __('If you activate this option the plugin will remove all options on deactivation. Please, be careful. This process cannot be undone.', 'pn-customers-manager'),
    ];
    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_nonce'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_nonce',
      'input' => 'input',
      'type' => 'nonce',
    ];
    $PN_CUSTOMERS_MANAGER_options['PN_CUSTOMERS_MANAGER_submit'] = [
      'id' => 'PN_CUSTOMERS_MANAGER_submit',
      'input' => 'input',
      'type' => 'submit',
      'value' => __('Save options', 'pn-customers-manager'),
    ];

    return $PN_CUSTOMERS_MANAGER_options;
  }

	/**
	 * Administrator menu.
	 *
	 * @since    1.0.0
	 */
	public function PN_CUSTOMERS_MANAGER_admin_menu() {
    // Determine the capability to use for the main menu
    // Use the first available capability, or manage_options as fallback
    $menu_cap = 'manage_options';
    if (current_user_can('edit_cm_pn_funnel')) {
      $menu_cap = 'edit_cm_pn_funnel';
    } elseif (current_user_can('edit_cm_pn_org')) {
      $menu_cap = 'edit_cm_pn_org';
    }
    
    // Check if user has any of the required capabilities
    $has_cap = current_user_can('edit_cm_pn_funnel') || current_user_can('edit_cm_pn_org') || current_user_can('manage_options');
    
    if (!$has_cap) {
      return;
    }

    add_menu_page(
      esc_html__('PN Customers Manager', 'pn-customers-manager'), 
      esc_html__('PN Customers Manager', 'pn-customers-manager'), 
      $menu_cap, 
      'PN_CUSTOMERS_MANAGER_options', 
      [$this, 'PN_CUSTOMERS_MANAGER_options'], 
      esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/pn-customers-manager-menu-icon.svg'),
    );
		
    add_submenu_page(
      'PN_CUSTOMERS_MANAGER_options',
      esc_html__('Settings', 'pn-customers-manager'), 
      esc_html__('Settings', 'pn-customers-manager'), 
      $menu_cap, 
      'PN_CUSTOMERS_MANAGER_options', 
      [$this, 'PN_CUSTOMERS_MANAGER_options'], 
    );

    // Add Funnels submenu (only if user has the capability)
    if (current_user_can('edit_cm_pn_funnel')) {
      add_submenu_page(
        'PN_CUSTOMERS_MANAGER_options',
        esc_html__('Funnels', 'pn-customers-manager'),
        esc_html__('Funnels', 'pn-customers-manager'),
        'edit_cm_pn_funnel',
        'edit.php?post_type=cm_pn_funnel'
      );
    }

    // Add Organizations submenu (only if user has the capability)
    if (current_user_can('edit_cm_pn_org')) {
      add_submenu_page(
        'PN_CUSTOMERS_MANAGER_options',
        esc_html__('Organizations', 'pn-customers-manager'),
        esc_html__('Organizations', 'pn-customers-manager'),
        'edit_cm_pn_org',
        'edit.php?post_type=cm_pn_org'
      );
    }
	}

	public function PN_CUSTOMERS_MANAGER_options() {
	  ?>
	    <div class="pn-customers-manager-options pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mt-50 pn-customers-manager-mb-50">
        <img src="<?php echo esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/banner-1544x500.png'); ?>" alt="<?php esc_html_e('Plugin main Banner', 'pn-customers-manager'); ?>" title="<?php esc_html_e('Plugin main Banner', 'pn-customers-manager'); ?>" class="pn-customers-manager-width-100-percent pn-customers-manager-border-radius-20 pn-customers-manager-mb-30">
        <h1 class="pn-customers-manager-mb-30"><?php esc_html_e('PN Customers Manager Settings', 'pn-customers-manager'); ?></h1>
        <div class="pn-customers-manager-options-fields pn-customers-manager-mb-30">
          <form action="" method="post" id="pn-customers-manager-form-setting" class="pn-customers-manager-form pn-customers-manager-p-30">
          <?php 
            $options = self::PN_CUSTOMERS_MANAGER_get_options();

            foreach ($options as $PN_CUSTOMERS_MANAGER_option) {
              cm_pn_forms::PN_CUSTOMERS_MANAGER_input_wrapper_builder($PN_CUSTOMERS_MANAGER_option, 'option', 0, 0, 'half');
            }
          ?>
          </form> 
        </div>
      </div>
	  <?php
	}

  public function PN_CUSTOMERS_MANAGER_activated_plugin($plugin) {
    if($plugin == 'pn-customers-manager/pn-customers-manager.php') {
      if (get_option('PN_CUSTOMERS_MANAGER_pages_funnel') && get_option('PN_CUSTOMERS_MANAGER_url_main')) {
        if (!get_transient('PN_CUSTOMERS_MANAGER_just_activated') && !defined('DOING_AJAX')) {
          set_transient('PN_CUSTOMERS_MANAGER_just_activated', true, 30);
        }
      }
    }
  }

  public function PN_CUSTOMERS_MANAGER_check_activation() {
    // Only run in admin and not during AJAX requests
    if (!is_admin() || defined('DOING_AJAX')) {
      return;
    }

    // Check if we're already in the redirection process
    if (get_option('PN_CUSTOMERS_MANAGER_redirecting')) {
      delete_option('PN_CUSTOMERS_MANAGER_redirecting');
      return;
    }

    if (get_transient('PN_CUSTOMERS_MANAGER_just_activated')) {
      $target_url = admin_url('admin.php?page=PN_CUSTOMERS_MANAGER_options');
      
      if ($target_url) {
        // Mark that we're in the redirection process
        update_option('PN_CUSTOMERS_MANAGER_redirecting', true);
        
        // Remove the transient
        delete_transient('PN_CUSTOMERS_MANAGER_just_activated');
        
        // Redirect and exit
        wp_safe_redirect(esc_url($target_url));
        exit;
      }
    }
  }

  /**
   * Adds the Settings link to the plugin list
   */
  public function PN_CUSTOMERS_MANAGER_plugin_action_links($links) {
      $settings_link = '<a href="admin.php?page=PN_CUSTOMERS_MANAGER_options">' . esc_html__('Settings', 'pn-customers-manager') . '</a>';
      array_unshift($links, $settings_link);
      
      return $links;
  }

}