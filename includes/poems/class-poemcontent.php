<?php
/**
 * MDPoetry: Decorates single-poem content with the plugin's bespoke markup.
 *
 * The single poem page renders through the active theme's own template (so it
 * inherits the theme's chrome and layout). This filter injects the byline,
 * tags, author-only notice, and back-link into `the_content`, guarded to the
 * front-end singular view so it never leaks into feeds, the REST API, the
 * editor, or archive loops.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Poems;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MDPoetry\PostTypes;

/**
 * Front-end content decorator for single `md_poem` views.
 *
 * @category class
 * @since 0.1.0
 * @author Eleanor Martin
 */
class PoemContent {

	/**
	 * Wires up the filter. Priority 30 runs after PoemVisibility (20), so the
	 * body we wrap is already the author-or-public version.
	 */
	public static function setup() {
		add_filter( 'the_content', array( new self(), 'decorate' ), 30 );
	}

	/**
	 * Prepends the byline and appends the footer to a single poem's content.
	 *
	 * @param  string $content Post content (post-visibility-filter).
	 * @return string
	 */
	public function decorate( $content ) {
		if ( ! is_singular( PostTypes::POST_TYPE_POEM ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post = get_post();
		if ( ! $post ) {
			return $content;
		}
		return self::byline( $post ) . $content . self::footer( $post );
	}

	/**
	 * Renders the "by {poet}" line, linked to the poet, if one is set.
	 *
	 * @param  \WP_Post $post The poem.
	 * @return string
	 */
	private static function byline( $post ) {
		$poet_id = (int) get_post_meta( $post->ID, PoemMetaBoxes::META_POET_ID, true );
		$poet    = $poet_id ? get_post( $poet_id ) : null;
		if ( ! $poet || PostTypes::POST_TYPE_POET !== $poet->post_type ) {
			return '';
		}
		return sprintf(
			'<p class="mdp-poem__byline">%s <a href="%s">%s</a></p>',
			esc_html__( 'by', 'mdpoetry-plugin' ),
			esc_url( get_permalink( $poet ) ),
			esc_html( $poet->post_title )
		);
	}

	/**
	 * Renders tags, the author-only notice (only when text is actually
	 * withheld), and the back-link to the poems index.
	 *
	 * @param  \WP_Post $post The poem.
	 * @return string
	 */
	private static function footer( $post ) {
		$html = '';

		$tag_list = get_the_term_list( $post->ID, PostTypes::TAXONOMY_POEM_TAGS, '', ', ', '' );
		if ( $tag_list && ! is_wp_error( $tag_list ) ) {
			$html .= '<p class="mdp-poem__tags">'
				. esc_html__( 'Tags:', 'mdpoetry-plugin' ) . ' '
				. wp_kses_post( $tag_list )
				. '</p>';
		}

		// A single-line poem hides nothing — the public view shows the whole
		// thing — so only show the notice when lines are actually withheld.
		$hidden_lines = max( 0, Poem::count_lines( $post->post_content ) - 1 );
		if ( ! PoemVisibility::viewer_is_author( $post ) && $hidden_lines > 0 ) {
			$html .= '<p class="mdp-poem__notice"><small>'
				. esc_html__( 'Only the author can view the full text of this poem.', 'mdpoetry-plugin' )
				. '</small></p>';
		}

		$author_id = (int) $post->post_author;
		if ( $author_id > 0 ) {
			$html .= sprintf(
				'<p class="mdp-poem__back"><a href="%s">%s</a></p>',
				esc_url( home_url( sprintf( '/u/%d/poems/', $author_id ) ) ),
				esc_html__( '← All poems', 'mdpoetry-plugin' )
			);
		}

		return $html;
	}
}
