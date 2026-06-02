<?php
/**
 * WooCommerce Integration Manager
 *
 * Loads WooCommerce features only when enabled in settings and WooCommerce is active
 *
 * @link       padresenlanube.com/
 * @since      1.1.75
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce {

	private static $instance = null;

	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Only load if enabled AND WooCommerce is active
		if ($this->is_enabled() && class_exists('WooCommerce')) {
			$this->load_components();
		}
	}

	/**
	 * Check if WooCommerce integration is enabled in settings
	 *
	 * @return bool
	 */
	private function is_enabled() {
		return get_option('pn_customers_manager_woocommerce_enabled') === 'on';
	}

	/**
	 * Load WooCommerce integration components
	 */
	private function load_components() {
		// Load iCal classes first (dependency for Calendar Booking Products)
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-ical.php';
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-ical-download.php';

		// Load Calendar Booking Products
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-calendar-booking.php';

		// Initialize Calendar Booking Products
		PN_CUSTOMERS_MANAGER_WooCommerce_Calendar_Booking::instance();

		// Initialize iCal download handler
		new PN_CUSTOMERS_MANAGER_WooCommerce_iCal_Download();

		// Load Checkout Custom Fields
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-checkout-fields.php';
		PN_CUSTOMERS_MANAGER_WooCommerce_Checkout_Fields::instance();

		// Load WooCommerce Accounting Summary
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-accounting.php';
		PN_CUSTOMERS_MANAGER_WooCommerce_Accounting::instance();

		// Load QR Tickets (if enabled)
		$qr_enabled = get_option('pn_customers_manager_woocommerce_qr_enabled', 'NOT_SET');
		error_log('PN_CM_WooCommerce: QR option value = ' . $qr_enabled);

		if ($qr_enabled === 'on') {
			error_log('PN_CM_WooCommerce: Loading QR class...');
			require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce-qr.php';
			PN_CUSTOMERS_MANAGER_WooCommerce_QR::instance();
			error_log('PN_CM_WooCommerce: QR class loaded and initialized');
		} else {
			error_log('PN_CM_WooCommerce: QR NOT loaded - option not enabled');
		}
	}
}
