<?php
/**
 * Customers Manager PN Custom Selector.
 *
 * A custom select plugin with multiple selection and search capabilities.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) {
    exit;
}

class CUSTOMERS_MANAGER_PN_Selector {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'customers-manager-pn-selector',
            plugin_dir_url(__FILE__) . 'assets/css/customers-manager-pn-selector.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'customers-manager-pn-selector',
            plugin_dir_url(__FILE__) . 'assets/js/customers-manager-pn-selector.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('customers-manager-pn-selector', 'CUSTOMERS_MANAGER_PN_Selector', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('customers-manager-pn-selector-nonce')
        ));
    }
}