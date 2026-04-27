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

    $pn_customers_manager_fields_meta = [];
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
        'input' => 'textarea',
        'label' => esc_html__('Client notes', 'pn-customers-manager'),
        'placeholder' => esc_html__('Notes visible to the client on the public invoice.', 'pn-customers-manager'),
        'value' => $default_client_notes,
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_notes_end'] = [
        'id' => 'pn_cm_invoice_section_notes_end',
        'section' => 'end',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_items_start'] = [
        'id' => 'pn_cm_invoice_section_items_start',
        'section' => 'start',
        'label' => esc_html__('Line items', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_items_block'] = [
        'id' => 'pn_cm_invoice_items_block',
        'input' => 'html',
        'skip_save' => true,
        'label' => esc_html__('Items', 'pn-customers-manager'),
        'html_content' => $this->render_admin_items_editor($invoice_id),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_items_end'] = [
        'id' => 'pn_cm_invoice_section_items_end',
        'section' => 'end',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_totals_start'] = [
        'id' => 'pn_cm_invoice_section_totals_start',
        'section' => 'start',
        'label' => esc_html__('Totals', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_totals_block'] = [
        'id' => 'pn_cm_invoice_totals_block',
        'input' => 'html',
        'skip_save' => true,
        'label' => esc_html__('Summary', 'pn-customers-manager'),
        'html_content' => $this->render_totals_display($invoice_id),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_totals_end'] = [
        'id' => 'pn_cm_invoice_section_totals_end',
        'section' => 'end',
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_public_link_start'] = [
        'id' => 'pn_cm_invoice_section_public_link_start',
        'section' => 'start',
        'label' => esc_html__('Public link', 'pn-customers-manager'),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_public_link_block'] = [
        'id' => 'pn_cm_invoice_public_link_block',
        'input' => 'html',
        'skip_save' => true,
        'label' => esc_html__('Public URL', 'pn-customers-manager'),
        'html_content' => $this->render_public_link($invoice_id),
      ];
      $pn_customers_manager_fields_meta['pn_cm_invoice_section_public_link_end'] = [
        'id' => 'pn_cm_invoice_section_public_link_end',
        'section' => 'end',
      ];
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
    foreach (self::pn_cm_invoice_get_fields_meta($post->ID) as $pn_customers_manager_field) {
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
          $invoice_number = self::pn_cm_invoice_generate_number();
          update_post_meta($post_id, 'pn_cm_invoice_number', $invoice_number);
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
   * Auto-generate invoice number from settings.
   */
  public static function pn_cm_invoice_generate_number() {
    $prefix = get_option('pn_customers_manager_invoice_number_prefix', 'INV');
    $next_number = intval(get_option('pn_customers_manager_invoice_next_number', 1));

    $invoice_number = $prefix . '-' . str_pad($next_number, 5, '0', STR_PAD_LEFT);

    update_option('pn_customers_manager_invoice_next_number', $next_number + 1);

    return $invoice_number;
  }

  /**
   * Recalculate totals from items in post meta.
   * All items count (no is_selected filter like Budget).
   */
  public static function pn_cm_invoice_recalculate_totals($invoice_id) {
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

    $discount_amount = $subtotal * $discount_rate / 100;
    $tax_amount      = ($subtotal - $discount_amount) * $tax_rate / 100;
    $total           = $subtotal - $discount_amount + $tax_amount;

    update_post_meta($invoice_id, 'pn_cm_invoice_subtotal', round($subtotal, 2));
    update_post_meta($invoice_id, 'pn_cm_invoice_discount_amount', round($discount_amount, 2));
    update_post_meta($invoice_id, 'pn_cm_invoice_tax_amount', round($tax_amount, 2));
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
                      <a href="#" class="pn-customers-manager-invoice-item-delete pn-customers-manager-text-decoration-none">
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
                      <a href="#" class="pn-customers-manager-invoice-item-delete pn-customers-manager-text-decoration-none">
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
            <?php esc_html_e('Add phase', 'pn-customers-manager'); ?>
          </a>
          <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-invoice-add-item">
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
    $total = floatval(get_post_meta($invoice_id, 'pn_cm_invoice_total', true));

    ob_start();
    ?>
      <div class="pn-customers-manager-invoice-totals">
        <table class="pn-customers-manager-width-100-percent">
          <tr>
            <td class="pn-customers-manager-text-align-right"><strong><?php esc_html_e('Subtotal', 'pn-customers-manager'); ?></strong></td>
            <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value"><?php echo esc_html(self::format_currency($subtotal)); ?></td>
          </tr>
          <?php if ($discount_rate > 0): ?>
            <tr>
              <td class="pn-customers-manager-text-align-right"><strong><?php echo esc_html(sprintf(__('Discount (%s%%)', 'pn-customers-manager'), number_format($discount_rate, 2))); ?></strong></td>
              <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value">-<?php echo esc_html(self::format_currency($discount_amount)); ?></td>
            </tr>
          <?php endif; ?>
          <?php if ($tax_rate > 0): ?>
            <tr>
              <td class="pn-customers-manager-text-align-right"><strong><?php echo esc_html(sprintf(__('Tax (%s%%)', 'pn-customers-manager'), number_format($tax_rate, 2))); ?></strong></td>
              <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value"><?php echo esc_html(self::format_currency($tax_amount)); ?></td>
            </tr>
          <?php endif; ?>
          <tr class="pn-customers-manager-invoice-total-row">
            <td class="pn-customers-manager-text-align-right"><strong><?php esc_html_e('Total', 'pn-customers-manager'); ?></strong></td>
            <td class="pn-customers-manager-text-align-right pn-customers-manager-invoice-totals-value"><strong><?php echo esc_html(self::format_currency($total)); ?></strong></td>
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

              $invoice_number = self::pn_cm_invoice_generate_number();
              update_post_meta($invoice_id, 'pn_cm_invoice_number', $invoice_number);

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
  public function pn_cm_invoice_register_scripts() {
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
  public function pn_cm_invoice_print_scripts() {
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
      wp_enqueue_script('pn-customers-manager-invoice-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-invoice-admin.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
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
        ],
      ]);
    }

    ob_start();
    ?>
      <div class="pn-customers-manager-cpt-list pn-customers-manager-pn_cm_invoice-list pn-customers-manager-mb-50 pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mb-100">
        <div class="pn-customers-manager-cpt-search-container pn-customers-manager-mb-20 pn-customers-manager-text-align-right">
          <div class="pn-customers-manager-cpt-search-wrapper">
            <input type="text" class="pn-customers-manager-cpt-search-input pn-customers-manager-input pn-customers-manager-display-none" placeholder="<?php esc_attr_e('Filter...', 'pn-customers-manager'); ?>" />
            <i class="material-icons-outlined pn-customers-manager-cpt-search-toggle pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Search Invoices', 'pn-customers-manager'); ?>">search</i>

            <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-add" data-pn-customers-manager-ajax-type="pn_cm_invoice_new">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Add new Invoice', 'pn-customers-manager'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="pn-customers-manager-cpt-list-wrapper pn-customers-manager-pn_cm_invoice-list-wrapper">
          <?php echo wp_kses(self::pn_cm_invoice_list(), PN_CUSTOMERS_MANAGER_KSES); ?>
        </div>
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
      <ul class="pn-customers-manager-invoices pn-customers-manager-list-style-none pn-customers-manager-p-0 pn-customers-manager-margin-auto">
        <?php if (!empty($invoices)): ?>
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
          ?>
            <li class="pn-customers-manager-invoice pn-customers-manager-pn_cm_invoice-list-item pn-customers-manager-mb-10" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-80-percent">
                  <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-view" data-pn-customers-manager-ajax-type="pn_cm_invoice_view" data-pn_cm_invoice-id="<?php echo esc_attr($invoice_id); ?>">
                    <span>
                      <strong><?php echo esc_html($invoice_number); ?></strong>
                      <?php if (!empty($invoice_title)): ?>
                        &mdash; <?php echo esc_html($invoice_title); ?>
                      <?php endif; ?>
                    </span>
                    <br>
                    <small>
                      <?php if (!empty($org_name)): ?>
                        <?php echo esc_html($org_name); ?> &middot;
                      <?php endif; ?>
                      <?php if (!empty($invoice_date)): ?>
                        <?php echo esc_html($invoice_date); ?>
                      <?php endif; ?>
                      <?php if (!empty($invoice_due_date) && in_array($invoice_status, ['draft', 'sent'], true)): ?>
                        &rarr; <?php echo esc_html($invoice_due_date); ?>
                      <?php endif; ?>
                    </small>
                    <br>
                    <small>
                      <?php echo wp_kses(self::get_status_badge($invoice_status), PN_CUSTOMERS_MANAGER_KSES); ?>
                      &middot; <strong><?php echo esc_html(self::format_currency($invoice_total)); ?></strong>
                      <?php if ($invoice_status === 'paid' && !empty($paid_at)): ?>
                        &middot; <?php echo esc_html(sprintf(
                          __('Paid %s', 'pn-customers-manager'),
                          date_i18n(get_option('date_format'), strtotime($paid_at))
                        )); ?>
                      <?php endif; ?>
                      <?php if (!empty($source_budget_id) && get_post_status($source_budget_id) !== false):
                        $src_bud_number = get_post_meta($source_budget_id, 'pn_cm_budget_number', true);
                      ?>
                        &middot; <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-14">description</i> <?php echo esc_html($src_bud_number); ?>
                      <?php endif; ?>
                    </small>
                  </a>
                </div>

                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right pn-customers-manager-position-relative">
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
              </div>
            </li>
          <?php endforeach ?>
        <?php endif ?>

        <li class="pn-customers-manager-add-new-cpt pn-customers-manager-mt-50 pn-customers-manager-invoice" data-pn_cm_invoice-id="0">
          <?php if (is_user_logged_in()): ?>
            <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_invoice-add" data-pn-customers-manager-ajax-type="pn_cm_invoice_new">
              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center">
                  <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-width-25">add</i>
                </div>
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-80-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent">
                  <?php esc_html_e('Add new Invoice', 'pn-customers-manager'); ?>
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
          <?php foreach (array_merge(self::pn_cm_invoice_get_fields(), self::pn_cm_invoice_get_fields_meta($invoice_id)) as $pn_customers_manager_field): ?>
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
    self::pn_cm_invoice_register_scripts();
    self::pn_cm_invoice_print_scripts();
    ?>
    <div class="pn-customers-manager-p-30">
      <h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Edit item', 'pn-customers-manager'); ?></h4>
      <form action="" method="post" class="pn-customers-manager-form pn-customers-manager-invoice-edit-item-form">
        <input type="hidden" name="pn_cm_item_id" value="<?php echo esc_attr($item_id); ?>" />
        <input type="hidden" name="pn_cm_invoice_id" value="<?php echo esc_attr($invoice_id); ?>" />
        <?php foreach ($fields as $field): ?>
          <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0), PN_CUSTOMERS_MANAGER_KSES); ?>
        <?php endforeach; ?>
        <div class="pn-customers-manager-text-align-right">
          <input class="pn-customers-manager-btn" type="submit" data-pn-customers-manager-type="pn_cm_invoice_update_item" value="<?php esc_attr_e('Save item', 'pn-customers-manager'); ?>"/>
        </div>
      </form>
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
    self::pn_cm_invoice_register_scripts();
    self::pn_cm_invoice_print_scripts();
    ?>
    <div class="pn-customers-manager-p-30">
      <h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Add item', 'pn-customers-manager'); ?></h4>
      <form action="" method="post" class="pn-customers-manager-form pn-customers-manager-invoice-add-item-form">
        <input type="hidden" name="pn_cm_invoice_id" value="<?php echo esc_attr($invoice_id); ?>" />
        <?php foreach ($fields as $field): ?>
          <?php echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0), PN_CUSTOMERS_MANAGER_KSES); ?>
        <?php endforeach; ?>
        <div class="pn-customers-manager-text-align-right">
          <input class="pn-customers-manager-btn" type="submit" data-pn-customers-manager-type="pn_cm_invoice_add_item" value="<?php esc_attr_e('Add item', 'pn-customers-manager'); ?>"/>
        </div>
      </form>
    </div>
    <?php
    return ob_get_clean();
  }
}
