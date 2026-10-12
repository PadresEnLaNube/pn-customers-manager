<?php
/**
 * WooCommerce Back in Stock Notifications.
 *
 * Shows an email subscription form on out-of-stock products and notifies
 * subscribers when the product is restocked.
 *
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */

class PN_CUSTOMERS_MANAGER_Woo_Back_In_Stock
{
	public function __construct()
	{
		if (get_option('pn_customers_manager_woo_bis_enabled') !== 'on') {
			return;
		}

		if (!class_exists('WooCommerce')) {
			return;
		}

		add_action('woocommerce_single_product_summary', [__CLASS__, 'render_form'], 31);
		add_action('woocommerce_product_set_stock_status', [__CLASS__, 'on_stock_status_change'], 10, 3);
		add_action('woocommerce_product_object_updated_props', [__CLASS__, 'on_product_updated_props'], 10, 2);
		add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
	}

	/**
	 * Register AJAX actions (called on init via the loader).
	 */
	public static function register_ajax()
	{
		// Handled through the nopriv AJAX switch, no extra wp_ajax_ hooks needed.
	}

	/**
	 * Check whether a product has any subscribers in the BIS table.
	 */
	private static function product_has_subscribers($product_id)
	{
		global $wpdb;
		return (bool) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}pn_cm_woo_back_in_stock WHERE product_id = %d",
			$product_id
		));
	}

	/**
	 * Enqueue CSS on single product pages when needed.
	 */
	public static function enqueue_assets()
	{
		if (!is_product()) {
			return;
		}

		global $post;
		$product = wc_get_product($post->ID);
		if (!$product) {
			return;
		}

		// Load CSS if product is out of stock OR if admin and product has subscribers
		$needs_css = !$product->is_in_stock();
		if (!$needs_css && current_user_can('manage_options')) {
			$needs_css = self::product_has_subscribers($product->get_id());
		}

		if (!$needs_css) {
			return;
		}

		wp_enqueue_style(
			'pn-cm-bis-styles',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css',
			[],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0'
		);

		// Inject corporate identity CSS custom properties for global button/input classes
		$corporate_defaults = [
			'color_main' => '#0000aa', 'bg_color_main' => '#0000aa', 'border_color_main' => '#0000aa',
			'color_main_alt' => '#232323', 'bg_color_main_alt' => '#232323',
		];
		$css_vars = ':root{';
		foreach ($corporate_defaults as $key => $default) {
			$val = sanitize_hex_color(get_option('pn_customers_manager_' . $key, $default)) ?: $default;
			$css_vars .= '--pn-customers-manager-' . str_replace('_', '-', $key) . ':' . $val . ';';
		}
		$css_vars .= '}';
		wp_add_inline_style('pn-cm-bis-styles', $css_vars);

		// Load Material Icons for the admin panel toggle
		if (current_user_can('manage_options') && !wp_style_is('wph-material-icons-outlined', 'enqueued')) {
			wp_enqueue_style(
				'wph-material-icons-outlined',
				PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css',
				[],
				defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0'
			);
		}
	}

	/**
	 * Render the email subscription form on out-of-stock products
	 * and the admin subscribers panel whenever there are subscribers.
	 */
	public static function render_form()
	{
		global $product;

		if (!$product || !is_a($product, 'WC_Product')) {
			return;
		}

		$product_id = $product->get_id();
		$is_out_of_stock = !$product->is_in_stock();

		// Show the subscription form only when out of stock
		if ($is_out_of_stock) {
			self::render_subscription_form($product_id);
		}

		// Show admin panel whenever there are subscribers (even if now in stock)
		if (current_user_can('manage_options') && self::product_has_subscribers($product_id)) {
			self::render_admin_panel($product_id);
		}
	}

	/**
	 * Output the email subscription form HTML + JS.
	 */
	private static function render_subscription_form($product_id)
	{
		$heading       = get_option('pn_customers_manager_woo_bis_heading', '');
		$description   = get_option('pn_customers_manager_woo_bis_description', '');
		$button_text   = get_option('pn_customers_manager_woo_bis_button_text', '');
		$success_msg   = get_option('pn_customers_manager_woo_bis_success_message', '');

		if (empty($heading)) {
			$heading = __('Notify me when available!', 'pn-customers-manager');
		}
		if (empty($description)) {
			$description = __('Leave your email and we will notify you when this product is back in stock.', 'pn-customers-manager');
		}
		if (empty($button_text)) {
			$button_text = __('Notify me', 'pn-customers-manager');
		}
		if (empty($success_msg)) {
			$success_msg = __('Done! We will notify you when it is available.', 'pn-customers-manager');
		}
		?>
		<div class="pn-cm-bis-form" id="pn-cm-bis-form" data-product-id="<?php echo esc_attr($product_id); ?>">
			<h4 class="pn-cm-bis-form__heading"><?php echo esc_html($heading); ?></h4>
			<p class="pn-cm-bis-form__description"><?php echo esc_html($description); ?></p>
			<div class="pn-cm-bis-form__fields">
				<input type="email" class="pn-cm-bis-form__input" placeholder="<?php echo esc_attr__('your@email.com', 'pn-customers-manager'); ?>" required>
				<button type="button" class="pn-cm-bis-form__button">
					<span class="pn-cm-bis-form__button-text"><?php echo esc_html($button_text); ?></span>
					<span class="pn-cm-bis-form__button-loader" style="display:none;">
						<?php PN_CUSTOMERS_MANAGER_Data::pn_customers_manager_loader(true); ?>
					</span>
				</button>
			</div>
			<div class="pn-cm-bis-form__message" style="display:none;"></div>
		</div>

		<?php if (!PN_CUSTOMERS_MANAGER_Common::pn_customers_manager_is_crm_page()) : ?>
		<div id="pn-customers-manager-main-message" class="pn-customers-manager-main-message" style="display:none;">
			<span id="pn-customers-manager-main-message-span"></span>
			<div id="pn-customers-manager-bar-wrapper"><div id="pn-customers-manager-bar"></div></div>
		</div>
		<?php endif; ?>

		<script>
		(function() {
			var form = document.getElementById('pn-cm-bis-form');
			if (!form) return;

			var btn = form.querySelector('.pn-cm-bis-form__button');
			var btnText = form.querySelector('.pn-cm-bis-form__button-text');
			var btnLoader = form.querySelector('.pn-cm-bis-form__button-loader');
			var input = form.querySelector('.pn-cm-bis-form__input');
			var fields = form.querySelector('.pn-cm-bis-form__fields');
			var msgEl = form.querySelector('.pn-cm-bis-form__message');
			var productId = form.getAttribute('data-product-id');
			var successMsg = <?php echo wp_json_encode($success_msg); ?>;

			/* Standalone slide-in notification (replicates get_main_message for non-CRM pages) */
			var bisMainMsgTimeout;
			function bisShowMainMessage(content, type) {
				var el = document.getElementById('pn-customers-manager-main-message');
				if (!el) return;
				var span = document.getElementById('pn-customers-manager-main-message-span');
				var bar = document.getElementById('pn-customers-manager-bar');
				if (span) span.innerHTML = content;

				el.classList.remove('pn-customers-manager-success', 'pn-customers-manager-error', 'pn-customers-manager-warning');
				if (type) el.classList.add('pn-customers-manager-' + type);

				el.style.display = 'block';
				el.style.position = 'fixed';
				el.style.zIndex = '999999';
				el.style.right = '-500px';

				/* Progress bar animation */
				if (bar) {
					bar.style.width = '1%';
					var barStart = Date.now();
					var barDuration = 5000;
					(function animateBar() {
						var elapsed = Date.now() - barStart;
						var pct = Math.min((elapsed / barDuration) * 100, 100);
						bar.style.width = pct + '%';
						if (pct < 100) requestAnimationFrame(animateBar);
					})();
				}

				/* Slide in animation */
				var start = Date.now();
				(function slideIn() {
					var elapsed = Date.now() - start;
					var progress = Math.min(elapsed / 600, 1);
					var ease = 1 - Math.pow(1 - progress, 3);
					el.style.right = (-500 + 500 * ease) + 'px';
					if (progress < 1) requestAnimationFrame(slideIn);
				})();

				clearTimeout(bisMainMsgTimeout);
				bisMainMsgTimeout = setTimeout(function() {
					el.style.transition = 'opacity 0.5s';
					el.style.opacity = '0';
					setTimeout(function() { el.style.display = 'none'; el.style.opacity = '1'; el.style.transition = ''; }, 500);
				}, 5000);
			}

			/* Use native function on CRM pages, standalone version otherwise */
			var showMessage = (typeof window.pn_customers_manager_get_main_message === 'function')
				? window.pn_customers_manager_get_main_message
				: bisShowMainMessage;

			function setLoading(loading) {
				btn.disabled = loading;
				btnText.style.display = loading ? 'none' : '';
				btnLoader.style.display = loading ? 'inline' : 'none';
			}

			btn.addEventListener('click', function() {
				var email = input.value.trim();
				if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
					msgEl.textContent = <?php echo wp_json_encode(__('Please enter a valid email address.', 'pn-customers-manager')); ?>;
					msgEl.className = 'pn-cm-bis-form__message pn-cm-bis-form__message--error';
					msgEl.style.display = 'block';
					showMessage(<?php echo wp_json_encode(__('Please enter a valid email address.', 'pn-customers-manager')); ?>, 'error');
					return;
				}

				setLoading(true);

				var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
				var nonce = <?php echo wp_json_encode(wp_create_nonce('pn-customers-manager-nonce')); ?>;

				var data = new FormData();
				data.append('action', 'pn_customers_manager_ajax_nopriv');
				data.append('pn_customers_manager_ajax_nopriv_type', 'pn_cm_woo_bis_subscribe');
				data.append('pn_customers_manager_ajax_nopriv_nonce', nonce);
				data.append('bis_email', email);
				data.append('bis_product_id', productId);

				fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
					.then(function(r) { return r.json(); })
					.then(function(resp) {
						if (resp.error_key && resp.error_key !== '') {
							msgEl.textContent = resp.error_content || 'Error';
							msgEl.className = 'pn-cm-bis-form__message pn-cm-bis-form__message--error';
							msgEl.style.display = 'block';
							setLoading(false);
							showMessage(resp.error_content || 'Error', 'error');
						} else {
							fields.style.display = 'none';
							msgEl.textContent = successMsg;
							msgEl.className = 'pn-cm-bis-form__message pn-cm-bis-form__message--success';
							msgEl.style.display = 'block';
							showMessage(successMsg, 'success');
						}
					})
					.catch(function() {
						msgEl.textContent = 'Error';
						msgEl.className = 'pn-cm-bis-form__message pn-cm-bis-form__message--error';
						msgEl.style.display = 'block';
						setLoading(false);
						showMessage('Error', 'error');
					});
			});

			input.addEventListener('keydown', function(e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					btn.click();
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Render the admin subscribers panel (visible only to admins).
	 */
	private static function render_admin_panel($product_id)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_woo_back_in_stock';
		$subscribers = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$table} WHERE product_id = %d ORDER BY status ASC, created_at DESC",
			$product_id
		));

		$ajax_url = admin_url('admin-ajax.php');
		$nonce    = wp_create_nonce('pn-customers-manager-nonce');
		$count    = count($subscribers);
		$pending  = 0;
		foreach ($subscribers as $s) {
			if ($s->status === 'pending') {
				$pending++;
			}
		}
		?>
		<div class="pn-cm-bis-admin pn-cm-bis-admin--collapsed" id="pn-cm-bis-admin">
			<div class="pn-cm-bis-admin__header" id="pn-cm-bis-admin-toggle">
				<h4 class="pn-cm-bis-admin__title">
					<i class="material-icons-outlined pn-cm-bis-admin__toggle-icon">expand_more</i>
					<?php echo esc_html__('Subscribers', 'pn-customers-manager'); ?>
					<span class="pn-cm-bis-admin__count"><?php echo esc_html($pending); ?> <?php echo esc_html__('pending', 'pn-customers-manager'); ?></span>
				</h4>
				<?php if ($count > 0) : ?>
				<button type="button" class="pn-cm-bis-admin__export pn-customers-manager-btn pn-customers-manager-btn-transparent pn-customers-manager-btn-mini" id="pn-cm-bis-export">
					<?php echo esc_html__('Export CSV', 'pn-customers-manager'); ?>
				</button>
				<?php endif; ?>
			</div>
			<div class="pn-cm-bis-admin__body" id="pn-cm-bis-admin-body">
				<?php if ($count > 0) : ?>
				<table class="pn-cm-bis-admin__table">
					<thead>
						<tr>
							<th><?php echo esc_html__('Email', 'pn-customers-manager'); ?></th>
							<th><?php echo esc_html__('Status', 'pn-customers-manager'); ?></th>
							<th><?php echo esc_html__('Date', 'pn-customers-manager'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="pn-cm-bis-admin-tbody">
						<?php foreach ($subscribers as $sub) : ?>
						<tr data-id="<?php echo esc_attr($sub->id); ?>">
							<td><?php echo esc_html($sub->email); ?></td>
							<td>
								<?php if ($sub->status === 'pending') : ?>
									<span class="pn-cm-bis-admin__badge pn-cm-bis-admin__badge--pending"><?php echo esc_html__('Pending', 'pn-customers-manager'); ?></span>
								<?php else : ?>
									<span class="pn-cm-bis-admin__badge pn-cm-bis-admin__badge--notified"><?php echo esc_html__('Notified', 'pn-customers-manager'); ?></span>
								<?php endif; ?>
							</td>
							<td class="pn-cm-bis-admin__date"><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($sub->created_at))); ?></td>
							<td>
								<button type="button" class="pn-cm-bis-admin__delete" data-id="<?php echo esc_attr($sub->id); ?>" title="<?php echo esc_attr__('Delete', 'pn-customers-manager'); ?>">&#10005;</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
				<p class="pn-cm-bis-admin__empty"><?php echo esc_html__('No subscribers for this product.', 'pn-customers-manager'); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<script>
		(function() {
			var ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
			var nonce = <?php echo wp_json_encode($nonce); ?>;
			var productId = <?php echo intval($product_id); ?>;

			// Toggle panel
			var panel = document.getElementById('pn-cm-bis-admin');
			var toggleBtn = document.getElementById('pn-cm-bis-admin-toggle');
			if (toggleBtn && panel) {
				toggleBtn.addEventListener('click', function(e) {
					if (e.target.closest('.pn-cm-bis-admin__export')) return;
					panel.classList.toggle('pn-cm-bis-admin--collapsed');
					var icon = panel.querySelector('.pn-cm-bis-admin__toggle-icon');
					if (icon) {
						icon.textContent = panel.classList.contains('pn-cm-bis-admin--collapsed') ? 'expand_more' : 'expand_less';
					}
				});
			}

			// Delete subscriber
			document.querySelectorAll('.pn-cm-bis-admin__delete').forEach(function(btn) {
				btn.addEventListener('click', function() {
					var id = this.getAttribute('data-id');
					var row = this.closest('tr');
					if (!confirm(<?php echo wp_json_encode(__('Delete this subscription?', 'pn-customers-manager')); ?>)) return;

					var data = new FormData();
					data.append('action', 'pn_customers_manager_ajax');
					data.append('pn_customers_manager_ajax_type', 'pn_cm_bis_delete');
					data.append('pn_customers_manager_ajax_nonce', nonce);
					data.append('bis_id', id);

					fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
						.then(function(r) { return r.json(); })
						.then(function(resp) {
							if (!resp.error_key || resp.error_key === '') {
								row.style.transition = 'opacity 0.3s';
								row.style.opacity = '0';
								setTimeout(function() { row.remove(); updateCount(); }, 300);
							}
						});
				});
			});

			function updateCount() {
				var pending = document.querySelectorAll('.pn-cm-bis-admin__badge--pending').length;
				var countEl = document.querySelector('.pn-cm-bis-admin__count');
				if (countEl) countEl.textContent = pending + ' ' + <?php echo wp_json_encode(__('pending', 'pn-customers-manager')); ?>;
				if (document.querySelectorAll('#pn-cm-bis-admin-tbody tr').length === 0) {
					var table = document.querySelector('.pn-cm-bis-admin__table');
					var exportBtn = document.getElementById('pn-cm-bis-export');
					if (table) table.style.display = 'none';
					if (exportBtn) exportBtn.style.display = 'none';
				}
			}

			// Export CSV
			var exportBtn = document.getElementById('pn-cm-bis-export');
			if (exportBtn) {
				exportBtn.addEventListener('click', function() {
					var data = new FormData();
					data.append('action', 'pn_customers_manager_ajax');
					data.append('pn_customers_manager_ajax_type', 'pn_cm_bis_export_csv');
					data.append('pn_customers_manager_ajax_nonce', nonce);
					data.append('bis_product_id', productId);

					fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
						.then(function(r) { return r.json(); })
						.then(function(resp) {
							if (resp.csv) {
								var bom = '\uFEFF';
								var blob = new Blob([bom + resp.csv], { type: 'text/csv;charset=utf-8;' });
								var url = URL.createObjectURL(blob);
								var a = document.createElement('a');
								a.href = url;
								a.download = 'back-in-stock-' + productId + '.csv';
								document.body.appendChild(a);
								a.click();
								document.body.removeChild(a);
								URL.revokeObjectURL(url);
							}
						});
				});
			}
		})();
		</script>
		<?php
	}

	/**
	 * Handle the subscription AJAX request.
	 */
	public static function handle_subscription()
	{
		$email      = isset($_POST['bis_email']) ? sanitize_email(wp_unslash($_POST['bis_email'])) : '';
		$product_id = isset($_POST['bis_product_id']) ? intval($_POST['bis_product_id']) : 0;

		// Validate email
		if (empty($email) || !is_email($email)) {
			echo wp_json_encode([
				'error_key'     => 'pn_cm_bis_invalid_email',
				'error_content' => esc_html__('Please enter a valid email address.', 'pn-customers-manager'),
			]);
			exit;
		}

		// Validate product
		$product = wc_get_product($product_id);
		if (!$product) {
			echo wp_json_encode([
				'error_key'     => 'pn_cm_bis_invalid_product',
				'error_content' => esc_html__('Product not found.', 'pn-customers-manager'),
			]);
			exit;
		}

		if ($product->is_in_stock()) {
			echo wp_json_encode([
				'error_key'     => 'pn_cm_bis_in_stock',
				'error_content' => esc_html__('This product is already in stock.', 'pn-customers-manager'),
			]);
			exit;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_woo_back_in_stock';

		// Check for duplicate subscription
		$exists = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$table} WHERE product_id = %d AND email = %s AND status = 'pending'",
			$product_id,
			$email
		));

		if ($exists) {
			echo wp_json_encode([
				'error_key'     => 'pn_cm_bis_already_subscribed',
				'error_content' => esc_html__('You are already subscribed to notifications for this product.', 'pn-customers-manager'),
			]);
			exit;
		}

		// Get existing user or create a new one (never overwrite existing user data)
		$email_parts = explode('@', $email);
		$existing_user = get_user_by('email', $email);

		if ($existing_user) {
			$user_id = $existing_user->ID;
		} else {
			$username = sanitize_user($email_parts[0] . '_' . wp_generate_password(4, false));
			$password = wp_generate_password(16, true);
			$user_id  = PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_insert(
				$username,
				$password,
				$email,
				'',
				'',
				'',
				'',
				'',
				'',
				['pn_customers_manager_role_client']
			);

			if (!$user_id || is_wp_error($user_id)) {
				$user_id = 0;
			}
		}

		// Insert subscription
		$wpdb->insert(
			$table,
			[
				'product_id'  => $product_id,
				'email'       => $email,
				'user_id'     => $user_id,
				'status'      => 'pending',
				'created_at'  => current_time('mysql'),
			],
			['%d', '%s', '%d', '%s', '%s']
		);

		// Insert CRM contact message
		$product_name = $product->get_name();
		$wpdb->insert(
			$wpdb->prefix . 'pn_cm_contact_messages',
			[
				'contact_name'    => !empty($email_parts[0]) ? $email_parts[0] : __('Visitor', 'pn-customers-manager'),
				'contact_email'   => $email,
				'contact_subject' => sprintf(
					/* translators: %s: product name */
					__('Stock notification - %s', 'pn-customers-manager'),
					$product_name
				),
				'contact_message' => sprintf(
					/* translators: 1: email address, 2: product name */
					__('%1$s has requested to be notified when the product \'%2$s\' is back in stock.', 'pn-customers-manager'),
					$email,
					$product_name
				),
				'recipient_email' => get_option('admin_email'),
				'source_url'      => get_permalink($product_id),
				'source_title'    => $product_name,
				'ip_address'      => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
				'is_read'         => 0,
				'is_spam'         => 0,
				'created_at'      => current_time('mysql'),
			],
			['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
		);

		// Send admin notification email
		self::send_admin_notification($email, $product_name, $product_id);

		echo wp_json_encode(['error_key' => '']);
		exit;
	}

	/**
	 * Send admin notification about a new back-in-stock subscription.
	 */
	private static function send_admin_notification($email, $product_name, $product_id)
	{
		$admin_email = get_option('admin_email');
		$site_name   = get_option('blogname', '');
		if (empty($site_name)) {
			$site_name = wp_parse_url(home_url(), PHP_URL_HOST);
		}

		$subject = sprintf(
			'[%s] %s - %s',
			$site_name,
			__('Stock notification', 'pn-customers-manager'),
			$product_name
		);

		$html_body  = '<h3>' . esc_html__('New stock subscription', 'pn-customers-manager') . '</h3>';
		$html_body .= sprintf(
			'<p><strong>%s:</strong> %s</p>',
			esc_html__('Email', 'pn-customers-manager'),
			esc_html($email)
		);
		$html_body .= sprintf(
			'<p><strong>%s:</strong> %s</p>',
			esc_html__('Product', 'pn-customers-manager'),
			esc_html($product_name)
		);
		$html_body .= sprintf(
			'<p><a href="%s">%s</a></p>',
			esc_url(get_permalink($product_id)),
			esc_html__('View product', 'pn-customers-manager')
		);

		if (class_exists('MAILPN_Mailing')) {
			$mailing = new MAILPN_Mailing();
			$mailing->mailpn_sender([
				'mailpn_user_to' => $admin_email,
				'mailpn_subject' => $subject,
				'mailpn_type'    => 'pn_cm_bis_admin_notification',
			], $html_body);
		} else {
			$headers = ['Content-Type: text/html; charset=UTF-8'];
			wp_mail($admin_email, $subject, $html_body, $headers);
		}
	}

	/**
	 * AJAX handler: delete a single subscriber row.
	 */
	public static function handle_delete()
	{
		if (!current_user_can('manage_options')) {
			echo wp_json_encode(['error_key' => 'pn_cm_bis_no_permission', 'error_content' => 'Forbidden']);
			exit;
		}

		$id = isset($_POST['bis_id']) ? intval($_POST['bis_id']) : 0;
		if ($id <= 0) {
			echo wp_json_encode(['error_key' => 'pn_cm_bis_invalid_id']);
			exit;
		}

		global $wpdb;
		$wpdb->delete($wpdb->prefix . 'pn_cm_woo_back_in_stock', ['id' => $id], ['%d']);

		echo wp_json_encode(['error_key' => '']);
		exit;
	}

	/**
	 * AJAX handler: export subscribers as CSV.
	 */
	public static function handle_export_csv()
	{
		if (!current_user_can('manage_options')) {
			echo wp_json_encode(['error_key' => 'pn_cm_bis_no_permission', 'error_content' => 'Forbidden']);
			exit;
		}

		$product_id = isset($_POST['bis_product_id']) ? intval($_POST['bis_product_id']) : 0;

		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_woo_back_in_stock';
		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT email, status, notified_at, created_at FROM {$table} WHERE product_id = %d ORDER BY created_at DESC",
			$product_id
		), ARRAY_A);

		$lines = [];
		$lines[] = implode(',', [
			__('Email', 'pn-customers-manager'),
			__('Status', 'pn-customers-manager'),
			__('Notified', 'pn-customers-manager'),
			__('Subscription date', 'pn-customers-manager'),
		]);
		foreach ($rows as $row) {
			$lines[] = implode(',', [
				'"' . str_replace('"', '""', $row['email']) . '"',
				$row['status'] === 'pending' ? __('Pending', 'pn-customers-manager') : __('Notified', 'pn-customers-manager'),
				$row['notified_at'] ?: '-',
				$row['created_at'],
			]);
		}

		echo wp_json_encode(['error_key' => '', 'csv' => implode("\n", $lines)]);
		exit;
	}

	/**
	 * Hook: woocommerce_product_set_stock_status
	 * Fires when stock status is explicitly set.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $status     New stock status.
	 * @param object $product    Product object.
	 */
	public static function on_stock_status_change($product_id, $status, $product = null)
	{
		if ($status !== 'instock') {
			return;
		}

		self::maybe_notify($product_id);
	}

	/**
	 * Hook: woocommerce_product_object_updated_props
	 * Backup hook — fires when product properties are saved from the admin editor.
	 *
	 * @param WC_Product $product       Product object.
	 * @param array      $updated_props List of property names that were updated.
	 */
	public static function on_product_updated_props($product, $updated_props)
	{
		if (!in_array('stock_status', $updated_props, true)) {
			return;
		}

		if ($product->get_stock_status() !== 'instock') {
			return;
		}

		self::maybe_notify($product->get_id());
	}

	/**
	 * Run notifications if enabled and not already processed this request.
	 */
	private static function maybe_notify($product_id)
	{
		static $notified = [];

		// Prevent double-firing within the same request (both hooks may fire)
		if (isset($notified[$product_id])) {
			return;
		}
		$notified[$product_id] = true;

		if (get_option('pn_customers_manager_woo_bis_enabled') !== 'on') {
			return;
		}

		self::notify_subscribers($product_id);
	}

	/**
	 * Notify all pending subscribers for a product.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function notify_subscribers($product_id)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_woo_back_in_stock';

		$subscribers = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$table} WHERE product_id = %d AND status = 'pending'",
			$product_id
		));

		if (empty($subscribers)) {
			return;
		}

		$product = wc_get_product($product_id);
		if (!$product) {
			return;
		}

		$product_name = $product->get_name();
		$product_url  = get_permalink($product_id);
		$site_name    = get_option('blogname', '');
		if (empty($site_name)) {
			$site_name = wp_parse_url(home_url(), PHP_URL_HOST);
		}

		$subject = sprintf(
			'[%s] %s',
			$site_name,
			sprintf(
				/* translators: %s: product name */
				__('%s is back in stock', 'pn-customers-manager'),
				$product_name
			)
		);

		$html_body  = '<h3>' . sprintf(
			/* translators: %s: product name */
			esc_html__('%s is back in stock!', 'pn-customers-manager'),
			esc_html($product_name)
		) . '</h3>';
		$html_body .= '<p>' . esc_html__('The product you were interested in is now available.', 'pn-customers-manager') . '</p>';
		$html_body .= sprintf(
			'<p><a href="%s" style="display:inline-block;padding:12px 28px;background:#0000aa;color:#fff;border-radius:4px;text-decoration:none;font-weight:500;">%s</a></p>',
			esc_url($product_url),
			esc_html__('View product', 'pn-customers-manager')
		);

		foreach ($subscribers as $sub) {
			if (class_exists('MAILPN_Mailing')) {
				$mailing = new MAILPN_Mailing();
				$mailing->mailpn_sender([
					'mailpn_user_to' => $sub->email,
					'mailpn_subject' => $subject,
					'mailpn_type'    => 'pn_cm_bis_restock_notification',
				], $html_body);
			} else {
				$headers = ['Content-Type: text/html; charset=UTF-8'];
				wp_mail($sub->email, $subject, $html_body, $headers);
			}

			$wpdb->update(
				$table,
				[
					'status'      => 'notified',
					'notified_at' => current_time('mysql'),
				],
				['id' => $sub->id],
				['%s', '%s'],
				['%d']
			);
		}
	}
}
