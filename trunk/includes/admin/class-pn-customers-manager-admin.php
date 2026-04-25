<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/admin
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Admin {

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
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_enqueue_styles() {
		wp_enqueue_style($this->plugin_name . '-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/css/admin/pn-customers-manager-admin.css', [], $this->version, 'all');
		wp_enqueue_style($this->plugin_name . '-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-budget.css', [], $this->version, 'all');
		wp_enqueue_style($this->plugin_name . '-invoice', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-invoice.css', [], $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_enqueue_scripts() {
		wp_enqueue_media();
		wp_enqueue_script($this->plugin_name . '-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-admin.js', ['jquery'], $this->version, false);

		wp_enqueue_script($this->plugin_name . '-budget-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-budget-admin.js', ['jquery'], $this->version, true);
		wp_localize_script($this->plugin_name . '-budget-admin', 'pnCmBudgetAdmin', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('pn-customers-manager-nonce'),
			'budgetId' => 0,
			'currencySymbol' => get_option('pn_customers_manager_budget_currency_symbol', '€'),
			'currencyPosition' => get_option('pn_customers_manager_budget_currency_position', 'after'),
			'defaultHourlyRate' => get_option('pn_customers_manager_budget_default_hourly_rate', '0'),
			'i18n' => [
				'error' => esc_html__('An error occurred.', 'pn-customers-manager'),
				'confirmDelete' => esc_html__('Are you sure you want to delete this item?', 'pn-customers-manager'),
				'confirmSend' => esc_html__('Are you sure you want to send this budget?', 'pn-customers-manager'),
				'budgetSent' => esc_html__('Budget sent successfully.', 'pn-customers-manager'),
				'noDescription' => esc_html__('Please enter a description.', 'pn-customers-manager'),
			'confirmGenerateInvoice' => esc_html__('Are you sure you want to generate an invoice from this budget?', 'pn-customers-manager'),
			'invoiceGenerated' => esc_html__('Invoice generated successfully.', 'pn-customers-manager'),
			'selectImage' => esc_html__('Select image', 'pn-customers-manager'),
			'useImage' => esc_html__('Use image', 'pn-customers-manager'),
			],
		]);

		wp_enqueue_script($this->plugin_name . '-invoice-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-invoice-admin.js', ['jquery'], $this->version, true);
		wp_localize_script($this->plugin_name . '-invoice-admin', 'pnCmInvoiceAdmin', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('pn-customers-manager-nonce'),
			'invoiceId' => 0,
			'currencySymbol' => get_option('pn_customers_manager_budget_currency_symbol', '€'),
			'currencyPosition' => get_option('pn_customers_manager_budget_currency_position', 'after'),
			'defaultHourlyRate' => get_option('pn_customers_manager_budget_default_hourly_rate', '0'),
			'i18n' => [
				'error' => esc_html__('An error occurred.', 'pn-customers-manager'),
				'confirmDelete' => esc_html__('Are you sure you want to delete this item?', 'pn-customers-manager'),
				'confirmSend' => esc_html__('Are you sure you want to send this invoice?', 'pn-customers-manager'),
				'invoiceSent' => esc_html__('Invoice sent successfully.', 'pn-customers-manager'),
				'invoiceRemoved' => esc_html__('Invoice removed successfully.', 'pn-customers-manager'),
				'invoiceDuplicated' => esc_html__('Invoice duplicated successfully.', 'pn-customers-manager'),
				'noDescription' => esc_html__('Please enter a description.', 'pn-customers-manager'),
				'newPhase' => esc_html__('New phase', 'pn-customers-manager'),
			],
		]);
	}
}
