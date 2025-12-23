<?php	
/**
 * Provide a common footer area view for the plugin
 *
 * This file is used to markup the common footer facing aspects of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/common/templates
 */

	if (!defined('ABSPATH')) exit; // Exit if accessed directly

	if(wp_is_block_theme()) {
  	wp_head();
		block_template_part('header');
	} else {
  		get_header();
	}

  $post_id = get_the_ID();

	$ingredients = get_post_meta($post_id, 'customers_manager_pn_ingredients_name', true);
	$steps = get_post_meta($post_id, 'customers_manager_pn_steps_name', true);
	$steps_description = get_post_meta($post_id, 'customers_manager_pn_steps_description', true);
	$steps_time = get_post_meta($post_id, 'customers_manager_pn_steps_time', true);
	$steps_total_time = get_post_meta($post_id, 'customers_manager_pn_time', true);
	$customers_manager_pn_images = explode(',', get_post_meta($post_id, 'customers_manager_pn_images', true));
	$suggestions = get_post_meta($post_id, 'customers_manager_pn_suggestions', true);
	$steps_count = (!empty($steps) && !empty($steps[0]) && is_array($steps) && count($steps) > 0) ? count($steps) : 0;
	$ingredients_count = (!empty($ingredients) && !empty($ingredients[0]) && is_array($ingredients) && count($ingredients) > 0) ? count($ingredients) : 0;

	function customers_manager_pn_minutes($time){
		if ($time) {
			$time = explode(':', $time);
			return ($time[0] * 60) + ($time[1]);
		} else {
			return 0;
		}
	}
