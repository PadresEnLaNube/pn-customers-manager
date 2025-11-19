<?php 
/**
 * Provide an archive page for Funnels
 *
 * This file is used to provide an archive page for Funnel
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

	if (class_exists('Polylang')) {
		$funnels = get_posts(['numberposts' => -1, 'fields' => 'ids', 'post_type' => 'crmpn_funnel', 'lang' => pll_current_language(), 'post_status' => ['publish'], 'order' => 'DESC', ]);
	} else {
		$funnels = get_posts(['numberposts' => -1, 'fields' => 'ids', 'post_type' => 'crmpn_funnel', 'post_status' => ['publish'], 'order' => 'DESC', ]);
	}
?>
	<body <?php body_class(); ?>>
		<div class="crmpn-wrapper crmpn-funnel-wrapper">
		  <h1 class="crmpn-p-20"><?php esc_html_e('Base CPT', 'crmpn'); ?></h1>
			
			<div class="crmpn-display-table crmpn-width-100-percent crmpn-mt-50 crmpn-mb-50">
				<?php if (!empty($funnels)): ?>
			  	<?php foreach ($funnels as $funnel_id): ?>
						<div class="crmpn-display-inline-table crmpn-width-33-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-p-20 crmpn-text-align-center crmpn-vertical-align-top">
							<div class="crmpn-mb-30">
								<a href="<?php echo esc_url(get_permalink($funnel_id)); ?>">
									<?php if (has_post_thumbnail($funnel_id)): ?>
								    <?php echo get_the_post_thumbnail($funnel_id, 'full', ['class' => 'crmpn-border-radius-20 crmpn-width-100-percent']); ?>
								  <?php else: ?>
								  	<img src="<?php echo esc_url(CRMPN_URL . 'assets/media/crmpn-image.jpg'); ?>" class="crmpn-border-radius-20 crmpn-width-100-percent">
								  <?php endif ?>
								</a>
							</div>

							<a href="<?php echo esc_url(get_permalink($funnel_id)); ?>"><h4 class="crmpn-color-main-hover crmpn-mb-20"><?php echo esc_html(get_the_title($funnel_id)); ?></h4></a>

							<?php if (current_user_can('administrator') || current_user_can('crmpn_role_manager')): ?>
				  			<a href="<?php echo esc_url(admin_url('post.php?post=' . $funnel_id . '&action=edit')); ?>"><i class="material-icons-outlined crmpn-font-size-30 crmpn-vertical-align-middle crmpn-mr-10 crmpn-color-main-0">edit</i> <?php esc_html_e('Edit funnel', 'crmpn'); ?></a>
				  		<?php endif ?>
						</div>
			  	<?php endforeach ?>
				<?php endif ?>

				<?php if (current_user_can('administrator') || current_user_can('crmpn_role_manager')): ?>
					<div class="crmpn-display-inline-table crmpn-width-33-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-p-20 crmpn-text-align-center crmpn-vertical-align-top">
						<div class="crmpn-mb-30">
							<a href="<?php echo esc_url(admin_url('post-new.php?post_type=crmpn_funnel')); ?>">
								<img src="<?php echo esc_url(CRMPN_URL . 'assets/media/crmpn-image.jpg'); ?>" class="crmpn-border-radius-20 crmpn-width-100-percent crmpn-filter-grayscale">
							</a>
						</div>

						<a href="<?php echo esc_url(admin_url('post-new.php?post_type=crmpn_funnel')); ?>"><h4 class="crmpn-color-main-hover crmpn-mb-20"><i class="material-icons-outlined crmpn-vertical-align-middle crmpn-mr-10">add</i> <?php esc_html_e('Add funnel', 'crmpn'); ?></h4></a>
					</div>
				<?php endif ?>
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