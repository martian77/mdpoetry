<?php
/**
 * MDPoetry: Classic PHP meta boxes for the poet editor.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Poets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MDPoetry\PostTypes;

/**
 * Adds a repeatable External Links meta box to the `md_poet` editor screen.
 *
 * @category class
 * @since 0.0.6
 * @author Eleanor Martin
 */
class PoetMetaBoxes {

	public const META_LINKS    = 'external_links';
	private const NONCE_NAME   = 'mdpoetry_poet_meta_nonce';
	private const NONCE_ACTION = 'mdpoetry_save_poet_meta';

	/**
	 * Wires up the meta box hooks.
	 */
	public static function setup() {
		$self = new self();
		add_action( 'add_meta_boxes', array( $self, 'register' ) );
		add_action( 'save_post_' . PostTypes::POST_TYPE_POET, array( $self, 'save' ), 10, 2 );
	}

	/**
	 * Registers the meta box.
	 */
	public function register() {
		add_meta_box(
			'mdpoetry_poet_links',
			__( 'External Links', 'mdpoetry-plugin' ),
			array( $this, 'render' ),
			PostTypes::POST_TYPE_POET,
			'normal',
			'default'
		);
	}

	/**
	 * Renders the meta box.
	 *
	 * @param \WP_Post $post The current post object.
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$links = get_post_meta( $post->ID, self::META_LINKS, true );
		if ( ! is_array( $links ) ) {
			$links = array();
		}
		// Render at least one empty row so the user can start typing immediately.
		if ( empty( $links ) ) {
			$links = array( array( 'label' => '', 'url' => '' ) );
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Add links to external pages about this poet (Wikipedia, Poetry Foundation, personal site, etc.).', 'mdpoetry-plugin' ); ?>
		</p>

		<table class="widefat mdp-poet-links" id="mdp-poet-links">
			<thead>
				<tr>
					<th style="width: 30%;"><?php esc_html_e( 'Label', 'mdpoetry-plugin' ); ?></th>
					<th><?php esc_html_e( 'URL', 'mdpoetry-plugin' ); ?></th>
					<th style="width: 60px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $links as $i => $link ) : ?>
					<?php $this->render_row( (int) $i, (array) $link ); ?>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p>
			<button type="button" class="button" id="mdp-poet-links-add">
				<?php esc_html_e( 'Add link', 'mdpoetry-plugin' ); ?>
			</button>
		</p>

		<template id="mdp-poet-links-row-template">
			<?php $this->render_row( -1, array( 'label' => '', 'url' => '' ) ); ?>
		</template>

		<script>
		( function () {
			var table  = document.getElementById( 'mdp-poet-links' );
			var add    = document.getElementById( 'mdp-poet-links-add' );
			var tmpl   = document.getElementById( 'mdp-poet-links-row-template' );
			if ( ! table || ! add || ! tmpl ) {
				return;
			}
			var tbody = table.querySelector( 'tbody' );

			function nextIndex() {
				var rows = tbody.querySelectorAll( 'tr' );
				var max  = -1;
				rows.forEach( function ( row ) {
					var idx = parseInt( row.getAttribute( 'data-index' ), 10 );
					if ( ! isNaN( idx ) && idx > max ) {
						max = idx;
					}
				} );
				return max + 1;
			}

			add.addEventListener( 'click', function () {
				var idx  = nextIndex();
				var html = tmpl.innerHTML.replace( /__INDEX__/g, String( idx ) );
				var wrap = document.createElement( 'tbody' );
				wrap.innerHTML = html.trim();
				var row = wrap.querySelector( 'tr' );
				if ( row ) {
					tbody.appendChild( row );
				}
			} );

			tbody.addEventListener( 'click', function ( ev ) {
				var btn = ev.target.closest( '.mdp-poet-links-remove' );
				if ( ! btn ) {
					return;
				}
				var row = btn.closest( 'tr' );
				if ( row ) {
					row.parentNode.removeChild( row );
				}
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Renders a single link row.
	 *
	 * Pass `-1` as the index to produce a template row containing the literal
	 * placeholder `__INDEX__` for JS to substitute.
	 *
	 * @param int   $i    Row index (or -1 for template).
	 * @param array $link Row data with `label` and `url` keys.
	 */
	private function render_row( $i, $link ) {
		$index_attr = ( -1 === $i ) ? '__INDEX__' : (string) $i;
		$label      = isset( $link['label'] ) ? (string) $link['label'] : '';
		$url        = isset( $link['url'] ) ? (string) $link['url'] : '';
		?>
		<tr data-index="<?php echo esc_attr( $index_attr ); ?>">
			<td>
				<input type="text"
					name="mdp_external_links[<?php echo esc_attr( $index_attr ); ?>][label]"
					value="<?php echo esc_attr( $label ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. Wikipedia', 'mdpoetry-plugin' ); ?>"
					style="width: 100%;">
			</td>
			<td>
				<input type="url"
					name="mdp_external_links[<?php echo esc_attr( $index_attr ); ?>][url]"
					value="<?php echo esc_attr( $url ); ?>"
					placeholder="https://"
					style="width: 100%;">
			</td>
			<td>
				<button type="button" class="button-link button-link-delete mdp-poet-links-remove">
					<?php esc_html_e( 'Remove', 'mdpoetry-plugin' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves the external links meta.
	 *
	 * @param int      $post_id The post being saved.
	 * @param \WP_Post $post    The post object.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw   = isset( $_POST['mdp_external_links'] ) && is_array( $_POST['mdp_external_links'] )
			? wp_unslash( $_POST['mdp_external_links'] )
			: array();
		$clean = array();
		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$label = isset( $entry['label'] ) ? sanitize_text_field( $entry['label'] ) : '';
			$url   = isset( $entry['url'] ) ? esc_url_raw( $entry['url'] ) : '';
			// Skip rows with no URL — a label without a destination is meaningless.
			if ( '' === $url ) {
				continue;
			}
			$clean[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}

		if ( ! empty( $clean ) ) {
			update_post_meta( $post_id, self::META_LINKS, $clean );
		} else {
			delete_post_meta( $post_id, self::META_LINKS );
		}
	}
}