?>
	<body <?php body_class(); ?>>
		<div id="customers-manager-pn-funnel-wrapper" class="customers-manager-pn-wrapper customers-manager-pn-funnel-wrapper" data-customers-manager-pn-ingredients-count="<?php echo intval($ingredients_count); ?>" data-customers-manager-pn-steps-count="<?php echo intval($steps_count); ?>">
		  <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
		  	<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent">
		  		<a href="<?php echo esc_url(get_post_type_archive_link('customers_manager_pn_funnel')); ?>"><i class="material-icons-outlined customers-manager-pn-font-size-30 customers-manager-pn-vertical-align-middle customers-manager-pn-mr-10 customers-manager-pn-color-main-0">keyboard_arrow_left</i> <?php esc_html_e('More funnels', 'customers-manager-pn'); ?></a>
		  	</div>
		  	<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-right">
		  		<?php if (current_user_can('administrator') || current_user_can('customers_manager_pn_role_manager')): ?>
		  			<a href="<?php echo esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')); ?>"><i class="material-icons-outlined customers-manager-pn-font-size-30 customers-manager-pn-vertical-align-middle customers-manager-pn-mr-10 customers-manager-pn-color-main-0">edit</i> <?php esc_html_e('Edit funnel', 'customers-manager-pn'); ?></a>
		  		<?php endif ?>
		  	</div>
		  </div>
			
			<h1 class="customers-manager-pn-text-align-center customers-manager-pn-mb-50"><?php echo esc_html(get_the_title($post_id)); ?></h1>

			<div class="customers-manager-pn-display-block customers-manager-pn-width-100-percent customers-manager-pn-mb-30">
				<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-mb-30 customers-manager-pn-vertical-align-top">
					<div class="customers-manager-pn-image customers-manager-pn-p-20 customers-manager-pn-mb-30">
						<?php if (has_post_thumbnail($post_id)): ?>
					    <?php echo get_the_post_thumbnail($post_id, 'full', ['class' => 'customers-manager-pn-border-radius-20']); ?>
					  <?php else: ?>
							<img src="<?php echo esc_url(CUSTOMERS_MANAGER_PN_URL . 'assets/media/customers-manager-pn-image.jpg'); ?>" class="customers-manager-pn-border-radius-20 customers-manager-pn-width-100-percent">
					  <?php endif ?>
					</div>

					<?php if (!empty($customers_manager_pn_images)): ?>
						<div class="customers-manager-pn-carousel customers-manager-pn-carousel-main-images">
			        <div class="owl-carousel owl-theme">
			          <?php if (!empty($customers_manager_pn_images)): ?>
			          	<?php if (has_post_thumbnail($post_id)): ?>
				          	<div class="customers-manager-pn-image customers-manager-pn-cursor-grab">
			                <a href="#" data-fancybox="gallery" data-src="<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'full', ['class' => 'customers-manager-pn-border-radius-10'])); ?>"><?php echo esc_html(get_the_post_thumbnail($post_id, 'thumbnail', ['class' => 'customers-manager-pn-border-radius-10'])); ?></a>  
			              </div>
								  <?php endif ?>

			            <?php foreach ($customers_manager_pn_images as $image_id): ?>
		              	<?php if (!empty($image_id)): ?>
			              	<div class="customers-manager-pn-image customers-manager-pn-cursor-grab">
			                	<a href="#" data-fancybox="gallery" data-src="<?php echo esc_url(wp_get_attachment_image_src($image_id, 'full')[0]); ?>"><?php echo esc_html(wp_get_attachment_image($image_id, 'thumbnail', false, ['class' => 'customers-manager-pn-border-radius-10'])); ?></a>  
			              	</div>
		              	<?php endif ?>
			            <?php endforeach ?>
			          <?php endif ?>
			        </div>
			      </div>
					<?php endif ?>
				</div>

				<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-mb-30 customers-manager-pn-vertical-align-top customers-manager-pn-mb-30">
					<div class="customers-manager-pn-funnel-content customers-manager-pn-p-20">
						<?php echo wp_kses_post(str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($post_id)->post_content))); ?>
					</div>
				</div>
			</div>

			<div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent customers-manager-pn-mb-50">
				<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-mb-30 customers-manager-pn-vertical-align-top">
					<div class="customers-manager-pn-ingredients customers-manager-pn-p-20">
						<?php if ($ingredients_count): ?>
							<h2 class="customers-manager-pn-mb-30"><?php esc_html_e('Ingredients', 'customers-manager-pn'); ?></h2>
							<ul>
								<?php foreach ($ingredients as $ingredient): ?>
									<li class="customers-manager-pn-mb-20 customers-manager-pn-font-size-20 customers-manager-pn-list-style-none">
										<div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
											<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-90-percent">
												<?php echo esc_html($ingredient); ?>
											</div>
											<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-10-percent">
												<i class="material-icons-outlined customers-manager-pn-ingredient-checkbox customers-manager-pn-cursor-pointer customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30">radio_button_unchecked</i>
											</div>
										</div>
									</li>
								<?php endforeach ?>
							</ul>
						<?php endif ?>
					</div>
				</div>

				<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-mb-30 customers-manager-pn-vertical-align-top">
					<div class="customers-manager-pn-steps customers-manager-pn-p-20 customers-manager-pn-mb-50">
						<?php if ($steps_count): ?>
							<div class="customers-manager-pn-mb-30">
								<div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
									<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-80-percent">
										<h2><?php esc_html_e('Elaboration steps', 'customers-manager-pn'); ?></h2>
									</div>
									<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent">
										<a href="#" class="customers-manager-pn-popup-player-btn" data-fancybox data-src="#customers-manager-pn-popup-player"><i class="material-icons-outlined customers-manager-pn-mr-10 customers-manager-pn-font-size-50 customers-manager-pn-float-right customers-manager-pn-vertical-align-middle customers-manager-pn-tooltip" title="<?php esc_html_e('Play funnel', 'customers-manager-pn'); ?>">play_circle_outline</i></a>
									</div>
								</div>
										
								<?php if (!empty($steps_total_time)): ?>
									<div class="customers-manager-pn-text-align-right">
										<i class="material-icons-outlined customers-manager-pn-mr-10 customers-manager-pn-font-size-10 customers-manager-pn-vertical-align-middle">timer</i> <small><strong><?php esc_html_e('Total time', 'customers-manager-pn'); ?></strong> <?php echo esc_html($steps_total_time); ?> (<?php esc_html_e('hours', 'customers-manager-pn'); ?>:<?php esc_html_e('minutes', 'customers-manager-pn'); ?>)</small>
									</div>
								<?php endif ?>
							</div>

							<ol>
								<?php foreach ($steps as $index => $step): ?>
									<li class="customers-manager-pn-mb-50">
										<div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
											<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-80-percent">
												<?php if (!empty($step)): ?>
													<h4 class="customers-manager-pn-mb-10"><?php echo esc_html($step); ?></h4>
												<?php endif ?>
											</div>

											<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent">
												<h5 class="customers-manager-pn-mb-10"><i class="material-icons-outlined customers-manager-pn-mr-10 customers-manager-pn-font-size-10 customers-manager-pn-vertical-align-middle">timer</i><?php echo !empty($steps_time[$index]) ? esc_html($steps_time[$index]) : '00:00'; ?></h5>
											</div>
										</div>

										<?php if (!empty($steps_description[$index])): ?>
											<p><?php echo esc_html($steps_description[$index]); ?></p>
										<?php endif ?>
									</li>
								<?php endforeach ?>
							</ol>

							<div id="customers-manager-pn-popup-player" class="customers-manager-pn-display-none-soft">
								<div id="customers-manager-pn-popup-steps" class="customers-manager-pn-mb-30" data-customers-manager-pn-current-step="1">
									<?php foreach ($steps as $index => $step): ?>
										<div class="customers-manager-pn-player-step <?php echo $index != 0 ? 'customers-manager-pn-display-none-soft' : ''; ?>" data-customers-manager-pn-step="<?php echo number_format($index + 1); ?>">
											<div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
												<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-80-percent customers-manager-pn-vertical-align-top">
													<?php if (!empty($step)): ?>
														<h3 class="customers-manager-pn-mb-10"><?php echo esc_html($step); ?></h3>
													<?php endif ?>
												</div>
												<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-vertical-align-top  customers-manager-pn-text-align-right">
													<h3>
														<i class="material-icons-outlined customers-manager-pn-display-inline customers-manager-pn-player-timer-icon customers-manager-pn-mr-10 customers-manager-pn-font-size-30 customers-manager-pn-vertical-align-middle">timer</i> 
														<span class="customers-manager-pn-player-timer customers-manager-pn-display-inline"><?php echo number_format(customers_manager_pn_minutes($steps_time[$index])); ?></span>'
													</h3>
												</div>
											</div>

											<?php if (!empty($steps_description[$index])): ?>
												<div class="customers-manager-pn-step-description"><?php echo esc_html($steps_description[$index]); ?></div>
											<?php endif ?>
										</div>
									<?php endforeach ?>
								</div>

								<div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
									<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-text-align-center customers-manager-pn-mb-20">
										<a href="#" class="customers-manager-pn-steps-prev customers-manager-pn-display-none"><?php esc_html_e('Previous', 'customers-manager-pn'); ?></a>
									</div>
									<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-text-align-center customers-manager-pn-mb-20">
										<a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-steps-next"><?php esc_html_e('Next', 'customers-manager-pn'); ?></a>
									</div>
								</div>
							</div>
						<?php endif ?>
					</div>

					<?php if (!empty($suggestions)): ?>
						<div class="customers-manager-pn-suggestions customers-manager-pn-mb-50">
							<div class="customers-manager-pn-text-align-center customers-manager-pn-mb-10"><i class="material-icons-outlined customers-manager-pn-font-size-50 customers-manager-pn-tooltip" title="<?php esc_html_e('Suggestions', 'customers-manager-pn'); ?>">lightbulb</i></div>

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