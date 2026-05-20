<?php
/**
 * AI-powered offer page generator.
 *
 * Uses OpenAI to generate persuasive marketing content for offers
 * and creates WordPress pages with Gutenberg blocks.
 *
 * @link       padresenlanube.com/
 * @since      1.0.70
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_AI_Offer_Generator {

	use PN_CM_AI_Chat_Common;

	/* ================================================================
	 * TRAIT ABSTRACT METHODS IMPLEMENTATION
	 * ================================================================ */

	protected static function platform_prefix() {
		return 'offer_gen_';
	}

	protected static function conversations_table_suffix() {
		return 'pn_cm_offer_generations';
	}

	protected static function platform_display_name() {
		return 'Offer Generator';
	}

	protected static function brand_color() {
		return '#b84700';
	}

	protected static function email_type() {
		return 'pn_cm_offer_generated';
	}

	protected static function log_channel() {
		return 'offer-generator';
	}

	protected static function debug_log_option() {
		return 'pn_customers_manager_offer_gen_debug_log';
	}

	protected static function node_subtype() {
		return 'offer_ai';
	}

	protected static function get_formatting_rules() {
		return '';
	}

	protected static function get_image_rules($include_images) {
		return '';
	}

	protected static function supports_native_images() {
		return false;
	}

	protected static function get_identifier_field() {
		return 'generation_id';
	}

	protected static function get_identifier_label() {
		return 'Generation';
	}

	protected static function get_identifier_value($conversation) {
		return isset($conversation->generation_id) ? $conversation->generation_id : '';
	}

	/* ================================================================
	 * SHORTCODE: AI OFFER GENERATOR WIZARD
	 * ================================================================ */

	/**
	 * Render the AI offer generator wizard shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_generator_form($atts = []) {
		$atts = shortcode_atts([
			'type' => 'genuine', // genuine or sales_presentation
		], $atts, 'pn-customers-manager-ai-offer-generator');

		// Check permissions
		if (!current_user_can('edit_posts')) {
			return '<p>' . esc_html__('You do not have permission to generate offers.', 'pn-customers-manager') . '</p>';
		}

		// Get existing business data from settings
		$business_data = $this->get_business_settings();

		ob_start();
		?>
		<div class="pn-cm-offer-gen-wizard" data-default-type="<?php echo esc_attr($atts['type']); ?>">
			<div class="pn-cm-offer-gen-wizard-header">
				<h2><?php esc_html_e('AI Offer Generator', 'pn-customers-manager'); ?></h2>
				<p><?php esc_html_e('Create professional marketing pages powered by AI in minutes.', 'pn-customers-manager'); ?></p>
			</div>

			<div class="pn-cm-offer-gen-wizard-progress">
				<div class="pn-cm-offer-gen-wizard-progress-bar">
					<div class="pn-cm-offer-gen-wizard-progress-fill" style="width: 25%;"></div>
				</div>
				<div class="pn-cm-offer-gen-wizard-progress-text">
					<?php esc_html_e('Step 1 of 4', 'pn-customers-manager'); ?>
				</div>
			</div>

			<form id="pn-cm-offer-gen-form">
				<?php wp_nonce_field('pn-customers-manager-nonce', 'pn_cm_nonce'); ?>

				<!-- Step 1: Offer Type -->
				<div class="pn-cm-offer-gen-step pn-cm-offer-gen-step-active" data-step="1">
					<h3><?php esc_html_e('Step 1: Choose Offer Type', 'pn-customers-manager'); ?></h3>
					<div class="pn-cm-offer-gen-radio-group">
						<label class="pn-cm-offer-gen-radio-card">
							<input type="radio" name="offer_type" value="genuine" checked>
							<div class="pn-cm-offer-gen-radio-content">
								<h4><?php esc_html_e('Business Offer', 'pn-customers-manager'); ?></h4>
								<p><?php esc_html_e('Complete offer page with problems, solution, transformation, pricing, and social proof.', 'pn-customers-manager'); ?></p>
							</div>
						</label>
						<label class="pn-cm-offer-gen-radio-card">
							<input type="radio" name="offer_type" value="sales_presentation">
							<div class="pn-cm-offer-gen-radio-content">
								<h4><?php esc_html_e('Sales Presentation', 'pn-customers-manager'); ?></h4>
								<p><?php esc_html_e('Professional presentation page focused on services and results.', 'pn-customers-manager'); ?></p>
							</div>
						</label>
					</div>
					<div class="pn-cm-offer-gen-actions">
						<button type="button" class="pn-cm-offer-gen-btn pn-cm-offer-gen-btn-primary pn-cm-offer-gen-next">
							<?php esc_html_e('Continue', 'pn-customers-manager'); ?>
						</button>
					</div>
				</div>

				<!-- Step 2: Business Information -->
				<div class="pn-cm-offer-gen-step" data-step="2">
					<h3><?php esc_html_e('Step 2: Business Information', 'pn-customers-manager'); ?></h3>
					<p class="pn-cm-offer-gen-step-description">
						<?php esc_html_e('This information is pre-filled from your settings. You can edit it for this specific offer.', 'pn-customers-manager'); ?>
					</p>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Business Name', 'pn-customers-manager'); ?></label>
						<input type="text" name="business_name" value="<?php echo esc_attr($business_data['business_name']); ?>" required>
					</div>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Tagline', 'pn-customers-manager'); ?></label>
						<textarea name="tagline" rows="2"><?php echo esc_textarea($business_data['tagline']); ?></textarea>
					</div>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Business Description', 'pn-customers-manager'); ?></label>
						<textarea name="description" rows="4" required><?php echo esc_textarea($business_data['description']); ?></textarea>
					</div>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Target Audience', 'pn-customers-manager'); ?></label>
						<textarea name="target_audience" rows="3" required><?php echo esc_textarea($business_data['target_audience']); ?></textarea>
					</div>

					<div class="pn-cm-offer-gen-actions">
						<button type="button" class="pn-cm-offer-gen-btn pn-cm-offer-gen-prev">
							<?php esc_html_e('Back', 'pn-customers-manager'); ?>
						</button>
						<button type="button" class="pn-cm-offer-gen-btn pn-cm-offer-gen-btn-primary pn-cm-offer-gen-next">
							<?php esc_html_e('Continue', 'pn-customers-manager'); ?>
						</button>
					</div>
				</div>

				<!-- Step 3: Specific Offer -->
				<div class="pn-cm-offer-gen-step" data-step="3">
					<h3><?php esc_html_e('Step 3: Offer Details', 'pn-customers-manager'); ?></h3>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Offer Title', 'pn-customers-manager'); ?></label>
						<input type="text" name="offer_title" placeholder="<?php esc_attr_e('My Premium Consulting Package', 'pn-customers-manager'); ?>" required>
					</div>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('What\'s Included?', 'pn-customers-manager'); ?></label>
						<textarea name="offer_includes" rows="4" placeholder="<?php esc_attr_e('List what the client gets...', 'pn-customers-manager'); ?>" required></textarea>
					</div>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Transformation / Results', 'pn-customers-manager'); ?></label>
						<textarea name="transformation" rows="3" placeholder="<?php esc_attr_e('From struggling with X to achieving Y...', 'pn-customers-manager'); ?>" required></textarea>
					</div>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Price', 'pn-customers-manager'); ?></label>
						<input type="text" name="price" placeholder="<?php esc_attr_e('€997', 'pn-customers-manager'); ?>">
					</div>

					<div class="pn-cm-offer-gen-actions">
						<button type="button" class="pn-cm-offer-gen-btn pn-cm-offer-gen-prev">
							<?php esc_html_e('Back', 'pn-customers-manager'); ?>
						</button>
						<button type="button" class="pn-cm-offer-gen-btn pn-cm-offer-gen-btn-primary pn-cm-offer-gen-next">
							<?php esc_html_e('Continue', 'pn-customers-manager'); ?>
						</button>
					</div>
				</div>

				<!-- Step 4: Customization -->
				<div class="pn-cm-offer-gen-step" data-step="4">
					<h3><?php esc_html_e('Step 4: Customize', 'pn-customers-manager'); ?></h3>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Tone of Voice', 'pn-customers-manager'); ?></label>
						<select name="tone">
							<option value="professional"><?php esc_html_e('Professional', 'pn-customers-manager'); ?></option>
							<option value="conversational" selected><?php esc_html_e('Conversational', 'pn-customers-manager'); ?></option>
							<option value="inspirational"><?php esc_html_e('Inspirational', 'pn-customers-manager'); ?></option>
						</select>
					</div>

					<div class="pn-cm-offer-gen-field">
						<label><?php esc_html_e('Content Length', 'pn-customers-manager'); ?></label>
						<select name="length">
							<option value="short"><?php esc_html_e('Short (concise)', 'pn-customers-manager'); ?></option>
							<option value="medium" selected><?php esc_html_e('Medium (balanced)', 'pn-customers-manager'); ?></option>
							<option value="long"><?php esc_html_e('Long (detailed)', 'pn-customers-manager'); ?></option>
						</select>
					</div>

					<div class="pn-cm-offer-gen-field">
						<label>
							<input type="checkbox" name="include_faq" value="yes" checked>
							<?php esc_html_e('Include FAQ section', 'pn-customers-manager'); ?>
						</label>
					</div>

					<div class="pn-cm-offer-gen-actions">
						<button type="button" class="pn-cm-offer-gen-btn pn-cm-offer-gen-prev">
							<?php esc_html_e('Back', 'pn-customers-manager'); ?>
						</button>
						<button type="button" class="pn-cm-offer-gen-btn pn-cm-offer-gen-btn-primary pn-cm-offer-gen-generate" id="pn-cm-offer-gen-generate-btn">
							<span class="pn-cm-offer-gen-btn-text"><?php esc_html_e('Generate My Offer Page', 'pn-customers-manager'); ?></span>
							<span class="pn-cm-offer-gen-spinner" style="display:none;">
								<span class="pn-cm-offer-gen-loading-dots">
									<span>.</span><span>.</span><span>.</span>
								</span>
							</span>
						</button>
					</div>
				</div>

			</form>

			<div class="pn-cm-offer-gen-result" style="display:none;">
				<div class="pn-cm-offer-gen-result-success">
					<span class="pn-cm-offer-gen-result-icon">✓</span>
					<h3><?php esc_html_e('Page Created Successfully!', 'pn-customers-manager'); ?></h3>
					<p class="pn-cm-offer-gen-result-message"></p>
					<a href="#" class="pn-cm-offer-gen-btn pn-cm-offer-gen-btn-primary pn-cm-offer-gen-edit-link" target="_blank">
						<?php esc_html_e('Edit Page', 'pn-customers-manager'); ?>
					</a>
				</div>
			</div>

			<div class="pn-cm-offer-gen-error" style="display:none;">
				<div class="pn-cm-offer-gen-error-content">
					<span class="pn-cm-offer-gen-error-icon">⚠</span>
					<p class="pn-cm-offer-gen-error-message"></p>
					<button type="button" class="pn-cm-offer-gen-btn pn-cm-offer-gen-retry">
						<?php esc_html_e('Try Again', 'pn-customers-manager'); ?>
					</button>
				</div>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}

	/* ================================================================
	 * AJAX HANDLER
	 * ================================================================ */

	/**
	 * Handle AJAX request to generate offer page.
	 */
	public function handle_generate_offer_ajax() {
		check_ajax_referer('pn-customers-manager-nonce', 'nonce');

		if (!current_user_can('edit_posts')) {
			wp_send_json_error([
				'message' => __('Insufficient permissions.', 'pn-customers-manager'),
			]);
		}

		// Get and sanitize form data
		$business_data = [
			'business_name'    => sanitize_text_field($_POST['business_name'] ?? ''),
			'tagline'          => sanitize_textarea_field($_POST['tagline'] ?? ''),
			'description'      => sanitize_textarea_field($_POST['description'] ?? ''),
			'target_audience'  => sanitize_textarea_field($_POST['target_audience'] ?? ''),
			'offer_title'      => sanitize_text_field($_POST['offer_title'] ?? ''),
			'offer_includes'   => sanitize_textarea_field($_POST['offer_includes'] ?? ''),
			'transformation'   => sanitize_textarea_field($_POST['transformation'] ?? ''),
			'price'            => sanitize_text_field($_POST['price'] ?? ''),
			'tone'             => sanitize_text_field($_POST['tone'] ?? 'conversational'),
			'length'           => sanitize_text_field($_POST['length'] ?? 'medium'),
			'include_faq'      => isset($_POST['include_faq']) && $_POST['include_faq'] === 'yes',
		];

		$offer_type = sanitize_text_field($_POST['offer_type'] ?? 'genuine');

		// Validate required fields
		if (empty($business_data['business_name']) || empty($business_data['description']) || empty($business_data['offer_title'])) {
			wp_send_json_error([
				'message' => __('Please fill in all required fields.', 'pn-customers-manager'),
			]);
		}

		// Generate content with AI
		$content_sections = $this->generate_offer_content($business_data, $offer_type);

		if (!$content_sections) {
			wp_send_json_error([
				'message' => __('AI generation failed. Please check your OpenAI API key in settings.', 'pn-customers-manager'),
			]);
		}

		// Create WordPress page
		$result = $this->create_wordpress_page($content_sections, [
			'offer_type'    => $offer_type,
			'offer_title'   => $business_data['offer_title'],
			'business_data' => $business_data,
		]);

		if (!$result || empty($result['page_id'])) {
			wp_send_json_error([
				'message' => __('Failed to create page. Please try again.', 'pn-customers-manager'),
			]);
		}

		wp_send_json_success([
			'page_id'  => $result['page_id'],
			'edit_url' => $result['edit_url'],
			'message'  => __('Page created successfully! Redirecting to editor...', 'pn-customers-manager'),
		]);
	}

	/* ================================================================
	 * AI CONTENT GENERATION
	 * ================================================================ */

	/**
	 * Generate offer content using AI.
	 *
	 * @param array  $business_data Business and offer data.
	 * @param string $offer_type Type of offer (genuine or sales_presentation).
	 * @return array|false Array of generated content sections or false on failure.
	 */
	private function generate_offer_content($business_data, $offer_type) {
		$sections = $this->get_sections_for_type($offer_type);
		$generated_content = [];

		foreach ($sections as $section_key) {
			$messages = [
				['role' => 'system', 'content' => $this->build_system_prompt($offer_type, $business_data)],
				['role' => 'user', 'content' => $this->build_section_prompt($section_key, $business_data)],
			];

			$response = self::call_openai($messages, 'gpt-4o', 0.7);

			if ($response === false) {
				return false;
			}

			$generated_content[$section_key] = $this->parse_ai_response($response);
		}

		return $generated_content;
	}

	/**
	 * Build system prompt for AI.
	 *
	 * @param string $offer_type Type of offer.
	 * @param array  $business_data Business data for context.
	 * @return string System prompt.
	 */
	private function build_system_prompt($offer_type, $business_data) {
		$tone_instructions = [
			'professional'    => 'Usa un tono profesional, formal pero accesible, con terminología especializada cuando sea apropiado.',
			'conversational'  => 'Usa un tono conversacional y cercano, como si hablaras con un amigo, pero manteniendo el profesionalismo.',
			'inspirational'   => 'Usa un tono inspirador y motivador, que genere emoción y aspiración en el lector.',
		];

		$tone = $tone_instructions[$business_data['tone']] ?? $tone_instructions['conversational'];

		return "Eres un experto copywriter especializado en ofertas de negocio siguiendo metodologías de marketing genuino (Isra Bravo) y ventas consultivas.

Tu tarea es generar contenido persuasivo, orientado a resultados para páginas de venta.

CONTEXTO DEL NEGOCIO:
- Negocio: {$business_data['business_name']}
- Descripción: {$business_data['description']}
- Público objetivo: {$business_data['target_audience']}

ESTILO:
{$tone}

IMPORTANTE:
- Tu respuesta debe ser SOLO el contenido solicitado en formato JSON válido
- Usa lenguaje directo y evita jerga innecesaria
- Toca puntos de dolor emocionales del público objetivo
- Muestra autoridad sin arrogancia
- Enfócate en beneficios, no solo características";
	}

	/**
	 * Build section-specific prompt for AI.
	 *
	 * @param string $section_key Section identifier.
	 * @param array  $business_data Business data.
	 * @return string Section prompt.
	 */
	private function build_section_prompt($section_key, $business_data) {
		$prompts = [
			'hero'           => "Genera el contenido para la sección HERO (cabecera principal) con:
- headline: Un titular impactante que capture la atención (máximo 70 caracteres)
- subtitle: Un subtítulo que explique el beneficio principal (1-2 líneas)
- cta_text: Texto para el botón de acción (2-4 palabras)

Oferta: {$business_data['offer_title']}
Transformación prometida: {$business_data['transformation']}

Responde SOLO con JSON en este formato:
{\"headline\": \"...\", \"subtitle\": \"...\", \"cta_text\": \"...\"}",

			'problems'       => "Genera una lista de 3-5 problemas específicos que enfrenta el público objetivo.
Cada problema debe ser concreto, emocional y reconocible.

Público objetivo: {$business_data['target_audience']}

Responde SOLO con JSON en este formato:
{\"problems\": [\"Problema 1...\", \"Problema 2...\", \"Problema 3...\"]}",

			'solution'       => "Genera el contenido para la sección SOLUCIÓN:
- headline: Título de la sección (5-8 palabras)
- body: Descripción de la solución (2-3 párrafos) que explique cómo resuelves los problemas

Descripción del negocio: {$business_data['description']}
Qué incluye: {$business_data['offer_includes']}

Responde SOLO con JSON:
{\"headline\": \"...\", \"body\": \"...\"}",

			'transformation' => "Genera contenido para la sección TRANSFORMACIÓN (antes/después):
- headline: Título inspirador
- before: Estado actual del cliente (2-3 puntos)
- after: Estado deseado después de trabajar contigo (2-3 puntos)

Transformación: {$business_data['transformation']}

Responde SOLO con JSON:
{\"headline\": \"...\", \"before\": \"...\", \"after\": \"...\"}",

			'cta_final'      => "Genera el CTA (Call to Action) final:
- headline: Título motivador para cerrar
- body: Texto de urgencia/cierre (1-2 líneas)

Responde SOLO con JSON:
{\"headline\": \"...\", \"body\": \"...\"}",
		];

		return $prompts[$section_key] ?? '';
	}

	/**
	 * Get sections to generate based on offer type.
	 *
	 * @param string $offer_type Type of offer.
	 * @return array Array of section keys.
	 */
	private function get_sections_for_type($offer_type) {
		if ($offer_type === 'sales_presentation') {
			return ['hero', 'solution', 'cta_final'];
		}

		return ['hero', 'problems', 'solution', 'transformation', 'cta_final'];
	}

	/**
	 * Parse AI response JSON.
	 *
	 * @param string $response AI response.
	 * @return array Parsed data.
	 */
	private function parse_ai_response($response) {
		// Remove markdown code blocks if present
		$response = preg_replace('/```json\s*|\s*```/', '', $response);
		$response = trim($response);

		$decoded = json_decode($response, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			// Fallback if JSON parsing fails
			return ['content' => $response];
		}

		return $decoded;
	}

	/* ================================================================
	 * WORDPRESS PAGE CREATION
	 * ================================================================ */

	/**
	 * Create WordPress page with generated content.
	 *
	 * @param array $content_sections Generated content sections.
	 * @param array $metadata Page metadata.
	 * @return array|false Result with page_id and edit_url, or false on failure.
	 */
	private function create_wordpress_page($content_sections, $metadata) {
		$post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();

		// Generate Gutenberg blocks
		$gutenberg_content = $this->generate_gutenberg_blocks($content_sections, $metadata);

		// Create unique title with timestamp
		$title = sprintf(
			'%s - %s',
			$metadata['offer_title'],
			date_i18n('Y-m-d H:i', current_time('timestamp'))
		);

		// Create page in draft status
		$page_id = $post_functions->pn_customers_manager_insert_post(
			$title,
			$gutenberg_content,
			'',
			sanitize_title($title),
			'page',
			'draft',
			get_current_user_id(),
			0,
			[],
			[],
			[
				'_pn_cm_offer_type'      => $metadata['offer_type'],
				'_pn_cm_generated_by_ai' => 'yes',
				'_pn_cm_generation_date' => current_time('mysql'),
				'_pn_cm_business_data'   => wp_json_encode($metadata['business_data']),
			],
			false
		);

		if (!$page_id) {
			return false;
		}

		return [
			'page_id'  => $page_id,
			'edit_url' => admin_url('post.php?post=' . $page_id . '&action=edit'),
		];
	}

	/**
	 * Generate Gutenberg blocks from content sections.
	 *
	 * @param array $content_sections Generated content.
	 * @param array $metadata Page metadata.
	 * @return string Gutenberg blocks HTML.
	 */
	private function generate_gutenberg_blocks($content_sections, $metadata) {
		$blocks = '';

		foreach ($content_sections as $section_key => $content) {
			switch ($section_key) {
				case 'hero':
					$blocks .= $this->generate_hero_block($content);
					break;
				case 'problems':
					$blocks .= $this->generate_problem_block($content);
					break;
				case 'solution':
					$blocks .= $this->generate_solution_block($content);
					break;
				case 'transformation':
					$blocks .= $this->generate_transformation_block($content);
					break;
				case 'cta_final':
					$blocks .= $this->generate_cta_block($content, $metadata);
					break;
			}
		}

		return $blocks;
	}

	/**
	 * Generate hero block.
	 */
	private function generate_hero_block($content) {
		$headline = $content['headline'] ?? '';
		$subtitle = $content['subtitle'] ?? '';
		$cta_text = $content['cta_text'] ?? __('Get Started', 'pn-customers-manager');

		$cta_url = get_option('pn_customers_manager_marketing_cta_primary_url', '#');

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}},"color":{"gradient":"linear-gradient(135deg,rgb(102,126,234) 0%%,rgb(118,75,162) 100%%)"}},"layout":{"type":"constrained","contentSize":"700px"}} -->
<div class="wp-block-group has-background" style="background:linear-gradient(135deg,rgb(102,126,234) 0%%,rgb(118,75,162) 100%%);padding-top:80px;padding-bottom:80px">
	<!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
	<h1 class="wp-block-heading has-text-align-center" style="color:#ffffff">%s</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"1.25rem"}}} -->
	<p class="has-text-align-center" style="color:#ffffff;font-size:1.25rem">%s</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<div class="wp-block-button">
			<a class="wp-block-button__link wp-element-button" href="%s">%s</a>
		</div>
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

