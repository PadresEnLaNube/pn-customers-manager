<?php
/**
 * MailPn Statistics dashboard.
 *
 * Renders a full analytics dashboard for MailPn email data
 * following the same layout as the UsersPn dashboard (gradient stat cards,
 * popup tables with icons, period selector, Chart.js charts).
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
	   Plugin detection
	   ────────────────────────────────── */

	public static function is_mailpn_active() {
		if (!function_exists('is_plugin_active')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return class_exists('MailPn') || is_plugin_active('mailpn/mailpn.php');
	}

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
	   Data retrieval — Emails sent
	   ────────────────────────────────── */

	private static function get_emails_sent($period) {
		global $wpdb;
		$since = self::get_date_threshold($period);

		$where_clause = $period === 'all'
			? "WHERE post_type = 'mailpn_rec' AND post_status IN ('publish','private')"
			: $wpdb->prepare(
				"WHERE post_type = 'mailpn_rec' AND post_status IN ('publish','private') AND post_date >= %s",
				$since
			);

		$count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} {$where_clause}");

		$rows = $wpdb->get_results(
			"SELECT ID, post_title, post_date FROM {$wpdb->posts} {$where_clause} ORDER BY post_date DESC LIMIT 50"
		);

		$html = self::build_emails_table($rows, 'sent');

		return ['count' => $count, 'html' => $html];
	}

	/* ──────────────────────────────────
	   Data retrieval — Emails opened
	   ────────────────────────────────── */

	private static function get_emails_opened($period) {
		global $wpdb;
		$since = self::get_date_threshold($period);

		$where_period = $period === 'all'
			? ''
			: $wpdb->prepare("AND p.post_date >= %s", $since);

		$count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_mailpn_opened' AND pm.meta_value = '1'
			AND p.post_type = 'mailpn_rec' {$where_period}"
		);

		$rows = $wpdb->get_results(
			"SELECT p.ID, p.post_title, p.post_date FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE pm.meta_key = '_mailpn_opened' AND pm.meta_value = '1'
			AND p.post_type = 'mailpn_rec' {$where_period}
			ORDER BY p.post_date DESC LIMIT 50"
		);

		$html = self::build_emails_table($rows, 'opened');

		return ['count' => $count, 'html' => $html];
	}

	/* ──────────────────────────────────
	   Data retrieval — Emails clicked
	   ────────────────────────────────── */

	private static function get_emails_clicked($period) {
		global $wpdb;
		$table_clicks = $wpdb->prefix . 'mailpn_click_tracking';
		$since = self::get_date_threshold($period);

		// Check if click tracking table exists
		if ($wpdb->get_var("SHOW TABLES LIKE '{$table_clicks}'") !== $table_clicks) {
			return ['count' => 0, 'html' => '<p>' . esc_html__('Click tracking table not available.', 'pn-customers-manager') . '</p>'];
		}

		$where_period = $period === 'all'
			? ''
			: $wpdb->prepare("WHERE ct.clicked_at >= %s", $since);

		$count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_clicks} ct {$where_period}"
		);

		$rows = $wpdb->get_results(
			"SELECT ct.email_id, ct.url, ct.clicked_at, p.post_title
			FROM {$table_clicks} ct
			LEFT JOIN {$wpdb->posts} p ON p.ID = ct.email_id
			{$where_period}
			ORDER BY ct.clicked_at DESC LIMIT 50"
		);

		$html = self::build_clicks_table($rows);

		return ['count' => $count, 'html' => $html];
	}

	/* ──────────────────────────────────
	   Data retrieval — Rates
	   ────────────────────────────────── */

	private static function get_open_rate($sent, $opened) {
		if ($sent <= 0) return 0;
		return round(($opened / $sent) * 100, 1);
	}

	private static function get_click_rate($sent, $clicked) {
		if ($sent <= 0) return 0;
		return round(($clicked / $sent) * 100, 1);
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

		// Emails sent per period
		$where_since = $period === 'all' ? '' : $wpdb->prepare("AND post_date >= %s", $since);
		$sent_rows = $wpdb->get_results(
			"SELECT DATE_FORMAT(post_date, '{$group_format}') AS period_key, COUNT(*) AS cnt
			FROM {$wpdb->posts}
			WHERE post_type = 'mailpn_rec' AND post_status IN ('publish','private') {$where_since}
			GROUP BY period_key ORDER BY period_key ASC",
			ARRAY_A
		);
		$sent_map = [];
		foreach ($sent_rows as $r) { $sent_map[$r['period_key']] = (int) $r['cnt']; }

		// Emails opened per period
		$opened_rows = $wpdb->get_results(
			"SELECT DATE_FORMAT(p.post_date, '{$group_format}') AS period_key, COUNT(DISTINCT pm.post_id) AS cnt
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_mailpn_opened' AND pm.meta_value = '1'
			AND p.post_type = 'mailpn_rec' {$where_since}
			GROUP BY period_key ORDER BY period_key ASC",
			ARRAY_A
		);
		$opened_map = [];
		foreach ($opened_rows as $r) { $opened_map[$r['period_key']] = (int) $r['cnt']; }

		// Clicks per period
		$table_clicks = $wpdb->prefix . 'mailpn_click_tracking';
		$clicked_map = [];
		if ($wpdb->get_var("SHOW TABLES LIKE '{$table_clicks}'") === $table_clicks) {
			$click_where = $period === 'all' ? '' : $wpdb->prepare("WHERE clicked_at >= %s", $since);
			$click_rows = $wpdb->get_results(
				"SELECT DATE_FORMAT(clicked_at, '{$group_format}') AS period_key, COUNT(*) AS cnt
				FROM {$table_clicks} {$click_where}
				GROUP BY period_key ORDER BY period_key ASC",
				ARRAY_A
			);
			foreach ($click_rows as $r) { $clicked_map[$r['period_key']] = (int) $r['cnt']; }
		}

		// Build labels + data arrays
		$labels = [];
		$data_sent = [];
		$data_opened = [];
		$data_clicked = [];

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
			$data_sent[] = $sent_map[$key] ?? 0;
			$data_opened[] = $opened_map[$key] ?? 0;
			$data_clicked[] = $clicked_map[$key] ?? 0;
		}

		return [
			'labels'  => $labels,
			'sent'    => $data_sent,
			'opened'  => $data_opened,
			'clicked' => $data_clicked,
		];
	}

	/* ──────────────────────────────────
	   HTML helpers — tables
	   ────────────────────────────────── */

	private static function build_emails_table($rows, $type = 'sent') {
		if (empty($rows)) {
			return '<p class="pn-cm-ms-empty">' . esc_html__('No data for this period.', 'pn-customers-manager') . '</p>';
		}

		$icon = $type === 'opened' ? 'mark_email_read' : 'send';

		ob_start();
		?>
		<table class="pn-cm-ms-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Subject', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Status', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Actions', 'pn-customers-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $row): ?>
					<?php
					$post_id = is_object($row) ? ($row->ID ?? 0) : 0;
					$title   = is_object($row) ? ($row->post_title ?? '—') : '—';
					$date    = is_object($row) ? ($row->post_date ?? '') : '';
					$edit_url = $post_id ? get_edit_post_link($post_id, 'raw') : '#';

					// Check open/click status
					$is_opened = $post_id ? get_post_meta($post_id, '_mailpn_opened', true) : false;
					$status_icon = $is_opened ? 'mark_email_read' : 'mark_email_unread';
					$status_label = $is_opened
						? __('Opened', 'pn-customers-manager')
						: __('Not opened', 'pn-customers-manager');
					$status_class = $is_opened ? 'pn-cm-ms-status-opened' : 'pn-cm-ms-status-pending';
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
						<td>
							<span class="pn-cm-ms-status <?php echo esc_attr($status_class); ?>">
								<i class="material-icons-outlined pn-cm-ms-icon"><?php echo esc_html($status_icon); ?></i>
								<?php echo esc_html($status_label); ?>
							</span>
						</td>
						<td class="pn-cm-ms-actions">
							<?php if ($edit_url !== '#'): ?>
								<a href="<?php echo esc_url($edit_url); ?>" target="_blank" title="<?php esc_attr_e('View', 'pn-customers-manager'); ?>">
									<i class="material-icons-outlined">visibility</i>
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

	private static function build_clicks_table($rows) {
		if (empty($rows)) {
			return '<p class="pn-cm-ms-empty">' . esc_html__('No data for this period.', 'pn-customers-manager') . '</p>';
		}

		ob_start();
		?>
		<table class="pn-cm-ms-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Email', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Clicked URL', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $row): ?>
					<?php
					$title = $row->post_title ?? '—';
					$url   = $row->url ?? '';
					$date  = $row->clicked_at ?? '';

					// Shorten URL for display
					$display_url = $url;
					if (strlen($display_url) > 50) {
						$display_url = substr($display_url, 0, 47) . '...';
					}
					?>
					<tr>
						<td>
							<i class="material-icons-outlined pn-cm-ms-icon">email</i>
							<?php echo esc_html($title); ?>
						</td>
						<td>
							<a href="<?php echo esc_url($url); ?>" target="_blank" class="pn-cm-ms-link" title="<?php echo esc_attr($url); ?>">
								<i class="material-icons-outlined pn-cm-ms-icon">link</i>
								<?php echo esc_html($display_url); ?>
							</a>
						</td>
						<td>
							<i class="material-icons-outlined pn-cm-ms-icon">schedule</i>
							<?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($date))); ?>
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
		$sent    = self::get_emails_sent($period);
		$opened  = self::get_emails_opened($period);
		$clicked = self::get_emails_clicked($period);
		$open_rate  = self::get_open_rate($sent['count'], $opened['count']);
		$click_rate = self::get_click_rate($sent['count'], $clicked['count']);
		$charts  = self::get_charts_data($period);

		echo wp_json_encode([
			'error_key' => '',
			'widgets'   => [
				'sent'       => ['count' => $sent['count']],
				'opened'     => ['count' => $opened['count']],
				'clicked'    => ['count' => $clicked['count']],
				'open_rate'  => ['count' => $open_rate . '%'],
				'click_rate' => ['count' => $click_rate . '%'],
			],
			'popups'    => [
				'sent'    => [
					'title' => sprintf(
						/* translators: %s: period label */
						__('Emails sent (%s)', 'pn-customers-manager'),
						$period_labels[$period]
					),
					'html' => $sent['html'],
				],
				'opened'  => [
					'title' => sprintf(
						/* translators: %s: period label */
						__('Emails opened (%s)', 'pn-customers-manager'),
						$period_labels[$period]
					),
					'html' => $opened['html'],
				],
				'clicked' => [
					'title' => sprintf(
						/* translators: %s: period label */
						__('Clicks (%s)', 'pn-customers-manager'),
						$period_labels[$period]
					),
					'html' => $clicked['html'],
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
		if (!self::is_mailpn_active()) {
			echo '<div class="wrap"><h1>' . esc_html__('Statistics', 'pn-customers-manager') . '</h1>';
			echo '<div class="notice notice-warning"><p>';
			esc_html_e('MailPn plugin is not installed or active. Install and activate MailPn to see email statistics.', 'pn-customers-manager');
			echo '</p></div></div>';
			return;
		}

		$period = isset($_GET['period']) ? sanitize_text_field(wp_unslash($_GET['period'])) : 'week';
		if (!in_array($period, self::$periods)) {
			$period = 'week';
		}

		$period_labels = self::get_period_labels();
		$period_select_labels = self::get_period_select_labels();
		$period_label = $period_labels[$period];

		// Gather data
		$sent    = self::get_emails_sent($period);
		$opened  = self::get_emails_opened($period);
		$clicked = self::get_emails_clicked($period);
		$open_rate  = self::get_open_rate($sent['count'], $opened['count']);
		$click_rate = self::get_click_rate($sent['count'], $clicked['count']);
		$charts  = self::get_charts_data($period);

		// Popup titles
		$popup_titles = [
			'sent'    => sprintf(__('Emails sent (%s)', 'pn-customers-manager'), $period_label),
			'opened'  => sprintf(__('Emails opened (%s)', 'pn-customers-manager'), $period_label),
			'clicked' => sprintf(__('Clicks (%s)', 'pn-customers-manager'), $period_label),
		];

		// Widget title templates (for JS replacement)
		$widget_tpl_sent    = __('Emails sent (%s)', 'pn-customers-manager');
		$widget_tpl_opened  = __('Emails opened (%s)', 'pn-customers-manager');
		$widget_tpl_clicked = __('Clicks (%s)', 'pn-customers-manager');
		$widget_tpl_open_rate  = __('Open rate (%s)', 'pn-customers-manager');
		$widget_tpl_click_rate = __('Click rate (%s)', 'pn-customers-manager');
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
				<!-- Sent -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-sent" data-popup="pn-cm-ms-popup-sent" data-widget="sent">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">send</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($sent['count']); ?></div>
					<div class="pn-cm-ms-widget-title">
						<?php printf(esc_html__('Emails sent (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>

				<!-- Opened -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-opened" data-popup="pn-cm-ms-popup-opened" data-widget="opened">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">mark_email_read</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($opened['count']); ?></div>
					<div class="pn-cm-ms-widget-title">
						<?php printf(esc_html__('Emails opened (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>

				<!-- Clicked -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-clicked" data-popup="pn-cm-ms-popup-clicked" data-widget="clicked">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">ads_click</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($clicked['count']); ?></div>
					<div class="pn-cm-ms-widget-title">
						<?php printf(esc_html__('Clicks (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>

				<!-- Open Rate -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-open-rate" data-widget="open_rate">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">percent</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($open_rate); ?>%</div>
					<div class="pn-cm-ms-widget-title">
						<?php printf(esc_html__('Open rate (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
					</div>
				</div>

				<!-- Click Rate -->
				<div class="pn-cm-ms-widget pn-cm-ms-bg-click-rate" data-widget="click_rate">
					<div class="pn-cm-ms-widget-icon"><i class="material-icons-outlined">trending_up</i></div>
					<div class="pn-cm-ms-widget-value"><?php echo esc_html($click_rate); ?>%</div>
					<div class="pn-cm-ms-widget-title">
						<?php printf(esc_html__('Click rate (%s)', 'pn-customers-manager'), esc_html($period_label)); ?>
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

					<!-- Sent chart -->
					<div class="pn-cm-ms-chart-card">
						<h3>
							<i class="material-icons-outlined pn-cm-ms-valign">send</i>
							<?php esc_html_e('Emails sent', 'pn-customers-manager'); ?>
						</h3>
						<div class="pn-cm-ms-chart-wrap">
							<canvas id="pn-cm-ms-chart-sent"></canvas>
						</div>
					</div>

					<!-- Opened chart -->
					<div class="pn-cm-ms-chart-card">
						<h3>
							<i class="material-icons-outlined pn-cm-ms-valign">mark_email_read</i>
							<?php esc_html_e('Emails opened', 'pn-customers-manager'); ?>
						</h3>
						<div class="pn-cm-ms-chart-wrap">
							<canvas id="pn-cm-ms-chart-opened"></canvas>
						</div>
					</div>

					<!-- Clicks chart -->
					<div class="pn-cm-ms-chart-card pn-cm-ms-chart-wide">
						<h3>
							<i class="material-icons-outlined pn-cm-ms-valign">ads_click</i>
							<?php esc_html_e('Clicks', 'pn-customers-manager'); ?>
						</h3>
						<div class="pn-cm-ms-chart-wrap">
							<canvas id="pn-cm-ms-chart-clicked"></canvas>
						</div>
					</div>
				</div>
			</div>

			<!-- Popups (hidden, shown on card click) -->
			<div class="pn-customers-manager-popup-overlay pn-cm-ms-overlay" style="display:none;"></div>

			<div id="pn-cm-ms-popup-sent" class="pn-cm-ms-popup" style="display:none;" data-popup-type="sent">
				<div class="pn-cm-ms-popup-content">
					<button class="pn-cm-ms-popup-close"><i class="material-icons-outlined">close</i></button>
					<div class="pn-cm-ms-popup-inner">
						<h2><?php echo esc_html($popup_titles['sent']); ?></h2>
						<div class="pn-cm-ms-popup-body"><?php echo $sent['html']; ?></div>
					</div>
				</div>
			</div>

			<div id="pn-cm-ms-popup-opened" class="pn-cm-ms-popup" style="display:none;" data-popup-type="opened">
				<div class="pn-cm-ms-popup-content">
					<button class="pn-cm-ms-popup-close"><i class="material-icons-outlined">close</i></button>
					<div class="pn-cm-ms-popup-inner">
						<h2><?php echo esc_html($popup_titles['opened']); ?></h2>
						<div class="pn-cm-ms-popup-body"><?php echo $opened['html']; ?></div>
					</div>
				</div>
			</div>

			<div id="pn-cm-ms-popup-clicked" class="pn-cm-ms-popup" style="display:none;" data-popup-type="clicked">
				<div class="pn-cm-ms-popup-content">
					<button class="pn-cm-ms-popup-close"><i class="material-icons-outlined">close</i></button>
					<div class="pn-cm-ms-popup-inner">
						<h2><?php echo esc_html($popup_titles['clicked']); ?></h2>
						<div class="pn-cm-ms-popup-body"><?php echo $clicked['html']; ?></div>
					</div>
				</div>
			</div>
		</div>
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
			'4.4.7',
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

		// Widget title templates for JS period-change
		$widget_labels = [
			'sent'       => __('Emails sent (%s)', 'pn-customers-manager'),
			'opened'     => __('Emails opened (%s)', 'pn-customers-manager'),
			'clicked'    => __('Clicks (%s)', 'pn-customers-manager'),
			'open_rate'  => __('Open rate (%s)', 'pn-customers-manager'),
			'click_rate' => __('Click rate (%s)', 'pn-customers-manager'),
		];

		wp_localize_script('pn-cm-mail-stats', 'pnCmMailStats', [
			'ajaxUrl'      => admin_url('admin-ajax.php'),
			'nonce'        => wp_create_nonce('pn-customers-manager-nonce'),
			'chartsData'   => $charts_data,
			'period'       => $period,
			'widgetLabels' => $widget_labels,
			'i18n'         => [
				'sent'     => __('Sent', 'pn-customers-manager'),
				'opened'   => __('Opened', 'pn-customers-manager'),
				'clicked'  => __('Clicks', 'pn-customers-manager'),
				'loading'  => __('Loading...', 'pn-customers-manager'),
			],
		]);
	}
}
