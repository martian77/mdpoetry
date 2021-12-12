<?php
/**
 * MDPoetry Poem: Datastore class for fetching poems.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Poems;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MDPoetry\PostTypes;

/**
 * Holds the CRUD functions for poems.
 *
 * @category class
 * @since 0.0.5
 * @author Eleanor Martin
 */
class PoemDataStore {

	/**
	 * Creates a new poem in the database.
	 *
	 * @param \MDPoetry\Poems\Poem $poem The poem to create.
	 */
	public function create( &$poem ) {
		if ( empty( $poem->get_date_created() ) ) {
			$poem->set_date_created( time() );
		}

		$id = wp_insert_post(
			\apply_filters(
				'mdpoetry_new_poem_data',
				array(
					'post_type' => PostTypes::POST_TYPE_POEM,
					'post_status' => $poem->get_status(),
					'post_author' => get_current_user_id(),
					'post_title' => $poem->get_title(),
					'post_content' => $poem->get_poem(),
					'post_excerpt' => $poem->get_excerpt(),
					'post_parent' => 0,
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'menu_order' => $poem->get_menu_order(),
					'post_date' => gmdate( 'Y-m-d H:i:s', $poem->get_date_created()->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $poem->get_date_created()->getTimestamp() ),
					'post_name' => $poem->get_slug(),
				)
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$poem->set_id( $id );

			do_action( 'mdpoetry_new_poem', $id, $poem );
		}
	}

	/**
	 * Reads the data for a poem back out of the database.
	 *
	 * @param \MDPoetry\Poems\Poem $poem Poem to be read.
	 */
	public function read( &$poem ) {

	}

	/**
	 * Update the poem in the database.
	 *
	 * @param  \MDPoetry\Poems\Poem $poem Poem to be updated.
	 */
	public function update( &$poem ) {

	}

	public function delete( &$poem ) {

	}
}
