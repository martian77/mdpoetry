<?php
/**
 * MDPoetry: Base object class to be extended.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base post object for custom post types.
 *
 * @category class
 * @since 0.0.5
 * @author Eleanor Martin
 */
class BaseObject {

	/**
	 * Id.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Date created
	 *
	 * @var string
	 */
	private $date_created;

	/**
	 * Post status
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Object excerpt.
	 *
	 * @var string
	 */
	private $excerpt;

	/**
	 * Post menu order
	 *
	 * @var int
	 */
	private $menu_order;

	/**
	 * Post slug.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Date created getter
	 *
	 * @return string
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * Excerpt getter
	 *
	 * @return string
	 */
	public function get_excerpt() {
		return $this->excerpt;
	}

	/**
	 * Status getter
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Menu order getter.
	 *
	 * @return int
	 */
	public function get_menu_order() {
		return $this->menu_order;
	}

	/**
	 * Slug getter.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Date created setter
	 *
	 * @param string $date_created Date the poem was created in the database.
	 */
	public function set_date_created( $date_created ) {
		$this->date_created = $date_created;
	}

	/**
	 * Excerpt setter.
	 *
	 * @param string $excerpt The excerpt to set.
	 */
	public function set_excerpt( $excerpt ) {
		$this->excerpt = $excerpt;
	}

	/**
	 * Status setter.
	 *
	 * @param string $status Status of the post.
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * Menu order setter.
	 *
	 * @param int $menu_order The menu order.
	 */
	public function set_menu_order( $menu_order ) {
		$this->menu_order = $menu_order;
	}

	/**
	 * Slug setter.
	 *
	 * @param string $slug Post slug.
	 */
	public function set_slug( $slug ) {
		$this->slug = $slug;
	}
}
