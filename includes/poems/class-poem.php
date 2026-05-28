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
	 * Strips block comment markers (e.g. `<!-- wp:paragraph -->`) and any
	 * remaining HTML tags, then splits on newlines and returns the first
	 * non-empty line.
	 *
	 * @param  string $content Raw poem body.
	 * @return string          First non-empty line, or empty string.
	 */
	public static function compute_excerpt( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return '';
		}
		$text  = preg_replace( '/<!--.*?-->/s', '', $content );
		$text  = wp_strip_all_tags( $text );
		$text  = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				return $line;
			}
		}
		return '';
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
