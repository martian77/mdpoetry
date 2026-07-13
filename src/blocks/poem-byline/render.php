<?php
/**
 * Server-render for mdpoetry/poem-byline.
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

$poem_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $poem_id ) {
	return;
}

$poet_id = (int) get_post_meta( $poem_id, PoemMetaBoxes::META_POET_ID, true );
$poet    = $poet_id ? get_post( $poet_id ) : null;

if ( ! $poet || PostTypes::POST_TYPE_POET !== $poet->post_type ) {
	return;
}
?>
<p <?php echo get_block_wrapper_attributes( array( 'class' => 'mdp-poem__byline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php esc_html_e( 'by', 'mdpoetry-plugin' ); ?>
	<a href="<?php echo esc_url( get_permalink( $poet ) ); ?>">
		<?php echo esc_html( $poet->post_title ); ?>
	</a>
</p>
