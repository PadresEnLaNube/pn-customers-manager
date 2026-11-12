<?php
/**
 * WooCommerce QR Tickets Integration
 *
 * Allows products to generate unique QR codes as tickets for physical delivery validation
 *
 * @link       padresenlanube.com/
 * @since      1.1.75
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce_QR {

	private static $instance = null;

	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Product settings
		add_filter('woocommerce_product_data_tabs', [$this, 'add_product_tab']);
		add_action('woocommerce_product_data_panels', [$this, 'product_data_panel']);
		add_action('woocommerce_process_product_meta', [$this, 'save_product_meta']);

		// Order handling
		add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_qr_to_order_item'], 10, 4);
		add_action('woocommerce_order_status_completed', [$this, 'generate_qr_codes']);

		// Frontend display - User QR codes (before add to cart button)
		add_action('woocommerce_before_add_to_cart_button', [$this, 'display_user_qr'], 15);

		// Admin panel (at the end of product page)
		add_action('woocommerce_after_single_product_summary', [$this, 'display_admin_panel'], 100);

		// Email integration
		add_action('woocommerce_email_after_order_table', [$this, 'add_qr_to_email'], 10, 4);

		// My Account order details integration
		add_action('woocommerce_order_details_after_order_table', [$this, 'add_qr_to_order_details'], 10, 1);

		// Admin order edit page metabox
		add_action('add_meta_boxes', [$this, 'add_order_qr_metabox']);

		// AJAX handlers
		add_action('wp_ajax_pn_cm_woo_qr_process', [$this, 'ajax_process_qr']);
		add_action('wp_ajax_pn_cm_woo_qr_mark_pending', [$this, 'ajax_mark_pending']);
		add_action('wp_ajax_pn_cm_woo_qr_delete', [$this, 'ajax_delete_qr']);
		add_action('wp_ajax_pn_cm_woo_qr_get_orders', [$this, 'ajax_get_orders']);
		add_action('wp_ajax_pn_cm_woo_qr_view', [$this, 'ajax_qr_view']);
		add_action('wp_ajax_pn_cm_woo_qr_get_variations', [$this, 'ajax_get_variations']);
		add_action('wp_ajax_pn_cm_woo_qr_bulk_process', [$this, 'ajax_bulk_process']);
		add_action('wp_ajax_pn_cm_woo_qr_bulk_pending', [$this, 'ajax_bulk_pending']);
		add_action('wp_ajax_pn_cm_woo_qr_bulk_delete', [$this, 'ajax_bulk_delete']);
		add_action('wp_ajax_pn_cm_woo_qr_get_pending_orders', [$this, 'ajax_get_pending_orders']);
		add_action('wp_ajax_pn_cm_woo_qr_complete_pending_orders', [$this, 'ajax_complete_pending_orders']);

		// Enqueue scripts
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

		// Shortcode
		add_shortcode('pn_qr_manager', [$this, 'qr_manager_shortcode']);
	}

	/**
	 * Add QR tab to product data tabs
	 */
	public function add_product_tab($tabs) {
		$tabs['pn_cm_woo_qr'] = [
			'label' => __('QR Tickets', 'pn-customers-manager'),
			'target' => 'pn_cm_woo_qr_data',
			'class' => ['show_if_simple', 'show_if_variable'],
			'priority' => 80,
		];

		return $tabs;
	}

	/**
	 * Display QR settings panel in product edit page
	 */
	public function product_data_panel() {
		global $post;
		?>
		<div id="pn_cm_woo_qr_data" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php
				woocommerce_wp_checkbox([
					'id' => '_pn_cm_woo_qr_enabled',
					'label' => __('Enable QR Tickets', 'pn-customers-manager'),
					'description' => __('Generate unique QR codes for each purchase of this product', 'pn-customers-manager'),
				]);

				woocommerce_wp_text_input([
					'id' => '_pn_cm_woo_qr_prefix',
					'label' => __('QR Prefix', 'pn-customers-manager'),
					'description' => __('Optional prefix for QR codes (e.g., EVENT2026)', 'pn-customers-manager'),
					'placeholder' => 'PREFIX',
				]);

				woocommerce_wp_textarea_input([
					'id' => '_pn_cm_woo_qr_instructions',
					'label' => __('Instructions for Customer', 'pn-customers-manager'),
					'description' => __('Text shown to customers with their QR code', 'pn-customers-manager'),
					'placeholder' => __('Show this QR code at the venue to collect your item', 'pn-customers-manager'),
				]);
				?>
			</div>

			<?php
			// Show statistics if there are orders
			$orders_data = $this->get_product_orders($post->ID);
			if (!empty($orders_data)) {
				?>
				<div class="options_group">
					<h4 style="padding: 0 12px;"><?php esc_html_e('QR Statistics', 'pn-customers-manager'); ?></h4>
					<p class="form-field" style="padding: 0 12px;">
						<strong><?php esc_html_e('Total QR Codes Generated:', 'pn-customers-manager'); ?></strong>
						<?php echo count($orders_data); ?><br>
						<strong><?php esc_html_e('Processed:', 'pn-customers-manager'); ?></strong>
						<?php echo count(array_filter($orders_data, function($o) { return !empty($o['processed']); })); ?><br>
						<strong><?php esc_html_e('Pending:', 'pn-customers-manager'); ?></strong>
						<?php echo count(array_filter($orders_data, function($o) { return empty($o['processed']); })); ?>
					</p>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Save product QR settings
	 */
	public function save_product_meta($product_id) {
		$enabled = isset($_POST['_pn_cm_woo_qr_enabled']) ? 'yes' : 'no';
		update_post_meta($product_id, '_pn_cm_woo_qr_enabled', $enabled);

		if (isset($_POST['_pn_cm_woo_qr_prefix'])) {
			update_post_meta($product_id, '_pn_cm_woo_qr_prefix', sanitize_text_field($_POST['_pn_cm_woo_qr_prefix']));
		}

		if (isset($_POST['_pn_cm_woo_qr_instructions'])) {
			update_post_meta($product_id, '_pn_cm_woo_qr_instructions', wp_kses_post($_POST['_pn_cm_woo_qr_instructions']));
		}
	}

	/**
	 * Add QR metadata to order line item when order is created
	 */
	public function add_qr_to_order_item($item, $cart_item_key, $values, $order) {
		$product = $values['data'];
		$product_id = $product->get_id();

		if (get_post_meta($product_id, '_pn_cm_woo_qr_enabled', true) === 'yes') {
			$item->add_meta_data('_pn_cm_woo_qr_enabled', 'yes', true);
			$item->add_meta_data('_pn_cm_woo_qr_processed', 'no', true);
		}
	}

	/**
	 * Generate QR codes when order is completed
	 */
	public function generate_qr_codes($order_id) {
		$order = wc_get_order($order_id);
		if (!$order) {
			return;
		}

		foreach ($order->get_items() as $item_id => $item) {
			$product_id = $item->get_product_id();

			$qr_enabled_item = $item->get_meta('_pn_cm_woo_qr_enabled');
			$qr_enabled_product = get_post_meta($product_id, '_pn_cm_woo_qr_enabled', true);

			// Check both item meta and product meta
			if ($qr_enabled_item === 'yes' || $qr_enabled_product === 'yes') {
				// Check if QR already generated
				$existing_qr = $item->get_meta('_pn_cm_woo_qr_code');
				if (empty($existing_qr)) {
					$qr_code = $this->generate_unique_qr_code($order_id, $item_id, $product_id);
					$item->update_meta_data('_pn_cm_woo_qr_code', $qr_code);
					$item->update_meta_data('_pn_cm_woo_qr_processed', 'no');
					$item->save();
				}
			}
		}
	}

	/**
	 * Generate unique QR code
	 */
	private function generate_unique_qr_code($order_id, $item_id, $product_id = null) {
		// Get product ID if not provided
		if (!$product_id) {
			$item = new WC_Order_Item_Product($item_id);
			$product_id = $item->get_product_id();
		}

		$prefix = get_post_meta($product_id, '_pn_cm_woo_qr_prefix', true);

		$unique_string = $prefix . '-' . $order_id . '-' . $item_id . '-' . time() . '-' . wp_generate_password(8, false);
		return strtoupper(substr(md5($unique_string), 0, 16));
	}

	/**
	 * Display QR code for customers who purchased
	 */
	public function display_user_qr() {
		if (!is_user_logged_in()) return;

		global $product;
		if (!$product) return;

		$product_id = $product->get_id();
		if (get_post_meta($product_id, '_pn_cm_woo_qr_enabled', true) !== 'yes') return;

		// Check if current user purchased this product
		$user_qrs = $this->get_user_qr_codes(get_current_user_id(), $product_id);
		if (empty($user_qrs)) return;

		$instructions = get_post_meta($product_id, '_pn_cm_woo_qr_instructions', true);
		if (empty($instructions)) {
			$instructions = __('Show this QR code to collect your item', 'pn-customers-manager');
		}

		?>
		<div class="pn-cm-qr-user-section">
			<h2><?php esc_html_e('Your QR Tickets', 'pn-customers-manager'); ?></h2>
			<p><?php echo wp_kses_post($instructions); ?></p>

			<div class="pn-cm-qr-user-codes">
				<?php foreach ($user_qrs as $qr_data): ?>
					<div class="pn-cm-qr-user-code <?php echo $qr_data['processed'] ? 'processed' : 'pending'; ?>">
						<div class="pn-cm-qr-code-image">
							<?php echo wp_kses_post($this->generate_qr_svg($qr_data['code'])); ?>
						</div>
						<div class="pn-cm-qr-code-info">
							<p class="pn-cm-qr-code-string"><?php echo esc_html($qr_data['code']); ?></p>
							<p class="pn-cm-qr-code-order"><?php /* translators: %s is the order ID */ printf(esc_html__('Order #%s', 'pn-customers-manager'), esc_html($qr_data['order_id'])); ?></p>
							<p class="pn-cm-qr-code-quantity"><?php /* translators: %d is the item quantity */ printf(esc_html__('Quantity: %d', 'pn-customers-manager'), esc_html($qr_data['quantity'])); ?></p>
							<?php if ($qr_data['processed']): ?>
								<p class="pn-cm-qr-status processed">
									<i class="material-icons-outlined">check_circle</i>
									<?php esc_html_e('Processed', 'pn-customers-manager'); ?>
								</p>
							<?php else: ?>
								<p class="pn-cm-qr-status pending">
									<i class="material-icons-outlined">schedule</i>
									<?php esc_html_e('Pending', 'pn-customers-manager'); ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display admin panel for managing QR codes
	 */
	public function display_admin_panel() {
		if (!current_user_can('manage_woocommerce')) return;

		global $product;
		if (!$product) return;

		$product_id = $product->get_id();
		if (get_post_meta($product_id, '_pn_cm_woo_qr_enabled', true) !== 'yes') return;

		// Get all products with QR enabled
		$qr_products = get_posts([
			'post_type' => 'product',
			'posts_per_page' => -1,
			'meta_key' => '_pn_cm_woo_qr_enabled',
			'meta_value' => 'yes',
			'orderby' => 'title',
			'order' => 'ASC',
		]);

		?>
		<div class="pn-cm-qr-admin-panel">
			<h2><?php esc_html_e('QR Tickets Management', 'pn-customers-manager'); ?></h2>

			<div class="pn-cm-qr-admin-controls">
				<div class="pn-cm-qr-actions-btns">
					<button type="button" id="pn-cm-qr-scan" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent">
						<?php esc_html_e('Scan QR Code', 'pn-customers-manager'); ?>
					</button>
				</div>

				<div class="pn-cm-qr-filters">
					<select id="pn-cm-qr-filter-product" class="pn-cm-qr-filter">
						<option value=""><?php esc_html_e('All Products', 'pn-customers-manager'); ?></option>
						<?php foreach ($qr_products as $qr_product): ?>
							<option value="<?php echo esc_attr($qr_product->ID); ?>" <?php selected($qr_product->ID, $product_id); ?>>
								<?php echo esc_html($qr_product->post_title); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select id="pn-cm-qr-filter-variation" class="pn-cm-qr-filter" style="display: none;">
						<option value=""><?php esc_html_e('All Variations', 'pn-customers-manager'); ?></option>
					</select>

					<select id="pn-cm-qr-filter-status" class="pn-cm-qr-filter">
						<option value=""><?php esc_html_e('All Status', 'pn-customers-manager'); ?></option>
						<option value="pending"><?php esc_html_e('Pending', 'pn-customers-manager'); ?></option>
						<option value="processed"><?php esc_html_e('Processed', 'pn-customers-manager'); ?></option>
					</select>

					<input type="text" id="pn-cm-qr-filter-search" class="pn-cm-qr-filter pn-cm-qr-search-input" placeholder="<?php esc_html_e('Search by customer, order, QR code...', 'pn-customers-manager'); ?>">
				</div>
			</div>

			<div id="pn-cm-qr-orders-wrapper" style="position: relative;">
				<div id="pn-cm-qr-orders-list" data-product-id="<?php echo esc_attr($product_id); ?>" data-variation-id="">
					<?php echo wp_kses_post($this->get_orders_html($product_id)); ?>
				</div>
				<div class="pn-cm-qr-loader pn-customers-manager-display-none">
					<div class="pn-customers-manager-loader-circle-wrapper">
						<div class="pn-customers-manager-text-align-center">
							<div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- QR Scanner Popup (outside admin panel for proper DOM access) -->
		<div id="pn-cm-qr-scanner-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
			<div class="pn-customers-manager-popup-overlay"></div>
			<div class="pn-customers-manager-popup-content">
				<h3 class="pn-customers-manager-text-align-center"><?php esc_html_e('Scan QR Code', 'pn-customers-manager'); ?></h3>
				<div id="pn-cm-qr-scanner-container" style="text-align: center; margin: 20px 0;">
					<!-- Scanner will be initialized here -->
				</div>
				<div style="text-align: center;">
					<button id="pn-cm-qr-switch-camera" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent" style="margin: 10px auto; display: none;">
						<i class="material-icons-outlined">switch_camera</i>
						<?php esc_html_e('Switch Camera', 'pn-customers-manager'); ?>
					</button>
				</div>
				<div id="pn-cm-qr-scanner-result" style="margin-top: 20px; text-align: center;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get orders HTML for admin panel
	 */
	private function get_orders_html($product_id = null, $status = '', $variation_id = null, $search = '') {
		// Get ALL orders data (without status/search filters) for accurate statistics
		$all_orders_data = $this->get_product_orders($product_id, '', $variation_id, '');

		// Calculate stats from ALL data (not filtered by status/search)
		$stats = [
			'total' => count($all_orders_data),
			'processed' => 0,
			'pending' => 0,
		];

		foreach ($all_orders_data as $data) {
			if ($data['processed']) {
				$stats['processed']++;
			} else {
				$stats['pending']++;
			}
		}

		// Get filtered data for display in the table
		$orders_data = $this->get_product_orders($product_id, $status, $variation_id, $search);

		if (empty($orders_data)) {
			// Still show stats even if filtered results are empty
			if (empty($all_orders_data)) {
				return '<p class="pn-cm-qr-no-orders">' . __('No QR tickets generated yet', 'pn-customers-manager') . '</p>';
			}
			// Show stats but indicate no results match the filter
			ob_start();
			?>
			<!-- QR Statistics -->
			<div class="pn-cm-qr-stats">
				<div class="pn-cm-qr-stat-card pn-cm-qr-stat-total">
					<div class="pn-cm-qr-stat-icon">
						<i class="material-icons-outlined">qr_code_2</i>
					</div>
					<div class="pn-cm-qr-stat-info">
						<div class="pn-cm-qr-stat-label"><?php esc_html_e('Total QR Codes', 'pn-customers-manager'); ?></div>
						<div class="pn-cm-qr-stat-value"><?php echo number_format($stats['total']); ?></div>
					</div>
				</div>
				<div class="pn-cm-qr-stat-card pn-cm-qr-stat-pending">
					<div class="pn-cm-qr-stat-icon">
						<i class="material-icons-outlined">schedule</i>
					</div>
					<div class="pn-cm-qr-stat-info">
						<div class="pn-cm-qr-stat-label"><?php esc_html_e('Pending', 'pn-customers-manager'); ?></div>
						<div class="pn-cm-qr-stat-value"><?php echo number_format($stats['pending']); ?></div>
					</div>
				</div>
				<div class="pn-cm-qr-stat-card pn-cm-qr-stat-processed">
					<div class="pn-cm-qr-stat-icon">
						<i class="material-icons-outlined">check_circle</i>
					</div>
					<div class="pn-cm-qr-stat-info">
						<div class="pn-cm-qr-stat-label"><?php esc_html_e('Processed', 'pn-customers-manager'); ?></div>
						<div class="pn-cm-qr-stat-value"><?php echo number_format($stats['processed']); ?></div>
					</div>
				</div>
			</div>
			<p class="pn-cm-qr-no-orders"><?php esc_html_e('No QR tickets match the current filters', 'pn-customers-manager'); ?></p>
			<?php
			return ob_get_clean();
		}

		ob_start();
		?>
		<!-- QR Statistics -->
		<div class="pn-cm-qr-stats">
			<div class="pn-cm-qr-stat-card pn-cm-qr-stat-total">
				<div class="pn-cm-qr-stat-icon">
					<i class="material-icons-outlined">qr_code_2</i>
				</div>
				<div class="pn-cm-qr-stat-info">
					<div class="pn-cm-qr-stat-label"><?php esc_html_e('Total QR Codes', 'pn-customers-manager'); ?></div>
					<div class="pn-cm-qr-stat-value"><?php echo number_format($stats['total']); ?></div>
				</div>
			</div>
			<div class="pn-cm-qr-stat-card pn-cm-qr-stat-pending">
				<div class="pn-cm-qr-stat-icon">
					<i class="material-icons-outlined">schedule</i>
				</div>
				<div class="pn-cm-qr-stat-info">
					<div class="pn-cm-qr-stat-label"><?php esc_html_e('Pending', 'pn-customers-manager'); ?></div>
					<div class="pn-cm-qr-stat-value"><?php echo number_format($stats['pending']); ?></div>
				</div>
			</div>
			<div class="pn-cm-qr-stat-card pn-cm-qr-stat-processed">
				<div class="pn-cm-qr-stat-icon">
					<i class="material-icons-outlined">check_circle</i>
				</div>
				<div class="pn-cm-qr-stat-info">
					<div class="pn-cm-qr-stat-label"><?php esc_html_e('Processed', 'pn-customers-manager'); ?></div>
					<div class="pn-cm-qr-stat-value"><?php echo number_format($stats['processed']); ?></div>
				</div>
			</div>
		</div>

		<!-- Bulk Actions Bar -->
		<div class="pn-cm-qr-bulk-actions" style="display: none;">
			<span class="pn-cm-qr-bulk-selected">0 <?php esc_html_e('selected', 'pn-customers-manager'); ?></span>
			<div class="pn-cm-qr-bulk-buttons">
				<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-success" id="pn-cm-qr-bulk-process">
					<i class="material-icons-outlined">check_circle</i>
					<?php esc_html_e('Mark as Processed', 'pn-customers-manager'); ?>
				</button>
				<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-warning" id="pn-cm-qr-bulk-pending">
					<i class="material-icons-outlined">schedule</i>
					<?php esc_html_e('Mark as Pending', 'pn-customers-manager'); ?>
				</button>
				<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-danger" id="pn-cm-qr-bulk-delete">
					<i class="material-icons-outlined">delete</i>
					<?php esc_html_e('Delete', 'pn-customers-manager'); ?>
				</button>
			</div>
		</div>

		<div class="pn-cm-qr-orders-wrapper">
		<table class="pn-cm-qr-orders-table">
			<thead>
				<tr>
					<th style="width: 40px;">
						<input type="checkbox" id="pn-cm-qr-select-all" class="pn-cm-qr-checkbox">
					</th>
					<th><?php esc_html_e('Customer', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Products', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Status', 'pn-customers-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($orders_data as $data): ?>
					<tr class="pn-cm-qr-row <?php echo $data['processed'] ? 'processed' : 'pending'; ?>" style="position: relative;">
						<td style="position: relative; z-index: 2;" onclick="event.stopPropagation();">
							<input type="checkbox" class="pn-cm-qr-checkbox pn-cm-qr-item-checkbox" value="<?php echo esc_attr($data['code']); ?>" data-item-id="<?php echo esc_attr($data['item_id']); ?>">
						</td>
						<td style="position: relative;">
							<a href="#"
								class="pn-customers-manager-popup-open-ajax pn-cm-qr-row-link"
								data-pn-customers-manager-popup-id="pn-customers-manager-popup-qr-view"
								data-pn-customers-manager-ajax-type="pn_cm_woo_qr_view"
								data-qr-code="<?php echo esc_attr($data['code']); ?>"
								style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1;">
								<span style="position: absolute; width: 1px; height: 1px; overflow: hidden;"><?php echo esc_html($data['customer']); ?></span>
							</a>
							<span style="position: relative; z-index: 0;"><?php echo esc_html($data['customer']); ?></span>
						</td>
						<td>
							<div class="pn-cm-qr-products">
								<div class="pn-cm-qr-product-item">
									<span class="pn-cm-qr-product-name">
										<?php echo esc_html($data['product_name']); ?>
										<?php if ($data['variation_text']): ?>
											<span class="pn-cm-qr-variation"><?php echo esc_html($data['variation_text']); ?></span>
										<?php endif; ?>
									</span>
									<span class="pn-cm-qr-product-qty">x<?php echo esc_html($data['quantity']); ?></span>
								</div>
							</div>
						</td>
						<td>
							<?php if ($data['processed']): ?>
								<span class="pn-cm-qr-badge processed">
									<i class="material-icons-outlined">check_circle</i>
									<span class="pn-cm-qr-badge-text"><?php esc_html_e('Processed', 'pn-customers-manager'); ?></span>
								</span>
							<?php else: ?>
								<span class="pn-cm-qr-badge pending">
									<i class="material-icons-outlined">schedule</i>
									<span class="pn-cm-qr-badge-text"><?php esc_html_e('Pending', 'pn-customers-manager'); ?></span>
								</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get all orders for a product with QR data
	 */
	private function get_product_orders($product_id = null, $status = '', $variation_id = null, $search = '') {
		global $wpdb;

		// Build query with optional filters
		$where_clauses = [];
		$prepare_values = [];

		// Filter by product if specified
		if ($product_id) {
			$where_clauses[] = 'oim_product.meta_value = %d';
			$prepare_values[] = $product_id;
		}

		// Filter by variation if specified
		if ($variation_id) {
			$where_clauses[] = 'oim_variation.meta_value = %d';
			$prepare_values[] = $variation_id;
		}

		// Filter by status if specified
		if ($status === 'processed') {
			$where_clauses[] = "oim_processed.meta_value = 'yes'";
		} elseif ($status === 'pending') {
			$where_clauses[] = "(oim_processed.meta_value IS NULL OR oim_processed.meta_value != 'yes')";
		}

		$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

		// Add search filter (will be added after main query to include post data)
		$search_term = $search ? '%' . $wpdb->esc_like($search) . '%' : '';

		$query = "
			SELECT
				oi.order_item_id,
				oi.order_id,
				oi.order_item_name,
				oim_qr.meta_value as qr_code,
				oim_processed.meta_value as processed,
				oim_date.meta_value as processed_date,
				oim_by.meta_value as processed_by,
				oim_qty.meta_value as quantity,
				oim_product.meta_value as product_id,
				oim_variation.meta_value as variation_id
			FROM {$wpdb->prefix}woocommerce_order_items oi
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_product
				ON oi.order_item_id = oim_product.order_item_id
				AND oim_product.meta_key = '_product_id'
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qr
				ON oi.order_item_id = oim_qr.order_item_id
				AND oim_qr.meta_key = '_pn_cm_woo_qr_code'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_variation
				ON oi.order_item_id = oim_variation.order_item_id
				AND oim_variation.meta_key = '_variation_id'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_processed
				ON oi.order_item_id = oim_processed.order_item_id
				AND oim_processed.meta_key = '_pn_cm_woo_qr_processed'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_date
				ON oi.order_item_id = oim_date.order_item_id
				AND oim_date.meta_key = '_pn_cm_woo_qr_processed_date'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_by
				ON oi.order_item_id = oim_by.order_item_id
				AND oim_by.meta_key = '_pn_cm_woo_qr_processed_by'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty
				ON oi.order_item_id = oim_qty.order_item_id
				AND oim_qty.meta_key = '_qty'
			{$where_sql}
			ORDER BY oi.order_id DESC
		";

		if (!empty($prepare_values)) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query uses $wpdb->prefix table names and placeholder-based $where_sql; values passed to prepare().
			$results = $wpdb->get_results($wpdb->prepare($query, ...$prepare_values));
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query uses only $wpdb->prefix table names; no user input when $prepare_values is empty.
			$results = $wpdb->get_results($query);
		}

		$orders_data = [];
		foreach ($results as $row) {
			$order = wc_get_order($row->order_id);
			if (!$order) continue;

			// Get the specific order item
			$order_item = $order->get_item($row->order_item_id);
			if (!$order_item) continue;

			// Get product information
			$product_id = $row->variation_id ? $row->variation_id : $row->product_id;
			$product = wc_get_product($product_id);

			$product_name = $order_item->get_name();
			$variation_text = '';

			// If it's a variation, validate it exists and get the variation attributes
			if ($row->variation_id && $product && $product->is_type('variation')) {
				// Verify the variation is published and exists
				if ($product->get_status() === 'publish') {
					$attributes = $product->get_variation_attributes();
					$variation_parts = [];
					foreach ($attributes as $key => $value) {
						$attribute_name = str_replace('attribute_', '', $key);
						$term = get_term_by('slug', $value, $attribute_name);
						$display_value = $term ? $term->name : $value;
						if ($display_value) {
							$variation_parts[] = $display_value;
						}
					}
					if (!empty($variation_parts)) {
						$variation_text = ' (' . implode(', ', $variation_parts) . ')';
					}
				} else {
					// Variation no longer exists or is not published - show generic message
					$variation_text = ' (' . __('Variation no longer available', 'pn-customers-manager') . ')';
				}
			}

			$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$order_number = $order->get_order_number();

			// Apply search filter if specified
			if ($search_term) {
				$search_match = false;

				// Search in customer name
				if (stripos($customer_name, $search) !== false) {
					$search_match = true;
				}

				// Search in QR code
				if (stripos($row->qr_code, $search) !== false) {
					$search_match = true;
				}

				// Search in order number/ID
				if (stripos($order_number, $search) !== false || stripos($row->order_id, $search) !== false) {
					$search_match = true;
				}

				// Search in product name
				if (stripos($product_name, $search) !== false) {
					$search_match = true;
				}

				// Skip if no match found
				if (!$search_match) {
					continue;
				}
			}

			$orders_data[] = [
				'item_id' => $row->order_item_id,
				'order_id' => $row->order_id,
				'code' => $row->qr_code,
				'processed' => $row->processed === 'yes',
				'processed_date' => $row->processed_date,
				'processed_by' => $row->processed_by,
				'quantity' => $row->quantity ?: 1,
				'customer' => $customer_name,
				'product_name' => $product_name,
				'variation_text' => $variation_text,
				'product_id' => $row->product_id,
				'variation_id' => $row->variation_id,
				'order_date' => $order->get_date_created()->date('Y-m-d H:i'),
			];
		}

		return $orders_data;
	}

	/**
	 * Get QR statistics
	 */
	private function get_qr_stats($product_id = null, $variation_id = null) {
		global $wpdb;

		// Build query with optional product filter
		$where_clauses = [];
		$prepare_values = [];

		if ($product_id) {
			$where_clauses[] = 'oim_product.meta_value = %d';
			$prepare_values[] = $product_id;
		}

		if ($variation_id) {
			$where_clauses[] = 'oim_variation.meta_value = %d';
			$prepare_values[] = $variation_id;
		}

		$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

		$query = "
			SELECT
				COUNT(*) as total,
				SUM(CASE WHEN oim_processed.meta_value = 'yes' THEN 1 ELSE 0 END) as processed,
				SUM(CASE WHEN oim_processed.meta_value IS NULL OR oim_processed.meta_value != 'yes' THEN 1 ELSE 0 END) as pending
			FROM {$wpdb->prefix}woocommerce_order_items oi
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_product
				ON oi.order_item_id = oim_product.order_item_id
				AND oim_product.meta_key = '_product_id'
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qr
				ON oi.order_item_id = oim_qr.order_item_id
				AND oim_qr.meta_key = '_pn_cm_woo_qr_code'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_variation
				ON oi.order_item_id = oim_variation.order_item_id
				AND oim_variation.meta_key = '_variation_id'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_processed
				ON oi.order_item_id = oim_processed.order_item_id
				AND oim_processed.meta_key = '_pn_cm_woo_qr_processed'
			{$where_sql}
		";

		if (!empty($prepare_values)) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query uses $wpdb->prefix table names and placeholder-based $where_sql; values passed to prepare().
			$result = $wpdb->get_row($wpdb->prepare($query, ...$prepare_values));
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query uses only $wpdb->prefix table names; no user input when $prepare_values is empty.
			$result = $wpdb->get_row($query);
		}

		return [
			'total' => (int) $result->total,
			'processed' => (int) $result->processed,
			'pending' => (int) $result->pending,
		];
	}

	/**
	 * Get QR codes for a specific user and product
	 */
	private function get_user_qr_codes($user_id, $product_id) {
		global $wpdb;

		$results = $wpdb->get_results($wpdb->prepare("
			SELECT
				oi.order_item_id,
				oi.order_id,
				oim_qr.meta_value as qr_code,
				oim_processed.meta_value as processed,
				oim_qty.meta_value as quantity
			FROM {$wpdb->prefix}woocommerce_order_items oi
			INNER JOIN {$wpdb->prefix}posts p ON oi.order_id = p.ID
			INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_customer_user'
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_product
				ON oi.order_item_id = oim_product.order_item_id
				AND oim_product.meta_key = '_product_id'
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qr
				ON oi.order_item_id = oim_qr.order_item_id
				AND oim_qr.meta_key = '_pn_cm_woo_qr_code'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_processed
				ON oi.order_item_id = oim_processed.order_item_id
				AND oim_processed.meta_key = '_pn_cm_woo_qr_processed'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty
				ON oi.order_item_id = oim_qty.order_item_id
				AND oim_qty.meta_key = '_qty'
			WHERE pm.meta_value = %d
			AND oim_product.meta_value = %d
			AND p.post_status = 'wc-completed'
			ORDER BY oi.order_id DESC
		", $user_id, $product_id));

		$qr_codes = [];
		foreach ($results as $row) {
			$qr_codes[] = [
				'item_id' => $row->order_item_id,
				'order_id' => $row->order_id,
				'code' => $row->qr_code,
				'processed' => $row->processed === 'yes',
				'quantity' => $row->quantity ?: 1,
			];
		}

		return $qr_codes;
	}

	/**
	 * Add QR codes to order completion email
	 */
	public function add_qr_to_email($order, $sent_to_admin, $plain_text, $email) {
		// Only add to customer emails when order is completed
		if ($sent_to_admin || $plain_text || $email->id !== 'customer_completed_order') {
			return;
		}

		// Ensure QR codes are generated before sending email
		$this->generate_qr_codes($order->get_id());

		$qr_items = [];
		foreach ($order->get_items() as $item_id => $item) {
			$qr_code = $item->get_meta('_pn_cm_woo_qr_code');
			if (!empty($qr_code)) {
				$product_id = $item->get_product_id();
				$instructions = get_post_meta($product_id, '_pn_cm_woo_qr_instructions', true);

				$qr_items[] = [
					'name' => $item->get_name(),
					'code' => $qr_code,
					'quantity' => $item->get_quantity(),
					'instructions' => $instructions,
				];
			}
		}

		if (empty($qr_items)) return;

		?>
		<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
			<h2 style="margin-top: 0; color: #333;"><?php esc_html_e('Your QR Tickets', 'pn-customers-manager'); ?></h2>

			<?php foreach ($qr_items as $qr_item): ?>
				<div style="margin-bottom: 30px; padding: 20px; background: white; border-radius: 8px;">
					<h3 style="margin-top: 0; color: #667eea;"><?php echo esc_html($qr_item['name']); ?></h3>
					<?php if (!empty($qr_item['instructions'])): ?>
						<p style="margin: 10px 0;"><?php echo wp_kses_post($qr_item['instructions']); ?></p>
					<?php endif; ?>

					<div style="text-align: center; margin: 20px 0;">
						<?php echo wp_kses_post($this->generate_qr_svg($qr_item['code'], 200)); ?>
					</div>

					<p style="text-align: center; font-family: monospace; font-size: 16px; font-weight: bold; color: #333;">
						<?php echo esc_html($qr_item['code']); ?>
					</p>

					<p style="text-align: center; color: #6c757d;">
						<?php /* translators: %d is the item quantity */ printf(esc_html__('Quantity: %d', 'pn-customers-manager'), esc_html($qr_item['quantity'])); ?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Add QR codes to My Account order details page
	 */
	public function add_qr_to_order_details($order) {
		// Ensure QR codes are generated
		$this->generate_qr_codes($order->get_id());

		$qr_items = [];
		foreach ($order->get_items() as $item_id => $item) {
			$qr_code = $item->get_meta('_pn_cm_woo_qr_code');
			if (!empty($qr_code)) {
				$product_id = $item->get_product_id();
				$instructions = get_post_meta($product_id, '_pn_cm_woo_qr_instructions', true);

				$qr_items[] = [
					'name' => $item->get_name(),
					'code' => $qr_code,
					'quantity' => $item->get_quantity(),
					'instructions' => $instructions,
					'processed' => $item->get_meta('_pn_cm_woo_qr_processed') === 'yes',
				];
			}
		}

		if (empty($qr_items)) return;

		?>
		<section class="woocommerce-order-qr-tickets" style="margin-top: 30px;">
			<h2><?php esc_html_e('Your QR Tickets', 'pn-customers-manager'); ?></h2>

			<?php foreach ($qr_items as $qr_item): ?>
				<div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
					<h3 style="margin-top: 0; color: #667eea;"><?php echo esc_html($qr_item['name']); ?></h3>
					<?php if (!empty($qr_item['instructions'])): ?>
						<p><?php echo wp_kses_post($qr_item['instructions']); ?></p>
					<?php endif; ?>

					<div style="text-align: center; margin: 20px 0;">
						<?php echo wp_kses_post($this->generate_qr_svg($qr_item['code'], 200)); ?>
					</div>

					<p style="text-align: center; font-family: monospace; font-size: 16px; font-weight: bold;">
						<?php echo esc_html($qr_item['code']); ?>
					</p>

					<p style="text-align: center;">
						<?php /* translators: %d is the item quantity */ printf(esc_html__('Quantity: %d', 'pn-customers-manager'), esc_html($qr_item['quantity'])); ?>
					</p>

					<?php if ($qr_item['processed']): ?>
						<p style="text-align: center; color: #28a745; font-weight: bold;">
							<?php esc_html_e('✓ Processed', 'pn-customers-manager'); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
	}

	/**
	 * Add QR Tickets metabox to order edit page
	 */
	public function add_order_qr_metabox() {
		$screen = class_exists('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')
			&& wc_get_container()->get(Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id('shop-order')
			: 'shop_order';

		add_meta_box(
			'pn_cm_woo_qr_tickets',
			__('QR Tickets', 'pn-customers-manager'),
			[$this, 'render_order_qr_metabox'],
			$screen,
			'normal',
			'high'
		);
	}

	/**
	 * Render QR Tickets metabox content
	 */
	public function render_order_qr_metabox($post_or_order_object) {
		$order = ($post_or_order_object instanceof WP_Post)
			? wc_get_order($post_or_order_object->ID)
			: $post_or_order_object;

		if (!$order) {
			return;
		}

		// Ensure QR codes are generated if order is completed
		if ($order->get_status() === 'completed') {
			$this->generate_qr_codes($order->get_id());
		}

		$qr_items = [];
		foreach ($order->get_items() as $item_id => $item) {
			$qr_code = $item->get_meta('_pn_cm_woo_qr_code');
			if (!empty($qr_code)) {
				$product_id = $item->get_product_id();
				$instructions = get_post_meta($product_id, '_pn_cm_woo_qr_instructions', true);

				$qr_items[] = [
					'name' => $item->get_name(),
					'code' => $qr_code,
					'quantity' => $item->get_quantity(),
					'instructions' => $instructions,
					'processed' => $item->get_meta('_pn_cm_woo_qr_processed') === 'yes',
					'processed_date' => $item->get_meta('_pn_cm_woo_qr_processed_date'),
					'processed_by' => $item->get_meta('_pn_cm_woo_qr_processed_by'),
				];
			}
		}

		if (empty($qr_items)) {
			echo '<p>' . esc_html__('No QR tickets for this order.', 'pn-customers-manager') . '</p>';
			return;
		}

		?>
		<style>
			.pn-cm-qr-item {
				margin-bottom: 20px;
				padding: 15px;
				background: #f8f9fa;
				border-radius: 5px;
			}
			.pn-cm-qr-item h4 {
				margin: 0 0 10px 0;
				color: #667eea;
				font-size: 14px;
			}
			.pn-cm-qr-code-display {
				text-align: center;
				margin: 10px 0;
			}
			.pn-cm-qr-code-display img {
				max-width: 100%;
				height: auto;
			}
			.pn-cm-qr-code-text {
				text-align: center;
				font-family: monospace;
				font-size: 14px;
				font-weight: bold;
				margin: 10px 0;
				word-break: break-all;
			}
			.pn-cm-qr-status {
				text-align: center;
				margin: 10px 0;
			}
			.pn-cm-qr-status.processed {
				color: #28a745;
				font-weight: bold;
			}
			.pn-cm-qr-status.pending {
				color: #ffc107;
				font-weight: bold;
			}
			.pn-cm-qr-meta {
				font-size: 12px;
				color: #666;
				margin-top: 5px;
			}
		</style>

		<?php foreach ($qr_items as $qr_item): ?>
			<div class="pn-cm-qr-item">
				<h4><?php echo esc_html($qr_item['name']); ?></h4>

				<?php if (!empty($qr_item['instructions'])): ?>
					<p style="font-size: 12px; margin: 5px 0;"><?php echo wp_kses_post($qr_item['instructions']); ?></p>
				<?php endif; ?>

				<div class="pn-cm-qr-code-display">
					<?php echo wp_kses_post($this->generate_qr_svg($qr_item['code'], 150)); ?>
				</div>

				<div class="pn-cm-qr-code-text">
					<?php echo esc_html($qr_item['code']); ?>
				</div>

				<div style="text-align: center; font-size: 12px; margin: 5px 0;">
					<?php /* translators: %d is the item quantity */ printf(esc_html__('Quantity: %d', 'pn-customers-manager'), esc_html($qr_item['quantity'])); ?>
				</div>

				<?php if ($qr_item['processed']): ?>
					<div class="pn-cm-qr-status processed">
						✓ <?php esc_html_e('Processed', 'pn-customers-manager'); ?>
					</div>
					<?php if (!empty($qr_item['processed_date']) || !empty($qr_item['processed_by'])): ?>
						<div class="pn-cm-qr-meta">
							<?php if (!empty($qr_item['processed_date'])): ?>
								<?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($qr_item['processed_date']))); ?>
							<?php endif; ?>
							<?php if (!empty($qr_item['processed_by'])): ?>
								<br><?php /* translators: %s is the name of the user who processed the QR code */ printf(esc_html__('by %s', 'pn-customers-manager'), esc_html($qr_item['processed_by'])); ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				<?php else: ?>
					<div class="pn-cm-qr-status pending">
						⏳ <?php esc_html_e('Pending', 'pn-customers-manager'); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Generate QR code as SVG
	 */
	private function generate_qr_svg($data, $size = 150) {
		// Using a simple QR generation approach with Google Charts API fallback
		// In production, you might want to use a PHP QR library like endroid/qr-code
		$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
		return '<img src="' . esc_url($qr_url) . '" alt="QR Code" style="max-width: ' . $size . 'px; height: auto;" />';
	}

	/**
	 * AJAX: Process QR code
	 */
	public function ajax_process_qr() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$qr_code = sanitize_text_field($_POST['qr_code']);

		// Find the order item with this QR code
		global $wpdb;
		$item_id = $wpdb->get_var($wpdb->prepare("
			SELECT order_item_id
			FROM {$wpdb->prefix}woocommerce_order_itemmeta
			WHERE meta_key = '_pn_cm_woo_qr_code'
			AND meta_value = %s
		", $qr_code));

		if (!$item_id) {
			wp_send_json_error(['message' => __('QR code not found', 'pn-customers-manager')]);
		}

		// Check if already processed
		$current_status = wc_get_order_item_meta($item_id, '_pn_cm_woo_qr_processed', true);
		$already_processed = ($current_status === 'yes');

		// Update processed status
		wc_update_order_item_meta($item_id, '_pn_cm_woo_qr_processed', 'yes');
		wc_update_order_item_meta($item_id, '_pn_cm_woo_qr_processed_date', current_time('mysql'));
		wc_update_order_item_meta($item_id, '_pn_cm_woo_qr_processed_by', wp_get_current_user()->display_name);

		$message = $already_processed
			? __('This QR code was already processed', 'pn-customers-manager')
			: __('QR code processed successfully', 'pn-customers-manager');

		wp_send_json_success([
			'message' => $message,
			'already_processed' => $already_processed,
		]);
	}

	/**
	 * AJAX: Mark QR code as pending
	 */
	public function ajax_mark_pending() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$qr_code = sanitize_text_field($_POST['qr_code']);

		// Find the order item with this QR code
		global $wpdb;
		$item_id = $wpdb->get_var($wpdb->prepare("
			SELECT order_item_id
			FROM {$wpdb->prefix}woocommerce_order_itemmeta
			WHERE meta_key = '_pn_cm_woo_qr_code'
			AND meta_value = %s
		", $qr_code));

		if (!$item_id) {
			wp_send_json_error(['message' => __('QR code not found', 'pn-customers-manager')]);
		}

		// Update to pending status
		wc_update_order_item_meta($item_id, '_pn_cm_woo_qr_processed', 'no');
		wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed_date');
		wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed_by');

		wp_send_json_success([
			'message' => __('QR code marked as pending', 'pn-customers-manager'),
		]);
	}

	/**
	 * AJAX: Delete QR code
	 */
	public function ajax_delete_qr() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$qr_code = sanitize_text_field($_POST['qr_code']);

		// Find the order item with this QR code
		global $wpdb;
		$item_id = $wpdb->get_var($wpdb->prepare("
			SELECT order_item_id
			FROM {$wpdb->prefix}woocommerce_order_itemmeta
			WHERE meta_key = '_pn_cm_woo_qr_code'
			AND meta_value = %s
		", $qr_code));

		if (!$item_id) {
			wp_send_json_error(['message' => __('QR code not found', 'pn-customers-manager')]);
		}

		// Delete QR meta data
		wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_code');
		wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed');
		wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed_date');
		wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed_by');

		wp_send_json_success([
			'message' => __('QR code deleted successfully', 'pn-customers-manager'),
		]);
	}

	/**
	 * AJAX: Get orders list
	 */
	public function ajax_get_orders() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$product_id = isset($_POST['product_id']) && $_POST['product_id'] !== '' ? intval($_POST['product_id']) : null;
		$status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
		$variation_id = isset($_POST['variation_id']) && $_POST['variation_id'] !== '' ? intval($_POST['variation_id']) : null;
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

		$html = $this->get_orders_html($product_id, $status, $variation_id, $search);

		wp_send_json_success(['html' => $html]);
	}

	/**
	 * AJAX: Get product variations
	 */
	public function ajax_get_variations() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$product_id = isset($_POST['product_id']) && $_POST['product_id'] !== '' ? intval($_POST['product_id']) : null;

		if (!$product_id) {
			wp_send_json_error(['message' => __('Product ID is required', 'pn-customers-manager')]);
		}

		$product = wc_get_product($product_id);

		if (!$product) {
			wp_send_json_error(['message' => __('Product not found', 'pn-customers-manager')]);
		}

		$variations = [];

		// Check if product is variable
		if ($product->is_type('variable')) {
			$variable_product = wc_get_product($product_id);
			$available_variations = $variable_product->get_available_variations();

			foreach ($available_variations as $variation_data) {
				$variation_id = $variation_data['variation_id'];
				$variation = wc_get_product($variation_id);

				// Only include variations that are published and currently available
				if ($variation && $variation->get_status() === 'publish') {
					// Get variation attributes formatted nicely
					$attributes = $variation->get_variation_attributes();
					$variation_parts = [];
					foreach ($attributes as $key => $value) {
						$attribute_name = str_replace('attribute_', '', $key);
						$term = get_term_by('slug', $value, $attribute_name);
						$display_value = $term ? $term->name : $value;
						if ($display_value) {
							$variation_parts[] = $display_value;
						}
					}

					if (!empty($variation_parts)) {
						$variations[] = [
							'id' => $variation_id,
							'name' => implode(', ', $variation_parts),
						];
					}
				}
			}
		}

		wp_send_json_success(['variations' => $variations]);
	}

	/**
	 * AJAX: Bulk process QR codes
	 */
	public function ajax_bulk_process() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$qr_codes = isset($_POST['qr_codes']) ? $_POST['qr_codes'] : [];

		if (empty($qr_codes) || !is_array($qr_codes)) {
			wp_send_json_error(['message' => __('No QR codes provided', 'pn-customers-manager')]);
		}

		global $wpdb;
		$processed = 0;
		$current_user = wp_get_current_user();
		$user_name = $current_user->display_name;

		foreach ($qr_codes as $qr_code) {
			$qr_code = sanitize_text_field($qr_code);

			// Find the order item with this QR code
			$item_id = $wpdb->get_var($wpdb->prepare("
				SELECT order_item_id
				FROM {$wpdb->prefix}woocommerce_order_itemmeta
				WHERE meta_key = '_pn_cm_woo_qr_code'
				AND meta_value = %s
			", $qr_code));

			if ($item_id) {
				wc_update_order_item_meta($item_id, '_pn_cm_woo_qr_processed', 'yes');
				wc_update_order_item_meta($item_id, '_pn_cm_woo_qr_processed_date', current_time('mysql'));
				wc_update_order_item_meta($item_id, '_pn_cm_woo_qr_processed_by', $user_name);
				$processed++;
			}
		}

		wp_send_json_success([
			'message' => /* translators: %d is the number of QR codes processed */ sprintf(__('%d QR codes marked as processed', 'pn-customers-manager'), $processed),
			'count' => $processed
		]);
	}

	/**
	 * AJAX: Bulk mark QR codes as pending
	 */
	public function ajax_bulk_pending() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$qr_codes = isset($_POST['qr_codes']) ? $_POST['qr_codes'] : [];

		if (empty($qr_codes) || !is_array($qr_codes)) {
			wp_send_json_error(['message' => __('No QR codes provided', 'pn-customers-manager')]);
		}

		global $wpdb;
		$processed = 0;

		foreach ($qr_codes as $qr_code) {
			$qr_code = sanitize_text_field($qr_code);

			// Find the order item with this QR code
			$item_id = $wpdb->get_var($wpdb->prepare("
				SELECT order_item_id
				FROM {$wpdb->prefix}woocommerce_order_itemmeta
				WHERE meta_key = '_pn_cm_woo_qr_code'
				AND meta_value = %s
			", $qr_code));

			if ($item_id) {
				wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed');
				wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed_date');
				wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed_by');
				$processed++;
			}
		}

		wp_send_json_success([
			'message' => /* translators: %d is the number of QR codes marked as pending */ sprintf(__('%d QR codes marked as pending', 'pn-customers-manager'), $processed),
			'count' => $processed
		]);
	}

	/**
	 * AJAX: Bulk delete QR codes
	 */
	public function ajax_bulk_delete() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$qr_codes = isset($_POST['qr_codes']) ? $_POST['qr_codes'] : [];

		if (empty($qr_codes) || !is_array($qr_codes)) {
			wp_send_json_error(['message' => __('No QR codes provided', 'pn-customers-manager')]);
		}

		global $wpdb;
		$deleted = 0;

		foreach ($qr_codes as $qr_code) {
			$qr_code = sanitize_text_field($qr_code);

			// Find the order item with this QR code
			$item_id = $wpdb->get_var($wpdb->prepare("
				SELECT order_item_id
				FROM {$wpdb->prefix}woocommerce_order_itemmeta
				WHERE meta_key = '_pn_cm_woo_qr_code'
				AND meta_value = %s
			", $qr_code));

			if ($item_id) {
				wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_code');
				wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed');
				wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed_date');
				wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_processed_by');
				wc_delete_order_item_meta($item_id, '_pn_cm_woo_qr_enabled');
				$deleted++;
			}
		}

		wp_send_json_success([
			'message' => /* translators: %d is the number of QR codes deleted */ sprintf(__('%d QR codes deleted', 'pn-customers-manager'), $deleted),
			'count' => $deleted
		]);
	}

	/**
	 * AJAX: Get QR detail view
	 */
	public function ajax_qr_view() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Permission denied', 'pn-customers-manager')]);
		}

		$qr_code = isset($_POST['qr_code']) ? sanitize_text_field($_POST['qr_code']) : '';

		if (empty($qr_code)) {
			wp_send_json_error(['message' => __('QR code is required', 'pn-customers-manager')]);
		}

		$html = $this->qr_view($qr_code);
		echo wp_kses_post($html);
		wp_die();
	}

	/**
	 * Generate QR detail view HTML
	 */
	private function qr_view($qr_code) {
		global $wpdb;

		// Get QR data
		$item_data = $wpdb->get_row($wpdb->prepare("
			SELECT
				oi.order_item_id,
				oi.order_id,
				oi.order_item_name,
				oim_qr.meta_value as qr_code,
				oim_processed.meta_value as processed,
				oim_date.meta_value as processed_date,
				oim_by.meta_value as processed_by,
				oim_qty.meta_value as quantity,
				oim_product.meta_value as product_id,
				oim_variation.meta_value as variation_id
			FROM {$wpdb->prefix}woocommerce_order_items oi
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qr
				ON oi.order_item_id = oim_qr.order_item_id
				AND oim_qr.meta_key = '_pn_cm_woo_qr_code'
				AND oim_qr.meta_value = %s
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_processed
				ON oi.order_item_id = oim_processed.order_item_id
				AND oim_processed.meta_key = '_pn_cm_woo_qr_processed'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_date
				ON oi.order_item_id = oim_date.order_item_id
				AND oim_date.meta_key = '_pn_cm_woo_qr_processed_date'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_by
				ON oi.order_item_id = oim_by.order_item_id
				AND oim_by.meta_key = '_pn_cm_woo_qr_processed_by'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty
				ON oi.order_item_id = oim_qty.order_item_id
				AND oim_qty.meta_key = '_qty'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_product
				ON oi.order_item_id = oim_product.order_item_id
				AND oim_product.meta_key = '_product_id'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_variation
				ON oi.order_item_id = oim_variation.order_item_id
				AND oim_variation.meta_key = '_variation_id'
		", $qr_code));

		if (!$item_data) {
			return '<p>' . __('QR code not found', 'pn-customers-manager') . '</p>';
		}

		$order = wc_get_order($item_data->order_id);
		if (!$order) {
			return '<p>' . __('Order not found', 'pn-customers-manager') . '</p>';
		}

		$processed = $item_data->processed === 'yes';
		$customer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$order_date = $order->get_date_created()->date('Y-m-d H:i');

		// Get the specific order item
		$order_item = $order->get_item($item_data->order_item_id);
		if (!$order_item) {
			return '<p>' . __('Order item not found', 'pn-customers-manager') . '</p>';
		}

		// Get product information
		$product_id = $item_data->variation_id ? $item_data->variation_id : $item_data->product_id;
		$product = wc_get_product($product_id);

		$product_name = $order_item->get_name();
		$variation_text = '';

		// If it's a variation, validate it exists and get the variation attributes
		if ($item_data->variation_id && $product && $product->is_type('variation')) {
			// Verify the variation is published and exists
			if ($product->get_status() === 'publish') {
				$attributes = $product->get_variation_attributes();
				$variation_parts = [];
				foreach ($attributes as $key => $value) {
					$attribute_name = str_replace('attribute_', '', $key);
					$term = get_term_by('slug', $value, $attribute_name);
					$display_value = $term ? $term->name : $value;
					if ($display_value) {
						$variation_parts[] = $display_value;
					}
				}
				if (!empty($variation_parts)) {
					$variation_text = ' (' . implode(', ', $variation_parts) . ')';
				}
			} else {
				// Variation no longer exists or is not published - show generic message
				$variation_text = ' (' . __('Variation no longer available', 'pn-customers-manager') . ')';
			}
		}

		ob_start();
		?>
		<div class="pn-cm-qr-view pn-customers-manager-p-30">
			<h4 class="pn-customers-manager-text-align-center"><?php esc_html_e('QR Ticket Details', 'pn-customers-manager'); ?></h4>

			<div class="pn-cm-qr-detail-wrapper">
				<div class="pn-cm-qr-detail-qr">
					<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($qr_code); ?>"
						alt="QR Code"
						style="max-width: 100%; height: auto; border: 2px solid #dee2e6; border-radius: 8px; padding: 10px; background: white;" />
					<div class="pn-cm-qr-code-string" style="margin-top: 15px; font-family: monospace; font-size: 14px; word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 4px;">
						<code style="color: #667eea; font-weight: bold;"><?php echo esc_html($qr_code); ?></code>
					</div>
				</div>

				<div class="pn-cm-qr-detail-info">
					<p><strong><?php esc_html_e('Order', 'pn-customers-manager'); ?>:</strong> #<?php echo esc_html($item_data->order_id); ?></p>
					<p><strong><?php esc_html_e('Customer', 'pn-customers-manager'); ?>:</strong> <?php echo esc_html($customer); ?></p>
					<p><strong><?php esc_html_e('Date', 'pn-customers-manager'); ?>:</strong> <?php echo esc_html($order_date); ?></p>

					<p><strong><?php esc_html_e('Product', 'pn-customers-manager'); ?>:</strong></p>
					<ul class="pn-cm-qr-products-list" style="margin: 10px 0; padding-left: 20px;">
						<li style="margin: 5px 0; color: #333;">
							<?php echo esc_html($product_name); ?>
							<?php if ($variation_text): ?>
								<span class="pn-cm-qr-variation" style="color: #6c757d; font-size: 12px; font-style: italic;"><?php echo esc_html($variation_text); ?></span>
							<?php endif; ?>
							<span class="pn-cm-qr-qty" style="color: #6c757d; font-weight: 600; margin-left: 5px;">x<?php echo esc_html($item_data->quantity ?: 1); ?></span>
						</li>
					</ul>

					<?php if ($processed): ?>
						<div class="pn-cm-qr-processed-info" style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 8px; border-left: 4px solid #28a745;">
							<p class="pn-cm-qr-status-processed" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 16px; margin: 0; color: #155724;">
								<i class="material-icons-outlined" style="font-size: 20px;">check_circle</i>
								<?php esc_html_e('Processed', 'pn-customers-manager'); ?>
							</p>
							<?php if ($item_data->processed_by): ?>
								<p style="margin: 10px 0 0; font-size: 14px; color: #155724;">
									<?php /* translators: %s is the name of the user who processed the QR code */ printf(esc_html__('By: %s', 'pn-customers-manager'), esc_html($item_data->processed_by)); ?>
								</p>
							<?php endif; ?>
							<?php if ($item_data->processed_date): ?>
								<p style="margin: 5px 0 0; font-size: 14px; color: #155724;">
									<?php /* translators: %s is the date when the QR code was processed */ printf(esc_html__('On: %s', 'pn-customers-manager'), esc_html($item_data->processed_date)); ?>
								</p>
							<?php endif; ?>
						</div>
					<?php else: ?>
						<div class="pn-cm-qr-status-info" style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
							<p class="pn-cm-qr-status-pending" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 16px; margin: 0; color: #856404;">
								<i class="material-icons-outlined" style="font-size: 20px;">schedule</i>
								<?php esc_html_e('Pending', 'pn-customers-manager'); ?>
							</p>
						</div>
					<?php endif; ?>

					<div class="pn-cm-qr-actions" style="display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6;">
						<?php if (!$processed): ?>
							<button class="pn-cm-qr-action-btn pn-cm-qr-process-btn"
								data-qr-code="<?php echo esc_attr($qr_code); ?>"
								title="<?php esc_attr_e('Process ticket', 'pn-customers-manager'); ?>"
								style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border: 2px solid #28a745; background: white; border-radius: 8px; cursor: pointer; color: #28a745; padding: 0;">
								<i class="material-icons-outlined" style="font-size: 24px;">check_circle</i>
							</button>
						<?php else: ?>
							<button class="pn-cm-qr-action-btn pn-cm-qr-pending-btn"
								data-qr-code="<?php echo esc_attr($qr_code); ?>"
								title="<?php esc_attr_e('Mark as pending', 'pn-customers-manager'); ?>"
								style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border: 2px solid #ffc107; background: white; border-radius: 8px; cursor: pointer; color: #ffc107; padding: 0;">
								<i class="material-icons-outlined" style="font-size: 24px;">schedule</i>
							</button>
						<?php endif; ?>

						<button class="pn-cm-qr-action-btn pn-cm-qr-delete-btn"
							data-qr-code="<?php echo esc_attr($qr_code); ?>"
							title="<?php esc_attr_e('Delete ticket', 'pn-customers-manager'); ?>"
							style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border: 2px solid #dc3545; background: white; border-radius: 8px; cursor: pointer; color: #dc3545; padding: 0;">
							<i class="material-icons-outlined" style="font-size: 24px;">delete</i>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode to display QR manager on any page
	 * Usage: [pn_qr_manager] or [pn_qr_manager product_id="123"]
	 */
	public function qr_manager_shortcode($atts) {
		// Check permissions - return nothing if user doesn't have permission
		if (!current_user_can('manage_woocommerce')) {
			return '';
		}

		// Parse attributes
		$atts = shortcode_atts([
			'product_id' => null,
		], $atts, 'pn_qr_manager');

		// Enqueue necessary scripts and styles
		wp_enqueue_style('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], PN_CUSTOMERS_MANAGER_VERSION);
		wp_enqueue_style('wph-material-icons-outlined', PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css', [], PN_CUSTOMERS_MANAGER_VERSION);

		// Enqueue popup CSS (required for popup system)
		if (!wp_style_is('pn-customers-manager-popups', 'enqueued')) {
			wp_enqueue_style('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', [], PN_CUSTOMERS_MANAGER_VERSION);
		}

		wp_enqueue_style('pn-cm-qr-frontend', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-cm-qr.css', ['pn-customers-manager'], PN_CUSTOMERS_MANAGER_VERSION);

		// Enqueue main pn-customers-manager script (required for main_message function)
		if (!wp_script_is('pn-customers-manager', 'registered')) {
			wp_register_script('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
		}
		if (!wp_script_is('pn-customers-manager', 'enqueued')) {
			wp_enqueue_script('pn-customers-manager');
		}

		// Localize main script
		if (!wp_script_is('pn-customers-manager', 'done')) {
			wp_localize_script('pn-customers-manager', 'pn_customers_manager_i18n', [
				'copied' => esc_html(__('Copied', 'pn-customers-manager')),
			]);
		}

		// Enqueue popup and AJAX scripts (required dependencies)
		if (!wp_script_is('pn-customers-manager-popups', 'registered')) {
			wp_register_script('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
		}
		if (!wp_script_is('pn-customers-manager-popups', 'enqueued')) {
			wp_enqueue_script('pn-customers-manager-popups');
		}

		if (!wp_script_is('pn-customers-manager-ajax', 'registered')) {
			wp_register_script('pn-customers-manager-ajax', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-ajax.js', ['jquery', 'pn-customers-manager-popups'], PN_CUSTOMERS_MANAGER_VERSION, true);
		}
		if (!wp_script_is('pn-customers-manager-ajax', 'enqueued')) {
			wp_enqueue_script('pn-customers-manager-ajax');
		}

		// Localize popup/AJAX scripts
		if (!wp_script_is('pn-customers-manager-ajax', 'done')) {
			wp_localize_script('pn-customers-manager-ajax', 'pn_customers_manager_ajax', [
				'ajax_url' => admin_url('admin-ajax.php'),
				'pn_customers_manager_ajax_nonce' => wp_create_nonce('pn-customers-manager-nonce'),
			]);

			wp_localize_script('pn-customers-manager-ajax', 'pn_customers_manager_action', [
				'pn_customers_manager_get_nonce' => wp_create_nonce('pn-customers-manager-get-nonce'),
			]);
		}

		// phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- QR scanner library loaded from CDN; no bundled alternative.
		wp_enqueue_script('pn-cm-qr-scanner', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', [], '2.3.8', true);
		wp_enqueue_script('pn-cm-qr-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-qr-admin.js', ['jquery', 'pn-cm-qr-scanner', 'pn-customers-manager', 'pn-customers-manager-popups', 'pn-customers-manager-ajax'], PN_CUSTOMERS_MANAGER_VERSION, true);

		// Localize script
		wp_localize_script('pn-cm-qr-admin', 'pnCmQrData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'adminUrl' => admin_url(),
			'nonce' => wp_create_nonce('pn_cm_woo_qr_nonce'),
			'i18n' => [
				'order' => __('Order', 'pn-customers-manager'),
				'customer' => __('Customer', 'pn-customers-manager'),
				'products' => __('Products', 'pn-customers-manager'),
				'processed' => __('Processed', 'pn-customers-manager'),
				'pending' => __('Pending', 'pn-customers-manager'),
				'processedDate' => __('Date', 'pn-customers-manager'),
				'processedBy' => __('Processed by', 'pn-customers-manager'),
				'markProcessed' => __('Mark as Processed', 'pn-customers-manager'),
				'markPending' => __('Mark as Pending', 'pn-customers-manager'),
				'delete' => __('Delete', 'pn-customers-manager'),
				'processing' => __('Processing...', 'pn-customers-manager'),
				'scanSuccess' => __('QR Code scanned successfully', 'pn-customers-manager'),
				'scanError' => __('Error accessing camera', 'pn-customers-manager'),
				'error' => __('An error occurred', 'pn-customers-manager'),
				'confirmProcess' => __('Are you sure you want to mark this QR code as processed?', 'pn-customers-manager'),
				'confirmPending' => __('Are you sure you want to mark this QR code as pending?', 'pn-customers-manager'),
				'confirmDelete' => __('Are you sure you want to delete this QR code? This action cannot be undone.', 'pn-customers-manager'),
				'selected' => __('selected', 'pn-customers-manager'),
				'selectedPlural' => __('selected', 'pn-customers-manager'),
				/* translators: %d is the number of selected QR codes */
				'confirmBulkProcess' => __('Are you sure you want to mark %d QR codes as processed?', 'pn-customers-manager'),
				/* translators: %d is the number of selected QR codes */
				'confirmBulkPending' => __('Are you sure you want to mark %d QR codes as pending?', 'pn-customers-manager'),
				/* translators: %d is the number of selected QR codes */
				'confirmBulkDelete' => __('Are you sure you want to delete %d QR codes? This action cannot be undone.', 'pn-customers-manager'),
				'completePending' => __('Complete Pending Orders', 'pn-customers-manager'),
				'loadingOrders' => __('Loading pending orders...', 'pn-customers-manager'),
				'noPendingOrders' => __('No pending orders found with QR products', 'pn-customers-manager'),
				/* translators: %d is the number of pending orders found */
				'pendingOrdersFound' => __('%d pending orders found', 'pn-customers-manager'),
				'selectAll' => __('Select All', 'pn-customers-manager'),
				'deselectAll' => __('Deselect All', 'pn-customers-manager'),
				'completeSelected' => __('Complete Selected Orders', 'pn-customers-manager'),
				/* translators: %s is the order number */
				'orderNumber' => __('Order #%s', 'pn-customers-manager'),
				'paymentMethod' => __('Payment Method', 'pn-customers-manager'),
				'completingOrders' => __('Completing orders...', 'pn-customers-manager'),
				/* translators: %d is the number of orders completed */
				'ordersCompleted' => __('%d orders completed successfully', 'pn-customers-manager'),
				/* translators: %d is the number of orders to be completed */
				'confirmComplete' => __('Are you sure you want to complete %d orders?', 'pn-customers-manager'),
			],
		]);

		// Get all products with QR enabled
		$qr_products = get_posts([
			'post_type' => 'product',
			'posts_per_page' => -1,
			'meta_key' => '_pn_cm_woo_qr_enabled',
			'meta_value' => 'yes',
			'orderby' => 'title',
			'order' => 'ASC',
		]);

		// Get product ID from shortcode attribute or default to first product
		$product_id = $atts['product_id'] ? intval($atts['product_id']) : null;

		// Start output buffering
		ob_start();
		?>
		<div class="pn-cm-qr-admin-panel pn-cm-qr-shortcode">
			<h2><?php esc_html_e('QR Tickets Management', 'pn-customers-manager'); ?></h2>

			<!-- Persistent notification area -->
			<div id="pn-cm-qr-notification" class="pn-cm-qr-notification pn-customers-manager-display-none" style="display: none;">
				<span id="pn-cm-qr-notification-message"></span>
				<button type="button" class="pn-cm-qr-notification-close" aria-label="<?php esc_attr_e('Close', 'pn-customers-manager'); ?>">
					<i class="material-icons-outlined">close</i>
				</button>
			</div>

			<div class="pn-cm-qr-admin-controls">
				<div class="pn-cm-qr-actions-btns">
					<button type="button" id="pn-cm-qr-scan" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent">
						<?php esc_html_e('Scan QR Code', 'pn-customers-manager'); ?>
					</button>
					<button type="button" id="pn-cm-qr-complete-pending" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent">
						<?php esc_html_e('Complete Pending Orders', 'pn-customers-manager'); ?>
					</button>
				</div>

				<div class="pn-cm-qr-filters">
					<select id="pn-cm-qr-filter-product" class="pn-cm-qr-filter">
						<option value=""><?php esc_html_e('All Products', 'pn-customers-manager'); ?></option>
						<?php foreach ($qr_products as $qr_product): ?>
							<option value="<?php echo esc_attr($qr_product->ID); ?>" <?php selected($qr_product->ID, $product_id); ?>>
								<?php echo esc_html($qr_product->post_title); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select id="pn-cm-qr-filter-variation" class="pn-cm-qr-filter" style="display: none;">
						<option value=""><?php esc_html_e('All Variations', 'pn-customers-manager'); ?></option>
					</select>

					<select id="pn-cm-qr-filter-status" class="pn-cm-qr-filter">
						<option value=""><?php esc_html_e('All Status', 'pn-customers-manager'); ?></option>
						<option value="pending"><?php esc_html_e('Pending', 'pn-customers-manager'); ?></option>
						<option value="processed"><?php esc_html_e('Processed', 'pn-customers-manager'); ?></option>
					</select>

					<input type="text" id="pn-cm-qr-filter-search" class="pn-cm-qr-filter pn-cm-qr-search-input" placeholder="<?php esc_html_e('Search by customer, order, QR code...', 'pn-customers-manager'); ?>">
				</div>
			</div>

			<div id="pn-cm-qr-orders-wrapper" style="position: relative;">
				<div id="pn-cm-qr-orders-list" data-product-id="<?php echo esc_attr($product_id); ?>" data-variation-id="">
					<?php echo wp_kses_post($this->get_orders_html($product_id)); ?>
				</div>
				<div class="pn-cm-qr-loader pn-customers-manager-display-none">
					<div class="pn-customers-manager-loader-circle-wrapper">
						<div class="pn-customers-manager-text-align-center">
							<div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- QR Scanner Popup (outside admin panel for proper DOM access) -->
		<div id="pn-cm-qr-scanner-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
			<div class="pn-customers-manager-popup-overlay"></div>
			<div class="pn-customers-manager-popup-content">
				<h3 class="pn-customers-manager-text-align-center"><?php esc_html_e('Scan QR Code', 'pn-customers-manager'); ?></h3>
				<div id="pn-cm-qr-scanner-container" style="text-align: center; margin: 20px 0;">
					<!-- Scanner will be initialized here -->
				</div>
				<div style="text-align: center;">
					<button id="pn-cm-qr-switch-camera" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent" style="margin: 10px auto; display: none;">
						<i class="material-icons-outlined">switch_camera</i>
						<?php esc_html_e('Switch Camera', 'pn-customers-manager'); ?>
					</button>
				</div>
				<div id="pn-cm-qr-scanner-result" style="margin-top: 20px; text-align: center;"></div>
			</div>
		</div>

		<!-- Complete Pending Orders Popup -->
		<div id="pn-cm-qr-complete-pending-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-large pn-customers-manager-display-none-soft">
			<div class="pn-customers-manager-popup-content">
				<h3><?php esc_html_e('Complete Pending Orders with QR Products', 'pn-customers-manager'); ?></h3>
				<div id="pn-cm-qr-pending-orders-content">
					<div class="pn-customers-manager-text-align-center" style="padding: 40px;">
						<div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div>
					</div>
				</div>
			</div>
		</div>

		<!-- Popup Overlay (shared by all popups) -->
		<div class="pn-customers-manager-popup-overlay pn-customers-manager-display-none-soft"></div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_frontend_scripts() {
		if (!is_product()) return;

		global $post;
		if (!$post || get_post_meta($post->ID, '_pn_cm_woo_qr_enabled', true) !== 'yes') {
			return;
		}

		// Enqueue main plugin styles for button classes
		wp_enqueue_style('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], PN_CUSTOMERS_MANAGER_VERSION);
		wp_enqueue_style('wph-material-icons-outlined', PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css', [], PN_CUSTOMERS_MANAGER_VERSION);

		wp_enqueue_style(
			'pn-cm-qr-frontend',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-cm-qr.css',
			['pn-customers-manager'],
			PN_CUSTOMERS_MANAGER_VERSION
		);

		wp_enqueue_script(
			'pn-cm-qr-frontend',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-qr-frontend.js',
			['jquery'],
			PN_CUSTOMERS_MANAGER_VERSION,
			true
		);

		// Only load scanner for admins
		if (current_user_can('manage_woocommerce')) {
			// Enqueue plugin popup and AJAX scripts (dependencies)
			// Force enqueue these scripts even if Common class skipped them (non-CRM page)
			if (!wp_script_is('pn-customers-manager-popups', 'registered')) {
				wp_register_script('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
			}
			if (!wp_script_is('pn-customers-manager-popups', 'enqueued')) {
				wp_enqueue_script('pn-customers-manager-popups');
			}

			if (!wp_script_is('pn-customers-manager-ajax', 'registered')) {
				wp_register_script('pn-customers-manager-ajax', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-ajax.js', ['jquery', 'pn-customers-manager-popups'], PN_CUSTOMERS_MANAGER_VERSION, true);
			}
			if (!wp_script_is('pn-customers-manager-ajax', 'enqueued')) {
				wp_enqueue_script('pn-customers-manager-ajax');
			}

			// phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- QR scanner library loaded from CDN; no bundled alternative.
			wp_enqueue_script(
				'pn-cm-qr-scanner',
				'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
				[],
				'2.3.8',
				true
			);

			wp_enqueue_script(
				'pn-cm-qr-admin',
				PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-qr-admin.js',
				['jquery', 'pn-cm-qr-scanner', 'pn-customers-manager-popups', 'pn-customers-manager-ajax'],
				PN_CUSTOMERS_MANAGER_VERSION,
				true
			);

			// Localize AJAX script variables (required by pn-customers-manager-ajax.js)
			wp_localize_script('pn-customers-manager-ajax', 'pn_customers_manager_ajax', [
				'ajax_url' => admin_url('admin-ajax.php'),
				'pn_customers_manager_ajax_nonce' => wp_create_nonce('pn-customers-manager-nonce'),
			]);

			wp_localize_script('pn-customers-manager-ajax', 'pn_customers_manager_action', [
				'pn_customers_manager_get_nonce' => wp_create_nonce('pn-customers-manager-get-nonce'),
			]);

			// Localize QR script
			wp_localize_script('pn-cm-qr-admin', 'pnCmQrData', [
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'adminUrl' => admin_url(),
				'nonce' => wp_create_nonce('pn_cm_woo_qr_nonce'),
				'i18n' => [
					'order' => __('Order', 'pn-customers-manager'),
					'customer' => __('Customer', 'pn-customers-manager'),
					'products' => __('Products', 'pn-customers-manager'),
					'processed' => __('Processed', 'pn-customers-manager'),
					'pending' => __('Pending', 'pn-customers-manager'),
					'processedDate' => __('Date', 'pn-customers-manager'),
					'processedBy' => __('Processed by', 'pn-customers-manager'),
					'markProcessed' => __('Mark as Processed', 'pn-customers-manager'),
					'markPending' => __('Mark as Pending', 'pn-customers-manager'),
					'delete' => __('Delete', 'pn-customers-manager'),
					'processing' => __('Processing...', 'pn-customers-manager'),
					'scanSuccess' => __('QR Code scanned successfully', 'pn-customers-manager'),
					'scanError' => __('Error accessing camera', 'pn-customers-manager'),
					'error' => __('An error occurred', 'pn-customers-manager'),
					'confirmProcess' => __('Are you sure you want to mark this QR code as processed?', 'pn-customers-manager'),
					'confirmPending' => __('Are you sure you want to mark this QR code as pending?', 'pn-customers-manager'),
					'confirmDelete' => __('Are you sure you want to delete this QR code? This action cannot be undone.', 'pn-customers-manager'),
				],
			]);
		}
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts($hook) {
		if ('post.php' !== $hook && 'post-new.php' !== $hook) {
			return;
		}

		global $post;
		if (!$post || $post->post_type !== 'product') {
			return;
		}

		wp_enqueue_style(
			'pn-cm-qr-admin',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-cm-qr.css',
			[],
			PN_CUSTOMERS_MANAGER_VERSION
		);

		// Enqueue main plugin styles for icons
		wp_enqueue_style('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], PN_CUSTOMERS_MANAGER_VERSION);
		wp_enqueue_style('wph-material-icons-outlined', PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css', [], PN_CUSTOMERS_MANAGER_VERSION);

		// Enqueue QR scanner library
		// phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- QR scanner library loaded from CDN; no bundled alternative.
		wp_enqueue_script(
			'pn-cm-qr-scanner',
			'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
			[],
			'2.3.8',
			true
		);

		// Enqueue main pn-customers-manager script (required for main_message function)
		if (!wp_script_is('pn-customers-manager', 'registered')) {
			wp_register_script('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
		}
		if (!wp_script_is('pn-customers-manager', 'enqueued')) {
			wp_enqueue_script('pn-customers-manager');
		}

		// Localize main script
		if (!wp_script_is('pn-customers-manager', 'done')) {
			wp_localize_script('pn-customers-manager', 'pn_customers_manager_i18n', [
				'copied' => esc_html(__('Copied', 'pn-customers-manager')),
			]);
		}

		// Enqueue plugin popup and AJAX scripts (dependencies)
		if (!wp_script_is('pn-customers-manager-popups', 'registered')) {
			wp_register_script('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);
		}
		if (!wp_script_is('pn-customers-manager-popups', 'enqueued')) {
			wp_enqueue_script('pn-customers-manager-popups');
		}

		if (!wp_script_is('pn-customers-manager-ajax', 'registered')) {
			wp_register_script('pn-customers-manager-ajax', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-ajax.js', ['jquery', 'pn-customers-manager-popups'], PN_CUSTOMERS_MANAGER_VERSION, true);
		}
		if (!wp_script_is('pn-customers-manager-ajax', 'enqueued')) {
			wp_enqueue_script('pn-customers-manager-ajax');
		}

		// Enqueue admin QR script
		wp_enqueue_script(
			'pn-cm-qr-admin',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-qr-admin.js',
			['jquery', 'pn-cm-qr-scanner', 'pn-customers-manager', 'pn-customers-manager-popups', 'pn-customers-manager-ajax'],
			PN_CUSTOMERS_MANAGER_VERSION,
			true
		);

		// Localize AJAX script variables (required by pn-customers-manager-ajax.js)
		wp_localize_script('pn-customers-manager-ajax', 'pn_customers_manager_ajax', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'pn_customers_manager_ajax_nonce' => wp_create_nonce('pn-customers-manager-nonce'),
		]);

		wp_localize_script('pn-customers-manager-ajax', 'pn_customers_manager_action', [
			'pn_customers_manager_get_nonce' => wp_create_nonce('pn-customers-manager-get-nonce'),
		]);

		// Localize QR script
		wp_localize_script('pn-cm-qr-admin', 'pnCmQrData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'adminUrl' => admin_url(),
			'nonce' => wp_create_nonce('pn_cm_woo_qr_nonce'),
			'i18n' => [
				'order' => __('Order', 'pn-customers-manager'),
				'customer' => __('Customer', 'pn-customers-manager'),
				'products' => __('Products', 'pn-customers-manager'),
				'processed' => __('Processed', 'pn-customers-manager'),
				'pending' => __('Pending', 'pn-customers-manager'),
				'processedDate' => __('Date', 'pn-customers-manager'),
				'processedBy' => __('Processed by', 'pn-customers-manager'),
				'markProcessed' => __('Mark as Processed', 'pn-customers-manager'),
				'markPending' => __('Mark as Pending', 'pn-customers-manager'),
				'delete' => __('Delete', 'pn-customers-manager'),
				'processing' => __('Processing...', 'pn-customers-manager'),
				'scanSuccess' => __('QR Code scanned successfully', 'pn-customers-manager'),
				'scanError' => __('Error accessing camera', 'pn-customers-manager'),
				'error' => __('An error occurred', 'pn-customers-manager'),
				'confirmProcess' => __('Are you sure you want to mark this QR code as processed?', 'pn-customers-manager'),
				'confirmPending' => __('Are you sure you want to mark this QR code as pending?', 'pn-customers-manager'),
				'confirmDelete' => __('Are you sure you want to delete this QR code? This action cannot be undone.', 'pn-customers-manager'),
				'selected' => __('selected', 'pn-customers-manager'),
				'selectedPlural' => __('selected', 'pn-customers-manager'),
				/* translators: %d is the number of selected QR codes */
				'confirmBulkProcess' => __('Are you sure you want to mark %d QR codes as processed?', 'pn-customers-manager'),
				/* translators: %d is the number of selected QR codes */
				'confirmBulkPending' => __('Are you sure you want to mark %d QR codes as pending?', 'pn-customers-manager'),
				/* translators: %d is the number of selected QR codes */
				'confirmBulkDelete' => __('Are you sure you want to delete %d QR codes? This action cannot be undone.', 'pn-customers-manager'),
			],
		]);
	}

	/**
	 * AJAX: Get pending orders with QR products
	 */
	public function ajax_get_pending_orders() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Unauthorized', 'pn-customers-manager')]);
		}

		// Get all products with QR enabled
		$qr_product_ids = get_posts([
			'post_type' => 'product',
			'posts_per_page' => -1,
			'meta_key' => '_pn_cm_woo_qr_enabled',
			'meta_value' => 'yes',
			'fields' => 'ids',
		]);

		if (empty($qr_product_ids)) {
			wp_send_json_success([
				'html' => '<div class="pn-customers-manager-text-align-center" style="padding: 40px;"><p>' . __('No products with QR enabled found', 'pn-customers-manager') . '</p></div>',
				'count' => 0
			]);
		}

		// Get pending orders that contain QR products
		$args = [
			'status' => ['pending', 'on-hold', 'processing'],
			'limit' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
		];

		$orders = wc_get_orders($args);
		$pending_orders = [];

		foreach ($orders as $order) {
			// Check if order has QR products
			$has_qr_product = false;
			$qr_products_in_order = [];

			foreach ($order->get_items() as $item) {
				$product_id = $item->get_product_id();
				if (in_array($product_id, $qr_product_ids)) {
					$has_qr_product = true;
					$product = $item->get_product();
					$qr_products_in_order[] = [
						'name' => $item->get_name(),
						'qty' => $item->get_quantity(),
					];
				}
			}

			// Only include orders with status different from 'completed'
			if ($has_qr_product && $order->get_status() !== 'completed') {
				$pending_orders[] = [
					'id' => $order->get_id(),
					'number' => $order->get_order_number(),
					'status' => $order->get_status(),
					'date' => $order->get_date_created()->date('Y-m-d H:i'),
					'total' => $order->get_formatted_order_total(),
					'payment_method' => $order->get_payment_method_title(),
					'customer_name' => $order->get_formatted_billing_full_name(),
					'customer_email' => $order->get_billing_email(),
					'products' => $qr_products_in_order,
				];
			}
		}

		if (empty($pending_orders)) {
			$html = '<div class="pn-customers-manager-text-align-center" style="padding: 40px;">';
			$html .= '<p>' . __('No pending orders found with QR products', 'pn-customers-manager') . '</p>';
			$html .= '</div>';

			wp_send_json_success([
				'html' => $html,
				'count' => 0
			]);
		}

		// Build HTML
		$html = '<div style="margin-bottom: 20px; padding: 15px; background: #e8f5e9; border-left: 4px solid #4caf50;">';
		$html .= '<strong>' . sprintf(/* translators: %d is the number of pending orders */ __('%d pending orders found with QR products', 'pn-customers-manager'), count($pending_orders)) . '</strong>';
		$html .= '</div>';

		$html .= '<div style="margin-bottom: 15px; text-align: right;">';
		$html .= '<button type="button" id="pn-cm-qr-select-all" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent">' . __('Select All', 'pn-customers-manager') . '</button>';
		$html .= '<button type="button" id="pn-cm-qr-deselect-all" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent" style="margin-left: 5px;">' . __('Deselect All', 'pn-customers-manager') . '</button>';
		$html .= '</div>';

		$html .= '<div style="max-height: 500px; overflow-y: auto; margin-bottom: 20px;">';
		$html .= '<table class="pn-cm-qr-pending-orders-table" style="width: 100%; border-collapse: collapse;">';
		$html .= '<thead>';
		$html .= '<tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">';
		$html .= '<th style="padding: 10px; text-align: left; width: 40px;"></th>';
		$html .= '<th style="padding: 10px; text-align: left;">' . __('Order', 'pn-customers-manager') . '</th>';
		$html .= '<th style="padding: 10px; text-align: left;">' . __('Customer', 'pn-customers-manager') . '</th>';
		$html .= '<th style="padding: 10px; text-align: left;">' . __('Payment Method', 'pn-customers-manager') . '</th>';
		$html .= '<th style="padding: 10px; text-align: left;">' . __('Products with QR', 'pn-customers-manager') . '</th>';
		$html .= '<th style="padding: 10px; text-align: left;">' . __('Status', 'pn-customers-manager') . '</th>';
		$html .= '<th style="padding: 10px; text-align: right;">' . __('Total', 'pn-customers-manager') . '</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		foreach ($pending_orders as $po) {
			$html .= '<tr style="border-bottom: 1px solid #eee;" class="pn-cm-qr-pending-order-row">';
			$html .= '<td style="padding: 10px;"><input type="checkbox" class="pn-cm-qr-order-checkbox" value="' . esc_attr($po['id']) . '" checked></td>';
			$html .= '<td style="padding: 10px;"><a href="' . admin_url('post.php?post=' . $po['id'] . '&action=edit') . '" target="_blank"><strong>#' . esc_html($po['number']) . '</strong></a><br><small>' . esc_html($po['date']) . '</small></td>';
			$html .= '<td style="padding: 10px;">' . esc_html($po['customer_name']) . '<br><small>' . esc_html($po['customer_email']) . '</small></td>';
			$html .= '<td style="padding: 10px;">' . esc_html($po['payment_method'] ?: __('N/A', 'pn-customers-manager')) . '</td>';
			$html .= '<td style="padding: 10px;">';
			foreach ($po['products'] as $prod) {
				$html .= '• ' . esc_html($prod['name']) . ' x' . $prod['qty'] . '<br>';
			}
			$html .= '</td>';
			$html .= '<td style="padding: 10px;"><span class="pn-cm-order-status-badge pn-cm-order-status-' . esc_attr($po['status']) . '">' . esc_html(wc_get_order_status_name($po['status'])) . '</span></td>';
			$html .= '<td style="padding: 10px; text-align: right;"><strong>' . $po['total'] . '</strong></td>';
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';

		$html .= '<div class="pn-customers-manager-text-align-center" style="padding-top: 20px; border-top: 2px solid #ddd;">';
		$html .= '<button type="button" id="pn-cm-qr-complete-selected-orders" class="pn-customers-manager-btn pn-customers-manager-btn-blue">';
		$html .= __('Complete Selected Orders', 'pn-customers-manager');
		$html .= '</button>';
		$html .= '</div>';

		wp_send_json_success([
			'html' => $html,
			'count' => count($pending_orders)
		]);
	}

	/**
	 * AJAX: Complete pending orders
	 */
	public function ajax_complete_pending_orders() {
		check_ajax_referer('pn_cm_woo_qr_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('Unauthorized', 'pn-customers-manager')]);
		}

		$order_ids = isset($_POST['order_ids']) ? array_map('intval', $_POST['order_ids']) : [];

		if (empty($order_ids)) {
			wp_send_json_error(['message' => __('No orders selected', 'pn-customers-manager')]);
		}

		$completed = 0;
		$errors = [];

		foreach ($order_ids as $order_id) {
			$order = wc_get_order($order_id);
			if (!$order) {
				$errors[] = sprintf(/* translators: %d is the order ID */ __('Order #%d not found', 'pn-customers-manager'), $order_id);
				continue;
			}

			// Change order status to completed
			$order->update_status('completed', __('Order completed via QR Manager bulk action', 'pn-customers-manager'));
			$completed++;
		}

		if ($completed > 0) {
			wp_send_json_success([
				'message' => /* translators: %d is the number of orders completed */ sprintf(__('%d orders completed successfully', 'pn-customers-manager'), $completed),
				'completed' => $completed,
				'errors' => $errors
			]);
		} else {
			wp_send_json_error([
				'message' => __('No orders were completed', 'pn-customers-manager'),
				'errors' => $errors
			]);
		}
	}
}
