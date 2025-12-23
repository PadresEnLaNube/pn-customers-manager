<?php

/**
 * Fired during plugin deactivation
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Deactivator {

	/**
	 * Plugin deactivation functions
	 *
	 * Functions to be loaded on plugin deactivation. This actions remove roles, options and post information attached to the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function customers_manager_pn_deactivate() {
		$plugin_post = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();
		
		if (get_option('customers_manager_pn_options_remove') == 'on') {
      remove_role('customers_manager_pn_role_manager');
      remove_role('customers_manager_pn_role_client');

      $cm_pn_funnel = get_posts(['fields' => 'ids', 'numberposts' => -1, 'post_type' => 'cm_pn_funnel', 'post_status' => 'any', ]);

      if (!empty($cm_pn_funnel)) {
        foreach ($cm_pn_funnel as $post_id) {
          wp_delete_post($post_id, true);
        }
      }

      foreach ($plugin_post->customers_manager_pn_get_fields() as $customers_manager_pn_option) {
        delete_option($customers_manager_pn_option['id']);
      }
    }

    update_option('customers_manager_pn_options_changed', true);
	}
}