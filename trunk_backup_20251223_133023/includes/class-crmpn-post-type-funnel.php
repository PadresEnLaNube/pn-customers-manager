<?php
/**
 * Funnel creator.
 *
 * This class defines Funnel options, menus and templates.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Post_Type_Funnel {
  public function customers_manager_pn_funnel_get_fields($funnel_id = 0) {
    $customers_manager_pn_fields = [];
      $customers_manager_pn_fields['customers_manager_pn_funnel_title'] = [
        'id' => 'customers_manager_pn_funnel_title',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'required' => true,
        'value' => !empty($funnel_id) ? esc_html(get_the_title($funnel_id)) : '',
        'label' => __('Funnel title', 'customers-manager-pn'),
        'placeholder' => __('Funnel title', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields['customers_manager_pn_funnel_description'] = [
        'id' => 'customers_manager_pn_funnel_description',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'textarea',
        'required' => true,
        'value' => !empty($funnel_id) ? (str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($funnel_id)->post_content))) : '',
        'label' => __('Funnel description', 'customers-manager-pn'),
        'placeholder' => __('Funnel description', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['customers_manager_pn_ajax_nonce'] = [
        'id' => 'customers_manager_pn_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $customers_manager_pn_fields;
  }

  public function customers_manager_pn_funnel_get_fields_meta() {
    $customers_manager_pn_fields_meta = [];
      $customers_manager_pn_fields_meta['customers_manager_pn_funnel_date'] = [
        'id' => 'customers_manager_pn_funnel_date',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => __('Funnel date', 'customers-manager-pn'),
        'placeholder' => __('Funnel date', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['customers_manager_pn_funnel_time'] = [
        'id' => 'customers_manager_pn_funnel_time',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'time',
        'label' => __('Funnel time', 'customers-manager-pn'),
        'placeholder' => __('Funnel time', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['customers_manager_pn_funnel_multimedia'] = [
        'id' => 'customers_manager_pn_funnel_multimedia',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'checkbox',
        'parent' => 'this',
        'label' => __('Funnel multimedia content', 'customers-manager-pn'),
        'placeholder' => __('Funnel multimedia content', 'customers-manager-pn'),
      ]; 
        $customers_manager_pn_fields_meta['customers_manager_pn_funnel_url'] = [
          'id' => 'customers_manager_pn_funnel_url',
          'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'customers_manager_pn_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel url', 'customers-manager-pn'),
          'placeholder' => __('Funnel url', 'customers-manager-pn'),
        ];
        $customers_manager_pn_fields_meta['customers_manager_pn_funnel_url_audio'] = [
          'id' => 'customers_manager_pn_funnel_url_audio',
          'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'customers_manager_pn_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel audio url', 'customers-manager-pn'),
          'placeholder' => __('Funnel audio url', 'customers-manager-pn'),
        ];
        $customers_manager_pn_fields_meta['customers_manager_pn_funnel_url_video'] = [
          'id' => 'customers_manager_pn_funnel_url_video',
          'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'customers_manager_pn_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel video url', 'customers-manager-pn'),
          'placeholder' => __('Funnel video url', 'customers-manager-pn'),
        ];
      $customers_manager_pn_fields_meta['customers_manager_pn_funnel_form'] = [
        'id' => 'customers_manager_pn_funnel_form',
        'input' => 'input',
        'type' => 'hidden',
      ];
      $customers_manager_pn_fields_meta['customers_manager_pn_ajax_nonce'] = [
        'id' => 'customers_manager_pn_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $customers_manager_pn_fields_meta;
  }

  /**
   * Register Funnel.
   *
   * @since    1.0.0
   */
  public function customers_manager_pn_funnel_register_post_type() {
    $labels = [
      'name'                => _x('Funnel', 'Post Type general name', 'customers-manager-pn'),
      'singular_name'       => _x('Funnel', 'Post Type singular name', 'customers-manager-pn'),
      'menu_name'           => esc_html(__('Funnels', 'customers-manager-pn')),
      'parent_item_colon'   => esc_html(__('Parent Funnel', 'customers-manager-pn')),
      'all_items'           => esc_html(__('All Funnels', 'customers-manager-pn')),
      'view_item'           => esc_html(__('View Funnel', 'customers-manager-pn')),
      'add_new_item'        => esc_html(__('Add new Funnel', 'customers-manager-pn')),
      'add_new'             => esc_html(__('Add new Funnel', 'customers-manager-pn')),
      'edit_item'           => esc_html(__('Edit Funnel', 'customers-manager-pn')),
      'update_item'         => esc_html(__('Update Funnel', 'customers-manager-pn')),
      'search_items'        => esc_html(__('Search Funnels', 'customers-manager-pn')),
      'not_found'           => esc_html(__('Not Funnel found', 'customers-manager-pn')),
      'not_found_in_trash'  => esc_html(__('Not Funnel found in Trash', 'customers-manager-pn')),
    ];

    $args = [
      'labels'              => $labels,
      'rewrite'             => ['slug' => (!empty(get_option('customers_manager_pn_funnel_slug')) ? get_option('customers_manager_pn_funnel_slug') : 'customers-manager-pn'), 'with_front' => false],
      'label'               => esc_html(__('Funnels', 'customers-manager-pn')),
      'description'         => esc_html(__('Funnel description', 'customers-manager-pn')),
      'supports'            => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'page-attributes', ],
      'hierarchical'        => true,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => true,
      'show_in_nav_menus'   => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 5,
      'menu_icon'           => esc_url(CUSTOMERS_MANAGER_PN_URL . 'assets/media/customers-manager-pn-funnel-menu-icon.svg'),
      'can_export'          => true,
      'has_archive'         => true,
      'exclude_from_search' => false,
      'publicly_queryable'  => true,
      'capability_type'     => 'page',
      'capabilities'        => CUSTOMERS_MANAGER_PN_ROLE_CUSTOMERS_MANAGER_PN_FUNNEL_CAPABILITIES,
      'taxonomies'          => ['customers_manager_pn_funnel_category'],
      'show_in_rest'        => true, /* REST API */
    ];

    register_post_type('customers_manager_pn_funnel', $args);
    add_theme_support('post-thumbnails', ['page', 'customers_manager_pn_funnel']);
  }

  /**
   * Add Funnel dashboard metabox.
   *
   * @since    1.0.0
   */
  public function customers_manager_pn_funnel_add_meta_box() {
    add_meta_box('customers_manager_pn_meta_box', esc_html(__('Funnel details', 'customers-manager-pn')), [$this, 'customers_manager_pn_funnel_meta_box_function'], 'customers_manager_pn_funnel', 'normal', 'high', ['__block_editor_compatible_meta_box' => true,]);
  }

  /**
   * Defines Funnel dashboard contents.
   *
   * @since    1.0.0
   */
  public function customers_manager_pn_funnel_meta_box_function($post) {
    foreach (self::customers_manager_pn_funnel_get_fields_meta() as $customers_manager_pn_field) {
      if (!is_null(CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_field, 'post', $post->ID))) {
        echo wp_kses(CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_field, 'post', $post->ID), customers_manager_pn_KSES);
      }
    }
  }

  /**
   * Defines single template for Funnel.
   *
   * @since    1.0.0
   */
  public function customers_manager_pn_funnel_single_template($single) {
    if (get_post_type() == 'customers_manager_pn_funnel') {
      if (file_exists(CUSTOMERS_MANAGER_PN_DIR . 'templates/public/single-customers_manager_pn_funnel.php')) {
        return CUSTOMERS_MANAGER_PN_DIR . 'templates/public/single-customers_manager_pn_funnel.php';
      }
    }

    return $single;
  }

  /**
   * Defines archive template for Funnel.
   *
   * @since    1.0.0
   */
  public function customers_manager_pn_funnel_archive_template($archive) {
    if (get_post_type() == 'customers_manager_pn_funnel') {
      if (file_exists(CUSTOMERS_MANAGER_PN_DIR . 'templates/public/archive-customers_manager_pn_funnel.php')) {
        return CUSTOMERS_MANAGER_PN_DIR . 'templates/public/archive-customers_manager_pn_funnel.php';
      }
    }

    return $archive;
  }

  public function customers_manager_pn_funnel_save_post($post_id, $cpt, $update) {
    if($cpt->post_type == 'customers_manager_pn_funnel' && array_key_exists('customers_manager_pn_funnel_form', $_POST)){
      // Always require nonce verification
      if (!array_key_exists('customers_manager_pn_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'customers_manager_pn_nonce_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'customers-manager-pn')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['customers_manager_pn_ajax_nonce'])), 'customers-manager-pn-nonce')) {
        echo wp_json_encode([
          'error_key' => 'customers_manager_pn_nonce_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'customers-manager-pn')),
        ]);

        exit;
      }

      if (!array_key_exists('customers_manager_pn_duplicate', $_POST)) {
        foreach (array_merge(self::customers_manager_pn_funnel_get_fields(), self::customers_manager_pn_funnel_get_fields_meta()) as $customers_manager_pn_field) {
          $customers_manager_pn_input = array_key_exists('input', $customers_manager_pn_field) ? $customers_manager_pn_field['input'] : '';

          if (array_key_exists($customers_manager_pn_field['id'], $_POST) || $customers_manager_pn_input == 'html_multi') {
            $customers_manager_pn_value = array_key_exists($customers_manager_pn_field['id'], $_POST) ? 
              CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                wp_unslash($_POST[$customers_manager_pn_field['id']]),
                $customers_manager_pn_field['input'], 
                !empty($customers_manager_pn_field['type']) ? $customers_manager_pn_field['type'] : '',
                $customers_manager_pn_field // Pass the entire field config
              ) : '';

            if (!empty($customers_manager_pn_input)) {
              switch ($customers_manager_pn_input) {
                case 'input':
                  if (array_key_exists('type', $customers_manager_pn_field) && $customers_manager_pn_field['type'] == 'checkbox') {
                    if (isset($_POST[$customers_manager_pn_field['id']])) {
                      update_post_meta($post_id, $customers_manager_pn_field['id'], $customers_manager_pn_value);
                    } else {
                      update_post_meta($post_id, $customers_manager_pn_field['id'], '');
                    }
                  } else {
                    update_post_meta($post_id, $customers_manager_pn_field['id'], $customers_manager_pn_value);
                  }

                  break;
                case 'select':
                  if (array_key_exists('multiple', $customers_manager_pn_field) && $customers_manager_pn_field['multiple']) {
                    $multi_array = [];
                    $empty = true;

                    foreach (wp_unslash($_POST[$customers_manager_pn_field['id']]) as $multi_value) {
                      $multi_array[] = CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                        $multi_value, 
                        $customers_manager_pn_field['input'], 
                        !empty($customers_manager_pn_field['type']) ? $customers_manager_pn_field['type'] : '',
                        $customers_manager_pn_field // Pass the entire field config
                      );
                    }

                    update_post_meta($post_id, $customers_manager_pn_field['id'], $multi_array);
                  } else {
                    update_post_meta($post_id, $customers_manager_pn_field['id'], $customers_manager_pn_value);
                  }
                  
                  break;
                case 'html_multi':
                  foreach ($customers_manager_pn_field['html_multi_fields'] as $customers_manager_pn_multi_field) {
                    if (array_key_exists($customers_manager_pn_multi_field['id'], $_POST)) {
                      $multi_array = [];
                      $empty = true;

                      // Sanitize the POST data before using it
                      $sanitized_post_data = isset($_POST[$customers_manager_pn_multi_field['id']]) ? 
                        array_map(function($value) {
                            return sanitize_text_field(wp_unslash($value));
                        }, (array)$_POST[$customers_manager_pn_multi_field['id']]) : [];
                      
                      foreach ($sanitized_post_data as $multi_value) {
                        if (!empty($multi_value)) {
                          $empty = false;
                        }

                        $multi_array[] = CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                          $multi_value, 
                          $customers_manager_pn_multi_field['input'], 
                          !empty($customers_manager_pn_multi_field['type']) ? $customers_manager_pn_multi_field['type'] : '',
                          $customers_manager_pn_multi_field // Pass the entire field config
                        );
                      }

                      if (!$empty) {
                        update_post_meta($post_id, $customers_manager_pn_multi_field['id'], $multi_array);
                      } else {
                        update_post_meta($post_id, $customers_manager_pn_multi_field['id'], '');
                      }
                    }
                  }

                  break;
                case 'tags':
                  // Handle tags field - save as array
                  $tags_array_field_name = $customers_manager_pn_field['id'] . '_tags_array';
                  if (array_key_exists($tags_array_field_name, $_POST)) {
                    $tags_json = CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                      wp_unslash($_POST[$tags_array_field_name]),
                      'input',
                      'text',
                      $customers_manager_pn_field
                    );
                    
                    // Decode JSON and save as array
                    $tags_array = json_decode($tags_json, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($tags_array)) {
                      update_post_meta($post_id, $customers_manager_pn_field['id'], $tags_array);
                    } else {
                      // Fallback: treat as comma-separated string
                      $tags_string = CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                        wp_unslash($_POST[$customers_manager_pn_field['id']]),
                        'input',
                        'text',
                        $customers_manager_pn_field
                      );
                      $tags_array = array_map('trim', explode(',', $tags_string));
                      $tags_array = array_filter($tags_array); // Remove empty values
                      update_post_meta($post_id, $customers_manager_pn_field['id'], $tags_array);
                    }
                  } else {
                    // Fallback: save the text input value as comma-separated array
                    $tags_string = CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(
                      wp_unslash($_POST[$customers_manager_pn_field['id']]),
                      'input',
                      'text',
                      $customers_manager_pn_field
                    );
                    $tags_array = array_map('trim', explode(',', $tags_string));
                    $tags_array = array_filter($tags_array); // Remove empty values
                    update_post_meta($post_id, $customers_manager_pn_field['id'], $tags_array);
                  }
                  break;
                default:
                  update_post_meta($post_id, $customers_manager_pn_field['id'], $customers_manager_pn_value);
                  break;
              }
            }
          } else {
            update_post_meta($post_id, $customers_manager_pn_field['id'], '');
          }
        }
      }
    }
  }

  public function customers_manager_pn_funnel_form_save($element_id, $key_value, $customers_manager_pn_form_type, $customers_manager_pn_form_subtype) {
    $post_type = !empty(get_post_type($element_id)) ? get_post_type($element_id) : 'customers_manager_pn_funnel';

    if ($post_type == 'customers_manager_pn_funnel') {
      switch ($customers_manager_pn_form_type) {
        case 'post':
          switch ($customers_manager_pn_form_subtype) {
            case 'post_new':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'customers_manager_pn_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $post_functions = new CUSTOMERS_MANAGER_PN_Functions_Post();
              $funnel_id = $post_functions->customers_manager_pn_insert_post(esc_html($customers_manager_pn_funnel_title), $customers_manager_pn_funnel_description, '', sanitize_title(esc_html($customers_manager_pn_funnel_title)), 'customers_manager_pn_funnel', 'publish', get_current_user_id());

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($funnel_id, $key, $value);
                }
              }

              break;
            case 'post_edit':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'customers_manager_pn_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $funnel_id = $element_id;
              wp_update_post(['ID' => $funnel_id, 'post_title' => $customers_manager_pn_funnel_title, 'post_content' => $customers_manager_pn_funnel_description,]);

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

  public function customers_manager_pn_funnel_register_scripts() {
    if (!wp_script_is('customers-manager-pn-aux', 'registered')) {
      wp_register_script('customers-manager-pn-aux', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-aux.js', [], CUSTOMERS_MANAGER_PN_VERSION, true);
    }

    if (!wp_script_is('customers-manager-pn-forms', 'registered')) {
      wp_register_script('customers-manager-pn-forms', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-forms.js', [], CUSTOMERS_MANAGER_PN_VERSION, true);
    }
    
    if (!wp_script_is('customers-manager-pn-selector', 'registered')) {
      wp_register_script('customers-manager-pn-selector', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-selector.js', [], CUSTOMERS_MANAGER_PN_VERSION, true);
    }
  }

  public function customers_manager_pn_funnel_print_scripts() {
    wp_print_scripts(['customers-manager-pn-aux', 'customers-manager-pn-forms', 'customers-manager-pn-selector']);
  }

  public function customers_manager_pn_funnel_list_wrapper() {
    ob_start();
    ?>
      <div class="customers-manager-pn-cpt-list customers-manager-pn-customers_manager_pn_funnel-list customers-manager-pn-mb-50">
        <div class="customers-manager-pn-cpt-search-container customers-manager-pn-mb-20 customers-manager-pn-text-align-right">
          <div class="customers-manager-pn-cpt-search-wrapper">
            <input type="text" class="customers-manager-pn-cpt-search-input customers-manager-pn-input customers-manager-pn-display-none" placeholder="<?php esc_attr_e('Filter...', 'customers-manager-pn'); ?>" />
            <i class="material-icons-outlined customers-manager-pn-cpt-search-toggle customers-manager-pn-cursor-pointer customers-manager-pn-font-size-30 customers-manager-pn-vertical-align-middle customers-manager-pn-tooltip" title="<?php esc_attr_e('Search Funnels', 'customers-manager-pn'); ?>">search</i>
            
            <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-customers_manager_pn_funnel-add" data-customers-manager-pn-ajax-type="customers_manager_pn_funnel_new">
              <i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-font-size-30 customers-manager-pn-vertical-align-middle customers-manager-pn-tooltip" title="<?php esc_attr_e('Add new Funnel', 'customers-manager-pn'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="customers-manager-pn-cpt-list-wrapper customers-manager-pn-customers_manager_pn_funnel-list-wrapper">
          <?php echo wp_kses(self::customers_manager_pn_funnel_list(), CUSTOMERS_MANAGER_PN_KSES); ?>
        </div>
      </div>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function customers_manager_pn_funnel_list() {
    $funnel_atts = [
      'fields' => 'ids',
      'numberposts' => -1,
      'post_type' => 'customers_manager_pn_funnel',
      'post_status' => 'any', 
      'orderby' => 'menu_order', 
      'order' => 'ASC', 
    ];
    
    if (class_exists('Polylang')) {
      $funnel_atts['lang'] = pll_current_language('slug');
    }

    $funnel = get_posts($funnel_atts);

    // Filter assets based on user permissions
    $funnel = CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_filter_user_posts($funnel, 'customers_manager_pn_funnel');

    ob_start();
    ?>
      <ul class="customers-manager-pn-funnels customers-manager-pn-list-style-none customers-manager-pn-p-0 customers-manager-pn-margin-auto">
        <?php if (!empty($funnel)): ?>
          <?php foreach ($funnel as $funnel_id): ?>
            <li class="customers-manager-pn-funnel customers-manager-pn-customers_manager_pn_funnel-list-item customers-manager-pn-mb-10" data-customers_manager_pn_funnel-id="<?php echo esc_attr($funnel_id); ?>">
              <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-60-percent">
                  <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-customers_manager_pn_funnel-view" data-customers-manager-pn-ajax-type="customers_manager_pn_funnel_view">
                    <span><?php echo esc_html(get_the_title($funnel_id)); ?></span>
                  </a>
                </div>

                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right customers-manager-pn-position-relative">
                  <i class="material-icons-outlined customers-manager-pn-menu-more-btn customers-manager-pn-cursor-pointer customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30">more_vert</i>

                  <div class="customers-manager-pn-menu-more customers-manager-pn-z-index-99 customers-manager-pn-display-none-soft">
                    <ul class="customers-manager-pn-list-style-none">
                      <li>
                        <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-customers_manager_pn_funnel-view" data-customers-manager-pn-ajax-type="customers_manager_pn_funnel_view">
                          <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                              <p><?php esc_html_e('View Funnel', 'customers-manager-pn'); ?></p>
                            </div>
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right">
                              <i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-ml-30">visibility</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-customers_manager_pn_funnel-edit" data-customers-manager-pn-ajax-type="customers_manager_pn_funnel_edit"> 
                          <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                              <p><?php esc_html_e('Edit Funnel', 'customers-manager-pn'); ?></p>
                            </div>
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right">
                              <i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-ml-30">edit</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="customers-manager-pn-customers_manager_pn_funnel-duplicate-post">
                          <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                              <p><?php esc_html_e('Duplicate Funnel', 'customers-manager-pn'); ?></p>
                            </div>
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right">
                              <i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-ml-30">copy</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="customers-manager-pn-popup-open" data-customers-manager-pn-popup-id="customers-manager-pn-popup-customers_manager_pn_funnel-remove">
                          <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                              <p><?php esc_html_e('Remove Funnel', 'customers-manager-pn'); ?></p>
                            </div>
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right">
                              <i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-ml-30">delete</i>
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

        <li class="customers-manager-pn-add-new-cpt customers-manager-pn-mt-50 customers-manager-pn-funnel" data-customers_manager_pn_funnel-id="0">
          <?php if (is_user_logged_in()): ?>
            <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-customers_manager_pn_funnel-add" data-customers-manager-pn-ajax-type="customers_manager_pn_funnel_new">
              <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-center">
                  <i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-width-25">add</i>
                </div>
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-80-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent">
                  <?php esc_html_e('Add new Funnel', 'customers-manager-pn'); ?>
                </div>
              </div>
            </a>
          <?php endif ?>
        </li>
      </ul>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function customers_manager_pn_funnel_view($funnel_id) {  
    ob_start();
    self::customers_manager_pn_funnel_register_scripts();
    self::customers_manager_pn_funnel_print_scripts();
    ?>
      <div class="customers_manager_pn_funnel-view customers-manager-pn-p-30" data-customers_manager_pn_funnel-id="<?php echo esc_attr($funnel_id); ?>">
        <h4 class="customers-manager-pn-text-align-center"><?php echo esc_html(get_the_title($funnel_id)); ?></h4>
        
        <div class="customers-manager-pn-word-wrap-break-word">
          <p><?php echo wp_kses(str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($funnel_id)->post_content)), CUSTOMERS_MANAGER_PN_KSES); ?></p>
        </div>

        <div class="customers_manager_pn_funnel-view-list">
          <?php foreach (array_merge(self::customers_manager_pn_funnel_get_fields(), self::customers_manager_pn_funnel_get_fields_meta()) as $customers_manager_pn_field): ?>
            <?php echo wp_kses(CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_input_display_wrapper($customers_manager_pn_field, 'post', $funnel_id), CUSTOMERS_MANAGER_PN_KSES); ?>
          <?php endforeach ?>

          <div class="customers-manager-pn-text-align-right customers-manager-pn-funnel" data-customers_manager_pn_funnel-id="<?php echo esc_attr($funnel_id); ?>">
            <a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-popup-open-ajax" data-customers-manager-pn-popup-id="customers-manager-pn-popup-customers_manager_pn_funnel-edit" data-customers-manager-pn-ajax-type="customers_manager_pn_funnel_edit"><?php esc_html_e('Edit Funnel', 'customers-manager-pn'); ?></a>
          </div>
        </div>
      </div>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function customers_manager_pn_funnel_new() {
    if (!is_user_logged_in()) {
      wp_die(esc_html__('You must be logged in to create a new asset.', 'customers-manager-pn'), esc_html__('Access Denied', 'customers-manager-pn'), ['response' => 403]);
    }

    ob_start();
    self::customers_manager_pn_funnel_register_scripts();
    self::customers_manager_pn_funnel_print_scripts();
    ?>
      <div class="customers_manager_pn_funnel-new customers-manager-pn-p-30">
        <h4 class="customers-manager-pn-mb-30"><?php esc_html_e('Add new Funnel', 'customers-manager-pn'); ?></h4>

        <form action="" method="post" id="customers-manager-pn-funnel-form-new" class="customers-manager-pn-form">      
          <?php foreach (array_merge(self::customers_manager_pn_funnel_get_fields(), self::customers_manager_pn_funnel_get_fields_meta()) as $customers_manager_pn_field): ?>
            <?php echo wp_kses(CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_field, 'post'), CUSTOMERS_MANAGER_PN_KSES); ?>
          <?php endforeach ?>

          <div class="customers-manager-pn-text-align-right">
            <input class="customers-manager-pn-btn" data-customers-manager-pn-type="post" data-customers-manager-pn-subtype="post_new" data-customers-manager-pn-post-type="customers_manager_pn_funnel" type="submit" value="<?php esc_attr_e('Create Funnel', 'customers-manager-pn'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function customers_manager_pn_funnel_edit($funnel_id) {
    ob_start();
    self::customers_manager_pn_funnel_register_scripts();
    self::customers_manager_pn_funnel_print_scripts();
    ?>
      <div class="customers_manager_pn_funnel-edit customers-manager-pn-p-30">
        <p class="customers-manager-pn-text-align-center customers-manager-pn-mb-0"><?php esc_html_e('Editing', 'customers-manager-pn'); ?></p>
        <h4 class="customers-manager-pn-text-align-center customers-manager-pn-mb-30"><?php echo esc_html(get_the_title($funnel_id)); ?></h4>

        <form action="" method="post" id="customers-manager-pn-funnel-form-edit" class="customers-manager-pn-form">      
          <?php foreach (array_merge(self::customers_manager_pn_funnel_get_fields(), self::customers_manager_pn_funnel_get_fields_meta()) as $customers_manager_pn_field): ?>
            <?php echo wp_kses(CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_field, 'post', $funnel_id), CUSTOMERS_MANAGER_PN_KSES); ?>
          <?php endforeach ?>

          <div class="customers-manager-pn-text-align-right">
            <input class="customers-manager-pn-btn" type="submit" data-customers-manager-pn-type="post" data-customers-manager-pn-subtype="post_edit" data-customers-manager-pn-post-type="customers_manager_pn_funnel" data-customers-manager-pn-post-id="<?php echo esc_attr($funnel_id); ?>" value="<?php esc_attr_e('Save Funnel', 'customers-manager-pn'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function customers_manager_pn_funnel_history_add($funnel_id) {  
    $customers_manager_pn_meta = get_post_meta($funnel_id);
    $customers_manager_pn_meta_array = [];

    if (!empty($customers_manager_pn_meta)) {
      foreach ($customers_manager_pn_meta as $customers_manager_pn_meta_key => $customers_manager_pn_meta_value) {
        if (strpos((string)$customers_manager_pn_meta_key, 'customers_manager_pn_') !== false && !empty($customers_manager_pn_meta_value[0])) {
          $customers_manager_pn_meta_array[$customers_manager_pn_meta_key] = $customers_manager_pn_meta_value[0];
        }
      }
    }
    
    if(empty(get_post_meta($funnel_id, 'customers_manager_pn_funnel_history', true))) {
      update_post_meta($funnel_id, 'customers_manager_pn_funnel_history', [strtotime('now') => $customers_manager_pn_meta_array]);
    } else {
      $customers_manager_pn_post_meta_new = get_post_meta($funnel_id, 'customers_manager_pn_funnel_history', true);
      $customers_manager_pn_post_meta_new[strtotime('now')] = $customers_manager_pn_meta_array;
      update_post_meta($funnel_id, 'customers_manager_pn_funnel_history', $customers_manager_pn_post_meta_new);
    }
  }

  public function customers_manager_pn_funnel_get_next($funnel_id) {
    $customers_manager_pn_funnel_periodicity = get_post_meta($funnel_id, 'customers_manager_pn_funnel_periodicity', true);
    $customers_manager_pn_funnel_date = get_post_meta($funnel_id, 'customers_manager_pn_funnel_date', true);
    $customers_manager_pn_funnel_time = get_post_meta($funnel_id, 'customers_manager_pn_funnel_time', true);

    $customers_manager_pn_funnel_timestamp = strtotime($customers_manager_pn_funnel_date . ' ' . $customers_manager_pn_funnel_time);

    if (!empty($customers_manager_pn_funnel_periodicity) && !empty($customers_manager_pn_funnel_timestamp)) {
      $now = strtotime('now');

      while ($customers_manager_pn_funnel_timestamp < $now) {
        $customers_manager_pn_funnel_timestamp = strtotime('+' . str_replace('_', ' ', $customers_manager_pn_funnel_periodicity), $customers_manager_pn_funnel_timestamp);
      }

      return $customers_manager_pn_funnel_timestamp;
    }
  }

  public function customers_manager_pn_funnel_owners($funnel_id) {
    $customers_manager_pn_owners = get_post_meta($funnel_id, 'customers_manager_pn_owners', true);
    $customers_manager_pn_owners_array = [get_post($funnel_id)->post_author];

    if (!empty($customers_manager_pn_owners)) {
      foreach ($customers_manager_pn_owners as $owner_id) {
        $customers_manager_pn_owners_array[] = $owner_id;
      }
    }

    return array_unique($customers_manager_pn_owners_array);
  }
}