<?php
/**
 * Funnel creator.
 *
 * This class defines Funnel options, menus and templates.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Post_Type_Funnel {
  public function pn_cm_funnel_get_fields($funnel_id = 0) {
    $pn_customers_manager_fields = [];
      $pn_customers_manager_fields['pn_cm_funnel_title'] = [
        'id' => 'pn_cm_funnel_title',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'required' => true,
        'value' => !empty($funnel_id) ? esc_html(get_the_title($funnel_id)) : '',
        'label' => __('Funnel title', 'pn-customers-manager'),
        'placeholder' => __('Funnel title', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields['pn_cm_funnel_description'] = [
        'id' => 'pn_cm_funnel_description',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'textarea',
        'required' => true,
        'value' => !empty($funnel_id) ? (str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($funnel_id)->post_content))) : '',
        'label' => __('Funnel description', 'pn-customers-manager'),
        'placeholder' => __('Funnel description', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['PN_CUSTOMERS_MANAGER_ajax_nonce'] = [
        'id' => 'PN_CUSTOMERS_MANAGER_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $pn_customers_manager_fields;
  }

  public function pn_cm_funnel_get_fields_meta() {
    $pn_customers_manager_fields_meta = [];
      $pn_customers_manager_fields_meta['pn_cm_funnel_date'] = [
        'id' => 'pn_cm_funnel_date',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => __('Funnel date', 'pn-customers-manager'),
        'placeholder' => __('Funnel date', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_funnel_time'] = [
        'id' => 'pn_cm_funnel_time',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'time',
        'label' => __('Funnel time', 'pn-customers-manager'),
        'placeholder' => __('Funnel time', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_funnel_multimedia'] = [
        'id' => 'pn_cm_funnel_multimedia',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'checkbox',
        'parent' => 'this',
        'label' => __('Funnel multimedia content', 'pn-customers-manager'),
        'placeholder' => __('Funnel multimedia content', 'pn-customers-manager'),
      ]; 
        $pn_customers_manager_fields_meta['pn_cm_funnel_url'] = [
          'id' => 'pn_cm_funnel_url',
          'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'pn_cm_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel url', 'pn-customers-manager'),
          'placeholder' => __('Funnel url', 'pn-customers-manager'),
        ];
        $pn_customers_manager_fields_meta['pn_cm_funnel_url_audio'] = [
          'id' => 'pn_cm_funnel_url_audio',
          'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'pn_cm_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel audio url', 'pn-customers-manager'),
          'placeholder' => __('Funnel audio url', 'pn-customers-manager'),
        ];
        $pn_customers_manager_fields_meta['pn_cm_funnel_url_video'] = [
          'id' => 'pn_cm_funnel_url_video',
          'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'pn_cm_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel video url', 'pn-customers-manager'),
          'placeholder' => __('Funnel video url', 'pn-customers-manager'),
        ];
      $pn_customers_manager_fields_meta['pn_cm_funnel_form'] = [
        'id' => 'pn_cm_funnel_form',
        'input' => 'input',
        'type' => 'hidden',
      ];
      $pn_customers_manager_fields_meta['PN_CUSTOMERS_MANAGER_ajax_nonce'] = [
        'id' => 'PN_CUSTOMERS_MANAGER_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $pn_customers_manager_fields_meta;
  }

  /**
   * Register Funnel.
   *
   * @since    1.0.0
   */
  public function pn_cm_funnel_register_post_type() {
    $labels = [
      'name'                => _x('Funnel', 'Post Type general name', 'pn-customers-manager'),
      'singular_name'       => _x('Funnel', 'Post Type singular name', 'pn-customers-manager'),
      'menu_name'           => esc_html(__('Funnels', 'pn-customers-manager')),
      'parent_item_colon'   => esc_html(__('Parent Funnel', 'pn-customers-manager')),
      'all_items'           => esc_html(__('All Funnels', 'pn-customers-manager')),
      'view_item'           => esc_html(__('View Funnel', 'pn-customers-manager')),
      'add_new_item'        => esc_html(__('Add new Funnel', 'pn-customers-manager')),
      'add_new'             => esc_html(__('Add new Funnel', 'pn-customers-manager')),
      'edit_item'           => esc_html(__('Edit Funnel', 'pn-customers-manager')),
      'update_item'         => esc_html(__('Update Funnel', 'pn-customers-manager')),
      'search_items'        => esc_html(__('Search Funnels', 'pn-customers-manager')),
      'not_found'           => esc_html(__('Not Funnel found', 'pn-customers-manager')),
      'not_found_in_trash'  => esc_html(__('Not Funnel found in Trash', 'pn-customers-manager')),
    ];

    $args = [
      'labels'              => $labels,
      'rewrite'             => ['slug' => (!empty(get_option('PN_CUSTOMERS_MANAGER_pn_cm_funnel_slug')) ? get_option('PN_CUSTOMERS_MANAGER_pn_cm_funnel_slug') : 'pn-customers-manager'), 'with_front' => false],
      'label'               => esc_html(__('Funnels', 'pn-customers-manager')),
      'description'         => esc_html(__('Funnel description', 'pn-customers-manager')),
      'supports'            => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'page-attributes', ],
      'hierarchical'        => true,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => false, // Menu added manually in settings
      'show_in_nav_menus'   => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 5,
      'menu_icon'           => esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/pn-customers-manager-funnel-menu-icon.svg'),
      'can_export'          => true,
      'has_archive'         => false,
      'exclude_from_search' => true,
      'publicly_queryable'  => false,
      'capability_type'     => 'page',
      'capabilities'        => PN_CUSTOMERS_MANAGER_ROLE_PN_CM_FUNNEL_CAPABILITIES,
      'taxonomies'          => ['pn_cm_funnel_category'],
      'show_in_rest'        => true, /* REST API */
    ];

    register_post_type('pn_cm_funnel', $args);
    add_theme_support('post-thumbnails', ['page', 'pn_cm_funnel']);
  }

  /**
   * Add Funnel dashboard metabox.
   *
   * @since    1.0.0
   */
  public function pn_cm_funnel_add_meta_box() {
    add_meta_box('PN_CUSTOMERS_MANAGER_meta_box', esc_html(__('Funnel details', 'pn-customers-manager')), [$this, 'pn_cm_funnel_meta_box_function'], 'pn_cm_funnel', 'normal', 'high', ['__block_editor_compatible_meta_box' => true,]);
  }

  /**
   * Defines Funnel dashboard contents.
   *
   * @since    1.0.0
   */
  public function pn_cm_funnel_meta_box_function($post) {
    foreach (self::pn_cm_funnel_get_fields_meta() as $pn_customers_manager_field) {
      if (!is_null(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $post->ID))) {
        echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $post->ID), PN_CUSTOMERS_MANAGER_KSES);
      }
    }
  }

  /**
   * Defines single template for Funnel.
   *
   * @since    1.0.0
   */
  public function pn_cm_funnel_single_template($single) {
    if (get_post_type() == 'pn_cm_funnel') {
      if (file_exists(PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-pn_cm_funnel.php')) {
        return PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-pn_cm_funnel.php';
      }
    }

    return $single;
  }

  /**
   * Defines archive template for Funnel.
   *
   * @since    1.0.0
   */
  public function pn_cm_funnel_archive_template($archive) {
    if (get_post_type() == 'pn_cm_funnel') {
      if (file_exists(PN_CUSTOMERS_MANAGER_DIR . 'templates/public/archive-pn_cm_funnel.php')) {
        return PN_CUSTOMERS_MANAGER_DIR . 'templates/public/archive-pn_cm_funnel.php';
      }
    }

    return $archive;
  }

  public function pn_cm_funnel_save_post($post_id, $cpt, $update) {
    if($cpt->post_type == 'pn_cm_funnel' && array_key_exists('pn_cm_funnel_form', $_POST)){
      // Always require nonce verification
      if (!array_key_exists('PN_CUSTOMERS_MANAGER_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'PN_CUSTOMERS_MANAGER_nonce_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'pn-customers-manager')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['PN_CUSTOMERS_MANAGER_ajax_nonce'])), 'pn-customers-manager-nonce')) {
        echo wp_json_encode([
          'error_key' => 'PN_CUSTOMERS_MANAGER_nonce_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'pn-customers-manager')),
        ]);

        exit;
      }

      if (!array_key_exists('PN_CUSTOMERS_MANAGER_duplicate', $_POST)) {
        foreach (array_merge(self::pn_cm_funnel_get_fields(), self::pn_cm_funnel_get_fields_meta()) as $pn_customers_manager_field) {
          $pn_customers_manager_input = array_key_exists('input', $pn_customers_manager_field) ? $pn_customers_manager_field['input'] : '';

          if (array_key_exists($pn_customers_manager_field['id'], $_POST) || $pn_customers_manager_input == 'html_multi') {
            $pn_customers_manager_value = array_key_exists($pn_customers_manager_field['id'], $_POST) ? 
              PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                wp_unslash($_POST[$pn_customers_manager_field['id']]),
                $pn_customers_manager_field['input'], 
                !empty($pn_customers_manager_field['type']) ? $pn_customers_manager_field['type'] : '',
                $pn_customers_manager_field // Pass the entire field config
              ) : '';

            if (!empty($pn_customers_manager_input)) {
              switch ($pn_customers_manager_input) {
                case 'input':
                  if (array_key_exists('type', $pn_customers_manager_field) && $pn_customers_manager_field['type'] == 'checkbox') {
                    if (isset($_POST[$pn_customers_manager_field['id']])) {
                      update_post_meta($post_id, $pn_customers_manager_field['id'], $pn_customers_manager_value);
                    } else {
                      update_post_meta($post_id, $pn_customers_manager_field['id'], '');
                    }
                  } else {
                    update_post_meta($post_id, $pn_customers_manager_field['id'], $pn_customers_manager_value);
                  }

                  break;
                case 'select':
                  if (array_key_exists('multiple', $pn_customers_manager_field) && $pn_customers_manager_field['multiple']) {
                    $multi_array = [];
                    $empty = true;

                    foreach (wp_unslash($_POST[$pn_customers_manager_field['id']]) as $multi_value) {
                      $multi_array[] = PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                        $multi_value, 
                        $pn_customers_manager_field['input'], 
                        !empty($pn_customers_manager_field['type']) ? $pn_customers_manager_field['type'] : '',
                        $pn_customers_manager_field // Pass the entire field config
                      );
                    }

                    update_post_meta($post_id, $pn_customers_manager_field['id'], $multi_array);
                  } else {
                    update_post_meta($post_id, $pn_customers_manager_field['id'], $pn_customers_manager_value);
                  }
                  
                  break;
                case 'html_multi':
                  foreach ($pn_customers_manager_field['html_multi_fields'] as $pn_customers_manager_multi_field) {
                    if (array_key_exists($pn_customers_manager_multi_field['id'], $_POST)) {
                      $multi_array = [];
                      $empty = true;

                      // Sanitize the POST data before using it
                      $sanitized_post_data = isset($_POST[$pn_customers_manager_multi_field['id']]) ? 
                        array_map(function($value) {
                            return sanitize_text_field(wp_unslash($value));
                        }, (array)$_POST[$pn_customers_manager_multi_field['id']]) : [];
                      
                      foreach ($sanitized_post_data as $multi_value) {
                        if (!empty($multi_value)) {
                          $empty = false;
                        }

                        $multi_array[] = PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                          $multi_value, 
                          $pn_customers_manager_multi_field['input'], 
                          !empty($pn_customers_manager_multi_field['type']) ? $pn_customers_manager_multi_field['type'] : '',
                          $pn_customers_manager_multi_field // Pass the entire field config
                        );
                      }

                      if (!$empty) {
                        update_post_meta($post_id, $pn_customers_manager_multi_field['id'], $multi_array);
                      } else {
                        update_post_meta($post_id, $pn_customers_manager_multi_field['id'], '');
                      }
                    }
                  }

                  break;
                case 'tags':
                  // Handle tags field - save as array
                  $tags_array_field_name = $pn_customers_manager_field['id'] . '_tags_array';
                  if (array_key_exists($tags_array_field_name, $_POST)) {
                    $tags_json = PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                      wp_unslash($_POST[$tags_array_field_name]),
                      'input',
                      'text',
                      $pn_customers_manager_field
                    );
                    
                    // Decode JSON and save as array
                    $tags_array = json_decode($tags_json, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($tags_array)) {
                      update_post_meta($post_id, $pn_customers_manager_field['id'], $tags_array);
                    } else {
                      // Fallback: treat as comma-separated string
                      $tags_string = PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                        wp_unslash($_POST[$pn_customers_manager_field['id']]),
                        'input',
                        'text',
                        $pn_customers_manager_field
                      );
                      $tags_array = array_map('trim', explode(',', $tags_string));
                      $tags_array = array_filter($tags_array); // Remove empty values
                      update_post_meta($post_id, $pn_customers_manager_field['id'], $tags_array);
                    }
                  } else {
                    // Fallback: save the text input value as comma-separated array
                    $tags_string = PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                      wp_unslash($_POST[$pn_customers_manager_field['id']]),
                      'input',
                      'text',
                      $pn_customers_manager_field
                    );
                    $tags_array = array_map('trim', explode(',', $tags_string));
                    $tags_array = array_filter($tags_array); // Remove empty values
                    update_post_meta($post_id, $pn_customers_manager_field['id'], $tags_array);
                  }
                  break;
                default:
                  update_post_meta($post_id, $pn_customers_manager_field['id'], $pn_customers_manager_value);
                  break;
              }
            }
          } else {
            update_post_meta($post_id, $pn_customers_manager_field['id'], '');
          }
        }
      }
    }
  }

  public function pn_cm_funnel_form_save($element_id, $key_value, $cm_pn_form_type, $cm_pn_form_subtype) {
    $post_type = !empty(get_post_type($element_id)) ? get_post_type($element_id) : 'pn_cm_funnel';

    if ($post_type == 'pn_cm_funnel') {
      switch ($cm_pn_form_type) {
        case 'post':
          switch ($cm_pn_form_subtype) {
            case 'post_new':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'PN_CUSTOMERS_MANAGER_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();
              $funnel_id = $post_functions->pn_customers_manager_insert_post(esc_html($pn_cm_funnel_title), $pn_cm_funnel_description, '', sanitize_title(esc_html($pn_cm_funnel_title)), 'pn_cm_funnel', 'publish', get_current_user_id());

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($funnel_id, $key, $value);
                }
              }

              break;
            case 'post_edit':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'PN_CUSTOMERS_MANAGER_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $funnel_id = $element_id;
              wp_update_post(['ID' => $funnel_id, 'post_title' => $pn_cm_funnel_title, 'post_content' => $pn_cm_funnel_description,]);

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($funnel_id, $key, $value);
                }
              }

              break;
          }
      }
    }
  }

  public function pn_cm_funnel_register_scripts() {
    if (!wp_script_is('pn-customers-manager-aux', 'registered')) {
      wp_register_script('pn-customers-manager-aux', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-aux.js', [], PN_CUSTOMERS_MANAGER_VERSION, true);
    }

    if (!wp_script_is('pn-customers-manager-forms', 'registered')) {
      wp_register_script('pn-customers-manager-forms', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-forms.js', [], PN_CUSTOMERS_MANAGER_VERSION, true);
    }
    
    if (!wp_script_is('pn-customers-manager-selector', 'registered')) {
      wp_register_script('pn-customers-manager-selector', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-selector.js', [], PN_CUSTOMERS_MANAGER_VERSION, true);
    }
  }

  public function pn_cm_funnel_print_scripts() {
    wp_print_scripts(['pn-customers-manager-aux', 'pn-customers-manager-forms', 'pn-customers-manager-selector']);
  }

  public function pn_cm_funnel_list_wrapper() {
    // If user is not logged in, return only the call to action (no search/add buttons)
    if (!is_user_logged_in()) {
      return self::pn_cm_funnel_list();
    }

    ob_start();
    ?>
      <div class="pn-customers-manager-cpt-list pn-customers-manager-pn_cm_funnel-list pn-customers-manager-mb-50">
        <div class="pn-customers-manager-cpt-search-container pn-customers-manager-mb-20 pn-customers-manager-text-align-right">
          <div class="pn-customers-manager-cpt-search-wrapper">
            <input type="text" class="pn-customers-manager-cpt-search-input pn-customers-manager-input pn-customers-manager-display-none" placeholder="<?php esc_attr_e('Filter...', 'pn-customers-manager'); ?>" />
            <i class="material-icons-outlined pn-customers-manager-cpt-search-toggle pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Search Funnels', 'pn-customers-manager'); ?>">search</i>
            
            <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_funnel-add" data-pn-customers-manager-ajax-type="pn_cm_funnel_new">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Add new Funnel', 'pn-customers-manager'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="pn-customers-manager-cpt-list-wrapper pn-customers-manager-pn_cm_funnel-list-wrapper">
          <?php echo wp_kses(self::pn_cm_funnel_list(), PN_CUSTOMERS_MANAGER_KSES); ?>
        </div>
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
  }

  public function pn_cm_funnel_list() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
      // Check if userspn plugin is active
      $userspn_active = false;
      
      // First, try to check if plugin.php is available and use is_plugin_active
      if (!function_exists('is_plugin_active') && file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
      }
      
      if (function_exists('is_plugin_active')) {
        $userspn_active = is_plugin_active('userspn/userspn.php') || is_plugin_active('users-pn/users-pn.php');
      }
      
      // Fallback: check if userspn class or function exists
      if (!$userspn_active) {
        $userspn_active = class_exists('USERSPN') || class_exists('USERS_PN') || function_exists('userspn_profile_popup');
      }

      // Prepare call to action parameters
      $cta_atts = [
        'PN_CUSTOMERS_MANAGER_call_to_action_class' => 'pn-customers-manager-p-50 pn-customers-manager-pt-30 pn-customers-manager-max-width-700 pn-customers-manager-margin-auto',
        'PN_CUSTOMERS_MANAGER_call_to_action_icon' => 'admin_panel_settings',
        'PN_CUSTOMERS_MANAGER_call_to_action_title' => __('You need an account', 'pn-customers-manager'),
        'PN_CUSTOMERS_MANAGER_call_to_action_content' => __('You must be registered on the platform to access this tool.', 'pn-customers-manager'),
        'PN_CUSTOMERS_MANAGER_call_to_action_button_text' => __('Create an account', 'pn-customers-manager'),
      ];

      if ($userspn_active) {
        // If userspn is active, use popup button
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_link'] = '#';
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_class'] = 'userspn-profile-popup-btn';
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_data_key'] = 'data-userspn-action';
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_data_value'] = 'register';
      } else {
        // If userspn is not active, use WordPress registration URL
        if (function_exists('wp_registration_url')) {
          $registration_url = wp_registration_url();
        } else {
          $registration_url = wp_login_url();
          $registration_url = add_query_arg('action', 'register', $registration_url);
        }
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_link'] = $registration_url;
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_class'] = '';
      }

      // Build shortcode attributes string
      $shortcode_atts = '';
      foreach ($cta_atts as $key => $value) {
        $shortcode_atts .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
      }

      // Return call to action
      return do_shortcode('[pn-customers-manager-call-to-action' . $shortcode_atts . ']');
    }

    $funnel_atts = [
      'fields' => 'ids',
      'numberposts' => -1,
      'post_type' => 'pn_cm_funnel',
      'post_status' => 'any', 
      'orderby' => 'menu_order', 
      'order' => 'ASC', 
    ];
    
    if (class_exists('Polylang')) {
      $funnel_atts['lang'] = pll_current_language('slug');
    }

    $funnel = get_posts($funnel_atts);

    // Filter assets based on user permissions
    $funnel = PN_CUSTOMERS_MANAGER_Functions_User::PN_CUSTOMERS_MANAGER_filter_user_posts($funnel, 'pn_cm_funnel');

    ob_start();
    ?>
      <ul class="pn-customers-manager-funnels pn-customers-manager-list-style-none pn-customers-manager-p-0 pn-customers-manager-margin-auto">
        <?php if (!empty($funnel)): ?>
          <?php foreach ($funnel as $funnel_id): ?>
            <li class="pn-customers-manager-funnel pn-customers-manager-pn_cm_funnel-list-item pn-customers-manager-mb-10" data-pn_cm_funnel-id="<?php echo esc_attr($funnel_id); ?>">
              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-60-percent">
                  <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_funnel-view" data-pn-customers-manager-ajax-type="pn_cm_funnel_view">
                    <span><?php echo esc_html(get_the_title($funnel_id)); ?></span>
                  </a>
                </div>

                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right pn-customers-manager-position-relative">
                  <i class="material-icons-outlined pn-customers-manager-menu-more-btn pn-customers-manager-cursor-pointer pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30">more_vert</i>

                  <div class="pn-customers-manager-menu-more pn-customers-manager-z-index-99 pn-customers-manager-display-none-soft">
                    <ul class="pn-customers-manager-list-style-none">
                      <li>
                        <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_funnel-view" data-pn-customers-manager-ajax-type="pn_cm_funnel_view">
                          <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                              <p><?php esc_html_e('View Funnel', 'pn-customers-manager'); ?></p>
                            </div>
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">visibility</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_funnel-edit" data-pn-customers-manager-ajax-type="pn_cm_funnel_edit"> 
                          <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                              <p><?php esc_html_e('Edit Funnel', 'pn-customers-manager'); ?></p>
                            </div>
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">edit</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="pn-customers-manager-pn_cm_funnel-duplicate-post">
                          <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                              <p><?php esc_html_e('Duplicate Funnel', 'pn-customers-manager'); ?></p>
                            </div>
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">copy</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="pn-customers-manager-popup-open" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_funnel-remove">
                          <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                              <p><?php esc_html_e('Remove Funnel', 'pn-customers-manager'); ?></p>
                            </div>
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">delete</i>
                            </div>
                          </div>
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </li>
          <?php endforeach ?>
        <?php endif ?>

        <li class="pn-customers-manager-add-new-cpt pn-customers-manager-mt-50 pn-customers-manager-funnel" data-pn_cm_funnel-id="0">
          <?php if (is_user_logged_in()): ?>
            <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_funnel-add" data-pn-customers-manager-ajax-type="pn_cm_funnel_new">
              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center">
                  <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-width-25">add</i>
                </div>
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-80-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent">
                  <?php esc_html_e('Add new Funnel', 'pn-customers-manager'); ?>
                </div>
              </div>
            </a>
          <?php endif ?>
        </li>
      </ul>
    <?php
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
  }

  public function pn_cm_funnel_view($funnel_id) {  
    ob_start();
    self::pn_cm_funnel_register_scripts();
    self::pn_cm_funnel_print_scripts();
    ?>
      <div class="pn_cm_funnel-view pn-customers-manager-p-30" data-pn_cm_funnel-id="<?php echo esc_attr($funnel_id); ?>">
        <h4 class="pn-customers-manager-text-align-center"><?php echo esc_html(get_the_title($funnel_id)); ?></h4>
        
        <div class="pn-customers-manager-word-wrap-break-word">
          <p><?php echo wp_kses(str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($funnel_id)->post_content)), PN_CUSTOMERS_MANAGER_KSES); ?></p>
        </div>

        <div class="pn_cm_funnel-view-list">
          <?php foreach (array_merge(self::pn_cm_funnel_get_fields(), self::pn_cm_funnel_get_fields_meta()) as $pn_customers_manager_field): ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::PN_CUSTOMERS_MANAGER_input_display_wrapper($pn_customers_manager_field, 'post', $funnel_id), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right pn-customers-manager-funnel" data-pn_cm_funnel-id="<?php echo esc_attr($funnel_id); ?>">
            <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-popup-open-ajax" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_funnel-edit" data-pn-customers-manager-ajax-type="pn_cm_funnel_edit"><?php esc_html_e('Edit Funnel', 'pn-customers-manager'); ?></a>
          </div>
        </div>
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
  }

  public function pn_cm_funnel_new() {
    if (!is_user_logged_in()) {
      wp_die(esc_html__('You must be logged in to create a new asset.', 'pn-customers-manager'), esc_html__('Access Denied', 'pn-customers-manager'), ['response' => 403]);
    }

    ob_start();
    self::pn_cm_funnel_register_scripts();
    self::pn_cm_funnel_print_scripts();
    ?>
      <div class="pn_cm_funnel-new pn-customers-manager-p-30">
        <h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Add new Funnel', 'pn-customers-manager'); ?></h4>

        <form action="" method="post" id="pn-customers-manager-funnel-form-new" class="pn-customers-manager-form">      
          <?php foreach (array_merge(self::pn_cm_funnel_get_fields(), self::pn_cm_funnel_get_fields_meta()) as $pn_customers_manager_field): ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post'), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right">
            <input class="pn-customers-manager-btn" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_new" data-pn-customers-manager-post-type="pn_cm_funnel" type="submit" value="<?php esc_attr_e('Create Funnel', 'pn-customers-manager'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
  }

  public function pn_cm_funnel_edit($funnel_id) {
    ob_start();
    self::pn_cm_funnel_register_scripts();
    self::pn_cm_funnel_print_scripts();
    ?>
      <div class="pn_cm_funnel-edit pn-customers-manager-p-30">
        <p class="pn-customers-manager-text-align-center pn-customers-manager-mb-0"><?php esc_html_e('Editing', 'pn-customers-manager'); ?></p>
        <h4 class="pn-customers-manager-text-align-center pn-customers-manager-mb-30"><?php echo esc_html(get_the_title($funnel_id)); ?></h4>

        <form action="" method="post" id="pn-customers-manager-funnel-form-edit" class="pn-customers-manager-form">      
          <?php foreach (array_merge(self::pn_cm_funnel_get_fields(), self::pn_cm_funnel_get_fields_meta()) as $pn_customers_manager_field): ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $funnel_id), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right">
            <input class="pn-customers-manager-btn" type="submit" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_edit" data-pn-customers-manager-post-type="pn_cm_funnel" data-pn-customers-manager-post-id="<?php echo esc_attr($funnel_id); ?>" value="<?php esc_attr_e('Save Funnel', 'pn-customers-manager'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
  }

  public function pn_cm_funnel_history_add($funnel_id) {  
    $pn_customers_manager_meta = get_post_meta($funnel_id);
    $pn_customers_manager_meta_array = [];

    if (!empty($pn_customers_manager_meta)) {
      foreach ($pn_customers_manager_meta as $pn_customers_manager_meta_key => $pn_customers_manager_meta_value) {
        if (strpos((string)$pn_customers_manager_meta_key, 'PN_CUSTOMERS_MANAGER_') !== false && !empty($pn_customers_manager_meta_value[0])) {
          $pn_customers_manager_meta_array[$pn_customers_manager_meta_key] = $pn_customers_manager_meta_value[0];
        }
      }
    }
    
    if(empty(get_post_meta($funnel_id, 'pn_cm_funnel_history', true))) {
      update_post_meta($funnel_id, 'pn_cm_funnel_history', [strtotime('now') => $pn_customers_manager_meta_array]);
    } else {
      $pn_customers_manager_post_meta_new = get_post_meta($funnel_id, 'pn_cm_funnel_history', true);
      $pn_customers_manager_post_meta_new[strtotime('now')] = $pn_customers_manager_meta_array;
      update_post_meta($funnel_id, 'pn_cm_funnel_history', $pn_customers_manager_post_meta_new);
    }
  }

  public function pn_cm_funnel_get_next($funnel_id) {
    $pn_cm_funnel_periodicity = get_post_meta($funnel_id, 'pn_cm_funnel_periodicity', true);
    $pn_cm_funnel_date = get_post_meta($funnel_id, 'pn_cm_funnel_date', true);
    $pn_cm_funnel_time = get_post_meta($funnel_id, 'pn_cm_funnel_time', true);

    $pn_cm_funnel_timestamp = strtotime($pn_cm_funnel_date . ' ' . $pn_cm_funnel_time);

    if (!empty($pn_cm_funnel_periodicity) && !empty($pn_cm_funnel_timestamp)) {
      $now = strtotime('now');

      while ($pn_cm_funnel_timestamp < $now) {
        $pn_cm_funnel_timestamp = strtotime('+' . str_replace('_', ' ', $pn_cm_funnel_periodicity), $pn_cm_funnel_timestamp);
      }

      return $pn_cm_funnel_timestamp;
    }
  }

  public function pn_cm_funnel_owners($funnel_id) {
    $pn_customers_manager_owners = get_post_meta($funnel_id, 'PN_CUSTOMERS_MANAGER_owners', true);
    $pn_customers_manager_owners_array = [get_post($funnel_id)->post_author];

    if (!empty($pn_customers_manager_owners)) {
      foreach ($pn_customers_manager_owners as $owner_id) {
        $pn_customers_manager_owners_array[] = $owner_id;
      }
    }

    return array_unique($pn_customers_manager_owners_array);
  }
}