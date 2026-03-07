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
		if ($referrer) {
			$first = $referrer->first_name;
			$last = $referrer->last_name;
			$referrer_name = trim($first . ' ' . $last);
			if (empty($referrer_name)) {
				$referrer_name = $referrer->display_name;
			}
		} else {
			$referrer_name = __('Alguien', 'pn-customers-manager');
		}

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

			<?php if (get_option('pn_customers_manager_referral_enabled', 'on') === 'on' || current_user_can('manage_options')) :
				$qr_code = self::get_or_create_referral_code($user_id);
				$qr_url = home_url('?pn_cm_qr_ref=' . $qr_code);
				$qr_url_encoded = rawurlencode($qr_url);
				$default_share_text = get_option('pn_customers_manager_referral_share_text', '');
				if (empty($default_share_text)) {
					$default_share_text = __('Te invito a unirte! Registrate con mi enlace y empieza a disfrutar de todas las ventajas:', 'pn-customers-manager');
				}
				$user_share_text = get_user_meta($user_id, 'pn_cm_referral_share_text', true);
				$socials_url = PN_CUSTOMERS_MANAGER_URL . 'assets/media/socials/white/';
				$branding_url = self::get_qr_branding_url();
			?>
			<div class="pn-cm-referral-share-section" data-referral-url="<?php echo esc_url($qr_url); ?>">
				<h3><?php esc_html_e('Comparte tu enlace de referido', 'pn-customers-manager'); ?></h3>
				<div class="pn-cm-referral-share-link-row">
					<input type="text" class="pn-cm-referral-share-link-input" value="<?php echo esc_url($qr_url); ?>" readonly />
					<button type="button" class="pn-cm-referral-share-copy">
						<i class="material-icons-outlined">content_copy</i>
					</button>
				</div>
				<div class="pn-cm-referral-share-text-row">
					<label><?php esc_html_e('Personaliza tu mensaje:', 'pn-customers-manager'); ?></label>
					<textarea class="pn-cm-referral-share-textarea" placeholder="<?php echo esc_attr($default_share_text); ?>"><?php echo esc_textarea($user_share_text); ?></textarea>
				</div>
				<div class="pn-cm-referral-share-buttons">
					<a href="#" target="_blank" class="pn-cm-referral-share-btn pn-cm-referral-share-whatsapp" title="WhatsApp" data-share="whatsapp">
						<img src="<?php echo esc_url($socials_url . 'whatsapp.svg'); ?>" alt="WhatsApp" />
					</a>
					<a href="#" target="_blank" class="pn-cm-referral-share-btn pn-cm-referral-share-facebook" title="Facebook" data-share="facebook">
						<img src="<?php echo esc_url($socials_url . 'facebook.svg'); ?>" alt="Facebook" />
					</a>
					<a href="#" target="_blank" class="pn-cm-referral-share-btn pn-cm-referral-share-twitter" title="X" data-share="twitter">
						<img src="<?php echo esc_url($socials_url . 'twitterx.svg'); ?>" alt="X" />
					</a>
					<a href="#" target="_blank" class="pn-cm-referral-share-btn pn-cm-referral-share-telegram" title="Telegram" data-share="telegram">
						<img src="<?php echo esc_url($socials_url . 'telegram-app.svg'); ?>" alt="Telegram" />
					</a>
				</div>
				<div class="pn-cm-referral-share-message" style="display:none;"></div>
			</div>

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

			<div class="pn-cm-referral-bizcard-trigger">
				<button type="button" class="pn-cm-referral-bizcard-btn"><?php esc_html_e('Generar tarjeta de visita', 'pn-customers-manager'); ?></button>
			</div>

			<div id="pn-cm-referral-bizcard-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-large pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-disable-esc="true" data-pn-customers-manager-popup-disable-overlay-close="true">
				<div class="pn-customers-manager-popup-overlay"></div>
				<div class="pn-customers-manager-popup-content">
					<div class="pn-cm-referral-bizcard-layout">
						<div class="pn-cm-referral-bizcard-preview">
							<div class="pn-cm-bizcard-canvas-wrapper">
								<canvas id="pn-cm-bizcard-canvas" width="1012" height="654"></canvas>
							</div>
							<div class="pn-cm-bizcard-zoom-controls">
								<button type="button" class="pn-cm-bizcard-zoom-btn" data-zoom="out" title="<?php esc_attr_e('Alejar', 'pn-customers-manager'); ?>">&#8722;</button>
								<span class="pn-cm-bizcard-zoom-level">100%</span>
								<button type="button" class="pn-cm-bizcard-zoom-btn" data-zoom="in" title="<?php esc_attr_e('Acercar', 'pn-customers-manager'); ?>">&#43;</button>
								<button type="button" class="pn-cm-bizcard-zoom-btn pn-cm-bizcard-zoom-reset" data-zoom="reset" title="<?php esc_attr_e('Restablecer', 'pn-customers-manager'); ?>">&#8634;</button>
							</div>
						</div>
						<div class="pn-cm-referral-bizcard-options">
							<div class="pn-cm-bizcard-tabs">
								<button type="button" class="pn-cm-bizcard-tab pn-cm-bizcard-tab-active" data-face="front"><?php esc_html_e('Anverso', 'pn-customers-manager'); ?></button>
								<button type="button" class="pn-cm-bizcard-tab" data-face="back"><?php esc_html_e('Reverso', 'pn-customers-manager'); ?></button>
							</div>
							<div class="pn-cm-bizcard-face-front">
							<?php if (current_user_can('manage_options')) :
								$bizcard_users = get_users(['orderby' => 'display_name', 'order' => 'ASC', 'fields' => ['ID', 'display_name', 'user_email']]);
							?>
							<div class="pn-cm-bizcard-option-group">
								<label><?php esc_html_e('Generar como usuario', 'pn-customers-manager'); ?></label>
								<div class="pn-cm-bizcard-user-select">
									<div class="pn-cm-bizcard-user-selected" id="pn-cm-bizcard-user-selected" data-value=""><?php echo esc_html(wp_get_current_user()->display_name . ' (' . __('yo', 'pn-customers-manager') . ')'); ?></div>
									<div class="pn-cm-bizcard-user-dropdown">
										<input type="text" class="pn-cm-bizcard-user-search" placeholder="<?php esc_attr_e('Buscar usuario...', 'pn-customers-manager'); ?>" />
										<div class="pn-cm-bizcard-user-list">
											<div class="pn-cm-bizcard-user-option pn-cm-bizcard-user-option-active" data-value=""><?php echo esc_html(wp_get_current_user()->display_name . ' (' . __('yo', 'pn-customers-manager') . ')'); ?></div>
											<?php foreach ($bizcard_users as $bu) :
												if ((int) $bu->ID === $user_id) continue;
											?>
												<div class="pn-cm-bizcard-user-option" data-value="<?php echo esc_attr($bu->ID); ?>"><?php echo esc_html($bu->display_name . ' — ' . $bu->user_email); ?></div>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
							<?php endif; ?>
							<div class="pn-cm-bizcard-option-group">
								<label><?php esc_html_e('Imagen de fondo', 'pn-customers-manager'); ?></label>
								<input type="file" id="pn-cm-bizcard-bg-input" class="pn-cm-bizcard-bg-input" accept="image/*" />
								<span class="pn-cm-bizcard-bg-label"><?php esc_html_e('Seleccionar imagen', 'pn-customers-manager'); ?></span>
								<div class="pn-cm-bizcard-bg-preview">
									<img class="pn-cm-bizcard-bg-thumb" src="" alt="" />
									<button type="button" class="pn-cm-bizcard-bg-remove"><?php esc_html_e('Eliminar', 'pn-customers-manager'); ?></button>
								</div>
								<?php if (current_user_can('manage_options')) :
									$media_images = get_posts([
										'post_type'      => 'attachment',
										'post_mime_type' => 'image',
										'post_status'    => 'inherit',
										'posts_per_page' => 50,
										'orderby'        => 'date',
										'order'          => 'DESC',
									]);
									if (!empty($media_images)) : ?>
									<div class="pn-cm-bizcard-media-gallery">
										<?php foreach ($media_images as $mi) :
											$thumb = wp_get_attachment_image_url($mi->ID, 'thumbnail');
											$full  = wp_get_attachment_image_url($mi->ID, 'full');
											if (!$thumb || !$full) continue;
										?>
											<img class="pn-cm-bizcard-media-thumb" src="<?php echo esc_url($thumb); ?>" data-full="<?php echo esc_url($full); ?>" alt="" />
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
								<?php endif; ?>
							</div>
							<div class="pn-cm-bizcard-option-group">
								<label for="pn-cm-bizcard-format"><?php esc_html_e('Formato', 'pn-customers-manager'); ?></label>
								<select id="pn-cm-bizcard-format">
									<option value="standard"><?php esc_html_e('Estandar (1012x654)', 'pn-customers-manager'); ?></option>
									<option value="square"><?php esc_html_e('Cuadrada (774x774)', 'pn-customers-manager'); ?></option>
									<option value="mini"><?php esc_html_e('Mini (833x333)', 'pn-customers-manager'); ?></option>
								</select>
							</div>
							<div class="pn-cm-bizcard-option-group">
								<div class="pn-cm-bizcard-qr-check">
									<input type="checkbox" id="pn-cm-bizcard-qr" checked />
									<label for="pn-cm-bizcard-qr"><?php esc_html_e('Incluir codigo QR', 'pn-customers-manager'); ?></label>
								</div>
							</div>
							<div class="pn-cm-bizcard-option-group">
								<label for="pn-cm-bizcard-name"><?php esc_html_e('Nombre', 'pn-customers-manager'); ?></label>
								<input type="text" id="pn-cm-bizcard-name" class="pn-cm-bizcard-field" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" />
							</div>
							<div class="pn-cm-bizcard-option-group">
								<label for="pn-cm-bizcard-title"><?php esc_html_e('Cargo', 'pn-customers-manager'); ?></label>
								<input type="text" id="pn-cm-bizcard-title" class="pn-cm-bizcard-field" value="" />
							</div>
							<div class="pn-cm-bizcard-option-group">
								<label for="pn-cm-bizcard-phone"><?php esc_html_e('Telefono', 'pn-customers-manager'); ?></label>
								<input type="tel" id="pn-cm-bizcard-phone" class="pn-cm-bizcard-field" value="" />
							</div>
							<div class="pn-cm-bizcard-option-group">
								<label for="pn-cm-bizcard-email"><?php esc_html_e('Email', 'pn-customers-manager'); ?></label>
								<input type="email" id="pn-cm-bizcard-email" class="pn-cm-bizcard-field" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" />
							</div>
							<div class="pn-cm-bizcard-option-group">
								<label for="pn-cm-bizcard-web"><?php esc_html_e('Web', 'pn-customers-manager'); ?></label>
								<input type="text" id="pn-cm-bizcard-web" class="pn-cm-bizcard-field" value="<?php echo esc_attr(home_url()); ?>" />
							</div>
							</div><!-- /.pn-cm-bizcard-face-front -->
							<div class="pn-cm-bizcard-face-back pn-cm-bizcard-face-hidden">
								<?php
									$bizcard_phrases = get_option('pn_customers_manager_referral_bizcard_phrase_text', []);
									if (!is_array($bizcard_phrases)) $bizcard_phrases = [];
									$bizcard_phrases = array_filter($bizcard_phrases, function($p) { return !empty(trim($p)); });
								?>
								<?php if (!empty($bizcard_phrases)) : ?>
								<div class="pn-cm-bizcard-option-group">
									<label for="pn-cm-bizcard-phrase-select"><?php esc_html_e('Frases predefinidas', 'pn-customers-manager'); ?></label>
									<select id="pn-cm-bizcard-phrase-select">
										<option value=""><?php esc_html_e('-- Seleccionar frase --', 'pn-customers-manager'); ?></option>
										<?php foreach ($bizcard_phrases as $phrase) : ?>
											<option value="<?php echo esc_attr($phrase); ?>"><?php echo esc_html($phrase); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<?php endif; ?>
								<div class="pn-cm-bizcard-option-group">
									<label for="pn-cm-bizcard-message"><?php esc_html_e('Mensaje', 'pn-customers-manager'); ?></label>
									<textarea id="pn-cm-bizcard-message" class="pn-cm-bizcard-field pn-cm-bizcard-message-textarea" placeholder="<?php esc_attr_e('Escribe tu mensaje para el reverso...', 'pn-customers-manager'); ?>"></textarea>
								</div>
								<div class="pn-cm-bizcard-option-group">
									<div class="pn-cm-bizcard-qr-check">
										<input type="checkbox" id="pn-cm-bizcard-qr-back" checked />
										<label for="pn-cm-bizcard-qr-back"><?php esc_html_e('Incluir codigo QR en reverso', 'pn-customers-manager'); ?></label>
									</div>
								</div>
							</div><!-- /.pn-cm-bizcard-face-back -->
							<div class="pn-cm-bizcard-download-buttons">
								<button type="button" class="pn-cm-bizcard-download pn-cm-bizcard-download-front"><?php esc_html_e('Descargar anverso', 'pn-customers-manager'); ?></button>
								<button type="button" class="pn-cm-bizcard-download pn-cm-bizcard-download-back"><?php esc_html_e('Descargar reverso', 'pn-customers-manager'); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>

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
			'id'              => uniqid('ref_', true),
			'email'           => $email,
			'user_id'         => $new_user_id,
			'token'           => $token,
			'status'          => 'pending',
			'created_at'      => current_time('mysql'),
			'completed_at'    => null,
			'reminder_count'  => 0,
			'last_reminder_at' => null,
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
	 * Process referral reminders via cron.
	 *
	 * Iterates all users with pending referrals and re-sends invitation
	 * emails based on the configured max sends and frequency settings.
	 */
	public static function process_referral_reminders() {
		$max_sends = (int) get_option('pn_customers_manager_referral_reminder_max_sends', 3);
		$frequency = (int) get_option('pn_customers_manager_referral_reminder_frequency', 7);

		if ($max_sends <= 0) {
			return;
		}

		$users = get_users([
			'meta_key' => 'pn_cm_referrals',
			'fields'   => 'ID',
		]);

		if (empty($users)) {
			return;
		}

		$now = current_time('timestamp');

		foreach ($users as $user_id) {
			$referrals = self::get_user_referrals($user_id);

			if (empty($referrals)) {
				continue;
			}

			$updated = false;

			foreach ($referrals as &$referral) {
				if ($referral['status'] !== 'pending') {
					continue;
				}

				$reminder_count = isset($referral['reminder_count']) ? (int) $referral['reminder_count'] : 0;

				if ($reminder_count >= $max_sends) {
					continue;
				}

				$last_sent = !empty($referral['last_reminder_at'])
					? $referral['last_reminder_at']
					: $referral['created_at'];

				$last_sent_time = strtotime($last_sent);

				if (($now - $last_sent_time) < ($frequency * DAY_IN_SECONDS)) {
					continue;
				}

				self::send_referral_email(
					$user_id,
					$referral['user_id'],
					$referral['email'],
					$referral['token']
				);

				$referral['reminder_count']   = $reminder_count + 1;
				$referral['last_reminder_at'] = current_time('mysql');
				$updated = true;
			}
			unset($referral);

			if ($updated) {
				update_user_meta($user_id, 'pn_cm_referrals', $referrals);
			}
		}
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
