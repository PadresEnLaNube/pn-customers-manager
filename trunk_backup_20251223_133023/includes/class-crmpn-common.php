<?php
/**
 * The-global functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to enqueue the-global stylesheet and JavaScript.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Common {

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
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets.
	 *
	 * @since    1.0.0
	 */
	public function customers_manager_pn_enqueue_styles() {
		if (!wp_style_is($this->plugin_name . '-material-icons-outlined', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-material-icons-outlined', CUSTOMERS_MANAGER_PN_URL . 'assets/css/material-icons-outlined.min.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-popups', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-popups', CUSTOMERS_MANAGER_PN_URL . 'assets/css/customers-manager-pn-popups.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-selector', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-selector', CUSTOMERS_MANAGER_PN_URL . 'assets/css/customers-manager-pn-selector.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-trumbowyg', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-trumbowyg', CUSTOMERS_MANAGER_PN_URL . 'assets/css/trumbowyg.min.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-tooltipster', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-tooltipster', CUSTOMERS_MANAGER_PN_URL . 'assets/css/tooltipster.min.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-owl', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-owl', CUSTOMERS_MANAGER_PN_URL . 'assets/css/owl.min.css', [], $this->version, 'all');
    }

		wp_enqueue_style($this->plugin_name, CUSTOMERS_MANAGER_PN_URL . 'assets/css/crmpn.css', [], $this->version, 'all');
	}

	/**
	 * Register the JavaScript.
	 *
	 * @since    1.0.0
	 */
	public function customers_manager_pn_enqueue_scripts() {
    if(!wp_script_is('jquery-ui-sortable', 'enqueued')) {
			wp_enqueue_script('jquery-ui-sortable');
    }

    if(!wp_script_is($this->plugin_name . '-trumbowyg', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-trumbowyg', CUSTOMERS_MANAGER_PN_URL . 'assets/js/trumbowyg.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

		wp_localize_script($this->plugin_name . '-trumbowyg', 'customers_manager_pn_trumbowyg', [
			'path' => CUSTOMERS_MANAGER_PN_URL . 'assets/media/trumbowyg-icons.svg',
		]);

    if(!wp_script_is($this->plugin_name . '-popups', 'enqueued')) {
      wp_enqueue_script($this->plugin_name . '-popups', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-popups.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

    if(!wp_script_is($this->plugin_name . '-selector', 'enqueued')) {
      wp_enqueue_script($this->plugin_name . '-selector', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-selector.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

    if(!wp_script_is($this->plugin_name . '-tooltipster', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-tooltipster', CUSTOMERS_MANAGER_PN_URL . 'assets/js/tooltipster.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

    if(!wp_script_is($this->plugin_name . '-owl', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-owl', CUSTOMERS_MANAGER_PN_URL . 'assets/js/owl.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

		wp_enqueue_script($this->plugin_name, CUSTOMERS_MANAGER_PN_URL . 'assets/js/crmpn.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-aux', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-aux.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-forms', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-forms.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-ajax', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-ajax.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);

		wp_localize_script($this->plugin_name . '-ajax', 'customers_manager_pn_ajax', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'customers_manager_pn_ajax_nonce' => wp_create_nonce('customers-manager-pn-nonce'),
		]);

		// Add CPTs data to JavaScript
		wp_localize_script($this->plugin_name . '-ajax', 'customers_manager_pn_cpts', CUSTOMERS_MANAGER_PN_CPTS);

		// Verify nonce for GET parameters
		$nonce_verified = false;
		if (!empty($_GET['customers_manager_pn_nonce'])) {
			$nonce_verified = wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['customers_manager_pn_nonce'])), 'customers-manager-pn-get-nonce');
		}

		// Only process GET parameters if nonce is verified
		$customers_manager_pn_action = '';
		$customers_manager_pn_btn_id = '';
		$customers_manager_pn_popup = '';
		$customers_manager_pn_tab = '';

		if ($nonce_verified) {
			$customers_manager_pn_action = !empty($_GET['customers_manager_pn_action']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_GET['customers_manager_pn_action'])) : '';
			$customers_manager_pn_btn_id = !empty($_GET['customers_manager_pn_btn_id']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_GET['customers_manager_pn_btn_id'])) : '';
			$customers_manager_pn_popup = !empty($_GET['customers_manager_pn_popup']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_GET['customers_manager_pn_popup'])) : '';
			$customers_manager_pn_tab = !empty($_GET['customers_manager_pn_tab']) ? CUSTOMERS_MANAGER_PN_Forms::customers_manager_pn_sanitizer(wp_unslash($_GET['customers_manager_pn_tab'])) : '';
		}
		
		wp_localize_script($this->plugin_name, 'customers_manager_pn_action', [
			'action' => $customers_manager_pn_action,
			'btn_id' => $customers_manager_pn_btn_id,
			'popup' => $customers_manager_pn_popup,
			'tab' => $customers_manager_pn_tab,
			'customers_manager_pn_get_nonce' => wp_create_nonce('customers-manager-pn-get-nonce'),
		]);

		wp_localize_script($this->plugin_name, 'customers_manager_pn_path', [
			'main' => CUSTOMERS_MANAGER_PN_URL,
			'assets' => CUSTOMERS_MANAGER_PN_URL . 'assets/',
			'css' => CUSTOMERS_MANAGER_PN_URL . 'assets/css/',
			'js' => CUSTOMERS_MANAGER_PN_URL . 'assets/js/',
			'media' => CUSTOMERS_MANAGER_PN_URL . 'assets/media/',
		]);

		wp_localize_script($this->plugin_name, 'customers_manager_pn_i18n', [
			'an_error_has_occurred' => esc_html(__('An error has occurred. Please try again in a few minutes.', 'customers-manager-pn')),
			'user_unlogged' => esc_html(__('Please create a new user or login to save the information.', 'customers-manager-pn')),
			'saved_successfully' => esc_html(__('Saved successfully', 'customers-manager-pn')),
			'removed_successfully' => esc_html(__('Removed successfully', 'customers-manager-pn')),
			'edit_image' => esc_html(__('Edit image', 'customers-manager-pn')),
			'edit_images' => esc_html(__('Edit images', 'customers-manager-pn')),
			'select_image' => esc_html(__('Select image', 'customers-manager-pn')),
			'select_images' => esc_html(__('Select images', 'customers-manager-pn')),
			'edit_video' => esc_html(__('Edit video', 'customers-manager-pn')),
			'edit_videos' => esc_html(__('Edit videos', 'customers-manager-pn')),
			'select_video' => esc_html(__('Select video', 'customers-manager-pn')),
			'select_videos' => esc_html(__('Select videos', 'customers-manager-pn')),
			'edit_audio' => esc_html(__('Edit audio', 'customers-manager-pn')),
			'edit_audios' => esc_html(__('Edit audios', 'customers-manager-pn')),
			'select_audio' => esc_html(__('Select audio', 'customers-manager-pn')),
			'select_audios' => esc_html(__('Select audios', 'customers-manager-pn')),
			'edit_file' => esc_html(__('Edit file', 'customers-manager-pn')),
			'edit_files' => esc_html(__('Edit files', 'customers-manager-pn')),
			'select_file' => esc_html(__('Select file', 'customers-manager-pn')),
			'select_files' => esc_html(__('Select files', 'customers-manager-pn')),
			'ordered_element' => esc_html(__('Ordered element', 'customers-manager-pn')),
			'select_option' => esc_html(__('Select option', 'customers-manager-pn')),
			'select_options' => esc_html(__('Select options', 'customers-manager-pn')),
			'copied' => esc_html(__('Copied', 'customers-manager-pn')),

			// Audio recorder translations
			'ready_to_record' => esc_html(__('Ready to record', 'customers-manager-pn')),
			'recording' => esc_html(__('Recording...', 'customers-manager-pn')),
			'recording_stopped' => esc_html(__('Recording stopped. Ready to play or transcribe.', 'customers-manager-pn')),
			'recording_completed' => esc_html(__('Recording completed. Ready to transcribe.', 'customers-manager-pn')),
			'microphone_error' => esc_html(__('Error: Could not access microphone', 'customers-manager-pn')),
			'no_audio_to_transcribe' => esc_html(__('No audio to transcribe', 'customers-manager-pn')),
			'invalid_response_format' => esc_html(__('Invalid server response format', 'customers-manager-pn')),
			'invalid_server_response' => esc_html(__('Invalid server response', 'customers-manager-pn')),
			'transcription_completed' => esc_html(__('Transcription completed', 'customers-manager-pn')),
			'no_transcription_received' => esc_html(__('No transcription received from server', 'customers-manager-pn')),
			'transcription_error' => esc_html(__('Error in transcription', 'customers-manager-pn')),
			'connection_error' => esc_html(__('Connection error', 'customers-manager-pn')),
			'connection_error_server' => esc_html(__('Connection error: Could not connect to server', 'customers-manager-pn')),
			'permission_error' => esc_html(__('Permission error: Security verification failed', 'customers-manager-pn')),
			'server_error' => esc_html(__('Server error: Internal server problem', 'customers-manager-pn')),
			'unknown_error' => esc_html(__('Unknown error', 'customers-manager-pn')),
			'processing_error' => esc_html(__('Error processing audio', 'customers-manager-pn')),
		]);

		// Initialize popups
		CUSTOMERS_MANAGER_PN_Popups::instance();

		// Initialize selectors
		CUSTOMERS_MANAGER_PN_Selector::instance();
	}

  public function customers_manager_pn_body_classes($classes) {
	  $classes[] = 'customers-manager-pn-body';

	  if (!is_user_logged_in()) {
      $classes[] = 'customers-manager-pn-body-unlogged';
    } else {
      $classes[] = 'customers-manager-pn-body-logged-in';

      $user = new WP_User(get_current_user_id());
      foreach ($user->roles as $role) {
        $classes[] = 'customers-manager-pn-body-' . $role;
      }
    }

	  return $classes;
  }
}
