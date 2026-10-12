<?php
/**
 * Public template for single budget view.
 * Accessed via token-based URL (no login required).
 *
 * @package PN_Customers_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get budget data - set by the template_redirect handler via set_query_var().
$budget_id = get_query_var( 'pn_cm_budget_id', 0 );

if ( empty( $budget_id ) ) {
	wp_die( esc_html__( 'Budget not found.', 'pn-customers-manager' ) );
}
$budget_number          = get_post_meta( $budget_id, 'pn_cm_budget_number', true );
$budget_date            = get_post_meta( $budget_id, 'pn_cm_budget_date', true );
$budget_valid_until     = get_post_meta( $budget_id, 'pn_cm_budget_valid_until', true );
$budget_status          = get_post_meta( $budget_id, 'pn_cm_budget_status', true );
$budget_tax_rate        = floatval( get_post_meta( $budget_id, 'pn_cm_budget_tax_rate', true ) );
$budget_discount_rate   = floatval( get_post_meta( $budget_id, 'pn_cm_budget_discount_rate', true ) );
$budget_client_notes_raw = get_post_meta( $budget_id, 'pn_cm_budget_client_notes', true );
// Strip placeholder text that was incorrectly saved as content in previous versions.
$pn_cm_placeholder_strings = array(
	'Notes visible to the client on the public budget.',
	'Notas visibles para el cliente en el presupuesto público.',
	'Leave empty to use the default terms from settings.',
	'Dejar vacío para usar los términos predeterminados de los ajustes.',
	'Leave empty to use the default from settings.',
	'Dejar vacío para usar el valor predeterminado de los ajustes.',
	'Notes visible only to administrators.',
	'Notas visibles solo para administradores.',
);
if ( ! empty( $budget_client_notes_raw ) && in_array( trim( wp_strip_all_tags( $budget_client_notes_raw ) ), $pn_cm_placeholder_strings, true ) ) {
	$budget_client_notes_raw = '';
	delete_post_meta( $budget_id, 'pn_cm_budget_client_notes' );
}
$budget_client_notes = ! empty( $budget_client_notes_raw ) ? $budget_client_notes_raw : get_option( 'pn_customers_manager_budget_default_client_notes', '' );
$budget_token           = get_post_meta( $budget_id, 'pn_cm_budget_token', true );
$budget_subtotal        = floatval( get_post_meta( $budget_id, 'pn_cm_budget_subtotal', true ) );
$budget_tax_amount      = floatval( get_post_meta( $budget_id, 'pn_cm_budget_tax_amount', true ) );
$budget_discount_amount = floatval( get_post_meta( $budget_id, 'pn_cm_budget_discount_amount', true ) );
$budget_total           = floatval( get_post_meta( $budget_id, 'pn_cm_budget_total', true ) );

// Organization data.
$org_id        = get_post_meta( $budget_id, 'pn_cm_budget_organization_id', true );
$org_name      = ! empty( $org_id ) ? get_the_title( $org_id ) : '';
$org_email     = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_email', true ) : '';
$org_phone     = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_phone', true ) : '';
$org_address   = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_address', true ) : '';
$org_city      = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_city', true ) : '';
$org_postal    = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_postal_code', true ) : '';
$org_country   = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_country', true ) : '';
$org_fiscal_id = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_fiscal_id', true ) : '';

// Company data from settings.
$company_name      = get_option( 'pn_customers_manager_budget_company_name', '' );
$company_address   = get_option( 'pn_customers_manager_budget_company_address', '' );
$company_fiscal_id = get_option( 'pn_customers_manager_budget_company_fiscal_id', '' );
$company_logo_raw  = get_option( 'pn_customers_manager_budget_company_logo', '' );
$company_logo      = ( ! empty( $company_logo_raw ) && is_numeric( $company_logo_raw ) )
	? wp_get_attachment_url( intval( $company_logo_raw ) )
	: $company_logo_raw;
$budget_conditions = get_post_meta( $budget_id, 'pn_cm_budget_conditions', true );
if ( ! empty( $budget_conditions ) && in_array( trim( wp_strip_all_tags( $budget_conditions ) ), $pn_cm_placeholder_strings, true ) ) {
	$budget_conditions = '';
	delete_post_meta( $budget_id, 'pn_cm_budget_conditions' );
}
$terms             = ! empty( $budget_conditions ) ? $budget_conditions : get_option( 'pn_customers_manager_budget_terms', '' );
$budget_others     = get_post_meta( $budget_id, 'pn_cm_budget_others', true );
if ( ! empty( $budget_others ) && in_array( trim( wp_strip_all_tags( $budget_others ) ), $pn_cm_placeholder_strings, true ) ) {
	$budget_others = '';
	delete_post_meta( $budget_id, 'pn_cm_budget_others' );
}
$default_others    = get_option( 'pn_customers_manager_budget_default_others', '' );
$others            = ! empty( $budget_others ) ? $budget_others : $default_others;

// Currency settings.
$currency_symbol   = get_option( 'pn_customers_manager_budget_currency_symbol', '€' );
$currency_position = get_option( 'pn_customers_manager_budget_currency_position', 'after' );

// Admin check for inline editing on public page.
$is_admin = current_user_can( 'manage_options' );

// Hide admin bar on this standalone page (prevents Chrome local-network-access dialog).
show_admin_bar( false );

// Budget items from post meta.
$items = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_items( $budget_id );

// Detect optional items.
$has_optional = false;
foreach ( $items as $item ) {
	if ( ! empty( $item['is_optional'] ) ) {
		$has_optional = true;
		break;
	}
}

// Status display.
$status_labels = array(
	'draft'    => __( 'Draft', 'pn-customers-manager' ),
	'sent'     => __( 'Sent', 'pn-customers-manager' ),
	'accepted' => __( 'Accepted', 'pn-customers-manager' ),
	'rejected' => __( 'Rejected', 'pn-customers-manager' ),
);
$status_label  = isset( $status_labels[ $budget_status ] ) ? $status_labels[ $budget_status ] : $budget_status;

// Format currency helper.
$format_currency = function ( $amount ) use ( $currency_symbol, $currency_position ) {
	$formatted = number_format( floatval( $amount ), 2, ',', '.' );
	return 'before' === $currency_position ? $currency_symbol . $formatted : $formatted . ' ' . $currency_symbol;
};

// Enqueue styles and scripts.
wp_enqueue_style( 'pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-budget.css', array(), PN_CUSTOMERS_MANAGER_VERSION );
wp_enqueue_style( 'material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined', array(), null );
wp_enqueue_script( 'pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-budget.js', array( 'jquery' ), PN_CUSTOMERS_MANAGER_VERSION, true );
wp_localize_script(
	'pn-customers-manager-budget',
	'pnCmBudget',
	array(
		'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
		'nonce'          => wp_create_nonce( 'pn-customers-manager-nonce' ),
		'budgetId'       => $budget_id,
		'budgetToken'    => $budget_token,
		'currencySymbol' => $currency_symbol,
		'currencyPosition' => $currency_position,
		'taxRate'        => $budget_tax_rate,
		'discountRate'   => $budget_discount_rate,
		'isLoggedIn'     => is_user_logged_in(),
		'i18n'           => array(
			'confirmAccept' => __( 'Are you sure you want to accept this budget?', 'pn-customers-manager' ),
			'confirmReject' => __( 'Are you sure you want to reject this budget?', 'pn-customers-manager' ),
			'accepted'      => __( 'Budget accepted successfully.', 'pn-customers-manager' ),
			'rejected'      => __( 'Budget rejected.', 'pn-customers-manager' ),
			'error'         => __( 'An error occurred. Please try again.', 'pn-customers-manager' ),
			'emailRequired'    => __( 'Please enter your email address to accept this budget.', 'pn-customers-manager' ),
			'emailInvalid'     => __( 'Please enter a valid email address.', 'pn-customers-manager' ),
			'nameRequired'     => __( 'Please enter your full name.', 'pn-customers-manager' ),
			'signatureRequired' => __( 'Please draw your signature.', 'pn-customers-manager' ),
		),
		'recurringLabels' => array(
			'daily'   => __( 'Daily', 'pn-customers-manager' ),
			'weekly'  => __( 'Weekly', 'pn-customers-manager' ),
			'monthly' => __( 'Monthly', 'pn-customers-manager' ),
			'yearly'  => __( 'Yearly', 'pn-customers-manager' ),
		),
	)
);

// Admin inline editing scripts.
if ( $is_admin ) {
	// WP 6.7+ replaced underscore.js with a minimal compat shim missing methods
	// required by wp.media(). Override with full library before scripts are printed.
	add_action( 'wp_enqueue_scripts', function () {
		wp_deregister_script( 'underscore' );
		wp_register_script( 'underscore', PN_CUSTOMERS_MANAGER_URL . 'assets/js/vendor/underscore.min.js', array(), '1.13.7' );
	}, 1 );

	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );

	// Popups CSS/JS (needed for edit/add item popups).
	wp_enqueue_style( 'pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', array(), PN_CUSTOMERS_MANAGER_VERSION );
	wp_enqueue_script( 'pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', array( 'jquery' ), PN_CUSTOMERS_MANAGER_VERSION, true );

	// Material icons for admin UI.
	wp_enqueue_style( 'wph-material-icons-outlined', PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css', array(), PN_CUSTOMERS_MANAGER_VERSION );

	// Tooltips for admin buttons.
	wp_enqueue_style( 'pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-tooltips.css', array(), PN_CUSTOMERS_MANAGER_VERSION );
	wp_enqueue_script( 'pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-tooltips.js', array( 'jquery' ), PN_CUSTOMERS_MANAGER_VERSION, true );

	// Trumbowyg WYSIWYG editor for item popups.
	wp_enqueue_style( 'wph-trumbowyg', PN_CUSTOMERS_MANAGER_URL . 'assets/css/trumbowyg.min.css', array(), PN_CUSTOMERS_MANAGER_VERSION );
	wp_enqueue_script( 'wph-trumbowyg', PN_CUSTOMERS_MANAGER_URL . 'assets/js/trumbowyg.min.js', array( 'jquery' ), PN_CUSTOMERS_MANAGER_VERSION, true );
	wp_localize_script( 'wph-trumbowyg', 'pn_customers_manager_trumbowyg', array(
		'path' => PN_CUSTOMERS_MANAGER_URL . 'assets/media/trumbowyg-icons.svg',
	) );

	wp_enqueue_script(
		'pn-customers-manager-budget-admin',
		PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-budget-admin.js',
		array( 'jquery', 'jquery-ui-sortable', 'wph-trumbowyg' ),
		PN_CUSTOMERS_MANAGER_VERSION,
		true
	);
	wp_localize_script(
		'pn-customers-manager-budget-admin',
		'pnCmBudgetAdmin',
		array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'pn-customers-manager-nonce' ),
			'budgetId'          => $budget_id,
			'currencySymbol'    => $currency_symbol,
			'currencyPosition'  => $currency_position,
			'defaultHourlyRate' => get_option( 'pn_customers_manager_budget_default_hourly_rate', '0' ),
			'hasOptionalItems'  => $has_optional,
			'i18n'              => array(
				'error'            => __( 'An error occurred.', 'pn-customers-manager' ),
				'confirmDelete'    => __( 'Are you sure you want to delete this item?', 'pn-customers-manager' ),
				'noDescription'    => __( 'Please enter a description.', 'pn-customers-manager' ),
				'newPhase'         => __( 'New phase', 'pn-customers-manager' ),
				'selectImage'      => __( 'Select image', 'pn-customers-manager' ),
				'useImage'         => __( 'Use image', 'pn-customers-manager' ),
				'addImage'         => __( 'Add image', 'pn-customers-manager' ),
				'singleImage'      => __( 'Image', 'pn-customers-manager' ),
				'singleImageDesc'  => __( 'Upload one or more images', 'pn-customers-manager' ),
				'compareImage'     => __( 'Before / After', 'pn-customers-manager' ),
				'compareImageDesc' => __( 'Select 2 images for a slide comparison', 'pn-customers-manager' ),
				'selectBeforeAfter'=> __( 'Select 2 images (before / after)', 'pn-customers-manager' ),
				'selectExactlyTwo' => __( 'Please select exactly 2 images.', 'pn-customers-manager' ),
				'save'             => __( 'Save', 'pn-customers-manager' ),
				'importItemsTitle' => __( 'Import items from CSV', 'pn-customers-manager' ),
				'importDropText'   => __( 'Drag & drop a CSV file here or click to select', 'pn-customers-manager' ),
				'importInvalidFile'=> __( 'Please select a valid CSV file.', 'pn-customers-manager' ),
				'importSuccess'    => __( 'items imported successfully.', 'pn-customers-manager' ),
				'copied'           => __( 'Copied!', 'pn-customers-manager' ),
				'bulkSelect'       => __( 'Select items', 'pn-customers-manager' ),
				'deleteSelected'   => __( 'Delete selected', 'pn-customers-manager' ),
				'confirmBulkDelete'=> __( 'Are you sure you want to delete the selected items?', 'pn-customers-manager' ),
				'noItemsSelected'  => __( 'No items selected.', 'pn-customers-manager' ),
				'bulkOptionalOn'   => __( 'Mark selected items as optional?', 'pn-customers-manager' ),
				'bulkOptionalOff'  => __( 'Remove optional from selected items?', 'pn-customers-manager' ),
				'bulkRecurringOn'  => __( 'Mark selected items as recurring?', 'pn-customers-manager' ),
				'bulkRecurringOff' => __( 'Remove recurring from selected items?', 'pn-customers-manager' ),
			),
			'recurringLabels'   => array(
				'daily'   => __( 'Daily', 'pn-customers-manager' ),
				'weekly'  => __( 'Weekly', 'pn-customers-manager' ),
				'monthly' => __( 'Monthly', 'pn-customers-manager' ),
				'yearly'  => __( 'Yearly', 'pn-customers-manager' ),
			),
		)
	);
}

// ─── Dequeue unnecessary assets loaded by common.php and theme ────────────────
// This standalone page only needs: budget CSS/JS, material-icons, jQuery,
// and for admins: popups, sortable, budget-admin JS.
add_action( 'wp_enqueue_scripts', function () use ( $is_admin ) {
	// Styles to always remove (conflict with budget standalone layout).
	$styles_to_remove = array(
		'pn-customers-manager',              // Main plugin CSS — overrides budget layout
		'pn-customers-manager-public',       // Public styles not needed on budget page
		'pn-customers-manager-selector',
		'wph-owl',
		'pn-customers-manager-referral',
		'pn-customers-manager-commercial',
		'pn-customers-manager-email-campaigns',
	);

	// Scripts to always remove (not needed or cause layout shift).
	$scripts_to_remove = array(
		'pn-customers-manager',              // Main plugin JS
		'pn-customers-manager-aux',          // Initializes Selector on ALL selects — causes layout shift
		'pn-customers-manager-public',       // Calls owlCarousel which is not loaded here
		'pn-customers-manager-selector',
		'pn-customers-manager-forms',
		'pn-customers-manager-ajax',
		'wph-owl',
		'pn-customers-manager-referral',
		'pn-customers-manager-qrcode',
		'pn-customers-manager-referral-qr',
		'pn-customers-manager-referral-bizcard',
		'pn-customers-manager-commercial',
		'pn-customers-manager-email-campaigns',
	);

	if ( ! $is_admin ) {
		// Non-admin: tooltips not needed.
		$styles_to_remove[]  = 'pn-customers-manager-tooltips';
		$scripts_to_remove[] = 'pn-customers-manager-tooltips';
	}

	if ( ! $is_admin ) {
		// Non-admin: also remove Trumbowyg (not needed for public view).
		$styles_to_remove[]  = 'wph-trumbowyg';
		$scripts_to_remove[] = 'wph-trumbowyg';
	}

	if ( ! $is_admin ) {
		// Non-admin: also remove popups and material icons (not needed for public view).
		$styles_to_remove[]  = 'pn-customers-manager-popups';
		$styles_to_remove[]  = 'wph-material-icons-outlined';
		$scripts_to_remove[] = 'pn-customers-manager-popups';
		$scripts_to_remove[] = 'jquery-ui-sortable';
	}

	foreach ( $styles_to_remove as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	foreach ( $scripts_to_remove as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}

	// Remove ALL theme and other plugin styles to prevent layout conflicts.
	// Keep our whitelist + WP core styles needed by wp.media for admins.
	$allowed_styles = array(
		'pn-customers-manager-budget',
		'material-icons',
		'pn-customers-manager-popups',
		'wph-material-icons-outlined',
	);

	if ( $is_admin ) {
		// Admins need WP core styles for wp.media modal.
		$allowed_styles = array_merge( $allowed_styles, array(
			'dashicons', 'media-views', 'imgareaselect', 'buttons',
			'editor-buttons', 'wp-auth-check', 'wp-mediaelement',
			'pn-customers-manager-invoice',
			'wph-trumbowyg',
			'pn-customers-manager-tooltips',
		) );
	}

	global $wp_styles;
	if ( ! empty( $wp_styles->queue ) ) {
		$queued_styles = array_values( $wp_styles->queue );
		foreach ( $queued_styles as $handle ) {
			if ( ! in_array( $handle, $allowed_styles, true ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}

	// Also remove ALL theme/other plugin scripts — only keep our whitelist.
	$allowed_scripts = array(
		'jquery',
		'jquery-core',
		'jquery-migrate',
		'pn-customers-manager-budget',
	);

	if ( $is_admin ) {
		$allowed_scripts = array_merge( $allowed_scripts, array(
			'jquery-ui-core',
			'jquery-ui-mouse',
			'jquery-ui-widget',
			'jquery-ui-sortable',
			'pn-customers-manager-budget-admin',
			'pn-customers-manager-popups',
			'wph-trumbowyg',
			'pn-customers-manager-tooltips',
		) );
	}

	global $wp_scripts;
	if ( ! empty( $wp_scripts->queue ) ) {
		$queued_scripts = array_values( $wp_scripts->queue );
		foreach ( $queued_scripts as $handle ) {
			if ( in_array( $handle, $allowed_scripts, true ) ) {
				continue;
			}
			// For admins, keep all WP core scripts (wp-includes / wp-admin)
			// so wp.media and its full dependency tree remain intact.
			// WP may store src as relative path or full URL, so check both.
			if ( $is_admin && isset( $wp_scripts->registered[ $handle ] ) ) {
				$src = $wp_scripts->registered[ $handle ]->src;
				if ( $src && ( strpos( $src, '/wp-includes/' ) !== false || strpos( $src, '/wp-admin/' ) !== false ) ) {
					continue;
				}
			}
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}, 999 );

// Pre-process items into groups: loose items and phase groups.
$groups        = array();
$current_phase = null;
foreach ( $items as $item ) {
	if ( 'phase' === $item['item_type'] ) {
		$current_phase = count( $groups );
		$groups[]      = array(
			'phase' => $item,
			'items' => array(),
		);
	} else {
		if ( null !== $current_phase ) {
			$groups[ $current_phase ]['items'][] = $item;
		} else {
			// Loose item (before any phase).
			$groups[] = array(
				'phase' => null,
				'items' => array( $item ),
			);
		}
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<?php
	$pn_cm_og_title = sprintf( __( 'Budget %s', 'pn-customers-manager' ), $budget_number );
	if ( ! empty( $company_name ) ) {
		$pn_cm_og_title .= ' — ' . $company_name;
	}
	$pn_cm_og_description = '';
	if ( ! empty( $org_name ) ) {
		$pn_cm_og_description .= $org_name . ' · ';
	}
	$pn_cm_og_description .= $format_currency( $budget_total );
	?>
	<title><?php echo esc_html( $pn_cm_og_title ); ?></title>
	<meta property="og:title" content="<?php echo esc_attr( $pn_cm_og_title ); ?>" />
	<meta property="og:description" content="<?php echo esc_attr( $pn_cm_og_description ); ?>" />
	<meta property="og:type" content="website" />
	<?php if ( ! empty( $company_logo ) ) : ?>
		<meta property="og:image" content="<?php echo esc_url( $company_logo ); ?>" />
	<?php endif; ?>
	<?php
	// Schema.org JSON-LD structured data.
	$pn_cm_currency_map = array( '€' => 'EUR', '$' => 'USD', '£' => 'GBP', '¥' => 'JPY' );
	$pn_cm_currency_code = isset( $pn_cm_currency_map[ $currency_symbol ] ) ? $pn_cm_currency_map[ $currency_symbol ] : $currency_symbol;
	$pn_cm_schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'Invoice',
		'identifier'      => $budget_number,
		'description'     => sprintf( 'Budget %s', $budget_number ),
		'totalPaymentDue' => array(
			'@type'    => 'PriceSpecification',
			'price'    => number_format( $budget_total, 2, '.', '' ),
			'priceCurrency' => $pn_cm_currency_code,
		),
		'paymentStatus'   => ( 'accepted' === $budget_status ) ? 'PaymentComplete' : 'PaymentDue',
	);
	if ( ! empty( $budget_date ) ) {
		$pn_cm_schema['paymentDueDate'] = $budget_date;
	}
	if ( ! empty( $company_name ) ) {
		$pn_cm_schema['provider'] = array(
			'@type' => 'Organization',
			'name'  => $company_name,
		);
	}
	if ( ! empty( $org_name ) ) {
		$pn_cm_schema['customer'] = array(
			'@type' => 'Organization',
			'name'  => $org_name,
		);
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $pn_cm_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
	<?php wp_head(); ?>
</head>
<body class="pn-customers-manager-budget-public">
	<div class="pn-cm-budget-wrapper">
		<?php if ( $is_admin ) :
			$budget_list_page_id = get_option( 'pn_customers_manager_page_budget_list', 0 );
			$budget_list_url     = ! empty( $budget_list_page_id ) ? get_permalink( $budget_list_page_id ) : '';
			if ( ! empty( $budget_list_url ) ) :
		?>
			<a href="<?php echo esc_url( $budget_list_url ); ?>" class="pn-cm-budget-back-link">
				<i class="material-icons-outlined">arrow_back</i>
				<?php esc_html_e( 'Back to budgets', 'pn-customers-manager' ); ?>
			</a>
		<?php endif; endif; ?>

		<!-- Header -->
		<header class="pn-cm-budget-header">
			<div class="pn-cm-budget-header-company">
				<?php if ( ! empty( $company_logo ) ) : ?>
					<img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company_name ); ?>" class="pn-cm-budget-logo">
				<?php endif; ?>
				<div class="pn-cm-budget-company-info">
					<?php if ( ! empty( $company_name ) ) : ?>
						<h2><?php echo esc_html( $company_name ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $company_address ) ) : ?>
						<p><?php echo nl2br( esc_html( $company_address ) ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $company_fiscal_id ) ) : ?>
						<p><?php echo esc_html( $company_fiscal_id ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<div class="pn-cm-budget-header-meta">
				<h1><?php echo esc_html( sprintf( __( 'Budget %s', 'pn-customers-manager' ), $budget_number ) ); ?></h1>
				<?php if ( $is_admin ) : ?>
					<div class="pn-cm-budget-status-wrapper">
						<span class="pn-cm-budget-status pn-cm-budget-status-<?php echo esc_attr( $budget_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
						<select class="pn-cm-budget-status-select" data-current="<?php echo esc_attr( $budget_status ); ?>">
							<?php foreach ( $status_labels as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $budget_status, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php else : ?>
					<span class="pn-cm-budget-status pn-cm-budget-status-<?php echo esc_attr( $budget_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
				<?php endif; ?>
			</div>
		</header>

		<!-- Budget info row -->
		<div class="pn-cm-budget-info-row">
			<div class="pn-cm-budget-info-block">
				<h3><?php esc_html_e( 'Bill to', 'pn-customers-manager' ); ?></h3>
				<?php if ( ! empty( $org_name ) ) : ?>
					<p><strong><?php echo esc_html( $org_name ); ?></strong></p>
				<?php endif; ?>
				<?php if ( ! empty( $org_address ) ) : ?>
					<p><?php echo esc_html( implode( ', ', array_filter( array( $org_address, $org_postal, $org_city, $org_country ) ) ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $org_fiscal_id ) ) : ?>
					<p><?php echo esc_html( $org_fiscal_id ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $org_email ) ) : ?>
					<p><?php echo esc_html( $org_email ); ?></p>
				<?php endif; ?>
			</div>
			<div class="pn-cm-budget-info-block">
				<h3><?php esc_html_e( 'Details', 'pn-customers-manager' ); ?></h3>
				<p><strong><?php esc_html_e( 'Date:', 'pn-customers-manager' ); ?></strong> <?php echo esc_html( $budget_date ); ?></p>
				<p><strong><?php esc_html_e( 'Valid until:', 'pn-customers-manager' ); ?></strong> <?php echo esc_html( $budget_valid_until ); ?></p>
			</div>
		</div>

		<!-- Client notes -->
		<?php if ( ! empty( $budget_client_notes ) || $is_admin ) : ?>
			<div class="pn-cm-budget-client-notes" id="pn-cm-budget-client-notes-block">
				<?php if ( $is_admin ) : ?>
					<a href="#" class="pn-cm-budget-field-edit pn-customers-manager-tooltip" data-field="pn_cm_budget_client_notes" data-label="<?php esc_attr_e( 'Client notes', 'pn-customers-manager' ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">edit</i>
					</a>
				<?php endif; ?>
				<?php if ( ! empty( $budget_client_notes ) ) : ?>
					<div class="pn-cm-budget-field-content"><?php echo wp_kses_post( $budget_client_notes ); ?></div>
				<?php else : ?>
					<div class="pn-cm-budget-field-content pn-cm-budget-field-empty"><em><?php esc_html_e( 'Client notes (click edit to add)', 'pn-customers-manager' ); ?></em></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Items -->
		<div class="pn-cm-budget-items-section" data-budget-id="<?php echo esc_attr( $budget_id ); ?>">
			<?php if ( $is_admin ) : ?>
				<input type="hidden" id="pn_cm_budget_tax_rate" value="<?php echo esc_attr( $budget_tax_rate ); ?>" />
				<input type="hidden" id="pn_cm_budget_discount_rate" value="<?php echo esc_attr( $budget_discount_rate ); ?>" />
			<?php endif; ?>
			<div class="pn-cm-budget-items-list" id="pn-cm-budget-items-body">
				<?php if ( ! empty( $groups ) ) : ?>
					<?php foreach ( $groups as $group ) : ?>
						<?php if ( null === $group['phase'] ) : ?>
							<?php // Loose items (no phase parent). ?>
							<?php foreach ( $group['items'] as $item ) : ?>
								<?php if ( 'image' === $item['item_type'] || 'image_compare' === $item['item_type'] ) { continue; } ?>
								<?php
									$item_alt_of = ! empty( $item['alt_of'] ) ? intval( $item['alt_of'] ) : 0;
									$item_alt_active = ! empty( $item['alt_active'] ) ? $item['alt_active'] : 'original';
									$item_has_alt = false;
									if ( ! $item_alt_of ) {
										foreach ( $group['items'] as $ci ) {
											if ( ! empty( $ci['alt_of'] ) && intval( $ci['alt_of'] ) === intval( $item['id'] ) ) {
												$item_has_alt = true;
												break;
											}
										}
									}
									$item_alt_hidden = '';
									if ( $item_alt_of > 0 ) {
										$parent_act = 'original';
										foreach ( $group['items'] as $ci ) {
											if ( intval( $ci['id'] ) === $item_alt_of ) {
												$parent_act = ! empty( $ci['alt_active'] ) ? $ci['alt_active'] : 'original';
												break;
											}
										}
										if ( $parent_act !== 'alternative' ) $item_alt_hidden = ' pn-cm-budget-alt-hidden';
									} elseif ( $item_has_alt && $item_alt_active === 'alternative' ) {
										$item_alt_hidden = ' pn-cm-budget-alt-hidden';
									}
								?>
								<div class="pn-cm-budget-item-row<?php echo esc_attr( $item_alt_hidden ); ?><?php echo $item_alt_of ? ' pn-cm-budget-alt-item' : ''; ?> <?php echo ! empty( $item['is_optional'] ) && empty( $item['is_selected'] ) ? 'pn-cm-budget-item-deselected' : ''; ?>"
									data-item-id="<?php echo esc_attr( $item['id'] ); ?>"
									data-item-type="<?php echo esc_attr( $item['item_type'] ); ?>"
									data-item-description="<?php echo esc_attr( $item['description'] ); ?>"
									data-item-quantity="<?php echo esc_attr( number_format( floatval( $item['quantity'] ), 2, '.', '' ) ); ?>"
									data-item-unit-price="<?php echo esc_attr( number_format( floatval( $item['unit_price'] ), 2, '.', '' ) ); ?>"
									data-item-optional="<?php echo esc_attr( $item['is_optional'] ); ?>"
									data-item-total="<?php echo esc_attr( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ); ?>"
									data-item-recurring="<?php echo esc_attr( ! empty( $item['is_recurring'] ) ? '1' : '0' ); ?>"
									data-item-recurring-period="<?php echo esc_attr( ! empty( $item['recurring_period'] ) ? $item['recurring_period'] : '' ); ?>"
									data-alt-of="<?php echo esc_attr( $item_alt_of ); ?>"
									data-alt-active="<?php echo esc_attr( $item_alt_active ); ?>">
									<?php if ( $is_admin ) : ?>
										<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Drag to reorder', 'pn-customers-manager' ); ?>">drag_indicator</i></span>
									<?php endif; ?>
									<div class="pn-cm-budget-item-content">
										<div class="pn-cm-budget-item-title">
											<?php if ( $item_alt_of ) : ?>
												<span class="pn-cm-budget-alt-badge"><?php esc_html_e( 'ALT', 'pn-customers-manager' ); ?></span>
											<?php endif; ?>
											<span class="pn-cm-budget-col-desc"><?php echo wp_kses_post( $item['description'] ); ?></span>
											<?php if ( $item_has_alt || $item_alt_of ) : ?>
												<a href="#" class="pn-cm-budget-swap-alt pn-customers-manager-tooltip" data-original-id="<?php echo esc_attr( $item_alt_of ? $item_alt_of : $item['id'] ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Toggle alternative', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">swap_horiz</i></a>
											<?php endif; ?>
											<?php if ( $has_optional ) : ?>
												<span class="pn-cm-budget-col-toggle">
													<?php if ( ! empty( $item['is_optional'] ) ) : ?>
														<label class="pn-cm-budget-toggle">
															<input type="checkbox" class="pn-cm-budget-toggle-item" data-item-id="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( $item['is_selected'], 1 ); ?>>
															<span class="pn-cm-budget-toggle-slider"></span>
														</label>
													<?php endif; ?>
												</span>
											<?php endif; ?>
											<?php if ( $is_admin ) : ?>
												<span class="pn-cm-budget-col-actions">
													<a href="#" class="pn-cm-budget-row-edit pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
													<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
												</span>
											<?php endif; ?>
										</div>
										<div class="pn-cm-budget-item-meta">
											<span class="pn-cm-budget-col-type"><i class="material-icons-outlined pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php echo 'hours' === $item['item_type'] ? esc_attr__( 'Hourly rate', 'pn-customers-manager' ) : esc_attr__( 'Fixed price', 'pn-customers-manager' ); ?>"><?php echo 'hours' === $item['item_type'] ? 'schedule' : 'payments'; ?></i></span>
											<?php if ( ! empty( $item['is_recurring'] ) && ! empty( $item['recurring_period'] ) ) :
												$rl = array( 'daily' => __( 'Daily', 'pn-customers-manager' ), 'weekly' => __( 'Weekly', 'pn-customers-manager' ), 'monthly' => __( 'Monthly', 'pn-customers-manager' ), 'yearly' => __( 'Yearly', 'pn-customers-manager' ) );
											?>
												<span class="pn-cm-budget-recurring-badge"><i class="material-icons-outlined">autorenew</i> <?php echo esc_html( isset( $rl[ $item['recurring_period'] ] ) ? $rl[ $item['recurring_period'] ] : $item['recurring_period'] ); ?></span>
											<?php endif; ?>
											<span class="pn-cm-budget-col-qty"><?php echo esc_html( number_format( floatval( $item['quantity'] ), 2, ',', '.' ) ); ?></span>
											<span class="pn-cm-budget-col-price"><?php echo esc_html( $format_currency( $item['unit_price'] ) ); ?></span>
											<span class="pn-cm-budget-col-total"><?php echo esc_html( $format_currency( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ) ); ?></span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<?php
							// Phase group.
							$phase_item   = $group['phase'];
							$phase_items  = $group['items'];
							$all_selected    = true;
							$all_optional    = true;
							$phase_total     = 0;
							$phase_has_items = false;

							$phase_alt_of     = ! empty( $phase_item['alt_of'] ) ? intval( $phase_item['alt_of'] ) : 0;
							$phase_alt_active = ! empty( $phase_item['alt_active'] ) ? $phase_item['alt_active'] : 'original';
							$phase_has_alt    = false;
							if ( ! $phase_alt_of ) {
								foreach ( $groups as $g_check ) {
									if ( ! empty( $g_check['phase'] ) && ! empty( $g_check['phase']['alt_of'] ) && intval( $g_check['phase']['alt_of'] ) === intval( $phase_item['id'] ) ) {
										$phase_has_alt = true;
										break;
									}
								}
							}
							$phase_alt_hidden = '';
							if ( $phase_alt_of > 0 ) {
								$parent_phase_active = 'original';
								foreach ( $groups as $g_check ) {
									if ( ! empty( $g_check['phase'] ) && intval( $g_check['phase']['id'] ) === $phase_alt_of ) {
										$parent_phase_active = ! empty( $g_check['phase']['alt_active'] ) ? $g_check['phase']['alt_active'] : 'original';
										break;
									}
								}
								if ( $parent_phase_active !== 'alternative' ) {
									$phase_alt_hidden = ' pn-cm-budget-alt-hidden';
								}
							} elseif ( $phase_has_alt && $phase_alt_active === 'alternative' ) {
								$phase_alt_hidden = ' pn-cm-budget-alt-hidden';
							}

							foreach ( $phase_items as $pi ) {
								if ( 'image' === $pi['item_type'] || 'image_compare' === $pi['item_type'] ) {
									continue;
								}
								$phase_has_items = true;
								if ( empty( $pi['is_optional'] ) ) {
									$all_optional = false;
								}
								if ( empty( $pi['is_selected'] ) ) {
									$all_selected = false;
								} else {
									$phase_total += floatval( $pi['quantity'] ) * floatval( $pi['unit_price'] );
								}
							}
							?>
							<div class="pn-cm-budget-phase-group<?php echo esc_attr( $phase_alt_hidden ); ?><?php echo $phase_alt_of ? ' pn-cm-budget-alt-phase' : ''; ?>" data-phase-id="<?php echo esc_attr( $phase_item['id'] ); ?>" data-alt-of="<?php echo esc_attr( $phase_alt_of ); ?>" data-alt-active="<?php echo esc_attr( $phase_alt_active ); ?>">
								<div class="pn-cm-budget-item-row pn-cm-budget-item-phase"
									data-item-id="<?php echo esc_attr( $phase_item['id'] ); ?>"
									data-item-type="phase"
									data-item-description="<?php echo esc_attr( $phase_item['description'] ); ?>"
									data-alt-of="<?php echo esc_attr( $phase_alt_of ); ?>"
									data-alt-active="<?php echo esc_attr( $phase_alt_active ); ?>">
									<?php if ( $is_admin ) : ?>
										<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Drag to reorder', 'pn-customers-manager' ); ?>">drag_indicator</i></span>
									<?php endif; ?>
									<i class="material-icons-outlined pn-cm-budget-phase-toggle pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Collapse / Expand', 'pn-customers-manager' ); ?>">expand_more</i>
									<div class="pn-cm-budget-item-content">
										<?php if ( $phase_alt_of ) : ?>
											<span class="pn-cm-budget-alt-badge"><?php esc_html_e( 'ALT', 'pn-customers-manager' ); ?></span>
										<?php endif; ?>
										<span class="pn-cm-budget-col-phase"><?php echo wp_kses_post( $phase_item['description'] ); ?></span>
										<?php if ( $phase_has_alt || $phase_alt_of ) : ?>
											<a href="#" class="pn-cm-budget-swap-alt-phase pn-customers-manager-tooltip" data-original-id="<?php echo esc_attr( $phase_alt_of ? $phase_alt_of : $phase_item['id'] ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Toggle alternative phase', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">swap_horiz</i></a>
										<?php endif; ?>
										<span class="pn-cm-budget-phase-total"><?php echo esc_html( $format_currency( $phase_total ) ); ?></span>
										<?php if ( $phase_has_items && $all_optional ) : ?>
											<label class="pn-cm-budget-toggle pn-cm-budget-phase-switch">
												<input type="checkbox" class="pn-cm-budget-phase-switch-input" <?php checked( $all_selected ); ?>>
												<span class="pn-cm-budget-toggle-slider"></span>
											</label>
										<?php endif; ?>
										<?php if ( $is_admin ) : ?>
											<span class="pn-cm-budget-col-actions">
												<a href="#" class="pn-cm-budget-row-edit pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
												<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
											</span>
										<?php endif; ?>
									</div>
								</div>
								<div class="pn-cm-budget-phase-items">
									<?php foreach ( $phase_items as $item ) : ?>
										<?php if ( 'image' === $item['item_type'] || 'image_compare' === $item['item_type'] ) { continue; } ?>
										<?php
											$pi_alt_of = ! empty( $item['alt_of'] ) ? intval( $item['alt_of'] ) : 0;
											$pi_alt_active = ! empty( $item['alt_active'] ) ? $item['alt_active'] : 'original';
											$pi_has_alt = false;
											if ( ! $pi_alt_of ) {
												foreach ( $phase_items as $pci ) {
													if ( ! empty( $pci['alt_of'] ) && intval( $pci['alt_of'] ) === intval( $item['id'] ) ) {
														$pi_has_alt = true;
														break;
													}
												}
											}
											$pi_alt_hidden = '';
											if ( $pi_alt_of > 0 ) {
												$p_act = 'original';
												foreach ( $phase_items as $pci ) {
													if ( intval( $pci['id'] ) === $pi_alt_of ) {
														$p_act = ! empty( $pci['alt_active'] ) ? $pci['alt_active'] : 'original';
														break;
													}
												}
												if ( $p_act !== 'alternative' ) $pi_alt_hidden = ' pn-cm-budget-alt-hidden';
											} elseif ( $pi_has_alt && $pi_alt_active === 'alternative' ) {
												$pi_alt_hidden = ' pn-cm-budget-alt-hidden';
											}
										?>
										<div class="pn-cm-budget-item-row<?php echo esc_attr( $pi_alt_hidden ); ?><?php echo $pi_alt_of ? ' pn-cm-budget-alt-item' : ''; ?> <?php echo ! empty( $item['is_optional'] ) && empty( $item['is_selected'] ) ? 'pn-cm-budget-item-deselected' : ''; ?>"
											data-item-id="<?php echo esc_attr( $item['id'] ); ?>"
											data-item-type="<?php echo esc_attr( $item['item_type'] ); ?>"
											data-item-description="<?php echo esc_attr( $item['description'] ); ?>"
											data-item-quantity="<?php echo esc_attr( number_format( floatval( $item['quantity'] ), 2, '.', '' ) ); ?>"
											data-item-unit-price="<?php echo esc_attr( number_format( floatval( $item['unit_price'] ), 2, '.', '' ) ); ?>"
											data-item-optional="<?php echo esc_attr( $item['is_optional'] ); ?>"
											data-item-total="<?php echo esc_attr( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ); ?>"
											data-item-recurring="<?php echo esc_attr( ! empty( $item['is_recurring'] ) ? '1' : '0' ); ?>"
											data-item-recurring-period="<?php echo esc_attr( ! empty( $item['recurring_period'] ) ? $item['recurring_period'] : '' ); ?>"
											data-alt-of="<?php echo esc_attr( $pi_alt_of ); ?>"
											data-alt-active="<?php echo esc_attr( $pi_alt_active ); ?>">
											<?php if ( $is_admin ) : ?>
												<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Drag to reorder', 'pn-customers-manager' ); ?>">drag_indicator</i></span>
											<?php endif; ?>
											<div class="pn-cm-budget-item-content">
												<div class="pn-cm-budget-item-title">
													<?php if ( $pi_alt_of ) : ?>
														<span class="pn-cm-budget-alt-badge"><?php esc_html_e( 'ALT', 'pn-customers-manager' ); ?></span>
													<?php endif; ?>
													<span class="pn-cm-budget-col-desc"><?php echo wp_kses_post( $item['description'] ); ?></span>
													<?php if ( $pi_has_alt || $pi_alt_of ) : ?>
														<a href="#" class="pn-cm-budget-swap-alt pn-customers-manager-tooltip" data-original-id="<?php echo esc_attr( $pi_alt_of ? $pi_alt_of : $item['id'] ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Toggle alternative', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">swap_horiz</i></a>
													<?php endif; ?>
													<?php if ( $has_optional ) : ?>
														<span class="pn-cm-budget-col-toggle">
															<?php if ( ! empty( $item['is_optional'] ) ) : ?>
																<label class="pn-cm-budget-toggle">
																	<input type="checkbox" class="pn-cm-budget-toggle-item" data-item-id="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( $item['is_selected'], 1 ); ?>>
																	<span class="pn-cm-budget-toggle-slider"></span>
																</label>
															<?php endif; ?>
														</span>
													<?php endif; ?>
													<?php if ( $is_admin ) : ?>
														<span class="pn-cm-budget-col-actions">
															<a href="#" class="pn-cm-budget-row-edit pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
															<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
														</span>
													<?php endif; ?>
												</div>
												<div class="pn-cm-budget-item-meta">
													<span class="pn-cm-budget-col-type"><i class="material-icons-outlined pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php echo 'hours' === $item['item_type'] ? esc_attr__( 'Hourly rate', 'pn-customers-manager' ) : esc_attr__( 'Fixed price', 'pn-customers-manager' ); ?>"><?php echo 'hours' === $item['item_type'] ? 'schedule' : 'payments'; ?></i></span>
													<?php if ( ! empty( $item['is_recurring'] ) && ! empty( $item['recurring_period'] ) ) :
														$rl2 = array( 'daily' => __( 'Daily', 'pn-customers-manager' ), 'weekly' => __( 'Weekly', 'pn-customers-manager' ), 'monthly' => __( 'Monthly', 'pn-customers-manager' ), 'yearly' => __( 'Yearly', 'pn-customers-manager' ) );
													?>
														<span class="pn-cm-budget-recurring-badge"><i class="material-icons-outlined">autorenew</i> <?php echo esc_html( isset( $rl2[ $item['recurring_period'] ] ) ? $rl2[ $item['recurring_period'] ] : $item['recurring_period'] ); ?></span>
													<?php endif; ?>
													<span class="pn-cm-budget-col-qty"><?php echo esc_html( number_format( floatval( $item['quantity'] ), 2, ',', '.' ) ); ?></span>
													<span class="pn-cm-budget-col-price"><?php echo esc_html( $format_currency( $item['unit_price'] ) ); ?></span>
													<span class="pn-cm-budget-col-total"><?php echo esc_html( $format_currency( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ) ); ?></span>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="pn-cm-budget-no-items">
						<?php esc_html_e( 'No items in this budget.', 'pn-customers-manager' ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Images gallery -->
			<?php
			$gallery_images = [];
			$gallery_compares = [];
			if ( ! empty( $groups ) ) {
				foreach ( $groups as $gallery_group ) {
					foreach ( $gallery_group['items'] as $gi ) {
						if ( 'image' === $gi['item_type'] ) {
							$gallery_images[] = $gi;
						} elseif ( 'image_compare' === $gi['item_type'] ) {
							$gallery_compares[] = $gi;
						}
					}
				}
			}
			?>
			<?php if ( ! empty( $gallery_images ) || ! empty( $gallery_compares ) || $is_admin ) : ?>
				<div class="pn-cm-budget-images-gallery" id="pn-cm-budget-images-gallery" data-budget-id="<?php echo esc_attr( $budget_id ); ?>">
					<?php foreach ( $gallery_compares as $gc ) :
						$gc_data = json_decode( $gc['description'], true );
						$gc_before = ! empty( $gc_data['before'] ) ? $gc_data['before'] : '';
						$gc_after = ! empty( $gc_data['after'] ) ? $gc_data['after'] : '';
					?>
						<div class="pn-cm-budget-item-row pn-cm-budget-item-image-compare pn-cm-budget-gallery-item"
							data-item-id="<?php echo esc_attr( $gc['id'] ); ?>"
							data-item-type="image_compare"
							data-item-description="<?php echo esc_attr( $gc['description'] ); ?>">
							<div class="pn-cm-budget-item-content">
								<div class="pn-cm-budget-compare-slider">
									<span class="pn-cm-budget-compare-label pn-cm-budget-compare-label-before"><?php esc_html_e( 'Antes', 'pn-customers-manager' ); ?></span>
									<span class="pn-cm-budget-compare-label pn-cm-budget-compare-label-after"><?php esc_html_e( 'After', 'pn-customers-manager' ); ?></span>
									<div class="pn-cm-budget-compare-before"><img src="<?php echo esc_url( $gc_before ); ?>" alt="<?php esc_attr_e( 'Antes', 'pn-customers-manager' ); ?>" /></div>
									<div class="pn-cm-budget-compare-after"><img src="<?php echo esc_url( $gc_after ); ?>" alt="<?php esc_attr_e( 'After', 'pn-customers-manager' ); ?>" /></div>
									<div class="pn-cm-budget-compare-handle"></div>
								</div>
								<?php if ( $is_admin ) : ?>
									<span class="pn-cm-budget-col-actions">
										<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
									</span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
					<?php foreach ( $gallery_images as $gi ) : ?>
						<div class="pn-cm-budget-item-row pn-cm-budget-item-image pn-cm-budget-gallery-item"
							data-item-id="<?php echo esc_attr( $gi['id'] ); ?>"
							data-item-type="image"
							data-item-description="<?php echo esc_attr( $gi['description'] ); ?>">
							<div class="pn-cm-budget-item-content">
								<img src="<?php echo esc_url( $gi['description'] ); ?>" class="pn-cm-budget-image-preview" alt="" />
								<?php if ( $is_admin ) : ?>
									<span class="pn-cm-budget-col-actions">
										<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
									</span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $is_admin ) : ?>
				<div class="pn-cm-budget-admin-buttons">
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-customers-manager-budget-add-phase" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Add phase', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">segment</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-customers-manager-budget-add-item" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Add item', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">add</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-customers-manager-budget-add-image" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Add image', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">image</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-budget-export-items" data-budget-id="<?php echo esc_attr( $budget_id ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Export items', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">download</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-budget-import-items" data-budget-id="<?php echo esc_attr( $budget_id ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Import items', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">upload</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-budget-ai-prompt-toggle" data-budget-id="<?php echo esc_attr( $budget_id ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'AI prompt', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">psychology</i>
					</a>
					<span class="pn-cm-budget-admin-buttons-separator"></span>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-budget-bulk-select-toggle" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Select items', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">checklist</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-budget-bulk-delete" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Delete selected', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">delete_sweep</i>
					</a>
					<span class="pn-cm-budget-bulk-action-switch">
						<span class="pn-cm-budget-bulk-action-label"><?php esc_html_e( 'Optional', 'pn-customers-manager' ); ?></span>
						<label class="pn-cm-budget-toggle"><input type="checkbox" class="pn-cm-budget-bulk-optional-toggle"><span class="pn-cm-budget-toggle-slider"></span></label>
					</span>
					<span class="pn-cm-budget-bulk-action-switch">
						<span class="pn-cm-budget-bulk-action-label"><?php esc_html_e( 'Recurring', 'pn-customers-manager' ); ?></span>
						<label class="pn-cm-budget-toggle"><input type="checkbox" class="pn-cm-budget-bulk-recurring-toggle"><span class="pn-cm-budget-toggle-slider"></span></label>
					</span>
				</div>
				<?php echo PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_ai_prompt_block( $budget_id ); ?>
			<?php endif; ?>
		</div>

		<!-- Totals -->
		<?php
		// Calculate recurring totals by period.
		$recurring_totals = array();
		$recurring_labels_map = array(
			'daily'   => __( 'Daily', 'pn-customers-manager' ),
			'weekly'  => __( 'Weekly', 'pn-customers-manager' ),
			'monthly' => __( 'Monthly', 'pn-customers-manager' ),
			'yearly'  => __( 'Yearly', 'pn-customers-manager' ),
		);
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $g ) {
				foreach ( $g['items'] as $ri ) {
					if ( in_array( $ri['item_type'], array( 'phase', 'image', 'image_compare' ), true ) ) continue;
					if ( empty( $ri['is_selected'] ) ) continue;
					if ( empty( $ri['is_recurring'] ) || empty( $ri['recurring_period'] ) ) continue;
					$p = $ri['recurring_period'];
					if ( ! isset( $recurring_totals[ $p ] ) ) $recurring_totals[ $p ] = 0;
					$recurring_totals[ $p ] += floatval( $ri['quantity'] ) * floatval( $ri['unit_price'] );
				}
			}
		}
		?>
		<div class="pn-cm-budget-totals">
			<div class="pn-cm-budget-totals-row">
				<span><?php esc_html_e( 'Subtotal', 'pn-customers-manager' ); ?></span>
				<span id="pn-cm-budget-subtotal"><?php echo esc_html( $format_currency( $budget_subtotal ) ); ?></span>
			</div>
			<?php foreach ( $recurring_totals as $period => $rec_total ) : ?>
				<div class="pn-cm-budget-totals-row pn-cm-budget-totals-recurring" data-recurring-period="<?php echo esc_attr( $period ); ?>">
					<span><?php
						echo esc_html( sprintf(
							/* translators: %s: period label (Daily, Monthly, etc.) */
							__( 'Recurring %s', 'pn-customers-manager' ),
							isset( $recurring_labels_map[ $period ] ) ? $recurring_labels_map[ $period ] : $period
						) );
					?></span>
					<span><?php echo esc_html( $format_currency( $rec_total ) ); ?></span>
				</div>
			<?php endforeach; ?>
			<?php if ( $budget_discount_rate > 0 ) : ?>
				<div class="pn-cm-budget-totals-row pn-cm-budget-totals-discount">
					<span><?php echo esc_html( sprintf( __( 'Discount (%s%%)', 'pn-customers-manager' ), number_format( $budget_discount_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-budget-discount">-<?php echo esc_html( $format_currency( $budget_discount_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $budget_tax_rate > 0 ) : ?>
				<div class="pn-cm-budget-totals-row">
					<span><?php echo esc_html( sprintf( __( 'Tax (%s%%)', 'pn-customers-manager' ), number_format( $budget_tax_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-budget-tax"><?php echo esc_html( $format_currency( $budget_tax_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<div class="pn-cm-budget-totals-row pn-cm-budget-totals-total">
				<span><?php esc_html_e( 'Total', 'pn-customers-manager' ); ?></span>
				<span id="pn-cm-budget-total"><?php echo esc_html( $format_currency( $budget_total ) ); ?></span>
			</div>
		</div>

		<!-- Footer image -->
		<?php
		$footer_image_id = get_post_meta( $budget_id, 'pn_cm_budget_footer_image', true );
		if ( ! empty( $footer_image_id ) && is_numeric( $footer_image_id ) ) :
			$footer_image_url = wp_get_attachment_url( intval( $footer_image_id ) );
			if ( $footer_image_url ) :
				?>
				<div class="pn-cm-budget-footer-image">
					<img src="<?php echo esc_url( $footer_image_url ); ?>" alt="<?php esc_attr_e( 'Budget image', 'pn-customers-manager' ); ?>" />
				</div>
				<?php
			endif;
		endif;
		?>

		<!-- Actions (only when status = sent) -->
		<?php if ( 'sent' === $budget_status ) : ?>
			<div class="pn-cm-budget-signature-panel" id="pn-cm-budget-signature-panel">
				<h3><?php esc_html_e( 'Accept and sign', 'pn-customers-manager' ); ?></h3>
				<div class="pn-cm-budget-signature-fields">
					<div class="pn-cm-budget-signature-field">
						<label for="pn-cm-budget-acceptor-name"><?php esc_html_e( 'Full name', 'pn-customers-manager' ); ?></label>
						<input type="text" id="pn-cm-budget-acceptor-name" class="pn-cm-budget-email-input" placeholder="<?php esc_attr_e( 'Your full name', 'pn-customers-manager' ); ?>" required />
					</div>
					<div class="pn-cm-budget-signature-field">
						<label for="pn-cm-budget-acceptor-email"><?php esc_html_e( 'Email address', 'pn-customers-manager' ); ?></label>
						<input type="email" id="pn-cm-budget-acceptor-email" class="pn-cm-budget-email-input" placeholder="email@example.com" required />
					</div>
				</div>
				<div class="pn-cm-budget-signature-canvas-wrapper">
					<label><?php esc_html_e( 'Signature', 'pn-customers-manager' ); ?></label>
					<canvas id="pn-cm-budget-signature-canvas" width="560" height="200"></canvas>
					<button type="button" id="pn-cm-budget-signature-clear" class="pn-cm-budget-btn pn-cm-budget-btn-clear">
						<i class="material-icons-outlined">refresh</i>
						<?php esc_html_e( 'Clear signature', 'pn-customers-manager' ); ?>
					</button>
				</div>
				<div class="pn-cm-budget-actions">
					<button type="button" id="pn-cm-budget-accept" class="pn-cm-budget-btn pn-cm-budget-btn-accept">
						<i class="material-icons-outlined">check</i>
						<?php esc_html_e( 'Accept and sign', 'pn-customers-manager' ); ?>
					</button>
				</div>
			</div>
		<?php elseif ( 'accepted' === $budget_status ) : ?>
			<?php
			$accepted_at            = get_post_meta( $budget_id, 'pn_cm_budget_accepted_at', true );
			$signature_id           = get_post_meta( $budget_id, 'pn_cm_budget_signature_attachment_id', true );
			$accepted_by_name       = get_post_meta( $budget_id, 'pn_cm_budget_accepted_by_name', true );
			$accepted_by_email      = get_post_meta( $budget_id, 'pn_cm_budget_accepted_by_email', true );
			$signature_url          = ! empty( $signature_id ) ? wp_get_attachment_url( intval( $signature_id ) ) : '';
			?>
			<div class="pn-cm-budget-signature-accepted">
				<?php if ( $signature_url ) : ?>
					<div class="pn-cm-budget-signature-image">
						<img src="<?php echo esc_url( $signature_url ); ?>" alt="<?php esc_attr_e( 'Signature', 'pn-customers-manager' ); ?>" />
					</div>
				<?php endif; ?>
				<div class="pn-cm-budget-signature-info">
					<?php if ( ! empty( $accepted_by_name ) ) : ?>
						<span class="pn-cm-budget-signature-name"><?php echo esc_html( $accepted_by_name ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $accepted_by_email ) ) : ?>
						<span class="pn-cm-budget-signature-email"><?php echo esc_html( $accepted_by_email ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<div class="pn-cm-budget-status-message pn-cm-budget-status-accepted-msg">
				<i class="material-icons-outlined">check</i>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: date and time when the budget was accepted */
						__( 'This budget was accepted on %s.', 'pn-customers-manager' ),
						$accepted_at ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $accepted_at ) ) : ''
					)
				);
				?>
			</div>
		<?php elseif ( 'rejected' === $budget_status ) : ?>
			<div class="pn-cm-budget-status-message pn-cm-budget-status-rejected-msg">
				<i class="material-icons-outlined">cancel</i>
				<?php
				$rejected_at = get_post_meta( $budget_id, 'pn_cm_budget_rejected_at', true );
				echo esc_html(
					sprintf(
						/* translators: %s: date and time when the budget was rejected */
						__( 'This budget was rejected on %s.', 'pn-customers-manager' ),
						$rejected_at ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $rejected_at ) ) : ''
					)
				);
				?>
			</div>
		<?php endif; ?>


		<!-- Terms -->
		<?php if ( ! empty( $terms ) || $is_admin ) : ?>
			<div class="pn-cm-budget-terms" id="pn-cm-budget-terms-block">
				<h3>
					<?php esc_html_e( 'Terms and conditions', 'pn-customers-manager' ); ?>
					<?php if ( $is_admin ) : ?>
						<a href="#" class="pn-cm-budget-field-edit pn-customers-manager-tooltip" data-field="pn_cm_budget_conditions" data-label="<?php esc_attr_e( 'Terms and conditions', 'pn-customers-manager' ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>">
							<i class="material-icons-outlined">edit</i>
						</a>
					<?php endif; ?>
				</h3>
				<?php if ( ! empty( $terms ) ) : ?>
					<div class="pn-cm-budget-terms-content pn-cm-budget-field-content"><?php echo wp_kses_post( $terms ); ?></div>
				<?php else : ?>
					<div class="pn-cm-budget-terms-content pn-cm-budget-field-content pn-cm-budget-field-empty"><em><?php esc_html_e( 'No terms configured.', 'pn-customers-manager' ); ?></em></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Additional information -->
		<?php if ( ! empty( $others ) || $is_admin ) : ?>
			<div class="pn-cm-budget-others" id="pn-cm-budget-others-block">
				<h3>
					<?php esc_html_e( 'Additional information', 'pn-customers-manager' ); ?>
					<?php if ( $is_admin ) : ?>
						<a href="#" class="pn-cm-budget-field-edit pn-customers-manager-tooltip" data-field="pn_cm_budget_others" data-label="<?php esc_attr_e( 'Additional information', 'pn-customers-manager' ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>">
							<i class="material-icons-outlined">edit</i>
						</a>
					<?php endif; ?>
				</h3>
				<?php if ( ! empty( $others ) ) : ?>
					<div class="pn-cm-budget-others-content pn-cm-budget-field-content"><?php echo wp_kses_post( $others ); ?></div>
				<?php else : ?>
					<div class="pn-cm-budget-others-content pn-cm-budget-field-content pn-cm-budget-field-empty"><em><?php esc_html_e( 'No additional information.', 'pn-customers-manager' ); ?></em></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Print button -->
		<div class="pn-cm-budget-print-btn-wrapper">
			<button type="button" id="pn-cm-budget-print" class="pn-cm-budget-btn pn-cm-budget-btn-print">
				<i class="material-icons-outlined">picture_as_pdf</i>
				<?php esc_html_e( 'Generate PDF', 'pn-customers-manager' ); ?>
			</button>
		</div>
	</div>

	<!-- Sticky totals bar -->
	<div class="pn-cm-budget-sticky-totals" id="pn-cm-budget-sticky-totals">
		<div class="pn-cm-budget-sticky-inner">
			<?php if ( ! empty( $recurring_totals ) ) : ?>
				<div class="pn-cm-budget-sticky-recurring" id="pn-cm-budget-sticky-recurring">
					<?php foreach ( $recurring_totals as $period => $rec_total ) : ?>
						<div class="pn-cm-budget-sticky-item pn-cm-budget-sticky-item-recurring" data-recurring-period="<?php echo esc_attr( $period ); ?>">
							<span class="pn-cm-budget-sticky-label"><?php
								echo esc_html( sprintf(
									__( 'Recurring %s', 'pn-customers-manager' ),
									isset( $recurring_labels_map[ $period ] ) ? $recurring_labels_map[ $period ] : $period
								) );
							?></span>
							<span class="pn-cm-budget-sticky-value"><?php echo esc_html( $format_currency( $rec_total ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="pn-cm-budget-sticky-item pn-cm-budget-sticky-item-total">
				<span class="pn-cm-budget-sticky-label"><?php esc_html_e( 'Total', 'pn-customers-manager' ); ?></span>
				<span class="pn-cm-budget-sticky-value" id="pn-cm-budget-sticky-total"><?php echo esc_html( $format_currency( $budget_total ) ); ?></span>
			</div>
		</div>
	</div>

	<?php wp_footer(); ?>
</body>
</html>
