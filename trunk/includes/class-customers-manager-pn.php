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
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */

class CUSTOMERS_MANAGER_PN {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      CUSTOMERS_MANAGER_PN_Loader    $customers_manager_pn_loader    Maintains and registers all hooks for the plugin.
	 */
	protected $customers_manager_pn_loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $customers_manager_pn_plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $customers_manager_pn_plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $customers_manager_pn_version    The current version of the plugin.
	 */
	protected $customers_manager_pn_version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin. Load the dependencies, define the locale, and set the hooks for the admin area and the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if (defined('CUSTOMERS_MANAGER_PN_VERSION')) {
			$this->customers_manager_pn_version = CUSTOMERS_MANAGER_PN_VERSION;
		} else {
			$this->customers_manager_pn_version = '1.0.0';
		}

		$this->customers_manager_pn_plugin_name = 'customers-manager-pn';

		self::customers_manager_pn_load_dependencies();
		self::customers_manager_pn_load_i18n();
		self::customers_manager_pn_define_common_hooks();
		self::customers_manager_pn_define_admin_hooks();
		self::customers_manager_pn_define_public_hooks();
		self::customers_manager_pn_define_custom_post_types();
		self::customers_manager_pn_define_taxonomies();
		self::customers_manager_pn_load_ajax();
		self::customers_manager_pn_load_ajax_nopriv();
		self::customers_manager_pn_load_data();
		self::customers_manager_pn_load_templates();
		self::customers_manager_pn_load_settings();
		self::customers_manager_pn_load_shortcodes();
		self::customers_manager_pn_define_client_hooks();
	}
			
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 * - CUSTOMERS_MANAGER_PN_Loader. Orchestrates the hooks of the plugin.
	 * - CUSTOMERS_MANAGER_PN_i18n. Defines internationalization functionality.
	 * - CUSTOMERS_MANAGER_PN_Common. Defines hooks used accross both, admin and public side.
	 * - CUSTOMERS_MANAGER_PN_Admin. Defines all hooks for the admin area.
	 * - CUSTOMERS_MANAGER_PN_Public. Defines all hooks for the public side of the site.
	 * - CUSTOMERS_MANAGER_PN_Post_Type_Funnel. Defines Funnel custom post type.
	 * - CUSTOMERS_MANAGER_PN_Taxonomies_Funnel. Defines Funnel taxonomies.
	 * - CUSTOMERS_MANAGER_PN_Templates. Load plugin templates.
	 * - CUSTOMERS_MANAGER_PN_Data. Load main usefull data.
	 * - CUSTOMERS_MANAGER_PN_Functions_Post. Posts management functions.
	 * - CUSTOMERS_MANAGER_PN_Functions_User. Users management functions.
	 * - CUSTOMERS_MANAGER_PN_Functions_Attachment. Attachments management functions.
	 * - CUSTOMERS_MANAGER_PN_Functions_Settings. Define settings.
	 * - CUSTOMERS_MANAGER_PN_Functions_Forms. Forms management functions.
	 * - CUSTOMERS_MANAGER_PN_Functions_Ajax. Ajax functions.
	 * - CUSTOMERS_MANAGER_PN_Functions_Ajax_Nopriv. Ajax No Private functions.
	 * - CUSTOMERS_MANAGER_PN_Popups. Define popups functionality.
	 * - CUSTOMERS_MANAGER_PN_Functions_Shortcodes. Define all shortcodes for the platform.
	 * - CUSTOMERS_MANAGER_PN_Functions_Validation. Define validation and sanitization.
	 *
	 * Create an instance of the loader which will be used to register the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-loader.php';

		/**
		 * The class responsible for defining internationalization functionality of the plugin.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-i18n.php';

		/**
		 * The class responsible for defining all actions that occur both in the admin area and in the public-facing side of the site.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-common.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/admin/class-customers-manager-pn-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/public/class-customers-manager-pn-public.php';

		/**
		 * The class responsible for create the Funnel custom post type.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-post-type-funnel.php';

		/**
		 * The class responsible for create the Funnel custom taxonomies.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-taxonomies-funnel.php';

		/**
		 * The class responsible for create the Organization custom post type.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-post-type-organization.php';

		/**
		 * The class responsible for plugin templates.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-templates.php';

		/**
		 * The class getting key data of the platform.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-data.php';

		/**
		 * The class defining posts management functions.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-functions-post.php';

		/**
		 * The class defining users management functions.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-functions-user.php';

		/**
		 * The class defining attahcments management functions.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-functions-attachment.php';

		/**
		 * The class defining settings.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-settings.php';

		/**
		 * The class defining form management.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-forms.php';

		/**
		 * The class defining ajax functions.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-ajax.php';

		/**
		 * The class defining no private ajax functions.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-ajax-nopriv.php';

		/**
		 * The class defining shortcodes.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-shortcodes.php';

		/**
		 * The class defining validation and sanitization.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-validation.php';

		/**
		 * Client form helper.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-client-form.php';


		/**
		 * The class responsible for popups functionality.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-popups.php';

		/**
		 * The class managing the custom selector component.
		 */
		require_once CUSTOMERS_MANAGER_PN_DIR . 'includes/class-customers-manager-pn-selector.php';

		$this->customers_manager_pn_loader = new CUSTOMERS_MANAGER_PN_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the CUSTOMERS_MANAGER_PN_i18n class in order to set the domain and to register the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_load_i18n() {
		$plugin_i18n = new CUSTOMERS_MANAGER_PN_i18n();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('after_setup_theme', $plugin_i18n, 'customers_manager_pn_load_plugin_textdomain');

		if (class_exists('Polylang')) {
			$this->customers_manager_pn_loader->customers_manager_pn_add_filter('pll_get_post_types', $plugin_i18n, 'customers_manager_pn_pll_get_post_types', 10, 2);
    }
	}

	/**
	 * Register all of the hooks related to the main functionalities of the plugin, common to public and admin faces.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_define_common_hooks() {
		$plugin_common = new CUSTOMERS_MANAGER_PN_Common(self::customers_manager_pn_get_plugin_name(), self::customers_manager_pn_get_version());
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_enqueue_scripts', $plugin_common, 'customers_manager_pn_enqueue_styles');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_enqueue_scripts', $plugin_common, 'customers_manager_pn_enqueue_scripts');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_enqueue_scripts', $plugin_common, 'customers_manager_pn_enqueue_styles');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_enqueue_scripts', $plugin_common, 'customers_manager_pn_enqueue_scripts');
		$this->customers_manager_pn_loader->customers_manager_pn_add_filter('body_class', $plugin_common, 'customers_manager_pn_body_classes');

		$plugin_post_type_funnel = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('cm_pn_funnel_form_save', $plugin_post_type_funnel, 'cm_pn_funnel_form_save', 999, 5);

		$plugin_post_type_organization = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('cm_pn_org_form_save', $plugin_post_type_organization, 'cm_pn_org_form_save', 999, 5);
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_define_admin_hooks() {
		$plugin_admin = new CUSTOMERS_MANAGER_PN_Admin(self::customers_manager_pn_get_plugin_name(), self::customers_manager_pn_get_version());
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_enqueue_scripts', $plugin_admin, 'customers_manager_pn_enqueue_styles');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_enqueue_scripts', $plugin_admin, 'customers_manager_pn_enqueue_scripts');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_define_public_hooks() {
		$plugin_public = new CUSTOMERS_MANAGER_PN_Public(self::customers_manager_pn_get_plugin_name(), self::customers_manager_pn_get_version());
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_enqueue_scripts', $plugin_public, 'customers_manager_pn_enqueue_styles');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_enqueue_scripts', $plugin_public, 'customers_manager_pn_enqueue_scripts');

		$plugin_user = new CUSTOMERS_MANAGER_PN_Functions_User();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_login', $plugin_user, 'customers_manager_pn_user_wp_login');
	}

	/**
	 * Register all Post Types with meta boxes and templates.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_define_custom_post_types() {
		$plugin_post_type_funnel = new CUSTOMERS_MANAGER_PN_Post_Type_Funnel();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('init', $plugin_post_type_funnel, 'cm_pn_funnel_register_post_type');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_init', $plugin_post_type_funnel, 'cm_pn_funnel_add_meta_box');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('save_post_cm_pn_funnel', $plugin_post_type_funnel, 'cm_pn_funnel_save_post', 10, 3);
		$this->customers_manager_pn_loader->customers_manager_pn_add_filter('single_template', $plugin_post_type_funnel, 'cm_pn_funnel_single_template', 10, 3);
		$this->customers_manager_pn_loader->customers_manager_pn_add_filter('archive_template', $plugin_post_type_funnel, 'cm_pn_funnel_archive_template', 10, 3);
		$this->customers_manager_pn_loader->customers_manager_pn_add_shortcode('customers-manager-pn-funnel-list', $plugin_post_type_funnel, 'cm_pn_funnel_list_wrapper');

		$plugin_post_type_organization = new CUSTOMERS_MANAGER_PN_Post_Type_organization();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('init', $plugin_post_type_organization, 'cm_pn_org_register_post_type');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_init', $plugin_post_type_organization, 'cm_pn_org_add_meta_box');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('save_post_cm_pn_org', $plugin_post_type_organization, 'cm_pn_org_save_post', 10, 3);
		$this->customers_manager_pn_loader->customers_manager_pn_add_filter('single_template', $plugin_post_type_organization, 'cm_pn_org_single_template', 10, 3);
		$this->customers_manager_pn_loader->customers_manager_pn_add_filter('archive_template', $plugin_post_type_organization, 'cm_pn_org_archive_template', 10, 3);
		$this->customers_manager_pn_loader->customers_manager_pn_add_shortcode('customers-manager-pn-organization-list', $plugin_post_type_organization, 'cm_pn_org_list_wrapper');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('init', $plugin_post_type_organization, 'register_organization_list_block');
	}

	/**
	 * Register all of the hooks related to Taxonomies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_define_taxonomies() {
		$plugin_taxonomies_funnel = new CUSTOMERS_MANAGER_PN_Taxonomies_Funnel();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('init', $plugin_taxonomies_funnel, 'customers_manager_pn_register_taxonomies');
	}

	/**
	 * Load most common data used on the platform.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_load_data() {
		$plugin_data = new CUSTOMERS_MANAGER_PN_Data();

		if (is_admin()) {
			$this->customers_manager_pn_loader->customers_manager_pn_add_action('init', $plugin_data, 'customers_manager_pn_load_plugin_data');
		} else {
			$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_head', $plugin_data, 'customers_manager_pn_load_plugin_data');
		}

		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_footer', $plugin_data, 'customers_manager_pn_flush_rewrite_rules');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_footer', $plugin_data, 'customers_manager_pn_flush_rewrite_rules');
	}

	/**
	 * Register templates.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_load_templates() {
		if (!defined('DOING_AJAX')) {
			$plugin_templates = new CUSTOMERS_MANAGER_PN_Templates();
			$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_footer', $plugin_templates, 'load_plugin_templates');
			$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_footer', $plugin_templates, 'load_plugin_templates');
		}
	}

	/**
	 * Register settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_load_settings() {
		$plugin_settings = new CUSTOMERS_MANAGER_PN_Settings();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_menu', $plugin_settings, 'customers_manager_pn_admin_menu');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('activated_plugin', $plugin_settings, 'customers_manager_pn_activated_plugin');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('admin_init', $plugin_settings, 'customers_manager_pn_check_activation');
		$this->customers_manager_pn_loader->customers_manager_pn_add_filter('plugin_action_links_crmpn/customers-manager-pn.php', $plugin_settings, 'customers_manager_pn_plugin_action_links');
	}

	/**
	 * Load ajax functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_load_ajax() {
		$plugin_ajax = new CUSTOMERS_MANAGER_PN_Ajax();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_ajax_customers_manager_pn_ajax', $plugin_ajax, 'customers_manager_pn_ajax_server');
	}

	/**
	 * Load no private ajax functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_load_ajax_nopriv() {
		$plugin_ajax_nopriv = new CUSTOMERS_MANAGER_PN_Ajax_Nopriv();
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_ajax_customers_manager_pn_ajax_nopriv', $plugin_ajax_nopriv, 'customers_manager_pn_ajax_nopriv_server');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('wp_ajax_nopriv_customers_manager_pn_ajax_nopriv', $plugin_ajax_nopriv, 'customers_manager_pn_ajax_nopriv_server');
	}

	/**
	 * Register shortcodes of the platform.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function customers_manager_pn_load_shortcodes() {
		$plugin_shortcodes = new CUSTOMERS_MANAGER_PN_Shortcodes();
		$this->customers_manager_pn_loader->customers_manager_pn_add_shortcode('customers-manager-pn-funnel', $plugin_shortcodes, 'cm_pn_funnel');
		$this->customers_manager_pn_loader->customers_manager_pn_add_shortcode('customers-manager-pn-test', $plugin_shortcodes, 'customers_manager_pn_test');
		$this->customers_manager_pn_loader->customers_manager_pn_add_shortcode('customers-manager-pn-call-to-action', $plugin_shortcodes, 'customers_manager_pn_call_to_action');
		$this->customers_manager_pn_loader->customers_manager_pn_add_shortcode('customers-manager-pn-client-form', $plugin_shortcodes, 'customers_manager_pn_client_form');
	}

	/**
	 * Register hooks related to the client registration form.
	 */
	private function customers_manager_pn_define_client_hooks() {
		$client_form = new CUSTOMERS_MANAGER_PN_Client_Form(self::customers_manager_pn_get_plugin_name(), self::customers_manager_pn_get_version());
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('init', $client_form, 'register_block');
		$this->customers_manager_pn_loader->customers_manager_pn_add_action('cm_pn_form_save', $client_form, 'handle_form_save', 10, 4);
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress. Then it flushes the rewrite rules if needed.
	 *
	 * @since    1.0.0
	 */
	public function customers_manager_pn_run() {
		$this->customers_manager_pn_loader->customers_manager_pn_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function customers_manager_pn_get_plugin_name() {
		return $this->customers_manager_pn_plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    CUSTOMERS_MANAGER_PN_Loader    Orchestrates the hooks of the plugin.
	 */
	public function customers_manager_pn_get_loader() {
		return $this->customers_manager_pn_loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function customers_manager_pn_get_version() {
		return $this->customers_manager_pn_version;
	}
}