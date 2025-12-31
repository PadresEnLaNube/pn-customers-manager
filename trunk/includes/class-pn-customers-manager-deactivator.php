<?php

/**
 * Fired during plugin deactivation
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Deactivator {

	/**
	 * Plugin deactivation functions
	 *
	 * Functions to be loaded on plugin deactivation. This actions remove roles, options and post information attached to the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function PN_CUSTOMERS_MANAGER_deactivate() {
		$plugin_post = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
		
		if (get_option('PN_CUSTOMERS_MANAGER_options_remove') == 'on') {
      remove_role('PN_CUSTOMERS_MANAGER_role_manager');
      remove_role('PN_CUSTOMERS_MANAGER_role_client');

      $cm_pn_funnel = get_posts(['fields' => 'ids', 'numberposts' => -1, 'post_type' => 'cm_pn_funnel', 'post_status' => 'any', ]);

      if (!empty($cm_pn_funnel)) {
        foreach ($cm_pn_funnel as $post_id) {
          wp_delete_post($post_id, true);
        }
      }

      foreach ($plugin_post->PN_CUSTOMERS_MANAGER_get_fields() as $PN_CUSTOMERS_MANAGER_option) {
        delete_option($PN_CUSTOMERS_MANAGER_option['id']);
      }
    }

    update_option('PN_CUSTOMERS_MANAGER_options_changed', true);
	}
}