<?php
/**
 * Load the plugin no private Ajax functions.
 *
 * Load the plugin no private Ajax functions to be executed in background.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Ajax_Nopriv {
  /**
   * Load the plugin templates.
   *
   * @since    1.0.0
   */
  public function PN_CUSTOMERS_MANAGER_ajax_nopriv_server() {
    if (array_key_exists('pn_customers_manager_ajax_nopriv_type', $_POST)) {
      if (!array_key_exists('pn_customers_manager_ajax_nopriv_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'pn_customers_manager_nonce_ajax_nopriv_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'pn-customers-manager')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pn_customers_manager_ajax_nopriv_nonce'])), 'pn-customers-manager-nonce')) {
        echo wp_json_encode([
          'error_key' => 'pn_customers_manager_nonce_ajax_nopriv_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'pn-customers-manager')),
        ]);

        exit;
      }

      $PN_CUSTOMERS_MANAGER_ajax_nopriv_type = cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['pn_customers_manager_ajax_nopriv_type']));
      
      $PN_CUSTOMERS_MANAGER_ajax_keys = !empty($_POST['pn_customers_manager_ajax_keys']) ? array_map(function($key) {
        $sanitized_key = wp_unslash($key);
        return array(
          'id' => sanitize_key($sanitized_key['id']),
          'node' => sanitize_key($sanitized_key['node']),
          'type' => sanitize_key($sanitized_key['type']),
          'multiple' => sanitize_key($sanitized_key['multiple'])
        );
      }, wp_unslash($_POST['pn_customers_manager_ajax_keys'])) : [];

      $PN_CUSTOMERS_MANAGER_key_value = [];

      if (!empty($PN_CUSTOMERS_MANAGER_ajax_keys)) {
        foreach ($PN_CUSTOMERS_MANAGER_ajax_keys as $PN_CUSTOMERS_MANAGER_key) {
          if ($PN_CUSTOMERS_MANAGER_key['multiple'] == 'true') {
            $PN_CUSTOMERS_MANAGER_clear_key = str_replace('[]', '', $PN_CUSTOMERS_MANAGER_key['id']);
            ${$PN_CUSTOMERS_MANAGER_clear_key} = $PN_CUSTOMERS_MANAGER_key_value[$PN_CUSTOMERS_MANAGER_clear_key] = [];

            if (!empty($_POST[$PN_CUSTOMERS_MANAGER_clear_key])) {
              $unslashed_array = wp_unslash($_POST[$PN_CUSTOMERS_MANAGER_clear_key]);
              
              if (!is_array($unslashed_array)) {
                $unslashed_array = array($unslashed_array);
              }

              $sanitized_array = array_map(function($value) use ($PN_CUSTOMERS_MANAGER_key) {
                return cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                  $value,
                  $PN_CUSTOMERS_MANAGER_key['node'],
                  $PN_CUSTOMERS_MANAGER_key['type'],
                  $PN_CUSTOMERS_MANAGER_key['field_config'] ?? [],
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
            $unslashed_value = !empty($_POST[$sanitized_key]) ? wp_unslash($_POST[$sanitized_key]) : '';
            
            $PN_CUSTOMERS_MANAGER_key_id = !empty($unslashed_value) ? 
              cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                $unslashed_value, 
                $PN_CUSTOMERS_MANAGER_key['node'], 
                $PN_CUSTOMERS_MANAGER_key['type'],
                $PN_CUSTOMERS_MANAGER_key['field_config'] ?? [],
              ) : '';
            
              ${$PN_CUSTOMERS_MANAGER_key['id']} = $PN_CUSTOMERS_MANAGER_key_value[$PN_CUSTOMERS_MANAGER_key['id']] = $PN_CUSTOMERS_MANAGER_key_id;
          }
        }
      }

      switch ($PN_CUSTOMERS_MANAGER_ajax_nopriv_type) {
        case 'cm_pn_form_save':
          $cm_pn_form_type = !empty($_POST['cm_pn_form_type']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['cm_pn_form_type'])) : '';

          if (!empty($PN_CUSTOMERS_MANAGER_key_value) && !empty($cm_pn_form_type)) {
            $cm_pn_form_id = !empty($_POST['cm_pn_form_id']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['cm_pn_form_id'])) : 0;
            $cm_pn_form_subtype = !empty($_POST['cm_pn_form_subtype']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['cm_pn_form_subtype'])) : '';
            $user_id = !empty($_POST['cm_pn_form_user_id']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['cm_pn_form_user_id'])) : 0;
            $post_id = !empty($_POST['cm_pn_form_post_id']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['cm_pn_form_post_id'])) : 0;
            $post_type = !empty($_POST['cm_pn_form_post_type']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['cm_pn_form_post_type'])) : '';

            if (($cm_pn_form_type == 'user' && empty($user_id) && !in_array($cm_pn_form_subtype, ['user_alt_new'])) || ($cm_pn_form_type == 'post' && (empty($post_id) && !(!empty($cm_pn_form_subtype) && in_array($cm_pn_form_subtype, ['post_new', 'post_edit'])))) || ($cm_pn_form_type == 'option' && !is_user_logged_in())) {
              session_start();

              $_SESSION['cm_pn_form'] = [];
              $_SESSION['cm_pn_form'][$cm_pn_form_id] = [];
              $_SESSION['cm_pn_form'][$cm_pn_form_id]['form_type'] = $cm_pn_form_type;
              $_SESSION['cm_pn_form'][$cm_pn_form_id]['values'] = $PN_CUSTOMERS_MANAGER_key_value;

              if (!empty($post_id)) {
                $_SESSION['cm_pn_form'][$cm_pn_form_id]['post_id'] = $post_id;
              }

              echo wp_json_encode(['error_key' => 'cm_pn_form_save_error_unlogged', ]);exit;
            }else{
              switch ($cm_pn_form_type) {
                case 'user':
                  if (!in_array($cm_pn_form_subtype, ['user_alt_new'])) {
                    if (empty($user_id)) {
                      if (PN_CUSTOMERS_MANAGER_Functions_User::PN_CUSTOMERS_MANAGER_user_is_admin(get_current_user_id())) {
                        $user_login = !empty($_POST['user_login']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['user_login'])) : 0;
                        $user_password = !empty($_POST['user_password']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['user_password'])) : 0;
                        $user_email = !empty($_POST['user_email']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST['user_email'])) : 0;

                        $user_id = PN_CUSTOMERS_MANAGER_Functions_User::PN_CUSTOMERS_MANAGER_user_insert($user_login, $user_password, $user_email);
                      }
                    }

                    if (!empty($user_id)) {
                      foreach ($PN_CUSTOMERS_MANAGER_key_value as $PN_CUSTOMERS_MANAGER_key => $PN_CUSTOMERS_MANAGER_value) {
                        // Skip action and ajax type keys
                        if (in_array($PN_CUSTOMERS_MANAGER_key, ['action', 'PN_CUSTOMERS_MANAGER_ajax_nopriv_type'])) {
                          continue;
                        }

                        // Ensure option name is prefixed with PN_CUSTOMERS_MANAGER_
                        // Special case: if key is just 'pn-customers-manager', don't add prefix as it's already the main option
                        if ($PN_CUSTOMERS_MANAGER_key !== 'pn-customers-manager' && strpos((string)$PN_CUSTOMERS_MANAGER_key, 'PN_CUSTOMERS_MANAGER_') !== 0) {
                          $PN_CUSTOMERS_MANAGER_key = 'PN_CUSTOMERS_MANAGER_' . $PN_CUSTOMERS_MANAGER_key;
                        } else {
                          // Key already has correct prefix
                        }

                        update_user_meta($user_id, $PN_CUSTOMERS_MANAGER_key, $PN_CUSTOMERS_MANAGER_value);
                      }
                    }
                  }

                  do_action('PN_CUSTOMERS_MANAGER_form_save', $user_id, $PN_CUSTOMERS_MANAGER_key_value, $cm_pn_form_type, $cm_pn_form_subtype);
                  break;
                case 'post':
                  if (empty($cm_pn_form_subtype) || in_array($cm_pn_form_subtype, ['post_new', 'post_edit'])) {
                    if (empty($post_id)) {
                      if (PN_CUSTOMERS_MANAGER_Functions_User::PN_CUSTOMERS_MANAGER_user_is_admin(get_current_user_id())) {
                        $post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();
                        $title = !empty($_POST[$post_type . '_title']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST[$post_type . '_title'])) : '';
                        $description = !empty($_POST[$post_type . '_description']) ? cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(wp_unslash($_POST[$post_type . '_description'])) : '';
                        
                        $post_id = $post_functions->PN_CUSTOMERS_MANAGER_insert_post($title, $description, '', sanitize_title($title), $post_type, 'publish', get_current_user_id());
                      }
                    }

                    if (!empty($post_id)) {
                      foreach ($PN_CUSTOMERS_MANAGER_key_value as $PN_CUSTOMERS_MANAGER_key => $PN_CUSTOMERS_MANAGER_value) {
                        if ($PN_CUSTOMERS_MANAGER_key == $post_type . '_title') {
                          wp_update_post([
                            'ID' => $post_id,
                            'post_title' => esc_html($PN_CUSTOMERS_MANAGER_value),
                          ]);
                        }

                        if ($PN_CUSTOMERS_MANAGER_key == $post_type . '_description') {
                          wp_update_post([
                            'ID' => $post_id,
                            'post_content' => esc_html($PN_CUSTOMERS_MANAGER_value),
                          ]);
                        }

                        // Skip action and ajax type keys
                        if (in_array($PN_CUSTOMERS_MANAGER_key, ['action', 'PN_CUSTOMERS_MANAGER_ajax_nopriv_type'])) {
                          continue;
                        }

                        // Ensure option name is prefixed with PN_CUSTOMERS_MANAGER_
                        // Special case: if key is just 'pn-customers-manager', don't add prefix as it's already the main option
                        if ($PN_CUSTOMERS_MANAGER_key !== 'pn-customers-manager' && strpos((string)$PN_CUSTOMERS_MANAGER_key, 'PN_CUSTOMERS_MANAGER_') !== 0) {
                          $PN_CUSTOMERS_MANAGER_key = 'PN_CUSTOMERS_MANAGER_' . $PN_CUSTOMERS_MANAGER_key;
                        } else {
                          // Key already has correct prefix
                        }

                        update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_key, $PN_CUSTOMERS_MANAGER_value);
                      }
                    }
                  }

                  // Dispara el hook genérico.
                  do_action('PN_CUSTOMERS_MANAGER_form_save', $post_id, $PN_CUSTOMERS_MANAGER_key_value, $cm_pn_form_type, $cm_pn_form_subtype, $post_type);

                  // Si el formulario apunta al CPT de organización, delega también en la lógica específica.
                  if (!empty($post_type) && $post_type === 'cm_pn_org') {
                    /**
                     * Permite que la clase del CPT de organización gestione la creación/edición
                     * a partir de los datos enviados desde el frontal, aunque el usuario
                     * no tenga permisos de administrador.
                     */
                    do_action('PN_CUSTOMERS_MANAGER_org_form_save', $post_id, $PN_CUSTOMERS_MANAGER_key_value, $cm_pn_form_type, $cm_pn_form_subtype);
                  }
                  break;
                case 'option':
                  if (PN_CUSTOMERS_MANAGER_Functions_User::PN_CUSTOMERS_MANAGER_user_is_admin(get_current_user_id())) {
                    $PN_CUSTOMERS_MANAGER_settings = new PN_CUSTOMERS_MANAGER_Settings();
                    $PN_CUSTOMERS_MANAGER_options = $PN_CUSTOMERS_MANAGER_settings->PN_CUSTOMERS_MANAGER_get_options();
                    $PN_CUSTOMERS_MANAGER_allowed_options = array_keys($PN_CUSTOMERS_MANAGER_options);

                    // First, add html_multi field IDs to allowed options temporarily
                    foreach ($PN_CUSTOMERS_MANAGER_options as $option_key => $option_config) {
                      if (isset($option_config['input']) && $option_config['input'] === 'html_multi' && 
                          isset($option_config['html_multi_fields']) && is_array($option_config['html_multi_fields'])) {
                        foreach ($option_config['html_multi_fields'] as $multi_field) {
                          if (isset($multi_field['id'])) {
                            $PN_CUSTOMERS_MANAGER_allowed_options[] = $multi_field['id'];
                          }
                        }
                      }
                    }

                    // Process remaining individual fields
                    foreach ($PN_CUSTOMERS_MANAGER_key_value as $PN_CUSTOMERS_MANAGER_key => $PN_CUSTOMERS_MANAGER_value) {
                      // Skip action and ajax type keys
                      if (in_array($PN_CUSTOMERS_MANAGER_key, ['action', 'PN_CUSTOMERS_MANAGER_ajax_nopriv_type'])) {
                        continue;
                      }

                      // Ensure option name is prefixed with PN_CUSTOMERS_MANAGER_
                      // Special case: if key is just 'pn-customers-manager', don't add prefix as it's already the main option
                      if ($PN_CUSTOMERS_MANAGER_key !== 'pn-customers-manager' && strpos((string)$PN_CUSTOMERS_MANAGER_key, 'PN_CUSTOMERS_MANAGER_') !== 0) {
                        $PN_CUSTOMERS_MANAGER_key = 'PN_CUSTOMERS_MANAGER_' . $PN_CUSTOMERS_MANAGER_key;
                      } else {
                        // Key already has correct prefix
                      }

                      // Only update if option is in allowed options list
                      if (in_array($PN_CUSTOMERS_MANAGER_key, $PN_CUSTOMERS_MANAGER_allowed_options)) {
                        update_option($PN_CUSTOMERS_MANAGER_key, $PN_CUSTOMERS_MANAGER_value);
                      }
                    }
                  }

                  do_action('PN_CUSTOMERS_MANAGER_form_save', 0, $PN_CUSTOMERS_MANAGER_key_value, $cm_pn_form_type, $cm_pn_form_subtype);
                  break;
              }

              $popup_close = in_array($cm_pn_form_subtype, ['post_new', 'post_edit', 'user_alt_new']) ? true : '';
              $update_list = in_array($cm_pn_form_subtype, ['post_new', 'post_edit', 'user_alt_new']) ? true : '';
              $check = in_array($cm_pn_form_subtype, ['post_check', 'post_uncheck']) ? $cm_pn_form_subtype : '';
              
              if ($update_list && !empty($post_type)) {
                switch ($post_type) {
                  case 'cm_pn_funnel':
                    $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
                    $update_html = $plugin_post_type_funnel->cm_pn_funnel_list();
                    break;
                  case 'cm_pn_org':
                    $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
                    $update_html = $plugin_post_type_organization->cm_pn_org_list();
                    break;
                }
              }else{
                $update_html = '';
              }

              echo wp_json_encode(['error_key' => '', 'popup_close' => $popup_close, 'update_list' => $update_list, 'update_html' => $update_html, 'check' => $check]);exit;
            }
          }else{
            echo wp_json_encode(['error_key' => 'cm_pn_form_save_error', ]);exit;
          }
          break;
      }

      echo wp_json_encode(['error_key' => '', ]);exit;
    }
  }
}