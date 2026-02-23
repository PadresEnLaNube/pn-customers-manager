<?php
/**
 * CSV Import for Organizations.
 *
 * Adds an "Import CSV" button to the Organizations admin list page
 * with template download, preview table and bulk import.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Csv_Import {

  const NONCE_ACTION = 'pn_customers_manager_csv_import';

  /**
   * Meta fields available for CSV import.
   * key => [ label, type ]
   */
  private static function pn_customers_manager_get_csv_meta_fields() {
    return [
      'pn_cm_organization_legal_name'          => [__('Legal name', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_trade_name'          => [__('Trade name', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_segment'             => [__('Segment', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_industry'            => [__('Industry', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_team_size'           => [__('Team size', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_annual_revenue'      => [__('Annual revenue', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_phone'               => [__('Phone', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_email'               => [__('Email', 'pn-customers-manager'), 'email'],
      'pn_cm_organization_website'             => [__('Website', 'pn-customers-manager'), 'url'],
      'pn_cm_organization_linkedin'            => [__('LinkedIn', 'pn-customers-manager'), 'url'],
      'pn_cm_organization_country'             => [__('Country', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_region'              => [__('Region', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_city'                => [__('City', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_address'             => [__('Address', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_postal_code'         => [__('Postal code', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_fiscal_id'           => [__('Tax ID', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_lead_source'         => [__('Lead source', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_lifecycle_stage'     => [__('Lifecycle stage', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_pipeline_stage'      => [__('Pipeline stage', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_priority'            => [__('Priority', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_health'              => [__('Account health', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_lead_score'          => [__('Lead score', 'pn-customers-manager'), 'number'],
      'pn_cm_organization_owner'               => [__('Owner (user ID)', 'pn-customers-manager'), 'number'],
      'pn_cm_organization_last_contact_date'   => [__('Last contact date', 'pn-customers-manager'), 'date'],
      'pn_cm_organization_last_contact_channel'=> [__('Last contact channel', 'pn-customers-manager'), 'select'],
      'pn_cm_organization_next_action'         => [__('Next action', 'pn-customers-manager'), 'textarea'],
      'pn_cm_organization_billing_email'       => [__('Billing email', 'pn-customers-manager'), 'email'],
      'pn_cm_organization_billing_phone'       => [__('Billing phone', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_billing_address'     => [__('Billing address', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_tags'                => [__('Tags (comma-separated)', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_notes'               => [__('Internal notes', 'pn-customers-manager'), 'textarea'],
      'pn_cm_organization_funnel_id'           => [__('Funnel ID', 'pn-customers-manager'), 'number'],
      'pn_cm_organization_funnel_stage'        => [__('Funnel stage', 'pn-customers-manager'), 'text'],
      'pn_cm_organization_funnel_status'       => [__('Funnel status', 'pn-customers-manager'), 'select'],
    ];
  }

  /**
   * Example row for the CSV template (indexed by labels).
   * Select fields show all valid options separated by " | ".
   */
  private static function pn_customers_manager_get_csv_example_row() {
    $select_options = self::pn_customers_manager_get_csv_select_options();
    $meta           = self::pn_customers_manager_get_csv_meta_fields();

    // Base examples indexed by internal key
    $raw = [
      'post_title'   => 'Acme Corp',
      'post_content' => 'Leading provider of innovative solutions.',
      'post_excerpt' => 'Tech company',
      'post_status'  => 'publish',
      'pn_cm_organization_legal_name'          => 'Acme Corporation S.L.',
      'pn_cm_organization_trade_name'          => 'Acme',
      'pn_cm_organization_phone'               => '+34 600 123 456',
      'pn_cm_organization_email'               => 'info@acme.com',
      'pn_cm_organization_website'             => 'https://acme.com',
      'pn_cm_organization_linkedin'            => 'https://linkedin.com/company/acme',
      'pn_cm_organization_country'             => 'Spain',
      'pn_cm_organization_region'              => 'Madrid',
      'pn_cm_organization_city'                => 'Madrid',
      'pn_cm_organization_address'             => 'Calle Gran Via 1',
      'pn_cm_organization_postal_code'         => '28001',
      'pn_cm_organization_fiscal_id'           => 'B12345678',
      'pn_cm_organization_lead_score'          => '60',
      'pn_cm_organization_owner'               => '1',
      'pn_cm_organization_last_contact_date'   => '2026-01-15',
      'pn_cm_organization_next_action'         => 'Send proposal next week',
      'pn_cm_organization_billing_email'       => 'billing@acme.com',
      'pn_cm_organization_billing_phone'       => '+34 600 789 012',
      'pn_cm_organization_billing_address'     => 'Calle Gran Via 1, 28001 Madrid',
      'pn_cm_organization_tags'                => 'VIP, Renewal',
      'pn_cm_organization_notes'               => 'Key account — assigned to sales team A.',
      'pn_cm_organization_funnel_id'           => '',
      'pn_cm_organization_funnel_stage'        => '',
    ];

    // Override select fields with their valid options
    foreach ($select_options as $key => $options) {
      $raw[$key] = implode(' | ', $options);
    }

    // Reindex by labels
    $label_map = [
      'post_title'   => __('Title', 'pn-customers-manager'),
      'post_content' => __('Content', 'pn-customers-manager'),
      'post_excerpt' => __('Excerpt', 'pn-customers-manager'),
      'post_status'  => __('Status', 'pn-customers-manager'),
    ];
    foreach ($meta as $key => $info) {
      $label_map[$key] = $info[0];
    }

    $row = [];
    foreach ($label_map as $key => $label) {
      $row[$label] = isset($raw[$key]) ? $raw[$key] : '';
    }

    // Taxonomies
    $taxonomies = get_object_taxonomies('pn_cm_organization', 'objects');
    foreach ($taxonomies as $tax) {
      $row[$tax->label] = ($tax->name === 'pn_cm_organization_category') ? 'Technology, SaaS' : '';
    }

    return $row;
  }

  /**
   * Valid options for select-type meta fields.
   */
  private static function pn_customers_manager_get_csv_select_options() {
    return [
      'pn_cm_organization_segment'             => ['startup', 'smb', 'enterprise', 'nonprofit', 'government'],
      'pn_cm_organization_industry'            => ['software', 'services', 'manufacturing', 'education', 'health', 'finance', 'retail', 'other'],
      'pn_cm_organization_team_size'           => ['1-10', '11-50', '51-200', '201-500', '500+'],
      'pn_cm_organization_annual_revenue'      => ['<250k', '250k-1m', '1m-5m', '5m-20m', '>20m'],
      'pn_cm_organization_lead_source'         => ['website', 'ads', 'event', 'referral', 'outbound', 'partner', 'other'],
      'pn_cm_organization_lifecycle_stage'     => ['lead', 'marketing_qualified', 'sales_qualified', 'opportunity', 'customer', 'churned'],
      'pn_cm_organization_pipeline_stage'      => ['qualification', 'discovery', 'proposal', 'negotiation', 'closed_won', 'closed_lost'],
      'pn_cm_organization_priority'            => ['high', 'medium', 'low'],
      'pn_cm_organization_health'              => ['healthy', 'risk', 'churn'],
      'pn_cm_organization_last_contact_channel'=> ['email', 'call', 'meeting', 'chat', 'event', 'other'],
      'pn_cm_organization_funnel_status'       => ['not_started', 'in_progress', 'stalled', 'won', 'lost'],
    ];
  }

  /**
   * Inverse map: label → key for translating CSV headers back to internal keys.
   */
  private static function pn_customers_manager_get_csv_label_to_key_map() {
    // English fallbacks (always present for retrocompatibility)
    $map = [
      'Title'   => 'post_title',
      'Content' => 'post_content',
      'Excerpt' => 'post_excerpt',
      'Status'  => 'post_status',
    ];

    // Translated labels (override English if different)
    $map[__('Title', 'pn-customers-manager')]   = 'post_title';
    $map[__('Content', 'pn-customers-manager')] = 'post_content';
    $map[__('Excerpt', 'pn-customers-manager')] = 'post_excerpt';
    $map[__('Status', 'pn-customers-manager')]  = 'post_status';

    $meta = self::pn_customers_manager_get_csv_meta_fields();
    foreach ($meta as $key => $info) {
      // $info[0] is already translated via __()
      $map[$info[0]] = $key;
    }

    // English fallback labels for meta fields
    $english_labels = [
      'Legal name' => 'pn_cm_organization_legal_name', 'Trade name' => 'pn_cm_organization_trade_name',
      'Segment' => 'pn_cm_organization_segment', 'Industry' => 'pn_cm_organization_industry',
      'Team size' => 'pn_cm_organization_team_size', 'Annual revenue' => 'pn_cm_organization_annual_revenue',
      'Phone' => 'pn_cm_organization_phone', 'Email' => 'pn_cm_organization_email',
      'Website' => 'pn_cm_organization_website', 'LinkedIn' => 'pn_cm_organization_linkedin',
      'Country' => 'pn_cm_organization_country', 'Region' => 'pn_cm_organization_region',
      'City' => 'pn_cm_organization_city', 'Address' => 'pn_cm_organization_address',
      'Postal code' => 'pn_cm_organization_postal_code', 'Tax ID' => 'pn_cm_organization_fiscal_id',
      'Lead source' => 'pn_cm_organization_lead_source', 'Lifecycle stage' => 'pn_cm_organization_lifecycle_stage',
      'Pipeline stage' => 'pn_cm_organization_pipeline_stage', 'Priority' => 'pn_cm_organization_priority',
      'Account health' => 'pn_cm_organization_health', 'Lead score' => 'pn_cm_organization_lead_score',
      'Owner (user ID)' => 'pn_cm_organization_owner', 'Last contact date' => 'pn_cm_organization_last_contact_date',
      'Last contact channel' => 'pn_cm_organization_last_contact_channel', 'Next action' => 'pn_cm_organization_next_action',
      'Billing email' => 'pn_cm_organization_billing_email', 'Billing phone' => 'pn_cm_organization_billing_phone',
      'Billing address' => 'pn_cm_organization_billing_address', 'Tags (comma-separated)' => 'pn_cm_organization_tags',
      'Internal notes' => 'pn_cm_organization_notes', 'Funnel ID' => 'pn_cm_organization_funnel_id',
      'Funnel stage' => 'pn_cm_organization_funnel_stage', 'Funnel status' => 'pn_cm_organization_funnel_status',
    ];
    foreach ($english_labels as $en_label => $key) {
      if (!isset($map[$en_label])) {
        $map[$en_label] = $key;
      }
    }

    $taxonomies = get_object_taxonomies('pn_cm_organization', 'objects');
    foreach ($taxonomies as $tax) {
      $map[$tax->label] = 'tax:' . $tax->name;
    }

    return $map;
  }

  /**
   * Key → translated label map for display purposes.
   */
  private static function pn_customers_manager_get_csv_key_to_label_map() {
    $map = [
      'post_title'   => __('Title', 'pn-customers-manager'),
      'post_content' => __('Content', 'pn-customers-manager'),
      'post_excerpt' => __('Excerpt', 'pn-customers-manager'),
      'post_status'  => __('Status', 'pn-customers-manager'),
    ];

    $meta = self::pn_customers_manager_get_csv_meta_fields();
    foreach ($meta as $key => $info) {
      $map[$key] = $info[0];
    }

    $taxonomies = get_object_taxonomies('pn_cm_organization', 'objects');
    foreach ($taxonomies as $tax) {
      $map['tax:' . $tax->name] = $tax->label;
    }

    return $map;
  }

  /* ───────────────────────────────────────────────
   *  BUTTON + MODAL INJECTION (admin_head on edit.php)
   * ─────────────────────────────────────────────── */

  public function pn_customers_manager_csv_inject_button() {
    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== 'pn_cm_organization') {
      return;
    }

    if (!current_user_can('edit_pn_cm_organization')) {
      return;
    }

    $nonce = wp_create_nonce(self::NONCE_ACTION);
    $ajax_url = admin_url('admin-ajax.php');
    $template_url = add_query_arg([
      'action'   => 'pn_customers_manager_csv_template',
      '_wpnonce' => $nonce,
    ], $ajax_url);

    self::pn_customers_manager_csv_render_css();
    self::pn_customers_manager_csv_render_modal($template_url);
    self::pn_customers_manager_csv_render_js($ajax_url, $nonce);
  }

  /* ───────────────────────────────────────────────
   *  INLINE CSS
   * ─────────────────────────────────────────────── */

  private static function pn_customers_manager_csv_render_css() {
    ?>
    <style>
      .pn-cm-csv-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:100100;align-items:center;justify-content:center}
      .pn-cm-csv-overlay.pn-cm-csv-open{display:flex}
      .pn-cm-csv-modal{background:#fff;border-radius:8px;width:720px;max-width:94vw;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 8px 30px rgba(0,0,0,.25);position:relative}
      .pn-cm-csv-header{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #e0e0e0}
      .pn-cm-csv-header h2{margin:0;font-size:17px}
      .pn-cm-csv-close{background:none;border:none;cursor:pointer;font-size:22px;color:#666;line-height:1;padding:4px}
      .pn-cm-csv-close:hover{color:#d63638}
      .pn-cm-csv-body{padding:24px;overflow-y:auto;flex:1}
      .pn-cm-csv-step{display:none}
      .pn-cm-csv-step.pn-cm-csv-active{display:block}
      .pn-cm-csv-file-zone{border:2px dashed #c3c4c7;border-radius:6px;padding:30px 20px;text-align:center;margin:16px 0;transition:border-color .2s,background .2s}
      .pn-cm-csv-file-zone.pn-cm-csv-dragover{border-color:#2271b1;background:#f0f6fc}
      .pn-cm-csv-file-zone input[type="file"]{display:none}
      .pn-cm-csv-file-zone label{cursor:pointer;color:#2271b1;font-weight:500}
      .pn-cm-csv-file-zone label:hover{color:#135e96;text-decoration:underline}
      .pn-cm-csv-file-name{margin-top:8px;font-size:13px;color:#50575e}
      .pn-cm-csv-preview-wrap{max-height:360px;overflow:auto;border:1px solid #e0e0e0;border-radius:4px}
      .pn-cm-csv-preview-wrap table{border-collapse:collapse;width:100%;font-size:13px}
      .pn-cm-csv-preview-wrap th{position:sticky;top:0;background:#f6f7f7;font-weight:600;text-align:left;padding:8px 10px;border-bottom:2px solid #c3c4c7;white-space:nowrap}
      .pn-cm-csv-preview-wrap td{padding:6px 10px;border-bottom:1px solid #f0f0f1;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
      .pn-cm-csv-preview-wrap tr:hover td{background:#f9f9f9}
      .pn-cm-csv-row-count{margin:12px 0 0;font-size:13px;color:#50575e}
      .pn-cm-csv-footer{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid #e0e0e0}
      .pn-cm-csv-results-success{background:#edfaef;border:1px solid #46b450;padding:14px 18px;border-radius:4px;margin-bottom:14px;color:#1e4620}
      .pn-cm-csv-results-errors{background:#fff3f3;border:1px solid #d63638;padding:14px 18px;border-radius:4px;max-height:200px;overflow-y:auto;color:#8a1f1f;font-size:13px}
      .pn-cm-csv-results-errors ul{margin:6px 0 0 16px;padding:0}
      .pn-cm-csv-loader{display:none;text-align:center;padding:40px 0}
      .pn-cm-csv-loader.pn-cm-csv-active{display:block}
      .pn-cm-csv-spinner{display:inline-block;width:32px;height:32px;border:3px solid #e0e0e0;border-top-color:#2271b1;border-radius:50%;animation:pn-cm-csv-spin .7s linear infinite}
      @keyframes pn-cm-csv-spin{to{transform:rotate(360deg)}}
      .pn-cm-csv-template-link{display:inline-flex;align-items:center;gap:6px;text-decoration:none;font-size:13px}
      .pn-cm-csv-import-btn .dashicons{vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:2px}
    </style>
    <?php
  }

  /* ───────────────────────────────────────────────
   *  MODAL HTML
   * ─────────────────────────────────────────────── */

  private static function pn_customers_manager_csv_render_modal($template_url) {
    ?>
    <div id="pn-cm-csv-overlay" class="pn-cm-csv-overlay">
      <div class="pn-cm-csv-modal">
        <div class="pn-cm-csv-header">
          <h2><?php esc_html_e('Import Organizations from CSV', 'pn-customers-manager'); ?></h2>
          <button type="button" class="pn-cm-csv-close" id="pn-cm-csv-close">&times;</button>
        </div>

        <div class="pn-cm-csv-body">
          <!-- Step 1: Upload -->
          <div id="pn-cm-csv-step-1" class="pn-cm-csv-step pn-cm-csv-active">
            <p><?php esc_html_e('Upload a CSV file to import organizations. The file must include at least a "post_title" column.', 'pn-customers-manager'); ?></p>
            <p>
              <a href="<?php echo esc_url($template_url); ?>" class="pn-cm-csv-template-link" target="_blank">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Download CSV template', 'pn-customers-manager'); ?>
              </a>
            </p>
            <div class="pn-cm-csv-file-zone" id="pn-cm-csv-file-zone">
              <input type="file" id="pn-cm-csv-file" accept=".csv" />
              <label for="pn-cm-csv-file">
                <span class="dashicons dashicons-upload" style="font-size:28px;width:28px;height:28px;display:block;margin:0 auto 8px"></span>
                <?php esc_html_e('Click to select a CSV file or drag & drop it here', 'pn-customers-manager'); ?>
              </label>
              <div class="pn-cm-csv-file-name" id="pn-cm-csv-file-name"></div>
            </div>
          </div>

          <!-- Step 2: Preview -->
          <div id="pn-cm-csv-step-2" class="pn-cm-csv-step">
            <div class="pn-cm-csv-preview-wrap" id="pn-cm-csv-preview-wrap"></div>
            <div class="pn-cm-csv-row-count" id="pn-cm-csv-row-count"></div>
          </div>

          <!-- Step 3: Results -->
          <div id="pn-cm-csv-step-3" class="pn-cm-csv-step">
            <div id="pn-cm-csv-results"></div>
          </div>

          <!-- Loader -->
          <div class="pn-cm-csv-loader" id="pn-cm-csv-loader">
            <div class="pn-cm-csv-spinner"></div>
            <p id="pn-cm-csv-loader-text"><?php esc_html_e('Processing…', 'pn-customers-manager'); ?></p>
          </div>
        </div>

        <div class="pn-cm-csv-footer" id="pn-cm-csv-footer"></div>
      </div>
    </div>
    <?php
  }

  /* ───────────────────────────────────────────────
   *  INLINE JS
   * ─────────────────────────────────────────────── */

  private static function pn_customers_manager_csv_render_js($ajax_url, $nonce) {
    ?>
    <script>
    (function(){
      'use strict';

      var ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
      var nonce   = <?php echo wp_json_encode($nonce); ?>;

      /* Inject Import button next to Add New */
      var addNewBtn = document.querySelector('.page-title-action');
      if (addNewBtn) {
        var importBtn = document.createElement('a');
        importBtn.href = '#';
        importBtn.className = 'page-title-action pn-cm-csv-import-btn';
        importBtn.innerHTML = '<span class="dashicons dashicons-upload"></span> ' + <?php echo wp_json_encode(__('Import CSV', 'pn-customers-manager')); ?>;
        addNewBtn.parentNode.insertBefore(importBtn, addNewBtn.nextSibling);
        importBtn.addEventListener('click', function(e){ e.preventDefault(); openModal(); });
      }

      /* Elements */
      var overlay   = document.getElementById('pn-cm-csv-overlay');
      var closeBtn  = document.getElementById('pn-cm-csv-close');
      var fileInput = document.getElementById('pn-cm-csv-file');
      var fileZone  = document.getElementById('pn-cm-csv-file-zone');
      var fileName  = document.getElementById('pn-cm-csv-file-name');
      var footer    = document.getElementById('pn-cm-csv-footer');
      var loader    = document.getElementById('pn-cm-csv-loader');
      var loaderTxt = document.getElementById('pn-cm-csv-loader-text');
      var previewData = null;

      /* Modal */
      function openModal() {
        showStep(1);
        fileInput.value = '';
        fileName.textContent = '';
        previewData = null;
        overlay.classList.add('pn-cm-csv-open');
      }

      function closeModal() {
        overlay.classList.remove('pn-cm-csv-open');
      }

      closeBtn.addEventListener('click', closeModal);
      overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });

      /* Drag & drop */
      ['dragenter','dragover'].forEach(function(evt){
        fileZone.addEventListener(evt, function(e){ e.preventDefault(); fileZone.classList.add('pn-cm-csv-dragover'); });
      });
      ['dragleave','drop'].forEach(function(evt){
        fileZone.addEventListener(evt, function(e){ e.preventDefault(); fileZone.classList.remove('pn-cm-csv-dragover'); });
      });
      fileZone.addEventListener('drop', function(e){
        var files = e.dataTransfer.files;
        if (files.length && files[0].name.toLowerCase().endsWith('.csv')) {
          fileInput.files = files;
          fileName.textContent = files[0].name;
        }
      });
      fileInput.addEventListener('change', function(){
        fileName.textContent = fileInput.files.length ? fileInput.files[0].name : '';
      });

      /* Steps */
      function showStep(n) {
        [1,2,3].forEach(function(i){
          document.getElementById('pn-cm-csv-step-' + i).classList.toggle('pn-cm-csv-active', i === n);
        });
        loader.classList.remove('pn-cm-csv-active');
        renderFooter(n);
      }

      function showLoader(text) {
        [1,2,3].forEach(function(i){ document.getElementById('pn-cm-csv-step-' + i).classList.remove('pn-cm-csv-active'); });
        loaderTxt.textContent = text || <?php echo wp_json_encode(__('Processing…', 'pn-customers-manager')); ?>;
        loader.classList.add('pn-cm-csv-active');
        footer.innerHTML = '';
      }

      function renderFooter(step) {
        footer.innerHTML = '';
        if (step === 1) {
          footer.appendChild(btn(<?php echo wp_json_encode(__('Preview', 'pn-customers-manager')); ?>, 'button button-primary', doPreview));
        }
        if (step === 2) {
          footer.appendChild(btn(<?php echo wp_json_encode(__('Back', 'pn-customers-manager')); ?>, 'button', function(){ showStep(1); }));
          footer.appendChild(btn(<?php echo wp_json_encode(__('Import', 'pn-customers-manager')); ?>, 'button button-primary', doImport));
        }
        if (step === 3) {
          footer.appendChild(btn(<?php echo wp_json_encode(__('Close & reload', 'pn-customers-manager')); ?>, 'button button-primary', function(){ window.location.reload(); }));
        }
      }

      function btn(label, cls, handler) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = cls; b.textContent = label;
        b.addEventListener('click', handler);
        return b;
      }

      /* Preview */
      function doPreview() {
        if (!fileInput.files.length) {
          alert(<?php echo wp_json_encode(__('Please select a CSV file first.', 'pn-customers-manager')); ?>);
          return;
        }
        showLoader(<?php echo wp_json_encode(__('Parsing CSV…', 'pn-customers-manager')); ?>);

        var fd = new FormData();
        fd.append('action', 'pn_customers_manager_csv_preview');
        fd.append('_wpnonce', nonce);
        fd.append('csv_file', fileInput.files[0]);

        fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' })
          .then(function(r){ return r.json(); })
          .then(function(resp){
            if (!resp.success) { alert(resp.data || 'Error'); showStep(1); return; }
            previewData = resp.data;
            renderPreview(previewData);
            showStep(2);
          })
          .catch(function(err){ alert('Network error: ' + err); showStep(1); });
      }

      function renderPreview(data) {
        var wrap  = document.getElementById('pn-cm-csv-preview-wrap');
        var count = document.getElementById('pn-cm-csv-row-count');
        var displayHeader = data.display_header || data.header;
        if (!data.rows.length) {
          wrap.innerHTML = '<p style="padding:20px">' + <?php echo wp_json_encode(__('The CSV file has no data rows.', 'pn-customers-manager')); ?> + '</p>';
          count.textContent = '';
          return;
        }
        var html = '<table><thead><tr>';
        displayHeader.forEach(function(h){ html += '<th>' + esc(h) + '</th>'; });
        html += '</tr></thead><tbody>';
        var max = Math.min(data.rows.length, 50);
        for (var i = 0; i < max; i++) {
          html += '<tr>';
          data.header.forEach(function(h){ html += '<td>' + esc(data.rows[i][h] || '') + '</td>'; });
          html += '</tr>';
        }
        html += '</tbody></table>';
        wrap.innerHTML = html;
        var msg = data.rows.length + ' ' + <?php echo wp_json_encode(__('rows found', 'pn-customers-manager')); ?>;
        if (data.rows.length > 50) msg += ' — ' + <?php echo wp_json_encode(__('showing first 50', 'pn-customers-manager')); ?>;
        count.textContent = msg;
      }

      function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

      /* Import */
      function doImport() {
        if (!previewData || !previewData.rows.length) return;
        showLoader(<?php echo wp_json_encode(__('Importing organizations…', 'pn-customers-manager')); ?>);

        var fd = new FormData();
        fd.append('action', 'pn_customers_manager_csv_import');
        fd.append('_wpnonce', nonce);
        fd.append('rows', JSON.stringify(previewData.rows));

        fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' })
          .then(function(r){ return r.json(); })
          .then(function(resp){
            if (!resp.success) { alert(resp.data || 'Error'); showStep(2); return; }
            renderResults(resp.data);
            showStep(3);
          })
          .catch(function(err){ alert('Network error: ' + err); showStep(2); });
      }

      function renderResults(data) {
        var el = document.getElementById('pn-cm-csv-results');
        var html = '<div class="pn-cm-csv-results-success"><strong>' + data.created + '</strong> '
          + <?php echo wp_json_encode(__('organizations created successfully.', 'pn-customers-manager')); ?> + '</div>';
        if (data.errors && data.errors.length) {
          html += '<div class="pn-cm-csv-results-errors"><strong>' + <?php echo wp_json_encode(__('Errors:', 'pn-customers-manager')); ?> + '</strong><ul>';
          data.errors.forEach(function(e){ html += '<li>' + esc(e) + '</li>'; });
          html += '</ul></div>';
        }
        el.innerHTML = html;
      }

    })();
    </script>
    <?php
  }

  /* ───────────────────────────────────────────────
   *  AJAX: Download CSV Template
   * ─────────────────────────────────────────────── */

  public function pn_customers_manager_csv_download_template() {
    if (!current_user_can('edit_pn_cm_organization')) {
      wp_die(esc_html__('Forbidden', 'pn-customers-manager'), 403);
    }

    check_admin_referer(self::NONCE_ACTION);

    $meta = self::pn_customers_manager_get_csv_meta_fields();
    $taxonomies = get_object_taxonomies('pn_cm_organization', 'objects');

    // Header with readable translated labels
    $header = [
      __('Title', 'pn-customers-manager'),
      __('Content', 'pn-customers-manager'),
      __('Excerpt', 'pn-customers-manager'),
      __('Status', 'pn-customers-manager'),
    ];
    foreach ($meta as $key => $info) {
      $header[] = $info[0];
    }
    foreach ($taxonomies as $tax) {
      $header[] = $tax->label;
    }

    // Example row (already indexed by labels)
    $example = self::pn_customers_manager_get_csv_example_row();
    $example_row = [];
    foreach ($header as $label) {
      $example_row[] = isset($example[$label]) ? $example[$label] : '';
    }

    $filename = 'organizations-import-template.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $header);
    fputcsv($output, $example_row);
    fclose($output);
    exit;
  }

  /* ───────────────────────────────────────────────
   *  AJAX: Preview CSV
   * ─────────────────────────────────────────────── */

  public function pn_customers_manager_csv_preview() {
    if (!current_user_can('edit_pn_cm_organization')) {
      wp_send_json_error(__('Permission denied.', 'pn-customers-manager'));
    }

    check_ajax_referer(self::NONCE_ACTION);

    if (empty($_FILES['csv_file']['tmp_name'])) {
      wp_send_json_error(__('No file uploaded.', 'pn-customers-manager'));
    }

    $file = $_FILES['csv_file']['tmp_name'];

    // Validate MIME
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

    $header = fgetcsv($handle);
    if (!$header) {
      fclose($handle);
      wp_send_json_error(__('Could not read CSV headers.', 'pn-customers-manager'));
    }

    $header = array_map('trim', $header);

    // Translate label headers to internal keys (supports both labels and legacy keys)
    $label_to_key = self::pn_customers_manager_get_csv_label_to_key_map();
    foreach ($header as &$col) {
      if (isset($label_to_key[$col])) {
        $col = $label_to_key[$col];
      }
    }
    unset($col);

    if (!in_array('post_title', $header, true)) {
      fclose($handle);
      wp_send_json_error(__('Missing required column: post_title (or Title)', 'pn-customers-manager'));
    }

    $rows = [];
    while (($data = fgetcsv($handle)) !== false) {
      $filtered = array_filter($data, function($v) { return $v !== '' && $v !== null; });
      if (empty($filtered)) {
        continue;
      }
      if (count($header) === count($data)) {
        $rows[] = array_combine($header, $data);
      }
    }
    fclose($handle);

    // Build display headers (translated labels) for preview table
    $key_to_label = self::pn_customers_manager_get_csv_key_to_label_map();
    $display_header = [];
    foreach ($header as $key) {
      $display_header[] = isset($key_to_label[$key]) ? $key_to_label[$key] : $key;
    }

    wp_send_json_success([
      'header'         => $header,
      'display_header' => $display_header,
      'rows'           => $rows,
    ]);
  }

  /* ───────────────────────────────────────────────
   *  AJAX: Import
   * ─────────────────────────────────────────────── */

  public function pn_customers_manager_csv_import() {
    if (!current_user_can('edit_pn_cm_organization')) {
      wp_send_json_error(__('Permission denied.', 'pn-customers-manager'));
    }

    check_ajax_referer(self::NONCE_ACTION);

    $raw  = isset($_POST['rows']) ? wp_unslash($_POST['rows']) : '';
    $rows = json_decode($raw, true);

    if (empty($rows) || !is_array($rows)) {
      wp_send_json_error(__('No data to import.', 'pn-customers-manager'));
    }

    $meta_fields = self::pn_customers_manager_get_csv_meta_fields();
    $taxonomies  = get_object_taxonomies('pn_cm_organization', 'objects');
    $tax_slugs   = [];
    foreach ($taxonomies as $tax) {
      $tax_slugs['tax:' . $tax->name] = $tax->name;
    }

    $results = [
      'created' => 0,
      'errors'  => [],
    ];

    $post_fields = ['post_title', 'post_content', 'post_excerpt', 'post_status'];

    foreach ($rows as $index => $row) {
      $row_num = $index + 1;

      $title = isset($row['post_title']) ? sanitize_text_field(trim($row['post_title'])) : '';
      if (empty($title)) {
        $results['errors'][] = sprintf(__('Row %d: post_title is empty, skipped.', 'pn-customers-manager'), $row_num);
        continue;
      }

      $post_data = [
        'post_title'   => $title,
        'post_content' => isset($row['post_content']) ? wp_kses_post($row['post_content']) : '',
        'post_excerpt' => isset($row['post_excerpt']) ? sanitize_text_field($row['post_excerpt']) : '',
        'post_status'  => 'publish',
        'post_type'    => 'pn_cm_organization',
      ];

      if (!empty($row['post_status'])) {
        $status = sanitize_text_field($row['post_status']);
        if (in_array($status, ['publish', 'draft', 'pending', 'private'], true)) {
          $post_data['post_status'] = $status;
        }
      }

      $post_id = wp_insert_post($post_data, true);

      if (is_wp_error($post_id)) {
        $results['errors'][] = sprintf(
          __('Row %d: Error creating post — %s', 'pn-customers-manager'),
          $row_num,
          $post_id->get_error_message()
        );
        continue;
      }

      // Meta fields
      foreach ($row as $col => $value) {
        if (in_array($col, $post_fields, true)) {
          continue;
        }
        if (isset($tax_slugs[$col])) {
          continue;
        }
        if (!isset($meta_fields[$col])) {
          continue;
        }

        $value = trim($value);
        if ($value === '') {
          continue;
        }

        $field_type = $meta_fields[$col][1];

        switch ($field_type) {
          case 'email':
            $value = sanitize_email($value);
            break;
          case 'url':
            $value = esc_url_raw($value);
            break;
          case 'number':
            $value = is_numeric($value) ? $value : '';
            break;
          case 'textarea':
            $value = wp_kses_post($value);
            break;
          default:
            $value = sanitize_text_field($value);
            break;
        }

        if ($value !== '') {
          update_post_meta($post_id, $col, $value);
        }
      }

      // Taxonomy terms
      foreach ($tax_slugs as $csv_col => $tax_name) {
        if (empty($row[$csv_col])) {
          continue;
        }

        $terms = array_map('trim', explode(',', $row[$csv_col]));
        $terms = array_filter($terms);

        if (!empty($terms)) {
          $term_ids = [];
          foreach ($terms as $term_name) {
            $existing = get_term_by('name', $term_name, $tax_name);
            if ($existing) {
              $term_ids[] = $existing->term_id;
            } else {
              $inserted = wp_insert_term($term_name, $tax_name);
              if (!is_wp_error($inserted)) {
                $term_ids[] = $inserted['term_id'];
              }
            }
          }

          if (!empty($term_ids)) {
            wp_set_object_terms($post_id, $term_ids, $tax_name);
          }
        }
      }

      $results['created']++;
    }

    wp_send_json_success($results);
  }

  /* ───────────────────────────────────────────────
   *  FRONT-END MODAL + ASSETS (called from shortcode)
   * ─────────────────────────────────────────────── */

  public static function pn_customers_manager_csv_render_frontend() {
    if (!current_user_can('edit_pn_cm_organization')) {
      return;
    }

    $nonce = wp_create_nonce(self::NONCE_ACTION);
    $ajax_url = admin_url('admin-ajax.php');
    $template_url = add_query_arg([
      'action'   => 'pn_customers_manager_csv_template',
      '_wpnonce' => $nonce,
    ], $ajax_url);

    self::pn_customers_manager_csv_render_css();
    self::pn_customers_manager_csv_render_modal($template_url);
    self::pn_customers_manager_csv_render_js_frontend($ajax_url, $nonce);
  }

  /* ───────────────────────────────────────────────
   *  INLINE JS (front-end variant)
   * ─────────────────────────────────────────────── */

  private static function pn_customers_manager_csv_render_js_frontend($ajax_url, $nonce) {
    ?>
    <script>
    (function(){
      'use strict';

      var ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
      var nonce   = <?php echo wp_json_encode($nonce); ?>;

      /* Bind click on the trigger button rendered in the toolbar */
      var triggerBtn = document.getElementById('pn-cm-csv-import-trigger');
      if (triggerBtn) {
        triggerBtn.addEventListener('click', function(e){ e.preventDefault(); openModal(); });
      }

      /* Elements */
      var overlay   = document.getElementById('pn-cm-csv-overlay');
      var closeBtn  = document.getElementById('pn-cm-csv-close');
      var fileInput = document.getElementById('pn-cm-csv-file');
      var fileZone  = document.getElementById('pn-cm-csv-file-zone');
      var fileName  = document.getElementById('pn-cm-csv-file-name');
      var footer    = document.getElementById('pn-cm-csv-footer');
      var loader    = document.getElementById('pn-cm-csv-loader');
      var loaderTxt = document.getElementById('pn-cm-csv-loader-text');
      var previewData = null;

      /* Modal */
      function openModal() {
        showStep(1);
        fileInput.value = '';
        fileName.textContent = '';
        previewData = null;
        overlay.classList.add('pn-cm-csv-open');
      }

      function closeModal() {
        overlay.classList.remove('pn-cm-csv-open');
      }

      closeBtn.addEventListener('click', closeModal);
      overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });

      /* Drag & drop */
      ['dragenter','dragover'].forEach(function(evt){
        fileZone.addEventListener(evt, function(e){ e.preventDefault(); fileZone.classList.add('pn-cm-csv-dragover'); });
      });
      ['dragleave','drop'].forEach(function(evt){
        fileZone.addEventListener(evt, function(e){ e.preventDefault(); fileZone.classList.remove('pn-cm-csv-dragover'); });
      });
      fileZone.addEventListener('drop', function(e){
        var files = e.dataTransfer.files;
        if (files.length && files[0].name.toLowerCase().endsWith('.csv')) {
          fileInput.files = files;
          fileName.textContent = files[0].name;
        }
      });
      fileInput.addEventListener('change', function(){
        fileName.textContent = fileInput.files.length ? fileInput.files[0].name : '';
      });

      /* Steps */
      function showStep(n) {
        [1,2,3].forEach(function(i){
          document.getElementById('pn-cm-csv-step-' + i).classList.toggle('pn-cm-csv-active', i === n);
        });
        loader.classList.remove('pn-cm-csv-active');
        renderFooter(n);
      }

      function showLoader(text) {
        [1,2,3].forEach(function(i){ document.getElementById('pn-cm-csv-step-' + i).classList.remove('pn-cm-csv-active'); });
        loaderTxt.textContent = text || <?php echo wp_json_encode(__('Processing…', 'pn-customers-manager')); ?>;
        loader.classList.add('pn-cm-csv-active');
        footer.innerHTML = '';
      }

      function renderFooter(step) {
        footer.innerHTML = '';
        if (step === 1) {
          footer.appendChild(btn(<?php echo wp_json_encode(__('Preview', 'pn-customers-manager')); ?>, 'pn-customers-manager-btn', doPreview));
        }
        if (step === 2) {
          footer.appendChild(btn(<?php echo wp_json_encode(__('Back', 'pn-customers-manager')); ?>, 'pn-customers-manager-btn', function(){ showStep(1); }));
          footer.appendChild(btn(<?php echo wp_json_encode(__('Import', 'pn-customers-manager')); ?>, 'pn-customers-manager-btn', doImport));
        }
        if (step === 3) {
          footer.appendChild(btn(<?php echo wp_json_encode(__('Close & reload', 'pn-customers-manager')); ?>, 'pn-customers-manager-btn', function(){ window.location.reload(); }));
        }
      }

      function btn(label, cls, handler) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = cls; b.textContent = label;
        b.addEventListener('click', handler);
        return b;
      }

      /* Preview */
      function doPreview() {
        if (!fileInput.files.length) {
          alert(<?php echo wp_json_encode(__('Please select a CSV file first.', 'pn-customers-manager')); ?>);
          return;
        }
        showLoader(<?php echo wp_json_encode(__('Parsing CSV…', 'pn-customers-manager')); ?>);

        var fd = new FormData();
        fd.append('action', 'pn_customers_manager_csv_preview');
        fd.append('_wpnonce', nonce);
        fd.append('csv_file', fileInput.files[0]);

        fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' })
          .then(function(r){ return r.json(); })
          .then(function(resp){
            if (!resp.success) { alert(resp.data || 'Error'); showStep(1); return; }
            previewData = resp.data;
            renderPreview(previewData);
            showStep(2);
          })
          .catch(function(err){ alert('Network error: ' + err); showStep(1); });
      }

      function renderPreview(data) {
        var wrap  = document.getElementById('pn-cm-csv-preview-wrap');
        var count = document.getElementById('pn-cm-csv-row-count');
        var displayHeader = data.display_header || data.header;
        if (!data.rows.length) {
          wrap.innerHTML = '<p style="padding:20px">' + <?php echo wp_json_encode(__('The CSV file has no data rows.', 'pn-customers-manager')); ?> + '</p>';
          count.textContent = '';
          return;
        }
        var html = '<table><thead><tr>';
        displayHeader.forEach(function(h){ html += '<th>' + esc(h) + '</th>'; });
        html += '</tr></thead><tbody>';
        var max = Math.min(data.rows.length, 50);
        for (var i = 0; i < max; i++) {
          html += '<tr>';
          data.header.forEach(function(h){ html += '<td>' + esc(data.rows[i][h] || '') + '</td>'; });
          html += '</tr>';
        }
        html += '</tbody></table>';
        wrap.innerHTML = html;
        var msg = data.rows.length + ' ' + <?php echo wp_json_encode(__('rows found', 'pn-customers-manager')); ?>;
        if (data.rows.length > 50) msg += ' — ' + <?php echo wp_json_encode(__('showing first 50', 'pn-customers-manager')); ?>;
        count.textContent = msg;
      }

      function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

      /* Import */
      function doImport() {
        if (!previewData || !previewData.rows.length) return;
        showLoader(<?php echo wp_json_encode(__('Importing organizations…', 'pn-customers-manager')); ?>);

        var fd = new FormData();
        fd.append('action', 'pn_customers_manager_csv_import');
        fd.append('_wpnonce', nonce);
        fd.append('rows', JSON.stringify(previewData.rows));

        fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' })
          .then(function(r){ return r.json(); })
          .then(function(resp){
            if (!resp.success) { alert(resp.data || 'Error'); showStep(2); return; }
            renderResults(resp.data);
            showStep(3);
          })
          .catch(function(err){ alert('Network error: ' + err); showStep(2); });
      }

      function renderResults(data) {
        var el = document.getElementById('pn-cm-csv-results');
        var html = '<div class="pn-cm-csv-results-success"><strong>' + data.created + '</strong> '
          + <?php echo wp_json_encode(__('organizations created successfully.', 'pn-customers-manager')); ?> + '</div>';
        if (data.errors && data.errors.length) {
          html += '<div class="pn-cm-csv-results-errors"><strong>' + <?php echo wp_json_encode(__('Errors:', 'pn-customers-manager')); ?> + '</strong><ul>';
          data.errors.forEach(function(e){ html += '<li>' + esc(e) + '</li>'; });
          html += '</ul></div>';
        }
        el.innerHTML = html;
      }

    })();
    </script>
    <?php
  }
}
