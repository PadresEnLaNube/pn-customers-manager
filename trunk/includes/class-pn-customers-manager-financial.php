<?php
/**
 * Financial Management System - Gestor de Ingresos y Gastos
 *
 * Sistema simplificado para gestionar transacciones financieras (ingresos y gastos)
 * asociadas a custom post types con categorización, filtrado y exportación.
 *
 * @link       padresenlanube.com/
 * @since      1.1.160
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

class PN_CUSTOMERS_MANAGER_Financial {

  /**
   * Get categories for a specific CPT
   *
   * @param string $post_type The CPT slug
   * @return array Array of categories
   */
  public static function get_categories($post_type) {
    $categories = get_option('pn_cm_financial_categories_' . $post_type, []);
    return is_array($categories) ? $categories : [];
  }

  /**
   * Save categories for a specific CPT
   *
   * @param string $post_type The CPT slug
   * @param array $categories Array of categories
   * @return bool Success
   */
  public static function save_categories($post_type, $categories) {
    return update_option('pn_cm_financial_categories_' . $post_type, $categories);
  }

  /**
   * Get enabled CPTs
   *
   * @return array Array of enabled CPT slugs
   */
  public static function get_enabled_cpts() {
    $enabled = get_option('pn_cm_financial_enabled_cpts', []);
    return is_array($enabled) ? $enabled : [];
  }

  /**
   * Enable financial management for a CPT
   *
   * @param string $post_type The CPT slug
   * @return bool Success
   */
  public static function enable_for_cpt($post_type) {
    $enabled = self::get_enabled_cpts();
    if (!in_array($post_type, $enabled)) {
      $enabled[] = $post_type;
      update_option('pn_cm_financial_enabled_cpts', $enabled);
      update_option('pn_cm_financial_enable_' . $post_type, 'on');
      return true;
    }
    return false;
  }

  /**
   * Disable financial management for a CPT
   *
   * @param string $post_type The CPT slug
   * @return bool Success
   */
  public static function disable_for_cpt($post_type) {
    $enabled = self::get_enabled_cpts();
    $key = array_search($post_type, $enabled);
    if ($key !== false) {
      unset($enabled[$key]);
      update_option('pn_cm_financial_enabled_cpts', array_values($enabled));
      delete_option('pn_cm_financial_enable_' . $post_type);
      return true;
    }
    return false;
  }

  /**
   * Check if financial management is enabled for a CPT
   *
   * @param string $post_type The CPT slug
   * @return bool
   */
  public static function is_enabled_for_cpt($post_type) {
    return in_array($post_type, self::get_enabled_cpts());
  }

  /**
   * Get transactions for a post
   *
   * @param int $post_id The post ID
   * @param array $filters Optional filters (type, category, date_from, date_to)
   * @return array Array of transactions
   */
  public static function get_transactions($post_id, $filters = []) {
    $transactions = get_post_meta($post_id, '_pn_cm_financial_transactions', true);
    if (!is_array($transactions)) {
      $transactions = [];
    }

    // Apply filters
    if (!empty($filters)) {
      $transactions = array_filter($transactions, function($transaction) use ($filters) {
        // Filter by type
        if (isset($filters['type']) && !empty($filters['type']) && $transaction['type'] !== $filters['type']) {
          return false;
        }

        // Filter by category
        if (isset($filters['category']) && !empty($filters['category']) && $transaction['category'] !== $filters['category']) {
          return false;
        }

        // Filter by date range
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
          if (strtotime($transaction['date']) < strtotime($filters['date_from'])) {
            return false;
          }
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
          if (strtotime($transaction['date']) > strtotime($filters['date_to'])) {
            return false;
          }
        }

        return true;
      });
    }

    // Sort by date desc
    usort($transactions, function($a, $b) {
      return strtotime($b['date']) - strtotime($a['date']);
    });

    return $transactions;
  }

  /**
   * Add transaction
   *
   * @param int $post_id The post ID
   * @param array $transaction Transaction data
   * @return bool|string Transaction ID or false
   */
  public static function add_transaction($post_id, $transaction) {
    $transactions = get_post_meta($post_id, '_pn_cm_financial_transactions', true);
    if (!is_array($transactions)) {
      $transactions = [];
    }

    $transaction['id'] = uniqid('trans_');
    $transaction['created_at'] = current_time('mysql');
    $transactions[] = $transaction;

    if (update_post_meta($post_id, '_pn_cm_financial_transactions', $transactions)) {
      return $transaction['id'];
    }

    return false;
  }

  /**
   * Update transaction
   *
   * @param int $post_id The post ID
   * @param string $transaction_id The transaction ID
   * @param array $transaction_data Updated transaction data
   * @return bool Success
   */
  public static function update_transaction($post_id, $transaction_id, $transaction_data) {
    $transactions = get_post_meta($post_id, '_pn_cm_financial_transactions', true);
    if (!is_array($transactions)) {
      return false;
    }

    foreach ($transactions as $key => $transaction) {
      if ($transaction['id'] === $transaction_id) {
        $transactions[$key] = array_merge($transaction, $transaction_data);
        $transactions[$key]['updated_at'] = current_time('mysql');
        return update_post_meta($post_id, '_pn_cm_financial_transactions', $transactions);
      }
    }

    return false;
  }

  /**
   * Delete transaction
   *
   * @param int $post_id The post ID
   * @param string $transaction_id The transaction ID
   * @return bool Success
   */
  public static function delete_transaction($post_id, $transaction_id) {
    $transactions = get_post_meta($post_id, '_pn_cm_financial_transactions', true);
    if (!is_array($transactions)) {
      return false;
    }

    foreach ($transactions as $key => $transaction) {
      if ($transaction['id'] === $transaction_id) {
        unset($transactions[$key]);
        return update_post_meta($post_id, '_pn_cm_financial_transactions', array_values($transactions));
      }
    }

    return false;
  }

  /**
   * Calculate totals for transactions
   *
   * @param array $transactions Array of transactions
   * @return array Totals (income, expense, balance)
   */
  public static function calculate_totals($transactions) {
    $income = 0;
    $expense = 0;

    foreach ($transactions as $transaction) {
      $amount = floatval($transaction['amount']);
      if ($transaction['type'] === 'income') {
        $income += $amount;
      } else {
        $expense += $amount;
      }
    }

    return [
      'income' => $income,
      'expense' => $expense,
      'balance' => $income - $expense,
    ];
  }

  /**
   * Export transactions to CSV
   *
   * @param array $transactions Array of transactions
   * @return string CSV content
   */
  public static function export_to_csv($transactions) {
    $csv = [];
    $csv[] = ['Date', 'Type', 'Category', 'Amount', 'Description'];

    foreach ($transactions as $transaction) {
      $csv[] = [
        $transaction['date'],
        $transaction['type'] === 'income' ? __('Income', 'pn-customers-manager') : __('Expense', 'pn-customers-manager'),
        $transaction['category'],
        number_format($transaction['amount'], 2, '.', ''),
        $transaction['description'],
      ];
    }

    $output = fopen('php://temp', 'r+');
    foreach ($csv as $row) {
      fputcsv($output, $row);
    }
    rewind($output);
    $csv_content = stream_get_contents($output);
    fclose($output);

    return $csv_content;
  }

  /**
   * Auto-enable financial management for hostpn_accommodation
   *
   * @since 1.1.160
   */
  public static function maybe_auto_enable_accommodation() {
    $already_configured = get_option('pn_cm_financial_accommodation_auto_enabled', false);

    if ($already_configured) {
      return;
    }

    if (!post_type_exists('hostpn_accommodation')) {
      return;
    }

    self::enable_for_cpt('hostpn_accommodation');

    // Add default categories for accommodation
    $default_categories = [
      ['id' => 'cleaning', 'name' => __('Cleaning', 'pn-customers-manager'), 'type' => 'expense', 'color' => '#e74c3c'],
      ['id' => 'commission', 'name' => __('Platform Commission', 'pn-customers-manager'), 'type' => 'expense', 'color' => '#e67e22'],
      ['id' => 'maintenance', 'name' => __('Maintenance', 'pn-customers-manager'), 'type' => 'expense', 'color' => '#f39c12'],
      ['id' => 'utilities', 'name' => __('Utilities', 'pn-customers-manager'), 'type' => 'expense', 'color' => '#3498db'],
      ['id' => 'rental_income', 'name' => __('Rental Income', 'pn-customers-manager'), 'type' => 'income', 'color' => '#27ae60'],
      ['id' => 'cleaning_fee', 'name' => __('Cleaning Fee', 'pn-customers-manager'), 'type' => 'income', 'color' => '#16a085'],
    ];

    self::save_categories('hostpn_accommodation', $default_categories);

    update_option('pn_cm_financial_accommodation_auto_enabled', true);
  }

  /**
   * Enqueue financial scripts and styles in front-end
   *
   * @since 1.1.160
   */
  public static function enqueue_frontend_assets() {
    if (!is_singular()) {
      return;
    }

    $post = get_post();
    if (!$post || !self::is_enabled_for_cpt($post->post_type)) {
      return;
    }

    // Enqueue Material Icons
    if (!wp_style_is('wph-material-icons-outlined', 'enqueued')) {
      wp_enqueue_style('wph-material-icons-outlined', PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css', [], PN_CUSTOMERS_MANAGER_VERSION, 'all');
    }

    // Enqueue selector CSS
    if (!wp_style_is('pn-customers-manager-selector', 'enqueued')) {
      wp_enqueue_style('pn-customers-manager-selector', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-selector.css', [], PN_CUSTOMERS_MANAGER_VERSION, 'all');
    }

    // Enqueue plugin main CSS
    if (!wp_style_is('pn-customers-manager', 'enqueued')) {
      wp_enqueue_style('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], PN_CUSTOMERS_MANAGER_VERSION, 'all');
    }

    // Enqueue financial CSS
    wp_enqueue_style(
      'pn-customers-manager-financial',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-financial.css',
      ['pn-customers-manager'],
      PN_CUSTOMERS_MANAGER_VERSION,
      'all'
    );

    // Enqueue plugin main JS
    if (!wp_script_is('pn-customers-manager', 'enqueued')) {
      wp_enqueue_script('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
    }

    // Enqueue selector JS
    if (!wp_script_is('pn-customers-manager-selector', 'enqueued')) {
      wp_enqueue_script('pn-customers-manager-selector', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-selector.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
    }

    // Enqueue AJAX helper
    if (!wp_script_is('pn-customers-manager-ajax', 'enqueued')) {
      wp_enqueue_script('pn-customers-manager-ajax', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-ajax.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
    }

    // Enqueue financial JS
    wp_enqueue_script(
      'pn-customers-manager-financial',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-financial.js',
      ['jquery', 'pn-customers-manager', 'pn-customers-manager-selector', 'pn-customers-manager-ajax'],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );

    // Localize scripts
    if (!wp_script_is('pn-customers-manager-ajax', 'localized')) {
      wp_localize_script('pn-customers-manager-ajax', 'pn_customers_manager_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'pn_customers_manager_ajax_nonce' => wp_create_nonce('pn-customers-manager-nonce'),
      ]);
    }

    if (!wp_script_is('pn-customers-manager', 'localized')) {
      wp_localize_script('pn-customers-manager', 'pn_customers_manager_i18n', [
        'copied' => esc_html__('Copied!', 'pn-customers-manager'),
        'an_error_has_occurred' => esc_html__('An error has occurred. Please try again.', 'pn-customers-manager'),
      ]);

      wp_localize_script('pn-customers-manager', 'pn_customers_manager_action', [
        'action' => '',
        'btn_id' => '',
        'popup' => '',
        'tab' => '',
      ]);
    }
  }

  /**
   * Add financial management to post content in front-end
   *
   * @param string $content The post content
   * @return string Modified content
   */
  public static function add_to_content($content) {
    if (!is_singular()) {
      return $content;
    }

    $post_id = get_the_ID();
    $post = get_post($post_id);

    if (!$post || !self::is_enabled_for_cpt($post->post_type)) {
      return $content;
    }

    $financial_html = self::render_frontend_manager($post_id);

    if (!empty($financial_html)) {
      $content = $financial_html . $content;
    }

    return $content;
  }

  /**
   * Render financial manager in front-end
   *
   * @param int $post_id The post ID
   * @return string HTML output
   */
  public static function render_frontend_manager($post_id) {
    $post = get_post($post_id);
    $categories = self::get_categories($post->post_type);
    $transactions = self::get_transactions($post_id);
    $totals = self::calculate_totals($transactions);

    ob_start();
    ?>
    <div class="pn-cm-financial-manager pn-customers-manager-toggle-wrapper pn-customers-manager-section-wrapper pn-customers-manager-mb-20">
      <a href="#" class="pn-customers-manager-toggle pn-customers-manager-width-100-percent pn-customers-manager-text-decoration-none">
        <h3 class="pn-customers-manager-display-inline-flex pn-customers-manager-align-items-center">
          <i class="material-icons-outlined pn-customers-manager-mr-10">account_balance</i>
          <?php esc_html_e('Financial Management', 'pn-customers-manager'); ?>
        </h3>
        <i class="material-icons-outlined">expand_more</i>
      </a>

      <div class="pn-customers-manager-toggle-content pn-customers-manager-display-none-soft pn-customers-manager-p-20">

        <!-- Summary Cards -->
        <div class="pn-cm-financial-summary pn-customers-manager-mb-30">
          <div class="pn-cm-summary-card pn-cm-summary-income">
            <div class="pn-cm-summary-icon">
              <i class="material-icons-outlined">trending_up</i>
            </div>
            <div class="pn-cm-summary-details">
              <div class="pn-cm-summary-label"><?php esc_html_e('Total Income', 'pn-customers-manager'); ?></div>
              <div class="pn-cm-summary-amount">€<?php echo number_format($totals['income'], 2); ?></div>
            </div>
          </div>

          <div class="pn-cm-summary-card pn-cm-summary-expense">
            <div class="pn-cm-summary-icon">
              <i class="material-icons-outlined">trending_down</i>
            </div>
            <div class="pn-cm-summary-details">
              <div class="pn-cm-summary-label"><?php esc_html_e('Total Expenses', 'pn-customers-manager'); ?></div>
              <div class="pn-cm-summary-amount">€<?php echo number_format($totals['expense'], 2); ?></div>
            </div>
          </div>

          <div class="pn-cm-summary-card pn-cm-summary-balance <?php echo $totals['balance'] >= 0 ? 'pn-cm-summary-positive' : 'pn-cm-summary-negative'; ?>">
            <div class="pn-cm-summary-icon">
              <i class="material-icons-outlined">account_balance_wallet</i>
            </div>
            <div class="pn-cm-summary-details">
              <div class="pn-cm-summary-label"><?php esc_html_e('Balance', 'pn-customers-manager'); ?></div>
              <div class="pn-cm-summary-amount">€<?php echo number_format($totals['balance'], 2); ?></div>
            </div>
          </div>
        </div>

        <!-- Add Transaction Form -->
        <div class="pn-cm-add-transaction pn-customers-manager-mb-30">
          <h4 class="pn-customers-manager-mb-15">
            <i class="material-icons-outlined pn-customers-manager-mr-5">add_circle</i>
            <?php esc_html_e('Add Transaction', 'pn-customers-manager'); ?>
          </h4>

          <form id="pn-cm-transaction-form" class="pn-cm-transaction-form" data-post-id="<?php echo esc_attr($post_id); ?>">
            <?php wp_nonce_field('pn_cm_financial_transaction', 'pn_cm_financial_nonce'); ?>

            <div class="pn-cm-form-grid">
              <div class="pn-cm-form-field">
                <label><?php esc_html_e('Type', 'pn-customers-manager'); ?> *</label>
                <select name="type" class="pn-customers-manager-input pn-customers-manager-selector" required>
                  <option value=""><?php esc_html_e('Select type', 'pn-customers-manager'); ?></option>
                  <option value="income"><?php esc_html_e('Income', 'pn-customers-manager'); ?></option>
                  <option value="expense"><?php esc_html_e('Expense', 'pn-customers-manager'); ?></option>
                </select>
              </div>

              <div class="pn-cm-form-field">
                <label><?php esc_html_e('Category', 'pn-customers-manager'); ?> *</label>
                <select name="category" class="pn-customers-manager-input pn-customers-manager-selector" required>
                  <option value=""><?php esc_html_e('Select category', 'pn-customers-manager'); ?></option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc_attr($category['id']); ?>" data-type="<?php echo esc_attr($category['type']); ?>">
                      <?php echo esc_html($category['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="pn-cm-form-field">
                <label><?php esc_html_e('Amount (€)', 'pn-customers-manager'); ?> *</label>
                <input type="number" name="amount" class="pn-customers-manager-input" step="0.01" min="0" required />
              </div>

              <div class="pn-cm-form-field">
                <label><?php esc_html_e('Date', 'pn-customers-manager'); ?> *</label>
                <input type="date" name="date" class="pn-customers-manager-input" value="<?php echo date('Y-m-d'); ?>" required />
              </div>

              <div class="pn-cm-form-field pn-cm-form-field-full">
                <label><?php esc_html_e('Description', 'pn-customers-manager'); ?></label>
                <textarea name="description" class="pn-customers-manager-input" rows="2"></textarea>
              </div>
            </div>

            <button type="submit" class="pn-customers-manager-btn pn-customers-manager-btn-action pn-customers-manager-mt-15">
              <i class="material-icons-outlined">save</i>
              <?php esc_html_e('Add Transaction', 'pn-customers-manager'); ?>
            </button>
          </form>
        </div>

        <!-- Filters -->
        <div class="pn-cm-filters pn-customers-manager-mb-20">
          <div class="pn-cm-filters-grid">
            <input type="text" id="pn-cm-filter-search" class="pn-customers-manager-input" placeholder="<?php esc_attr_e('Search...', 'pn-customers-manager'); ?>" />

            <select id="pn-cm-filter-type" class="pn-customers-manager-input pn-customers-manager-selector">
              <option value=""><?php esc_html_e('All Types', 'pn-customers-manager'); ?></option>
              <option value="income"><?php esc_html_e('Income', 'pn-customers-manager'); ?></option>
              <option value="expense"><?php esc_html_e('Expense', 'pn-customers-manager'); ?></option>
            </select>

            <select id="pn-cm-filter-category" class="pn-customers-manager-input pn-customers-manager-selector">
              <option value=""><?php esc_html_e('All Categories', 'pn-customers-manager'); ?></option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo esc_attr($category['id']); ?>">
                  <?php echo esc_html($category['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>

            <button id="pn-cm-export-csv" class="pn-customers-manager-btn pn-customers-manager-btn-secondary">
              <i class="material-icons-outlined">download</i>
              <?php esc_html_e('Export CSV', 'pn-customers-manager'); ?>
            </button>
          </div>
        </div>

        <!-- Transactions List -->
        <div class="pn-cm-transactions-list">
          <table class="pn-cm-transactions-table">
            <thead>
              <tr>
                <th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
                <th><?php esc_html_e('Type', 'pn-customers-manager'); ?></th>
                <th><?php esc_html_e('Category', 'pn-customers-manager'); ?></th>
                <th><?php esc_html_e('Description', 'pn-customers-manager'); ?></th>
                <th><?php esc_html_e('Amount', 'pn-customers-manager'); ?></th>
                <th><?php esc_html_e('Actions', 'pn-customers-manager'); ?></th>
              </tr>
            </thead>
            <tbody id="pn-cm-transactions-tbody">
              <?php if (empty($transactions)): ?>
                <tr>
                  <td colspan="6" class="pn-cm-no-transactions">
                    <?php esc_html_e('No transactions yet.', 'pn-customers-manager'); ?>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($transactions as $transaction):
                  $category_data = null;
                  foreach ($categories as $cat) {
                    if ($cat['id'] === $transaction['category']) {
                      $category_data = $cat;
                      break;
                    }
                  }
                  ?>
                  <tr data-transaction-id="<?php echo esc_attr($transaction['id']); ?>" data-type="<?php echo esc_attr($transaction['type']); ?>" data-category="<?php echo esc_attr($transaction['category']); ?>">
                    <td><?php echo esc_html(date('d/m/Y', strtotime($transaction['date']))); ?></td>
                    <td>
                      <span class="pn-cm-type-badge pn-cm-type-<?php echo esc_attr($transaction['type']); ?>">
                        <?php echo $transaction['type'] === 'income' ? esc_html__('Income', 'pn-customers-manager') : esc_html__('Expense', 'pn-customers-manager'); ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($category_data): ?>
                        <span class="pn-cm-category-badge" style="background-color: <?php echo esc_attr($category_data['color']); ?>">
                          <?php echo esc_html($category_data['name']); ?>
                        </span>
                      <?php else: ?>
                        <?php echo esc_html($transaction['category']); ?>
                      <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($transaction['description']); ?></td>
                    <td class="pn-cm-amount pn-cm-amount-<?php echo esc_attr($transaction['type']); ?>">
                      €<?php echo number_format($transaction['amount'], 2); ?>
                    </td>
                    <td class="pn-cm-actions">
                      <button class="pn-cm-edit-transaction" data-id="<?php echo esc_attr($transaction['id']); ?>" title="<?php esc_attr_e('Edit', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined">edit</i>
                      </button>
                      <button class="pn-cm-delete-transaction" data-id="<?php echo esc_attr($transaction['id']); ?>" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
                        <i class="material-icons-outlined">delete</i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
      // Toggle functionality
      $('.pn-cm-financial-manager .pn-customers-manager-toggle').on('click', function(e) {
        e.preventDefault();
        var $content = $(this).next('.pn-customers-manager-toggle-content');
        var $icon = $(this).find('i').last();
        var isActive = $content.hasClass('pn-customers-manager-toggle-active');

        if (isActive) {
          $content.removeClass('pn-customers-manager-toggle-active');
          $content.slideUp(300);
          $icon.text('expand_more');
        } else {
          $content.addClass('pn-customers-manager-toggle-active');
          $content.slideDown(300);
          $icon.text('expand_less');
        }
      });

      // Filter categories by type
      $('select[name="type"]').on('change', function() {
        var type = $(this).val();
        var $categorySelect = $('select[name="category"]');

        $categorySelect.find('option').each(function() {
          if ($(this).val() === '') return;

          var optionType = $(this).data('type');
          if (!type || optionType === type) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });

        $categorySelect.val('');
      });

      // Add transaction
      $('#pn-cm-transaction-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalHtml = $btn.html();

        $.ajax({
          url: pn_customers_manager_ajax.ajax_url,
          type: 'POST',
          data: $form.serialize() + '&action=pn_customers_manager_ajax&pn_customers_manager_ajax_type=add_financial_transaction&pn_customers_manager_ajax_nonce=' + pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
          beforeSend: function() {
            $btn.prop('disabled', true).html('<i class="material-icons-outlined">hourglass_empty</i> <?php esc_html_e('Saving...', 'pn-customers-manager'); ?>');
          },
          success: function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;

            if (data.error_key) {
              pn_customers_manager_get_main_message(data.error_content || '<?php esc_html_e('Error adding transaction', 'pn-customers-manager'); ?>', 'error');
            } else {
              pn_customers_manager_get_main_message('<?php esc_html_e('Transaction added successfully', 'pn-customers-manager'); ?>', 'success');
              setTimeout(function() {
                location.reload();
              }, 1000);
            }
          },
          error: function() {
            pn_customers_manager_get_main_message('<?php esc_html_e('Error adding transaction', 'pn-customers-manager'); ?>', 'error');
            $btn.prop('disabled', false).html(originalHtml);
          }
        });
      });

      // Delete transaction
      $(document).on('click', '.pn-cm-delete-transaction', function() {
        if (!confirm('<?php esc_html_e('Are you sure you want to delete this transaction?', 'pn-customers-manager'); ?>')) {
          return;
        }

        var transactionId = $(this).data('id');
        var postId = $('#pn-cm-transaction-form').data('post-id');

        $.ajax({
          url: pn_customers_manager_ajax.ajax_url,
          type: 'POST',
          data: {
            action: 'pn_customers_manager_ajax',
            pn_customers_manager_ajax_type: 'delete_financial_transaction',
            pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
            post_id: postId,
            transaction_id: transactionId
          },
          success: function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;

            if (data.error_key) {
              pn_customers_manager_get_main_message(data.error_content || '<?php esc_html_e('Error deleting transaction', 'pn-customers-manager'); ?>', 'error');
            } else {
              pn_customers_manager_get_main_message('<?php esc_html_e('Transaction deleted successfully', 'pn-customers-manager'); ?>', 'success');
              setTimeout(function() {
                location.reload();
              }, 1000);
            }
          },
          error: function() {
            pn_customers_manager_get_main_message('<?php esc_html_e('Error deleting transaction', 'pn-customers-manager'); ?>', 'error');
          }
        });
      });

      // Export CSV
      $('#pn-cm-export-csv').on('click', function() {
        var postId = $('#pn-cm-transaction-form').data('post-id');
        window.location.href = pn_customers_manager_ajax.ajax_url + '?action=pn_customers_manager_ajax&pn_customers_manager_ajax_type=export_financial_csv&post_id=' + postId + '&pn_customers_manager_ajax_nonce=' + pn_customers_manager_ajax.pn_customers_manager_ajax_nonce;
      });

      // Filter transactions
      function filterTransactions() {
        var search = $('#pn-cm-filter-search').val().toLowerCase();
        var type = $('#pn-cm-filter-type').val();
        var category = $('#pn-cm-filter-category').val();

        $('#pn-cm-transactions-tbody tr').each(function() {
          var $row = $(this);
          var text = $row.text().toLowerCase();
          var rowType = $row.data('type');
          var rowCategory = $row.data('category');

          var showRow = true;

          if (search && text.indexOf(search) === -1) {
            showRow = false;
          }

          if (type && rowType !== type) {
            showRow = false;
          }

          if (category && rowCategory !== category) {
            showRow = false;
          }

          $row.toggle(showRow);
        });
      }

      $('#pn-cm-filter-search, #pn-cm-filter-type, #pn-cm-filter-category').on('change keyup', filterTransactions);
    });
    </script>
    <?php
    return ob_get_clean();
  }

  /**
   * Enqueue assets for financial management
   */
  public static function enqueue_assets() {
    // Only enqueue on admin pages
    if (!is_admin()) {
      return;
    }

    // Enqueue styles
    wp_enqueue_style(
      'pn-customers-manager-financial',
      PN_CUSTOMERS_MANAGER_URL . 'assets/css/admin/pn-customers-manager-financial.css',
      [],
      PN_CUSTOMERS_MANAGER_VERSION
    );

    // Enqueue scripts
    wp_enqueue_script(
      'pn-customers-manager-financial',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-financial.js',
      ['jquery'],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );
  }

  /**
   * Get settings section for admin
   *
   * @return array Settings fields
   */
  public static function get_settings_section() {
    $options = [];

    $options['pn_cm_financial_parent_section_start'] = [
      'id' => 'pn_cm_financial_parent_section_start',
      'section' => 'start',
      'label' => __('Financial Management', 'pn-customers-manager'),
      'description' => __('Configure income and expense categories for your custom post types. Manage financial transactions directly from the front-end.', 'pn-customers-manager'),
    ];

    // Get available CPTs
    $available_cpts = self::get_available_post_types();
    $enabled_cpts = self::get_enabled_cpts();

    if (empty($available_cpts)) {
      $options['pn_cm_financial_no_cpts'] = [
        'id' => 'pn_cm_financial_no_cpts',
        'input' => 'html',
        'html_content' => '<div class="pn-cm-info-box">' .
          '<p>' . esc_html__('No custom post types available.', 'pn-customers-manager') . '</p>' .
          '</div>',
      ];
    } else {
      // Create fields for each CPT
      foreach ($available_cpts as $cpt_slug => $cpt_label) {
        $is_enabled = in_array($cpt_slug, $enabled_cpts);
        $checkbox_option = get_option('pn_cm_financial_enable_' . $cpt_slug, '');
        $categories = self::get_categories($cpt_slug);

        // Enable checkbox
        $options['pn_cm_financial_enable_' . $cpt_slug] = [
          'id' => 'pn_cm_financial_enable_' . $cpt_slug,
          'class' => 'pn-cm-financial-enable-cpt',
          'input' => 'input',
          'type' => 'checkbox',
          'value' => $checkbox_option,
          'parent' => 'this',
          'label' => sprintf(__('Enable for %s', 'pn-customers-manager'), $cpt_label) . ' <code>' . $cpt_slug . '</code>',
          'description' => sprintf(
            __('Enable financial management for %s to track income and expenses.', 'pn-customers-manager'),
            $cpt_label
          ),
        ];

        // Categories management (only visible when enabled)
        $categories_html = self::render_categories_manager($cpt_slug, $cpt_label, $categories);

        $options['pn_cm_financial_categories_' . $cpt_slug] = [
          'id' => 'pn_cm_financial_categories_' . $cpt_slug,
          'input' => 'html',
          'parent' => 'pn_cm_financial_enable_' . $cpt_slug,
          'parent_option' => 'on',
          'html_content' => $categories_html,
        ];
      }
    }

    $options['pn_cm_financial_parent_section_end'] = [
      'id' => 'pn_cm_financial_parent_section_end',
      'section' => 'end',
    ];

    return $options;
  }

  /**
   * Render categories manager for a CPT
   *
   * @param string $cpt_slug CPT slug
   * @param string $cpt_label CPT label
   * @param array $categories Existing categories
   * @return string HTML output
   */
  private static function render_categories_manager($cpt_slug, $cpt_label, $categories) {
    ob_start();
    ?>
    <div class="pn-cm-categories-manager" data-cpt="<?php echo esc_attr($cpt_slug); ?>">
      <h4 class="pn-customers-manager-mb-15">
        <i class="material-icons-outlined pn-customers-manager-mr-5">category</i>
        <?php printf(esc_html__('Categories for %s', 'pn-customers-manager'), esc_html($cpt_label)); ?>
      </h4>

      <div class="pn-cm-categories-list pn-customers-manager-mb-20">
        <?php if (empty($categories)): ?>
          <p class="pn-cm-no-categories"><?php esc_html_e('No categories configured yet.', 'pn-customers-manager'); ?></p>
        <?php else: ?>
          <?php foreach ($categories as $category): ?>
            <div class="pn-cm-category-item" data-category-id="<?php echo esc_attr($category['id']); ?>">
              <div class="pn-cm-category-color" style="background-color: <?php echo esc_attr($category['color']); ?>"></div>
              <div class="pn-cm-category-details">
                <div class="pn-cm-category-name"><?php echo esc_html($category['name']); ?></div>
                <div class="pn-cm-category-type">
                  <span class="pn-cm-type-badge pn-cm-type-<?php echo esc_attr($category['type']); ?>">
                    <?php echo $category['type'] === 'income' ? esc_html__('Income', 'pn-customers-manager') : esc_html__('Expense', 'pn-customers-manager'); ?>
                  </span>
                </div>
              </div>
              <div class="pn-cm-category-actions">
                <button type="button" class="pn-cm-edit-category" data-id="<?php echo esc_attr($category['id']); ?>" title="<?php esc_attr_e('Edit', 'pn-customers-manager'); ?>">
                  <i class="material-icons-outlined">edit</i>
                </button>
                <button type="button" class="pn-cm-delete-category" data-id="<?php echo esc_attr($category['id']); ?>" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
                  <i class="material-icons-outlined">delete</i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-action pn-cm-add-category" data-cpt="<?php echo esc_attr($cpt_slug); ?>">
        <i class="material-icons-outlined">add</i>
        <?php esc_html_e('Add Category', 'pn-customers-manager'); ?>
      </button>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Get available post types
   *
   * @return array Array of post types
   */
  public static function get_available_post_types() {
    $post_types = get_post_types(['public' => true], 'objects');
    $available = [];

    foreach ($post_types as $post_type) {
      if (!in_array($post_type->name, ['attachment', 'revision', 'nav_menu_item', 'page'])) {
        $available[$post_type->name] = $post_type->label;
      }
    }

    return $available;
  }
}
