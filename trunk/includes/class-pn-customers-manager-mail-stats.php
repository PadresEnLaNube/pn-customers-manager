<?php
/**
 * CRM Statistics dashboard.
 *
 * Renders a full analytics dashboard for CRM data
 * (organizations, budgets, invoices, conversations, messages)
 * following the same layout as the previous MailPn dashboard
 * (gradient stat cards, popup tables with icons, period selector, Chart.js charts).
 *
 * @link       padresenlanube.com/
 * @since      1.0.62
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

class PN_CUSTOMERS_MANAGER_Mail_Stats {

	/* ──────────────────────────────────
	   Constants
	   ────────────────────────────────── */

	private static $periods = ['day', 'week', 'month', 'year', 'all'];

	/* ──────────────────────────────────
	   Period helpers
	   ────────────────────────────────── */

	private static function get_period_labels() {
		return [
			'day'   => __('24 h', 'pn-customers-manager'),
			'week'  => __('7 days', 'pn-customers-manager'),
			'month' => __('30 days', 'pn-customers-manager'),
			'year'  => __('1 year', 'pn-customers-manager'),
			'all'   => __('All time', 'pn-customers-manager'),
		];
	}

	private static function get_period_select_labels() {
		return [
			'day'   => __('Last 24 hours', 'pn-customers-manager'),
			'week'  => __('Last 7 days', 'pn-customers-manager'),
			'month' => __('Last 30 days', 'pn-customers-manager'),
			'year'  => __('Last year', 'pn-customers-manager'),
			'all'   => __('All time', 'pn-customers-manager'),
		];
	}

	private static function get_date_threshold($period) {
		switch ($period) {
			case 'day':   return gmdate('Y-m-d H:i:s', strtotime('-24 hours'));
			case 'week':  return gmdate('Y-m-d H:i:s', strtotime('-7 days'));
			case 'month': return gmdate('Y-m-d H:i:s', strtotime('-30 days'));
			case 'year':  return gmdate('Y-m-d H:i:s', strtotime('-365 days'));
			case 'all':   return '1970-01-01 00:00:00';
		}
		return gmdate('Y-m-d H:i:s', strtotime('-7 days'));
	}

	private static function get_chart_title($period) {
		$map = [
			'day'   => __('Last 24 hours trend', 'pn-customers-manager'),
			'week'  => __('Last 7 days trend', 'pn-customers-manager'),
			'month' => __('Last 30 days trend', 'pn-customers-manager'),
			'year'  => __('Last year trend', 'pn-customers-manager'),
			'all'   => __('All-time trend', 'pn-customers-manager'),
		];
		return $map[$period] ?? $map['week'];
	}

	/* ──────────────────────────────────
	   Data retrieval — Organizations
	   ────────────────────────────────── */

	private static function get_organizations_created($period) {
		return self::get_posts_by_type('pn_cm_organization', $period);
	}

	/* ──────────────────────────────────
	   Data retrieval — Budgets
	   ────────────────────────────────── */

	private static function get_budgets_created($period) {
		return self::get_posts_by_type('pn_cm_budget', $period);
	}

	/* ──────────────────────────────────
	   Data retrieval — Invoices
	   ────────────────────────────────── */

	private static function get_invoices_created($period) {
		return self::get_posts_by_type('pn_cm_invoice', $period);
	}

	/* ──────────────────────────────────
	   Data retrieval — Generic CPT helper
	   ────────────────────────────────── */

	private static function get_posts_by_type($post_type, $period) {
		global $wpdb;
		$since = self::get_date_threshold($period);

		$where_clause = $period === 'all'
			? $wpdb->prepare("WHERE post_type = %s AND post_status IN ('publish','private')", $post_type)
			: $wpdb->prepare(
				"WHERE post_type = %s AND post_status IN ('publish','private') AND post_date >= %s",
				$post_type,
				$since
			);

		$count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} {$where_clause}");

		$rows = $wpdb->get_results(
			"SELECT ID, post_title, post_date, post_status FROM {$wpdb->posts} {$where_clause} ORDER BY post_date DESC LIMIT 50"
		);

		$html = self::build_posts_table($rows, $post_type);

		return ['count' => $count, 'html' => $html];
	}

	/* ──────────────────────────────────
	   Data retrieval — Conversations
	   ────────────────────────────────── */

	private static function get_conversations_count($period) {
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';

		$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
		if (!$table_exists) {
			return ['count' => 0, 'html' => '<p class="pn-cm-ms-empty">' . esc_html__('No data for this period.', 'pn-customers-manager') . '</p>'];
		}

		$since = self::get_date_threshold($period);

		$where_period = $period === 'all'
			? "WHERE status = 'active'"
			: $wpdb->prepare("WHERE status = 'active' AND created_at >= %s", $since);

		$count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_period}");

		$rows = $wpdb->get_results(
			"SELECT id, phone, status, created_at FROM {$table} {$where_period} ORDER BY created_at DESC LIMIT 50"
		);

		$html = self::build_conversations_table($rows);

		return ['count' => $count, 'html' => $html];
	}

	/* ──────────────────────────────────
	   Data retrieval — Contact Messages
	   ────────────────────────────────── */

	private static function get_messages_received($period) {
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_contact_messages';

		$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
		if (!$table_exists) {
			return ['count' => 0, 'html' => '<p class="pn-cm-ms-empty">' . esc_html__('No data for this period.', 'pn-customers-manager') . '</p>'];
		}

		$since = self::get_date_threshold($period);

		$where_clause = $period === 'all'
			? "WHERE is_spam = 0"
			: $wpdb->prepare("WHERE is_spam = 0 AND created_at >= %s", $since);

		$count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");

		$rows = $wpdb->get_results(
			"SELECT id, name, email, message, is_read, created_at FROM {$table} {$where_clause} ORDER BY created_at DESC LIMIT 50"
		);

		$html = self::build_messages_table($rows);

		return ['count' => $count, 'html' => $html];
	}

	/* ──────────────────────────────────
	   Chart data
	   ────────────────────────────────── */

	private static function get_charts_data($period) {
		global $wpdb;

		switch ($period) {
			case 'day':
				$group_format = '%Y-%m-%d %H:00';
				$label_format = 'H:i';
				$steps = 24;
				$interval = 'HOUR';
				break;
			case 'week':
				$group_format = '%Y-%m-%d';
				$label_format = 'D d';
				$steps = 7;
				$interval = 'DAY';
				break;
			case 'month':
				$group_format = '%Y-%m-%d';
				$label_format = 'M d';
				$steps = 30;
				$interval = 'DAY';
				break;
			case 'year':
				$group_format = '%Y-%m';
				$label_format = 'M Y';
				$steps = 12;
				$interval = 'MONTH';
				break;
			case 'all':
			default:
				$group_format = '%Y-%m';
				$label_format = 'M Y';
				$steps = 24;
				$interval = 'MONTH';
				break;
		}

		$since = self::get_date_threshold($period);

		$post_types = [
			'organizations' => 'pn_cm_organization',
			'budgets'       => 'pn_cm_budget',
			'invoices'      => 'pn_cm_invoice',
		];

		$maps = [];
		foreach ($post_types as $key => $pt) {
			$where_since = $period === 'all' ? '' : $wpdb->prepare("AND post_date >= %s", $since);
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT(post_date, %s) AS period_key, COUNT(*) AS cnt
					FROM {$wpdb->posts}
					WHERE post_type = %s AND post_status IN ('publish','private') {$where_since}
					GROUP BY period_key ORDER BY period_key ASC",
					$group_format,
					$pt
				),
				ARRAY_A
			);
			$map = [];
			foreach ($rows as $r) { $map[$r['period_key']] = (int) $r['cnt']; }
			$maps[$key] = $map;
		}

		// Build labels + data arrays
		$labels = [];
		$data = ['organizations' => [], 'budgets' => [], 'invoices' => []];

		$now = current_time('timestamp');

		for ($i = $steps - 1; $i >= 0; $i--) {
			switch ($interval) {
				case 'HOUR':
					$ts = strtotime("-{$i} hours", $now);
					$key = gmdate('Y-m-d H:00', $ts);
					$label = date_i18n($label_format, $ts);
					break;
				case 'DAY':
					$ts = strtotime("-{$i} days", $now);
					$key = gmdate('Y-m-d', $ts);
					$label = date_i18n($label_format, $ts);
					break;
				case 'MONTH':
					$ts = strtotime("-{$i} months", $now);
					$key = gmdate('Y-m', $ts);
					$label = date_i18n($label_format, $ts);
					break;
			}

			$labels[] = $label;
			foreach ($maps as $mk => $mv) {
				$data[$mk][] = $mv[$key] ?? 0;
			}
		}

		return [
			'labels'        => $labels,
			'organizations' => $data['organizations'],
			'budgets'       => $data['budgets'],
			'invoices'      => $data['invoices'],
		];
	}

	/* ──────────────────────────────────
	   HTML helpers — tables
	   ────────────────────────────────── */

	private static function build_posts_table($rows, $post_type) {
		if (empty($rows)) {
			return '<p class="pn-cm-ms-empty">' . esc_html__('No data for this period.', 'pn-customers-manager') . '</p>';
		}

		$icons = [
			'pn_cm_organization' => 'business',
			'pn_cm_budget'       => 'request_quote',
			'pn_cm_invoice'      => 'receipt_long',
		];
		$icon = $icons[$post_type] ?? 'article';

		ob_start();
		?>
		<table class="pn-cm-ms-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Title', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Actions', 'pn-customers-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $row): ?>
					<?php
					$post_id  = $row->ID ?? 0;
					$title    = $row->post_title ?: '—';
					$date     = $row->post_date ?? '';
					$edit_url = $post_id ? get_edit_post_link($post_id, 'raw') : '#';
					?>
					<tr>
						<td>
							<a href="<?php echo esc_url($edit_url); ?>" target="_blank" class="pn-cm-ms-link">
								<i class="material-icons-outlined pn-cm-ms-icon"><?php echo esc_html($icon); ?></i>
								<?php echo esc_html($title); ?>
							</a>
						</td>
						<td>
							<i class="material-icons-outlined pn-cm-ms-icon">schedule</i>
							<?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($date))); ?>
						</td>
						<td class="pn-cm-ms-actions">
							<?php if ($edit_url !== '#'): ?>
								<a href="<?php echo esc_url($edit_url); ?>" target="_blank" title="<?php esc_attr_e('Edit', 'pn-customers-manager'); ?>">
									<i class="material-icons-outlined">edit</i>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	private static function build_conversations_table($rows) {
		if (empty($rows)) {
			return '<p class="pn-cm-ms-empty">' . esc_html__('No data for this period.', 'pn-customers-manager') . '</p>';
		}

		ob_start();
		?>
		<table class="pn-cm-ms-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Phone', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Status', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $row): ?>
					<tr>
						<td>
							<i class="material-icons-outlined pn-cm-ms-icon">chat</i>
							<?php echo esc_html($row->phone ?? '—'); ?>
						</td>
						<td>
							<span class="pn-cm-ms-status pn-cm-ms-status-opened">
								<i class="material-icons-outlined pn-cm-ms-icon">check_circle</i>
								<?php echo esc_html($row->status ?? 'active'); ?>
							</span>
						</td>
						<td>
							<i class="material-icons-outlined pn-cm-ms-icon">schedule</i>
							<?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($row->created_at ?? ''))); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	private static function build_messages_table($rows) {
		if (empty($rows)) {
			return '<p class="pn-cm-ms-empty">' . esc_html__('No data for this period.', 'pn-customers-manager') . '</p>';
		}

		ob_start();
		?>
		<table class="pn-cm-ms-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Name', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Email', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Status', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $row): ?>
					<?php
					$is_read = !empty($row->is_read);
					$status_icon = $is_read ? 'mark_email_read' : 'mark_email_unread';
					$status_label = $is_read
						? __('Read', 'pn-customers-manager')
						: __('Unread', 'pn-customers-manager');
					$status_class = $is_read ? 'pn-cm-ms-status-opened' : 'pn-cm-ms-status-pending';
					?>
					<tr>
						<td>
							<i class="material-icons-outlined pn-cm-ms-icon">person</i>
							<?php echo esc_html($row->name ?? '—'); ?>
						</td>
						<td><?php echo esc_html($row->email ?? '—'); ?></td>
						<td>
							<span class="pn-cm-ms-status <?php echo esc_attr($status_class); ?>">
								<i class="material-icons-outlined pn-cm-ms-icon"><?php echo esc_html($status_icon); ?></i>
								<?php echo esc_html($status_label); ?>
							</span>
						</td>
						<td>
							<i class="material-icons-outlined pn-cm-ms-icon">schedule</i>
							<?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($row->created_at ?? ''))); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/* ──────────────────────────────────
	   AJAX handler — period change
	   ────────────────────────────────── */

	public static function ajax_dashboard_period() {
		$period = isset($_POST['period']) ? sanitize_text_field(wp_unslash($_POST['period'])) : 'week';
		if (!in_array($period, self::$periods)) {
			$period = 'week';
		}

		$period_labels = self::get_period_labels();
		$organizations = self::get_organizations_created($period);
		$budgets       = self::get_budgets_created($period);
		$invoices      = self::get_invoices_created($period);
		$conversations = self::get_conversations_count($period);
		$messages      = self::get_messages_received($period);
		$charts        = self::get_charts_data($period);

		echo wp_json_encode([
			'error_key' => '',
			'widgets'   => [
				'organizations' => ['count' => $organizations['count']],
				'budgets'       => ['count' => $budgets['count']],
				'invoices'      => ['count' => $invoices['count']],
				'conversations' => ['count' => $conversations['count']],
				'messages'      => ['count' => $messages['count']],
			],
			'popups'    => [
				'organizations' => [
					'title' => sprintf(
						/* translators: %s: period label */
						__('Organizations (%s)', 'pn-customers-manager'),
						$period_labels[$period]
					),
					'html' => $organizations['html'],
				],
				'budgets'  => [
					'title' => sprintf(
						/* translators: %s: period label */
						__('Budgets (%s)', 'pn-customers-manager'),
						$period_labels[$period]
					),
					'html' => $budgets['html'],
				],
				'invoices' => [
					'title' => sprintf(
						/* translators: %s: period label */
						__('Invoices (%s)', 'pn-customers-manager'),
						$period_labels[$period]
					),
					'html' => $invoices['html'],
				],
				'conversations' => [
					'title' => sprintf(
						/* translators: %s: period label */
						__('WhatsApp Conversations (%s)', 'pn-customers-manager'),
						$period_labels[$period]
					),
					'html' => $conversations['html'],
				],
				'messages' => [
					'title' => sprintf(
						/* translators: %s: period label */
						__('Contact Messages (%s)', 'pn-customers-manager'),
						$period_labels[$period]
					),
					'html' => $messages['html'],
				],
			],
			'charts'    => $charts,
			'labels'    => [
				'widget_period' => $period_labels[$period],
				'chart_title'   => self::get_chart_title($period),
			],
		]);
		exit;
	}

	/* ──────────────────────────────────
	   Admin page render
	   ────────────────────────────────── */

	public static function render_page() {
		$period = isset($_GET['period']) ? sanitize_text_field(wp_unslash($_GET['period'])) : 'week';
		if (!in_array($period, self::$periods)) {
			$period = 'week';
		}

		$period_labels = self::get_period_labels();
		$period_select_labels = self::get_period_select_labels();
		$period_label = $period_labels[$period];

		// Gather data
		$organizations = self::get_organizations_created($period);
		$budgets       = self::get_budgets_created($period);
		$invoices      = self::get_invoices_created($period);
		$conversations = self::get_conversations_count($period);
		$messages      = self::get_messages_received($period);
		$charts        = self::get_charts_data($period);

		// Popup titles
		$popup_titles = [
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'organizations' => sprintf(__('Organizations (%s)', 'pn-customers-manager'), $period_label),
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'budgets'       => sprintf(__('Budgets (%s)', 'pn-customers-manager'), $period_label),
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'invoices'      => sprintf(__('Invoices (%s)', 'pn-customers-manager'), $period_label),
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'conversations' => sprintf(__('WhatsApp Conversations (%s)', 'pn-customers-manager'), $period_label),
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'messages'      => sprintf(__('Contact Messages (%s)', 'pn-customers-manager'), $period_label),
		];
		?>
		<div class="wrap pn-cm-ms-wrap">
			<!-- Header -->
			<div class="pn-cm-ms-header">
				<h1><?php esc_html_e('Statistics', 'pn-customers-manager'); ?></h1>
				<div class="pn-cm-ms-period-selector">
					<label for="pn-cm-ms-period-select">
						<i class="material-icons-outlined pn-cm-ms-valign">date_range</i>
						<?php esc_html_e('Period:', 'pn-customers-manager'); ?>
					</label>
					<select id="pn-cm-ms-period-select">
						<?php foreach ($period_select_labels as $val => $label): ?>
							<option value="<?php echo esc_attr($val); ?>" <?php selected($period, $val); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<!-- Stat cards row -->
			<div class="pn-cm-ms-widgets">
				<!-- Organizations -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-organizations" data-popup="pn-cm-ms-popup-organizations" data-widget="organizations">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">business</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($organizations['count']); ?></div>
					<div class="pn-cm-ms-widget-title">
						<?php
						/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
						printf(esc_html__('Organizations (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>

				<!-- Budgets -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-budgets" data-popup="pn-cm-ms-popup-budgets" data-widget="budgets">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">request_quote</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($budgets['count']); ?></div>
					<div class="pn-cm-ms-widget-title">
						<?php
						/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
						printf(esc_html__('Budgets (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>

				<!-- Invoices -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-invoices" data-popup="pn-cm-ms-popup-invoices" data-widget="invoices">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">receipt_long</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($invoices['count']); ?></div>
					<div class="pn-cm-ms-widget-title">
						<?php
						/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
						printf(esc_html__('Invoices (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>

				<!-- Conversations -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-conversations" data-popup="pn-cm-ms-popup-conversations" data-widget="conversations">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">chat</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($conversations['count']); ?></div>
					<div class="pn-cm-ms-widget-title">
						<?php
						/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
						printf(esc_html__('WhatsApp Conversations (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>

				<!-- Messages -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-messages" data-popup="pn-cm-ms-popup-messages" data-widget="messages">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">inbox</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($messages['count']); ?></div>
					<div class="pn-cm-ms-widget-title">
						<?php
						/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
						printf(esc_html__('Contact Messages (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>
			</div>

			<!-- Charts section -->
			<div class="pn-cm-ms-charts">
				<div class="pn-cm-ms-charts-grid">
					<!-- Combined chart (full width) -->
					<div class="pn-cm-ms-chart-card pn-cm-ms-chart-wide">
						<h3 id="pn-cm-ms-chart-combined-title">
							<i class="material-icons-outlined pn-cm-ms-valign">show_chart</i>
							<span><?php echo esc_html(self::get_chart_title($period)); ?></span>
						</h3>
						<div class="pn-cm-ms-chart-wrap">
							<canvas id="pn-cm-ms-chart-combined"></canvas>
						</div>
					</div>

					<!-- Organizations chart -->
					<div class="pn-cm-ms-chart-card">
						<h3>
							<i class="material-icons-outlined pn-cm-ms-valign">business</i>
							<?php esc_html_e('Organizations', 'pn-customers-manager'); ?>
						</h3>
						<div class="pn-cm-ms-chart-wrap">
							<canvas id="pn-cm-ms-chart-organizations"></canvas>
						</div>
					</div>

					<!-- Budgets chart -->
					<div class="pn-cm-ms-chart-card">
						<h3>
							<i class="material-icons-outlined pn-cm-ms-valign">request_quote</i>
							<?php esc_html_e('Budgets', 'pn-customers-manager'); ?>
						</h3>
						<div class="pn-cm-ms-chart-wrap">
							<canvas id="pn-cm-ms-chart-budgets"></canvas>
						</div>
					</div>

					<!-- Invoices chart -->
					<div class="pn-cm-ms-chart-card pn-cm-ms-chart-wide">
						<h3>
							<i class="material-icons-outlined pn-cm-ms-valign">receipt_long</i>
							<?php esc_html_e('Invoices', 'pn-customers-manager'); ?>
						</h3>
						<div class="pn-cm-ms-chart-wrap">
							<canvas id="pn-cm-ms-chart-invoices"></canvas>
						</div>
					</div>
				</div>
			</div>

			<!-- Popups (hidden, shown on card click) -->
			<div class="pn-customers-manager-popup-overlay pn-cm-ms-overlay" style="display:none;"></div>

			<div id="pn-cm-ms-popup-organizations" class="pn-cm-ms-popup" style="display:none;" data-popup-type="organizations">
				<div class="pn-cm-ms-popup-content">
					<button class="pn-cm-ms-popup-close"><i class="material-icons-outlined">close</i></button>
					<div class="pn-cm-ms-popup-inner">
						<h2><?php echo esc_html($popup_titles['organizations']); ?></h2>
						<div class="pn-cm-ms-popup-body"><?php echo wp_kses_post($organizations['html']); ?></div>
					</div>
				</div>
			</div>

			<div id="pn-cm-ms-popup-budgets" class="pn-cm-ms-popup" style="display:none;" data-popup-type="budgets">
				<div class="pn-cm-ms-popup-content">
					<button class="pn-cm-ms-popup-close"><i class="material-icons-outlined">close</i></button>
					<div class="pn-cm-ms-popup-inner">
						<h2><?php echo esc_html($popup_titles['budgets']); ?></h2>
						<div class="pn-cm-ms-popup-body"><?php echo wp_kses_post($budgets['html']); ?></div>
					</div>
				</div>
			</div>

			<div id="pn-cm-ms-popup-invoices" class="pn-cm-ms-popup" style="display:none;" data-popup-type="invoices">
				<div class="pn-cm-ms-popup-content">
					<button class="pn-cm-ms-popup-close"><i class="material-icons-outlined">close</i></button>
					<div class="pn-cm-ms-popup-inner">
						<h2><?php echo esc_html($popup_titles['invoices']); ?></h2>
						<div class="pn-cm-ms-popup-body"><?php echo wp_kses_post($invoices['html']); ?></div>
					</div>
				</div>
			</div>

			<div id="pn-cm-ms-popup-conversations" class="pn-cm-ms-popup" style="display:none;" data-popup-type="conversations">
				<div class="pn-cm-ms-popup-content">
					<button class="pn-cm-ms-popup-close"><i class="material-icons-outlined">close</i></button>
					<div class="pn-cm-ms-popup-inner">
						<h2><?php echo esc_html($popup_titles['conversations']); ?></h2>
						<div class="pn-cm-ms-popup-body"><?php echo wp_kses_post($conversations['html']); ?></div>
					</div>
				</div>
			</div>

			<div id="pn-cm-ms-popup-messages" class="pn-cm-ms-popup" style="display:none;" data-popup-type="messages">
				<div class="pn-cm-ms-popup-content">
					<button class="pn-cm-ms-popup-close"><i class="material-icons-outlined">close</i></button>
					<div class="pn-cm-ms-popup-inner">
						<h2><?php echo esc_html($popup_titles['messages']); ?></h2>
						<div class="pn-cm-ms-popup-body"><?php echo wp_kses_post($messages['html']); ?></div>
					</div>
				</div>
			</div>
		</div>

		<!-- Activity Log -->
		<?php echo wp_kses_post(PN_CUSTOMERS_MANAGER_Activity_Log::render_section()); ?>
		<?php

		self::enqueue_assets($charts, $period);
	}

	/* ──────────────────────────────────
	   Assets
	   ────────────────────────────────── */

	private static function enqueue_assets($charts_data, $period) {
		// Chart.js (reuse vendor bundle)
		wp_enqueue_script(
			'chartjs',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/vendor/chart.min.js',
			[],
			'4.5.1',
			true
		);

		// CSS
		wp_enqueue_style(
			'pn-cm-mail-stats',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-mail-stats.css',
			[],
			PN_CUSTOMERS_MANAGER_VERSION
		);

		// JS
		wp_enqueue_script(
			'pn-cm-mail-stats',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-mail-stats.js',
			['chartjs', 'jquery'],
			PN_CUSTOMERS_MANAGER_VERSION,
			true
		);

		// Activity Log CSS
		wp_enqueue_style(
			'pn-cm-activity-log',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/admin/pn-customers-manager-activity-log.css',
			[],
			PN_CUSTOMERS_MANAGER_VERSION
		);

		// Activity Log JS
		wp_enqueue_script(
			'pn-cm-activity-log',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-activity-log.js',
			['jquery'],
			PN_CUSTOMERS_MANAGER_VERSION,
			true
		);

		wp_localize_script('pn-cm-activity-log', 'pnCmActivityLog', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('pn-customers-manager-nonce'),
		]);

		// Widget title templates for JS period-change
		$widget_labels = [
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'organizations' => __('Organizations (%s)', 'pn-customers-manager'),
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'budgets'       => __('Budgets (%s)', 'pn-customers-manager'),
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'invoices'      => __('Invoices (%s)', 'pn-customers-manager'),
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'conversations' => __('WhatsApp Conversations (%s)', 'pn-customers-manager'),
			/* translators: %s: selected time period label (e.g. "Last 30 days", "This year") */
			'messages'      => __('Contact Messages (%s)', 'pn-customers-manager'),
		];

		wp_localize_script('pn-cm-mail-stats', 'pnCmMailStats', [
			'ajaxUrl'      => admin_url('admin-ajax.php'),
			'nonce'        => wp_create_nonce('pn-customers-manager-nonce'),
			'chartsData'   => $charts_data,
			'period'       => $period,
			'widgetLabels' => $widget_labels,
			'i18n'         => [
				'organizations' => __('Organizations', 'pn-customers-manager'),
				'budgets'       => __('Budgets', 'pn-customers-manager'),
				'invoices'      => __('Invoices', 'pn-customers-manager'),
				'loading'       => __('Loading...', 'pn-customers-manager'),
			],
		]);
	}
}
