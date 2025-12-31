<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin so that it is ready for translation.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Data {
	/**
	 * The main data array.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      PN_CUSTOMERS_MANAGER_Data    $data    Empty array.
	 */
	protected $data = [];

	/**
	 * Load the plugin most usefull data.
	 *
	 * @since    1.0.0
	 */
	public function PN_CUSTOMERS_MANAGER_load_plugin_data() {
		$this->data['user_id'] = get_current_user_id();

		if (is_admin()) {
			$this->data['post_id'] = !empty($GLOBALS['_REQUEST']['post']) ? $GLOBALS['_REQUEST']['post'] : 0;
		} else {
			$this->data['post_id'] = get_the_ID();
		}

		$GLOBALS['PN_CUSTOMERS_MANAGER_data'] = $this->data;
	}

	/**
	 * Flush wp rewrite rules.
	 *
	 * @since    1.0.0
	 */
	public function PN_CUSTOMERS_MANAGER_flush_rewrite_rules() {
    if (get_option('PN_CUSTOMERS_MANAGER_options_changed')) {
      flush_rewrite_rules();
      update_option('PN_CUSTOMERS_MANAGER_options_changed', false);
    }
  }

  /**
	 * Gets the mini loader.
	 *
	 * @since    1.0.0
	 */
	public static function PN_CUSTOMERS_MANAGER_loader($display = false) {
		?>
			<div class="pn-customers-manager-waiting <?php echo ($display) ? 'pn-customers-manager-display-block' : 'pn-customers-manager-display-none'; ?>">
				<div class="pn-customers-manager-loader-circle-waiting"><div></div><div></div><div></div><div></div></div>
			</div>
		<?php
  }

  /**
	 * Load popup loader.
	 *
	 * @since    1.0.0
	 */
	public static function PN_CUSTOMERS_MANAGER_popup_loader() {
		?>
			<div class="pn-customers-manager-popup-content">
				<div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>
			</div>
		<?php
	}
}