<?php
/**
 * Customers Manager PN Custom Selector.
 *
 * A custom select plugin with multiple selection and search capabilities.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) {
    exit;
}

class PN_CUSTOMERS_MANAGER_Selector {
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
            'pn-customers-manager-selector',
            plugin_dir_url(__FILE__) . 'assets/css/pn-customers-manager-selector.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'pn-customers-manager-selector',
            plugin_dir_url(__FILE__) . 'assets/js/pn-customers-manager-selector.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('pn-customers-manager-selector', 'PN_CUSTOMERS_MANAGER_Selector', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pn-customers-manager-selector-nonce')
        ));
    }
}