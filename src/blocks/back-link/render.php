<?php
/**
 * Server-render for mdpoetry/back-link.
 *
 * @package MDPoetry
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$index_type = isset( $attributes['indexType'] ) && in_array( $attributes['indexType'], array( 'poems', 'poets' ), true )
	? $attributes['indexType']
	: 'poems';

$user_id = isset( $attributes['userId'] ) ? (int) $attributes['userId'] : 0;
if ( ! $user_id ) {
	$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
	$user_id = $post_id ? (int) get_post_field( 'post_author', $post_id ) : 0;
}

if ( ! $user_id ) {
	return;
}

$label = ( 'poets' === $index_type )
	? __( '← All poets', 'mdpoetry-plugin' )
	: __( '← All poems', 'mdpoetry-plugin' );
?>
<p <?php echo get_block_wrapper_attributes( array( 'class' => 'mdp-back-link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<a href="<?php echo esc_url( home_url( sprintf( '/u/%d/%s/', $user_id, $index_type ) ) ); ?>">
		<?php echo esc_html( $label ); ?>
	</a>
</p>
