<?php
/**
 * MDPoetry: Routes single-CPT and custom-archive templates to plugin defaults.
 *
 * @package MDPoetry
 */

namespace MDPoetry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template loader for `md_poem`, `md_poet`, and the namespaced poets archive.
 *
 * Themes can still override by providing the same filename in the theme root
 * or under the `md-poetry/` subfolder of the theme.
 *
 * @category class
 * @since 0.0.6
 * @author Eleanor Martin
 */
class Templates {

	/**
	 * Wires up the template filters.
	 */
	public static function setup() {
		add_filter( 'single_template', array( self::class, 'filter_single_template' ) );
		add_filter( 'template_include', array( self::class, 'filter_template_include' ) );
		// Called directly (not via add_action('init', ...)): Main::init() already
		// runs inside WP's own `init` firing, and a callback added to a hook
		// while that same priority is being iterated doesn't run this pass.
		self::register_block_templates();
	}

	/**
	 * Registers default block templates for `single-md_poem`/`single-md_poet`
	 * on block themes.
	 *
	 * This hooks the same fallback resolution core already runs for block
	 * themes (see `locate_block_template()`), so a theme-provided override in
	 * the Site Editor still wins over these defaults.
	 */
	public static function register_block_templates() {
		if ( ! wp_is_block_theme() ) {
			return;
		}

		register_block_template(
			MDP_PLUGIN_SHORTNAME . '//single-' . PostTypes::POST_TYPE_POEM,
			array(
				'title'       => __( 'Single Poem', 'mdpoetry-plugin' ),
				'description' => __( 'Displays a single poem, with the visibility filter applied to its body.', 'mdpoetry-plugin' ),
				'content'     => (string) file_get_contents( MDP_ABSPATH . 'templates/block-templates/single-md_poem.html' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
				'post_types'  => array( PostTypes::POST_TYPE_POEM ),
			)
		);

		register_block_template(
			MDP_PLUGIN_SHORTNAME . '//single-' . PostTypes::POST_TYPE_POET,
			array(
				'title'       => __( 'Single Poet', 'mdpoetry-plugin' ),
				'description' => __( "Displays a poet's bio, photo, links, and bibliography.", 'mdpoetry-plugin' ),
				'content'     => (string) file_get_contents( MDP_ABSPATH . 'templates/block-templates/single-md_poet.html' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
				'post_types'  => array( PostTypes::POST_TYPE_POET ),
			)
		);
	}

	/**
	 * Routes single-CPT requests to plugin-provided templates if no theme
	 * override is present.
	 *
	 * Block themes are left alone: `register_block_templates()` above wires
	 * the plugin defaults into core's own block-template fallback resolution,
	 * which runs later on this same filter and needs `$template` to still be
	 * empty to do its job.
	 *
	 * @param  string $template Path chosen by core.
	 * @return string
	 */
	public static function filter_single_template( $template ) {
		if ( wp_is_block_theme() ) {
			return $template;
		}

		$post = get_post();
		if ( ! $post ) {
			return $template;
		}

		$type = $post->post_type;
		if ( PostTypes::POST_TYPE_POEM !== $type && PostTypes::POST_TYPE_POET !== $type ) {
			return $template;
		}

		$located = self::locate( 'single-' . $type . '.php' );
		return $located ? $located : $template;
	}

	/**
	 * Routes custom routes (the namespaced poets and poems archives) to their
	 * template.
	 *
	 * These routes aren't real archive/singular queries (see `Rewrites`), so
	 * core's block-template fallback never sees them — on a block theme this
	 * routes to the block-rendering sibling templates instead of the classic
	 * ones.
	 *
	 * @param  string $template Template path chosen by core.
	 * @return string
	 */
	public static function filter_template_include( $template ) {
		// An unknown user-id is flagged 404 upstream (Rewrites::maybe_404_unknown_user).
		// Leave core's resolved 404 template in place rather than forcing our archive.
		if ( is_404() ) {
			return $template;
		}
		if ( (int) get_query_var( Rewrites::QV_USER_ID ) > 0 ) {
			$archive     = get_query_var( Rewrites::QV_ARCHIVE );
			$block_theme = wp_is_block_theme();
			$file        = null;
			if ( Rewrites::ARCHIVE_POETS === $archive ) {
				$file = $block_theme ? 'archive-poets-block.php' : 'archive-poets.php';
			} elseif ( Rewrites::ARCHIVE_POEMS === $archive ) {
				$file = $block_theme ? 'archive-poems-block.php' : 'archive-poems.php';
			}
			if ( $file ) {
				$located = self::locate( $file );
				if ( $located ) {
					return $located;
				}
			}
		}
		return $template;
	}

	/**
	 * Locates a template, preferring a theme override over the plugin default.
	 *
	 * @param  string $filename Bare template filename.
	 * @return string|null      Absolute path, or null if not found.
	 */
	private static function locate( $filename ) {
		$theme_template = locate_template(
			array(
				trailingslashit( MDP_TEMPLATE_PATH ) . $filename,
				$filename,
			)
		);
		if ( $theme_template ) {
			return $theme_template;
		}

		$plugin_template = MDP_ABSPATH . 'templates/' . $filename;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return null;
	}
}
