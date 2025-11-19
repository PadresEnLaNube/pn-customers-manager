<?php
/**
 * Load the plugin Ajax functions.
 *
 * Load the plugin Ajax functions to be executed in background.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Ajax {
  /**
   * Load ajax functions.
   *
   * @since    1.0.0
   */
  public function crmpn_ajax_server() {
    if (array_key_exists('crmpn_ajax_type', $_POST)) {
      // Always require nonce verification
      if (!array_key_exists('crmpn_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'crmpn_nonce_ajax_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'crmpn')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['crmpn_ajax_nonce'])), 'crmpn-nonce')) {
        echo wp_json_encode([
          'error_key' => 'crmpn_nonce_ajax_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'crmpn')),
        ]);

        exit;
      }

      $crmpn_ajax_type = CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_ajax_type']));

      $crmpn_ajax_keys = !empty($_POST['crmpn_ajax_keys']) ? array_map(function($key) {
        return array(
          'id' => sanitize_key($key['id']),
          'node' => sanitize_key($key['node']),
          'type' => sanitize_key($key['type']),
          'field_config' => !empty($key['field_config']) ? $key['field_config'] : []
        );
      }, wp_unslash($_POST['crmpn_ajax_keys'])) : [];

      $crmpn_funnel_id = !empty($_POST['crmpn_funnel_id']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_funnel_id'])) : 0;
      
      $crmpn_key_value = [];

      if (!empty($crmpn_ajax_keys)) {
        foreach ($crmpn_ajax_keys as $crmpn_key) {
          if (strpos((string)$crmpn_key['id'], '[]') !== false) {
            $crmpn_clear_key = str_replace('[]', '', $crmpn_key['id']);
            ${$crmpn_clear_key} = $crmpn_key_value[$crmpn_clear_key] = [];

            if (!empty($_POST[$crmpn_clear_key])) {
              $unslashed_array = wp_unslash($_POST[$crmpn_clear_key]);
              $sanitized_array = array_map(function($value) use ($crmpn_key) {
                return CRMPN_Forms::crmpn_sanitizer(
                  $value,
                  $crmpn_key['node'],
                  $crmpn_key['type'],
                  $crmpn_key['field_config']
                );
              }, $unslashed_array);
              
              foreach ($sanitized_array as $multi_key => $multi_value) {
                $final_value = !empty($multi_value) ? $multi_value : '';
                ${$crmpn_clear_key}[$multi_key] = $crmpn_key_value[$crmpn_clear_key][$multi_key] = $final_value;
              }
            } else {
              ${$crmpn_clear_key} = '';
              $crmpn_key_value[$crmpn_clear_key][$multi_key] = '';
            }
          } else {
            $sanitized_key = sanitize_key($crmpn_key['id']);
            $crmpn_key_id = !empty($_POST[$sanitized_key]) ? 
              CRMPN_Forms::crmpn_sanitizer(
                wp_unslash($_POST[$sanitized_key]), 
                $crmpn_key['node'], 
                $crmpn_key['type'],
                $crmpn_key['field_config']
              ) : '';
            ${$crmpn_key['id']} = $crmpn_key_value[$crmpn_key['id']] = $crmpn_key_id;
          }
        }
      }

      switch ($crmpn_ajax_type) {
        case 'crmpn_funnel_view':
          if (!empty($crmpn_funnel_id)) {
            $plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->crmpn_funnel_view($crmpn_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'crmpn_funnel_view_error', 
              'error_content' => esc_html(__('An error occurred while showing the Funnel.', 'crmpn')), 
            ]);

            exit;
          }
          break;
        case 'crmpn_funnel_edit':
          // Check if the Funnel exists
          $crmpn_funnel = get_post($crmpn_funnel_id);
          

          if (!empty($crmpn_funnel_id)) {
            $plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->crmpn_funnel_edit($crmpn_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'crmpn_funnel_edit_error', 
              'error_content' => esc_html(__('An error occurred while showing the Funnel.', 'crmpn')), 
            ]);

            exit;
          }
          break;
        case 'crmpn_funnel_new':
          if (!is_user_logged_in()) {
            echo wp_json_encode([
              'error_key' => 'not_logged_in',
              'error_content' => esc_html(__('You must be logged in to create a new asset.', 'crmpn')),
            ]);
            exit;
          }

          $plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();

          echo wp_json_encode([
            'error_key' => '', 
            'html' => $plugin_post_type_funnel->crmpn_funnel_new($crmpn_funnel_id), 
          ]);

          exit;
          break;
        case 'crmpn_funnel_check':
          if (!empty($crmpn_funnel_id)) {
            $plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->crmpn_funnel_check($crmpn_funnel_id), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'crmpn_funnel_check_error', 
              'error_content' => esc_html(__('An error occurred while checking the Funnel.', 'crmpn')), 
              ]);

            exit;
          }
          break;
        case 'crmpn_funnel_duplicate':
          if (!empty($crmpn_funnel_id)) {
            $plugin_post_type_post = new CRMPN_Functions_Post();
            $plugin_post_type_post->crmpn_duplicate_post($crmpn_funnel_id, 'publish');
            
            $plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->crmpn_funnel_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'crmpn_funnel_duplicate_error', 
              'error_content' => esc_html(__('An error occurred while duplicating the Funnel.', 'crmpn')), 
            ]);

            exit;
          }
          break;
        case 'crmpn_funnel_remove':
          if (!empty($crmpn_funnel_id)) {
            wp_delete_post($crmpn_funnel_id, true);

            $plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
            echo wp_json_encode([
              'error_key' => '', 
              'html' => $plugin_post_type_funnel->crmpn_funnel_list(), 
            ]);

            exit;
          }else{
            echo wp_json_encode([
              'error_key' => 'crmpn_funnel_remove_error', 
              'error_content' => esc_html(__('An error occurred while removing the Funnel.', 'crmpn')), 
            ]);

            exit;
          }
          break;
        case 'crmpn_funnel_share':
          $plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
          echo wp_json_encode([
            'error_key' => '', 
            'html' => $plugin_post_type_funnel->crmpn_funnel_share(), 
          ]);

          exit;
          break;
      }

      echo wp_json_encode([
        'error_key' => 'crmpn_save_error', 
      ]);

      exit;
    }
  }
}