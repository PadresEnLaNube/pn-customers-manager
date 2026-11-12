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

class PN_CUSTOMERS_MANAGER
{
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      PN_CUSTOMERS_MANAGER_Loader    $pn_customers_manager_loader    Maintains and registers all hooks for the plugin.
	 */
	protected $pn_customers_manager_loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $pn_customers_manager_plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $pn_customers_manager_plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $pn_customers_manager_version    The current version of the plugin.
	 */
	protected $pn_customers_manager_version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin. Load the dependencies, define the locale, and set the hooks for the admin area and the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('PN_CUSTOMERS_MANAGER_VERSION')) {
			$this->pn_customers_manager_version = PN_CUSTOMERS_MANAGER_VERSION;
		} else {
			$this->pn_customers_manager_version = '1.2.165';
		}

		$this->pn_customers_manager_plugin_name = 'pn-customers-manager';

		self::pn_customers_manager_load_dependencies();
		self::pn_customers_manager_load_i18n();
		self::pn_customers_manager_define_common_hooks();
		self::pn_customers_manager_define_admin_hooks();
		self::pn_customers_manager_define_public_hooks();
		self::pn_customers_manager_define_custom_post_types();
		self::pn_customers_manager_define_taxonomies();
		self::pn_customers_manager_load_ajax();
		self::pn_customers_manager_load_ajax_nopriv();
		self::pn_customers_manager_load_data();
		self::pn_customers_manager_load_templates();
		self::pn_customers_manager_load_settings();
		self::pn_customers_manager_load_shortcodes();
		self::pn_customers_manager_define_client_hooks();
		self::pn_customers_manager_define_contact_form_hooks();
		self::pn_customers_manager_define_funnel_form_hooks();
		self::pn_customers_manager_define_referral_hooks();
		self::pn_customers_manager_define_commercial_hooks();
		self::pn_customers_manager_define_email_campaigns_hooks();
		self::pn_customers_manager_define_marketing_hooks();
		self::pn_customers_manager_define_offer_page_generator_hooks();
		self::pn_customers_manager_define_csv_import_hooks();
		self::pn_customers_manager_define_budget_csv_hooks();
		self::pn_customers_manager_define_whatsapp_ai_hooks();
		self::pn_customers_manager_define_instagram_ai_hooks();
		self::pn_customers_manager_define_ghl_hooks();
		self::pn_customers_manager_define_zoho_hooks();
		self::pn_customers_manager_define_clients_hooks();
		self::pn_customers_manager_define_projections_hooks();
		self::pn_customers_manager_define_cta_hooks();
		self::pn_customers_manager_define_recurring_invoices_hooks();
		self::pn_customers_manager_define_woocommerce_hooks();
		self::pn_customers_manager_define_mockup_flow_hooks();
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
	private function pn_customers_manager_load_dependencies()
	{
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
		 * The class managing marketing offers and AI-powered page generation.
		 * Must be loaded before Settings because Settings uses Marketing::get_settings_section()
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-marketing.php';

		/**
		 * The class managing social media links.
		 * Must be loaded before Settings because Settings uses Social_Media::get_settings_section()
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-social-media.php';

		/**
		 * The class managing Call to Action injection into post content.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-cta.php';

		/**
		 * The class managing WooCommerce CTA Gutenberg block.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woo-cta.php';

		/**
		 * The class managing WooCommerce Back in Stock notifications.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woo-back-in-stock.php';

		/**
		 * The class providing the Mockup Flow viewer.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-mockup-flow.php';

		/**
		 * The class defining settings.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-settings.php';

		// Sync the transcription OpenAI key mirror with the main OpenAI key.
		add_filter('pre_option_pn_customers_manager_transcription_openai_key', function () {
			return get_option('pn_customers_manager_whatsapp_openai_key', '');
		});
		add_action('update_option_pn_customers_manager_transcription_openai_key', function ($old, $new) {
			if ($new !== get_option('pn_customers_manager_whatsapp_openai_key', '')) {
				update_option('pn_customers_manager_whatsapp_openai_key', $new);
			}
		}, 10, 2);
		add_action('add_option_pn_customers_manager_transcription_openai_key', function ($option, $value) {
			if ($value !== get_option('pn_customers_manager_whatsapp_openai_key', '')) {
				update_option('pn_customers_manager_whatsapp_openai_key', $value);
			}
		}, 10, 2);

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
		 * Contact form helper.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-contact-form.php';

		/**
		 * Funnel form helper.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-funnel-form.php';

		/**
		 * The class responsible for popups functionality.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-popups.php';

		/**
		 * The class managing the custom selector component.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-selector.php';

		/**
		 * The class defining referral system functionality.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-referral.php';

		/**
		 * The class managing contact messages in the admin dashboard.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-contact-messages.php';

		/**
		 * The class managing commercial agents.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-commercial.php';

		/**
		 * The class providing CSV import for Organizations.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-csv-import.php';

		/**
		 * The class providing CSV import/export for Budgets.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-budget-csv.php';

		/**
		 * The class managing email campaigns with mailpn integration.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-email-campaigns.php';

		/**
		 * The class providing the visual funnel builder canvas.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-funnel-builder.php';

		/**
		 * Shared AI chat trait (must be loaded before WhatsApp/Instagram/Offer Generator classes).
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/trait-pn-customers-manager-ai-chat-common.php';

		/**
		 * The class providing AI-powered offer page generation.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-ai-offer-generator.php';

		/**
		 * The class providing AI-powered offer page generation with marketing data.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-offer-page-generator.php';

		/**
		 * The class providing WhatsApp AI integration.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-whatsapp-ai.php';

		/**
		 * The class providing Instagram AI integration.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-instagram-ai.php';

		/**
		 * The class providing Go High Level CRM integration.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-ghl.php';

		/**
		 * The class providing Zoho CRM integration.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-zoho.php';

		/**
		 * The class providing the CRM Clients dashboard.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-clients.php';

		/**
		 * The class providing business projections.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-projections.php';

		/**
		 * The class providing the Activity Log (audit trail).
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-activity-log.php';

		/**
		 * The class providing MailPn statistics dashboard.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-mail-stats.php';

		/**
		 * The class responsible for create the Budget custom post type.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-post-type-budget.php';

		/**
		 * The class responsible for create the Invoice custom post type.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-post-type-invoice.php';

		/**
		 * One-time migration: budget items from custom table to post meta.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-migration.php';

		/**
		 * The class managing WooCommerce integration.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-woocommerce.php';

		/**
		 * The class responsible for tutorial onboarding functionality.
		 */
		require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-tutorial.php';

		$this->pn_customers_manager_loader = new PN_CUSTOMERS_MANAGER_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the PN_CUSTOMERS_MANAGER_i18n class in order to set the domain and to register the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_load_i18n()
	{
		$plugin_i18n = new PN_CUSTOMERS_MANAGER_i18n();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('after_setup_theme', $plugin_i18n, 'pn_customers_manager_load_plugin_textdomain');

		if (class_exists('Polylang')) {
			$this->pn_customers_manager_loader->pn_customers_manager_add_filter('pll_get_post_types', $plugin_i18n, 'pn_customers_manager_pll_get_post_types', 10, 2);
		}
	}

	/**
	 * Register all of the hooks related to the main functionalities of the plugin, common to public and admin faces.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_define_common_hooks()
	{
		$plugin_common = new PN_CUSTOMERS_MANAGER_Common(self::pn_customers_manager_get_plugin_name(), self::pn_customers_manager_get_version());
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_enqueue_scripts', $plugin_common, 'pn_customers_manager_enqueue_styles');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_enqueue_scripts', $plugin_common, 'pn_customers_manager_enqueue_scripts');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_enqueue_scripts', $plugin_common, 'pn_customers_manager_enqueue_styles');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_enqueue_scripts', $plugin_common, 'pn_customers_manager_enqueue_scripts');
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('body_class', $plugin_common, 'pn_customers_manager_body_classes');

		$plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_cm_funnel_form_save', $plugin_post_type_funnel, 'pn_cm_funnel_form_save', 999, 5);

		$plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('Pn_cm_organization_form_save', $plugin_post_type_organization, 'pn_cm_organization_form_save', 999, 5);

		$plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_cm_budget_form_save', $plugin_post_type_budget, 'pn_cm_budget_form_save', 999, 5);

		$plugin_post_type_invoice = new PN_CUSTOMERS_MANAGER_Post_Type_Invoice();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_cm_invoice_form_save', $plugin_post_type_invoice, 'pn_cm_invoice_form_save', 999, 5);
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_define_admin_hooks()
	{
		$plugin_admin = new PN_CUSTOMERS_MANAGER_Admin(self::pn_customers_manager_get_plugin_name(), self::pn_customers_manager_get_version());
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_enqueue_scripts', $plugin_admin, 'pn_customers_manager_enqueue_styles');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_enqueue_scripts', $plugin_admin, 'pn_customers_manager_enqueue_scripts');

		// Tutorial assets
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_enqueue_scripts', 'PN_CUSTOMERS_MANAGER_Tutorial', 'enqueue_assets');

		// One-time migration: budget items from custom table to post meta.
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $this, 'pn_customers_manager_maybe_migrate_budget_items');

		// Database version check — show admin notice if DB needs updating.
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_notices', $this, 'pn_customers_manager_db_update_notice');

		// Activity Log: hook into save_post for all CPTs
		foreach (array_keys(PN_CUSTOMERS_MANAGER_CPTS) as $cpt_slug) {
			$this->pn_customers_manager_loader->pn_customers_manager_add_action('save_post_' . $cpt_slug, 'PN_CUSTOMERS_MANAGER_Activity_Log', 'on_save_post', 999, 3);
		}
		// Activity Log: hook into trashed_post
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('trashed_post', 'PN_CUSTOMERS_MANAGER_Activity_Log', 'on_trash_post');
	}

	/**
	 * Run one-time migration of budget items to post meta if not done yet.
	 */
	public function pn_customers_manager_maybe_migrate_budget_items()
	{
		if (get_option('pn_cm_budget_items_migrated') !== '1') {
			PN_CUSTOMERS_MANAGER_Migration::migrate_budget_items_to_meta();
		}
	}

	/**
	 * Show an admin notice when the database version is outdated.
	 */
	public function pn_customers_manager_db_update_notice() {
		if (!current_user_can('manage_options')) {
			return;
		}

		$installed_version = get_option('pn_customers_manager_db_version', '0');

		if (version_compare($installed_version, PN_CUSTOMERS_MANAGER_DB_VERSION, '>=')) {
			return;
		}

		$nonce = wp_create_nonce('pn-customers-manager-nonce');
		$main_color = sanitize_hex_color(get_option('pn_customers_manager_color_main', '#0000aa'));
		?>
		<style>
			.pn-cm-db-notice {
				margin: 20px 20px 20px 2px;
				padding: 0;
				border: none;
				border-radius: 12px;
				background: #fff;
				box-shadow: 0 2px 12px rgba(0,0,0,.08);
				overflow: hidden;
			}
			.pn-cm-db-notice-inner {
				display: flex;
				align-items: center;
				gap: 20px;
				padding: 24px 28px;
			}
			.pn-cm-db-notice-icon {
				width: 48px;
				height: 48px;
				border-radius: 12px;
				background: <?php echo esc_attr($main_color); ?>;
				display: flex;
				align-items: center;
				justify-content: center;
				flex-shrink: 0;
			}
			.pn-cm-db-notice-icon svg {
				width: 24px;
				height: 24px;
				fill: #fff;
			}
			.pn-cm-db-notice-body {
				flex: 1;
				min-width: 0;
			}
			.pn-cm-db-notice-title {
				font-size: 15px;
				font-weight: 600;
				color: #1d2327;
				margin: 0 0 4px;
			}
			.pn-cm-db-notice-desc {
				font-size: 13px;
				color: #646970;
				margin: 0;
				line-height: 1.5;
			}
			.pn-cm-db-notice-desc code {
				background: #f0f0f1;
				padding: 1px 6px;
				border-radius: 3px;
				font-size: 12px;
			}
			.pn-cm-db-notice-action {
				flex-shrink: 0;
			}
			#pn-cm-db-update-btn {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				padding: 10px 24px;
				border: none;
				border-radius: 8px;
				background: <?php echo esc_attr($main_color); ?>;
				color: #fff;
				font-size: 13px;
				font-weight: 600;
				cursor: pointer;
				transition: opacity .2s, transform .1s;
				line-height: 1;
			}
			#pn-cm-db-update-btn:hover {
				opacity: .9;
				transform: translateY(-1px);
			}
			#pn-cm-db-update-btn:disabled {
				opacity: .6;
				cursor: default;
				transform: none;
			}
			.pn-cm-db-progress-wrap {
				display: none;
				padding: 0 28px 24px;
			}
			.pn-cm-db-progress-track {
				width: 100%;
				height: 6px;
				background: #f0f0f1;
				border-radius: 3px;
				overflow: hidden;
			}
			.pn-cm-db-progress-bar {
				height: 100%;
				width: 0%;
				border-radius: 3px;
				background: <?php echo esc_attr($main_color); ?>;
				transition: width .3s ease;
			}
			.pn-cm-db-progress-label {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-top: 8px;
				font-size: 12px;
				color: #646970;
			}
			.pn-cm-db-notice-success {
				display: none;
				padding: 24px 28px;
				text-align: center;
			}
			.pn-cm-db-notice-success svg {
				width: 40px;
				height: 40px;
				fill: #00a32a;
				margin-bottom: 8px;
			}
			.pn-cm-db-notice-success p {
				margin: 0;
				font-size: 14px;
				font-weight: 600;
				color: #1d2327;
			}
		</style>
		<div class="notice pn-cm-db-notice" id="pn-cm-db-update-notice">
			<div class="pn-cm-db-notice-inner" id="pn-cm-db-main">
				<div class="pn-cm-db-notice-icon">
					<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
				</div>
				<div class="pn-cm-db-notice-body">
					<p class="pn-cm-db-notice-title"><?php esc_html_e('Database update available', 'pn-customers-manager'); ?></p>
					<p class="pn-cm-db-notice-desc">
						<?php
							printf(
								/* translators: 1: installed DB version, 2: required DB version */
								esc_html__('PN Customers Manager needs to update the database from version %1$s to %2$s. This process runs in the background.', 'pn-customers-manager'),
								'<code>' . esc_html($installed_version) . '</code>',
								'<code>' . esc_html(PN_CUSTOMERS_MANAGER_DB_VERSION) . '</code>'
							);
						?>
					</p>
				</div>
				<div class="pn-cm-db-notice-action">
					<button type="button" id="pn-cm-db-update-btn">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
						<?php esc_html_e('Update database', 'pn-customers-manager'); ?>
					</button>
				</div>
			</div>
			<div class="pn-cm-db-progress-wrap" id="pn-cm-db-progress">
				<div class="pn-cm-db-progress-track">
					<div class="pn-cm-db-progress-bar" id="pn-cm-db-bar"></div>
				</div>
				<div class="pn-cm-db-progress-label">
					<span id="pn-cm-db-step"><?php esc_html_e('Updating tables...', 'pn-customers-manager'); ?></span>
					<span id="pn-cm-db-pct">0%</span>
				</div>
			</div>
			<div class="pn-cm-db-notice-success" id="pn-cm-db-success">
				<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
				<p><?php esc_html_e('Database updated successfully!', 'pn-customers-manager'); ?></p>
			</div>
		</div>
		<script>
		(function() {
			var btn       = document.getElementById('pn-cm-db-update-btn');
			var main      = document.getElementById('pn-cm-db-main');
			var progress  = document.getElementById('pn-cm-db-progress');
			var bar       = document.getElementById('pn-cm-db-bar');
			var step      = document.getElementById('pn-cm-db-step');
			var pct       = document.getElementById('pn-cm-db-pct');
			var success   = document.getElementById('pn-cm-db-success');
			var notice    = document.getElementById('pn-cm-db-update-notice');
			if (!btn) return;

			var MIN_DURATION = 10000;
			var steps = [
				<?php echo wp_json_encode(__('Checking schema...', 'pn-customers-manager')); ?>,
				<?php echo wp_json_encode(__('Creating tables...', 'pn-customers-manager')); ?>,
				<?php echo wp_json_encode(__('Updating indexes...', 'pn-customers-manager')); ?>,
				<?php echo wp_json_encode(__('Verifying integrity...', 'pn-customers-manager')); ?>,
				<?php echo wp_json_encode(__('Finalizing...', 'pn-customers-manager')); ?>
			];

			btn.addEventListener('click', function() {
				btn.disabled = true;
				progress.style.display = 'block';

				var startTime  = Date.now();
				var ajaxDone   = false;
				var ajaxOk     = false;
				var ajaxError  = '';
				var currentPct = 0;
				var targetPct  = 0;
				var stepIdx    = 0;

				// Fire AJAX request.
				var formData = new FormData();
				formData.append('action', 'pn_customers_manager_ajax');
				formData.append('pn_customers_manager_ajax_nonce', '<?php echo esc_js($nonce); ?>');
				formData.append('pn_customers_manager_ajax_type', 'pn_cm_db_update');

				fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				})
				.then(function(r) { return r.json(); })
				.then(function(res) {
					ajaxDone = true;
					ajaxOk   = (res.error_key === '');
					if (!ajaxOk) ajaxError = res.error_content || <?php echo wp_json_encode(__('Update failed.', 'pn-customers-manager')); ?>;
				})
				.catch(function() {
					ajaxDone = true;
					ajaxOk   = false;
					ajaxError = <?php echo wp_json_encode(__('Update failed. Please try again.', 'pn-customers-manager')); ?>;
				});

				// Animate progress bar.
				var interval = setInterval(function() {
					var elapsed = Date.now() - startTime;
					var timePct = Math.min(elapsed / MIN_DURATION, 1);

					// Phase targets: climb to 85% over the minimum duration,
					// then jump to 100% once AJAX is done and time is met.
					if (!ajaxDone) {
						targetPct = Math.min(timePct * 85, 85);
					} else if (ajaxOk && timePct >= 1) {
						targetPct = 100;
					} else if (ajaxOk) {
						targetPct = Math.max(85, timePct * 95);
					} else {
						// Error — freeze and show error.
						clearInterval(interval);
						step.textContent = ajaxError;
						step.style.color = '#d63638';
						pct.textContent  = '';
						btn.disabled     = false;
						return;
					}

					// Smooth easing toward target.
					currentPct += (targetPct - currentPct) * 0.15;
					if (targetPct - currentPct < 0.5) currentPct = targetPct;

					var rounded = Math.round(currentPct);
					bar.style.width = rounded + '%';
					pct.textContent = rounded + '%';

					// Update step label.
					var newIdx = Math.min(Math.floor(rounded / 20), steps.length - 1);
					if (newIdx !== stepIdx) {
						stepIdx = newIdx;
						step.textContent = steps[stepIdx];
					}

					// Done.
					if (currentPct >= 100) {
						clearInterval(interval);
						setTimeout(function() {
							main.style.display     = 'none';
							progress.style.display = 'none';
							success.style.display  = 'block';
							setTimeout(function() {
								notice.style.transition = 'opacity .5s, margin .5s';
								notice.style.opacity    = '0';
								notice.style.margin     = '0';
								notice.style.overflow   = 'hidden';
								notice.style.maxHeight  = notice.scrollHeight + 'px';
								setTimeout(function() { notice.style.maxHeight = '0'; }, 50);
								setTimeout(function() { notice.style.display = 'none'; }, 600);
							}, 2500);
						}, 400);
					}
				}, 60);
			});
		})();
		</script>
		<?php
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_define_public_hooks()
	{
		$plugin_public = new PN_CUSTOMERS_MANAGER_Public(self::pn_customers_manager_get_plugin_name(), self::pn_customers_manager_get_version());
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_enqueue_scripts', $plugin_public, 'pn_customers_manager_enqueue_styles');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_enqueue_scripts', $plugin_public, 'pn_customers_manager_enqueue_scripts');

		$plugin_user = new PN_CUSTOMERS_MANAGER_Functions_User();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_login', $plugin_user, 'pn_customers_manager_user_wp_login');

		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_head', $this, 'pn_customers_manager_maybe_noindex_crm_pages', 1);

		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('rest_pre_dispatch', $this, 'pn_customers_manager_restrict_crm_rest_api', 10, 3);
	}

	/**
	 * Output noindex meta tag on CRM management pages unless allowed by settings.
	 */
	public function pn_customers_manager_maybe_noindex_crm_pages()
	{
		if (is_admin()) {
			return;
		}

		if (get_option('pn_customers_manager_allow_crm_indexing') === 'on') {
			return;
		}

		// Budget public URL (template already has noindex, but cover wp_head too)
		if (get_query_var('pn_cm_budget_token')) {
			return; // Already handled in the budget template
		}

		$post = get_queried_object();
		if (!($post instanceof WP_Post)) {
			return;
		}

		$crm_shortcodes = [
			'pn-customers-manager-funnel-list',
			'pn-customers-manager-organization-list',
			'pn-customers-manager-commercial-panel',
			'pn-customers-manager-email-campaigns',
			'pn-customers-manager-referrals',
			'pn-customers-manager-whatsapp-ai',
			'pn-customers-manager-instagram-ai',
			'pn-customers-manager-client-form',
			'pn-customers-manager-budget-list',
		];

		$crm_blocks = [
			'pn-customers-manager/organization-list',
			'pn-customers-manager/commercial-panel',
			'pn-customers-manager/email-campaigns',
			'pn-customers-manager/client-form',
			'pn-customers-manager/budget-list',
		];

		foreach ($crm_shortcodes as $shortcode) {
			if (has_shortcode($post->post_content, $shortcode)) {
				echo '<meta name="robots" content="noindex, nofollow">' . "\n";
				return;
			}
		}

		foreach ($crm_blocks as $block) {
			if (has_block($block, $post)) {
				echo '<meta name="robots" content="noindex, nofollow">' . "\n";
				return;
			}
		}
	}

	/**
	 * Block unauthenticated REST API requests to CRM custom post types.
	 *
	 * @param mixed            $result  Response to replace the requested version with.
	 * @param WP_REST_Server   $server  Server instance.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @return mixed|WP_Error
	 */
	public function pn_customers_manager_restrict_crm_rest_api($result, $server, $request)
	{
		if (null !== $result) {
			return $result;
		}

		$route = $request->get_route();

		foreach (array_keys(PN_CUSTOMERS_MANAGER_CPTS) as $cpt_slug) {
			if (preg_match('#^/wp/v2/' . preg_quote($cpt_slug, '#') . '(/|$)#', $route)) {
				if (!current_user_can('manage_options')) {
					return new WP_Error(
						'rest_forbidden',
						__('You do not have permission to access this resource.', 'pn-customers-manager'),
						array('status' => 401)
					);
				}
				break;
			}
		}

		return $result;
	}

	/**
	 * Register all Post Types with meta boxes and templates.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_define_custom_post_types()
	{
		$plugin_post_type_funnel = new PN_CUSTOMERS_MANAGER_Post_Type_Funnel();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_post_type_funnel, 'pn_cm_funnel_register_post_type');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $plugin_post_type_funnel, 'pn_cm_funnel_add_meta_box');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('save_post_pn_cm_funnel', $plugin_post_type_funnel, 'pn_cm_funnel_save_post', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('single_template', $plugin_post_type_funnel, 'pn_cm_funnel_single_template', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('archive_template', $plugin_post_type_funnel, 'pn_cm_funnel_archive_template', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-funnel-list', $plugin_post_type_funnel, 'pn_cm_funnel_list_wrapper');

		$plugin_post_type_organization = new PN_CUSTOMERS_MANAGER_Post_Type_organization();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_post_type_organization, 'pn_cm_organization_register_post_type');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $plugin_post_type_organization, 'pn_cm_organization_add_meta_box');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('save_post_pn_cm_organization', $plugin_post_type_organization, 'pn_cm_organization_save_post', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('single_template', $plugin_post_type_organization, 'pn_cm_organization_single_template', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('archive_template', $plugin_post_type_organization, 'pn_cm_organization_archive_template', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-organization-list', $plugin_post_type_organization, 'pn_cm_organization_list_wrapper');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_post_type_organization, 'register_organization_list_block');

		$plugin_post_type_budget = new PN_CUSTOMERS_MANAGER_Post_Type_Budget();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_post_type_budget, 'pn_cm_budget_register_post_type');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $plugin_post_type_budget, 'pn_cm_budget_add_meta_box');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('save_post_pn_cm_budget', $plugin_post_type_budget, 'pn_cm_budget_save_post', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('single_template', $plugin_post_type_budget, 'pn_cm_budget_single_template', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_post_type_budget, 'pn_cm_budget_init_rewrite');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('template_redirect', $plugin_post_type_budget, 'pn_cm_budget_template_redirect');
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('query_vars', $plugin_post_type_budget, 'pn_cm_budget_query_vars');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-budget-list', $plugin_post_type_budget, 'pn_cm_budget_list_wrapper');
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('manage_pn_cm_budget_posts_columns', $plugin_post_type_budget, 'pn_cm_budget_admin_columns');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('manage_pn_cm_budget_posts_custom_column', $plugin_post_type_budget, 'pn_cm_budget_admin_column_content', 10, 2);
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('manage_edit-pn_cm_budget_sortable_columns', $plugin_post_type_budget, 'pn_cm_budget_admin_sortable_columns');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pre_get_posts', $plugin_post_type_budget, 'pn_cm_budget_admin_sort_columns');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_enqueue_scripts', $plugin_post_type_budget, 'pn_cm_budget_admin_list_styles');

		$plugin_post_type_invoice = new PN_CUSTOMERS_MANAGER_Post_Type_Invoice();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_post_type_invoice, 'pn_cm_invoice_register_post_type');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $plugin_post_type_invoice, 'pn_cm_invoice_add_meta_box');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('save_post_pn_cm_invoice', $plugin_post_type_invoice, 'pn_cm_invoice_save_post', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('single_template', $plugin_post_type_invoice, 'pn_cm_invoice_single_template', 10, 3);
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_post_type_invoice, 'pn_cm_invoice_init_rewrite');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('template_redirect', $plugin_post_type_invoice, 'pn_cm_invoice_template_redirect');
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('query_vars', $plugin_post_type_invoice, 'pn_cm_invoice_query_vars');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-invoice-list', $plugin_post_type_invoice, 'pn_cm_invoice_list_wrapper');
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('manage_pn_cm_invoice_posts_columns', $plugin_post_type_invoice, 'pn_cm_invoice_admin_columns');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('manage_pn_cm_invoice_posts_custom_column', $plugin_post_type_invoice, 'pn_cm_invoice_admin_column_content', 10, 2);
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('manage_edit-pn_cm_invoice_sortable_columns', $plugin_post_type_invoice, 'pn_cm_invoice_admin_sortable_columns');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pre_get_posts', $plugin_post_type_invoice, 'pn_cm_invoice_admin_sort_columns');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_enqueue_scripts', $plugin_post_type_invoice, 'pn_cm_invoice_admin_list_styles');

	}

	/**
	 * Register all of the hooks related to Taxonomies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_define_taxonomies()
	{
		$plugin_taxonomies_funnel = new PN_CUSTOMERS_MANAGER_Taxonomies_Funnel();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_taxonomies_funnel, 'pn_customers_manager_register_taxonomies');
	}

	/**
	 * Load most common data used on the platform.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_load_data()
	{
		$plugin_data = new PN_CUSTOMERS_MANAGER_Data();

		if (is_admin()) {
			$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $plugin_data, 'pn_customers_manager_load_plugin_data');
		} else {
			$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_head', $plugin_data, 'pn_customers_manager_load_plugin_data');
		}

		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_footer', $plugin_data, 'pn_customers_manager_flush_rewrite_rules');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_footer', $plugin_data, 'pn_customers_manager_flush_rewrite_rules');
	}

	/**
	 * Register templates.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_load_templates()
	{
		if (!defined('DOING_AJAX')) {
			$plugin_templates = new PN_CUSTOMERS_MANAGER_Templates();
			$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_footer', $plugin_templates, 'load_plugin_templates');
			$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_footer', $plugin_templates, 'load_plugin_templates');
		}
	}

	/**
	 * Register settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_load_settings()
	{
		$plugin_settings = new PN_CUSTOMERS_MANAGER_Settings();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_menu', $plugin_settings, 'pn_customers_manager_admin_menu');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('activated_plugin', $plugin_settings, 'pn_customers_manager_activated_plugin');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $plugin_settings, 'pn_customers_manager_check_activation');
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('plugin_action_links_crmpn/pn-customers-manager.php', $plugin_settings, 'pn_customers_manager_plugin_action_links');
	}

	/**
	 * Load ajax functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_load_ajax()
	{
		$plugin_ajax = new PN_CUSTOMERS_MANAGER_Ajax();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_ajax', $plugin_ajax, 'pn_customers_manager_ajax_server');
	}

	/**
	 * Load no private ajax functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_load_ajax_nopriv()
	{
		$plugin_ajax_nopriv = new PN_CUSTOMERS_MANAGER_Ajax_Nopriv();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_ajax_nopriv', $plugin_ajax_nopriv, 'pn_customers_manager_ajax_nopriv_server');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_nopriv_pn_customers_manager_ajax_nopriv', $plugin_ajax_nopriv, 'pn_customers_manager_ajax_nopriv_server');
	}

	/**
	 * Register shortcodes of the platform.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pn_customers_manager_load_shortcodes()
	{
		$plugin_shortcodes = new PN_CUSTOMERS_MANAGER_Shortcodes();
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-funnel', $plugin_shortcodes, 'pn_cm_funnel');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-test', $plugin_shortcodes, 'pn_customers_manager_test');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-call-to-action', $plugin_shortcodes, 'pn_customers_manager_call_to_action');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-client-form', $plugin_shortcodes, 'pn_customers_manager_client_form');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-contact-form', $plugin_shortcodes, 'pn_customers_manager_contact_form');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-button', $plugin_shortcodes, 'pn_customers_manager_button');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-whatsapp-ai', $plugin_shortcodes, 'pn_customers_manager_whatsapp_ai');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-instagram-ai', $plugin_shortcodes, 'pn_customers_manager_instagram_ai');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-style-guide', $plugin_shortcodes, 'pn_customers_manager_style_guide');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-social-media', $plugin_shortcodes, 'pn_customers_manager_social_media');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-funnel-form', $plugin_shortcodes, 'pn_customers_manager_funnel_form');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Shortcodes', 'register_button_block');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Social_Media', 'register_block');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Woo_CTA', 'register_block');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-mockup-flow', 'PN_CUSTOMERS_MANAGER_Mockup_Flow', 'shortcode_handler');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-mockup-flow-list', 'PN_CUSTOMERS_MANAGER_Mockup_Flow', 'list_wrapper');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Mockup_Flow', 'register_block');
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-clients', 'PN_CUSTOMERS_MANAGER_Clients', 'shortcode_handler');
	}

	/**
	 * Register hooks related to the client registration form.
	 */
	private function pn_customers_manager_define_client_hooks()
	{
		$client_form = new PN_CUSTOMERS_MANAGER_Client_Form(self::pn_customers_manager_get_plugin_name(), self::pn_customers_manager_get_version());
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $client_form, 'register_block');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_customers_manager_form_save', $client_form, 'handle_form_save', 10, 4);
	}


	/**
	 * Register hooks related to the contact form.
	 */
	private function pn_customers_manager_define_contact_form_hooks()
	{
		$contact_form = new PN_CUSTOMERS_MANAGER_Contact_Form(self::pn_customers_manager_get_plugin_name(), self::pn_customers_manager_get_version());
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $contact_form, 'register_block');
	}

	/**
	 * Register hooks related to the funnel form.
	 */
	private function pn_customers_manager_define_funnel_form_hooks()
	{
		$funnel_form = new PN_CUSTOMERS_MANAGER_Funnel_Form(self::pn_customers_manager_get_plugin_name(), self::pn_customers_manager_get_version());
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', $funnel_form, 'register_block');
	}

	/**
	 * Register hooks related to the referral system.
	 */
	private function pn_customers_manager_define_referral_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-referrals', 'PN_CUSTOMERS_MANAGER_Referral', 'render_referrals_shortcode');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Referral', 'register_block');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('template_redirect', 'PN_CUSTOMERS_MANAGER_Referral', 'handle_referral_acceptance');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('template_redirect', 'PN_CUSTOMERS_MANAGER_Referral', 'handle_qr_referral_landing', 5);
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_footer', 'PN_CUSTOMERS_MANAGER_Referral', 'inject_qr_landing_popup');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_cm_referral_reminders_cron', 'PN_CUSTOMERS_MANAGER_Referral', 'process_referral_reminders');
	}

	/**
	 * Register hooks related to the commercial agents system.
	 */
	private function pn_customers_manager_define_commercial_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-commercial-panel', 'PN_CUSTOMERS_MANAGER_Commercial', 'render_commercial_panel');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Commercial', 'register_block');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $this, 'pn_customers_manager_ensure_commercial_role');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $this, 'pn_customers_manager_ensure_crm_cap');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', $this, 'pn_customers_manager_ensure_cpt_caps');
	}

	/**
	 * Ensure the commercial role exists (upgrade path for existing installs).
	 */
	public function pn_customers_manager_ensure_commercial_role()
	{
		if (!get_role('pn_customers_manager_role_commercial')) {
			add_role('pn_customers_manager_role_commercial', esc_html(__('Comercial - PN', 'pn-customers-manager')));
			$role = get_role('pn_customers_manager_role_commercial');
			if ($role instanceof WP_Role) {
				$role->add_cap('read');
			}
		}
	}

	/**
	 * Ensure pn_cm_manage_crm capability exists on manager and admin roles
	 * (upgrade path for existing installs).
	 */
	public function pn_customers_manager_ensure_crm_cap()
	{
		$manager = get_role('pn_customers_manager_role_manager');
		if ($manager instanceof \WP_Role && !$manager->has_cap('pn_cm_manage_crm')) {
			$manager->add_cap('pn_cm_manage_crm');
		}
		$admin = get_role('administrator');
		if ($admin instanceof \WP_Role && !$admin->has_cap('pn_cm_manage_crm')) {
			$admin->add_cap('pn_cm_manage_crm');
		}
	}

	/**
	 * Ensure all CPT capabilities exist on admin and manager roles
	 * (upgrade path for existing installs when new CPTs are added).
	 */
	public function pn_customers_manager_ensure_cpt_caps()
	{
		$admin = get_role('administrator');
		$manager = get_role('pn_customers_manager_role_manager');

		if (!$admin instanceof \WP_Role) {
			return;
		}

		foreach (PN_CUSTOMERS_MANAGER_CPTS as $cpt_key => $cpt_name) {
			$constant_name = 'PN_CUSTOMERS_MANAGER_ROLE_' . strtoupper($cpt_key) . '_CAPABILITIES';
			if (!defined($constant_name)) {
				continue;
			}
			$capabilities = constant($constant_name);
			foreach ($capabilities as $cap) {
				if (!$admin->has_cap($cap)) {
					$admin->add_cap($cap);
				}
				if ($manager instanceof \WP_Role && !$manager->has_cap($cap)) {
					$manager->add_cap($cap);
				}
			}
			$manage_cap = 'manage_' . $cpt_key . '_options';
			if (!$admin->has_cap($manage_cap)) {
				$admin->add_cap($manage_cap);
			}
			if ($manager instanceof \WP_Role && !$manager->has_cap($manage_cap)) {
				$manager->add_cap($manage_cap);
			}
		}
	}

	/**
	 * Register hooks related to the email campaigns system.
	 */
	private function pn_customers_manager_define_email_campaigns_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode('pn-customers-manager-email-campaigns', 'PN_CUSTOMERS_MANAGER_Email_Campaigns', 'render_shortcode');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Email_Campaigns', 'register_block');
	}

	/**
	 * Register hooks for marketing offers and AI-powered page generation.
	 */
	private function pn_customers_manager_define_marketing_hooks()
	{
		// Shortcodes for static pages
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode(
			'pn-customers-manager-genuine-offer',
			'PN_CUSTOMERS_MANAGER_Marketing',
			'render_genuine_offer_shortcode'
		);
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode(
			'pn-customers-manager-sales-presentation',
			'PN_CUSTOMERS_MANAGER_Marketing',
			'render_sales_presentation_shortcode'
		);

		// Gutenberg blocks
		$this->pn_customers_manager_loader->pn_customers_manager_add_action(
			'init',
			'PN_CUSTOMERS_MANAGER_Marketing',
			'register_blocks'
		);

		// AI Offer Generator shortcode and AJAX
		$plugin_ai_offer_gen = new PN_CUSTOMERS_MANAGER_AI_Offer_Generator();
		$this->pn_customers_manager_loader->pn_customers_manager_add_shortcode(
			'pn-customers-manager-ai-offer-generator',
			$plugin_ai_offer_gen,
			'render_generator_form'
		);

		// AJAX handler for generating offers
		$this->pn_customers_manager_loader->pn_customers_manager_add_action(
			'wp_ajax_pn_cm_generate_offer',
			$plugin_ai_offer_gen,
			'handle_generate_offer_ajax'
		);

		// Enqueue assets for offer generator
		$this->pn_customers_manager_loader->pn_customers_manager_add_action(
			'wp_enqueue_scripts',
			'PN_CUSTOMERS_MANAGER_AI_Offer_Generator',
			'enqueue_assets'
		);

		// Add noindex to marketing pages
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter(
			'wp_robots',
			'PN_CUSTOMERS_MANAGER_Marketing',
			'add_noindex_to_marketing_pages'
		);

		// Frontend AJAX handlers for offer data editing
		$this->pn_customers_manager_loader->pn_customers_manager_add_action(
			'wp_ajax_pn_cm_frontend_save_offer_data',
			'PN_CUSTOMERS_MANAGER_Marketing',
			'handle_frontend_save_offer_data'
		);

		$this->pn_customers_manager_loader->pn_customers_manager_add_action(
			'wp_ajax_pn_cm_frontend_delete_offer_page',
			'PN_CUSTOMERS_MANAGER_Marketing',
			'handle_frontend_delete_offer_page'
		);
	}

	/**
	 * Register hooks for Offer Page Generator.
	 */
	private function pn_customers_manager_define_offer_page_generator_hooks()
	{
		// Register admin menu pages
		$this->pn_customers_manager_loader->pn_customers_manager_add_action(
			'admin_menu',
			'PN_CUSTOMERS_MANAGER_Offer_Page_Generator',
			'register_admin_pages'
		);

		// AJAX handlers
		$this->pn_customers_manager_loader->pn_customers_manager_add_action(
			'wp_ajax_pn_cm_generate_offer_page',
			'PN_CUSTOMERS_MANAGER_Offer_Page_Generator',
			'handle_generate_offer_page'
		);

		$this->pn_customers_manager_loader->pn_customers_manager_add_action(
			'wp_ajax_pn_cm_delete_offer_page',
			'PN_CUSTOMERS_MANAGER_Offer_Page_Generator',
			'handle_delete_offer_page'
		);
	}

	/**
	 * Register hooks for CSV import on Organizations.
	 */
	private function pn_customers_manager_define_csv_import_hooks()
	{
		$csv_import = new PN_CUSTOMERS_MANAGER_Csv_Import();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_head-edit.php', $csv_import, 'pn_customers_manager_csv_inject_button');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_csv_template', $csv_import, 'pn_customers_manager_csv_download_template');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_csv_preview', $csv_import, 'pn_customers_manager_csv_preview');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_csv_import', $csv_import, 'pn_customers_manager_csv_import');
	}

	/**
	 * Register hooks for Budget CSV import/export.
	 */
	private function pn_customers_manager_define_budget_csv_hooks()
	{
		$budget_csv = new PN_CUSTOMERS_MANAGER_Budget_Csv();
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_budget_csv_download_template', $budget_csv, 'pn_customers_manager_budget_csv_download_template');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_budget_csv_export', $budget_csv, 'pn_customers_manager_budget_csv_export');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_budget_csv_preview', $budget_csv, 'pn_customers_manager_budget_csv_preview');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('wp_ajax_pn_customers_manager_budget_csv_import', $budget_csv, 'pn_customers_manager_budget_csv_import');
	}

	/**
	 * Register hooks for WhatsApp AI integration.
	 */
	private function pn_customers_manager_define_whatsapp_ai_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('rest_api_init', 'PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'register_routes');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'early_webhook_verify', 1);
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_cm_whatsapp_queue_cron', 'PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'process_message_queue');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_cm_send_scheduled_wa_template', 'PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'send_scheduled_template', 10, 8);
		// Fallback: process overdue scheduled templates on every page load.
		// Lightweight check (reads WP cron option) that ensures templates fire
		// even when DISABLE_WP_CRON is true or WP Cron is unreliable.
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'process_pending_template_events', 20);
	}

	/**
	 * Register hooks for Instagram AI integration.
	 */
	private function pn_customers_manager_define_instagram_ai_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('rest_api_init', 'PN_CUSTOMERS_MANAGER_Instagram_AI', 'register_routes');
	}

	/**
	 * Register hooks for GHL webhook integration.
	 */
	private function pn_customers_manager_define_ghl_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('rest_api_init', 'PN_CUSTOMERS_MANAGER_GHL', 'register_routes');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('updated_post_meta', 'PN_CUSTOMERS_MANAGER_GHL', 'on_funnel_status_changed', 10, 4);
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('added_post_meta', 'PN_CUSTOMERS_MANAGER_GHL', 'on_funnel_status_changed', 10, 4);
	}

	/**
	 * Register hooks for Zoho CRM integration.
	 */
	private function pn_customers_manager_define_zoho_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('rest_api_init', 'PN_CUSTOMERS_MANAGER_Zoho', 'register_routes');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', 'PN_CUSTOMERS_MANAGER_Zoho', 'maybe_handle_oauth_callback');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('updated_post_meta', 'PN_CUSTOMERS_MANAGER_Zoho', 'on_funnel_status_changed', 10, 4);
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('added_post_meta', 'PN_CUSTOMERS_MANAGER_Zoho', 'on_funnel_status_changed', 10, 4);
	}

	/**
	 * Register hooks for CRM Clients dashboard.
	 */
	private function pn_customers_manager_define_clients_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Clients', 'register_block');
	}

	/**
	 * Register hooks for business projections.
	 */
	private function pn_customers_manager_define_projections_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_cm_projections_snapshot_cron', 'PN_CUSTOMERS_MANAGER_Projections', 'collect_snapshot');
	}

	/**
	 * Register hooks for CTA content injection.
	 */
	private function pn_customers_manager_define_cta_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_filter('the_content', 'PN_CUSTOMERS_MANAGER_CTA', 'add_to_content');
	}

	/**
	 * Register hooks for recurring invoices.
	 */
	private function pn_customers_manager_define_recurring_invoices_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('pn_cm_recurring_invoices_cron', 'PN_CUSTOMERS_MANAGER_Post_Type_Invoice', 'process_recurring_invoices');
	}

	/**
	 * Register WooCommerce integration hooks.
	 *
	 * @since    1.1.75
	 * @access   private
	 */
	private function pn_customers_manager_define_woocommerce_hooks()
	{
		// Only initialize if WooCommerce is active
		if (class_exists('WooCommerce')) {
			PN_CUSTOMERS_MANAGER_WooCommerce::instance();
			new PN_CUSTOMERS_MANAGER_Woo_Back_In_Stock();
		}
	}

	/**
	 * Register Mockup Flow hooks.
	 */
	private function pn_customers_manager_define_mockup_flow_hooks()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('init', 'PN_CUSTOMERS_MANAGER_Mockup_Flow', 'register_post_type');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('admin_init', 'PN_CUSTOMERS_MANAGER_Mockup_Flow', 'add_meta_box');
		$this->pn_customers_manager_loader->pn_customers_manager_add_action('save_post_pn_cm_mockup_flow', 'PN_CUSTOMERS_MANAGER_Mockup_Flow', 'save_post');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress. Then it flushes the rewrite rules if needed.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_run()
	{
		$this->pn_customers_manager_loader->pn_customers_manager_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function pn_customers_manager_get_plugin_name()
	{
		return $this->pn_customers_manager_plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PN_CUSTOMERS_MANAGER_Loader    Orchestrates the hooks of the plugin.
	 */
	public function pn_customers_manager_get_loader()
	{
		return $this->pn_customers_manager_loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function pn_customers_manager_get_version()
	{
		return $this->pn_customers_manager_version;
	}
}