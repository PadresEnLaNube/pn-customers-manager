<?php
/**
 * Invoice creator.
 *
 * This class defines Invoice options, menus and templates.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Post_Type_Invoice {
  public function pn_cm_invoice_get_fields($invoice_id = 0) {
    $pn_customers_manager_fields = [];
      $pn_customers_manager_fields['pn_cm_invoice_title'] = [
        'id' => 'pn_cm_invoice_title',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'required' => true,
        'value' => !empty($invoice_id) ? esc_html(get_the_title($invoice_id)) : '',
        'label' => __('Invoice title', 'pn-customers-manager'),
        'placeholder' => __('Invoice title', 'pn-customers-manager'),
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
   * Meta fields for Invoice.
   */
  public function pn_cm_invoice_get_fields_meta($invoice_id = 0) {
    $default_due_days = intval(get_option('pn_customers_manager_invoice_default_due_days', 30));
    $default_tax_rate = get_option('pn_customers_manager_invoice_default_tax_rate', get_option('pn_customers_manager_budget_default_tax_rate', ''));
    $default_client_notes = get_option('pn_customers_manager_invoice_default_client_notes', '');
    $default_irpf_rate = get_option('pn_customers_manager_invoice_irpf_enabled', '0') === '1'
        ? get_option('pn_customers_manager_invoice_default_irpf_rate', '15')
        : '0';

    $default_expense_tax_rate = get_option('pn_customers_manager_expense_default_tax_rate', '');
    $default_expense_irpf_rate = get_option('pn_customers_manager_expense_irpf_enabled', '0') === '1'
        ? get_option('pn_customers_manager_expense_default_irpf_rate', '15')
        : '0';

    $pn_customers_manager_fields_meta = [];
      // Quick-link block at top of metabox.
      $pn_customers_manager_fields_meta['pn_cm_invoice_quick_link_block'] = [
        'id' => 'pn_cm_invoice_quick_link_block',
        'input' => 'html',
        'skip_save' => true,
        'html_content' => $this->render_quick_link_block($invoice_id),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_type'] = [
        'id' => 'pn_cm_invoice_type',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => __('Type', 'pn-customers-manager'),
        'value' => 'income',
        'parent' => 'this',
        'options' => [
          'income' => __('Income', 'pn-customers-manager'),
          'expense' => __('Expense', 'pn-customers-manager'),
        ],
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_ai_upload_block'] = [
        'id' => 'pn_cm_invoice_ai_upload_block',
        'input' => 'html',
        'skip_save' => true,
        'html_content' => $this->render_ai_upload_zone( $invoice_id ),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'expense',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_details_start'] = [
        'id' => 'pn_cm_invoice_section_details_start',
        'section' => 'start',
        'label' => esc_html__('Invoice details', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_organization_id'] = [
        'id' => 'pn_cm_invoice_organization_id',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Organization', 'pn-customers-manager'),
        'options' => self::get_organization_options(),
        'description' => '<a href="' . esc_url(admin_url('post-new.php?post_type=pn_cm_organization')) . '" target="_blank">' . esc_html__('Add new organization', 'pn-customers-manager') . '</a>',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_number'] = [
        'id' => 'pn_cm_invoice_number',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Invoice number', 'pn-customers-manager'),
        'placeholder' => esc_html__('Auto-generated', 'pn-customers-manager'),
        'readonly' => !empty($invoice_id),
        'skip_save' => true,
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      // Expense-only: supplier invoice number.
      $pn_customers_manager_fields_meta['pn_cm_invoice_supplier_number'] = [
        'id' => 'pn_cm_invoice_supplier_number',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => __('Supplier invoice number', 'pn-customers-manager'),
        'placeholder' => __('e.g. INV-2024-001', 'pn-customers-manager'),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'expense',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_category'] = [
        'id' => 'pn_cm_invoice_category',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => __('Category', 'pn-customers-manager'),
        'placeholder' => __('e.g. Software, Hosting, Office', 'pn-customers-manager'),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'expense',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_payment_method'] = [
        'id' => 'pn_cm_invoice_payment_method',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => __('Payment method', 'pn-customers-manager'),
        'placeholder' => __('e.g. Bank transfer, Card, PayPal', 'pn-customers-manager'),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'expense',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_date'] = [
        'id' => 'pn_cm_invoice_date',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => esc_html__('Date', 'pn-customers-manager'),
        'value' => gmdate('Y-m-d'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_due_date'] = [
        'id' => 'pn_cm_invoice_due_date',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => esc_html__('Due date', 'pn-customers-manager'),
        'value' => gmdate('Y-m-d', strtotime('+' . $default_due_days . ' days')),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_status'] = [
        'id' => 'pn_cm_invoice_status',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Status', 'pn-customers-manager'),
        'value' => 'draft',
        'options' => [
          'draft' => esc_html__('Draft', 'pn-customers-manager'),
          'sent' => esc_html__('Sent', 'pn-customers-manager'),
          'paid' => esc_html__('Paid', 'pn-customers-manager'),
          'cancelled' => esc_html__('Cancelled', 'pn-customers-manager'),
        ],
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_tax_rate'] = [
        'id' => 'pn_cm_invoice_tax_rate',
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
      $pn_customers_manager_fields_meta['pn_cm_invoice_discount_rate'] = [
        'id' => 'pn_cm_invoice_discount_rate',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'number',
        'label' => esc_html__('Discount rate (%)', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. 10', 'pn-customers-manager'),
        'value' => '0',
        'min' => 0,
        'max' => 100,
        'step' => '0.01',
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_irpf_rate'] = [
        'id' => 'pn_cm_invoice_irpf_rate',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'number',
        'label' => esc_html__('IRPF withholding rate (%)', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. 15', 'pn-customers-manager'),
        'value' => $default_irpf_rate,
        'min' => 0,
        'max' => 100,
        'step' => '0.01',
      ];
      // Expense-only: editable subtotal.
      $pn_customers_manager_fields_meta['pn_cm_invoice_expense_subtotal'] = [
        'id' => 'pn_cm_invoice_expense_subtotal',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'number',
        'label' => __('Subtotal', 'pn-customers-manager'),
        'placeholder' => '0.00',
        'min' => 0,
        'step' => '0.01',
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'expense',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_details_end'] = [
        'id' => 'pn_cm_invoice_section_details_end',
        'section' => 'end',
      ];

      $pn_customers_manager_fields_meta['pn_cm_invoice_section_notes_start'] = [
        'id' => 'pn_cm_invoice_section_notes_start',
        'section' => 'start',
        'label' => esc_html__('Notes', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_notes'] = [
        'id' => 'pn_cm_invoice_notes',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'textarea',
        'label' => esc_html__('Internal notes', 'pn-customers-manager'),
        'placeholder' => esc_html__('Notes visible only to administrators.', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_client_notes'] = [
        'id' => 'pn_cm_invoice_client_notes',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => esc_html__('Client notes', 'pn-customers-manager'),
        'placeholder' => esc_html__('Notes visible to the client on the public invoice.', 'pn-customers-manager'),
        'value' => $default_client_notes,
      ];
      $default_invoice_terms = get_option('pn_customers_manager_invoice_terms', '');
      if ( empty( $default_invoice_terms ) ) {
        $default_invoice_terms = get_option('pn_customers_manager_budget_terms', '');
      }
      $pn_customers_manager_fields_meta['pn_cm_invoice_conditions'] = [
        'id' => 'pn_cm_invoice_conditions',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'editor',
        'label' => esc_html__('Terms and conditions', 'pn-customers-manager'),
        'placeholder' => esc_html__('Terms and conditions for this invoice.', 'pn-customers-manager'),
        'value' => $default_invoice_terms,
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_notes_end'] = [
        'id' => 'pn_cm_invoice_section_notes_end',
        'section' => 'end',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_footer_image_start'] = [
        'id' => 'pn_cm_invoice_section_footer_image_start',
        'section' => 'start',
        'label' => esc_html__('Footer image', 'pn-customers-manager'),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_footer_image'] = [
        'id' => 'pn_cm_invoice_footer_image',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'image',
        'label' => esc_html__('Footer image', 'pn-customers-manager'),
        'description' => esc_html__('Image displayed at the bottom of the public invoice, after the totals section.', 'pn-customers-manager'),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_footer_image_end'] = [
        'id' => 'pn_cm_invoice_section_footer_image_end',
        'section' => 'end',
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_recurring_start'] = [
        'id' => 'pn_cm_invoice_section_recurring_start',
        'section' => 'start',
        'label' => esc_html__('Recurring invoice', 'pn-customers-manager'),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_recurring'] = [
        'id' => 'pn_cm_invoice_recurring',
        'class' => 'pn-customers-manager-checkbox',
        'input' => 'input',
        'type' => 'checkbox',
        'label' => esc_html__('Enable recurring billing', 'pn-customers-manager'),
        'description' => esc_html__('When enabled, a new invoice will be automatically generated with the same items and amounts according to the selected frequency. An email notification will be sent to the administrator.', 'pn-customers-manager'),
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_recurring_period'] = [
        'id' => 'pn_cm_invoice_recurring_period',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Frequency', 'pn-customers-manager'),
        'value' => 'yearly',
        'options' => [
          'monthly'   => esc_html__('Monthly', 'pn-customers-manager'),
          'quarterly' => esc_html__('Quarterly', 'pn-customers-manager'),
          'biannual'  => esc_html__('Biannual', 'pn-customers-manager'),
          'yearly'    => esc_html__('Yearly', 'pn-customers-manager'),
        ],
        'parent' => 'pn_cm_invoice_recurring',
        'parent_option' => 'on',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_recurring_next_date'] = [
        'id' => 'pn_cm_invoice_recurring_next_date',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => esc_html__('Next billing date', 'pn-customers-manager'),
        'description' => esc_html__('Date when the next recurring invoice will be generated. Leave empty to calculate automatically from the invoice date.', 'pn-customers-manager'),
        'parent' => 'pn_cm_invoice_recurring',
        'parent_option' => 'on',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_last_recurring_date'] = [
        'id' => 'pn_cm_invoice_last_recurring_date',
        'input' => 'input',
        'type' => 'hidden',
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_recurring_end'] = [
        'id' => 'pn_cm_invoice_section_recurring_end',
        'section' => 'end',
        'parent' => 'pn_cm_invoice_type',
        'parent_option' => 'income',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_items_block'] = [
        'id' => 'pn_cm_invoice_items_block',
        'label' => '',
        'input' => 'html',
        'skip_save' => true,
        'html_content' => $this->render_admin_items_editor($invoice_id),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_totals_block'] = [
        'id' => 'pn_cm_invoice_totals_block',
        'label' => '',
        'input' => 'html',
        'skip_save' => true,
        'html_content' => $this->render_totals_display($invoice_id),
      ];
      // Deliverable files section.
      if ( ! empty( $invoice_id ) ) {
        $pn_customers_manager_fields_meta['pn_cm_invoice_files_block'] = [
          'id'           => 'pn_cm_invoice_files_block',
          'label'        => '',
          'input'        => 'html',
          'skip_save'    => true,
          'html_content' => $this->render_files_block( $invoice_id ),
        ];
      }
      $pn_customers_manager_fields_meta['pn_cm_invoice_form'] = [
        'id' => 'pn_cm_invoice_form',
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
   * Register Invoice CPT.
   */
  public function pn_cm_invoice_register_post_type() {
    $labels = [
      'name'                => _x('Invoice', 'Post Type general name', 'pn-customers-manager'),
      'singular_name'       => _x('Invoice', 'Post Type singular name', 'pn-customers-manager'),
      'menu_name'           => esc_html(__('Invoices', 'pn-customers-manager')),
      'parent_item_colon'   => esc_html(__('Parent Invoice', 'pn-customers-manager')),
      'all_items'           => esc_html(__('All Invoices', 'pn-customers-manager')),
      'view_item'           => esc_html(__('View Invoice', 'pn-customers-manager')),
      'add_new_item'        => esc_html(__('Add new Invoice', 'pn-customers-manager')),
      'add_new'             => esc_html(__('Add new Invoice', 'pn-customers-manager')),
      'edit_item'           => esc_html(__('Edit Invoice', 'pn-customers-manager')),
      'update_item'         => esc_html(__('Update Invoice', 'pn-customers-manager')),
      'search_items'        => esc_html(__('Search Invoices', 'pn-customers-manager')),
      'not_found'           => esc_html(__('No Invoice found', 'pn-customers-manager')),
      'not_found_in_trash'  => esc_html(__('No Invoice found in Trash', 'pn-customers-manager')),
    ];

    $args = [
      'labels'              => $labels,
      'label'               => esc_html(__('Invoices', 'pn-customers-manager')),
      'description'         => esc_html(__('Invoice description', 'pn-customers-manager')),
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
      'capabilities'        => defined('PN_CUSTOMERS_MANAGER_ROLE_PN_CM_INVOICE_CAPABILITIES') ? PN_CUSTOMERS_MANAGER_ROLE_PN_CM_INVOICE_CAPABILITIES : [],
      'show_in_rest'        => true,
    ];

    register_post_type('pn_cm_invoice', $args);
  }

  /**
   * Add Invoice dashboard metabox.
   */
  public function pn_cm_invoice_add_meta_box() {
    add_meta_box('pn_customers_manager_invoice_meta_box', esc_html(__('Invoice details', 'pn-customers-manager')), [$this, 'pn_cm_invoice_meta_box_function'], 'pn_cm_invoice', 'normal', 'high', ['__block_editor_compatible_meta_box' => true]);
  }

  /**
   * Defines Invoice dashboard contents.
   */
  public function pn_cm_invoice_meta_box_function($post) {
    $fields = self::pn_cm_invoice_get_fields_meta($post->ID);
    $fields = PN_CUSTOMERS_MANAGER_Settings::pn_customers_manager_filter_cpt_fields($fields, 'pn_cm_invoice');
    foreach ($fields as $pn_customers_manager_field) {
      if (!is_null(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $post->ID))) {
        echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $post->ID), PN_CUSTOMERS_MANAGER_KSES);
      }
    }
  }

  /**
   * Save Invoice post meta.
   */
  public function pn_cm_invoice_save_post($post_id, $cpt, $update) {
    if ($cpt->post_type == 'pn_cm_invoice' && array_key_exists('pn_cm_invoice_form', $_POST)) {
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
        $existing_number = get_post_meta($post_id, 'pn_cm_invoice_number', true);
        if (empty($existing_number)) {
          $invoice_type_val = !empty($_POST['pn_cm_invoice_type']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_invoice_type'])) : '';
          if ($invoice_type_val === 'expense') {
            // Expenses use the supplier's own invoice number — never auto-generate.
            $supplier_num = !empty($_POST['pn_cm_invoice_supplier_number']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_invoice_supplier_number'])) : '';
            if (!empty($supplier_num)) {
              update_post_meta($post_id, 'pn_cm_invoice_number', $supplier_num);
            }
          } else {
            $manual_number = !empty($_POST['pn_cm_invoice_number']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_invoice_number'])) : '';
            $invoice_number = !empty($manual_number) ? $manual_number : self::pn_cm_invoice_generate_number();
            update_post_meta($post_id, 'pn_cm_invoice_number', $invoice_number);
          }
        }

        $existing_token = get_post_meta($post_id, 'pn_cm_invoice_token', true);
        if (empty($existing_token)) {
          $token = self::pn_cm_invoice_generate_token();
          update_post_meta($post_id, 'pn_cm_invoice_token', $token);
        }

        foreach (array_merge(self::pn_cm_invoice_get_fields(), self::pn_cm_invoice_get_fields_meta($post_id)) as $pn_customers_manager_field) {
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

        $this->pn_cm_invoice_save_items($post_id);
        self::pn_cm_invoice_recalculate_totals($post_id);

        // Attach pending temp file from AI upload zone.
        if ( ! empty( $_POST['pn_cm_invoice_pending_temp_file'] ) ) {
          $temp_name = sanitize_file_name( wp_unslash( $_POST['pn_cm_invoice_pending_temp_file'] ) );
          $dir       = self::get_invoice_files_dir();
          $temp_path = $dir . '/' . $temp_name;
          if ( file_exists( $temp_path ) && strpos( $temp_name, 'tmp-' ) === 0 ) {
            $original_name = preg_replace( '/^tmp-[a-zA-Z0-9]+-/', '', $temp_name );
            $unique_hash   = wp_generate_password( 12, false );
            $stored_name   = $post_id . '-' . $unique_hash . '-' . $original_name;
            $new_path      = $dir . '/' . $stored_name;
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Renaming temp file to permanent within the same directory.
            if ( rename( $temp_path, $new_path ) ) {
              $file_id = self::get_next_file_id( $post_id );
              $entry   = [
                'id'          => $file_id,
                'filename'    => $original_name,
                'stored_name' => $stored_name,
                'size'        => filesize( $new_path ),
                'uploaded_at' => current_time( 'mysql' ),
              ];
              $files   = self::get_invoice_files( $post_id );
              $files[] = $entry;
              self::save_invoice_files( $post_id, $files );
            }
          }
        }
      }
    }
  }

  /**
   * Save invoice items from POST data to post meta.
   */
  private function pn_cm_invoice_save_items($invoice_id) {
    if (!isset($_POST['pn_cm_invoice_item_description']) || !is_array($_POST['pn_cm_invoice_item_description'])) {
      return;
    }

    $descriptions = array_map('sanitize_text_field', wp_unslash($_POST['pn_cm_invoice_item_description']));
    $types        = isset($_POST['pn_cm_invoice_item_type']) ? array_map('sanitize_text_field', wp_unslash($_POST['pn_cm_invoice_item_type'])) : [];
    $quantities   = isset($_POST['pn_cm_invoice_item_quantity']) ? array_map('floatval', wp_unslash($_POST['pn_cm_invoice_item_quantity'])) : [];
    $unit_prices  = isset($_POST['pn_cm_invoice_item_unit_price']) ? array_map('floatval', wp_unslash($_POST['pn_cm_invoice_item_unit_price'])) : [];
    $item_ids     = isset($_POST['pn_cm_invoice_item_id']) ? array_map('intval', wp_unslash($_POST['pn_cm_invoice_item_id'])) : [];

    $has_any_description = false;
    $new_items = [];

    foreach ($descriptions as $index => $description) {
      if (empty($description)) {
        continue;
      }

      $has_any_description = true;
      $item_id = isset($item_ids[$index]) ? $item_ids[$index] : 0;
      $qty     = isset($quantities[$index]) ? $quantities[$index] : 1;
      $price   = isset($unit_prices[$index]) ? $unit_prices[$index] : 0;
      $type    = isset($types[$index]) ? $types[$index] : 'fixed';

      if (empty($item_id)) {
        $item_id = self::get_next_item_id($invoice_id);
      }

      $new_items[] = [
        'id'          => $item_id,
        'item_type'   => $type,
        'description' => $description,
        'quantity'    => $qty,
        'unit_price'  => $price,
        'total'       => round($qty * $price, 2),
        'sort_order'  => $index,
      ];
    }

    if (!$has_any_description) {
      return;
    }

    self::save_invoice_items($invoice_id, $new_items);
  }

  /**
   * Defines single template for Invoice.
   */
  public function pn_cm_invoice_single_template($single) {
    if (get_post_type() == 'pn_cm_invoice') {
      if (file_exists(PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-pn_cm_invoice.php')) {
        return PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-pn_cm_invoice.php';
      }
    }
    return $single;
  }

  /**
   * Get the public URL slug for invoices.
   */
  public static function pn_cm_invoice_get_public_slug() {
    $slug = get_option('pn_customers_manager_invoice_public_slug', 'invoice');
    return !empty($slug) ? sanitize_title($slug) : 'invoice';
  }

  /**
   * Build the public URL for an invoice given its token.
   */
  public static function pn_cm_invoice_get_public_url($token) {
    return home_url(self::pn_cm_invoice_get_public_slug() . '/' . $token);
  }

  /**
   * Generate a unique random token for the public invoice URL.
   */
  public static function pn_cm_invoice_generate_token() {
    return wp_generate_password(32, false);
  }

  /**
   * Build an invoice number string from the given settings without side effects.
   */
  private static function pn_cm_invoice_build_number($format, $prefix, $padding, $separator, $next, $year, $month) {
    $number_str = str_pad($next, $padding, '0', STR_PAD_LEFT);

    $prefix_part = ($prefix !== '') ? $prefix . $separator : '';

    switch ($format) {
      case 'prefix-year-number':
        return $prefix_part . $year . $separator . $number_str;
      case 'year-number':
        return $year . $separator . $number_str;
      case 'prefix-year/number':
        return $prefix_part . $year . '/' . $number_str;
      case 'year/number':
        return $year . '/' . $number_str;
      case 'prefix-yearmonth-number':
        return $prefix_part . $year . $month . $separator . $number_str;
      case 'prefix-number':
      default:
        return $prefix_part . $number_str;
    }
  }

  /**
   * Preview the next invoice number without incrementing the counter.
   */
  public static function pn_cm_invoice_preview_number() {
    $format        = get_option('pn_customers_manager_invoice_number_format', 'prefix-number');
    $prefix        = get_option('pn_customers_manager_invoice_number_prefix', 'INV');
    $padding       = intval(get_option('pn_customers_manager_invoice_number_padding', 5));
    $separator     = get_option('pn_customers_manager_invoice_number_separator', '-');
    $next          = intval(get_option('pn_customers_manager_invoice_next_number', 1));
    $stored_year   = get_option('pn_customers_manager_invoice_number_year', '');
    $current_year  = gmdate('Y');
    $current_month = gmdate('m');

    $has_year = strpos($format, 'year') !== false;
    if ($has_year && $stored_year !== $current_year) {
      $next = 1;
    }

    return self::pn_cm_invoice_build_number($format, $prefix, $padding, $separator, $next, $current_year, $current_month);
  }

  /**
   * Auto-generate invoice number from settings.
   */
  public static function pn_cm_invoice_generate_number() {
    $format        = get_option('pn_customers_manager_invoice_number_format', 'prefix-number');
    $prefix        = get_option('pn_customers_manager_invoice_number_prefix', 'INV');
    $padding       = intval(get_option('pn_customers_manager_invoice_number_padding', 5));
    $separator     = get_option('pn_customers_manager_invoice_number_separator', '-');
    $next          = intval(get_option('pn_customers_manager_invoice_next_number', 1));
    $stored_year   = get_option('pn_customers_manager_invoice_number_year', '');
    $current_year  = gmdate('Y');
    $current_month = gmdate('m');

    // Year-based reset: if format includes year and year changed, reset counter.
    $has_year = strpos($format, 'year') !== false;
    if ($has_year && $stored_year !== $current_year) {
      $next = 1;
      update_option('pn_customers_manager_invoice_number_year', $current_year);
    }

    $invoice_number = self::pn_cm_invoice_build_number($format, $prefix, $padding, $separator, $next, $current_year, $current_month);

    update_option('pn_customers_manager_invoice_next_number', $next + 1);

    return $invoice_number;
  }

  /**
   * Recalculate totals from items in post meta.
   * All items count (no is_selected filter like Budget).
   */
  public static function pn_cm_invoice_recalculate_totals($invoice_id) {
    $invoice_type = get_post_meta($invoice_id, 'pn_cm_invoice_type', true);

    if ($invoice_type === 'expense') {
      // If expense has items, compute subtotal from them; otherwise use the flat field.
      $expense_items = self::get_invoice_items($invoice_id);
      $items_subtotal = 0;
      $has_items = false;
      if (!empty($expense_items)) {
        foreach ($expense_items as &$ei) {
          if (isset($ei['item_type']) && $ei['item_type'] === 'phase') continue;
          $calc = round(floatval($ei['quantity']) * floatval($ei['unit_price']), 2);
          if (floatval($ei['total']) !== $calc) {
            $ei['total'] = $calc;
          }
          $items_subtotal += $calc;
          $has_items = true;
        }
        unset($ei);
        if ($has_items) {
          update_post_meta($invoice_id, 'pn_cm_invoice_items', $expense_items);
        }
      }

      $subtotal  = $has_items ? $items_subtotal : floatval(get_post_meta($invoice_id, 'pn_cm_invoice_expense_subtotal', true));
      $tax_rate  = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_tax_rate', true));
      $irpf_rate = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_irpf_rate', true));

      $discount_amount = 0;
      $tax_amount      = $subtotal * $tax_rate / 100;
      $irpf_amount     = $subtotal * $irpf_rate / 100;
      $total           = $subtotal + $tax_amount - $irpf_amount;

      update_post_meta($invoice_id, 'pn_cm_invoice_subtotal', round($subtotal, 2));
      update_post_meta($invoice_id, 'pn_cm_invoice_discount_amount', 0);
      update_post_meta($invoice_id, 'pn_cm_invoice_tax_amount', round($tax_amount, 2));
      update_post_meta($invoice_id, 'pn_cm_invoice_irpf_amount', round($irpf_amount, 2));
      update_post_meta($invoice_id, 'pn_cm_invoice_total', round($total, 2));
      // Also sync the expense_subtotal field with computed value.
      if ($has_items) {
        update_post_meta($invoice_id, 'pn_cm_invoice_expense_subtotal', round($subtotal, 2));
      }
      return;
    }

    $items = self::get_invoice_items($invoice_id);

    $changed = false;
    foreach ($items as &$item) {
      if (isset($item['item_type']) && $item['item_type'] === 'phase') {
        continue;
      }
      $calc = round(floatval($item['quantity']) * floatval($item['unit_price']), 2);
      if (floatval($item['total']) !== $calc) {
        $item['total'] = $calc;
        $changed = true;
      }
    }
    unset($item);
    if ($changed) {
      update_post_meta($invoice_id, 'pn_cm_invoice_items', $items);
    }

    $subtotal = 0;
    foreach ($items as $item) {
      if (isset($item['item_type']) && $item['item_type'] === 'phase') {
        continue;
      }
      $subtotal += floatval($item['quantity']) * floatval($item['unit_price']);
    }

    $tax_rate      = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_tax_rate', true));
    $discount_rate = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_discount_rate', true));
    $irpf_rate     = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_irpf_rate', true));

    $discount_amount     = $subtotal * $discount_rate / 100;
    $base_after_discount = $subtotal - $discount_amount;
    $tax_amount          = $base_after_discount * $tax_rate / 100;
    $irpf_amount         = $base_after_discount * $irpf_rate / 100;
    $total               = $base_after_discount + $tax_amount - $irpf_amount;

    update_post_meta($invoice_id, 'pn_cm_invoice_subtotal', round($subtotal, 2));
    update_post_meta($invoice_id, 'pn_cm_invoice_discount_amount', round($discount_amount, 2));
    update_post_meta($invoice_id, 'pn_cm_invoice_tax_amount', round($tax_amount, 2));
    update_post_meta($invoice_id, 'pn_cm_invoice_irpf_amount', round($irpf_amount, 2));
    update_post_meta($invoice_id, 'pn_cm_invoice_total', round($total, 2));
  }

  /**
   * Render admin items editor (no Optional column).
   */
  private function render_admin_items_editor($invoice_id) {
    if (empty($invoice_id)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('Save the invoice first to add items.', 'pn-customers-manager') . '</p>';
    }

    $items = self::get_invoice_items($invoice_id);

    ob_start();
    ?>
      <div class="pn-customers-manager-invoice-items-editor" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
        <table class="pn-customers-manager-invoice-items-table pn-customers-manager-width-100-percent">
          <thead>
            <tr>
              <th class="pn-customers-manager-invoice-col-drag"></th>
              <th class="pn-customers-manager-invoice-col-description"><?php esc_html_e('Description', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-invoice-col-type"><?php esc_html_e('Type', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-invoice-col-quantity"><?php esc_html_e('Qty', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-invoice-col-price"><?php esc_html_e('Unit price', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-invoice-col-total"><?php esc_html_e('Total', 'pn-customers-manager'); ?></th>
              <th class="pn-customers-manager-invoice-col-actions"></th>
            </tr>
          </thead>
          <tbody class="pn-customers-manager-invoice-items-body">
            <?php if (!empty($items)): ?>
              <?php foreach ($items as $item): ?>
                <?php if ($item['item_type'] === 'phase'): ?>
                  <tr class="pn-customers-manager-invoice-item-row pn-customers-manager-invoice-item-phase" data-item-id="<?php echo esc_attr($item['id']); ?>">
                    <td class="pn-customers-manager-invoice-col-drag">
                      <i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>
                      <input type="hidden" name="pn_cm_invoice_item_id[]" value="<?php echo esc_attr($item['id']); ?>" />
                      <input type="hidden" name="pn_cm_invoice_item_type[]" value="phase" />
                      <input type="hidden" name="pn_cm_invoice_item_quantity[]" value="0" />
                      <input type="hidden" name="pn_cm_invoice_item_unit_price[]" value="0" />
                    </td>
                    <td colspan="5" class="pn-customers-manager-invoice-col-description">
                      <input type="text" name="pn_cm_invoice_item_description[]" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-invoice-phase-input" value="<?php echo esc_attr($item['description']); ?>" />
                    </td>
                    <td class="pn-customers-manager-invoice-col-actions">
                      <a href="#" class="pn-cm-invoice-row-edit pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Edit', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">edit</i>
                      </a>
                      <a href="#" class="pn-cm-invoice-row-delete pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i>
                      </a>
                    </td>
                  </tr>
                <?php else: ?>
                  <tr class="pn-customers-manager-invoice-item-row" data-item-id="<?php echo esc_attr($item['id']); ?>">
                    <td class="pn-customers-manager-invoice-col-drag">
                      <i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>
                      <input type="hidden" name="pn_cm_invoice_item_id[]" value="<?php echo esc_attr($item['id']); ?>" />
                    </td>
                    <td class="pn-customers-manager-invoice-col-description">
                      <input type="text" name="pn_cm_invoice_item_description[]" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($item['description']); ?>" />
                    </td>
                    <td class="pn-customers-manager-invoice-col-type">
                      <select name="pn_cm_invoice_item_type[]" class="pn-customers-manager-select">
                        <option value="hours" <?php selected($item['item_type'], 'hours'); ?>><?php esc_html_e('Hours', 'pn-customers-manager'); ?></option>
                        <option value="fixed" <?php selected($item['item_type'], 'fixed'); ?>><?php esc_html_e('Fixed', 'pn-customers-manager'); ?></option>
                      </select>
                    </td>
                    <td class="pn-customers-manager-invoice-col-quantity">
                      <input type="number" name="pn_cm_invoice_item_quantity[]" class="pn-customers-manager-input pn-customers-manager-invoice-item-quantity" value="<?php echo esc_attr($item['quantity']); ?>" step="0.01" min="0" />
                    </td>
                    <td class="pn-customers-manager-invoice-col-price">
                      <input type="number" name="pn_cm_invoice_item_unit_price[]" class="pn-customers-manager-input pn-customers-manager-invoice-item-unit-price" value="<?php echo esc_attr($item['unit_price']); ?>" step="0.01" min="0" />
                    </td>
                    <td class="pn-customers-manager-invoice-col-total">
                      <span class="pn-customers-manager-invoice-item-line-total"><?php echo esc_html(self::format_currency(floatval($item['quantity']) * floatval($item['unit_price']))); ?></span>
                    </td>
                    <td class="pn-customers-manager-invoice-col-actions">
                      <a href="#" class="pn-cm-invoice-row-edit pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Edit', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">edit</i>
                      </a>
                      <a href="#" class="pn-cm-invoice-row-delete pn-customers-manager-text-decoration-none" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
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
          <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-invoice-add-phase">
            <i class="material-icons-outlined">segment</i>
            <?php esc_html_e('Add phase', 'pn-customers-manager'); ?>
          </a>
          <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-invoice-add-item">
            <i class="material-icons-outlined">add</i>
            <?php esc_html_e('Add item', 'pn-customers-manager'); ?>
          </a>
        </div>
      </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render totals display.
   */
  private function render_totals_display($invoice_id) {
    if (empty($invoice_id)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('Save the invoice first to see totals.', 'pn-customers-manager') . '</p>';
    }

    $subtotal = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_subtotal', true));
    $discount_amount = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_discount_amount', true));
    $discount_rate = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_discount_rate', true));
    $tax_amount = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_tax_amount', true));
    $tax_rate = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_tax_rate', true));
    $irpf_amount = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_irpf_amount', true));
    $irpf_rate = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_irpf_rate', true));
    $total = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_total', true));

    ob_start();
    ?>
      <div class="pn-customers-manager-invoice-totals">
        <table class="pn-customers-manager-width-100-percent">
          <tr>
            <td class="pn-customers-manager-text-align-right"><strong><?php esc_html_e('Subtotal', 'pn-customers-manager'); ?></strong></td>
            <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value" id="pn-cm-invoice-admin-subtotal"><?php echo esc_html(self::format_currency($subtotal)); ?></td>
          </tr>
          <?php if ($discount_rate > 0): ?>
            <tr>
              <?php // translators: %s is the discount percentage value. ?>
              <td class="pn-customers-manager-text-align-right"><strong><?php echo esc_html(sprintf(__('Discount (%s%%)', 'pn-customers-manager'), number_format($discount_rate, 2))); ?></strong></td>
              <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value" id="pn-cm-invoice-admin-discount">-<?php echo esc_html(self::format_currency($discount_amount)); ?></td>
            </tr>
          <?php endif; ?>
          <?php if ($tax_rate > 0): ?>
            <tr>
              <?php // translators: %s is the tax percentage value. ?>
              <td class="pn-customers-manager-text-align-right"><strong><?php echo esc_html(sprintf(__('Tax (%s%%)', 'pn-customers-manager'), number_format($tax_rate, 2))); ?></strong></td>
              <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value" id="pn-cm-invoice-admin-tax"><?php echo esc_html(self::format_currency($tax_amount)); ?></td>
            </tr>
          <?php endif; ?>
          <?php if ($irpf_rate > 0): ?>
            <tr>
              <?php // translators: %s is the IRPF (income tax withholding) percentage value. ?>
              <td class="pn-customers-manager-text-align-right"><strong><?php echo esc_html(sprintf(__('IRPF (%s%%)', 'pn-customers-manager'), number_format($irpf_rate, 2))); ?></strong></td>
              <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value" id="pn-cm-invoice-admin-irpf">-<?php echo esc_html(self::format_currency($irpf_amount)); ?></td>
            </tr>
          <?php endif; ?>
          <tr class="pn-customers-manager-invoice-total-row">
            <td class="pn-customers-manager-text-align-right"><strong><?php esc_html_e('Total', 'pn-customers-manager'); ?></strong></td>
            <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value" id="pn-cm-invoice-admin-total"><strong><?php echo esc_html(self::format_currency($total)); ?></strong></td>
          </tr>
        </table>
      </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render public link section.
   */
  private function render_public_link($invoice_id) {
    if (empty($invoice_id)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('Save the invoice first to generate a public link.', 'pn-customers-manager') . '</p>';
    }

    $token = get_post_meta($invoice_id, 'pn_cm_invoice_token', true);

    if (empty($token)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('No token generated yet. Save the invoice to generate one.', 'pn-customers-manager') . '</p>';
    }

    $public_url = self::pn_cm_invoice_get_public_url($token);

    ob_start();
    ?>
      <div class="pn-customers-manager-invoice-public-link">
        <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
          <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-80-percent">
            <input type="text" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-invoice-public-url" value="<?php echo esc_attr($public_url); ?>" readonly />
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

  // ── Post meta helpers ──

  /**
   * Get invoice items from post meta.
   */
  public static function get_invoice_items($invoice_id) {
    $items = get_post_meta($invoice_id, 'pn_cm_invoice_items', true);
    if (empty($items) || !is_array($items)) {
      return [];
    }
    usort($items, function ($a, $b) {
      return intval($a['sort_order']) - intval($b['sort_order']);
    });
    return $items;
  }

  /**
   * Save invoice items to post meta.
   */
  public static function save_invoice_items($invoice_id, $items) {
    foreach ($items as &$item) {
      if (isset($item['item_type']) && $item['item_type'] === 'phase') {
        $item['total'] = 0;
      } else {
        $item['total'] = round(floatval($item['quantity']) * floatval($item['unit_price']), 2);
      }
    }
    unset($item);
    update_post_meta($invoice_id, 'pn_cm_invoice_items', $items);
  }

  /**
   * Get a single invoice item by its ID.
   */
  public static function get_invoice_item($invoice_id, $item_id) {
    $items = self::get_invoice_items($invoice_id);
    foreach ($items as $item) {
      if (intval($item['id']) === intval($item_id)) {
        return $item;
      }
    }
    return null;
  }

  /**
   * Get and increment next item ID counter.
   */
  public static function get_next_item_id($invoice_id) {
    $next = intval(get_post_meta($invoice_id, 'pn_cm_invoice_next_item_id', true));
    if ($next < 1) {
      $next = 1;
    }
    update_post_meta($invoice_id, 'pn_cm_invoice_next_item_id', $next + 1);
    return $next;
  }

  /**
   * Add a new item to an invoice.
   */
  public static function add_invoice_item($invoice_id, $data) {
    $items    = self::get_invoice_items($invoice_id);
    $new_id   = self::get_next_item_id($invoice_id);
    $max_sort = 0;
    foreach ($items as $item) {
      if (intval($item['sort_order']) > $max_sort) {
        $max_sort = intval($item['sort_order']);
      }
    }

    $new_item = [
      'id'          => $new_id,
      'item_type'   => isset($data['item_type']) ? $data['item_type'] : 'fixed',
      'description' => isset($data['description']) ? $data['description'] : '',
      'quantity'    => isset($data['quantity']) ? floatval($data['quantity']) : 1,
      'unit_price'  => isset($data['unit_price']) ? floatval($data['unit_price']) : 0,
      'total'       => 0,
      'sort_order'  => isset($data['sort_order']) ? intval($data['sort_order']) : $max_sort + 1,
    ];
    $new_item['total'] = round($new_item['quantity'] * $new_item['unit_price'], 2);

    $items[] = $new_item;
    self::save_invoice_items($invoice_id, $items);

    return $new_id;
  }

  /**
   * Update an existing invoice item.
   */
  public static function update_invoice_item($invoice_id, $item_id, $data) {
    $items = self::get_invoice_items($invoice_id);
    $found = false;
    foreach ($items as &$item) {
      if (intval($item['id']) === intval($item_id)) {
        foreach ($data as $key => $value) {
          $item[$key] = $value;
        }
        $found = true;
        break;
      }
    }
    unset($item);
    if ($found) {
      self::save_invoice_items($invoice_id, $items);
    }
    return $found;
  }

  /**
   * Delete an invoice item.
   */
  public static function delete_invoice_item($invoice_id, $item_id) {
    $items    = self::get_invoice_items($invoice_id);
    $filtered = array_values(array_filter($items, function ($item) use ($item_id) {
      return intval($item['id']) !== intval($item_id);
    }));
    if (count($filtered) < count($items)) {
      self::save_invoice_items($invoice_id, $filtered);
      return true;
    }
    return false;
  }

  /**
   * Format amount with currency symbol from settings (shared with budget).
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

  // ── Invoice files (deliverables) ──

  /**
   * Get the protected upload directory for invoice files.
   * Creates the directory with .htaccess protection on first call.
   */
  public static function get_invoice_files_dir() {
    $upload_dir = wp_upload_dir();
    $dir = $upload_dir['basedir'] . '/pn-cm-invoice-files';
    if ( ! file_exists( $dir ) ) {
      wp_mkdir_p( $dir );
      file_put_contents( $dir . '/.htaccess', "deny from all\n" );
      file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" );
    }
    return $dir;
  }

  /**
   * Get invoice files from post meta.
   */
  public static function get_invoice_files( $invoice_id ) {
    $files = get_post_meta( $invoice_id, 'pn_cm_invoice_files', true );
    if ( empty( $files ) || ! is_array( $files ) ) {
      return [];
    }
    return $files;
  }

  /**
   * Save invoice files to post meta.
   */
  public static function save_invoice_files( $invoice_id, $files ) {
    update_post_meta( $invoice_id, 'pn_cm_invoice_files', $files );
  }

  /**
   * Get and increment next file ID counter.
   */
  public static function get_next_file_id( $invoice_id ) {
    $next = intval( get_post_meta( $invoice_id, 'pn_cm_invoice_next_file_id', true ) );
    if ( $next < 1 ) {
      $next = 1;
    }
    update_post_meta( $invoice_id, 'pn_cm_invoice_next_file_id', $next + 1 );
    return $next;
  }

  /**
   * Add a file to an invoice from $_FILES upload data.
   *
   * @param int   $invoice_id
   * @param array $file_data  Entry from $_FILES.
   * @return array|WP_Error   File entry on success.
   */
  public static function add_invoice_file( $invoice_id, $file_data ) {
    if ( empty( $file_data['name'] ) || $file_data['error'] !== UPLOAD_ERR_OK ) {
      return new \WP_Error( 'upload_error', __( 'File upload failed.', 'pn-customers-manager' ) );
    }

    $dir = self::get_invoice_files_dir();
    $original_name = sanitize_file_name( $file_data['name'] );
    $unique_hash   = wp_generate_password( 12, false );
    $stored_name   = $invoice_id . '-' . $unique_hash . '-' . $original_name;
    $dest_path     = $dir . '/' . $stored_name;

    // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- move_uploaded_file is the standard PHP function for handling uploads.
    if ( ! move_uploaded_file( $file_data['tmp_name'], $dest_path ) ) {
      return new \WP_Error( 'move_error', __( 'Could not save the file.', 'pn-customers-manager' ) );
    }

    $file_id = self::get_next_file_id( $invoice_id );
    $entry = [
      'id'          => $file_id,
      'filename'    => $original_name,
      'stored_name' => $stored_name,
      'size'        => filesize( $dest_path ),
      'uploaded_at' => current_time( 'mysql' ),
    ];

    $files = self::get_invoice_files( $invoice_id );
    $files[] = $entry;
    self::save_invoice_files( $invoice_id, $files );

    return $entry;
  }

  /**
   * Delete a file from an invoice.
   */
  public static function delete_invoice_file( $invoice_id, $file_id ) {
    $files    = self::get_invoice_files( $invoice_id );
    $filtered = [];
    $removed  = null;

    foreach ( $files as $file ) {
      if ( intval( $file['id'] ) === intval( $file_id ) ) {
        $removed = $file;
      } else {
        $filtered[] = $file;
      }
    }

    if ( $removed ) {
      $dir  = self::get_invoice_files_dir();
      $path = $dir . '/' . $removed['stored_name'];
      if ( file_exists( $path ) ) {
        wp_delete_file( $path );
      }
      self::save_invoice_files( $invoice_id, $filtered );
      return true;
    }

    return false;
  }

  /**
   * Render the files block for the admin metabox.
   */
  private function render_files_block( $invoice_id ) {
    if ( empty( $invoice_id ) ) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__( 'Save the invoice first to attach files.', 'pn-customers-manager' ) . '</p>';
    }

    $files = self::get_invoice_files( $invoice_id );

    ob_start();
    ?>
    <div class="pn-cm-invoice-files-admin" data-invoice-id="<?php echo esc_attr( $invoice_id ); ?>">
      <table class="pn-cm-invoice-files-admin-table pn-customers-manager-width-100-percent">
        <thead>
          <tr>
            <th><?php esc_html_e( 'File', 'pn-customers-manager' ); ?></th>
            <th><?php esc_html_e( 'Size', 'pn-customers-manager' ); ?></th>
            <th><?php esc_html_e( 'Date', 'pn-customers-manager' ); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody class="pn-cm-invoice-files-admin-body">
          <?php if ( ! empty( $files ) ) : ?>
            <?php
              $current_type = get_post_meta( $invoice_id, 'pn_cm_invoice_type', true );
              foreach ( $files as $file ) : ?>
              <tr data-file-id="<?php echo esc_attr( $file['id'] ); ?>">
                <td><i class="material-icons-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;">description</i><?php echo esc_html( $file['filename'] ); ?></td>
                <td><?php echo esc_html( size_format( $file['size'] ) ); ?></td>
                <td><?php echo esc_html( $file['uploaded_at'] ); ?></td>
                <td class="pn-customers-manager-text-align-right">
                  <?php if ( $current_type === 'expense' ) : ?>
                  <a href="#" class="pn-cm-invoice-ai-analyze pn-customers-manager-text-decoration-none" data-file-id="<?php echo esc_attr( $file['id'] ); ?>" title="<?php esc_attr_e( 'Analyze with AI', 'pn-customers-manager' ); ?>" style="margin-right:8px;">
                    <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer" style="color:#6c5ce7;font-size:18px;">auto_awesome</i>
                  </a>
                  <?php endif; ?>
                  <a href="#" class="pn-cm-invoice-file-delete pn-customers-manager-text-decoration-none" data-file-id="<?php echo esc_attr( $file['id'] ); ?>">
                    <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer" style="color:#dc3545;">delete</i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else : ?>
            <tr class="pn-cm-invoice-files-empty-row"><td colspan="4" style="text-align:center;color:#999;padding:15px;"><?php esc_html_e( 'No files attached.', 'pn-customers-manager' ); ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <div class="pn-customers-manager-mt-15 pn-customers-manager-text-align-right">
        <input type="file" class="pn-cm-invoice-file-input" multiple style="display:none;" />
        <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-cm-invoice-file-upload-btn">
          <i class="material-icons-outlined pn-customers-manager-vertical-align-middle" style="font-size:16px;">attach_file</i>
          <?php esc_html_e( 'Upload files', 'pn-customers-manager' ); ?>
        </a>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Handle form save for invoice (new / edit).
   */
  public function pn_cm_invoice_form_save($element_id, $key_value, $cm_pn_form_type, $cm_pn_form_subtype) {
    $post_type = !empty(get_post_type($element_id)) ? get_post_type($element_id) : 'pn_cm_invoice';

    if ($post_type == 'pn_cm_invoice') {
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

              $post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();
              $invoice_id = $post_functions->pn_customers_manager_insert_post(esc_html($pn_cm_invoice_title), '', '', sanitize_title(esc_html($pn_cm_invoice_title)), 'pn_cm_invoice', 'publish', get_current_user_id());

              $invoice_type_val = isset($key_value['pn_cm_invoice_type']) ? $key_value['pn_cm_invoice_type'] : '';
              if ($invoice_type_val === 'expense') {
                // Expenses use the supplier's own invoice number — never auto-generate.
                $supplier_num = isset($key_value['pn_cm_invoice_supplier_number']) ? $key_value['pn_cm_invoice_supplier_number'] : '';
                if (!empty($supplier_num)) {
                  update_post_meta($invoice_id, 'pn_cm_invoice_number', $supplier_num);
                }
              } else {
                $invoice_number = self::pn_cm_invoice_generate_number();
                update_post_meta($invoice_id, 'pn_cm_invoice_number', $invoice_number);
              }

              $token = self::pn_cm_invoice_generate_token();
              update_post_meta($invoice_id, 'pn_cm_invoice_token', $token);

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($invoice_id, $key, $value);
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

              $invoice_id = $element_id;
              wp_update_post(['ID' => $invoice_id, 'post_title' => $pn_cm_invoice_title]);

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($invoice_id, $key, $value);
                }
              }

              self::pn_cm_invoice_recalculate_totals($invoice_id);

              break;
          }
      }
    }
  }

  /**
   * Register scripts needed for invoices.
   */
  public static function pn_cm_invoice_register_scripts() {
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
   * Print scripts needed for invoices.
   */
  public static function pn_cm_invoice_print_scripts() {
    wp_print_scripts(['pn-customers-manager-aux', 'pn-customers-manager-forms', 'pn-customers-manager-selector']);
  }

  /**
   * Get status badge HTML.
   */
  private static function get_status_badge($status) {
    $labels = [
      'draft' => esc_html__('Draft', 'pn-customers-manager'),
      'sent' => esc_html__('Sent', 'pn-customers-manager'),
      'paid' => esc_html__('Paid', 'pn-customers-manager'),
      'cancelled' => esc_html__('Cancelled', 'pn-customers-manager'),
    ];

    $label = isset($labels[$status]) ? $labels[$status] : esc_html($status);

    return '<span class="pn-customers-manager-invoice-status-badge pn-cm-invoice-list-status pn-cm-invoice-list-status-' . esc_attr($status) . '">' . $label . '</span>';
  }

  /**
   * Render the invoice list wrapper (shortcode callback).
   */
  public function pn_cm_invoice_list_wrapper() {
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

    if (!is_admin()) {
      wp_enqueue_style('pn-customers-manager-invoice', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-invoice.css', [], PN_CUSTOMERS_MANAGER_VERSION);
      wp_enqueue_style('pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-budget.css', [], PN_CUSTOMERS_MANAGER_VERSION);
      wp_enqueue_script('pn-cm-chartjs', PN_CUSTOMERS_MANAGER_URL . 'assets/js/vendor/chart.min.js', [], PN_CUSTOMERS_MANAGER_VERSION, true);
      wp_enqueue_script('pn-customers-manager-invoice-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-invoice-admin.js', ['jquery', 'pn-cm-chartjs'], PN_CUSTOMERS_MANAGER_VERSION, true);
      wp_localize_script('pn-customers-manager-invoice-admin', 'pnCmInvoiceAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pn-customers-manager-nonce'),
        'invoiceId' => 0,
        'currencySymbol' => get_option('pn_customers_manager_budget_currency_symbol', '€'),
        'currencyPosition' => get_option('pn_customers_manager_budget_currency_position', 'after'),
        'defaultHourlyRate' => get_option('pn_customers_manager_budget_default_hourly_rate', '0'),
        'i18n' => [
          'error' => esc_html__('An error occurred.', 'pn-customers-manager'),
          'confirmDelete' => esc_html__('Are you sure you want to delete this item?', 'pn-customers-manager'),
          'confirmSend' => esc_html__('Are you sure you want to send this invoice?', 'pn-customers-manager'),
          'invoiceSent' => esc_html__('Invoice sent successfully.', 'pn-customers-manager'),
          'invoiceRemoved' => esc_html__('Invoice removed successfully.', 'pn-customers-manager'),
          'invoiceDuplicated' => esc_html__('Invoice duplicated successfully.', 'pn-customers-manager'),
          'noDescription' => esc_html__('Please enter a description.', 'pn-customers-manager'),
          'newPhase' => esc_html__('New phase', 'pn-customers-manager'),
          'aiAnalyzed' => esc_html__('AI analysis completed. Fields have been filled.', 'pn-customers-manager'),
          'aiUploading' => esc_html__('Analyzing with AI…', 'pn-customers-manager'),
          'aiUploadInvalidType' => esc_html__('Please upload a PDF, JPG, or PNG file.', 'pn-customers-manager'),
          'aiUploadTooLarge' => esc_html__('File is too large. Maximum size is 10 MB.', 'pn-customers-manager'),
          'aiUploadNoKey' => esc_html__('No AI API key configured.', 'pn-customers-manager'),
        ],
      ]);
    }

    ob_start();
    echo wp_kses_post( PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_admin_nav( 'invoices' ) );
    ?>
      <div class="pn-customers-manager-cpt-list pn-customers-manager-pn_cm_invoice-list pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mb-100 pn-customers-manager-pb-100">
        <div class="pn-customers-manager-cpt-search-container pn-customers-manager-mb-20 pn-customers-manager-text-align-right">
          <div class="pn-customers-manager-cpt-search-wrapper">
            <input type="text" class="pn-customers-manager-cpt-search-input pn-customers-manager-input pn-customers-manager-display-none" placeholder="<?php esc_attr_e('Filter...', 'pn-customers-manager'); ?>" />
            <i class="material-icons-outlined pn-customers-manager-cpt-search-toggle pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Search Invoices', 'pn-customers-manager'); ?>">search</i>

            <a href="#" id="pn-cm-invoice-csv-export-btn" class="pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" title="<?php esc_attr_e('Export CSV', 'pn-customers-manager'); ?>">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle">cloud_download</i>
            </a>
            <a href="#" id="pn-cm-invoice-csv-import-btn" class="pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" title="<?php esc_attr_e('Import CSV', 'pn-customers-manager'); ?>">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle">cloud_upload</i>
            </a>

            <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-add" data-pn-customers-manager-ajax-type="pn_cm_invoice_new">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Add new Invoice', 'pn-customers-manager'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="pn-customers-manager-cpt-list-wrapper pn-customers-manager-pn_cm_invoice-list-wrapper">
          <?php echo wp_kses(self::pn_cm_invoice_list(), PN_CUSTOMERS_MANAGER_KSES); ?>
        </div>
        <?php echo wp_kses( PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_global_financial_summary( 'invoice', array( 'sent', 'paid' ) ), PN_CUSTOMERS_MANAGER_KSES ); ?>
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * Render the invoice list.
   */
  public function pn_cm_invoice_list() {
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

    $invoice_atts = [
      'fields' => 'ids',
      'numberposts' => -1,
      'post_type' => 'pn_cm_invoice',
      'post_status' => 'any',
      'orderby' => 'date',
      'order' => 'DESC',
    ];

    if (class_exists('Polylang')) {
      $invoice_atts['lang'] = pll_current_language('slug');
    }

    $invoices = get_posts($invoice_atts);

    ob_start();
    ?>
      <div class="pn-cm-invoice-type-filters">
        <button type="button" class="pn-cm-invoice-type-filter active" data-type="all"><?php esc_html_e('All', 'pn-customers-manager'); ?></button>
        <button type="button" class="pn-cm-invoice-type-filter" data-type="income"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;margin-right:2px;">north_east</i><?php esc_html_e('Income', 'pn-customers-manager'); ?></button>
        <button type="button" class="pn-cm-invoice-type-filter" data-type="expense"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;margin-right:2px;">south_west</i><?php esc_html_e('Expenses', 'pn-customers-manager'); ?></button>
      </div>
      <?php if (!empty($invoices)): ?>
        <table class="pn-cm-invoice-list-table">
          <thead>
            <tr>
              <th class="pn-cm-invoice-list-col-type"><?php esc_html_e('Type', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-invoice-list-col-number"><?php esc_html_e('Number', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-invoice-list-col-title"><?php esc_html_e('Title', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-invoice-list-col-org"><?php esc_html_e('Organization', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-invoice-list-col-date"><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-invoice-list-col-status"><?php esc_html_e('Status', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-invoice-list-col-total"><?php esc_html_e('Total', 'pn-customers-manager'); ?></th>
              <th class="pn-cm-invoice-list-col-actions"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($invoices as $invoice_id):
              $invoice_number = get_post_meta($invoice_id, 'pn_cm_invoice_number', true);
              $invoice_title = get_the_title($invoice_id);
              $invoice_status = get_post_meta($invoice_id, 'pn_cm_invoice_status', true);
              $invoice_date = get_post_meta($invoice_id, 'pn_cm_invoice_date', true);
              $invoice_due_date = get_post_meta($invoice_id, 'pn_cm_invoice_due_date', true);
              $invoice_total = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_total', true));
              $org_id = get_post_meta($invoice_id, 'pn_cm_invoice_organization_id', true);
              $org_name = !empty($org_id) ? get_the_title($org_id) : '';
              $paid_at = get_post_meta($invoice_id, 'pn_cm_invoice_paid_at', true);
              $source_budget_id = get_post_meta($invoice_id, 'pn_cm_invoice_source_budget_id', true);
              $invoice_type = get_post_meta($invoice_id, 'pn_cm_invoice_type', true);
              if (empty($invoice_type)) $invoice_type = 'income';
              $invoice_is_recurring = get_post_meta($invoice_id, 'pn_cm_invoice_recurring', true) === 'on';
            ?>
              <tr class="pn-customers-manager-pn_cm_invoice-list-item" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>" data-invoice-type="<?php echo esc_attr($invoice_type); ?>">
                <td class="pn-cm-invoice-list-col-type" data-label="<?php esc_attr_e('Type', 'pn-customers-manager'); ?>">
                  <?php if ($invoice_type === 'expense'): ?>
                    <span class="pn-cm-invoice-type-badge pn-cm-invoice-type-expense pn-customers-manager-tooltip" title="<?php echo esc_attr($invoice_is_recurring ? __('Recurring expense', 'pn-customers-manager') : __('Expense', 'pn-customers-manager')); ?>"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">south_west</i><?php if ($invoice_is_recurring): ?><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">autorenew</i><?php endif; ?></span>
                  <?php else: ?>
                    <span class="pn-cm-invoice-type-badge pn-cm-invoice-type-income pn-customers-manager-tooltip" title="<?php echo esc_attr($invoice_is_recurring ? __('Recurring income', 'pn-customers-manager') : __('Income', 'pn-customers-manager')); ?>"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">north_east</i><?php if ($invoice_is_recurring): ?><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">autorenew</i><?php endif; ?></span>
                  <?php endif; ?>
                </td>
                <td class="pn-cm-invoice-list-col-number" data-label="<?php esc_attr_e('Number', 'pn-customers-manager'); ?>">
                  <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-view" data-pn-customers-manager-ajax-type="pn_cm_invoice_view" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
                    <strong><?php
                      if ($invoice_type === 'expense') {
                        $supplier_num = get_post_meta($invoice_id, 'pn_cm_invoice_supplier_number', true);
                        echo esc_html(!empty($supplier_num) ? $supplier_num : '—');
                      } else {
                        echo esc_html($invoice_number);
                      }
                    ?></strong>
                  </a>
                </td>
                <td class="pn-cm-invoice-list-col-title" data-label="<?php esc_attr_e('Title', 'pn-customers-manager'); ?>">
                  <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-view" data-pn-customers-manager-ajax-type="pn_cm_invoice_view" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
                    <?php echo esc_html($invoice_title); ?>
                  </a>
                </td>
                <td class="pn-cm-invoice-list-col-org" data-label="<?php esc_attr_e('Organization', 'pn-customers-manager'); ?>">
                  <?php echo esc_html($org_name); ?>
                </td>
                <td class="pn-cm-invoice-list-col-date" data-label="<?php esc_attr_e('Date', 'pn-customers-manager'); ?>">
                  <?php if (!empty($invoice_date)): ?>
                    <?php echo esc_html($invoice_date); ?>
                  <?php endif; ?>
                  <?php if (!empty($invoice_due_date) && in_array($invoice_status, ['draft', 'sent'], true)): ?>
                    <br><small><?php echo esc_html(sprintf(
                      /* translators: %s: date */
                      __('Due %s', 'pn-customers-manager'),
                      $invoice_due_date
                    )); ?></small>
                  <?php endif; ?>
                  <?php if ($invoice_status === 'paid' && !empty($paid_at)): ?>
                    <br><small><?php echo esc_html(sprintf(
                      /* translators: %s: date */
                      __('Paid %s', 'pn-customers-manager'),
                      date_i18n(get_option('date_format'), strtotime($paid_at))
                    )); ?></small>
                  <?php endif; ?>
                </td>
                <td class="pn-cm-invoice-list-col-status" data-label="<?php esc_attr_e('Status', 'pn-customers-manager'); ?>">
                  <?php echo wp_kses(self::get_status_badge($invoice_status), PN_CUSTOMERS_MANAGER_KSES); ?>
                  <?php if (!empty($source_budget_id) && get_post_status($source_budget_id) !== false):
                    $src_bud_number = get_post_meta($source_budget_id, 'pn_cm_budget_number', true);
                  ?>
                    <br><small><i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-14">description</i> <?php echo esc_html($src_bud_number); ?></small>
                  <?php endif; ?>
                </td>
                <td class="pn-cm-invoice-list-col-total" data-label="<?php esc_attr_e('Total', 'pn-customers-manager'); ?>">
                  <strong><?php echo esc_html(self::format_currency($invoice_total)); ?></strong>
                </td>
                <td class="pn-cm-invoice-list-col-actions">
                  <div class="pn-customers-manager-position-relative">
                    <i class="material-icons-outlined pn-customers-manager-menu-more-btn pn-customers-manager-cursor-pointer pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30">more_vert</i>

                    <div class="pn-customers-manager-menu-more pn-customers-manager-z-index-99 pn-customers-manager-display-none-soft">
                      <ul class="pn-customers-manager-list-style-none">
                        <?php
                        $invoice_token = get_post_meta($invoice_id, 'pn_cm_invoice_token', true);
                        if (!empty($invoice_token)):
                          $invoice_public_url = self::pn_cm_invoice_get_public_url($invoice_token);
                        ?>
                        <li>
                          <a href="<?php echo esc_url($invoice_public_url); ?>" target="_blank" class="pn-customers-manager-text-decoration-none">
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
                        <?php if (!empty($source_budget_id) && get_post_status($source_budget_id) !== false): ?>
                        <li>
                          <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-view" data-pn-customers-manager-ajax-type="pn_cm_budget_view" data-pn_cm_budget-id="<?php echo esc_attr($source_budget_id); ?>">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('View Source Budget', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">description</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <?php endif; ?>
                        <li>
                          <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-view" data-pn-customers-manager-ajax-type="pn_cm_invoice_view" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('View Invoice', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">visibility</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <li>
                          <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-edit" data-pn-customers-manager-ajax-type="pn_cm_invoice_edit" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Edit Invoice', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">edit</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <li>
                          <a href="#" class="pn-customers-manager-pn_cm_invoice-duplicate-post">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Duplicate Invoice', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">content_copy</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <?php if ($invoice_type !== 'expense'): ?>
                        <li>
                          <a href="#" class="pn-customers-manager-pn_cm_invoice-send-post pn-customers-manager-text-decoration-none" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Send Invoice', 'pn-customers-manager'); ?></p>
                              </div>
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                                <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">send</i>
                              </div>
                            </div>
                          </a>
                        </li>
                        <?php endif; ?>
                        <li>
                          <a href="#" class="pn-customers-manager-popup-open" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-remove">
                            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                                <p><?php esc_html_e('Remove Invoice', 'pn-customers-manager'); ?></p>
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
        <p class="pn-customers-manager-text-align-center pn-customers-manager-mt-30"><?php esc_html_e('No invoices found.', 'pn-customers-manager'); ?></p>
      <?php endif ?>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * View popup content for an invoice.
   */
  public function pn_cm_invoice_view($invoice_id) {
    ob_start();
    self::pn_cm_invoice_register_scripts();
    self::pn_cm_invoice_print_scripts();

    $invoice_number = get_post_meta($invoice_id, 'pn_cm_invoice_number', true);
    $invoice_status = get_post_meta($invoice_id, 'pn_cm_invoice_status', true);
    ?>
      <div class="pn_cm_invoice-view pn-customers-manager-p-30" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
        <h4 class="pn-customers-manager-text-align-center">
          <?php echo esc_html($invoice_number); ?> &mdash; <?php echo esc_html(get_the_title($invoice_id)); ?>
        </h4>
        <p class="pn-customers-manager-text-align-center">
          <?php echo wp_kses(self::get_status_badge($invoice_status), PN_CUSTOMERS_MANAGER_KSES); ?>
        </p>

        <div class="pn_cm_invoice-view-list">
          <?php foreach (array_merge(self::pn_cm_invoice_get_fields(), self::pn_cm_invoice_get_fields_meta($invoice_id)) as $pn_customers_manager_field): ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_display_wrapper($pn_customers_manager_field, 'post', $invoice_id), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right pn-customers-manager-invoice pn-customers-manager-mt-20" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
            <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-popup-open-ajax" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-edit" data-pn-customers-manager-ajax-type="pn_cm_invoice_edit"><?php esc_html_e('Edit Invoice', 'pn-customers-manager'); ?></a>
          </div>

          <?php
          // Linked source budget section
          $source_budget_id = get_post_meta($invoice_id, 'pn_cm_invoice_source_budget_id', true);
          if (!empty($source_budget_id) && get_post_status($source_budget_id) !== false):
            $bud_number = get_post_meta($source_budget_id, 'pn_cm_budget_number', true);
            $bud_status = get_post_meta($source_budget_id, 'pn_cm_budget_status', true);
          ?>
            <div class="pn-customers-manager-linked-documents pn-customers-manager-mt-20 pn-customers-manager-pt-20" style="border-top: 1px solid #eee;">
              <p class="pn-customers-manager-mb-10"><i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-18">description</i> <strong><?php esc_html_e('Source Budget', 'pn-customers-manager'); ?></strong></p>
              <div class="pn-customers-manager-mb-5">
                <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_budget-view" data-pn-customers-manager-ajax-type="pn_cm_budget_view" data-pn_cm_budget-id="<?php echo esc_attr($source_budget_id); ?>">
                  <?php echo esc_html($bud_number); ?> &mdash; <?php echo esc_html(get_the_title($source_budget_id)); ?>
                  <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_status_badge($bud_status), PN_CUSTOMERS_MANAGER_KSES); ?>
                </a>
              </div>
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
   * New invoice form.
   */
  public function pn_cm_invoice_new() {
    if (!is_user_logged_in()) {
      wp_die(esc_html__('You must be logged in to create a new invoice.', 'pn-customers-manager'), esc_html__('Access Denied', 'pn-customers-manager'), ['response' => 403]);
    }

    ob_start();
    self::pn_cm_invoice_register_scripts();
    self::pn_cm_invoice_print_scripts();
    ?>
      <div class="pn_cm_invoice-new pn-customers-manager-p-30">
        <h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Add new Invoice', 'pn-customers-manager'); ?></h4>

        <form action="" method="post" id="pn-customers-manager-invoice-form-new" class="pn-customers-manager-form">
          <?php foreach (array_merge(self::pn_cm_invoice_get_fields(), self::pn_cm_invoice_get_fields_meta(0)) as $pn_customers_manager_field): ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post'), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right">
            <input class="pn-customers-manager-btn" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_new" data-pn-customers-manager-post-type="pn_cm_invoice" type="submit" value="<?php esc_attr_e('Create Invoice', 'pn-customers-manager'); ?>"/>
          </div>
        </form>
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * Edit invoice form.
   */
  public function pn_cm_invoice_edit($invoice_id) {
    ob_start();
    self::pn_cm_invoice_register_scripts();
    self::pn_cm_invoice_print_scripts();

    $invoice_number = get_post_meta($invoice_id, 'pn_cm_invoice_number', true);
    ?>
      <div class="pn_cm_invoice-edit pn-customers-manager-p-30">
        <p class="pn-customers-manager-text-align-center pn-customers-manager-mb-0"><?php esc_html_e('Editing', 'pn-customers-manager'); ?></p>
        <h4 class="pn-customers-manager-text-align-center pn-customers-manager-mb-30"><?php echo esc_html($invoice_number); ?> &mdash; <?php echo esc_html(get_the_title($invoice_id)); ?></h4>

        <form action="" method="post" id="pn-customers-manager-invoice-form-edit" class="pn-customers-manager-form">
          <?php
            $pn_cm_invoice_edit_exclude = ['pn_cm_invoice_items_block', 'pn_cm_invoice_totals_block', 'pn_cm_invoice_files_block'];
            foreach (array_merge(self::pn_cm_invoice_get_fields(), self::pn_cm_invoice_get_fields_meta($invoice_id)) as $pn_customers_manager_field):
              if (in_array($pn_customers_manager_field['id'] ?? '', $pn_cm_invoice_edit_exclude, true)) continue;
          ?>
            <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_field, 'post', $invoice_id), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right">
            <input class="pn-customers-manager-btn" type="submit" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_edit" data-pn-customers-manager-post-type="pn_cm_invoice" data-pn-customers-manager-post-id="<?php echo esc_attr($invoice_id); ?>" value="<?php esc_attr_e('Save Invoice', 'pn-customers-manager'); ?>"/>
          </div>
        </form>
      </div>
    <?php
    $pn_customers_manager_return_string = ob_get_contents();
    ob_end_clean();
    return $pn_customers_manager_return_string;
  }

  /**
   * Add rewrite rule for public invoice view.
   */
  public function pn_cm_invoice_init_rewrite() {
    $slug = self::pn_cm_invoice_get_public_slug();
    $rule = '^' . $slug . '/([a-zA-Z0-9]+)/?$';
    $query = 'index.php?pn_cm_invoice_token=$matches[1]';
    add_rewrite_rule($rule, $query, 'top');

    $stored_rules = get_option('rewrite_rules', []);
    if (!is_array($stored_rules) || !isset($stored_rules[$rule]) || $stored_rules[$rule] !== $query) {
      flush_rewrite_rules(false);
    }
  }

  /**
   * Register the query var for invoice token.
   */
  public function pn_cm_invoice_query_vars($vars) {
    $vars[] = 'pn_cm_invoice_token';
    return $vars;
  }

  /**
   * Handle template redirect for public invoice view.
   */
  public function pn_cm_invoice_template_redirect() {
    $token = get_query_var('pn_cm_invoice_token');

    if (empty($token)) {
      return;
    }

    $token = sanitize_text_field($token);

    $post_statuses = ['publish'];
    if (current_user_can('manage_options')) {
      $post_statuses = ['publish', 'draft', 'private', 'pending'];
    }

    $invoices = get_posts([
      'post_type' => 'pn_cm_invoice',
      'numberposts' => 1,
      'post_status' => $post_statuses,
      'meta_key' => 'pn_cm_invoice_token',
      'meta_value' => $token,
      'fields' => 'ids',
    ]);

    if (empty($invoices)) {
      global $wp_query;
      $wp_query->set_404();
      status_header(404);
      return;
    }

    $invoice_id = $invoices[0];
    $template = PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-pn_cm_invoice.php';

    if (file_exists($template)) {
      set_query_var('pn_cm_invoice_id', $invoice_id);
      include $template;
      exit;
    }
  }

  /**
   * Render the edit form for a single invoice item.
   */
  public static function pn_cm_invoice_render_item_edit_form($item_id, $invoice_id) {
    $item = self::get_invoice_item($invoice_id, $item_id);

    if (!$item) {
      return '<p>' . esc_html__('Item not found.', 'pn-customers-manager') . '</p>';
    }

    $is_phase = ($item['item_type'] === 'phase');

    $fields = [];

    $fields['pn_cm_item_description'] = [
      'id'       => 'pn_cm_item_description',
      'class'    => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input'    => 'input',
      'type'     => 'text',
      'required' => true,
      'value'    => $item['description'],
      'label'    => __('Description', 'pn-customers-manager'),
    ];

    if (!$is_phase) {
      $fields['pn_cm_item_type'] = [
        'id'      => 'pn_cm_item_type',
        'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input'   => 'select',
        'label'   => __('Type', 'pn-customers-manager'),
        'value'   => $item['item_type'],
        'options' => [
          'hours' => esc_html__('Hours', 'pn-customers-manager'),
          'fixed' => esc_html__('Fixed', 'pn-customers-manager'),
        ],
      ];
      $fields['pn_cm_item_quantity'] = [
        'id'    => 'pn_cm_item_quantity',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type'  => 'number',
        'value' => $item['quantity'],
        'label' => __('Quantity', 'pn-customers-manager'),
        'step'  => '0.01',
        'min'   => '0',
      ];
      $fields['pn_cm_item_unit_price'] = [
        'id'    => 'pn_cm_item_unit_price',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type'  => 'number',
        'value' => $item['unit_price'],
        'label' => __('Unit price', 'pn-customers-manager'),
        'step'  => '0.01',
        'min'   => '0',
      ];
    }

    $fields['pn_customers_manager_ajax_nonce'] = [
      'id' => 'pn_customers_manager_ajax_nonce',
      'input' => 'input',
      'type' => 'nonce',
    ];

    ob_start();
    ?>
    <div class="pn-cm-invoice-edit-item-popup pn-customers-manager-p-30"
         data-item-id="<?php echo esc_attr($item_id); ?>"
         data-invoice-id="<?php echo esc_attr($invoice_id); ?>"
         data-item-type="<?php echo esc_attr($item['item_type']); ?>">
      <h4 class="pn-customers-manager-mb-30">
        <?php echo $is_phase ? esc_html__('Edit phase', 'pn-customers-manager') : esc_html__('Edit item', 'pn-customers-manager'); ?>
      </h4>
      <?php foreach ($fields as $field): ?>
        <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0), PN_CUSTOMERS_MANAGER_KSES); ?>
      <?php endforeach; ?>
      <div class="pn-customers-manager-text-align-right pn-customers-manager-mt-15">
        <a href="#" class="pn-customers-manager-btn pn-cm-invoice-popup-save"><?php esc_html_e('Save', 'pn-customers-manager'); ?></a>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render the add form for a new invoice item.
   */
  public static function pn_cm_invoice_render_item_add_form($invoice_id) {
    $fields = [];

    $fields['pn_cm_item_type'] = [
      'id'      => 'pn_cm_item_type',
      'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
      'input'   => 'select',
      'label'   => __('Type', 'pn-customers-manager'),
      'value'   => 'fixed',
      'options' => [
        'hours' => esc_html__('Hours', 'pn-customers-manager'),
        'fixed' => esc_html__('Fixed', 'pn-customers-manager'),
        'phase' => esc_html__('Phase', 'pn-customers-manager'),
      ],
    ];
    $fields['pn_cm_item_description'] = [
      'id'       => 'pn_cm_item_description',
      'class'    => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input'    => 'input',
      'type'     => 'text',
      'required' => true,
      'label'    => __('Description', 'pn-customers-manager'),
    ];
    $fields['pn_cm_item_quantity'] = [
      'id'    => 'pn_cm_item_quantity',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type'  => 'number',
      'value' => '1',
      'label' => __('Quantity', 'pn-customers-manager'),
      'step'  => '0.01',
      'min'   => '0',
    ];
    $fields['pn_cm_item_unit_price'] = [
      'id'    => 'pn_cm_item_unit_price',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type'  => 'number',
      'value' => '0',
      'label' => __('Unit price', 'pn-customers-manager'),
      'step'  => '0.01',
      'min'   => '0',
    ];
    $fields['pn_customers_manager_ajax_nonce'] = [
      'id' => 'pn_customers_manager_ajax_nonce',
      'input' => 'input',
      'type' => 'nonce',
    ];

    ob_start();
    ?>
    <div class="pn-cm-invoice-edit-item-popup pn-customers-manager-p-30"
         data-invoice-id="<?php echo esc_attr($invoice_id); ?>">
      <h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Add item', 'pn-customers-manager'); ?></h4>
      <?php foreach ($fields as $field): ?>
        <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0), PN_CUSTOMERS_MANAGER_KSES); ?>
      <?php endforeach; ?>
      <div class="pn-customers-manager-text-align-right pn-customers-manager-mt-15">
        <a href="#" class="pn-customers-manager-btn pn-cm-invoice-popup-add-save"><?php esc_html_e('Add item', 'pn-customers-manager'); ?></a>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Process recurring invoices cron job.
   * This function is called daily to check if any recurring invoices need to be renewed.
   */
  public static function process_recurring_invoices() {
    // Get all published invoices with recurring enabled
    $args = [
      'post_type' => 'pn_cm_invoice',
      'post_status' => 'publish',
      'numberposts' => -1,
      'fields' => 'ids',
      'meta_query' => [
        [
          'key' => 'pn_cm_invoice_recurring',
          'value' => 'on',
          'compare' => '='
        ]
      ]
    ];

    $invoice_ids = get_posts($args);

    if (empty($invoice_ids)) {
      return;
    }

    $renewed_invoices = [];
    $today = current_time('Y-m-d');

    foreach ($invoice_ids as $invoice_id) {
      // Check if a specific next date is set
      $next_date_override = get_post_meta($invoice_id, 'pn_cm_invoice_recurring_next_date', true);

      if (!empty($next_date_override)) {
        $next_renewal_date = $next_date_override;
      } else {
        $last_recurring_date = get_post_meta($invoice_id, 'pn_cm_invoice_last_recurring_date', true);

        // If no last recurring date, use the invoice creation date
        if (empty($last_recurring_date)) {
          $invoice_date = get_post_meta($invoice_id, 'pn_cm_invoice_date', true);
          $last_recurring_date = !empty($invoice_date) ? $invoice_date : get_the_date('Y-m-d', $invoice_id);
        }

        // Calculate next renewal date based on period
        $period = get_post_meta($invoice_id, 'pn_cm_invoice_recurring_period', true);
        if (empty($period)) {
          $period = 'yearly';
        }
        $period_map = [
          'monthly'   => '+1 month',
          'quarterly' => '+3 months',
          'biannual'  => '+6 months',
          'yearly'    => '+1 year',
        ];
        $interval = isset($period_map[$period]) ? $period_map[$period] : '+1 year';
        $next_renewal_date = gmdate('Y-m-d', strtotime($last_recurring_date . ' ' . $interval));
      }

      if ($today >= $next_renewal_date) {
        // Create new invoice
        $new_invoice_id = self::duplicate_invoice_for_recurring($invoice_id);

        if ($new_invoice_id) {
          // Update last recurring date and clear next date override in original invoice
          update_post_meta($invoice_id, 'pn_cm_invoice_last_recurring_date', $today);
          delete_post_meta($invoice_id, 'pn_cm_invoice_recurring_next_date');

          $renewed_invoices[] = [
            'original_id' => $invoice_id,
            'new_id' => $new_invoice_id,
            'original_number' => get_post_meta($invoice_id, 'pn_cm_invoice_number', true),
            'new_number' => get_post_meta($new_invoice_id, 'pn_cm_invoice_number', true),
            'organization_id' => get_post_meta($new_invoice_id, 'pn_cm_invoice_organization_id', true),
          ];
        }
      }
    }

    // Send notification email if any invoices were renewed
    if (!empty($renewed_invoices)) {
      self::send_recurring_invoice_notification($renewed_invoices);
    }
  }

  /**
   * Duplicate an invoice for recurring billing.
   *
   * @param int $invoice_id Original invoice ID
   * @return int|false New invoice ID or false on failure
   */
  private static function duplicate_invoice_for_recurring($invoice_id) {
    $original_post = get_post($invoice_id);
    if (!$original_post) {
      return false;
    }

    // Create new invoice post
    $new_post_data = [
      'post_title' => $original_post->post_title,
      'post_type' => 'pn_cm_invoice',
      'post_status' => 'publish',
    ];

    $new_invoice_id = wp_insert_post($new_post_data);
    if (is_wp_error($new_invoice_id)) {
      return false;
    }

    // Copy all meta fields except specific ones
    $exclude_meta = ['pn_cm_invoice_number', 'pn_cm_invoice_token', 'pn_cm_invoice_date',
                     'pn_cm_invoice_due_date', 'pn_cm_invoice_paid_at', 'pn_cm_invoice_last_recurring_date',
                     'pn_cm_invoice_recurring_next_date'];

    $meta_keys = get_post_meta($invoice_id);
    foreach ($meta_keys as $key => $values) {
      if (in_array($key, $exclude_meta)) {
        continue;
      }

      foreach ($values as $value) {
        add_post_meta($new_invoice_id, $key, maybe_unserialize($value));
      }
    }

    // Generate new invoice number and token
    $new_number = self::pn_cm_invoice_generate_number();
    $new_token = self::pn_cm_invoice_generate_token();

    update_post_meta($new_invoice_id, 'pn_cm_invoice_number', $new_number);
    update_post_meta($new_invoice_id, 'pn_cm_invoice_token', $new_token);

    // Set new dates
    $today = current_time('Y-m-d');
    $default_due_days = (int) get_option('pn_customers_manager_invoice_default_due_days', 30);
    $due_date = gmdate('Y-m-d', strtotime($today . ' +' . $default_due_days . ' days'));

    update_post_meta($new_invoice_id, 'pn_cm_invoice_date', $today);
    update_post_meta($new_invoice_id, 'pn_cm_invoice_due_date', $due_date);

    // Set status to draft for review
    update_post_meta($new_invoice_id, 'pn_cm_invoice_status', 'draft');

    // Initialize last_recurring_date for the new invoice
    update_post_meta($new_invoice_id, 'pn_cm_invoice_last_recurring_date', $today);

    return $new_invoice_id;
  }

  /**
   * Send email notification to administrator about renewed invoices.
   *
   * @param array $renewed_invoices Array of renewed invoice data
   */
  private static function send_recurring_invoice_notification($renewed_invoices) {
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');

    // translators: %s is the site name.
    $subject = sprintf(__('[%s] Recurring Invoices Automatically Generated', 'pn-customers-manager'), $site_name);

    // translators: %1$s is a line break, %2$s is the sentence about which invoices were generated.
    $message = sprintf(__('Hello,%1$s%2$s', 'pn-customers-manager'), "\n\n",
                       // translators: %s is the current date.
                       sprintf(__('The following recurring invoices have been automatically generated on %s:', 'pn-customers-manager'),
                               current_time(get_option('date_format')))) . "\n\n";

    foreach ($renewed_invoices as $invoice) {
      $org_name = !empty($invoice['organization_id']) ? get_the_title($invoice['organization_id']) : __('No organization', 'pn-customers-manager');
      $invoice_url = admin_url('post.php?post=' . $invoice['new_id'] . '&action=edit');

      $message .= sprintf("- %s → %s (%s)\n   %s: %s\n   %s\n\n",
        $invoice['original_number'],
        $invoice['new_number'],
        $org_name,
        __('Status', 'pn-customers-manager'),
        __('Draft (pending review)', 'pn-customers-manager'),
        $invoice_url
      );
    }

    // translators: %d is the number of invoices generated.
    $message .= "\n" . sprintf(__('Total invoices generated: %d', 'pn-customers-manager'), count($renewed_invoices)) . "\n\n";
    $message .= sprintf(__('You can review and modify these invoices in the WordPress admin panel before sending them to clients.', 'pn-customers-manager')) . "\n\n";
    $message .= __('This is an automated message from the PN Customers Manager plugin.', 'pn-customers-manager');

    // Try to use MAILPN if available, otherwise use wp_mail
    if (class_exists('MAILPN') && method_exists('MAILPN', 'send')) {
      MAILPN::send($admin_email, $subject, $message);
    } else {
      wp_mail($admin_email, $subject, $message);
    }
  }

  // ── Quick-link block ──

  /**
   * Render quick-link block for admin metabox.
   */
  /**
   * Render the AI upload drop-zone for expense invoices.
   */
  private function render_ai_upload_zone( $invoice_id ) {
    ob_start();
    ?>
    <div class="pn-cm-invoice-ai-upload-zone" data-invoice-id="<?php echo esc_attr( intval( $invoice_id ) ); ?>">
      <div class="pn-cm-invoice-ai-upload-dropzone">
        <i class="material-icons-outlined pn-cm-ai-upload-icon">auto_awesome</i>
        <div class="pn-cm-ai-upload-text"><?php esc_html_e( 'Drop a PDF or image to auto-fill with AI', 'pn-customers-manager' ); ?></div>
        <div class="pn-cm-ai-upload-subtext"><?php esc_html_e( 'PDF, JPG or PNG — max 10 MB', 'pn-customers-manager' ); ?></div>
        <div class="pn-cm-ai-upload-loader">
          <div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div>
          <span class="pn-cm-ai-upload-loader-text"><?php esc_html_e( 'Analyzing with AI…', 'pn-customers-manager' ); ?></span>
        </div>
        <div class="pn-cm-ai-upload-success-bar">
          <i class="material-icons-outlined">check_circle</i>
          <span class="pn-cm-ai-upload-success-filename"></span>
          <a href="#" class="pn-cm-ai-upload-retry"><?php esc_html_e( 'Upload another', 'pn-customers-manager' ); ?></a>
        </div>
        <input type="file" class="pn-cm-ai-upload-file-input" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" style="display:none;" />
      </div>
      <input type="hidden" name="pn_cm_invoice_pending_temp_file" class="pn-cm-ai-upload-temp-file" value="" />
    </div>
    <?php
    return ob_get_clean();
  }

  private function render_quick_link_block( $invoice_id ) {
    if ( empty( $invoice_id ) ) {
      return '';
    }
    $token = get_post_meta( $invoice_id, 'pn_cm_invoice_token', true );
    if ( empty( $token ) ) {
      return '';
    }
    $public_url = self::pn_cm_invoice_get_public_url( $token );

    ob_start();
    ?>
      <div class="pn-cm-invoice-admin-quick-link">
        <a href="<?php echo esc_url( $public_url ); ?>" target="_blank" class="pn-cm-invoice-admin-quick-link-btn">
          <i class="material-icons-outlined">open_in_new</i>
          <?php esc_html_e( 'View public invoice', 'pn-customers-manager' ); ?>
        </a>
        <a href="#" class="pn-cm-invoice-admin-quick-link-copy pn-customers-manager-btn-copy" data-pn-customers-manager-copy-text="<?php echo esc_attr( $public_url ); ?>">
          <i class="material-icons-outlined">content_copy</i>
          <?php esc_html_e( 'Copy URL', 'pn-customers-manager' ); ?>
        </a>
      </div>
    <?php
    return ob_get_clean();
  }

  // ── Admin list columns ──

  /**
   * Custom columns for invoice list table.
   */
  public function pn_cm_invoice_admin_columns( $columns ) {
    $new = [];
    $new['cb'] = $columns['cb'];
    $new['pn_cm_invoice_type_col'] = esc_html__( 'Type', 'pn-customers-manager' );
    $new['pn_cm_invoice_number'] = esc_html__( 'Number', 'pn-customers-manager' );
    $new['title'] = $columns['title'];
    $new['pn_cm_invoice_organization'] = esc_html__( 'Organization', 'pn-customers-manager' );
    $new['pn_cm_invoice_status'] = esc_html__( 'Status', 'pn-customers-manager' );
    $new['pn_cm_invoice_total'] = esc_html__( 'Total', 'pn-customers-manager' );
    $new['pn_cm_invoice_date_col'] = esc_html__( 'Date', 'pn-customers-manager' );
    return $new;
  }

  /**
   * Render custom column content.
   */
  public function pn_cm_invoice_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
      case 'pn_cm_invoice_type_col':
        $type = get_post_meta( $post_id, 'pn_cm_invoice_type', true );
        if ( $type === 'expense' ) {
          echo '<span class="pn-cm-invoice-type-badge pn-cm-invoice-type-expense"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">south_west</i> ' . esc_html__( 'Expense', 'pn-customers-manager' ) . '</span>';
        } else {
          echo '<span class="pn-cm-invoice-type-badge pn-cm-invoice-type-income"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">north_east</i> ' . esc_html__( 'Income', 'pn-customers-manager' ) . '</span>';
        }
        break;

      case 'pn_cm_invoice_number':
        $number = get_post_meta( $post_id, 'pn_cm_invoice_number', true );
        echo esc_html( $number );
        break;

      case 'pn_cm_invoice_organization':
        $org_id = get_post_meta( $post_id, 'pn_cm_invoice_organization_id', true );
        if ( ! empty( $org_id ) ) {
          echo esc_html( get_the_title( $org_id ) );
        } else {
          echo '—';
        }
        break;

      case 'pn_cm_invoice_status':
        $status = get_post_meta( $post_id, 'pn_cm_invoice_status', true );
        echo wp_kses_post( self::get_status_badge( $status ? $status : 'draft' ) );
        break;

      case 'pn_cm_invoice_total':
        $total = floatval( get_post_meta( $post_id, 'pn_cm_invoice_total', true ) );
        echo esc_html( self::format_currency( $total ) );
        break;

      case 'pn_cm_invoice_date_col':
        $date = get_post_meta( $post_id, 'pn_cm_invoice_date', true );
        if ( ! empty( $date ) ) {
          echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) );
        } else {
          echo '—';
        }
        break;
    }
  }

  /**
   * Sortable columns.
   */
  public function pn_cm_invoice_admin_sortable_columns( $columns ) {
    $columns['pn_cm_invoice_number']   = 'pn_cm_invoice_number';
    $columns['pn_cm_invoice_status']   = 'pn_cm_invoice_status';
    $columns['pn_cm_invoice_total']    = 'pn_cm_invoice_total';
    $columns['pn_cm_invoice_date_col'] = 'pn_cm_invoice_date';
    return $columns;
  }

  /**
   * Handle sorting by custom meta keys.
   */
  public function pn_cm_invoice_admin_sort_columns( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
      return;
    }
    if ( $query->get( 'post_type' ) !== 'pn_cm_invoice' ) {
      return;
    }
    $orderby = $query->get( 'orderby' );
    switch ( $orderby ) {
      case 'pn_cm_invoice_number':
        $query->set( 'meta_key', 'pn_cm_invoice_number' );
        $query->set( 'orderby', 'meta_value' );
        break;
      case 'pn_cm_invoice_status':
        $query->set( 'meta_key', 'pn_cm_invoice_status' );
        $query->set( 'orderby', 'meta_value' );
        break;
      case 'pn_cm_invoice_total':
        $query->set( 'meta_key', 'pn_cm_invoice_total' );
        $query->set( 'orderby', 'meta_value_num' );
        break;
      case 'pn_cm_invoice_date':
        $query->set( 'meta_key', 'pn_cm_invoice_date' );
        $query->set( 'orderby', 'meta_value' );
        break;
    }
  }

  /**
   * Enqueue styles on admin invoice list page.
   */
  public function pn_cm_invoice_admin_list_styles( $hook ) {
    if ( $hook !== 'edit.php' ) {
      return;
    }
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'pn_cm_invoice' ) {
      return;
    }
    wp_enqueue_style( 'pn-customers-manager-invoice', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-invoice.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_style( 'pn-customers-manager-material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined', [], null );
  }

  /**
   * Call AI vision API to analyze an invoice/receipt file.
   *
   * @param string $file_path Absolute path to the file on disk.
   * @return array|WP_Error Extracted fields array or WP_Error on failure.
   */
  public static function call_ai_vision( $file_path ) {
    if ( ! class_exists( 'PN_CUSTOMERS_MANAGER_WhatsApp_AI' ) || ! method_exists( 'PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'get_ai_providers' ) ) {
      return new \WP_Error( 'no_ai', __( 'AI module is not available.', 'pn-customers-manager' ) );
    }

    $model    = get_option( 'pn_customers_manager_whatsapp_ai_model', 'gpt-4o-mini' );
    $provider = PN_CUSTOMERS_MANAGER_WhatsApp_AI::resolve_provider_for_model( $model );
    $api_key  = get_option( $provider['key_option'], '' );

    if ( empty( $api_key ) ) {
      return new \WP_Error( 'no_key', __( 'No AI API key configured. Please configure an API key in Settings > AI Providers.', 'pn-customers-manager' ) );
    }

    $mime = mime_content_type( $file_path );

    // Convert PDF to PNG image for providers that don't support PDF in vision API (e.g. DeepSeek).
    if ( $mime === 'application/pdf' ) {
      if ( ! class_exists( 'Imagick' ) ) {
        return new \WP_Error( 'no_imagick', __( 'PDF analysis requires the Imagick PHP extension. Please upload a JPG or PNG image instead.', 'pn-customers-manager' ) );
      }
      try {
        $imagick = new \Imagick();
        $imagick->setResolution( 200, 200 );
        $imagick->readImage( $file_path . '[0]' ); // First page only.
        $imagick->setImageFormat( 'png' );
        $file_contents = $imagick->getImageBlob();
        $imagick->clear();
        $imagick->destroy();
        $mime = 'image/png';
      } catch ( \Exception $e ) {
        // translators: %s is the error message from the PDF conversion library.
        return new \WP_Error( 'pdf_convert', sprintf( __( 'Could not convert PDF to image: %s. Please upload a JPG or PNG image instead.', 'pn-customers-manager' ), $e->getMessage() ) );
      }
    } else {
      $file_contents = file_get_contents( $file_path );
      if ( $file_contents === false ) {
        return new \WP_Error( 'file_read', __( 'Could not read file.', 'pn-customers-manager' ) );
      }
    }

    $base64 = base64_encode( $file_contents );

    $prompt = 'Analyze this invoice/receipt image and extract the following data. Return ONLY a valid JSON object with these keys:
- "organization": vendor/supplier company name (string)
- "organization_address": vendor street address (string)
- "organization_city": vendor city (string)
- "organization_postal_code": vendor postal/ZIP code (string)
- "organization_country": vendor country ISO 2-letter code, e.g. "ES", "IE", "US" (string)
- "organization_fiscal_id": vendor tax ID / VAT number / CIF / NIF (string)
- "supplier_number": invoice number from the supplier (string)
- "date": invoice date in YYYY-MM-DD format (string)
- "due_date": payment due date in YYYY-MM-DD format, or empty if not found (string)
- "description": brief summary of all invoiced items/services/concepts, comma-separated (string)
- "items": array of line items, each with "concept" (string), "quantity" (number), "unit_price" (number). Extract every item/service line from the invoice.
- "subtotal": base amount before tax, as a number (number)
- "tax_rate": tax/VAT percentage, as a number (number). If no VAT/tax is applied, use 0.
- "irpf_rate": IRPF withholding percentage if present, as a number (number), 0 if not found
- "category": expense category like "Software", "Hosting", "Office", "Services", "Materials", "Travel", etc. (string)
- "payment_method": payment method if visible, e.g. "Bank transfer", "Card", "PayPal", or empty if not found (string)

If a field cannot be determined, use an empty string for strings or 0 for numbers. For items, use an empty array [] if no individual items can be identified.';

    $data_url = 'data:' . $mime . ';base64,' . $base64;

    $messages = [
      [
        'role'    => 'system',
        'content' => 'You are an invoice data extraction assistant. Extract data from the uploaded invoice/receipt and return ONLY a JSON object. No markdown, no code fences, just raw JSON.',
      ],
      [
        'role'    => 'user',
        'content' => [
          [ 'type' => 'text', 'text' => $prompt ],
          [ 'type' => 'image_url', 'image_url' => [ 'url' => $data_url ] ],
        ],
      ],
    ];

    $body = [
      'model'       => $model,
      'messages'     => $messages,
      'temperature' => 0.1,
    ];

    $response = wp_remote_post( $provider['url'], [
      'timeout' => 60,
      'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
      ],
      'body' => wp_json_encode( $body ),
    ] );

    if ( is_wp_error( $response ) ) {
      return $response;
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body_raw    = wp_remote_retrieve_body( $response );

    if ( $status_code !== 200 ) {
      // translators: %1$d is the HTTP status code, %2$s is the response body from the AI API.
      return new \WP_Error( 'api_error', sprintf( __( 'AI API returned status %1$d: %2$s', 'pn-customers-manager' ), $status_code, substr( $body_raw, 0, 500 ) ) );
    }

    $data = json_decode( $body_raw, true );
    if ( empty( $data['choices'][0]['message']['content'] ) ) {
      return new \WP_Error( 'no_content', __( 'AI returned an empty response.', 'pn-customers-manager' ) );
    }

    $content = $data['choices'][0]['message']['content'];
    // Strip markdown code fences if present.
    $content = preg_replace( '/^```(?:json)?\s*/i', '', $content );
    $content = preg_replace( '/\s*```$/', '', $content );
    $content = trim( $content );

    $parsed = json_decode( $content, true );
    if ( ! is_array( $parsed ) ) {
      return new \WP_Error( 'parse_error', __( 'Could not parse AI response as JSON.', 'pn-customers-manager' ) );
    }

    return $parsed;
  }
}
