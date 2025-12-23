<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    customers_manager_pn
 * @subpackage customers_manager_pn/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Activator {
	/**
   * Plugin activation functions
   *
   * Functions to be loaded on plugin activation. This actions creates roles, options and post information attached to the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function customers_manager_pn_activate() {
    require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-functions-post.php';
    require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-functions-attachment.php';

    $post_functions = new CUSTOMERS_MANAGER_PN_Functions_Post();
    $attachment_functions = new CUSTOMERS_MANAGER_PN_Functions_Attachment();

    add_role('customers_manager_pn_role_manager', esc_html(__('PN Customers Manager', 'customers-manager-pn')));
    add_role('customers_manager_pn_role_client', esc_html(__('Client - PN', 'customers-manager-pn')));

    $customers_manager_pn_role_admin = get_role('administrator');
    $customers_manager_pn_role_manager = get_role('customers_manager_pn_role_manager');
    $customers_manager_pn_role_client = get_role('customers_manager_pn_role_client');

    $customers_manager_pn_role_manager->add_cap('upload_files'); 
    $customers_manager_pn_role_manager->add_cap('read'); 
    if ($customers_manager_pn_role_client instanceof WP_Role) {
      $customers_manager_pn_role_client->add_cap('read');
    }

    foreach (CUSTOMERS_MANAGER_PN_CPTS as $cpt_key => $cpt_name) { 
      // Assign all custom capabilities for the CPT to both admin and manager roles to ensure menu visibility
      $capabilities = constant('CUSTOMERS_MANAGER_PN_ROLE_' . strtoupper($cpt_key) . '_CAPABILITIES');
      foreach ($capabilities as $cap) {
        $customers_manager_pn_role_admin->add_cap($cap);
        $customers_manager_pn_role_manager->add_cap($cap);
      }
      
      // Additionally, assign the management option      
      $customers_manager_pn_role_admin->add_cap('manage_' . $cpt_key . '_options');
      $customers_manager_pn_role_manager->add_cap('manage_' . $cpt_key . '_options');
    }

    if (empty(get_posts(['fields' => 'ids', 'numberposts' => -1, 'post_type' => 'customers_manager_pn_funnel', 'post_status' => 'any', ]))) {
      $customers_manager_pn_title = __('Funnel Test', 'customers-manager-pn');
      $customers_manager_pn_post_content = '';
      $customers_manager_pn_id = $post_functions->customers_manager_pn_insert_post(esc_html($customers_manager_pn_title), $customers_manager_pn_post_content, '', sanitize_title(esc_html($customers_manager_pn_title)), 'customers_manager_pn_funnel', 'publish', 1);

      if (class_exists('Polylang') && function_exists('pll_default_language')) {
        $language = pll_default_language();
        pll_set_post_language($customers_manager_pn_id, $language);
        $locales = pll_languages_list(['hide_empty' => false]);

        if (!empty($locales)) {
          foreach ($locales as $locale) {
            if ($locale != $language) {
              $customers_manager_pn_title = __('Funnel Test', 'customers-manager-pn') . ' ' . $locale;
              $customers_manager_pn_post_content = '';
              $translated_customers_manager_pn_id = $post_functions->customers_manager_pn_insert_post(esc_html($customers_manager_pn_title), $customers_manager_pn_post_content, '', sanitize_title(esc_html($customers_manager_pn_title)), 'customers_manager_pn_funnel', 'publish', 1);

              pll_set_post_language($translated_customers_manager_pn_id, $locale);

              pll_save_post_translations([
                $language => $customers_manager_pn_id,
                $locale => $translated_customers_manager_pn_id,
              ]);
            }
          }
        }
      }
    }

    update_option('customers_manager_pn_options_changed', true);
  }
}