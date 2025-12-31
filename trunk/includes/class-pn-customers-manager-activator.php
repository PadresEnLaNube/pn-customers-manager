<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Activator {
	/**
   * Plugin activation functions
   *
   * Functions to be loaded on plugin activation. This actions creates roles, options and post information attached to the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function PN_CUSTOMERS_MANAGER_activate() {
    require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-functions-post.php';
    require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-functions-attachment.php';

    $post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();
    $attachment_functions = new PN_CUSTOMERS_MANAGER_Functions_Attachment();

    add_role('PN_CUSTOMERS_MANAGER_role_manager', esc_html(__('PN Customers Manager', 'pn-customers-manager')));
    add_role('PN_CUSTOMERS_MANAGER_role_client', esc_html(__('Client - PN', 'pn-customers-manager')));

    $PN_CUSTOMERS_MANAGER_role_admin = get_role('administrator');
    $PN_CUSTOMERS_MANAGER_role_manager = get_role('PN_CUSTOMERS_MANAGER_role_manager');
    $PN_CUSTOMERS_MANAGER_role_client = get_role('PN_CUSTOMERS_MANAGER_role_client');

    $PN_CUSTOMERS_MANAGER_role_manager->add_cap('upload_files'); 
    $PN_CUSTOMERS_MANAGER_role_manager->add_cap('read'); 
    if ($PN_CUSTOMERS_MANAGER_role_client instanceof WP_Role) {
      $PN_CUSTOMERS_MANAGER_role_client->add_cap('read');
    }

    foreach (PN_CUSTOMERS_MANAGER_CPTS as $cpt_key => $cpt_name) { 
      // Assign all custom capabilities for the CPT to both admin and manager roles to ensure menu visibility
      $capabilities = constant('PN_CUSTOMERS_MANAGER_ROLE_' . strtoupper($cpt_key) . '_CAPABILITIES');
      foreach ($capabilities as $cap) {
        $PN_CUSTOMERS_MANAGER_role_admin->add_cap($cap);
        $PN_CUSTOMERS_MANAGER_role_manager->add_cap($cap);
      }
      
      // Additionally, assign the management option      
      $PN_CUSTOMERS_MANAGER_role_admin->add_cap('manage_' . $cpt_key . '_options');
      $PN_CUSTOMERS_MANAGER_role_manager->add_cap('manage_' . $cpt_key . '_options');
    }

    if (empty(get_posts(['fields' => 'ids', 'numberposts' => -1, 'post_type' => 'cm_pn_funnel', 'post_status' => 'any', ]))) {
      $PN_CUSTOMERS_MANAGER_title = __('Funnel Test', 'pn-customers-manager');
      $PN_CUSTOMERS_MANAGER_post_content = '';
      $PN_CUSTOMERS_MANAGER_id = $post_functions->PN_CUSTOMERS_MANAGER_insert_post(esc_html($PN_CUSTOMERS_MANAGER_title), $PN_CUSTOMERS_MANAGER_post_content, '', sanitize_title(esc_html($PN_CUSTOMERS_MANAGER_title)), 'cm_pn_funnel', 'publish', 1);

      if (class_exists('Polylang') && function_exists('pll_default_language')) {
        $language = pll_default_language();
        pll_set_post_language($PN_CUSTOMERS_MANAGER_id, $language);
        $locales = pll_languages_list(['hide_empty' => false]);

        if (!empty($locales)) {
          foreach ($locales as $locale) {
            if ($locale != $language) {
              $PN_CUSTOMERS_MANAGER_title = __('Funnel Test', 'pn-customers-manager') . ' ' . $locale;
              $PN_CUSTOMERS_MANAGER_post_content = '';
              $translated_PN_CUSTOMERS_MANAGER_id = $post_functions->PN_CUSTOMERS_MANAGER_insert_post(esc_html($PN_CUSTOMERS_MANAGER_title), $PN_CUSTOMERS_MANAGER_post_content, '', sanitize_title(esc_html($PN_CUSTOMERS_MANAGER_title)), 'cm_pn_funnel', 'publish', 1);

              pll_set_post_language($translated_PN_CUSTOMERS_MANAGER_id, $locale);

              pll_save_post_translations([
                $language => $PN_CUSTOMERS_MANAGER_id,
                $locale => $translated_PN_CUSTOMERS_MANAGER_id,
              ]);
            }
          }
        }
      }
    }

    update_option('PN_CUSTOMERS_MANAGER_options_changed', true);
  }
}