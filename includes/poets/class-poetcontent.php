<?php
/**
 * MDPoetry: Decorates single-poet content with the plugin's bespoke markup.
 *
 * The single poet page renders through the active theme's own template (so it
 * inherits the theme's chrome and layout). This filter injects the photo,
 * external links, the poet's poem list, aggregated tag counts, and a back-link
 * into `the_content`, guarded to the front-end singular view so it never leaks
 * into feeds, the REST API, the editor, or archive loops.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Poets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MDPoetry\PostTypes;
use MDPoetry\Poems\PoemMetaBoxes;

/**
 * Front-end content decorator for single `md_poet` views.
 *
 * @category class
 * @since 0.1.0
 * @author Eleanor Martin
 */
class PoetContent {

	/**
	 * Wires up the filter.
	 */
	public static function setup() {
		add_filter( 'the_content', array( new self(), 'decorate' ), 30 );
	}

	/**
	 * Prepends the photo and appends the poet's sections to the bio content.
	 *
	 * @param  string $content Post content (the bio).
	 * @return string
	 */
	public function decorate( $content ) {
		if ( ! is_singular( PostTypes::POST_TYPE_POET ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post = get_post();
		if ( ! $post ) {
			return $content;
		}
		return self::photo( $post ) . $content . self::sections( $post );
	}

	/**
	 * Renders the poet's featured-image photo, if set.
	 *
	 * @param  \WP_Post $post The poet.
	 * @return string
	 */
	private static function photo( $post ) {
		if ( ! has_post_thumbnail( $post ) ) {
			return '';
		}
		return '<div class="mdp-poet__photo">' . get_the_post_thumbnail( $post, 'medium' ) . '</div>';
	}

	/**
	 * Renders external links, the poem list, aggregated tag counts, and the
	 * back-link to the poets index.
	 *
	 * @param  \WP_Post $post The poet.
	 * @return string
	 */
	private static function sections( $post ) {
		$poet_id   = $post->ID;
		$author_id = (int) $post->post_author;

		// Per-user model: poet is owned by exactly one user. List that user's
		// poems linking to this poet.
		$poems = get_posts(
			array(
				'post_type'      => PostTypes::POST_TYPE_POEM,
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'author'         => $author_id,
				'meta_query'     => array(
					array(
						'key'     => PoemMetaBoxes::META_POET_ID,
						'value'   => $poet_id,
						'compare' => '=',
					),
				),
			)
		);

		$html  = self::links( $poet_id );
		$html .= self::poem_list( $poems );
		$html .= self::tag_counts( $poems );

		if ( $author_id > 0 ) {
			$html .= sprintf(
				'<p class="mdp-poet__back"><a href="%s">%s</a></p>',
				esc_url( home_url( sprintf( '/u/%d/poets/', $author_id ) ) ),
				esc_html__( '← All poets', 'mdpoetry-plugin' )
			);
		}

		return $html;
	}

	/**
	 * Renders the external-links section.
	 *
	 * @param  int $poet_id The poet post ID.
	 * @return string
	 */
	private static function links( $poet_id ) {
		$links = get_post_meta( $poet_id, PoetMetaBoxes::META_LINKS, true );
		$links = is_array( $links ) ? $links : array();
		if ( empty( $links ) ) {
			return '';
		}

		$items = '';
		foreach ( $links as $link ) {
			$url = isset( $link['url'] ) ? $link['url'] : '';
			if ( '' === $url ) {
				continue;
			}
			$label = isset( $link['label'] ) && '' !== $link['label'] ? $link['label'] : $url;
			$items .= sprintf(
				'<li><a href="%s" rel="noopener noreferrer">%s</a></li>',
				esc_url( $url ),
				esc_html( $label )
			);
		}
		if ( '' === $items ) {
			return '';
		}

		return '<section class="mdp-poet__links"><h2>'
			. esc_html__( 'Links', 'mdpoetry-plugin' )
			. '</h2><ul>' . $items . '</ul></section>';
	}

	/**
	 * Renders the count heading and alphabetical poem list.
	 *
	 * @param  \WP_Post[] $poems The poet's poems.
	 * @return string
	 */
	private static function poem_list( $poems ) {
		$count   = count( $poems );
		$heading = sprintf(
			/* translators: %d: number of poems by this poet */
			esc_html( _n( '%d poem', '%d poems', $count, 'mdpoetry-plugin' ) ),
			(int) $count
		);

		$html = '<section class="mdp-poet__poems"><h2>' . $heading . '</h2>';
		if ( ! empty( $poems ) ) {
			$html .= '<ul class="mdp-poet__poem-list">';
			foreach ( $poems as $poem ) {
				$html .= sprintf(
					'<li><a href="%s">%s</a></li>',
					esc_url( get_permalink( $poem ) ),
					esc_html( $poem->post_title )
				);
			}
			$html .= '</ul>';
		}
		$html .= '</section>';

		return $html;
	}

	/**
	 * Renders per-tag usage counts across the poet's poems, ordered by usage
	 * then alphabetically.
	 *
	 * @param  \WP_Post[] $poems The poet's poems.
	 * @return string
	 */
	private static function tag_counts( $poems ) {
		$tag_counts = array();
		foreach ( $poems as $poem ) {
			$terms = get_the_terms( $poem->ID, PostTypes::TAXONOMY_POEM_TAGS );
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				if ( ! isset( $tag_counts[ $term->term_id ] ) ) {
					$tag_counts[ $term->term_id ] = array(
						'term'  => $term,
						'count' => 0,
					);
				}
				$tag_counts[ $term->term_id ]['count']++;
			}
		}
		if ( empty( $tag_counts ) ) {
			return '';
		}
		uasort(
			$tag_counts,
			function ( $a, $b ) {
				if ( $a['count'] === $b['count'] ) {
					return strcasecmp( $a['term']->name, $b['term']->name );
				}
				return $b['count'] - $a['count'];
			}
		);

		$items = '';
		foreach ( $tag_counts as $entry ) {
			$term_link = get_term_link( $entry['term'] );
			if ( is_wp_error( $term_link ) ) {
				continue;
			}
			$items .= sprintf(
				'<li><a href="%s">%s</a> <span class="mdp-poet__tag-count">(%d)</span></li>',
				esc_url( $term_link ),
				esc_html( $entry['term']->name ),
				(int) $entry['count']
			);
		}
		if ( '' === $items ) {
			return '';
		}

		return '<section class="mdp-poet__tags"><h2>'
			. esc_html__( 'Tags', 'mdpoetry-plugin' )
			. '</h2><ul class="mdp-poet__tag-list">' . $items . '</ul></section>';
	}
}
