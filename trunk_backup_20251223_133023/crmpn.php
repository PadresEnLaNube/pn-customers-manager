<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin admin area. This file also includes all of the dependencies used by the plugin, registers the activation and deactivation functions, and defines a function that starts the plugin.
 *
 * @link              padresenlanube.com/
 * @since             1.0.0
 * @package           CUSTOMERS_MANAGER_PN
 *
 * @wordpress-plugin
 * Plugin Name:       PN Customers Manager
 * Plugin URI:        https://padresenlanube.com/plugins/customers-manager-pn/
 * Description:       Manage your tasks and time tracking with this plugin. Create tasks, assign them to users, and track the time spent on each task.
 * Version:           1.0.0
 * Requires at least: 3.0
 * Requires PHP:      7.2
 * Author:            Padres en la Nube
 * Author URI:        https://padresenlanube.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       customers-manager-pn
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('CUSTOMERS_MANAGER_PN_VERSION', '1.0.0');
define('CUSTOMERS_MANAGER_PN_DIR', plugin_dir_path(__FILE__));
define('CUSTOMERS_MANAGER_PN_URL', plugin_dir_url(__FILE__));
define('CUSTOMERS_MANAGER_PN_CPTS', [
	'customers_manager_pn_funnel' => 'Funnel',
	'customers_manager_pn_organization' => 'Organization',
	'customers_manager_pn_organization' => 'Organization',
	'customers_manager_pn_form' => 'Form',
]);

/**
 * Plugin role capabilities
 */
$customers_manager_pn_role_cpt_capabilities = [];

foreach (CUSTOMERS_MANAGER_PN_CPTS as $cpt_key => $cpt_value) {
	$customers_manager_pn_role_cpt_capabilities[$cpt_key] = [
		'edit_post' 				=> 'edit_' . $cpt_key,
		'edit_posts' 				=> 'edit_' . $cpt_key,
		'edit_private_posts' 		=> 'edit_private_' . $cpt_key,
		'edit_published_posts' 		=> 'edit_published_' . $cpt_key,
		'edit_others_posts' 		=> 'edit_others_' . $cpt_key,
		'publish_posts' 			=> 'publish_' . $cpt_key,

		// Post reading capabilities
		'read_post' 				=> 'read_' . $cpt_key,
		'read_private_posts' 		=> 'read_private_' . $cpt_key,
		
		// Post deletion capabilities
		'delete_post' 				=> 'delete_' . $cpt_key,
		'delete_posts' 				=> 'delete_' . $cpt_key,
		'delete_private_posts' 		=> 'delete_private_' . $cpt_key,
		'delete_published_posts' 	=> 'delete_published_' . $cpt_key,
		'delete_others_posts'		=> 'delete_others_' . $cpt_key,

		// Media capabilities
		'upload_files' 				=> 'upload_files',

		// Taxonomy capabilities
		'manage_terms' 				=> 'manage_' . $cpt_key . '_category',
		'edit_terms' 				=> 'edit_' . $cpt_key . '_category',
		'delete_terms' 				=> 'delete_' . $cpt_key . '_category',
		'assign_terms' 				=> 'assign_' . $cpt_key . '_category',

		// Options capabilities
		'manage_options' 			=> 'manage_' . $cpt_key . '_options'
	];
	
	define('CUSTOMERS_MANAGER_PN_ROLE_' . strtoupper($cpt_key) . '_CAPABILITIES', $customers_manager_pn_role_cpt_capabilities[$cpt_key]);
}

/**
 * Plugin KSES allowed HTML elements and attributes
 */
