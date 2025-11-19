<?php
/**
 * The-global functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to enqueue the-global stylesheet and JavaScript.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Common {

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
	public function crmpn_enqueue_styles() {
		if (!wp_style_is($this->plugin_name . '-material-icons-outlined', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-material-icons-outlined', CRMPN_URL . 'assets/css/material-icons-outlined.min.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-popups', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-popups', CRMPN_URL . 'assets/css/crmpn-popups.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-selector', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-selector', CRMPN_URL . 'assets/css/crmpn-selector.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-trumbowyg', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-trumbowyg', CRMPN_URL . 'assets/css/trumbowyg.min.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-tooltipster', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-tooltipster', CRMPN_URL . 'assets/css/tooltipster.min.css', [], $this->version, 'all');
    }

    if (!wp_style_is($this->plugin_name . '-owl', 'enqueued')) {
			wp_enqueue_style($this->plugin_name . '-owl', CRMPN_URL . 'assets/css/owl.min.css', [], $this->version, 'all');
    }

		wp_enqueue_style($this->plugin_name, CRMPN_URL . 'assets/css/crmpn.css', [], $this->version, 'all');
	}

	/**
	 * Register the JavaScript.
	 *
	 * @since    1.0.0
	 */
	public function crmpn_enqueue_scripts() {
    if(!wp_script_is('jquery-ui-sortable', 'enqueued')) {
			wp_enqueue_script('jquery-ui-sortable');
    }

    if(!wp_script_is($this->plugin_name . '-trumbowyg', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-trumbowyg', CRMPN_URL . 'assets/js/trumbowyg.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

		wp_localize_script($this->plugin_name . '-trumbowyg', 'crmpn_trumbowyg', [
			'path' => CRMPN_URL . 'assets/media/trumbowyg-icons.svg',
		]);

    if(!wp_script_is($this->plugin_name . '-popups', 'enqueued')) {
      wp_enqueue_script($this->plugin_name . '-popups', CRMPN_URL . 'assets/js/crmpn-popups.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

    if(!wp_script_is($this->plugin_name . '-selector', 'enqueued')) {
      wp_enqueue_script($this->plugin_name . '-selector', CRMPN_URL . 'assets/js/crmpn-selector.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

    if(!wp_script_is($this->plugin_name . '-tooltipster', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-tooltipster', CRMPN_URL . 'assets/js/tooltipster.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

    if(!wp_script_is($this->plugin_name . '-owl', 'enqueued')) {
			wp_enqueue_script($this->plugin_name . '-owl', CRMPN_URL . 'assets/js/owl.min.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
    }

		wp_enqueue_script($this->plugin_name, CRMPN_URL . 'assets/js/crmpn.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-aux', CRMPN_URL . 'assets/js/crmpn-aux.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-forms', CRMPN_URL . 'assets/js/crmpn-forms.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);
		wp_enqueue_script($this->plugin_name . '-ajax', CRMPN_URL . 'assets/js/crmpn-ajax.js', ['jquery'], $this->version, false, ['in_footer' => true, 'strategy' => 'defer']);

		wp_localize_script($this->plugin_name . '-ajax', 'crmpn_ajax', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'crmpn_ajax_nonce' => wp_create_nonce('crmpn-nonce'),
		]);

		// Add CPTs data to JavaScript
		wp_localize_script($this->plugin_name . '-ajax', 'crmpn_cpts', CRMPN_CPTS);

		// Verify nonce for GET parameters
		$nonce_verified = false;
		if (!empty($_GET['crmpn_nonce'])) {
			$nonce_verified = wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['crmpn_nonce'])), 'crmpn-get-nonce');
		}

		// Only process GET parameters if nonce is verified
		$crmpn_action = '';
		$crmpn_btn_id = '';
		$crmpn_popup = '';
		$crmpn_tab = '';

		if ($nonce_verified) {
			$crmpn_action = !empty($_GET['crmpn_action']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_GET['crmpn_action'])) : '';
			$crmpn_btn_id = !empty($_GET['crmpn_btn_id']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_GET['crmpn_btn_id'])) : '';
			$crmpn_popup = !empty($_GET['crmpn_popup']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_GET['crmpn_popup'])) : '';
			$crmpn_tab = !empty($_GET['crmpn_tab']) ? CRMPN_Forms::crmpn_sanitizer(wp_unslash($_GET['crmpn_tab'])) : '';
		}
		
		wp_localize_script($this->plugin_name, 'crmpn_action', [
			'action' => $crmpn_action,
			'btn_id' => $crmpn_btn_id,
			'popup' => $crmpn_popup,
			'tab' => $crmpn_tab,
			'crmpn_get_nonce' => wp_create_nonce('crmpn-get-nonce'),
		]);

		wp_localize_script($this->plugin_name, 'crmpn_path', [
			'main' => CRMPN_URL,
			'assets' => CRMPN_URL . 'assets/',
			'css' => CRMPN_URL . 'assets/css/',
			'js' => CRMPN_URL . 'assets/js/',
			'media' => CRMPN_URL . 'assets/media/',
		]);

		wp_localize_script($this->plugin_name, 'crmpn_i18n', [
			'an_error_has_occurred' => esc_html(__('An error has occurred. Please try again in a few minutes.', 'crmpn')),
			'user_unlogged' => esc_html(__('Please create a new user or login to save the information.', 'crmpn')),
			'saved_successfully' => esc_html(__('Saved successfully', 'crmpn')),
			'removed_successfully' => esc_html(__('Removed successfully', 'crmpn')),
			'edit_image' => esc_html(__('Edit image', 'crmpn')),
			'edit_images' => esc_html(__('Edit images', 'crmpn')),
			'select_image' => esc_html(__('Select image', 'crmpn')),
			'select_images' => esc_html(__('Select images', 'crmpn')),
			'edit_video' => esc_html(__('Edit video', 'crmpn')),
			'edit_videos' => esc_html(__('Edit videos', 'crmpn')),
			'select_video' => esc_html(__('Select video', 'crmpn')),
			'select_videos' => esc_html(__('Select videos', 'crmpn')),
			'edit_audio' => esc_html(__('Edit audio', 'crmpn')),
			'edit_audios' => esc_html(__('Edit audios', 'crmpn')),
			'select_audio' => esc_html(__('Select audio', 'crmpn')),
			'select_audios' => esc_html(__('Select audios', 'crmpn')),
			'edit_file' => esc_html(__('Edit file', 'crmpn')),
			'edit_files' => esc_html(__('Edit files', 'crmpn')),
			'select_file' => esc_html(__('Select file', 'crmpn')),
			'select_files' => esc_html(__('Select files', 'crmpn')),
			'ordered_element' => esc_html(__('Ordered element', 'crmpn')),
			'select_option' => esc_html(__('Select option', 'crmpn')),
			'select_options' => esc_html(__('Select options', 'crmpn')),
			'copied' => esc_html(__('Copied', 'crmpn')),

			// Audio recorder translations
			'ready_to_record' => esc_html(__('Ready to record', 'crmpn')),
			'recording' => esc_html(__('Recording...', 'crmpn')),
			'recording_stopped' => esc_html(__('Recording stopped. Ready to play or transcribe.', 'crmpn')),
			'recording_completed' => esc_html(__('Recording completed. Ready to transcribe.', 'crmpn')),
			'microphone_error' => esc_html(__('Error: Could not access microphone', 'crmpn')),
			'no_audio_to_transcribe' => esc_html(__('No audio to transcribe', 'crmpn')),
			'invalid_response_format' => esc_html(__('Invalid server response format', 'crmpn')),
			'invalid_server_response' => esc_html(__('Invalid server response', 'crmpn')),
			'transcription_completed' => esc_html(__('Transcription completed', 'crmpn')),
			'no_transcription_received' => esc_html(__('No transcription received from server', 'crmpn')),
			'transcription_error' => esc_html(__('Error in transcription', 'crmpn')),
			'connection_error' => esc_html(__('Connection error', 'crmpn')),
			'connection_error_server' => esc_html(__('Connection error: Could not connect to server', 'crmpn')),
			'permission_error' => esc_html(__('Permission error: Security verification failed', 'crmpn')),
			'server_error' => esc_html(__('Server error: Internal server problem', 'crmpn')),
			'unknown_error' => esc_html(__('Unknown error', 'crmpn')),
			'processing_error' => esc_html(__('Error processing audio', 'crmpn')),
		]);

		// Initialize popups
		CRMPN_Popups::instance();

		// Initialize selectors
		CRMPN_Selector::instance();
	}

  public function crmpn_body_classes($classes) {
	  $classes[] = 'crmpn-body';

	  if (!is_user_logged_in()) {
      $classes[] = 'crmpn-body-unlogged';
    } else {
      $classes[] = 'crmpn-body-logged-in';

      $user = new WP_User(get_current_user_id());
      foreach ($user->roles as $role) {
        $classes[] = 'crmpn-body-' . $role;
      }
    }

	  return $classes;
  }
}
