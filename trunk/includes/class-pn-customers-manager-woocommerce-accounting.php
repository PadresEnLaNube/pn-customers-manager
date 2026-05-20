<?php
/**
 * WooCommerce Accounting Summary
 *
 * Generates and sends monthly accounting summaries to selected users
 *
 * @link       padresenlanube.com/
 * @since      1.1.75
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce_Accounting {

	private static $instance = null;

	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Register cron schedule
		add_filter('cron_schedules', [$this, 'add_cron_schedule']);
		add_action('wp', [$this, 'schedule_monthly_report']);

		// Cron action
		add_action('pn_cm_woo_accounting_monthly', [$this, 'send_monthly_report']);
	}

	/**
	 * Add custom cron schedule (first day of month)
	 */
	public function add_cron_schedule($schedules) {
		$schedules['monthly'] = [
			'interval' => 30 * DAY_IN_SECONDS,
			'display'  => __('Monthly', 'pn-customers-manager'),
		];
		return $schedules;
	}

	/**
	 * Schedule monthly report if not already scheduled
	 */
	public function schedule_monthly_report() {
		if (!wp_next_scheduled('pn_cm_woo_accounting_monthly')) {
			wp_schedule_event(time(), 'monthly', 'pn_cm_woo_accounting_monthly');
		}
	}

	/**
	 * Send monthly report to selected users
	 */
	public function send_monthly_report() {
		// Only send on the 1st of the month
		if (date('d') != '01') {
			return;
		}

		// Check if feature is enabled
		if (get_option('pn_customers_manager_woocommerce_accounting_enabled') !== 'on') {
			return;
		}

		// Get recipients
		$recipients = get_option('pn_customers_manager_woocommerce_accounting_recipients', []);
		if (empty($recipients) || !is_array($recipients)) {
			return;
		}

		// Generate report
		$report_html = $this->generate_report_html();
		if (empty($report_html)) {
			return;
		}

		// Send to each recipient
		foreach ($recipients as $user_id) {
			$this->send_email($user_id, $report_html);
		}
	}

	/**
	 * Generate HTML report for last month
	 *
	 * @return string HTML content
	 */
	public function generate_report_html() {
		global $wpdb;

		// Last month dates
		$last_year = date('Y', strtotime('last day of previous month'));
		$last_month = date('m', strtotime('last day of previous month'));
		$last_month_name = date_i18n('F', strtotime('last day of previous month'));
		$last_day = date('d', strtotime('last day of previous month'));

		// Get orders
		$orders = wc_get_orders([
			'limit' => -1,
			'status' => ['completed', 'processing'],
			'date_created' => $last_year . '-' . $last_month . '-01...' . $last_year . '-' . $last_month . '-' . $last_day,
			'orderby' => 'date',
			'order' => 'ASC',
		]);

		if (empty($orders)) {
			return '';
		}

		// Calculate totals
		$orders_total = 0;
		$payment_methods = [];
		$products_summary = [];
		$daily_revenue = [];

		foreach ($orders as $order) {
			$order_total = (float) $order->get_total();
			$orders_total += $order_total;

			// By payment method
			$payment_method = $order->get_payment_method_title();
			if (!isset($payment_methods[$payment_method])) {
				$payment_methods[$payment_method] = 0;
			}
			$payment_methods[$payment_method] += $order_total;

			// By day
			$order_date = $order->get_date_created()->format('Y-m-d');
			if (!isset($daily_revenue[$order_date])) {
				$daily_revenue[$order_date] = 0;
			}
			$daily_revenue[$order_date] += $order_total;

			// By product
			foreach ($order->get_items() as $item) {
				$product_name = $item->get_name();
				$product_total = (float) $item->get_total();

				if (!isset($products_summary[$product_name])) {
					$products_summary[$product_name] = [
						'quantity' => 0,
						'revenue' => 0,
					];
				}
				$products_summary[$product_name]['quantity'] += $item->get_quantity();
				$products_summary[$product_name]['revenue'] += $product_total;
			}
		}

		// Sort products by revenue
		uasort($products_summary, function($a, $b) {
			return $b['revenue'] <=> $a['revenue'];
		});

		// Generate HTML
		return $this->build_email_html([
			'month_name' => ucfirst($last_month_name) . ' ' . $last_year,
			'total_orders' => count($orders),
			'total_revenue' => $orders_total,
			'payment_methods' => $payment_methods,
			'products_summary' => $products_summary,
			'daily_revenue' => $daily_revenue,
		]);
	}

	/**
	 * Build modern HTML email template
	 *
	 * @param array $data Report data
	 * @return string HTML email content
	 */
	private function build_email_html($data) {
		$currency = get_woocommerce_currency_symbol();
		$site_name = get_bloginfo('name');
		$site_url = home_url();

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="es">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo esc_html__('Monthly Accounting Summary', 'pn-customers-manager'); ?></title>
			<style>
				body {
					margin: 0;
					padding: 0;
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
					background-color: #f5f5f5;
					color: #333;
				}
				.email-container {
					max-width: 600px;
					margin: 20px auto;
					background: #ffffff;
					border-radius: 8px;
					overflow: hidden;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				}
				.email-header {
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					color: #ffffff;
					padding: 30px 20px;
					text-align: center;
				}
				.email-header h1 {
					margin: 0 0 5px 0;
					font-size: 28px;
					font-weight: 600;
				}
				.email-header p {
					margin: 0;
					font-size: 16px;
					opacity: 0.95;
				}
				.email-body {
					padding: 30px 20px;
				}
				.kpi-grid {
					display: grid;
					grid-template-columns: 1fr 1fr;
					gap: 15px;
					margin-bottom: 30px;
				}
				.kpi-card {
					background: #f8f9fa;
					border-radius: 8px;
					padding: 20px;
					text-align: center;
					border: 1px solid #e9ecef;
				}
				.kpi-value {
					font-size: 32px;
					font-weight: 700;
					color: #667eea;
					margin: 0;
				}
				.kpi-label {
					font-size: 14px;
					color: #6c757d;
					margin: 5px 0 0 0;
					text-transform: uppercase;
					letter-spacing: 0.5px;
				}
				.section {
					margin-bottom: 30px;
				}
				.section-title {
					font-size: 18px;
					font-weight: 600;
					margin: 0 0 15px 0;
					color: #333;
					border-bottom: 2px solid #667eea;
					padding-bottom: 8px;
				}
				.table {
					width: 100%;
					border-collapse: collapse;
					margin-top: 10px;
				}
				.table th {
					background: #f8f9fa;
					padding: 12px 15px;
					text-align: left;
					font-size: 13px;
					font-weight: 600;
					color: #495057;
					text-transform: uppercase;
					letter-spacing: 0.5px;
					border-bottom: 2px solid #dee2e6;
				}
				.table td {
					padding: 12px 15px;
					border-bottom: 1px solid #e9ecef;
					font-size: 14px;
				}
				.table tr:last-child td {
					border-bottom: none;
				}
				.table tr:hover {
					background: #f8f9fa;
				}
				.text-right {
					text-align: right;
				}
				.text-muted {
					color: #6c757d;
				}
				.highlight {
					background: #fff3cd;
					padding: 2px 6px;
					border-radius: 4px;
					font-weight: 600;
				}
				.email-footer {
					background: #f8f9fa;
					padding: 20px;
					text-align: center;
					font-size: 13px;
					color: #6c757d;
				}
				.email-footer a {
					color: #667eea;
					text-decoration: none;
				}
				@media only screen and (max-width: 600px) {
					.kpi-grid {
						grid-template-columns: 1fr;
					}
					.email-header h1 {
						font-size: 24px;
					}
					.kpi-value {
						font-size: 28px;
					}
				}
			</style>
		</head>
		<body>
			<div class="email-container">
				<div class="email-header">
					<h1><?php echo esc_html__('Monthly Accounting Summary', 'pn-customers-manager'); ?></h1>
					<p><?php echo esc_html($data['month_name']); ?></p>
				</div>

				<div class="email-body">
					<!-- KPI Cards -->
					<div class="kpi-grid">
						<div class="kpi-card">
							<p class="kpi-value"><?php echo number_format($data['total_orders']); ?></p>
							<p class="kpi-label"><?php echo esc_html__('Total Orders', 'pn-customers-manager'); ?></p>
						</div>
						<div class="kpi-card">
							<p class="kpi-value"><?php echo number_format($data['total_revenue'], 2, ',', '.'); ?> <?php echo $currency; ?></p>
							<p class="kpi-label"><?php echo esc_html__('Total Revenue', 'pn-customers-manager'); ?></p>
						</div>
					</div>

					<!-- Payment Methods -->
					<?php if (!empty($data['payment_methods'])): ?>
					<div class="section">
						<h2 class="section-title"><?php echo esc_html__('Revenue by Payment Method', 'pn-customers-manager'); ?></h2>
						<table class="table">
							<thead>
								<tr>
									<th><?php echo esc_html__('Payment Method', 'pn-customers-manager'); ?></th>
									<th class="text-right"><?php echo esc_html__('Revenue', 'pn-customers-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($data['payment_methods'] as $method => $total): ?>
								<tr>
									<td><?php echo esc_html($method ?: __('Unknown', 'pn-customers-manager')); ?></td>
									<td class="text-right"><strong><?php echo number_format($total, 2, ',', '.'); ?> <?php echo $currency; ?></strong></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php endif; ?>

					<!-- Top Products -->
					<?php if (!empty($data['products_summary'])): ?>
					<div class="section">
						<h2 class="section-title"><?php echo esc_html__('Top Products', 'pn-customers-manager'); ?></h2>
						<table class="table">
							<thead>
								<tr>
									<th><?php echo esc_html__('Product', 'pn-customers-manager'); ?></th>
									<th class="text-right"><?php echo esc_html__('Units', 'pn-customers-manager'); ?></th>
									<th class="text-right"><?php echo esc_html__('Revenue', 'pn-customers-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$count = 0;
								foreach ($data['products_summary'] as $product_name => $stats):
									if ($count >= 10) break; // Show top 10
									$count++;
								?>
								<tr>
									<td><?php echo esc_html($product_name); ?></td>
									<td class="text-right"><?php echo number_format($stats['quantity']); ?></td>
									<td class="text-right"><strong><?php echo number_format($stats['revenue'], 2, ',', '.'); ?> <?php echo $currency; ?></strong></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php endif; ?>
				</div>

				<div class="email-footer">
					<p><?php echo esc_html__('This is an automated report from', 'pn-customers-manager'); ?> <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a></p>
					<p class="text-muted"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?></p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send email to user
	 *
	 * @param int $user_id User ID
	 * @param string $html_content HTML email content
	 * @return bool Success
	 */
	private function send_email($user_id, $html_content) {
		$user = get_userdata($user_id);
		if (!$user) {
			return false;
		}

		$last_month_name = date_i18n('F Y', strtotime('last day of previous month'));
		$subject = sprintf(__('Monthly Accounting Summary - %s', 'pn-customers-manager'), $last_month_name);

		// Try to use MailPn if available
		if (function_exists('mailpn_send')) {
			return mailpn_send($user->user_email, $subject, $html_content);
		}

		// Fallback to wp_mail
		$headers = ['Content-Type: text/html; charset=UTF-8'];
		return wp_mail($user->user_email, $subject, $html_content, $headers);
	}

	/**
	 * Manual test send (for admin)
	 */
	public function test_send() {
		$html = $this->generate_report_html();
		if (empty($html)) {
			return ['success' => false, 'message' => __('No orders found for last month', 'pn-customers-manager')];
		}

		$current_user_id = get_current_user_id();
		$result = $this->send_email($current_user_id, $html);

		return [
			'success' => $result,
			'message' => $result
				? __('Test email sent successfully', 'pn-customers-manager')
				: __('Failed to send test email', 'pn-customers-manager')
		];
	}
}
