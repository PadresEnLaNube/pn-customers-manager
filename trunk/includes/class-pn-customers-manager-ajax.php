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
  public function pn_customers_manager_ajax_server() {
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

      // Only CRM managers and administrators can use the CRM AJAX endpoints.
      if (!current_user_can('edit_pn_cm_funnel') && !current_user_can('edit_pn_cm_organization') && !current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
        echo wp_json_encode([
          'error_key' => 'pn_customers_manager_access_denied',
          'error_content' => esc_html(__('You do not have permission to access this section.', 'pn-customers-manager')),
        ]);
        exit;
      }

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
            $plugin_post_type_post->pn_customers_manager_duplicate_post($pn_cm_funnel_id, 'publish');
            
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
        case 'pn_cm_funnel_node_settings':
          $node_subtype = !empty($_POST['node_subtype']) ? sanitize_text_field(wp_unslash($_POST['node_subtype'])) : '';
          echo PN_CUSTOMERS_MANAGER_Funnel_Builder::render_settings_popup_fields($node_subtype);
          exit;
        case 'pn_cm_funnel_builder_save':
          PN_CUSTOMERS_MANAGER_Funnel_Builder::ajax_save_canvas($pn_cm_funnel_id);
          break;
        case 'pn_cm_funnel_builder_load':
          PN_CUSTOMERS_MANAGER_Funnel_Builder::ajax_load_canvas($pn_cm_funnel_id);
          break;
        case 'pn_cm_openai_test':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_WhatsApp_AI::ajax_test_openai());
          exit;
        case 'pn_cm_whatsapp_test':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          $test_phone = !empty($_POST['test_phone']) ? sanitize_text_field(wp_unslash($_POST['test_phone'])) : '';
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_WhatsApp_AI::ajax_test_whatsapp($test_phone));
          exit;
        case 'pn_cm_whatsapp_test_receive':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          $since = !empty($_POST['since']) ? sanitize_text_field(wp_unslash($_POST['since'])) : '';
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_WhatsApp_AI::ajax_test_webhook_receive($since));
          exit;
        case 'pn_cm_wa_conversations_list':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_WhatsApp_AI::ajax_get_conversations_list());
          exit;
        case 'pn_cm_wa_conversation_messages':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          $conv_id = !empty($_POST['conv_id']) ? absint($_POST['conv_id']) : 0;
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_WhatsApp_AI::ajax_get_conversation_messages($conv_id));
          exit;
        case 'pn_cm_instagram_test':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          $test_ig_id = !empty($_POST['test_ig_id']) ? sanitize_text_field(wp_unslash($_POST['test_ig_id'])) : '';
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_Instagram_AI::ajax_test_instagram($test_ig_id));
          exit;
        case 'pn_cm_instagram_test_receive':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          $since = !empty($_POST['since']) ? sanitize_text_field(wp_unslash($_POST['since'])) : '';
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_Instagram_AI::ajax_test_webhook_receive($since));
          exit;
        case 'pn_cm_ig_conversations_list':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_Instagram_AI::ajax_get_conversations_list());
          exit;
        case 'pn_cm_ig_conversation_messages':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('No tienes permiso.', 'pn-customers-manager')]);
            exit;
          }
          $conv_id = !empty($_POST['conv_id']) ? absint($_POST['conv_id']) : 0;
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_Instagram_AI::ajax_get_conversation_messages($conv_id));
          exit;
        case 'pn_cm_wa_conversation_detail_html':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission']);
            exit;
          }
          $conv_id = !empty($_POST['conv_id']) ? absint($_POST['conv_id']) : 0;
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_WhatsApp_AI::ajax_get_conversation_detail_html($conv_id));
          exit;
        case 'pn_cm_ig_conversation_detail_html':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission']);
            exit;
          }
          $conv_id = !empty($_POST['conv_id']) ? absint($_POST['conv_id']) : 0;
          echo wp_json_encode(PN_CUSTOMERS_MANAGER_Instagram_AI::ajax_get_conversation_detail_html($conv_id));
          exit;
        case 'pn_cm_wa_conversation_action':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission']);
            exit;
          }
          $conv_id = !empty($_POST['conv_id']) ? absint($_POST['conv_id']) : 0;
          $conv_action = !empty($_POST['conv_action']) ? sanitize_text_field(wp_unslash($_POST['conv_action'])) : '';
          if ($conv_action === 'close' && $conv_id) {
            PN_CUSTOMERS_MANAGER_WhatsApp_AI::close_conversation_public($conv_id);
            echo wp_json_encode(['error_key' => '', 'message' => 'closed']);
          } elseif ($conv_action === 'delete' && $conv_id) {
            PN_CUSTOMERS_MANAGER_WhatsApp_AI::delete_conversation_public($conv_id);
            echo wp_json_encode(['error_key' => '', 'message' => 'deleted']);
          } elseif ($conv_action === 'reset' && $conv_id) {
            PN_CUSTOMERS_MANAGER_WhatsApp_AI::reset_conversation_public($conv_id);
            echo wp_json_encode(['error_key' => '', 'message' => 'reset']);
          } else {
            echo wp_json_encode(['error_key' => 'invalid_action']);
          }
          exit;
        case 'pn_cm_ig_conversation_action':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission']);
            exit;
          }
          $conv_id = !empty($_POST['conv_id']) ? absint($_POST['conv_id']) : 0;
          $conv_action = !empty($_POST['conv_action']) ? sanitize_text_field(wp_unslash($_POST['conv_action'])) : '';
          if ($conv_action === 'close' && $conv_id) {
            PN_CUSTOMERS_MANAGER_Instagram_AI::close_conversation_public($conv_id);
            echo wp_json_encode(['error_key' => '', 'message' => 'closed']);
          } elseif ($conv_action === 'delete' && $conv_id) {
            PN_CUSTOMERS_MANAGER_Instagram_AI::delete_conversation_public($conv_id);
            echo wp_json_encode(['error_key' => '', 'message' => 'deleted']);
          } elseif ($conv_action === 'reset' && $conv_id) {
            PN_CUSTOMERS_MANAGER_Instagram_AI::reset_conversation_public($conv_id);
            echo wp_json_encode(['error_key' => '', 'message' => 'reset']);
          } else {
            echo wp_json_encode(['error_key' => 'invalid_action']);
          }
          exit;
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
            $plugin_post_type_post->pn_customers_manager_duplicate_post($pn_cm_organization_id, 'publish');
            
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
        case 'pn_cm_create_plugin_page':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_create_plugin_page_error',
              'error_content' => esc_html(__('You do not have permission to create pages.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $page_title = !empty($_POST['page_title']) ? sanitize_text_field(wp_unslash($_POST['page_title'])) : '';
          $shortcode_name = !empty($_POST['shortcode']) ? sanitize_text_field(wp_unslash($_POST['shortcode'])) : '';
          $page_option = !empty($_POST['page_option']) ? sanitize_key(wp_unslash($_POST['page_option'])) : '';

          if (empty($page_title) || empty($shortcode_name) || empty($page_option)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_create_plugin_page_error',
              'error_content' => esc_html(__('Missing required fields.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $allowed_options = array_keys(PN_CUSTOMERS_MANAGER_Settings::pn_customers_manager_get_managed_pages());
          if (!in_array($page_option, $allowed_options, true)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_create_plugin_page_error',
              'error_content' => esc_html(__('Invalid page option.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $page_content = '[' . $shortcode_name . ']';

          $page_id = wp_insert_post([
            'post_title'   => $page_title,
            'post_content' => $page_content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
          ]);

          if (is_wp_error($page_id)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_create_plugin_page_error',
              'error_content' => esc_html($page_id->get_error_message()),
            ]);
            exit;
          }

          update_option($page_option, $page_id);

          echo wp_json_encode([
            'error_key'  => '',
            'page_id'    => $page_id,
            'page_title' => esc_html($page_title),
            'page_url'   => esc_url(get_permalink($page_id)),
            'edit_url'   => esc_url(get_edit_post_link($page_id, 'raw')),
          ]);
          exit;
          break;
        case 'pn_cm_unlink_plugin_page':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_unlink_plugin_page_error',
              'error_content' => esc_html(__('You do not have permission to manage pages.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $page_option = !empty($_POST['page_option']) ? sanitize_key(wp_unslash($_POST['page_option'])) : '';

          $allowed_options = array_keys(PN_CUSTOMERS_MANAGER_Settings::pn_customers_manager_get_managed_pages());
          if (empty($page_option) || !in_array($page_option, $allowed_options, true)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_unlink_plugin_page_error',
              'error_content' => esc_html(__('Invalid page option.', 'pn-customers-manager')),
            ]);
            exit;
          }

          delete_option($page_option);

          echo wp_json_encode([
            'error_key' => '',
          ]);
          exit;
          break;
        case 'pn_cm_referral_create':
          if (!is_user_logged_in()) {
            echo wp_json_encode(['error_key' => 'not_logged_in']);
            exit;
          }
          $email = sanitize_email(wp_unslash($_POST['referral_email'] ?? ''));
          $result = PN_CUSTOMERS_MANAGER_Referral::create_referral(get_current_user_id(), $email);
          echo wp_json_encode(!empty($result['error'])
            ? ['error_key' => $result['error']]
            : ['error_key' => '', 'referral_link' => $result['referral_link'], 'referral' => $result['referral']]);
          exit;
          break;
        case 'pn_cm_bizcard_user_data':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'access_denied']);
            exit;
          }
          $target_user_id = intval($_POST['target_user_id'] ?? 0);
          $target_user = get_userdata($target_user_id);
          if (!$target_user) {
            echo wp_json_encode(['error_key' => 'user_not_found']);
            exit;
          }
          $ref_code = PN_CUSTOMERS_MANAGER_Referral::get_or_create_referral_code($target_user_id);
          $ref_url = home_url('?pn_cm_qr_ref=' . $ref_code);
          $branding_url = PN_CUSTOMERS_MANAGER_Referral::get_qr_branding_url();
          echo wp_json_encode([
            'error_key' => '',
            'name' => $target_user->display_name,
            'email' => $target_user->user_email,
            'qr_url' => $ref_url,
            'qr_code' => $ref_code,
            'branding_url' => $branding_url ? $branding_url : '',
          ]);
          exit;
          break;
        case 'pn_cm_referral_save_share_text':
          if (!is_user_logged_in()) {
            echo wp_json_encode(['error_key' => 'not_logged_in']);
            exit;
          }
          $share_text = sanitize_textarea_field(wp_unslash($_POST['share_text'] ?? ''));
          update_user_meta(get_current_user_id(), 'pn_cm_referral_share_text', $share_text);
          echo wp_json_encode(['error_key' => '']);
          exit;
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
        case 'pn_customers_manager_contact_new':
          if (empty($pn_cm_organization_id)) {
            echo wp_json_encode([
              'error_key' => 'pn_customers_manager_contact_new_error',
              'error_content' => esc_html(__('Organization ID is required.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $existing_contacts = get_post_meta($pn_cm_organization_id, 'pn_cm_organization_contacts', true);
          if (empty($existing_contacts) || !is_array($existing_contacts)) {
            $existing_contacts = [];
          }
          $existing_contacts = array_map('intval', $existing_contacts);

          $user_args = [
            'fields'  => ['ID', 'display_name', 'user_email'],
            'orderby' => 'display_name',
            'order'   => 'ASC',
          ];
          if (!empty($existing_contacts)) {
            $user_args['exclude'] = $existing_contacts;
          }
          $available_users = get_users($user_args);

          ob_start();
          ?>
          <div class="pn-customers-manager-contact-add-form pn-customers-manager-p-30">
            <h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Add contact', 'pn-customers-manager'); ?></h4>

            <?php if (empty($available_users)): ?>
              <p><?php esc_html_e('No users available to link.', 'pn-customers-manager'); ?></p>
            <?php else: ?>
              <div class="pn-customers-manager-input-wrapper pn-customers-manager-mb-20">
                <label for="pn_cm_contact_user_id"><?php esc_html_e('Select a user', 'pn-customers-manager'); ?></label>
                <select id="pn_cm_contact_user_id" class="pn-customers-manager-select pn-customers-manager-width-100-percent">
                  <option value=""><?php esc_html_e('Select a user', 'pn-customers-manager'); ?></option>
                  <?php foreach ($available_users as $user): ?>
                    <?php
                      $label = '';
                      if (class_exists('PN_CUSTOMERS_MANAGER_Functions_User')) {
                        $label = PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_get_name($user->ID);
                      }
                      if (empty($label)) {
                        $label = !empty($user->display_name) ? $user->display_name : $user->user_email;
                      }
                    ?>
                    <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($label . ' (' . $user->user_email . ')'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="pn-customers-manager-text-align-right">
                <button type="button"
                        class="pn-customers-manager-btn pn-customers-manager-contact-add-submit"
                        data-org-id="<?php echo esc_attr($pn_cm_organization_id); ?>">
                  <?php esc_html_e('Link contact', 'pn-customers-manager'); ?>
                </button>
              </div>
            <?php endif; ?>
          </div>
          <?php
          $html = ob_get_clean();

          echo wp_json_encode([
            'error_key' => '',
            'html' => $html,
          ]);
          exit;
          break;

        case 'pn_customers_manager_contact_add':
          if (empty($pn_cm_organization_id)) {
            echo wp_json_encode([
              'error_key' => 'pn_customers_manager_contact_add_error',
              'error_content' => esc_html(__('Organization ID is required.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $contact_user_id = isset($_POST['pn_cm_contact_user_id']) ? absint($_POST['pn_cm_contact_user_id']) : 0;
          if (empty($contact_user_id) || !get_user_by('id', $contact_user_id)) {
            echo wp_json_encode([
              'error_key' => 'pn_customers_manager_contact_add_error',
              'error_content' => esc_html(__('Please select a valid user.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $contacts = get_post_meta($pn_cm_organization_id, 'pn_cm_organization_contacts', true);
          if (empty($contacts) || !is_array($contacts)) {
            $contacts = [];
          }
          $contacts = array_map('intval', $contacts);

          if (!in_array($contact_user_id, $contacts, true)) {
            $contacts[] = $contact_user_id;
            update_post_meta($pn_cm_organization_id, 'pn_cm_organization_contacts', $contacts);
          }

          $plugin_org = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
          echo wp_json_encode([
            'error_key' => '',
            'contacts_html' => PN_CUSTOMERS_MANAGER_Post_Type_organization::pn_customers_manager_render_contacts_list_public($pn_cm_organization_id),
          ]);
          exit;
          break;

        case 'pn_customers_manager_contact_remove':
          if (empty($pn_cm_organization_id)) {
            echo wp_json_encode([
              'error_key' => 'pn_customers_manager_contact_remove_error',
              'error_content' => esc_html(__('Organization ID is required.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $contact_user_id = isset($_POST['pn_cm_contact_user_id']) ? absint($_POST['pn_cm_contact_user_id']) : 0;
          if (empty($contact_user_id)) {
            echo wp_json_encode([
              'error_key' => 'pn_customers_manager_contact_remove_error',
              'error_content' => esc_html(__('Contact ID is required.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $contacts = get_post_meta($pn_cm_organization_id, 'pn_cm_organization_contacts', true);
          if (empty($contacts) || !is_array($contacts)) {
            $contacts = [];
          }
          $contacts = array_map('intval', $contacts);
          $contacts = array_values(array_diff($contacts, [$contact_user_id]));
          update_post_meta($pn_cm_organization_id, 'pn_cm_organization_contacts', $contacts);

          echo wp_json_encode([
            'error_key' => '',
            'contacts_html' => PN_CUSTOMERS_MANAGER_Post_Type_organization::pn_customers_manager_render_contacts_list_public($pn_cm_organization_id),
          ]);
          exit;
          break;

        case 'pn_cm_commercial_apply':
          if (!is_user_logged_in()) {
            echo wp_json_encode([
              'error_key' => 'not_logged_in',
              'error_content' => esc_html__('You must be logged in to send your application.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $result = PN_CUSTOMERS_MANAGER_Commercial::handle_commercial_application();
          echo wp_json_encode($result);
          exit;
          break;
        case 'pn_cm_commercial_approve':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_commercial_permission_error',
              'error_content' => esc_html__('You do not have permission to perform this action.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $commercial_user_id = isset($_POST['pn_cm_commercial_user_id']) ? absint($_POST['pn_cm_commercial_user_id']) : 0;
          $result = PN_CUSTOMERS_MANAGER_Commercial::approve_commercial($commercial_user_id);
          echo wp_json_encode($result);
          exit;
          break;
        case 'pn_cm_commercial_reject':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_commercial_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acción.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $commercial_user_id = isset($_POST['pn_cm_commercial_user_id']) ? absint($_POST['pn_cm_commercial_user_id']) : 0;
          $result = PN_CUSTOMERS_MANAGER_Commercial::reject_commercial($commercial_user_id);
          echo wp_json_encode($result);
          exit;
          break;
        case 'pn_cm_contact_mark_read':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acción.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $message_id = isset($_POST['message_id']) ? absint($_POST['message_id']) : 0;

          if (!empty($message_id)) {
            global $wpdb;
            $wpdb->update(
              $wpdb->prefix . 'pn_cm_contact_messages',
              ['is_read' => 1],
              ['id' => $message_id],
              ['%d'],
              ['%d']
            );

            echo wp_json_encode([
              'error_key'    => '',
              'unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
              'spam_count'   => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => 'pn_cm_contact_mark_read_error']);
          exit;
          break;
        case 'pn_cm_contact_delete':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acción.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $message_id = isset($_POST['message_id']) ? absint($_POST['message_id']) : 0;

          if (!empty($message_id)) {
            global $wpdb;
            $wpdb->delete(
              $wpdb->prefix . 'pn_cm_contact_messages',
              ['id' => $message_id],
              ['%d']
            );

            echo wp_json_encode([
              'error_key'    => '',
              'unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
              'spam_count'   => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => 'pn_cm_contact_delete_error']);
          exit;
          break;
        case 'pn_cm_contact_mark_spam':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acción.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $message_id = isset($_POST['message_id']) ? absint($_POST['message_id']) : 0;
          $unmark     = isset($_POST['unmark']) && (int) $_POST['unmark'] === 1;

          if (!empty($message_id)) {
            global $wpdb;
            $table = $wpdb->prefix . 'pn_cm_contact_messages';

            // Load existing row to submit ham/spam feedback to Akismet
            $existing = $wpdb->get_row(
              $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $message_id)
            );

            $wpdb->update(
              $table,
              ['is_spam' => $unmark ? 0 : 1],
              ['id' => $message_id],
              ['%d'],
              ['%d']
            );

            // Send feedback to Akismet if enabled, so its filters keep learning
            if ($existing && get_option('pn_customers_manager_akismet_enabled') === 'on' && class_exists('Akismet') && method_exists('Akismet', 'get_api_key') && Akismet::get_api_key()) {
              $params = [
                'blog'                 => home_url(),
                'user_ip'              => $existing->ip_address,
                'user_agent'           => '',
                'referrer'             => $existing->source_url,
                'permalink'            => $existing->source_url,
                'comment_type'         => 'contact-form',
                'comment_author'       => $existing->contact_name,
                'comment_author_email' => $existing->contact_email,
                'comment_content'      => $existing->contact_message,
                'blog_lang'            => get_locale(),
                'blog_charset'         => get_bloginfo('charset'),
              ];

              $endpoint = $unmark ? 'submit-ham' : 'submit-spam';
              try {
                Akismet::http_post(http_build_query($params), $endpoint);
              } catch (\Exception $e) {
                // Non-fatal: ignore feedback errors.
              }
            }

            echo wp_json_encode([
              'error_key'    => '',
              'unmark'       => $unmark ? 1 : 0,
              'unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
              'spam_count'   => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => 'pn_cm_contact_mark_spam_error']);
          exit;
          break;
        case 'pn_cm_contact_list':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acción.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $view  = isset($_POST['view']) ? sanitize_key(wp_unslash($_POST['view'])) : 'inbox';
          $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;

          echo wp_json_encode([
            'error_key'    => '',
            'view'         => in_array($view, ['inbox', 'spam'], true) ? $view : 'inbox',
            'paged'        => $paged,
            'html'         => PN_CUSTOMERS_MANAGER_Contact_Messages::render_messages_list($view, $paged),
            'tabs_html'    => PN_CUSTOMERS_MANAGER_Contact_Messages::render_tabs($view),
            'unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
            'spam_count'   => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
          ]);
          exit;
          break;
        case 'pn_cm_assign_role':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_assign_role_error',
              'error_content' => esc_html__('You do not have permission to manage user roles.', 'pn-customers-manager'),
            ]);
            exit;
          }

          // Verify role assignment nonce
          $role_nonce = isset($_POST['pn_customers_manager_role_nonce']) ? sanitize_text_field(wp_unslash($_POST['pn_customers_manager_role_nonce'])) : '';
          if (!wp_verify_nonce($role_nonce, 'pn-customers-manager-role-assignment')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_assign_role_error',
              'error_content' => esc_html__('Security check failed for role assignment.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $user_ids = isset($_POST['user_ids']) && is_array($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : [];
          $role = isset($_POST['role']) ? sanitize_text_field(wp_unslash($_POST['role'])) : '';
          $action_type = isset($_POST['action_type']) ? sanitize_text_field(wp_unslash($_POST['action_type'])) : 'assign';

          if (empty($user_ids)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_assign_role_error',
              'error_content' => esc_html__('No users selected.', 'pn-customers-manager'),
            ]);
            exit;
          }

          if (empty($role)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_assign_role_error',
              'error_content' => esc_html__('No role specified.', 'pn-customers-manager'),
            ]);
            exit;
          }

          // Validate role is one of the plugin roles
          $plugin_roles = [
            'pn_customers_manager_role_manager',
            'pn_customers_manager_role_client',
            'pn_customers_manager_role_commercial',
          ];

          if (!in_array($role, $plugin_roles)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_assign_role_error',
              'error_content' => esc_html__('Invalid role.', 'pn-customers-manager'),
            ]);
            exit;
          }

          // Ensure the role exists before trying to assign it
          $wp_roles = wp_roles();
          if (!$wp_roles->is_role($role)) {
            $role_labels = [
              'pn_customers_manager_role_manager' => __('PN Customers Manager', 'pn-customers-manager'),
              'pn_customers_manager_role_client' => __('Client - PN', 'pn-customers-manager'),
              'pn_customers_manager_role_commercial' => __('Comercial - PN', 'pn-customers-manager'),
            ];
            add_role($role, esc_html($role_labels[$role]), ['read' => true]);
          }

          $success_count = 0;

          foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) {
              continue;
            }

            $user_roles = (array) $user->roles;

            if ($action_type === 'assign') {
              if (!in_array($role, $user_roles)) {
                $user->add_role($role);
                $success_count++;
              }
            } elseif ($action_type === 'remove') {
              if (in_array($role, $user_roles)) {
                $user->remove_role($role);
                $success_count++;
              }
            }
          }

          if ($success_count > 0) {
            $message = $action_type === 'assign'
              ? sprintf(
                  /* translators: %d: number of users */
                  __('%d user(s) assigned the role successfully.', 'pn-customers-manager'),
                  $success_count
                )
              : sprintf(
                  /* translators: %d: number of users */
                  __('%d user(s) removed from the role successfully.', 'pn-customers-manager'),
                  $success_count
                );

            echo wp_json_encode([
              'error_key' => '',
              'success' => true,
              'message' => $message,
            ]);
          } else {
            echo wp_json_encode([
              'error_key' => 'pn_cm_assign_role_no_changes',
              'success' => false,
              'message' => esc_html__('No changes were made.', 'pn-customers-manager'),
            ]);
          }
          exit;
          break;
        case 'pn_cm_email_campaign_send':
          if (!current_user_can('manage_options') && !current_user_can('edit_pn_cm_funnel') && !current_user_can('edit_pn_cm_organization')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_email_campaign_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acción.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $result = PN_CUSTOMERS_MANAGER_Email_Campaigns::handle_send_campaign();
          echo wp_json_encode($result);
          exit;
          break;
        case 'pn_cm_email_campaign_progress':
          if (!current_user_can('manage_options') && !current_user_can('edit_pn_cm_funnel') && !current_user_can('edit_pn_cm_organization')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_email_campaign_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acción.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $result = PN_CUSTOMERS_MANAGER_Email_Campaigns::handle_get_progress();
          echo wp_json_encode($result);
          exit;
          break;
        case 'pn_cm_email_campaign_refresh':
          if (!current_user_can('manage_options') && !current_user_can('edit_pn_cm_funnel') && !current_user_can('edit_pn_cm_organization')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_email_campaign_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acción.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $result = PN_CUSTOMERS_MANAGER_Email_Campaigns::handle_refresh();
          echo wp_json_encode($result);
          exit;
          break;

        case 'pn_cm_projections_data':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Projections::ajax_get_projection_data();
          exit;
        case 'pn_cm_projection_create':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Projections::ajax_create_projection();
          exit;
        case 'pn_cm_projection_delete':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Projections::ajax_delete_projection();
          exit;
        case 'pn_cm_projections_social':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Projections::ajax_get_social_metrics();
          exit;
        case 'pn_cm_social_media_test':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Projections::ajax_test_social_media();
          exit;

        case 'pn_cm_mail_stats_period':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Mail_Stats::ajax_dashboard_period();
          exit;

        case 'pn_cm_settings_export':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode(['error_key' => 'permission_denied']);
            exit;
          }

          $settings  = new PN_CUSTOMERS_MANAGER_Settings();
          $options   = $settings->pn_customers_manager_get_options();
          $export    = [];

          foreach ($options as $key => $config) {
            if (!isset($config['input']) || in_array($config['input'], ['html_multi'])) continue;
            if (isset($config['type']) && in_array($config['type'], ['nonce', 'submit'])) continue;
            if (isset($config['section'])) continue;

            $value = get_option($key, '');
            if ($value !== '') {
              $export[$key] = $value;
            }
          }

          echo wp_json_encode(['error_key' => '', 'settings' => $export]);
          exit;
          break;

        case 'pn_cm_settings_import':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode(['error_key' => 'permission_denied']);
            exit;
          }

          $raw = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : '';
          $import = json_decode($raw, true);

          if (!is_array($import) || empty($import)) {
            echo wp_json_encode(['error_key' => 'invalid_data', 'error_content' => 'Invalid settings data.']);
            exit;
          }

          $settings  = new PN_CUSTOMERS_MANAGER_Settings();
          $options   = $settings->pn_customers_manager_get_options();
          $allowed   = array_keys($options);
          $count     = 0;

          foreach ($import as $key => $value) {
            if (in_array($key, $allowed)) {
              update_option($key, sanitize_text_field($value));
              $count++;
            }
          }

          echo wp_json_encode(['error_key' => '', 'count' => $count]);
          exit;
          break;

        case 'pn_cm_install_plugin':
          if (!current_user_can('install_plugins')) {
            echo wp_json_encode(['error_key' => 'permission_denied']);
            exit;
          }

          $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
          $allowed_slugs = ['mailpn', 'userspn', 'pn-tasks-manager', 'pn-cookies-manager'];

          if (!in_array($slug, $allowed_slugs, true)) {
            echo wp_json_encode(['error_key' => 'invalid_slug']);
            exit;
          }

          include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
          include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
          include_once ABSPATH . 'wp-admin/includes/plugin.php';

          $api = plugins_api('plugin_information', [
            'slug'   => $slug,
            'fields' => ['sections' => false],
          ]);

          if (is_wp_error($api)) {
            echo wp_json_encode(['error_key' => 'api_error', 'error_content' => $api->get_error_message()]);
            exit;
          }

          $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
          $result   = $upgrader->install($api->download_link);

          if (is_wp_error($result)) {
            echo wp_json_encode(['error_key' => 'install_error', 'error_content' => $result->get_error_message()]);
            exit;
          }

          if ($result === false) {
            echo wp_json_encode(['error_key' => 'install_failed', 'error_content' => 'Installation failed.']);
            exit;
          }

          echo wp_json_encode(['error_key' => '']);
          exit;
          break;

        case 'pn_cm_activate_plugin':
          if (!current_user_can('activate_plugins')) {
            echo wp_json_encode(['error_key' => 'permission_denied']);
            exit;
          }

          $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
          $plugin_files = [
            'mailpn'             => 'mailpn/mailpn.php',
            'userspn'            => 'userspn/userspn.php',
            'pn-tasks-manager'   => 'pn-tasks-manager/pn-tasks-manager.php',
            'pn-cookies-manager' => 'pn-cookies-manager/pn-cookies-manager.php',
          ];

          if (!isset($plugin_files[$slug])) {
            echo wp_json_encode(['error_key' => 'invalid_slug']);
            exit;
          }

          $plugin_file = $plugin_files[$slug];
          $result = activate_plugin($plugin_file);

          if (is_wp_error($result)) {
            echo wp_json_encode(['error_key' => 'activate_error', 'error_content' => $result->get_error_message()]);
            exit;
          }

          echo wp_json_encode(['error_key' => '']);
          exit;
          break;

        // ── Budget AJAX cases ──
        case 'pn_cm_budget_view':
          $pn_cm_budget_id = !empty($_POST['pn_cm_budget_id']) ? intval($_POST['pn_cm_budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            $plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            echo wp_json_encode([
              'error_key' => '',
              'html' => $plugin_post_type_budget->pn_cm_budget_view($pn_cm_budget_id),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_budget_view_error', 'error_content' => esc_html(__('An error occurred while showing the Budget.', 'pn-customers-manager'))]);
          exit;
          break;

        case 'pn_cm_budget_edit':
          $pn_cm_budget_id = !empty($_POST['pn_cm_budget_id']) ? intval($_POST['pn_cm_budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            $plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            echo wp_json_encode([
              'error_key' => '',
              'html' => $plugin_post_type_budget->pn_cm_budget_edit($pn_cm_budget_id),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_budget_edit_error', 'error_content' => esc_html(__('An error occurred while showing the Budget.', 'pn-customers-manager'))]);
          exit;
          break;

        case 'pn_cm_budget_new':
          if (!is_user_logged_in()) {
            echo wp_json_encode(['error_key' => 'not_logged_in', 'error_content' => esc_html(__('You must be logged in to create a new asset.', 'pn-customers-manager'))]);
            exit;
          }
          $plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
          echo wp_json_encode([
            'error_key' => '',
            'html' => $plugin_post_type_budget->pn_cm_budget_new(),
          ]);
          exit;
          break;

        case 'pn_cm_budget_remove':
          $pn_cm_budget_id = !empty($_POST['pn_cm_budget_id']) ? intval($_POST['pn_cm_budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            wp_delete_post($pn_cm_budget_id, true);
            // Also delete items
            global $wpdb;
            $wpdb->delete($wpdb->prefix . 'pn_cm_budget_items', ['budget_id' => $pn_cm_budget_id], ['%d']);

            $plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            echo wp_json_encode([
              'error_key' => '',
              'html' => $plugin_post_type_budget->pn_cm_budget_list(),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_budget_remove_error', 'error_content' => esc_html(__('An error occurred while removing the Budget.', 'pn-customers-manager'))]);
          exit;
          break;

        case 'pn_cm_budget_duplicate':
          $pn_cm_budget_id = !empty($_POST['pn_cm_budget_id']) ? intval($_POST['pn_cm_budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            $original = get_post($pn_cm_budget_id);
            if ($original) {
              $new_id = wp_insert_post([
                'post_type' => 'pn_cm_budget',
                'post_title' => $original->post_title . ' (' . __('Copy', 'pn-customers-manager') . ')',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
              ]);

              if ($new_id && !is_wp_error($new_id)) {
                // Copy meta
                $meta_keys = ['pn_cm_budget_organization_id', 'pn_cm_budget_tax_rate', 'pn_cm_budget_discount_rate', 'pn_cm_budget_notes', 'pn_cm_budget_client_notes'];
                foreach ($meta_keys as $mk) {
                  $val = get_post_meta($pn_cm_budget_id, $mk, true);
                  if ($val !== '') update_post_meta($new_id, $mk, $val);
                }

                // Generate new number, date, token
                $budget_class = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
                update_post_meta($new_id, 'pn_cm_budget_number', $budget_class->pn_cm_budget_generate_number());
                update_post_meta($new_id, 'pn_cm_budget_date', current_time('Y-m-d'));
                $validity = intval(get_option('pn_customers_manager_budget_default_validity_days', 30));
                update_post_meta($new_id, 'pn_cm_budget_valid_until', date('Y-m-d', strtotime('+' . $validity . ' days')));
                update_post_meta($new_id, 'pn_cm_budget_status', 'draft');
                update_post_meta($new_id, 'pn_cm_budget_token', $budget_class->pn_cm_budget_generate_token());

                // Copy items
                global $wpdb;
                $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pn_cm_budget_items WHERE budget_id = %d ORDER BY sort_order ASC", $pn_cm_budget_id));
                foreach ($items as $item) {
                  $wpdb->insert($wpdb->prefix . 'pn_cm_budget_items', [
                    'budget_id' => $new_id,
                    'item_type' => $item->item_type,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                    'is_optional' => $item->is_optional,
                    'is_selected' => $item->is_selected,
                    'sort_order' => $item->sort_order,
                  ]);
                }

                $budget_class->pn_cm_budget_recalculate_totals($new_id);
              }
            }

            $plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            echo wp_json_encode([
              'error_key' => '',
              'html' => $plugin_post_type_budget->pn_cm_budget_list(),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_budget_duplicate_error']);
          exit;
          break;

        case 'pn_cm_budget_add_item':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            global $wpdb;
            $item_type = sanitize_text_field(wp_unslash($_POST['item_type'] ?? 'fixed'));
            $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
            $quantity = floatval($_POST['quantity'] ?? 1);
            $unit_price = floatval($_POST['unit_price'] ?? 0);
            $is_optional = intval($_POST['is_optional'] ?? 0);
            $total = $quantity * $unit_price;

            $max_order = $wpdb->get_var($wpdb->prepare("SELECT MAX(sort_order) FROM {$wpdb->prefix}pn_cm_budget_items WHERE budget_id = %d", $pn_cm_budget_id));
            $sort_order = intval($max_order) + 1;

            $wpdb->insert($wpdb->prefix . 'pn_cm_budget_items', [
              'budget_id' => $pn_cm_budget_id,
              'item_type' => $item_type,
              'description' => $description,
              'quantity' => $quantity,
              'unit_price' => $unit_price,
              'total' => $total,
              'is_optional' => $is_optional,
              'is_selected' => 1,
              'sort_order' => $sort_order,
            ], ['%d', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%d']);

            $item_id = $wpdb->insert_id;

            $budget_class = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            $budget_class->pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            echo wp_json_encode([
              'error_key' => '',
              'item' => [
                'id' => $item_id,
                'item_type' => $item_type,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'total' => $total,
                'is_optional' => $is_optional,
                'is_selected' => 1,
              ],
              'totals' => [
                'subtotal' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_budget_add_item_error']);
          exit;
          break;

        case 'pn_cm_budget_remove_item':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_budget_id) && !empty($item_id)) {
            global $wpdb;
            $wpdb->delete($wpdb->prefix . 'pn_cm_budget_items', ['id' => $item_id, 'budget_id' => $pn_cm_budget_id], ['%d', '%d']);

            $budget_class = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            $budget_class->pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            echo wp_json_encode([
              'error_key' => '',
              'totals' => [
                'subtotal' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_budget_remove_item_error']);
          exit;
          break;

        case 'pn_cm_budget_reorder_items':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $order = !empty($_POST['order']) ? json_decode(sanitize_text_field(wp_unslash($_POST['order'])), true) : [];
          if (!empty($pn_cm_budget_id) && !empty($order)) {
            global $wpdb;
            foreach ($order as $item) {
              $wpdb->update(
                $wpdb->prefix . 'pn_cm_budget_items',
                ['sort_order' => intval($item['position'])],
                ['id' => intval($item['id']), 'budget_id' => $pn_cm_budget_id],
                ['%d'],
                ['%d', '%d']
              );
            }
            echo wp_json_encode(['error_key' => '']);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_budget_reorder_error']);
          exit;
          break;

        case 'pn_cm_budget_update_item':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_budget_id) && !empty($item_id)) {
            global $wpdb;
            $item_type   = sanitize_text_field(wp_unslash($_POST['item_type'] ?? 'fixed'));
            $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
            $quantity    = floatval($_POST['quantity'] ?? 0);
            $unit_price  = floatval($_POST['unit_price'] ?? 0);
            $is_optional = intval($_POST['is_optional'] ?? 0);
            $total       = $quantity * $unit_price;

            $wpdb->update(
              $wpdb->prefix . 'pn_cm_budget_items',
              [
                'item_type'   => $item_type,
                'description' => $description,
                'quantity'    => $quantity,
                'unit_price'  => $unit_price,
                'total'       => $total,
                'is_optional' => $is_optional,
              ],
              ['id' => $item_id, 'budget_id' => $pn_cm_budget_id],
              ['%s', '%s', '%f', '%f', '%f', '%d'],
              ['%d', '%d']
            );

            $budget_class = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            $budget_class->pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            wp_send_json([
              'error_key' => '',
              'item' => [
                'id'         => $item_id,
                'item_type'  => $item_type,
                'description'=> $description,
                'quantity'   => $quantity,
                'unit_price' => $unit_price,
                'total'      => $total,
                'is_optional'=> $is_optional,
              ],
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_update_item_error']);
          break;

        case 'pn_cm_budget_edit_item_form':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_budget_edit_form_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_budget_id) && !empty($item_id)) {
            wp_send_json([
              'error_key' => '',
              'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_render_item_edit_form($item_id, $pn_cm_budget_id),
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_edit_form_error']);
          break;

        case 'pn_cm_budget_send':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            update_post_meta($pn_cm_budget_id, 'pn_cm_budget_status', 'sent');
            echo wp_json_encode(['error_key' => '', 'status' => 'sent']);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_budget_send_error']);
          exit;
          break;
      }

      echo wp_json_encode([
        'error_key' => 'pn_customers_manager_save_error',
      ]);

      exit;
    }
  }
}