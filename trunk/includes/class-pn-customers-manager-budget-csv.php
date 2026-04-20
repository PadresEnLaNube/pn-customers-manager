<?php
/**
 * CSV Import/Export for Budgets.
 *
 * Adds Export and Import CSV functionality to the Budget list page.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Budget_Csv {

  const NONCE_ACTION = 'pn_customers_manager_budget_csv';

  /**
   * Meta fields available for Budget CSV.
   * key => [ label, type ]
   */
  private static function pn_customers_manager_get_budget_csv_meta_fields() {
    return [
      'pn_cm_budget_number'        => [__('Budget Number', 'pn-customers-manager'), 'text'],
      'pn_cm_budget_organization_id' => [__('Organization', 'pn-customers-manager'), 'text'],
      'pn_cm_budget_date'          => [__('Date', 'pn-customers-manager'), 'date'],
      'pn_cm_budget_valid_until'   => [__('Valid Until', 'pn-customers-manager'), 'date'],
      'pn_cm_budget_status'        => [__('Status', 'pn-customers-manager'), 'select'],
      'pn_cm_budget_tax_rate'      => [__('Tax Rate (%)', 'pn-customers-manager'), 'number'],
      'pn_cm_budget_discount_rate' => [__('Discount Rate (%)', 'pn-customers-manager'), 'number'],
      'pn_cm_budget_notes'         => [__('Internal Notes', 'pn-customers-manager'), 'textarea'],
      'pn_cm_budget_client_notes'  => [__('Client Notes', 'pn-customers-manager'), 'textarea'],
      '_items_json'                => [__('Items (JSON)', 'pn-customers-manager'), 'json'],
    ];
  }

  /**
   * Valid options for select-type meta fields.
   */
  private static function pn_customers_manager_get_budget_csv_select_options() {
    return [
      'pn_cm_budget_status' => ['draft', 'sent', 'accepted', 'rejected'],
    ];
  }

  /**
   * Example row for the CSV template.
   */
  private static function pn_customers_manager_get_budget_csv_example_row() {
    $select_options = self::pn_customers_manager_get_budget_csv_select_options();
    $meta           = self::pn_customers_manager_get_budget_csv_meta_fields();

    $raw = [
      'post_title'                   => 'Website Redesign',
      'pn_cm_budget_number'          => '',
      'pn_cm_budget_organization_id' => 'Acme Corp',
      'pn_cm_budget_date'            => gmdate('Y-m-d'),
      'pn_cm_budget_valid_until'     => gmdate('Y-m-d', strtotime('+30 days')),
      'pn_cm_budget_tax_rate'        => '21',
      'pn_cm_budget_discount_rate'   => '0',
      'pn_cm_budget_notes'           => 'Internal reference: project #42',
      'pn_cm_budget_client_notes'    => 'Payment due within 30 days.',
      '_items_json'                  => '[{"item_type":"hours","description":"Design & UX","quantity":40,"unit_price":75,"is_optional":0,"sort_order":0},{"item_type":"fixed","description":"Hosting setup","quantity":1,"unit_price":200,"is_optional":1,"sort_order":1}]',
    ];

    foreach ($select_options as $key => $options) {
      $raw[$key] = implode(' | ', $options);
    }

    $label_map = [
      'post_title' => __('Title', 'pn-customers-manager'),
    ];
    foreach ($meta as $key => $info) {
      $label_map[$key] = $info[0];
    }

    $row = [];
    foreach ($label_map as $key => $label) {
      $row[$label] = isset($raw[$key]) ? $raw[$key] : '';
    }

    return $row;
  }

  /**
   * Inverse map: label → key.
   */
  private static function pn_customers_manager_get_budget_csv_label_to_key_map() {
    $map = [
      'Title' => 'post_title',
    ];

    $map[__('Title', 'pn-customers-manager')] = 'post_title';

    $meta = self::pn_customers_manager_get_budget_csv_meta_fields();
    foreach ($meta as $key => $info) {
      $map[$info[0]] = $key;
    }

    // English fallback labels
    $english_labels = [
      'Budget Number'    => 'pn_cm_budget_number',
      'Organization'     => 'pn_cm_budget_organization_id',
      'Date'             => 'pn_cm_budget_date',
      'Valid Until'       => 'pn_cm_budget_valid_until',
      'Status'           => 'pn_cm_budget_status',
      'Tax Rate (%)'     => 'pn_cm_budget_tax_rate',
      'Discount Rate (%)' => 'pn_cm_budget_discount_rate',
      'Internal Notes'   => 'pn_cm_budget_notes',
      'Client Notes'     => 'pn_cm_budget_client_notes',
      'Items (JSON)'     => '_items_json',
    ];
    foreach ($english_labels as $en_label => $key) {
      if (!isset($map[$en_label])) {
        $map[$en_label] = $key;
      }
    }

    return $map;
  }

  /**
   * Key → translated label map.
   */
  private static function pn_customers_manager_get_budget_csv_key_to_label_map() {
    $map = [
      'post_title' => __('Title', 'pn-customers-manager'),
    ];

    $meta = self::pn_customers_manager_get_budget_csv_meta_fields();
    foreach ($meta as $key => $info) {
      $map[$key] = $info[0];
    }

    return $map;
  }

  /* ───────────────────────────────────────────────
   *  UI RENDERING (called from budget list wrapper)
   * ─────────────────────────────────────────────── */

  public static function pn_customers_manager_budget_csv_render_ui() {
    if (!current_user_can('manage_options')) {
      return;
    }

    $nonce = wp_create_nonce(self::NONCE_ACTION);
    $ajax_url = admin_url('admin-ajax.php');
    $template_url = add_query_arg([
      'action'   => 'pn_customers_manager_budget_csv_download_template',
      '_wpnonce' => $nonce,
    ], $ajax_url);
    $export_url = add_query_arg([
      'action'   => 'pn_customers_manager_budget_csv_export',
      '_wpnonce' => $nonce,
    ], $ajax_url);

    self::pn_customers_manager_budget_csv_render_modal($template_url);
    self::pn_customers_manager_budget_csv_render_js($ajax_url, $nonce, $export_url);
  }

  /* ───────────────────────────────────────────────
   *  MODAL HTML
   * ─────────────────────────────────────────────── */

  private static function pn_customers_manager_budget_csv_render_modal($template_url) {
    ?>
    <div id="pn-customers-manager-popup-pn_cm_budget-csv-import" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-disable-esc="true" data-pn-customers-manager-popup-disable-overlay-close="true">
      <div class="pn-customers-manager-popup-content">
        <div class="pn-cm-csv-body pn-customers-manager-p-30">
          <h3 class="pn-customers-manager-text-align-center pn-customers-manager-mt-0"><?php esc_html_e('Import Budgets from CSV', 'pn-customers-manager'); ?></h3>

          <!-- Step 1: Upload -->
          <div id="pn-cm-budget-csv-step-1" class="pn-cm-csv-step pn-cm-csv-active">
            <p><?php esc_html_e('Upload a CSV file to import budgets. The file must include at least a "Title" column.', 'pn-customers-manager'); ?></p>
            <p>
              <a href="<?php echo esc_url($template_url); ?>" class="pn-cm-csv-template-link" target="_blank">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Download CSV template', 'pn-customers-manager'); ?>
              </a>
            </p>
            <input type="file" id="pn-cm-budget-csv-file" accept=".csv" class="pn-customers-manager-display-none" />
            <div class="pn-cm-csv-file-zone" id="pn-cm-budget-csv-file-zone">
              <span class="dashicons dashicons-upload"></span>
              <p><?php esc_html_e('Drag & drop a CSV file here', 'pn-customers-manager'); ?></p>
              <label for="pn-cm-budget-csv-file" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-cm-csv-select-file-label"><?php esc_html_e('Select file', 'pn-customers-manager'); ?></label>
              <div class="pn-cm-csv-file-name" id="pn-cm-budget-csv-file-name"></div>
            </div>
          </div>

          <!-- Step 2: Preview -->
          <div id="pn-cm-budget-csv-step-2" class="pn-cm-csv-step">
            <div class="pn-cm-csv-preview-wrap" id="pn-cm-budget-csv-preview-wrap"></div>
            <div class="pn-cm-csv-row-count" id="pn-cm-budget-csv-row-count"></div>
          </div>

          <!-- Step 3: Results -->
          <div id="pn-cm-budget-csv-step-3" class="pn-cm-csv-step">
            <div id="pn-cm-budget-csv-results"></div>
          </div>

          <!-- Loader -->
          <div class="pn-cm-csv-loader" id="pn-cm-budget-csv-loader">
            <div class="pn-cm-csv-spinner"></div>
            <p id="pn-cm-budget-csv-loader-text"><?php esc_html_e('Processing…', 'pn-customers-manager'); ?></p>
          </div>

          <div class="pn-cm-csv-footer" id="pn-cm-budget-csv-footer"></div>
        </div>
      </div>
    </div>
    <?php
  }

  /* ───────────────────────────────────────────────
   *  JS ENQUEUE
   * ─────────────────────────────────────────────── */

  private static function pn_customers_manager_budget_csv_render_js($ajax_url, $nonce, $export_url) {
    wp_enqueue_script(
      'pn-customers-manager-budget-csv',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-budget-csv.js',
      ['jquery'],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );

    wp_localize_script('pn-customers-manager-budget-csv', 'pnCmBudgetCsv', [
      'ajaxUrl'   => $ajax_url,
      'nonce'     => $nonce,
      'exportUrl' => $export_url,
      'i18n'      => self::pn_customers_manager_budget_csv_get_i18n(),
    ]);
  }

  /* ───────────────────────────────────────────────
   *  AJAX: Download CSV Template
   * ─────────────────────────────────────────────── */

  public function pn_customers_manager_budget_csv_download_template() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('Forbidden', 'pn-customers-manager'), 403);
    }

    check_admin_referer(self::NONCE_ACTION);

    $meta = self::pn_customers_manager_get_budget_csv_meta_fields();

    $header = [
      __('Title', 'pn-customers-manager'),
    ];
    foreach ($meta as $key => $info) {
      $header[] = $info[0];
    }

    $example = self::pn_customers_manager_get_budget_csv_example_row();
    $example_row = [];
    foreach ($header as $label) {
      $example_row[] = isset($example[$label]) ? $example[$label] : '';
    }

    $filename = 'budgets-import-template.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $header);
    fputcsv($output, $example_row);
    fclose($output);
    exit;
  }

  /* ───────────────────────────────────────────────
   *  AJAX: Export Budgets to CSV
   * ─────────────────────────────────────────────── */

  public function pn_customers_manager_budget_csv_export() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('Forbidden', 'pn-customers-manager'), 403);
    }

    check_admin_referer(self::NONCE_ACTION);

    global $wpdb;
    $items_table = $wpdb->prefix . 'pn_cm_budget_items';

    $query_args = [
      'post_type'   => 'pn_cm_budget',
      'numberposts' => -1,
      'post_status' => 'any',
      'orderby'     => 'date',
      'order'       => 'DESC',
      'fields'      => 'ids',
    ];

    // Optional status filter
    if (!empty($_GET['status'])) {
      $status_filter = sanitize_text_field(wp_unslash($_GET['status']));
      $valid_statuses = ['draft', 'sent', 'accepted', 'rejected'];
      if (in_array($status_filter, $valid_statuses, true)) {
        $query_args['meta_key']   = 'pn_cm_budget_status';
        $query_args['meta_value'] = $status_filter;
      }
    }

    $budgets = get_posts($query_args);

    $meta = self::pn_customers_manager_get_budget_csv_meta_fields();

    $header = [
      __('Title', 'pn-customers-manager'),
    ];
    foreach ($meta as $key => $info) {
      $header[] = $info[0];
    }

    $filename = 'budgets-export-' . gmdate('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $header);

    foreach ($budgets as $budget_id) {
      $row = [get_the_title($budget_id)];

      foreach ($meta as $key => $info) {
        if ($key === '_items_json') {
          // Encode items as JSON
          $items = $wpdb->get_results(
            $wpdb->prepare(
              "SELECT item_type, description, quantity, unit_price, is_optional, sort_order FROM {$items_table} WHERE budget_id = %d ORDER BY sort_order ASC",
              $budget_id
            ),
            ARRAY_A
          );
          $row[] = !empty($items) ? wp_json_encode($items) : '[]';
        } elseif ($key === 'pn_cm_budget_organization_id') {
          // Resolve org ID to name
          $org_id = get_post_meta($budget_id, $key, true);
          if (!empty($org_id)) {
            $org_title = get_the_title(intval($org_id));
            $row[] = !empty($org_title) ? $org_title : $org_id;
          } else {
            $row[] = '';
          }
        } else {
          $row[] = get_post_meta($budget_id, $key, true);
        }
      }

      fputcsv($output, $row);
    }

    fclose($output);
    exit;
  }

  /* ───────────────────────────────────────────────
   *  AJAX: Preview CSV
   * ─────────────────────────────────────────────── */

  public function pn_customers_manager_budget_csv_preview() {
    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Permission denied.', 'pn-customers-manager'));
    }

    check_ajax_referer(self::NONCE_ACTION);

    if (empty($_FILES['csv_file']['tmp_name'])) {
      wp_send_json_error(__('No file uploaded.', 'pn-customers-manager'));
    }

    $file = $_FILES['csv_file']['tmp_name'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file);
    finfo_close($finfo);

    $allowed = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'text/x-csv'];
    if (!in_array($mime, $allowed, true)) {
      wp_send_json_error(__('Invalid file type. Please upload a CSV file.', 'pn-customers-manager'));
    }

    $handle = fopen($file, 'r');
    if ($handle === false) {
      wp_send_json_error(__('Could not open the file.', 'pn-customers-manager'));
    }

    // Skip BOM
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
      rewind($handle);
    }

    $csv_header = fgetcsv($handle);
    if (!$csv_header) {
      fclose($handle);
      wp_send_json_error(__('Could not read CSV headers.', 'pn-customers-manager'));
    }

    $csv_header = array_map('trim', $csv_header);

    $label_to_key = self::pn_customers_manager_get_budget_csv_label_to_key_map();
    foreach ($csv_header as &$col) {
      if (isset($label_to_key[$col])) {
        $col = $label_to_key[$col];
      }
    }
    unset($col);

    if (!in_array('post_title', $csv_header, true)) {
      fclose($handle);
      wp_send_json_error(__('Missing required column: Title', 'pn-customers-manager'));
    }

    $rows = [];
    while (($data = fgetcsv($handle)) !== false) {
      $filtered = array_filter($data, function($v) { return $v !== '' && $v !== null; });
      if (empty($filtered)) {
        continue;
      }
      if (count($csv_header) === count($data)) {
        $rows[] = array_combine($csv_header, $data);
      }
    }
    fclose($handle);

    $key_to_label = self::pn_customers_manager_get_budget_csv_key_to_label_map();
    $display_header = [];
    foreach ($csv_header as $key) {
      $display_header[] = isset($key_to_label[$key]) ? $key_to_label[$key] : $key;
    }

    wp_send_json_success([
      'header'         => $csv_header,
      'display_header' => $display_header,
      'rows'           => $rows,
    ]);
  }

  /* ───────────────────────────────────────────────
   *  AJAX: Import Budgets
   * ─────────────────────────────────────────────── */

  public function pn_customers_manager_budget_csv_import() {
    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Permission denied.', 'pn-customers-manager'));
    }

    check_ajax_referer(self::NONCE_ACTION);

    $raw  = isset($_POST['rows']) ? wp_unslash($_POST['rows']) : '';
    $rows = json_decode($raw, true);

    if (empty($rows) || !is_array($rows)) {
      wp_send_json_error(__('No data to import.', 'pn-customers-manager'));
    }

    global $wpdb;
    $items_table = $wpdb->prefix . 'pn_cm_budget_items';

    $results = [
      'created' => 0,
      'errors'  => [],
    ];

    foreach ($rows as $index => $row) {
      $row_num = $index + 1;

      $title = isset($row['post_title']) ? sanitize_text_field(trim($row['post_title'])) : '';
      if (empty($title)) {
        $results['errors'][] = sprintf(__('Row %d: Title is empty, skipped.', 'pn-customers-manager'), $row_num);
        continue;
      }

      $post_data = [
        'post_title'  => $title,
        'post_status' => 'publish',
        'post_type'   => 'pn_cm_budget',
      ];

      $post_id = wp_insert_post($post_data, true);

      if (is_wp_error($post_id)) {
        $results['errors'][] = sprintf(
          __('Row %d: Error creating budget — %s', 'pn-customers-manager'),
          $row_num,
          $post_id->get_error_message()
        );
        continue;
      }

      // Budget number (auto-generate if empty)
      $budget_number = isset($row['pn_cm_budget_number']) ? sanitize_text_field(trim($row['pn_cm_budget_number'])) : '';
      if (empty($budget_number)) {
        $budget_number = PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_generate_number();
      }
      update_post_meta($post_id, 'pn_cm_budget_number', $budget_number);

      // Token (always auto-generate)
      $token = PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_generate_token();
      update_post_meta($post_id, 'pn_cm_budget_token', $token);

      // Organization (resolve by name or ID)
      if (!empty($row['pn_cm_budget_organization_id'])) {
        $org_value = sanitize_text_field(trim($row['pn_cm_budget_organization_id']));
        $org_id = self::pn_customers_manager_resolve_organization($org_value);
        if (!empty($org_id)) {
          update_post_meta($post_id, 'pn_cm_budget_organization_id', $org_id);
        }
      }

      // Date fields
      foreach (['pn_cm_budget_date', 'pn_cm_budget_valid_until'] as $date_key) {
        if (!empty($row[$date_key])) {
          $date_val = sanitize_text_field(trim($row[$date_key]));
          if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_val)) {
            update_post_meta($post_id, $date_key, $date_val);
          }
        }
      }

      // Status
      if (!empty($row['pn_cm_budget_status'])) {
        $status = sanitize_text_field(trim($row['pn_cm_budget_status']));
        $valid_statuses = ['draft', 'sent', 'accepted', 'rejected'];
        if (in_array($status, $valid_statuses, true)) {
          update_post_meta($post_id, 'pn_cm_budget_status', $status);
        } else {
          update_post_meta($post_id, 'pn_cm_budget_status', 'draft');
        }
      } else {
        update_post_meta($post_id, 'pn_cm_budget_status', 'draft');
      }

      // Numeric fields
      foreach (['pn_cm_budget_tax_rate', 'pn_cm_budget_discount_rate'] as $num_key) {
        if (isset($row[$num_key]) && $row[$num_key] !== '') {
          $num_val = trim($row[$num_key]);
          if (is_numeric($num_val)) {
            update_post_meta($post_id, $num_key, floatval($num_val));
          }
        }
      }

      // Text/textarea fields
      foreach (['pn_cm_budget_notes', 'pn_cm_budget_client_notes'] as $text_key) {
        if (!empty($row[$text_key])) {
          update_post_meta($post_id, $text_key, wp_kses_post(trim($row[$text_key])));
        }
      }

      // Items (JSON)
      if (!empty($row['_items_json'])) {
        $items = json_decode(trim($row['_items_json']), true);
        if (is_array($items)) {
          foreach ($items as $sort => $item) {
            $qty   = isset($item['quantity']) ? floatval($item['quantity']) : 1;
            $price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;

            $wpdb->insert(
              $items_table,
              [
                'budget_id'   => $post_id,
                'item_type'   => isset($item['item_type']) ? sanitize_text_field($item['item_type']) : 'fixed',
                'description' => isset($item['description']) ? sanitize_text_field($item['description']) : '',
                'quantity'    => $qty,
                'unit_price'  => $price,
                'total'       => round($qty * $price, 2),
                'is_optional' => isset($item['is_optional']) ? intval($item['is_optional']) : 0,
                'is_selected' => 1,
                'sort_order'  => isset($item['sort_order']) ? intval($item['sort_order']) : $sort,
              ],
              ['%d', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%d']
            );
          }
        }
      }

      // Recalculate totals
      PN_CUSTOMERS_MANAGER_Post_Type_Budget::pn_cm_budget_recalculate_totals($post_id);

      $results['created']++;
    }

    wp_send_json_success($results);
  }

  /* ───────────────────────────────────────────────
   *  HELPER: Resolve organization by name or ID
   * ─────────────────────────────────────────────── */

  private static function pn_customers_manager_resolve_organization($value) {
    // If numeric, check if it's a valid org post ID
    if (is_numeric($value)) {
      $org_id = intval($value);
      if (get_post_type($org_id) === 'pn_cm_organization') {
        return $org_id;
      }
    }

    // Search by title
    $orgs = get_posts([
      'post_type'   => 'pn_cm_organization',
      'title'       => $value,
      'numberposts' => 1,
      'post_status' => 'any',
      'fields'      => 'ids',
    ]);

    if (!empty($orgs)) {
      return $orgs[0];
    }

    return 0;
  }

  /* ───────────────────────────────────────────────
   *  I18N STRINGS
   * ─────────────────────────────────────────────── */

  private static function pn_customers_manager_budget_csv_get_i18n() {
    return [
      'importCsv'        => __('Import CSV', 'pn-customers-manager'),
      'preview'          => __('Preview', 'pn-customers-manager'),
      'back'             => __('Back', 'pn-customers-manager'),
      'importBtn'        => __('Import', 'pn-customers-manager'),
      'closeReload'      => __('Close & reload', 'pn-customers-manager'),
      'selectFile'       => __('Please select a CSV file first.', 'pn-customers-manager'),
      'parsingCsv'       => __('Parsing CSV…', 'pn-customers-manager'),
      'processing'       => __('Processing…', 'pn-customers-manager'),
      'noDataRows'       => __('The CSV file has no data rows.', 'pn-customers-manager'),
      'rowsFound'        => __('rows found', 'pn-customers-manager'),
      'showingFirst50'   => __('showing first 50', 'pn-customers-manager'),
      'importingBudgets' => __('Importing budgets…', 'pn-customers-manager'),
      'budgetsCreated'   => __('budgets created successfully.', 'pn-customers-manager'),
      'errors'           => __('Errors:', 'pn-customers-manager'),
    ];
  }
}
