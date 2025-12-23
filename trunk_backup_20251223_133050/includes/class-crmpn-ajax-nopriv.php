<?php
/**
 * Load the plugin no private Ajax functions.
 *
 * Load the plugin no private Ajax functions to be executed in background.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Ajax_Nopriv {
  /**
   * Load the plugin templates.
   *
   * @since    1.0.0
   */
  public function customers_manager_pn_ajax_nopriv_server() {
    if (array_key_exists('customers_manager_pn_ajax_nopriv_type', $_POST)) {
      if (!array_key_exists('customers_manager_pn_ajax_nopriv_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'customers_manager_pn_nonce_ajax_nopriv_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'customers-manager-pn')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['customers_manager_pn_ajax_nopriv_nonce'])), 'customers-manager-pn-nonce')) {
        echo wp_json_encode([
          'error_key' => 'customers_manager_pn_nonce_ajax_nopriv_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'customers-manager-pn')),
        ]);

        exit;
      }

      $customers_manager_pn_ajax_nopriv_type = CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_ajax_nopriv_type']));
      
      $customers_manager_pn_ajax_keys = !empty($_POST['customers_manager_pn_ajax_keys']) ? array_map(function($key) {
        $sanitized_key = wp_unslash($key);
        return array(
          'id' => sanitize_key($sanitized_key['id']),
          'node' => sanitize_key($sanitized_key['node']),
          'type' => sanitize_key($sanitized_key['type']),
          'multiple' => sanitize_key($sanitized_key['multiple'])
        );
      }, wp_unslash($_POST['customers_manager_pn_ajax_keys'])) : [];

      $customers_manager_pn_key_value = [];

      if (!empty($customers_manager_pn_ajax_keys)) {
        foreach ($customers_manager_pn_ajax_keys as $customers_manager_pn_key) {
          if ($customers_manager_pn_key['multiple'] == 'true') {
            $customers_manager_pn_clear_key = str_replace('[]', '', $customers_manager_pn_key['id']);
            ${$customers_manager_pn_clear_key} = $customers_manager_pn_key_value[$customers_manager_pn_clear_key] = [];

            if (!empty($_POST[$customers_manager_pn_clear_key])) {
              $unslashed_array = wp_unslash($_POST[$customers_manager_pn_clear_key]);
              
              if (!is_array($unslashed_array)) {
                $unslashed_array = array($unslashed_array);
              }

              $sanitized_array = array_map(function($value) use ($customers_manager_pn_key) {
                return CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                  $value,
                  $customers_manager_pn_key['node'],
                  $customers_manager_pn_key['type'],
                  $customers_manager_pn_key['field_config'] ?? [],
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
            $unslashed_value = !empty($_POST[$sanitized_key]) ? wp_unslash($_POST[$sanitized_key]) : '';
            
            $customers_manager_pn_key_id = !empty($unslashed_value) ? 
              CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                $unslashed_value, 
                $customers_manager_pn_key['node'], 
                $customers_manager_pn_key['type'],
                $customers_manager_pn_key['field_config'] ?? [],
              ) : '';
            
              ${$customers_manager_pn_key['id']} = $customers_manager_pn_key_value[$customers_manager_pn_key['id']] = $customers_manager_pn_key_id;
          }
        }
      }

      switch ($customers_manager_pn_ajax_nopriv_type) {
        case 'customers_manager_pn_form_save':
          $customers_manager_pn_form_type = !empty($_POST['customers_manager_pn_form_type']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_form_type'])) : '';

          if (!empty($customers_manager_pn_key_value) && !empty($customers_manager_pn_form_type)) {
            $customers_manager_pn_form_id = !empty($_POST['customers_manager_pn_form_id']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_form_id'])) : 0;
            $customers_manager_pn_form_subtype = !empty($_POST['customers_manager_pn_form_subtype']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_form_subtype'])) : '';
            $user_id = !empty($_POST['customers_manager_pn_form_user_id']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_form_user_id'])) : 0;
            $post_id = !empty($_POST['customers_manager_pn_form_post_id']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_form_post_id'])) : 0;
            $post_type = !empty($_POST['customers_manager_pn_form_post_type']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['customers_manager_pn_form_post_type'])) : '';

            if (($customers_manager_pn_form_type == 'user' && empty($user_id) && !in_array($customers_manager_pn_form_subtype, ['user_alt_new'])) || ($customers_manager_pn_form_type == 'post' && (empty($post_id) && !(!empty($customers_manager_pn_form_subtype) && in_array($customers_manager_pn_form_subtype, ['post_new', 'post_edit'])))) || ($customers_manager_pn_form_type == 'option' && !is_user_logged_in())) {
              session_start();

              $_SESSION['customers_manager_pn_form'] = [];
              $_SESSION['customers_manager_pn_form'][$customers_manager_pn_form_id] = [];
              $_SESSION['customers_manager_pn_form'][$customers_manager_pn_form_id]['form_type'] = $customers_manager_pn_form_type;
              $_SESSION['customers_manager_pn_form'][$customers_manager_pn_form_id]['values'] = $customers_manager_pn_key_value;

              if (!empty($post_id)) {
                $_SESSION['customers_manager_pn_form'][$customers_manager_pn_form_id]['post_id'] = $post_id;
              }

              echo wp_json_encode(['error_key' => 'customers_manager_pn_form_save_error_unlogged', ]);exit;
            }else{
              switch ($customers_manager_pn_form_type) {
                case 'user':
                  if (!in_array($customers_manager_pn_form_subtype, ['user_alt_new'])) {
                    if (empty($user_id)) {
                      if (CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_is_admin(get_current_user_id())) {
                        $user_login = !empty($_POST['user_login']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['user_login'])) : 0;
                        $user_password = !empty($_POST['user_password']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['user_password'])) : 0;
                        $user_email = !empty($_POST['user_email']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST['user_email'])) : 0;

                        $user_id = CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_insert($user_login, $user_password, $user_email);
                      }
                    }

                    if (!empty($user_id)) {
                      foreach ($customers_manager_pn_key_value as $customers_manager_pn_key => $customers_manager_pn_value) {
                        // Skip action and ajax type keys
                        if (in_array($customers_manager_pn_key, ['action', 'customers_manager_pn_ajax_nopriv_type'])) {
                          continue;
                        }

                        // Ensure option name is prefixed with customers_manager_pn_
                        // Special case: if key is just 'customers-manager-pn', don't add prefix as it's already the main option
                        if ($customers_manager_pn_key !== 'customers-manager-pn' && strpos((string)$customers_manager_pn_key, 'customers_manager_pn_') !== 0) {
                          $customers_manager_pn_key = 'customers_manager_pn_' . $customers_manager_pn_key;
                        } else {
                          // Key already has correct prefix
                        }

                        update_user_meta($user_id, $customers_manager_pn_key, $customers_manager_pn_value);
                      }
                    }
                  }

                  do_action('customers_manager_pn_form_save', $user_id, $customers_manager_pn_key_value, $customers_manager_pn_form_type, $customers_manager_pn_form_subtype);
                  break;
                case 'post':
                  if (empty($customers_manager_pn_form_subtype) || in_array($customers_manager_pn_form_subtype, ['post_new', 'post_edit'])) {
                    if (empty($post_id)) {
                      if (CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_is_admin(get_current_user_id())) {
                        $post_functions = new CUSTOMERS_MANAGER_PN_Functions_Post();
                        $title = !empty($_POST[$post_type . '_title']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST[$post_type . '_title'])) : '';
                        $description = !empty($_POST[$post_type . '_description']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_POST[$post_type . '_description'])) : '';
                        
                        $post_id = $post_functions->customers_manager_pn_insert_post($title, $description, '', sanitize_title($title), $post_type, 'publish', get_current_user_id());
                      }
                    }

                    if (!empty($post_id)) {
                      foreach ($customers_manager_pn_key_value as $customers_manager_pn_key => $customers_manager_pn_value) {
                        if ($customers_manager_pn_key == $post_type . '_title') {
                          wp_update_post([
                            'ID' => $post_id,
                            'post_title' => esc_html($customers_manager_pn_value),
                          ]);
                        }

                        if ($customers_manager_pn_key == $post_type . '_description') {
                          wp_update_post([
                            'ID' => $post_id,
                            'post_content' => esc_html($customers_manager_pn_value),
                          ]);
                        }

                        // Skip action and ajax type keys
                        if (in_array($customers_manager_pn_key, ['action', 'customers_manager_pn_ajax_nopriv_type'])) {
                          continue;
                        }

                        // Ensure option name is prefixed with customers_manager_pn_
                        // Special case: if key is just 'customers-manager-pn', don't add prefix as it's already the main option
                        if ($customers_manager_pn_key !== 'customers-manager-pn' && strpos((string)$customers_manager_pn_key, 'customers_manager_pn_') !== 0) {
                          $customers_manager_pn_key = 'customers_manager_pn_' . $customers_manager_pn_key;
                        } else {
                          // Key already has correct prefix
                        }

                        update_post_meta($post_id, $customers_manager_pn_key, $customers_manager_pn_value);
                      }
                    }
                  }

                  // Dispara el hook genérico.
                  do_action('customers_manager_pn_form_save', $post_id, $customers_manager_pn_key_value, $customers_manager_pn_form_type, $customers_manager_pn_form_subtype, $post_type);

                  // Si el formulario apunta al CPT de organización, delega también en la lógica específica.
                  if (!empty($post_type) && $post_type === 'customers_manager_pn_organization') {
                    /**
                     * Permite que la clase del CPT de organización gestione la creación/edición
                     * a partir de los datos enviados desde el frontal, aunque el usuario
                     * no tenga permisos de administrador.
                     */
                    do_action('customers_manager_pn_organization_form_save', $post_id, $customers_manager_pn_key_value, $customers_manager_pn_form_type, $customers_manager_pn_form_subtype);
                  }
                  break;
                case 'option':
                  if (CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_is_admin(get_current_user_id())) {
                    $customers_manager_pn_settings = new CUSTOMERS_MANAGER_PN_Settings();
                    $customers_manager_pn_options = $customers_manager_pn_settings->customers_manager_pn_get_options();
                    $customers_manager_pn_allowed_options = array_keys($customers_manager_pn_options);

                    // First, add html_multi field IDs to allowed options temporarily
                    foreach ($customers_manager_pn_options as $option_key => $option_config) {
                      if (isset($option_config['input']) && $option_config['input'] === 'html_multi' && 
                          isset($option_config['html_multi_fields']) && is_array($option_config['html_multi_fields'])) {
                        foreach ($option_config['html_multi_fields'] as $multi_field) {
                          if (isset($multi_field['id'])) {
                            $customers_manager_pn_allowed_options[] = $multi_field['id'];
                          }
                        }
                      }
                    }

                    // Process remaining individual fields
                    foreach ($customers_manager_pn_key_value as $customers_manager_pn_key => $customers_manager_pn_value) {
                      // Skip action and ajax type keys
                      if (in_array($customers_manager_pn_key, ['action', 'customers_manager_pn_ajax_nopriv_type'])) {
                        continue;
                      }

                      // Ensure option name is prefixed with customers_manager_pn_
                      // Special case: if key is just 'customers-manager-pn', don't add prefix as it's already the main option
                      if ($customers_manager_pn_key !== 'customers-manager-pn' && strpos((string)$customers_manager_pn_key, 'customers_manager_pn_') !== 0) {
                        $customers_manager_pn_key = 'customers_manager_pn_' . $customers_manager_pn_key;
                      } else {
                        // Key already has correct prefix
                      }

                      // Only update if option is in allowed options list
                      if (in_array($customers_manager_pn_key, $customers_manager_pn_allowed_options)) {
                        update_option($customers_manager_pn_key, $customers_manager_pn_value);
                      }
                    }
                  }

                  do_action('customers_manager_pn_form_save', 0, $customers_manager_pn_key_value, $customers_manager_pn_form_type, $customers_manager_pn_form_subtype);
                  break;
              }

              $popup_close = in_array($customers_manager_pn_form_subtype, ['post_new', 'post_edit', 'user_alt_new']) ? true : '';
              $update_list = in_array($customers_manager_pn_form_subtype, ['post_new', 'post_edit', 'user_alt_new']) ? true : '';
              $check = in_array($customers_manager_pn_form_subtype, ['post_check', 'post_uncheck']) ? $customers_manager_pn_form_subtype : '';
              
              if ($update_list && !empty($post_type)) {
                switch ($post_type) {
                  case 'customers_manager_pn_funnel':
                    $plugin_post_type_funnel = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();
                    $update_html = $plugin_post_type_funnel->customers_manager_pn_funnel_list();
                    break;
                  case 'customers_manager_pn_organization':
                    $plugin_post_type_organization = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
                    $update_html = $plugin_post_type_organization->customers_manager_pn_organization_list();
                    break;
                }
              }else{
                $update_html = '';
              }

              echo wp_json_encode(['error_key' => '', 'popup_close' => $popup_close, 'update_list' => $update_list, 'update_html' => $update_html, 'check' => $check]);exit;
            }
          }else{
            echo wp_json_encode(['error_key' => 'customers_manager_pn_form_save_error', ]);exit;
          }
          break;
      }

      echo wp_json_encode(['error_key' => '', ]);exit;
    }
  }
}