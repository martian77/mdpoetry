<?php
/**
 * MDPoetry: Routes single-CPT templates to plugin-provided defaults.
 *
 * @package MDPoetry
 */

namespace MDPoetry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single-template loader for `md_poem` and `md_poet`.
 *
 * Themes can still override by providing `single-md_poem.php` or
 * `single-md_poet.php` in the theme (or under `md-poetry/` in the theme).
 *
 * @category class
 * @since 0.0.6
 * @author Eleanor Martin
 */
class Templates {

	/**
	 * Wires up the template filter.
	 */
	public static function setup() {
		add_filter( 'single_template', array( self::class, 'filter_single_template' ) );
	}

	/**
	 * Routes single-CPT requests to plugin-provided templates if no theme
	 * override is present.
	 *
	 * @param  string $template Path chosen by core.
	 * @return string
	 */
	public static function filter_single_template( $template ) {
		$post = get_post();
		if ( ! $post ) {
			return $template;
		}

		$type = $post->post_type;
		if ( PostTypes::POST_TYPE_POEM !== $type && PostTypes::POST_TYPE_POET !== $type ) {
			return $template;
		}

		$filename = 'single-' . $type . '.php';

		// Check theme overrides first.
		$theme_template = locate_template(
			array(
				trailingslashit( MDP_TEMPLATE_PATH ) . $filename,
				$filename,
			)
		);
		if ( $theme_template ) {
			return $theme_template;
		}

		// Fall back to the plugin's own template.
		$plugin_template = MDP_ABSPATH . 'templates/' . $filename;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}
}
