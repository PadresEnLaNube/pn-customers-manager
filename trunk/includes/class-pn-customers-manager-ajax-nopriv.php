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
  public function pn_customers_manager_ajax_nopriv_server() {
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

      $pn_customers_manager_ajax_nopriv_type = PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['pn_customers_manager_ajax_nopriv_type']));
      
      $pn_customers_manager_ajax_keys = !empty($_POST['pn_customers_manager_ajax_keys']) ? array_map(function($key) {
        $sanitized_key = wp_unslash($key);
        return array(
          'id' => preg_replace('/[^a-zA-Z0-9_\-\[\]]/', '', $sanitized_key['id']),
          'node' => sanitize_key($sanitized_key['node']),
          'type' => sanitize_key($sanitized_key['type']),
          'multiple' => sanitize_key($sanitized_key['multiple'])
        );
      }, wp_unslash($_POST['pn_customers_manager_ajax_keys'])) : [];

      $pn_customers_manager_key_value = [];

      if (!empty($pn_customers_manager_ajax_keys)) {
        foreach ($pn_customers_manager_ajax_keys as $pn_customers_manager_key) {
          if ($pn_customers_manager_key['multiple'] == 'true') {
            $pn_customers_manager_clear_key = str_replace('[]', '', $pn_customers_manager_key['id']);
            ${$pn_customers_manager_clear_key} = $pn_customers_manager_key_value[$pn_customers_manager_clear_key] = [];

            if (!empty($_POST[$pn_customers_manager_clear_key])) {
              $unslashed_array = wp_unslash($_POST[$pn_customers_manager_clear_key]);
              
              if (!is_array($unslashed_array)) {
                $unslashed_array = array($unslashed_array);
              }

              $sanitized_array = array_map(function($value) use ($pn_customers_manager_key) {
                return PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                  $value,
                  $pn_customers_manager_key['node'],
                  $pn_customers_manager_key['type'],
                  $pn_customers_manager_key['field_config'] ?? [],
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
            $sanitized_key = $pn_customers_manager_key['id'];
            $unslashed_value = !empty($_POST[$sanitized_key]) ? wp_unslash($_POST[$sanitized_key]) : '';
            
            $pn_customers_manager_key_id = !empty($unslashed_value) ? 
              PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                $unslashed_value, 
                $pn_customers_manager_key['node'], 
                $pn_customers_manager_key['type'],
                $pn_customers_manager_key['field_config'] ?? [],
              ) : '';
            
              ${$pn_customers_manager_key['id']} = $pn_customers_manager_key_value[$pn_customers_manager_key['id']] = $pn_customers_manager_key_id;
          }
        }
      }

      switch ($pn_customers_manager_ajax_nopriv_type) {
        case 'pn_cm_qr_referral_create':
          $referral_email = isset($_POST['referral_email']) ? sanitize_email(wp_unslash($_POST['referral_email'])) : '';
          $referral_code  = isset($_POST['referral_code']) ? sanitize_text_field(wp_unslash($_POST['referral_code'])) : '';

          if (empty($referral_email) || !is_email($referral_email)) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_qr_referral_invalid_email',
              'error_content' => esc_html__('Introduce un correo electrónico válido.', 'pn-customers-manager'),
            ]);
            exit;
          }

          if (empty($referral_code) || strlen($referral_code) !== 8 || !preg_match('/^[A-Z0-9]{8}$/', $referral_code)) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_qr_referral_invalid_code',
              'error_content' => esc_html__('Referral code is invalid.', 'pn-customers-manager'),
            ]);
            exit;
          }

          // Rate limit: 5 attempts per IP per hour
          $ip_hash = md5(sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''));
          $rate_key = 'pn_cm_qr_rate_' . $ip_hash;
          $attempts = (int) get_transient($rate_key);

          if ($attempts >= 5) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_qr_referral_rate_limit',
              'error_content' => esc_html__('Demasiados intentos. Inténtalo de nuevo más tarde.', 'pn-customers-manager'),
            ]);
            exit;
          }

          set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

          $referrer_id = PN_CUSTOMERS_MANAGER_Referral::find_user_by_qr_code($referral_code);

          if (!$referrer_id) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_qr_referral_invalid_code',
              'error_content' => esc_html__('Referral code is invalid.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $result = PN_CUSTOMERS_MANAGER_Referral::create_referral($referrer_id, $referral_email);

          if (isset($result['error'])) {
            $error_messages = [
              'invalid_email'        => esc_html__('Invalid email.', 'pn-customers-manager'),
              'email_exists'         => esc_html__('This email is already registered.', 'pn-customers-manager'),
              'already_sent'         => esc_html__('An invitation has already been sent to this email.', 'pn-customers-manager'),
              'user_creation_failed' => esc_html__('User creation failed. Try again.', 'pn-customers-manager'),
            ];

            $error_msg = isset($error_messages[$result['error']]) ? $error_messages[$result['error']] : esc_html__('Ha ocurrido un error.', 'pn-customers-manager');

            echo wp_json_encode([
              'error_key'     => 'pn_cm_qr_referral_' . $result['error'],
              'error_content' => $error_msg,
            ]);
            exit;
          }

          echo wp_json_encode([
            'error_key'     => '',
            'error_content' => esc_html__('Registro completado! Revisa tu email para activar tu cuenta.', 'pn-customers-manager'),
          ]);
          exit;

        case 'pn_cm_contact_send':
          $contact_name    = isset($_POST['contact_name']) ? sanitize_text_field(wp_unslash($_POST['contact_name'])) : '';
          $contact_email   = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
          $contact_subject = isset($_POST['contact_subject']) ? sanitize_text_field(wp_unslash($_POST['contact_subject'])) : '';
          $contact_message = isset($_POST['contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['contact_message'])) : '';
          $contact_honey   = isset($_POST['contact_website']) ? sanitize_text_field(wp_unslash($_POST['contact_website'])) : '';
          $recipient_email = isset($_POST['contact_recipient_email']) ? sanitize_email(wp_unslash($_POST['contact_recipient_email'])) : '';

          // Honeypot check
          if (!empty($contact_honey)) {
            echo wp_json_encode(['error_key' => '']);
            exit;
          }

          // Required fields
          if (empty($contact_name) || empty($contact_email) || empty($contact_message)) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_missing_fields',
              'error_content' => esc_html__('Por favor, completa todos los campos obligatorios.', 'pn-customers-manager'),
            ]);
            exit;
          }

          // Validate email
          if (!is_email($contact_email)) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_invalid_email',
              'error_content' => esc_html__('Introduce un correo electrónico válido.', 'pn-customers-manager'),
            ]);
            exit;
          }

          // Determine recipient
          $to = !empty($recipient_email) && is_email($recipient_email) ? $recipient_email : get_option('admin_email');

          // Sanitize source fields
          $source_url   = isset($_POST['contact_source_url']) ? esc_url_raw(wp_unslash($_POST['contact_source_url'])) : '';
          $source_title = isset($_POST['contact_source_title']) ? sanitize_text_field(wp_unslash($_POST['contact_source_title'])) : '';

          // ── Akismet bot detection ──
          $is_spam = 0;
          $akismet_enabled = get_option('pn_customers_manager_akismet_enabled') === 'on';
          $akismet_discard = get_option('pn_customers_manager_akismet_discard') === 'on';

          if ($akismet_enabled && class_exists('Akismet') && method_exists('Akismet', 'get_api_key') && Akismet::get_api_key()) {
            $akismet_params = [
              'blog'                 => home_url(),
              'user_ip'              => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
              'user_agent'           => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
              'referrer'             => sanitize_text_field($_SERVER['HTTP_REFERER'] ?? ''),
              'permalink'            => $source_url,
              'comment_type'         => 'contact-form',
              'comment_author'       => $contact_name,
              'comment_author_email' => $contact_email,
              'comment_content'      => $contact_message,
              'blog_lang'            => get_locale(),
              'blog_charset'         => get_bloginfo('charset'),
            ];

            if (!empty($contact_subject)) {
              $akismet_params['comment_author_url'] = '';
            }

            try {
              $akismet_response = Akismet::http_post(http_build_query($akismet_params), 'comment-check');

              // Response is an array: [headers, body]. Body is 'true' | 'false'.
              if (is_array($akismet_response) && isset($akismet_response[1])) {
                $akismet_body = trim((string) $akismet_response[1]);

                // "discard" hint may arrive in the Akismet headers.
                $is_discard = false;
                if (isset($akismet_response[0]) && is_array($akismet_response[0])) {
                  foreach ($akismet_response[0] as $h_key => $h_val) {
                    if (strtolower((string) $h_key) === 'x-akismet-pro-tip' && strtolower(trim((string) $h_val)) === 'discard') {
                      $is_discard = true;
                      break;
                    }
                  }
                }

                if ($akismet_body === 'true') {
                  $is_spam = 1;

                  if ($is_discard && $akismet_discard) {
                    // Silently accept the submission without storing it or
                    // emailing anybody. The caller gets a success response
                    // so the bot can't probe the filter.
                    echo wp_json_encode(['error_key' => '']);
                    exit;
                  }
                }
              }
            } catch (\Exception $e) {
              // If Akismet fails, fall back to storing the message normally.
              $is_spam = 0;
            }
          }

          // Save to database (always, regardless of email result)
          global $wpdb;
          $wpdb->insert(
            $wpdb->prefix . 'pn_cm_contact_messages',
            [
              'contact_name'    => $contact_name,
              'contact_email'   => $contact_email,
              'contact_subject' => $contact_subject,
              'contact_message' => $contact_message,
              'recipient_email' => $to,
              'source_url'      => $source_url,
              'source_title'    => $source_title,
              'ip_address'      => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
              'is_read'         => 0,
              'is_spam'         => $is_spam,
              'created_at'      => current_time('mysql'),
            ],
            ['%s','%s','%s','%s','%s','%s','%s','%s','%d','%d','%s']
          );

          // Do not send notification emails for spam; just return success so
          // the bot can't infer that it was caught.
          if ($is_spam) {
            echo wp_json_encode(['error_key' => '']);
            exit;
          }

          $site_name = get_option('blogname', '');
          if (empty($site_name)) {
            $site_name = wp_parse_url(home_url(), PHP_URL_HOST);
          }
          $subject   = !empty($contact_subject)
            ? sprintf('[%s] %s', $site_name, $contact_subject)
            : sprintf('[%s] %s', $site_name, esc_html__('Nuevo mensaje de contacto', 'pn-customers-manager'));

          // Try sending via mailpn if available, otherwise fallback to wp_mail
          if (class_exists('MAILPN_Mailing')) {
            $html_body  = sprintf('<p><strong>%s:</strong> %s</p>', esc_html__('Nombre', 'pn-customers-manager'), esc_html($contact_name));
            $html_body .= sprintf('<p><strong>%s:</strong> %s</p>', esc_html__('Email', 'pn-customers-manager'), esc_html($contact_email));
            if (!empty($contact_subject)) {
              $html_body .= sprintf('<p><strong>%s:</strong> %s</p>', esc_html__('Asunto', 'pn-customers-manager'), esc_html($contact_subject));
            }
            $html_body .= sprintf('<p><strong>%s:</strong></p><p>%s</p>', esc_html__('Mensaje', 'pn-customers-manager'), nl2br(esc_html($contact_message)));

            $mailing = new MAILPN_Mailing();
            $sent = $mailing->mailpn_sender([
              'mailpn_user_to' => $to,
              'mailpn_subject' => $subject,
              'mailpn_type'    => 'pn_cm_contact_form',
            ], $html_body);
          } else {
            $body  = sprintf("%s: %s\n", esc_html__('Nombre', 'pn-customers-manager'), $contact_name);
            $body .= sprintf("%s: %s\n", esc_html__('Email', 'pn-customers-manager'), $contact_email);
            if (!empty($contact_subject)) {
              $body .= sprintf("%s: %s\n", esc_html__('Asunto', 'pn-customers-manager'), $contact_subject);
            }
            $body .= sprintf("\n%s:\n%s\n", esc_html__('Mensaje', 'pn-customers-manager'), $contact_message);

            $headers = [
              'Content-Type: text/plain; charset=UTF-8',
              sprintf('Reply-To: %s <%s>', $contact_name, $contact_email),
            ];

            $sent = wp_mail($to, $subject, $body, $headers);
          }

          if (!$sent) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_send_failed',
              'error_content' => esc_html__('No se pudo enviar el mensaje. Inténtalo de nuevo más tarde.', 'pn-customers-manager'),
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => '']);
          exit;

        case 'cm_pn_form_save':
          $cm_pn_form_type = !empty($_POST['cm_pn_form_type']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['cm_pn_form_type'])) : '';

          if (!empty($pn_customers_manager_key_value) && !empty($cm_pn_form_type)) {
            $cm_pn_form_id = !empty($_POST['cm_pn_form_id']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['cm_pn_form_id'])) : 0;
            $cm_pn_form_subtype = !empty($_POST['cm_pn_form_subtype']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['cm_pn_form_subtype'])) : '';
            $user_id = !empty($_POST['cm_pn_form_user_id']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['cm_pn_form_user_id'])) : 0;
            $post_id = !empty($_POST['cm_pn_form_post_id']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['cm_pn_form_post_id'])) : 0;
            $post_type = !empty($_POST['cm_pn_form_post_type']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['cm_pn_form_post_type'])) : '';

            if (($cm_pn_form_type == 'user' && empty($user_id) && !in_array($cm_pn_form_subtype, ['user_alt_new'])) || ($cm_pn_form_type == 'post' && (empty($post_id) && !(!empty($cm_pn_form_subtype) && in_array($cm_pn_form_subtype, ['post_new', 'post_edit'])))) || ($cm_pn_form_type == 'option' && !is_user_logged_in())) {
              session_start();

              $_SESSION['cm_pn_form'] = [];
              $_SESSION['cm_pn_form'][$cm_pn_form_id] = [];
              $_SESSION['cm_pn_form'][$cm_pn_form_id]['form_type'] = $cm_pn_form_type;
              $_SESSION['cm_pn_form'][$cm_pn_form_id]['values'] = $pn_customers_manager_key_value;

              if (!empty($post_id)) {
                $_SESSION['cm_pn_form'][$cm_pn_form_id]['post_id'] = $post_id;
              }

              echo wp_json_encode(['error_key' => 'cm_pn_form_save_error_unlogged', ]);exit;
            }else{
              switch ($cm_pn_form_type) {
                case 'user':
                  if (!in_array($cm_pn_form_subtype, ['user_alt_new'])) {
                    if (empty($user_id)) {
                      if (PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_is_admin(get_current_user_id())) {
                        $user_login = !empty($_POST['user_login']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['user_login'])) : 0;
                        $user_password = !empty($_POST['user_password']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['user_password'])) : 0;
                        $user_email = !empty($_POST['user_email']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST['user_email'])) : 0;

                        $user_id = PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_insert($user_login, $user_password, $user_email);
                      }
                    }

                    if (!empty($user_id)) {
                      // Authorization: only the account owner or an admin may update user meta
                      if (!is_user_logged_in() || (intval($user_id) !== get_current_user_id() && !PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_is_admin(get_current_user_id()))) {
                        echo wp_json_encode(['error_key' => 'pn_customers_manager_form_save_error_unauthorized', 'error_content' => esc_html(__('You are not authorized to perform this action.', 'pn-customers-manager'))]);
                        exit;
                      }

                      foreach ($pn_customers_manager_key_value as $pn_customers_manager_key => $pn_customers_manager_value) {
                        // Skip action and ajax type keys
                        if (in_array($pn_customers_manager_key, ['action', 'pn_customers_manager_ajax_nopriv_type'])) {
                          continue;
                        }

                        // Ensure option name is prefixed with pn_customers_manager_
                        // Special case: if key is just 'pn-customers-manager', don't add prefix as it's already the main option
                        if ($pn_customers_manager_key !== 'pn-customers-manager' && strpos((string)$pn_customers_manager_key, 'pn_customers_manager_') !== 0) {
                          $pn_customers_manager_key = 'pn_customers_manager_' . $pn_customers_manager_key;
                        } else {
                          // Key already has correct prefix
                        }

                        update_user_meta($user_id, $pn_customers_manager_key, $pn_customers_manager_value);
                      }
                    }
                  }

                  do_action('pn_customers_manager_form_save', $user_id, $pn_customers_manager_key_value, $cm_pn_form_type, $cm_pn_form_subtype, '');
                  break;
                case 'post':
                  if (empty($cm_pn_form_subtype) || in_array($cm_pn_form_subtype, ['post_new', 'post_edit'])) {
                    if (empty($post_id)) {
                      if (PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_is_admin(get_current_user_id())) {
                        $post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();
                        $title = !empty($_POST[$post_type . '_title']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST[$post_type . '_title'])) : '';
                        $description = !empty($_POST[$post_type . '_description']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_POST[$post_type . '_description'])) : '';
                        
                        $post_id = $post_functions->pn_customers_manager_insert_post($title, $description, '', sanitize_title($title), $post_type, 'publish', get_current_user_id());
                      }
                    }

                    if (!empty($post_id)) {
                      // Authorization: only post owner or admin may update post meta
                      if (!is_user_logged_in()) {
                        echo wp_json_encode(['error_key' => 'pn_customers_manager_form_save_error_unauthorized', 'error_content' => esc_html(__('You are not authorized to perform this action.', 'pn-customers-manager'))]);
                        exit;
                      }
                      $post_author_id = intval(get_post_field('post_author', $post_id));
                      if (get_current_user_id() !== $post_author_id && !PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_is_admin(get_current_user_id())) {
                        echo wp_json_encode(['error_key' => 'pn_customers_manager_form_save_error_unauthorized', 'error_content' => esc_html(__('You are not authorized to perform this action.', 'pn-customers-manager'))]);
                        exit;
                      }

                      foreach ($pn_customers_manager_key_value as $pn_customers_manager_key => $pn_customers_manager_value) {
                        if ($pn_customers_manager_key == $post_type . '_title') {
                          wp_update_post([
                            'ID' => $post_id,
                            'post_title' => esc_html($pn_customers_manager_value),
                          ]);
                        }

                        if ($pn_customers_manager_key == $post_type . '_description') {
                          wp_update_post([
                            'ID' => $post_id,
                            'post_content' => esc_html($pn_customers_manager_value),
                          ]);
                        }

                        // Skip action and ajax type keys
                        if (in_array($pn_customers_manager_key, ['action', 'pn_customers_manager_ajax_nopriv_type'])) {
                          continue;
                        }

                        // Ensure option name is prefixed with pn_customers_manager_
                        // Special case: if key is just 'pn-customers-manager', don't add prefix as it's already the main option
                        if ($pn_customers_manager_key !== 'pn-customers-manager' && strpos((string)$pn_customers_manager_key, 'pn_customers_manager_') !== 0) {
                          $pn_customers_manager_key = 'pn_customers_manager_' . $pn_customers_manager_key;
                        } else {
                          // Key already has correct prefix
                        }

                        update_post_meta($post_id, $pn_customers_manager_key, $pn_customers_manager_value);
                      }
                    }
                  }

                  // Dispara el hook genérico.
                  do_action('pn_customers_manager_form_save', $post_id, $pn_customers_manager_key_value, $cm_pn_form_type, $cm_pn_form_subtype, $post_type);

                  // Si el formulario apunta al CPT de organización, delega también en la lógica específica.
                  if (!empty($post_type) && $post_type === 'pn_cm_organization') {
                    /**
                     * Permite que la clase del CPT de organización gestione la creación/edición
                     * a partir de los datos enviados desde el frontal, aunque el usuario
                     * no tenga permisos de administrador.
                     */
                    do_action('Pn_cm_organization_form_save', $post_id, $pn_customers_manager_key_value, $cm_pn_form_type, $cm_pn_form_subtype);
                  }

                  if (!empty($post_type) && $post_type === 'pn_cm_budget') {
                    do_action('pn_cm_budget_form_save', $post_id, $pn_customers_manager_key_value, $cm_pn_form_type, $cm_pn_form_subtype, $post_type);
                  }
                  break;
                case 'option':
                  if (PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_is_admin(get_current_user_id())) {
                    $pn_customers_manager_settings = new PN_CUSTOMERS_MANAGER_Settings();
                    $pn_customers_manager_options = $pn_customers_manager_settings->pn_customers_manager_get_options();
                    $pn_customers_manager_allowed_options = array_keys($pn_customers_manager_options);

                    // First, add html_multi field IDs to allowed options temporarily
                    foreach ($pn_customers_manager_options as $option_key => $option_config) {
                      if (isset($option_config['input']) && $option_config['input'] === 'html_multi' && 
                          isset($option_config['html_multi_fields']) && is_array($option_config['html_multi_fields'])) {
                        foreach ($option_config['html_multi_fields'] as $multi_field) {
                          if (isset($multi_field['id'])) {
                            $pn_customers_manager_allowed_options[] = $multi_field['id'];
                          }
                        }
                      }
                    }

                    // Process remaining individual fields
                    foreach ($pn_customers_manager_key_value as $pn_customers_manager_key => $pn_customers_manager_value) {
                      // Skip action and ajax type keys
                      if (in_array($pn_customers_manager_key, ['action', 'pn_customers_manager_ajax_nopriv_type'])) {
                        continue;
                      }

                      // Ensure option name is prefixed with pn_customers_manager_
                      // Special case: if key is just 'pn-customers-manager', don't add prefix as it's already the main option
                      if ($pn_customers_manager_key !== 'pn-customers-manager' && strpos((string)$pn_customers_manager_key, 'pn_customers_manager_') !== 0) {
                        $pn_customers_manager_key = 'pn_customers_manager_' . $pn_customers_manager_key;
                      } else {
                        // Key already has correct prefix
                      }

                      // Only update if option is in allowed options list
                      if (in_array($pn_customers_manager_key, $pn_customers_manager_allowed_options)) {
                        update_option($pn_customers_manager_key, $pn_customers_manager_value);
                      }
                    }
                  }

                  do_action('pn_customers_manager_form_save', 0, $pn_customers_manager_key_value, $cm_pn_form_type, $cm_pn_form_subtype, '');
                  break;
              }

              $popup_close = in_array($cm_pn_form_subtype, ['post_new', 'post_edit', 'user_alt_new']) ? true : '';
              $update_list = in_array($cm_pn_form_subtype, ['post_new', 'post_edit', 'user_alt_new']) ? true : '';
              $check = in_array($cm_pn_form_subtype, ['post_check', 'post_uncheck']) ? $cm_pn_form_subtype : '';
              
              if ($update_list && !empty($post_type)) {
                switch ($post_type) {
                  case 'pn_cm_funnel':
                    $plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
                    $update_html = $plugin_post_type_funnel->pn_cm_funnel_list();
                    break;
                  case 'pn_cm_organization':
                    $plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
                    $update_html = $plugin_post_type_organization->pn_cm_organization_list();
                    break;
                  case 'pn_cm_budget':
                    $plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
                    $update_html = $plugin_post_type_budget->pn_cm_budget_list();
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

        case 'pn_cm_budget_toggle_item':
          $budget_id    = isset($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $budget_token = isset($_POST['budget_token']) ? sanitize_text_field(wp_unslash($_POST['budget_token'])) : '';
          $item_id      = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          $is_selected  = isset($_POST['is_selected']) ? intval($_POST['is_selected']) : 0;

          if (empty($budget_id) || empty($budget_token) || empty($item_id)) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid request.', 'pn-customers-manager')]]);
            exit;
          }

          // Validate token
          $stored_token = get_post_meta($budget_id, 'pn_cm_budget_token', true);
          if (empty($stored_token) || $stored_token !== $budget_token) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid token.', 'pn-customers-manager')]]);
            exit;
          }

          // Only allow toggling when status is "sent"
          $status = get_post_meta($budget_id, 'pn_cm_budget_status', true);
          if ($status !== 'sent') {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('This budget cannot be modified.', 'pn-customers-manager')]]);
            exit;
          }

          global $wpdb;
          $table = $wpdb->prefix . 'pn_cm_budget_items';

          // Verify item belongs to this budget and is optional
          $item = $wpdb->get_row($wpdb->prepare(
            "SELECT id, is_optional FROM {$table} WHERE id = %d AND budget_id = %d",
            $item_id, $budget_id
          ));

          if (!$item || !$item->is_optional) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Item not found or not optional.', 'pn-customers-manager')]]);
            exit;
          }

          $wpdb->update($table, ['is_selected' => $is_selected ? 1 : 0], ['id' => $item_id], ['%d'], ['%d']);

          // Recalculate totals
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($budget_id);

          echo wp_json_encode([
            'success' => true,
            'data' => [
              'subtotal' => get_post_meta($budget_id, 'pn_cm_budget_subtotal', true),
              'discount' => get_post_meta($budget_id, 'pn_cm_budget_discount_amount', true),
              'tax'      => get_post_meta($budget_id, 'pn_cm_budget_tax_amount', true),
              'total'    => get_post_meta($budget_id, 'pn_cm_budget_total', true),
            ],
          ]);
          exit;

        case 'pn_cm_budget_accept':
          $budget_id    = isset($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $budget_token = isset($_POST['budget_token']) ? sanitize_text_field(wp_unslash($_POST['budget_token'])) : '';

          if (empty($budget_id) || empty($budget_token)) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid request.', 'pn-customers-manager')]]);
            exit;
          }

          $stored_token = get_post_meta($budget_id, 'pn_cm_budget_token', true);
          if (empty($stored_token) || $stored_token !== $budget_token) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid token.', 'pn-customers-manager')]]);
            exit;
          }

          $status = get_post_meta($budget_id, 'pn_cm_budget_status', true);
          if ($status !== 'sent') {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('This budget cannot be accepted.', 'pn-customers-manager')]]);
            exit;
          }

          update_post_meta($budget_id, 'pn_cm_budget_status', 'accepted');
          update_post_meta($budget_id, 'pn_cm_budget_accepted_at', current_time('mysql'));

          echo wp_json_encode(['success' => true]);
          exit;

        case 'pn_cm_budget_reject':
          $budget_id    = isset($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $budget_token = isset($_POST['budget_token']) ? sanitize_text_field(wp_unslash($_POST['budget_token'])) : '';

          if (empty($budget_id) || empty($budget_token)) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid request.', 'pn-customers-manager')]]);
            exit;
          }

          $stored_token = get_post_meta($budget_id, 'pn_cm_budget_token', true);
          if (empty($stored_token) || $stored_token !== $budget_token) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid token.', 'pn-customers-manager')]]);
            exit;
          }

          $status = get_post_meta($budget_id, 'pn_cm_budget_status', true);
          if ($status !== 'sent') {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('This budget cannot be rejected.', 'pn-customers-manager')]]);
            exit;
          }

          update_post_meta($budget_id, 'pn_cm_budget_status', 'rejected');
          update_post_meta($budget_id, 'pn_cm_budget_rejected_at', current_time('mysql'));

          echo wp_json_encode(['success' => true]);
          exit;
      }

      echo wp_json_encode(['error_key' => '', ]);exit;
    }
  }
}