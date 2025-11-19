<?php	
/**
 * Provide a common footer area view for the plugin
 *
 * This file is used to markup the common footer facing aspects of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    CRMPN
 * @subpackage CRMPN/common/templates
 */

	if (!defined('ABSPATH')) exit; // Exit if accessed directly

	if(wp_is_block_theme()) {
  	wp_head();
		block_template_part('header');
	} else {
  		get_header();
	}

  $post_id = get_the_ID();

	$ingredients = get_post_meta($post_id, 'crmpn_ingredients_name', true);
	$steps = get_post_meta($post_id, 'crmpn_steps_name', true);
	$steps_description = get_post_meta($post_id, 'crmpn_steps_description', true);
	$steps_time = get_post_meta($post_id, 'crmpn_steps_time', true);
	$steps_total_time = get_post_meta($post_id, 'crmpn_time', true);
	$crmpn_images = explode(',', get_post_meta($post_id, 'crmpn_images', true));
	$suggestions = get_post_meta($post_id, 'crmpn_suggestions', true);
	$steps_count = (!empty($steps) && !empty($steps[0]) && is_array($steps) && count($steps) > 0) ? count($steps) : 0;
	$ingredients_count = (!empty($ingredients) && !empty($ingredients[0]) && is_array($ingredients) && count($ingredients) > 0) ? count($ingredients) : 0;

	function crmpn_minutes($time){
		if ($time) {
			$time = explode(':', $time);
			return ($time[0] * 60) + ($time[1]);
		} else {
			return 0;
		}
	}
