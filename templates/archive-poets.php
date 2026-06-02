<?php
/**
 * Poets archive template.
 *
 * Routed here when the request matches /u/{user-id}/poets/ (or the legacy
 * /md_poet/ archive after a redirect). Lists poets owned by the URL's user,
 * alphabetical by name, with a poem count next to each.
 *
 * @package MDPoetry
 */

use MDPoetry\PostTypes;
use MDPoetry\Poems\PoemMetaBoxes;
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

$user = get_user_by( 'ID', $user_id );
if ( ! $user ) {
	// Unknown users are 404'd before template selection (see
	// Rewrites::maybe_404_unknown_user); bail defensively.
	return;
}

// Make sure WordPress treats this as a normal page response.
status_header( 200 );
global $wp_query;
$wp_query->is_404 = false;

$poets = get_posts(
	array(
		'post_type'      => PostTypes::POST_TYPE_POET,
		'author'         => $user_id,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post_status'    => array( 'publish', 'private' ),
	)
);

$is_own_index = ( get_current_user_id() === $user_id );

\MDPoetry\Templates::render_header();
?>

<main id="primary" class="site-main mdp-poets-archive">
	<?php // Constrained-layout group so block themes apply their content width + centering. ?>
	<div class="wp-block-group is-layout-constrained">
	<header class="mdp-poets-archive__header">
		<h1><?php esc_html_e( 'Poets', 'mdpoetry-plugin' ); ?></h1>
		<?php if ( ! $is_own_index ) : ?>
			<p class="mdp-poets-archive__owner">
				<?php
				printf(
					/* translators: %s: display name of the user whose poets are being shown */
					esc_html__( 'Logged by %s', 'mdpoetry-plugin' ),
					esc_html( $user->display_name )
				);
				?>
			</p>
		<?php endif; ?>
		<p class="mdp-poets-archive__nav">
			<a href="<?php echo esc_url( home_url( sprintf( '/u/%d/poems/', $user_id ) ) ); ?>">
				<?php esc_html_e( 'View poems →', 'mdpoetry-plugin' ); ?>
			</a>
		</p>
	</header>

	<?php if ( empty( $poets ) ) : ?>
		<p class="mdp-poets-archive__empty">
			<?php esc_html_e( 'No poets here yet.', 'mdpoetry-plugin' ); ?>
		</p>
	<?php else : ?>
		<ul class="mdp-poets-archive__list">
			<?php foreach ( $poets as $poet ) : ?>
				<?php
				$poem_count = ( new \WP_Query(
					array(
						'post_type'      => PostTypes::POST_TYPE_POEM,
						'author'         => $user_id,
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'no_found_rows'  => false,
						'meta_query'     => array(
							array(
								'key'     => PoemMetaBoxes::META_POET_ID,
								'value'   => $poet->ID,
								'compare' => '=',
							),
						),
					)
				) )->found_posts;
				?>
				<li class="mdp-poets-archive__item">
					<a href="<?php echo esc_url( get_permalink( $poet ) ); ?>">
						<?php echo esc_html( $poet->post_title ); ?>
					</a>
					<span class="mdp-poets-archive__count">
						<?php
						printf(
							/* translators: %d: poem count for this poet */
							esc_html( _n( '%d poem', '%d poems', $poem_count, 'mdpoetry-plugin' ) ),
							(int) $poem_count
						);
						?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	</div>
</main>

<?php
\MDPoetry\Templates::render_footer();
