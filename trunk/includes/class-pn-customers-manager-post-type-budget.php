<?php
/**
 * Budget creator.
 *
 * This class defines Budget options, menus and templates.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Post_Type_Budget {
  public function pn_cm_budget_get_fields($budget_id = 0) {
    $pn_customers_manager_fields = [];
      $pn_customers_manager_fields['pn_cm_budget_title'] = [
        'id' => 'pn_cm_budget_title',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'required' => true,
        'value' => !empty($budget_id) ? esc_html(get_the_title($budget_id)) : '',
        'label' => __('Budget title', 'pn-customers-manager'),
        'placeholder' => __('Budget title', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields['pn_customers_manager_ajax_nonce'] = [
        'id' => 'pn_customers_manager_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $pn_customers_manager_fields;
  }

  /**
   * Build a list of published organizations to populate the select.
   *
   * @return array
   */
  private static function get_organization_options() {
    $options = ['' => esc_html__('Select an organization', 'pn-customers-manager')];
    $orgs = get_posts([
      'post_type' => 'pn_cm_organization',
      'numberposts' => -1,
      'orderby' => 'title',
      'order' => 'ASC',
      'fields' => 'ids',
    ]);
    foreach ($orgs as $org_id) {
      $options[$org_id] = esc_html(get_the_title($org_id));
    }
    return $options;
  }

  /**
   * Meta fields for Budget.
   *
   * @param int $budget_id
   * @return array
   */
  public function pn_cm_budget_get_fields_meta($budget_id = 0) {
    $default_validity_days = intval(get_option('pn_customers_manager_budget_default_validity_days', 30));
    $default_tax_rate = get_option('pn_customers_manager_budget_default_tax_rate', '');
    $default_client_notes = get_option('pn_customers_manager_budget_default_client_notes', '');
    $default_terms = get_option('pn_customers_manager_budget_terms', '');
    $default_others = get_option('pn_customers_manager_budget_default_others', '');

    $pn_customers_manager_fields_meta = [];
      // Quick-access public URL block at the top (only for saved budgets).
      if ( ! empty( $budget_id ) ) {
        $pn_customers_manager_fields_meta['pn_cm_budget_quick_link_block'] = [
          'id'           => 'pn_cm_budget_quick_link_block',
          'input'        => 'html',
          'skip_save'    => true,
          'html_content' => $this->render_quick_link_block( $budget_id ),
        ];
      }
      $pn_customers_manager_fields_meta['pn_cm_budget_section_details_start'] = [
        'id' => 'pn_cm_budget_section_details_start',
        'section' => 'start',
        'label' => esc_html__('Budget details', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_organization_id'] = [
        'id' => 'pn_cm_budget_organization_id',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Organization', 'pn-customers-manager'),
        'options' => self::get_organization_options(),
        'description' => '<a href="' . esc_url(admin_url('post-new.php?post_type=pn_cm_organization')) . '" target="_blank">' . esc_html__('Add new organization', 'pn-customers-manager') . '</a>',
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_number'] = [
        'id' => 'pn_cm_budget_number',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Budget number', 'pn-customers-manager'),
        'placeholder' => esc_html__('Auto-generated', 'pn-customers-manager'),
        'readonly' => !empty($budget_id),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_date'] = [
        'id' => 'pn_cm_budget_date',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => esc_html__('Date', 'pn-customers-manager'),
        'value' => gmdate('Y-m-d'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_valid_until'] = [
        'id' => 'pn_cm_budget_valid_until',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => esc_html__('Valid until', 'pn-customers-manager'),
        'value' => gmdate('Y-m-d', strtotime('+' . $default_validity_days . ' days')),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_status'] = [
        'id' => 'pn_cm_budget_status',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Status', 'pn-customers-manager'),
        'value' => 'draft',
        'options' => [
          'draft' => esc_html__('Draft', 'pn-customers-manager'),
          'sent' => esc_html__('Sent', 'pn-customers-manager'),
          'accepted' => esc_html__('Accepted', 'pn-customers-manager'),
          'rejected' => esc_html__('Rejected', 'pn-customers-manager'),
        ],
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_tax_rate'] = [
        'id' => 'pn_cm_budget_tax_rate',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'number',
        'label' => esc_html__('Tax rate (%)', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. 21', 'pn-customers-manager'),
        'value' => $default_tax_rate,
        'min' => 0,
        'max' => 100,
        'step' => '0.01',
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_discount_rate'] = [
        'id' => 'pn_cm_budget_discount_rate',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'number',
        'label' => esc_html__('Discount rate (%)', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. 10', 'pn-customers-manager'),
        'value' => '0',
        'min' => 0,
        'max' => 100,
        'step' => '0.01',
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_section_details_end'] = [
        'id' => 'pn_cm_budget_section_details_end',
        'section' => 'end',
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_section_notes_start'] = [
        'id' => 'pn_cm_budget_section_notes_start',
        'section' => 'start',
        'label' => esc_html__('Notes', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_notes'] = [
        'id' => 'pn_cm_budget_notes',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'textarea',
        'label' => esc_html__('Internal notes', 'pn-customers-manager'),
        'placeholder' => esc_html__('Notes visible only to administrators.', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_client_notes'] = [
        'id' => 'pn_cm_budget_client_notes',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => esc_html__('Client notes', 'pn-customers-manager'),
        'placeholder' => esc_html__('Notes visible to the client on the public budget.', 'pn-customers-manager'),
        'value' => $default_client_notes,
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_conditions'] = [
        'id' => 'pn_cm_budget_conditions',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => esc_html__('Terms and conditions', 'pn-customers-manager'),
        'placeholder' => esc_html__('Leave empty to use the default terms from settings.', 'pn-customers-manager'),
        'description' => !empty($default_terms)
          ? esc_html__('If empty, the global terms from settings will be used.', 'pn-customers-manager')
          : esc_html__('No global terms configured. Set defaults in Settings > Budgets.', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_others'] = [
        'id' => 'pn_cm_budget_others',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => esc_html__('Additional information', 'pn-customers-manager'),
        'placeholder' => esc_html__('Leave empty to use the default from settings.', 'pn-customers-manager'),
        'value' => $default_others,
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_section_notes_end'] = [
        'id' => 'pn_cm_budget_section_notes_end',
        'section' => 'end',
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_section_footer_image_start'] = [
        'id' => 'pn_cm_budget_section_footer_image_start',
        'section' => 'start',
        'label' => esc_html__('Footer image', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_footer_image'] = [
        'id' => 'pn_cm_budget_footer_image',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'image',
        'label' => esc_html__('Footer image', 'pn-customers-manager'),
        'description' => esc_html__('Image displayed at the end of the budget, before terms and conditions.', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_section_footer_image_end'] = [
        'id' => 'pn_cm_budget_section_footer_image_end',
        'section' => 'end',
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_items_block'] = [
        'id' => 'pn_cm_budget_items_block',
        'label' => '',
        'input' => 'html',
        'skip_save' => true,
        'html_content' => $this->render_admin_items_editor($budget_id),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_totals_block'] = [
        'id' => 'pn_cm_budget_totals_block',
        'label' => '',
        'input' => 'html',
        'skip_save' => true,
        'html_content' => $this->render_totals_display($budget_id),
      ];
      $pn_customers_manager_fields_meta['pn_cm_budget_form'] = [
        'id' => 'pn_cm_budget_form',
        'input' => 'input',
        'type' => 'hidden',
      ];
      $pn_customers_manager_fields_meta['pn_customers_manager_ajax_nonce'] = [
        'id' => 'pn_customers_manager_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $pn_customers_manager_fields_meta;
  }

  /**
   * Register Budget CPT.
   *
   * @since    1.0.0
   */
  public function pn_cm_budget_register_post_type() {
    $labels = [
      'name'                => _x('Budget', 'Post Type general name', 'pn-customers-manager'),
      'singular_name'       => _x('Budget', 'Post Type singular name', 'pn-customers-manager'),
      'menu_name'           => esc_html(__('Budgets', 'pn-customers-manager')),
      'parent_item_colon'   => esc_html(__('Parent Budget', 'pn-customers-manager')),
      'all_items'           => esc_html(__('All Budgets', 'pn-customers-manager')),
      'view_item'           => esc_html(__('View Budget', 'pn-customers-manager')),
      'add_new_item'        => esc_html(__('Add new Budget', 'pn-customers-manager')),
      'add_new'             => esc_html(__('Add new Budget', 'pn-customers-manager')),
      'edit_item'           => esc_html(__('Edit Budget', 'pn-customers-manager')),
      'update_item'         => esc_html(__('Update Budget', 'pn-customers-manager')),
      'search_items'        => esc_html(__('Search Budgets', 'pn-customers-manager')),
      'not_found'           => esc_html(__('No Budget found', 'pn-customers-manager')),
      'not_found_in_trash'  => esc_html(__('No Budget found in Trash', 'pn-customers-manager')),
    ];

    $args = [
      'labels'              => $labels,
      'label'               => esc_html(__('Budgets', 'pn-customers-manager')),
      'description'         => esc_html(__('Budget description', 'pn-customers-manager')),
      'supports'            => ['title'],
      'hierarchical'        => false,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => false,
      'show_in_nav_menus'   => false,
      'show_in_admin_bar'   => false,
      'menu_position'       => 5,
      'can_export'          => true,
      'has_archive'         => false,
      'exclude_from_search' => true,
      'publicly_queryable'  => false,
      'capability_type'     => 'page',
      'capabilities'        => defined('PN_CUSTOMERS_MANAGER_ROLE_PN_CM_BUDGET_CAPABILITIES') ? PN_CUSTOMERS_MANAGER_ROLE_PN_CM_BUDGET_CAPABILITIES : [],
      'show_in_rest'        => true,
    ];

    register_post_type('pn_cm_budget', $args);
  }

  /**
   * Add Budget dashboard metabox.
   *
   * @since    1.0.0
   */
  public function pn_cm_budget_add_meta_box() {
    add_meta_box('pn_customers_manager_budget_meta_box', esc_html(__('Budget details', 'pn-customers-manager')), [$this, 'pn_cm_budget_meta_box_function'], 'pn_cm_budget', 'normal', 'high', ['__block_editor_compatible_meta_box' => true,]);
  }

  /**
   * Defines Budget dashboard contents.
   *
   * @since    1.0.0
   */
  public function pn_cm_budget_meta_box_function($post) {
    // Enqueue budget CSS + JS on admin edit screen.
    wp_enqueue_style('pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-budget.css', [], PN_CUSTOMERS_MANAGER_VERSION);
    wp_enqueue_script('pn-customers-manager-budget-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-budget-admin.js', ['jquery', 'wph-trumbowyg'], PN_CUSTOMERS_MANAGER_VERSION, true);
    wp_localize_script('pn-customers-manager-budget-admin', 'pnCmBudgetAdmin', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('pn-customers-manager-nonce'),
      'budgetId' => $post->ID,
      'currencySymbol' => get_option('pn_customers_manager_budget_currency_symbol', '€'),
      'currencyPosition' => get_option('pn_customers_manager_budget_currency_position', 'after'),
      'defaultHourlyRate' => get_option('pn_customers_manager_budget_default_hourly_rate', '0'),
      'i18n' => [
        'error' => esc_html__('An error occurred.', 'pn-customers-manager'),
        'confirmDelete' => esc_html__('Are you sure you want to delete this item?', 'pn-customers-manager'),
        'confirmSend' => esc_html__('Are you sure you want to send this budget?', 'pn-customers-manager'),
        'budgetSent' => esc_html__('Budget sent successfully.', 'pn-customers-manager'),
        'budgetRemoved' => esc_html__('Budget removed successfully.', 'pn-customers-manager'),
        'budgetDuplicated' => esc_html__('Budget duplicated successfully.', 'pn-customers-manager'),
        'noDescription' => esc_html__('Please enter a description.', 'pn-customers-manager'),
        'newPhase' => esc_html__('New phase', 'pn-customers-manager'),
        'confirmGenerateInvoice' => esc_html__('Are you sure you want to generate an invoice from this budget?', 'pn-customers-manager'),
        'invoiceGenerated' => esc_html__('Invoice generated successfully.', 'pn-customers-manager'),
        'importItemsTitle' => esc_html__('Import items from CSV', 'pn-customers-manager'),
        'importDropText'   => esc_html__('Drag & drop a CSV file here or click to select', 'pn-customers-manager'),
        'importInvalidFile'=> esc_html__('Please select a valid CSV file.', 'pn-customers-manager'),
        'importSuccess'    => esc_html__('items imported successfully.', 'pn-customers-manager'),
        'copied'           => esc_html__('Copied!', 'pn-customers-manager'),
      ],
      'recurringLabels' => [
        'daily'   => esc_html__( 'Daily', 'pn-customers-manager' ),
        'weekly'  => esc_html__( 'Weekly', 'pn-customers-manager' ),
        'monthly' => esc_html__( 'Monthly', 'pn-customers-manager' ),
        'yearly'  => esc_html__( 'Yearly', 'pn-customers-manager' ),
      ],
    ]);

    foreach (self::pn_cm_budget_get_fields_meta($post->ID) as $pn_customers_manager_field) {
      if (!is_null(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $post->ID))) {
        echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $post->ID), PN_CUSTOMERS_MANAGER_KSES);
      }
    }
  }

  /**
   * Save Budget post meta.
   *
   * @since    1.0.0
   */
  public function pn_cm_budget_save_post($post_id, $cpt, $update) {
    if ($cpt->post_type == 'pn_cm_budget' && array_key_exists('pn_cm_budget_form', $_POST)) {
      // Always require nonce verification
      if (!array_key_exists('pn_customers_manager_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'pn_customers_manager_nonce_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'pn-customers-manager')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pn_customers_manager_ajax_nonce'])), 'pn-customers-manager-nonce')) {
        echo wp_json_encode([
          'error_key' => 'pn_customers_manager_nonce_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'pn-customers-manager')),
        ]);

        exit;
      }

      if (!array_key_exists('pn_customers_manager_duplicate', $_POST)) {
        // Auto-generate budget number if new
        $existing_number = get_post_meta($post_id, 'pn_cm_budget_number', true);
        if (empty($existing_number)) {
          $budget_number = self::pn_cm_budget_generate_number();
          update_post_meta($post_id, 'pn_cm_budget_number', $budget_number);
        }

        // Auto-generate token if new
        $existing_token = get_post_meta($post_id, 'pn_cm_budget_token', true);
        if (empty($existing_token)) {
          $token = self::pn_cm_budget_generate_token();
          update_post_meta($post_id, 'pn_cm_budget_token', $token);
        }

        foreach (array_merge(self::pn_cm_budget_get_fields(), self::pn_cm_budget_get_fields_meta($post_id)) as $pn_customers_manager_field) {
          // Skip fields marked as skip_save
          if (!empty($pn_customers_manager_field['skip_save'])) {
            continue;
          }

          $pn_customers_manager_input = array_key_exists('input', $pn_customers_manager_field) ? $pn_customers_manager_field['input'] : '';

          if (array_key_exists($pn_customers_manager_field['id'], $_POST) || $pn_customers_manager_input == 'html_multi') {
            $pn_customers_manager_value = array_key_exists($pn_customers_manager_field['id'], $_POST) ?
              PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                wp_unslash($_POST[$pn_customers_manager_field['id']]),
                $pn_customers_manager_field['input'],
                !empty($pn_customers_manager_field['type']) ? $pn_customers_manager_field['type'] : '',
                $pn_customers_manager_field
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

                    foreach (wp_unslash($_POST[$pn_customers_manager_field['id']]) as $multi_value) {
                      $multi_array[] = PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(
                        $multi_value,
                        $pn_customers_manager_field['input'],
                        !empty($pn_customers_manager_field['type']) ? $pn_customers_manager_field['type'] : '',
                        $pn_customers_manager_field
                      );
                    }

                    update_post_meta($post_id, $pn_customers_manager_field['id'], $multi_array);
                  } else {
                    update_post_meta($post_id, $pn_customers_manager_field['id'], $pn_customers_manager_value);
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

        // Save items from POST data
        $this->pn_cm_budget_save_items($post_id);

        // Recalculate totals
        self::pn_cm_budget_recalculate_totals($post_id);
      }
    }
  }

  /**
   * Save budget items from POST data to post meta.
   *
   * @param int $budget_id
   */
  private function pn_cm_budget_save_items( $budget_id ) {
    if ( ! isset( $_POST['pn_cm_budget_item_description'] ) || ! is_array( $_POST['pn_cm_budget_item_description'] ) ) {
      return;
    }

    $descriptions = array_map( 'wp_kses_post', wp_unslash( $_POST['pn_cm_budget_item_description'] ) );
    $types        = isset( $_POST['pn_cm_budget_item_type'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['pn_cm_budget_item_type'] ) ) : [];
    $quantities   = isset( $_POST['pn_cm_budget_item_quantity'] ) ? array_map( 'floatval', wp_unslash( $_POST['pn_cm_budget_item_quantity'] ) ) : [];
    $unit_prices  = isset( $_POST['pn_cm_budget_item_unit_price'] ) ? array_map( 'floatval', wp_unslash( $_POST['pn_cm_budget_item_unit_price'] ) ) : [];
    $is_optional  = isset( $_POST['pn_cm_budget_item_is_optional'] ) ? array_map( 'intval', wp_unslash( $_POST['pn_cm_budget_item_is_optional'] ) ) : [];
    $item_ids     = isset( $_POST['pn_cm_budget_item_id'] ) ? array_map( 'intval', wp_unslash( $_POST['pn_cm_budget_item_id'] ) ) : [];
    $is_recurring     = isset( $_POST['pn_cm_budget_item_is_recurring'] ) ? array_map( 'intval', wp_unslash( $_POST['pn_cm_budget_item_is_recurring'] ) ) : [];
    $recurring_period = isset( $_POST['pn_cm_budget_item_recurring_period'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['pn_cm_budget_item_recurring_period'] ) ) : [];

    $has_any_description = false;
    $new_items           = [];

    foreach ( $descriptions as $index => $description ) {
      if ( empty( $description ) ) {
        continue;
      }

      $has_any_description = true;
      $item_id  = isset( $item_ids[ $index ] ) ? $item_ids[ $index ] : 0;
      $qty      = isset( $quantities[ $index ] ) ? $quantities[ $index ] : 1;
      $price    = isset( $unit_prices[ $index ] ) ? $unit_prices[ $index ] : 0;
      $type     = isset( $types[ $index ] ) ? $types[ $index ] : 'fixed';
      $optional = isset( $is_optional[ $index ] ) ? $is_optional[ $index ] : 0;

      // Preserve is_selected from existing item, default 1 for new.
      $existing   = ! empty( $item_id ) ? self::get_budget_item( $budget_id, $item_id ) : null;
      $selected   = $existing ? intval( $existing['is_selected'] ) : 1;

      if ( empty( $item_id ) ) {
        $item_id = self::get_next_item_id( $budget_id );
      }

      $new_items[] = [
        'id'          => $item_id,
        'item_type'   => $type,
        'description' => $description,
        'quantity'    => $qty,
        'unit_price'  => $price,
        'total'       => round( $qty * $price, 2 ),
        'is_optional' => $optional,
        'is_selected'      => $selected,
        'sort_order'       => $index,
        'is_recurring'     => isset( $is_recurring[ $index ] ) ? $is_recurring[ $index ] : 0,
        'recurring_period' => isset( $recurring_period[ $index ] ) ? $recurring_period[ $index ] : '',
      ];
    }

    if ( ! $has_any_description ) {
      return;
    }

    self::save_budget_items( $budget_id, $new_items );
  }

  /**
   * Defines single template for Budget.
   *
   * @since    1.0.0
   */
  public function pn_cm_budget_single_template($single) {
    if (get_post_type() == 'pn_cm_budget') {
      if (file_exists(PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-pn_cm_budget.php')) {
        return PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-pn_cm_budget.php';
      }
    }

    return $single;
  }

  /**
   * Get the public URL slug for budgets.
   *
   * @return string
   */
  public static function pn_cm_budget_get_public_slug() {
    $slug = get_option('pn_customers_manager_budget_public_slug', 'budget');
    return !empty($slug) ? sanitize_title($slug) : 'budget';
  }

  /**
   * Build the public URL for a budget given its token.
   *
   * @param string $token
   * @return string
   */
  public static function pn_cm_budget_get_public_url($token) {
    return home_url(self::pn_cm_budget_get_public_slug() . '/' . $token);
  }

  /**
   * Generate a unique random token for the public budget URL.
   *
   * @return string
   */
  public static function pn_cm_budget_generate_token() {
    return wp_generate_password(32, false);
  }

  /**
   * Auto-generate budget number from settings.
   *
   * @return string
   */
  public static function pn_cm_budget_generate_number() {
    $prefix = get_option('pn_customers_manager_budget_number_prefix', 'BUD');
    $next_number = intval(get_option('pn_customers_manager_budget_next_number', 1));

    $budget_number = $prefix . '-' . str_pad($next_number, 5, '0', STR_PAD_LEFT);

    // Increment next number
    update_option('pn_customers_manager_budget_next_number', $next_number + 1);

    return $budget_number;
  }

  /**
   * Recalculate totals from items in post meta.
   *
   * @param int $budget_id
   */
  public static function pn_cm_budget_recalculate_totals( $budget_id ) {
    $items = self::get_budget_items( $budget_id );

    // Update individual item totals and re-save.
    $changed = false;
    foreach ( $items as &$item ) {
      if ( isset( $item['item_type'] ) && in_array( $item['item_type'], array( 'phase', 'image', 'image_compare' ), true ) ) {
        continue;
      }
      $calc = round( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ), 2 );
      if ( floatval( $item['total'] ) !== $calc ) {
        $item['total'] = $calc;
        $changed       = true;
      }
    }
    unset( $item );
    if ( $changed ) {
      update_post_meta( $budget_id, 'pn_cm_budget_items', $items );
    }

    // Build a map of alt_active values keyed by original item ID.
    $alt_active_map = [];
    foreach ( $items as $item ) {
      if ( ! empty( $item['alt_active'] ) && empty( $item['alt_of'] ) ) {
        $alt_active_map[ intval( $item['id'] ) ] = $item['alt_active'];
      }
    }

    $subtotal = 0;
    $current_phase_inactive = false;

    foreach ( $items as $item ) {
      if ( isset( $item['item_type'] ) && 'phase' === $item['item_type'] ) {
        $phase_alt_of = ! empty( $item['alt_of'] ) ? intval( $item['alt_of'] ) : 0;
        if ( $phase_alt_of > 0 ) {
          $parent_active = isset( $alt_active_map[ $phase_alt_of ] ) ? $alt_active_map[ $phase_alt_of ] : 'original';
          $current_phase_inactive = ( $parent_active !== 'alternative' );
        } elseif ( ! empty( $item['alt_active'] ) && $item['alt_active'] === 'alternative' ) {
          $current_phase_inactive = true;
        } else {
          $current_phase_inactive = false;
        }
        continue;
      }

      if ( isset( $item['item_type'] ) && in_array( $item['item_type'], array( 'image', 'image_compare' ), true ) ) {
        continue;
      }

      // Skip items that belong to an inactive alternative phase.
      if ( $current_phase_inactive ) {
        continue;
      }

      if ( empty( $item['is_selected'] ) ) {
        continue;
      }
      // Skip inactive alternatives: if this is an alternative item and the original's alt_active is 'original', skip it.
      $alt_of = ! empty( $item['alt_of'] ) ? intval( $item['alt_of'] ) : 0;
      if ( $alt_of > 0 ) {
        $parent_active = isset( $alt_active_map[ $alt_of ] ) ? $alt_active_map[ $alt_of ] : 'original';
        if ( $parent_active !== 'alternative' ) {
          continue;
        }
      }
      // Skip the original item if its alt_active is 'alternative' (alternative is active instead).
      if ( empty( $item['alt_of'] ) && ! empty( $item['alt_active'] ) && $item['alt_active'] === 'alternative' ) {
        continue;
      }
      // Only one-time (non-recurring) items count toward the subtotal.
      if ( ! empty( $item['is_recurring'] ) ) {
        continue;
      }
      $subtotal += floatval( $item['quantity'] ) * floatval( $item['unit_price'] );
    }

    $tax_rate      = floatval( get_post_meta( $budget_id, 'pn_cm_budget_tax_rate', true ) );
    $discount_rate = floatval( get_post_meta( $budget_id, 'pn_cm_budget_discount_rate', true ) );

    $discount_amount = $subtotal * $discount_rate / 100;
    $tax_amount      = ( $subtotal - $discount_amount ) * $tax_rate / 100;
    $total           = $subtotal - $discount_amount + $tax_amount;

    update_post_meta( $budget_id, 'pn_cm_budget_subtotal', round( $subtotal, 2 ) );
    update_post_meta( $budget_id, 'pn_cm_budget_discount_amount', round( $discount_amount, 2 ) );
    update_post_meta( $budget_id, 'pn_cm_budget_tax_amount', round( $tax_amount, 2 ) );
    update_post_meta( $budget_id, 'pn_cm_budget_total', round( $total, 2 ) );
  }

  /**
   * Render admin items editor.
   *
   * @param int $budget_id
   * @return string
   */
  private function render_admin_items_editor($budget_id) {
    if (empty($budget_id)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('Save the budget first to add items.', 'pn-customers-manager') . '</p>';
    }

    $items = self::get_budget_items($budget_id);

    ob_start();
    ?>
      <div class="pn-customers-manager-budget-items-editor" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
        <table class="pn-customers-manager-budget-items-table pn-customers-manager-width-100-percent">
          <thead>
            <tr>
              <th class="pn-customers-manager-budget-col-drag"></th>
              <th class="pn-customers-manager-budget-col-description"><?php esc_html_e('Description', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-budget-col-type"><?php esc_html_e('Type', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-budget-col-quantity"><?php esc_html_e('Qty', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-budget-col-price"><?php esc_html_e('Unit price', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-budget-col-total"><?php esc_html_e('Total', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-budget-col-optional"><?php esc_html_e('Optional', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-budget-col-actions"></th>
            </tr>
          </thead>
          <tbody class="pn-customers-manager-budget-items-body">
            <?php if (!empty($items)): ?>
              <?php foreach ($items as $item): ?>
                <?php if ($item['item_type'] === 'phase'): ?>
                  <tr class="pn-customers-manager-budget-item-row pn-customers-manager-budget-item-phase" data-item-id="<?php echo esc_attr($item['id']); ?>">
                    <td class="pn-customers-manager-budget-col-drag">
                      <i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>
                      <input type="hidden" name="pn_cm_budget_item_id[]" value="<?php echo esc_attr($item['id']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_type[]" value="phase" />
                      <input type="hidden" name="pn_cm_budget_item_description[]" value="<?php echo esc_attr($item['description']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_quantity[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_unit_price[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_is_optional[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_is_recurring[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_recurring_period[]" value="" />
                    </td>
                    <td colspan="6" class="pn-customers-manager-budget-col-description">
                      <span class="pn-customers-manager-budget-item-desc-display pn-customers-manager-budget-phase-input"><?php echo wp_kses_post($item['description']); ?></span>
                    </td>
                    <td class="pn-customers-manager-budget-col-actions">
                      <a href="#" class="pn-cm-budget-row-edit pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Edit', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">edit</i>
                      </a>
                      <a href="#" class="pn-cm-budget-row-delete pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i>
                      </a>
                    </td>
                  </tr>
                <?php elseif ($item['item_type'] === 'image'): ?>
                  <tr class="pn-customers-manager-budget-item-row pn-customers-manager-budget-item-image" data-item-id="<?php echo esc_attr($item['id']); ?>">
                    <td class="pn-customers-manager-budget-col-drag">
                      <i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>
                      <input type="hidden" name="pn_cm_budget_item_id[]" value="<?php echo esc_attr($item['id']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_type[]" value="image" />
                      <input type="hidden" name="pn_cm_budget_item_quantity[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_unit_price[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_is_optional[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_is_recurring[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_recurring_period[]" value="" />
                    </td>
                    <td colspan="6" class="pn-customers-manager-budget-col-description">
                      <input type="hidden" name="pn_cm_budget_item_description[]" value="<?php echo esc_attr($item['description']); ?>" />
                      <img src="<?php echo esc_url($item['description']); ?>" class="pn-customers-manager-budget-image-preview" alt="" />
                    </td>
                    <td class="pn-customers-manager-budget-col-actions">
                      <a href="#" class="pn-cm-budget-row-delete pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i>
                      </a>
                    </td>
                  </tr>
                <?php elseif ($item['item_type'] === 'image_compare'): ?>
                  <?php
                    $compare_data = json_decode($item['description'], true);
                    $compare_before = !empty($compare_data['before']) ? $compare_data['before'] : '';
                    $compare_after = !empty($compare_data['after']) ? $compare_data['after'] : '';
                  ?>
                  <tr class="pn-customers-manager-budget-item-row pn-customers-manager-budget-item-image-compare" data-item-id="<?php echo esc_attr($item['id']); ?>">
                    <td class="pn-customers-manager-budget-col-drag">
                      <i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>
                      <input type="hidden" name="pn_cm_budget_item_id[]" value="<?php echo esc_attr($item['id']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_type[]" value="image_compare" />
                      <input type="hidden" name="pn_cm_budget_item_description[]" value="<?php echo esc_attr($item['description']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_quantity[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_unit_price[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_is_optional[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_is_recurring[]" value="0" />
                      <input type="hidden" name="pn_cm_budget_item_recurring_period[]" value="" />
                    </td>
                    <td colspan="6" class="pn-customers-manager-budget-col-description">
                      <div class="pn-cm-budget-compare-slider">
                        <span class="pn-cm-budget-compare-label pn-cm-budget-compare-label-before"><?php esc_html_e('Antes', 'pn-customers-manager'); ?></span>
                        <span class="pn-cm-budget-compare-label pn-cm-budget-compare-label-after"><?php esc_html_e('After', 'pn-customers-manager'); ?></span>
                        <div class="pn-cm-budget-compare-before"><img src="<?php echo esc_url($compare_before); ?>" alt="<?php esc_attr_e('Antes', 'pn-customers-manager'); ?>" /></div>
                        <div class="pn-cm-budget-compare-after"><img src="<?php echo esc_url($compare_after); ?>" alt="<?php esc_attr_e('After', 'pn-customers-manager'); ?>" /></div>
                        <div class="pn-cm-budget-compare-handle"></div>
                      </div>
                    </td>
                    <td class="pn-customers-manager-budget-col-actions">
                      <a href="#" class="pn-cm-budget-row-delete pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i>
                      </a>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php
                    $item_alt_of = ! empty( $item['alt_of'] ) ? intval( $item['alt_of'] ) : 0;
                    $item_alt_active = ! empty( $item['alt_active'] ) ? $item['alt_active'] : 'original';
                    // Determine if this item has an alternative.
                    $has_alt = false;
                    if ( ! $item_alt_of ) {
                      foreach ( $items as $check_item ) {
                        if ( ! empty( $check_item['alt_of'] ) && intval( $check_item['alt_of'] ) === intval( $item['id'] ) ) {
                          $has_alt = true;
                          break;
                        }
                      }
                    }
                    $alt_hidden_class = '';
                    if ( $item_alt_of > 0 ) {
                      // This is an alternative item. Check if it's active.
                      $parent_alt_active = 'original';
                      foreach ( $items as $check_item ) {
                        if ( intval( $check_item['id'] ) === $item_alt_of ) {
                          $parent_alt_active = ! empty( $check_item['alt_active'] ) ? $check_item['alt_active'] : 'original';
                          break;
                        }
                      }
                      if ( $parent_alt_active !== 'alternative' ) {
                        $alt_hidden_class = ' pn-cm-budget-alt-hidden';
                      }
                    } elseif ( $has_alt && $item_alt_active === 'alternative' ) {
                      $alt_hidden_class = ' pn-cm-budget-alt-hidden';
                    }
                  ?>
                  <tr class="pn-customers-manager-budget-item-row<?php echo esc_attr( $alt_hidden_class ); ?><?php echo $item_alt_of ? ' pn-cm-budget-alt-item' : ''; ?>" data-item-id="<?php echo esc_attr($item['id']); ?>" data-alt-of="<?php echo esc_attr( $item_alt_of ); ?>" data-alt-active="<?php echo esc_attr( $item_alt_active ); ?>">
                    <td class="pn-customers-manager-budget-col-drag">
                      <i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>
                      <input type="hidden" name="pn_cm_budget_item_id[]" value="<?php echo esc_attr($item['id']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_description[]" value="<?php echo esc_attr($item['description']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_type[]" value="<?php echo esc_attr($item['item_type']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_quantity[]" class="pn-customers-manager-budget-item-quantity" value="<?php echo esc_attr($item['quantity']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_unit_price[]" class="pn-customers-manager-budget-item-unit-price" value="<?php echo esc_attr($item['unit_price']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_is_optional[]" value="<?php echo esc_attr($item['is_optional']); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_is_recurring[]" value="<?php echo esc_attr( ! empty( $item['is_recurring'] ) ? $item['is_recurring'] : 0 ); ?>" />
                      <input type="hidden" name="pn_cm_budget_item_recurring_period[]" value="<?php echo esc_attr( ! empty( $item['recurring_period'] ) ? $item['recurring_period'] : '' ); ?>" />
                    </td>
                    <td class="pn-customers-manager-budget-col-description">
                      <?php if ( $item_alt_of ) : ?>
                        <span class="pn-cm-budget-alt-badge"><?php esc_html_e( 'ALT', 'pn-customers-manager' ); ?></span>
                      <?php endif; ?>
                      <span class="pn-customers-manager-budget-item-desc-display"><?php echo wp_kses_post($item['description']); ?></span>
                    </td>
                    <td class="pn-customers-manager-budget-col-type">
                      <span class="pn-customers-manager-budget-item-type-display"><?php echo $item['item_type'] === 'hours' ? esc_html__('Hours', 'pn-customers-manager') : esc_html__('Fixed', 'pn-customers-manager'); ?></span>
                      <?php if ( ! empty( $item['is_recurring'] ) && ! empty( $item['recurring_period'] ) ) :
                        $recurring_labels = array(
                          'daily'   => __( 'Daily', 'pn-customers-manager' ),
                          'weekly'  => __( 'Weekly', 'pn-customers-manager' ),
                          'monthly' => __( 'Monthly', 'pn-customers-manager' ),
                          'yearly'  => __( 'Yearly', 'pn-customers-manager' ),
                        );
                        $period_label = isset( $recurring_labels[ $item['recurring_period'] ] ) ? $recurring_labels[ $item['recurring_period'] ] : $item['recurring_period'];
                      ?>
                        <span class="pn-cm-budget-recurring-badge"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">autorenew</i> <?php echo esc_html( $period_label ); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="pn-customers-manager-budget-col-quantity">
                      <span class="pn-customers-manager-budget-item-qty-display"><?php echo esc_html(number_format(floatval($item['quantity']), 2)); ?></span>
                    </td>
                    <td class="pn-customers-manager-budget-col-price">
                      <span class="pn-customers-manager-budget-item-price-display"><?php echo esc_html(self::format_currency(floatval($item['unit_price']))); ?></span>
                    </td>
                    <td class="pn-customers-manager-budget-col-total">
                      <span class="pn-customers-manager-budget-item-line-total"><?php echo esc_html(self::format_currency(floatval($item['quantity']) * floatval($item['unit_price']))); ?></span>
                    </td>
                    <td class="pn-customers-manager-budget-col-optional">
                      <?php if ($item['is_optional']) : ?>
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle" style="color:#2271b1;font-size:18px;" title="<?php esc_attr_e('Optional', 'pn-customers-manager'); ?>">check_box</i>
                      <?php endif; ?>
                    </td>
                    <td class="pn-customers-manager-budget-col-actions">
                      <?php if ( $has_alt ) : ?>
                        <a href="#" class="pn-cm-budget-row-swap-alt pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Toggle alternative', 'pn-customers-manager'); ?>">
                          <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">swap_horiz</i>
                        </a>
                      <?php elseif ( ! $item_alt_of ) : ?>
                        <a href="#" class="pn-cm-budget-row-add-alt pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Add alternative', 'pn-customers-manager'); ?>">
                          <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer" style="font-size:16px;">add_circle_outline</i>
                        </a>
                      <?php endif; ?>
                      <a href="#" class="pn-cm-budget-row-edit pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Edit', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">edit</i>
                      </a>
                      <a href="#" class="pn-cm-budget-row-delete pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i>
                      </a>
                    </td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pn-customers-manager-text-align-right pn-customers-manager-mt-15">
          <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-budget-add-phase">
            <?php esc_html_e('Add phase', 'pn-customers-manager'); ?>
          </button>
          <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-budget-add-item">
            <?php esc_html_e('Add item', 'pn-customers-manager'); ?>
          </button>
          <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-budget-add-image">
            <?php esc_html_e('Add image', 'pn-customers-manager'); ?>
          </button>
          <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-cm-budget-export-items" data-budget-id="<?php echo esc_attr($budget_id); ?>">
            <?php esc_html_e('Export items', 'pn-customers-manager'); ?>
          </button>
          <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-cm-budget-import-items" data-budget-id="<?php echo esc_attr($budget_id); ?>">
            <?php esc_html_e('Import items', 'pn-customers-manager'); ?>
          </button>
          <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-cm-budget-ai-prompt-toggle" data-budget-id="<?php echo esc_attr($budget_id); ?>">
            <?php esc_html_e('AI prompt', 'pn-customers-manager'); ?>
          </button>
        </div>
        <?php echo self::render_ai_prompt_block($budget_id); ?>
      </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render totals display.
   *
   * @param int $budget_id
   * @return string
   */
  private function render_totals_display($budget_id) {
    if (empty($budget_id)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('Save the budget first to see totals.', 'pn-customers-manager') . '</p>';
    }

    $subtotal = floatval(get_post_meta($budget_id, 'pn_cm_budget_subtotal', true));
    $discount_amount = floatval(get_post_meta($budget_id, 'pn_cm_budget_discount_amount', true));
    $discount_rate = floatval(get_post_meta($budget_id, 'pn_cm_budget_discount_rate', true));
    $tax_amount = floatval(get_post_meta($budget_id, 'pn_cm_budget_tax_amount', true));
    $tax_rate = floatval(get_post_meta($budget_id, 'pn_cm_budget_tax_rate', true));
    $total = floatval(get_post_meta($budget_id, 'pn_cm_budget_total', true));

    ob_start();
    ?>
      <div class="pn-customers-manager-budget-totals">
        <table class="pn-customers-manager-width-100-percent">
          <tr>
            <td class="pn-customers-manager-text-align-right"><strong><?php esc_html_e('Subtotal', 'pn-customers-manager'); ?></strong></td>
            <td class="pn-customers-manager-text-align-right pn-customers-manager-budget-totals-value" id="pn-cm-budget-admin-subtotal"><?php echo esc_html(self::format_currency($subtotal)); ?></td>
          </tr>
          <tr id="pn-cm-budget-admin-discount-row" <?php echo $discount_rate > 0 ? '' : 'style="display:none"'; ?>>
            <td class="pn-customers-manager-text-align-right"><strong><?php echo esc_html(sprintf(__('Discount (%s%%)', 'pn-customers-manager'), number_format($discount_rate, 2))); ?></strong></td>
            <td class="pn-customers-manager-text-align-right pn-customers-manager-budget-totals-value" id="pn-cm-budget-admin-discount">-<?php echo esc_html(self::format_currency($discount_amount)); ?></td>
          </tr>
          <tr id="pn-cm-budget-admin-tax-row" <?php echo $tax_rate > 0 ? '' : 'style="display:none"'; ?>>
            <td class="pn-customers-manager-text-align-right"><strong><?php echo esc_html(sprintf(__('Tax (%s%%)', 'pn-customers-manager'), number_format($tax_rate, 2))); ?></strong></td>
            <td class="pn-customers-manager-text-align-right pn-customers-manager-budget-totals-value" id="pn-cm-budget-admin-tax"><?php echo esc_html(self::format_currency($tax_amount)); ?></td>
          </tr>
          <tr class="pn-customers-manager-budget-total-row">
            <td class="pn-customers-manager-text-align-right"><strong><?php esc_html_e('Total', 'pn-customers-manager'); ?></strong></td>
            <td class="pn-customers-manager-text-align-right pn-customers-manager-budget-totals-value" id="pn-cm-budget-admin-total"><strong><?php echo esc_html(self::format_currency($total)); ?></strong></td>
          </tr>
        </table>
      </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render a quick-access public URL block for the top of the metabox.
   *
   * @param int $budget_id
   * @return string
   */
  private function render_quick_link_block( $budget_id ) {
    $token = get_post_meta( $budget_id, 'pn_cm_budget_token', true );
    if ( empty( $token ) ) {
      return '';
    }
    $public_url = self::pn_cm_budget_get_public_url( $token );

    ob_start();
    ?>
      <div class="pn-cm-budget-admin-quick-link">
        <a href="<?php echo esc_url( $public_url ); ?>" target="_blank" class="pn-cm-budget-admin-quick-link-btn">
          <i class="material-icons-outlined">open_in_new</i>
          <?php esc_html_e( 'View public budget', 'pn-customers-manager' ); ?>
        </a>
        <a href="#" class="pn-cm-budget-admin-quick-link-copy pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="<?php echo esc_attr( $public_url ); ?>">
          <i class="material-icons-outlined">content_copy</i>
          <?php esc_html_e( 'Copy URL', 'pn-customers-manager' ); ?>
        </a>
      </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render public link section.
   *
   * @param int $budget_id
   * @return string
   */
  private function render_public_link($budget_id) {
    if (empty($budget_id)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('Save the budget first to generate a public link.', 'pn-customers-manager') . '</p>';
    }

    $token = get_post_meta($budget_id, 'pn_cm_budget_token', true);

    if (empty($token)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('No token generated yet. Save the budget to generate one.', 'pn-customers-manager') . '</p>';
    }

    $public_url = self::pn_cm_budget_get_public_url($token);

    ob_start();
    ?>
      <div class="pn-customers-manager-budget-public-link">
        <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
          <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-80-percent">
            <input type="text" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-budget-public-url" value="<?php echo esc_attr($public_url); ?>" readonly />
          </div>
          <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
            <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="<?php echo esc_attr($public_url); ?>">
              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle">content_copy</i>
              <?php esc_html_e('Copy', 'pn-customers-manager'); ?>
            </a>
          </div>
        </div>
      </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render the AI prompt block with CSV format instructions.
   *
   * @param int $budget_id
   * @return string HTML block.
   */
  public static function render_ai_prompt_block( $budget_id ) {
    $prompt_text = 'Generate a CSV file with budget line items using the following format.' . "\n\n"
      . 'CSV SPECIFICATIONS:' . "\n"
      . '- Encoding: UTF-8' . "\n"
      . '- Separator: comma (,)' . "\n"
      . '- First row: header row (mandatory)' . "\n"
      . '- Wrap descriptions containing commas in double quotes' . "\n\n"
      . 'COLUMNS (in order):' . "\n"
      . 'item_type,description,quantity,unit_price,is_optional,is_recurring,recurring_period' . "\n\n"
      . 'FIELD VALUES:' . "\n"
      . '- item_type: "phase" (section header), "fixed" (fixed price item), "hours" (hourly rate item)' . "\n"
      . '- description: text describing the item (may contain HTML)' . "\n"
      . '- quantity: decimal number (leave empty or 0 for phases)' . "\n"
      . '- unit_price: decimal number (leave empty or 0 for phases)' . "\n"
      . '- is_optional: 0 (included) or 1 (optional, client can toggle)' . "\n"
      . '- is_recurring: 0 (one-time) or 1 (recurring charge)' . "\n"
      . '- recurring_period: "daily", "weekly", "monthly", "yearly" or empty' . "\n\n"
      . 'EXAMPLE CSV:' . "\n"
      . 'item_type,description,quantity,unit_price,is_optional,is_recurring,recurring_period' . "\n"
      . 'phase,"Web Design",,,0,0,' . "\n"
      . 'hours,"Responsive layout design",40,75.00,0,0,' . "\n"
      . 'fixed,"Custom icons pack",1,350.00,1,0,' . "\n"
      . 'phase,"Development",,,0,0,' . "\n"
      . 'hours,"Frontend development",80,85.00,0,0,' . "\n"
      . 'hours,"Backend development",60,90.00,0,0,' . "\n"
      . 'fixed,"API integration",1,500.00,1,0,' . "\n"
      . 'phase,"Maintenance",,,0,0,' . "\n"
      . 'fixed,"Monthly hosting",1,200.00,0,1,monthly' . "\n"
      . 'fixed,"Annual SSL certificate",1,50.00,0,1,yearly' . "\n\n"
      . 'RULES:' . "\n"
      . '- Phases group the items that follow them until the next phase' . "\n"
      . '- Phases have no quantity, price, optional or recurring values' . "\n"
      . '- Each row after a phase belongs to that phase' . "\n"
      . '- Recurring items are excluded from the one-time subtotal';

    ob_start();
    ?>
    <div class="pn-cm-budget-ai-prompt-block" style="display:none;">
      <div class="pn-cm-budget-ai-prompt-header">
        <strong><?php esc_html_e('AI prompt — CSV format instructions', 'pn-customers-manager'); ?></strong>
        <button type="button" class="pn-cm-budget-ai-prompt-copy pn-customers-manager-btn pn-customers-manager-btn-mini">
          <?php esc_html_e('Copy', 'pn-customers-manager'); ?>
        </button>
      </div>
      <pre class="pn-cm-budget-ai-prompt-text"><?php echo esc_html($prompt_text); ?></pre>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Get budget items from post meta.
   *
   * @param int $budget_id
   * @return array Array of item arrays sorted by sort_order.
   */
  public static function get_budget_items( $budget_id ) {
    $items = get_post_meta( $budget_id, 'pn_cm_budget_items', true );
    if ( empty( $items ) || ! is_array( $items ) ) {
      return [];
    }
    usort( $items, function ( $a, $b ) {
      return intval( $a['sort_order'] ) - intval( $b['sort_order'] );
    } );
    return $items;
  }

  /**
   * Save budget items to post meta.
   *
   * @param int   $budget_id
   * @param array $items
   */
  public static function save_budget_items( $budget_id, $items ) {
    // Auto-recalculate each item total.
    foreach ( $items as &$item ) {
      if ( isset( $item['item_type'] ) && in_array( $item['item_type'], array( 'phase', 'image', 'image_compare' ), true ) ) {
        $item['total'] = 0;
      } else {
        $item['total'] = round( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ), 2 );
      }
    }
    unset( $item );
    update_post_meta( $budget_id, 'pn_cm_budget_items', $items );
  }

  /**
   * Get a single budget item by its ID.
   *
   * @param int $budget_id
   * @param int $item_id
   * @return array|null
   */
  public static function get_budget_item( $budget_id, $item_id ) {
    $items = self::get_budget_items( $budget_id );
    foreach ( $items as $item ) {
      if ( intval( $item['id'] ) === intval( $item_id ) ) {
        return $item;
      }
    }
    return null;
  }

  /**
   * Get and increment next item ID counter.
   *
   * @param int $budget_id
   * @return int
   */
  public static function get_next_item_id( $budget_id ) {
    $next = intval( get_post_meta( $budget_id, 'pn_cm_budget_next_item_id', true ) );
    if ( $next < 1 ) {
      $next = 1;
    }
    update_post_meta( $budget_id, 'pn_cm_budget_next_item_id', $next + 1 );
    return $next;
  }

  /**
   * Add a new item to a budget.
   *
   * @param int   $budget_id
   * @param array $data Item data (item_type, description, quantity, unit_price, is_optional, is_selected).
   * @return int The new item ID.
   */
  public static function add_budget_item( $budget_id, $data ) {
    $items    = self::get_budget_items( $budget_id );
    $new_id   = self::get_next_item_id( $budget_id );
    $max_sort = 0;
    foreach ( $items as $item ) {
      if ( intval( $item['sort_order'] ) > $max_sort ) {
        $max_sort = intval( $item['sort_order'] );
      }
    }

    $new_item = [
      'id'          => $new_id,
      'item_type'   => isset( $data['item_type'] ) ? $data['item_type'] : 'fixed',
      'description' => isset( $data['description'] ) ? $data['description'] : '',
      'quantity'    => isset( $data['quantity'] ) ? floatval( $data['quantity'] ) : 1,
      'unit_price'  => isset( $data['unit_price'] ) ? floatval( $data['unit_price'] ) : 0,
      'total'       => 0,
      'is_optional' => isset( $data['is_optional'] ) ? intval( $data['is_optional'] ) : 0,
      'is_selected'      => isset( $data['is_selected'] ) ? intval( $data['is_selected'] ) : 1,
      'sort_order'       => isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : $max_sort + 1,
      'is_recurring'     => isset( $data['is_recurring'] ) ? intval( $data['is_recurring'] ) : 0,
      'recurring_period' => isset( $data['recurring_period'] ) ? sanitize_text_field( $data['recurring_period'] ) : '',
      'alt_of'           => isset( $data['alt_of'] ) ? intval( $data['alt_of'] ) : 0,
      'alt_active'       => isset( $data['alt_active'] ) ? sanitize_text_field( $data['alt_active'] ) : 'original',
    ];
    $new_item['total'] = round( $new_item['quantity'] * $new_item['unit_price'], 2 );

    $items[] = $new_item;
    self::save_budget_items( $budget_id, $items );

    return $new_id;
  }

  /**
   * Update an existing budget item.
   *
   * @param int   $budget_id
   * @param int   $item_id
   * @param array $data Fields to update.
   * @return bool
   */
  public static function update_budget_item( $budget_id, $item_id, $data ) {
    $items = self::get_budget_items( $budget_id );
    $found = false;
    foreach ( $items as &$item ) {
      if ( intval( $item['id'] ) === intval( $item_id ) ) {
        foreach ( $data as $key => $value ) {
          $item[ $key ] = $value;
        }
        $found = true;
        break;
      }
    }
    unset( $item );
    if ( $found ) {
      self::save_budget_items( $budget_id, $items );
    }
    return $found;
  }

  /**
   * Delete a budget item.
   *
   * @param int $budget_id
   * @param int $item_id
   * @return bool
   */
  public static function delete_budget_item( $budget_id, $item_id ) {
    $items    = self::get_budget_items( $budget_id );
    // Also remove any alternative items that reference this item.
    $filtered = array_values( array_filter( $items, function ( $item ) use ( $item_id ) {
      if ( intval( $item['id'] ) === intval( $item_id ) ) {
        return false;
      }
      if ( ! empty( $item['alt_of'] ) && intval( $item['alt_of'] ) === intval( $item_id ) ) {
        return false;
      }
      return true;
    } ) );
    if ( count( $filtered ) < count( $items ) ) {
      self::save_budget_items( $budget_id, $filtered );
      return true;
    }
    return false;
  }

  /**
   * Format amount with currency symbol from settings.
   *
   * @param float $amount
   * @return string
   */
  public static function format_currency($amount) {
    $symbol = get_option('pn_customers_manager_budget_currency_symbol', '$');
    $position = get_option('pn_customers_manager_budget_currency_position', 'before');
    $formatted = number_format($amount, 2, '.', ',');

    if ($position === 'after') {
      return $formatted . $symbol;
    }

    return $symbol . $formatted;
  }

  /**
   * Handle form save for budget (new / edit).
   *
   * @param int    $element_id
   * @param array  $key_value
   * @param string $cm_pn_form_type
   * @param string $cm_pn_form_subtype
   */
  public function pn_cm_budget_form_save($element_id, $key_value, $cm_pn_form_type, $cm_pn_form_subtype) {
    $post_type = !empty(get_post_type($element_id)) ? get_post_type($element_id) : 'pn_cm_budget';

    if ($post_type == 'pn_cm_budget') {
      switch ($cm_pn_form_type) {
        case 'post':
          switch ($cm_pn_form_subtype) {
            case 'post_new':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'pn_customers_manager_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $pn_cm_budget_title = isset($key_value['pn_cm_budget_title']) ? $key_value['pn_cm_budget_title'] : '';
              $post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();
              $budget_id = $post_functions->pn_customers_manager_insert_post(esc_html($pn_cm_budget_title), '', '', sanitize_title(esc_html($pn_cm_budget_title)), 'pn_cm_budget', 'publish', get_current_user_id());

              // Auto-generate number and token for new budget
              $budget_number = self::pn_cm_budget_generate_number();
              update_post_meta($budget_id, 'pn_cm_budget_number', $budget_number);

              $token = self::pn_cm_budget_generate_token();
              update_post_meta($budget_id, 'pn_cm_budget_token', $token);

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($budget_id, $key, $value);
                }
              }

              break;
            case 'post_edit':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'pn_customers_manager_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $budget_id = $element_id;
              $pn_cm_budget_title = isset($key_value['pn_cm_budget_title']) ? $key_value['pn_cm_budget_title'] : get_the_title($budget_id);
              wp_update_post(['ID' => $budget_id, 'post_title' => $pn_cm_budget_title]);

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($budget_id, $key, $value);
                }
              }

              // Recalculate totals after edit
              self::pn_cm_budget_recalculate_totals($budget_id);

              break;
          }
      }
    }
  }

  /**
   * Register scripts needed for budgets.
   */
  public function pn_cm_budget_register_scripts() {
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

  /**
   * Print scripts needed for budgets.
   */
  public function pn_cm_budget_print_scripts() {
    wp_print_scripts(['pn-customers-manager-aux', 'pn-customers-manager-forms', 'pn-customers-manager-selector']);
  }

  /**
   * Define custom columns for the WP Admin budget list table.
   *
   * @param array $columns
   * @return array
   */
  public function pn_cm_budget_admin_columns( $columns ) {
    $new = [];
    $new['cb'] = $columns['cb'];
    $new['pn_cm_budget_number'] = esc_html__( 'Number', 'pn-customers-manager' );
    $new['title'] = $columns['title'];
    $new['pn_cm_budget_organization'] = esc_html__( 'Organization', 'pn-customers-manager' );
    $new['pn_cm_budget_status'] = esc_html__( 'Status', 'pn-customers-manager' );
    $new['pn_cm_budget_total'] = esc_html__( 'Total', 'pn-customers-manager' );
    $new['pn_cm_budget_date_col'] = esc_html__( 'Date', 'pn-customers-manager' );
    return $new;
  }

  /**
   * Render custom column content for the WP Admin budget list table.
   *
   * @param string $column
   * @param int    $post_id
   */
  public function pn_cm_budget_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
      case 'pn_cm_budget_number':
        $number = get_post_meta( $post_id, 'pn_cm_budget_number', true );
        echo esc_html( $number );
        break;

      case 'pn_cm_budget_organization':
        $org_id = get_post_meta( $post_id, 'pn_cm_budget_organization_id', true );
        if ( ! empty( $org_id ) ) {
          echo esc_html( get_the_title( $org_id ) );
        } else {
          echo '—';
        }
        break;

      case 'pn_cm_budget_status':
        $status = get_post_meta( $post_id, 'pn_cm_budget_status', true );
        echo wp_kses_post( self::get_status_badge( $status ? $status : 'draft' ) );
        break;

      case 'pn_cm_budget_total':
        $total = floatval( get_post_meta( $post_id, 'pn_cm_budget_total', true ) );
        echo esc_html( self::format_currency( $total ) );
        break;

      case 'pn_cm_budget_date_col':
        $date = get_post_meta( $post_id, 'pn_cm_budget_date', true );
        if ( ! empty( $date ) ) {
          echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) );
        } else {
          echo '—';
        }
        break;
    }
  }

  /**
   * Make custom columns sortable.
   *
   * @param array $columns
   * @return array
   */
  public function pn_cm_budget_admin_sortable_columns( $columns ) {
    $columns['pn_cm_budget_number']   = 'pn_cm_budget_number';
    $columns['pn_cm_budget_status']   = 'pn_cm_budget_status';
    $columns['pn_cm_budget_total']    = 'pn_cm_budget_total';
    $columns['pn_cm_budget_date_col'] = 'pn_cm_budget_date';
    return $columns;
  }

  /**
   * Handle sorting by custom meta for budget columns.
   *
   * @param WP_Query $query
   */
  public function pn_cm_budget_admin_sort_columns( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
      return;
    }
    if ( $query->get( 'post_type' ) !== 'pn_cm_budget' ) {
      return;
    }
    $orderby = $query->get( 'orderby' );
    switch ( $orderby ) {
      case 'pn_cm_budget_number':
        $query->set( 'meta_key', 'pn_cm_budget_number' );
        $query->set( 'orderby', 'meta_value' );
        break;
      case 'pn_cm_budget_status':
        $query->set( 'meta_key', 'pn_cm_budget_status' );
        $query->set( 'orderby', 'meta_value' );
        break;
      case 'pn_cm_budget_total':
        $query->set( 'meta_key', 'pn_cm_budget_total' );
        $query->set( 'orderby', 'meta_value_num' );
        break;
      case 'pn_cm_budget_date':
        $query->set( 'meta_key', 'pn_cm_budget_date' );
        $query->set( 'orderby', 'meta_value' );
        break;
    }
  }

  /**
   * Enqueue admin styles for the budget list table.
   *
   * @param string $hook
   */
  public function pn_cm_budget_admin_list_styles( $hook ) {
    if ( $hook !== 'edit.php' ) {
      return;
    }
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'pn_cm_budget' ) {
      return;
    }
    wp_enqueue_style( 'pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-budget.css', [], PN_CUSTOMERS_MANAGER_VERSION );
  }

  /**
   * Get status badge HTML.
   *
   * @param string $status
   * @return string
   */
  public static function get_status_badge($status) {
    $labels = [
      'draft' => esc_html__('Draft', 'pn-customers-manager'),
      'sent' => esc_html__('Sent', 'pn-customers-manager'),
      'accepted' => esc_html__('Accepted', 'pn-customers-manager'),
      'rejected' => esc_html__('Rejected', 'pn-customers-manager'),
    ];

    $label = isset($labels[$status]) ? $labels[$status] : esc_html($status);

    return '<span class="pn-customers-manager-budget-status-badge pn-cm-budget-list-status pn-cm-budget-list-status-' . esc_attr($status) . '">' . $label . '</span>';
  }

  /**
   * Render the budget list wrapper.
   *
   * @return string
   */
  public function pn_cm_budget_list_wrapper() {
    if (!is_user_logged_in()) {
      return do_shortcode('[pn-customers-manager-call-to-action'
        . ' pn_customers_manager_call_to_action_icon="admin_panel_settings"'
        . ' pn_customers_manager_call_to_action_title="' . esc_attr__('Access restricted', 'pn-customers-manager') . '"'
        . ' pn_customers_manager_call_to_action_content="' . esc_attr__('You do not have permission to access this section.', 'pn-customers-manager') . '"'
        . ' pn_customers_manager_call_to_action_button_link="#"'
        . ' pn_customers_manager_call_to_action_button_text="' . esc_attr__('Log in', 'pn-customers-manager') . '"'
        . ' pn_customers_manager_call_to_action_button_class="userspn-profile-popup-btn"'
        . ' pn_customers_manager_call_to_action_button_data_key="data-userspn-action"'
        . ' pn_customers_manager_call_to_action_button_data_value="login"'
        . ']');
    }

    if (!current_user_can('manage_options')) {
      return do_shortcode('[pn-customers-manager-call-to-action'
        . ' pn_customers_manager_call_to_action_icon="admin_panel_settings"'
        . ' pn_customers_manager_call_to_action_title="' . esc_attr__('Access restricted', 'pn-customers-manager') . '"'
        . ' pn_customers_manager_call_to_action_content="' . esc_attr__('You do not have permission to access this section.', 'pn-customers-manager') . '"'
        . ']');
    }

    // Enqueue budget styles and scripts on front-end so list actions work
    if (!is_admin()) {
      wp_enqueue_style('pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-budget.css', [], PN_CUSTOMERS_MANAGER_VERSION);
      wp_enqueue_script('pn-customers-manager-budget-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-budget-admin.js', ['jquery', 'wph-trumbowyg'], PN_CUSTOMERS_MANAGER_VERSION, true);
      wp_localize_script('pn-customers-manager-budget-admin', 'pnCmBudgetAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pn-customers-manager-nonce'),
        'budgetId' => 0,
        'currencySymbol' => get_option('pn_customers_manager_budget_currency_symbol', '€'),
        'currencyPosition' => get_option('pn_customers_manager_budget_currency_position', 'after'),
        'defaultHourlyRate' => get_option('pn_customers_manager_budget_default_hourly_rate', '0'),
        'i18n' => [
          'error' => esc_html__('An error occurred.', 'pn-customers-manager'),
          'confirmDelete' => esc_html__('Are you sure you want to delete this item?', 'pn-customers-manager'),
          'confirmSend' => esc_html__('Are you sure you want to send this budget?', 'pn-customers-manager'),
          'budgetSent' => esc_html__('Budget sent successfully.', 'pn-customers-manager'),
          'budgetRemoved' => esc_html__('Budget removed successfully.', 'pn-customers-manager'),
          'budgetDuplicated' => esc_html__('Budget duplicated successfully.', 'pn-customers-manager'),
          'noDescription' => esc_html__('Please enter a description.', 'pn-customers-manager'),
          'newPhase' => esc_html__('New phase', 'pn-customers-manager'),
          'confirmGenerateInvoice' => esc_html__('Are you sure you want to generate an invoice from this budget?', 'pn-customers-manager'),
          'invoiceGenerated' => esc_html__('Invoice generated successfully.', 'pn-customers-manager'),
        ],
        'recurringLabels' => [
          'daily'   => esc_html__( 'Daily', 'pn-customers-manager' ),
          'weekly'  => esc_html__( 'Weekly', 'pn-customers-manager' ),
          'monthly' => esc_html__( 'Monthly', 'pn-customers-manager' ),
          'yearly'  => esc_html__( 'Yearly', 'pn-customers-manager' ),
        ],
      ]);
    }

    ob_start();
    ?>
      <div class="pn-customers-manager-cpt-list pn-customers-manager-pn_cm_budget-list pn-customers-manager-mb-50 pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mb-100">
        <div class="pn-customers-manager-cpt-search-container pn-customers-manager-mb-20 pn-customers-manager-text-align-right">
          <div class="pn-customers-manager-cpt-search-wrapper">
            <input type="text" class="pn-customers-manager-cpt-search-input pn-customers-manager-input pn-customers-manager-display-none" placeholder="<?php esc_attr_e('Filter...', 'pn-customers-manager'); ?>" />
            <i class="material-icons-outlined pn-customers-manager-cpt-search-toggle pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Search Budgets', 'pn-customers-manager'); ?>">search</i>

            <a href="#" id="pn-cm-budget-csv-export-btn" class="pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" title="<?php esc_attr_e('Export CSV', 'pn-customers-manager'); ?>">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle">cloud_download</i>
            </a>
            <a href="#" id="pn-cm-budget-csv-import-btn" class="pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" title="<?php esc_attr_e('Import CSV', 'pn-customers-manager'); ?>">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle">upload</i>
            </a>

            <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-add" data-pn-customers-manager-ajax-type="pn_cm_budget_new">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Add new Budget', 'pn-customers-manager'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="pn-customers-manager-cpt-list-wrapper pn-customers-manager-pn_cm_budget-list-wrapper">
          <?php echo wp_kses(self::pn_cm_budget_list(), PN_CUSTOMERS_MANAGER_KSES); ?>
        </div>
      </div>
    <?php PN_CUSTOMERS_MANAGER_Budget_Csv::pn_customers_manager_budget_csv_render_ui(); ?>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * Render the budget list.
   *
   * @return string
   */
  public function pn_cm_budget_list() {
    if (!is_user_logged_in()) {
      $cta_atts = [
        'pn_customers_manager_call_to_action_class' => 'pn-customers-manager-p-50 pn-customers-manager-pt-30 pn-customers-manager-max-width-700 pn-customers-manager-margin-auto',
        'pn_customers_manager_call_to_action_icon' => 'admin_panel_settings',
        'pn_customers_manager_call_to_action_title' => __('You need an account', 'pn-customers-manager'),
        'pn_customers_manager_call_to_action_content' => __('You must be registered on the platform to access this tool.', 'pn-customers-manager'),
        'pn_customers_manager_call_to_action_button_text' => __('Create an account', 'pn-customers-manager'),
        'pn_customers_manager_call_to_action_button_link' => '#',
        'pn_customers_manager_call_to_action_button_class' => 'userspn-profile-popup-btn',
        'pn_customers_manager_call_to_action_button_data_key' => 'data-userspn-action',
        'pn_customers_manager_call_to_action_button_data_value' => 'register',
      ];

      $shortcode_atts = '';
      foreach ($cta_atts as $key => $value) {
        $shortcode_atts .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
      }

      return do_shortcode('[pn-customers-manager-call-to-action' . $shortcode_atts . ']');
    }

    $budget_atts = [
      'fields' => 'ids',
      'numberposts' => -1,
      'post_type' => 'pn_cm_budget',
      'post_status' => 'any',
      'orderby' => 'date',
      'order' => 'DESC',
    ];

    if (class_exists('Polylang')) {
      $budget_atts['lang'] = pll_current_language('slug');
    }

    $budgets = get_posts($budget_atts);

    ob_start();
    ?>
      <?php if (!empty($budgets)): ?>
        <table class="pn-cm-budget-list-table">
          <thead>
            <tr>
              <th class="pn-cm-budget-list-col-number"><?php esc_html_e('Number', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-budget-list-col-title"><?php esc_html_e('Title', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-budget-list-col-org"><?php esc_html_e('Organization', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-budget-list-col-date"><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-budget-list-col-status"><?php esc_html_e('Status', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-budget-list-col-total"><?php esc_html_e('Total', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-budget-list-col-actions"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($budgets as $budget_id):
              $budget_number = get_post_meta($budget_id, 'pn_cm_budget_number', true);
              $budget_title = get_the_title($budget_id);
              $budget_status = get_post_meta($budget_id, 'pn_cm_budget_status', true);
              $budget_date = get_post_meta($budget_id, 'pn_cm_budget_date', true);
              $budget_valid_until = get_post_meta($budget_id, 'pn_cm_budget_valid_until', true);
              $budget_total = floatval(get_post_meta($budget_id, 'pn_cm_budget_total', true));
              $org_id = get_post_meta($budget_id, 'pn_cm_budget_organization_id', true);
              $org_name = !empty($org_id) ? get_the_title($org_id) : '';
              $accepted_at = get_post_meta($budget_id, 'pn_cm_budget_accepted_at', true);
              $rejected_at = get_post_meta($budget_id, 'pn_cm_budget_rejected_at', true);
              $budget_invoice_ids = get_post_meta($budget_id, 'pn_cm_budget_invoice_ids', true);
              $has_invoices = is_array($budget_invoice_ids) && !empty($budget_invoice_ids);
            ?>
              <tr class="pn-customers-manager-pn_cm_budget-list-item" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
                <td class="pn-cm-budget-list-col-number" data-label="<?php esc_attr_e('Number', 'pn-customers-manager'); ?>">
                  <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-view" data-pn-customers-manager-ajax-type="pn_cm_budget_view" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
                    <strong><?php echo esc_html($budget_number); ?></strong>
                  </a>
                </td>
                <td class="pn-cm-budget-list-col-title" data-label="<?php esc_attr_e('Title', 'pn-customers-manager'); ?>">
                  <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-view" data-pn-customers-manager-ajax-type="pn_cm_budget_view" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
                    <?php echo esc_html($budget_title); ?>
                  </a>
                </td>
                <td class="pn-cm-budget-list-col-org" data-label="<?php esc_attr_e('Organization', 'pn-customers-manager'); ?>">
                  <?php echo esc_html($org_name); ?>
                </td>
                <td class="pn-cm-budget-list-col-date" data-label="<?php esc_attr_e('Date', 'pn-customers-manager'); ?>">
                  <?php if (!empty($budget_date)): ?>
                    <?php echo esc_html($budget_date); ?>
                  <?php endif; ?>
                  <?php if (!empty($budget_valid_until) && in_array($budget_status, ['draft', 'sent'], true)): ?>
                    <br><small><?php echo esc_html(sprintf(
                      /* translators: %s: date */
                      __('Valid until %s', 'pn-customers-manager'),
                      $budget_valid_until
                    )); ?></small>
                  <?php endif; ?>
                  <?php if ($budget_status === 'accepted' && !empty($accepted_at)): ?>
                    <br><small><?php echo esc_html(sprintf(
                      /* translators: %s: date */
                      __('Accepted %s', 'pn-customers-manager'),
                      date_i18n(get_option('date_format'), strtotime($accepted_at))
                    )); ?></small>
                  <?php elseif ($budget_status === 'rejected' && !empty($rejected_at)): ?>
                    <br><small><?php echo esc_html(sprintf(
                      /* translators: %s: date */
                      __('Rejected %s', 'pn-customers-manager'),
                      date_i18n(get_option('date_format'), strtotime($rejected_at))
                    )); ?></small>
                  <?php endif; ?>
                </td>
                <td class="pn-cm-budget-list-col-status" data-label="<?php esc_attr_e('Status', 'pn-customers-manager'); ?>">
                  <?php echo wp_kses(self::get_status_badge($budget_status), PN_CUSTOMERS_MANAGER_KSES); ?>
                  <?php if ($has_invoices): ?>
                    <br><small><i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-14">receipt_long</i> <?php echo esc_html(sprintf(
                      /* translators: %d: number of invoices */
                      _n('%d invoice', '%d invoices', count($budget_invoice_ids), 'pn-customers-manager'),
                      count($budget_invoice_ids)
                    )); ?></small>
                  <?php endif; ?>
                </td>
                <td class="pn-cm-budget-list-col-total" data-label="<?php esc_attr_e('Total', 'pn-customers-manager'); ?>">
                  <strong><?php echo esc_html(self::format_currency($budget_total)); ?></strong>
                </td>
                <td class="pn-cm-budget-list-col-actions">
                  <div class="pn-customers-manager-position-relative">
                    <i class="material-icons-outlined pn-customers-manager-menu-more-btn pn-customers-manager-cursor-pointer pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30">more_vert</i>

                    <div class="pn-customers-manager-menu-more pn-customers-manager-z-index-99 pn-customers-manager-display-none-soft">
                      <ul class="pn-customers-manager-list-style-none">
                        <?php
                        $budget_token = get_post_meta($budget_id, 'pn_cm_budget_token', true);
                        if (!empty($budget_token)):
                          $budget_public_url = self::pn_cm_budget_get_public_url($budget_token);
                        ?>
                        <li>
                          <a href="<?php echo esc_url($budget_public_url); ?>" target="_blank" class="pn-customers-manager-text-decoration-none">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('View Page', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">open_in_new</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <?php endif; ?>
                        <li>
                          <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-view" data-pn-customers-manager-ajax-type="pn_cm_budget_view" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('View Budget', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">visibility</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <li>
                          <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-edit" data-pn-customers-manager-ajax-type="pn_cm_budget_edit" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Edit Budget', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">edit</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <li>
                          <a href="#" class="pn-customers-manager-pn_cm_budget-duplicate-post">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Duplicate Budget', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">content_copy</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <li>
                          <a href="#" class="pn-customers-manager-pn_cm_budget-send-post pn-customers-manager-text-decoration-none" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Send Budget', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">send</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <li>
                          <a href="#" class="pn-customers-manager-pn_cm_budget-generate-invoice pn-customers-manager-text-decoration-none" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Generate Invoice', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">receipt_long</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <li>
                          <a href="#" class="pn-customers-manager-popup-open" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-remove">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Remove Budget', 'pn-customers-manager'); ?></p>
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
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="pn-customers-manager-text-align-center pn-customers-manager-mt-30"><?php esc_html_e('No budgets found.', 'pn-customers-manager'); ?></p>
      <?php endif ?>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * View popup content for a budget.
   *
   * @param int $budget_id
   * @return string
   */
  public function pn_cm_budget_view($budget_id) {
    ob_start();
    self::pn_cm_budget_register_scripts();
    self::pn_cm_budget_print_scripts();

    $budget_number = get_post_meta($budget_id, 'pn_cm_budget_number', true);
    $budget_status = get_post_meta($budget_id, 'pn_cm_budget_status', true);
    ?>
      <div class="pn_cm_budget-view pn-customers-manager-p-30" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
        <h4 class="pn-customers-manager-text-align-center">
          <?php echo esc_html($budget_number); ?> &mdash; <?php echo esc_html(get_the_title($budget_id)); ?>
        </h4>
        <p class="pn-customers-manager-text-align-center">
          <?php echo wp_kses(self::get_status_badge($budget_status), PN_CUSTOMERS_MANAGER_KSES); ?>
        </p>

        <div class="pn_cm_budget-view-list">
          <?php foreach (array_merge(self::pn_cm_budget_get_fields(), self::pn_cm_budget_get_fields_meta($budget_id)) as $pn_customers_manager_field): ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_display_wrapper($pn_customers_manager_field, 'post', $budget_id), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <?php
          // Signature block for accepted budgets.
          if ( 'accepted' === $budget_status ) {
            $sig_id         = get_post_meta( $budget_id, 'pn_cm_budget_signature_attachment_id', true );
            $sig_name       = get_post_meta( $budget_id, 'pn_cm_budget_accepted_by_name', true );
            $sig_email      = get_post_meta( $budget_id, 'pn_cm_budget_accepted_by_email', true );
            $sig_date       = get_post_meta( $budget_id, 'pn_cm_budget_accepted_at', true );
            $sig_url        = ! empty( $sig_id ) ? wp_get_attachment_url( intval( $sig_id ) ) : '';
            if ( $sig_url || $sig_name || $sig_email ) {
              ?>
              <div class="pn-customers-manager-mt-20 pn-customers-manager-pt-20" style="border-top:1px solid #eee;text-align:center;">
                <p class="pn-customers-manager-mb-10"><i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-18">draw</i> <strong><?php esc_html_e( 'Signature', 'pn-customers-manager' ); ?></strong></p>
                <?php if ( $sig_url ) : ?>
                  <div style="display:inline-block;border-bottom:2px solid #333;padding-bottom:8px;margin-bottom:8px;">
                    <img src="<?php echo esc_url( $sig_url ); ?>" alt="<?php esc_attr_e( 'Signature', 'pn-customers-manager' ); ?>" style="max-width:250px;height:auto;display:block;" />
                  </div>
                <?php endif; ?>
                <?php if ( ! empty( $sig_name ) ) : ?>
                  <p style="margin:4px 0 0;font-weight:600;"><?php echo esc_html( $sig_name ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $sig_email ) ) : ?>
                  <p style="margin:2px 0 0;font-size:13px;color:#666;"><?php echo esc_html( $sig_email ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $sig_date ) ) : ?>
                  <p style="margin:2px 0 0;font-size:12px;color:#999;"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $sig_date ) ) ); ?></p>
                <?php endif; ?>
              </div>
              <?php
            }
          }
          ?>

          <div class="pn-customers-manager-text-align-right pn-customers-manager-budget pn-customers-manager-mt-20" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>">
            <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-popup-open-ajax" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-edit" data-pn-customers-manager-ajax-type="pn_cm_budget_edit"><?php esc_html_e('Edit Budget', 'pn-customers-manager'); ?></a>
            <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-pn_cm_budget-generate-invoice" data-pn_cm_budget-id="<?php echo esc_attr($budget_id); ?>"><i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-18">receipt_long</i> <?php esc_html_e('Generate Invoice', 'pn-customers-manager'); ?></a>
          </div>

          <?php
          // Linked invoices section
          $linked_invoice_ids = get_post_meta($budget_id, 'pn_cm_budget_invoice_ids', true);
          if (is_array($linked_invoice_ids) && !empty($linked_invoice_ids)): ?>
            <div class="pn-customers-manager-linked-documents pn-customers-manager-mt-20 pn-customers-manager-pt-20" style="border-top: 1px solid #eee;">
              <p class="pn-customers-manager-mb-10"><i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-18">receipt_long</i> <strong><?php esc_html_e('Linked Invoices', 'pn-customers-manager'); ?></strong></p>
              <?php foreach ($linked_invoice_ids as $linked_invoice_id):
                if (get_post_status($linked_invoice_id) === false) continue;
                $inv_number = get_post_meta($linked_invoice_id, 'pn_cm_invoice_number', true);
                $inv_status = get_post_meta($linked_invoice_id, 'pn_cm_invoice_status', true);
              ?>
                <div class="pn-customers-manager-mb-5">
                  <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-view" data-pn-customers-manager-ajax-type="pn_cm_invoice_view" data-pn_cm_invoice-id="<?php echo esc_attr($linked_invoice_id); ?>">
                    <?php echo esc_html($inv_number); ?> &mdash; <?php echo esc_html(get_the_title($linked_invoice_id)); ?>
                    <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_status_badge($inv_status), PN_CUSTOMERS_MANAGER_KSES); ?>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * New budget form.
   *
   * @return string
   */
  public function pn_cm_budget_new() {
    if (!is_user_logged_in()) {
      wp_die(esc_html__('You must be logged in to create a new budget.', 'pn-customers-manager'), esc_html__('Access Denied', 'pn-customers-manager'), ['response' => 403]);
    }

    ob_start();
    self::pn_cm_budget_register_scripts();
    self::pn_cm_budget_print_scripts();
    ?>
      <div class="pn_cm_budget-new pn-customers-manager-p-30">
        <h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Add new Budget', 'pn-customers-manager'); ?></h4>

        <form action="" method="post" id="pn-customers-manager-budget-form-new" class="pn-customers-manager-form">
          <?php foreach (array_merge(self::pn_cm_budget_get_fields(), self::pn_cm_budget_get_fields_meta(0)) as $pn_customers_manager_field): ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post'), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right">
            <input class="pn-customers-manager-btn" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_new" data-pn-customers-manager-post-type="pn_cm_budget" type="submit" value="<?php esc_attr_e('Create Budget', 'pn-customers-manager'); ?>"/>
          </div>
        </form>
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * Edit budget form.
   *
   * @param int $budget_id
   * @return string
   */
  public function pn_cm_budget_edit($budget_id) {
    ob_start();
    self::pn_cm_budget_register_scripts();
    self::pn_cm_budget_print_scripts();

    $budget_number = get_post_meta($budget_id, 'pn_cm_budget_number', true);
    ?>
      <div class="pn_cm_budget-edit pn-customers-manager-p-30">
        <p class="pn-customers-manager-text-align-center pn-customers-manager-mb-0"><?php esc_html_e('Editing', 'pn-customers-manager'); ?></p>
        <h4 class="pn-customers-manager-text-align-center pn-customers-manager-mb-30"><?php echo esc_html($budget_number); ?> &mdash; <?php echo esc_html(get_the_title($budget_id)); ?></h4>

        <form action="" method="post" id="pn-customers-manager-budget-form-edit" class="pn-customers-manager-form">
          <?php foreach (array_merge(self::pn_cm_budget_get_fields(), self::pn_cm_budget_get_fields_meta($budget_id)) as $pn_customers_manager_field): ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $budget_id), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right">
            <input class="pn-customers-manager-btn" type="submit" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_edit" data-pn-customers-manager-post-type="pn_cm_budget" data-pn-customers-manager-post-id="<?php echo esc_attr($budget_id); ?>" value="<?php esc_attr_e('Save Budget', 'pn-customers-manager'); ?>"/>
          </div>
        </form>
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * Add rewrite rule for public budget view.
   */
  public function pn_cm_budget_init_rewrite() {
    $slug = self::pn_cm_budget_get_public_slug();
    $rule = '^' . $slug . '/([a-zA-Z0-9]+)/?$';
    $query = 'index.php?pn_cm_budget_token=$matches[1]';
    add_rewrite_rule($rule, $query, 'top');

    // Auto-flush when the rule is missing or its query changed.
    $stored_rules = get_option('rewrite_rules', []);
    if (!is_array($stored_rules) || !isset($stored_rules[$rule]) || $stored_rules[$rule] !== $query) {
      flush_rewrite_rules(false);
    }
  }

  /**
   * Register the query var for budget token.
   *
   * @param array $vars
   * @return array
   */
  public function pn_cm_budget_query_vars($vars) {
    $vars[] = 'pn_cm_budget_token';
    return $vars;
  }

  /**
   * Handle template redirect for public budget view.
   */
  public function pn_cm_budget_template_redirect() {
    $token = get_query_var('pn_cm_budget_token');

    if (empty($token)) {
      return;
    }

    $token = sanitize_text_field($token);

    $post_statuses = ['publish'];
    if (current_user_can('manage_options')) {
      $post_statuses = ['publish', 'draft', 'private', 'pending'];
    }

    $budgets = get_posts([
      'post_type' => 'pn_cm_budget',
      'numberposts' => 1,
      'post_status' => $post_statuses,
      'meta_key' => 'pn_cm_budget_token',
      'meta_value' => $token,
      'fields' => 'ids',
    ]);

    if (empty($budgets)) {
      global $wp_query;
      $wp_query->set_404();
      status_header(404);
      return;
    }

    $budget_id = $budgets[0];
    $template = PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-pn_cm_budget.php';

    if (file_exists($template)) {
      // Make budget_id available in the template
      set_query_var('pn_cm_budget_id', $budget_id);
      include $template;
      exit;
    }
  }

  /**
   * Render the edit form for a single budget item via the Forms class.
   *
   * @param int $item_id
   * @param int $budget_id
   * @return string
   */
  public static function pn_cm_budget_render_item_edit_form( $item_id, $budget_id ) {
    $item = self::get_budget_item( $budget_id, $item_id );

    if ( ! $item ) {
      return '<p>' . esc_html__( 'Item not found.', 'pn-customers-manager' ) . '</p>';
    }

    $is_phase = ( $item['item_type'] === 'phase' );

    // Check if this item can have an alternative (not already an alt, and no alt exists for it).
    $alt_of     = ! empty( $item['alt_of'] ) ? intval( $item['alt_of'] ) : 0;
    $has_alt    = false;
    if ( ! $alt_of ) {
      $all_items = self::get_budget_items( $budget_id );
      foreach ( $all_items as $check ) {
        if ( ! empty( $check['alt_of'] ) && intval( $check['alt_of'] ) === intval( $item_id ) ) {
          $has_alt = true;
          break;
        }
      }
    }
    $can_add_alt = ( ! $alt_of && ! $has_alt );

    $fields = [];

    $fields['pn_cm_item_description'] = [
      'id'       => 'pn_cm_item_description',
      'class'    => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input'    => 'editor',
      'required' => true,
      'value'    => $item['description'],
      'label'    => __( 'Description', 'pn-customers-manager' ),
    ];

    if ( ! $is_phase ) {
      $fields['pn_cm_item_type'] = [
        'id'      => 'pn_cm_item_type',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'label'   => __( 'Type', 'pn-customers-manager' ),
        'value'   => $item['item_type'],
        'options' => [
          'hours' => esc_html__( 'Hours', 'pn-customers-manager' ),
          'fixed' => esc_html__( 'Fixed', 'pn-customers-manager' ),
        ],
      ];

      $fields['pn_cm_item_quantity'] = [
        'id'    => 'pn_cm_item_quantity',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type'  => 'number',
        'label' => __( 'Quantity', 'pn-customers-manager' ),
        'value' => $item['quantity'],
        'step'  => '0.01',
        'min'   => 0,
      ];

      $fields['pn_cm_item_unit_price'] = [
        'id'    => 'pn_cm_item_unit_price',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type'  => 'number',
        'label' => __( 'Unit price', 'pn-customers-manager' ),
        'value' => $item['unit_price'],
        'step'  => '0.01',
        'min'   => 0,
      ];

      $fields['pn_cm_item_is_optional'] = [
        'id'    => 'pn_cm_item_is_optional',
        'class' => 'pn-customers-manager-input',
        'input' => 'input',
        'type'  => 'checkbox',
        'label' => __( 'Optional item', 'pn-customers-manager' ),
        'value' => ! empty( $item['is_optional'] ) ? 'on' : '',
      ];

      $fields['pn_cm_item_is_recurring'] = [
        'id'    => 'pn_cm_item_is_recurring',
        'class' => 'pn-customers-manager-input',
        'input' => 'input',
        'type'  => 'checkbox',
        'label' => __( 'Recurring expense', 'pn-customers-manager' ),
        'value' => ! empty( $item['is_recurring'] ) ? 'on' : '',
      ];

      $fields['pn_cm_item_recurring_period'] = [
        'id'      => 'pn_cm_item_recurring_period',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'label'   => __( 'Recurring period', 'pn-customers-manager' ),
        'value'   => ! empty( $item['recurring_period'] ) ? $item['recurring_period'] : '',
        'options' => [
          ''        => esc_html__( '— Select —', 'pn-customers-manager' ),
          'daily'   => esc_html__( 'Daily', 'pn-customers-manager' ),
          'weekly'  => esc_html__( 'Weekly', 'pn-customers-manager' ),
          'monthly' => esc_html__( 'Monthly', 'pn-customers-manager' ),
          'yearly'  => esc_html__( 'Yearly', 'pn-customers-manager' ),
        ],
      ];
    }

    ob_start();
    ?>
    <div class="pn-cm-budget-edit-item-popup pn-customers-manager-p-30"
         data-item-id="<?php echo esc_attr( $item_id ); ?>"
         data-budget-id="<?php echo esc_attr( $budget_id ); ?>"
         data-item-type="<?php echo esc_attr( $item['item_type'] ); ?>">
      <h4 class="pn-customers-manager-mb-30">
        <?php echo $is_phase ? esc_html__('Edit phase', 'pn-customers-manager') : esc_html__('Edit item', 'pn-customers-manager'); ?>
      </h4>
      <?php foreach ($fields as $field): ?>
        <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0), PN_CUSTOMERS_MANAGER_KSES); ?>
      <?php endforeach; ?>
      <div class="pn-customers-manager-text-align-right pn-customers-manager-mt-15" style="display:flex;justify-content:space-between;align-items:center;">
        <?php if ( $can_add_alt ) : ?>
          <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-cm-budget-popup-add-alt"><?php echo $is_phase ? esc_html__('Create alternative phase', 'pn-customers-manager') : esc_html__('Create alternative', 'pn-customers-manager'); ?></a>
        <?php else : ?>
          <span></span>
        <?php endif; ?>
        <a href="#" class="pn-customers-manager-btn pn-cm-budget-popup-save"><?php esc_html_e('Save', 'pn-customers-manager'); ?></a>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render the "add item" form using the Forms class.
   */
  public static function pn_cm_budget_render_item_add_form($budget_id) {
    $default_rate = get_option('pn_customers_manager_budget_default_hourly_rate', '0');

    $fields = [];

    $fields['pn_cm_item_description'] = [
      'id' => 'pn_cm_item_description',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'editor',
      'required' => true,
      'value' => '',
      'label' => __('Description', 'pn-customers-manager'),
    ];

    $fields['pn_cm_item_type'] = [
      'id' => 'pn_cm_item_type',
      'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
      'input' => 'select',
      'label' => __('Type', 'pn-customers-manager'),
      'value' => 'hours',
      'options' => [
        'hours' => esc_html__('Hours', 'pn-customers-manager'),
        'fixed' => esc_html__('Fixed', 'pn-customers-manager'),
      ],
    ];

    $fields['pn_cm_item_quantity'] = [
      'id' => 'pn_cm_item_quantity',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'label' => __('Quantity', 'pn-customers-manager'),
      'value' => '1',
      'step' => '0.01',
      'min' => 0,
    ];

    $fields['pn_cm_item_unit_price'] = [
      'id' => 'pn_cm_item_unit_price',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'label' => __('Unit price', 'pn-customers-manager'),
      'value' => $default_rate,
      'step' => '0.01',
      'min' => 0,
    ];

    $fields['pn_cm_item_is_optional'] = [
      'id' => 'pn_cm_item_is_optional',
      'class' => 'pn-customers-manager-input',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Optional item', 'pn-customers-manager'),
      'value' => '',
    ];

    $fields['pn_cm_item_is_recurring'] = [
      'id'    => 'pn_cm_item_is_recurring',
      'class' => 'pn-customers-manager-input',
      'input' => 'input',
      'type'  => 'checkbox',
      'label' => __( 'Recurring expense', 'pn-customers-manager' ),
      'value' => '',
    ];

    $fields['pn_cm_item_recurring_period'] = [
      'id'      => 'pn_cm_item_recurring_period',
      'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
      'input'   => 'select',
      'label'   => __( 'Recurring period', 'pn-customers-manager' ),
      'value'   => '',
      'options' => [
        ''        => esc_html__( '— Select —', 'pn-customers-manager' ),
        'daily'   => esc_html__( 'Daily', 'pn-customers-manager' ),
        'weekly'  => esc_html__( 'Weekly', 'pn-customers-manager' ),
        'monthly' => esc_html__( 'Monthly', 'pn-customers-manager' ),
        'yearly'  => esc_html__( 'Yearly', 'pn-customers-manager' ),
      ],
    ];

    ob_start();
    ?>
    <div class="pn-cm-budget-edit-item-popup pn-customers-manager-p-30"
         data-budget-id="<?php echo esc_attr($budget_id); ?>"
         data-form-mode="add">
      <h4 class="pn-customers-manager-mb-30">
        <?php esc_html_e('Add item', 'pn-customers-manager'); ?>
      </h4>
      <?php foreach ($fields as $field): ?>
        <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0), PN_CUSTOMERS_MANAGER_KSES); ?>
      <?php endforeach; ?>
      <div class="pn-customers-manager-text-align-right pn-customers-manager-mt-15">
        <a href="#" class="pn-customers-manager-btn pn-cm-budget-popup-add-save"><?php esc_html_e('Add item', 'pn-customers-manager'); ?></a>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

}
