<?php
/**
 * MDPoetry: Registers the plugin's custom blocks.
 *
 * @package MDPoetry
 */

namespace MDPoetry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the server-rendered blocks used by the block-theme templates.
 *
 * Blocks are built by `npm run build` (see src/blocks/) into build/blocks/;
 * each built folder's block.json wires up its own render.php, so nothing
 * else needs registering here.
 *
 * @category class
 * @since 0.0.9
 * @author Eleanor Martin
 */
class Blocks {

	/**
	 * Registers the blocks.
	 *
	 * Called directly (not via add_action('init', ...)): Main::init() already
	 * runs inside WP's own `init` firing, and a callback added to a hook while
	 * that same priority is being iterated doesn't run this pass.
	 */
	public static function setup() {
		self::register_blocks();
	}

	/**
	 * Registers every built block.
	 */
	public static function register_blocks() {
		$build_dir = MDP_ABSPATH . 'build/blocks';
		$folders   = glob( $build_dir . '/*', GLOB_ONLYDIR );
		if ( ! $folders ) {
			return;
		}
		foreach ( $folders as $folder ) {
			if ( file_exists( $folder . '/block.json' ) ) {
				register_block_type( $folder );
			}
		}
	}
}
