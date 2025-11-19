<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    crmpn
 * @subpackage crmpn/includes
 * @author     Padres en la Nube
 */
class CRMPN_Activator {
	/**
   * Plugin activation functions
   *
   * Functions to be loaded on plugin activation. This actions creates roles, options and post information attached to the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function crmpn_activate() {
    require_once CRMPN_DIR . 'includes/class-crmpn-functions-post.php';
    require_once CRMPN_DIR . 'includes/class-crmpn-functions-attachment.php';

    $post_functions = new CRMPN_Functions_Post();
    $attachment_functions = new CRMPN_Functions_Attachment();

    add_role('crmpn_role_manager', esc_html(__('Customers Manager - PN', 'crmpn')));
    add_role('crmpn_role_client', esc_html(__('Client - PN', 'crmpn')));

    $crmpn_role_admin = get_role('administrator');
    $crmpn_role_manager = get_role('crmpn_role_manager');
    $crmpn_role_client = get_role('crmpn_role_client');

    $crmpn_role_manager->add_cap('upload_files'); 
    $crmpn_role_manager->add_cap('read'); 
    if ($crmpn_role_client instanceof WP_Role) {
      $crmpn_role_client->add_cap('read');
    }

    foreach (CRMPN_CPTS as $cpt_key => $cpt_name) { 
      // Assign all custom capabilities for the CPT to both admin and manager roles to ensure menu visibility
      $capabilities = constant('CRMPN_ROLE_' . strtoupper($cpt_key) . '_CAPABILITIES');
      foreach ($capabilities as $cap) {
        $crmpn_role_admin->add_cap($cap);
        $crmpn_role_manager->add_cap($cap);
      }
      
      // Additionally, assign the management option      
      $crmpn_role_admin->add_cap('manage_' . $cpt_key . '_options');
      $crmpn_role_manager->add_cap('manage_' . $cpt_key . '_options');
    }

    if (empty(get_posts(['fields' => 'ids', 'numberposts' => -1, 'post_type' => 'crmpn_funnel', 'post_status' => 'any', ]))) {
      $crmpn_title = __('Funnel Test', 'crmpn');
      $crmpn_post_content = '';
      $crmpn_id = $post_functions->crmpn_insert_post(esc_html($crmpn_title), $crmpn_post_content, '', sanitize_title(esc_html($crmpn_title)), 'crmpn_funnel', 'publish', 1);

      if (class_exists('Polylang') && function_exists('pll_default_language')) {
        $language = pll_default_language();
        pll_set_post_language($crmpn_id, $language);
        $locales = pll_languages_list(['hide_empty' => false]);

        if (!empty($locales)) {
          foreach ($locales as $locale) {
            if ($locale != $language) {
              $crmpn_title = __('Funnel Test', 'crmpn') . ' ' . $locale;
              $crmpn_post_content = '';
              $translated_crmpn_id = $post_functions->crmpn_insert_post(esc_html($crmpn_title), $crmpn_post_content, '', sanitize_title(esc_html($crmpn_title)), 'crmpn_funnel', 'publish', 1);

              pll_set_post_language($translated_crmpn_id, $locale);

              pll_save_post_translations([
                $language => $crmpn_id,
                $locale => $translated_crmpn_id,
              ]);
            }
          }
        }
      }
    }

    update_option('crmpn_options_changed', true);
  }
}