',
			esc_html($headline),
			esc_html($subtitle),
			esc_url($cta_url),
			esc_html($cta_text)
		);
	}

	/**
	 * Generate problem block.
	 */
	private function generate_problem_block($content) {
		$problems = $content['problems'] ?? [];

		if (empty($problems)) {
			return '';
		}

		$problems_html = '';
		foreach ($problems as $problem) {
			$problems_html .= sprintf(
				'	<!-- wp:list-item -->
	<li>%s</li>
	<!-- /wp:list-item -->

',
				esc_html($problem)
			);
		}

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}},"color":{"background":"#f8f9fa"}},"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group has-background" style="background-color:#f8f9fa;padding-top:60px;padding-bottom:60px">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center">%s</h2>
	<!-- /wp:heading -->

	<!-- wp:list -->
	<ul class="wp-block-list">
%s	</ul>
	<!-- /wp:list -->
</div>
<!-- /wp:group -->

',
			esc_html__('Are you facing these challenges?', 'pn-customers-manager'),
			$problems_html
		);
	}

	/**
	 * Generate solution block.
	 */
	private function generate_solution_block($content) {
		$headline = $content['headline'] ?? __('The Solution', 'pn-customers-manager');
		$body = $content['body'] ?? '';

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group" style="padding-top:60px;padding-bottom:60px">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center">%s</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"large"} -->
	<p class="has-large-font-size">%s</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

