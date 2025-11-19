<?php

/**
 * Fired during plugin deactivation
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    CRMPN
 * @subpackage CRMPN/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Deactivator {

	/**
	 * Plugin deactivation functions
	 *
	 * Functions to be loaded on plugin deactivation. This actions remove roles, options and post information attached to the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function crmpn_deactivate() {
		$plugin_post = new CRMPN_Post_Type_Funnel();
		
		if (get_option('crmpn_options_remove') == 'on') {
      remove_role('crmpn_role_manager');
      remove_role('crmpn_role_client');

      $crmpn_funnel = get_posts(['fields' => 'ids', 'numberposts' => -1, 'post_type' => 'crmpn_funnel', 'post_status' => 'any', ]);

      if (!empty($crmpn_funnel)) {
        foreach ($crmpn_funnel as $post_id) {
          wp_delete_post($post_id, true);
        }
      }

      foreach ($plugin_post->crmpn_get_fields() as $crmpn_option) {
        delete_option($crmpn_option['id']);
      }
    }

    update_option('crmpn_options_changed', true);
	}
}