<?php
/**
 * Server-render for mdpoetry/poets-listing.
 *
 * Full body of the /u/{id}/poets/ virtual page: heading, owner notice, nav
 * link to the poems index, and the alphabetical poets list with poem counts.
 * Ported verbatim from templates/archive-poets.php (the classic-theme
 * equivalent).
 *
 * @package MDPoetry
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

use MDPoetry\PostTypes;
use MDPoetry\Poems\PoemMetaBoxes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = isset( $attributes['userId'] ) ? (int) $attributes['userId'] : 0;
if ( $user_id <= 0 ) {
	return;
}

$user = get_user_by( 'ID', $user_id );
if ( ! $user ) {
	return;
}

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
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'mdp-poets-archive' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
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
				$poem_count = ( new WP_Query(
					array(
						'post_type'      => PostTypes::POST_TYPE_POEM,
						'author'         => $user_id,
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'no_found_rows'  => false,
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