',
			esc_html($headline),
			esc_html($body)
		);
	}

	/**
	 * Generate transformation block.
	 */
	private function generate_transformation_block($content) {
		$headline = $content['headline'] ?? __('Your Transformation', 'pn-customers-manager');
		$before = $content['before'] ?? '';
		$after = $content['after'] ?? '';

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}},"color":{"gradient":"linear-gradient(135deg,rgb(240,147,251) 0%%,rgb(245,87,108) 100%%)"}},"layout":{"type":"constrained","contentSize":"900px"}} -->
<div class="wp-block-group has-background" style="background:linear-gradient(135deg,rgb(240,147,251) 0%%,rgb(245,87,108) 100%%);padding-top:60px;padding-bottom:60px">
	<!-- wp:heading {"textAlign":"center","style":{"color":{"text":"#ffffff"}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#ffffff">%s</h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"2rem","left":"2rem"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"style":{"color":{"text":"#ffffff"}}} -->
			<h3 class="wp-block-heading" style="color:#ffffff">%s</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"style":{"color":{"text":"#ffffff"}}} -->
			<p style="color:#ffffff">%s</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"100px"} -->
		<div class="wp-block-column" style="flex-basis:100px">
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"3rem"},"color":{"text":"#ffffff"}}} -->
			<p class="has-text-align-center" style="color:#ffffff;font-size:3rem">→</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"style":{"color":{"text":"#ffffff"}}} -->
			<h3 class="wp-block-heading" style="color:#ffffff">%s</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"style":{"color":{"text":"#ffffff"}}} -->
			<p style="color:#ffffff">%s</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->

