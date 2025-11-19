<?php
/**
 * CRMPN Custom Selector.
 *
 * A custom select plugin with multiple selection and search capabilities.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRMPN_Selector {
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
            'crmpn-selector',
            plugin_dir_url(__FILE__) . 'assets/css/crmpn-selector.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'crmpn-selector',
            plugin_dir_url(__FILE__) . 'assets/js/crmpn-selector.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('crmpn-selector', 'CRMPN_Selector', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crmpn-selector-nonce')
        ));
    }
}