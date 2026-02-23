<?php
/**
 * Referral system for pn-customers-manager.
 *
 * Handles referral creation, acceptance, email sending, shortcode rendering,
 * and Gutenberg block registration.
 *
 * @link       padresenlanube.com/
 * @since      1.0.6
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Referral {

	/**
	 * QR landing: the code from the URL.
	 * @var string|null
	 */
	private static $qr_landing_code = null;

	/**
	 * QR landing: the referrer user ID.
	 * @var int|null
	 */
	private static $qr_landing_referrer_id = null;

	/**
	 * Get or create a permanent 8-char referral code for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return string The 8-char uppercase alphanumeric code.
	 */
	public static function get_or_create_referral_code($user_id) {
		$code = get_user_meta($user_id, 'pn_cm_qr_referral_code', true);

		if (!empty($code) && strlen($code) === 8) {
			return $code;
		}

		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$max_attempts = 20;

		for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
			$code = '';
			for ($i = 0; $i < 8; $i++) {
				$code .= $chars[wp_rand(0, strlen($chars) - 1)];
			}

			$existing = get_users([
				'meta_key'   => 'pn_cm_qr_referral_code',
				'meta_value' => $code,
				'number'     => 1,
				'fields'     => 'ID',
			]);

			if (empty($existing)) {
				update_user_meta($user_id, 'pn_cm_qr_referral_code', $code);
				return $code;
			}
		}

		$code = strtoupper(substr(md5($user_id . wp_salt()), 0, 8));
		update_user_meta($user_id, 'pn_cm_qr_referral_code', $code);
		return $code;
	}

	/**
	 * Get the QR branding image URL from settings.
	 *
	 * @return string|false The image URL or false if not set.
	 */
	public static function get_qr_branding_url() {
		$attachment_id = get_option('pn_customers_manager_referral_qr_branding', '');

		if (empty($attachment_id)) {
			return false;
		}

		$url = wp_get_attachment_image_url((int) $attachment_id, 'thumbnail');
		return $url ? $url : false;
	}

	/**
	 * Find a user by their QR referral code.
	 *
	 * @param string $code The 8-char referral code.
	 * @return int|false The user ID or false if not found.
	 */
	public static function find_user_by_qr_code($code) {
		$users = get_users([
			'meta_key'   => 'pn_cm_qr_referral_code',
			'meta_value' => sanitize_text_field($code),
			'number'     => 1,
			'fields'     => 'ID',
		]);

		return !empty($users) ? (int) $users[0] : false;
	}

	/**
	 * Handle QR referral landing on template_redirect (priority 5).
	 * Detects ?pn_cm_qr_ref=CODE, validates, and stores in static props.
	 */
	public static function handle_qr_referral_landing() {
		if (empty($_GET['pn_cm_qr_ref'])) {
			return;
		}

		$code = sanitize_text_field(wp_unslash($_GET['pn_cm_qr_ref']));

		if (strlen($code) !== 8 || !preg_match('/^[A-Z0-9]{8}$/', $code)) {
			return;
		}

		$referrer_id = self::find_user_by_qr_code($code);

		if (!$referrer_id) {
			return;
		}

		self::$qr_landing_code = $code;
		self::$qr_landing_referrer_id = $referrer_id;
	}

	/**
	 * Inject QR landing popup on wp_footer if a valid QR code was detected.
	 */
	public static function inject_qr_landing_popup() {
		if (empty(self::$qr_landing_code) || empty(self::$qr_landing_referrer_id)) {
			return;
		}

		if (get_option('pn_customers_manager_referral_enabled', 'on') !== 'on') {
			return;
		}

		$referrer = get_userdata(self::$qr_landing_referrer_id);
		$referrer_name = $referrer ? $referrer->display_name : __('Alguien', 'pn-customers-manager');

		$popup_id = 'pn-cm-qr-referral-landing-popup';
		?>
		<div id="<?php echo esc_attr($popup_id); ?>" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
			<div class="pn-customers-manager-popup-overlay"></div>
			<div class="pn-customers-manager-popup-content">
				<div class="pn-cm-qr-referral-landing-form">
					<h3><?php echo esc_html(sprintf(
						/* translators: %s: referrer name */
						__('%s te ha invitado!', 'pn-customers-manager'),
						$referrer_name
					)); ?></h3>
					<p><?php esc_html_e('Introduce tu email para completar el registro.', 'pn-customers-manager'); ?></p>
					<input type="email" id="pn-cm-qr-referral-email" placeholder="<?php esc_attr_e('Tu email', 'pn-customers-manager'); ?>" />
					<input type="hidden" id="pn-cm-qr-referral-code" value="<?php echo esc_attr(self::$qr_landing_code); ?>" />
					<button type="button" id="pn-cm-qr-referral-submit" class="pn-cm-referral-submit"><?php esc_html_e('Registrarme', 'pn-customers-manager'); ?></button>
					<div class="pn-cm-qr-referral-landing-message" style="display:none;"></div>
				</div>
			</div>
		</div>
		<script>
			(function() {
				if (typeof pn_customers_manager_Popups !== 'undefined') {
					pn_customers_manager_Popups.open('<?php echo esc_js($popup_id); ?>');
				} else {
					document.addEventListener('DOMContentLoaded', function() {
						if (typeof pn_customers_manager_Popups !== 'undefined') {
							pn_customers_manager_Popups.open('<?php echo esc_js($popup_id); ?>');
						}
					});
				}
			})();
		</script>
		<?php
	}

	/**
	 * Render the referrals shortcode.
	 *
	 * Outputs: create form, stats cards, referral list, and top referrers ranking.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_referrals_shortcode($atts = []) {
		if (!is_user_logged_in()) {
			return '<p class="pn-cm-referral-login-required">' . esc_html__('Please log in to access the referrals panel.', 'pn-customers-manager') . '</p>';
		}

		$user_id = get_current_user_id();
		$referrals = self::get_user_referrals($user_id);
		$completed_count = self::get_completed_count($user_id);
		$total_count = count($referrals);
		$pending_count = $total_count - $completed_count;
		$top_referrers = self::get_top_referrers(10);

		ob_start();
		?>
		<div class="pn-cm-referral-panel">

			<div class="pn-cm-referral-create">
				<h3><?php esc_html_e('Invite someone', 'pn-customers-manager'); ?></h3>
				<div class="pn-cm-referral-form">
					<input type="email" class="pn-cm-referral-email" placeholder="<?php esc_attr_e('Enter email address', 'pn-customers-manager'); ?>" />
					<button type="button" class="pn-cm-referral-submit"><?php esc_html_e('Send invitation', 'pn-customers-manager'); ?></button>
				</div>
				<div class="pn-cm-referral-link-display" style="display:none;">
					<label><?php esc_html_e('Referral link:', 'pn-customers-manager'); ?></label>
					<div class="pn-cm-referral-link-row">
						<input type="text" class="pn-cm-referral-link-input" readonly />
						<button type="button" class="pn-cm-referral-copy-link"><?php esc_html_e('Copy', 'pn-customers-manager'); ?></button>
					</div>
				</div>
				<div class="pn-cm-referral-message" style="display:none;"></div>
			</div>

			<?php if (get_option('pn_customers_manager_referral_enabled', 'on') === 'on') :
				$qr_code = self::get_or_create_referral_code($user_id);
				$qr_url = home_url('?pn_cm_qr_ref=' . $qr_code);
				$branding_url = self::get_qr_branding_url();
			?>
			<details class="pn-cm-referral-qr-section">
				<summary class="pn-cm-referral-qr-toggle"><?php esc_html_e('Tu codigo QR de referido', 'pn-customers-manager'); ?></summary>
				<div class="pn-cm-referral-qr-body">
					<div class="pn-cm-referral-qr-canvas-wrapper">
						<canvas id="pn-cm-referral-qr-canvas" width="300" height="340"></canvas>
					</div>
					<div class="pn-cm-referral-qr-code-label"><?php echo esc_html($qr_code); ?></div>
					<button type="button" class="pn-cm-referral-qr-download"><?php esc_html_e('Descargar QR', 'pn-customers-manager'); ?></button>
				</div>
			</details>
			<script>
				window.pnCmReferralQrData = {
					url: <?php echo wp_json_encode($qr_url); ?>,
					code: <?php echo wp_json_encode($qr_code); ?>,
					brandingUrl: <?php echo wp_json_encode($branding_url ? $branding_url : ''); ?>
				};
			</script>
			<?php endif; ?>

			<div class="pn-cm-referral-stats">
				<div class="pn-cm-referral-stat-card">
					<span class="pn-cm-referral-stat-number pn-cm-referral-stat-total"><?php echo esc_html($total_count); ?></span>
					<span class="pn-cm-referral-stat-label"><?php esc_html_e('Total', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-referral-stat-card">
					<span class="pn-cm-referral-stat-number pn-cm-referral-stat-completed"><?php echo esc_html($completed_count); ?></span>
					<span class="pn-cm-referral-stat-label"><?php esc_html_e('Completed', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-referral-stat-card">
					<span class="pn-cm-referral-stat-number pn-cm-referral-stat-pending"><?php echo esc_html($pending_count); ?></span>
					<span class="pn-cm-referral-stat-label"><?php esc_html_e('Pending', 'pn-customers-manager'); ?></span>
				</div>
			</div>

			<div class="pn-cm-referral-list">
				<div class="pn-cm-referral-list-header">
					<span class="pn-cm-referral-col-email"><?php esc_html_e('Email', 'pn-customers-manager'); ?></span>
					<span class="pn-cm-referral-col-status"><?php esc_html_e('Status', 'pn-customers-manager'); ?></span>
					<span class="pn-cm-referral-col-date"><?php esc_html_e('Date', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-referral-list-body">
					<?php if (!empty($referrals)) : ?>
						<?php foreach (array_reverse($referrals) as $referral) : ?>
							<div class="pn-cm-referral-item" data-status="<?php echo esc_attr($referral['status']); ?>">
								<span class="pn-cm-referral-col-email"><?php echo esc_html($referral['email']); ?></span>
								<span class="pn-cm-referral-col-status">
									<span class="pn-cm-referral-badge pn-cm-referral-badge-<?php echo esc_attr($referral['status']); ?>">
										<?php echo esc_html(ucfirst($referral['status'])); ?>
									</span>
								</span>
								<span class="pn-cm-referral-col-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($referral['created_at']))); ?></span>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="pn-cm-referral-empty"><?php esc_html_e('No referrals yet. Invite someone to get started!', 'pn-customers-manager'); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<?php if (!empty($top_referrers) && get_option('pn_customers_manager_referral_show_ranking', '') === 'on') : ?>
			<div class="pn-cm-referral-ranking">
				<h3><?php esc_html_e('Top Referrers', 'pn-customers-manager'); ?></h3>
				<ol class="pn-cm-referral-leaderboard">
					<?php foreach ($top_referrers as $index => $referrer) :
						$user_data = get_userdata($referrer['user_id']);
						$display_name = $user_data ? $user_data->display_name : __('Unknown', 'pn-customers-manager');
						$is_current = (int)$referrer['user_id'] === $user_id;
					?>
						<li class="pn-cm-referral-leaderboard-item<?php echo $is_current ? ' pn-cm-referral-leaderboard-current' : ''; ?>">
							<span class="pn-cm-referral-leaderboard-rank"><?php echo esc_html($index + 1); ?></span>
							<span class="pn-cm-referral-leaderboard-name"><?php echo esc_html($display_name); ?></span>
							<span class="pn-cm-referral-leaderboard-count"><?php echo esc_html($referrer['count']); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Gutenberg block render callback.
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML output.
	 */
	public static function render_block($attributes) {
		return self::render_referrals_shortcode($attributes);
	}

	/**
	 * Register the Gutenberg block.
	 */
	public static function register_block() {
		register_block_type('pn-customers-manager/referrals', [
			'render_callback' => [__CLASS__, 'render_block'],
			'attributes' => [],
		]);
	}

	/**
	 * Handle referral acceptance via template_redirect.
	 *
	 * Validates token, marks referral as completed, creates password reset key,
	 * and redirects the referred user to set their password.
	 */
	public static function handle_referral_acceptance() {
		if (empty($_GET['pn_cm_ref'])) {
			return;
		}

		$token = sanitize_text_field(wp_unslash($_GET['pn_cm_ref']));

		if (empty($token)) {
			return;
		}

		$referred_users = get_users([
			'meta_key'   => 'pn_cm_referral_token',
			'meta_value' => $token,
			'number'     => 1,
			'fields'     => 'all',
		]);

		if (empty($referred_users)) {
			wp_safe_redirect(home_url());
			exit;
		}

		$referred_user = $referred_users[0];
		$referrer_id = get_user_meta($referred_user->ID, 'pn_cm_referred_by', true);

		if (!empty($referrer_id)) {
			self::complete_referral((int) $referrer_id, $token);
		}

		delete_user_meta($referred_user->ID, 'pn_cm_referral_token');

		$reset_key = get_password_reset_key($referred_user);

		if (!is_wp_error($reset_key)) {
			$redirect_url = network_site_url("wp-login.php?action=rp&key={$reset_key}&login=" . rawurlencode($referred_user->user_login), 'login');
		} else {
			$redirect_url = wp_login_url();
		}

		wp_safe_redirect($redirect_url);
		exit;
	}

	/**
	 * Create a new referral.
	 *
	 * Validates email, prevents duplicates, creates the WP user,
	 * stores metadata, and sends the invitation email.
	 *
	 * @param int    $referrer_id The ID of the referring user.
	 * @param string $email       The email of the person being referred.
	 * @return array Result with 'referral_link' and 'referral' on success, or 'error' key on failure.
	 */
	public static function create_referral($referrer_id, $email) {
		if (!is_email($email)) {
			return ['error' => 'invalid_email'];
		}

		$existing_user = get_user_by('email', $email);
		if ($existing_user) {
			return ['error' => 'email_exists'];
		}

		$referrals = self::get_user_referrals($referrer_id);
		foreach ($referrals as $ref) {
			if ($ref['email'] === $email) {
				return ['error' => 'already_sent'];
			}
		}

		$token = wp_generate_password(32, false);
		$username = sanitize_user(current(explode('@', $email)), true);

		$existing_login = get_user_by('login', $username);
		if ($existing_login) {
			$username = $username . '_' . wp_rand(100, 999);
		}

		$random_password = wp_generate_password(24, true, true);
		$new_user_id = wp_insert_user([
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $random_password,
			'role'       => 'subscriber',
		]);

		if (is_wp_error($new_user_id)) {
			return ['error' => 'user_creation_failed'];
		}

		update_user_meta($new_user_id, 'pn_cm_referred_by', $referrer_id);
		update_user_meta($new_user_id, 'pn_cm_referral_token', $token);

		$referral = [
			'id'           => uniqid('ref_', true),
			'email'        => $email,
			'user_id'      => $new_user_id,
			'token'        => $token,
			'status'       => 'pending',
			'created_at'   => current_time('mysql'),
			'completed_at' => null,
		];

		$referrals[] = $referral;
		update_user_meta($referrer_id, 'pn_cm_referrals', $referrals);

		$referral_link = home_url('?pn_cm_ref=' . $token);

		self::send_referral_email($referrer_id, $new_user_id, $email, $token);

		return [
			'referral_link' => $referral_link,
			'referral'      => $referral,
		];
	}

	/**
	 * Send the referral invitation email via mailpn.
	 *
	 * @param int    $referrer_id  The referrer user ID.
	 * @param int    $referred_id  The referred user ID (mailpn_user_to).
	 * @param string $email        The referred email address.
	 * @param string $token        The referral token.
	 */
	public static function send_referral_email($referrer_id, $referred_id, $email, $token) {
		$referrer = get_userdata($referrer_id);
		$referrer_name = $referrer ? $referrer->display_name : __('Someone', 'pn-customers-manager');
		$site_name = get_option('blogname');
		$referral_link = home_url('?pn_cm_ref=' . $token);

		$html_content = '<h2 style="color:#333;font-family:Arial,sans-serif;">' . esc_html(sprintf(
			/* translators: %s: referrer name */
			__('%s has invited you!', 'pn-customers-manager'),
			$referrer_name
		)) . '</h2>';
		$html_content .= '<p style="color:#555;font-family:Arial,sans-serif;font-size:16px;line-height:1.5;">' . esc_html(sprintf(
			/* translators: 1: referrer name, 2: site name */
			__('%1$s has invited you to join %2$s. Click the button below to accept the invitation and set up your account.', 'pn-customers-manager'),
			$referrer_name,
			$site_name
		)) . '</p>';
		$html_content .= '<p style="text-align:center;margin:30px 0;">';
		$html_content .= '<a href="' . esc_url($referral_link) . '" style="display:inline-block;padding:14px 32px;background-color:#0000aa;color:#ffffff;text-decoration:none;border-radius:6px;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;">';
		$html_content .= esc_html__('Accept Invitation', 'pn-customers-manager');
		$html_content .= '</a></p>';

		if (class_exists('MAILPN_Mailing')) {
			try {
				$mailing = new MAILPN_Mailing();
				$mailing->mailpn_sender([
					'mailpn_user_to' => $referred_id,
					'mailpn_type'    => 'pn_cm_referral_invitation',
					'mailpn_subject' => sprintf(
						/* translators: %s: referrer name */
						__('%s has invited you!', 'pn-customers-manager'),
						$referrer_name
					),
				], $html_content);
			} catch (Exception $e) {
				error_log('[PN_CM_Referral] MailPN error: ' . $e->getMessage());
			}
		}
	}

	/**
	 * Complete a referral by updating its status and incrementing the referrer's count.
	 *
	 * @param int    $referrer_id The referrer user ID.
	 * @param string $token       The referral token to match.
	 */
	public static function complete_referral($referrer_id, $token) {
		$referrals = self::get_user_referrals($referrer_id);
		$updated = false;

		foreach ($referrals as &$referral) {
			if ($referral['token'] === $token && $referral['status'] === 'pending') {
				$referral['status'] = 'completed';
				$referral['completed_at'] = current_time('mysql');
				$updated = true;
				break;
			}
		}
		unset($referral);

		if ($updated) {
			update_user_meta($referrer_id, 'pn_cm_referrals', $referrals);
			$current_count = (int) get_user_meta($referrer_id, 'pn_cm_referral_completed_count', true);
			update_user_meta($referrer_id, 'pn_cm_referral_completed_count', $current_count + 1);

			do_action( 'pn_cm_referral_completed', $referrer_id, $referral['user_id'], $referral );
		}
	}

	/**
	 * Get all referrals for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return array List of referral records.
	 */
	public static function get_user_referrals($user_id) {
		$referrals = get_user_meta($user_id, 'pn_cm_referrals', true);
		return is_array($referrals) ? $referrals : [];
	}

	/**
	 * Get the completed referral count for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return int Completed count.
	 */
	public static function get_completed_count($user_id) {
		return (int) get_user_meta($user_id, 'pn_cm_referral_completed_count', true);
	}

	/**
	 * Get the top referrers ordered by completed count.
	 *
	 * @param int $limit Number of top referrers to return.
	 * @return array List of ['user_id' => int, 'count' => int].
	 */
	public static function get_top_referrers($limit = 10) {
		global $wpdb;

		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT user_id, meta_value AS count
			 FROM {$wpdb->usermeta}
			 WHERE meta_key = 'pn_cm_referral_completed_count'
			   AND meta_value > 0
			 ORDER BY CAST(meta_value AS UNSIGNED) DESC
			 LIMIT %d",
			$limit
		), ARRAY_A);

		if (empty($results)) {
			return [];
		}

		return array_map(function($row) {
			return [
				'user_id' => (int) $row['user_id'],
				'count'   => (int) $row['count'],
			];
		}, $results);
	}
}
