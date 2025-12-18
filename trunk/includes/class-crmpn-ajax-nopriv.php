<?php
/**
 * Load the plugin no private Ajax functions.
 *
 * Load the plugin no private Ajax functions to be executed in background.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Ajax_Nopriv {
  /**
   * Load the plugin templates.
   *
   * @since    1.0.0
   */
  public function crmpn_ajax_nopriv_server() {
    if (array_key_exists('crmpn_ajax_nopriv_type', $_POST)) {
      if (!array_key_exists('crmpn_ajax_nopriv_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'crmpn_nonce_ajax_nopriv_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'crmpn')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['crmpn_ajax_nopriv_nonce'])), 'crmpn-nonce')) {
        echo wp_json_encode([
          'error_key' => 'crmpn_nonce_ajax_nopriv_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'crmpn')),
        ]);

        exit;
      }

      $crmpn_ajax_nopriv_type = CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_ajax_nopriv_type']));
      
      $crmpn_ajax_keys = !empty($_POST['crmpn_ajax_keys']) ? array_map(function($key) {
        $sanitized_key = wp_unslash($key);
        return array(
          'id' => sanitize_key($sanitized_key['id']),
          'node' => sanitize_key($sanitized_key['node']),
          'type' => sanitize_key($sanitized_key['type']),
          'multiple' => sanitize_key($sanitized_key['multiple'])
        );
      }, wp_unslash($_POST['crmpn_ajax_keys'])) : [];

      $crmpn_key_value = [];

      if (!empty($crmpn_ajax_keys)) {
        foreach ($crmpn_ajax_keys as $crmpn_key) {
          if ($crmpn_key['multiple'] == 'true') {
            $crmpn_clear_key = str_replace('[]', '', $crmpn_key['id']);
            ${$crmpn_clear_key} = $crmpn_key_value[$crmpn_clear_key] = [];

            if (!empty($_POST[$crmpn_clear_key])) {
              $unslashed_array = wp_unslash($_POST[$crmpn_clear_key]);
              
              if (!is_array($unslashed_array)) {
                $unslashed_array = array($unslashed_array);
              }

              $sanitized_array = array_map(function($value) use ($crmpn_key) {
                return CRMPN_Forms::crmpn_sanitizer(
                  $value,
                  $crmpn_key['node'],
                  $crmpn_key['type'],
                  $crmpn_key['field_config'] ?? [],
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
            $unslashed_value = !empty($_POST[$sanitized_key]) ? wp_unslash($_POST[$sanitized_key]) : '';
            
            $crmpn_key_id = !empty($unslashed_value) ? 
              CRMPN_Forms::crmpn_sanitizer(
                $unslashed_value, 
                $crmpn_key['node'], 
                $crmpn_key['type'],
                $crmpn_key['field_config'] ?? [],
              ) : '';
            
              ${$crmpn_key['id']} = $crmpn_key_value[$crmpn_key['id']] = $crmpn_key_id;
          }
        }
      }

      switch ($crmpn_ajax_nopriv_type) {
        case 'crmpn_form_save':
          $crmpn_form_type = !empty($_POST['crmpn_form_type']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_form_type'])) : '';

          if (!empty($crmpn_key_value) && !empty($crmpn_form_type)) {
            $crmpn_form_id = !empty($_POST['crmpn_form_id']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_form_id'])) : 0;
            $crmpn_form_subtype = !empty($_POST['crmpn_form_subtype']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_form_subtype'])) : '';
            $user_id = !empty($_POST['crmpn_form_user_id']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_form_user_id'])) : 0;
            $post_id = !empty($_POST['crmpn_form_post_id']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_form_post_id'])) : 0;
            $post_type = !empty($_POST['crmpn_form_post_type']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['crmpn_form_post_type'])) : '';

            if (($crmpn_form_type == 'user' && empty($user_id) && !in_array($crmpn_form_subtype, ['user_alt_new'])) || ($crmpn_form_type == 'post' && (empty($post_id) && !(!empty($crmpn_form_subtype) && in_array($crmpn_form_subtype, ['post_new', 'post_edit'])))) || ($crmpn_form_type == 'option' && !is_user_logged_in())) {
              session_start();

              $_SESSION['crmpn_form'] = [];
              $_SESSION['crmpn_form'][$crmpn_form_id] = [];
              $_SESSION['crmpn_form'][$crmpn_form_id]['form_type'] = $crmpn_form_type;
              $_SESSION['crmpn_form'][$crmpn_form_id]['values'] = $crmpn_key_value;

              if (!empty($post_id)) {
                $_SESSION['crmpn_form'][$crmpn_form_id]['post_id'] = $post_id;
              }

              echo wp_json_encode(['error_key' => 'crmpn_form_save_error_unlogged', ]);exit;
            }else{
              switch ($crmpn_form_type) {
                case 'user':
                  if (!in_array($crmpn_form_subtype, ['user_alt_new'])) {
                    if (empty($user_id)) {
                      if (CRMPN_Functions_User::crmpn_user_is_admin(get_current_user_id())) {
                        $user_login = !empty($_POST['user_login']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['user_login'])) : 0;
                        $user_password = !empty($_POST['user_password']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['user_password'])) : 0;
                        $user_email = !empty($_POST['user_email']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST['user_email'])) : 0;

                        $user_id = CRMPN_Functions_User::crmpn_user_insert($user_login, $user_password, $user_email);
                      }
                    }

                    if (!empty($user_id)) {
                      foreach ($crmpn_key_value as $crmpn_key => $crmpn_value) {
                        // Skip action and ajax type keys
                        if (in_array($crmpn_key, ['action', 'crmpn_ajax_nopriv_type'])) {
                          continue;
                        }

                        // Ensure option name is prefixed with crmpn_
                        // Special case: if key is just 'crmpn', don't add prefix as it's already the main option
                        if ($crmpn_key !== 'crmpn' && strpos((string)$crmpn_key, 'crmpn_') !== 0) {
                          $crmpn_key = 'crmpn_' . $crmpn_key;
                        } else {
                          // Key already has correct prefix
                        }

                        update_user_meta($user_id, $crmpn_key, $crmpn_value);
                      }
                    }
                  }

                  do_action('crmpn_form_save', $user_id, $crmpn_key_value, $crmpn_form_type, $crmpn_form_subtype);
                  break;
                case 'post':
                  if (empty($crmpn_form_subtype) || in_array($crmpn_form_subtype, ['post_new', 'post_edit'])) {
                    if (empty($post_id)) {
                      if (CRMPN_Functions_User::crmpn_user_is_admin(get_current_user_id())) {
                        $post_functions = new CRMPN_Functions_Post();
                        $title = !empty($_POST[$post_type . '_title']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST[$post_type . '_title'])) : '';
                        $description = !empty($_POST[$post_type . '_description']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_POST[$post_type . '_description'])) : '';
                        
                        $post_id = $post_functions->crmpn_insert_post($title, $description, '', sanitize_title($title), $post_type, 'publish', get_current_user_id());
                      }
                    }

                    if (!empty($post_id)) {
                      foreach ($crmpn_key_value as $crmpn_key => $crmpn_value) {
                        if ($crmpn_key == $post_type . '_title') {
                          wp_update_post([
                            'ID' => $post_id,
                            'post_title' => esc_html($crmpn_value),
                          ]);
                        }

                        if ($crmpn_key == $post_type . '_description') {
                          wp_update_post([
                            'ID' => $post_id,
                            'post_content' => esc_html($crmpn_value),
                          ]);
                        }

                        // Skip action and ajax type keys
                        if (in_array($crmpn_key, ['action', 'crmpn_ajax_nopriv_type'])) {
                          continue;
                        }

                        // Ensure option name is prefixed with crmpn_
                        // Special case: if key is just 'crmpn', don't add prefix as it's already the main option
                        if ($crmpn_key !== 'crmpn' && strpos((string)$crmpn_key, 'crmpn_') !== 0) {
                          $crmpn_key = 'crmpn_' . $crmpn_key;
                        } else {
                          // Key already has correct prefix
                        }

                        update_post_meta($post_id, $crmpn_key, $crmpn_value);
                      }
                    }
                  }

                  // Dispara el hook genérico.
                  do_action('crmpn_form_save', $post_id, $crmpn_key_value, $crmpn_form_type, $crmpn_form_subtype, $post_type);

                  // Si el formulario apunta al CPT de organización, delega también en la lógica específica.
                  if (!empty($post_type) && $post_type === 'crmpn_organization') {
                    /**
                     * Permite que la clase del CPT de organización gestione la creación/edición
                     * a partir de los datos enviados desde el frontal, aunque el usuario
                     * no tenga permisos de administrador.
                     */
                    do_action('crmpn_organization_form_save', $post_id, $crmpn_key_value, $crmpn_form_type, $crmpn_form_subtype);
                  }
                  break;
                case 'option':
                  if (CRMPN_Functions_User::crmpn_user_is_admin(get_current_user_id())) {
                    $crmpn_settings = new CRMPN_Settings();
                    $crmpn_options = $crmpn_settings->crmpn_get_options();
                    $crmpn_allowed_options = array_keys($crmpn_options);

                    // First, add html_multi field IDs to allowed options temporarily
                    foreach ($crmpn_options as $option_key => $option_config) {
                      if (isset($option_config['input']) && $option_config['input'] === 'html_multi' && 
                          isset($option_config['html_multi_fields']) && is_array($option_config['html_multi_fields'])) {
                        foreach ($option_config['html_multi_fields'] as $multi_field) {
                          if (isset($multi_field['id'])) {
                            $crmpn_allowed_options[] = $multi_field['id'];
                          }
                        }
                      }
                    }

                    // Process remaining individual fields
                    foreach ($crmpn_key_value as $crmpn_key => $crmpn_value) {
                      // Skip action and ajax type keys
                      if (in_array($crmpn_key, ['action', 'crmpn_ajax_nopriv_type'])) {
                        continue;
                      }

                      // Ensure option name is prefixed with crmpn_
                      // Special case: if key is just 'crmpn', don't add prefix as it's already the main option
                      if ($crmpn_key !== 'crmpn' && strpos((string)$crmpn_key, 'crmpn_') !== 0) {
                        $crmpn_key = 'crmpn_' . $crmpn_key;
                      } else {
                        // Key already has correct prefix
                      }

                      // Only update if option is in allowed options list
                      if (in_array($crmpn_key, $crmpn_allowed_options)) {
                        update_option($crmpn_key, $crmpn_value);
                      }
                    }
                  }

                  do_action('crmpn_form_save', 0, $crmpn_key_value, $crmpn_form_type, $crmpn_form_subtype);
                  break;
              }

              $popup_close = in_array($crmpn_form_subtype, ['post_new', 'post_edit', 'user_alt_new']) ? true : '';
              $update_list = in_array($crmpn_form_subtype, ['post_new', 'post_edit', 'user_alt_new']) ? true : '';
              $check = in_array($crmpn_form_subtype, ['post_check', 'post_uncheck']) ? $crmpn_form_subtype : '';
              
              if ($update_list && !empty($post_type)) {
                switch ($post_type) {
                  case 'crmpn_funnel':
                    $plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
                    $update_html = $plugin_post_type_funnel->crmpn_funnel_list();
                    break;
                  case 'crmpn_organization':
                    $plugin_post_type_organization = new CRMPN_Post_Type_organization();
                    $update_html = $plugin_post_type_organization->crmpn_organization_list();
                    break;
                }
              }else{
                $update_html = '';
              }

              echo wp_json_encode(['error_key' => '', 'popup_close' => $popup_close, 'update_list' => $update_list, 'update_html' => $update_html, 'check' => $check]);exit;
            }
          }else{
            echo wp_json_encode(['error_key' => 'crmpn_form_save_error', ]);exit;
          }
          break;
      }

      echo wp_json_encode(['error_key' => '', ]);exit;
    }
  }
}