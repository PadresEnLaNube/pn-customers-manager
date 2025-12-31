<?php
/**
 * Load the plugin Ajax functions.
 *
 * Load the plugin Ajax functions to be executed in background.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Ajax {
  /**
   * Load ajax functions.
   *
   * @since    1.0.0
   */
  public function PN_CUSTOMERS_MANAGER_ajax_server() {
    if (array_key_exists('pn_customers_manager_ajax_type', $_POST)) {
      // Always require nonce verification
      if (!array_key_exists('pn_customers_manager_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'pn_customers_manager_nonce_ajax_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'pn-customers-manager')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pn_customers_manager_ajax_nonce'])), 'pn-customers-manager-nonce')) {
        echo wp_json_encode([
          'error_key' => 'pn_customers_manager_nonce_ajax_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'pn-customers-manager')),
        ]);

        exit;
      }

      $PN_CUSTOMERS_MANAGER_ajax_type = cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['pn_customers_manager_ajax_type']));

      $PN_CUSTOMERS_MANAGER_ajax_keys = !empty($_POST['pn_customers_manager_ajax_keys']) ? array_map(function($key) {
        return array(
          'id' => sanitize_key($key['id']),
          'node' => sanitize_key($key['node']),
          'type' => sanitize_key($key['type']),
          'field_config' => !empty($key['field_config']) ? $key['field_config'] : []
        );
      }, wp_unslash($_POST['pn_customers_manager_ajax_keys'])) : [];

      $cm_pn_funnel_id = !empty($_POST['cm_pn_funnel_id']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['cm_pn_funnel_id'])) : 0;
      $cm_pn_org_id = !empty($_POST['cm_pn_org_id']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['cm_pn_org_id'])) : 0;
      
      $PN_CUSTOMERS_MANAGER_key_value = [];

      if (!empty($PN_CUSTOMERS_MANAGER_ajax_keys)) {
        foreach ($PN_CUSTOMERS_MANAGER_ajax_keys as $PN_CUSTOMERS_MANAGER_key) {
          if (strpos((string)$PN_CUSTOMERS_MANAGER_key['id'], '[]') !== false) {
            $PN_CUSTOMERS_MANAGER_clear_key = str_replace('[]', '', $PN_CUSTOMERS_MANAGER_key['id']);
            ${$PN_CUSTOMERS_MANAGER_clear_key} = $PN_CUSTOMERS_MANAGER_key_value[$PN_CUSTOMERS_MANAGER_clear_key] = [];

            if (!empty($_POST[$PN_CUSTOMERS_MANAGER_clear_key])) {
              $unslashed_array = wp_unslash($_POST[$PN_CUSTOMERS_MANAGER_clear_key]);
              $sanitized_array = array_map(function($value) use ($PN_CUSTOMERS_MANAGER_key) {
                return cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                  $value,
                  $PN_CUSTOMERS_MANAGER_key['node'],
                  $PN_CUSTOMERS_MANAGER_key['type'],
                  $PN_CUSTOMERS_MANAGER_key['field_config']
                );
              }, $unslashed_array);
              
              foreach ($sanitized_array as $multi_key => $multi_value) {
                $final_value = !empty($multi_value) ? $multi_value : '';
                ${$PN_CUSTOMERS_MANAGER_clear_key}[$multi_key] = $PN_CUSTOMERS_MANAGER_key_value[$PN_CUSTOMERS_MANAGER_clear_key][$multi_key] = $final_value;
              }
            } else {
              ${$PN_CUSTOMERS_MANAGER_clear_key} = '';
              $PN_CUSTOMERS_MANAGER_key_value[$PN_CUSTOMERS_MANAGER_clear_key][$multi_key] = '';
            }
          } else {
            $sanitized_key = sanitize_key($PN_CUSTOMERS_MANAGER_key['id']);
            $PN_CUSTOMERS_MANAGER_key_id = !empty($_POST[$sanitized_key]) ? 
              cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                wp_unslash($_POST[$sanitized_key]), 
                $PN_CUSTOMERS_MANAGER_key['node'], 
                $PN_CUSTOMERS_MANAGER_key['type'],
                $PN_CUSTOMERS_MANAGER_key['field_config']
              ) : '';
            ${$PN_CUSTOMERS_MANAGER_key['id']} = $PN_CUSTOMERS_MANAGER_key_value[$PN_CUSTOMERS_MANAGER_key['id']] = $PN_CUSTOMERS_MANAGER_key_id;
          }
        }
      }

      switch ($PN_CUSTOMERS_MANAGER_ajax_type) {
        case 'cm_pn_funnel_view':
          if (!empty($cm_pn_funnel_id)) {
            $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->cm_pn_funnel_view($cm_pn_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'cm_pn_funnel_view_error', 
              'error_content' => esc_html(__('An error occurred while showing the Funnel.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'cm_pn_funnel_edit':
          // Check if the Funnel exists
          $cm_pn_funnel = get_post($cm_pn_funnel_id);
          

          if (!empty($cm_pn_funnel_id)) {
            $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->cm_pn_funnel_edit($cm_pn_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'cm_pn_funnel_edit_error', 
              'error_content' => esc_html(__('An error occurred while showing the Funnel.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'cm_pn_funnel_new':
          if (!is_user_logged_in()) {
            echo wp_json_encode([
              'error_key' => 'not_logged_in',
              'error_content' => esc_html(__('You must be logged in to create a new asset.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();

          echo wp_json_encode([
            'error_key' => '', 
            'html' => $plugin_post_type_funnel->cm_pn_funnel_new(), 
          ]);

          exit;
          break;
        case 'cm_pn_funnel_duplicate':
          if (!empty($cm_pn_funnel_id)) {
            $plugin_post_type_post = new PN_CUSTOMERS_MANAGER_Functions_Post();
            $plugin_post_type_post->PN_CUSTOMERS_MANAGER_duplicate_post($cm_pn_funnel_id, 'publish');
            
            $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->cm_pn_funnel_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'cm_pn_funnel_duplicate_error', 
              'error_content' => esc_html(__('An error occurred while duplicating the Funnel.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'cm_pn_funnel_remove':
          if (!empty($cm_pn_funnel_id)) {
            wp_delete_post($cm_pn_funnel_id, true);

            $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->cm_pn_funnel_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'cm_pn_funnel_remove_error', 
              'error_content' => esc_html(__('An error occurred while removing the Funnel.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'cm_pn_org_view':
          if (!empty($cm_pn_org_id)) {
            $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->cm_pn_org_view($cm_pn_org_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'cm_pn_org_view_error', 
              'error_content' => esc_html(__('An error occurred while showing the Organization.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'cm_pn_org_edit':
          // Check if the Organization exists
          $cm_pn_org = get_post($cm_pn_org_id);
          
          if (!empty($cm_pn_org_id) && !empty($cm_pn_org) && $cm_pn_org->post_type == 'cm_pn_org') {
            $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->cm_pn_org_edit($cm_pn_org_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'cm_pn_org_edit_error', 
              'error_content' => esc_html(__('An error occurred while showing the Organization.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'cm_pn_org_new':
          if (!is_user_logged_in()) {
            echo wp_json_encode([
              'error_key' => 'not_logged_in',
              'error_content' => esc_html(__('You must be logged in to create a new asset.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();

          echo wp_json_encode([
            'error_key' => '', 
            'html' => $plugin_post_type_organization->cm_pn_org_new(), 
          ]);

          exit;
          break;
        case 'cm_pn_org_duplicate':
          if (!empty($cm_pn_org_id)) {
            $plugin_post_type_post = new PN_CUSTOMERS_MANAGER_Functions_Post();
            $plugin_post_type_post->PN_CUSTOMERS_MANAGER_duplicate_post($cm_pn_org_id, 'publish');
            
            $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->cm_pn_org_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'cm_pn_org_duplicate_error', 
              'error_content' => esc_html(__('An error occurred while duplicating the Organization.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'cm_pn_org_remove':
          if (!empty($cm_pn_org_id)) {
            wp_delete_post($cm_pn_org_id, true);

            $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->cm_pn_org_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'cm_pn_org_remove_error', 
              'error_content' => esc_html(__('An error occurred while removing the Organization.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
      }

      echo wp_json_encode([
        'error_key' => 'PN_CUSTOMERS_MANAGER_save_error', 
      ]);

      exit;
    }
  }
}