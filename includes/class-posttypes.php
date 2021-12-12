<?php
/**
 * MDPoetry: Adds posttypes and taxonomies.
 *
 * @package MDPoetry
 */

namespace MDPoetry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds any required post types/taxonomies for this plugin.
 *
 * @category class
 * @since 0.0.5
 * @author Eleanor Martin
 */
class PostTypes {

	public const POST_TYPE_POEM = 'md_poem';

	/**
	 * Runs the setup
	 */
	public function run_setup() {
		// Add posttypes.
		$this->add_post_types();
		// Add any taxonomies.
		$this->add_taxonomies();
	}

	/**
	 * Adds the post types.
	 */
	private function add_post_types() {
		$poem = array(
			'labels' => array(
				'name' => __( 'Poems', 'mdpoetry-plugin' ),
				'singular_name' => __( 'Poem', 'mdpoetry-plugin' ),
			),
			'public' => true,
			'has_archive' => true,
		);
		register_post_type( self::POST_TYPE_POEM, $poem );
		$poet = array(
			'labels' => array(
				'name' => __( 'Poets', 'mdpoetry-plugin' ),
				'singular_name' => __( 'Poet', 'mdpoetry-plugin' ),
			),
			'public' => true,
			'has_archive' => true,
		);
		register_post_type( 'md_poet', $poet );
	}

	/**
	 * Add the taxonomies.
	 */
	private function add_taxonomies() {
		$labels = array(
			'name'          => __( 'Poem Tags', 'mdpoetry-plugin' ),
			'singular_name' => __( 'Poem Tag', 'mdpoetry-plugin' ),
			'search_items'  => __( 'Search Poem Tags', 'mdpoetry-plugin' ),
			'all_items'     => __( 'All Poem Tags', 'mdpoetry-plugin' ),
			'edit_item'     => __( 'Edit Poem Tag', 'mdpoetry-plugin' ),
			'update_item'   => __( 'Update Poem Tag', 'mdpoetry-plugin' ),
			'add_new_item'  => __( 'Add New Poem Tag', 'mdpoetry-plugin' ),
			'new_item_name' => __( 'New Poem Tag', 'mdpoetry-plugin' ),
			'menu_name'     => __( 'Poem Tags', 'mdpoetry-plugin' ),
		);
		$args   = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
		);
		register_taxonomy( 'poem_tags', array( 'md_poem' ), $args );
	}

	/**
	 * Call the post type setup.
	 */
	public static function setup_post_types() {
		$posttype = new PostTypes();
		$posttype->run_setup();
	}
}
