<?php
/**
 * Business Projections system.
 *
 * Handles data collection, projections algorithm, social media APIs,
 * and the admin dashboard for business projections.
 *
 * @link       padresenlanube.com/
 * @since      1.0.62
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

class PN_CUSTOMERS_MANAGER_Projections {

	// ──────────────────────────────────────────────
	// Plugin detection helpers
	// ──────────────────────────────────────────────

	public static function is_userspn_active() {
		return class_exists('UsersPn') || is_plugin_active('userspn/userspn.php');
	}

	public static function is_mailpn_active() {
		return class_exists('MailPn') || is_plugin_active('mailpn/mailpn.php');
	}

	// ──────────────────────────────────────────────
	// Snapshot collection (cron handler)
	// ──────────────────────────────────────────────

	public static function collect_snapshot() {
		global $wpdb;

		$today = current_time('Y-m-d');
		$metrics = [];

		// CRM internal metrics (always available)
		$metrics = array_merge($metrics, self::get_crm_internal_metrics());

		// UsersPn metrics
		if (self::is_userspn_active()) {
			$metrics = array_merge($metrics, self::get_userspn_analytics());
		}

		// MailPn metrics
		if (self::is_mailpn_active()) {
			$metrics = array_merge($metrics, self::get_mailpn_analytics());
		}

		// Social media metrics
		$social_metrics = self::get_social_media_analytics();
		if (!empty($social_metrics)) {
			$metrics = array_merge($metrics, $social_metrics);
		}

		// Store metrics
		$table = $wpdb->prefix . 'pn_cm_projections_snapshots';
		foreach ($metrics as $m) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$table} (snapshot_date, metric_source, metric_key, metric_value, created_at)
					VALUES (%s, %s, %s, %f, %s)
					ON DUPLICATE KEY UPDATE metric_value = %f",
					$today,
					$m['source'],
					$m['key'],
					$m['value'],
					current_time('mysql'),
					$m['value']
				)
			);
		}
	}

	// ──────────────────────────────────────────────
	// CRM internal metrics
	// ──────────────────────────────────────────────

	private static function get_crm_internal_metrics() {
		$metrics = [];

		// Total organizations
		$orgs = wp_count_posts('pn_cm_organization');
		$metrics[] = [
			'source' => 'crm',
			'key'    => 'total_organizations',
			'value'  => isset($orgs->publish) ? (float) $orgs->publish : 0,
		];

		// Total funnels
		$funnels = wp_count_posts('pn_cm_funnel');
		$metrics[] = [
			'source' => 'crm',
			'key'    => 'total_funnels',
			'value'  => isset($funnels->publish) ? (float) $funnels->publish : 0,
		];

		// Total contact messages
		global $wpdb;
		$table_messages = $wpdb->prefix . 'pn_cm_contact_messages';
		$total_messages = (float) $wpdb->get_var("SELECT COUNT(*) FROM {$table_messages}");
		$metrics[] = [
			'source' => 'crm',
			'key'    => 'total_messages',
			'value'  => $total_messages,
		];

		// Total referrals
		$referral_users = get_users([
			'meta_key'  => 'pn_cm_referral_code',
			'fields'    => 'ID',
		]);
		$total_referrals = 0;
		foreach ($referral_users as $uid) {
			$refs = get_user_meta($uid, 'pn_cm_referrals', true);
			if (is_array($refs)) {
				$total_referrals += count($refs);
			}
		}
		$metrics[] = [
			'source' => 'crm',
			'key'    => 'total_referrals',
			'value'  => (float) $total_referrals,
		];

		return $metrics;
	}

	// ──────────────────────────────────────────────
	// UsersPn analytics
	// ──────────────────────────────────────────────

	public static function get_userspn_analytics() {
		$metrics = [];

		$total = count_users();
		$metrics[] = [
			'source' => 'userspn',
			'key'    => 'total_users',
			'value'  => (float) $total['total_users'],
		];

		// New registrations last 30 days
		$thirty_days_ago = gmdate('Y-m-d H:i:s', strtotime('-30 days'));
		$new_users = new WP_User_Query([
			'date_query' => [
				['after' => $thirty_days_ago],
			],
			'count_total' => true,
			'fields'      => 'ID',
			'number'      => 0,
		]);
		$metrics[] = [
			'source' => 'userspn',
			'key'    => 'new_registrations',
			'value'  => (float) $new_users->get_total(),
		];

		// Active users (logged in within last 30 days)
		global $wpdb;
		$active_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
				WHERE meta_key = 'last_login'
				AND meta_value >= %s",
				$thirty_days_ago
			)
		);
		// Fallback: if last_login meta doesn't exist, try session_tokens
		if ($active_count === 0) {
			$active_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
					WHERE meta_key = 'session_tokens'
					AND meta_value != ''
					AND meta_value != 'a:0:{}'
					AND user_id IN (
						SELECT ID FROM {$wpdb->users} WHERE user_registered >= %s
						OR ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'last_activity' AND meta_value >= %s)
					)",
					$thirty_days_ago,
					strtotime($thirty_days_ago)
				)
			);
		}
		$metrics[] = [
			'source' => 'userspn',
			'key'    => 'active_users',
			'value'  => (float) $active_count,
		];

		return $metrics;
	}

	// ──────────────────────────────────────────────
	// MailPn analytics
	// ──────────────────────────────────────────────

	public static function get_mailpn_analytics() {
		$metrics = [];
		global $wpdb;

		// Total emails sent (mailpn_rec post type)
		$emails = wp_count_posts('mailpn_rec');
		$total_sent = 0;
		if ($emails) {
			$total_sent = (float) ($emails->publish ?? 0) + (float) ($emails->private ?? 0);
		}
		$metrics[] = [
			'source' => 'mailpn',
			'key'    => 'emails_sent',
			'value'  => $total_sent,
		];

		// Emails opened (check postmeta for open tracking)
		$opened = (float) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
			WHERE meta_key = '_mailpn_opened' AND meta_value = '1'
			AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mailpn_rec')"
		);
		$metrics[] = [
			'source' => 'mailpn',
			'key'    => 'emails_opened',
			'value'  => $opened,
		];

		// Emails clicked
		$table_clicks = $wpdb->prefix . 'mailpn_click_tracking';
		$clicked = 0;
		if ($wpdb->get_var("SHOW TABLES LIKE '{$table_clicks}'") === $table_clicks) {
			$clicked = (float) $wpdb->get_var("SELECT COUNT(DISTINCT email_id) FROM {$table_clicks}");
		}
		$metrics[] = [
			'source' => 'mailpn',
			'key'    => 'emails_clicked',
			'value'  => $clicked,
		];

		// Rates
		if ($total_sent > 0) {
			$metrics[] = [
				'source' => 'mailpn',
				'key'    => 'open_rate',
				'value'  => round(($opened / $total_sent) * 100, 2),
			];
			$metrics[] = [
				'source' => 'mailpn',
				'key'    => 'click_rate',
				'value'  => round(($clicked / $total_sent) * 100, 2),
			];
		} else {
			$metrics[] = ['source' => 'mailpn', 'key' => 'open_rate', 'value' => 0];
			$metrics[] = ['source' => 'mailpn', 'key' => 'click_rate', 'value' => 0];
		}

		return $metrics;
	}

	// ──────────────────────────────────────────────
	// Social Media APIs
	// ──────────────────────────────────────────────

	public static function get_social_media_analytics() {
		$metrics = [];

		// Instagram Graph API
		$ig_token = get_option('pn_customers_manager_social_ig_token', '');
		$ig_account = get_option('pn_customers_manager_social_ig_account_id', '');
		if (!empty($ig_token) && !empty($ig_account)) {
			$ig_metrics = self::fetch_instagram_metrics($ig_token, $ig_account);
			$metrics = array_merge($metrics, $ig_metrics);
		}

		// Facebook Insights
		$fb_token = get_option('pn_customers_manager_social_fb_token', '');
		$fb_page = get_option('pn_customers_manager_social_fb_page_id', '');
		if (!empty($fb_token) && !empty($fb_page)) {
			$fb_metrics = self::fetch_facebook_metrics($fb_token, $fb_page);
			$metrics = array_merge($metrics, $fb_metrics);
		}

		// Twitter/X API v2
		$tw_bearer = get_option('pn_customers_manager_social_tw_bearer', '');
		$tw_account = get_option('pn_customers_manager_social_tw_account_id', '');
		if (!empty($tw_bearer) && !empty($tw_account)) {
			$tw_metrics = self::fetch_twitter_metrics($tw_bearer, $tw_account);
			$metrics = array_merge($metrics, $tw_metrics);
		}

		return $metrics;
	}

	private static function fetch_instagram_metrics($token, $account_id) {
		$metrics = [];
		$url = "https://graph.facebook.com/v19.0/{$account_id}?fields=followers_count,media_count&access_token={$token}";
		$response = wp_remote_get($url, ['timeout' => 15]);

		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
			$data = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($data['followers_count'])) {
				$metrics[] = ['source' => 'instagram', 'key' => 'ig_followers', 'value' => (float) $data['followers_count']];
			}
		}

		// Insights (impressions, reach, engagement)
		$insights_url = "https://graph.facebook.com/v19.0/{$account_id}/insights?metric=impressions,reach&period=day&access_token={$token}";
		$ins_response = wp_remote_get($insights_url, ['timeout' => 15]);

		if (!is_wp_error($ins_response) && wp_remote_retrieve_response_code($ins_response) === 200) {
			$ins_data = json_decode(wp_remote_retrieve_body($ins_response), true);
			if (!empty($ins_data['data'])) {
				foreach ($ins_data['data'] as $insight) {
					$name = $insight['name'] ?? '';
					$values = $insight['values'] ?? [];
					$latest_value = !empty($values) ? (float) end($values)['value'] : 0;
					if ($name === 'impressions') {
						$metrics[] = ['source' => 'instagram', 'key' => 'ig_impressions', 'value' => $latest_value];
					} elseif ($name === 'reach') {
						$metrics[] = ['source' => 'instagram', 'key' => 'ig_reach', 'value' => $latest_value];
					}
				}
			}
		}

		return $metrics;
	}

	private static function fetch_facebook_metrics($token, $page_id) {
		$metrics = [];
		$url = "https://graph.facebook.com/v19.0/{$page_id}?fields=fan_count,engaged_users&access_token={$token}";
		$response = wp_remote_get($url, ['timeout' => 15]);

		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
			$data = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($data['fan_count'])) {
				$metrics[] = ['source' => 'facebook', 'key' => 'fb_fans', 'value' => (float) $data['fan_count']];
			}
		}

		// Page insights for engaged users
		$insights_url = "https://graph.facebook.com/v19.0/{$page_id}/insights?metric=page_engaged_users&period=day&access_token={$token}";
		$ins_response = wp_remote_get($insights_url, ['timeout' => 15]);

		if (!is_wp_error($ins_response) && wp_remote_retrieve_response_code($ins_response) === 200) {
			$ins_data = json_decode(wp_remote_retrieve_body($ins_response), true);
			if (!empty($ins_data['data'][0]['values'])) {
				$values = $ins_data['data'][0]['values'];
				$latest = (float) end($values)['value'];
				$metrics[] = ['source' => 'facebook', 'key' => 'fb_engaged_users', 'value' => $latest];
			}
		}

		return $metrics;
	}

	private static function fetch_twitter_metrics($bearer, $account_id) {
		$metrics = [];
		$url = "https://api.twitter.com/2/users/{$account_id}?user.fields=public_metrics";
		$response = wp_remote_get($url, [
			'timeout' => 15,
			'headers' => ['Authorization' => 'Bearer ' . $bearer],
		]);

		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
			$data = json_decode(wp_remote_retrieve_body($response), true);
			$pm = $data['data']['public_metrics'] ?? [];
			if (isset($pm['followers_count'])) {
				$metrics[] = ['source' => 'twitter', 'key' => 'tw_followers', 'value' => (float) $pm['followers_count']];
			}
			if (isset($pm['tweet_count'])) {
				$metrics[] = ['source' => 'twitter', 'key' => 'tw_tweets', 'value' => (float) $pm['tweet_count']];
			}
		}

		return $metrics;
	}

	// ──────────────────────────────────────────────
	// Manual projections — data helpers
	// ──────────────────────────────────────────────

	/**
	 * Return all manual projections for a given source/metric, ordered by target date.
	 */
	public static function get_manual_projections($source, $metric) {
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_projections_manual';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, metric_source, metric_key, target_date, projected_value, notes, created_by, created_at
				FROM {$table}
				WHERE metric_source = %s AND metric_key = %s
				ORDER BY target_date ASC",
				$source,
				$metric
			),
			ARRAY_A
		);
	}

	/**
	 * Compute the deviation between a manual projection and the closest actual snapshot.
	 * Returns null if the target date is still in the future (no actual value yet).
	 */
	public static function compute_projection_deviation($source, $metric, $target_date, $projected_value) {
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_projections_snapshots';

		$today = current_time('Y-m-d');
		if ($target_date > $today) {
			return null;
		}

		// Find the closest snapshot on or before the target date
		$actual = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT metric_value FROM {$table}
				WHERE metric_source = %s AND metric_key = %s
				AND snapshot_date <= %s
				ORDER BY snapshot_date DESC
				LIMIT 1",
				$source,
				$metric,
				$target_date
			)
		);

		if ($actual === null) {
			return null;
		}

		$actual = (float) $actual;
		$projected = (float) $projected_value;
		$deviation_pct = $projected != 0
			? round((($actual - $projected) / $projected) * 100, 2)
			: 0;

		return [
			'actual'        => $actual,
			'projected'     => $projected,
			'deviation_pct' => $deviation_pct,
		];
	}

	// ──────────────────────────────────────────────
	// AJAX handlers
	// ──────────────────────────────────────────────

	public static function ajax_get_projection_data() {
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_projections_snapshots';

		$source = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : 'crm';
		$metric = isset($_POST['metric']) ? sanitize_text_field(wp_unslash($_POST['metric'])) : '';

		$valid_sources = ['crm', 'userspn', 'mailpn', 'instagram', 'facebook', 'twitter'];
		if (!in_array($source, $valid_sources)) {
			$source = 'crm';
		}

		// Get available metrics for source
		$available_metrics = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT metric_key FROM {$table} WHERE metric_source = %s ORDER BY metric_key",
				$source
			)
		);

		if (empty($metric) && !empty($available_metrics)) {
			$metric = $available_metrics[0];
		}

		// Get historical data (actual values collected by the cron)
		$historical = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT snapshot_date, metric_value FROM {$table}
				WHERE metric_source = %s AND metric_key = %s
				ORDER BY snapshot_date ASC",
				$source,
				$metric
			),
			ARRAY_A
		);

		// Get manual projections for this metric
		$manual_projections = self::get_manual_projections($source, $metric);

		// Enrich projections with deviation info when the target date has passed
		$projections_with_deviation = [];
		foreach ($manual_projections as $proj) {
			$deviation = self::compute_projection_deviation(
				$proj['metric_source'],
				$proj['metric_key'],
				$proj['target_date'],
				$proj['projected_value']
			);
			$projections_with_deviation[] = array_merge($proj, [
				'deviation' => $deviation,
			]);
		}

		// Frequency label for the UI
		$frequency = get_option('pn_customers_manager_projection_frequency', 'daily');

		echo wp_json_encode([
			'error_key'         => '',
			'available_metrics' => $available_metrics,
			'current_metric'    => $metric,
			'historical'        => $historical,
			'projections'       => $projections_with_deviation,
			'frequency'         => $frequency,
		]);
		exit;
	}

	public static function ajax_create_projection() {
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_projections_manual';

		$source = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : '';
		$metric = isset($_POST['metric']) ? sanitize_text_field(wp_unslash($_POST['metric'])) : '';
		$target_date = isset($_POST['target_date']) ? sanitize_text_field(wp_unslash($_POST['target_date'])) : '';
		$projected_value = isset($_POST['projected_value']) ? (float) $_POST['projected_value'] : 0;
		$notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';

		$valid_sources = ['crm', 'userspn', 'mailpn', 'instagram', 'facebook', 'twitter'];
		if (!in_array($source, $valid_sources, true) || empty($metric) || empty($target_date)) {
			echo wp_json_encode([
				'error_key'     => 'invalid_input',
				'error_content' => esc_html__('Please fill in all required fields.', 'pn-customers-manager'),
			]);
			exit;
		}

		// Validate date format YYYY-MM-DD
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $target_date)) {
			echo wp_json_encode([
				'error_key'     => 'invalid_date',
				'error_content' => esc_html__('Invalid target date.', 'pn-customers-manager'),
			]);
			exit;
		}

		$inserted = $wpdb->insert(
			$table,
			[
				'metric_source'   => $source,
				'metric_key'      => $metric,
				'target_date'     => $target_date,
				'projected_value' => $projected_value,
				'notes'           => $notes,
				'created_by'      => get_current_user_id(),
				'created_at'      => current_time('mysql'),
			],
			['%s', '%s', '%s', '%f', '%s', '%d', '%s']
		);

		if (!$inserted) {
			echo wp_json_encode([
				'error_key'     => 'insert_failed',
				'error_content' => esc_html__('Could not save the projection.', 'pn-customers-manager'),
			]);
			exit;
		}

		echo wp_json_encode([
			'error_key' => '',
			'message'   => esc_html__('Projection created.', 'pn-customers-manager'),
			'id'        => (int) $wpdb->insert_id,
		]);
		exit;
	}

	public static function ajax_delete_projection() {
		global $wpdb;
		$table = $wpdb->prefix . 'pn_cm_projections_manual';

		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		if ($id <= 0) {
			echo wp_json_encode([
				'error_key'     => 'invalid_id',
				'error_content' => esc_html__('Invalid projection ID.', 'pn-customers-manager'),
			]);
			exit;
		}

		$deleted = $wpdb->delete($table, ['id' => $id], ['%d']);
		if (!$deleted) {
			echo wp_json_encode([
				'error_key'     => 'delete_failed',
				'error_content' => esc_html__('Could not delete the projection.', 'pn-customers-manager'),
			]);
			exit;
		}

		echo wp_json_encode([
			'error_key' => '',
			'message'   => esc_html__('Projection deleted.', 'pn-customers-manager'),
		]);
		exit;
	}

	public static function ajax_get_social_metrics() {
		$metrics = self::get_social_media_analytics();

		echo wp_json_encode([
			'error_key' => '',
			'metrics'   => $metrics,
		]);
		exit;
	}

	public static function ajax_test_social_media() {
		$results = [];

		// Test Instagram
		$ig_token = get_option('pn_customers_manager_social_ig_token', '');
		$ig_account = get_option('pn_customers_manager_social_ig_account_id', '');
		if (!empty($ig_token) && !empty($ig_account)) {
			$url = "https://graph.facebook.com/v19.0/{$ig_account}?fields=id,username&access_token={$ig_token}";
			$response = wp_remote_get($url, ['timeout' => 15]);
			if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
				$data = json_decode(wp_remote_retrieve_body($response), true);
				$results['instagram'] = [
					'status'  => 'ok',
					'message' => sprintf(
						/* translators: %s: Instagram username */
						esc_html__('Connected: @%s', 'pn-customers-manager'),
						$data['username'] ?? $ig_account
					),
				];
			} else {
				$err = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
				$results['instagram'] = ['status' => 'error', 'message' => $err];
			}
		} else {
			$results['instagram'] = ['status' => 'not_configured', 'message' => esc_html__('Not configured.', 'pn-customers-manager')];
		}

		// Test Facebook
		$fb_token = get_option('pn_customers_manager_social_fb_token', '');
		$fb_page = get_option('pn_customers_manager_social_fb_page_id', '');
		if (!empty($fb_token) && !empty($fb_page)) {
			$url = "https://graph.facebook.com/v19.0/{$fb_page}?fields=id,name&access_token={$fb_token}";
			$response = wp_remote_get($url, ['timeout' => 15]);
			if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
				$data = json_decode(wp_remote_retrieve_body($response), true);
				$results['facebook'] = [
					'status'  => 'ok',
					'message' => sprintf(
						/* translators: %s: Facebook page name */
						esc_html__('Connected: %s', 'pn-customers-manager'),
						$data['name'] ?? $fb_page
					),
				];
			} else {
				$err = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
				$results['facebook'] = ['status' => 'error', 'message' => $err];
			}
		} else {
			$results['facebook'] = ['status' => 'not_configured', 'message' => esc_html__('Not configured.', 'pn-customers-manager')];
		}

		// Test Twitter/X
		$tw_bearer = get_option('pn_customers_manager_social_tw_bearer', '');
		$tw_account = get_option('pn_customers_manager_social_tw_account_id', '');
		if (!empty($tw_bearer) && !empty($tw_account)) {
			$url = "https://api.twitter.com/2/users/{$tw_account}?user.fields=username";
			$response = wp_remote_get($url, [
				'timeout' => 15,
				'headers' => ['Authorization' => 'Bearer ' . $tw_bearer],
			]);
			if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
				$data = json_decode(wp_remote_retrieve_body($response), true);
				$results['twitter'] = [
					'status'  => 'ok',
					'message' => sprintf(
						/* translators: %s: Twitter username */
						esc_html__('Connected: @%s', 'pn-customers-manager'),
						$data['data']['username'] ?? $tw_account
					),
				];
			} else {
				$err = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
				$results['twitter'] = ['status' => 'error', 'message' => $err];
			}
		} else {
			$results['twitter'] = ['status' => 'not_configured', 'message' => esc_html__('Not configured.', 'pn-customers-manager')];
		}

		echo wp_json_encode(['error_key' => '', 'results' => $results]);
		exit;
	}

	// ──────────────────────────────────────────────
	// Admin page rendering
	// ──────────────────────────────────────────────

	public static function render_page() {
		$userspn_active = self::is_userspn_active();
		$mailpn_active = self::is_mailpn_active();

		$ig_configured = !empty(get_option('pn_customers_manager_social_ig_token', ''));
		$fb_configured = !empty(get_option('pn_customers_manager_social_fb_token', ''));
		$tw_configured = !empty(get_option('pn_customers_manager_social_tw_bearer', ''));

		$settings_url = admin_url('admin.php?page=pn_customers_manager_options');
		?>
		<div class="wrap pn-cm-projections-wrap">
			<h1><?php esc_html_e('Projections', 'pn-customers-manager'); ?></h1>

			<!-- Plugin status cards -->
			<div class="pn-cm-proj-status-cards">
				<div class="pn-cm-proj-status-card <?php echo $userspn_active ? 'pn-cm-proj-active' : 'pn-cm-proj-inactive'; ?>">
					<span class="pn-cm-proj-status-icon material-icons-outlined"><?php echo $userspn_active ? 'check_circle' : 'cancel'; ?></span>
					<span class="pn-cm-proj-status-label"><?php esc_html_e('Users', 'pn-customers-manager'); ?></span>
					<?php if (!$userspn_active): ?>
						<small><?php esc_html_e('Not installed', 'pn-customers-manager'); ?></small>
					<?php endif; ?>
				</div>
				<div class="pn-cm-proj-status-card <?php echo $mailpn_active ? 'pn-cm-proj-active' : 'pn-cm-proj-inactive'; ?>">
					<span class="pn-cm-proj-status-icon material-icons-outlined"><?php echo $mailpn_active ? 'check_circle' : 'cancel'; ?></span>
					<span class="pn-cm-proj-status-label"><?php esc_html_e('Emails', 'pn-customers-manager'); ?></span>
					<?php if (!$mailpn_active): ?>
						<small><?php esc_html_e('Not installed', 'pn-customers-manager'); ?></small>
					<?php endif; ?>
				</div>
				<div class="pn-cm-proj-status-card <?php echo $ig_configured ? 'pn-cm-proj-active' : 'pn-cm-proj-inactive'; ?>">
					<span class="pn-cm-proj-status-icon material-icons-outlined"><?php echo $ig_configured ? 'check_circle' : 'cancel'; ?></span>
					<span class="pn-cm-proj-status-label">Instagram</span>
					<?php if (!$ig_configured): ?>
						<small><a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Configure', 'pn-customers-manager'); ?></a></small>
					<?php endif; ?>
				</div>
				<div class="pn-cm-proj-status-card <?php echo $fb_configured ? 'pn-cm-proj-active' : 'pn-cm-proj-inactive'; ?>">
					<span class="pn-cm-proj-status-icon material-icons-outlined"><?php echo $fb_configured ? 'check_circle' : 'cancel'; ?></span>
					<span class="pn-cm-proj-status-label">Facebook</span>
					<?php if (!$fb_configured): ?>
						<small><a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Configure', 'pn-customers-manager'); ?></a></small>
					<?php endif; ?>
				</div>
				<div class="pn-cm-proj-status-card <?php echo $tw_configured ? 'pn-cm-proj-active' : 'pn-cm-proj-inactive'; ?>">
					<span class="pn-cm-proj-status-icon material-icons-outlined"><?php echo $tw_configured ? 'check_circle' : 'cancel'; ?></span>
					<span class="pn-cm-proj-status-label">X / Twitter</span>
					<?php if (!$tw_configured): ?>
						<small><a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Configure', 'pn-customers-manager'); ?></a></small>
					<?php endif; ?>
				</div>
			</div>

			<!-- Toolbar -->
			<div class="pn-cm-proj-toolbar">
				<div class="pn-cm-proj-frequency-note">
					<span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">schedule</span>
					<?php
					$frequency_labels = [
						'hourly'     => __('Hourly', 'pn-customers-manager'),
						'twicedaily' => __('Twice daily', 'pn-customers-manager'),
						'daily'      => __('Daily', 'pn-customers-manager'),
						'weekly'     => __('Weekly', 'pn-customers-manager'),
					];
					$current_frequency = get_option('pn_customers_manager_projection_frequency', 'daily');
					$frequency_label = isset($frequency_labels[$current_frequency]) ? $frequency_labels[$current_frequency] : $frequency_labels['daily'];
					printf(
						/* translators: %s: Snapshot frequency label (e.g. Daily) */
						esc_html__('Automatic snapshot frequency: %s', 'pn-customers-manager'),
						'<strong>' . esc_html($frequency_label) . '</strong>'
					);
					?>
					<a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Change', 'pn-customers-manager'); ?></a>
				</div>
				<button type="button" id="pn-cm-proj-open-modal" class="pn-customers-manager-btn pn-customers-manager-btn-mini">
					<span class="material-icons-outlined" style="font-size:18px;vertical-align:middle;">add_chart</span>
					<?php esc_html_e('Create projection', 'pn-customers-manager'); ?>
				</button>
			</div>

			<!-- Tabs -->
			<div class="pn-cm-proj-tabs">
				<?php if ($userspn_active): ?>
					<button type="button" class="pn-cm-proj-tab" data-source="userspn"><?php esc_html_e('Users', 'pn-customers-manager'); ?></button>
				<?php endif; ?>
				<?php if ($mailpn_active): ?>
					<button type="button" class="pn-cm-proj-tab" data-source="mailpn"><?php esc_html_e('Email', 'pn-customers-manager'); ?></button>
				<?php endif; ?>
				<?php if ($ig_configured || $fb_configured || $tw_configured): ?>
					<button type="button" class="pn-cm-proj-tab" data-source="social"><?php esc_html_e('Social Media', 'pn-customers-manager'); ?></button>
				<?php endif; ?>
				<button type="button" class="pn-cm-proj-tab active" data-source="crm"><?php esc_html_e('CRM', 'pn-customers-manager'); ?></button>
			</div>

			<!-- Social media sub-tabs (hidden by default) -->
			<div class="pn-cm-proj-social-subtabs" style="display:none;">
				<?php if ($ig_configured): ?>
					<button type="button" class="pn-cm-proj-social-subtab active" data-source="instagram">Instagram</button>
				<?php endif; ?>
				<?php if ($fb_configured): ?>
					<button type="button" class="pn-cm-proj-social-subtab" data-source="facebook">Facebook</button>
				<?php endif; ?>
				<?php if ($tw_configured): ?>
					<button type="button" class="pn-cm-proj-social-subtab" data-source="twitter">X / Twitter</button>
				<?php endif; ?>
			</div>

			<!-- Metric selector -->
			<div class="pn-cm-proj-metric-selector">
				<label for="pn-cm-proj-metric"><?php esc_html_e('Metric:', 'pn-customers-manager'); ?></label>
				<select id="pn-cm-proj-metric"></select>
			</div>

			<!-- Chart: historical evolution + manual projections overlay -->
			<div class="pn-cm-proj-chart-container">
				<canvas id="pn-cm-proj-line-chart"></canvas>
			</div>

			<div id="pn-cm-proj-no-data" style="display:none;">
				<p><?php esc_html_e('No snapshots have been collected yet. The chart will populate once the cron runs.', 'pn-customers-manager'); ?></p>
			</div>

			<!-- Manual projections list -->
			<div class="pn-cm-proj-list-wrapper">
				<h3><?php esc_html_e('Manual projections', 'pn-customers-manager'); ?></h3>
				<table id="pn-cm-proj-list-table" class="pn-cm-proj-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Target date', 'pn-customers-manager'); ?></th>
							<th><?php esc_html_e('Projected', 'pn-customers-manager'); ?></th>
							<th><?php esc_html_e('Actual', 'pn-customers-manager'); ?></th>
							<th><?php esc_html_e('Deviation %', 'pn-customers-manager'); ?></th>
							<th><?php esc_html_e('Notes', 'pn-customers-manager'); ?></th>
							<th><?php esc_html_e('Actions', 'pn-customers-manager'); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
				<div id="pn-cm-proj-list-empty" style="display:none;">
					<p><?php esc_html_e('No manual projections for this metric yet.', 'pn-customers-manager'); ?></p>
				</div>
			</div>

			<!-- Create projection modal -->
			<?php
			// Build source options dynamically based on active integrations.
			$source_options = [
				'crm' => esc_html__('CRM', 'pn-customers-manager'),
			];
			if ($userspn_active) {
				$source_options['userspn'] = esc_html__('Users', 'pn-customers-manager');
			}
			if ($mailpn_active) {
				$source_options['mailpn'] = esc_html__('Email', 'pn-customers-manager');
			}
			if ($ig_configured) {
				$source_options['instagram'] = 'Instagram';
			}
			if ($fb_configured) {
				$source_options['facebook'] = 'Facebook';
			}
			if ($tw_configured) {
				$source_options['twitter'] = 'X / Twitter';
			}

			// Build the modal form field definitions for the Forms class.
			$modal_fields = self::get_modal_form_fields($source_options);
			?>
			<div id="pn-cm-proj-modal" class="pn-cm-proj-modal" style="display:none;">
				<div class="pn-cm-proj-modal-backdrop"></div>
				<div class="pn-cm-proj-modal-content">
					<button type="button" class="pn-cm-proj-modal-close" aria-label="<?php esc_attr_e('Close', 'pn-customers-manager'); ?>">&times;</button>
					<h2><?php esc_html_e('Create projection', 'pn-customers-manager'); ?></h2>
					<p class="pn-cm-proj-modal-intro"><?php esc_html_e('Enter a manual future estimate for a metric. Over time, the chart will show your projection against the actual values collected by the cron so you can compare results.', 'pn-customers-manager'); ?></p>
					<form id="pn-cm-proj-form">
						<?php foreach ($modal_fields as $field): ?>
							<?php
							ob_start();
							PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', 0, 0, 'full');
							$field_html = ob_get_clean();
							echo wp_kses($field_html, PN_CUSTOMERS_MANAGER_KSES);
							?>
						<?php endforeach; ?>
						<div class="pn-cm-proj-form-actions">
							<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-cm-proj-modal-cancel"><?php esc_html_e('Cancel', 'pn-customers-manager'); ?></button>
							<button type="submit" class="pn-customers-manager-btn pn-customers-manager-btn-mini"><?php esc_html_e('Save projection', 'pn-customers-manager'); ?></button>
						</div>
						<span id="pn-cm-proj-form-status"></span>
					</form>
				</div>
			</div>
		</div>
		<?php

		self::enqueue_assets();
	}

	/**
	 * Build the field definitions for the "create projection" modal form.
	 *
	 * All forms in the plugin are rendered through the PN_CUSTOMERS_MANAGER_Forms
	 * class by passing an array of field configurations. This helper returns
	 * that array so that render_page() can loop over it and delegate the markup
	 * generation to pn_customers_manager_input_wrapper_builder().
	 *
	 * @param array $source_options value => label map of available sources.
	 * @return array[]
	 */
	private static function get_modal_form_fields($source_options) {
		return [
			[
				'id'       => 'pn-cm-proj-form-source',
				'label'    => esc_html__('Source', 'pn-customers-manager'),
				'input'    => 'select',
				'class'    => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
				'options'  => $source_options,
				'value'    => 'crm',
				'required' => true,
			],
			[
				'id'       => 'pn-cm-proj-form-metric',
				'label'    => esc_html__('Metric', 'pn-customers-manager'),
				'input'    => 'select',
				'class'    => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
				// Placeholder option so the <select> is rendered; the JS will
				// replace the options as soon as the metrics list is loaded.
				'options'  => ['' => esc_html__('Loading...', 'pn-customers-manager')],
				'required' => true,
			],
			[
				'id'       => 'pn-cm-proj-form-date',
				'label'    => esc_html__('Target date', 'pn-customers-manager'),
				'input'    => 'input',
				'type'     => 'date',
				'class'    => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
				'required' => true,
			],
			[
				'id'       => 'pn-cm-proj-form-value',
				'label'    => esc_html__('Expected value', 'pn-customers-manager'),
				'input'    => 'input',
				'type'     => 'number',
				'class'    => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
				'step'     => '0.01',
				'min'      => '0',
				'required' => true,
			],
			[
				'id'          => 'pn-cm-proj-form-notes',
				'label'       => esc_html__('Notes (optional)', 'pn-customers-manager'),
				'input'       => 'textarea',
				'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
				'placeholder' => esc_html__('Optional notes about this projection', 'pn-customers-manager'),
			],
		];
	}

	// ──────────────────────────────────────────────
	// Asset enqueue (page-specific)
	// ──────────────────────────────────────────────

	private static function enqueue_assets() {
		// Chart.js
		wp_enqueue_script(
			'chartjs',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/vendor/chart.min.js',
			[],
			'4.4.7',
			true
		);

		// Projections CSS
		wp_enqueue_style(
			'pn-cm-projections',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-projections.css',
			[],
			PN_CUSTOMERS_MANAGER_VERSION
		);

		// Projections JS
		wp_enqueue_script(
			'pn-cm-projections',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-projections.js',
			['chartjs', 'jquery'],
			PN_CUSTOMERS_MANAGER_VERSION,
			true
		);

		wp_localize_script('pn-cm-projections', 'pnCmProjections', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('pn-customers-manager-nonce'),
			'i18n'    => [
				'historical'       => __('Historical', 'pn-customers-manager'),
				'projected'        => __('Manual projection', 'pn-customers-manager'),
				'actual'           => __('Actual', 'pn-customers-manager'),
				'saving'           => __('Saving projection...', 'pn-customers-manager'),
				'saved'            => __('Projection saved.', 'pn-customers-manager'),
				'saveError'        => __('Error saving projection.', 'pn-customers-manager'),
				'deleting'         => __('Deleting...', 'pn-customers-manager'),
				'deleted'          => __('Projection deleted.', 'pn-customers-manager'),
				'deleteError'      => __('Error deleting projection.', 'pn-customers-manager'),
				'confirmDelete'    => __('Delete this projection?', 'pn-customers-manager'),
				'noData'           => __('No data available yet.', 'pn-customers-manager'),
				'noSnapshots'      => __('No snapshots have been collected yet. The chart will populate once the cron runs.', 'pn-customers-manager'),
				'noProjections'    => __('No manual projections for this metric yet.', 'pn-customers-manager'),
				'loading'          => __('Loading...', 'pn-customers-manager'),
				'pending'          => __('Pending', 'pn-customers-manager'),
				'delete'           => __('Delete', 'pn-customers-manager'),
				'testing'          => __('Testing...', 'pn-customers-manager'),
			],
		]);
	}
}
