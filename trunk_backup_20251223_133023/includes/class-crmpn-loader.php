<?php
/**
 * Register all actions, filters and shortcodes for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout the plugin, and register them with the WordPress API. Call the run function to execute the list of actions, filters and shortcodes.
 * 
 * Usage:
 * - $this->loader = new CUSTOMERS_MANAGER_PN_Loader();
 * - $this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');
 * 
 * @link       padresenlanube.com/
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Loader {
	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	/**
	 * The array of shortcodes registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $shortcodes    The shortcodes registered with WordPress to fire when the plugin loads.
	 */
	protected $shortcodes ;

	/**
	 * Initialize the collections used to maintain the actions, filters and shortcodes.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->actions = [];
		$this->filters = [];
		$this->shortcodes = [];
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 * @param    string               $hook             The name of the WordPress action that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the action is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function customers_manager_pn_add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
		$this->actions = $this->customers_manager_pn_add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 * @param    string               $hook             The name of the WordPress filter that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1
	 */
	public function customers_manager_pn_add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
		$this->filters = $this->customers_manager_pn_add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
	}

	/**
	 * Add a new shortcode to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 * @param    string               $hook             The name of the WordPress shortcode that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the shortcode is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1
	 */
	public function customers_manager_pn_add_shortcode($hook, $component, $callback, $priority = 0, $accepted_args = 0) {
		$this->shortcodes = $this->customers_manager_pn_add($this->shortcodes, $hook, $component, $callback, $priority, $accepted_args);
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single collection.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array                $hooks            The collection of hooks that is being registered (that is, actions or filters).
	 * @param    string               $hook             The name of the WordPress filter that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         The priority at which the function should be fired.
	 * @param    int                  $accepted_args    The number of arguments that should be passed to the $callback.
	 * @return   array                                  The collection of actions, filters and shortcodes registered with WordPress.
	 */
	private function customers_manager_pn_add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
		$hooks[] = [
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args
		];

		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function customers_manager_pn_run() {
		foreach ($this->filters as $hook) {
			add_filter($hook['hook'], [$hook['component'], $hook['callback']], $hook['priority'], $hook['accepted_args']);
		}

		foreach ($this->actions as $hook) {
			add_action($hook['hook'], [$hook['component'], $hook['callback']], $hook['priority'], $hook['accepted_args']);
		}

		foreach ($this->shortcodes as $hook) {
			add_shortcode($hook['hook'], [$hook['component'], $hook['callback']]);
		}
	}
}