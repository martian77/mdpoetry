<?php
/**
 * Single Poet template.
 *
 * Shows the poet's bio/photo/links + total poem count + alphabetical list of
 * poems by that poet + per-tag usage counts across this poet's poems.
 *
 * @package MDPoetry
 */

use MDPoetry\PostTypes;
use MDPoetry\Poets\PoetMetaBoxes;
use MDPoetry\Poems\PoemMetaBoxes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main mdp-single-poet">
	<?php
	while ( have_posts() ) :
		the_post();
		$poet_id    = get_the_ID();
		$poet_post  = get_post();
		$author_id  = (int) $poet_post->post_author;
		$links      = get_post_meta( $poet_id, PoetMetaBoxes::META_LINKS, true );
		$links      = is_array( $links ) ? $links : array();

		// Per-user model: poet is owned by exactly one user. List that user's
		// poems linking to this poet.
		$poems = get_posts(
			array(
				'post_type'      => PostTypes::POST_TYPE_POEM,
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'author'         => $author_id,
				'meta_query'     => array(
					array(
						'key'     => PoemMetaBoxes::META_POET_ID,
						'value'   => $poet_id,
						'compare' => '=',
					),
				),
			)
		);

		// Aggregate tag usage counts across this poet's poems.
		$tag_counts = array();
		foreach ( $poems as $poem ) {
			$terms = get_the_terms( $poem->ID, PostTypes::TAXONOMY_POEM_TAGS );
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				if ( ! isset( $tag_counts[ $term->term_id ] ) ) {
					$tag_counts[ $term->term_id ] = array(
						'term'  => $term,
						'count' => 0,
					);
				}
				$tag_counts[ $term->term_id ]['count']++;
			}
		}
		uasort(
			$tag_counts,
			function ( $a, $b ) {
				if ( $a['count'] === $b['count'] ) {
					return strcasecmp( $a['term']->name, $b['term']->name );
				}
				return $b['count'] - $a['count'];
			}
		);
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'mdp-poet' ); ?>>
			<header class="mdp-poet__header">
				<h1 class="mdp-poet__name"><?php the_title(); ?></h1>
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="mdp-poet__photo">
						<?php the_post_thumbnail( 'medium' ); ?>
					</div>
				<?php endif; ?>
			</header>

			<?php if ( get_the_content() ) : ?>
				<div class="mdp-poet__bio">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $links ) ) : ?>
				<section class="mdp-poet__links">
					<h2><?php esc_html_e( 'Links', 'mdpoetry-plugin' ); ?></h2>
					<ul>
						<?php foreach ( $links as $link ) : ?>
							<?php
							$url   = isset( $link['url'] ) ? $link['url'] : '';
							$label = isset( $link['label'] ) && '' !== $link['label']
								? $link['label']
								: $url;
							if ( '' === $url ) {
								continue;
							}
							?>
							<li>
								<a href="<?php echo esc_url( $url ); ?>" rel="noopener noreferrer">
									<?php echo esc_html( $label ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<section class="mdp-poet__poems">
				<h2>
					<?php
					printf(
						/* translators: %d: number of poems by this poet */
						esc_html( _n( '%d poem', '%d poems', count( $poems ), 'mdpoetry-plugin' ) ),
						(int) count( $poems )
					);
					?>
				</h2>
				<?php if ( ! empty( $poems ) ) : ?>
					<ul class="mdp-poet__poem-list">
						<?php foreach ( $poems as $poem ) : ?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $poem ) ); ?>">
									<?php echo esc_html( $poem->post_title ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>

			<?php if ( ! empty( $tag_counts ) ) : ?>
				<section class="mdp-poet__tags">
					<h2><?php esc_html_e( 'Tags', 'mdpoetry-plugin' ); ?></h2>
					<ul class="mdp-poet__tag-list">
						<?php foreach ( $tag_counts as $entry ) : ?>
							<?php
							$term_link = get_term_link( $entry['term'] );
							if ( is_wp_error( $term_link ) ) {
								continue;
							}
							?>
							<li>
								<a href="<?php echo esc_url( $term_link ); ?>">
									<?php echo esc_html( $entry['term']->name ); ?>
								</a>
								<span class="mdp-poet__tag-count">(<?php echo (int) $entry['count']; ?>)</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</article>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
