<?php
/**
 * WooCommerce Calendar Booking Products System
 *
 * Interactive calendar system for managing campament reservations with capacity control and member pricing
 *
 * @link       padresenlanube.com/
 * @since      1.1.75
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce_Calendar_Booking
{
	private static $instance = null;

	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		// Admin: pestaña Calendario de Productos en editor de producto
		add_filter('woocommerce_product_data_tabs', [$this, 'product_data_tab']);
		add_action('woocommerce_product_data_panels', [$this, 'product_data_panel']);
		add_action('woocommerce_process_product_meta', [$this, 'save_product_meta']);
		add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

		// Frontend
		add_action('woocommerce_before_add_to_cart_button', [$this, 'render_calendar']);
		add_action('wp_enqueue_scripts', [$this, 'frontend_scripts']);

		// Carrito
		add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_add_to_cart'], 10, 3);
		add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);
		add_filter('woocommerce_get_item_data', [$this, 'get_item_data'], 10, 2);
		add_action('woocommerce_before_calculate_totals', [$this, 'calculate_totals'], 20, 1);

		// Pedido
		add_action('woocommerce_checkout_create_order_line_item', [$this, 'create_order_line_item'], 10, 4);
		add_action('woocommerce_order_status_processing', [$this, 'increment_bookings']);
		add_action('woocommerce_order_status_completed', [$this, 'increment_bookings']);
		add_action('woocommerce_order_status_cancelled', [$this, 'decrement_bookings']);
		add_action('woocommerce_order_status_refunded', [$this, 'decrement_bookings']);
		add_action('woocommerce_order_status_failed', [$this, 'decrement_bookings']);

		// Ocultar cantidad en producto camp
		add_filter('woocommerce_is_sold_individually', [$this, 'sold_individually'], 10, 2);

		// AJAX disponibilidad
		add_action('wp_ajax_pn_cm_woo_calendar_get_availability', [$this, 'ajax_get_availability']);
		add_action('wp_ajax_nopriv_pn_cm_woo_calendar_get_availability', [$this, 'ajax_get_availability']);

		// AJAX admin: reservas por día + resumen
		add_action('wp_ajax_pn_cm_woo_calendar_get_date_bookings', [$this, 'ajax_get_date_bookings']);
		add_action('wp_ajax_pn_cm_woo_calendar_get_admin_summary', [$this, 'ajax_get_admin_summary']);

		// Admin: edición de fechas en pedidos
		add_action('add_meta_boxes', [$this, 'add_order_calendar_meta_box']);
		add_action('admin_enqueue_scripts', [$this, 'order_edit_scripts']);
		add_action('wp_ajax_pn_cm_calendar_update_order_dates', [$this, 'ajax_update_order_dates']);
	}

	/* ───────────────────────────────────────────
	 *  Helpers
	 * ─────────────────────────────────────────── */

	private function is_camp_product($product_id)
	{
		return get_post_meta($product_id, '_pn_cm_woo_calendar_enabled', true) === 'yes';
	}

	private function get_weeks($product_id)
	{
		$weeks = get_post_meta($product_id, '_pn_cm_woo_calendar_weeks', true);
		if (!is_array($weeks)) return [];
		// Ensure backwards compatibility: add default values for new fields
		foreach ($weeks as &$w) {
			if (!isset($w['price'])) $w['price'] = 0;
			$p = (float) $w['price'];
			if (!isset($w['member_price']) || (float) $w['member_price'] <= 0) {
				$w['member_price'] = $p;
			} else {
				$w['member_price'] = (float) $w['member_price'];
			}
			$w['price'] = $p;
			if (!isset($w['capacity'])) $w['capacity'] = 25;
			if (!isset($w['discount'])) $w['discount'] = 0;
			if (!isset($w['discount_type'])) $w['discount_type'] = 'percent';
			if (!isset($w['groups'])) $w['groups'] = [];
		}
		unset($w);
		return $weeks;
	}

	private function get_day_price($product_id)
	{
		return (float) get_post_meta($product_id, '_pn_cm_woo_calendar_day_price', true);
	}

	private function get_member_day_price($product_id)
	{
		$mp = get_post_meta($product_id, '_pn_cm_woo_calendar_member_day_price', true);
		if ($mp === '' || $mp === false || (float) $mp <= 0) {
			return $this->get_day_price($product_id);
		}
		return (float) $mp;
	}

	/**
	 * Check if current user has member role for this product
	 */
	private function is_current_user_member($product_id)
	{
		if (!is_user_logged_in()) return false;

		$allowed_roles = get_post_meta($product_id, '_pn_cm_woo_calendar_member_roles', true);
		if (empty($allowed_roles) || !is_array($allowed_roles)) {
			return false;
		}

		$user = wp_get_current_user();
		foreach ($allowed_roles as $role) {
			if (in_array($role, (array) $user->roles, true)) {
				return true;
			}
		}
		return false;
	}

	private function get_bookings($product_id)
	{
		$bookings = get_post_meta($product_id, '_pn_cm_woo_calendar_bookings', true);
		return is_array($bookings) ? $bookings : [];
	}

	private function get_availability($product_id)
	{
		$weeks = $this->get_weeks($product_id);
		$bookings = $this->get_bookings($product_id);
		$availability = [];
		foreach ($weeks as $week) {
			$has_groups = !empty($week['groups']);
			$block_capacity = (int) $week['capacity'];

			foreach ($week['dates'] as $date) {
				if ($has_groups) {
					// With groups: compute per-group availability + total day availability
					$day_total_capacity = 0;
					$day_total_booked = 0;
					foreach ($week['groups'] as $group) {
						$group_capacity = (int) $group['capacity'];
						$composite_key = $date . '__' . $group['id'];
						$group_booked = isset($bookings[$composite_key]) ? (int) $bookings[$composite_key] : 0;
						$availability[$composite_key] = max(0, $group_capacity - $group_booked);
						$day_total_capacity += $group_capacity;
						$day_total_booked += $group_booked;
					}
					// Day-level availability = sum of all groups
					$availability[$date] = max(0, $day_total_capacity - $day_total_booked);
				} else {
					// No groups: simple capacity
					$booked = isset($bookings[$date]) ? (int) $bookings[$date] : 0;
					$availability[$date] = max(0, $block_capacity - $booked);
				}
			}
		}
		return $availability;
	}

	/**
	 * Apply block discount to a price
	 */
	private function apply_discount($price, $discount, $discount_type)
	{
		$discount = (float) $discount;
		if ($discount <= 0) return $price;

		if ($discount_type === 'fixed') {
			return max(0, $price - $discount);
		}
		// percent
		return max(0, $price * (1 - $discount / 100));
	}

	/**
	 * Normalize groups from old format { blockId: "groupId" } to new format { blockId: { groupId: qty } }
	 */
	private function normalize_groups($groups)
	{
		if (!is_array($groups)) return [];
		$normalized = [];
		foreach ($groups as $block_id => $value) {
			if (is_string($value)) {
				$normalized[$block_id] = [$value => 1];
			} elseif (is_array($value)) {
				$normalized[$block_id] = array_map('intval', $value);
			}
		}
		return $normalized;
	}

	private function calculate_price($product_id, $selected_dates, $selected_weeks, $is_member = false)
	{
		$enable_member = get_post_meta($product_id, '_pn_cm_woo_calendar_enable_member_discount', true) === 'yes';
		$is_member     = $is_member && $enable_member;

		$weeks            = $this->get_weeks($product_id);
		$base_day_price   = (float) $this->get_day_price($product_id);
		$member_day_price = (float) $this->get_member_day_price($product_id);

		$active_day_price = ($is_member && $member_day_price > 0) ? $member_day_price : $base_day_price;

		$total = 0;
		$week_map = [];
		foreach ($weeks as $week) {
			$week_map[$week['id']] = $week;
		}

		// Bloques completos
		$covered_dates = [];
		foreach ($selected_weeks as $week_id) {
			if (isset($week_map[$week_id])) {
				$w = $week_map[$week_id];
				$week_price = (float) $w['price'];
				if ($is_member && isset($w['member_price']) && (float) $w['member_price'] > 0) {
					$week_price = (float) $w['member_price'];
				}
				// Apply block discount
				$week_price = $this->apply_discount($week_price, $w['discount'], $w['discount_type']);
				$total += $week_price;
				foreach ($w['dates'] as $d) {
					$covered_dates[] = $d;
				}
			}
		}

		// Días sueltos
		foreach ($selected_dates as $date) {
			if (!in_array($date, $covered_dates, true)) {
				$dp = $active_day_price;
				// Si no hay precio de día suelto configurado, buscar precio prorrateado del bloque
				if ($dp <= 0) {
					foreach ($weeks as $w) {
						if (!empty($w['dates']) && in_array($date, $w['dates'], true)) {
							$w_price = ($is_member && isset($w['member_price']) && (float) $w['member_price'] > 0) ? (float) $w['member_price'] : (float) $w['price'];
							$cnt = count($w['dates']);
							if ($cnt > 0) {
								$dp = $w_price / $cnt;
							}
							break;
						}
					}
				}
				// Si sigue siendo 0, usar el precio regular del producto de WooCommerce
				if ($dp <= 0) {
					$wc_prod = wc_get_product($product_id);
					if ($wc_prod && (float) $wc_prod->get_price() > 0) {
						$dp = (float) $wc_prod->get_price();
					}
				}
				$total += $dp;
			}
		}

		return $total;
	}

	/**
	 * Genera array de fechas laborables (L-V) entre dos fechas
	 */
	private function generate_weekday_dates($start, $end)
	{
		$dates = [];
		$current = new DateTime($start);
		$last    = new DateTime($end);
		while ($current <= $last) {
			$dow = (int) $current->format('N'); // 1=lunes, 7=domingo
			if ($dow >= 1 && $dow <= 5) {
				$dates[] = $current->format('Y-m-d');
			}
			$current->modify('+1 day');
		}
		return $dates;
	}

	/* ───────────────────────────────────────────
	 *  Admin: pestaña producto
	 * ─────────────────────────────────────────── */

	public function product_data_tab($tabs)
	{
		$tabs['pn_cm_woo_camp'] = [
			'label'    => __('Calendario de Productos', 'pn-customers-manager'),
			'target'   => 'pn_cm_woo_calendar_data',
			'class'    => [],
			'priority' => 80,
		];
		return $tabs;
	}

	public function product_data_panel()
	{
		global $post;
		$product_id            = $post->ID;
		$day_price             = get_post_meta($product_id, '_pn_cm_woo_calendar_day_price', true);
		$member_day_price      = get_post_meta($product_id, '_pn_cm_woo_calendar_member_day_price', true);
		$schedule              = get_post_meta($product_id, '_pn_cm_woo_calendar_schedule', true);
		$enable_member         = get_post_meta($product_id, '_pn_cm_woo_calendar_enable_member_discount', true) === 'yes';
		$member_label          = get_post_meta($product_id, '_pn_cm_woo_calendar_member_label', true);
		if (empty($member_label)) {
			$member_label = __('We are a member family', 'pn-customers-manager');
		}
		$weeks                 = $this->get_weeks($product_id);
		?>
		<div id="pn_cm_woo_calendar_data" class="panel woocommerce_options_panel">

			<!-- Ajustes generales -->
			<div class="options_group">
				<?php
				if (function_exists('woocommerce_wp_checkbox')) {
					woocommerce_wp_checkbox([
						'id'          => '_pn_cm_woo_calendar_enabled',
						'label'       => __('Activar calendario de productos', 'pn-customers-manager'),
						'description' => __('Habilitar sistema de reserva de calendario de productos para este producto', 'pn-customers-manager'),
					]);
					woocommerce_wp_checkbox([
						'id'          => '_pn_cm_woo_calendar_show_availability',
						'label'       => __('Mostrar plazas', 'pn-customers-manager'),
						'description' => __('Mostrar plazas restantes en cada día del calendario', 'pn-customers-manager'),
					]);
					woocommerce_wp_checkbox([
						'id'          => '_pn_cm_woo_calendar_enable_member_discount',
						'label'       => __('Activar tarifa / descuento especial', 'pn-customers-manager'),
						'description' => __('Habilitar precio especial para familias asociadas, socios o colectivos.', 'pn-customers-manager'),
					]);
				}
				if (function_exists('woocommerce_wp_text_input')) {
					woocommerce_wp_text_input([
						'id'          => '_pn_cm_woo_calendar_schedule',
						'label'       => __('Schedule', 'pn-customers-manager'),
						'placeholder' => '9:00 - 14:30 (madrugadores desde 8:30)',
					]);
				}
				?>
				<div class="pn-cm-member-only-field" <?php echo !$enable_member ? 'style="display:none;"' : ''; ?>>
					<?php
					if (function_exists('woocommerce_wp_text_input')) {
						woocommerce_wp_text_input([
							'id'          => '_pn_cm_woo_calendar_member_label',
							'label'       => __('Etiqueta de la opción especial', 'pn-customers-manager'),
							'placeholder' => __('We are a member family', 'pn-customers-manager'),
							'description' => __('Texto que se mostrará junto a la casilla para activar esta tarifa especial.', 'pn-customers-manager'),
							'value'       => $member_label,
						]);
					}
					?>
				</div>
			</div>

			<!-- Roles con descuento especial -->
			<div class="options_group pn-cm-member-only-field" <?php echo !$enable_member ? 'style="display:none;"' : ''; ?>>
				<?php
				$all_roles = wp_roles()->get_names();
				$selected_roles = get_post_meta($post->ID, '_pn_cm_woo_calendar_member_roles', true);
				if (!is_array($selected_roles)) $selected_roles = [];
				?>
				<p class="form-field">
					<label class="pn-cm-admin-main-label"><?php esc_html_e('Roles with special / member discount', 'pn-customers-manager'); ?></label>
					<select name="_pn_cm_woo_calendar_member_roles[]" multiple style="width:100%;min-height:100px;">
						<?php foreach ($all_roles as $role_key => $role_name): ?>
							<option value="<?php echo esc_attr($role_key); ?>" <?php echo in_array($role_key, $selected_roles) ? 'selected' : ''; ?>>
								<?php echo esc_html($role_name); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="description"><?php esc_html_e('Select which user roles automatically get the special rate when logged in. Hold Ctrl/Cmd to select multiple.', 'pn-customers-manager'); ?></span>
				</p>
			</div>

			<!-- Precios por día suelto -->
			<div class="options_group">
				<p class="form-field pn-cm-admin-field-group">
					<label class="pn-cm-admin-main-label"><?php esc_html_e('Single day price', 'pn-customers-manager'); ?></label>
					<div class="pn-cm-admin-price-row">
						<div class="pn-cm-admin-price-item">
							<label class="pn-cm-admin-sub-label"><?php esc_html_e('Standard rate', 'pn-customers-manager'); ?></label>
							<div class="pn-cm-calendar-input-with-suffix">
								<input type="number" id="_pn_cm_woo_calendar_day_price" name="_pn_cm_woo_calendar_day_price"
								       value="<?php echo esc_attr($day_price); ?>"
								       placeholder="25.00" step="0.01" min="0" class="short" />
								<span class="pn-cm-calendar-suffix">&euro;</span>
							</div>
						</div>
						<div class="pn-cm-admin-price-item pn-cm-member-only-field" <?php echo !$enable_member ? 'style="display:none;"' : ''; ?>>
							<label class="pn-cm-admin-sub-label pn-cm-member-price-title">
								<?php esc_html_e('Single day price', 'pn-customers-manager'); ?> (<span class="pn-cm-member-label-text"><?php echo esc_html($member_label); ?></span>)
							</label>
							<div class="pn-cm-calendar-input-with-suffix">
								<input type="number" id="_pn_cm_woo_calendar_member_day_price" name="_pn_cm_woo_calendar_member_day_price"
								       value="<?php echo esc_attr($member_day_price); ?>"
								       placeholder="20.00" step="0.01" min="0" class="short" />
								<span class="pn-cm-calendar-suffix">&euro;</span>
							</div>
						</div>
					</div>
				</p>
			</div>

			<!-- Bloques -->
			<div class="options_group pn-cm-calendar-weeks-section">
				<p class="form-field">
					<label class="pn-cm-calendar-section-title"><?php esc_html_e('Product calendar blocks', 'pn-customers-manager'); ?></label>
				</p>
				<div id="pn-cm-calendar-weeks-repeater">
					<?php
					if (!empty($weeks)) {
						foreach ($weeks as $i => $week) {
							$this->render_week_row($i, $week);
						}
					}
					?>
				</div>
				<div class="pn-cm-calendar-add-wrap">
					<button type="button" class="button button-primary" id="pn-cm-calendar-add-week">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e('Add block', 'pn-customers-manager'); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_week_row($index, $week = [])
	{
		global $post;
		$enable_member = $post ? get_post_meta($post->ID, '_pn_cm_woo_calendar_enable_member_discount', true) === 'yes' : false;
		$member_label  = $post ? get_post_meta($post->ID, '_pn_cm_woo_calendar_member_label', true) : '';
		if (empty($member_label)) {
			$member_label = __('We are a member family', 'pn-customers-manager');
		}
		$defaults = [
			'id' => '', 'label' => '', 'dates' => [],
			'price' => '', 'member_price' => '', 'capacity' => 25,
			'discount' => 0, 'discount_type' => 'percent', 'groups' => [],
		];
		$week = wp_parse_args($week, $defaults);
		$dates_json = !empty($week['dates']) ? wp_json_encode($week['dates']) : '[]';
		$n = $index + 1;
		$has_groups = !empty($week['groups']);
		?>
		<div class="pn-cm-calendar-week-row" data-index="<?php echo esc_attr($index); ?>">
			<div class="pn-cm-calendar-week-header">
				<span class="pn-cm-calendar-week-handle dashicons dashicons-move"></span>
				<span class="pn-cm-calendar-week-number">#<?php echo esc_html($n); ?></span>
				<span class="pn-cm-calendar-week-header-label">
					<?php echo !empty($week['label']) ? esc_html($week['label']) : esc_html__('New block', 'pn-customers-manager'); ?>
				</span>
				<button type="button" class="pn-cm-calendar-remove-week" title="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
			<div class="pn-cm-calendar-week-body">
				<!-- ID + Etiqueta -->
				<div class="pn-cm-calendar-row">
					<div class="pn-cm-calendar-field">
						<label class="pn-cm-admin-sub-label"><?php esc_html_e('Identifier', 'pn-customers-manager'); ?></label>
						<input type="text" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][id]"
						       value="<?php echo esc_attr($week['id']); ?>"
						       placeholder="bloque-1" />
						<span class="pn-cm-calendar-field-hint"><?php esc_html_e('Internal use, no spaces', 'pn-customers-manager'); ?></span>
					</div>
					<div class="pn-cm-calendar-field">
						<label class="pn-cm-admin-sub-label"><?php esc_html_e('Display name', 'pn-customers-manager'); ?></label>
						<input type="text" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][label]"
						       value="<?php echo esc_attr($week['label']); ?>"
						       placeholder="Bloque 1" class="pn-cm-calendar-label-input" />
					</div>
				</div>
				<!-- Calendario interactivo -->
				<div class="pn-cm-calendar-picker-section">
					<label class="pn-cm-admin-sub-label"><?php esc_html_e('Dates', 'pn-customers-manager'); ?></label>
					<span class="pn-cm-calendar-picker-hint"><?php esc_html_e('Click = select/deselect &middot; Shift+click = range &middot; Ctrl+click = add individual', 'pn-customers-manager'); ?></span>
					<div class="pn-cm-calendar-block-picker" data-dates="<?php echo esc_attr($dates_json); ?>"></div>
					<input type="hidden" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][dates_json]"
					       class="pn-cm-calendar-dates-json" value="<?php echo esc_attr($dates_json); ?>" />
					<div class="pn-cm-calendar-dates-preview"></div>
				</div>
				<!-- Configuración: Aforo + Precios + Descuento -->
				<div class="pn-cm-calendar-config-grid">
					<div class="pn-cm-calendar-field pn-cm-calendar-capacity-field" <?php echo $has_groups ? 'style="display:none;"' : ''; ?>>
						<label class="pn-cm-admin-sub-label"><?php esc_html_e('Capacity/day', 'pn-customers-manager'); ?></label>
						<div class="pn-cm-calendar-input-with-suffix">
							<input type="number" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][capacity]"
							       value="<?php echo esc_attr($week['capacity']); ?>"
							       placeholder="25" min="1" />
							<span class="pn-cm-calendar-suffix"><?php esc_html_e('spots', 'pn-customers-manager'); ?></span>
						</div>
					</div>
					<div class="pn-cm-calendar-field">
						<label class="pn-cm-admin-sub-label"><?php esc_html_e('Standard price', 'pn-customers-manager'); ?></label>
						<div class="pn-cm-calendar-input-with-suffix">
							<input type="number" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][price]"
							       value="<?php echo esc_attr($week['price']); ?>"
							       placeholder="100.00" step="0.01" min="0" />
							<span class="pn-cm-calendar-suffix">&euro;</span>
						</div>
					</div>
					<div class="pn-cm-calendar-field pn-cm-member-only-field" <?php echo !$enable_member ? 'style="display:none;"' : ''; ?>>
						<label class="pn-cm-admin-sub-label pn-cm-member-price-title">
							<?php esc_html_e('Price', 'pn-customers-manager'); ?> (<span class="pn-cm-member-label-text"><?php echo esc_html($member_label); ?></span>)
						</label>
						<div class="pn-cm-calendar-input-with-suffix">
							<input type="number" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][member_price]"
							       value="<?php echo esc_attr($week['member_price']); ?>"
							       placeholder="80.00" step="0.01" min="0" />
							<span class="pn-cm-calendar-suffix">&euro;</span>
						</div>
					</div>
					<div class="pn-cm-calendar-field">
						<label class="pn-cm-admin-sub-label"><?php esc_html_e('Discount', 'pn-customers-manager'); ?></label>
						<div class="pn-cm-calendar-discount-inputs">
							<input type="number" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][discount]"
							       value="<?php echo esc_attr($week['discount']); ?>"
							       placeholder="0" step="0.01" min="0" class="pn-cm-calendar-discount-amount" />
							<select name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][discount_type]" class="pn-cm-calendar-discount-type">
								<option value="percent" <?php selected($week['discount_type'], 'percent'); ?>>%</option>
								<option value="fixed" <?php selected($week['discount_type'], 'fixed'); ?>>&euro;</option>
							</select>
						</div>
					</div>
				</div>
				<?php if ($has_groups): ?>
					<div class="pn-cm-calendar-capacity-note">
						<span class="pn-cm-calendar-field-hint"><?php esc_html_e('Capacity is defined in each group', 'pn-customers-manager'); ?></span>
					</div>
				<?php endif; ?>
				<!-- Grupos -->
				<div class="pn-cm-calendar-groups-section">
					<label class="pn-cm-calendar-groups-title"><?php esc_html_e('Activity groups', 'pn-customers-manager'); ?></label>
					<div class="pn-cm-calendar-groups-repeater">
						<?php if (!empty($week['groups'])): ?>
							<?php foreach ($week['groups'] as $gi => $group): ?>
								<div class="pn-cm-calendar-group-row" data-group-index="<?php echo esc_attr($gi); ?>">
									<input type="hidden" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][groups][<?php echo esc_attr($gi); ?>][id]"
									       value="<?php echo esc_attr($group['id']); ?>" class="pn-cm-calendar-group-id" />
									<div class="pn-cm-calendar-field">
										<input type="text" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][groups][<?php echo esc_attr($gi); ?>][label]"
										       value="<?php echo esc_attr($group['label']); ?>"
										       placeholder="<?php esc_attr_e('Group name', 'pn-customers-manager'); ?>" class="pn-cm-calendar-group-label" />
									</div>
									<div class="pn-cm-calendar-field pn-cm-calendar-field--narrow">
										<input type="number" name="_pn_cm_woo_calendar_weeks[<?php echo esc_attr($index); ?>][groups][<?php echo esc_attr($gi); ?>][capacity]"
										       value="<?php echo esc_attr($group['capacity']); ?>"
										       placeholder="15" min="1" class="pn-cm-calendar-group-capacity" />
									</div>
									<button type="button" class="pn-cm-calendar-remove-group" title="<?php esc_attr_e('Delete group', 'pn-customers-manager'); ?>">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<button type="button" class="button pn-cm-calendar-add-group">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e('Add group', 'pn-customers-manager'); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	public function save_product_meta($product_id)
	{
		$enabled = isset($_POST['_pn_cm_woo_calendar_enabled']) ? 'yes' : 'no';
		update_post_meta($product_id, '_pn_cm_woo_calendar_enabled', $enabled);

		$show_avail = isset($_POST['_pn_cm_woo_calendar_show_availability']) ? 'yes' : 'no';
		update_post_meta($product_id, '_pn_cm_woo_calendar_show_availability', $show_avail);

		$enable_member = isset($_POST['_pn_cm_woo_calendar_enable_member_discount']) ? 'yes' : 'no';
		update_post_meta($product_id, '_pn_cm_woo_calendar_enable_member_discount', $enable_member);

		if (isset($_POST['_pn_cm_woo_calendar_member_label'])) {
			update_post_meta($product_id, '_pn_cm_woo_calendar_member_label', sanitize_text_field($_POST['_pn_cm_woo_calendar_member_label']));
		}

		if (isset($_POST['_pn_cm_woo_calendar_day_price'])) {
			update_post_meta($product_id, '_pn_cm_woo_calendar_day_price', sanitize_text_field($_POST['_pn_cm_woo_calendar_day_price']));
		}
		if (isset($_POST['_pn_cm_woo_calendar_member_day_price'])) {
			update_post_meta($product_id, '_pn_cm_woo_calendar_member_day_price', sanitize_text_field($_POST['_pn_cm_woo_calendar_member_day_price']));
		}
		if (isset($_POST['_pn_cm_woo_calendar_schedule'])) {
			update_post_meta($product_id, '_pn_cm_woo_calendar_schedule', sanitize_text_field($_POST['_pn_cm_woo_calendar_schedule']));
		}

		// Member roles
		if (isset($_POST['_pn_cm_woo_calendar_member_roles']) && is_array($_POST['_pn_cm_woo_calendar_member_roles'])) {
			$roles = array_map('sanitize_text_field', $_POST['_pn_cm_woo_calendar_member_roles']);
			update_post_meta($product_id, '_pn_cm_woo_calendar_member_roles', $roles);
		} else {
			delete_post_meta($product_id, '_pn_cm_woo_calendar_member_roles');
		}

		// Bloques
		$weeks = [];
		$used_ids = [];
		if (isset($_POST['_pn_cm_woo_calendar_weeks']) && is_array($_POST['_pn_cm_woo_calendar_weeks'])) {
			$index = 0;
			foreach ($_POST['_pn_cm_woo_calendar_weeks'] as $week_data) {
				// Read dates from dates_json (primary) or fallback to date range
				$dates = [];
				if (!empty($week_data['dates_json'])) {
					$decoded = json_decode(stripslashes($week_data['dates_json']), true);
					if (is_array($decoded)) {
						$dates = array_map('sanitize_text_field', $decoded);
						sort($dates);
					}
				}

				// Fallback: generate from date range if dates_json is empty
				if (empty($dates)) {
					$date_start = isset($week_data['date_start']) ? sanitize_text_field($week_data['date_start']) : '';
					$date_end   = isset($week_data['date_end']) ? sanitize_text_field($week_data['date_end']) : '';
					if ($date_start && $date_end && $date_end >= $date_start) {
						$dates = $this->generate_weekday_dates($date_start, $date_end);
					}
				}

				// Auto-generar ID si está vacío y garantizar unicidad
				$id = sanitize_title($week_data['id']);
				if (empty($id)) {
					$id = 'bloque-' . ($index + 1);
				}
				$base_id = $id;
				$suffix = 2;
				while (in_array($id, $used_ids, true)) {
					$id = $base_id . '-' . $suffix;
					$suffix++;
				}
				$used_ids[] = $id;

				// Parse groups
				$groups = [];
				if (isset($week_data['groups']) && is_array($week_data['groups'])) {
					$group_used_ids = [];
					$gi = 0;
					foreach ($week_data['groups'] as $group_data) {
						$group_id = sanitize_title($group_data['id']);
						if (empty($group_id)) {
							$group_id = 'grupo-' . ($gi + 1);
						}
						$base_gid = $group_id;
						$gsuffix = 2;
						while (in_array($group_id, $group_used_ids, true)) {
							$group_id = $base_gid . '-' . $gsuffix;
							$gsuffix++;
						}
						$group_used_ids[] = $group_id;

						$groups[] = [
							'id'       => $group_id,
							'label'    => sanitize_text_field($group_data['label']),
							'capacity' => max(1, (int) $group_data['capacity']),
						];
						$gi++;
					}
				}

				$price_val = (float) $week_data['price'];
				$member_price_raw = isset($week_data['member_price']) ? trim($week_data['member_price']) : '';
				$member_price_val = ($member_price_raw !== '' && (float) $member_price_raw > 0) ? (float) $member_price_raw : $price_val;

				$weeks[] = [
					'id'            => $id,
					'label'         => sanitize_text_field($week_data['label']),
					'dates'         => $dates,
					'price'         => $price_val,
					'member_price'  => $member_price_val,
					'capacity'      => max(1, (int) $week_data['capacity']),
					'discount'      => max(0, (float) $week_data['discount']),
					'discount_type' => in_array($week_data['discount_type'], ['percent', 'fixed']) ? $week_data['discount_type'] : 'percent',
					'groups'        => $groups,
				];
				$index++;
			}
		}
		update_post_meta($product_id, '_pn_cm_woo_calendar_weeks', $weeks);
	}

	/* ───────────────────────────────────────────
	 *  Admin scripts
	 * ─────────────────────────────────────────── */

	public function admin_scripts($hook)
	{
		if (!in_array($hook, ['post.php', 'post-new.php'])) return;
		global $post;
		if (empty($post) || $post->post_type !== 'product') return;

		$plugin_url = PN_CUSTOMERS_MANAGER_URL;
		$plugin_dir = PN_CUSTOMERS_MANAGER_DIR;

		wp_enqueue_script(
			'pn-cm-calendar-booking-admin',
			$plugin_url . 'assets/js/pn-cm-calendar-booking-admin.js',
			['jquery', 'jquery-ui-sortable'],
			filemtime($plugin_dir . 'assets/js/pn-cm-calendar-booking-admin.js'),
			true
		);
		wp_enqueue_style(
			'pn-cm-calendar-booking-css',
			$plugin_url . 'assets/css/pn-cm-calendar-booking.css',
			[],
			filemtime($plugin_dir . 'assets/css/pn-cm-calendar-booking.css')
		);
	}

	/* ───────────────────────────────────────────
	 *  Frontend: scripts y calendario
	 * ─────────────────────────────────────────── */

	public function frontend_scripts()
	{
		if (!is_product()) return;
		global $post;
		if (!$this->is_camp_product($post->ID)) return;

		$plugin_url = PN_CUSTOMERS_MANAGER_URL;
		$plugin_dir = PN_CUSTOMERS_MANAGER_DIR;

		wp_enqueue_style(
			'pn-cm-calendar-booking-css',
			esc_url($plugin_url . 'assets/css/pn-cm-calendar-booking.css'),
			[],
			filemtime($plugin_dir . 'assets/css/pn-cm-calendar-booking.css')
		);
		wp_enqueue_script(
			'pn-cm-calendar-booking',
			$plugin_url . 'assets/js/pn-cm-calendar-booking-frontend.js',
			['jquery'],
			filemtime($plugin_dir . 'assets/js/pn-cm-calendar-booking-frontend.js'),
			true
		);

		$weeks = $this->get_weeks($post->ID);
		$enable_member = get_post_meta($post->ID, '_pn_cm_woo_calendar_enable_member_discount', true) === 'yes';
		$member_label  = get_post_meta($post->ID, '_pn_cm_woo_calendar_member_label', true);
		if (empty($member_label)) {
			$member_label = __('We are a member family', 'pn-customers-manager');
		}

		// Enviar ambos sets de precios al frontend
		$weeks_for_js = [];
		foreach ($weeks as $w) {
			$price_val = (float) $w['price'];
			$member_price_val = (isset($w['member_price']) && (float) $w['member_price'] > 0) ? (float) $w['member_price'] : $price_val;
			$weeks_for_js[] = [
				'id'            => $w['id'],
				'label'         => $w['label'],
				'dates'         => $w['dates'],
				'price'         => $price_val,
				'member_price'  => $member_price_val,
				'capacity'      => (int) $w['capacity'],
				'discount'      => (float) $w['discount'],
				'discount_type' => $w['discount_type'],
				'groups'        => $w['groups'],
			];
		}

		$day_price_val = (float) $this->get_day_price($post->ID);
		$member_day_price_val = (float) $this->get_member_day_price($post->ID);
		if ($member_day_price_val <= 0) {
			$member_day_price_val = $day_price_val;
		}

		// Usar wp_add_inline_script con JSON nativo para preservar tipos de datos
		$camp_data = [
			'ajaxUrl'              => admin_url('admin-ajax.php'),
			'nonce'                => wp_create_nonce('pn_cm_calendar_nonce'),
			'productId'            => $post->ID,
			'showAvailability'     => get_post_meta($post->ID, '_pn_cm_woo_calendar_show_availability', true) === 'yes',
			'enableMemberDiscount' => $enable_member,
			'weeks'                => $weeks_for_js,
			'dayPrice'             => $day_price_val,
			'memberDayPrice'       => $member_day_price_val,
			'schedule'             => get_post_meta($post->ID, '_pn_cm_woo_calendar_schedule', true),
			'availability'         => $this->get_availability($post->ID),
			'isAdmin'              => current_user_can('manage_woocommerce'),
			'isMember'             => $this->is_current_user_member($post->ID),
			'currency'             => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
			'i18n'                 => [
				'schedule'      => __('Schedule', 'pn-customers-manager'),
				'available'     => __('Available', 'pn-customers-manager'),
				'selected'      => __('Selected', 'pn-customers-manager'),
				'full'          => __('Full', 'pn-customers-manager'),
				'yourSelection' => __('Your selection', 'pn-customers-manager'),
				'total'         => __('Total', 'pn-customers-manager'),
				'looseDays'     => __('Individual days', 'pn-customers-manager'),
				'noSelection'   => __('Select days or calendar blocks', 'pn-customers-manager'),
				'memberLabel'   => $member_label,
				'memberBadge'   => __('Member price applied', 'pn-customers-manager'),
				'adminPanel'    => __('Admin panel', 'pn-customers-manager'),
				'adminBack'     => __('Back to purchase', 'pn-customers-manager'),
				'adminOccupancy' => __('Occupancy', 'pn-customers-manager'),
				'adminBookings' => __('Bookings', 'pn-customers-manager'),
				'adminNoBookings' => __('No bookings for this day', 'pn-customers-manager'),
				'adminName'     => __('Name', 'pn-customers-manager'),
				'adminEmail'    => __('Email', 'pn-customers-manager'),
				'adminPhone'    => __('Phone', 'pn-customers-manager'),
				'adminNotes'    => __('Notes', 'pn-customers-manager'),
				'adminOrder'    => __('Order', 'pn-customers-manager'),
				'adminDate'     => __('Order date', 'pn-customers-manager'),
				'adminClose'    => __('Close', 'pn-customers-manager'),
				'adminLoading'  => __('Loading...', 'pn-customers-manager'),
				'adminError'    => __('Error loading bookings', 'pn-customers-manager'),
				'adminSummaryTitle'  => __('Billing summary', 'pn-customers-manager'),
				'adminTotalOrders'   => __('Total orders', 'pn-customers-manager'),
				'adminTotalBookings' => __('Bookings (spots·day)', 'pn-customers-manager'),
				'adminTotalRevenue'  => __('Total revenue', 'pn-customers-manager'),
				'adminWeekSummary'   => __('Breakdown by block', 'pn-customers-manager'),
				'adminWeekLabel'     => __('Block', 'pn-customers-manager'),
				'adminWeekBookings'  => __('Bookings', 'pn-customers-manager'),
				'adminWeekRevenue'   => __('Revenue', 'pn-customers-manager'),
				'selectGroup'        => __('Select group', 'pn-customers-manager'),
				'group'              => __('Group', 'pn-customers-manager'),
				'discountLabel'      => __('Discount', 'pn-customers-manager'),
				'spotsLeft'          => __('spots', 'pn-customers-manager'),
				'selectGroupError'   => __('Please select a group for each block with activity groups.', 'pn-customers-manager'),
				'plazas'             => __('Spots', 'pn-customers-manager'),
				'plazasLine'         => __('spots', 'pn-customers-manager'),
				'distributeSpots'    => __('Distribute spots', 'pn-customers-manager'),
				'invalidDistribution' => __('The spot distribution does not match the selected total.', 'pn-customers-manager'),
				'groupFull'          => __('Not enough spots in the selected group.', 'pn-customers-manager'),
				'notEnoughSpots'     => __('Not enough spots available for the selected quantity.', 'pn-customers-manager'),
			],
			'months' => [
				__('January', 'pn-customers-manager'), __('February', 'pn-customers-manager'), __('March', 'pn-customers-manager'),
				__('April', 'pn-customers-manager'), __('May', 'pn-customers-manager'), __('June', 'pn-customers-manager'),
				__('July', 'pn-customers-manager'), __('August', 'pn-customers-manager'), __('September', 'pn-customers-manager'),
				__('October', 'pn-customers-manager'), __('November', 'pn-customers-manager'), __('December', 'pn-customers-manager'),
			],
			'weekDays' => [
				__('M', 'pn-customers-manager'), __('T', 'pn-customers-manager'), __('W', 'pn-customers-manager'),
				__('T', 'pn-customers-manager'), __('F', 'pn-customers-manager'), __('S', 'pn-customers-manager'), __('S', 'pn-customers-manager'),
			],
		];
		wp_add_inline_script(
			'pn-cm-calendar-booking',
			'var pnCmCampData = ' . wp_json_encode($camp_data) . ';',
			'before'
		);
	}

	public function render_calendar()
	{
		global $post;
		if (!$this->is_camp_product($post->ID)) return;
		?>
		<div id="pn-cm-calendar-booking-wrap">
			<div id="pn-cm-calendar-member-toggle"></div>
			<div id="pn-cm-calendar-schedule"></div>
			<div id="pn-cm-calendar-legend"></div>
			<div id="pn-cm-calendar-calendar"></div>
			<div id="pn-cm-calendar-weeks-buttons"></div>
			<div id="pn-cm-calendar-summary"></div>
			<div id="pn-cm-calendar-admin-summary"></div>
			<input type="hidden" name="pn_cm_calendar_dates" id="pn-cm-calendar-dates-input" value="" />
			<input type="hidden" name="pn_cm_calendar_weeks" id="pn-cm-calendar-weeks-input" value="" />
			<input type="hidden" name="pn_cm_calendar_is_member" id="pn-cm-calendar-is-member-input" value="0" />
			<input type="hidden" name="pn_cm_calendar_groups" id="pn-cm-calendar-groups-input" value="" />
			<input type="hidden" name="pn_cm_calendar_qty" id="pn-cm-calendar-qty-input" value="1" />
		</div>
		<?php
	}

	/* ───────────────────────────────────────────
	 *  Forzar venta individual
	 * ─────────────────────────────────────────── */

	public function sold_individually($individual, $product)
	{
		if ($this->is_camp_product($product->get_id())) {
			return true;
		}
		return $individual;
	}

	/* ───────────────────────────────────────────
	 *  Carrito: validación
	 * ─────────────────────────────────────────── */

	public function validate_add_to_cart($passed, $product_id, $quantity)
	{
		if (!$this->is_camp_product($product_id)) return $passed;

		$dates_json = isset($_POST['pn_cm_calendar_dates']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_calendar_dates'])) : '';
		$dates = json_decode($dates_json, true);

		if (empty($dates) || !is_array($dates)) {
			if (function_exists('wc_add_notice')) {
				wc_add_notice(__('Por favor, selecciona al menos un día del calendario de productos.', 'pn-customers-manager'), 'error');
			}
			return false;
		}

		// Read qty (plazas)
		$qty = isset($_POST['pn_cm_calendar_qty']) ? max(1, (int) $_POST['pn_cm_calendar_qty']) : 1;
		if ($qty > 100) {
			if (function_exists('wc_add_notice')) {
				wc_add_notice(__('La cantidad máxima de plazas es 100.', 'pn-customers-manager'), 'error');
			}
			return false;
		}

		$today = wp_date('Y-m-d');
		foreach ($dates as $date) {
			if ($date < $today) {
				if (function_exists('wc_add_notice')) {
					wc_add_notice(__('No puedes reservar fechas pasadas.', 'pn-customers-manager'), 'error');
				}
				return false;
			}
		}

		// Parse groups selection (new format: { blockId: { groupId: qty } })
		$groups_json = isset($_POST['pn_cm_calendar_groups']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_calendar_groups'])) : '';
		$selected_groups = json_decode($groups_json, true);
		if (!is_array($selected_groups)) $selected_groups = [];
		$selected_groups = $this->normalize_groups($selected_groups);

		// Parse selected weeks/blocks
		$weeks_json = isset($_POST['pn_cm_calendar_weeks']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_calendar_weeks'])) : '';
		$selected_weeks = json_decode($weeks_json, true);
		if (!is_array($selected_weeks)) $selected_weeks = [];

		$weeks = $this->get_weeks($product_id);
		$week_map = [];
		foreach ($weeks as $w) {
			$week_map[$w['id']] = $w;
		}

		// Find all blocks with groups that have any selected date (full or partial selection)
		$blocks_needing_group = [];
		foreach ($weeks as $w) {
			if (empty($w['groups'])) continue;
			foreach ($w['dates'] as $d) {
				if (in_array($d, $dates)) {
					$blocks_needing_group[$w['id']] = $w;
					break;
				}
			}
		}

		// Validate groups: blocks with groups must have a valid group distribution
		foreach ($blocks_needing_group as $week_id => $week_data) {
			if (empty($selected_groups[$week_id])) {
				if (function_exists('wc_add_notice')) {
					wc_add_notice(__('Please select a group for each block with activity groups.', 'pn-customers-manager'), 'error');
				}
				return false;
			}

			$distribution = $selected_groups[$week_id];
			$valid_group_ids = array_column($week_data['groups'], 'id');

			// Validate all group IDs exist
			foreach ($distribution as $gid => $gqty) {
				if (!in_array($gid, $valid_group_ids, true)) {
					if (function_exists('wc_add_notice')) {
						wc_add_notice(__('El grupo seleccionado no es válido.', 'pn-customers-manager'), 'error');
					}
					return false;
				}
			}

			// Validate distribution sum equals qty
			$dist_sum = array_sum($distribution);
			if ($dist_sum !== $qty) {
				if (function_exists('wc_add_notice')) {
					wc_add_notice(__('The spot distribution does not match the selected total.', 'pn-customers-manager'), 'error');
				}
				return false;
			}
		}

		$availability = $this->get_availability($product_id);

		// Validate availability: each date must have >= qty spots
		foreach ($dates as $date) {
			if (!isset($availability[$date]) || $availability[$date] < $qty) {
				if (function_exists('wc_add_notice')) {
					wc_add_notice(
						// translators: %s is the booking date that has no available spots
						sprintf(__('La fecha %s no tiene suficientes plazas disponibles.', 'pn-customers-manager'), $date),
						'error'
					);
				}
				return false;
			}
		}

		// Validate group-level availability for blocks with groups
		foreach ($blocks_needing_group as $week_id => $week_data) {
			if (!empty($selected_groups[$week_id])) {
				$distribution = $selected_groups[$week_id];
				foreach ($distribution as $group_id => $group_qty) {
					if ($group_qty <= 0) continue;
					foreach ($week_data['dates'] as $date) {
						if (!in_array($date, $dates)) continue;
						$composite_key = $date . '__' . $group_id;
						$group_avail = isset($availability[$composite_key]) ? $availability[$composite_key] : 0;
						if ($group_avail < $group_qty) {
							if (function_exists('wc_add_notice')) {
								wc_add_notice(
									// translators: %s is the booking date that has no available spots in the selected group
									sprintf(__('La fecha %s no tiene suficientes plazas disponibles en el grupo seleccionado.', 'pn-customers-manager'), $date),
									'error'
								);
							}
							return false;
						}
					}
				}
			}
		}

		return $passed;
	}

	/* ───────────────────────────────────────────
	 *  Carrito: guardar datos
	 * ─────────────────────────────────────────── */

	public function add_cart_item_data($cart_item_data, $product_id, $variation_id)
	{
		if (!$this->is_camp_product($product_id)) return $cart_item_data;

		$dates_json = isset($_POST['pn_cm_calendar_dates']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_calendar_dates'])) : '';
		$weeks_json = isset($_POST['pn_cm_calendar_weeks']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_calendar_weeks'])) : '';
		$groups_json = isset($_POST['pn_cm_calendar_groups']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_calendar_groups'])) : '';

		$dates = json_decode($dates_json, true);
		$weeks = json_decode($weeks_json, true);
		$groups = json_decode($groups_json, true);

		if (!is_array($dates)) $dates = [];
		if (!is_array($weeks)) $weeks = [];
		if (!is_array($groups)) $groups = [];

		$groups = $this->normalize_groups($groups);

		$qty = isset($_POST['pn_cm_calendar_qty']) ? max(1, (int) $_POST['pn_cm_calendar_qty']) : 1;
		$is_member = !empty($_POST['pn_cm_calendar_is_member']) && $_POST['pn_cm_calendar_is_member'] === '1';

		$base_price = $this->calculate_price($product_id, $dates, $weeks, $is_member);

		$cart_item_data['pn_cm_calendar_dates']     = $dates;
		$cart_item_data['pn_cm_calendar_weeks']     = $weeks;
		$cart_item_data['pn_cm_calendar_groups']    = $groups;
		$cart_item_data['pn_cm_calendar_is_member'] = $is_member;
		$cart_item_data['pn_cm_calendar_qty']       = $qty;
		$cart_item_data['pn_cm_calendar_price']     = $base_price * $qty;
		$cart_item_data['unique_key']           = md5(microtime() . wp_rand());

		return $cart_item_data;
	}

	/* ───────────────────────────────────────────
	 *  Carrito: mostrar datos
	 * ─────────────────────────────────────────── */

	public function get_item_data($item_data, $cart_item)
	{
		if (empty($cart_item['pn_cm_calendar_dates'])) return $item_data;

		$qty = isset($cart_item['pn_cm_calendar_qty']) ? (int) $cart_item['pn_cm_calendar_qty'] : 1;
		if ($qty < 1) $qty = 1;

		// Show plazas if more than 1
		if ($qty > 1) {
			$item_data[] = [
				'key'   => __('Spots', 'pn-customers-manager'),
				'value' => $qty,
			];
		}

		if (!empty($cart_item['pn_cm_calendar_is_member'])) {
			$member_label = get_post_meta($product_id, '_pn_cm_woo_calendar_member_label', true);
			if (empty($member_label)) {
				$member_label = __('We are a member family', 'pn-customers-manager');
			}
			$item_data[] = [
				'key'   => __('Tarifa', 'pn-customers-manager'),
				'value' => $member_label,
			];
		}

		$product_id = $cart_item['product_id'];
		$week_map = [];
		foreach ($this->get_weeks($product_id) as $week) {
			$week_map[$week['id']] = $week;
		}

		$selected_weeks = isset($cart_item['pn_cm_calendar_weeks']) ? $cart_item['pn_cm_calendar_weeks'] : [];
		$selected_groups = isset($cart_item['pn_cm_calendar_groups']) ? $cart_item['pn_cm_calendar_groups'] : [];
		$selected_groups = $this->normalize_groups($selected_groups);

		if (!empty($selected_weeks)) {
			$labels = [];
			foreach ($selected_weeks as $wid) {
				if (isset($week_map[$wid])) {
					$w = $week_map[$wid];
					$label = !empty($w['label']) ? $w['label'] : $this->format_week_range($w['dates']);
					// Add group distribution info
					if (!empty($w['groups']) && !empty($selected_groups[$wid])) {
						$group_labels = [];
						foreach ($selected_groups[$wid] as $gid => $gqty) {
							if ($gqty <= 0) continue;
							foreach ($w['groups'] as $g) {
								if ($g['id'] === $gid) {
									$group_labels[] = $g['label'] . ' × ' . $gqty;
									break;
								}
							}
						}
						if (!empty($group_labels)) {
							$label .= ' — ' . implode(', ', $group_labels);
						}
					}
					$labels[] = $label;
				}
			}
			if (!empty($labels)) {
				$item_data[] = [
					'key'   => __('Bloques', 'pn-customers-manager'),
					'value' => implode(', ', $labels),
				];
			}
		}

		$covered = [];
		foreach ($selected_weeks as $wid) {
			if (isset($week_map[$wid])) {
				$covered = array_merge($covered, $week_map[$wid]['dates']);
			}
		}
		$loose_days = array_diff($cart_item['pn_cm_calendar_dates'], $covered);

		// Group loose days by block with groups (show group info)
		$loose_by_block = [];
		$truly_loose = [];
		foreach ($loose_days as $day) {
			$found_block = null;
			foreach ($week_map as $wid => $w) {
				if (!empty($w['groups']) && in_array($day, $w['dates'], true)) {
					$found_block = $wid;
					break;
				}
			}
			if ($found_block) {
				$loose_by_block[$found_block][] = $day;
			} else {
				$truly_loose[] = $day;
			}
		}

		// Show loose days with group distribution info
		foreach ($loose_by_block as $wid => $days) {
			$w = $week_map[$wid];
			$label = (!empty($w['label']) ? $w['label'] . ': ' : '') . implode(', ', array_map([$this, 'format_date_short'], $days));
			if (!empty($selected_groups[$wid])) {
				$group_labels = [];
				foreach ($selected_groups[$wid] as $gid => $gqty) {
					if ($gqty <= 0) continue;
					foreach ($w['groups'] as $g) {
						if ($g['id'] === $gid) {
							$group_labels[] = $g['label'] . ' × ' . $gqty;
							break;
						}
					}
				}
				if (!empty($group_labels)) {
					$label .= ' — ' . implode(', ', $group_labels);
				}
			}
			$item_data[] = [
				'key'   => __('Días', 'pn-customers-manager'),
				'value' => $label,
			];
		}

		if (!empty($truly_loose)) {
			$formatted = array_map([$this, 'format_date_short'], array_values($truly_loose));
			$item_data[] = [
				'key'   => __('Individual days', 'pn-customers-manager'),
				'value' => implode(', ', $formatted),
			];
		}

		return $item_data;
	}

	/**
	 * Formatea "2026-07-03" -> "3 jul"
	 */
	private function format_date_short($date_str)
	{
		$months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
		$parts = explode('-', $date_str);
		if (count($parts) !== 3) return $date_str;
		return intval($parts[2]) . ' ' . $months[intval($parts[1]) - 1];
	}

	/**
	 * Formatea un rango de fechas: "25 jun - 3 jul"
	 */
	private function format_week_range($dates)
	{
		if (empty($dates)) return '';
		$first = reset($dates);
		$last  = end($dates);
		if ($first === $last || count($dates) === 1) {
			return $this->format_date_short($first);
		}
		return $this->format_date_short($first) . ' - ' . $this->format_date_short($last);
	}

	/* ───────────────────────────────────────────
	 *  Carrito: sobrescribir precio
	 * ─────────────────────────────────────────── */

	public function calculate_totals($cart)
	{
		if (is_admin() && !defined('DOING_AJAX')) return;
		if (did_action('woocommerce_before_calculate_totals') >= 2) return;

		foreach ($cart->get_cart() as $cart_item) {
			if (isset($cart_item['pn_cm_calendar_price'])) {
				$cart_item['data']->set_price($cart_item['pn_cm_calendar_price']);
			}
		}
	}

	/* ───────────────────────────────────────────
	 *  Pedido: guardar meta
	 * ─────────────────────────────────────────── */

	public function create_order_line_item($item, $cart_item_key, $values, $order)
	{
		if (empty($values['pn_cm_calendar_dates'])) return;

		$qty = isset($values['pn_cm_calendar_qty']) ? max(1, (int) $values['pn_cm_calendar_qty']) : 1;
		$selected_groups = isset($values['pn_cm_calendar_groups']) ? $this->normalize_groups($values['pn_cm_calendar_groups']) : [];

		$item->add_meta_data(__('Fechas calendario de productos', 'pn-customers-manager'), implode(', ', $values['pn_cm_calendar_dates']));

		if ($qty > 1) {
			$item->add_meta_data(__('Spots', 'pn-customers-manager'), $qty);
		}

		$product_id = $values['product_id'];
		$week_map = [];
		foreach ($this->get_weeks($product_id) as $w) {
			$week_map[$w['id']] = $w;
		}

		if (!empty($values['pn_cm_calendar_weeks'])) {
			$labels = [];
			foreach ($values['pn_cm_calendar_weeks'] as $wid) {
				if (isset($week_map[$wid])) {
					$w = $week_map[$wid];
					$label = !empty($w['label']) ? $w['label'] : $this->format_week_range($w['dates']);
					if (!empty($w['groups']) && !empty($selected_groups[$wid])) {
						$group_labels = [];
						foreach ($selected_groups[$wid] as $gid => $gqty) {
							if ($gqty <= 0) continue;
							foreach ($w['groups'] as $g) {
								if ($g['id'] === $gid) {
									$group_labels[] = $g['label'] . ' × ' . $gqty;
									break;
								}
							}
						}
						if (!empty($group_labels)) {
							$label .= ' — ' . implode(', ', $group_labels);
						}
					}
					$labels[] = $label;
				}
			}
			if (!empty($labels)) {
				$item->add_meta_data(__('Bloques', 'pn-customers-manager'), implode(', ', $labels));
			}
		}

		// Show group info for partial blocks (dates not covered by full blocks)
		$covered_dates = [];
		foreach (($values['pn_cm_calendar_weeks'] ?? []) as $wid) {
			if (isset($week_map[$wid])) {
				$covered_dates = array_merge($covered_dates, $week_map[$wid]['dates']);
			}
		}
		$loose_dates = array_diff($values['pn_cm_calendar_dates'], $covered_dates);
		$partial_blocks = [];
		foreach ($loose_dates as $day) {
			foreach ($week_map as $wid => $w) {
				if (!empty($w['groups']) && in_array($day, $w['dates'], true)) {
					$partial_blocks[$wid][] = $day;
					break;
				}
			}
		}
		foreach ($partial_blocks as $wid => $days) {
			$w = $week_map[$wid];
			$label = (!empty($w['label']) ? $w['label'] . ': ' : '') . implode(', ', array_map([$this, 'format_date_short'], $days));
			if (!empty($selected_groups[$wid])) {
				$group_labels = [];
				foreach ($selected_groups[$wid] as $gid => $gqty) {
					if ($gqty <= 0) continue;
					foreach ($w['groups'] as $g) {
						if ($g['id'] === $gid) {
							$group_labels[] = $g['label'] . ' × ' . $gqty;
							break;
						}
					}
				}
				if (!empty($group_labels)) {
					$label .= ' — ' . implode(', ', $group_labels);
				}
			}
			$item->add_meta_data(__('Días', 'pn-customers-manager') . ' (' . ($w['label'] ?: $wid) . ')', $label);
		}

		$item->add_meta_data('_pn_cm_woo_calendar_dates', $values['pn_cm_calendar_dates']);
		$item->add_meta_data('_pn_cm_woo_calendar_weeks', $values['pn_cm_calendar_weeks']);
		$item->add_meta_data('_pn_cm_woo_calendar_qty', $qty);
		if (!empty($selected_groups)) {
			$item->add_meta_data('_pn_cm_woo_calendar_groups', $selected_groups);
		}
	}

	/* ───────────────────────────────────────────
	 *  Pedido: gestión de aforo
	 * ─────────────────────────────────────────── */

	public function increment_bookings($order_id)
	{
		$this->update_bookings($order_id, 'increment');
	}

	public function decrement_bookings($order_id)
	{
		$this->update_bookings($order_id, 'decrement');
	}

	private function update_bookings($order_id, $action)
	{
		if (!function_exists('wc_get_order')) return;

		$order = wc_get_order($order_id);
		if (!$order) return;

		$counted = $order->get_meta('_pn_cm_woo_calendar_bookings_counted');

		if ($action === 'increment' && $counted === 'yes') return;
		if ($action === 'decrement' && $counted !== 'yes') return;

		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();
			if (!$this->is_camp_product($product_id)) continue;

			$dates = $item->get_meta('_pn_cm_woo_calendar_dates');
			if (empty($dates) || !is_array($dates)) continue;

			$qty = $item->get_meta('_pn_cm_woo_calendar_qty');
			$qty = $qty ? max(1, (int) $qty) : 1;

			$item_groups = $item->get_meta('_pn_cm_woo_calendar_groups');
			if (!is_array($item_groups)) $item_groups = [];
			$item_groups = $this->normalize_groups($item_groups);

			$item_weeks = $item->get_meta('_pn_cm_woo_calendar_weeks');
			if (!is_array($item_weeks)) $item_weeks = [];

			$weeks = $this->get_weeks($product_id);
			$week_map = [];
			foreach ($weeks as $w) {
				$week_map[$w['id']] = $w;
			}

			$bookings = $this->get_bookings($product_id);

			// Build list of block IDs that have group selections (both full and partial)
			$blocks_with_group_selection = array_unique(array_merge($item_weeks, array_keys($item_groups)));

			foreach ($dates as $date) {
				// Update day-level booking by qty
				if (!isset($bookings[$date])) $bookings[$date] = 0;
				if ($action === 'increment') {
					$bookings[$date] += $qty;
				} else {
					$bookings[$date] = max(0, $bookings[$date] - $qty);
				}

				// Update group-level bookings by distribution
				foreach ($blocks_with_group_selection as $wid) {
					if (isset($week_map[$wid]) && !empty($week_map[$wid]['groups']) && !empty($item_groups[$wid])) {
						$w = $week_map[$wid];
						if (in_array($date, $w['dates'], true)) {
							foreach ($item_groups[$wid] as $group_id => $group_qty) {
								if ($group_qty <= 0) continue;
								$composite_key = $date . '__' . $group_id;
								if (!isset($bookings[$composite_key])) $bookings[$composite_key] = 0;
								if ($action === 'increment') {
									$bookings[$composite_key] += $group_qty;
								} else {
									$bookings[$composite_key] = max(0, $bookings[$composite_key] - $group_qty);
								}
							}
						}
					}
				}
			}

			update_post_meta($product_id, '_pn_cm_woo_calendar_bookings', $bookings);
		}

		if ($action === 'increment') {
			$order->update_meta_data('_pn_cm_woo_calendar_bookings_counted', 'yes');
		} else {
			$order->update_meta_data('_pn_cm_woo_calendar_bookings_counted', 'no');
		}
		$order->save();
	}

	/* ───────────────────────────────────────────
	 *  AJAX: disponibilidad
	 * ─────────────────────────────────────────── */

	public function ajax_get_availability()
	{
		check_ajax_referer('pn_cm_calendar_nonce', 'nonce');
		$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
		if (!$product_id || !$this->is_camp_product($product_id)) {
			wp_send_json_error();
		}
		wp_send_json_success($this->get_availability($product_id));
	}

	/* ───────────────────────────────────────────
	 *  AJAX: reservas de un día (solo admin)
	 * ─────────────────────────────────────────── */

	public function ajax_get_date_bookings()
	{
		check_ajax_referer('pn_cm_calendar_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => 'No autorizado']);
		}

		$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
		$date       = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

		if (!$product_id || !$this->is_camp_product($product_id) || !$date) {
			wp_send_json_error(['message' => 'Parámetros inválidos']);
		}

		// Obtener capacidad del día y grupos
		$weeks    = $this->get_weeks($product_id);
		$capacity = 0;
		$block_groups = [];
		foreach ($weeks as $week) {
			if (in_array($date, $week['dates'], true)) {
				if (!empty($week['groups'])) {
					$block_groups = $week['groups'];
					$capacity = 0;
					foreach ($week['groups'] as $g) {
						$capacity += (int) $g['capacity'];
					}
				} else {
					$capacity = (int) $week['capacity'];
				}
				break;
			}
		}

		$bookings_count = $this->get_bookings($product_id);
		$booked         = isset($bookings_count[$date]) ? (int) $bookings_count[$date] : 0;

		// Group breakdown
		$groups_breakdown = [];
		if (!empty($block_groups)) {
			foreach ($block_groups as $g) {
				$composite_key = $date . '__' . $g['id'];
				$group_booked = isset($bookings_count[$composite_key]) ? (int) $bookings_count[$composite_key] : 0;
				$groups_breakdown[] = [
					'id'       => $g['id'],
					'label'    => $g['label'],
					'capacity' => (int) $g['capacity'],
					'booked'   => $group_booked,
				];
			}
		}

		// Buscar pedidos con este producto y fecha
		if (!function_exists('wc_get_orders')) {
			wp_send_json_error(['message' => 'WooCommerce no disponible']);
		}

		$orders = wc_get_orders([
			'status' => ['processing', 'completed'],
			'limit'  => -1,
		]);

		$reservations = [];
		foreach ($orders as $order) {
			foreach ($order->get_items() as $item) {
				if ($item->get_product_id() !== $product_id) continue;

				$item_dates = $item->get_meta('_pn_cm_woo_calendar_dates');
				if (!is_array($item_dates) || !in_array($date, $item_dates, true)) continue;

				$item_groups = $item->get_meta('_pn_cm_woo_calendar_groups');
				if (!is_array($item_groups)) $item_groups = [];
				$item_groups = $this->normalize_groups($item_groups);

				$item_qty = $item->get_meta('_pn_cm_woo_calendar_qty');
				$item_qty = $item_qty ? max(1, (int) $item_qty) : 1;

				$group_label = '';
				foreach ($item_groups as $wid => $distribution) {
					foreach ($weeks as $w) {
						if ($w['id'] === $wid && in_array($date, $w['dates'], true)) {
							$parts = [];
							foreach ($distribution as $gid => $gqty) {
								if ($gqty <= 0) continue;
								foreach ($w['groups'] as $g) {
									if ($g['id'] === $gid) {
										$parts[] = $g['label'] . ($gqty > 1 ? ' × ' . $gqty : '');
										break;
									}
								}
							}
							$group_label = implode(', ', $parts);
							break 2;
						}
					}
				}

				$reservations[] = [
					'order_id'    => $order->get_id(),
					'order_date'  => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i') : '',
					'status'      => $order->get_status(),
					'first_name'  => $order->get_billing_first_name(),
					'last_name'   => $order->get_billing_last_name(),
					'email'       => $order->get_billing_email(),
					'phone'       => $order->get_billing_phone(),
					'notes'       => $order->get_customer_note(),
					'edit_url'    => admin_url('post.php?post=' . $order->get_id() . '&action=edit'),
					'group'       => $group_label,
					'qty'         => $item_qty,
				];
			}
		}

		wp_send_json_success([
			'date'             => $date,
			'capacity'         => $capacity,
			'booked'           => $booked,
			'reservations'     => $reservations,
			'groups_breakdown' => $groups_breakdown,
		]);
	}

	/* ───────────────────────────────────────────
	 *  AJAX: resumen facturación (solo admin)
	 * ─────────────────────────────────────────── */

	public function ajax_get_admin_summary()
	{
		check_ajax_referer('pn_cm_calendar_nonce', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => 'No autorizado']);
		}

		$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
		if (!$product_id || !$this->is_camp_product($product_id)) {
			wp_send_json_error(['message' => 'Parámetros inválidos']);
		}

		if (!function_exists('wc_get_orders')) {
			wp_send_json_error(['message' => 'WooCommerce no disponible']);
		}

		$weeks = $this->get_weeks($product_id);

		// Mapa de fecha -> week index para asignar revenue por bloque
		$date_to_week = [];
		foreach ($weeks as $i => $week) {
			foreach ($week['dates'] as $d) {
				$date_to_week[$d] = $i;
			}
		}

		$orders = wc_get_orders([
			'status' => ['processing', 'completed'],
			'limit'  => -1,
		]);

		$total_revenue    = 0;
		$total_bookings   = 0;
		$order_ids        = [];
		$week_stats       = [];
		foreach ($weeks as $i => $week) {
			$week_stats[$i] = [
				'label'    => $week['label'] ?: ('Bloque ' . ($i + 1)),
				'bookings' => 0,
				'revenue'  => 0,
			];
		}

		foreach ($orders as $order) {
			foreach ($order->get_items() as $item) {
				if ($item->get_product_id() !== $product_id) continue;

				$item_dates = $item->get_meta('_pn_cm_woo_calendar_dates');
				if (!is_array($item_dates) || empty($item_dates)) continue;

				$line_total = (float) $item->get_total();
				$order_ids[$order->get_id()] = true;
				$total_revenue  += $line_total;
				$total_bookings += count($item_dates);

				$per_day = count($item_dates) > 0 ? $line_total / count($item_dates) : 0;
				foreach ($item_dates as $d) {
					if (isset($date_to_week[$d])) {
						$wi = $date_to_week[$d];
						$week_stats[$wi]['bookings']++;
						$week_stats[$wi]['revenue'] += $per_day;
					}
				}
			}
		}

		foreach ($week_stats as &$ws) {
			$ws['revenue'] = round($ws['revenue'], 2);
		}
		unset($ws);

		wp_send_json_success([
			'total_orders'   => count($order_ids),
			'total_bookings' => $total_bookings,
			'total_revenue'  => round($total_revenue, 2),
			'currency'       => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
			'weeks'          => array_values($week_stats),
		]);
	}

	/* ───────────────────────────────────────────
	 *  Admin: edición de fechas en pedidos
	 * ─────────────────────────────────────────── */

	public function add_order_calendar_meta_box()
	{
		if (!current_user_can('manage_woocommerce')) return;

		$screen = class_exists('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')
			&& wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id('shop-order')
			: 'shop_order';

		add_meta_box(
			'pn_cm_calendar_order_dates',
			__('Fechas de Calendario de Productos', 'pn-customers-manager'),
			[$this, 'render_order_calendar_meta_box'],
			$screen,
			'normal',
			'default'
		);
	}

	public function render_order_calendar_meta_box($post_or_order)
	{
		$order = $post_or_order instanceof WP_Post
			? wc_get_order($post_or_order->ID)
			: $post_or_order;

		if (!$order) return;

		$has_calendar_items = false;
		$items_html = '';

		foreach ($order->get_items() as $item_id => $item) {
			$product_id = $item->get_product_id();
			if (!$this->is_camp_product($product_id)) continue;

			$has_calendar_items = true;
			$dates = $item->get_meta('_pn_cm_woo_calendar_dates');
			$weeks_ids = $item->get_meta('_pn_cm_woo_calendar_weeks');
			$item_groups = $item->get_meta('_pn_cm_woo_calendar_groups');

			if (empty($dates) || !is_array($dates)) continue;
			if (!is_array($item_groups)) $item_groups = [];

			$product = wc_get_product($product_id);
			$product_name = $product ? $product->get_name() : __('Producto', 'pn-customers-manager');

			$weeks = $this->get_weeks($product_id);
			$availability = $this->get_availability($product_id);

			// Preparar datos para JavaScript
			$weeks_for_js = [];
			foreach ($weeks as $w) {
				$weeks_for_js[] = [
					'id'            => $w['id'],
					'label'         => $w['label'],
					'dates'         => $w['dates'],
					'price'         => (float) $w['price'],
					'member_price'  => isset($w['member_price']) ? (float) $w['member_price'] : (float) $w['price'],
					'capacity'      => (int) $w['capacity'],
					'discount'      => (float) $w['discount'],
					'discount_type' => $w['discount_type'],
					'groups'        => $w['groups'],
				];
			}

			$items_html .= '<div class="pn-cm-calendar-order-item" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px;">';
			$items_html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">';
			$items_html .= '<strong style="font-size: 14px;">' . esc_html($product_name) . '</strong>';
			$items_html .= '<button type="button" class="button button-primary pn-cm-calendar-edit-dates" ';
			$items_html .= 'data-item-id="' . esc_attr($item_id) . '" ';
			$items_html .= 'data-product-id="' . esc_attr($product_id) . '" ';
			$items_html .= 'data-order-id="' . esc_attr($order->get_id()) . '" ';
			$items_html .= 'data-current-dates="' . esc_attr(wp_json_encode($dates)) . '" ';
			$items_html .= 'data-current-weeks="' . esc_attr(wp_json_encode($weeks_ids ?: [])) . '" ';
			$items_html .= 'data-current-groups="' . esc_attr(wp_json_encode($item_groups)) . '" ';
			$items_html .= 'data-weeks="' . esc_attr(wp_json_encode($weeks_for_js)) . '" ';
			$items_html .= 'data-day-price="' . esc_attr($this->get_day_price($product_id)) . '" ';
			$items_html .= 'data-availability="' . esc_attr(wp_json_encode($availability)) . '" ';
			$item_qty = $item->get_meta('_pn_cm_woo_calendar_qty');
			$item_qty = $item_qty ? max(1, (int) $item_qty) : 1;
			$items_html .= 'data-qty="' . esc_attr($item_qty) . '">';
			$items_html .= __('Editar Fechas', 'pn-customers-manager');
			$items_html .= '</button>';
			$items_html .= '</div>';
			$items_html .= '<div class="pn-cm-calendar-dates-display" id="pn-cm-calendar-dates-display-' . esc_attr($item_id) . '">';
			$items_html .= $this->render_dates_pills($dates, $weeks_ids, $weeks, $item_groups);
			$items_html .= '</div>';
			$items_html .= '</div>';
		}

		if (!$has_calendar_items) {
			echo '<p>' . esc_html__('Este pedido no contiene productos de calendario.', 'pn-customers-manager') . '</p>';
			return;
		}

		echo wp_kses_post($items_html);
	}

	public function order_edit_scripts($hook)
	{
		$is_order_edit = in_array($hook, ['post.php', 'post-new.php']) ||
			(class_exists('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') &&
				wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled() &&
				strpos($hook, 'woocommerce_page_wc-orders') !== false);

		if (!$is_order_edit) return;

		global $post;
		$order = null;

		if ($post && $post->post_type === 'shop_order') {
			$order = wc_get_order($post->ID);
		} elseif (isset($_GET['id'])) {
			$order = wc_get_order(absint($_GET['id']));
		}

		if (!$order) return;

		$has_calendar = false;
		foreach ($order->get_items() as $item) {
			if ($this->is_camp_product($item->get_product_id())) {
				$has_calendar = true;
				break;
			}
		}

		if (!$has_calendar) return;

		$plugin_url = PN_CUSTOMERS_MANAGER_URL;
		$plugin_dir = PN_CUSTOMERS_MANAGER_DIR;

		wp_enqueue_style(
			'pn-cm-calendar-booking-css',
			$plugin_url . 'assets/css/pn-cm-calendar-booking.css',
			[],
			filemtime($plugin_dir . 'assets/css/pn-cm-calendar-booking.css')
		);

		wp_enqueue_script(
			'pn-cm-calendar-order-edit',
			$plugin_url . 'assets/js/pn-cm-calendar-order-edit.js',
			['jquery'],
			filemtime($plugin_dir . 'assets/js/pn-cm-calendar-order-edit.js'),
			true
		);

		wp_localize_script('pn-cm-calendar-order-edit', 'pnCmCalendarOrderEdit', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('pn_cm_calendar_order_edit'),
			'i18n'    => [
				'editDates'       => __('Editar Fechas', 'pn-customers-manager'),
				'savingChanges'   => __('Guardando cambios...', 'pn-customers-manager'),
				'success'         => __('Fechas actualizadas correctamente', 'pn-customers-manager'),
				'error'           => __('Error al actualizar fechas', 'pn-customers-manager'),
				'selectDates'     => __('Selecciona las nuevas fechas', 'pn-customers-manager'),
				'currentDates'    => __('Fechas actuales', 'pn-customers-manager'),
				'newDates'        => __('Nuevas fechas', 'pn-customers-manager'),
				'save'            => __('Guardar Cambios', 'pn-customers-manager'),
				'cancel'          => __('Cancelar', 'pn-customers-manager'),
				'total'           => __('Total', 'pn-customers-manager'),
				'available'       => __('Available', 'pn-customers-manager'),
				'selected'        => __('Selected', 'pn-customers-manager'),
				'full'            => __('Full', 'pn-customers-manager'),
				'noAvailability'  => __('No hay disponibilidad para las fechas seleccionadas', 'pn-customers-manager'),
				'selectAtLeast'   => __('Selecciona al menos una fecha', 'pn-customers-manager'),
				'group'           => __('Group', 'pn-customers-manager'),
				'selectGroup'     => __('Select group', 'pn-customers-manager'),
				'spotsLeft'       => __('spots', 'pn-customers-manager'),
				'plazas'          => __('Spots', 'pn-customers-manager'),
				'distributeSpots' => __('Distribute spots', 'pn-customers-manager'),
				'invalidDistribution' => __('The spot distribution does not match the selected total.', 'pn-customers-manager'),
			],
			'months' => [
				__('January', 'pn-customers-manager'), __('February', 'pn-customers-manager'), __('March', 'pn-customers-manager'),
				__('April', 'pn-customers-manager'), __('May', 'pn-customers-manager'), __('June', 'pn-customers-manager'),
				__('July', 'pn-customers-manager'), __('August', 'pn-customers-manager'), __('September', 'pn-customers-manager'),
				__('October', 'pn-customers-manager'), __('November', 'pn-customers-manager'), __('December', 'pn-customers-manager'),
			],
			'weekDays' => [
				__('M', 'pn-customers-manager'), __('T', 'pn-customers-manager'), __('W', 'pn-customers-manager'),
				__('T', 'pn-customers-manager'), __('F', 'pn-customers-manager'), __('S', 'pn-customers-manager'), __('S', 'pn-customers-manager'),
			],
			'currency' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
		]);
	}

	public function ajax_update_order_dates()
	{
		check_ajax_referer('pn_cm_calendar_order_edit', 'nonce');

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción', 'pn-customers-manager')]);
		}

		$order_id   = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
		$item_id    = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
		$new_dates  = isset($_POST['dates']) ? json_decode(stripslashes($_POST['dates']), true) : [];
		$new_weeks  = isset($_POST['weeks']) ? json_decode(stripslashes($_POST['weeks']), true) : [];
		$new_groups = isset($_POST['groups']) ? json_decode(stripslashes($_POST['groups']), true) : [];
		$new_qty    = isset($_POST['qty']) ? max(1, (int) $_POST['qty']) : 1;
		$new_groups = $this->normalize_groups($new_groups);

		if (!$order_id || !$item_id || empty($new_dates) || !is_array($new_dates)) {
			wp_send_json_error(['message' => __('Parámetros inválidos', 'pn-customers-manager')]);
		}

		$order = wc_get_order($order_id);
		if (!$order) {
			wp_send_json_error(['message' => __('Pedido no encontrado', 'pn-customers-manager')]);
		}

		$item = $order->get_item($item_id);
		if (!$item) {
			wp_send_json_error(['message' => __('Item no encontrado', 'pn-customers-manager')]);
		}

		$product_id = $item->get_product_id();
		if (!$this->is_camp_product($product_id)) {
			wp_send_json_error(['message' => __('Este no es un producto de calendario', 'pn-customers-manager')]);
		}

		// Obtener fechas y grupos actuales
		$old_dates = $item->get_meta('_pn_cm_woo_calendar_dates');
		$old_weeks = $item->get_meta('_pn_cm_woo_calendar_weeks');
		$old_groups = $item->get_meta('_pn_cm_woo_calendar_groups');
		$old_qty = $item->get_meta('_pn_cm_woo_calendar_qty');
		if (!is_array($old_dates)) $old_dates = [];
		if (!is_array($old_weeks)) $old_weeks = [];
		if (!is_array($old_groups)) $old_groups = [];
		$old_groups = $this->normalize_groups($old_groups);
		$old_qty = $old_qty ? max(1, (int) $old_qty) : 1;

		// Validar disponibilidad simulada (excluyendo las fechas actuales del item)
		$simulated_availability = $this->get_simulated_availability($product_id, $old_dates, $old_groups, $old_weeks, $old_qty);

		foreach ($new_dates as $date) {
			if (!isset($simulated_availability[$date]) || $simulated_availability[$date] < $new_qty) {
				wp_send_json_error([
					'message' => sprintf(
						/* translators: %s is the booking date that has no available spots */
					__('La fecha %s no tiene suficientes plazas disponibles', 'pn-customers-manager'),
						$date
					)
				]);
			}
		}

		// Actualización atómica
		$this->update_booking_dates_atomic($product_id, $old_dates, $new_dates, $old_groups, $new_groups, $old_weeks, $new_weeks, $order, $old_qty, $new_qty);

		// Actualizar meta del item
		$item->update_meta_data('_pn_cm_woo_calendar_dates', $new_dates);
		$item->update_meta_data('_pn_cm_woo_calendar_weeks', $new_weeks);
		$item->update_meta_data('_pn_cm_woo_calendar_qty', $new_qty);
		if (!empty($new_groups)) {
			$item->update_meta_data('_pn_cm_woo_calendar_groups', $new_groups);
		}

		// Actualizar meta visible
		$item->update_meta_data(__('Fechas calendario de productos', 'pn-customers-manager'), implode(', ', $new_dates));

		$weeks = $this->get_weeks($product_id);
		if (!empty($new_weeks)) {
			$week_map = [];
			foreach ($weeks as $w) {
				$week_map[$w['id']] = $w;
			}
			$labels = [];
			foreach ($new_weeks as $wid) {
				if (isset($week_map[$wid])) {
					$w = $week_map[$wid];
					$label = !empty($w['label']) ? $w['label'] : $this->format_week_range($w['dates']);
					if (!empty($w['groups']) && !empty($new_groups[$wid])) {
						foreach ($w['groups'] as $g) {
							if ($g['id'] === $new_groups[$wid]) {
								$label .= ' — ' . $g['label'];
								break;
							}
						}
					}
					$labels[] = $label;
				}
			}
			if (!empty($labels)) {
				$item->update_meta_data(__('Bloques', 'pn-customers-manager'), implode(', ', $labels));
			}
		}

		// Recalcular precio (base × qty)
		$new_price = $this->calculate_price($product_id, $new_dates, $new_weeks, false) * $new_qty;
		$item->set_subtotal($new_price);
		$item->set_total($new_price);
		$item->save();

		$order->calculate_totals();

		$old_dates_str = implode(', ', $old_dates);
		$new_dates_str = implode(', ', $new_dates);
		$note = sprintf(
			/* translators: %1$s is the user display name, %2$s is the old dates, %3$s is the new dates, %4$s is the new price */
		__('Fechas de calendario actualizadas por %1$s. Fechas anteriores: %2$s. Nuevas fechas: %3$s. Nuevo precio: %4$s', 'pn-customers-manager'),
			wp_get_current_user()->display_name,
			$old_dates_str,
			$new_dates_str,
			wc_price($new_price)
		);
		$order->add_order_note($note);
		$order->save();

		$dates_html = $this->render_dates_pills($new_dates, $new_weeks, $weeks, $new_groups);

		wp_send_json_success([
			'message'    => __('Fechas actualizadas correctamente', 'pn-customers-manager'),
			'new_price'  => wc_price($new_price),
			'dates_html' => $dates_html,
		]);
	}

	/**
	 * Obtener disponibilidad simulada excluyendo fechas y grupos específicos
	 */
	private function get_simulated_availability($product_id, $exclude_dates, $exclude_groups = [], $exclude_weeks = [], $exclude_qty = 1)
	{
		$weeks    = $this->get_weeks($product_id);
		$bookings = $this->get_bookings($product_id);
		$availability = [];
		$exclude_groups = $this->normalize_groups($exclude_groups);

		$week_map = [];
		foreach ($weeks as $week) {
			$week_map[$week['id']] = $week;
		}

		foreach ($weeks as $week) {
			$has_groups = !empty($week['groups']);
			$block_capacity = (int) $week['capacity'];

			foreach ($week['dates'] as $date) {
				if ($has_groups) {
					$day_total_capacity = 0;
					$day_total_booked = 0;
					foreach ($week['groups'] as $group) {
						$group_capacity = (int) $group['capacity'];
						$composite_key = $date . '__' . $group['id'];
						$group_booked = isset($bookings[$composite_key]) ? (int) $bookings[$composite_key] : 0;

						// If excluding this date+group combination, free spots by distribution qty
						if (in_array($date, $exclude_dates, true)) {
							foreach ($exclude_weeks as $ewid) {
								if ($ewid === $week['id'] && !empty($exclude_groups[$ewid]) && isset($exclude_groups[$ewid][$group['id']])) {
									$group_booked = max(0, $group_booked - (int) $exclude_groups[$ewid][$group['id']]);
								}
							}
						}

						$availability[$composite_key] = max(0, $group_capacity - $group_booked);
						$day_total_capacity += $group_capacity;
						$day_total_booked += $group_booked;
					}
					$availability[$date] = max(0, $day_total_capacity - $day_total_booked);
				} else {
					$booked = isset($bookings[$date]) ? (int) $bookings[$date] : 0;
					if (in_array($date, $exclude_dates, true)) {
						$booked = max(0, $booked - $exclude_qty);
					}
					$availability[$date] = max(0, $block_capacity - $booked);
				}
			}
		}

		return $availability;
	}

	/**
	 * Actualizar bookings de forma atómica (with group support)
	 */
	private function update_booking_dates_atomic($product_id, $old_dates, $new_dates, $old_groups, $new_groups, $old_weeks, $new_weeks, $order, $old_qty = 1, $new_qty = 1)
	{
		$counted = $order->get_meta('_pn_cm_woo_calendar_bookings_counted');
		if ($counted !== 'yes') return;

		$weeks = $this->get_weeks($product_id);
		$week_map = [];
		foreach ($weeks as $w) {
			$week_map[$w['id']] = $w;
		}

		$old_groups = $this->normalize_groups($old_groups);
		$new_groups = $this->normalize_groups($new_groups);

		$bookings = $this->get_bookings($product_id);

		// Decrementar fechas viejas by old_qty
		foreach ($old_dates as $date) {
			if (isset($bookings[$date])) {
				$bookings[$date] = max(0, $bookings[$date] - $old_qty);
			}
			// Decrementar grupo viejo by distribution
			foreach ($old_weeks as $wid) {
				if (isset($week_map[$wid]) && !empty($week_map[$wid]['groups']) && !empty($old_groups[$wid])) {
					if (in_array($date, $week_map[$wid]['dates'], true)) {
						foreach ($old_groups[$wid] as $group_id => $group_qty) {
							if ($group_qty <= 0) continue;
							$composite_key = $date . '__' . $group_id;
							if (isset($bookings[$composite_key])) {
								$bookings[$composite_key] = max(0, $bookings[$composite_key] - $group_qty);
							}
						}
					}
				}
			}
		}

		// Incrementar fechas nuevas by new_qty
		foreach ($new_dates as $date) {
			if (!isset($bookings[$date])) $bookings[$date] = 0;
			$bookings[$date] += $new_qty;
			// Incrementar grupo nuevo by distribution
			foreach ($new_weeks as $wid) {
				if (isset($week_map[$wid]) && !empty($week_map[$wid]['groups']) && !empty($new_groups[$wid])) {
					if (in_array($date, $week_map[$wid]['dates'], true)) {
						foreach ($new_groups[$wid] as $group_id => $group_qty) {
							if ($group_qty <= 0) continue;
							$composite_key = $date . '__' . $group_id;
							if (!isset($bookings[$composite_key])) $bookings[$composite_key] = 0;
							$bookings[$composite_key] += $group_qty;
						}
					}
				}
			}
		}

		update_post_meta($product_id, '_pn_cm_woo_calendar_bookings', $bookings);
	}

	/**
	 * Renderizar pills de fechas para display
	 */
	private function render_dates_pills($dates, $weeks_ids, $weeks, $item_groups = [])
	{
		if (empty($dates)) {
			return '<p style="color: #999; font-size: 13px;">' . __('Sin fechas', 'pn-customers-manager') . '</p>';
		}

		if (!is_array($weeks_ids)) $weeks_ids = [];
		if (!is_array($item_groups)) $item_groups = [];

		$week_map = [];
		foreach ($weeks as $w) {
			$week_map[$w['id']] = $w;
		}

		$html = '';

		// Mostrar bloques
		if (!empty($weeks_ids)) {
			$html .= '<div style="margin-bottom: 8px;">';
			$html .= '<strong style="font-size: 12px; color: #666;">' . __('Bloques:', 'pn-customers-manager') . '</strong><br>';
			foreach ($weeks_ids as $wid) {
				if (isset($week_map[$wid])) {
					$w = $week_map[$wid];
					$label = !empty($w['label']) ? $w['label'] : $this->format_week_range($w['dates']);
					if (!empty($w['groups']) && !empty($item_groups[$wid])) {
						foreach ($w['groups'] as $g) {
							if ($g['id'] === $item_groups[$wid]) {
								$label .= ' — ' . $g['label'];
								break;
							}
						}
					}
					$html .= '<span style="display: inline-block; background: #e3f2fd; color: #1976d2; padding: 4px 10px; border-radius: 12px; font-size: 12px; margin: 2px 4px 2px 0;">';
					$html .= esc_html($label);
					$html .= '</span>';
				}
			}
			$html .= '</div>';
		}

		// Mostrar días sueltos
		$covered = [];
		foreach ($weeks_ids as $wid) {
			if (isset($week_map[$wid])) {
				$covered = array_merge($covered, $week_map[$wid]['dates']);
			}
		}
		$loose_days = array_diff($dates, $covered);

		if (!empty($loose_days)) {
			$html .= '<div>';
			$html .= '<strong style="font-size: 12px; color: #666;">' . __('Días sueltos:', 'pn-customers-manager') . '</strong><br>';
			foreach ($loose_days as $date) {
				$formatted = $this->format_date_short($date);
				$html .= '<span style="display: inline-block; background: #f3f4f6; color: #374151; padding: 4px 10px; border-radius: 12px; font-size: 12px; margin: 2px 4px 2px 0;">';
				$html .= esc_html($formatted);
				$html .= '</span>';
			}
			$html .= '</div>';
		}

		return $html;
	}
}
