<?php
/**
 * Server-render for mdpoetry/poems-listing.
 *
 * Full body of the /u/{id}/poems/ virtual page: heading, owner notice, nav
 * link to the poets index, and the alphabetical poems list. Ported verbatim
 * from templates/archive-poems.php (the classic-theme equivalent).
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

$poems = get_posts(
	array(
		'post_type'      => PostTypes::POST_TYPE_POEM,
		'author'         => $user_id,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post_status'    => array( 'publish', 'private' ),
	)
);

$is_own_index = ( get_current_user_id() === $user_id );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'mdp-poems-archive' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<header class="mdp-poems-archive__header">
		<h1><?php esc_html_e( 'Poems', 'mdpoetry-plugin' ); ?></h1>
		<?php if ( ! $is_own_index ) : ?>
			<p class="mdp-poems-archive__owner">
				<?php
				printf(
					/* translators: %s: display name of the user whose poems are being shown */
					esc_html__( 'Logged by %s', 'mdpoetry-plugin' ),
					esc_html( $user->display_name )
				);
				?>
			</p>
		<?php endif; ?>
		<p class="mdp-poems-archive__nav">
			<a href="<?php echo esc_url( home_url( sprintf( '/u/%d/poets/', $user_id ) ) ); ?>">
				<?php esc_html_e( 'View poets →', 'mdpoetry-plugin' ); ?>
			</a>
		</p>
	</header>

	<?php if ( empty( $poems ) ) : ?>
		<p class="mdp-poems-archive__empty">
			<?php esc_html_e( 'No poems here yet.', 'mdpoetry-plugin' ); ?>
		</p>
	<?php else : ?>
		<ul class="mdp-poems-archive__list">
			<?php foreach ( $poems as $poem ) : ?>
				<?php
				$poet_id  = (int) get_post_meta( $poem->ID, PoemMetaBoxes::META_POET_ID, true );
				$poet     = $poet_id ? get_post( $poet_id ) : null;
				$has_poet = ( $poet && PostTypes::POST_TYPE_POET === $poet->post_type );
				?>
				<li class="mdp-poems-archive__item">
					<a class="mdp-poems-archive__title" href="<?php echo esc_url( get_permalink( $poem ) ); ?>">
						<?php echo esc_html( $poem->post_title ); ?>
					</a>
					<?php if ( $has_poet ) : ?>
						<span class="mdp-poems-archive__poet">
							<?php esc_html_e( 'by', 'mdpoetry-plugin' ); ?>
							<a href="<?php echo esc_url( get_permalink( $poet ) ); ?>">
								<?php echo esc_html( $poet->post_title ); ?>
							</a>
						</span>
					<?php endif; ?>
					<span class="mdp-poems-archive__date">
						<?php echo esc_html( get_the_date( '', $poem ) ); ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
