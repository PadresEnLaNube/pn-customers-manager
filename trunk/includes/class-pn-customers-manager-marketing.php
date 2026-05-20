<?php
/**
 * Marketing offers and business pages management.
 *
 * Handles marketing offer configuration, AI-powered page generation,
 * and frontend rendering of business offers and sales presentations.
 *
 * @link       padresenlanube.com/
 * @since      1.0.70
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Marketing {

	/**
	 * Managed pages for marketing offers.
	 *
	 * @var array
	 */
	private static $marketing_pages = [
		'pn_customers_manager_marketing_offer_page' => [
			'shortcode' => 'pn-customers-manager-genuine-offer',
			'block'     => 'pn-customers-manager/genuine-offer',
			'label'     => 'Business Offer',
		],
		'pn_customers_manager_marketing_sales_presentation_page' => [
			'shortcode' => 'pn-customers-manager-sales-presentation',
			'block'     => 'pn-customers-manager/sales-presentation',
			'label'     => 'Sales Presentation',
		],
	];

	/**
	 * Get marketing managed pages configuration.
	 *
	 * @return array
	 */
	public static function get_marketing_pages() {
		return self::$marketing_pages;
	}

	/**
	 * Get the settings section options array for the marketing section.
	 *
	 * @return array
	 */
	public static function get_settings_section() {
		$options = [];

		// Section start
		$options['pn_customers_manager_marketing_section_start'] = [
			'id'          => 'pn_customers_manager_marketing_section_start',
			'section'     => 'start',
			'label'       => __('Marketing', 'pn-customers-manager'),
			'description' => __('Configure your business information to generate AI-powered marketing offers and sales presentations.', 'pn-customers-manager'),
		];

		// Business Information subsection
		$options['pn_customers_manager_marketing_business_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_business_subsection_start',
			'section' => 'start',
			'label'   => __('Business Information', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_business_name'] = [
			'id'          => 'pn_customers_manager_marketing_business_name',
			'input'       => 'input',
			'type'        => 'text',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Business name', 'pn-customers-manager'),
			'placeholder' => __('Your business name', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_tagline'] = [
			'id'          => 'pn_customers_manager_marketing_tagline',
			'input'       => 'textarea',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Tagline', 'pn-customers-manager'),
			'description' => __('Short catchphrase that describes your business (1 line).', 'pn-customers-manager'),
			'placeholder' => __('We help you achieve...', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_description'] = [
			'id'          => 'pn_customers_manager_marketing_description',
			'input'       => 'textarea',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Business description', 'pn-customers-manager'),
			'description' => __('What your business does, what problem it solves (2-3 paragraphs).', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_logo'] = [
			'id'          => 'pn_customers_manager_marketing_logo',
			'input'       => 'image',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Logo', 'pn-customers-manager'),
			'description' => __('Your business logo (optional, used in generated pages).', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_business_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_business_subsection_end',
			'section' => 'end',
		];

		// Target Audience subsection
		$options['pn_customers_manager_marketing_target_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_target_subsection_start',
			'section' => 'start',
			'label'   => __('Target Audience', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_target_description'] = [
			'id'          => 'pn_customers_manager_marketing_target_description',
			'input'       => 'textarea',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Target audience description', 'pn-customers-manager'),
			'description' => __('Who is your ideal customer? Describe their characteristics, needs, desires.', 'pn-customers-manager'),
			'placeholder' => __('Entrepreneurs between 30-45 years old who...', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_target_gender'] = [
			'id'      => 'pn_customers_manager_marketing_target_gender',
			'input'   => 'select',
			'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
			'label'   => __('Gender', 'pn-customers-manager'),
			'options' => [
				''       => __('-- Select --', 'pn-customers-manager'),
				'male'   => __('Male', 'pn-customers-manager'),
				'female' => __('Female', 'pn-customers-manager'),
				'all'    => __('All', 'pn-customers-manager'),
			],
		];

		$options['pn_customers_manager_marketing_target_age_range'] = [
			'id'          => 'pn_customers_manager_marketing_target_age_range',
			'input'       => 'input',
			'type'        => 'text',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Age range', 'pn-customers-manager'),
			'placeholder' => __('25-45', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_target_profession'] = [
			'id'          => 'pn_customers_manager_marketing_target_profession',
			'input'       => 'input',
			'type'        => 'text',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Profession / Occupation', 'pn-customers-manager'),
			'placeholder' => __('Entrepreneurs, freelancers, professionals...', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_target_socioeconomic'] = [
			'id'      => 'pn_customers_manager_marketing_target_socioeconomic',
			'input'   => 'select',
			'class'   => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
			'label'   => __('Socioeconomic level', 'pn-customers-manager'),
			'options' => [
				''       => __('-- Select --', 'pn-customers-manager'),
				'low'    => __('Low', 'pn-customers-manager'),
				'medium' => __('Medium', 'pn-customers-manager'),
				'high'   => __('High', 'pn-customers-manager'),
			],
		];

		$options['pn_customers_manager_marketing_target_awareness_level'] = [
			'id'          => 'pn_customers_manager_marketing_target_awareness_level',
			'input'       => 'select',
			'class'       => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
			'label'       => __('Awareness level', 'pn-customers-manager'),
			'description' => __('How aware is your audience of their problem and your solution?', 'pn-customers-manager'),
			'options'     => [
				'unaware'        => __('Unaware (doesn\'t know they have a problem)', 'pn-customers-manager'),
				'problem_aware'  => __('Problem Aware (knows the problem, not the solution)', 'pn-customers-manager'),
				'solution_aware' => __('Solution Aware (knows solutions exist)', 'pn-customers-manager'),
				'product_aware'  => __('Product Aware (knows your solution)', 'pn-customers-manager'),
				'most_aware'     => __('Most Aware (ready to buy)', 'pn-customers-manager'),
			],
		];

		$options['pn_customers_manager_marketing_target_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_target_subsection_end',
			'section' => 'end',
		];

		// Problems subsection
		$options['pn_customers_manager_marketing_problems_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_problems_subsection_start',
			'section' => 'start',
			'label'   => __('Problems You Solve', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_problems'] = [
			'id'                => 'pn_customers_manager_marketing_problems',
			'input'             => 'html_multi',
			'label'             => __('Problems', 'pn-customers-manager'),
			'description'       => __('List specific pain points your target audience faces.', 'pn-customers-manager'),
			'html_multi_fields' => [
				[
					'id'          => 'pn_customers_manager_marketing_problems_text',
					'input'       => 'textarea',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'placeholder' => __('Describe a specific problem...', 'pn-customers-manager'),
				],
			],
		];

		$options['pn_customers_manager_marketing_problems_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_problems_subsection_end',
			'section' => 'end',
		];

		// Transformation subsection
		$options['pn_customers_manager_marketing_transformation_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_transformation_subsection_start',
			'section' => 'start',
			'label'   => __('Transformation', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_transformation'] = [
			'id'          => 'pn_customers_manager_marketing_transformation',
			'input'       => 'textarea',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Transformation (Before → After)', 'pn-customers-manager'),
			'description' => __('What change do your clients experience? From what state to what state?', 'pn-customers-manager'),
			'placeholder' => __('Before: Struggling with X... After: Achieving Y...', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_transformation_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_transformation_subsection_end',
			'section' => 'end',
		];

		// Services and Pricing subsection
		$options['pn_customers_manager_marketing_services_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_services_subsection_start',
			'section' => 'start',
			'label'   => __('Services & Pricing', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_services'] = [
			'id'                => 'pn_customers_manager_marketing_services',
			'input'             => 'html_multi',
			'label'             => __('Services/Plans', 'pn-customers-manager'),
			'description'       => __('Your service packages or pricing plans.', 'pn-customers-manager'),
			'html_multi_fields' => [
				[
					'id'          => 'pn_customers_manager_marketing_services_name',
					'input'       => 'input',
					'type'        => 'text',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label'       => __('Service name', 'pn-customers-manager'),
					'placeholder' => __('Basic Plan', 'pn-customers-manager'),
				],
				[
					'id'          => 'pn_customers_manager_marketing_services_description',
					'input'       => 'textarea',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label'       => __('What\'s included', 'pn-customers-manager'),
					'placeholder' => __('Feature 1, Feature 2...', 'pn-customers-manager'),
				],
				[
					'id'          => 'pn_customers_manager_marketing_services_price',
					'input'       => 'input',
					'type'        => 'text',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label'       => __('Price', 'pn-customers-manager'),
					'placeholder' => __('€99/month', 'pn-customers-manager'),
				],
			],
		];

		$options['pn_customers_manager_marketing_services_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_services_subsection_end',
			'section' => 'end',
		];

		// Process/Steps subsection
		$options['pn_customers_manager_marketing_process_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_process_subsection_start',
			'section' => 'start',
			'label'   => __('Process / Steps', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_process_steps'] = [
			'id'                => 'pn_customers_manager_marketing_process_steps',
			'input'             => 'html_multi',
			'label'             => __('Process steps', 'pn-customers-manager'),
			'description'       => __('How does your service/product work? Define the steps or phases.', 'pn-customers-manager'),
			'html_multi_fields' => [
				[
					'id'          => 'pn_customers_manager_marketing_process_steps_title',
					'input'       => 'input',
					'type'        => 'text',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label'       => __('Step title', 'pn-customers-manager'),
					'placeholder' => __('Step 1: Initial consultation', 'pn-customers-manager'),
				],
				[
					'id'          => 'pn_customers_manager_marketing_process_steps_description',
					'input'       => 'textarea',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label'       => __('Description', 'pn-customers-manager'),
					'placeholder' => __('We analyze your needs...', 'pn-customers-manager'),
				],
			],
		];

		$options['pn_customers_manager_marketing_process_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_process_subsection_end',
			'section' => 'end',
		];

		// Authority subsection
		$options['pn_customers_manager_marketing_authority_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_authority_subsection_start',
			'section' => 'start',
			'label'   => __('Authority & Social Proof', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_authority_years'] = [
			'id'          => 'pn_customers_manager_marketing_authority_years',
			'input'       => 'input',
			'type'        => 'number',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Years of experience', 'pn-customers-manager'),
			'placeholder' => __('5', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_authority_clients'] = [
			'id'          => 'pn_customers_manager_marketing_authority_clients',
			'input'       => 'input',
			'type'        => 'number',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Number of clients served', 'pn-customers-manager'),
			'placeholder' => __('500', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_authority_testimonials'] = [
			'id'                => 'pn_customers_manager_marketing_authority_testimonials',
			'input'             => 'html_multi',
			'label'             => __('Testimonials', 'pn-customers-manager'),
			'description'       => __('Client testimonials that demonstrate results.', 'pn-customers-manager'),
			'html_multi_fields' => [
				[
					'id'          => 'pn_customers_manager_marketing_authority_testimonials_text',
					'input'       => 'textarea',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label'       => __('Testimonial', 'pn-customers-manager'),
					'placeholder' => __('Thanks to [business], I was able to...', 'pn-customers-manager'),
				],
				[
					'id'          => 'pn_customers_manager_marketing_authority_testimonials_author',
					'input'       => 'input',
					'type'        => 'text',
					'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label'       => __('Author', 'pn-customers-manager'),
					'placeholder' => __('María García, CEO of Company X', 'pn-customers-manager'),
				],
			],
		];

		$options['pn_customers_manager_marketing_authority_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_authority_subsection_end',
			'section' => 'end',
		];

		// Scarcity subsection
		$options['pn_customers_manager_marketing_scarcity_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_scarcity_subsection_start',
			'section' => 'start',
			'label'   => __('Urgency & Scarcity', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_scarcity_type'] = [
			'id'          => 'pn_customers_manager_marketing_scarcity_type',
			'input'       => 'select',
			'class'       => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
			'label'       => __('Scarcity type', 'pn-customers-manager'),
			'description' => __('Create urgency to encourage decision-making.', 'pn-customers-manager'),
			'options'     => [
				'none'       => __('None', 'pn-customers-manager'),
				'limited'    => __('Limited spots', 'pn-customers-manager'),
				'time_limit' => __('Time-limited offer', 'pn-customers-manager'),
				'bonus'      => __('Bonus for early action', 'pn-customers-manager'),
			],
		];

		$options['pn_customers_manager_marketing_scarcity_text'] = [
			'id'          => 'pn_customers_manager_marketing_scarcity_text',
			'input'       => 'input',
			'type'        => 'text',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Scarcity message', 'pn-customers-manager'),
			'placeholder' => __('Only 5 spots remaining this month', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_scarcity_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_scarcity_subsection_end',
			'section' => 'end',
		];

		// CTAs subsection
		$options['pn_customers_manager_marketing_cta_subsection_start'] = [
			'id'      => 'pn_customers_manager_marketing_cta_subsection_start',
			'section' => 'start',
			'label'   => __('Call to Actions', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_cta_primary_text'] = [
			'id'          => 'pn_customers_manager_marketing_cta_primary_text',
			'input'       => 'input',
			'type'        => 'text',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Primary CTA text', 'pn-customers-manager'),
			'placeholder' => __('Get started now', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_cta_primary_url'] = [
			'id'          => 'pn_customers_manager_marketing_cta_primary_url',
			'input'       => 'input',
			'type'        => 'url',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Primary CTA URL', 'pn-customers-manager'),
			'placeholder' => __('https://example.com/contact', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_cta_secondary_text'] = [
			'id'          => 'pn_customers_manager_marketing_cta_secondary_text',
			'input'       => 'input',
			'type'        => 'text',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Secondary CTA text', 'pn-customers-manager'),
			'placeholder' => __('Learn more', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_cta_secondary_url'] = [
			'id'          => 'pn_customers_manager_marketing_cta_secondary_url',
			'input'       => 'input',
			'type'        => 'url',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label'       => __('Secondary CTA URL', 'pn-customers-manager'),
			'placeholder' => __('https://example.com/about', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_marketing_cta_subsection_end'] = [
			'id'      => 'pn_customers_manager_marketing_cta_subsection_end',
			'section' => 'end',
		];

		// Section end
		$options['pn_customers_manager_marketing_section_end'] = [
			'id'      => 'pn_customers_manager_marketing_section_end',
			'section' => 'end',
		];

		return $options;
	}

	/**
	 * Get all marketing settings data.
	 *
	 * @return array
	 */
	private static function get_marketing_data() {
		// Try new option names first, fallback to old names for backwards compatibility
		$services_desc = get_option('pn_customers_manager_marketing_services_desc', []);
		if (empty($services_desc)) {
			$services_desc = get_option('pn_customers_manager_marketing_services_description', []);
		}

		$testimonials_text = get_option('pn_customers_manager_marketing_testimonials_text', []);
		if (empty($testimonials_text)) {
			$testimonials_text = get_option('pn_customers_manager_marketing_authority_testimonials_text', []);
		}

		$testimonials_author = get_option('pn_customers_manager_marketing_testimonials_author', []);
		if (empty($testimonials_author)) {
			$testimonials_author = get_option('pn_customers_manager_marketing_authority_testimonials_author', []);
		}

		$process_title = get_option('pn_customers_manager_marketing_process_title', []);
		if (empty($process_title)) {
			$process_title = get_option('pn_customers_manager_marketing_process_steps_title', []);
		}

		$process_desc = get_option('pn_customers_manager_marketing_process_desc', []);
		if (empty($process_desc)) {
			$process_desc = get_option('pn_customers_manager_marketing_process_steps_description', []);
		}

		return [
			'business_name'        => get_option('pn_customers_manager_marketing_business_name', ''),
			'tagline'              => get_option('pn_customers_manager_marketing_tagline', ''),
			'description'          => get_option('pn_customers_manager_marketing_description', ''),
			'logo'                 => get_option('pn_customers_manager_marketing_logo', ''),
			'target_description'   => get_option('pn_customers_manager_marketing_target_description', ''),
			'solution_headline'    => get_option('pn_customers_manager_marketing_solution_headline', ''),
			'solution_description' => get_option('pn_customers_manager_marketing_solution_description', ''),
			'problems'             => get_option('pn_customers_manager_marketing_problems_text', []),
			'transformation'       => get_option('pn_customers_manager_marketing_transformation', ''),
			'services_name'        => get_option('pn_customers_manager_marketing_services_name', []),
			'services_desc'        => $services_desc,
			'services_price'       => get_option('pn_customers_manager_marketing_services_price', []),
			'process_title'        => $process_title,
			'process_desc'         => $process_desc,
			'testimonials_text'    => $testimonials_text,
			'testimonials_author'  => $testimonials_author,
			'cta_primary_text'     => get_option('pn_customers_manager_marketing_cta_primary_text', __('Get started', 'pn-customers-manager')),
			'cta_primary_url'      => get_option('pn_customers_manager_marketing_cta_primary_url', '#'),
			'cta_secondary_text'   => get_option('pn_customers_manager_marketing_cta_secondary_text', ''),
			'cta_secondary_url'    => get_option('pn_customers_manager_marketing_cta_secondary_url', ''),
		];
	}

	/**
	 * Render the business offer shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	/**
	 * Enqueue frontend assets for marketing shortcodes.
	 */
	public static function enqueue_frontend_assets() {
		if (!is_admin()) {
			$version = defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0';

			// Enqueue styles
			wp_enqueue_style('wph-material-icons-outlined', PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css', [], $version, 'all');
			wp_enqueue_style('pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], $version, 'all');
			wp_enqueue_style('pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-tooltips.css', [], $version, 'all');

			// Enqueue scripts (jQuery only, tooltips handled inline)
			wp_enqueue_script('jquery');
		}
	}

	public static function render_genuine_offer_shortcode($atts = []) {
		// Enqueue assets
		self::enqueue_frontend_assets();

		$atts = shortcode_atts([], $atts, 'pn-customers-manager-genuine-offer');
		$data = self::get_marketing_data();
		$created_pages = get_option('pn_cm_business_offer_pages', []);

		// Filter out deleted pages
		$created_pages = array_filter($created_pages, function($page_id) {
			return get_post($page_id) !== null;
		});

		ob_start();
		?>
		<div class="pn-cm-marketing-genuine-offer pn-customers-manager-frontend-editor">

			<!-- Marketing Data Editor Toggle -->
			<div class="pn-customers-manager-toggle-wrapper pn-customers-manager-section-wrapper pn-customers-manager-mb-20">
				<a href="#" class="pn-customers-manager-toggle pn-customers-manager-width-100-percent pn-customers-manager-text-decoration-none">
					<h2 class="pn-customers-manager-mb-0">
						<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">edit</span>
						<?php esc_html_e('Business Offer Data', 'pn-customers-manager'); ?>
					</h2>
				</a>
				<div class="pn-customers-manager-toggle-content">
					<form id="pn-cm-offer-data-form" class="pn-customers-manager-form">
						<?php wp_nonce_field('pn_cm_save_offer_data', 'pn_cm_nonce'); ?>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Business Name', 'pn-customers-manager'); ?>
							</label>
							<input type="text" name="business_name" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($data['business_name']); ?>">
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Tagline', 'pn-customers-manager'); ?>
							</label>
							<textarea name="tagline" rows="2" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($data['tagline']); ?></textarea>
							<p class="pn-customers-manager-description"><?php esc_html_e('Short catchphrase that describes your business (1 line).', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Business Description', 'pn-customers-manager'); ?>
							</label>
							<textarea name="description" rows="4" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($data['description']); ?></textarea>
							<p class="pn-customers-manager-description"><?php esc_html_e('What your business does, what problem it solves (2-3 paragraphs).', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Target Audience', 'pn-customers-manager'); ?>
							</label>
							<textarea name="target_description" rows="3" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($data['target_description']); ?></textarea>
							<p class="pn-customers-manager-description"><?php esc_html_e('Who is your ideal customer? Describe their characteristics, problems, and needs.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Customer Problems', 'pn-customers-manager'); ?>
							</label>
							<?php
							$problems = is_array($data['problems']) ? $data['problems'] : [];
							if (empty($problems)) {
								$problems = [''];
							}
							foreach ($problems as $index => $problem) :
							?>
								<textarea name="problems[]" rows="2" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-10"><?php echo esc_textarea($problem); ?></textarea>
							<?php endforeach; ?>
							<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-cm-add-problem" style="margin-top: 5px;">
								<span class="material-icons-outlined">add</span>
								<?php esc_html_e('Add Problem', 'pn-customers-manager'); ?>
							</button>
							<p class="pn-customers-manager-description"><?php esc_html_e('List the main problems your customers face.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Solution Headline', 'pn-customers-manager'); ?>
							</label>
							<input type="text" name="solution_headline" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($data['solution_headline']); ?>">
							<p class="pn-customers-manager-description"><?php esc_html_e('Brief headline describing your solution.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Solution Description', 'pn-customers-manager'); ?>
							</label>
							<textarea name="solution_description" rows="4" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($data['solution_description']); ?></textarea>
							<p class="pn-customers-manager-description"><?php esc_html_e('Detailed explanation of how you solve the problems.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Transformation Statement', 'pn-customers-manager'); ?>
							</label>
							<textarea name="transformation" rows="3" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($data['transformation']); ?></textarea>
							<p class="pn-customers-manager-description"><?php esc_html_e('Describe the transformation: from current state to desired state.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Process Steps', 'pn-customers-manager'); ?>
							</label>
							<?php
							$process_titles = is_array($data['process_title']) ? $data['process_title'] : [];
							$process_descs = is_array($data['process_desc']) ? $data['process_desc'] : [];
							if (empty($process_titles)) {
								$process_titles = [''];
								$process_descs = [''];
							}
							foreach ($process_titles as $index => $title) :
							?>
								<div class="pn-customers-manager-process-step-group pn-customers-manager-mb-15">
									<input type="text" name="process_title[]" placeholder="<?php esc_attr_e('Step title', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-5" value="<?php echo esc_attr($title); ?>">
									<textarea name="process_desc[]" rows="2" placeholder="<?php esc_attr_e('Step description', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($process_descs[$index] ?? ''); ?></textarea>
								</div>
							<?php endforeach; ?>
							<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-cm-add-process-step" style="margin-top: 5px;">
								<span class="material-icons-outlined">add</span>
								<?php esc_html_e('Add Step', 'pn-customers-manager'); ?>
							</button>
							<p class="pn-customers-manager-description"><?php esc_html_e('Your methodology or process steps.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Services/Products', 'pn-customers-manager'); ?>
							</label>
							<?php
							$services_names = is_array($data['services_name']) ? $data['services_name'] : [];
							$services_prices = is_array($data['services_price']) ? $data['services_price'] : [];
							$services_descs = is_array($data['services_desc']) ? $data['services_desc'] : [];
							if (empty($services_names)) {
								$services_names = [''];
								$services_prices = [''];
								$services_descs = [''];
							}
							foreach ($services_names as $index => $name) :
							?>
								<div class="pn-customers-manager-service-group pn-customers-manager-mb-15">
									<input type="text" name="services_name[]" placeholder="<?php esc_attr_e('Service name', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-5" value="<?php echo esc_attr($name); ?>">
									<input type="text" name="services_price[]" placeholder="<?php esc_attr_e('Price', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-5" value="<?php echo esc_attr($services_prices[$index] ?? ''); ?>">
									<textarea name="services_desc[]" rows="2" placeholder="<?php esc_attr_e('Description', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($services_descs[$index] ?? ''); ?></textarea>
								</div>
							<?php endforeach; ?>
							<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-cm-add-service" style="margin-top: 5px;">
								<span class="material-icons-outlined">add</span>
								<?php esc_html_e('Add Service', 'pn-customers-manager'); ?>
							</button>
							<p class="pn-customers-manager-description"><?php esc_html_e('List your services/products with prices.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Testimonials', 'pn-customers-manager'); ?>
							</label>
							<?php
							$testimonials = is_array($data['testimonials_text']) ? $data['testimonials_text'] : [];
							$testimonial_authors = is_array($data['testimonials_author']) ? $data['testimonials_author'] : [];
							if (empty($testimonials)) {
								$testimonials = [''];
								$testimonial_authors = [''];
							}
							foreach ($testimonials as $index => $testimonial) :
							?>
								<div class="pn-customers-manager-testimonial-group pn-customers-manager-mb-15">
									<textarea name="testimonials_text[]" rows="2" placeholder="<?php esc_attr_e('Testimonial quote', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-5"><?php echo esc_textarea($testimonial); ?></textarea>
									<input type="text" name="testimonials_author[]" placeholder="<?php esc_attr_e('Author name and company', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($testimonial_authors[$index] ?? ''); ?>">
								</div>
							<?php endforeach; ?>
							<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-cm-add-testimonial" style="margin-top: 5px;">
								<span class="material-icons-outlined">add</span>
								<?php esc_html_e('Add Testimonial', 'pn-customers-manager'); ?>
							</button>
							<p class="pn-customers-manager-description"><?php esc_html_e('Customer testimonials.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Primary CTA Text', 'pn-customers-manager'); ?>
							</label>
							<input type="text" name="cta_primary_text" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($data['cta_primary_text']); ?>">
							<p class="pn-customers-manager-description"><?php esc_html_e('Text for the main call-to-action button.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Primary CTA URL', 'pn-customers-manager'); ?>
							</label>
							<input type="url" name="cta_primary_url" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($data['cta_primary_url']); ?>">
							<p class="pn-customers-manager-description"><?php esc_html_e('URL for the main call-to-action button.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Additional Comments for AI', 'pn-customers-manager'); ?>
							</label>
							<textarea name="additional_comments" rows="3" class="pn-customers-manager-input pn-customers-manager-width-100-percent" placeholder="<?php esc_attr_e('Any specific instructions or context for the AI when generating the page...', 'pn-customers-manager'); ?>"></textarea>
							<p class="pn-customers-manager-description"><?php esc_html_e('Optional: Provide additional context or instructions for the AI.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-actions pn-customers-manager-display-flex pn-customers-manager-gap-10">
							<button type="submit" name="action" value="save" class="pn-customers-manager-btn pn-customers-manager-btn-primary">
								<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">save</span>
								<?php esc_html_e('Save Data', 'pn-customers-manager'); ?>
							</button>
							<button type="submit" name="action" value="generate" class="pn-customers-manager-btn pn-customers-manager-btn-secondary" id="pn-cm-generate-page-btn">
								<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">auto_awesome</span>
								<?php esc_html_e('Generate Page with AI', 'pn-customers-manager'); ?>
							</button>
						</div>

						<div id="pn-cm-offer-message" class="pn-customers-manager-message" style="display:none; margin-top: 20px;"></div>
					</form>
				</div>
			</div>

			<!-- Created Pages List Toggle -->
			<?php if (!empty($created_pages)) : ?>
			<div class="pn-customers-manager-toggle-wrapper pn-customers-manager-section-wrapper pn-customers-manager-mb-20">
				<a href="#" class="pn-customers-manager-toggle pn-customers-manager-width-100-percent pn-customers-manager-text-decoration-none">
					<h2 class="pn-customers-manager-mb-0">
						<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">pages</span>
						<?php esc_html_e('Created Pages', 'pn-customers-manager'); ?>
						<span class="pn-customers-manager-badge"><?php echo count($created_pages); ?></span>
					</h2>
				</a>
				<div class="pn-customers-manager-toggle-content">
					<div class="pn-cm-pages-list">
						<?php foreach ($created_pages as $page_id) : ?>
							<?php
							$post = get_post($page_id);
							if (!$post) continue;
							?>
							<div class="pn-cm-page-item pn-customers-manager-display-flex pn-customers-manager-align-items-center pn-customers-manager-justify-content-space-between pn-customers-manager-p-15 pn-customers-manager-mb-10" style="border: 1px solid #ddd; border-radius: 4px;">
								<div class="pn-cm-page-info">
									<strong><?php echo esc_html($post->post_title); ?></strong>
									<div class="pn-customers-manager-text-small pn-customers-manager-text-muted">
										<?php
										$status_labels = [
											'publish' => __('Published', 'pn-customers-manager'),
											'draft'   => __('Draft', 'pn-customers-manager'),
											'pending' => __('Pending', 'pn-customers-manager'),
										];
										echo esc_html($status_labels[$post->post_status] ?? $post->post_status);
										?> • <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($post->post_date))); ?>
									</div>
								</div>
								<div class="pn-cm-page-actions pn-customers-manager-display-flex pn-customers-manager-gap-10">
									<a href="<?php echo esc_url(get_edit_post_link($page_id)); ?>" class="pn-customers-manager-btn pn-customers-manager-btn-small pn-customers-manager-btn-transparent pn-customers-manager-tooltip" data-hostpn-tooltip="<?php esc_attr_e('Edit', 'pn-customers-manager'); ?>">
										<span class="material-icons-outlined">edit</span>
									</a>
									<a href="<?php echo esc_url(get_permalink($page_id)); ?>" class="pn-customers-manager-btn pn-customers-manager-btn-small pn-customers-manager-btn-transparent pn-customers-manager-tooltip" data-hostpn-tooltip="<?php esc_attr_e('View', 'pn-customers-manager'); ?>" target="_blank">
										<span class="material-icons-outlined">visibility</span>
									</a>
									<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-small pn-customers-manager-btn-transparent pn-customers-manager-tooltip pn-cm-delete-offer-page" data-page-id="<?php echo esc_attr($page_id); ?>" data-hostpn-tooltip="<?php esc_attr_e('Delete', 'pn-customers-manager'); ?>">
										<span class="material-icons-outlined">delete</span>
									</button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<script>
			jQuery(document).ready(function($) {
				// Toggle functionality
				$('.pn-cm-marketing-genuine-offer .pn-customers-manager-toggle').on('click', function(e) {
					e.preventDefault();
					var $content = $(this).next('.pn-customers-manager-toggle-content');
					var isActive = $content.hasClass('pn-customers-manager-toggle-active');

					if (isActive) {
						$content.removeClass('pn-customers-manager-toggle-active');
						$content.slideUp(300);
					} else {
						$content.addClass('pn-customers-manager-toggle-active');
						$content.slideDown(300);
					}
				});

				// Simple tooltip functionality
				$('[data-hostpn-tooltip]').hover(
					function() {
						var tooltipText = $(this).attr('data-hostpn-tooltip');
						var $tooltip = $('<div class="pn-cm-tooltip">' + tooltipText + '</div>');
						$('body').append($tooltip);

						var offset = $(this).offset();
						$tooltip.css({
							position: 'absolute',
							top: offset.top - $tooltip.outerHeight() - 5,
							left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2),
							background: '#333',
							color: 'white',
							padding: '5px 10px',
							borderRadius: '4px',
							fontSize: '12px',
							zIndex: 10000,
							whiteSpace: 'nowrap'
						});
					},
					function() {
						$('.pn-cm-tooltip').remove();
					}
				);

				// Add problem field
				$('.pn-cm-add-problem').on('click', function() {
					var $container = $(this).prev();
					var $newField = $('<textarea name="problems[]" rows="2" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-10"></textarea>');
					$(this).before($newField);
				});

				// Add process step
				$('.pn-cm-add-process-step').on('click', function() {
					var $template = $('.pn-customers-manager-process-step-group:first').clone();
					$template.find('input, textarea').val('');
					$(this).before($template);
				});

				// Add service
				$('.pn-cm-add-service').on('click', function() {
					var $template = $('.pn-customers-manager-service-group:first').clone();
					$template.find('input, textarea').val('');
					$(this).before($template);
				});

				// Add testimonial
				$('.pn-cm-add-testimonial').on('click', function() {
					var $template = $('.pn-customers-manager-testimonial-group:first').clone();
					$template.find('input, textarea').val('');
					$(this).before($template);
				});

				// Form submission
				$('#pn-cm-offer-data-form').on('submit', function(e) {
					e.preventDefault();

					var $form = $(this);
					var $btn = $form.find('button[type="submit"]:focus');
					var action = $btn.val();
					var formData = $form.serialize() + '&form_action=' + action;

					$btn.prop('disabled', true);
					$('#pn-cm-offer-message').hide();

					$.ajax({
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						type: 'POST',
						data: formData + '&action=pn_cm_frontend_save_offer_data',
						success: function(response) {
							if (response.success) {
								$('#pn-cm-offer-message')
									.removeClass('pn-customers-manager-message-error')
									.addClass('pn-customers-manager-message-success')
									.html('<strong><?php esc_html_e('Success!', 'pn-customers-manager'); ?></strong> ' + response.data.message)
									.show();

								if (response.data.page_url) {
									setTimeout(function() {
										location.reload();
									}, 2000);
								}
							} else {
								$('#pn-cm-offer-message')
									.removeClass('pn-customers-manager-message-success')
									.addClass('pn-customers-manager-message-error')
									.html('<strong><?php esc_html_e('Error!', 'pn-customers-manager'); ?></strong> ' + (response.data.message || '<?php esc_html_e('An error occurred.', 'pn-customers-manager'); ?>'))
									.show();
							}
						},
						error: function() {
							$('#pn-cm-offer-message')
								.removeClass('pn-customers-manager-message-success')
								.addClass('pn-customers-manager-message-error')
								.html('<strong><?php esc_html_e('Error!', 'pn-customers-manager'); ?></strong> <?php esc_html_e('Communication error. Please try again.', 'pn-customers-manager'); ?>')
								.show();
						},
						complete: function() {
							$btn.prop('disabled', false);
						}
					});
				});

				// Delete page
				$('.pn-cm-delete-offer-page').on('click', function() {
					if (!confirm('<?php esc_html_e('Are you sure you want to delete this page?', 'pn-customers-manager'); ?>')) {
						return;
					}

					var $btn = $(this);
					var pageId = $btn.data('page-id');

					$btn.prop('disabled', true);

					$.ajax({
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						type: 'POST',
						data: {
							action: 'pn_cm_frontend_delete_offer_page',
							nonce: '<?php echo wp_create_nonce('pn_cm_delete_offer_page'); ?>',
							page_id: pageId
						},
						success: function(response) {
							if (response.success) {
								$btn.closest('.pn-cm-page-item').fadeOut(300, function() {
									$(this).remove();
								});
							} else {
								alert(response.data.message || '<?php esc_html_e('Failed to delete page.', 'pn-customers-manager'); ?>');
								$btn.prop('disabled', false);
							}
						},
						error: function() {
							alert('<?php esc_html_e('Communication error.', 'pn-customers-manager'); ?>');
							$btn.prop('disabled', false);
						}
					});
				});
			});
			</script>

			<style>
			.pn-cm-marketing-genuine-offer {
				max-width: 1200px;
				margin: 0 auto;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-toggle-wrapper {
				background: #fff;
				border: 1px solid #ddd;
				border-radius: 8px;
				overflow: hidden;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-toggle {
				display: block;
				padding: 20px;
				background: #f9f9f9;
				border-bottom: 1px solid #ddd;
				cursor: pointer;
				transition: background 0.3s;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-toggle:hover {
				background: #f0f0f0;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-toggle h2 {
				margin: 0;
				display: flex;
				align-items: center;
				gap: 10px;
				font-size: 20px;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-toggle-content {
				display: none;
				padding: 20px;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-toggle-content.pn-customers-manager-toggle-active {
				display: block;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-form-field {
				margin-bottom: 20px;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-label {
				display: block;
				margin-bottom: 5px;
				font-weight: 600;
				color: #333;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-input,
			.pn-cm-marketing-genuine-offer textarea {
				width: 100%;
				padding: 10px;
				border: 1px solid #ddd;
				border-radius: 4px;
				font-size: 14px;
				font-family: inherit;
				box-sizing: border-box;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-input:focus,
			.pn-cm-marketing-genuine-offer textarea:focus {
				outline: none;
				border-color: var(--pn-color-main, #b84700);
				box-shadow: 0 0 0 2px rgba(184, 71, 0, 0.1);
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-description {
				margin: 5px 0 0 0;
				font-size: 12px;
				color: #666;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn {
				display: inline-flex;
				align-items: center;
				gap: 5px;
				padding: 10px 20px;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
				font-weight: 500;
				transition: all 0.3s;
				text-decoration: none;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn-primary {
				background: var(--pn-color-main, #b84700);
				color: white;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn-primary:hover {
				background: #964100;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn-secondary {
				background: var(--pn-color-main-blue, #2563EB);
				color: white;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn-secondary:hover {
				background: #1d4ed8;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn-transparent {
				background: transparent;
				border: 1px solid #ddd;
				color: #333;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn-transparent:hover {
				background: #f9f9f9;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn-small {
				padding: 5px 10px;
				font-size: 13px;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-btn:disabled {
				opacity: 0.6;
				cursor: not-allowed;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-form-actions {
				display: flex;
				gap: 10px;
				flex-wrap: wrap;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-message {
				padding: 15px;
				border-radius: 4px;
				border: 1px solid;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-message-success {
				background: #d4edda;
				border-color: #c3e6cb;
				color: #155724;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-message-error {
				background: #f8d7da;
				border-color: #f5c6cb;
				color: #721c24;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-badge {
				display: inline-block;
				background: var(--pn-color-main, #b84700);
				color: white;
				padding: 2px 8px;
				border-radius: 10px;
				font-size: 12px;
				margin-left: 10px;
			}
			.pn-cm-marketing-genuine-offer .pn-cm-page-item {
				background: #fff;
			}
			.pn-cm-marketing-genuine-offer .pn-cm-page-info strong {
				display: block;
				margin-bottom: 5px;
				font-size: 15px;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-text-small {
				font-size: 12px;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-text-muted {
				color: #666;
			}
			.pn-cm-marketing-genuine-offer .pn-cm-page-actions {
				display: flex;
				gap: 5px;
			}
			.pn-cm-marketing-genuine-offer .pn-customers-manager-mb-5 { margin-bottom: 5px !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-mb-10 { margin-bottom: 10px !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-mb-15 { margin-bottom: 15px !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-mb-20 { margin-bottom: 20px !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-display-flex { display: flex !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-gap-10 { gap: 10px !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-align-items-center { align-items: center !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-justify-content-space-between { justify-content: space-between !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-p-15 { padding: 15px !important; }
			.pn-cm-marketing-genuine-offer .pn-customers-manager-p-20 { padding: 20px !important; }
			.pn-cm-marketing-genuine-offer .material-icons-outlined {
				font-size: 20px;
				vertical-align: middle;
			}
			</style>

			<?php if (!empty($data['business_name']) || !empty($data['tagline'])) : ?>
			<!-- Hero Section -->
			<section class="pn-cm-marketing-hero">
				<?php if (!empty($data['business_name'])) : ?>
					<h1><?php echo esc_html($data['business_name']); ?></h1>
				<?php endif; ?>

				<?php if (!empty($data['tagline'])) : ?>
					<p><?php echo esc_html($data['tagline']); ?></p>
				<?php endif; ?>

				<?php if (!empty($data['cta_primary_url'])) : ?>
					<a href="<?php echo esc_url($data['cta_primary_url']); ?>" class="pn-cm-btn">
						<?php echo esc_html($data['cta_primary_text']); ?>
					</a>
				<?php endif; ?>
			</section>
			<?php endif; ?>

			<?php if (!empty($data['problems']) && is_array($data['problems'])) : ?>
			<!-- Problems Section -->
			<section class="pn-cm-marketing-problems">
				<h2><?php esc_html_e('Are you facing these challenges?', 'pn-customers-manager'); ?></h2>
				<ul>
					<?php foreach ($data['problems'] as $problem) : ?>
						<?php if (!empty($problem)) : ?>
							<li><?php echo esc_html($problem); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php endif; ?>

			<?php if (!empty($data['description'])) : ?>
			<!-- Solution Section -->
			<section class="pn-cm-marketing-solution">
				<h2><?php esc_html_e('The Solution', 'pn-customers-manager'); ?></h2>
				<div class="pn-cm-marketing-solution-content">
					<?php echo wp_kses_post(wpautop($data['description'])); ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if (!empty($data['transformation'])) : ?>
			<!-- Transformation Section -->
			<section class="pn-cm-marketing-transformation">
				<h2><?php esc_html_e('Your Transformation', 'pn-customers-manager'); ?></h2>
				<div class="pn-cm-marketing-transformation-content">
					<?php echo wp_kses_post(wpautop($data['transformation'])); ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if (!empty($data['process_title']) && is_array($data['process_title'])) : ?>
			<!-- Process Section -->
			<section class="pn-cm-marketing-process">
				<h2><?php esc_html_e('How It Works', 'pn-customers-manager'); ?></h2>
				<div class="pn-cm-marketing-process-steps">
					<?php foreach ($data['process_title'] as $index => $title) : ?>
						<?php if (!empty($title)) : ?>
							<div class="pn-cm-marketing-process-step">
								<div class="pn-cm-marketing-process-step-number"><?php echo esc_html($index + 1); ?></div>
								<h3><?php echo esc_html($title); ?></h3>
								<?php if (!empty($data['process_desc'][$index])) : ?>
									<p><?php echo esc_html($data['process_desc'][$index]); ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if (!empty($data['services_name']) && is_array($data['services_name'])) : ?>
			<!-- Pricing Section -->
			<section class="pn-cm-marketing-pricing">
				<h2><?php esc_html_e('Choose Your Plan', 'pn-customers-manager'); ?></h2>
				<div class="pn-cm-marketing-pricing-grid">
					<?php foreach ($data['services_name'] as $index => $name) : ?>
						<?php if (!empty($name)) : ?>
							<div class="pn-cm-marketing-pricing-card">
								<h3><?php echo esc_html($name); ?></h3>
								<?php if (!empty($data['services_price'][$index])) : ?>
									<div class="price"><?php echo esc_html($data['services_price'][$index]); ?></div>
								<?php endif; ?>
								<?php if (!empty($data['services_desc'][$index])) : ?>
									<div class="description">
										<?php echo wp_kses_post(wpautop($data['services_desc'][$index])); ?>
									</div>
								<?php endif; ?>
								<?php if (!empty($data['cta_primary_url'])) : ?>
									<a href="<?php echo esc_url($data['cta_primary_url']); ?>" class="pn-cm-btn">
										<?php echo esc_html($data['cta_primary_text']); ?>
									</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if (!empty($data['testimonials_text']) && is_array($data['testimonials_text'])) : ?>
			<!-- Testimonials Section -->
			<section class="pn-cm-marketing-testimonials">
				<h2><?php esc_html_e('What Our Clients Say', 'pn-customers-manager'); ?></h2>
				<div class="pn-cm-marketing-testimonials-grid">
					<?php foreach ($data['testimonials_text'] as $index => $testimonial) : ?>
						<?php if (!empty($testimonial)) : ?>
							<div class="pn-cm-marketing-testimonial">
								<p><?php echo esc_html($testimonial); ?></p>
								<?php if (!empty($data['testimonials_author'][$index])) : ?>
									<div class="pn-cm-marketing-testimonial-author">
										— <?php echo esc_html($data['testimonials_author'][$index]); ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if (!empty($data['cta_primary_url'])) : ?>
			<!-- Final CTA Section -->
			<section class="pn-cm-marketing-cta">
				<h2><?php esc_html_e('Ready to Get Started?', 'pn-customers-manager'); ?></h2>
				<p><?php esc_html_e('Take the first step towards transformation today.', 'pn-customers-manager'); ?></p>
				<a href="<?php echo esc_url($data['cta_primary_url']); ?>" class="pn-cm-btn">
					<?php echo esc_html($data['cta_primary_text']); ?>
				</a>
				<?php if (!empty($data['cta_secondary_url']) && !empty($data['cta_secondary_text'])) : ?>
					<a href="<?php echo esc_url($data['cta_secondary_url']); ?>" class="pn-cm-btn pn-cm-btn-secondary">
						<?php echo esc_html($data['cta_secondary_text']); ?>
					</a>
				<?php endif; ?>
			</section>
			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the sales presentation shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_sales_presentation_shortcode($atts = []) {
		// Enqueue assets
		self::enqueue_frontend_assets();

		$atts = shortcode_atts([], $atts, 'pn-customers-manager-sales-presentation');
		$data = self::get_marketing_data();
		$created_pages = get_option('pn_cm_sales_presentation_pages', []);

		// Filter out deleted pages
		$created_pages = array_filter($created_pages, function($page_id) {
			return get_post($page_id) !== null;
		});

		ob_start();
		?>
		<div class="pn-cm-marketing-sales-presentation pn-customers-manager-frontend-editor">

			<!-- Marketing Data Editor Toggle -->
			<div class="pn-customers-manager-toggle-wrapper pn-customers-manager-section-wrapper pn-customers-manager-mb-20">
				<a href="#" class="pn-customers-manager-toggle pn-customers-manager-width-100-percent pn-customers-manager-text-decoration-none">
					<h2 class="pn-customers-manager-mb-0">
						<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">slideshow</span>
						<?php esc_html_e('Sales Presentation Data', 'pn-customers-manager'); ?>
					</h2>
				</a>
				<div class="pn-customers-manager-toggle-content">
					<form id="pn-cm-sales-presentation-form" class="pn-customers-manager-form">
						<?php wp_nonce_field('pn_cm_save_offer_data', 'pn_cm_nonce'); ?>
						<input type="hidden" name="page_type" value="sales_presentation">

						<!-- Basic Info Section -->
						<div class="pn-cm-form-subsection">
							<h3 class="pn-cm-subsection-toggle pn-customers-manager-cursor-pointer">
								<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">info</span>
								<?php esc_html_e('Basic Information', 'pn-customers-manager'); ?>
								<span class="material-icons-outlined pn-cm-toggle-icon pn-customers-manager-vertical-align-middle">expand_more</span>
							</h3>
							<div class="pn-cm-subsection-content">
								<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
									<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
										<?php esc_html_e('Business Name', 'pn-customers-manager'); ?>
									</label>
									<input type="text" name="business_name" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($data['business_name']); ?>">
								</div>

								<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
									<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
										<?php esc_html_e('Tagline', 'pn-customers-manager'); ?>
									</label>
									<textarea name="tagline" rows="2" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($data['tagline']); ?></textarea>
									<p class="pn-customers-manager-description"><?php esc_html_e('Short catchphrase (1 line).', 'pn-customers-manager'); ?></p>
								</div>

								<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
									<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
										<?php esc_html_e('Business Description', 'pn-customers-manager'); ?>
									</label>
									<textarea name="description" rows="4" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($data['description']); ?></textarea>
									<p class="pn-customers-manager-description"><?php esc_html_e('Brief overview of your business (2-3 paragraphs).', 'pn-customers-manager'); ?></p>
								</div>
							</div>
						</div>

						<!-- Services Section -->
						<div class="pn-cm-form-subsection">
							<h3 class="pn-cm-subsection-toggle pn-customers-manager-cursor-pointer">
								<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">store</span>
								<?php esc_html_e('Services/Products', 'pn-customers-manager'); ?>
								<span class="material-icons-outlined pn-cm-toggle-icon pn-customers-manager-vertical-align-middle">expand_more</span>
							</h3>
							<div class="pn-cm-subsection-content">
								<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
									<?php
									$services_names = is_array($data['services_name']) ? $data['services_name'] : [];
									$services_prices = is_array($data['services_price']) ? $data['services_price'] : [];
									$services_descs = is_array($data['services_desc']) ? $data['services_desc'] : [];
									if (empty($services_names)) {
										$services_names = [''];
										$services_prices = [''];
										$services_descs = [''];
									}
									foreach ($services_names as $index => $name) :
									?>
										<div class="pn-customers-manager-service-group pn-customers-manager-mb-15">
											<input type="text" name="services_name[]" placeholder="<?php esc_attr_e('Service name', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-5" value="<?php echo esc_attr($name); ?>">
											<input type="text" name="services_price[]" placeholder="<?php esc_attr_e('Price', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-5" value="<?php echo esc_attr($services_prices[$index] ?? ''); ?>">
											<textarea name="services_desc[]" rows="2" placeholder="<?php esc_attr_e('Description', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent"><?php echo esc_textarea($services_descs[$index] ?? ''); ?></textarea>
										</div>
									<?php endforeach; ?>
									<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-cm-add-service-sp" style="margin-top: 5px;">
										<span class="material-icons-outlined">add</span>
										<?php esc_html_e('Add Service', 'pn-customers-manager'); ?>
									</button>
									<p class="pn-customers-manager-description"><?php esc_html_e('List your services/products with prices.', 'pn-customers-manager'); ?></p>
								</div>
							</div>
						</div>

						<!-- Testimonials Section -->
						<div class="pn-cm-form-subsection">
							<h3 class="pn-cm-subsection-toggle pn-customers-manager-cursor-pointer">
								<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">format_quote</span>
								<?php esc_html_e('Testimonials', 'pn-customers-manager'); ?>
								<span class="material-icons-outlined pn-cm-toggle-icon pn-customers-manager-vertical-align-middle">expand_more</span>
							</h3>
							<div class="pn-cm-subsection-content">
								<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
									<?php
									$testimonials = is_array($data['testimonials_text']) ? $data['testimonials_text'] : [];
									$testimonial_authors = is_array($data['testimonials_author']) ? $data['testimonials_author'] : [];
									if (empty($testimonials)) {
										$testimonials = [''];
										$testimonial_authors = [''];
									}
									foreach ($testimonials as $index => $testimonial) :
									?>
										<div class="pn-customers-manager-testimonial-group pn-customers-manager-mb-15">
											<textarea name="testimonials_text[]" rows="2" placeholder="<?php esc_attr_e('Testimonial quote', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-mb-5"><?php echo esc_textarea($testimonial); ?></textarea>
											<input type="text" name="testimonials_author[]" placeholder="<?php esc_attr_e('Author name and company', 'pn-customers-manager'); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($testimonial_authors[$index] ?? ''); ?>">
										</div>
									<?php endforeach; ?>
									<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-cm-add-testimonial-sp" style="margin-top: 5px;">
										<span class="material-icons-outlined">add</span>
										<?php esc_html_e('Add Testimonial', 'pn-customers-manager'); ?>
									</button>
									<p class="pn-customers-manager-description"><?php esc_html_e('Customer testimonials.', 'pn-customers-manager'); ?></p>
								</div>
							</div>
						</div>

						<!-- Call to Action Section -->
						<div class="pn-cm-form-subsection">
							<h3 class="pn-cm-subsection-toggle pn-customers-manager-cursor-pointer">
								<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">call_to_action</span>
								<?php esc_html_e('Call to Action', 'pn-customers-manager'); ?>
								<span class="material-icons-outlined pn-cm-toggle-icon pn-customers-manager-vertical-align-middle">expand_more</span>
							</h3>
							<div class="pn-cm-subsection-content">
								<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
									<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
										<?php esc_html_e('Primary CTA Text', 'pn-customers-manager'); ?>
									</label>
									<input type="text" name="cta_primary_text" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($data['cta_primary_text']); ?>">
								</div>

								<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
									<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
										<?php esc_html_e('Primary CTA URL', 'pn-customers-manager'); ?>
									</label>
									<input type="url" name="cta_primary_url" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="<?php echo esc_attr($data['cta_primary_url']); ?>">
								</div>
							</div>
						</div>

						<div class="pn-customers-manager-form-field pn-customers-manager-mb-20">
							<label class="pn-customers-manager-label pn-customers-manager-display-block pn-customers-manager-mb-5">
								<?php esc_html_e('Additional Comments for AI', 'pn-customers-manager'); ?>
							</label>
							<textarea name="additional_comments" rows="3" class="pn-customers-manager-input pn-customers-manager-width-100-percent" placeholder="<?php esc_attr_e('Any specific instructions or context for the AI when generating the page...', 'pn-customers-manager'); ?>"></textarea>
							<p class="pn-customers-manager-description"><?php esc_html_e('Optional: Provide additional context or instructions for the AI.', 'pn-customers-manager'); ?></p>
						</div>

						<div class="pn-customers-manager-form-actions pn-customers-manager-display-flex pn-customers-manager-gap-10">
							<button type="submit" name="action" value="save" class="pn-customers-manager-btn pn-customers-manager-btn-primary">
								<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">save</span>
								<?php esc_html_e('Save Data', 'pn-customers-manager'); ?>
							</button>
							<button type="submit" name="action" value="generate" class="pn-customers-manager-btn pn-customers-manager-btn-secondary" id="pn-cm-generate-page-sp-btn">
								<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">auto_awesome</span>
								<?php esc_html_e('Generate Page with AI', 'pn-customers-manager'); ?>
							</button>
						</div>

						<div id="pn-cm-sales-presentation-message" class="pn-customers-manager-message" style="display:none; margin-top: 20px;"></div>
					</form>
				</div>
			</div>

			<!-- Created Pages List Toggle -->
			<?php if (!empty($created_pages)) : ?>
			<div class="pn-customers-manager-toggle-wrapper pn-customers-manager-section-wrapper pn-customers-manager-mb-20">
				<a href="#" class="pn-customers-manager-toggle pn-customers-manager-width-100-percent pn-customers-manager-text-decoration-none">
					<h2 class="pn-customers-manager-mb-0">
						<span class="material-icons-outlined pn-customers-manager-vertical-align-middle">pages</span>
						<?php esc_html_e('Created Presentations', 'pn-customers-manager'); ?>
						<span class="pn-customers-manager-badge"><?php echo count($created_pages); ?></span>
					</h2>
				</a>
				<div class="pn-customers-manager-toggle-content">
					<div class="pn-cm-pages-list">
						<?php foreach ($created_pages as $page_id) : ?>
							<?php
							$post = get_post($page_id);
							if (!$post) continue;
							?>
							<div class="pn-cm-page-item pn-customers-manager-display-flex pn-customers-manager-align-items-center pn-customers-manager-justify-content-space-between pn-customers-manager-p-15 pn-customers-manager-mb-10" style="border: 1px solid #ddd; border-radius: 4px;">
								<div class="pn-cm-page-info">
									<strong><?php echo esc_html($post->post_title); ?></strong>
									<div class="pn-customers-manager-text-small pn-customers-manager-text-muted">
										<?php
										$status_labels = [
											'publish' => __('Published', 'pn-customers-manager'),
											'draft'   => __('Draft', 'pn-customers-manager'),
											'pending' => __('Pending', 'pn-customers-manager'),
										];
										echo esc_html($status_labels[$post->post_status] ?? $post->post_status);
										?>
										• <?php echo get_the_date('', $page_id); ?>
									</div>
								</div>
								<div class="pn-cm-page-actions pn-customers-manager-display-flex pn-customers-manager-gap-10">
									<a href="<?php echo get_edit_post_link($page_id); ?>" class="pn-customers-manager-btn pn-customers-manager-btn-small pn-customers-manager-btn-transparent">
										<span class="material-icons-outlined">edit</span>
										<?php esc_html_e('Edit', 'pn-customers-manager'); ?>
									</a>
									<a href="<?php echo get_permalink($page_id); ?>" target="_blank" class="pn-customers-manager-btn pn-customers-manager-btn-small pn-customers-manager-btn-transparent">
										<span class="material-icons-outlined">visibility</span>
										<?php esc_html_e('View', 'pn-customers-manager'); ?>
									</a>
									<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-small pn-customers-manager-btn-danger pn-cm-delete-sales-presentation-page" data-page-id="<?php echo esc_attr($page_id); ?>">
										<span class="material-icons-outlined">delete</span>
										<?php esc_html_e('Delete', 'pn-customers-manager'); ?>
									</button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Inline JavaScript for toggles and form functionality -->
			<script>
			jQuery(document).ready(function($) {
				// Main toggles
				$('.pn-customers-manager-toggle').on('click', function(e) {
					e.preventDefault();
					var $content = $(this).next('.pn-customers-manager-toggle-content');
					$content.slideToggle(300);
					$content.toggleClass('pn-customers-manager-toggle-active');
				});

				// Subsection toggles
				$('.pn-cm-subsection-toggle').on('click', function() {
					var $content = $(this).next('.pn-cm-subsection-content');
					var $icon = $(this).find('.pn-cm-toggle-icon');
					$content.slideToggle(300);
					$icon.text($icon.text() === 'expand_more' ? 'expand_less' : 'expand_more');
				});

				// Add service
				$('.pn-cm-add-service-sp').on('click', function() {
					var $template = $('.pn-customers-manager-service-group:first').clone();
					$template.find('input, textarea').val('');
					$(this).before($template);
				});

				// Add testimonial
				$('.pn-cm-add-testimonial-sp').on('click', function() {
					var $template = $('.pn-customers-manager-testimonial-group:first').clone();
					$template.find('input, textarea').val('');
					$(this).before($template);
				});

				// Form submission
				$('#pn-cm-sales-presentation-form').on('submit', function(e) {
					e.preventDefault();

					var $form = $(this);
					var $btn = $form.find('button[type="submit"]:focus');
					var action = $btn.val();
					var formData = $form.serialize() + '&form_action=' + action;

					$btn.prop('disabled', true);
					$('#pn-cm-sales-presentation-message').hide();

					$.ajax({
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						type: 'POST',
						data: formData + '&action=pn_cm_frontend_save_offer_data',
						success: function(response) {
							if (response.success) {
								$('#pn-cm-sales-presentation-message')
									.removeClass('pn-customers-manager-message-error')
									.addClass('pn-customers-manager-message-success')
									.html('<strong><?php esc_html_e('Success!', 'pn-customers-manager'); ?></strong> ' + response.data.message)
									.show();

								if (response.data.page_url) {
									setTimeout(function() {
										location.reload();
									}, 2000);
								}
							} else {
								$('#pn-cm-sales-presentation-message')
									.removeClass('pn-customers-manager-message-success')
									.addClass('pn-customers-manager-message-error')
									.html('<strong><?php esc_html_e('Error!', 'pn-customers-manager'); ?></strong> ' + (response.data.message || '<?php esc_html_e('An error occurred.', 'pn-customers-manager'); ?>'))
									.show();
							}
						},
						error: function() {
							$('#pn-cm-sales-presentation-message')
								.removeClass('pn-customers-manager-message-success')
								.addClass('pn-customers-manager-message-error')
								.html('<strong><?php esc_html_e('Error!', 'pn-customers-manager'); ?></strong> <?php esc_html_e('Communication error. Please try again.', 'pn-customers-manager'); ?>')
								.show();
						},
						complete: function() {
							$btn.prop('disabled', false);
						}
					});
				});

				// Delete page
				$('.pn-cm-delete-sales-presentation-page').on('click', function() {
					if (!confirm('<?php esc_html_e('Are you sure you want to delete this page?', 'pn-customers-manager'); ?>')) {
						return;
					}

					var $btn = $(this);
					var pageId = $btn.data('page-id');

					$btn.prop('disabled', true);

					$.ajax({
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						type: 'POST',
						data: {
							action: 'pn_cm_frontend_delete_offer_page',
							nonce: '<?php echo wp_create_nonce('pn_cm_delete_offer_page'); ?>',
							page_id: pageId,
							page_type: 'sales_presentation'
						},
						success: function(response) {
							if (response.success) {
								$btn.closest('.pn-cm-page-item').fadeOut(300, function() {
									$(this).remove();
								});
							} else {
								alert(response.data.message || '<?php esc_html_e('Failed to delete page.', 'pn-customers-manager'); ?>');
								$btn.prop('disabled', false);
							}
						},
						error: function() {
							alert('<?php esc_html_e('Communication error.', 'pn-customers-manager'); ?>');
							$btn.prop('disabled', false);
						}
					});
				});
			});
			</script>

			<style>
			.pn-cm-marketing-sales-presentation {
				max-width: 1200px;
				margin: 0 auto;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-toggle-wrapper {
				background: #fff;
				border: 1px solid #ddd;
				border-radius: 8px;
				overflow: hidden;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-toggle {
				display: block;
				padding: 20px;
				background: #f9f9f9;
				border-bottom: 1px solid #ddd;
				cursor: pointer;
				transition: background 0.3s;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-toggle:hover {
				background: #f0f0f0;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-toggle h2 {
				margin: 0;
				display: flex;
				align-items: center;
				gap: 10px;
				font-size: 20px;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-toggle-content {
				display: none;
				padding: 20px;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-toggle-content.pn-customers-manager-toggle-active {
				display: block;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-form-field {
				margin-bottom: 20px;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-label {
				display: block;
				margin-bottom: 5px;
				font-weight: 600;
				color: #333;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-input,
			.pn-cm-marketing-sales-presentation textarea {
				width: 100%;
				padding: 10px;
				border: 1px solid #ddd;
				border-radius: 4px;
				font-size: 14px;
				font-family: inherit;
				box-sizing: border-box;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-input:focus,
			.pn-cm-marketing-sales-presentation textarea:focus {
				outline: none;
				border-color: var(--pn-color-main, #b84700);
				box-shadow: 0 0 0 2px rgba(184, 71, 0, 0.1);
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-description {
				margin: 5px 0 0 0;
				font-size: 12px;
				color: #666;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn {
				display: inline-flex;
				align-items: center;
				gap: 5px;
				padding: 10px 20px;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
				font-weight: 500;
				transition: all 0.3s;
				text-decoration: none;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-primary {
				background: var(--pn-color-main, #b84700);
				color: white;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-primary:hover {
				background: #964100;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-secondary {
				background: var(--pn-color-main-blue, #2563EB);
				color: white;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-secondary:hover {
				background: #1d4ed8;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-transparent {
				background: transparent;
				border: 1px solid #ddd;
				color: #333;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-transparent:hover {
				background: #f9f9f9;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-small {
				padding: 5px 10px;
				font-size: 13px;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-danger {
				background: #dc3545;
				color: white;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn-danger:hover {
				background: #c82333;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-btn:disabled {
				opacity: 0.6;
				cursor: not-allowed;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-form-actions {
				display: flex;
				gap: 10px;
				flex-wrap: wrap;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-message {
				padding: 15px;
				border-radius: 4px;
				border: 1px solid;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-message-success {
				background: #d4edda;
				border-color: #c3e6cb;
				color: #155724;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-message-error {
				background: #f8d7da;
				border-color: #f5c6cb;
				color: #721c24;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-badge {
				display: inline-block;
				background: var(--pn-color-main, #b84700);
				color: white;
				padding: 2px 8px;
				border-radius: 10px;
				font-size: 12px;
				margin-left: 10px;
			}
			.pn-cm-marketing-sales-presentation .pn-cm-page-item {
				background: #fff;
			}
			.pn-cm-marketing-sales-presentation .pn-cm-page-info strong {
				display: block;
				margin-bottom: 5px;
				font-size: 15px;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-text-small {
				font-size: 12px;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-text-muted {
				color: #666;
			}
			.pn-cm-marketing-sales-presentation .pn-cm-page-actions {
				display: flex;
				gap: 5px;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-cursor-pointer {
				cursor: pointer;
			}
			.pn-cm-form-subsection {
				margin-bottom: 30px;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				overflow: hidden;
			}
			.pn-cm-subsection-toggle {
				margin: 0;
				padding: 15px 20px;
				background: #f8f9fa;
				cursor: pointer;
				transition: background 0.3s;
				display: flex;
				align-items: center;
				gap: 10px;
				font-size: 16px;
				border-bottom: 1px solid #e0e0e0;
			}
			.pn-cm-subsection-toggle:hover {
				background: #e9ecef;
			}
			.pn-cm-toggle-icon {
				margin-left: auto;
			}
			.pn-cm-subsection-content {
				padding: 20px;
				display: none;
			}
			.pn-cm-marketing-sales-presentation .pn-customers-manager-mb-5 { margin-bottom: 5px !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-mb-10 { margin-bottom: 10px !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-mb-15 { margin-bottom: 15px !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-mb-20 { margin-bottom: 20px !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-display-flex { display: flex !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-gap-10 { gap: 10px !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-align-items-center { align-items: center !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-justify-content-space-between { justify-content: space-between !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-p-15 { padding: 15px !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-width-100-percent { width: 100% !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-display-block { display: block !important; }
			.pn-cm-marketing-sales-presentation .pn-customers-manager-vertical-align-middle { vertical-align: middle !important; }
			.pn-cm-marketing-sales-presentation .material-icons-outlined {
				font-size: 20px;
				vertical-align: middle;
			}
			</style>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Register Gutenberg blocks for marketing pages.
	 */
	public static function register_blocks() {
		if (!function_exists('register_block_type')) {
			return;
		}

		// Business Offer block
		register_block_type('pn-customers-manager/genuine-offer', [
			'render_callback' => [__CLASS__, 'render_genuine_offer_shortcode'],
			'attributes'      => [],
		]);

		// Sales Presentation block
		register_block_type('pn-customers-manager/sales-presentation', [
			'render_callback' => [__CLASS__, 'render_sales_presentation_shortcode'],
			'attributes'      => [],
		]);
	}

	/**
	 * Add noindex meta tag to marketing pages.
	 * This prevents search engines from indexing these pages.
	 *
	 * @param array $robots Current robots directives.
	 * @return array Modified robots directives.
	 */
	public static function add_noindex_to_marketing_pages($robots) {
		if (is_singular() && self::is_marketing_page()) {
			$robots['noindex'] = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}

	/**
	 * Check if the current page is a marketing page.
	 *
	 * @return bool True if current page contains marketing shortcodes/blocks.
	 */
	private static function is_marketing_page() {
		global $post;

		if (!$post) {
			return false;
		}

		// Check if the page contains any of the marketing shortcodes or blocks
		$marketing_indicators = [
			'[pn-customers-manager-genuine-offer]',
			'[pn-customers-manager-sales-presentation]',
			'wp:pn-customers-manager/genuine-offer',
			'wp:pn-customers-manager/sales-presentation',
		];

		foreach ($marketing_indicators as $indicator) {
			if (strpos($post->post_content, $indicator) !== false) {
				return true;
			}
		}

		// Also check option pages
		foreach (self::$marketing_pages as $option_key => $config) {
			$page_id = get_option($option_key);
			if ($page_id && (int) $page_id === $post->ID) {
				return true;
			}
		}

		return false;
	}

	/* ================================================================
	 * FRONTEND AJAX HANDLERS
	 * ================================================================ */

	/**
	 * Handle frontend AJAX request to save offer data and optionally generate page.
	 */
	public static function handle_frontend_save_offer_data() {
		check_ajax_referer('pn_cm_save_offer_data', 'pn_cm_nonce');

		$form_action = sanitize_text_field($_POST['form_action'] ?? 'save');
		$page_type = sanitize_text_field($_POST['page_type'] ?? 'business_offer');

		// Sanitize and save all marketing data
		$prefix = 'pn_customers_manager_marketing_';
		$fields = [
			'business_name' => 'text',
			'tagline' => 'textarea',
			'description' => 'textarea',
			'target_description' => 'textarea',
			'solution_headline' => 'text',
			'solution_description' => 'textarea',
			'transformation' => 'textarea',
			'process_steps' => 'textarea',
			'services' => 'textarea',
			'testimonials' => 'textarea',
			'cta_primary_text' => 'text',
			'cta_primary_url' => 'url',
		];

		foreach ($fields as $field => $type) {
			$value = $_POST[$field] ?? '';
			if ($type === 'url') {
				update_option($prefix . $field, esc_url_raw($value));
			} elseif ($type === 'textarea') {
				update_option($prefix . $field, sanitize_textarea_field($value));
			} else {
				update_option($prefix . $field, sanitize_text_field($value));
			}
		}

		// Save array fields
		$array_fields = [
			'problems' => 'problems_text',
			'process_title' => 'process_title',
			'process_desc' => 'process_desc',
			'services_name' => 'services_name',
			'services_price' => 'services_price',
			'services_desc' => 'services_desc',
			'testimonials_text' => 'testimonials_text',
			'testimonials_author' => 'testimonials_author',
		];

		foreach ($array_fields as $post_key => $option_key) {
			$values = isset($_POST[$post_key]) && is_array($_POST[$post_key]) ? $_POST[$post_key] : [];
			$sanitized = array_map('sanitize_textarea_field', $values);
			update_option($prefix . $option_key, $sanitized);
		}

		// If action is just save, return success
		if ($form_action === 'save') {
			wp_send_json_success([
				'message' => __('Data saved successfully!', 'pn-customers-manager'),
			]);
		}

		// If action is generate, create the page with AI
		if ($form_action === 'generate') {
			require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-offer-page-generator.php';

			// Prepare marketing data
			$marketing_data = [];
			foreach ($fields as $field => $type) {
				$marketing_data[$field] = get_option($prefix . $field, '');
			}

			// Convert problems array to text
			$problems_array = get_option($prefix . 'problems_text', []);
			$marketing_data['problems'] = is_array($problems_array) ? implode("\n", array_filter($problems_array)) : '';

			// Get additional comments
			$additional_comments = sanitize_textarea_field($_POST['additional_comments'] ?? '');

			// Get colors from settings
			$colors = [
				'main' => get_option('pn_customers_manager_color_main', '#b84700'),
				'main_alt' => get_option('pn_customers_manager_color_main_alt', '#F5E6D0'),
				'main_blue' => get_option('pn_customers_manager_color_main_blue', '#2563EB'),
				'main_grey' => get_option('pn_customers_manager_color_main_grey', '#6B6B80'),
			];

			// Check if OpenAI API key is configured
			$api_key = get_option('pn_customers_manager_whatsapp_openai_key', '');
			if (empty($api_key)) {
				error_log('PN_CM_Frontend: OpenAI API key not configured');
				wp_send_json_error([
					'message' => __('OpenAI API key is not configured. Please go to Settings > PN Customers Manager > WhatsApp AI and configure your OpenAI API key.', 'pn-customers-manager'),
				]);
			}

			error_log('PN_CM_Frontend: Starting AI generation with colors: ' . json_encode($colors));
			error_log('PN_CM_Frontend: Marketing data keys: ' . implode(', ', array_keys($marketing_data)));

			// Generate with AI
			$generator = new PN_CUSTOMERS_MANAGER_Offer_Page_Generator();

			try {
				$gutenberg_blocks = $generator->generate_gutenberg_with_colors($marketing_data, $additional_comments, $page_type, $colors);

				if (!$gutenberg_blocks) {
					error_log('PN_CM_Frontend: generate_gutenberg_with_colors returned empty/false');
					wp_send_json_error([
						'message' => __('AI generation returned empty content. Check debug.log for details. The API might have returned an error or the content was filtered out.', 'pn-customers-manager'),
					]);
				}

				error_log('PN_CM_Frontend: Successfully generated content, length: ' . strlen($gutenberg_blocks));
			} catch (Exception $e) {
				error_log('PN_CM_Frontend: Exception during generation: ' . $e->getMessage());
				wp_send_json_error([
					'message' => __('Exception during AI generation: ', 'pn-customers-manager') . $e->getMessage(),
				]);
			}

			// Create page with appropriate title
			$page_titles = [
				'business_offer' => __('Business Offer - %s', 'pn-customers-manager'),
				'sales_presentation' => __('Sales Presentation - %s', 'pn-customers-manager'),
			];
			$page_title = sprintf(
				$page_titles[$page_type] ?? __('Generated Page - %s', 'pn-customers-manager'),
				date_i18n('Y-m-d H:i', current_time('timestamp'))
			);

			$page_id = wp_insert_post([
				'post_title'   => $page_title,
				'post_content' => $gutenberg_blocks,
				'post_status'  => 'draft',
				'post_type'    => 'page',
				'post_author'  => get_current_user_id(),
				'meta_input'   => [
					'_pn_cm_page_type'       => $page_type,
					'_pn_cm_generated_by_ai' => 'yes',
					'_pn_cm_generation_date' => current_time('mysql'),
				],
			]);

			if (!$page_id || is_wp_error($page_id)) {
				wp_send_json_error([
					'message' => __('Failed to create page. Please try again.', 'pn-customers-manager'),
				]);
			}

			// Track page
			$page_tracking_options = [
				'business_offer' => 'pn_cm_business_offer_pages',
				'sales_presentation' => 'pn_cm_sales_presentation_pages',
			];
			$tracking_option = $page_tracking_options[$page_type] ?? 'pn_cm_business_offer_pages';

			$pages = get_option($tracking_option, []);
			if (!is_array($pages)) {
				$pages = [];
			}
			if (!in_array($page_id, $pages)) {
				$pages[] = $page_id;
				update_option($tracking_option, $pages);
			}

			wp_send_json_success([
				'message' => __('Page generated successfully! The page has been created as a draft and is ready for editing.', 'pn-customers-manager'),
				'page_url' => get_edit_post_link($page_id, 'raw'),
				'page_id' => $page_id,
			]);
		}

		wp_send_json_error([
			'message' => __('Invalid action.', 'pn-customers-manager'),
		]);
	}

	/**
	 * Handle frontend AJAX request to delete offer page.
	 */
	public static function handle_frontend_delete_offer_page() {
		check_ajax_referer('pn_cm_delete_offer_page', 'nonce');

		$page_id = absint($_POST['page_id'] ?? 0);
		$page_type = sanitize_text_field($_POST['page_type'] ?? 'business_offer');

		if (!$page_id) {
			wp_send_json_error([
				'message' => __('Invalid page ID.', 'pn-customers-manager'),
			]);
		}

		// Check permissions
		if (!current_user_can('delete_post', $page_id)) {
			wp_send_json_error([
				'message' => __('You do not have permission to delete this page.', 'pn-customers-manager'),
			]);
		}

		// Delete page
		$result = wp_delete_post($page_id, true);

		if (!$result) {
			wp_send_json_error([
				'message' => __('Failed to delete page.', 'pn-customers-manager'),
			]);
		}

		// Remove from tracking based on page type
		$page_tracking_options = [
			'business_offer' => 'pn_cm_business_offer_pages',
			'sales_presentation' => 'pn_cm_sales_presentation_pages',
		];
		$tracking_option = $page_tracking_options[$page_type] ?? 'pn_cm_business_offer_pages';

		$pages = get_option($tracking_option, []);
		if (is_array($pages)) {
			$pages = array_filter($pages, function($id) use ($page_id) {
				return $id != $page_id;
			});
			update_option($tracking_option, array_values($pages));
		}

		wp_send_json_success([
			'message' => __('Page deleted successfully.', 'pn-customers-manager'),
		]);
	}
}
