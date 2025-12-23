<?php
/**
 * Load the plugin Ajax functions.
 *
 * Load the plugin Ajax functions to be executed in background.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Ajax {
  /**
   * Load ajax functions.
   *
   * @since    1.0.0
   */
  public function customers_manager_pn_ajax_server() {
    if (array_key_exists('customers_manager_pn_ajax_type', $_POST)) {
      // Always require nonce verification
      if (!array_key_exists('customers_manager_pn_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'customers_manager_pn_nonce_ajax_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'customers-manager-pn')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['customers_manager_pn_ajax_nonce'])), 'customers-manager-pn-nonce')) {
        echo wp_json_encode([
          'error_key' => 'customers_manager_pn_nonce_ajax_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'customers-manager-pn')),
        ]);

        exit;
      }

      $customers_manager_pn_ajax_type = CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_ajax_type']));

      $customers_manager_pn_ajax_keys = !empty($_POST['customers_manager_pn_ajax_keys']) ? array_map(function($key) {
        return array(
          'id' => sanitize_key($key['id']),
          'node' => sanitize_key($key['node']),
          'type' => sanitize_key($key['type']),
          'field_config' => !empty($key['field_config']) ? $key['field_config'] : []
        );
      }, wp_unslash($_POST['customers_manager_pn_ajax_keys'])) : [];

      $customers_manager_pn_funnel_id = !empty($_POST['customers_manager_pn_funnel_id']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_funnel_id'])) : 0;
      $customers_manager_pn_organization_id = !empty($_POST['customers_manager_pn_organization_id']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_organization_id'])) : 0;
      
      $customers_manager_pn_key_value = [];

      if (!empty($customers_manager_pn_ajax_keys)) {
        foreach ($customers_manager_pn_ajax_keys as $customers_manager_pn_key) {
          if (strpos((string)$customers_manager_pn_key['id'], '[]') !== false) {
            $customers_manager_pn_clear_key = str_replace('[]', '', $customers_manager_pn_key['id']);
            ${$customers_manager_pn_clear_key} = $customers_manager_pn_key_value[$customers_manager_pn_clear_key] = [];

            if (!empty($_POST[$customers_manager_pn_clear_key])) {
              $unslashed_array = wp_unslash($_POST[$customers_manager_pn_clear_key]);
              $sanitized_array = array_map(function($value) use ($customers_manager_pn_key) {
                return CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                  $value,
                  $customers_manager_pn_key['node'],
                  $customers_manager_pn_key['type'],
                  $customers_manager_pn_key['field_config']
                );
              }, $unslashed_array);
              
              foreach ($sanitized_array as $multi_key => $multi_value) {
                $final_value = !empty($multi_value) ? $multi_value : '';
                ${$customers_manager_pn_clear_key}[$multi_key] = $customers_manager_pn_key_value[$customers_manager_pn_clear_key][$multi_key] = $final_value;
              }
            } else {
              ${$customers_manager_pn_clear_key} = '';
              $customers_manager_pn_key_value[$customers_manager_pn_clear_key][$multi_key] = '';
            }
          } else {
            $sanitized_key = sanitize_key($customers_manager_pn_key['id']);
            $customers_manager_pn_key_id = !empty($_POST[$sanitized_key]) ? 
              CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                wp_unslash($_POST[$sanitized_key]), 
                $customers_manager_pn_key['node'], 
                $customers_manager_pn_key['type'],
                $customers_manager_pn_key['field_config']
              ) : '';
            ${$customers_manager_pn_key['id']} = $customers_manager_pn_key_value[$customers_manager_pn_key['id']] = $customers_manager_pn_key_id;
          }
        }
      }

      switch ($customers_manager_pn_ajax_type) {
        case 'customers_manager_pn_funnel_view':
          if (!empty($customers_manager_pn_funnel_id)) {
            $plugin_post_type_funnel = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->customers_manager_pn_funnel_view($customers_manager_pn_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'customers_manager_pn_funnel_view_error', 
              'error_content' => esc_html(__('An error occurred while showing the Funnel.', 'customers-manager-pn')), 
            ]);

            exit;
          }
          break;
        case 'customers_manager_pn_funnel_edit':
          // Check if the Funnel exists
          $customers_manager_pn_funnel = get_post($customers_manager_pn_funnel_id);
          

          if (!empty($customers_manager_pn_funnel_id)) {
            $plugin_post_type_funnel = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->customers_manager_pn_funnel_edit($customers_manager_pn_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'customers_manager_pn_funnel_edit_error', 
              'error_content' => esc_html(__('An error occurred while showing the Funnel.', 'customers-manager-pn')), 
            ]);

            exit;
          }
          break;
        case 'customers_manager_pn_funnel_new':
          if (!is_user_logged_in()) {
            echo wp_json_encode([
              'error_key' => 'not_logged_in',
              'error_content' => esc_html(__('You must be logged in to create a new asset.', 'customers-manager-pn')),
            ]);
            exit;
          }

          $plugin_post_type_funnel = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();

          echo wp_json_encode([
            'error_key' => '', 
            'html' => $plugin_post_type_funnel->customers_manager_pn_funnel_new(), 
          ]);

          exit;
          break;
        case 'customers_manager_pn_funnel_duplicate':
          if (!empty($customers_manager_pn_funnel_id)) {
            $plugin_post_type_post = new CUSTOMERS_MANAGER_PN_Functions_Post();
            $plugin_post_type_post->customers_manager_pn_duplicate_post($customers_manager_pn_funnel_id, 'publish');
            
            $plugin_post_type_funnel = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->customers_manager_pn_funnel_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'customers_manager_pn_funnel_duplicate_error', 
              'error_content' => esc_html(__('An error occurred while duplicating the Funnel.', 'customers-manager-pn')), 
            ]);

            exit;
          }
          break;
        case 'customers_manager_pn_funnel_remove':
          if (!empty($customers_manager_pn_funnel_id)) {
            wp_delete_post($customers_manager_pn_funnel_id, true);

            $plugin_post_type_funnel = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->customers_manager_pn_funnel_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'customers_manager_pn_funnel_remove_error', 
              'error_content' => esc_html(__('An error occurred while removing the Funnel.', 'customers-manager-pn')), 
            ]);

            exit;
          }
          break;
        case 'customers_manager_pn_organization_view':
          if (!empty($customers_manager_pn_organization_id)) {
            $plugin_post_type_organization = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->customers_manager_pn_organization_view($customers_manager_pn_organization_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'customers_manager_pn_organization_view_error', 
              'error_content' => esc_html(__('An error occurred while showing the Organization.', 'customers-manager-pn')), 
            ]);

            exit;
          }
          break;
        case 'customers_manager_pn_organization_edit':
          // Check if the Organization exists
          $customers_manager_pn_organization = get_post($customers_manager_pn_organization_id);
          
          if (!empty($customers_manager_pn_organization_id) && !empty($customers_manager_pn_organization) && $customers_manager_pn_organization->post_type == 'customers_manager_pn_organization') {
            $plugin_post_type_organization = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->customers_manager_pn_organization_edit($customers_manager_pn_organization_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'customers_manager_pn_organization_edit_error', 
              'error_content' => esc_html(__('An error occurred while showing the Organization.', 'customers-manager-pn')), 
            ]);

            exit;
          }
          break;
        case 'customers_manager_pn_organization_new':
          if (!is_user_logged_in()) {
            echo wp_json_encode([
              'error_key' => 'not_logged_in',
              'error_content' => esc_html(__('You must be logged in to create a new asset.', 'customers-manager-pn')),
            ]);
            exit;
          }

          $plugin_post_type_organization = new CUSTOMERS_MANAGER_PN_Post_Type_organization();

          echo wp_json_encode([
            'error_key' => '', 
            'html' => $plugin_post_type_organization->customers_manager_pn_organization_new(), 
          ]);

          exit;
          break;
        case 'customers_manager_pn_organization_duplicate':
          if (!empty($customers_manager_pn_organization_id)) {
            $plugin_post_type_post = new CUSTOMERS_MANAGER_PN_Functions_Post();
            $plugin_post_type_post->customers_manager_pn_duplicate_post($customers_manager_pn_organization_id, 'publish');
            
            $plugin_post_type_organization = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->customers_manager_pn_organization_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'customers_manager_pn_organization_duplicate_error', 
              'error_content' => esc_html(__('An error occurred while duplicating the Organization.', 'customers-manager-pn')), 
            ]);

            exit;
          }
          break;
        case 'customers_manager_pn_organization_remove':
          if (!empty($customers_manager_pn_organization_id)) {
            wp_delete_post($customers_manager_pn_organization_id, true);

            $plugin_post_type_organization = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->customers_manager_pn_organization_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'customers_manager_pn_organization_remove_error', 
              'error_content' => esc_html(__('An error occurred while removing the Organization.', 'customers-manager-pn')), 
            ]);

            exit;
          }
          break;
      }

      echo wp_json_encode([
        'error_key' => 'customers_manager_pn_save_error', 
      ]);

      exit;
    }
  }
}