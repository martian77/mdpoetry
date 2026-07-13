<?php
/**
 * Server-render for mdpoetry/poet-bibliography.
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

// Per-user model: poet is owned by exactly one user. List that user's poems
// linking to this poet.
$poems = get_posts(
	array(
		'post_type'      => PostTypes::POST_TYPE_POEM,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'author'         => $author_id,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => PoemMetaBoxes::META_POET_ID,
				'value'   => $poet_id,
				'compare' => '=',
			),
		),
	)
);
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'mdp-poet__poems' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
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
