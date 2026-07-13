<?php
/**
 * Server-render for mdpoetry/poet-tag-summary.
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

$poet_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $poet_id ) {
	return;
}

$author_id = (int) get_post_field( 'post_author', $poet_id );

$poems = get_posts(
	array(
		'post_type'      => PostTypes::POST_TYPE_POEM,
		'posts_per_page' => -1,
		'author'         => $author_id,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
foreach ( $poems as $poem_id ) {
	$terms = get_the_terms( $poem_id, PostTypes::TAXONOMY_POEM_TAGS );
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
		++$tag_counts[ $term->term_id ]['count'];
	}
}

if ( empty( $tag_counts ) ) {
	return;
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
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'mdp-poet__tags' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
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