',
			esc_html($headline),
			esc_html__('Before', 'pn-customers-manager'),
			esc_html($before),
			esc_html__('After', 'pn-customers-manager'),
			esc_html($after)
		);
	}

	/**
	 * Generate CTA block.
	 */
	private function generate_cta_block($content, $metadata) {
		$headline = $content['headline'] ?? __('Ready to Get Started?', 'pn-customers-manager');
		$body = $content['body'] ?? '';

		$cta_text = get_option('pn_customers_manager_marketing_cta_primary_text', __('Get Started', 'pn-customers-manager'));
		$cta_url = get_option('pn_customers_manager_marketing_cta_primary_url', '#');

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}},"color":{"gradient":"linear-gradient(135deg,rgb(102,126,234) 0%%,rgb(118,75,162) 100%%)"}},"layout":{"type":"constrained","contentSize":"700px"}} -->
<div class="wp-block-group has-background" style="background:linear-gradient(135deg,rgb(102,126,234) 0%%,rgb(118,75,162) 100%%);padding-top:80px;padding-bottom:80px">
	<!-- wp:heading {"textAlign":"center","style":{"color":{"text":"#ffffff"}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#ffffff">%s</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"1.25rem"}}} -->
	<p class="has-text-align-center" style="color:#ffffff;font-size:1.25rem">%s</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<div class="wp-block-button">
			<a class="wp-block-button__link wp-element-button" href="%s">%s</a>
		</div>
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

',
			esc_html($headline),
			esc_html($body),
			esc_url($cta_url),
			esc_html($cta_text)
		);
	}

	/* ================================================================
	 * UTILITY METHODS
	 * ================================================================ */

	/**
	 * Get business settings from options.
	 *
	 * @return array Business settings.
	 */
	private function get_business_settings() {
		return [
			'business_name'   => get_option('pn_customers_manager_marketing_business_name', ''),
			'tagline'         => get_option('pn_customers_manager_marketing_tagline', ''),
			'description'     => get_option('pn_customers_manager_marketing_description', ''),
			'target_audience' => get_option('pn_customers_manager_marketing_target_description', ''),
		];
	}

	/**
	 * Enqueue scripts and styles for the offer generator.
	 */
	public static function enqueue_assets() {
		// Only enqueue if user can edit posts
		if (!current_user_can('edit_posts')) {
			return;
		}

		$version = defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.70';

		// Enqueue CSS
		wp_enqueue_style(
			'pn-cm-offer-generator',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-cm-offer-generator.css',
			[],
			$version,
			'all'
		);

		// Enqueue JS
		wp_enqueue_script(
			'pn-cm-offer-generator',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-offer-generator.js',
			['jquery'],
			$version,
			true
		);

		// Localize script
		wp_localize_script('pn-cm-offer-generator', 'pnCMOfferGen', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'strings'  => [
				'step_of'           => __('Step %1$d of %2$d', 'pn-customers-manager'),
				'field_required'    => __('This field is required.', 'pn-customers-manager'),
				'generation_failed' => __('Failed to generate offer. Please try again.', 'pn-customers-manager'),
				'ajax_error'        => __('Communication error. Please check your connection and try again.', 'pn-customers-manager'),
			],
		]);
	}
}
