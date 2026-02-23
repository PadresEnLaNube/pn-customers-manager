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
      if (!current_user_can('edit_pn_cm_funnel') && !current_user_can('edit_pn_cm_organization')) {
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
        case 'pn_cm_create_organization_page':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_create_organization_page_error',
              'error_content' => esc_html(__('You do not have permission to perform this action.', 'pn-customers-manager')),
            ]);
            exit;
          }

          $existing_page = PN_CUSTOMERS_MANAGER_Settings::pn_customers_manager_find_organization_page();

          if ($existing_page) {
            echo wp_json_encode([
              'error_key' => '',
              'redirect_url' => get_edit_post_link($existing_page, 'raw'),
            ]);
            exit;
          }

          $page_id = wp_insert_post([
            'post_title'   => __('Organizations', 'pn-customers-manager'),
            'post_content' => '<!-- wp:pn-customers-manager/organization-list /-->',
            'post_status'  => 'draft',
            'post_type'    => 'page',
          ]);

          if (is_wp_error($page_id)) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_create_organization_page_error',
              'error_content' => esc_html($page_id->get_error_message()),
            ]);
            exit;
          }

          echo wp_json_encode([
            'error_key'    => '',
            'redirect_url' => get_edit_post_link($page_id, 'raw'),
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
              'error_content' => esc_html__('Debes iniciar sesion.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $result = PN_CUSTOMERS_MANAGER_Commercial::handle_commercial_application();
          echo wp_json_encode($result);
          exit;
          break;
        case 'pn_cm_commercial_approve':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_commercial_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta accion.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $commercial_user_id = isset($_POST['pn_cm_commercial_user_id']) ? absint($_POST['pn_cm_commercial_user_id']) : 0;
          $result = PN_CUSTOMERS_MANAGER_Commercial::approve_commercial($commercial_user_id);
          echo wp_json_encode($result);
          exit;
          break;
        case 'pn_cm_commercial_reject':
          if (!current_user_can('manage_options')) {
            echo wp_json_encode([
              'error_key' => 'pn_cm_commercial_permission_error',
              'error_content' => esc_html__('No tienes permiso para realizar esta accion.', 'pn-customers-manager'),
            ]);
            exit;
          }

          $commercial_user_id = isset($_POST['pn_cm_commercial_user_id']) ? absint($_POST['pn_cm_commercial_user_id']) : 0;
          $result = PN_CUSTOMERS_MANAGER_Commercial::reject_commercial($commercial_user_id);
          echo wp_json_encode($result);
          exit;
          break;
        case 'pn_cm_contact_mark_read':
          if (!current_user_can('manage_options')) {
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
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => 'pn_cm_contact_mark_read_error']);
          exit;
          break;
        case 'pn_cm_contact_delete':
          if (!current_user_can('manage_options')) {
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
            ]);
            exit;
          }

          echo wp_json_encode(['error_key' => 'pn_cm_contact_delete_error']);
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