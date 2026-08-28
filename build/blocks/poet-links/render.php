<?php
/**
 * Server-render for mdpoetry/poet-links.
 *
 * @package MDPoetry
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

use MDPoetry\Poets\PoetMetaBoxes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$poet_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $poet_id ) {
	return;
}

$links = get_post_meta( $poet_id, PoetMetaBoxes::META_LINKS, true );
$links = is_array( $links ) ? $links : array();
if ( empty( $links ) ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'mdp-poet__links' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
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
