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

              // For multiple fields, sanitize each element as a scalar value.
              // e.g. select-multiple → select, so the validator does not reject strings.
              $element_type = $pn_customers_manager_key['type'];
              if ($element_type === 'select-multiple') {
                $element_type = 'select-one';
              }
              $sanitized_array = array_map(function($value) use ($pn_customers_manager_key, $element_type) {
                return PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                  $value,
                  $pn_customers_manager_key['node'],
                  $element_type,
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
              'error_content' => esc_html__('Please enter a valid email address.', 'pn-customers-manager'),
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
              'error_content' => esc_html__('Too many attempts. Please try again later.', 'pn-customers-manager'),
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
              'error_content' => esc_html__('Please enter a valid email address.', 'pn-customers-manager'),
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
              'error_content' => esc_html__('Could not send the message. Please try again later.', 'pn-customers-manager'),
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => '']);
          exit;

        case 'pn_cm_woo_bis_subscribe':
          PN_CUSTOMERS_MANAGER_Woo_Back_In_Stock::handle_subscription();
          break;

        case 'pn_cm_funnel_form_submit':
          PN_CUSTOMERS_MANAGER_Funnel_Form::handle_submission();
          break;

        case 'pn_cm_mockup_flow_get_comments':
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_get_comments();
          break;
        case 'pn_cm_mockup_flow_get_image_urls':
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_get_image_urls();
          break;
        case 'pn_cm_mockup_flow_download_all':
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_download_all_images();
          break;
        case 'pn_cm_mockup_flow_add_comment':
          if (!is_user_logged_in()) {
            echo wp_json_encode(['error_key' => 'pn_cm_mf_login_required', 'error_content' => esc_html__('You must be logged in to add comments.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_add_comment();
          break;

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

                        // Skip invoice number and token — handled separately below
                        if (in_array($pn_customers_manager_key, ['pn_cm_invoice_number', 'pn_cm_invoice_token'], true)) {
                          continue;
                        }

                        update_post_meta($post_id, $pn_customers_manager_key, $pn_customers_manager_value);
                      }

                      // Auto-generate invoice number and token for new invoices
                      if (!empty($post_type) && $post_type === 'pn_cm_invoice' && $cm_pn_form_subtype === 'post_new') {
                        $existing_number = get_post_meta($post_id, 'pn_cm_invoice_number', true);
                        if (empty($existing_number)) {
                          $invoice_number = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_generate_number();
                          update_post_meta($post_id, 'pn_cm_invoice_number', $invoice_number);
                        }
                        $existing_token = get_post_meta($post_id, 'pn_cm_invoice_token', true);
                        if (empty($existing_token)) {
                          $token = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_generate_token();
                          update_post_meta($post_id, 'pn_cm_invoice_token', $token);
                        }
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

                  PN_CUSTOMERS_MANAGER_Activity_Log::log('settings', 'setting', 0, 'Settings', 'Settings updated');
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
                  case 'pn_cm_mockup_flow':
                    $update_html = PN_CUSTOMERS_MANAGER_Mockup_Flow::mockup_flow_list();
                    break;
                  case 'pn_cm_invoice':
                    $plugin_post_type_invoice = new PN_CUSTOMERS_MANAGER_Post_Type_Invoice();
                    $update_html = $plugin_post_type_invoice->pn_cm_invoice_list();
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

          // Verify item belongs to this budget and is optional
          $item = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_item($budget_id, $item_id);

          if (!$item || empty($item['is_optional'])) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Item not found or not optional.', 'pn-customers-manager')]]);
            exit;
          }

          PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_budget_item($budget_id, $item_id, ['is_selected' => $is_selected ? 1 : 0]);

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

        case 'pn_cm_budget_toggle_phase_items':
          $budget_id    = isset($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $budget_token = isset($_POST['budget_token']) ? sanitize_text_field(wp_unslash($_POST['budget_token'])) : '';
          $item_ids     = isset($_POST['item_ids']) ? array_map('intval', (array) $_POST['item_ids']) : [];
          $is_selected  = isset($_POST['is_selected']) ? intval($_POST['is_selected']) : 0;

          if (empty($budget_id) || empty($budget_token) || empty($item_ids)) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid request.', 'pn-customers-manager')]]);
            exit;
          }

          // Validate token.
          $stored_token = get_post_meta($budget_id, 'pn_cm_budget_token', true);
          if (empty($stored_token) || $stored_token !== $budget_token) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid token.', 'pn-customers-manager')]]);
            exit;
          }

          // Only allow toggling when status is "sent".
          $status = get_post_meta($budget_id, 'pn_cm_budget_status', true);
          if ($status !== 'sent') {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('This budget cannot be modified.', 'pn-customers-manager')]]);
            exit;
          }

          foreach ($item_ids as $iid) {
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_budget_item($budget_id, $iid, ['is_selected' => $is_selected ? 1 : 0]);
          }

          // Recalculate totals.
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

        case 'pn_cm_budget_toggle_alternative':
          $budget_id    = isset($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $budget_token = isset($_POST['budget_token']) ? sanitize_text_field(wp_unslash($_POST['budget_token'])) : '';
          $item_id      = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

          if (empty($budget_id) || empty($budget_token) || empty($item_id)) {
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
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('This budget cannot be modified.', 'pn-customers-manager')]]);
            exit;
          }

          $item = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_item($budget_id, $item_id);
          if (!$item) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Item not found.', 'pn-customers-manager')]]);
            exit;
          }

          $current = !empty($item['alt_active']) ? $item['alt_active'] : 'original';
          $new_state = ($current === 'original') ? 'alternative' : 'original';
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_budget_item($budget_id, $item_id, ['alt_active' => $new_state]);
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($budget_id);

          echo wp_json_encode([
            'success'    => true,
            'alt_active' => $new_state,
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

          // Store acceptor email.
          $acceptor_email = isset($_POST['acceptor_email']) ? sanitize_email(wp_unslash($_POST['acceptor_email'])) : '';
          if (!empty($acceptor_email)) {
            update_post_meta($budget_id, 'pn_cm_budget_accepted_by_email', $acceptor_email);
          }

          // Store acceptor name.
          $acceptor_name = isset($_POST['acceptor_name']) ? sanitize_text_field(wp_unslash($_POST['acceptor_name'])) : '';
          if (!empty($acceptor_name)) {
            update_post_meta($budget_id, 'pn_cm_budget_accepted_by_name', $acceptor_name);
          }

          // Save signature image as attachment.
          $signature_data = isset($_POST['signature_data']) ? wp_unslash($_POST['signature_data']) : '';
          if (!empty($signature_data) && strpos($signature_data, 'data:image/png;base64,') === 0) {
            $base64 = str_replace('data:image/png;base64,', '', $signature_data);
            $decoded = base64_decode($base64);

            if ($decoded !== false) {
              $upload_dir = wp_upload_dir();
              $sig_dir = $upload_dir['basedir'] . '/pn-cm-budget-signatures';
              if (!file_exists($sig_dir)) {
                wp_mkdir_p($sig_dir);
              }

              $filename = 'signature-budget-' . $budget_id . '-' . time() . '.png';
              $filepath = $sig_dir . '/' . $filename;

              if (file_put_contents($filepath, $decoded) !== false) {
                $file_url = $upload_dir['baseurl'] . '/pn-cm-budget-signatures/' . $filename;
                $attachment = array(
                  'post_mime_type' => 'image/png',
                  'post_title'    => 'Signature - Budget #' . get_post_meta($budget_id, 'pn_cm_budget_number', true),
                  'post_content'  => '',
                  'post_status'   => 'inherit',
                  'post_parent'   => $budget_id,
                );
                $attach_id = wp_insert_attachment($attachment, $filepath, $budget_id);
                if (!is_wp_error($attach_id)) {
                  require_once ABSPATH . 'wp-admin/includes/image.php';
                  $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
                  wp_update_attachment_metadata($attach_id, $attach_data);
                  update_post_meta($budget_id, 'pn_cm_budget_signature_attachment_id', $attach_id);
                }
              }
            }
          }

          // Recalculate totals to ensure they reflect the final item selection.
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($budget_id);

          // --- Send acceptance notification emails ---
          $this->pn_cm_budget_send_acceptance_email($budget_id);

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

        case 'pn_cm_invoice_download_file':
          $invoice_id    = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $invoice_token = isset($_POST['invoice_token']) ? sanitize_text_field(wp_unslash($_POST['invoice_token'])) : '';
          $file_id       = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;

          if (empty($invoice_id) || empty($invoice_token) || empty($file_id)) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid request.', 'pn-customers-manager')]]);
            exit;
          }

          $stored_token = get_post_meta($invoice_id, 'pn_cm_invoice_token', true);
          if (empty($stored_token) || $stored_token !== $invoice_token) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Invalid token.', 'pn-customers-manager')]]);
            exit;
          }

          $invoice_status = get_post_meta($invoice_id, 'pn_cm_invoice_status', true);
          if ($invoice_status === 'draft') {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('Downloads are not available for draft invoices.', 'pn-customers-manager')]]);
            exit;
          }

          $files = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_files($invoice_id);
          $target_file = null;
          foreach ($files as $f) {
            if (intval($f['id']) === $file_id) {
              $target_file = $f;
              break;
            }
          }

          if (!$target_file) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('File not found.', 'pn-customers-manager')]]);
            exit;
          }

          $dir = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_files_dir();
          $filepath = $dir . '/' . $target_file['stored_name'];

          if (!file_exists($filepath)) {
            echo wp_json_encode(['success' => false, 'data' => ['message' => esc_html__('File not found on disk.', 'pn-customers-manager')]]);
            exit;
          }

          header('Content-Type: application/octet-stream');
          header('Content-Disposition: attachment; filename="' . $target_file['filename'] . '"');
          header('Content-Length: ' . filesize($filepath));
          header('Cache-Control: no-store, no-cache, must-revalidate');
          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming file download to browser.
          readfile($filepath);
          exit;
      }

      echo wp_json_encode(['error_key' => '', ]);exit;
    }
  }

  /**
   * Send acceptance notification emails to client and admin.
   *
   * @param int $budget_id
   */
  private function pn_cm_budget_send_acceptance_email( $budget_id ) {
    global $wpdb;

    // Budget meta.
    $budget_number  = get_post_meta( $budget_id, 'pn_cm_budget_number', true );
    $accepted_at    = get_post_meta( $budget_id, 'pn_cm_budget_accepted_at', true );
    $subtotal       = floatval( get_post_meta( $budget_id, 'pn_cm_budget_subtotal', true ) );
    $discount_amount = floatval( get_post_meta( $budget_id, 'pn_cm_budget_discount_amount', true ) );
    $tax_amount     = floatval( get_post_meta( $budget_id, 'pn_cm_budget_tax_amount', true ) );
    $total          = floatval( get_post_meta( $budget_id, 'pn_cm_budget_total', true ) );

    // Organization / client data.
    $org_id    = get_post_meta( $budget_id, 'pn_cm_budget_organization_id', true );
    $org_name  = ! empty( $org_id ) ? get_the_title( $org_id ) : '';
    $org_email = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_email', true ) : '';

    // Company data.
    $company_name = get_option( 'pn_customers_manager_budget_company_name', '' );

    // Admin email.
    $admin_email = get_option( 'admin_email' );

    // Fetch budget items from post meta.
    $items = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_items( $budget_id );

    // Build items HTML table.
    $items_html = '<table style="width:100%;border-collapse:collapse;margin:16px 0;">';
    $items_html .= '<thead><tr style="background:#f5f5f5;">';
    $items_html .= '<th style="text-align:left;padding:8px;border:1px solid #ddd;">' . esc_html__( 'Description', 'pn-customers-manager' ) . '</th>';
    $items_html .= '<th style="text-align:center;padding:8px;border:1px solid #ddd;">' . esc_html__( 'Qty', 'pn-customers-manager' ) . '</th>';
    $items_html .= '<th style="text-align:right;padding:8px;border:1px solid #ddd;">' . esc_html__( 'Unit price', 'pn-customers-manager' ) . '</th>';
    $items_html .= '<th style="text-align:right;padding:8px;border:1px solid #ddd;">' . esc_html__( 'Total', 'pn-customers-manager' ) . '</th>';
    $items_html .= '<th style="text-align:center;padding:8px;border:1px solid #ddd;">' . esc_html__( 'Status', 'pn-customers-manager' ) . '</th>';
    $items_html .= '</tr></thead><tbody>';

    $current_phase = '';
    foreach ( $items as $item ) {
      $item_type = isset( $item['item_type'] ) ? $item['item_type'] : '';

      if ( 'phase' === $item_type ) {
        $current_phase = $item['description'];
        $items_html .= '<tr style="background:#eef2f7;">';
        $items_html .= '<td colspan="5" style="padding:8px;border:1px solid #ddd;font-weight:bold;">' . esc_html( wp_strip_all_tags( $item['description'] ) ) . '</td>';
        $items_html .= '</tr>';
        continue;
      }

      // Skip image and image_compare items — they are not relevant in email.
      if ( in_array( $item_type, array( 'image', 'image_compare' ), true ) ) {
        continue;
      }

      $line_total  = floatval( $item['quantity'] ) * floatval( $item['unit_price'] );
      $is_selected = intval( $item['is_selected'] );
      $is_optional = intval( $item['is_optional'] );

      $status_label = '';
      if ( $is_optional ) {
        $status_label = $is_selected
          ? '<span style="color:#2e7d32;">' . esc_html__( 'Approved', 'pn-customers-manager' ) . '</span>'
          : '<span style="color:#c62828;">' . esc_html__( 'Excluded', 'pn-customers-manager' ) . '</span>';
      } else {
        $status_label = '<span style="color:#555;">' . esc_html__( 'Included', 'pn-customers-manager' ) . '</span>';
      }

      $row_style = $is_optional && ! $is_selected ? 'color:#999;text-decoration:line-through;' : '';

      // Strip HTML tags from description for plain-text email rendering.
      $plain_description = wp_strip_all_tags( $item['description'] );

      $items_html .= '<tr style="' . $row_style . '">';
      $items_html .= '<td style="padding:8px;border:1px solid #ddd;">' . esc_html( $plain_description ) . '</td>';
      $items_html .= '<td style="text-align:center;padding:8px;border:1px solid #ddd;">' . esc_html( number_format( floatval( $item['quantity'] ), 2 ) ) . '</td>';
      $items_html .= '<td style="text-align:right;padding:8px;border:1px solid #ddd;">' . esc_html( PN_CUSTOMERS_MANAGER_Post_Type_Budget::format_currency( floatval( $item['unit_price'] ) ) ) . '</td>';
      $items_html .= '<td style="text-align:right;padding:8px;border:1px solid #ddd;">' . esc_html( PN_CUSTOMERS_MANAGER_Post_Type_Budget::format_currency( $line_total ) ) . '</td>';
      $items_html .= '<td style="text-align:center;padding:8px;border:1px solid #ddd;">' . $status_label . '</td>';
      $items_html .= '</tr>';
    }

    $items_html .= '</tbody></table>';

    // Totals summary.
    $totals_html = '<table style="width:auto;margin-left:auto;border-collapse:collapse;margin-top:8px;">';
    $totals_html .= '<tr><td style="padding:4px 16px;text-align:right;">' . esc_html__( 'Subtotal', 'pn-customers-manager' ) . '</td>';
    $totals_html .= '<td style="padding:4px 16px;text-align:right;font-weight:bold;">' . esc_html( PN_CUSTOMERS_MANAGER_Post_Type_Budget::format_currency( $subtotal ) ) . '</td></tr>';
    if ( $discount_amount > 0 ) {
      $totals_html .= '<tr><td style="padding:4px 16px;text-align:right;">' . esc_html__( 'Discount', 'pn-customers-manager' ) . '</td>';
      $totals_html .= '<td style="padding:4px 16px;text-align:right;color:#c62828;">-' . esc_html( PN_CUSTOMERS_MANAGER_Post_Type_Budget::format_currency( $discount_amount ) ) . '</td></tr>';
    }
    $tax_rate = floatval( get_post_meta( $budget_id, 'pn_cm_budget_tax_rate', true ) );
    if ( $tax_rate > 0 ) {
      $totals_html .= '<tr><td style="padding:4px 16px;text-align:right;">' . esc_html__( 'Tax', 'pn-customers-manager' ) . '</td>';
      $totals_html .= '<td style="padding:4px 16px;text-align:right;">' . esc_html( PN_CUSTOMERS_MANAGER_Post_Type_Budget::format_currency( $tax_amount ) ) . '</td></tr>';
    }
    $totals_html .= '<tr style="border-top:2px solid #333;"><td style="padding:8px 16px;text-align:right;font-weight:bold;">' . esc_html__( 'Total', 'pn-customers-manager' ) . '</td>';
    $totals_html .= '<td style="padding:8px 16px;text-align:right;font-weight:bold;font-size:1.1em;">' . esc_html( PN_CUSTOMERS_MANAGER_Post_Type_Budget::format_currency( $total ) ) . '</td></tr>';
    $totals_html .= '</table>';

    // Compose email body.
    $site_name = get_option( 'blogname', '' );
    if ( empty( $site_name ) ) {
      $site_name = wp_parse_url( home_url(), PHP_URL_HOST );
    }

    $subject = sprintf(
      '[%s] %s #%s',
      $site_name,
      __( 'Budget accepted', 'pn-customers-manager' ),
      $budget_number
    );

    // translators: %s is the budget number (e.g. #123)
    $html_body  = '<h2>' . sprintf( esc_html__( 'Budget %s has been accepted', 'pn-customers-manager' ), '#' . esc_html( $budget_number ) ) . '</h2>';
    if ( ! empty( $org_name ) ) {
      $html_body .= '<p><strong>' . esc_html__( 'Client', 'pn-customers-manager' ) . ':</strong> ' . esc_html( $org_name ) . '</p>';
    }
    $acceptor_name  = get_post_meta( $budget_id, 'pn_cm_budget_accepted_by_name', true );
    $acceptor_email = get_post_meta( $budget_id, 'pn_cm_budget_accepted_by_email', true );
    if ( ! empty( $acceptor_name ) ) {
      $html_body .= '<p><strong>' . esc_html__( 'Signed by', 'pn-customers-manager' ) . ':</strong> ' . esc_html( $acceptor_name );
      if ( ! empty( $acceptor_email ) ) {
        $html_body .= ' (' . esc_html( $acceptor_email ) . ')';
      }
      $html_body .= '</p>';
    } elseif ( ! empty( $acceptor_email ) ) {
      $html_body .= '<p><strong>' . esc_html__( 'Accepted by', 'pn-customers-manager' ) . ':</strong> ' . esc_html( $acceptor_email ) . '</p>';
    }
    $html_body .= '<p><strong>' . esc_html__( 'Accepted on', 'pn-customers-manager' ) . ':</strong> ' . esc_html( $accepted_at ) . '</p>';

    // Signature image.
    $signature_id = get_post_meta( $budget_id, 'pn_cm_budget_signature_attachment_id', true );
    if ( ! empty( $signature_id ) ) {
      $signature_url = wp_get_attachment_url( intval( $signature_id ) );
      if ( $signature_url ) {
        $html_body .= '<div style="margin:16px 0;text-align:center;">';
        $html_body .= '<img src="' . esc_url( $signature_url ) . '" alt="' . esc_attr__( 'Signature', 'pn-customers-manager' ) . '" style="max-width:300px;height:auto;border-bottom:2px solid #333;padding-bottom:8px;" />';
        $html_body .= '</div>';
      }
    }

    $html_body .= '<h3>' . esc_html__( 'Items detail', 'pn-customers-manager' ) . '</h3>';
    $html_body .= $items_html;
    $html_body .= $totals_html;

    // Recipients: admin + client + acceptor.
    $recipients = array();
    if ( ! empty( $admin_email ) ) {
      $recipients[] = $admin_email;
    }
    if ( ! empty( $org_email ) && $org_email !== $admin_email ) {
      $recipients[] = $org_email;
    }
    if ( ! empty( $acceptor_email ) && ! in_array( $acceptor_email, $recipients, true ) ) {
      $recipients[] = $acceptor_email;
    }

    if ( empty( $recipients ) ) {
      return;
    }

    // Send via MailPN if available, otherwise wp_mail.
    if ( class_exists( 'MAILPN_Mailing' ) ) {
      $mailing = new MAILPN_Mailing();
      foreach ( $recipients as $to ) {
        $mailing->mailpn_sender( [
          'mailpn_user_to' => $to,
          'mailpn_subject' => $subject,
          'mailpn_type'    => 'pn_cm_budget_accepted',
        ], $html_body );
      }
    } else {
      $headers = array( 'Content-Type: text/html; charset=UTF-8' );
      foreach ( $recipients as $to ) {
        wp_mail( $to, $subject, $html_body, $headers );
      }
    }
  }
}