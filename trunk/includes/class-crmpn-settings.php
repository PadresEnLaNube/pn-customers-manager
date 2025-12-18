<?php
/**
 * Settings manager.
 *
 * This class defines plugin settings, both in dashboard or in front-end.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Settings {
  public function crmpn_get_options() {
    $crmpn_options = [];
    
    foreach (CRMPN_CPTS as $crmpn_cpt) {
      $crmpn_options['crmpn_' . $crmpn_cpt . '_slug'] = [
        'id' => 'crmpn_' . $crmpn_cpt . '_slug',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => __($crmpn_cpt . ' slug', 'crmpn'),
        'placeholder' => __($crmpn_cpt . ' slug', 'crmpn'),
        'description' => __('This option sets the slug of the ' . $crmpn_cpt . ' archive page, and the ' . $crmpn_cpt . ' pages. By default they will be:', 'crmpn') . '<br><a href="' . esc_url(home_url('/' . $crmpn_cpt . '-slug')) . '" target="_blank">' . esc_url(home_url('/' . $crmpn_cpt . '-slug')) . '</a><br>' . esc_url(home_url('/' . $crmpn_cpt . '-slug/' . $crmpn_cpt)),
      ];
    }

    $crmpn_options['crmpn_options_remove'] = [
      'id' => 'crmpn_options_remove',
      'class' => 'crmpn-input crmpn-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Remove plugin options on deactivation', 'crmpn'),
      'description' => __('If you activate this option the plugin will remove all options on deactivation. Please, be careful. This process cannot be undone.', 'crmpn'),
    ];
    $crmpn_options['crmpn_nonce'] = [
      'id' => 'crmpn_nonce',
      'input' => 'input',
      'type' => 'nonce',
    ];
    $crmpn_options['crmpn_submit'] = [
      'id' => 'crmpn_submit',
      'input' => 'input',
      'type' => 'submit',
      'value' => __('Save options', 'crmpn'),
    ];

    return $crmpn_options;
  }

	/**
	 * Administrator menu.
	 *
	 * @since    1.0.0
	 */
	public function crmpn_admin_menu() {
    add_menu_page(
      esc_html__('Customers Manager - PN', 'crmpn'), 
      esc_html__('Customers Manager - PN', 'crmpn'), 
      'edit_crmpn_funnel', 
      'crmpn_options', 
      [$this, 'crmpn_options'], 
      esc_url(CRMPN_URL . 'assets/media/crmpn-menu-icon.svg'),
    );
		
    add_submenu_page(
      'crmpn_options',
      esc_html__('Settings', 'crmpn'), 
      esc_html__('Settings', 'crmpn'), 
      'edit_crmpn_funnel', 
      'crmpn_options', 
      [$this, 'crmpn_options'], 
    );

    // CPTs already appear automatically as submenus (show_in_menu => crmpn_options).
    // The explicit submenu entries were removed to avoid duplicates.
	}

	public function crmpn_options() {
	  ?>
	    <div class="crmpn-options crmpn-max-width-1000 crmpn-margin-auto crmpn-mt-50 crmpn-mb-50">
        <img src="<?php echo esc_url(CRMPN_URL . 'assets/media/banner-1544x500.png'); ?>" alt="<?php esc_html_e('Plugin main Banner', 'crmpn'); ?>" title="<?php esc_html_e('Plugin main Banner', 'crmpn'); ?>" class="crmpn-width-100-percent crmpn-border-radius-20 crmpn-mb-30">
        <h1 class="crmpn-mb-30"><?php esc_html_e('Customers Manager - PN Settings', 'crmpn'); ?></h1>
        <div class="crmpn-options-fields crmpn-mb-30">
          <form action="" method="post" id="crmpn-form-setting" class="crmpn-form crmpn-p-30">
          <?php 
            $options = self::crmpn_get_options();

            foreach ($options as $crmpn_option) {
              CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_option, 'option', 0, 0, 'half');
            }
          ?>
          </form> 
        </div>
      </div>
	  <?php
	}

  public function crmpn_activated_plugin($plugin) {
    if($plugin == 'crmpn/crmpn.php') {
      if (get_option('crmpn_pages_funnel') && get_option('crmpn_url_main')) {
        if (!get_transient('crmpn_just_activated') && !defined('DOING_AJAX')) {
          set_transient('crmpn_just_activated', true, 30);
        }
      }
    }
  }

  public function crmpn_check_activation() {
    // Only run in admin and not during AJAX requests
    if (!is_admin() || defined('DOING_AJAX')) {
      return;
    }

    // Check if we're already in the redirection process
    if (get_option('crmpn_redirecting')) {
      delete_option('crmpn_redirecting');
      return;
    }

    if (get_transient('crmpn_just_activated')) {
      $target_url = admin_url('admin.php?page=crmpn_options');
      
      if ($target_url) {
        // Mark that we're in the redirection process
        update_option('crmpn_redirecting', true);
        
        // Remove the transient
        delete_transient('crmpn_just_activated');
        
        // Redirect and exit
        wp_safe_redirect(esc_url($target_url));
        exit;
      }
    }
  }

  /**
   * Adds the Settings link to the plugin list
   */
  public function crmpn_plugin_action_links($links) {
      $settings_link = '<a href="admin.php?page=crmpn_options">' . esc_html__('Settings', 'crmpn') . '</a>';
      array_unshift($links, $settings_link);
      
      return $links;
  }

}