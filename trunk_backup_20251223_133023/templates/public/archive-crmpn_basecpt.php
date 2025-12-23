<?php 
/**
 * Provide an archive page for Funnels
 *
 * This file is used to provide an archive page for Funnel
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

	if (class_exists('Polylang')) {
		$funnels = get_posts(['numberposts' => -1, 'fields' => 'ids', 'post_type' => 'customers_manager_pn_funnel', 'lang' => pll_current_language(), 'post_status' => ['publish'], 'order' => 'DESC', ]);
	} else {
		$funnels = get_posts(['numberposts' => -1, 'fields' => 'ids', 'post_type' => 'customers_manager_pn_funnel', 'post_status' => ['publish'], 'order' => 'DESC', ]);
	}
?>
	<body <?php body_class(); ?>>
		<div class="customers-manager-pn-wrapper customers-manager-pn-funnel-wrapper">
		  <h1 class="customers-manager-pn-p-20"><?php esc_html_e('Base CPT', 'customers-manager-pn'); ?></h1>
			
			<div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent customers-manager-pn-mt-50 customers-manager-pn-mb-50">
				<?php if (!empty($funnels)): ?>
			  	<?php foreach ($funnels as $funnel_id): ?>
						<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-33-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-p-20 customers-manager-pn-text-align-center customers-manager-pn-vertical-align-top">
							<div class="customers-manager-pn-mb-30">
								<a href="<?php echo esc_url(get_permalink($funnel_id)); ?>">
									<?php if (has_post_thumbnail($funnel_id)): ?>
								    <?php echo get_the_post_thumbnail($funnel_id, 'full', ['class' => 'customers-manager-pn-border-radius-20 customers-manager-pn-width-100-percent']); ?>
								  <?php else: ?>
								  	<img src="<?php echo esc_url(CUSTOMERS_MANAGER_PN_URL . 'assets/media/customers-manager-pn-image.jpg'); ?>" class="customers-manager-pn-border-radius-20 customers-manager-pn-width-100-percent">
								  <?php endif ?>
								</a>
							</div>

							<a href="<?php echo esc_url(get_permalink($funnel_id)); ?>"><h4 class="customers-manager-pn-color-main-hover customers-manager-pn-mb-20"><?php echo esc_html(get_the_title($funnel_id)); ?></h4></a>

							<?php if (current_user_can('administrator') || current_user_can('customers_manager_pn_role_manager')): ?>
				  			<a href="<?php echo esc_url(admin_url('post.php?post=' . $funnel_id . '&action=edit')); ?>"><i class="material-icons-outlined customers-manager-pn-font-size-30 customers-manager-pn-vertical-align-middle customers-manager-pn-mr-10 customers-manager-pn-color-main-0">edit</i> <?php esc_html_e('Edit funnel', 'customers-manager-pn'); ?></a>
				  		<?php endif ?>
						</div>
			  	<?php endforeach ?>
				<?php endif ?>

				<?php if (current_user_can('administrator') || current_user_can('customers_manager_pn_role_manager')): ?>
					<div class="customers-manager-pn-display-inline-table customers-manager-pn-width-33-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-p-20 customers-manager-pn-text-align-center customers-manager-pn-vertical-align-top">
						<div class="customers-manager-pn-mb-30">
							<a href="<?php echo esc_url(admin_url('post-new.php?post_type=customers_manager_pn_funnel')); ?>">
								<img src="<?php echo esc_url(CUSTOMERS_MANAGER_PN_URL . 'assets/media/customers-manager-pn-image.jpg'); ?>" class="customers-manager-pn-border-radius-20 customers-manager-pn-width-100-percent customers-manager-pn-filter-grayscale">
							</a>
						</div>

						<a href="<?php echo esc_url(admin_url('post-new.php?post_type=customers_manager_pn_funnel')); ?>"><h4 class="customers-manager-pn-color-main-hover customers-manager-pn-mb-20"><i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-mr-10">add</i> <?php esc_html_e('Add funnel', 'customers-manager-pn'); ?></h4></a>
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