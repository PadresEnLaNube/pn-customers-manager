<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin so that it is ready for translation.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Data {
	/**
	 * The main data array.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      CUSTOMERS_MANAGER_PN_Data    $data    Empty array.
	 */
	protected $data = [];

	/**
	 * Load the plugin most usefull data.
	 *
	 * @since    1.0.0
	 */
	public function customers_manager_pn_load_plugin_data() {
		$this->data['user_id'] = get_current_user_id();

		if (is_admin()) {
			$this->data['post_id'] = !empty($GLOBALS['_REQUEST']['post']) ? $GLOBALS['_REQUEST']['post'] : 0;
		} else {
			$this->data['post_id'] = get_the_ID();
		}

		$GLOBALS['customers_manager_pn_data'] = $this->data;
	}

	/**
	 * Flush wp rewrite rules.
	 *
	 * @since    1.0.0
	 */
	public function customers_manager_pn_flush_rewrite_rules() {
    if (get_option('customers_manager_pn_options_changed')) {
      flush_rewrite_rules();
      update_option('customers_manager_pn_options_changed', false);
    }
  }

  /**
	 * Gets the mini loader.
	 *
	 * @since    1.0.0
	 */
	public static function customers_manager_pn_loader($display = false) {
		?>
			<div class="customers-manager-pn-waiting <?php echo ($display) ? 'customers-manager-pn-display-block' : 'customers-manager-pn-display-none'; ?>">
				<div class="customers-manager-pn-loader-circle-waiting"><div></div><div></div><div></div><div></div></div>
			</div>
		<?php
  }

  /**
	 * Load popup loader.
	 *
	 * @since    1.0.0
	 */
	public static function customers_manager_pn_popup_loader() {
		?>
			<div class="customers-manager-pn-popup-content">
				<div class="customers-manager-pn-loader-circle-wrapper"><div class="customers-manager-pn-text-align-center"><div class="customers-manager-pn-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>
			</div>
		<?php
	}
}