<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current version of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */

class CRMPN {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      CRMPN_Loader    $crmpn_loader    Maintains and registers all hooks for the plugin.
	 */
	protected $crmpn_loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $crmpn_plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $crmpn_plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $crmpn_version    The current version of the plugin.
	 */
	protected $crmpn_version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin. Load the dependencies, define the locale, and set the hooks for the admin area and the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if (defined('CRMPN_VERSION')) {
			$this->crmpn_version = CRMPN_VERSION;
		} else {
			$this->crmpn_version = '1.0.0';
		}

		$this->crmpn_plugin_name = 'crmpn';

		self::crmpn_load_dependencies();
		self::crmpn_load_i18n();
		self::crmpn_define_common_hooks();
		self::crmpn_define_admin_hooks();
		self::crmpn_define_public_hooks();
		self::crmpn_define_custom_post_types();
		self::crmpn_define_taxonomies();
		self::crmpn_load_ajax();
		self::crmpn_load_ajax_nopriv();
		self::crmpn_load_data();
		self::crmpn_load_templates();
		self::crmpn_load_settings();
		self::crmpn_load_shortcodes();
	}
			
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 * - CRMPN_Loader. Orchestrates the hooks of the plugin.
	 * - CRMPN_i18n. Defines internationalization functionality.
	 * - CRMPN_Common. Defines hooks used accross both, admin and public side.
	 * - CRMPN_Admin. Defines all hooks for the admin area.
	 * - CRMPN_Public. Defines all hooks for the public side of the site.
	 * - CRMPN_Post_Type_Funnel. Defines Funnel custom post type.
	 * - CRMPN_Taxonomies_Funnel. Defines Funnel taxonomies.
	 * - CRMPN_Templates. Load plugin templates.
	 * - CRMPN_Data. Load main usefull data.
	 * - CRMPN_Functions_Post. Posts management functions.
	 * - CRMPN_Functions_User. Users management functions.
	 * - CRMPN_Functions_Attachment. Attachments management functions.
	 * - CRMPN_Functions_Settings. Define settings.
	 * - CRMPN_Functions_Forms. Forms management functions.
	 * - CRMPN_Functions_Ajax. Ajax functions.
	 * - CRMPN_Functions_Ajax_Nopriv. Ajax No Private functions.
	 * - CRMPN_Popups. Define popups functionality.
	 * - CRMPN_Functions_Shortcodes. Define all shortcodes for the platform.
	 * - CRMPN_Functions_Validation. Define validation and sanitization.
	 *
	 * Create an instance of the loader which will be used to register the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-loader.php';

		/**
		 * The class responsible for defining internationalization functionality of the plugin.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-i18n.php';

		/**
		 * The class responsible for defining all actions that occur both in the admin area and in the public-facing side of the site.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-common.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once CRMPN_DIR . 'includes/admin/class-crmpn-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once CRMPN_DIR . 'includes/public/class-crmpn-public.php';

		/**
		 * The class responsible for create the Funnel custom post type.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-post-type-funnel.php';

		/**
		 * The class responsible for create the Funnel custom taxonomies.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-taxonomies-funnel.php';

		/**
		 * The class responsible for plugin templates.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-templates.php';

		/**
		 * The class getting key data of the platform.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-data.php';

		/**
		 * The class defining posts management functions.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-functions-post.php';

		/**
		 * The class defining users management functions.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-functions-user.php';

		/**
		 * The class defining attahcments management functions.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-functions-attachment.php';

		/**
		 * The class defining settings.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-settings.php';

		/**
		 * The class defining form management.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-forms.php';

		/**
		 * The class defining ajax functions.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-ajax.php';

		/**
		 * The class defining no private ajax functions.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-ajax-nopriv.php';

		/**
		 * The class defining shortcodes.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-shortcodes.php';

		/**
		 * The class defining validation and sanitization.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-validation.php';

		/**
		 * The class responsible for popups functionality.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-popups.php';

		/**
		 * The class managing the custom selector component.
		 */
		require_once CRMPN_DIR . 'includes/class-crmpn-selector.php';

		$this->crmpn_loader = new CRMPN_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the CRMPN_i18n class in order to set the domain and to register the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_load_i18n() {
		$plugin_i18n = new CRMPN_i18n();
		$this->crmpn_loader->crmpn_add_action('after_setup_theme', $plugin_i18n, 'crmpn_load_plugin_textdomain');

		if (class_exists('Polylang')) {
			$this->crmpn_loader->crmpn_add_filter('pll_get_post_types', $plugin_i18n, 'crmpn_pll_get_post_types', 10, 2);
    }
	}

	/**
	 * Register all of the hooks related to the main functionalities of the plugin, common to public and admin faces.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_define_common_hooks() {
		$plugin_common = new CRMPN_Common(self::crmpn_get_plugin_name(), self::crmpn_get_version());
		$this->crmpn_loader->crmpn_add_action('wp_enqueue_scripts', $plugin_common, 'crmpn_enqueue_styles');
		$this->crmpn_loader->crmpn_add_action('wp_enqueue_scripts', $plugin_common, 'crmpn_enqueue_scripts');
		$this->crmpn_loader->crmpn_add_action('admin_enqueue_scripts', $plugin_common, 'crmpn_enqueue_styles');
		$this->crmpn_loader->crmpn_add_action('admin_enqueue_scripts', $plugin_common, 'crmpn_enqueue_scripts');
		$this->crmpn_loader->crmpn_add_filter('body_class', $plugin_common, 'crmpn_body_classes');

		$plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
		$this->crmpn_loader->crmpn_add_action('crmpn_funnel_form_save', $plugin_post_type_funnel, 'crmpn_funnel_form_save', 999, 5);
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_define_admin_hooks() {
		$plugin_admin = new CRMPN_Admin(self::crmpn_get_plugin_name(), self::crmpn_get_version());
		$this->crmpn_loader->crmpn_add_action('admin_enqueue_scripts', $plugin_admin, 'crmpn_enqueue_styles');
		$this->crmpn_loader->crmpn_add_action('admin_enqueue_scripts', $plugin_admin, 'crmpn_enqueue_scripts');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_define_public_hooks() {
		$plugin_public = new CRMPN_Public(self::crmpn_get_plugin_name(), self::crmpn_get_version());
		$this->crmpn_loader->crmpn_add_action('wp_enqueue_scripts', $plugin_public, 'crmpn_enqueue_styles');
		$this->crmpn_loader->crmpn_add_action('wp_enqueue_scripts', $plugin_public, 'crmpn_enqueue_scripts');

		$plugin_user = new CRMPN_Functions_User();
		$this->crmpn_loader->crmpn_add_action('wp_login', $plugin_user, 'crmpn_user_wp_login');
	}

	/**
	 * Register all Post Types with meta boxes and templates.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_define_custom_post_types() {
		$plugin_post_type_funnel = new CRMPN_Post_Type_Funnel();
		$this->crmpn_loader->crmpn_add_action('init', $plugin_post_type_funnel, 'crmpn_funnel_register_post_type');
		$this->crmpn_loader->crmpn_add_action('admin_init', $plugin_post_type_funnel, 'crmpn_funnel_add_meta_box');
		$this->crmpn_loader->crmpn_add_action('save_post_crmpn_funnel', $plugin_post_type_funnel, 'crmpn_funnel_save_post', 10, 3);
		$this->crmpn_loader->crmpn_add_filter('single_template', $plugin_post_type_funnel, 'crmpn_funnel_single_template', 10, 3);
		$this->crmpn_loader->crmpn_add_filter('archive_template', $plugin_post_type_funnel, 'crmpn_funnel_archive_template', 10, 3);
		$this->crmpn_loader->crmpn_add_shortcode('crmpn-funnel-list', $plugin_post_type_funnel, 'crmpn_funnel_list_wrapper');
	}

	/**
	 * Register all of the hooks related to Taxonomies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_define_taxonomies() {
		$plugin_taxonomies_funnel = new CRMPN_Taxonomies_Funnel();
		$this->crmpn_loader->crmpn_add_action('init', $plugin_taxonomies_funnel, 'crmpn_register_taxonomies');
	}

	/**
	 * Load most common data used on the platform.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_load_data() {
		$plugin_data = new CRMPN_Data();

		if (is_admin()) {
			$this->crmpn_loader->crmpn_add_action('init', $plugin_data, 'crmpn_load_plugin_data');
		} else {
			$this->crmpn_loader->crmpn_add_action('wp_head', $plugin_data, 'crmpn_load_plugin_data');
		}

		$this->crmpn_loader->crmpn_add_action('wp_footer', $plugin_data, 'crmpn_flush_rewrite_rules');
		$this->crmpn_loader->crmpn_add_action('admin_footer', $plugin_data, 'crmpn_flush_rewrite_rules');
	}

	/**
	 * Register templates.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_load_templates() {
		if (!defined('DOING_AJAX')) {
			$plugin_templates = new CRMPN_Templates();
			$this->crmpn_loader->crmpn_add_action('wp_footer', $plugin_templates, 'load_plugin_templates');
			$this->crmpn_loader->crmpn_add_action('admin_footer', $plugin_templates, 'load_plugin_templates');
		}
	}

	/**
	 * Register settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_load_settings() {
		$plugin_settings = new CRMPN_Settings();
		$this->crmpn_loader->crmpn_add_action('admin_menu', $plugin_settings, 'crmpn_admin_menu');
		$this->crmpn_loader->crmpn_add_action('activated_plugin', $plugin_settings, 'crmpn_activated_plugin');
		$this->crmpn_loader->crmpn_add_action('admin_init', $plugin_settings, 'crmpn_check_activation');
		$this->crmpn_loader->crmpn_add_filter('plugin_action_links_crmpn/crmpn.php', $plugin_settings, 'crmpn_plugin_action_links');
	}

	/**
	 * Load ajax functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_load_ajax() {
		$plugin_ajax = new CRMPN_Ajax();
		$this->crmpn_loader->crmpn_add_action('wp_ajax_crmpn_ajax', $plugin_ajax, 'crmpn_ajax_server');
	}

	/**
	 * Load no private ajax functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_load_ajax_nopriv() {
		$plugin_ajax_nopriv = new CRMPN_Ajax_Nopriv();
		$this->crmpn_loader->crmpn_add_action('wp_ajax_crmpn_ajax_nopriv', $plugin_ajax_nopriv, 'crmpn_ajax_nopriv_server');
		$this->crmpn_loader->crmpn_add_action('wp_ajax_nopriv_crmpn_ajax_nopriv', $plugin_ajax_nopriv, 'crmpn_ajax_nopriv_server');
	}

	/**
	 * Register shortcodes of the platform.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function crmpn_load_shortcodes() {
		$plugin_shortcodes = new CRMPN_Shortcodes();
		$this->crmpn_loader->crmpn_add_shortcode('crmpn-funnel', $plugin_shortcodes, 'crmpn_funnel');
		$this->crmpn_loader->crmpn_add_shortcode('crmpn-test', $plugin_shortcodes, 'crmpn_test');
		$this->crmpn_loader->crmpn_add_shortcode('crmpn-call-to-action', $plugin_shortcodes, 'crmpn_call_to_action');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress. Then it flushes the rewrite rules if needed.
	 *
	 * @since    1.0.0
	 */
	public function crmpn_run() {
		$this->crmpn_loader->crmpn_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function crmpn_get_plugin_name() {
		return $this->crmpn_plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    CRMPN_Loader    Orchestrates the hooks of the plugin.
	 */
	public function crmpn_get_loader() {
		return $this->crmpn_loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function crmpn_get_version() {
		return $this->crmpn_version;
	}
}