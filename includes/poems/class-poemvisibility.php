<?php
/**
 * MDPoetry: Visibility filter for poem content.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Poems;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MDPoetry\PostTypes;

/**
 * Replaces poem body with first-line + source for non-author viewers.
 *
 * Copyright-safe: the full body is only ever returned to the post author.
 * Same code path for single-user and multi-user installs — in a single-user
 * install every poem is yours, so it behaves transparently.
 *
 * @category class
 * @since 0.0.6
 * @author Eleanor Martin
 */
class PoemVisibility {

	/**
	 * Wires up the filter.
	 */
	public static function setup() {
		$self = new self();
		add_filter( 'the_content', array( $self, 'filter_content' ), 20 );
		add_filter( 'posts_where', array( $self, 'filter_search_where' ), 10, 2 );
	}

	/**
	 * Keeps poems you don't own out of front-end search results.
	 *
	 * `md_poem` is a public post type, so by default a non-author searching a
	 * word that only appears in a hidden line would surface the poem — the body
	 * isn't shown, but the match leaks that the content exists, undercutting the
	 * "first line + source only" promise. We exclude `md_poem` rows not authored
	 * by the current viewer (so your own poems stay findable when logged in)
	 * rather than using the blunt `exclude_from_search`, which would hide them
	 * from the author too. Other post types are untouched.
	 *
	 * @param  string    $where The WHERE clause of the query.
	 * @param  \WP_Query $query The query being run.
	 * @return string
	 */
	public function filter_search_where( $where, $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return $where;
		}
		global $wpdb;
		$where .= $wpdb->prepare(
			" AND NOT ( {$wpdb->posts}.post_type = %s AND {$wpdb->posts}.post_author <> %d )",
			PostTypes::POST_TYPE_POEM,
			get_current_user_id()
		);
		return $where;
	}

	/**
	 * Filters `the_content` for `md_poem` posts.
	 *
	 * @param  string $content Original post content (already block-rendered).
	 * @return string
	 */
	public function filter_content( $content ) {
		$post = get_post();
		if ( ! $post || PostTypes::POST_TYPE_POEM !== $post->post_type ) {
			return $content;
		}
		if ( self::viewer_is_author( $post ) ) {
			return $content;
		}
		return self::render_public_view( $post );
	}

	/**
	 * Returns true if the current user authored the given post.
	 *
	 * @param \WP_Post $post The post to check.
	 * @return bool
	 */
	public static function viewer_is_author( $post ) {
		$current = get_current_user_id();
		return $current > 0 && (int) $post->post_author === $current;
	}

	/**
	 * Renders the public (non-author) view: first line + source.
	 *
	 * @param \WP_Post $post The poem post.
	 * @return string HTML.
	 */
	public static function render_public_view( $post ) {
		$excerpt    = Poem::compute_excerpt( $post->post_content );
		$total      = Poem::count_lines( $post->post_content );
		$more_lines = max( 0, $total - 1 );
		$source     = (string) get_post_meta( $post->ID, PoemMetaBoxes::META_SOURCE, true );

		$html  = '<div class="mdp-poem-public">';
		if ( '' !== $excerpt ) {
			$html .= '<p class="mdp-poem-first-line">' . esc_html( $excerpt ) . '…</p>';
			if ( $more_lines > 0 ) {
				$html .= '<p class="mdp-poem-more-lines">'
					. esc_html(
						sprintf(
							/* translators: %d: number of remaining lines hidden from non-authors */
							_n( '(%d more line…)', '(%d more lines…)', $more_lines, 'mdpoetry-plugin' ),
							$more_lines
						)
					)
					. '</p>';
			}
		}
		if ( '' !== $source ) {
			$html .= '<p class="mdp-poem-source"><em>'
				. esc_html__( 'Source:', 'mdpoetry-plugin' )
				. '</em> '
				. self::format_source( $source )
				. '</p>';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * Linkifies a source string if it looks like a URL, otherwise escapes it.
	 *
	 * @param string $source Raw source string.
	 * @return string HTML-safe rendered source.
	 */
	private static function format_source( $source ) {
		if ( filter_var( $source, FILTER_VALIDATE_URL ) ) {
			return '<a href="' . esc_url( $source ) . '" rel="noopener noreferrer">'
				. esc_html( $source )
				. '</a>';
		}
		return esc_html( $source );
	}
}
