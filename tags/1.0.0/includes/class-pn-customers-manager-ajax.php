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

      $pn_customers_manager_ajax_type = PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['pn_customers_manager_ajax_type']));

      $pn_customers_manager_ajax_keys = !empty($_POST['pn_customers_manager_ajax_keys']) ? array_map(function($key) {
        return array(
          'id' => sanitize_key($key['id']),
          'node' => sanitize_key($key['node']),
          'type' => sanitize_key($key['type']),
          'field_config' => !empty($key['field_config']) ? $key['field_config'] : []
        );
      }, wp_unslash($_POST['pn_customers_manager_ajax_keys'])) : [];

      $pn_cm_funnel_id = !empty($_POST['pn_cm_funnel_id']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['pn_cm_funnel_id'])) : 0;
      $pn_cm_organization_id = !empty($_POST['pn_cm_organization_id']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['pn_cm_organization_id'])) : 0;
      
      $pn_customers_manager_key_value = [];

      if (!empty($pn_customers_manager_ajax_keys)) {
        foreach ($pn_customers_manager_ajax_keys as $pn_customers_manager_key) {
          if (strpos((string)$pn_customers_manager_key['id'], '[]') !== false) {
            $pn_customers_manager_clear_key = str_replace('[]', '', $pn_customers_manager_key['id']);
            ${$pn_customers_manager_clear_key} = $pn_customers_manager_key_value[$pn_customers_manager_clear_key] = [];

            if (!empty($_POST[$pn_customers_manager_clear_key])) {
              $unslashed_array = wp_unslash($_POST[$pn_customers_manager_clear_key]);
              $sanitized_array = array_map(function($value) use ($pn_customers_manager_key) {
                return PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                  $value,
                  $pn_customers_manager_key['node'],
                  $pn_customers_manager_key['type'],
                  $pn_customers_manager_key['field_config']
                );
              }, $unslashed_array);
              
              foreach ($sanitized_array as $multi_key => $multi_value) {
                $final_value = !empty($multi_value) ? $multi_value : '';
                ${$pn_customers_manager_clear_key}[$multi_key] = $pn_customers_manager_key_value[$pn_customers_manager_clear_key][$multi_key] = $final_value;
              }
            } else {
              ${$pn_customers_manager_clear_key} = '';
              $pn_customers_manager_key_value[$pn_customers_manager_clear_key][$multi_key] = '';
            }
          } else {
            $sanitized_key = sanitize_key($pn_customers_manager_key['id']);
            $pn_customers_manager_key_id = !empty($_POST[$sanitized_key]) ? 
              PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                wp_unslash($_POST[$sanitized_key]), 
                $pn_customers_manager_key['node'], 
                $pn_customers_manager_key['type'],
                $pn_customers_manager_key['field_config']
              ) : '';
            ${$pn_customers_manager_key['id']} = $pn_customers_manager_key_value[$pn_customers_manager_key['id']] = $pn_customers_manager_key_id;
          }
        }
      }

      switch ($pn_customers_manager_ajax_type) {
        case 'pn_cm_funnel_view':
          if (!empty($pn_cm_funnel_id)) {
            $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->pn_cm_funnel_view($pn_cm_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'pn_cm_funnel_view_error', 
              'error_content' => esc_html(__('An error occurred while showing the Funnel.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'pn_cm_funnel_edit':
          // Check if the Funnel exists
          $pn_cm_funnel = get_post($pn_cm_funnel_id);
          

          if (!empty($pn_cm_funnel_id)) {
            $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->pn_cm_funnel_edit($pn_cm_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'pn_cm_funnel_edit_error', 
              'error_content' => esc_html(__('An error occurred while showing the Funnel.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'pn_cm_funnel_new':
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
            'html' => $plugin_post_type_funnel->pn_cm_funnel_new(), 
          ]);

          exit;
          break;
        case 'pn_cm_funnel_duplicate':
          if (!empty($pn_cm_funnel_id)) {
            $plugin_post_type_post = new PN_CUSTOMERS_MANAGER_Functions_Post();
            $plugin_post_type_post->PN_CUSTOMERS_MANAGER_duplicate_post($pn_cm_funnel_id, 'publish');
            
            $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->pn_cm_funnel_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'pn_cm_funnel_duplicate_error', 
              'error_content' => esc_html(__('An error occurred while duplicating the Funnel.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'pn_cm_funnel_remove':
          if (!empty($pn_cm_funnel_id)) {
            wp_delete_post($pn_cm_funnel_id, true);

            $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->pn_cm_funnel_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'pn_cm_funnel_remove_error', 
              'error_content' => esc_html(__('An error occurred while removing the Funnel.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'pn_cm_organization_view':
          if (!empty($pn_cm_organization_id)) {
            $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->pn_cm_organization_view($pn_cm_organization_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'pn_cm_organization_view_error', 
              'error_content' => esc_html(__('An error occurred while showing the Organization.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'pn_cm_organization_edit':
          // Check if the Organization exists
          $pn_cm_organization = get_post($pn_cm_organization_id);
          
          if (!empty($pn_cm_organization_id) && !empty($pn_cm_organization) && $pn_cm_organization->post_type == 'pn_cm_organization') {
            $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->pn_cm_organization_edit($pn_cm_organization_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'pn_cm_organization_edit_error', 
              'error_content' => esc_html(__('An error occurred while showing the Organization.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'pn_cm_organization_new':
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
            'html' => $plugin_post_type_organization->pn_cm_organization_new(), 
          ]);

          exit;
          break;
        case 'pn_cm_organization_duplicate':
          if (!empty($pn_cm_organization_id)) {
            $plugin_post_type_post = new PN_CUSTOMERS_MANAGER_Functions_Post();
            $plugin_post_type_post->PN_CUSTOMERS_MANAGER_duplicate_post($pn_cm_organization_id, 'publish');
            
            $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->pn_cm_organization_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'pn_cm_organization_duplicate_error', 
              'error_content' => esc_html(__('An error occurred while duplicating the Organization.', 'pn-customers-manager')), 
            ]);

            exit;
          }
          break;
        case 'pn_cm_organization_remove':
          if (!empty($pn_cm_organization_id)) {
            wp_delete_post($pn_cm_organization_id, true);

            $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_organization->pn_cm_organization_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'pn_cm_organization_remove_error', 
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