<?php
/**
 * The-global functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to enqueue the-global stylesheet and JavaScript.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Common
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_enqueue_styles()
	{
		if (!wp_style_is($this->plugin_name . '-material-icons-outlined', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-material-icons-outlined', PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css', [], $this->version, 'all');
		}

		if (!wp_style_is($this->plugin_name . '-popups', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', [], $this->version, 'all');
		}

		if (!wp_style_is($this->plugin_name . '-selector', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-selector', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-selector.css', [], $this->version, 'all');
		}

		if (!wp_style_is($this->plugin_name . '-trumbowyg', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-trumbowyg', PN_CUSTOMERS_MANAGER_URL . 'assets/css/trumbowyg.min.css', [], $this->version, 'all');
		}

		if (!wp_style_is($this->plugin_name . '-tooltipster', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-tooltipster', PN_CUSTOMERS_MANAGER_URL . 'assets/css/tooltipster.min.css', [], $this->version, 'all');
		}

		if (!wp_style_is($this->plugin_name . '-owl', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-owl', PN_CUSTOMERS_MANAGER_URL . 'assets/css/owl.min.css', [], $this->version, 'all');
		}

		if (!wp_style_is($this->plugin_name . '-referral', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-referral', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-referral.css', [], $this->version, 'all');
		}

		if (!wp_style_is($this->plugin_name . '-commercial', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-commercial', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-commercial.css', [], $this->version, 'all');
		}

		wp_enqueue_style($this->plugin_name, PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], $this->version, 'all');

		// Enqueue dynamic CSS for color customization
		$this->pn_customers_manager_enqueue_dynamic_colors();
	}

	/**
	 * Generate and enqueue dynamic CSS for color customization.
	 *
	 * @since    1.0.0
	 */
	private function pn_customers_manager_enqueue_dynamic_colors()
	{
		// Get color values from options, with defaults
		$colors = [
			'color_main' => get_option('pn_customers_manager_color_main', '#0000aa'),
			'bg_color_main' => get_option('pn_customers_manager_bg_color_main', '#0000aa'),
			'border_color_main' => get_option('pn_customers_manager_border_color_main', '#0000aa'),
			'color_main_alt' => get_option('pn_customers_manager_color_main_alt', '#232323'),
			'bg_color_main_alt' => get_option('pn_customers_manager_bg_color_main_alt', '#232323'),
			'border_color_main_alt' => get_option('pn_customers_manager_border_color_main_alt', '#232323'),
			'color_main_blue' => get_option('pn_customers_manager_color_main_blue', '#6e6eff'),
			'color_main_grey' => get_option('pn_customers_manager_color_main_grey', '#f5f5f5'),
		];

		// Default values as fallback
		$defaults = [
			'color_main' => '#0000aa',
			'bg_color_main' => '#0000aa',
			'border_color_main' => '#0000aa',
			'color_main_alt' => '#232323',
			'bg_color_main_alt' => '#232323',
			'border_color_main_alt' => '#232323',
			'color_main_blue' => '#6e6eff',
			'color_main_grey' => '#f5f5f5',
		];

		// Sanitize color values and use defaults if invalid
		foreach ($colors as $key => $color) {
			$sanitized = sanitize_hex_color($color);
			$colors[$key] = $sanitized ? $sanitized : $defaults[$key];
		}

		// Generate CSS
		$css = ':root {';
		$css .= '  --pn-customers-manager-color-main:' . esc_attr($colors['color_main']) . ';';
		$css .= '  --pn-customers-manager-bg-color-main:' . esc_attr($colors['bg_color_main']) . ';';
		$css .= '  --pn-customers-manager-border-color-main:' . esc_attr($colors['border_color_main']) . ';';
		$css .= '  --pn-customers-manager-color-main-alt:' . esc_attr($colors['color_main_alt']) . ';';
		$css .= '  --pn-customers-manager-bg-color-main-alt:' . esc_attr($colors['bg_color_main_alt']) . ';';
		$css .= '  --pn-customers-manager-border-color-main-alt:' . esc_attr($colors['border_color_main_alt']) . ';';
		$css .= '  --pn-customers-manager-color-main-blue:' . esc_attr($colors['color_main_blue']) . ';';
		$css .= '  --pn-customers-manager-color-main-grey:' . esc_attr($colors['color_main_grey']) . ';';
		$css .= '}';

		// Add inline style after the main stylesheet
		wp_add_inline_style($this->plugin_name, $css);
	}

	/**
	 * Register the JavaScript.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_enqueue_scripts()
	{
		if (!wp_script_is('jquery-ui-sortable', 'enqueued')) {
			wp_enqueue_script('jquery-ui-sortable');
		}

		if (!wp_script_is($this->plugin_name . '-trumbowyg', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-trumbowyg', PN_CUSTOMERS_MANAGER_URL . 'assets/js/trumbowyg.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		}

		wp_localize_script($this->plugin_name . '-trumbowyg', 'pn_customers_manager_trumbowyg', [
			'path' => PN_CUSTOMERS_MANAGER_URL . 'assets/media/trumbowyg-icons.svg',
		]);

		if (!wp_script_is($this->plugin_name . '-popups', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		}

		if (!wp_script_is($this->plugin_name . '-selector', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-selector', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-selector.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		}

		if (!wp_script_is($this->plugin_name . '-tooltipster', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-tooltipster', PN_CUSTOMERS_MANAGER_URL . 'assets/js/tooltipster.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		}

		if (!wp_script_is($this->plugin_name . '-owl', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-owl', PN_CUSTOMERS_MANAGER_URL . 'assets/js/owl.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		}

		wp_enqueue_script($this->plugin_name, PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-aux', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-aux.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-forms', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-forms.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-ajax', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-ajax.js', [$this->plugin_name . '-popups', 'jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);

		if (!wp_script_is($this->plugin_name . '-referral', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-referral', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-referral.js', ['jquery'], $this->version, false);
		}

		if (!wp_script_is($this->plugin_name . '-qrcode', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-qrcode', PN_CUSTOMERS_MANAGER_URL . 'assets/js/qrcode.min.js', [], $this->version, false);
		}

		if (!wp_script_is($this->plugin_name . '-referral-qr', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-referral-qr', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-referral-qr.js', ['jquery', $this->plugin_name . '-qrcode'], $this->version, false);
		}

		if (!wp_script_is($this->plugin_name . '-commercial', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-commercial', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-commercial.js', ['jquery'], $this->version, false);
		}

		wp_localize_script($this->plugin_name . '-ajax', 'pn_customers_manager_ajax', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'pn_customers_manager_ajax_nonce' => wp_create_nonce('pn-customers-manager-nonce'),
		]);

		// Add CPTs data to JavaScript
		wp_localize_script($this->plugin_name . '-ajax', 'pn_customers_manager_cpts', PN_CUSTOMERS_MANAGER_CPTS);

		// Verify nonce for GET parameters
		$nonce_verified = false;
		if (!empty($_GET['pn_customers_manager_nonce'])) {
			$nonce_verified = wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['pn_customers_manager_nonce'])), 'pn-customers-manager-get-nonce');
		}

		// Only process GET parameters if nonce is verified
		$pn_customers_manager_action = '';
		$pn_customers_manager_btn_id = '';
		$pn_customers_manager_popup = '';
		$pn_customers_manager_tab = '';

		if ($nonce_verified) {
			$pn_customers_manager_action = !empty($_GET['pn_customers_manager_action']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_GET['pn_customers_manager_action'])) : '';
			$pn_customers_manager_btn_id = !empty($_GET['pn_customers_manager_btn_id']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_GET['pn_customers_manager_btn_id'])) : '';
			$pn_customers_manager_popup = !empty($_GET['pn_customers_manager_popup']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_GET['pn_customers_manager_popup'])) : '';
			$pn_customers_manager_tab = !empty($_GET['pn_customers_manager_tab']) ? PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_sanitizer(wp_unslash($_GET['pn_customers_manager_tab'])) : '';
		}

		wp_localize_script($this->plugin_name, 'pn_customers_manager_action', [
			'action' => $pn_customers_manager_action,
			'btn_id' => $pn_customers_manager_btn_id,
			'popup' => $pn_customers_manager_popup,
			'tab' => $pn_customers_manager_tab,
			'pn_customers_manager_get_nonce' => wp_create_nonce('pn-customers-manager-get-nonce'),
		]);

		wp_localize_script($this->plugin_name, 'pn_customers_manager_path', [
			'main' => PN_CUSTOMERS_MANAGER_URL,
			'assets' => PN_CUSTOMERS_MANAGER_URL . 'assets/',
			'css' => PN_CUSTOMERS_MANAGER_URL . 'assets/css/',
			'js' => PN_CUSTOMERS_MANAGER_URL . 'assets/js/',
			'media' => PN_CUSTOMERS_MANAGER_URL . 'assets/media/',
		]);

		wp_localize_script($this->plugin_name, 'pn_customers_manager_i18n', [
			'an_error_has_occurred' => esc_html(__('An error has occurred. Please try again in a few minutes.', 'pn-customers-manager')),
			'user_unlogged' => esc_html(__('Please create a new user or login to save the information.', 'pn-customers-manager')),
			'saved_successfully' => esc_html(__('Saved successfully', 'pn-customers-manager')),
			'removed_successfully' => esc_html(__('Removed successfully', 'pn-customers-manager')),
			'duplicated_successfully' => esc_html(__('Duplicated successfully', 'pn-customers-manager')),
			'edit_image' => esc_html(__('Edit image', 'pn-customers-manager')),
			'edit_images' => esc_html(__('Edit images', 'pn-customers-manager')),
			'select_image' => esc_html(__('Select image', 'pn-customers-manager')),
			'select_images' => esc_html(__('Select images', 'pn-customers-manager')),
			'edit_video' => esc_html(__('Edit video', 'pn-customers-manager')),
			'edit_videos' => esc_html(__('Edit videos', 'pn-customers-manager')),
			'select_video' => esc_html(__('Select video', 'pn-customers-manager')),
			'select_videos' => esc_html(__('Select videos', 'pn-customers-manager')),
			'edit_audio' => esc_html(__('Edit audio', 'pn-customers-manager')),
			'edit_audios' => esc_html(__('Edit audios', 'pn-customers-manager')),
			'select_audio' => esc_html(__('Select audio', 'pn-customers-manager')),
			'select_audios' => esc_html(__('Select audios', 'pn-customers-manager')),
			'edit_file' => esc_html(__('Edit file', 'pn-customers-manager')),
			'edit_files' => esc_html(__('Edit files', 'pn-customers-manager')),
			'select_file' => esc_html(__('Select file', 'pn-customers-manager')),
			'select_files' => esc_html(__('Select files', 'pn-customers-manager')),
			'ordered_element' => esc_html(__('Ordered element', 'pn-customers-manager')),
			'select_option' => esc_html(__('Select option', 'pn-customers-manager')),
			'select_options' => esc_html(__('Select options', 'pn-customers-manager')),
			'copied' => esc_html(__('Copied', 'pn-customers-manager')),

			// Audio recorder translations
			'ready_to_record' => esc_html(__('Ready to record', 'pn-customers-manager')),
			'recording' => esc_html(__('Recording...', 'pn-customers-manager')),
			'recording_stopped' => esc_html(__('Recording stopped. Ready to play or transcribe.', 'pn-customers-manager')),
			'recording_completed' => esc_html(__('Recording completed. Ready to transcribe.', 'pn-customers-manager')),
			'microphone_error' => esc_html(__('Error: Could not access microphone', 'pn-customers-manager')),
			'no_audio_to_transcribe' => esc_html(__('No audio to transcribe', 'pn-customers-manager')),
			'invalid_response_format' => esc_html(__('Invalid server response format', 'pn-customers-manager')),
			'invalid_server_response' => esc_html(__('Invalid server response', 'pn-customers-manager')),
			'transcription_completed' => esc_html(__('Transcription completed', 'pn-customers-manager')),
			'no_transcription_received' => esc_html(__('No transcription received from server', 'pn-customers-manager')),
			'transcription_error' => esc_html(__('Error in transcription', 'pn-customers-manager')),
			'connection_error' => esc_html(__('Connection error', 'pn-customers-manager')),
			'connection_error_server' => esc_html(__('Connection error: Could not connect to server', 'pn-customers-manager')),
			'permission_error' => esc_html(__('Permission error: Security verification failed', 'pn-customers-manager')),
			'server_error' => esc_html(__('Server error: Internal server problem', 'pn-customers-manager')),
			'unknown_error' => esc_html(__('Unknown error', 'pn-customers-manager')),
			'processing_error' => esc_html(__('Error processing audio', 'pn-customers-manager')),

			// Referral translations
			'referral_sent' => esc_html(__('Invitation sent successfully!', 'pn-customers-manager')),
			'referral_link_copied' => esc_html(__('Referral link copied to clipboard!', 'pn-customers-manager')),
			'referral_invalid_email' => esc_html(__('Please enter a valid email address.', 'pn-customers-manager')),
			'referral_email_exists' => esc_html(__('This email is already registered.', 'pn-customers-manager')),
			'referral_already_sent' => esc_html(__('An invitation has already been sent to this email.', 'pn-customers-manager')),
			'referral_not_logged_in' => esc_html(__('You must be logged in to send referrals.', 'pn-customers-manager')),
			'referral_user_creation_failed' => esc_html(__('Could not create the user. Please try again.', 'pn-customers-manager')),

			// QR Referral translations
			'qr_referral_download' => esc_html(__('Descargar QR', 'pn-customers-manager')),
			'qr_referral_email_required' => esc_html(__('Introduce tu correo electronico.', 'pn-customers-manager')),
			'qr_referral_success' => esc_html(__('Registro completado! Revisa tu email para activar tu cuenta.', 'pn-customers-manager')),
			'qr_referral_error' => esc_html(__('Ha ocurrido un error. Intentalo de nuevo.', 'pn-customers-manager')),

			// Commercial translations
			'commercial_application_sent' => esc_html(__('Tu solicitud ha sido enviada correctamente. Te notificaremos pronto.', 'pn-customers-manager')),
			'commercial_missing_fields' => esc_html(__('Por favor, completa todos los campos obligatorios.', 'pn-customers-manager')),
			'commercial_confirm_approve' => esc_html(__('¿Seguro que quieres aprobar a este agente comercial?', 'pn-customers-manager')),
			'commercial_confirm_reject' => esc_html(__('¿Seguro que quieres rechazar a este agente comercial?', 'pn-customers-manager')),
			'commercial_status_approved' => esc_html(__('Aprobado', 'pn-customers-manager')),
			'commercial_status_rejected' => esc_html(__('Rechazado', 'pn-customers-manager')),
		]);

		// Initialize popups
		PN_CUSTOMERS_MANAGER_Popups::instance();

		// Initialize selectors
		PN_CUSTOMERS_MANAGER_Selector::instance();
	}

	public function pn_customers_manager_body_classes($classes)
	{
		$classes[] = 'pn-customers-manager-body';

		if (!is_user_logged_in()) {
			$classes[] = 'pn-customers-manager-body-unlogged';
		} else {
			$classes[] = 'pn-customers-manager-body-logged-in';

			$user = new WP_User(get_current_user_id());
			foreach ($user->roles as $role) {
				$classes[] = 'pn-customers-manager-body-' . $role;
			}
		}

		return $classes;
	}
}
