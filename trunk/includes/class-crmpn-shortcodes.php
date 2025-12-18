<?php
/**
 * Platform shortcodes.
 *
 * This class defines all shortcodes of the platform.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Shortcodes {
	/**
	 * Manage the shortcodes in the platform.
	 *
	 * @since    1.0.0
	 */
	public function crmpn_test($atts) {
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
    <div class="crmpn-course-container crmpn-pt-30 crmpn-pb-50">
      <h2 class="crmpn-mb-30"><?php echo esc_html($course_data['title']); ?></h2>
      
      <?php 
      $video_counter = 0;
      foreach ($course_data['sections'] as $section_name => $videos): 
      ?>
        <div class="crmpn-course-section crmpn-mb-40">
          <h3 class="crmpn-mb-20 crmpn-font-size-24"><?php echo esc_html($section_name); ?></h3>
          <ul class="crmpn-course-video-list crmpn-list-style-none crmpn-pl-0">
            <?php foreach ($videos as $video_title => $video_url): 
              $video_counter++;
              $popup_id = 'crmpn-video-popup-' . $video_counter;
              $video_id = basename(parse_url($video_url, PHP_URL_PATH));
            ?>
              <li class="crmpn-course-video-item crmpn-mb-10">
                <a href="#" 
                   class="crmpn-course-video-link crmpn-color-main-0 crmpn-text-decoration-none" 
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
        <div class="crmpn-course-resources crmpn-mt-40">
          <h3 class="crmpn-mb-20 crmpn-font-size-24">Recursos</h3>
          <ul class="crmpn-course-resource-list crmpn-list-style-none crmpn-pl-0">
            <?php foreach ($course_data['resources'] as $resource_url): ?>
              <li class="crmpn-course-resource-item crmpn-mb-10">
                <a href="<?php echo esc_url($resource_url); ?>" 
                   target="_blank" 
                   class="crmpn-course-resource-link crmpn-color-main-0 crmpn-text-decoration-none">
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
          $popup_id = 'crmpn-video-popup-' . $video_counter;
          $video_id = basename(parse_url($video_url, PHP_URL_PATH));
      ?>
        <div id="<?php echo esc_attr($popup_id); ?>" class="crmpn-popup crmpn-display-none-soft">
          <div class="crmpn-popup-overlay"></div>
          <div class="crmpn-popup-content" style="max-width: 90%; width: 1200px;">
            <div class="crmpn-video-embed">
              <iframe src="<?php echo esc_url($video_url); ?>" 
                      frameborder="0" 
                      allow="autoplay; fullscreen; picture-in-picture" 
                      allowfullscreen></iframe>
            </div>
            <h4 class="crmpn-mt-20"><?php echo esc_html($video_title); ?></h4>
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
        $('.crmpn-course-video-link').on('click', function(e) {
          e.preventDefault();
          var popupId = $(this).attr('data-popup-id');
          var popupElement = $('#' + popupId);
          
          if (popupElement.length) {
            CRMPN_Popups.open(popupElement);
          }
        });
      });
    })(jQuery);
    </script>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
	}

  public function crmpn_call_to_action($atts) {
    // echo do_shortcode('[crmpn-call-to-action crmpn_call_to_action_icon="error_outline" crmpn_call_to_action_title="' . esc_html(__('Default title', 'crmpn')) . '" crmpn_call_to_action_content="' . esc_html(__('Default content', 'crmpn')) . '" crmpn_call_to_action_button_link="#" crmpn_call_to_action_button_text="' . esc_html(__('Button text', 'crmpn')) . '" crmpn_call_to_action_button_class="crmpn-class"]');
    $a = extract(shortcode_atts(array(
      'crmpn_call_to_action_class' => '',
      'crmpn_call_to_action_icon' => '',
      'crmpn_call_to_action_title' => '',
      'crmpn_call_to_action_content' => '',
      'crmpn_call_to_action_button_link' => '#',
      'crmpn_call_to_action_button_text' => '',
      'crmpn_call_to_action_button_class' => '',
      'crmpn_call_to_action_button_data_key' => '',
      'crmpn_call_to_action_button_data_value' => '',
      'crmpn_call_to_action_button_blank' => 0,
    ), $atts));

    ob_start();
    ?>
      <div class="crmpn-call-to-action crmpn-text-align-center crmpn-pt-30 crmpn-pb-50 <?php echo esc_attr($crmpn_call_to_action_class); ?>">
        <div class="crmpn-call-to-action-icon">
          <i class="material-icons-outlined crmpn-font-size-75 crmpn-color-main-0"><?php echo esc_html($crmpn_call_to_action_icon); ?></i>
        </div>

        <h4 class="crmpn-call-to-action-title crmpn-text-align-center crmpn-mt-10 crmpn-mb-20"><?php echo esc_html($crmpn_call_to_action_title); ?></h4>
        
        <?php if (!empty($crmpn_call_to_action_content)): ?>
          <p class="crmpn-text-align-center"><?php echo wp_kses_post($crmpn_call_to_action_content); ?></p>
        <?php endif ?>

        <?php if (!empty($crmpn_call_to_action_button_text)): ?>
          <div class="crmpn-text-align-center crmpn-mt-20">
            <a class="crmpn-btn crmpn-btn-transparent crmpn-margin-auto <?php echo esc_attr($crmpn_call_to_action_button_class); ?>" <?php echo ($crmpn_call_to_action_button_blank) ? 'target="_blank"' : ''; ?> href="<?php echo esc_url($crmpn_call_to_action_button_link); ?>" <?php echo (!empty($crmpn_call_to_action_button_data_key) && !empty($crmpn_call_to_action_button_data_value)) ? esc_attr($crmpn_call_to_action_button_data_key) . '="' . esc_attr($crmpn_call_to_action_button_data_value) . '"' : ''; ?>><?php echo esc_html($crmpn_call_to_action_button_text); ?></a>
          </div>
        <?php endif ?>
      </div>
    <?php 
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  /**
   * Client onboarding form shortcode.
   *
   * @param array $atts
   * @return string
   */
  public function crmpn_client_form($atts = []) {
    return CRMPN_Client_Form::render_form($atts);
  }

}