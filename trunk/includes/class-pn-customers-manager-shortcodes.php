<?php
/**
 * Platform shortcodes.
 *
 * This class defines all shortcodes of the platform.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Shortcodes {
	/**
	 * Manage the shortcodes in the platform.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_test($atts) {
    $a = extract(shortcode_atts([
      'user_id' => 0,
      'post_id' => 0,
    ], $atts));

    // Course data structure
    $course_data = [
      'title' => 'Alex Dito - Invertir en valor',
      'sections' => [
        'Primeros pasos' => [
          'Bienvenida' => 'https://player.vimeo.com/video/1008254060',
          'Contenido y recursos' => 'https://player.vimeo.com/video/1008254029',
          'Consejos iniciales' => 'https://player.vimeo.com/video/1008254001',
          'El iceberg de la inversión' => 'https://player.vimeo.com/video/1008253982',
          'La mentalidad correcta' => 'https://player.vimeo.com/video/1008253956',
          'Existe creación de riqueza' => 'https://player.vimeo.com/video/1008253924',
          'El ciclo de construcción de capital' => 'https://player.vimeo.com/video/1008253900',
          'Los enemigos del inversor' => 'https://player.vimeo.com/video/1008253878',
        ],
        'Teoría' => [
          'Clase 1 - Introducción al Value Investing' => 'https://player.vimeo.com/video/1124950252',
          'Clase 2 - Análisis del precio de cotización' => 'https://player.vimeo.com/video/1125297208',
          'Clase 3 - Análisis de la cuenta de resultados' => 'https://player.vimeo.com/video/1125642300',
          'Clase 4 - Análisis del Cash Flow' => 'https://player.vimeo.com/video/1125986993',
          'Clase 5 - Análisis del balance' => 'https://player.vimeo.com/video/1126945359',
          'Clase 6 - Análisis de los retornos' => 'https://player.vimeo.com/video/1127283285',
          'Clase 7 - Valoración de empresas' => 'https://player.vimeo.com/video/1127634931',
          'Clase 8 - Gestión de carteras' => 'https://player.vimeo.com/video/1128000677',
        ],
        'Práctica' => [
          'Bolsas de valores - Intercontinental Exchange y Nasdaq' => 'https://player.vimeo.com/video/1134725667',
          'Comercio electrónico - Amazon y Alibaba' => 'https://player.vimeo.com/video/1130335296',
          'Software vertical' => 'https://player.vimeo.com/video/1122972473',
        ],
      ],
      'resources' => [
        'https://docs.google.com/spreadsheets/d/1tW4RoB0SQoZsh9JNcqQ0VJFBkp6UZ0wVDy8Zl4BAL2g/edit?gid=0#gid=0',
        'https://docs.google.com/spreadsheets/d/1snkZ72GhpmB90V5MHkpZLIAXSWyCYu4e8xK98NanT0Y/edit?gid=652981159#gid=652981159',
        'https://docs.google.com/spreadsheets/d/1lVrosSJddK9dm0hpsfzDEbh3Qk_61RQgr7TBYXtHtV0/edit?gid=652981159#gid=652981159',
      ],
    ];

    ob_start();
    ?>
    <div class="pn-customers-manager-course-container pn-customers-manager-pt-30 pn-customers-manager-pb-50">
      <h2 class="pn-customers-manager-mb-30"><?php echo esc_html($course_data['title']); ?></h2>
      
      <?php 
      $video_counter = 0;
      foreach ($course_data['sections'] as $section_name => $videos): 
      ?>
        <div class="pn-customers-manager-course-section pn-customers-manager-mb-40">
          <h3 class="pn-customers-manager-mb-20 pn-customers-manager-font-size-24"><?php echo esc_html($section_name); ?></h3>
          <ul class="pn-customers-manager-course-video-list pn-customers-manager-list-style-none pn-customers-manager-pl-0">
            <?php foreach ($videos as $video_title => $video_url): 
              $video_counter++;
              $popup_id = 'pn-customers-manager-video-popup-' . $video_counter;
              $video_id = basename(parse_url($video_url, PHP_URL_PATH));
            ?>
              <li class="pn-customers-manager-course-video-item pn-customers-manager-mb-10">
                <a href="#" 
                   class="pn-customers-manager-course-video-link pn-customers-manager-color-main-0 pn-customers-manager-text-decoration-none" 
                   data-video-url="<?php echo esc_url($video_url); ?>"
                   data-popup-id="<?php echo esc_attr($popup_id); ?>">
                  <?php echo esc_html($video_title); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>

      <?php if (!empty($course_data['resources'])): ?>
        <div class="pn-customers-manager-course-resources pn-customers-manager-mt-40">
          <h3 class="pn-customers-manager-mb-20 pn-customers-manager-font-size-24">Recursos</h3>
          <ul class="pn-customers-manager-course-resource-list pn-customers-manager-list-style-none pn-customers-manager-pl-0">
            <?php foreach ($course_data['resources'] as $resource_url): ?>
              <li class="pn-customers-manager-course-resource-item pn-customers-manager-mb-10">
                <a href="<?php echo esc_url($resource_url); ?>" 
                   target="_blank" 
                   class="pn-customers-manager-course-resource-link pn-customers-manager-color-main-0 pn-customers-manager-text-decoration-none">
                  <?php echo esc_html($resource_url); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Video Popups -->
      <?php 
      $video_counter = 0;
      foreach ($course_data['sections'] as $section_name => $videos): 
        foreach ($videos as $video_title => $video_url): 
          $video_counter++;
          $popup_id = 'pn-customers-manager-video-popup-' . $video_counter;
          $video_id = basename(parse_url($video_url, PHP_URL_PATH));
      ?>
        <div id="<?php echo esc_attr($popup_id); ?>" class="pn-customers-manager-popup pn-customers-manager-display-none-soft">
          <div class="pn-customers-manager-popup-overlay"></div>
          <div class="pn-customers-manager-popup-content" style="max-width: 90%; width: 1200px;">
            <div class="pn-customers-manager-video-embed">
              <iframe src="<?php echo esc_url($video_url); ?>" 
                      frameborder="0" 
                      allow="autoplay; fullscreen; picture-in-picture" 
                      allowfullscreen></iframe>
            </div>
            <h4 class="pn-customers-manager-mt-20"><?php echo esc_html($video_title); ?></h4>
          </div>
        </div>
      <?php 
        endforeach;
      endforeach; 
      ?>
    </div>

    <script>
    (function($) {
      $(document).ready(function() {
        $('.pn-customers-manager-course-video-link').on('click', function(e) {
          e.preventDefault();
          var popupId = $(this).attr('data-popup-id');
          var popupElement = $('#' + popupId);
          
          if (popupElement.length) {
            PN_CUSTOMERS_MANAGER_Popups.open(popupElement);
          }
        });
      });
    })(jQuery);
    </script>
    <?php
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
	}

  public function pn_customers_manager_call_to_action($atts) {
    // echo do_shortcode('[pn-customers-manager-call-to-action PN_CUSTOMERS_MANAGER_call_to_action_icon="error_outline" PN_CUSTOMERS_MANAGER_call_to_action_title="' . esc_html(__('Default title', 'pn-customers-manager')) . '" PN_CUSTOMERS_MANAGER_call_to_action_content="' . esc_html(__('Default content', 'pn-customers-manager')) . '" PN_CUSTOMERS_MANAGER_call_to_action_button_link="#" PN_CUSTOMERS_MANAGER_call_to_action_button_text="' . esc_html(__('Button text', 'pn-customers-manager')) . '" PN_CUSTOMERS_MANAGER_call_to_action_button_class="pn-customers-manager-class"]');
    $a = extract(shortcode_atts(array(
      'PN_CUSTOMERS_MANAGER_call_to_action_class' => '',
      'PN_CUSTOMERS_MANAGER_call_to_action_icon' => '',
      'PN_CUSTOMERS_MANAGER_call_to_action_title' => '',
      'PN_CUSTOMERS_MANAGER_call_to_action_content' => '',
      'PN_CUSTOMERS_MANAGER_call_to_action_button_link' => '#',
      'PN_CUSTOMERS_MANAGER_call_to_action_button_text' => '',
      'PN_CUSTOMERS_MANAGER_call_to_action_button_class' => '',
      'PN_CUSTOMERS_MANAGER_call_to_action_button_data_key' => '',
      'PN_CUSTOMERS_MANAGER_call_to_action_button_data_value' => '',
      'PN_CUSTOMERS_MANAGER_call_to_action_button_blank' => 0,
    ), $atts));

    ob_start();
    ?>
      <div class="pn-customers-manager-call-to-action pn-customers-manager-text-align-center pn-customers-manager-pt-30 pn-customers-manager-pb-50 <?php echo esc_attr($pn_customers_manager_call_to_action_class); ?>">
        <div class="pn-customers-manager-call-to-action-icon">
          <i class="material-icons-outlined pn-customers-manager-font-size-75 pn-customers-manager-color-main-0"><?php echo esc_html($pn_customers_manager_call_to_action_icon); ?></i>
        </div>

        <h4 class="pn-customers-manager-call-to-action-title pn-customers-manager-text-align-center pn-customers-manager-mt-10 pn-customers-manager-mb-20"><?php echo esc_html($pn_customers_manager_call_to_action_title); ?></h4>
        
        <?php if (!empty($pn_customers_manager_call_to_action_content)): ?>
          <p class="pn-customers-manager-text-align-center"><?php echo wp_kses_post($pn_customers_manager_call_to_action_content); ?></p>
        <?php endif ?>

        <?php if (!empty($pn_customers_manager_call_to_action_button_text)): ?>
          <div class="pn-customers-manager-text-align-center pn-customers-manager-mt-20">
            <a class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-customers-manager-margin-auto <?php echo esc_attr($pn_customers_manager_call_to_action_button_class); ?>" <?php echo ($pn_customers_manager_call_to_action_button_blank) ? 'target="_blank"' : ''; ?> href="<?php echo esc_url($pn_customers_manager_call_to_action_button_link); ?>" <?php echo (!empty($pn_customers_manager_call_to_action_button_data_key) && !empty($pn_customers_manager_call_to_action_button_data_value)) ? esc_attr($pn_customers_manager_call_to_action_button_data_key) . '="' . esc_attr($pn_customers_manager_call_to_action_button_data_value) . '"' : ''; ?>><?php echo esc_html($pn_customers_manager_call_to_action_button_text); ?></a>
          </div>
        <?php endif ?>
      </div>
    <?php 
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
  }

  /**
   * Client onboarding form shortcode.
   *
   * @param array $atts
   * @return string
   */
  public function pn_customers_manager_client_form($atts = []) {
    return PN_CUSTOMERS_MANAGER_Client_Form::render_form($atts);
  }

}