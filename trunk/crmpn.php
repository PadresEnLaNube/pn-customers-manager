<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin admin area. This file also includes all of the dependencies used by the plugin, registers the activation and deactivation functions, and defines a function that starts the plugin.
 *
 * @link              padresenlanube.com/
 * @since             1.0.0
 * @package           CRMPN
 *
 * @wordpress-plugin
 * Plugin Name:       Customers Manager - PN
 * Plugin URI:        https://padresenlanube.com/plugins/crmpn/
 * Description:       Manage your tasks and time tracking with this plugin. Create tasks, assign them to users, and track the time spent on each task.
 * Version:           1.0.0
 * Requires at least: 3.0
 * Requires PHP:      7.2
 * Author:            Padres en la Nube
 * Author URI:        https://padresenlanube.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       crmpn
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
define('CRMPN_VERSION', '1.0.0');
define('CRMPN_DIR', plugin_dir_path(__FILE__));
define('CRMPN_URL', plugin_dir_url(__FILE__));
define('CRMPN_CPTS', [
	'crmpn_funnel' => 'Funnel',
	'crmpn_organization' => 'Organization',
	'crmpn_organization' => 'Organization',
	'crmpn_form' => 'Form',
]);

/**
 * Plugin role capabilities
 */
$crmpn_role_cpt_capabilities = [];

foreach (CRMPN_CPTS as $cpt_key => $cpt_value) {
	$crmpn_role_cpt_capabilities[$cpt_key] = [
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
	
	define('CRMPN_ROLE_' . strtoupper($cpt_key) . '_CAPABILITIES', $crmpn_role_cpt_capabilities[$cpt_key]);
}

/**
 * Plugin KSES allowed HTML elements and attributes
 */
$crmpn_kses = [
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
		'data-crmpn-ajax-type' => [],
		'data-crmpn-popup-id' => [],
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
		'data-crmpn-parent' => [],
		'data-crmpn-parent-option' => [],
		'data-crmpn-type' => [],
		'data-crmpn-subtype' => [],
		'data-crmpn-user-id' => [],
		'data-crmpn-post-id' => [],
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
		'data-crmpn-parent' => [],
		'data-crmpn-parent-option' => [],
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
		'data-crmpn-parent' => [],
		'data-crmpn-parent-option' => [],
	],
	'label' => [
		'id' => [],
		'class' => [],
		'for' => [],
	],
];

foreach (CRMPN_CPTS as $cpt_key => $cpt_value) {
	$custom_data_attr = 'data-' . $cpt_key . '-id';
	$crmpn_kses['li'][$custom_data_attr] = [];
	$crmpn_kses['a'][$custom_data_attr] = [];
}

// Now define the constant with the complete array
define('CRMPN_KSES', $crmpn_kses);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-crmpn-activator.php
 */
function crmpn_activation_hook() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-crmpn-activator.php';
	CRMPN_Activator::crmpn_activate();
	
	// Clear any previous state
	delete_option('crmpn_redirecting');
	
	// Set transient only if it doesn't exist
	if (!get_transient('crmpn_just_activated')) {
		set_transient('crmpn_just_activated', true, 30);
	}
}

// Register activation hook
register_activation_hook(__FILE__, 'crmpn_activation_hook');

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-crmpn-deactivator.php
 */
function crmpn_deactivation_cleanup() {
	delete_option('crmpn_redirecting');
}
register_deactivation_hook(__FILE__, 'crmpn_deactivation_cleanup');

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
function crmpn_run() {
	$plugin = new CRMPN();
	$plugin->crmpn_run();
}

// Initialize the plugin on init hook instead of plugins_loaded
add_action('init', 'crmpn_run', 0);