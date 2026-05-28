<?php
/**
 * MDPoetry: Main class for the poet object.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Poets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MDPoetry\Base\BaseObject;

/**
 * Main class for the poet object.
 *
 * @category class
 * @since 0.0.6
 * @author Eleanor Martin
 */
class Poet extends BaseObject {

	/**
	 * Poet's display name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Biography / notes about the poet.
	 *
	 * @var string
	 */
	private $bio;

	/**
	 * Attachment ID for the poet's photo (featured image).
	 *
	 * @var int
	 */
	private $photo_id;

	/**
	 * External links for the poet (Wikipedia, Poetry Foundation, etc.).
	 *
	 * Each entry is an associative array with keys `label` and `url`.
	 *
	 * @var array
	 */
	private $external_links = array();

	/**
	 * Name getter.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Bio getter.
	 *
	 * @return string
	 */
	public function get_bio() {
		return $this->bio;
	}

	/**
	 * Photo ID getter.
	 *
	 * @return int
	 */
	public function get_photo_id() {
		return (int) $this->photo_id;
	}

	/**
	 * External links getter.
	 *
	 * @return array
	 */
	public function get_external_links() {
		return is_array( $this->external_links ) ? $this->external_links : array();
	}

	/**
	 * Name setter.
	 *
	 * @param string $name Poet's display name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Bio setter.
	 *
	 * @param string $bio Biography text.
	 */
	public function set_bio( $bio ) {
		$this->bio = $bio;
	}

	/**
	 * Photo ID setter.
	 *
	 * @param int $photo_id Attachment ID.
	 */
	public function set_photo_id( $photo_id ) {
		$this->photo_id = (int) $photo_id;
	}

	/**
	 * External links setter.
	 *
	 * @param array $external_links List of `[ 'label' => ..., 'url' => ... ]` arrays.
	 */
	public function set_external_links( $external_links ) {
		$this->external_links = is_array( $external_links ) ? $external_links : array();
	}
}
