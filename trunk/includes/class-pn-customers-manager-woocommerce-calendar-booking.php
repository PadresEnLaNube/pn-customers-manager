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
		return is_array($weeks) ? $weeks : [];
	}

	private function get_day_price($product_id)
	{
		return (float) get_post_meta($product_id, '_pn_cm_woo_calendar_day_price', true);
	}

	private function get_member_day_price($product_id)
	{
		return (float) get_post_meta($product_id, '_pn_cm_woo_calendar_member_day_price', true);
	}

	/**
	 * Check if current user has member role for this product
	 *
	 * @param int $product_id Product ID
	 * @return bool
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
			$capacity = (int) $week['capacity'];
			foreach ($week['dates'] as $date) {
				$booked = isset($bookings[$date]) ? (int) $bookings[$date] : 0;
				$availability[$date] = max(0, $capacity - $booked);
			}
		}
		return $availability;
	}

	private function calculate_price($product_id, $selected_dates, $selected_weeks, $is_member = false)
	{
		$weeks     = $this->get_weeks($product_id);
		$day_price = $is_member ? $this->get_member_day_price($product_id) : $this->get_day_price($product_id);
		$total     = 0;

		$week_map = [];
		foreach ($weeks as $week) {
			$week_map[$week['id']] = $week;
		}

		// Semanas completas
		$covered_dates = [];
		foreach ($selected_weeks as $week_id) {
			if (isset($week_map[$week_id])) {
				$w = $week_map[$week_id];
				$week_price = $is_member && isset($w['member_price']) ? (float) $w['member_price'] : (float) $w['price'];
				$total += $week_price;
				foreach ($w['dates'] as $d) {
					$covered_dates[] = $d;
				}
			}
		}

		// Días sueltos
		foreach ($selected_dates as $date) {
			if (!in_array($date, $covered_dates)) {
				$total += $day_price;
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
		$product_id       = $post->ID;
		$day_price        = get_post_meta($product_id, '_pn_cm_woo_calendar_day_price', true);
		$member_day_price = get_post_meta($product_id, '_pn_cm_woo_calendar_member_day_price', true);
		$schedule         = get_post_meta($product_id, '_pn_cm_woo_calendar_schedule', true);
		$weeks            = $this->get_weeks($product_id);
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
				}
				if (function_exists('woocommerce_wp_text_input')) {
					woocommerce_wp_text_input([
						'id'          => '_pn_cm_woo_calendar_schedule',
						'label'       => __('Horario', 'pn-customers-manager'),
						'placeholder' => '9:00 - 14:30 (madrugadores desde 8:30)',
					]);
				}
				?>
			</div>

			<!-- Roles con descuento de socio -->
			<div class="options_group">
				<?php
				$all_roles = wp_roles()->get_names();
				$selected_roles = get_post_meta($post->ID, '_pn_cm_woo_calendar_member_roles', true);
				if (!is_array($selected_roles)) $selected_roles = [];
				?>
				<p class="form-field">
					<label><?php _e('Roles con descuento de socio', 'pn-customers-manager'); ?></label>
					<select name="_pn_cm_woo_calendar_member_roles[]" multiple style="width:100%;min-height:100px;">
						<?php foreach ($all_roles as $role_key => $role_name): ?>
							<option value="<?php echo esc_attr($role_key); ?>" <?php echo in_array($role_key, $selected_roles) ? 'selected' : ''; ?>>
								<?php echo esc_html($role_name); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="description"><?php _e('Selecciona qué roles de usuario obtienen el precio de socio. Mantén Ctrl/Cmd para seleccionar múltiples.', 'pn-customers-manager'); ?></span>
				</p>
			</div>

			<!-- Precios por día -->
			<div class="options_group">
				<p class="form-field _pn_cm_woo_calendar_day_price_field">
					<label><?php _e('Precio por día suelto', 'pn-customers-manager'); ?></label>
					<span class="wrap">
						<span class="pn-cm-calendar-admin-price-pair">
							<span class="pn-cm-calendar-admin-price-label"><?php _e('No socios', 'pn-customers-manager'); ?></span>
							<input type="number" id="_pn_cm_woo_calendar_day_price" name="_pn_cm_woo_calendar_day_price"
							       value="<?php echo esc_attr($day_price); ?>"
							       placeholder="25.00" step="0.01" min="0" class="short" />
							<span class="pn-cm-calendar-admin-price-currency">&euro;</span>
						</span>
						<span class="pn-cm-calendar-admin-price-pair">
							<span class="pn-cm-calendar-admin-price-label"><?php _e('Socios', 'pn-customers-manager'); ?></span>
							<input type="number" id="_pn_cm_woo_calendar_member_day_price" name="_pn_cm_woo_calendar_member_day_price"
							       value="<?php echo esc_attr($member_day_price); ?>"
							       placeholder="20.00" step="0.01" min="0" class="short" />
							<span class="pn-cm-calendar-admin-price-currency">&euro;</span>
						</span>
					</span>
				</p>
			</div>

			<!-- Semanas -->
			<div class="options_group pn-cm-calendar-weeks-section">
				<p class="form-field">
					<label class="pn-cm-calendar-section-title"><?php _e('Semanas del calendario de productos', 'pn-customers-manager'); ?></label>
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
						<?php _e('Añadir semana', 'pn-customers-manager'); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_week_row($index, $week = [])
	{
		$defaults = [
			'id' => '', 'label' => '', 'dates' => [],
			'price' => '', 'member_price' => '', 'capacity' => 25,
		];
		$week = wp_parse_args($week, $defaults);
		$date_start = !empty($week['dates']) ? $week['dates'][0] : '';
		$date_end   = !empty($week['dates']) ? end($week['dates']) : '';
		$n = $index + 1;
		?>
		<div class="pn-cm-calendar-week-row" data-index="<?php echo esc_attr($index); ?>">
			<div class="pn-cm-calendar-week-header">
				<span class="pn-cm-calendar-week-handle dashicons dashicons-move"></span>
				<span class="pn-cm-calendar-week-number">#<?php echo $n; ?></span>
				<span class="pn-cm-calendar-week-header-label">
					<?php echo !empty($week['label']) ? esc_html($week['label']) : __('Nueva semana', 'pn-customers-manager'); ?>
				</span>
				<button type="button" class="pn-cm-calendar-remove-week" title="<?php esc_attr_e('Eliminar', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
			<div class="pn-cm-calendar-week-body">
				<!-- Fila 1: ID + Etiqueta -->
				<div class="pn-cm-calendar-row">
					<div class="pn-cm-calendar-field">
						<label><?php _e('Identificador', 'pn-customers-manager'); ?></label>
						<input type="text" name="_pn_cm_woo_calendar_weeks[<?php echo $index; ?>][id]"
						       value="<?php echo esc_attr($week['id']); ?>"
						       placeholder="semana-1" />
						<span class="pn-cm-calendar-field-hint"><?php _e('Uso interno, sin espacios', 'pn-customers-manager'); ?></span>
					</div>
					<div class="pn-cm-calendar-field">
						<label><?php _e('Nombre visible', 'pn-customers-manager'); ?></label>
						<input type="text" name="_pn_cm_woo_calendar_weeks[<?php echo $index; ?>][label]"
						       value="<?php echo esc_attr($week['label']); ?>"
						       placeholder="Semana 1" class="pn-cm-calendar-label-input" />
					</div>
				</div>
				<!-- Fila 2: Fecha inicio + Fecha fin + Aforo -->
				<div class="pn-cm-calendar-row">
					<div class="pn-cm-calendar-field">
						<label><?php _e('Fecha inicio', 'pn-customers-manager'); ?></label>
						<input type="date" name="_pn_cm_woo_calendar_weeks[<?php echo $index; ?>][date_start]"
						       value="<?php echo esc_attr($date_start); ?>" class="pn-cm-calendar-date-input" />
					</div>
					<div class="pn-cm-calendar-field">
						<label><?php _e('Fecha fin', 'pn-customers-manager'); ?></label>
						<input type="date" name="_pn_cm_woo_calendar_weeks[<?php echo $index; ?>][date_end]"
						       value="<?php echo esc_attr($date_end); ?>" class="pn-cm-calendar-date-input" />
					</div>
					<div class="pn-cm-calendar-field pn-cm-calendar-field--narrow">
						<label><?php _e('Aforo/día', 'pn-customers-manager'); ?></label>
						<input type="number" name="_pn_cm_woo_calendar_weeks[<?php echo $index; ?>][capacity]"
						       value="<?php echo esc_attr($week['capacity']); ?>"
						       placeholder="25" min="1" />
					</div>
				</div>
				<!-- Fila 3: Precios -->
				<div class="pn-cm-calendar-row">
					<div class="pn-cm-calendar-field">
						<label><?php _e('Precio semana - No socios', 'pn-customers-manager'); ?></label>
						<div class="pn-cm-calendar-input-with-suffix">
							<input type="number" name="_pn_cm_woo_calendar_weeks[<?php echo $index; ?>][price]"
							       value="<?php echo esc_attr($week['price']); ?>"
							       placeholder="100.00" step="0.01" min="0" />
							<span class="pn-cm-calendar-suffix">&euro;</span>
						</div>
					</div>
					<div class="pn-cm-calendar-field">
						<label><?php _e('Precio semana - Socios', 'pn-customers-manager'); ?></label>
						<div class="pn-cm-calendar-input-with-suffix">
							<input type="number" name="_pn_cm_woo_calendar_weeks[<?php echo $index; ?>][member_price]"
							       value="<?php echo esc_attr($week['member_price']); ?>"
							       placeholder="80.00" step="0.01" min="0" />
							<span class="pn-cm-calendar-suffix">&euro;</span>
						</div>
					</div>
				</div>
				<!-- Preview de días generados -->
				<div class="pn-cm-calendar-dates-preview"></div>
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

		// Semanas
		$weeks = [];
		$used_ids = [];
		if (isset($_POST['_pn_cm_woo_calendar_weeks']) && is_array($_POST['_pn_cm_woo_calendar_weeks'])) {
			$index = 0;
			foreach ($_POST['_pn_cm_woo_calendar_weeks'] as $week_data) {
				$date_start = isset($week_data['date_start']) ? sanitize_text_field($week_data['date_start']) : '';
				$date_end   = isset($week_data['date_end']) ? sanitize_text_field($week_data['date_end']) : '';

				// Generar días laborables (L-V) entre las dos fechas
				$dates = [];
				if ($date_start && $date_end && $date_end >= $date_start) {
					$dates = $this->generate_weekday_dates($date_start, $date_end);
				}

				// Auto-generar ID si está vacío y garantizar unicidad
				$id = sanitize_title($week_data['id']);
				if (empty($id)) {
					$id = 'semana-' . ($index + 1);
				}
				$base_id = $id;
				$suffix = 2;
				while (in_array($id, $used_ids, true)) {
					$id = $base_id . '-' . $suffix;
					$suffix++;
				}
				$used_ids[] = $id;

				$weeks[] = [
					'id'           => $id,
					'label'        => sanitize_text_field($week_data['label']),
					'dates'        => $dates,
					'price'        => (float) $week_data['price'],
					'member_price' => (float) $week_data['member_price'],
					'capacity'     => max(1, (int) $week_data['capacity']),
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
			$plugin_url . 'assets/css/pn-cm-calendar-booking.css',
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

		// Enviar ambos sets de precios al frontend
		$weeks_for_js = [];
		foreach ($weeks as $w) {
			$weeks_for_js[] = [
				'id'           => $w['id'],
				'label'        => $w['label'],
				'dates'        => $w['dates'],
				'price'        => (float) $w['price'],
				'member_price' => isset($w['member_price']) ? (float) $w['member_price'] : (float) $w['price'],
				'capacity'     => (int) $w['capacity'],
			];
		}

		// Usar wp_add_inline_script con JSON nativo para preservar tipos de datos
		$camp_data = [
			'ajaxUrl'          => admin_url('admin-ajax.php'),
			'nonce'            => wp_create_nonce('pn_cm_calendar_nonce'),
			'productId'        => $post->ID,
			'showAvailability' => get_post_meta($post->ID, '_pn_cm_woo_calendar_show_availability', true) === 'yes',
			'weeks'          => $weeks_for_js,
			'dayPrice'       => (float) $this->get_day_price($post->ID),
			'memberDayPrice' => (float) $this->get_member_day_price($post->ID),
			'schedule'       => get_post_meta($post->ID, '_pn_cm_woo_calendar_schedule', true),
			'availability'   => $this->get_availability($post->ID),
			'isAdmin'        => current_user_can('manage_woocommerce'),
			'isMember'       => $this->is_current_user_member($post->ID),
			'currency'       => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
			'i18n'           => [
				'schedule'      => __('Horario', 'pn-customers-manager'),
				'available'     => __('Disponible', 'pn-customers-manager'),
				'selected'      => __('Seleccionado', 'pn-customers-manager'),
				'full'          => __('Completo', 'pn-customers-manager'),
				'yourSelection' => __('Tu selección', 'pn-customers-manager'),
				'total'         => __('Total', 'pn-customers-manager'),
				'looseDays'     => __('Días sueltos', 'pn-customers-manager'),
				'noSelection'   => __('Selecciona días o semanas del calendario', 'pn-customers-manager'),
				'memberLabel'   => __('Somos familia asociada', 'pn-customers-manager'),
				'memberBadge'   => __('Precio socio aplicado', 'pn-customers-manager'),
				'adminPanel'    => __('Panel Admin', 'pn-customers-manager'),
				'adminBack'     => __('Volver a compra', 'pn-customers-manager'),
				'adminOccupancy' => __('Ocupación', 'pn-customers-manager'),
				'adminBookings' => __('Reservas', 'pn-customers-manager'),
				'adminNoBookings' => __('No hay reservas para este día', 'pn-customers-manager'),
				'adminName'     => __('Nombre', 'pn-customers-manager'),
				'adminEmail'    => __('Email', 'pn-customers-manager'),
				'adminPhone'    => __('Teléfono', 'pn-customers-manager'),
				'adminNotes'    => __('Notas', 'pn-customers-manager'),
				'adminOrder'    => __('Pedido', 'pn-customers-manager'),
				'adminDate'     => __('Fecha pedido', 'pn-customers-manager'),
				'adminClose'    => __('Cerrar', 'pn-customers-manager'),
				'adminLoading'  => __('Cargando...', 'pn-customers-manager'),
				'adminError'    => __('Error al cargar las reservas', 'pn-customers-manager'),
				'adminSummaryTitle'  => __('Resumen de facturación', 'pn-customers-manager'),
				'adminTotalOrders'   => __('Pedidos totales', 'pn-customers-manager'),
				'adminTotalBookings' => __('Reservas (plazas·día)', 'pn-customers-manager'),
				'adminTotalRevenue'  => __('Facturación total', 'pn-customers-manager'),
				'adminWeekSummary'   => __('Desglose por semana', 'pn-customers-manager'),
				'adminWeekLabel'     => __('Semana', 'pn-customers-manager'),
				'adminWeekBookings'  => __('Reservas', 'pn-customers-manager'),
				'adminWeekRevenue'   => __('Facturación', 'pn-customers-manager'),
			],
			'months' => [
				__('Enero', 'pn-customers-manager'), __('Febrero', 'pn-customers-manager'), __('Marzo', 'pn-customers-manager'),
				__('Abril', 'pn-customers-manager'), __('Mayo', 'pn-customers-manager'), __('Junio', 'pn-customers-manager'),
				__('Julio', 'pn-customers-manager'), __('Agosto', 'pn-customers-manager'), __('Septiembre', 'pn-customers-manager'),
				__('Octubre', 'pn-customers-manager'), __('Noviembre', 'pn-customers-manager'), __('Diciembre', 'pn-customers-manager'),
			],
			'weekDays' => [
				__('L', 'pn-customers-manager'), __('M', 'pn-customers-manager'), __('X', 'pn-customers-manager'),
				__('J', 'pn-customers-manager'), __('V', 'pn-customers-manager'), __('S', 'pn-customers-manager'), __('D', 'pn-customers-manager'),
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

		$today = wp_date('Y-m-d');
		foreach ($dates as $date) {
			if ($date < $today) {
				if (function_exists('wc_add_notice')) {
					wc_add_notice(__('No puedes reservar fechas pasadas.', 'pn-customers-manager'), 'error');
				}
				return false;
			}
		}

		$availability = $this->get_availability($product_id);
		foreach ($dates as $date) {
			if (!isset($availability[$date]) || $availability[$date] <= 0) {
				if (function_exists('wc_add_notice')) {
					wc_add_notice(
						sprintf(__('La fecha %s no tiene plazas disponibles.', 'pn-customers-manager'), $date),
						'error'
					);
				}
				return false;
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

		$dates = json_decode($dates_json, true);
		$weeks = json_decode($weeks_json, true);

		if (!is_array($dates)) $dates = [];
		if (!is_array($weeks)) $weeks = [];

		$is_member = !empty($_POST['pn_cm_calendar_is_member']) && $_POST['pn_cm_calendar_is_member'] === '1';

		$cart_item_data['pn_cm_calendar_dates']     = $dates;
		$cart_item_data['pn_cm_calendar_weeks']     = $weeks;
		$cart_item_data['pn_cm_calendar_is_member'] = $is_member;
		$cart_item_data['pn_cm_calendar_price']     = $this->calculate_price($product_id, $dates, $weeks, $is_member);
		$cart_item_data['unique_key']           = md5(microtime() . wp_rand());

		return $cart_item_data;
	}

	/* ───────────────────────────────────────────
	 *  Carrito: mostrar datos
	 * ─────────────────────────────────────────── */

	public function get_item_data($item_data, $cart_item)
	{
		if (empty($cart_item['pn_cm_calendar_dates'])) return $item_data;

		if (!empty($cart_item['pn_cm_calendar_is_member'])) {
			$item_data[] = [
				'key'   => __('Tarifa', 'pn-customers-manager'),
				'value' => __('Familia asociada', 'pn-customers-manager'),
			];
		}

		$product_id = $cart_item['product_id'];
		$week_map = [];
		foreach ($this->get_weeks($product_id) as $week) {
			$week_map[$week['id']] = $week;
		}

		$selected_weeks = isset($cart_item['pn_cm_calendar_weeks']) ? $cart_item['pn_cm_calendar_weeks'] : [];
		if (!empty($selected_weeks)) {
			$labels = [];
			foreach ($selected_weeks as $wid) {
				if (isset($week_map[$wid])) {
					$w = $week_map[$wid];
					$label = !empty($w['label']) ? $w['label'] : $this->format_week_range($w['dates']);
					$labels[] = $label;
				}
			}
			if (!empty($labels)) {
				$item_data[] = [
					'key'   => __('Semanas', 'pn-customers-manager'),
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
		if (!empty($loose_days)) {
			$formatted = array_map([$this, 'format_date_short'], array_values($loose_days));
			$item_data[] = [
				'key'   => __('Días sueltos', 'pn-customers-manager'),
				'value' => implode(', ', $formatted),
			];
		}

		return $item_data;
	}

	/**
	 * Formatea "2026-07-03" → "3 jul"
	 */
	private function format_date_short($date_str)
	{
		$months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
		$parts = explode('-', $date_str);
		if (count($parts) !== 3) return $date_str;
		return intval($parts[2]) . ' ' . $months[intval($parts[1]) - 1];
	}

	/**
	 * Formatea un rango de fechas de semana: "25 jun - 3 jul"
	 */
	private function format_week_range($dates)
	{
		if (empty($dates)) return '';
		$first = reset($dates);
		$last  = end($dates);
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

		$item->add_meta_data(__('Fechas calendario de productos', 'pn-customers-manager'), implode(', ', $values['pn_cm_calendar_dates']));

		if (!empty($values['pn_cm_calendar_weeks'])) {
			$product_id = $values['product_id'];
			$week_map = [];
			foreach ($this->get_weeks($product_id) as $w) {
				$week_map[$w['id']] = $w;
			}
			$labels = [];
			foreach ($values['pn_cm_calendar_weeks'] as $wid) {
				if (isset($week_map[$wid])) {
					$w = $week_map[$wid];
					$labels[] = !empty($w['label']) ? $w['label'] : $this->format_week_range($w['dates']);
				}
			}
			if (!empty($labels)) {
				$item->add_meta_data(__('Semanas', 'pn-customers-manager'), implode(', ', $labels));
			}
		}

		$item->add_meta_data('_pn_cm_woo_calendar_dates', $values['pn_cm_calendar_dates']);
		$item->add_meta_data('_pn_cm_woo_calendar_weeks', $values['pn_cm_calendar_weeks']);
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

			$bookings = $this->get_bookings($product_id);

			foreach ($dates as $date) {
				if (!isset($bookings[$date])) $bookings[$date] = 0;
				if ($action === 'increment') {
					$bookings[$date]++;
				} else {
					$bookings[$date] = max(0, $bookings[$date] - 1);
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

		// Obtener capacidad del día
		$weeks    = $this->get_weeks($product_id);
		$capacity = 0;
		foreach ($weeks as $week) {
			if (in_array($date, $week['dates'], true)) {
				$capacity = (int) $week['capacity'];
				break;
			}
		}

		$bookings_count = $this->get_bookings($product_id);
		$booked         = isset($bookings_count[$date]) ? (int) $bookings_count[$date] : 0;

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
				];
			}
		}

		wp_send_json_success([
			'date'         => $date,
			'capacity'     => $capacity,
			'booked'       => $booked,
			'reservations' => $reservations,
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

		// Mapa de fecha → week index para asignar revenue por semana
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
		$total_bookings   = 0; // plazas·día
		$order_ids        = [];
		$week_stats       = [];
		foreach ($weeks as $i => $week) {
			$week_stats[$i] = [
				'label'    => $week['label'] ?: ('Semana ' . ($i + 1)),
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

				// Distribuir revenue proporcionalmente por día a cada semana
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

		// Redondear revenues
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

	/**
	 * Registra meta box para edición de fechas de calendario en pedidos
	 */
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

	/**
	 * Renderiza meta box de edición de fechas de calendario en pedidos
	 */
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

			if (empty($dates) || !is_array($dates)) continue;

			$product = wc_get_product($product_id);
			$product_name = $product ? $product->get_name() : __('Producto', 'pn-customers-manager');

			$weeks = $this->get_weeks($product_id);
			$availability = $this->get_availability($product_id);

			// Preparar datos para JavaScript
			$weeks_for_js = [];
			foreach ($weeks as $w) {
				$weeks_for_js[] = [
					'id'           => $w['id'],
					'label'        => $w['label'],
					'dates'        => $w['dates'],
					'price'        => (float) $w['price'],
					'member_price' => isset($w['member_price']) ? (float) $w['member_price'] : (float) $w['price'],
					'capacity'     => (int) $w['capacity'],
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
			$items_html .= 'data-weeks="' . esc_attr(wp_json_encode($weeks_for_js)) . '" ';
			$items_html .= 'data-day-price="' . esc_attr($this->get_day_price($product_id)) . '" ';
			$items_html .= 'data-availability="' . esc_attr(wp_json_encode($availability)) . '">';
			$items_html .= __('Editar Fechas', 'pn-customers-manager');
			$items_html .= '</button>';
			$items_html .= '</div>';
			$items_html .= '<div class="pn-cm-calendar-dates-display" id="pn-cm-calendar-dates-display-' . esc_attr($item_id) . '">';
			$items_html .= $this->render_dates_pills($dates, $weeks_ids, $weeks);
			$items_html .= '</div>';
			$items_html .= '</div>';
		}

		if (!$has_calendar_items) {
			echo '<p>' . __('Este pedido no contiene productos de calendario.', 'pn-customers-manager') . '</p>';
			return;
		}

		echo $items_html;
	}

	/**
	 * Carga scripts para edición de fechas en pedidos
	 */
	public function order_edit_scripts($hook)
	{
		// Verificar si estamos en página de edición de pedido
		$is_order_edit = in_array($hook, ['post.php', 'post-new.php']) ||
			(class_exists('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') &&
				wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled() &&
				strpos($hook, 'woocommerce_page_wc-orders') !== false);

		if (!$is_order_edit) return;

		// Verificar si es un pedido
		global $post;
		$order = null;

		if ($post && $post->post_type === 'shop_order') {
			$order = wc_get_order($post->ID);
		} elseif (isset($_GET['id'])) {
			$order = wc_get_order(absint($_GET['id']));
		}

		if (!$order) return;

		// Verificar si tiene items de calendario
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

		// CSS
		wp_enqueue_style(
			'pn-cm-calendar-booking-css',
			$plugin_url . 'assets/css/pn-cm-calendar-booking.css',
			[],
			filemtime($plugin_dir . 'assets/css/pn-cm-calendar-booking.css')
		);

		// JS
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
				'available'       => __('Disponible', 'pn-customers-manager'),
				'selected'        => __('Seleccionado', 'pn-customers-manager'),
				'full'            => __('Completo', 'pn-customers-manager'),
				'noAvailability'  => __('No hay disponibilidad para las fechas seleccionadas', 'pn-customers-manager'),
				'selectAtLeast'   => __('Selecciona al menos una fecha', 'pn-customers-manager'),
			],
			'months' => [
				__('Enero', 'pn-customers-manager'), __('Febrero', 'pn-customers-manager'), __('Marzo', 'pn-customers-manager'),
				__('Abril', 'pn-customers-manager'), __('Mayo', 'pn-customers-manager'), __('Junio', 'pn-customers-manager'),
				__('Julio', 'pn-customers-manager'), __('Agosto', 'pn-customers-manager'), __('Septiembre', 'pn-customers-manager'),
				__('Octubre', 'pn-customers-manager'), __('Noviembre', 'pn-customers-manager'), __('Diciembre', 'pn-customers-manager'),
			],
			'weekDays' => [
				__('L', 'pn-customers-manager'), __('M', 'pn-customers-manager'), __('X', 'pn-customers-manager'),
				__('J', 'pn-customers-manager'), __('V', 'pn-customers-manager'), __('S', 'pn-customers-manager'), __('D', 'pn-customers-manager'),
			],
			'currency' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
		]);
	}

	/**
	 * AJAX: actualizar fechas de un item de pedido
	 */
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

		// Obtener fechas actuales
		$old_dates = $item->get_meta('_pn_cm_woo_calendar_dates');
		$old_weeks = $item->get_meta('_pn_cm_woo_calendar_weeks');
		if (!is_array($old_dates)) $old_dates = [];
		if (!is_array($old_weeks)) $old_weeks = [];

		// Validar disponibilidad simulada (excluyendo las fechas actuales del item)
		$simulated_availability = $this->get_simulated_availability($product_id, $old_dates);

		foreach ($new_dates as $date) {
			if (!isset($simulated_availability[$date]) || $simulated_availability[$date] <= 0) {
				wp_send_json_error([
					'message' => sprintf(
						__('La fecha %s no tiene plazas disponibles', 'pn-customers-manager'),
						$date
					)
				]);
			}
		}

		// Actualización atómica: decrementar viejas, incrementar nuevas
		$this->update_booking_dates_atomic($product_id, $old_dates, $new_dates, $order);

		// Actualizar meta del item
		$item->update_meta_data('_pn_cm_woo_calendar_dates', $new_dates);
		$item->update_meta_data('_pn_cm_woo_calendar_weeks', $new_weeks);

		// Actualizar meta visible
		$item->update_meta_data(__('Fechas calendario de productos', 'pn-customers-manager'), implode(', ', $new_dates));

		if (!empty($new_weeks)) {
			$week_map = [];
			foreach ($this->get_weeks($product_id) as $w) {
				$week_map[$w['id']] = $w;
			}
			$labels = [];
			foreach ($new_weeks as $wid) {
				if (isset($week_map[$wid])) {
					$w = $week_map[$wid];
					$labels[] = !empty($w['label']) ? $w['label'] : $this->format_week_range($w['dates']);
				}
			}
			if (!empty($labels)) {
				$item->update_meta_data(__('Semanas', 'pn-customers-manager'), implode(', ', $labels));
			}
		}

		// Recalcular precio
		$new_price = $this->calculate_price($product_id, $new_dates, $new_weeks, false);
		$item->set_subtotal($new_price);
		$item->set_total($new_price);
		$item->save();

		// Recalcular totales del pedido
		$order->calculate_totals();

		// Agregar nota al pedido
		$old_dates_str = implode(', ', $old_dates);
		$new_dates_str = implode(', ', $new_dates);
		$note = sprintf(
			__('Fechas de calendario actualizadas por %s. Fechas anteriores: %s. Nuevas fechas: %s. Nuevo precio: %s', 'pn-customers-manager'),
			wp_get_current_user()->display_name,
			$old_dates_str,
			$new_dates_str,
			wc_price($new_price)
		);
		$order->add_order_note($note);
		$order->save();

		// Renderizar nuevo HTML de fechas
		$weeks = $this->get_weeks($product_id);
		$dates_html = $this->render_dates_pills($new_dates, $new_weeks, $weeks);

		wp_send_json_success([
			'message'    => __('Fechas actualizadas correctamente', 'pn-customers-manager'),
			'new_price'  => wc_price($new_price),
			'dates_html' => $dates_html,
		]);
	}

	/**
	 * Obtener disponibilidad simulada excluyendo fechas específicas
	 */
	private function get_simulated_availability($product_id, $exclude_dates)
	{
		$weeks    = $this->get_weeks($product_id);
		$bookings = $this->get_bookings($product_id);
		$availability = [];

		foreach ($weeks as $week) {
			$capacity = (int) $week['capacity'];
			foreach ($week['dates'] as $date) {
				$booked = isset($bookings[$date]) ? (int) $bookings[$date] : 0;

				// Si la fecha está en exclude_dates, liberar una plaza
				if (in_array($date, $exclude_dates, true)) {
					$booked = max(0, $booked - 1);
				}

				$availability[$date] = max(0, $capacity - $booked);
			}
		}

		return $availability;
	}

	/**
	 * Actualizar bookings de forma atómica
	 */
	private function update_booking_dates_atomic($product_id, $old_dates, $new_dates, $order)
	{
		// Solo actualizar si el pedido ya fue contado
		$counted = $order->get_meta('_pn_cm_woo_calendar_bookings_counted');
		if ($counted !== 'yes') {
			return; // No hacer nada si el pedido aún no ha sido contado
		}

		$bookings = $this->get_bookings($product_id);

		// Decrementar fechas viejas
		foreach ($old_dates as $date) {
			if (isset($bookings[$date])) {
				$bookings[$date] = max(0, $bookings[$date] - 1);
			}
		}

		// Incrementar fechas nuevas
		foreach ($new_dates as $date) {
			if (!isset($bookings[$date])) {
				$bookings[$date] = 0;
			}
			$bookings[$date]++;
		}

		update_post_meta($product_id, '_pn_cm_woo_calendar_bookings', $bookings);
	}

	/**
	 * Renderizar pills de fechas para display
	 */
	private function render_dates_pills($dates, $weeks_ids, $weeks)
	{
		if (empty($dates)) {
			return '<p style="color: #999; font-size: 13px;">' . __('Sin fechas', 'pn-customers-manager') . '</p>';
		}

		$week_map = [];
		foreach ($weeks as $w) {
			$week_map[$w['id']] = $w;
		}

		$html = '';

		// Mostrar semanas
		if (!empty($weeks_ids)) {
			$html .= '<div style="margin-bottom: 8px;">';
			$html .= '<strong style="font-size: 12px; color: #666;">' . __('Semanas:', 'pn-customers-manager') . '</strong><br>';
			foreach ($weeks_ids as $wid) {
				if (isset($week_map[$wid])) {
					$w = $week_map[$wid];
					$label = !empty($w['label']) ? $w['label'] : $this->format_week_range($w['dates']);
					$html .= '<span style="display: inline-block; background: #e3f2fd; color: #1976d2; padding: 4px 10px; border-radius: 12px; font-size: 12px; margin: 2px 4px 2px 0;">';
					$html .= esc_html($label);
					$html .= '</span>';
				}
			}
			$html .= '</div>';
		}

		// Mostrar días sueltos (los que no están en semanas)
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
