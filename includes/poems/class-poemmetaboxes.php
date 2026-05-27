<?php
/**
 * MDPoetry: Classic PHP meta boxes for the poem editor.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Poems;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MDPoetry\PostTypes;

/**
 * Adds Poet and Source meta boxes to the `md_poem` editor screen.
 *
 * @category class
 * @since 0.0.6
 * @author Eleanor Martin
 */
class PoemMetaBoxes {

	public const META_POET_ID  = 'poet_id';
	public const META_SOURCE   = 'source';
	private const NONCE_NAME   = 'mdpoetry_poem_meta_nonce';
	private const NONCE_ACTION = 'mdpoetry_save_poem_meta';

	/**
	 * Wires up the meta box hooks.
	 */
	public static function setup() {
		$self = new self();
		add_action( 'add_meta_boxes', array( $self, 'register' ) );
		add_action( 'save_post_' . PostTypes::POST_TYPE_POEM, array( $self, 'save' ), 10, 2 );
	}

	/**
	 * Registers the meta box.
	 */
	public function register() {
		add_meta_box(
			'mdpoetry_poem_details',
			__( 'Poem Details', 'mdpoetry-plugin' ),
			array( $this, 'render' ),
			PostTypes::POST_TYPE_POEM,
			'side',
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

		$poet_id = (int) get_post_meta( $post->ID, self::META_POET_ID, true );
		$source  = (string) get_post_meta( $post->ID, self::META_SOURCE, true );

		// Per-user model: only show this user's poets in the dropdown.
		$poets = get_posts(
			array(
				'post_type'      => PostTypes::POST_TYPE_POET,
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'author'         => get_current_user_id(),
				'post_status'    => array( 'publish', 'draft', 'private', 'future', 'pending' ),
			)
		);
		?>
		<p>
			<label for="mdp_poet_id"><strong><?php esc_html_e( 'Poet', 'mdpoetry-plugin' ); ?></strong></label><br>
			<select name="mdp_poet_id" id="mdp_poet_id" style="width: 100%;">
				<option value="0"><?php esc_html_e( '— None —', 'mdpoetry-plugin' ); ?></option>
				<?php foreach ( $poets as $poet ) : ?>
					<option value="<?php echo esc_attr( (string) $poet->ID ); ?>" <?php selected( $poet_id, $poet->ID ); ?>>
						<?php echo esc_html( $poet->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( empty( $poets ) ) : ?>
				<span class="description">
					<?php
					printf(
						/* translators: %s: link to add a new poet */
						wp_kses(
							__( 'No poets yet. <a href="%s">Add a poet</a> to link this poem to one.', 'mdpoetry-plugin' ),
							array( 'a' => array( 'href' => array() ) )
						),
						esc_url( admin_url( 'post-new.php?post_type=' . PostTypes::POST_TYPE_POET ) )
					);
					?>
				</span>
			<?php endif; ?>
		</p>
		<p>
			<label for="mdp_source"><strong><?php esc_html_e( 'Source', 'mdpoetry-plugin' ); ?></strong></label><br>
			<input type="text" name="mdp_source" id="mdp_source" value="<?php echo esc_attr( $source ); ?>" style="width: 100%;">
			<span class="description"><?php esc_html_e( 'URL, book reference, or other source.', 'mdpoetry-plugin' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Saves the meta box fields.
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

		$poet_id = isset( $_POST['mdp_poet_id'] ) ? absint( wp_unslash( $_POST['mdp_poet_id'] ) ) : 0;
		if ( $poet_id ) {
			update_post_meta( $post_id, self::META_POET_ID, $poet_id );
		} else {
			delete_post_meta( $post_id, self::META_POET_ID );
		}

		$source = isset( $_POST['mdp_source'] )
			? sanitize_text_field( wp_unslash( $_POST['mdp_source'] ) )
			: '';
		if ( '' !== $source ) {
			update_post_meta( $post_id, self::META_SOURCE, $source );
		} else {
			delete_post_meta( $post_id, self::META_SOURCE );
		}
	}
}
