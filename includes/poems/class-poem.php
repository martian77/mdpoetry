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
	 * Excerpt getter
	 *
	 * @return string
	 */
	public function get_excerpt() {
		// Want to return just the first line.
		if ( empty( $this->excerpt ) ) {
			$text = explode( '<br>', $this->poem );
			parent::set_excerpt( $text[0] );
		}
		return parent::get_excerpt();
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
