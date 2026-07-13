<?php
/**
 * Server-render for mdpoetry/poem-visibility-notice.
 *
 * @package MDPoetry
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

use MDPoetry\Poems\PoemVisibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$poem_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$post    = $poem_id ? get_post( $poem_id ) : null;

if ( ! $post || PoemVisibility::viewer_is_author( $post ) ) {
	return;
}
?>
<p <?php echo get_block_wrapper_attributes( array( 'class' => 'mdp-poem__notice' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<small><?php esc_html_e( 'Only the author can view the full text of this poem.', 'mdpoetry-plugin' ); ?></small>
</p>
