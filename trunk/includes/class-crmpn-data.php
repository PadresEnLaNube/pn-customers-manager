<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin so that it is ready for translation.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Data {
	/**
	 * The main data array.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      CRMPN_Data    $data    Empty array.
	 */
	protected $data = [];

	/**
	 * Load the plugin most usefull data.
	 *
	 * @since    1.0.0
	 */
	public function crmpn_load_plugin_data() {
		$this->data['user_id'] = get_current_user_id();

		if (is_admin()) {
			$this->data['post_id'] = !empty($GLOBALS['_REQUEST']['post']) ? $GLOBALS['_REQUEST']['post'] : 0;
		} else {
			$this->data['post_id'] = get_the_ID();
		}

		$GLOBALS['crmpn_data'] = $this->data;
	}

	/**
	 * Flush wp rewrite rules.
	 *
	 * @since    1.0.0
	 */
	public function crmpn_flush_rewrite_rules() {
    if (get_option('crmpn_options_changed')) {
      flush_rewrite_rules();
      update_option('crmpn_options_changed', false);
    }
  }

  /**
	 * Gets the mini loader.
	 *
	 * @since    1.0.0
	 */
	public static function crmpn_loader($display = false) {
		?>
			<div class="crmpn-waiting <?php echo ($display) ? 'crmpn-display-block' : 'crmpn-display-none'; ?>">
				<div class="crmpn-loader-circle-waiting"><div></div><div></div><div></div><div></div></div>
			</div>
		<?php
  }

  /**
	 * Load popup loader.
	 *
	 * @since    1.0.0
	 */
	public static function crmpn_popup_loader() {
		?>
			<div class="crmpn-popup-content">
				<div class="crmpn-loader-circle-wrapper"><div class="crmpn-text-align-center"><div class="crmpn-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>
			</div>
		<?php
	}
}