<?php
/**
 * Poems archive template — block-theme rendering path.
 *
 * Routed here instead of archive-poems.php when the active theme is a block
 * theme (see Templates::filter_template_include). This route isn't a real
 * archive/singular query — it's the virtual /u/{user-id}/poems/ page driven
 * entirely by Rewrites — so it can't hook core's block-template fallback the
 * way single-md_poem/single-md_poet do. Instead it renders the page shell
 * itself: the theme's real header/footer template parts via
 * block_header_area()/block_footer_area(), with the body rendered as real
 * block markup (do_blocks()) rather than hand-written HTML, so it inherits
 * the theme's global styles the same way a native block template would.
 *
 * @package MDPoetry
 */

use MDPoetry\Rewrites;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = (int) get_query_var( Rewrites::QV_USER_ID );
if ( $user_id <= 0 ) {
	// The shortcut /poems/ should have redirected before we got here.
	// Defensive fallback in case it didn't.
	wp_safe_redirect( home_url( '/poems/' ) );
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
		'<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} --><main class="wp-block-group"><!-- wp:mdpoetry/poems-listing {"userId":%d} /--></main><!-- /wp:group -->',
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