?>
	<body <?php body_class(); ?>>
		<div id="crmpn-funnel-wrapper" class="crmpn-wrapper crmpn-funnel-wrapper" data-crmpn-ingredients-count="<?php echo intval($ingredients_count); ?>" data-crmpn-steps-count="<?php echo intval($steps_count); ?>">
		  <div class="crmpn-display-table crmpn-width-100-percent">
		  	<div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent">
		  		<a href="<?php echo esc_url(get_post_type_archive_link('crmpn_funnel')); ?>"><i class="material-icons-outlined crmpn-font-size-30 crmpn-vertical-align-middle crmpn-mr-10 crmpn-color-main-0">keyboard_arrow_left</i> <?php esc_html_e('More funnels', 'crmpn'); ?></a>
		  	</div>
		  	<div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-right">
		  		<?php if (current_user_can('administrator') || current_user_can('crmpn_role_manager')): ?>
		  			<a href="<?php echo esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')); ?>"><i class="material-icons-outlined crmpn-font-size-30 crmpn-vertical-align-middle crmpn-mr-10 crmpn-color-main-0">edit</i> <?php esc_html_e('Edit funnel', 'crmpn'); ?></a>
		  		<?php endif ?>
		  	</div>
		  </div>
			
			<h1 class="crmpn-text-align-center crmpn-mb-50"><?php echo esc_html(get_the_title($post_id)); ?></h1>

			<div class="crmpn-display-block crmpn-width-100-percent crmpn-mb-30">
				<div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-mb-30 crmpn-vertical-align-top">
					<div class="crmpn-image crmpn-p-20 crmpn-mb-30">
						<?php if (has_post_thumbnail($post_id)): ?>
					    <?php echo get_the_post_thumbnail($post_id, 'full', ['class' => 'crmpn-border-radius-20']); ?>
					  <?php else: ?>
							<img src="<?php echo esc_url(CRMPN_URL . 'assets/media/crmpn-image.jpg'); ?>" class="crmpn-border-radius-20 crmpn-width-100-percent">
					  <?php endif ?>
					</div>

					<?php if (!empty($crmpn_images)): ?>
						<div class="crmpn-carousel crmpn-carousel-main-images">
			        <div class="owl-carousel owl-theme">
			          <?php if (!empty($crmpn_images)): ?>
			          	<?php if (has_post_thumbnail($post_id)): ?>
				          	<div class="crmpn-image crmpn-cursor-grab">
			                <a href="#" data-fancybox="gallery" data-src="<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'full', ['class' => 'crmpn-border-radius-10'])); ?>"><?php echo esc_html(get_the_post_thumbnail($post_id, 'thumbnail', ['class' => 'crmpn-border-radius-10'])); ?></a>  
			              </div>
								  <?php endif ?>

			            <?php foreach ($crmpn_images as $image_id): ?>
		              	<?php if (!empty($image_id)): ?>
			              	<div class="crmpn-image crmpn-cursor-grab">
			                	<a href="#" data-fancybox="gallery" data-src="<?php echo esc_url(wp_get_attachment_image_src($image_id, 'full')[0]); ?>"><?php echo esc_html(wp_get_attachment_image($image_id, 'thumbnail', false, ['class' => 'crmpn-border-radius-10'])); ?></a>  
			              	</div>
		              	<?php endif ?>
			            <?php endforeach ?>
			          <?php endif ?>
			        </div>
			      </div>
					<?php endif ?>
				</div>

				<div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-mb-30 crmpn-vertical-align-top crmpn-mb-30">
					<div class="crmpn-funnel-content crmpn-p-20">
						<?php echo wp_kses_post(str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($post_id)->post_content))); ?>
					</div>
				</div>
			</div>

			<div class="crmpn-display-table crmpn-width-100-percent crmpn-mb-50">
				<div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-mb-30 crmpn-vertical-align-top">
					<div class="crmpn-ingredients crmpn-p-20">
						<?php if ($ingredients_count): ?>
							<h2 class="crmpn-mb-30"><?php esc_html_e('Ingredients', 'crmpn'); ?></h2>
							<ul>
								<?php foreach ($ingredients as $ingredient): ?>
									<li class="crmpn-mb-20 crmpn-font-size-20 crmpn-list-style-none">
										<div class="crmpn-display-table crmpn-width-100-percent">
											<div class="crmpn-display-inline-table crmpn-width-90-percent">
												<?php echo esc_html($ingredient); ?>
											</div>
											<div class="crmpn-display-inline-table crmpn-width-10-percent">
												<i class="material-icons-outlined crmpn-ingredient-checkbox crmpn-cursor-pointer crmpn-vertical-align-middle crmpn-font-size-30">radio_button_unchecked</i>
											</div>
										</div>
									</li>
								<?php endforeach ?>
							</ul>
						<?php endif ?>
					</div>
				</div>

				<div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-mb-30 crmpn-vertical-align-top">
					<div class="crmpn-steps crmpn-p-20 crmpn-mb-50">
						<?php if ($steps_count): ?>
							<div class="crmpn-mb-30">
								<div class="crmpn-display-table crmpn-width-100-percent">
									<div class="crmpn-display-inline-table crmpn-width-80-percent">
										<h2><?php esc_html_e('Elaboration steps', 'crmpn'); ?></h2>
									</div>
									<div class="crmpn-display-inline-table crmpn-width-20-percent">
										<a href="#" class="crmpn-popup-player-btn" data-fancybox data-src="#crmpn-popup-player"><i class="material-icons-outlined crmpn-mr-10 crmpn-font-size-50 crmpn-float-right crmpn-vertical-align-middle crmpn-tooltip" title="<?php esc_html_e('Play funnel', 'crmpn'); ?>">play_circle_outline</i></a>
									</div>
								</div>
										
								<?php if (!empty($steps_total_time)): ?>
									<div class="crmpn-text-align-right">
										<i class="material-icons-outlined crmpn-mr-10 crmpn-font-size-10 crmpn-vertical-align-middle">timer</i> <small><strong><?php esc_html_e('Total time', 'crmpn'); ?></strong> <?php echo esc_html($steps_total_time); ?> (<?php esc_html_e('hours', 'crmpn'); ?>:<?php esc_html_e('minutes', 'crmpn'); ?>)</small>
									</div>
								<?php endif ?>
							</div>

							<ol>
								<?php foreach ($steps as $index => $step): ?>
									<li class="crmpn-mb-50">
										<div class="crmpn-display-table crmpn-width-100-percent">
											<div class="crmpn-display-inline-table crmpn-width-80-percent">
												<?php if (!empty($step)): ?>
													<h4 class="crmpn-mb-10"><?php echo esc_html($step); ?></h4>
												<?php endif ?>
											</div>

											<div class="crmpn-display-inline-table crmpn-width-20-percent">
												<h5 class="crmpn-mb-10"><i class="material-icons-outlined crmpn-mr-10 crmpn-font-size-10 crmpn-vertical-align-middle">timer</i><?php echo !empty($steps_time[$index]) ? esc_html($steps_time[$index]) : '00:00'; ?></h5>
											</div>
										</div>

										<?php if (!empty($steps_description[$index])): ?>
											<p><?php echo esc_html($steps_description[$index]); ?></p>
										<?php endif ?>
									</li>
								<?php endforeach ?>
							</ol>

							<div id="crmpn-popup-player" class="crmpn-display-none-soft">
								<div id="crmpn-popup-steps" class="crmpn-mb-30" data-crmpn-current-step="1">
									<?php foreach ($steps as $index => $step): ?>
										<div class="crmpn-player-step <?php echo $index != 0 ? 'crmpn-display-none-soft' : ''; ?>" data-crmpn-step="<?php echo number_format($index + 1); ?>">
											<div class="crmpn-display-table crmpn-width-100-percent">
												<div class="crmpn-display-inline-table crmpn-width-80-percent crmpn-vertical-align-top">
													<?php if (!empty($step)): ?>
														<h3 class="crmpn-mb-10"><?php echo esc_html($step); ?></h3>
													<?php endif ?>
												</div>
												<div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-vertical-align-top  crmpn-text-align-right">
													<h3>
														<i class="material-icons-outlined crmpn-display-inline crmpn-player-timer-icon crmpn-mr-10 crmpn-font-size-30 crmpn-vertical-align-middle">timer</i> 
														<span class="crmpn-player-timer crmpn-display-inline"><?php echo number_format(crmpn_minutes($steps_time[$index])); ?></span>'
													</h3>
												</div>
											</div>

											<?php if (!empty($steps_description[$index])): ?>
												<div class="crmpn-step-description"><?php echo esc_html($steps_description[$index]); ?></div>
											<?php endif ?>
										</div>
									<?php endforeach ?>
								</div>

								<div class="crmpn-display-table crmpn-width-100-percent">
									<div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-text-align-center crmpn-mb-20">
										<a href="#" class="crmpn-steps-prev crmpn-display-none"><?php esc_html_e('Previous', 'crmpn'); ?></a>
									</div>
									<div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-text-align-center crmpn-mb-20">
										<a href="#" class="crmpn-btn crmpn-btn-mini crmpn-steps-next"><?php esc_html_e('Next', 'crmpn'); ?></a>
									</div>
								</div>
							</div>
						<?php endif ?>
					</div>

					<?php if (!empty($suggestions)): ?>
						<div class="crmpn-suggestions crmpn-mb-50">
							<div class="crmpn-text-align-center crmpn-mb-10"><i class="material-icons-outlined crmpn-font-size-50 crmpn-tooltip" title="<?php esc_html_e('Suggestions', 'crmpn'); ?>">lightbulb</i></div>

							<?php echo wp_kses_post(wp_specialchars_decode($suggestions)); ?>
						</div>
					<?php endif ?>
				</div>
			</div>
		</div>
	</body>
<?php 
	if(wp_is_block_theme()) {
  	wp_footer();
		block_template_part('footer');
	} else {
  	get_footer();
	}
?>