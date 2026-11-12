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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
            $pn_cm_funnel_title = get_the_title($pn_cm_funnel_id);
            PN_CUSTOMERS_MANAGER_Activity_Log::log('delete', 'funnel', $pn_cm_funnel_id, $pn_cm_funnel_title, 'Deleted Funnel #' . $pn_cm_funnel_id);
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
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is already escaped internally via wp_kses and esc_html; contains form elements that wp_kses_post would strip
          echo PN_CUSTOMERS_MANAGER_Funnel_Builder::render_settings_popup_fields($node_subtype);
          exit;
        case 'pn_cm_funnel_builder_save':
          PN_CUSTOMERS_MANAGER_Funnel_Builder::ajax_save_canvas($pn_cm_funnel_id);
          break;
        case 'pn_cm_funnel_builder_load':
          PN_CUSTOMERS_MANAGER_Funnel_Builder::ajax_load_canvas($pn_cm_funnel_id);
          break;
        case 'pn_cm_funnel_builder_create':
          PN_CUSTOMERS_MANAGER_Funnel_Builder::ajax_create_funnel();
          break;
        case 'pn_cm_funnel_builder_delete':
          PN_CUSTOMERS_MANAGER_Funnel_Builder::ajax_delete_funnel();
          break;
        case 'pn_cm_funnel_builder_rename':
          PN_CUSTOMERS_MANAGER_Funnel_Builder::ajax_rename_funnel();
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
            PN_CUSTOMERS_MANAGER_Activity_Log::log('create', 'organization', $pn_cm_organization_id, get_the_title($pn_cm_organization_id), 'Duplicated Organization #' . $pn_cm_organization_id);
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

          $managed = PN_CUSTOMERS_MANAGER_Settings::pn_customers_manager_get_managed_pages();
          $block   = isset( $managed[ $page_option ]['block'] ) ? $managed[ $page_option ]['block'] : null;
          $page_content = $block
            ? '<!-- wp:' . $block . ' /-->'
            : '[' . $shortcode_name . ']';

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
            PN_CUSTOMERS_MANAGER_Activity_Log::log('delete', 'organization', $pn_cm_organization_id, get_the_title($pn_cm_organization_id), 'Deleted Organization #' . $pn_cm_organization_id);
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
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
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
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
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
              'error_key'          => '',
              'unread_count'       => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
              'spam_count'         => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
              'forms_unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_forms_unread_count(),
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
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
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
              'error_key'          => '',
              'unread_count'       => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
              'spam_count'         => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
              'forms_unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_forms_unread_count(),
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
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
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
              'error_key'          => '',
              'unmark'             => $unmark ? 1 : 0,
              'unread_count'       => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
              'spam_count'         => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
              'forms_unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_forms_unread_count(),
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => 'pn_cm_contact_mark_spam_error']);
          exit;
          break;
        case 'pn_cm_form_mark_read':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $message_id = isset($_POST['message_id']) ? absint($_POST['message_id']) : 0;

          if (!empty($message_id)) {
            global $wpdb;
            $wpdb->update(
              $wpdb->prefix . 'pn_cm_funnel_form_submissions',
              ['is_read' => 1],
              ['id' => $message_id],
              ['%d'],
              ['%d']
            );

            echo wp_json_encode([
              'error_key'          => '',
              'unread_count'       => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
              'spam_count'         => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
              'forms_unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_forms_unread_count(),
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => 'pn_cm_form_mark_read_error']);
          exit;
          break;
        case 'pn_cm_form_delete':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $message_id = isset($_POST['message_id']) ? absint($_POST['message_id']) : 0;

          if (!empty($message_id)) {
            global $wpdb;
            $wpdb->delete(
              $wpdb->prefix . 'pn_cm_funnel_form_submissions',
              ['id' => $message_id],
              ['%d']
            );

            echo wp_json_encode([
              'error_key'          => '',
              'unread_count'       => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
              'spam_count'         => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
              'forms_unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_forms_unread_count(),
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => 'pn_cm_form_delete_error']);
          exit;
          break;
        case 'pn_cm_contact_list':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode([
              'error_key'     => 'pn_cm_contact_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $view  = isset($_POST['view']) ? sanitize_key(wp_unslash($_POST['view'])) : 'inbox';
          $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;

          $normalized_view = in_array($view, ['inbox', 'spam', 'forms'], true) ? $view : 'inbox';

          if ($normalized_view === 'forms') {
            $html = PN_CUSTOMERS_MANAGER_Contact_Messages::render_forms_list($normalized_view, $paged);
          } else {
            $html = PN_CUSTOMERS_MANAGER_Contact_Messages::render_messages_list($normalized_view, $paged);
          }

          echo wp_json_encode([
            'error_key'          => '',
            'view'               => $normalized_view,
            'paged'              => $paged,
            'html'               => $html,
            'tabs_html'          => PN_CUSTOMERS_MANAGER_Contact_Messages::render_tabs($normalized_view),
            'unread_count'       => PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count(),
            'spam_count'         => PN_CUSTOMERS_MANAGER_Contact_Messages::get_spam_count(),
            'forms_unread_count' => PN_CUSTOMERS_MANAGER_Contact_Messages::get_forms_unread_count(),
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
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
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
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
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
              'error_content' => esc_html__('No tienes permiso para realizar esta acciÃ³n.', 'pn-customers-manager'),
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

        // ── Go High Level ──
        case 'pn_cm_ghl_test':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_GHL::ajax_test();
          exit;
        case 'pn_cm_ghl_push_all':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_GHL::ajax_push_all();
          exit;
        case 'pn_cm_ghl_pull_all':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_GHL::ajax_pull_all();
          exit;
        case 'pn_cm_ghl_get_pipeline_stages':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_GHL::ajax_get_pipeline_stages();
          exit;

        // ── Zoho CRM ──
        case 'pn_cm_zoho_disconnect':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Zoho::disconnect();
          echo wp_json_encode(['error_key' => '', 'message' => esc_html__('Zoho disconnected.', 'pn-customers-manager')]);
          exit;
        case 'pn_cm_zoho_test':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Zoho::ajax_test();
          exit;
        case 'pn_cm_zoho_push_all':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Zoho::ajax_push_all();
          exit;
        case 'pn_cm_zoho_pull_all':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Zoho::ajax_pull_all();
          exit;
        case 'pn_cm_zoho_get_deal_stages':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Zoho::ajax_get_deal_stages();
          exit;

        // ── Clients Dashboard ──
        case 'pn_cm_client_get_list':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_get_clients();
          exit;
        case 'pn_cm_client_update_stage':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_update_stage();
          exit;
        case 'pn_cm_client_add':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_add_client();
          exit;
        case 'pn_cm_client_get_detail':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_get_detail();
          exit;

        case 'pn_cm_client_get_edit_form':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_get_edit_form();
          exit;

        case 'pn_cm_client_save':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_save_client();
          exit;

        case 'pn_cm_client_add_task':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_add_task();
          exit;

        case 'pn_cm_client_add_appointment':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_add_appointment();
          exit;

        case 'pn_cm_client_complete_task':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_complete_task();
          exit;

        case 'pn_cm_client_get_messages':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_get_conversation_messages();
          exit;

        case 'pn_cm_client_get_duplicates':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_get_duplicates();
          exit;

        case 'pn_cm_client_merge_duplicates':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Clients::ajax_merge_duplicates();
          exit;

        case 'pn_cm_db_update':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-activator.php';
          PN_CUSTOMERS_MANAGER_Activator::pn_customers_manager_create_tables();
          echo wp_json_encode(['error_key' => '', 'version' => PN_CUSTOMERS_MANAGER_DB_VERSION]);
          exit;

        case 'pn_cm_activity_log_list':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission']);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Activity_Log::ajax_get_logs();
          exit;

        case 'pn_cm_mail_stats_period':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            echo wp_json_encode(['error_key' => 'no_permission', 'error_content' => esc_html__('You do not have permission.', 'pn-customers-manager')]);
            exit;
          }
          PN_CUSTOMERS_MANAGER_Mail_Stats::ajax_dashboard_period();
          exit;

        case 'pn_cm_hide_banner':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode(['error_key' => 'permission_denied']);
            exit;
          }
          update_option('pn_customers_manager_show_banner', '');
          echo wp_json_encode(['error_key' => '']);
          exit;
          break;

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

        // â”€â”€ Budget AJAX cases â”€â”€
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
            PN_CUSTOMERS_MANAGER_Activity_Log::log('delete', 'budget', $pn_cm_budget_id, get_the_title($pn_cm_budget_id), 'Deleted Budget #' . $pn_cm_budget_id);
            wp_delete_post($pn_cm_budget_id, true);

            $plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            wp_send_json([
              'error_key' => '',
              'html' => $plugin_post_type_budget->pn_cm_budget_list(),
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_remove_error', 'error_content' => esc_html(__('An error occurred while removing the Budget.', 'pn-customers-manager'))]);
          break;

        case 'pn_cm_budget_duplicate':
          $pn_cm_budget_id = !empty($_POST['pn_cm_budget_id']) ? intval($_POST['pn_cm_budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            PN_CUSTOMERS_MANAGER_Activity_Log::log('create', 'budget', $pn_cm_budget_id, get_the_title($pn_cm_budget_id), 'Duplicated Budget #' . $pn_cm_budget_id);
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
                update_post_meta($new_id, 'pn_cm_budget_number', PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_generate_number());
                update_post_meta($new_id, 'pn_cm_budget_date', current_time('Y-m-d'));
                $validity = intval(get_option('pn_customers_manager_budget_default_validity_days', 30));
                update_post_meta($new_id, 'pn_cm_budget_valid_until', gmdate('Y-m-d', strtotime('+' . $validity . ' days')));
                update_post_meta($new_id, 'pn_cm_budget_status', 'draft');
                update_post_meta($new_id, 'pn_cm_budget_token', PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_generate_token());

                // Copy items from post meta â€” reassign IDs
                $src_items = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_items($pn_cm_budget_id);
                $new_items = [];
                $next_id   = 1;
                foreach ($src_items as $si) {
                  $si['id'] = $next_id++;
                  $new_items[] = $si;
                }
                PN_CUSTOMERS_MANAGER_Post_Type_Budget::save_budget_items($new_id, $new_items);
                update_post_meta($new_id, 'pn_cm_budget_next_item_id', $next_id);

                PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($new_id);
              }
            }

            $plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
            wp_send_json([
              'error_key' => '',
              'html' => $plugin_post_type_budget->pn_cm_budget_list(),
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_duplicate_error']);
          break;

        case 'pn_cm_budget_add_item':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            $item_type   = sanitize_text_field(wp_unslash($_POST['item_type'] ?? 'fixed'));
            $description = wp_kses_post(wp_unslash($_POST['description'] ?? ''));
            $quantity    = floatval($_POST['quantity'] ?? 1);
            $unit_price  = floatval($_POST['unit_price'] ?? 0);
            $is_optional      = intval($_POST['is_optional'] ?? 0);
            $is_recurring     = intval($_POST['is_recurring'] ?? 0);
            $recurring_period = sanitize_text_field(wp_unslash($_POST['recurring_period'] ?? ''));
            $alt_of           = intval($_POST['alt_of'] ?? 0);
            $total            = round($quantity * $unit_price, 2);

            $item_data = [
              'item_type'        => $item_type,
              'description'      => $description,
              'quantity'         => $quantity,
              'unit_price'       => $unit_price,
              'is_optional'      => $is_optional,
              'is_selected'      => 1,
              'is_recurring'     => $is_recurring,
              'recurring_period' => $recurring_period,
              'alt_of'           => $alt_of,
            ];

            // If creating an alternative, place it right after the original item.
            if ($alt_of > 0) {
              $all_items = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_items($pn_cm_budget_id);
              foreach ($all_items as $ai) {
                if (intval($ai['id']) === $alt_of) {
                  $item_data['sort_order'] = intval($ai['sort_order']) + 1;
                  break;
                }
              }
            }

            $item_id = PN_CUSTOMERS_MANAGER_Post_Type_Budget::add_budget_item($pn_cm_budget_id, $item_data);

            PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            wp_send_json([
              'error_key' => '',
              'item' => [
                'id'               => $item_id,
                'item_type'        => $item_type,
                'description'      => $description,
                'quantity'         => $quantity,
                'unit_price'       => $unit_price,
                'total'            => $total,
                'is_optional'      => $is_optional,
                'is_selected'      => 1,
                'is_recurring'     => $is_recurring,
                'recurring_period' => $recurring_period,
                'alt_of'           => $alt_of,
                'alt_active'       => 'original',
              ],
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_add_item_error']);
          break;

        case 'pn_cm_budget_add_item_form':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_budget_add_form_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            wp_send_json([
              'error_key' => '',
              'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_render_item_add_form($pn_cm_budget_id),
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_add_form_error']);
          break;

        case 'pn_cm_budget_remove_item':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_budget_id) && !empty($item_id)) {
            PN_CUSTOMERS_MANAGER_Activity_Log::log('delete', 'budget', $pn_cm_budget_id, get_the_title($pn_cm_budget_id), 'Deleted item #' . $item_id . ' from Budget #' . $pn_cm_budget_id);
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::delete_budget_item($pn_cm_budget_id, $item_id);
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            wp_send_json([
              'error_key' => '',
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_remove_item_error']);
          break;

        case 'pn_cm_budget_bulk_remove_items':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_budget_bulk_remove_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_ids = !empty($_POST['item_ids']) ? json_decode(stripslashes($_POST['item_ids']), true) : [];
          if (!empty($pn_cm_budget_id) && !empty($item_ids) && is_array($item_ids)) {
            foreach ($item_ids as $iid) {
              PN_CUSTOMERS_MANAGER_Post_Type_Budget::delete_budget_item($pn_cm_budget_id, intval($iid));
            }
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            wp_send_json([
              'error_key' => '',
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_bulk_remove_error']);
          break;

        case 'pn_cm_budget_bulk_update_items':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_budget_bulk_update_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_ids = !empty($_POST['item_ids']) ? json_decode(stripslashes($_POST['item_ids']), true) : [];
          $field = !empty($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
          $value = intval($_POST['value'] ?? 0);
          if (!empty($pn_cm_budget_id) && !empty($item_ids) && is_array($item_ids) && in_array($field, ['is_optional', 'is_recurring'], true)) {
            foreach ($item_ids as $iid) {
              PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_budget_item($pn_cm_budget_id, intval($iid), [$field => $value ? 1 : 0]);
            }
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);
            wp_send_json([
              'error_key' => '',
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_bulk_update_error']);
          break;

        case 'pn_cm_budget_toggle_alternative':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_budget_id) && !empty($item_id)) {
            $item = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_item($pn_cm_budget_id, $item_id);
            if ($item) {
              $current = !empty($item['alt_active']) ? $item['alt_active'] : 'original';
              $new_state = ($current === 'original') ? 'alternative' : 'original';
              PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_budget_item($pn_cm_budget_id, $item_id, ['alt_active' => $new_state]);
              PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);

              wp_send_json([
                'error_key'  => '',
                'alt_active' => $new_state,
                'totals' => [
                  'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                  'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                  'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                  'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
                ],
              ]);
            }
          }
          wp_send_json(['error_key' => 'pn_cm_budget_toggle_alt_error']);
          break;

        case 'pn_cm_budget_reorder_items':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $order = !empty($_POST['order']) ? json_decode(sanitize_text_field(wp_unslash($_POST['order'])), true) : [];
          if (!empty($pn_cm_budget_id) && !empty($order)) {
            $items = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_items($pn_cm_budget_id);
            $order_map = [];
            foreach ($order as $o) {
              $order_map[ intval($o['id']) ] = intval($o['position']);
            }
            foreach ($items as &$item) {
              if (isset($order_map[ intval($item['id']) ])) {
                $item['sort_order'] = $order_map[ intval($item['id']) ];
              }
            }
            unset($item);
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::save_budget_items($pn_cm_budget_id, $items);
            wp_send_json(['error_key' => '']);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_reorder_error']);
          break;

        case 'pn_cm_budget_toggle_item':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_id         = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          $is_selected     = intval($_POST['is_selected'] ?? 1);
          if (!empty($pn_cm_budget_id) && !empty($item_id)) {
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_budget_item($pn_cm_budget_id, $item_id, ['is_selected' => $is_selected ? 1 : 0]);
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            wp_send_json([
              'error_key' => '',
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_toggle_item_error']);
          break;

        case 'pn_cm_budget_toggle_phase_items':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_ids = !empty($_POST['item_ids']) ? array_map('intval', (array)$_POST['item_ids']) : [];
          $selected = intval($_POST['is_selected'] ?? 1);
          if (!empty($pn_cm_budget_id) && !empty($item_ids)) {
            foreach ($item_ids as $iid) {
              PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_budget_item($pn_cm_budget_id, $iid, ['is_selected' => $selected ? 1 : 0]);
            }
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            wp_send_json([
              'error_key' => '',
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_toggle_phase_error']);
          break;

        case 'pn_cm_budget_update_item':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_budget_id) && !empty($item_id)) {
            $item_type   = sanitize_text_field(wp_unslash($_POST['item_type'] ?? 'fixed'));
            $description = wp_kses_post(wp_unslash($_POST['description'] ?? ''));
            $quantity    = floatval($_POST['quantity'] ?? 0);
            $unit_price  = floatval($_POST['unit_price'] ?? 0);
            $is_optional      = intval($_POST['is_optional'] ?? 0);
            $is_recurring     = intval($_POST['is_recurring'] ?? 0);
            $recurring_period = sanitize_text_field(wp_unslash($_POST['recurring_period'] ?? ''));
            $total            = round($quantity * $unit_price, 2);

            PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_budget_item($pn_cm_budget_id, $item_id, [
              'item_type'        => $item_type,
              'description'      => $description,
              'quantity'         => $quantity,
              'unit_price'       => $unit_price,
              'is_optional'      => $is_optional,
              'is_recurring'     => $is_recurring,
              'recurring_period' => $recurring_period,
            ]);
            PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);

            wp_send_json([
              'error_key' => '',
              'item' => [
                'id'               => $item_id,
                'item_type'        => $item_type,
                'description'      => $description,
                'quantity'         => $quantity,
                'unit_price'       => $unit_price,
                'total'            => $total,
                'is_optional'      => $is_optional,
                'is_recurring'     => $is_recurring,
                'recurring_period' => $recurring_period,
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

        case 'pn_cm_budget_update_field':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            wp_send_json(['error_key' => 'pn_cm_budget_update_field_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          $field_key = !empty($_POST['field_key']) ? sanitize_text_field(wp_unslash($_POST['field_key'])) : '';
          $field_value = isset($_POST['field_value']) ? wp_kses_post(wp_unslash($_POST['field_value'])) : '';

          $allowed_fields = ['pn_cm_budget_client_notes', 'pn_cm_budget_conditions', 'pn_cm_budget_others'];
          $allowed_status_values = ['draft', 'sent', 'accepted', 'rejected'];

          if (!empty($pn_cm_budget_id) && $field_key === 'pn_cm_budget_status') {
            $status_value = sanitize_text_field(wp_unslash($_POST['field_value']));
            if (in_array($status_value, $allowed_status_values, true)) {
              update_post_meta($pn_cm_budget_id, 'pn_cm_budget_status', $status_value);
              wp_send_json(['error_key' => '', 'value' => $status_value]);
            }
          }

          if (!empty($pn_cm_budget_id) && in_array($field_key, $allowed_fields, true)) {
            update_post_meta($pn_cm_budget_id, $field_key, $field_value);
            wp_send_json(['error_key' => '', 'value' => $field_value]);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_update_field_error']);
          break;

        case 'pn_cm_budget_send':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          if (!empty($pn_cm_budget_id)) {
            update_post_meta($pn_cm_budget_id, 'pn_cm_budget_status', 'sent');
            PN_CUSTOMERS_MANAGER_Activity_Log::log('status_change', 'budget', $pn_cm_budget_id, get_the_title($pn_cm_budget_id), 'Budget status changed to sent');
            wp_send_json(['error_key' => '', 'status' => 'sent']);
          }
          wp_send_json(['error_key' => 'pn_cm_budget_send_error']);
          break;

        case 'pn_cm_budget_generate_invoice':
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          if (empty($pn_cm_budget_id)) {
            wp_send_json(['error_key' => 'pn_cm_budget_generate_invoice_error', 'error_content' => esc_html(__('Budget ID is required.', 'pn-customers-manager'))]);
          }
          PN_CUSTOMERS_MANAGER_Activity_Log::log('create', 'invoice', $pn_cm_budget_id, get_the_title($pn_cm_budget_id), 'Generated Invoice from Budget #' . $pn_cm_budget_id);

          // Read budget data
          $budget_items = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_items($pn_cm_budget_id);
          $budget_org_id = get_post_meta($pn_cm_budget_id, 'pn_cm_budget_organization_id', true);
          $budget_tax_rate = get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_rate', true);
          $budget_discount_rate = get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_rate', true);
          $budget_client_notes = get_post_meta($pn_cm_budget_id, 'pn_cm_budget_client_notes', true);
          $budget_title = get_the_title($pn_cm_budget_id);

          // Create invoice post
          $post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();
          $invoice_id = $post_functions->pn_customers_manager_insert_post(
            esc_html($budget_title),
            '',
            '',
            sanitize_title(esc_html($budget_title)),
            'pn_cm_invoice',
            'publish',
            get_current_user_id()
          );

          if (empty($invoice_id) || is_wp_error($invoice_id)) {
            wp_send_json(['error_key' => 'pn_cm_budget_generate_invoice_error', 'error_content' => esc_html(__('Failed to create invoice.', 'pn-customers-manager'))]);
          }

          // Generate number and token
          $invoice_number = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_generate_number();
          update_post_meta($invoice_id, 'pn_cm_invoice_number', $invoice_number);

          $token = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_generate_token();
          update_post_meta($invoice_id, 'pn_cm_invoice_token', $token);

          // Copy meta from budget
          update_post_meta($invoice_id, 'pn_cm_invoice_organization_id', $budget_org_id);
          update_post_meta($invoice_id, 'pn_cm_invoice_tax_rate', $budget_tax_rate);
          update_post_meta($invoice_id, 'pn_cm_invoice_discount_rate', $budget_discount_rate);
          update_post_meta($invoice_id, 'pn_cm_invoice_client_notes', $budget_client_notes);
          update_post_meta($invoice_id, 'pn_cm_invoice_status', 'draft');
          update_post_meta($invoice_id, 'pn_cm_invoice_date', gmdate('Y-m-d'));

          $due_days = intval(get_option('pn_customers_manager_invoice_default_due_days', 30));
          update_post_meta($invoice_id, 'pn_cm_invoice_due_date', gmdate('Y-m-d', strtotime('+' . $due_days . ' days')));

          // Store source budget reference (bidirectional)
          update_post_meta($invoice_id, 'pn_cm_invoice_source_budget_id', $pn_cm_budget_id);

          $existing_invoice_ids = get_post_meta($pn_cm_budget_id, 'pn_cm_budget_invoice_ids', true);
          if (!is_array($existing_invoice_ids)) $existing_invoice_ids = [];
          $existing_invoice_ids[] = $invoice_id;
          update_post_meta($pn_cm_budget_id, 'pn_cm_budget_invoice_ids', array_unique($existing_invoice_ids));

          // Copy selected items (strip is_optional / is_selected, assign new IDs)
          $invoice_items = [];
          $next_id = 1;
          foreach ($budget_items as $item) {
            // Only copy selected items
            if (isset($item['is_selected']) && empty($item['is_selected']) && $item['item_type'] !== 'phase') {
              continue;
            }

            $invoice_items[] = [
              'id'          => $next_id,
              'item_type'   => $item['item_type'],
              'description' => $item['description'],
              'quantity'    => floatval($item['quantity']),
              'unit_price'  => floatval($item['unit_price']),
              'total'       => round(floatval($item['quantity']) * floatval($item['unit_price']), 2),
              'sort_order'  => $next_id - 1,
            ];
            $next_id++;
          }

          update_post_meta($invoice_id, 'pn_cm_invoice_items', $invoice_items);
          update_post_meta($invoice_id, 'pn_cm_invoice_next_item_id', $next_id);

          // Recalculate totals
          PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($invoice_id);

          wp_send_json([
            'error_key' => '',
            'invoice_id' => $invoice_id,
            'invoice_number' => $invoice_number,
          ]);
          break;

        case 'pn_cm_budget_export_items':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_budget_export_items_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          if (empty($pn_cm_budget_id)) {
            wp_send_json(['error_key' => 'pn_cm_budget_export_items_error', 'error_content' => esc_html(__('Budget ID is required.', 'pn-customers-manager'))]);
          }

          $items = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_items($pn_cm_budget_id);
          $budget_title = sanitize_file_name(get_the_title($pn_cm_budget_id));

          header('Content-Type: text/csv; charset=UTF-8');
          header('Content-Disposition: attachment; filename="budget-items-' . $budget_title . '.csv"');
          header('Pragma: no-cache');
          header('Expires: 0');

          $output = fopen('php://output', 'w');
          // UTF-8 BOM for Excel compatibility.
          fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
          fputcsv($output, ['item_type', 'description', 'quantity', 'unit_price', 'is_optional', 'is_recurring', 'recurring_period', 'alt_of', 'alt_active']);

          foreach ($items as $item) {
            if (isset($item['item_type']) && $item['item_type'] === 'image') {
              continue;
            }
            fputcsv($output, [
              isset($item['item_type']) ? $item['item_type'] : 'fixed',
              isset($item['description']) ? $item['description'] : '',
              isset($item['item_type']) && $item['item_type'] === 'phase' ? '' : (isset($item['quantity']) ? floatval($item['quantity']) : 0),
              isset($item['item_type']) && $item['item_type'] === 'phase' ? '' : (isset($item['unit_price']) ? floatval($item['unit_price']) : 0),
              isset($item['is_optional']) ? intval($item['is_optional']) : 0,
              isset($item['is_recurring']) ? intval($item['is_recurring']) : 0,
              isset($item['recurring_period']) ? $item['recurring_period'] : '',
              isset($item['alt_of']) ? intval($item['alt_of']) : 0,
              isset($item['alt_active']) ? $item['alt_active'] : '',
            ]);
          }

          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Writing CSV to php://output stream.
          fclose($output);
          exit;
          break;

        case 'pn_cm_budget_import_items':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_budget_import_items_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_budget_id = !empty($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
          if (empty($pn_cm_budget_id)) {
            wp_send_json(['error_key' => 'pn_cm_budget_import_items_error', 'error_content' => esc_html(__('Budget ID is required.', 'pn-customers-manager'))]);
          }

          if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json(['error_key' => 'pn_cm_budget_import_items_error', 'error_content' => esc_html(__('No valid CSV file uploaded.', 'pn-customers-manager'))]);
          }

          $csv_file = $_FILES['csv_file']['tmp_name'];
          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading uploaded CSV temp file.
          $handle = fopen($csv_file, 'r');
          if (!$handle) {
            wp_send_json(['error_key' => 'pn_cm_budget_import_items_error', 'error_content' => esc_html(__('Could not read the CSV file.', 'pn-customers-manager'))]);
          }

          // Skip UTF-8 BOM if present.
          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading BOM bytes from CSV stream.
          $bom = fread($handle, 3);
          if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
          }

          // Read and validate header row.
          $header = fgetcsv($handle);
          if (!$header) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing CSV file handle on error.
            fclose($handle);
            wp_send_json(['error_key' => 'pn_cm_budget_import_items_error', 'error_content' => esc_html(__('Empty CSV file.', 'pn-customers-manager'))]);
          }
          $header = array_map('trim', $header);
          $header = array_map('strtolower', $header);

          $valid_types = ['phase', 'fixed', 'hours', 'image'];
          $valid_periods = ['daily', 'weekly', 'monthly', 'yearly', ''];
          $new_items = [];
          $row_number = 1;

          while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            if (empty(array_filter($row))) {
              continue; // skip empty rows
            }

            $data = [];
            foreach ($header as $i => $col) {
              $data[$col] = isset($row[$i]) ? $row[$i] : '';
            }

            $item_type = isset($data['item_type']) ? sanitize_text_field(trim($data['item_type'])) : 'fixed';
            if (!in_array($item_type, $valid_types, true)) {
              $item_type = 'fixed';
            }

            $description = isset($data['description']) ? wp_kses_post(trim($data['description'])) : '';
            $quantity = isset($data['quantity']) ? floatval($data['quantity']) : ($item_type === 'phase' ? 0 : 1);
            $unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0;
            $is_optional = isset($data['is_optional']) ? intval($data['is_optional']) : 0;
            $is_recurring = isset($data['is_recurring']) ? intval($data['is_recurring']) : 0;
            $recurring_period = isset($data['recurring_period']) ? sanitize_text_field(trim($data['recurring_period'])) : '';
            if (!in_array($recurring_period, $valid_periods, true)) {
              $recurring_period = '';
            }
            $alt_of = isset($data['alt_of']) ? intval($data['alt_of']) : 0;
            $alt_active = isset($data['alt_active']) ? sanitize_text_field(trim($data['alt_active'])) : '';

            if ($item_type === 'phase') {
              $quantity = 0;
              $unit_price = 0;
              $is_optional = 0;
              $is_recurring = 0;
              $recurring_period = '';
            }

            $item_id = PN_CUSTOMERS_MANAGER_Post_Type_Budget::add_budget_item($pn_cm_budget_id, [
              'item_type'        => $item_type,
              'description'      => $description,
              'quantity'         => $quantity,
              'unit_price'       => $unit_price,
              'is_optional'      => $is_optional,
              'is_selected'      => 1,
              'is_recurring'     => $is_recurring,
              'recurring_period' => $recurring_period,
              'alt_of'           => $alt_of,
              'alt_active'       => $alt_active,
            ]);

            $total = round($quantity * $unit_price, 2);
            $new_items[] = [
              'id'               => $item_id,
              'item_type'        => $item_type,
              'description'      => $description,
              'quantity'         => $quantity,
              'unit_price'       => $unit_price,
              'total'            => $total,
              'is_optional'      => $is_optional,
              'is_selected'      => 1,
              'is_recurring'     => $is_recurring,
              'recurring_period' => $recurring_period,
              'alt_of'           => $alt_of,
              'alt_active'       => $alt_active,
            ];
          }

          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing CSV file handle after import.
          fclose($handle);

          PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($pn_cm_budget_id);

          wp_send_json([
            'error_key' => '',
            'items' => $new_items,
            'count' => count($new_items),
            'totals' => [
              'subtotal'        => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_subtotal', true)),
              'tax_amount'      => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_tax_amount', true)),
              'discount_amount' => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_discount_amount', true)),
              'total'           => floatval(get_post_meta($pn_cm_budget_id, 'pn_cm_budget_total', true)),
            ],
          ]);
          break;

        // â"€â"€ Invoice AJAX cases â"€â"€
        case 'pn_cm_invoice_view':
          $pn_cm_invoice_id = !empty($_POST['pn_cm_invoice_id']) ? intval($_POST['pn_cm_invoice_id']) : 0;
          if (!empty($pn_cm_invoice_id)) {
            $plugin_post_type_invoice = new PN_CUSTOMERS_MANAGER_Post_Type_Invoice();
            echo wp_json_encode([
              'error_key' => '',
              'html' => $plugin_post_type_invoice->pn_cm_invoice_view($pn_cm_invoice_id),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_invoice_view_error', 'error_content' => esc_html(__('An error occurred while showing the Invoice.', 'pn-customers-manager'))]);
          exit;
          break;

        case 'pn_cm_invoice_edit':
          $pn_cm_invoice_id = !empty($_POST['pn_cm_invoice_id']) ? intval($_POST['pn_cm_invoice_id']) : 0;
          if (!empty($pn_cm_invoice_id)) {
            $plugin_post_type_invoice = new PN_CUSTOMERS_MANAGER_Post_Type_Invoice();
            echo wp_json_encode([
              'error_key' => '',
              'html' => $plugin_post_type_invoice->pn_cm_invoice_edit($pn_cm_invoice_id),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_invoice_edit_error', 'error_content' => esc_html(__('An error occurred while showing the Invoice.', 'pn-customers-manager'))]);
          exit;
          break;

        case 'pn_cm_invoice_new':
          if (!is_user_logged_in()) {
            echo wp_json_encode(['error_key' => 'not_logged_in', 'error_content' => esc_html(__('You must be logged in to create a new asset.', 'pn-customers-manager'))]);
            exit;
          }
          $plugin_post_type_invoice = new PN_CUSTOMERS_MANAGER_Post_Type_Invoice();
          echo wp_json_encode([
            'error_key' => '',
            'html' => $plugin_post_type_invoice->pn_cm_invoice_new(),
          ]);
          exit;
          break;

        case 'pn_cm_invoice_remove':
          $pn_cm_invoice_id = !empty($_POST['pn_cm_invoice_id']) ? intval($_POST['pn_cm_invoice_id']) : 0;
          if (!empty($pn_cm_invoice_id)) {
            PN_CUSTOMERS_MANAGER_Activity_Log::log('delete', 'invoice', $pn_cm_invoice_id, get_the_title($pn_cm_invoice_id), 'Deleted Invoice #' . $pn_cm_invoice_id);
            wp_delete_post($pn_cm_invoice_id, true);

            $plugin_post_type_invoice = new PN_CUSTOMERS_MANAGER_Post_Type_Invoice();
            wp_send_json([
              'error_key' => '',
              'html' => $plugin_post_type_invoice->pn_cm_invoice_list(),
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_remove_error', 'error_content' => esc_html(__('An error occurred while removing the Invoice.', 'pn-customers-manager'))]);
          break;

        case 'pn_cm_invoice_duplicate':
          $pn_cm_invoice_id = !empty($_POST['pn_cm_invoice_id']) ? intval($_POST['pn_cm_invoice_id']) : 0;
          if (!empty($pn_cm_invoice_id)) {
            PN_CUSTOMERS_MANAGER_Activity_Log::log('create', 'invoice', $pn_cm_invoice_id, get_the_title($pn_cm_invoice_id), 'Duplicated Invoice #' . $pn_cm_invoice_id);
            $original = get_post($pn_cm_invoice_id);
            if ($original) {
              $new_id = wp_insert_post([
                'post_type' => 'pn_cm_invoice',
                'post_title' => $original->post_title . ' (' . __('Copy', 'pn-customers-manager') . ')',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
              ]);

              if ($new_id && !is_wp_error($new_id)) {
                $meta_keys = ['pn_cm_invoice_organization_id', 'pn_cm_invoice_tax_rate', 'pn_cm_invoice_discount_rate', 'pn_cm_invoice_irpf_rate', 'pn_cm_invoice_notes', 'pn_cm_invoice_client_notes'];
                foreach ($meta_keys as $mk) {
                  $val = get_post_meta($pn_cm_invoice_id, $mk, true);
                  if ($val !== '') update_post_meta($new_id, $mk, $val);
                }

                update_post_meta($new_id, 'pn_cm_invoice_number', PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_generate_number());
                update_post_meta($new_id, 'pn_cm_invoice_date', current_time('Y-m-d'));
                $due_days = intval(get_option('pn_customers_manager_invoice_default_due_days', 30));
                update_post_meta($new_id, 'pn_cm_invoice_due_date', gmdate('Y-m-d', strtotime('+' . $due_days . ' days')));
                update_post_meta($new_id, 'pn_cm_invoice_status', 'draft');
                update_post_meta($new_id, 'pn_cm_invoice_token', PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_generate_token());

                $src_items = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_items($pn_cm_invoice_id);
                $new_items = [];
                $next_id   = 1;
                foreach ($src_items as $si) {
                  $si['id'] = $next_id++;
                  $new_items[] = $si;
                }
                PN_CUSTOMERS_MANAGER_Post_Type_Invoice::save_invoice_items($new_id, $new_items);
                update_post_meta($new_id, 'pn_cm_invoice_next_item_id', $next_id);

                PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($new_id);
              }
            }

            $plugin_post_type_invoice = new PN_CUSTOMERS_MANAGER_Post_Type_Invoice();
            wp_send_json([
              'error_key' => '',
              'html' => $plugin_post_type_invoice->pn_cm_invoice_list(),
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_duplicate_error']);
          break;

        case 'pn_cm_invoice_add_item':
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          if (!empty($pn_cm_invoice_id)) {
            $item_type   = sanitize_text_field(wp_unslash($_POST['item_type'] ?? 'fixed'));
            $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
            $quantity    = floatval($_POST['quantity'] ?? 1);
            $unit_price  = floatval($_POST['unit_price'] ?? 0);
            $total       = round($quantity * $unit_price, 2);

            $item_id = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::add_invoice_item($pn_cm_invoice_id, [
              'item_type'   => $item_type,
              'description' => $description,
              'quantity'    => $quantity,
              'unit_price'  => $unit_price,
            ]);

            PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($pn_cm_invoice_id);

            wp_send_json([
              'error_key' => '',
              'item' => [
                'id'          => $item_id,
                'item_type'   => $item_type,
                'description' => $description,
                'quantity'    => $quantity,
                'unit_price'  => $unit_price,
                'total'       => $total,
              ],
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_discount_amount', true)),
                'irpf_amount'     => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_irpf_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_add_item_error']);
          break;

        case 'pn_cm_invoice_add_item_form':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_add_form_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          if (!empty($pn_cm_invoice_id)) {
            wp_send_json([
              'error_key' => '',
              'html' => PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_render_item_add_form($pn_cm_invoice_id),
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_add_form_error']);
          break;

        case 'pn_cm_invoice_remove_item':
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_invoice_id) && !empty($item_id)) {
            PN_CUSTOMERS_MANAGER_Activity_Log::log('delete', 'invoice', $pn_cm_invoice_id, get_the_title($pn_cm_invoice_id), 'Deleted item #' . $item_id . ' from Invoice #' . $pn_cm_invoice_id);
            PN_CUSTOMERS_MANAGER_Post_Type_Invoice::delete_invoice_item($pn_cm_invoice_id, $item_id);
            PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($pn_cm_invoice_id);

            wp_send_json([
              'error_key' => '',
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_discount_amount', true)),
                'irpf_amount'     => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_irpf_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_remove_item_error']);
          break;

        case 'pn_cm_invoice_reorder_items':
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $order = !empty($_POST['order']) ? json_decode(sanitize_text_field(wp_unslash($_POST['order'])), true) : [];
          if (!empty($pn_cm_invoice_id) && !empty($order)) {
            $items = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_items($pn_cm_invoice_id);
            $order_map = array_flip($order);
            foreach ($items as &$it) {
              $id_str = strval($it['id']);
              if (isset($order_map[$id_str])) {
                $it['sort_order'] = $order_map[$id_str];
              }
            }
            unset($it);
            PN_CUSTOMERS_MANAGER_Post_Type_Invoice::save_invoice_items($pn_cm_invoice_id, $items);
            wp_send_json(['error_key' => '']);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_reorder_error']);
          break;

        case 'pn_cm_invoice_update_item':
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_invoice_id) && !empty($item_id)) {
            $item_type   = sanitize_text_field(wp_unslash($_POST['item_type'] ?? 'fixed'));
            $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
            $quantity    = floatval($_POST['quantity'] ?? 1);
            $unit_price  = floatval($_POST['unit_price'] ?? 0);

            $update_data = [
              'item_type'   => $item_type,
              'description' => $description,
              'quantity'    => $quantity,
              'unit_price'  => $unit_price,
            ];

            PN_CUSTOMERS_MANAGER_Post_Type_Invoice::update_invoice_item($pn_cm_invoice_id, $item_id, $update_data);
            PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($pn_cm_invoice_id);

            wp_send_json([
              'error_key' => '',
              'item' => [
                'item_id'     => $item_id,
                'item_type'   => $item_type,
                'description' => $description,
                'quantity'    => $quantity,
                'unit_price'  => $unit_price,
              ],
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_discount_amount', true)),
                'irpf_amount'     => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_irpf_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_update_item_error']);
          break;

        case 'pn_cm_invoice_export_items':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_export_items_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          if (empty($pn_cm_invoice_id)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_export_items_error', 'error_content' => esc_html(__('Invoice ID is required.', 'pn-customers-manager'))]);
          }

          $items = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_items($pn_cm_invoice_id);
          $invoice_title = sanitize_file_name(get_the_title($pn_cm_invoice_id));

          header('Content-Type: text/csv; charset=UTF-8');
          header('Content-Disposition: attachment; filename="invoice-items-' . $invoice_title . '.csv"');
          header('Pragma: no-cache');
          header('Expires: 0');

          $output = fopen('php://output', 'w');
          fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
          fputcsv($output, ['item_type', 'description', 'quantity', 'unit_price']);

          foreach ($items as $item) {
            fputcsv($output, [
              isset($item['item_type']) ? $item['item_type'] : 'fixed',
              isset($item['description']) ? $item['description'] : '',
              isset($item['item_type']) && $item['item_type'] === 'phase' ? '' : (isset($item['quantity']) ? floatval($item['quantity']) : 0),
              isset($item['item_type']) && $item['item_type'] === 'phase' ? '' : (isset($item['unit_price']) ? floatval($item['unit_price']) : 0),
            ]);
          }

          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Writing CSV to php://output stream.
          fclose($output);
          exit;
          break;

        case 'pn_cm_invoice_import_items':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_import_items_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          if (empty($pn_cm_invoice_id)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_import_items_error', 'error_content' => esc_html(__('Invoice ID is required.', 'pn-customers-manager'))]);
          }

          if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json(['error_key' => 'pn_cm_invoice_import_items_error', 'error_content' => esc_html(__('No valid CSV file uploaded.', 'pn-customers-manager'))]);
          }

          $csv_file = $_FILES['csv_file']['tmp_name'];
          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading uploaded CSV temp file.
          $handle = fopen($csv_file, 'r');
          if (!$handle) {
            wp_send_json(['error_key' => 'pn_cm_invoice_import_items_error', 'error_content' => esc_html(__('Could not read the CSV file.', 'pn-customers-manager'))]);
          }

          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading BOM bytes from CSV stream.
          $bom = fread($handle, 3);
          if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
          }

          $header = fgetcsv($handle);
          if (!$header) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing CSV file handle on error.
            fclose($handle);
            wp_send_json(['error_key' => 'pn_cm_invoice_import_items_error', 'error_content' => esc_html(__('Empty CSV file.', 'pn-customers-manager'))]);
          }
          $header = array_map('trim', $header);
          $header = array_map('strtolower', $header);

          $valid_types = ['phase', 'fixed', 'hours'];
          $new_items = [];

          while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) {
              continue;
            }

            $data = [];
            foreach ($header as $i => $col) {
              $data[$col] = isset($row[$i]) ? $row[$i] : '';
            }

            $item_type = isset($data['item_type']) ? sanitize_text_field(trim($data['item_type'])) : 'fixed';
            if (!in_array($item_type, $valid_types, true)) {
              $item_type = 'fixed';
            }

            $description = isset($data['description']) ? wp_kses_post(trim($data['description'])) : '';
            $quantity = isset($data['quantity']) ? floatval($data['quantity']) : ($item_type === 'phase' ? 0 : 1);
            $unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0;

            if ($item_type === 'phase') {
              $quantity = 0;
              $unit_price = 0;
            }

            $item_id = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::add_invoice_item($pn_cm_invoice_id, [
              'item_type'   => $item_type,
              'description' => $description,
              'quantity'    => $quantity,
              'unit_price'  => $unit_price,
            ]);

            $total = round($quantity * $unit_price, 2);
            $new_items[] = [
              'id'          => $item_id,
              'item_type'   => $item_type,
              'description' => $description,
              'quantity'    => $quantity,
              'unit_price'  => $unit_price,
              'total'       => $total,
            ];
          }

          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing CSV file handle after import.
          fclose($handle);

          PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($pn_cm_invoice_id);

          wp_send_json([
            'error_key' => '',
            'items' => $new_items,
            'count' => count($new_items),
            'totals' => [
              'subtotal'        => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_subtotal', true)),
              'tax_amount'      => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_tax_amount', true)),
              'discount_amount' => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_discount_amount', true)),
              'total'           => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_total', true)),
            ],
          ]);
          break;

        case 'pn_cm_invoice_bulk_remove_items':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_bulk_remove_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $item_ids = !empty($_POST['item_ids']) ? json_decode(stripslashes($_POST['item_ids']), true) : [];
          if (!empty($pn_cm_invoice_id) && !empty($item_ids) && is_array($item_ids)) {
            foreach ($item_ids as $iid) {
              PN_CUSTOMERS_MANAGER_Post_Type_Invoice::delete_invoice_item($pn_cm_invoice_id, intval($iid));
            }
            PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($pn_cm_invoice_id);

            wp_send_json([
              'error_key' => '',
              'totals' => [
                'subtotal'        => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_subtotal', true)),
                'tax_amount'      => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_tax_amount', true)),
                'discount_amount' => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_discount_amount', true)),
                'irpf_amount'     => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_irpf_amount', true)),
                'total'           => floatval(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_total', true)),
              ],
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_bulk_remove_error']);
          break;

        case 'pn_cm_invoice_edit_item_form':
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : 0;
          if (!empty($pn_cm_invoice_id) && !empty($item_id)) {
            wp_send_json([
              'error_key' => '',
              'html' => PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_render_item_edit_form($item_id, $pn_cm_invoice_id),
            ]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_edit_form_error']);
          break;

        case 'pn_cm_invoice_send':
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          if (!empty($pn_cm_invoice_id)) {
            update_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_status', 'sent');
            PN_CUSTOMERS_MANAGER_Activity_Log::log('status_change', 'invoice', $pn_cm_invoice_id, get_the_title($pn_cm_invoice_id), 'Invoice status changed to sent');
            wp_send_json(['error_key' => '', 'status' => 'sent']);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_send_error']);
          break;

        case 'pn_cm_invoice_update_status':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_update_status_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $new_status = !empty($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
          $allowed_statuses = ['draft', 'sent', 'paid', 'cancelled'];
          if (!empty($pn_cm_invoice_id) && in_array($new_status, $allowed_statuses, true)) {
            update_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_status', $new_status);
            if ($new_status === 'paid' && empty(get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_paid_at', true))) {
              update_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_paid_at', current_time('mysql'));
            }
            PN_CUSTOMERS_MANAGER_Activity_Log::log('status_change', 'invoice', $pn_cm_invoice_id, get_the_title($pn_cm_invoice_id), 'Invoice status changed to ' . $new_status);
            wp_send_json(['error_key' => '', 'status' => $new_status]);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_update_status_error']);
          break;

        case 'pn_cm_invoice_reset_counter':
          if (!current_user_can('manage_options')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_reset_counter_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $is_locked = get_option('pn_customers_manager_invoice_number_locked', '0') === '1';
          if ($is_locked) {
            wp_send_json(['error_key' => 'pn_cm_invoice_reset_counter_error', 'error_content' => esc_html(__('Numbering format is locked. Unlock it first to reset the counter.', 'pn-customers-manager'))]);
          }
          update_option('pn_customers_manager_invoice_next_number', '1');
          wp_send_json(['error_key' => '']);
          break;

        case 'pn_cm_invoice_upload_file':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_upload_file_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          if (empty($pn_cm_invoice_id) || empty($_FILES['file'])) {
            wp_send_json(['error_key' => 'pn_cm_invoice_upload_file_error', 'error_content' => esc_html(__('Invoice ID and file are required.', 'pn-customers-manager'))]);
          }
          $result = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::add_invoice_file($pn_cm_invoice_id, $_FILES['file']);
          if (is_wp_error($result)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_upload_file_error', 'error_content' => $result->get_error_message()]);
          }
          $result['size_formatted'] = size_format($result['size']);
          wp_send_json(['error_key' => '', 'file' => $result]);
          break;

        case 'pn_cm_invoice_delete_file':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_delete_file_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $file_id = !empty($_POST['file_id']) ? intval($_POST['file_id']) : 0;
          if (empty($pn_cm_invoice_id) || empty($file_id)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_delete_file_error', 'error_content' => esc_html(__('Invoice ID and file ID are required.', 'pn-customers-manager'))]);
          }
          $deleted = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::delete_invoice_file($pn_cm_invoice_id, $file_id);
          if ($deleted) {
            wp_send_json(['error_key' => '']);
          }
          wp_send_json(['error_key' => 'pn_cm_invoice_delete_file_error', 'error_content' => esc_html(__('File not found.', 'pn-customers-manager'))]);
          break;

        case 'pn_cm_invoice_download_file':
          // Logged-in users can also download via the public page (admins viewing the invoice).
          $pn_cm_invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $invoice_token    = isset($_POST['invoice_token']) ? sanitize_text_field(wp_unslash($_POST['invoice_token'])) : '';
          $file_id          = !empty($_POST['file_id']) ? intval($_POST['file_id']) : 0;

          if (empty($pn_cm_invoice_id) || empty($invoice_token) || empty($file_id)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_download_file_error', 'error_content' => esc_html(__('Invalid request.', 'pn-customers-manager'))]);
          }

          $stored_token = get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_token', true);
          if (empty($stored_token) || $stored_token !== $invoice_token) {
            wp_send_json(['error_key' => 'pn_cm_invoice_download_file_error', 'error_content' => esc_html(__('Invalid token.', 'pn-customers-manager'))]);
          }

          $invoice_status = get_post_meta($pn_cm_invoice_id, 'pn_cm_invoice_status', true);
          if ($invoice_status === 'draft') {
            wp_send_json(['error_key' => 'pn_cm_invoice_download_file_error', 'error_content' => esc_html(__('Downloads are not available for draft invoices.', 'pn-customers-manager'))]);
          }

          $files = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_files($pn_cm_invoice_id);
          $target_file = null;
          foreach ($files as $f) {
            if (intval($f['id']) === $file_id) {
              $target_file = $f;
              break;
            }
          }

          if (!$target_file) {
            wp_send_json(['error_key' => 'pn_cm_invoice_download_file_error', 'error_content' => esc_html(__('File not found.', 'pn-customers-manager'))]);
          }

          $dir = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_files_dir();
          $filepath = $dir . '/' . $target_file['stored_name'];

          if (!file_exists($filepath)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_download_file_error', 'error_content' => esc_html(__('File not found on disk.', 'pn-customers-manager'))]);
          }

          header('Content-Type: application/octet-stream');
          header('Content-Disposition: attachment; filename="' . $target_file['filename'] . '"');
          header('Content-Length: ' . filesize($filepath));
          header('Cache-Control: no-store, no-cache, must-revalidate');
          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming file download to browser.
          readfile($filepath);
          exit;

        case 'pn_cm_invoice_ai_analyze_file':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }
          $invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $file_id    = !empty($_POST['file_id']) ? intval($_POST['file_id']) : 0;
          if (empty($invoice_id) || empty($file_id)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('Invoice ID and file ID are required.', 'pn-customers-manager'))]);
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
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('File not found.', 'pn-customers-manager'))]);
          }
          $dir = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_files_dir();
          $filepath = $dir . '/' . $target_file['stored_name'];
          if (!file_exists($filepath)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('File not found on disk.', 'pn-customers-manager'))]);
          }
          $result = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::call_ai_vision($filepath);
          if (is_wp_error($result)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => $result->get_error_message()]);
          }
          // Resolve organization: find or create.
          if (!empty($result['organization'])) {
            $org_name = sanitize_text_field($result['organization']);
            global $wpdb;
            $found_id = $wpdb->get_var($wpdb->prepare(
              "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'pn_cm_organization' AND post_status = 'publish' AND LOWER(post_title) = LOWER(%s) LIMIT 1",
              $org_name
            ));
            if ($found_id) {
              $result['organization_id'] = intval($found_id);
            } else {
              $new_org_id = wp_insert_post([
                'post_title'  => $org_name,
                'post_type'   => 'pn_cm_organization',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
              ]);
              if ($new_org_id && !is_wp_error($new_org_id)) {
                $result['organization_id'] = $new_org_id;
                $result['organization_created'] = true;
                // Save org details from AI extraction.
                if (!empty($result['organization_address'])) update_post_meta($new_org_id, 'pn_cm_organization_address', sanitize_text_field($result['organization_address']));
                if (!empty($result['organization_city'])) update_post_meta($new_org_id, 'pn_cm_organization_city', sanitize_text_field($result['organization_city']));
                if (!empty($result['organization_postal_code'])) update_post_meta($new_org_id, 'pn_cm_organization_postal_code', sanitize_text_field($result['organization_postal_code']));
                if (!empty($result['organization_country'])) update_post_meta($new_org_id, 'pn_cm_organization_country', sanitize_text_field($result['organization_country']));
                if (!empty($result['organization_fiscal_id'])) update_post_meta($new_org_id, 'pn_cm_organization_fiscal_id', sanitize_text_field($result['organization_fiscal_id']));
              }
            }
          }
          // Save items to invoice if AI returned them.
          if ($invoice_id > 0 && !empty($result['items']) && is_array($result['items'])) {
            $new_items = [];
            foreach ($result['items'] as $idx => $ai_item) {
              $qty   = isset($ai_item['quantity']) ? floatval($ai_item['quantity']) : 1;
              $price = isset($ai_item['unit_price']) ? floatval($ai_item['unit_price']) : 0;
              $new_items[] = [
                'id'          => PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_next_item_id($invoice_id) + $idx,
                'item_type'   => 'fixed',
                'description' => sanitize_text_field($ai_item['concept'] ?? ''),
                'quantity'    => $qty,
                'unit_price'  => $price,
                'total'       => round($qty * $price, 2),
                'sort_order'  => $idx,
              ];
            }
            if (!empty($new_items)) {
              PN_CUSTOMERS_MANAGER_Post_Type_Invoice::save_invoice_items($invoice_id, $new_items);
              PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($invoice_id);
            }
          }
          wp_send_json(['error_key' => '', 'data' => $result]);
          break;

        case 'pn_cm_invoice_ai_analyze_upload':
          if (!current_user_can('manage_options') && !current_user_can('pn_cm_manage_crm')) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('You do not have permission.', 'pn-customers-manager'))]);
          }

          if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('No file uploaded.', 'pn-customers-manager'))]);
          }

          $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png'];
          $file_mime = mime_content_type($_FILES['file']['tmp_name']);
          if (!in_array($file_mime, $allowed_mimes, true)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('Invalid file type. Only PDF, JPG and PNG are allowed.', 'pn-customers-manager'))]);
          }

          $max_size = 10 * 1024 * 1024; // 10 MB
          if ($_FILES['file']['size'] > $max_size) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('File is too large. Maximum size is 10 MB.', 'pn-customers-manager'))]);
          }

          $invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
          $dir = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_files_dir();
          $original_name = sanitize_file_name($_FILES['file']['name']);
          $unique_hash = wp_generate_password(12, false);
          $temp_name = 'tmp-' . $unique_hash . '-' . $original_name;
          $temp_path = $dir . '/' . $temp_name;

          // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- move_uploaded_file is the standard PHP function for handling uploads.
          if (!move_uploaded_file($_FILES['file']['tmp_name'], $temp_path)) {
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => esc_html(__('Could not save the file.', 'pn-customers-manager'))]);
          }

          $ai_result = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::call_ai_vision($temp_path);
          if (is_wp_error($ai_result)) {
            wp_delete_file($temp_path);
            wp_send_json(['error_key' => 'pn_cm_invoice_ai_analyze_error', 'error_content' => $ai_result->get_error_message()]);
          }

          // Auto-create organization if AI returned a name that doesn't exist yet.
          if (!empty($ai_result['organization'])) {
            $org_name = sanitize_text_field($ai_result['organization']);
            $existing_orgs = get_posts([
              'post_type'   => 'pn_cm_organization',
              'numberposts' => 1,
              'title'       => $org_name,
              'post_status' => 'any',
              'fields'      => 'ids',
            ]);
            if (empty($existing_orgs)) {
              // Also try a case-insensitive search via SQL.
              global $wpdb;
              $found_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'pn_cm_organization' AND post_status = 'publish' AND LOWER(post_title) = LOWER(%s) LIMIT 1",
                $org_name
              ));
              if ($found_id) {
                $ai_result['organization_id'] = intval($found_id);
              } else {
                $new_org_id = wp_insert_post([
                  'post_title'  => $org_name,
                  'post_type'   => 'pn_cm_organization',
                  'post_status' => 'publish',
                  'post_author' => get_current_user_id(),
                ]);
                if ($new_org_id && !is_wp_error($new_org_id)) {
                  $ai_result['organization_id'] = $new_org_id;
                  $ai_result['organization_created'] = true;
                  // Save org details from AI extraction.
                  if (!empty($ai_result['organization_address'])) update_post_meta($new_org_id, 'pn_cm_organization_address', sanitize_text_field($ai_result['organization_address']));
                  if (!empty($ai_result['organization_city'])) update_post_meta($new_org_id, 'pn_cm_organization_city', sanitize_text_field($ai_result['organization_city']));
                  if (!empty($ai_result['organization_postal_code'])) update_post_meta($new_org_id, 'pn_cm_organization_postal_code', sanitize_text_field($ai_result['organization_postal_code']));
                  if (!empty($ai_result['organization_country'])) update_post_meta($new_org_id, 'pn_cm_organization_country', sanitize_text_field($ai_result['organization_country']));
                  if (!empty($ai_result['organization_fiscal_id'])) update_post_meta($new_org_id, 'pn_cm_organization_fiscal_id', sanitize_text_field($ai_result['organization_fiscal_id']));
                }
              }
            } else {
              $ai_result['organization_id'] = intval($existing_orgs[0]);
            }
          }

          $response_data = ['error_key' => '', 'data' => $ai_result];

          // Save items to invoice if AI returned them.
          if ($invoice_id > 0 && !empty($ai_result['items']) && is_array($ai_result['items'])) {
            $new_items = [];
            foreach ($ai_result['items'] as $idx => $ai_item) {
              $qty   = isset($ai_item['quantity']) ? floatval($ai_item['quantity']) : 1;
              $price = isset($ai_item['unit_price']) ? floatval($ai_item['unit_price']) : 0;
              $new_items[] = [
                'id'          => PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_next_item_id($invoice_id) + $idx,
                'item_type'   => 'fixed',
                'description' => sanitize_text_field($ai_item['concept'] ?? ''),
                'quantity'    => $qty,
                'unit_price'  => $price,
                'total'       => round($qty * $price, 2),
                'sort_order'  => $idx,
              ];
            }
            if (!empty($new_items)) {
              PN_CUSTOMERS_MANAGER_Post_Type_Invoice::save_invoice_items($invoice_id, $new_items);
              PN_CUSTOMERS_MANAGER_Post_Type_Invoice::pn_cm_invoice_recalculate_totals($invoice_id);
            }
          }

          if ($invoice_id > 0) {
            // Invoice exists: rename to permanent and attach.
            $stored_name = $invoice_id . '-' . $unique_hash . '-' . $original_name;
            $final_path  = $dir . '/' . $stored_name;
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Renaming temp file to permanent within the same directory.
            rename($temp_path, $final_path);

            $file_id = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_next_file_id($invoice_id);
            $entry = [
              'id'          => $file_id,
              'filename'    => $original_name,
              'stored_name' => $stored_name,
              'size'        => filesize($final_path),
              'uploaded_at' => current_time('mysql'),
            ];
            $files = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_files($invoice_id);
            $files[] = $entry;
            PN_CUSTOMERS_MANAGER_Post_Type_Invoice::save_invoice_files($invoice_id, $files);

            $response_data['file'] = $entry;
          } else {
            // Invoice not yet saved: return temp filename for hidden input.
            $response_data['temp_file'] = $temp_name;
          }

          wp_send_json($response_data);
          break;

        case 'migrate_calendar_booking':
          if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to run migrations.', 'pn-customers-manager')]);
            exit;
          }
          require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-migration.php';
          $result = PN_CUSTOMERS_MANAGER_WooCommerce_Migration::migrate_calendar_booking_data();
          wp_send_json_success($result);
          break;
        case 'test_accounting_email':
          if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to test emails.', 'pn-customers-manager')]);
            exit;
          }
          require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-accounting.php';
          $result = PN_CUSTOMERS_MANAGER_WooCommerce_Accounting::instance()->test_send();
          if ($result['success']) {
            wp_send_json_success($result);
          } else {
            wp_send_json_error($result);
          }
          break;
        case 'pn_cm_woo_qr_view':
          if (!current_user_can('manage_woocommerce')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_woo_qr_view_error',
              'error_content' => esc_html(__('You do not have permission to view QR codes.', 'pn-customers-manager')),
            ]);
            exit;
          }
          $qr_code = !empty($_POST['qr_code']) ? sanitize_text_field(wp_unslash($_POST['qr_code'])) : '';
          if (empty($qr_code)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_woo_qr_view_error',
              'error_content' => esc_html(__('QR code is required.', 'pn-customers-manager')),
            ]);
            exit;
          }
          require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-qr.php';
          $qr_instance = PN_CUSTOMERS_MANAGER_WooCommerce_QR::instance();
          // Use reflection to call private method
          $reflection = new ReflectionClass($qr_instance);
          $method = $reflection->getMethod('qr_view');
          $method->setAccessible(true);
          $html = $method->invoke($qr_instance, $qr_code);
          echo wp_json_encode([
            'error_key' => '',
            'html' => $html,
          ]);
          exit;
          break;

        case 'pn_cm_bis_delete':
          PN_CUSTOMERS_MANAGER_Woo_Back_In_Stock::handle_delete();
          break;

        case 'pn_cm_woo_fila_cero_delete':
          PN_CUSTOMERS_MANAGER_WooCommerce_Fila_Cero::ajax_delete_entry();
          break;

        case 'pn_cm_bis_export_csv':
          PN_CUSTOMERS_MANAGER_Woo_Back_In_Stock::handle_export_csv();
          break;

        case 'pn_customers_manager_complete_tutorial':
          $completed = !empty($_POST['completed']) ? intval($_POST['completed']) : 0;
          PN_CUSTOMERS_MANAGER_Tutorial::mark_completed($completed ? true : false);
          echo wp_json_encode(['success' => true]);
          exit;
          break;

        case 'pn_cm_mockup_flow_save_canvas':
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_save_canvas();
          break;
        case 'pn_cm_mockup_flow_add_comment':
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_add_comment();
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
        case 'pn_cm_mockup_flow_import_images':
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_import_images();
          break;
        case 'pn_cm_mockup_flow_resolve_comment':
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_resolve_comment();
          break;
        case 'pn_cm_mockup_flow_delete_comment':
          PN_CUSTOMERS_MANAGER_Mockup_Flow::ajax_delete_comment();
          break;

        case 'pn_cm_mockup_flow_new':
          if (!is_user_logged_in()) {
            echo wp_json_encode(['error_key' => 'not_logged_in', 'error_content' => esc_html(__('You must be logged in.', 'pn-customers-manager'))]);
            exit;
          }
          echo wp_json_encode([
            'error_key' => '',
            'html' => PN_CUSTOMERS_MANAGER_Mockup_Flow::mockup_flow_new(),
          ]);
          exit;
          break;

        case 'pn_cm_mockup_flow_view':
          $pn_cm_mockup_flow_id = !empty($_POST['pn_cm_mockup_flow_id']) ? intval($_POST['pn_cm_mockup_flow_id']) : 0;
          if (!empty($pn_cm_mockup_flow_id)) {
            echo wp_json_encode([
              'error_key' => '',
              'html' => PN_CUSTOMERS_MANAGER_Mockup_Flow::mockup_flow_view($pn_cm_mockup_flow_id),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_mockup_flow_view_error', 'error_content' => esc_html(__('Mockup Flow not found.', 'pn-customers-manager'))]);
          exit;
          break;

        case 'pn_cm_mockup_flow_edit':
          $pn_cm_mockup_flow_id = !empty($_POST['pn_cm_mockup_flow_id']) ? intval($_POST['pn_cm_mockup_flow_id']) : 0;
          if (!empty($pn_cm_mockup_flow_id)) {
            echo wp_json_encode([
              'error_key' => '',
              'html' => PN_CUSTOMERS_MANAGER_Mockup_Flow::mockup_flow_edit($pn_cm_mockup_flow_id),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_mockup_flow_edit_error', 'error_content' => esc_html(__('Mockup Flow not found.', 'pn-customers-manager'))]);
          exit;
          break;

        case 'pn_cm_mockup_flow_remove_confirm':
          $pn_cm_mockup_flow_id = !empty($_POST['pn_cm_mockup_flow_id']) ? intval($_POST['pn_cm_mockup_flow_id']) : 0;
          if (!empty($pn_cm_mockup_flow_id)) {
            echo wp_json_encode([
              'error_key' => '',
              'html' => PN_CUSTOMERS_MANAGER_Mockup_Flow::mockup_flow_remove_confirm($pn_cm_mockup_flow_id),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_mockup_flow_remove_error', 'error_content' => esc_html(__('Mockup Flow not found.', 'pn-customers-manager'))]);
          exit;
          break;

        case 'pn_cm_mockup_flow_remove':
          $pn_cm_mockup_flow_id = !empty($_POST['pn_cm_mockup_flow_id']) ? intval($_POST['pn_cm_mockup_flow_id']) : 0;
          if (!empty($pn_cm_mockup_flow_id)) {
            wp_delete_post($pn_cm_mockup_flow_id, true);
            echo wp_json_encode([
              'error_key' => '',
              'html' => PN_CUSTOMERS_MANAGER_Mockup_Flow::mockup_flow_list(),
            ]);
            exit;
          }
          echo wp_json_encode(['error_key' => 'pn_cm_mockup_flow_remove_error', 'error_content' => esc_html(__('An error occurred.', 'pn-customers-manager'))]);
          exit;
          break;

        // ── Budget Payments ──
        case 'pn_cm_budget_add_payment':
          if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pn_cm_manage_crm' ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'You do not have permission.', 'pn-customers-manager' ) ) );
          }
          $budget_id = ! empty( $_POST['budget_id'] ) ? intval( $_POST['budget_id'] ) : 0;
          if ( empty( $budget_id ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'Invalid budget.', 'pn-customers-manager' ) ) );
          }
          $payment_data = array(
            'date'      => ! empty( $_POST['payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) : gmdate( 'Y-m-d' ),
            'amount'    => ! empty( $_POST['payment_amount'] ) ? floatval( $_POST['payment_amount'] ) : 0,
            'concept'   => isset( $_POST['payment_concept'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_concept'] ) ) : '',
            'type'      => ! empty( $_POST['payment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_type'] ) ) : 'income',
            'category'  => ! empty( $_POST['payment_category'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_category'] ) ) : 'client_payment',
            'recipient' => isset( $_POST['payment_recipient'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_recipient'] ) ) : '',
            'method'    => ! empty( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '',
            'notes'     => isset( $_POST['payment_notes'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_notes'] ) ) : '',
            'visible'   => isset( $_POST['payment_visible'] ) ? intval( $_POST['payment_visible'] ) : 1,
          );
          if ( $payment_data['amount'] <= 0 ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'Amount must be greater than zero.', 'pn-customers-manager' ) ) );
          }
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::add_payment( $budget_id, $payment_data, 'budget' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::maybe_mark_as_paid( $budget_id, 'budget' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::sync_payments( $budget_id, 'budget' );
          $new_status = get_post_meta( $budget_id, 'pn_cm_budget_status', true );
          wp_send_json( array( 'error_key' => '', 'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_payments_block( $budget_id, 'budget' ), 'status' => $new_status ) );
          break;

        case 'pn_cm_budget_remove_payment':
          if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pn_cm_manage_crm' ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'You do not have permission.', 'pn-customers-manager' ) ) );
          }
          $budget_id  = ! empty( $_POST['budget_id'] ) ? intval( $_POST['budget_id'] ) : 0;
          $payment_id = ! empty( $_POST['payment_id'] ) ? intval( $_POST['payment_id'] ) : 0;
          if ( empty( $budget_id ) || empty( $payment_id ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error' ) );
          }
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::remove_payment( $budget_id, $payment_id, 'budget' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::maybe_mark_as_paid( $budget_id, 'budget' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::sync_payments( $budget_id, 'budget' );
          $new_status = get_post_meta( $budget_id, 'pn_cm_budget_status', true );
          wp_send_json( array( 'error_key' => '', 'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_payments_block( $budget_id, 'budget' ), 'status' => $new_status ) );
          break;

        case 'pn_cm_budget_toggle_payment_visibility':
          if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pn_cm_manage_crm' ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'You do not have permission.', 'pn-customers-manager' ) ) );
          }
          $budget_id  = ! empty( $_POST['budget_id'] ) ? intval( $_POST['budget_id'] ) : 0;
          $payment_id = ! empty( $_POST['payment_id'] ) ? intval( $_POST['payment_id'] ) : 0;
          if ( empty( $budget_id ) || empty( $payment_id ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error' ) );
          }
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::toggle_payment_visibility( $budget_id, $payment_id, 'budget' );
          wp_send_json( array( 'error_key' => '', 'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_payments_block( $budget_id, 'budget' ) ) );
          break;

        case 'pn_cm_budget_edit_payment':
          if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pn_cm_manage_crm' ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'You do not have permission.', 'pn-customers-manager' ) ) );
          }
          $budget_id  = ! empty( $_POST['budget_id'] ) ? intval( $_POST['budget_id'] ) : 0;
          $payment_id = ! empty( $_POST['payment_id'] ) ? intval( $_POST['payment_id'] ) : 0;
          if ( empty( $budget_id ) || empty( $payment_id ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error' ) );
          }
          $payment_data = array(
            'date'      => ! empty( $_POST['payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) : gmdate( 'Y-m-d' ),
            'amount'    => ! empty( $_POST['payment_amount'] ) ? floatval( $_POST['payment_amount'] ) : 0,
            'concept'   => isset( $_POST['payment_concept'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_concept'] ) ) : '',
            'type'      => ! empty( $_POST['payment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_type'] ) ) : 'income',
            'category'  => ! empty( $_POST['payment_category'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_category'] ) ) : 'client_payment',
            'recipient' => isset( $_POST['payment_recipient'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_recipient'] ) ) : '',
            'method'    => ! empty( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '',
            'notes'     => isset( $_POST['payment_notes'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_notes'] ) ) : '',
            'visible'   => isset( $_POST['payment_visible'] ) ? intval( $_POST['payment_visible'] ) : 0,
          );
          if ( $payment_data['amount'] <= 0 ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'Amount must be greater than zero.', 'pn-customers-manager' ) ) );
          }
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_payment( $budget_id, $payment_id, $payment_data, 'budget' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::maybe_mark_as_paid( $budget_id, 'budget' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::sync_payments( $budget_id, 'budget' );
          $new_status = get_post_meta( $budget_id, 'pn_cm_budget_status', true );
          wp_send_json( array( 'error_key' => '', 'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_payments_block( $budget_id, 'budget' ), 'status' => $new_status ) );
          break;

        // ── Invoice Payments ──
        case 'pn_cm_invoice_add_payment':
          if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pn_cm_manage_crm' ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'You do not have permission.', 'pn-customers-manager' ) ) );
          }
          $invoice_id = ! empty( $_POST['invoice_id'] ) ? intval( $_POST['invoice_id'] ) : 0;
          if ( empty( $invoice_id ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'Invalid invoice.', 'pn-customers-manager' ) ) );
          }
          $payment_data = array(
            'date'      => ! empty( $_POST['payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) : gmdate( 'Y-m-d' ),
            'amount'    => ! empty( $_POST['payment_amount'] ) ? floatval( $_POST['payment_amount'] ) : 0,
            'concept'   => isset( $_POST['payment_concept'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_concept'] ) ) : '',
            'type'      => ! empty( $_POST['payment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_type'] ) ) : 'income',
            'category'  => ! empty( $_POST['payment_category'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_category'] ) ) : 'client_payment',
            'recipient' => isset( $_POST['payment_recipient'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_recipient'] ) ) : '',
            'method'    => ! empty( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '',
            'notes'     => isset( $_POST['payment_notes'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_notes'] ) ) : '',
            'visible'   => isset( $_POST['payment_visible'] ) ? intval( $_POST['payment_visible'] ) : 1,
          );
          if ( $payment_data['amount'] <= 0 ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'Amount must be greater than zero.', 'pn-customers-manager' ) ) );
          }
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::add_payment( $invoice_id, $payment_data, 'invoice' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::maybe_mark_as_paid( $invoice_id, 'invoice' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::sync_payments( $invoice_id, 'invoice' );
          wp_send_json( array( 'error_key' => '', 'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_payments_block( $invoice_id, 'invoice' ) ) );
          break;

        case 'pn_cm_invoice_remove_payment':
          if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pn_cm_manage_crm' ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'You do not have permission.', 'pn-customers-manager' ) ) );
          }
          $invoice_id = ! empty( $_POST['invoice_id'] ) ? intval( $_POST['invoice_id'] ) : 0;
          $payment_id = ! empty( $_POST['payment_id'] ) ? intval( $_POST['payment_id'] ) : 0;
          if ( empty( $invoice_id ) || empty( $payment_id ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error' ) );
          }
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::remove_payment( $invoice_id, $payment_id, 'invoice' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::maybe_mark_as_paid( $invoice_id, 'invoice' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::sync_payments( $invoice_id, 'invoice' );
          wp_send_json( array( 'error_key' => '', 'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_payments_block( $invoice_id, 'invoice' ) ) );
          break;

        case 'pn_cm_invoice_toggle_payment_visibility':
          if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pn_cm_manage_crm' ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'You do not have permission.', 'pn-customers-manager' ) ) );
          }
          $invoice_id = ! empty( $_POST['invoice_id'] ) ? intval( $_POST['invoice_id'] ) : 0;
          $payment_id = ! empty( $_POST['payment_id'] ) ? intval( $_POST['payment_id'] ) : 0;
          if ( empty( $invoice_id ) || empty( $payment_id ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error' ) );
          }
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::toggle_payment_visibility( $invoice_id, $payment_id, 'invoice' );
          wp_send_json( array( 'error_key' => '', 'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_payments_block( $invoice_id, 'invoice' ) ) );
          break;

        case 'pn_cm_invoice_edit_payment':
          if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pn_cm_manage_crm' ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'You do not have permission.', 'pn-customers-manager' ) ) );
          }
          $invoice_id = ! empty( $_POST['invoice_id'] ) ? intval( $_POST['invoice_id'] ) : 0;
          $payment_id = ! empty( $_POST['payment_id'] ) ? intval( $_POST['payment_id'] ) : 0;
          if ( empty( $invoice_id ) || empty( $payment_id ) ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error' ) );
          }
          $payment_data = array(
            'date'      => ! empty( $_POST['payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) : gmdate( 'Y-m-d' ),
            'amount'    => ! empty( $_POST['payment_amount'] ) ? floatval( $_POST['payment_amount'] ) : 0,
            'concept'   => isset( $_POST['payment_concept'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_concept'] ) ) : '',
            'type'      => ! empty( $_POST['payment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_type'] ) ) : 'income',
            'category'  => ! empty( $_POST['payment_category'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_category'] ) ) : 'client_payment',
            'recipient' => isset( $_POST['payment_recipient'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_recipient'] ) ) : '',
            'method'    => ! empty( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '',
            'notes'     => isset( $_POST['payment_notes'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_notes'] ) ) : '',
            'visible'   => isset( $_POST['payment_visible'] ) ? intval( $_POST['payment_visible'] ) : 0,
          );
          if ( $payment_data['amount'] <= 0 ) {
            wp_send_json( array( 'error_key' => 'pn_cm_payment_error', 'error_content' => esc_html__( 'Amount must be greater than zero.', 'pn-customers-manager' ) ) );
          }
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::update_payment( $invoice_id, $payment_id, $payment_data, 'invoice' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::maybe_mark_as_paid( $invoice_id, 'invoice' );
          PN_CUSTOMERS_MANAGER_Post_Type_Budget::sync_payments( $invoice_id, 'invoice' );
          wp_send_json( array( 'error_key' => '', 'html' => PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_payments_block( $invoice_id, 'invoice' ) ) );
          break;
      }

      echo wp_json_encode([
        'error_key' => 'pn_customers_manager_save_error',
      ]);

      exit;
    }
  }
}
