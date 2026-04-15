<?php
/**
 * Email campaigns management system.
 *
 * Handles email campaign configuration, sending via mailpn integration,
 * tracking opens/clicks, and frontend panel rendering.
 *
 * @link       padresenlanube.com/
 * @since      1.0.19
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Email_Campaigns {

	/**
	 * Check if the mailpn plugin is active.
	 *
	 * @return bool
	 */
	public static function is_mailpn_active() {
		if (!function_exists('is_plugin_active')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active('mailpn/mailpn.php');
	}

	/**
	 * Get available mail templates from mailpn.
	 *
	 * @return array Array of [ID => title].
	 */
	public static function get_mail_templates() {
		if (!self::is_mailpn_active()) {
			return [];
		}

		$templates = [];
		$posts = get_posts([
			'post_type'   => 'mailpn_mail',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
		]);

		foreach ($posts as $post) {
			$templates[$post->ID] = $post->post_title;
		}

		return $templates;
	}

	/**
	 * Get all configured campaigns.
	 *
	 * @return array Array of campaigns with title and template.
	 */
	public static function get_campaigns() {
		$titles = get_option('pn_customers_manager_email_campaigns_title', []);
		$template_ids = get_option('pn_customers_manager_email_campaigns_template', []);

		if (empty($titles)) {
			return [];
		}

		// Ensure arrays
		if (!is_array($titles)) {
			$titles = [$titles];
		}
		if (!is_array($template_ids)) {
			$template_ids = [$template_ids];
		}

		$campaigns = [];
		foreach ($titles as $index => $title) {
			if (empty($title)) {
				continue;
			}
			$campaigns[] = [
				'index'       => $index,
				'title'       => $title,
				'template_id' => isset($template_ids[$index]) ? absint($template_ids[$index]) : 0,
			];
		}

		return $campaigns;
	}

	/**
	 * Get a campaign by its title.
	 *
	 * @param string $title The campaign title.
	 * @return array|false Campaign data or false.
	 */
	public static function get_campaign_by_title($title) {
		$campaigns = self::get_campaigns();

		foreach ($campaigns as $campaign) {
			if ($campaign['title'] === $title) {
				return $campaign;
			}
		}

		return false;
	}

	/**
	 * Get the settings section options array for the email campaigns section.
	 *
	 * @return array
	 */
	public static function get_settings_section() {
		$options = [];

		$options['pn_customers_manager_email_campaigns_section_start'] = [
			'id'          => 'pn_customers_manager_email_campaigns_section_start',
			'section'     => 'start',
			'label'       => __('Email Campaigns', 'pn-customers-manager'),
			'description' => __('Email campaign settings with mailpn integration.', 'pn-customers-manager'),
		];

		if (!self::is_mailpn_active()) {
			$install_url = wp_nonce_url(
				admin_url('update.php?action=install-plugin&plugin=mailpn'),
				'install-plugin_mailpn'
			);

			$options['pn_customers_manager_email_campaigns_install'] = [
				'id'           => 'pn_customers_manager_email_campaigns_install',
				'input'        => 'html',
				'html_content' => '<p>' . esc_html__('The mailpn plugin is required for email campaigns.', 'pn-customers-manager') . '</p>'
					. '<a href="' . esc_url($install_url) . '" class="pn-customers-manager-btn">'
					. esc_html__('Install mailpn', 'pn-customers-manager')
					. '</a>',
			];
		} else {
			$templates = self::get_mail_templates();
			$template_options = ['' => __('-- Select template --', 'pn-customers-manager')];
			foreach ($templates as $id => $title) {
				$template_options[$id] = $title;
			}

			$options['pn_customers_manager_email_campaigns'] = [
				'id'    => 'pn_customers_manager_email_campaigns',
				'input' => 'html_multi',
				'label' => __('Campaigns', 'pn-customers-manager'),
				'html_multi_fields' => [
					[
						'id'          => 'pn_customers_manager_email_campaigns_title',
						'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
						'input'       => 'input',
						'type'        => 'text',
						'label'       => __('Campaign title', 'pn-customers-manager'),
						'placeholder' => __('Campaign title', 'pn-customers-manager'),
					],
					[
						'id'      => 'pn_customers_manager_email_campaigns_template',
						'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
						'input'   => 'select',
						'options' => $template_options,
						'label'   => __('Email template', 'pn-customers-manager'),
					],
				],
			];
		}

		$options['pn_customers_manager_email_campaigns_section_end'] = [
			'id'      => 'pn_customers_manager_email_campaigns_section_end',
			'section' => 'end',
		];

		return $options;
	}

	/**
	 * Render the email campaigns shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_shortcode($atts = []) {
		$atts = shortcode_atts([
			'campaign' => '',
		], $atts, 'pn-customers-manager-email-campaigns');

		if (!is_user_logged_in()) {
			return '';
		}

		if (!current_user_can('manage_options') && !current_user_can('edit_pn_cm_funnel') && !current_user_can('edit_pn_cm_organization')) {
			return '';
		}

		ob_start();

		if (!self::is_mailpn_active()) {
			echo '<p>' . esc_html__('The mailpn plugin is not active. Contact the administrator.', 'pn-customers-manager') . '</p>';
			return ob_get_clean();
		}

		$campaign_title = sanitize_text_field($atts['campaign']);

		if (empty($campaign_title)) {
			$all_campaigns = self::get_campaigns();
			if (empty($all_campaigns)) {
				return ob_get_clean();
			}
		} else {
			$campaign = self::get_campaign_by_title($campaign_title);
			if (!$campaign) {
				return ob_get_clean();
			}
		}

		echo '<div class="pn-cm-email-campaigns-wrapper">';
		echo '<h2 class="pn-cm-email-campaigns-title">' . esc_html__('Email Campaigns', 'pn-customers-manager') . '</h2>';
		echo '<p class="pn-cm-email-campaigns-description">'
			. esc_html__('Send email campaigns to registered users or external addresses. Track opens and clicks from sent emails.', 'pn-customers-manager')
			. '</p>';

		if (empty($campaign_title)) {
			foreach ($all_campaigns as $campaign) {
				self::render_single_campaign($campaign);
			}
		} else {
			self::render_single_campaign($campaign);
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Render a single campaign panel.
	 *
	 * @param array $campaign Campaign data with title and template_id.
	 */
	private static function render_single_campaign($campaign) {
		$campaign_title = $campaign['title'];
		$mail_id = $campaign['template_id'];
		$records = self::get_campaign_records($mail_id);
		$total_sent = count($records);
		$total_opened = self::count_opens($records);
		$total_clicks = self::count_total_clicks($mail_id);

		$queue = get_option('mailpn_queue', []);
		$queued_count = 0;
		if (is_array($queue) && isset($queue[$mail_id]) && is_array($queue[$mail_id])) {
			$queued_count = count($queue[$mail_id]);
		}

		// Get all users for the selector
		$all_users = get_users([
			'fields'  => ['ID', 'display_name', 'user_email'],
			'orderby' => 'display_name',
			'order'   => 'ASC',
		]);

		$user_options = [];
		foreach ($all_users as $user) {
			$user_options[$user->ID] = $user->display_name . ' (' . $user->user_email . ')';
		}

		$unique_suffix = '_' . $campaign['index'];

		$users_field = [
			'id'       => 'pn_cm_email_campaigns_users' . $unique_suffix,
			'input'    => 'select',
			'class'    => 'pn-cm-email-campaigns-users-select pn-customers-manager-select pn-customers-manager-width-100-percent',
			'multiple' => true,
			'label'    => __('Select users', 'pn-customers-manager'),
			'options'  => $user_options,
		];

		$emails_field = [
			'id'    => 'pn_cm_email_campaigns_external_emails_wrapper' . $unique_suffix,
			'input' => 'html_multi',
			'label' => __('External emails', 'pn-customers-manager'),
			'html_multi_fields' => [
				[
					'id'          => 'pn_cm_email_campaigns_external_emails' . $unique_suffix,
					'input'       => 'input',
					'type'        => 'email',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'placeholder' => 'email@example.com',
				],
			],
		];

		?>
		<div class="pn-cm-email-campaigns-panel pn-customers-manager-toggle-wrapper" data-mail-id="<?php echo esc_attr($mail_id); ?>">
			<a href="#" class="pn-customers-manager-toggle pn-customers-manager-text-decoration-none">
				<div class="pn-cm-email-campaigns-header">
					<h2 class="pn-customers-manager-width-100-percent"><?php echo esc_html($campaign_title); ?> <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-float-right">add</i></h2>
				</div>
			</a>

			<div class="pn-customers-manager-toggle-content pn-customers-manager-display-none-soft">

			<div class="pn-cm-email-campaigns-stats">
				<div class="pn-cm-email-campaigns-stat-card">
					<span class="pn-cm-email-campaigns-stat-number" data-stat="sent"><?php echo esc_html($total_sent); ?></span>
					<span class="pn-cm-email-campaigns-stat-label"><?php esc_html_e('Sent', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-email-campaigns-stat-card">
					<span class="pn-cm-email-campaigns-stat-number" data-stat="opened"><?php echo esc_html($total_opened); ?></span>
					<span class="pn-cm-email-campaigns-stat-label"><?php esc_html_e('Opened', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-email-campaigns-stat-card">
					<span class="pn-cm-email-campaigns-stat-number" data-stat="clicks"><?php echo esc_html($total_clicks); ?></span>
					<span class="pn-cm-email-campaigns-stat-label"><?php esc_html_e('Clicks', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-email-campaigns-stat-card">
					<span class="pn-cm-email-campaigns-stat-number" data-stat="queued"><?php echo esc_html($queued_count); ?></span>
					<span class="pn-cm-email-campaigns-stat-label"><?php esc_html_e('Queued', 'pn-customers-manager'); ?></span>
				</div>
			</div>

			<?php
			$mail_post = get_post($mail_id);
			if ($mail_post && !empty($mail_post->post_content)) :
			?>
			<div class="pn-cm-email-campaigns-preview-section">
				<a href="#" class="pn-cm-email-campaigns-preview-toggle">
					<span class="material-icons-outlined pn-cm-email-campaigns-preview-icon">expand_more</span>
					<span><?php esc_html_e('Email content', 'pn-customers-manager'); ?></span>
				</a>
				<div class="pn-cm-email-campaigns-preview-body" style="display:none;">
					<div class="pn-cm-email-campaigns-preview-actions">
						<button type="button" class="pn-cm-email-campaigns-btn pn-cm-email-campaigns-btn-copy">
							<span class="material-icons-outlined">content_copy</span>
							<?php esc_html_e('Copy content', 'pn-customers-manager'); ?>
						</button>
					</div>
					<div class="pn-cm-email-campaigns-preview-frame">
						<?php echo wp_kses_post($mail_post->post_content); ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<div class="pn-cm-email-campaigns-send-section">
				<h3><?php esc_html_e('Send campaign', 'pn-customers-manager'); ?></h3>
				<div class="pn-cm-email-campaigns-send-form">
					<?php PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($users_field, 'option', 0, 0, 'full'); ?>
					<?php PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($emails_field, 'option', 0, 0, 'full'); ?>

					<button type="button" class="pn-cm-email-campaigns-btn pn-cm-email-campaigns-btn-primary pn-cm-email-campaigns-send-btn">
						<span class="material-icons-outlined">send</span>
						<?php esc_html_e('Send', 'pn-customers-manager'); ?>
					</button>
				</div>

				<div class="pn-cm-email-campaigns-progress" style="display:none;">
					<div class="pn-cm-email-campaigns-progress-bar">
						<div class="pn-cm-email-campaigns-progress-fill" style="width:0%;"></div>
					</div>
					<span class="pn-cm-email-campaigns-progress-text">0%</span>
				</div>

				<div class="pn-cm-email-campaigns-message" style="display:none;"></div>
			</div>

			<div class="pn-cm-email-campaigns-records-section">
				<h3><?php esc_html_e('Sent emails', 'pn-customers-manager'); ?></h3>
				<div class="pn-cm-email-campaigns-records-wrapper">
					<?php echo self::render_records_table($records, $mail_id); ?>
				</div>
			</div>

			</div><!-- /.pn-customers-manager-toggle-content -->
		</div>
		<?php
	}

	/**
	 * Register the Gutenberg block.
	 */
	public static function register_block() {
		if (!function_exists('register_block_type')) {
			return;
		}

		$campaigns = self::get_campaigns();
		$campaign_options = [];
		foreach ($campaigns as $campaign) {
			$campaign_options[] = [
				'label' => $campaign['title'],
				'value' => $campaign['title'],
			];
		}

		wp_register_script(
			'pn-customers-manager-email-campaigns-block',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-email-campaigns.js',
			['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.19',
			true
		);

		wp_localize_script('pn-customers-manager-email-campaigns-block', 'pnCMEmailCampaigns', [
			'campaigns' => $campaign_options,
		]);

		register_block_type('pn-customers-manager/email-campaigns', [
			'editor_script'   => 'pn-customers-manager-email-campaigns-block',
			'render_callback' => [__CLASS__, 'render_shortcode'],
			'attributes'      => [
				'campaign' => [
					'type'    => 'string',
					'default' => '',
				],
			],
		]);
	}

	/**
	 * Get campaign records (mailpn_rec posts for a given mail_id).
	 *
	 * @param int $mail_id The mail template ID.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_campaign_records($mail_id) {
		if (empty($mail_id)) {
			return [];
		}

		$records = get_posts([
			'post_type'   => 'mailpn_rec',
			'post_status' => 'any',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => 'mailpn_rec_mail_id',
					'value' => $mail_id,
				],
			],
		]);

		return $records;
	}

	/**
	 * Count records that have been opened.
	 *
	 * @param array $records Array of WP_Post objects.
	 * @return int Number of opened records.
	 */
	public static function count_opens($records) {
		$count = 0;
		foreach ($records as $record) {
			$opened = get_post_meta($record->ID, 'mailpn_rec_opened', true);
			if ($opened) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Count total clicks for a mail template.
	 *
	 * @param int $mail_id The mail template ID.
	 * @return int Total clicks.
	 */
	public static function count_total_clicks($mail_id) {
		if (class_exists('MAILPN_Click_Tracking')) {
			$stats = MAILPN_Click_Tracking::get_click_stats($mail_id);
			if (isset($stats['total_clicks'])) {
				return (int) $stats['total_clicks'];
			}
		}

		return 0;
	}

	/**
	 * Render the records table.
	 *
	 * @param array $records Array of WP_Post objects.
	 * @param int   $mail_id The mail template ID.
	 * @return string HTML table.
	 */
	public static function render_records_table($records, $mail_id) {
		if (empty($records)) {
			return '<p class="pn-cm-email-campaigns-no-records">' . esc_html__('No emails sent yet.', 'pn-customers-manager') . '</p>';
		}

		$click_stats_by_record = [];
		if (class_exists('MAILPN_Click_Tracking')) {
			$stats = MAILPN_Click_Tracking::get_click_stats($mail_id);
			if (isset($stats['by_record']) && is_array($stats['by_record'])) {
				$click_stats_by_record = $stats['by_record'];
			}
		}

		ob_start();
		?>
		<table class="pn-cm-email-campaigns-table">
			<thead>
				<tr>
					<th><?php esc_html_e('User', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Email', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Opened', 'pn-customers-manager'); ?></th>
					<th><?php esc_html_e('Clicks', 'pn-customers-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($records as $record) :
					$to_value = get_post_meta($record->ID, 'mailpn_rec_to', true);
					$to_email_meta = get_post_meta($record->ID, 'mailpn_rec_to_email', true);
					$opened = get_post_meta($record->ID, 'mailpn_rec_opened', true);
					$record_clicks = isset($click_stats_by_record[$record->ID]) ? (int) $click_stats_by_record[$record->ID] : 0;

					if (is_numeric($to_value)) {
						$user = get_userdata(intval($to_value));
						$user_name = $user ? $user->display_name : '—';
						$to_email = $user ? $user->user_email : $to_email_meta;
					} else {
						$to_email = !empty($to_email_meta) ? $to_email_meta : $to_value;
						$user = $to_email ? get_user_by('email', $to_email) : false;
						$user_name = $user ? $user->display_name : '—';
					}
				?>
				<tr>
					<td><?php echo esc_html($user_name); ?></td>
					<td><?php echo esc_html($to_email); ?></td>
					<td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($record->post_date))); ?></td>
					<td>
						<?php if ($opened) : ?>
							<span class="pn-cm-email-campaigns-badge pn-cm-email-campaigns-badge-yes"><?php esc_html_e('Yes', 'pn-customers-manager'); ?></span>
						<?php else : ?>
							<span class="pn-cm-email-campaigns-badge pn-cm-email-campaigns-badge-no"><?php esc_html_e('No', 'pn-customers-manager'); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html($record_clicks); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}

	/**
	 * Handle sending a campaign via AJAX.
	 *
	 * @return array Response with error_key.
	 */
	public static function handle_send_campaign() {
		if (!self::is_mailpn_active()) {
			return [
				'error_key'     => 'mailpn_not_active',
				'error_content' => esc_html__('The mailpn plugin is not active.', 'pn-customers-manager'),
			];
		}

		$mail_id = isset($_POST['mail_id']) ? absint($_POST['mail_id']) : 0;
		$user_ids = isset($_POST['user_ids']) && is_array($_POST['user_ids']) ? array_map('absint', $_POST['user_ids']) : [];
		$external_emails = [];
		if (!empty($_POST['emails'])) {
			$raw_emails = sanitize_textarea_field(wp_unslash($_POST['emails']));
			$external_emails = array_filter(array_map('trim', explode("\n", $raw_emails)), function ($email) {
				return is_email($email);
			});
		}

		if (empty($mail_id)) {
			return [
				'error_key'     => 'missing_mail_id',
				'error_content' => esc_html__('No email template specified.', 'pn-customers-manager'),
			];
		}

		if (empty($user_ids) && empty($external_emails)) {
			return [
				'error_key'     => 'no_recipients',
				'error_content' => esc_html__('Select at least one user or enter an email.', 'pn-customers-manager'),
			];
		}

		$mail_post = get_post($mail_id);
		if (!$mail_post || $mail_post->post_type !== 'mailpn_mail') {
			return [
				'error_key'     => 'invalid_mail',
				'error_content' => esc_html__('The email template is not valid.', 'pn-customers-manager'),
			];
		}

		$queued_count = 0;

		// Send to registered users
		if (!empty($user_ids)) {
			if (class_exists('MAILPN_Mailing')) {
				foreach ($user_ids as $user_id) {
					$user = get_userdata($user_id);
					if ($user) {
						(new MAILPN_Mailing())->mailpn_queue_add($mail_id, $user_id);
						$queued_count++;
					}
				}
			} else {
				$queue = get_option('mailpn_queue', []);
				if (!is_array($queue)) {
					$queue = [];
				}
				if (!isset($queue[$mail_id]) || !is_array($queue[$mail_id])) {
					$queue[$mail_id] = [];
				}
				foreach ($user_ids as $user_id) {
					if (!in_array($user_id, $queue[$mail_id])) {
						$queue[$mail_id][] = $user_id;
						$queued_count++;
					}
				}
				update_option('mailpn_queue', $queue);
			}
		}

		// Send to external emails via mailpn queue
		if (!empty($external_emails)) {
			if (class_exists('MAILPN_Mailing')) {
				$mailing = new MAILPN_Mailing();
				foreach ($external_emails as $email) {
					// If email belongs to an existing user, queue by user ID; otherwise queue the email directly
					$existing_user = get_user_by('email', $email);
					$mailing->mailpn_queue_add($mail_id, $existing_user ? $existing_user->ID : $email);
					$queued_count++;
				}
			} else {
				$queue = get_option('mailpn_queue', []);
				if (!is_array($queue)) {
					$queue = [];
				}
				if (!isset($queue[$mail_id]) || !is_array($queue[$mail_id])) {
					$queue[$mail_id] = [];
				}
				foreach ($external_emails as $email) {
					$existing_user = get_user_by('email', $email);
					$recipient = $existing_user ? $existing_user->ID : $email;
					if (!in_array($recipient, $queue[$mail_id])) {
						$queue[$mail_id][] = $recipient;
						$queued_count++;
					}
				}
				update_option('mailpn_queue', $queue);
			}
		}

		return [
			'error_key'   => '',
			'queued'      => $queued_count,
		];
	}

	/**
	 * Handle progress check via AJAX.
	 *
	 * @return array Response with progress data.
	 */
	public static function handle_get_progress() {
		$mail_id = isset($_POST['mail_id']) ? absint($_POST['mail_id']) : 0;

		if (empty($mail_id)) {
			return [
				'error_key'     => 'missing_mail_id',
				'error_content' => esc_html__('No email template specified.', 'pn-customers-manager'),
			];
		}

		$queue = get_option('mailpn_queue', []);
		$queued_count = 0;
		if (is_array($queue) && isset($queue[$mail_id]) && is_array($queue[$mail_id])) {
			$queued_count = count($queue[$mail_id]);
		}

		$records = self::get_campaign_records($mail_id);
		$total_sent = count($records);
		$total_opened = self::count_opens($records);
		$total_clicks = self::count_total_clicks($mail_id);

		$total = $total_sent + $queued_count;
		$percentage = $total > 0 ? round(($total_sent / $total) * 100) : 100;

		return [
			'error_key'   => '',
			'sent'        => $total_sent,
			'opened'      => $total_opened,
			'clicks'      => $total_clicks,
			'queued'      => $queued_count,
			'percentage'  => $percentage,
			'table_html'  => self::render_records_table($records, $mail_id),
		];
	}

	/**
	 * Handle table/stats refresh via AJAX.
	 *
	 * @return array Response with refreshed data.
	 */
	public static function handle_refresh() {
		return self::handle_get_progress();
	}
}
