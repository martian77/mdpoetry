<?php
/**
 * Poets archive template — block-theme rendering path.
 *
 * See archive-poems-block.php for why this route renders its own shell
 * instead of hooking core's block-template fallback.
 *
 * @package MDPoetry
 */

use MDPoetry\Rewrites;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = (int) get_query_var( Rewrites::QV_USER_ID );
if ( $user_id <= 0 ) {
	// The shortcut /poets/ should have redirected before we got here.
	// Defensive fallback in case it didn't.
	wp_safe_redirect( home_url( '/poets/' ) );
	exit;
}

if ( ! get_user_by( 'ID', $user_id ) ) {
	// Unknown users are 404'd before template selection (see
	// Rewrites::maybe_404_unknown_user); bail defensively.
	return;
}

// Make sure WordPress treats this as a normal page response.
status_header( 200 );
global $wp_query;
$wp_query->is_404 = false;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
<header class="wp-block-template-part">
	<?php block_header_area(); ?>
</header>
<?php
echo do_blocks(
	sprintf(
		'<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} --><main class="wp-block-group"><!-- wp:mdpoetry/poets-listing {"userId":%d} /--></main><!-- /wp:group -->',
		$user_id
	)
);
?>
<footer class="wp-block-template-part">
	<?php block_footer_area(); ?>
</footer>
</div>
<?php
wp_enqueue_stored_styles();
wp_footer();
?>
</body>
</html>