$customers_manager_pn_kses = [
	// Basic text elements
	'div' => ['id' => [], 'class' => []],
	'section' => ['id' => [], 'class' => []],
	'article' => ['id' => [], 'class' => []],
	'aside' => ['id' => [], 'class' => []],
	'footer' => ['id' => [], 'class' => []],
	'header' => ['id' => [], 'class' => []],
	'main' => ['id' => [], 'class' => []],
	'nav' => ['id' => [], 'class' => []],
	'p' => ['id' => [], 'class' => []],
	'span' => ['id' => [], 'class' => []],
	'small' => ['id' => [], 'class' => []],
	'em' => [],
	'strong' => [],
	'br' => [],

	// Headings
	'h1' => ['id' => [], 'class' => []],
	'h2' => ['id' => [], 'class' => []],
	'h3' => ['id' => [], 'class' => []],
	'h4' => ['id' => [], 'class' => []],
	'h5' => ['id' => [], 'class' => []],
	'h6' => ['id' => [], 'class' => []],

	// Lists
	'ul' => ['id' => [], 'class' => []],
	'ol' => ['id' => [], 'class' => []],
	'li' => [
		'id' => [],
		'class' => [],
	],

	// Links and media
	'a' => [
		'id' => [],
		'class' => [],
		'href' => [],
		'title' => [],
		'target' => [],
		'data-customers-manager-pn-ajax-type' => [],
		'data-customers-manager-pn-popup-id' => [],
	],
	'img' => [
		'id' => [],
		'class' => [],
		'src' => [],
		'alt' => [],
		'title' => [],
	],
	'i' => [
		'id' => [], 
		'class' => [], 
		'title' => []
	],

	// Forms and inputs
	'form' => [
		'id' => [],
		'class' => [],
		'action' => [],
		'method' => [],
	],
	'input' => [
		'name' => [],
		'id' => [],
		'class' => [],
		'type' => [],
		'checked' => [],
		'multiple' => [],
		'disabled' => [],
		'value' => [],
		'placeholder' => [],
		'data-customers-manager-pn-parent' => [],
		'data-customers-manager-pn-parent-option' => [],
		'data-customers-manager-pn-type' => [],
		'data-customers-manager-pn-subtype' => [],
		'data-customers-manager-pn-user-id' => [],
		'data-customers-manager-pn-post-id' => [],
	],
	'select' => [
		'name' => [],
		'id' => [],
		'class' => [],
		'type' => [],
		'checked' => [],
		'multiple' => [],
		'disabled' => [],
		'value' => [],
		'placeholder' => [],
		'data-placeholder' => [],
		'data-customers-manager-pn-parent' => [],
		'data-customers-manager-pn-parent-option' => [],
	],
	'option' => [
		'name' => [],
		'id' => [],
		'class' => [],
		'disabled' => [],
		'selected' => [],
		'value' => [],
		'placeholder' => [],
	],
	'textarea' => [
		'name' => [],
		'id' => [],
		'class' => [],
		'type' => [],
		'multiple' => [],
		'disabled' => [],
		'value' => [],
		'placeholder' => [],
		'data-customers-manager-pn-parent' => [],
		'data-customers-manager-pn-parent-option' => [],
	],
	'label' => [
		'id' => [],
		'class' => [],
		'for' => [],
	],
];

foreach (CUSTOMERS_MANAGER_PN_CPTS as $cpt_key => $cpt_value) {
	$custom_data_attr = 'data-' . $cpt_key . '-id';
	$customers_manager_pn_kses['li'][$custom_data_attr] = [];
	$customers_manager_pn_kses['a'][$custom_data_attr] = [];
}

// Now define the constant with the complete array
define('CUSTOMERS_MANAGER_PN_KSES', $customers_manager_pn_kses);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-customers-manager-pn-activator.php
 */
function customers_manager_pn_activation_hook() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-customers-manager-pn-activator.php';
	CUSTOMERS_MANAGER_PN_Activator::customers_manager_pn_activate();
	
	// Clear any previous state
	delete_option('customers_manager_pn_redirecting');
	
	// Set transient only if it doesn't exist
	if (!get_transient('customers_manager_pn_just_activated')) {
		set_transient('customers_manager_pn_just_activated', true, 30);
	}
}

// Register activation hook
register_activation_hook(__FILE__, 'customers_manager_pn_activation_hook');

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-customers-manager-pn-deactivator.php
 */
function customers_manager_pn_deactivation_cleanup() {
	delete_option('customers_manager_pn_redirecting');
}
register_deactivation_hook(__FILE__, 'customers_manager_pn_deactivation_cleanup');

/**
 * The core plugin class that is used to define internationalization, admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-crmpn.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, then kicking off the plugin from this point in the file does not affect the page life cycle.
 *
 * @since    1.0.0
 */
function customers_manager_pn_run() {
	$plugin = new CUSTOMERS_MANAGER_PN();
	$plugin->customers_manager_pn_run();
}

// Initialize the plugin on init hook instead of plugins_loaded
add_action('init', 'customers_manager_pn_run', 0);