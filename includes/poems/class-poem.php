<?php
/**
 * MDPoetry: Main class for the poetry object.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Poems;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MDPoetry\Base\BaseObject;

/**
 * Main class for the poem object.
 *
 * @category class
 * @since 0.0.5
 * @author Eleanor Martin
 */
class Poem extends BaseObject {

	/**
	 * Poem title
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Main poem text.
	 *
	 * @var string
	 */
	private $poem;

	/**
	 * Title getter
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Poem getter.
	 *
	 * @return string
	 */
	public function get_poem() {
		return $this->poem;
	}

	/**
	 * Excerpt getter.
	 *
	 * Returns the first non-empty line of the poem body, stripping Gutenberg
	 * block comment markers and HTML tags before splitting on newlines.
	 *
	 * @return string
	 */
	public function get_excerpt() {
		$existing = parent::get_excerpt();
		if ( empty( $existing ) && ! empty( $this->poem ) ) {
			parent::set_excerpt( self::compute_excerpt( $this->poem ) );
		}
		return parent::get_excerpt();
	}

	/**
	 * Computes the first-line excerpt from poem body content.
	 *
	 * @param  string $content Raw poem body.
	 * @return string          First non-empty line, or empty string.
	 */
	public static function compute_excerpt( $content ) {
		$lines = self::extract_lines( $content );
		return empty( $lines ) ? '' : $lines[0];
	}

	/**
	 * Counts non-empty lines in a poem body.
	 *
	 * @param  string $content Raw poem body.
	 * @return int             Number of non-empty lines.
	 */
	public static function count_lines( $content ) {
		return count( self::extract_lines( $content ) );
	}

	/**
	 * Normalises raw poem body into an array of trimmed, non-empty lines.
	 *
	 * Strips Gutenberg block comment markers, converts `<br>` tags (used by
	 * the Poetry/Verse block) to newlines so they survive HTML stripping,
	 * removes remaining tags, decodes entities, then splits on newlines.
	 *
	 * @param  string $content Raw poem body.
	 * @return string[]        Ordered list of non-empty lines.
	 */
	private static function extract_lines( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return array();
		}
		$text = preg_replace( '/<!--.*?-->/s', '', $content );
		$text = preg_replace( '#<br\s*/?>#i', "\n", $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );

		$result = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
			$trimmed = trim( $line );
			if ( '' !== $trimmed ) {
				$result[] = $trimmed;
			}
		}
		return $result;
	}

	/**
	 * Title setter
	 *
	 * @param string $title Poem title.
	 */
	public function set_title( $title ) {
		$this->title = $title;
	}

	/**
	 * Poem setter
	 *
	 * @param string $poem_text main body of the poem.
	 */
	public function set_poem( $poem_text ) {
		$this->poem = $poem_text;
	}
}
