<?php
/**
 * Single Poem template.
 *
 * Title, byline, and body. The body itself goes through the visibility filter
 * (see PoemVisibility), so non-authors automatically see first-line + source.
 *
 * @package MDPoetry
 */

use MDPoetry\PostTypes;
use MDPoetry\Poems\Poem;
use MDPoetry\Poems\PoemMetaBoxes;
use MDPoetry\Poems\PoemVisibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main mdp-single-poem">
	<?php // Constrained-layout group so block themes apply their content width + centering. ?>
	<div class="wp-block-group is-layout-constrained">
	<?php
	while ( have_posts() ) :
		the_post();
		$poem_id        = get_the_ID();
		$poet_id        = (int) get_post_meta( $poem_id, PoemMetaBoxes::META_POET_ID, true );
		$poet           = $poet_id ? get_post( $poet_id ) : null;
		$viewer_is_author = PoemVisibility::viewer_is_author( get_post() );
		// Lines hidden from non-authors. A single-line poem hides nothing —
		// the public view already shows the whole thing — so no notice below.
		$hidden_lines = max( 0, Poem::count_lines( get_post_field( 'post_content', $poem_id ) ) - 1 );
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'mdp-poem' ); ?>>
			<header class="mdp-poem__header">
				<h1 class="mdp-poem__title"><?php the_title(); ?></h1>
				<?php if ( $poet && PostTypes::POST_TYPE_POET === $poet->post_type ) : ?>
					<p class="mdp-poem__byline">
						<?php esc_html_e( 'by', 'mdpoetry-plugin' ); ?>
						<a href="<?php echo esc_url( get_permalink( $poet ) ); ?>">
							<?php echo esc_html( $poet->post_title ); ?>
						</a>
					</p>
				<?php endif; ?>
			</header>

			<div class="mdp-poem__content">
				<?php the_content(); ?>
			</div>

			<footer class="mdp-poem__footer">
				<?php
				$tag_list = get_the_term_list( $poem_id, PostTypes::TAXONOMY_POEM_TAGS, '', ', ', '' );
				if ( $tag_list && ! is_wp_error( $tag_list ) ) :
					?>
					<p class="mdp-poem__tags">
						<?php esc_html_e( 'Tags:', 'mdpoetry-plugin' ); ?>
						<?php echo wp_kses_post( $tag_list ); ?>
					</p>
				<?php endif; ?>

				<?php if ( ! $viewer_is_author && $hidden_lines > 0 ) : ?>
					<p class="mdp-poem__notice">
						<small>
							<?php esc_html_e( 'Only the author can view the full text of this poem.', 'mdpoetry-plugin' ); ?>
						</small>
					</p>
				<?php endif; ?>

				<?php $author_id = (int) get_post_field( 'post_author', $poem_id ); ?>
				<?php if ( $author_id > 0 ) : ?>
					<p class="mdp-poem__back">
						<a href="<?php echo esc_url( home_url( sprintf( '/u/%d/poems/', $author_id ) ) ); ?>">
							<?php esc_html_e( '← All poems', 'mdpoetry-plugin' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</footer>
		</article>
		<?php
	endwhile;
	?>
	</div>
</main>

<?php
get_footer();
