<?php
/**
 * Commercial agents management system.
 *
 * Handles commercial agent applications, approval workflow, dashboard rendering,
 * and admin management page.
 *
 * @link       padresenlanube.com/
 * @since      1.0.9
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Commercial {

	/**
	 * Render the commercial panel shortcode.
	 *
	 * Shows different content based on user state:
	 * - Not logged in: CTA to login via userspn popup
	 * - Logged in, no application: Application form
	 * - Pending: Status message
	 * - Rejected: Rejection message
	 * - Approved: Commercial dashboard
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_commercial_panel($atts = []) {
		ob_start();

		if (!is_user_logged_in()) {
			echo do_shortcode('[pn-customers-manager-call-to-action'
				. ' pn_customers_manager_call_to_action_icon="storefront"'
				. ' pn_customers_manager_call_to_action_title="' . esc_attr__('Programa de Agentes Comerciales', 'pn-customers-manager') . '"'
				. ' pn_customers_manager_call_to_action_content="' . esc_attr__('Inicia sesion para acceder al panel de agentes comerciales o enviar tu solicitud.', 'pn-customers-manager') . '"'
				. ' pn_customers_manager_call_to_action_button_link="#"'
				. ' pn_customers_manager_call_to_action_button_text="' . esc_attr__('Iniciar sesion', 'pn-customers-manager') . '"'
				. ' pn_customers_manager_call_to_action_button_class="userspn-profile-popup-btn"'
				. ' pn_customers_manager_call_to_action_button_data_key="data-userspn-action"'
				. ' pn_customers_manager_call_to_action_button_data_value="login"'
				. ']');
			return ob_get_clean();
		}

		$user_id = get_current_user_id();
		$status = get_user_meta($user_id, 'pn_cm_commercial_status', true);
		$user = wp_get_current_user();

		if (empty($status)) {
			// Show application form
			?>
			<div class="pn-cm-commercial-panel pn-cm-commercial-apply">
				<h2><?php esc_html_e('Solicitud de Agente Comercial', 'pn-customers-manager'); ?></h2>
				<p class="pn-cm-commercial-apply-description"><?php esc_html_e('Completa el siguiente formulario para solicitar acceso como agente comercial. Revisaremos tu solicitud y te notificaremos.', 'pn-customers-manager'); ?></p>

				<form class="pn-cm-commercial-apply-form" id="pn-cm-commercial-apply-form">
					<div class="pn-cm-commercial-form-row">
						<div class="pn-cm-commercial-form-field">
							<label for="pn_cm_commercial_first_name"><?php esc_html_e('Nombre', 'pn-customers-manager'); ?> <span class="pn-cm-commercial-required">*</span></label>
							<input type="text" id="pn_cm_commercial_first_name" name="pn_cm_commercial_first_name" value="<?php echo esc_attr($user->first_name); ?>" required />
						</div>
						<div class="pn-cm-commercial-form-field">
							<label for="pn_cm_commercial_last_name"><?php esc_html_e('Apellidos', 'pn-customers-manager'); ?> <span class="pn-cm-commercial-required">*</span></label>
							<input type="text" id="pn_cm_commercial_last_name" name="pn_cm_commercial_last_name" value="<?php echo esc_attr($user->last_name); ?>" required />
						</div>
					</div>

					<div class="pn-cm-commercial-form-row">
						<div class="pn-cm-commercial-form-field">
							<label for="pn_cm_commercial_email"><?php esc_html_e('Email', 'pn-customers-manager'); ?> <span class="pn-cm-commercial-required">*</span></label>
							<input type="email" id="pn_cm_commercial_email" name="pn_cm_commercial_email" value="<?php echo esc_attr($user->user_email); ?>" required />
						</div>
						<div class="pn-cm-commercial-form-field">
							<label for="pn_cm_commercial_phone"><?php esc_html_e('Telefono', 'pn-customers-manager'); ?> <span class="pn-cm-commercial-required">*</span></label>
							<input type="tel" id="pn_cm_commercial_phone" name="pn_cm_commercial_phone" required />
						</div>
					</div>

					<div class="pn-cm-commercial-form-row">
						<div class="pn-cm-commercial-form-field pn-cm-commercial-form-field-full">
							<label for="pn_cm_commercial_company"><?php esc_html_e('Empresa', 'pn-customers-manager'); ?></label>
							<input type="text" id="pn_cm_commercial_company" name="pn_cm_commercial_company" />
						</div>
					</div>

					<div class="pn-cm-commercial-form-row">
						<div class="pn-cm-commercial-form-field pn-cm-commercial-form-field-full">
							<label for="pn_cm_commercial_message"><?php esc_html_e('Mensaje / Motivacion', 'pn-customers-manager'); ?></label>
							<textarea id="pn_cm_commercial_message" name="pn_cm_commercial_message" rows="4"></textarea>
						</div>
					</div>

					<div class="pn-cm-commercial-form-actions">
						<button type="submit" class="pn-cm-commercial-btn pn-cm-commercial-btn-primary">
							<?php esc_html_e('Enviar solicitud', 'pn-customers-manager'); ?>
						</button>
					</div>

					<div class="pn-cm-commercial-message" style="display:none;"></div>
				</form>
			</div>
			<?php
		} elseif ($status === 'pending') {
			?>
			<div class="pn-cm-commercial-panel pn-cm-commercial-status">
				<div class="pn-cm-commercial-status-box pn-cm-commercial-status-pending">
					<span class="material-icons-outlined pn-cm-commercial-status-icon">hourglass_top</span>
					<h2><?php esc_html_e('Solicitud en revision', 'pn-customers-manager'); ?></h2>
					<p><?php esc_html_e('Tu solicitud como agente comercial esta siendo revisada. Te notificaremos cuando haya una actualizacion.', 'pn-customers-manager'); ?></p>
				</div>
			</div>
			<?php
		} elseif ($status === 'rejected') {
			?>
			<div class="pn-cm-commercial-panel pn-cm-commercial-status">
				<div class="pn-cm-commercial-status-box pn-cm-commercial-status-rejected">
					<span class="material-icons-outlined pn-cm-commercial-status-icon">cancel</span>
					<h2><?php esc_html_e('Solicitud rechazada', 'pn-customers-manager'); ?></h2>
					<p><?php esc_html_e('Tu solicitud como agente comercial ha sido rechazada. Si crees que es un error, contacta con soporte.', 'pn-customers-manager'); ?></p>
				</div>
			</div>
			<?php
		} elseif ($status === 'approved') {
			echo self::render_commercial_dashboard();
		}

		return ob_get_clean();
	}

	/**
	 * Render the approved commercial agent dashboard.
	 *
	 * Shows stats cards, CRM link, and embedded referrals panel.
	 *
	 * @return string HTML output.
	 */
	public static function render_commercial_dashboard() {
		$user_id = get_current_user_id();
		$referrals = PN_CUSTOMERS_MANAGER_Referral::get_user_referrals($user_id);
		$completed_count = PN_CUSTOMERS_MANAGER_Referral::get_completed_count($user_id);
		$total_count = count($referrals);
		$pending_count = $total_count - $completed_count;
		$conversion_rate = $total_count > 0 ? round(($completed_count / $total_count) * 100, 1) : 0;

		$crm_page_id = get_option('pn_customers_manager_commercial_crm_page', '');
		$crm_url = !empty($crm_page_id) ? get_permalink($crm_page_id) : '';

		ob_start();
		?>
		<div class="pn-cm-commercial-panel pn-cm-commercial-dashboard">
			<h2><?php esc_html_e('Panel Comercial', 'pn-customers-manager'); ?></h2>

			<div class="pn-cm-commercial-stats">
				<div class="pn-cm-commercial-stat-card">
					<span class="pn-cm-commercial-stat-number"><?php echo esc_html($total_count); ?></span>
					<span class="pn-cm-commercial-stat-label"><?php esc_html_e('Referidos enviados', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-commercial-stat-card">
					<span class="pn-cm-commercial-stat-number"><?php echo esc_html($completed_count); ?></span>
					<span class="pn-cm-commercial-stat-label"><?php esc_html_e('Completados', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-commercial-stat-card">
					<span class="pn-cm-commercial-stat-number"><?php echo esc_html($pending_count); ?></span>
					<span class="pn-cm-commercial-stat-label"><?php esc_html_e('Pendientes', 'pn-customers-manager'); ?></span>
				</div>
				<div class="pn-cm-commercial-stat-card">
					<span class="pn-cm-commercial-stat-number"><?php echo esc_html($conversion_rate); ?>%</span>
					<span class="pn-cm-commercial-stat-label"><?php esc_html_e('Tasa de conversion', 'pn-customers-manager'); ?></span>
				</div>
			</div>

			<?php if (!empty($crm_url)) : ?>
			<div class="pn-cm-commercial-crm-link">
				<a href="<?php echo esc_url($crm_url); ?>" class="pn-cm-commercial-btn pn-cm-commercial-btn-primary">
					<span class="material-icons-outlined">dashboard</span>
					<?php esc_html_e('Acceder al CRM', 'pn-customers-manager'); ?>
				</a>
			</div>
			<?php endif; ?>

			<div class="pn-cm-commercial-referrals-section">
				<h3><?php esc_html_e('Mis Referidos', 'pn-customers-manager'); ?></h3>
				<?php echo PN_CUSTOMERS_MANAGER_Referral::render_referrals_shortcode(); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle commercial application via AJAX.
	 *
	 * Validates required fields and stores user meta.
	 *
	 * @return array Response with error_key.
	 */
	public static function handle_commercial_application() {
		if (!is_user_logged_in()) {
			return [
				'error_key' => 'not_logged_in',
				'error_content' => esc_html__('Debes iniciar sesion para enviar tu solicitud.', 'pn-customers-manager'),
			];
		}

		$user_id = get_current_user_id();

		// Check if already applied
		$existing_status = get_user_meta($user_id, 'pn_cm_commercial_status', true);
		if (!empty($existing_status)) {
			return [
				'error_key' => 'already_applied',
				'error_content' => esc_html__('Ya has enviado una solicitud anteriormente.', 'pn-customers-manager'),
			];
		}

		$first_name = !empty($_POST['pn_cm_commercial_first_name']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_commercial_first_name'])) : '';
		$last_name = !empty($_POST['pn_cm_commercial_last_name']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_commercial_last_name'])) : '';
		$email = !empty($_POST['pn_cm_commercial_email']) ? sanitize_email(wp_unslash($_POST['pn_cm_commercial_email'])) : '';
		$phone = !empty($_POST['pn_cm_commercial_phone']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_commercial_phone'])) : '';
		$company = !empty($_POST['pn_cm_commercial_company']) ? sanitize_text_field(wp_unslash($_POST['pn_cm_commercial_company'])) : '';
		$message = !empty($_POST['pn_cm_commercial_message']) ? sanitize_textarea_field(wp_unslash($_POST['pn_cm_commercial_message'])) : '';

		// Validate required fields
		if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
			return [
				'error_key' => 'missing_fields',
				'error_content' => esc_html__('Por favor, completa todos los campos obligatorios.', 'pn-customers-manager'),
			];
		}

		if (!is_email($email)) {
			return [
				'error_key' => 'invalid_email',
				'error_content' => esc_html__('Por favor, introduce un email valido.', 'pn-customers-manager'),
			];
		}

		// Update WP profile
		wp_update_user([
			'ID' => $user_id,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'user_email' => $email,
		]);

		// Store commercial-specific meta
		update_user_meta($user_id, 'pn_cm_commercial_status', 'pending');
		update_user_meta($user_id, 'pn_cm_commercial_applied_at', current_time('mysql'));
		update_user_meta($user_id, 'pn_cm_commercial_phone', $phone);
		update_user_meta($user_id, 'pn_cm_commercial_company', $company);
		update_user_meta($user_id, 'pn_cm_commercial_message', $message);

		return [
			'error_key' => '',
		];
	}

	/**
	 * Approve a commercial agent.
	 *
	 * Sets status to approved, assigns commercial role, records timestamp.
	 *
	 * @param int $user_id The user ID to approve.
	 * @return array Response with error_key.
	 */
	public static function approve_commercial($user_id) {
		$user_id = absint($user_id);

		if (empty($user_id)) {
			return [
				'error_key' => 'invalid_user',
				'error_content' => esc_html__('Usuario no valido.', 'pn-customers-manager'),
			];
		}

		$status = get_user_meta($user_id, 'pn_cm_commercial_status', true);
		if ($status !== 'pending') {
			return [
				'error_key' => 'invalid_status',
				'error_content' => esc_html__('Esta solicitud no esta pendiente.', 'pn-customers-manager'),
			];
		}

		update_user_meta($user_id, 'pn_cm_commercial_status', 'approved');
		update_user_meta($user_id, 'pn_cm_commercial_approved_at', current_time('mysql'));

		$user = new WP_User($user_id);
		$user->add_role('pn_customers_manager_role_commercial');

		return ['error_key' => ''];
	}

	/**
	 * Reject a commercial agent.
	 *
	 * Sets status to rejected.
	 *
	 * @param int $user_id The user ID to reject.
	 * @return array Response with error_key.
	 */
	public static function reject_commercial($user_id) {
		$user_id = absint($user_id);

		if (empty($user_id)) {
			return [
				'error_key' => 'invalid_user',
				'error_content' => esc_html__('Usuario no valido.', 'pn-customers-manager'),
			];
		}

		$status = get_user_meta($user_id, 'pn_cm_commercial_status', true);
		if ($status !== 'pending') {
			return [
				'error_key' => 'invalid_status',
				'error_content' => esc_html__('Esta solicitud no esta pendiente.', 'pn-customers-manager'),
			];
		}

		update_user_meta($user_id, 'pn_cm_commercial_status', 'rejected');

		return ['error_key' => ''];
	}

	/**
	 * Get the count of pending commercial applications.
	 *
	 * @return int Pending count.
	 */
	public static function get_pending_count() {
		$users = get_users([
			'meta_key'   => 'pn_cm_commercial_status',
			'meta_value' => 'pending',
			'fields'     => 'ID',
		]);

		return count($users);
	}

	/**
	 * Render the admin page for managing commercial agents.
	 */
	public static function render_admin_commercial_agents() {
		$filter = !empty($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';

		$meta_query = [];
		if ($filter !== 'all') {
			$meta_query[] = [
				'key'   => 'pn_cm_commercial_status',
				'value' => $filter,
			];
		} else {
			$meta_query[] = [
				'key'     => 'pn_cm_commercial_status',
				'compare' => 'EXISTS',
			];
		}

		$agents = get_users([
			'meta_query' => $meta_query,
			'orderby'    => 'meta_value',
			'order'      => 'DESC',
		]);

		// Count by status
		$count_all = count(get_users([
			'meta_query' => [['key' => 'pn_cm_commercial_status', 'compare' => 'EXISTS']],
			'fields'     => 'ID',
		]));
		$count_pending = count(get_users([
			'meta_query' => [['key' => 'pn_cm_commercial_status', 'value' => 'pending']],
			'fields'     => 'ID',
		]));
		$count_approved = count(get_users([
			'meta_query' => [['key' => 'pn_cm_commercial_status', 'value' => 'approved']],
			'fields'     => 'ID',
		]));
		$count_rejected = count(get_users([
			'meta_query' => [['key' => 'pn_cm_commercial_status', 'value' => 'rejected']],
			'fields'     => 'ID',
		]));

		$base_url = admin_url('admin.php?page=pn_customers_manager_commercial_agents');
		?>
		<div class="wrap pn-cm-commercial-admin">
			<h1><?php esc_html_e('Agentes Comerciales', 'pn-customers-manager'); ?></h1>

			<ul class="subsubsub">
				<li><a href="<?php echo esc_url($base_url); ?>" class="<?php echo $filter === 'all' ? 'current' : ''; ?>"><?php esc_html_e('Todos', 'pn-customers-manager'); ?> <span class="count">(<?php echo esc_html($count_all); ?>)</span></a> |</li>
				<li><a href="<?php echo esc_url(add_query_arg('status', 'pending', $base_url)); ?>" class="<?php echo $filter === 'pending' ? 'current' : ''; ?>"><?php esc_html_e('Pendientes', 'pn-customers-manager'); ?> <span class="count">(<?php echo esc_html($count_pending); ?>)</span></a> |</li>
				<li><a href="<?php echo esc_url(add_query_arg('status', 'approved', $base_url)); ?>" class="<?php echo $filter === 'approved' ? 'current' : ''; ?>"><?php esc_html_e('Aprobados', 'pn-customers-manager'); ?> <span class="count">(<?php echo esc_html($count_approved); ?>)</span></a> |</li>
				<li><a href="<?php echo esc_url(add_query_arg('status', 'rejected', $base_url)); ?>" class="<?php echo $filter === 'rejected' ? 'current' : ''; ?>"><?php esc_html_e('Rechazados', 'pn-customers-manager'); ?> <span class="count">(<?php echo esc_html($count_rejected); ?>)</span></a></li>
			</ul>

			<table class="wp-list-table widefat fixed striped pn-cm-commercial-table">
				<thead>
					<tr>
						<th><?php esc_html_e('Nombre', 'pn-customers-manager'); ?></th>
						<th><?php esc_html_e('Email', 'pn-customers-manager'); ?></th>
						<th><?php esc_html_e('Telefono', 'pn-customers-manager'); ?></th>
						<th><?php esc_html_e('Empresa', 'pn-customers-manager'); ?></th>
						<th><?php esc_html_e('Fecha solicitud', 'pn-customers-manager'); ?></th>
						<th><?php esc_html_e('Estado', 'pn-customers-manager'); ?></th>
						<th><?php esc_html_e('Acciones', 'pn-customers-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($agents)) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e('No se encontraron agentes comerciales.', 'pn-customers-manager'); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ($agents as $agent) :
							$agent_status = get_user_meta($agent->ID, 'pn_cm_commercial_status', true);
							$applied_at = get_user_meta($agent->ID, 'pn_cm_commercial_applied_at', true);
							$phone = get_user_meta($agent->ID, 'pn_cm_commercial_phone', true);
							$company = get_user_meta($agent->ID, 'pn_cm_commercial_company', true);
						?>
						<tr data-user-id="<?php echo esc_attr($agent->ID); ?>">
							<td>
								<strong><?php echo esc_html($agent->first_name . ' ' . $agent->last_name); ?></strong>
							</td>
							<td><?php echo esc_html($agent->user_email); ?></td>
							<td><?php echo esc_html($phone); ?></td>
							<td><?php echo esc_html($company); ?></td>
							<td><?php echo $applied_at ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($applied_at))) : '—'; ?></td>
							<td>
								<span class="pn-cm-commercial-admin-badge pn-cm-commercial-admin-badge-<?php echo esc_attr($agent_status); ?>">
									<?php
									$status_labels = [
										'pending'  => __('Pendiente', 'pn-customers-manager'),
										'approved' => __('Aprobado', 'pn-customers-manager'),
										'rejected' => __('Rechazado', 'pn-customers-manager'),
									];
									echo esc_html($status_labels[$agent_status] ?? $agent_status);
									?>
								</span>
							</td>
							<td>
								<?php if ($agent_status === 'pending') : ?>
									<button type="button" class="button button-primary pn-cm-commercial-approve-btn" data-user-id="<?php echo esc_attr($agent->ID); ?>">
										<?php esc_html_e('Aprobar', 'pn-customers-manager'); ?>
									</button>
									<button type="button" class="button pn-cm-commercial-reject-btn" data-user-id="<?php echo esc_attr($agent->ID); ?>">
										<?php esc_html_e('Rechazar', 'pn-customers-manager'); ?>
									</button>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
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

		wp_register_script(
			'pn-customers-manager-commercial-panel-block',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-commercial-panel.js',
			['wp-blocks', 'wp-element', 'wp-i18n'],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.9',
			true
		);

		register_block_type('pn-customers-manager/commercial-panel', [
			'editor_script'   => 'pn-customers-manager-commercial-panel-block',
			'render_callback' => [__CLASS__, 'render_commercial_panel'],
			'attributes'      => [],
		]);
	}
}
