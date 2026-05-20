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

		// AJAX handlers
		add_action('wp_ajax_pn_cm_woo_qr_process', [$this, 'ajax_process_qr']);
		add_action('wp_ajax_pn_cm_woo_qr_mark_pending', [$this, 'ajax_mark_pending']);
		add_action('wp_ajax_pn_cm_woo_qr_delete', [$this, 'ajax_delete_qr']);
		add_action('wp_ajax_pn_cm_woo_qr_get_orders', [$this, 'ajax_get_orders']);
		add_action('wp_ajax_pn_cm_woo_qr_view', [$this, 'ajax_qr_view']);

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
					<h4 style="padding: 0 12px;"><?php _e('QR Statistics', 'pn-customers-manager'); ?></h4>
					<p class="form-field" style="padding: 0 12px;">
						<strong><?php _e('Total QR Codes Generated:', 'pn-customers-manager'); ?></strong>
						<?php echo count($orders_data); ?><br>
						<strong><?php _e('Processed:', 'pn-customers-manager'); ?></strong>
						<?php echo count(array_filter($orders_data, function($o) { return !empty($o['processed']); })); ?><br>
						<strong><?php _e('Pending:', 'pn-customers-manager'); ?></strong>
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
			<h2><?php _e('Your QR Tickets', 'pn-customers-manager'); ?></h2>
			<p><?php echo wp_kses_post($instructions); ?></p>

			<div class="pn-cm-qr-user-codes">
				<?php foreach ($user_qrs as $qr_data): ?>
					<div class="pn-cm-qr-user-code <?php echo $qr_data['processed'] ? 'processed' : 'pending'; ?>">
						<div class="pn-cm-qr-code-image">
							<?php echo $this->generate_qr_svg($qr_data['code']); ?>
						</div>
						<div class="pn-cm-qr-code-info">
							<p class="pn-cm-qr-code-string"><?php echo esc_html($qr_data['code']); ?></p>
							<p class="pn-cm-qr-code-order"><?php printf(__('Order #%s', 'pn-customers-manager'), $qr_data['order_id']); ?></p>
							<p class="pn-cm-qr-code-quantity"><?php printf(__('Quantity: %d', 'pn-customers-manager'), $qr_data['quantity']); ?></p>
							<?php if ($qr_data['processed']): ?>
								<p class="pn-cm-qr-status processed">
									<i class="material-icons-outlined">check_circle</i>
									<?php _e('Processed', 'pn-customers-manager'); ?>
								</p>
							<?php else: ?>
								<p class="pn-cm-qr-status pending">
									<i class="material-icons-outlined">schedule</i>
									<?php _e('Pending', 'pn-customers-manager'); ?>
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
			<h2><?php _e('QR Tickets Management', 'pn-customers-manager'); ?></h2>

			<div class="pn-cm-qr-admin-controls">
				<div class="pn-cm-qr-filters">
					<select id="pn-cm-qr-filter-product" class="pn-cm-qr-filter">
						<option value=""><?php _e('All Products', 'pn-customers-manager'); ?></option>
						<?php foreach ($qr_products as $qr_product): ?>
							<option value="<?php echo esc_attr($qr_product->ID); ?>" <?php selected($qr_product->ID, $product_id); ?>>
								<?php echo esc_html($qr_product->post_title); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select id="pn-cm-qr-filter-status" class="pn-cm-qr-filter">
						<option value=""><?php _e('All Status', 'pn-customers-manager'); ?></option>
						<option value="pending"><?php _e('Pending', 'pn-customers-manager'); ?></option>
						<option value="processed"><?php _e('Processed', 'pn-customers-manager'); ?></option>
					</select>
				</div>

				<div class="pn-cm-qr-actions-btns">
					<button type="button" id="pn-cm-qr-scan" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent">
						<?php _e('Scan QR Code', 'pn-customers-manager'); ?>
					</button>
				</div>
			</div>

			<div id="pn-cm-qr-orders-list" data-product-id="<?php echo esc_attr($product_id); ?>">
				<?php echo $this->get_orders_html($product_id); ?>
			</div>
		</div>

		<!-- QR Scanner Popup (outside admin panel for proper DOM access) -->
		<div id="pn-cm-qr-scanner-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
			<div class="pn-customers-manager-popup-overlay"></div>
			<div class="pn-customers-manager-popup-content">
				<h3 class="pn-customers-manager-text-align-center"><?php _e('Scan QR Code', 'pn-customers-manager'); ?></h3>
				<div id="pn-cm-qr-scanner-container" style="text-align: center; margin: 20px 0;">
					<!-- Scanner will be initialized here -->
				</div>
				<div style="text-align: center;">
					<button id="pn-cm-qr-switch-camera" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent" style="margin: 10px auto; display: none;">
						<i class="material-icons-outlined">switch_camera</i>
						<?php _e('Switch Camera', 'pn-customers-manager'); ?>
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
	private function get_orders_html($product_id = null, $status = '') {
		$orders_data = $this->get_product_orders($product_id, $status);

		if (empty($orders_data)) {
			return '<p class="pn-cm-qr-no-orders">' . __('No QR tickets generated yet', 'pn-customers-manager') . '</p>';
		}

		ob_start();
		?>
		<div class="pn-cm-qr-orders-wrapper">
		<table class="pn-cm-qr-orders-table">
			<thead>
				<tr>
					<th><?php _e('Customer', 'pn-customers-manager'); ?></th>
					<th><?php _e('Products', 'pn-customers-manager'); ?></th>
					<th><?php _e('Status', 'pn-customers-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($orders_data as $data): ?>
					<tr class="pn-cm-qr-row <?php echo $data['processed'] ? 'processed' : 'pending'; ?>" style="position: relative;">
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
								<?php foreach ($data['order_items'] as $item): ?>
									<div class="pn-cm-qr-product-item">
										<span class="pn-cm-qr-product-name"><?php echo esc_html($item['name']); ?></span>
										<span class="pn-cm-qr-product-qty">x<?php echo esc_html($item['quantity']); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</td>
						<td>
							<?php if ($data['processed']): ?>
								<span class="pn-cm-qr-badge processed">
									<i class="material-icons-outlined">check_circle</i>
									<span class="pn-cm-qr-badge-text"><?php _e('Processed', 'pn-customers-manager'); ?></span>
								</span>
							<?php else: ?>
								<span class="pn-cm-qr-badge pending">
									<i class="material-icons-outlined">schedule</i>
									<span class="pn-cm-qr-badge-text"><?php _e('Pending', 'pn-customers-manager'); ?></span>
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
	private function get_product_orders($product_id = null, $status = '') {
		global $wpdb;

		// Build query with optional filters
		$where_clauses = [];
		$prepare_values = [];

		// Filter by product if specified
		if ($product_id) {
			$where_clauses[] = 'oim_product.meta_value = %d';
			$prepare_values[] = $product_id;
		}

		// Filter by status if specified
		if ($status === 'processed') {
			$where_clauses[] = "oim_processed.meta_value = 'yes'";
		} elseif ($status === 'pending') {
			$where_clauses[] = "(oim_processed.meta_value IS NULL OR oim_processed.meta_value != 'yes')";
		}

		$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

		$query = "
			SELECT
				oi.order_item_id,
				oi.order_id,
				oim_qr.meta_value as qr_code,
				oim_processed.meta_value as processed,
				oim_date.meta_value as processed_date,
				oim_by.meta_value as processed_by,
				oim_qty.meta_value as quantity,
				oim_product.meta_value as product_id
			FROM {$wpdb->prefix}woocommerce_order_items oi
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_product
				ON oi.order_item_id = oim_product.order_item_id
				AND oim_product.meta_key = '_product_id'
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qr
				ON oi.order_item_id = oim_qr.order_item_id
				AND oim_qr.meta_key = '_pn_cm_woo_qr_code'
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
			$results = $wpdb->get_results($wpdb->prepare($query, ...$prepare_values));
		} else {
			$results = $wpdb->get_results($query);
		}

		$orders_data = [];
		foreach ($results as $row) {
			$order = wc_get_order($row->order_id);
			if (!$order) continue;

			// Get all items in order for product breakdown
			$order_items = [];
			foreach ($order->get_items() as $item) {
				$order_items[] = [
					'name' => $item->get_name(),
					'quantity' => $item->get_quantity(),
				];
			}

			$orders_data[] = [
				'item_id' => $row->order_item_id,
				'order_id' => $row->order_id,
				'code' => $row->qr_code,
				'processed' => $row->processed === 'yes',
				'processed_date' => $row->processed_date,
				'processed_by' => $row->processed_by,
				'quantity' => $row->quantity ?: 1,
				'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'order_items' => $order_items,
				'order_date' => $order->get_date_created()->date('Y-m-d H:i'),
			];
		}

		return $orders_data;
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
			<h2 style="margin-top: 0; color: #333;"><?php _e('Your QR Tickets', 'pn-customers-manager'); ?></h2>

			<?php foreach ($qr_items as $qr_item): ?>
				<div style="margin-bottom: 30px; padding: 20px; background: white; border-radius: 8px;">
					<h3 style="margin-top: 0; color: #667eea;"><?php echo esc_html($qr_item['name']); ?></h3>
					<?php if (!empty($qr_item['instructions'])): ?>
						<p style="margin: 10px 0;"><?php echo wp_kses_post($qr_item['instructions']); ?></p>
					<?php endif; ?>

					<div style="text-align: center; margin: 20px 0;">
						<?php echo $this->generate_qr_svg($qr_item['code'], 200); ?>
					</div>

					<p style="text-align: center; font-family: monospace; font-size: 16px; font-weight: bold; color: #333;">
						<?php echo esc_html($qr_item['code']); ?>
					</p>

					<p style="text-align: center; color: #6c757d;">
						<?php printf(__('Quantity: %d', 'pn-customers-manager'), $qr_item['quantity']); ?>
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
			<h2><?php _e('Your QR Tickets', 'pn-customers-manager'); ?></h2>

			<?php foreach ($qr_items as $qr_item): ?>
				<div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
					<h3 style="margin-top: 0; color: #667eea;"><?php echo esc_html($qr_item['name']); ?></h3>
					<?php if (!empty($qr_item['instructions'])): ?>
						<p><?php echo wp_kses_post($qr_item['instructions']); ?></p>
					<?php endif; ?>

					<div style="text-align: center; margin: 20px 0;">
						<?php echo $this->generate_qr_svg($qr_item['code'], 200); ?>
					</div>

					<p style="text-align: center; font-family: monospace; font-size: 16px; font-weight: bold;">
						<?php echo esc_html($qr_item['code']); ?>
					</p>

					<p style="text-align: center;">
						<?php printf(__('Quantity: %d', 'pn-customers-manager'), $qr_item['quantity']); ?>
					</p>

					<?php if ($qr_item['processed']): ?>
						<p style="text-align: center; color: #28a745; font-weight: bold;">
							<?php _e('✓ Processed', 'pn-customers-manager'); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</section>
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

		$html = $this->get_orders_html($product_id, $status);

		wp_send_json_success(['html' => $html]);
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
		echo $html;
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
				oim_qr.meta_value as qr_code,
				oim_processed.meta_value as processed,
				oim_date.meta_value as processed_date,
				oim_by.meta_value as processed_by,
				oim_qty.meta_value as quantity,
				oim_product.meta_value as product_id
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

		// Get all items in order
		$order_items = [];
		foreach ($order->get_items() as $item) {
			$order_items[] = [
				'name' => $item->get_name(),
				'quantity' => $item->get_quantity(),
			];
		}

		ob_start();
		?>
		<div class="pn-cm-qr-view pn-customers-manager-p-30">
			<h4 class="pn-customers-manager-text-align-center"><?php _e('QR Ticket Details', 'pn-customers-manager'); ?></h4>

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
					<p><strong><?php _e('Order', 'pn-customers-manager'); ?>:</strong> #<?php echo esc_html($item_data->order_id); ?></p>
					<p><strong><?php _e('Customer', 'pn-customers-manager'); ?>:</strong> <?php echo esc_html($customer); ?></p>
					<p><strong><?php _e('Date', 'pn-customers-manager'); ?>:</strong> <?php echo esc_html($order_date); ?></p>

					<p><strong><?php _e('Products', 'pn-customers-manager'); ?>:</strong></p>
					<ul class="pn-cm-qr-products-list" style="margin: 10px 0; padding-left: 20px;">
						<?php foreach ($order_items as $item): ?>
							<li style="margin: 5px 0; color: #333;">
								<?php echo esc_html($item['name']); ?>
								<span class="pn-cm-qr-qty" style="color: #6c757d; font-weight: 600; margin-left: 5px;">x<?php echo esc_html($item['quantity']); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>

					<?php if ($processed): ?>
						<div class="pn-cm-qr-processed-info" style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 8px; border-left: 4px solid #28a745;">
							<p class="pn-cm-qr-status-processed" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 16px; margin: 0; color: #155724;">
								<i class="material-icons-outlined" style="font-size: 20px;">check_circle</i>
								<?php _e('Processed', 'pn-customers-manager'); ?>
							</p>
							<?php if ($item_data->processed_by): ?>
								<p style="margin: 10px 0 0; font-size: 14px; color: #155724;">
									<?php printf(__('By: %s', 'pn-customers-manager'), esc_html($item_data->processed_by)); ?>
								</p>
							<?php endif; ?>
							<?php if ($item_data->processed_date): ?>
								<p style="margin: 5px 0 0; font-size: 14px; color: #155724;">
									<?php printf(__('On: %s', 'pn-customers-manager'), esc_html($item_data->processed_date)); ?>
								</p>
							<?php endif; ?>
						</div>
					<?php else: ?>
						<div class="pn-cm-qr-status-info" style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
							<p class="pn-cm-qr-status-pending" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 16px; margin: 0; color: #856404;">
								<i class="material-icons-outlined" style="font-size: 20px;">schedule</i>
								<?php _e('Pending', 'pn-customers-manager'); ?>
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

		wp_enqueue_script('pn-cm-qr-scanner', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', [], '2.3.8', true);
		wp_enqueue_script('pn-cm-qr-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-qr-admin.js', ['jquery', 'pn-cm-qr-scanner', 'pn-customers-manager-popups', 'pn-customers-manager-ajax'], PN_CUSTOMERS_MANAGER_VERSION, true);

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
			<h2><?php _e('QR Tickets Management', 'pn-customers-manager'); ?></h2>

			<div class="pn-cm-qr-admin-controls">
				<div class="pn-cm-qr-filters">
					<select id="pn-cm-qr-filter-product" class="pn-cm-qr-filter">
						<option value=""><?php _e('All Products', 'pn-customers-manager'); ?></option>
						<?php foreach ($qr_products as $qr_product): ?>
							<option value="<?php echo esc_attr($qr_product->ID); ?>" <?php selected($qr_product->ID, $product_id); ?>>
								<?php echo esc_html($qr_product->post_title); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select id="pn-cm-qr-filter-status" class="pn-cm-qr-filter">
						<option value=""><?php _e('All Status', 'pn-customers-manager'); ?></option>
						<option value="pending"><?php _e('Pending', 'pn-customers-manager'); ?></option>
						<option value="processed"><?php _e('Processed', 'pn-customers-manager'); ?></option>
					</select>
				</div>

				<div class="pn-cm-qr-actions-btns">
					<button type="button" id="pn-cm-qr-scan" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent">
						<?php _e('Scan QR Code', 'pn-customers-manager'); ?>
					</button>
				</div>
			</div>

			<div id="pn-cm-qr-orders-list" data-product-id="<?php echo esc_attr($product_id); ?>">
				<?php echo $this->get_orders_html($product_id); ?>
			</div>
		</div>

		<!-- QR Scanner Popup (outside admin panel for proper DOM access) -->
		<div id="pn-cm-qr-scanner-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
			<div class="pn-customers-manager-popup-overlay"></div>
			<div class="pn-customers-manager-popup-content">
				<h3 class="pn-customers-manager-text-align-center"><?php _e('Scan QR Code', 'pn-customers-manager'); ?></h3>
				<div id="pn-cm-qr-scanner-container" style="text-align: center; margin: 20px 0;">
					<!-- Scanner will be initialized here -->
				</div>
				<div style="text-align: center;">
					<button id="pn-cm-qr-switch-camera" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent" style="margin: 10px auto; display: none;">
						<i class="material-icons-outlined">switch_camera</i>
						<?php _e('Switch Camera', 'pn-customers-manager'); ?>
					</button>
				</div>
				<div id="pn-cm-qr-scanner-result" style="margin-top: 20px; text-align: center;"></div>
			</div>
		</div>
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
		wp_enqueue_script(
			'pn-cm-qr-scanner',
			'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
			[],
			'2.3.8',
			true
		);

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
