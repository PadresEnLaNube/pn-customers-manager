<?php
/**
 * Funnel creator.
 *
 * This class defines Funnel options, menus and templates.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Post_Type_Funnel {
  public function crmpn_funnel_get_fields($funnel_id = 0) {
    $crmpn_fields = [];
      $crmpn_fields['crmpn_funnel_title'] = [
        'id' => 'crmpn_funnel_title',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'required' => true,
        'value' => !empty($funnel_id) ? esc_html(get_the_title($funnel_id)) : '',
        'label' => __('Funnel title', 'crmpn'),
        'placeholder' => __('Funnel title', 'crmpn'),
      ];
      $crmpn_fields['crmpn_funnel_description'] = [
        'id' => 'crmpn_funnel_description',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'textarea',
        'required' => true,
        'value' => !empty($funnel_id) ? (str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($funnel_id)->post_content))) : '',
        'label' => __('Funnel description', 'crmpn'),
        'placeholder' => __('Funnel description', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_ajax_nonce'] = [
        'id' => 'crmpn_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $crmpn_fields;
  }

  public function crmpn_funnel_get_fields_meta() {
    $crmpn_fields_meta = [];
      $crmpn_fields_meta['crmpn_funnel_date'] = [
        'id' => 'crmpn_funnel_date',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => __('Funnel date', 'crmpn'),
        'placeholder' => __('Funnel date', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_funnel_time'] = [
        'id' => 'crmpn_funnel_time',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'time',
        'label' => __('Funnel time', 'crmpn'),
        'placeholder' => __('Funnel time', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_funnel_multimedia'] = [
        'id' => 'crmpn_funnel_multimedia',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'checkbox',
        'parent' => 'this',
        'label' => __('Funnel multimedia content', 'crmpn'),
        'placeholder' => __('Funnel multimedia content', 'crmpn'),
      ]; 
        $crmpn_fields_meta['crmpn_funnel_url'] = [
          'id' => 'crmpn_funnel_url',
          'class' => 'crmpn-input crmpn-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'crmpn_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel url', 'crmpn'),
          'placeholder' => __('Funnel url', 'crmpn'),
        ];
        $crmpn_fields_meta['crmpn_funnel_url_audio'] = [
          'id' => 'crmpn_funnel_url_audio',
          'class' => 'crmpn-input crmpn-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'crmpn_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel audio url', 'crmpn'),
          'placeholder' => __('Funnel audio url', 'crmpn'),
        ];
        $crmpn_fields_meta['crmpn_funnel_url_video'] = [
          'id' => 'crmpn_funnel_url_video',
          'class' => 'crmpn-input crmpn-width-100-percent',
          'input' => 'input',
          'type' => 'url',
          'parent' => 'crmpn_funnel_multimedia',
          'parent_option' => 'on',
          'label' => __('Funnel video url', 'crmpn'),
          'placeholder' => __('Funnel video url', 'crmpn'),
        ];
      $crmpn_fields_meta['crmpn_funnel_form'] = [
        'id' => 'crmpn_funnel_form',
        'input' => 'input',
        'type' => 'hidden',
      ];
      $crmpn_fields_meta['crmpn_ajax_nonce'] = [
        'id' => 'crmpn_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $crmpn_fields_meta;
  }

  /**
   * Register Funnel.
   *
   * @since    1.0.0
   */
  public function crmpn_funnel_register_post_type() {
    $labels = [
      'name'                => _x('Funnel', 'Post Type general name', 'crmpn'),
      'singular_name'       => _x('Funnel', 'Post Type singular name', 'crmpn'),
      'menu_name'           => esc_html(__('Funnels', 'crmpn')),
      'parent_item_colon'   => esc_html(__('Parent Funnel', 'crmpn')),
      'all_items'           => esc_html(__('All Funnels', 'crmpn')),
      'view_item'           => esc_html(__('View Funnel', 'crmpn')),
      'add_new_item'        => esc_html(__('Add new Funnel', 'crmpn')),
      'add_new'             => esc_html(__('Add new Funnel', 'crmpn')),
      'edit_item'           => esc_html(__('Edit Funnel', 'crmpn')),
      'update_item'         => esc_html(__('Update Funnel', 'crmpn')),
      'search_items'        => esc_html(__('Search Funnels', 'crmpn')),
      'not_found'           => esc_html(__('Not Funnel found', 'crmpn')),
      'not_found_in_trash'  => esc_html(__('Not Funnel found in Trash', 'crmpn')),
    ];

    $args = [
      'labels'              => $labels,
      'rewrite'             => ['slug' => (!empty(get_option('crmpn_funnel_slug')) ? get_option('crmpn_funnel_slug') : 'crmpn'), 'with_front' => false],
      'label'               => esc_html(__('Funnels', 'crmpn')),
      'description'         => esc_html(__('Funnel description', 'crmpn')),
      'supports'            => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'page-attributes', ],
      'hierarchical'        => true,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => true,
      'show_in_nav_menus'   => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 5,
      'menu_icon'           => esc_url(CRMPN_URL . 'assets/media/crmpn-funnel-menu-icon.svg'),
      'can_export'          => true,
      'has_archive'         => true,
      'exclude_from_search' => false,
      'publicly_queryable'  => true,
      'capability_type'     => 'page',
      'capabilities'        => CRMPN_ROLE_CRMPN_BASECPT_CAPABILITIES,
      'taxonomies'          => ['crmpn_funnel_category'],
      'show_in_rest'        => true, /* REST API */
    ];

    register_post_type('crmpn_funnel', $args);
    add_theme_support('post-thumbnails', ['page', 'crmpn_funnel']);
  }

  /**
   * Add Funnel dashboard metabox.
   *
   * @since    1.0.0
   */
  public function crmpn_funnel_add_meta_box() {
    add_meta_box('crmpn_meta_box', esc_html(__('Funnel details', 'crmpn')), [$this, 'crmpn_funnel_meta_box_function'], 'crmpn_funnel', 'normal', 'high', ['__block_editor_compatible_meta_box' => true,]);
  }

  /**
   * Defines Funnel dashboard contents.
   *
   * @since    1.0.0
   */
  public function crmpn_funnel_meta_box_function($post) {
    foreach (self::crmpn_funnel_get_fields_meta() as $crmpn_field) {
      if (!is_null(CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_field, 'post', $post->ID))) {
        echo wp_kses(CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_field, 'post', $post->ID), crmpn_KSES);
      }
    }
  }

  /**
   * Defines single template for Funnel.
   *
   * @since    1.0.0
   */
  public function crmpn_funnel_single_template($single) {
    if (get_post_type() == 'crmpn_funnel') {
      if (file_exists(CRMPN_DIR . 'templates/public/single-crmpn_funnel.php')) {
        return CRMPN_DIR . 'templates/public/single-crmpn_funnel.php';
      }
    }

    return $single;
  }

  /**
   * Defines archive template for Funnel.
   *
   * @since    1.0.0
   */
  public function crmpn_funnel_archive_template($archive) {
    if (get_post_type() == 'crmpn_funnel') {
      if (file_exists(CRMPN_DIR . 'templates/public/archive-crmpn_funnel.php')) {
        return CRMPN_DIR . 'templates/public/archive-crmpn_funnel.php';
      }
    }

    return $archive;
  }

  public function crmpn_funnel_save_post($post_id, $cpt, $update) {
    if($cpt->post_type == 'crmpn_funnel' && array_key_exists('crmpn_funnel_form', $_POST)){
      // Always require nonce verification
      if (!array_key_exists('crmpn_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'crmpn_nonce_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'crmpn')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['crmpn_ajax_nonce'])), 'crmpn-nonce')) {
        echo wp_json_encode([
          'error_key' => 'crmpn_nonce_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'crmpn')),
        ]);

        exit;
      }

      if (!array_key_exists('crmpn_duplicate', $_POST)) {
        foreach (array_merge(self::crmpn_funnel_get_fields(), self::crmpn_funnel_get_fields_meta()) as $crmpn_field) {
          $crmpn_input = array_key_exists('input', $crmpn_field) ? $crmpn_field['input'] : '';

          if (array_key_exists($crmpn_field['id'], $_POST) || $crmpn_input == 'html_multi') {
            $crmpn_value = array_key_exists($crmpn_field['id'], $_POST) ? 
              CRMPN_Forms::crmpn_sanitizer(
                wp_unslash($_POST[$crmpn_field['id']]),
                $crmpn_field['input'], 
                !empty($crmpn_field['type']) ? $crmpn_field['type'] : '',
                $crmpn_field // Pass the entire field config
              ) : '';

            if (!empty($crmpn_input)) {
              switch ($crmpn_input) {
                case 'input':
                  if (array_key_exists('type', $crmpn_field) && $crmpn_field['type'] == 'checkbox') {
                    if (isset($_POST[$crmpn_field['id']])) {
                      update_post_meta($post_id, $crmpn_field['id'], $crmpn_value);
                    } else {
                      update_post_meta($post_id, $crmpn_field['id'], '');
                    }
                  } else {
                    update_post_meta($post_id, $crmpn_field['id'], $crmpn_value);
                  }

                  break;
                case 'select':
                  if (array_key_exists('multiple', $crmpn_field) && $crmpn_field['multiple']) {
                    $multi_array = [];
                    $empty = true;

                    foreach (wp_unslash($_POST[$crmpn_field['id']]) as $multi_value) {
                      $multi_array[] = CRMPN_Forms::crmpn_sanitizer(
                        $multi_value, 
                        $crmpn_field['input'], 
                        !empty($crmpn_field['type']) ? $crmpn_field['type'] : '',
                        $crmpn_field // Pass the entire field config
                      );
                    }

                    update_post_meta($post_id, $crmpn_field['id'], $multi_array);
                  } else {
                    update_post_meta($post_id, $crmpn_field['id'], $crmpn_value);
                  }
                  
                  break;
                case 'html_multi':
                  foreach ($crmpn_field['html_multi_fields'] as $crmpn_multi_field) {
                    if (array_key_exists($crmpn_multi_field['id'], $_POST)) {
                      $multi_array = [];
                      $empty = true;

                      // Sanitize the POST data before using it
                      $sanitized_post_data = isset($_POST[$crmpn_multi_field['id']]) ? 
                        array_map(function($value) {
                            return sanitize_text_field(wp_unslash($value));
                        }, (array)$_POST[$crmpn_multi_field['id']]) : [];
                      
                      foreach ($sanitized_post_data as $multi_value) {
                        if (!empty($multi_value)) {
                          $empty = false;
                        }

                        $multi_array[] = CRMPN_Forms::crmpn_sanitizer(
                          $multi_value, 
                          $crmpn_multi_field['input'], 
                          !empty($crmpn_multi_field['type']) ? $crmpn_multi_field['type'] : '',
                          $crmpn_multi_field // Pass the entire field config
                        );
                      }

                      if (!$empty) {
                        update_post_meta($post_id, $crmpn_multi_field['id'], $multi_array);
                      } else {
                        update_post_meta($post_id, $crmpn_multi_field['id'], '');
                      }
                    }
                  }

                  break;
                case 'tags':
                  // Handle tags field - save as array
                  $tags_array_field_name = $crmpn_field['id'] . '_tags_array';
                  if (array_key_exists($tags_array_field_name, $_POST)) {
                    $tags_json = CRMPN_Forms::crmpn_sanitizer(
                      wp_unslash($_POST[$tags_array_field_name]),
                      'input',
                      'text',
                      $crmpn_field
                    );
                    
                    // Decode JSON and save as array
                    $tags_array = json_decode($tags_json, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($tags_array)) {
                      update_post_meta($post_id, $crmpn_field['id'], $tags_array);
                    } else {
                      // Fallback: treat as comma-separated string
                      $tags_string = CRMPN_Forms::crmpn_sanitizer(
                        wp_unslash($_POST[$crmpn_field['id']]),
                        'input',
                        'text',
                        $crmpn_field
                      );
                      $tags_array = array_map('trim', explode(',', $tags_string));
                      $tags_array = array_filter($tags_array); // Remove empty values
                      update_post_meta($post_id, $crmpn_field['id'], $tags_array);
                    }
                  } else {
                    // Fallback: save the text input value as comma-separated array
                    $tags_string = CRMPN_Forms::crmpn_sanitizer(
                      wp_unslash($_POST[$crmpn_field['id']]),
                      'input',
                      'text',
                      $crmpn_field
                    );
                    $tags_array = array_map('trim', explode(',', $tags_string));
                    $tags_array = array_filter($tags_array); // Remove empty values
                    update_post_meta($post_id, $crmpn_field['id'], $tags_array);
                  }
                  break;
                default:
                  update_post_meta($post_id, $crmpn_field['id'], $crmpn_value);
                  break;
              }
            }
          } else {
            update_post_meta($post_id, $crmpn_field['id'], '');
          }
        }
      }
    }
  }

  public function crmpn_funnel_form_save($element_id, $key_value, $crmpn_form_type, $crmpn_form_subtype) {
    $post_type = !empty(get_post_type($element_id)) ? get_post_type($element_id) : 'crmpn_funnel';

    if ($post_type == 'crmpn_funnel') {
      switch ($crmpn_form_type) {
        case 'post':
          switch ($crmpn_form_subtype) {
            case 'post_new':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'crmpn_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $post_functions = new CRMPN_Functions_Post();
              $funnel_id = $post_functions->crmpn_insert_post(esc_html($crmpn_funnel_title), $crmpn_funnel_description, '', sanitize_title(esc_html($crmpn_funnel_title)), 'crmpn_funnel', 'publish', get_current_user_id());

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($funnel_id, $key, $value);
                }
              }

              break;
            case 'post_edit':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'crmpn_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $funnel_id = $element_id;
              wp_update_post(['ID' => $funnel_id, 'post_title' => $crmpn_funnel_title, 'post_content' => $crmpn_funnel_description,]);

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

  public function crmpn_funnel_register_scripts() {
    if (!wp_script_is('crmpn-aux', 'registered')) {
      wp_register_script('crmpn-aux', CRMPN_URL . 'assets/js/crmpn-aux.js', [], CRMPN_VERSION, true);
    }

    if (!wp_script_is('crmpn-forms', 'registered')) {
      wp_register_script('crmpn-forms', CRMPN_URL . 'assets/js/crmpn-forms.js', [], CRMPN_VERSION, true);
    }
    
    if (!wp_script_is('crmpn-selector', 'registered')) {
      wp_register_script('crmpn-selector', CRMPN_URL . 'assets/js/crmpn-selector.js', [], CRMPN_VERSION, true);
    }
  }

  public function crmpn_funnel_print_scripts() {
    wp_print_scripts(['crmpn-aux', 'crmpn-forms', 'crmpn-selector']);
  }

  public function crmpn_funnel_list_wrapper() {
    ob_start();
    ?>
      <div class="crmpn-cpt-list crmpn-crmpn_funnel-list crmpn-mb-50">
        <div class="crmpn-cpt-search-container crmpn-mb-20 crmpn-text-align-right">
          <div class="crmpn-cpt-search-wrapper">
            <input type="text" class="crmpn-cpt-search-input crmpn-input crmpn-display-none" placeholder="<?php esc_attr_e('Filter...', 'crmpn'); ?>" />
            <i class="material-icons-outlined crmpn-cpt-search-toggle crmpn-cursor-pointer crmpn-font-size-30 crmpn-vertical-align-middle crmpn-tooltip" title="<?php esc_attr_e('Search Funnels', 'crmpn'); ?>">search</i>
            
            <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_funnel-add" data-crmpn-ajax-type="crmpn_funnel_new">
              <i class="material-icons-outlined crmpn-cursor-pointer crmpn-font-size-30 crmpn-vertical-align-middle crmpn-tooltip" title="<?php esc_attr_e('Add new Funnel', 'crmpn'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="crmpn-cpt-list-wrapper crmpn-crmpn_funnel-list-wrapper">
          <?php echo wp_kses(self::crmpn_funnel_list(), CRMPN_KSES); ?>
        </div>
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_funnel_list() {
    $funnel_atts = [
      'fields' => 'ids',
      'numberposts' => -1,
      'post_type' => 'crmpn_funnel',
      'post_status' => 'any', 
      'orderby' => 'menu_order', 
      'order' => 'ASC', 
    ];
    
    if (class_exists('Polylang')) {
      $funnel_atts['lang'] = pll_current_language('slug');
    }

    $funnel = get_posts($funnel_atts);

    // Filter assets based on user permissions
    $funnel = CRMPN_Functions_User::funnel_filter_user_posts($funnel, 'funnel_funnel');

    ob_start();
    ?>
      <ul class="crmpn-funnels crmpn-list-style-none crmpn-p-0 crmpn-margin-auto">
        <?php if (!empty($funnel)): ?>
          <?php foreach ($funnel as $funnel_id): ?>
            <li class="crmpn-funnel crmpn-crmpn_funnel-list-item crmpn-mb-10" data-crmpn_funnel-id="<?php echo esc_attr($funnel_id); ?>">
              <div class="crmpn-display-table crmpn-width-100-percent">
                <div class="crmpn-display-inline-table crmpn-width-60-percent">
                  <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_funnel-view" data-crmpn-ajax-type="crmpn_funnel_view">
                    <span><?php echo esc_html(get_the_title($funnel_id)); ?></span>
                  </a>
                </div>

                <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right crmpn-position-relative">
                  <i class="material-icons-outlined crmpn-menu-more-btn crmpn-cursor-pointer crmpn-vertical-align-middle crmpn-font-size-30">more_vert</i>

                  <div class="crmpn-menu-more crmpn-z-index-99 crmpn-display-none-soft">
                    <ul class="crmpn-list-style-none">
                      <li>
                        <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_funnel-view" data-crmpn-ajax-type="crmpn_funnel_view">
                          <div class="crmpn-display-table crmpn-width-100-percent">
                            <div class="crmpn-display-inline-table crmpn-width-70-percent">
                              <p><?php esc_html_e('View Funnel', 'crmpn'); ?></p>
                            </div>
                            <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right">
                              <i class="material-icons-outlined crmpn-vertical-align-middle crmpn-font-size-30 crmpn-ml-30">visibility</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_funnel-edit" data-crmpn-ajax-type="crmpn_funnel_edit"> 
                          <div class="crmpn-display-table crmpn-width-100-percent">
                            <div class="crmpn-display-inline-table crmpn-width-70-percent">
                              <p><?php esc_html_e('Edit Funnel', 'crmpn'); ?></p>
                            </div>
                            <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right">
                              <i class="material-icons-outlined crmpn-vertical-align-middle crmpn-font-size-30 crmpn-ml-30">edit</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="crmpn-crmpn_funnel-duplicate-post">
                          <div class="crmpn-display-table crmpn-width-100-percent">
                            <div class="crmpn-display-inline-table crmpn-width-70-percent">
                              <p><?php esc_html_e('Duplicate Funnel', 'crmpn'); ?></p>
                            </div>
                            <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right">
                              <i class="material-icons-outlined crmpn-vertical-align-middle crmpn-font-size-30 crmpn-ml-30">copy</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="crmpn-popup-open" data-crmpn-popup-id="crmpn-popup-crmpn_funnel-remove">
                          <div class="crmpn-display-table crmpn-width-100-percent">
                            <div class="crmpn-display-inline-table crmpn-width-70-percent">
                              <p><?php esc_html_e('Remove Funnel', 'crmpn'); ?></p>
                            </div>
                            <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right">
                              <i class="material-icons-outlined crmpn-vertical-align-middle crmpn-font-size-30 crmpn-ml-30">delete</i>
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

        <li class="crmpn-add-new-cpt crmpn-mt-50 crmpn-funnel" data-crmpn_funnel-id="0">
          <?php if (is_user_logged_in()): ?>
            <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_funnel-add" data-crmpn-ajax-type="crmpn_funnel_new">
              <div class="crmpn-display-table crmpn-width-100-percent">
                <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-center">
                  <i class="material-icons-outlined crmpn-cursor-pointer crmpn-vertical-align-middle crmpn-font-size-30 crmpn-width-25">add</i>
                </div>
                <div class="crmpn-display-inline-table crmpn-width-80-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent">
                  <?php esc_html_e('Add new Funnel', 'crmpn'); ?>
                </div>
              </div>
            </a>
          <?php endif ?>
        </li>
      </ul>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_funnel_view($funnel_id) {  
    ob_start();
    self::crmpn_funnel_register_scripts();
    self::crmpn_funnel_print_scripts();
    ?>
      <div class="crmpn_funnel-view crmpn-p-30" data-crmpn_funnel-id="<?php echo esc_attr($funnel_id); ?>">
        <h4 class="crmpn-text-align-center"><?php echo esc_html(get_the_title($funnel_id)); ?></h4>
        
        <div class="crmpn-word-wrap-break-word">
          <p><?php echo wp_kses(str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($funnel_id)->post_content)), CRMPN_KSES); ?></p>
        </div>

        <div class="crmpn_funnel-view-list">
          <?php foreach (array_merge(self::crmpn_funnel_get_fields(), self::crmpn_funnel_get_fields_meta()) as $crmpn_field): ?>
            <?php echo wp_kses(CRMPN_Forms::crmpn_input_display_wrapper($crmpn_field, 'post', $funnel_id), CRMPN_KSES); ?>
          <?php endforeach ?>

          <div class="crmpn-text-align-right crmpn-funnel" data-crmpn_funnel-id="<?php echo esc_attr($funnel_id); ?>">
            <a href="#" class="crmpn-btn crmpn-btn-mini crmpn-popup-open-ajax" data-crmpn-popup-id="crmpn-popup-crmpn_funnel-edit" data-crmpn-ajax-type="crmpn_funnel_edit"><?php esc_html_e('Edit Funnel', 'crmpn'); ?></a>
          </div>
        </div>
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_funnel_new() {
    if (!is_user_logged_in()) {
      wp_die(esc_html__('You must be logged in to create a new asset.', 'crmpn'), esc_html__('Access Denied', 'crmpn'), ['response' => 403]);
    }

    ob_start();
    self::crmpn_funnel_register_scripts();
    self::crmpn_funnel_print_scripts();
    ?>
      <div class="crmpn_funnel-new crmpn-p-30">
        <h4 class="crmpn-mb-30"><?php esc_html_e('Add new Funnel', 'crmpn'); ?></h4>

        <form action="" method="post" id="crmpn-funnel-form-new" class="crmpn-form">      
          <?php foreach (array_merge(self::crmpn_funnel_get_fields(), self::crmpn_funnel_get_fields_meta()) as $crmpn_field): ?>
            <?php echo wp_kses(CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_field, 'post'), CRMPN_KSES); ?>
          <?php endforeach ?>

          <div class="crmpn-text-align-right">
            <input class="crmpn-btn" data-crmpn-type="post" data-crmpn-subtype="post_new" data-crmpn-post-type="crmpn_funnel" type="submit" value="<?php esc_attr_e('Create Funnel', 'crmpn'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_funnel_edit($funnel_id) {
    ob_start();
    self::crmpn_funnel_register_scripts();
    self::crmpn_funnel_print_scripts();
    ?>
      <div class="crmpn_funnel-edit crmpn-p-30">
        <p class="crmpn-text-align-center crmpn-mb-0"><?php esc_html_e('Editing', 'crmpn'); ?></p>
        <h4 class="crmpn-text-align-center crmpn-mb-30"><?php echo esc_html(get_the_title($funnel_id)); ?></h4>

        <form action="" method="post" id="crmpn-funnel-form-edit" class="crmpn-form">      
          <?php foreach (array_merge(self::crmpn_funnel_get_fields(), self::crmpn_funnel_get_fields_meta()) as $crmpn_field): ?>
            <?php echo wp_kses(CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_field, 'post', $funnel_id), CRMPN_KSES); ?>
          <?php endforeach ?>

          <div class="crmpn-text-align-right">
            <input class="crmpn-btn" type="submit" data-crmpn-type="post" data-crmpn-subtype="post_edit" data-crmpn-post-type="crmpn_funnel" data-crmpn-post-id="<?php echo esc_attr($funnel_id); ?>" value="<?php esc_attr_e('Save Funnel', 'crmpn'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_funnel_history_add($funnel_id) {  
    $crmpn_meta = get_post_meta($funnel_id);
    $crmpn_meta_array = [];

    if (!empty($crmpn_meta)) {
      foreach ($crmpn_meta as $crmpn_meta_key => $crmpn_meta_value) {
        if (strpos((string)$crmpn_meta_key, 'crmpn_') !== false && !empty($crmpn_meta_value[0])) {
          $crmpn_meta_array[$crmpn_meta_key] = $crmpn_meta_value[0];
        }
      }
    }
    
    if(empty(get_post_meta($funnel_id, 'crmpn_funnel_history', true))) {
      update_post_meta($funnel_id, 'crmpn_funnel_history', [strtotime('now') => $crmpn_meta_array]);
    } else {
      $crmpn_post_meta_new = get_post_meta($funnel_id, 'crmpn_funnel_history', true);
      $crmpn_post_meta_new[strtotime('now')] = $crmpn_meta_array;
      update_post_meta($funnel_id, 'crmpn_funnel_history', $crmpn_post_meta_new);
    }
  }

  public function crmpn_funnel_get_next($funnel_id) {
    $crmpn_funnel_periodicity = get_post_meta($funnel_id, 'crmpn_funnel_periodicity', true);
    $crmpn_funnel_date = get_post_meta($funnel_id, 'crmpn_funnel_date', true);
    $crmpn_funnel_time = get_post_meta($funnel_id, 'crmpn_funnel_time', true);

    $crmpn_funnel_timestamp = strtotime($crmpn_funnel_date . ' ' . $crmpn_funnel_time);

    if (!empty($crmpn_funnel_periodicity) && !empty($crmpn_funnel_timestamp)) {
      $now = strtotime('now');

      while ($crmpn_funnel_timestamp < $now) {
        $crmpn_funnel_timestamp = strtotime('+' . str_replace('_', ' ', $crmpn_funnel_periodicity), $crmpn_funnel_timestamp);
      }

      return $crmpn_funnel_timestamp;
    }
  }

  public function crmpn_funnel_owners($funnel_id) {
    $crmpn_owners = get_post_meta($funnel_id, 'crmpn_owners', true);
    $crmpn_owners_array = [get_post($funnel_id)->post_author];

    if (!empty($crmpn_owners)) {
      foreach ($crmpn_owners as $owner_id) {
        $crmpn_owners_array[] = $owner_id;
      }
    }

    return array_unique($crmpn_owners_array);
  }
}