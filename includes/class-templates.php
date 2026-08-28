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
	 *
	 * Note: single poem/poet pages are deliberately NOT given a plugin
	 * template. They render through the active theme's own single template so
	 * they inherit its chrome and layout (critical for block themes); the
	 * plugin's bespoke markup is injected into `the_content` instead — see
	 * PoemContent / PoetContent.
	 */
	public static function setup() {
		add_filter( 'template_include', array( self::class, 'filter_template_include' ) );
	}

	/**
	 * Routes custom routes (the namespaced poets and poems archives) to their
	 * template.
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
			$archive = get_query_var( Rewrites::QV_ARCHIVE );
			$file    = null;
			if ( Rewrites::ARCHIVE_POETS === $archive ) {
				$file = 'archive-poets.php';
			} elseif ( Rewrites::ARCHIVE_POEMS === $archive ) {
				$file = 'archive-poems.php';
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
