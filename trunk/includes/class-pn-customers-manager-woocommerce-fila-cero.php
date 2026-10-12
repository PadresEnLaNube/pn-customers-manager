<?php
/**
 * WooCommerce Fila Cero – Solidarity Purchase System
 *
 * Allows visitors to make a solidarity purchase that does not reduce stock.
 * The buyer's name is displayed on the product page.
 *
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce_Fila_Cero {

	private static $instance = null;

	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Product editor tab & panel
		add_filter('woocommerce_product_data_tabs', [$this, 'product_data_tab']);
		add_action('woocommerce_product_data_panels', [$this, 'product_data_panel']);
		add_action('woocommerce_process_product_meta', [$this, 'save_product_meta']);

		// Frontend display
		add_action('woocommerce_single_product_summary', [$this, 'render_fila_cero'], 32);

		// Cart integration
		add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 2);
		add_filter('woocommerce_get_item_data', [$this, 'get_item_data'], 10, 2);

		// Order integration
		add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_item_meta'], 10, 4);
		add_action('woocommerce_order_status_processing', [$this, 'record_fila_cero']);
		add_action('woocommerce_order_status_completed', [$this, 'record_fila_cero']);
		add_action('woocommerce_order_status_cancelled', [$this, 'remove_fila_cero']);
		add_action('woocommerce_order_status_refunded', [$this, 'remove_fila_cero']);

		// Also hide when an order is trashed or permanently deleted
		add_action('trashed_post', [$this, 'remove_fila_cero_on_delete']);
		add_action('before_delete_post', [$this, 'remove_fila_cero_on_delete']);
		add_action('woocommerce_trash_order', [$this, 'remove_fila_cero']);
		add_action('woocommerce_delete_order', [$this, 'remove_fila_cero']);

		// Prevent stock reduction for fila cero items
		add_filter('woocommerce_order_item_quantity', [$this, 'zero_fila_cero_qty'], 10, 3);

		// Show fila cero meta in order details, emails, and admin
		add_filter('woocommerce_order_item_get_formatted_meta_data', [$this, 'format_order_item_meta'], 10, 2);

		// AJAX for admin delete
		add_action('wp_ajax_pn_cm_woo_fila_cero_delete', [__CLASS__, 'ajax_delete_entry']);
	}

	/* ──────────────────────────────────────────────
	 * Product editor – Tab
	 * ──────────────────────────────────────────── */

	public function product_data_tab($tabs) {
		$tabs['pn_cm_woo_fila_cero'] = [
			'label'    => __('Fila Cero', 'pn-customers-manager'),
			'target'   => 'pn_cm_woo_fila_cero_data',
			'class'    => ['show_if_simple', 'show_if_variable'],
			'priority' => 81,
		];
		return $tabs;
	}

	/* ──────────────────────────────────────────────
	 * Product editor – Panel
	 * ──────────────────────────────────────────── */

	public function product_data_panel() {
		global $post, $wpdb;

		$product_id = $post->ID;
		$enabled    = get_post_meta($product_id, '_pn_cm_woo_fila_cero_enabled', true);
		$heading    = get_post_meta($product_id, '_pn_cm_woo_fila_cero_heading', true);
		$desc       = get_post_meta($product_id, '_pn_cm_woo_fila_cero_description', true);
		$btn_text   = get_post_meta($product_id, '_pn_cm_woo_fila_cero_button_text', true);
		$names_head = get_post_meta($product_id, '_pn_cm_woo_fila_cero_names_heading', true);

		$total = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}pn_cm_woo_fila_cero WHERE product_id = %d AND status = 'active'",
			$product_id
		));

		echo '<div id="pn_cm_woo_fila_cero_data" class="panel woocommerce_options_panel">';

		woocommerce_wp_checkbox([
			'id'          => '_pn_cm_woo_fila_cero_enabled',
			'label'       => __('Enable Fila Cero', 'pn-customers-manager'),
			'description' => __('Allow solidarity purchases on this product (does not reduce stock).', 'pn-customers-manager'),
			'value'       => $enabled,
		]);

		woocommerce_wp_text_input([
			'id'          => '_pn_cm_woo_fila_cero_heading',
			'label'       => __('Heading', 'pn-customers-manager'),
			'placeholder' => __('Fila Cero — Solidarity Purchase', 'pn-customers-manager'),
			'value'       => $heading,
		]);

		woocommerce_wp_textarea_input([
			'id'          => '_pn_cm_woo_fila_cero_description',
			'label'       => __('Description', 'pn-customers-manager'),
			'placeholder' => __('Buy this product as a solidarity gesture. Your name will appear on this page. Stock is not reduced.', 'pn-customers-manager'),
			'value'       => $desc,
		]);

		woocommerce_wp_text_input([
			'id'          => '_pn_cm_woo_fila_cero_button_text',
			'label'       => __('Button Text', 'pn-customers-manager'),
			'placeholder' => __('Add solidarity purchase', 'pn-customers-manager'),
			'value'       => $btn_text,
		]);

		woocommerce_wp_text_input([
			'id'          => '_pn_cm_woo_fila_cero_names_heading',
			'label'       => __('Names Section Heading', 'pn-customers-manager'),
			'placeholder' => __('Solidarity buyers', 'pn-customers-manager'),
			'value'       => $names_head,
		]);

		if ($total > 0) {
			echo '<p class="form-field"><strong>' . sprintf(__('Total solidarity purchases: %d', 'pn-customers-manager'), $total) . '</strong></p>';
		}

		echo '</div>';
	}

	/* ──────────────────────────────────────────────
	 * Product editor – Save meta
	 * ──────────────────────────────────────────── */

	public function save_product_meta($product_id) {
		$enabled = isset($_POST['_pn_cm_woo_fila_cero_enabled']) ? 'yes' : 'no';
		update_post_meta($product_id, '_pn_cm_woo_fila_cero_enabled', $enabled);

		if (isset($_POST['_pn_cm_woo_fila_cero_heading'])) {
			update_post_meta($product_id, '_pn_cm_woo_fila_cero_heading', sanitize_text_field($_POST['_pn_cm_woo_fila_cero_heading']));
		}
		if (isset($_POST['_pn_cm_woo_fila_cero_description'])) {
			update_post_meta($product_id, '_pn_cm_woo_fila_cero_description', sanitize_textarea_field($_POST['_pn_cm_woo_fila_cero_description']));
		}
		if (isset($_POST['_pn_cm_woo_fila_cero_button_text'])) {
			update_post_meta($product_id, '_pn_cm_woo_fila_cero_button_text', sanitize_text_field($_POST['_pn_cm_woo_fila_cero_button_text']));
		}
		if (isset($_POST['_pn_cm_woo_fila_cero_names_heading'])) {
			update_post_meta($product_id, '_pn_cm_woo_fila_cero_names_heading', sanitize_text_field($_POST['_pn_cm_woo_fila_cero_names_heading']));
		}
	}

	/* ──────────────────────────────────────────────
	 * Frontend – Render block
	 * ──────────────────────────────────────────── */

	public function render_fila_cero() {
		global $product;
		if (!$product || !is_a($product, 'WC_Product')) return;

		$product_id = $product->get_id();
		$enabled    = get_post_meta($product_id, '_pn_cm_woo_fila_cero_enabled', true);
		if ($enabled !== 'yes') return;

		$heading    = get_post_meta($product_id, '_pn_cm_woo_fila_cero_heading', true);
		$desc       = get_post_meta($product_id, '_pn_cm_woo_fila_cero_description', true);
		$btn_text   = get_post_meta($product_id, '_pn_cm_woo_fila_cero_button_text', true);
		$names_head = get_post_meta($product_id, '_pn_cm_woo_fila_cero_names_heading', true);

		if (empty($heading))    $heading    = __('Fila Cero — Solidarity Purchase', 'pn-customers-manager');
		if (empty($desc))       $desc       = __('Buy this product as a solidarity gesture. Your name will appear on this page. Stock is not reduced.', 'pn-customers-manager');
		if (empty($btn_text))   $btn_text   = __('Add solidarity purchase', 'pn-customers-manager');
		if (empty($names_head)) $names_head = __('Solidarity buyers', 'pn-customers-manager');

		$entries = self::get_entries($product_id);
		$is_admin = current_user_can('manage_options');

		// Enqueue CSS
		wp_enqueue_style(
			'pn-cm-fc-styles',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css',
			[],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0'
		);

		if ($is_admin && !wp_style_is('wph-material-icons-outlined', 'enqueued')) {
			wp_enqueue_style(
				'wph-material-icons-outlined',
				PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css',
				[],
				defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0'
			);
		}

		// Corporate identity colors — inject as CSS custom properties so global
		// classes (pn-customers-manager-btn, pn-customers-manager-input) work
		// on WooCommerce product pages outside the CRM context.
		$corporate_colors = [
			'color_main'        => get_option('pn_customers_manager_color_main', '#0000aa'),
			'bg_color_main'     => get_option('pn_customers_manager_bg_color_main', '#0000aa'),
			'border_color_main' => get_option('pn_customers_manager_border_color_main', '#0000aa'),
			'color_main_alt'    => get_option('pn_customers_manager_color_main_alt', '#232323'),
			'bg_color_main_alt' => get_option('pn_customers_manager_bg_color_main_alt', '#232323'),
		];
		$css_vars = '';
		foreach ($corporate_colors as $key => $val) {
			$sanitized = sanitize_hex_color($val);
			if (!$sanitized) {
				$defaults = ['color_main' => '#0000aa', 'bg_color_main' => '#0000aa', 'border_color_main' => '#0000aa', 'color_main_alt' => '#232323', 'bg_color_main_alt' => '#232323'];
				$sanitized = $defaults[$key];
			}
			$css_vars .= '--pn-customers-manager-' . str_replace('_', '-', $key) . ':' . esc_attr($sanitized) . ';';
		}

		echo '<div class="pn-cm-fc" style="' . $css_vars . '">';

		// ── Heading & description ──
		echo '<h4 class="pn-cm-fc__heading">' . esc_html($heading) . '</h4>';
		echo '<p class="pn-cm-fc__description">' . esc_html($desc) . '</p>';

		// ── Add-to-cart form ──
		echo '<form class="pn-cm-fc-form" method="post" action="' . esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())) . '">';
		echo '<input type="text" name="pn_cm_fila_cero_name" class="pn-cm-fc-form__input pn-customers-manager-input" placeholder="' . esc_attr__('Your name (optional)', 'pn-customers-manager') . '" />';
		echo '<input type="hidden" name="add-to-cart" value="' . esc_attr($product_id) . '" />';
		echo '<input type="hidden" name="quantity" value="1" />';
		echo '<button type="submit" class="pn-cm-fc-form__button pn-customers-manager-btn pn-customers-manager-btn-transparent">' . esc_html($btn_text) . '</button>';
		echo '</form>';

		// ── Names list ──
		if (!empty($entries)) {
			echo '<div class="pn-cm-fc-names">';
			echo '<h5 class="pn-cm-fc-names__heading">' . esc_html($names_head) . '</h5>';
			echo '<div class="pn-cm-fc-names__list">';
			foreach ($entries as $entry) {
				echo '<span class="pn-cm-fc-names__item">' . esc_html($entry->display_name) . '</span>';
			}
			echo '</div>';
			echo '</div>';
		}

		echo '</div>'; // .pn-cm-fc

		// ── Admin panel ──
		if ($is_admin) {
			$all_entries = self::get_entries($product_id, true);
			echo '<div class="pn-cm-fc-admin pn-cm-fc-admin--collapsed">';
			echo '<div class="pn-cm-fc-admin__header">';
			echo '<h5 class="pn-cm-fc-admin__title">';
			echo '<span class="material-icons-outlined pn-cm-fc-admin__toggle-icon">expand_more</span>';
			echo __('Fila Cero — Admin', 'pn-customers-manager');
			echo '<span class="pn-cm-fc-admin__count">(' . count($all_entries) . ')</span>';
			echo '</h5>';
			echo '</div>';

			echo '<div class="pn-cm-fc-admin__body">';
			if (!empty($all_entries)) {
				echo '<table class="pn-cm-fc-admin__table">';
				echo '<thead><tr>';
				echo '<th>' . __('Name', 'pn-customers-manager') . '</th>';
				echo '<th>' . __('Order', 'pn-customers-manager') . '</th>';
				echo '<th>' . __('Status', 'pn-customers-manager') . '</th>';
				echo '<th>' . __('Date', 'pn-customers-manager') . '</th>';
				echo '<th></th>';
				echo '</tr></thead>';
				echo '<tbody>';
				foreach ($all_entries as $entry) {
					$order_url = admin_url('post.php?post=' . $entry->order_id . '&action=edit');
					$badge_class = $entry->status === 'active' ? 'pn-cm-fc-admin__badge--active' : 'pn-cm-fc-admin__badge--hidden';
					echo '<tr>';
					echo '<td>' . esc_html($entry->display_name) . '</td>';
					echo '<td><a href="' . esc_url($order_url) . '">#' . esc_html($entry->order_id) . '</a></td>';
					echo '<td><span class="pn-cm-fc-admin__badge ' . $badge_class . '">' . esc_html($entry->status) . '</span></td>';
					echo '<td class="pn-cm-fc-admin__date">' . esc_html(date_i18n(get_option('date_format'), strtotime($entry->created_at))) . '</td>';
					echo '<td><button class="pn-cm-fc-admin__delete" data-id="' . esc_attr($entry->id) . '" title="' . esc_attr__('Delete', 'pn-customers-manager') . '">&times;</button></td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
			} else {
				echo '<p class="pn-cm-fc-admin__empty">' . __('No entries yet.', 'pn-customers-manager') . '</p>';
			}
			echo '</div>'; // __body
			echo '</div>'; // .pn-cm-fc-admin

			// Inline admin JS
			?>
			<script>
			(function(){
				var panel = document.querySelector('.pn-cm-fc-admin');
				if (!panel) return;
				var header = panel.querySelector('.pn-cm-fc-admin__header');
				if (header) {
					header.style.cursor = 'pointer';
					header.addEventListener('click', function() {
						panel.classList.toggle('pn-cm-fc-admin--collapsed');
					});
				}
				panel.addEventListener('click', function(e) {
					var btn = e.target.closest('.pn-cm-fc-admin__delete');
					if (!btn) return;
					e.stopPropagation();
					if (!confirm('<?php echo esc_js(__('Delete this entry?', 'pn-customers-manager')); ?>')) return;
					var row = btn.closest('tr');
					var id = btn.getAttribute('data-id');
					var fd = new FormData();
					fd.append('action', 'pn_customers_manager_ajax');
					fd.append('sub_action', 'pn_cm_woo_fila_cero_delete');
					fd.append('entry_id', id);
					fd.append('security', '<?php echo wp_create_nonce('pn_customers_manager_ajax'); ?>');
					fetch('<?php echo admin_url('admin-ajax.php'); ?>', {method:'POST', body: fd})
						.then(function(r){ return r.json(); })
						.then(function(d){
							if (d.success) { row.style.transition='opacity .3s'; row.style.opacity='0'; setTimeout(function(){ row.remove(); }, 300); }
						});
				});
			})();
			</script>
			<?php
		}
	}

	/* ──────────────────────────────────────────────
	 * Cart – Add item data
	 * ──────────────────────────────────────────── */

	public function add_cart_item_data($cart_item_data, $product_id) {
		if (isset($_POST['pn_cm_fila_cero_name'])) {
			$cart_item_data['pn_cm_fila_cero']      = 1;
			$cart_item_data['pn_cm_fila_cero_name']  = sanitize_text_field($_POST['pn_cm_fila_cero_name']);
		}
		return $cart_item_data;
	}

	/* ──────────────────────────────────────────────
	 * Cart – Display item data
	 * ──────────────────────────────────────────── */

	public function get_item_data($item_data, $cart_item) {
		if (!empty($cart_item['pn_cm_fila_cero'])) {
			$name = !empty($cart_item['pn_cm_fila_cero_name']) ? $cart_item['pn_cm_fila_cero_name'] : __('Anonymous', 'pn-customers-manager');
			$item_data[] = [
				'key'   => __('Fila Cero — Solidarity Purchase', 'pn-customers-manager'),
				'value' => esc_html($name),
			];
		}
		return $item_data;
	}

	/* ──────────────────────────────────────────────
	 * Order – Save meta on line item
	 * ──────────────────────────────────────────── */

	public function save_order_item_meta($item, $cart_item_key, $values, $order) {
		if (!empty($values['pn_cm_fila_cero'])) {
			$item->add_meta_data('_pn_cm_fila_cero', 'yes', true);
			$item->add_meta_data('_pn_cm_fila_cero_name', $values['pn_cm_fila_cero_name'], true);
		}
	}

	/* ──────────────────────────────────────────────
	 * Order – Record on processing/completed
	 * ──────────────────────────────────────────── */

	public function record_fila_cero($order_id) {
		global $wpdb;
		$order = wc_get_order($order_id);
		if (!$order) return;

		$table = $wpdb->prefix . 'pn_cm_woo_fila_cero';

		foreach ($order->get_items() as $item) {
			if ($item->get_meta('_pn_cm_fila_cero') !== 'yes') continue;

			$product_id   = $item->get_product_id();
			$display_name = $item->get_meta('_pn_cm_fila_cero_name');
			if (empty($display_name)) {
				$display_name = __('Anonymous', 'pn-customers-manager');
			}

			// Deduplicate by order_id + product_id
			$exists = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM {$table} WHERE order_id = %d AND product_id = %d",
				$order_id,
				$product_id
			));

			if ($exists) {
				// Reactivate if it was hidden
				$wpdb->update($table, ['status' => 'active'], ['id' => $exists]);
			} else {
				$wpdb->insert($table, [
					'product_id'   => $product_id,
					'order_id'     => $order_id,
					'display_name' => $display_name,
					'status'       => 'active',
					'created_at'   => current_time('mysql'),
				]);
			}
		}
	}

	/* ──────────────────────────────────────────────
	 * Order – Remove on cancelled/refunded
	 * ──────────────────────────────────────────── */

	public function remove_fila_cero($order_id) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'pn_cm_woo_fila_cero',
			['status' => 'hidden'],
			['order_id' => $order_id]
		);
	}

	/* ──────────────────────────────────────────────
	 * Order – Hide on trash / permanent delete
	 * ──────────────────────────────────────────── */

	public function remove_fila_cero_on_delete($post_id) {
		if (get_post_type($post_id) !== 'shop_order') {
			// HPOS: check if it could be a WC order stored in a custom table
			if (function_exists('wc_get_order') && wc_get_order($post_id)) {
				// Valid order – continue
			} else {
				return;
			}
		}
		$this->remove_fila_cero($post_id);
	}

	/* ──────────────────────────────────────────────
	 * Stock – Prevent reduction for fila cero items
	 * ──────────────────────────────────────────── */

	public function zero_fila_cero_qty($qty, $order, $item) {
		if ($item->get_meta('_pn_cm_fila_cero') === 'yes') {
			return 0;
		}
		return $qty;
	}

	/* ──────────────────────────────────────────────
	 * Order/Email – Show fila cero meta in order details and emails
	 * ──────────────────────────────────────────── */

	public function format_order_item_meta($formatted_meta, $item) {
		if ($item->get_meta('_pn_cm_fila_cero') !== 'yes') {
			return $formatted_meta;
		}

		$name = $item->get_meta('_pn_cm_fila_cero_name');

		// Remove the raw hidden meta keys from display
		foreach ($formatted_meta as $key => $meta) {
			if (in_array($meta->key, ['_pn_cm_fila_cero', '_pn_cm_fila_cero_name'], true)) {
				unset($formatted_meta[$key]);
			}
		}

		// Add a single readable entry
		$display = new stdClass();
		$display->key           = 'pn_cm_fila_cero';
		$display->display_key   = __('Fila Cero', 'pn-customers-manager');
		$display->display_value = !empty($name)
			? sprintf(__('Solidarity purchase — %s', 'pn-customers-manager'), esc_html($name))
			: __('Solidarity purchase', 'pn-customers-manager');
		$formatted_meta['pn_cm_fila_cero'] = $display;

		return $formatted_meta;
	}

	/* ──────────────────────────────────────────────
	 * DB – Get entries for a product
	 * ──────────────────────────────────────────── */

	public static function get_entries($product_id, $include_hidden = false) {
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_woo_fila_cero';

		if ($include_hidden) {
			return $wpdb->get_results($wpdb->prepare(
				"SELECT * FROM {$table} WHERE product_id = %d ORDER BY created_at DESC",
				$product_id
			));
		}

		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$table} WHERE product_id = %d AND status = 'active' ORDER BY created_at DESC",
			$product_id
		));
	}

	/* ──────────────────────────────────────────────
	 * AJAX – Admin delete entry
	 * ──────────────────────────────────────────── */

	public static function ajax_delete_entry() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Unauthorized', 'pn-customers-manager')]);
		}

		$entry_id = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;
		if (!$entry_id) {
			wp_send_json_error(['message' => __('Invalid entry', 'pn-customers-manager')]);
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'pn_cm_woo_fila_cero',
			['status' => 'hidden'],
			['id' => $entry_id]
		);

		wp_send_json_success();
	}
}
