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
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

class PN_CUSTOMERS_MANAGER {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      PN_CUSTOMERS_MANAGER_Loader    $PN_CUSTOMERS_MANAGER_loader    Maintains and registers all hooks for the plugin.
	 */
	protected $PN_CUSTOMERS_MANAGER_loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $PN_CUSTOMERS_MANAGER_plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $PN_CUSTOMERS_MANAGER_plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $PN_CUSTOMERS_MANAGER_version    The current version of the plugin.
	 */
	protected $PN_CUSTOMERS_MANAGER_version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin. Load the dependencies, define the locale, and set the hooks for the admin area and the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if (defined('PN_CUSTOMERS_MANAGER_VERSION')) {
			$this->PN_CUSTOMERS_MANAGER_version = PN_CUSTOMERS_MANAGER_VERSION;
		} else {
			$this->PN_CUSTOMERS_MANAGER_version = '1.0.0';
		}

		$this->PN_CUSTOMERS_MANAGER_plugin_name = 'pn-customers-manager';

		self::PN_CUSTOMERS_MANAGER_load_dependencies();
		self::PN_CUSTOMERS_MANAGER_load_i18n();
		self::PN_CUSTOMERS_MANAGER_define_common_hooks();
		self::PN_CUSTOMERS_MANAGER_define_admin_hooks();
		self::PN_CUSTOMERS_MANAGER_define_public_hooks();
		self::PN_CUSTOMERS_MANAGER_define_custom_post_types();
		self::PN_CUSTOMERS_MANAGER_define_taxonomies();
		self::PN_CUSTOMERS_MANAGER_load_ajax();
		self::PN_CUSTOMERS_MANAGER_load_ajax_nopriv();
		self::PN_CUSTOMERS_MANAGER_load_data();
		self::PN_CUSTOMERS_MANAGER_load_templates();
		self::PN_CUSTOMERS_MANAGER_load_settings();
		self::PN_CUSTOMERS_MANAGER_load_shortcodes();
		self::PN_CUSTOMERS_MANAGER_define_client_hooks();
	}
			
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 * - PN_CUSTOMERS_MANAGER_Loader. Orchestrates the hooks of the plugin.
	 * - PN_CUSTOMERS_MANAGER_i18n. Defines internationalization functionality.
	 * - PN_CUSTOMERS_MANAGER_Common. Defines hooks used accross both, admin and public side.
	 * - PN_CUSTOMERS_MANAGER_Admin. Defines all hooks for the admin area.
	 * - PN_CUSTOMERS_MANAGER_Public. Defines all hooks for the public side of the site.
	 * - PN_CUSTOMERS_MANAGER_Post_Type_Funnel. Defines Funnel custom post type.
	 * - PN_CUSTOMERS_MANAGER_Taxonomies_Funnel. Defines Funnel taxonomies.
	 * - PN_CUSTOMERS_MANAGER_Templates. Load plugin templates.
	 * - PN_CUSTOMERS_MANAGER_Data. Load main usefull data.
	 * - PN_CUSTOMERS_MANAGER_Functions_Post. Posts management functions.
	 * - PN_CUSTOMERS_MANAGER_Functions_User. Users management functions.
	 * - PN_CUSTOMERS_MANAGER_Functions_Attachment. Attachments management functions.
	 * - PN_CUSTOMERS_MANAGER_Functions_Settings. Define settings.
	 * - PN_CUSTOMERS_MANAGER_Functions_Forms. Forms management functions.
	 * - PN_CUSTOMERS_MANAGER_Functions_Ajax. Ajax functions.
	 * - PN_CUSTOMERS_MANAGER_Functions_Ajax_Nopriv. Ajax No Private functions.
	 * - PN_CUSTOMERS_MANAGER_Popups. Define popups functionality.
	 * - PN_CUSTOMERS_MANAGER_Functions_Shortcodes. Define all shortcodes for the platform.
	 * - PN_CUSTOMERS_MANAGER_Functions_Validation. Define validation and sanitization.
	 *
	 * Create an instance of the loader which will be used to register the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-loader.php';

		/**
		 * The class responsible for defining internationalization functionality of the plugin.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-i18n.php';

		/**
		 * The class responsible for defining all actions that occur both in the admin area and in the public-facing side of the site.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-common.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/admin/class-pn-customers-manager-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/public/class-pn-customers-manager-public.php';

		/**
		 * The class responsible for create the Funnel custom post type.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-post-type-funnel.php';

		/**
		 * The class responsible for create the Funnel custom taxonomies.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-taxonomies-funnel.php';

		/**
		 * The class responsible for create the Organization custom post type.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-post-type-organization.php';

		/**
		 * The class responsible for plugin templates.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-templates.php';

		/**
		 * The class getting key data of the platform.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-data.php';

		/**
		 * The class defining posts management functions.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-functions-post.php';

		/**
		 * The class defining users management functions.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-functions-user.php';

		/**
		 * The class defining attahcments management functions.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-functions-attachment.php';

		/**
		 * The class defining settings.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-settings.php';

		/**
		 * The class defining form management.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-forms.php';

		/**
		 * The class defining ajax functions.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-ajax.php';

		/**
		 * The class defining no private ajax functions.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-ajax-nopriv.php';

		/**
		 * The class defining shortcodes.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-shortcodes.php';

		/**
		 * The class defining validation and sanitization.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-validation.php';

		/**
		 * Client form helper.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-client-form.php';


		/**
		 * The class responsible for popups functionality.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-popups.php';

		/**
		 * The class managing the custom selector component.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-selector.php';

		$this->PN_CUSTOMERS_MANAGER_loader = new PN_CUSTOMERS_MANAGER_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the PN_CUSTOMERS_MANAGER_i18n class in order to set the domain and to register the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_load_i18n() {
		$plugin_i18n = new PN_CUSTOMERS_MANAGER_i18n();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('after_setup_theme', $plugin_i18n, 'PN_CUSTOMERS_MANAGER_load_plugin_textdomain');

		if (class_exists('Polylang')) {
			$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_filter('pll_get_post_types', $plugin_i18n, 'PN_CUSTOMERS_MANAGER_pll_get_post_types', 10, 2);
    }
	}

	/**
	 * Register all of the hooks related to the main functionalities of the plugin, common to public and admin faces.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_define_common_hooks() {
		$plugin_common = new PN_CUSTOMERS_MANAGER_Common(self::PN_CUSTOMERS_MANAGER_get_plugin_name(), self::PN_CUSTOMERS_MANAGER_get_version());
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_enqueue_scripts', $plugin_common, 'PN_CUSTOMERS_MANAGER_enqueue_styles');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_enqueue_scripts', $plugin_common, 'PN_CUSTOMERS_MANAGER_enqueue_scripts');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_enqueue_scripts', $plugin_common, 'PN_CUSTOMERS_MANAGER_enqueue_styles');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_enqueue_scripts', $plugin_common, 'PN_CUSTOMERS_MANAGER_enqueue_scripts');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_filter('body_class', $plugin_common, 'PN_CUSTOMERS_MANAGER_body_classes');

		$plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('cm_pn_funnel_form_save', $plugin_post_type_funnel, 'cm_pn_funnel_form_save', 999, 5);

		$plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('PN_CUSTOMERS_MANAGER_org_form_save', $plugin_post_type_organization, 'cm_pn_org_form_save', 999, 5);
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_define_admin_hooks() {
		$plugin_admin = new PN_CUSTOMERS_MANAGER_Admin(self::PN_CUSTOMERS_MANAGER_get_plugin_name(), self::PN_CUSTOMERS_MANAGER_get_version());
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_enqueue_scripts', $plugin_admin, 'PN_CUSTOMERS_MANAGER_enqueue_styles');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_enqueue_scripts', $plugin_admin, 'PN_CUSTOMERS_MANAGER_enqueue_scripts');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_define_public_hooks() {
		$plugin_public = new PN_CUSTOMERS_MANAGER_Public(self::PN_CUSTOMERS_MANAGER_get_plugin_name(), self::PN_CUSTOMERS_MANAGER_get_version());
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_enqueue_scripts', $plugin_public, 'PN_CUSTOMERS_MANAGER_enqueue_styles');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_enqueue_scripts', $plugin_public, 'PN_CUSTOMERS_MANAGER_enqueue_scripts');

		$plugin_user = new PN_CUSTOMERS_MANAGER_Functions_User();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_login', $plugin_user, 'PN_CUSTOMERS_MANAGER_user_wp_login');
	}

	/**
	 * Register all Post Types with meta boxes and templates.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_define_custom_post_types() {
		$plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('init', $plugin_post_type_funnel, 'cm_pn_funnel_register_post_type');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_init', $plugin_post_type_funnel, 'cm_pn_funnel_add_meta_box');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('save_post_cm_pn_funnel', $plugin_post_type_funnel, 'cm_pn_funnel_save_post', 10, 3);
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_filter('single_template', $plugin_post_type_funnel, 'cm_pn_funnel_single_template', 10, 3);
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_filter('archive_template', $plugin_post_type_funnel, 'cm_pn_funnel_archive_template', 10, 3);
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_shortcode('pn-customers-manager-funnel-list', $plugin_post_type_funnel, 'cm_pn_funnel_list_wrapper');

		$plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('init', $plugin_post_type_organization, 'cm_pn_org_register_post_type');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_init', $plugin_post_type_organization, 'cm_pn_org_add_meta_box');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('save_post_cm_pn_org', $plugin_post_type_organization, 'cm_pn_org_save_post', 10, 3);
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_filter('single_template', $plugin_post_type_organization, 'cm_pn_org_single_template', 10, 3);
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_filter('archive_template', $plugin_post_type_organization, 'cm_pn_org_archive_template', 10, 3);
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_shortcode('pn-customers-manager-organization-list', $plugin_post_type_organization, 'cm_pn_org_list_wrapper');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('init', $plugin_post_type_organization, 'register_organization_list_block');
	}

	/**
	 * Register all of the hooks related to Taxonomies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_define_taxonomies() {
		$plugin_taxonomies_funnel = new PN_CUSTOMERS_MANAGER_Taxonomies_Funnel();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('init', $plugin_taxonomies_funnel, 'PN_CUSTOMERS_MANAGER_register_taxonomies');
	}

	/**
	 * Load most common data used on the platform.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_load_data() {
		$plugin_data = new PN_CUSTOMERS_MANAGER_Data();

		if (is_admin()) {
			$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('init', $plugin_data, 'PN_CUSTOMERS_MANAGER_load_plugin_data');
		} else {
			$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_head', $plugin_data, 'PN_CUSTOMERS_MANAGER_load_plugin_data');
		}

		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_footer', $plugin_data, 'PN_CUSTOMERS_MANAGER_flush_rewrite_rules');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_footer', $plugin_data, 'PN_CUSTOMERS_MANAGER_flush_rewrite_rules');
	}

	/**
	 * Register templates.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_load_templates() {
		if (!defined('DOING_AJAX')) {
			$plugin_templates = new PN_CUSTOMERS_MANAGER_Templates();
			$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_footer', $plugin_templates, 'load_plugin_templates');
			$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_footer', $plugin_templates, 'load_plugin_templates');
		}
	}

	/**
	 * Register settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_load_settings() {
		$plugin_settings = new PN_CUSTOMERS_MANAGER_Settings();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_menu', $plugin_settings, 'PN_CUSTOMERS_MANAGER_admin_menu');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('activated_plugin', $plugin_settings, 'PN_CUSTOMERS_MANAGER_activated_plugin');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('admin_init', $plugin_settings, 'PN_CUSTOMERS_MANAGER_check_activation');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_filter('plugin_action_links_crmpn/pn-customers-manager.php', $plugin_settings, 'PN_CUSTOMERS_MANAGER_plugin_action_links');
	}

	/**
	 * Load ajax functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_load_ajax() {
		$plugin_ajax = new PN_CUSTOMERS_MANAGER_Ajax();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_ajax_pn_customers_manager_ajax', $plugin_ajax, 'PN_CUSTOMERS_MANAGER_ajax_server');
	}

	/**
	 * Load no private ajax functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_load_ajax_nopriv() {
		$plugin_ajax_nopriv = new PN_CUSTOMERS_MANAGER_Ajax_Nopriv();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_ajax_pn_customers_manager_ajax_nopriv', $plugin_ajax_nopriv, 'PN_CUSTOMERS_MANAGER_ajax_nopriv_server');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('wp_ajax_nopriv_pn_customers_manager_ajax_nopriv', $plugin_ajax_nopriv, 'PN_CUSTOMERS_MANAGER_ajax_nopriv_server');
	}

	/**
	 * Register shortcodes of the platform.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function PN_CUSTOMERS_MANAGER_load_shortcodes() {
		$plugin_shortcodes = new PN_CUSTOMERS_MANAGER_Shortcodes();
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_shortcode('pn-customers-manager-funnel', $plugin_shortcodes, 'cm_pn_funnel');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_shortcode('pn-customers-manager-test', $plugin_shortcodes, 'PN_CUSTOMERS_MANAGER_test');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_shortcode('pn-customers-manager-call-to-action', $plugin_shortcodes, 'PN_CUSTOMERS_MANAGER_call_to_action');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_shortcode('pn-customers-manager-client-form', $plugin_shortcodes, 'PN_CUSTOMERS_MANAGER_client_form');
	}

	/**
	 * Register hooks related to the client registration form.
	 */
	private function PN_CUSTOMERS_MANAGER_define_client_hooks() {
		$client_form = new PN_CUSTOMERS_MANAGER_Client_Form(self::PN_CUSTOMERS_MANAGER_get_plugin_name(), self::PN_CUSTOMERS_MANAGER_get_version());
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('init', $client_form, 'register_block');
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_add_action('PN_CUSTOMERS_MANAGER_form_save', $client_form, 'handle_form_save', 10, 4);
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress. Then it flushes the rewrite rules if needed.
	 *
	 * @since    1.0.0
	 */
	public function PN_CUSTOMERS_MANAGER_run() {
		$this->PN_CUSTOMERS_MANAGER_loader->PN_CUSTOMERS_MANAGER_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function PN_CUSTOMERS_MANAGER_get_plugin_name() {
		return $this->PN_CUSTOMERS_MANAGER_plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PN_CUSTOMERS_MANAGER_Loader    Orchestrates the hooks of the plugin.
	 */
	public function PN_CUSTOMERS_MANAGER_get_loader() {
		return $this->PN_CUSTOMERS_MANAGER_loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function PN_CUSTOMERS_MANAGER_get_version() {
		return $this->PN_CUSTOMERS_MANAGER_version;
	}
}