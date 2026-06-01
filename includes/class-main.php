<?php
/**
 * MDPoetry: Main loader class for MD Poetry plugin.
 *
 * Handles all of the initial setup.
 *
 * @package MDPoetry
 */

namespace MDPoetry;

use MDPoetry\Admin\AdminSettings;
use MDPoetry\Poems\PoemMetaBoxes;
use MDPoetry\Poems\PoemVisibility;
use MDPoetry\Poets\PoetMetaBoxes;
use MDPoetry\Rewrites;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * Initialises all the things that the plugin needs.
 *
 * @category class
 * @author Eleanor Martin
 * @since 0.0.1
 */
class Main {

	/**
	 * Singleton instance of the plugin class.
	 *
	 * @var MDP_Main
	 */
	private static $instance;

	/**
	 * Constructs the class.
	 */
	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init();
	}

	/**
	 * Initialise anything that needs doing.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function init() {
		// Add any actions.
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Instantiate any classes.
		$settings = new AdminSettings();
		// And check the latest default options are set.
		$settings->update_default_options();

		// Set up the post types.
		PostTypes::setup_post_types();

		// Meta boxes for the editor screens.
		PoemMetaBoxes::setup();
		PoetMetaBoxes::setup();

		// Copyright-safe visibility filter on poem content.
		PoemVisibility::setup();

		// Namespaced URLs (/u/{id}/...) and the /poets/ shortcut.
		Rewrites::setup();

		// Plugin-provided single templates (theme can still override).
		Templates::setup();

		// Flush rewrites once per version bump so namespaced URLs resolve
		// without requiring a manual visit to Settings -> Permalinks.
		$this->maybe_flush_rewrites();
	}

	/**
	 * Flushes rewrite rules when the stored version doesn't match the current
	 * plugin version.
	 */
	private function maybe_flush_rewrites() {
		$stored = get_option( 'mdpoetry_version' );
		if ( $stored !== MDP_VERSION ) {
			flush_rewrite_rules();
			update_option( 'mdpoetry_version', MDP_VERSION );
		}
	}

	/**
	 * List of constants to define for the plugin.
	 */
	private function define_constants() {
		// Display name for the plugin. Used for settings page name etc.
		$this->define( 'MDP_PLUGIN_NAME', 'MD Poetry' );
		// This constant should be changed for your new plugin. Make it lowercase!
		$this->define( 'MDP_PLUGIN_SHORTNAME', 'mdpoetry' );
		$this->define( 'MDP_ABSPATH', dirname( MDP_PLUGIN_FILE ) . '/' );
		$this->define( 'MDP_TEMPLATE_PATH', 'md-poetry' );
		$this->define( 'MDP_VERSION', '0.0.7.2' );
	}

	/**
	 * Class includes for the plugin.
	 *
	 * @since 0.0.1
	 */
	private function includes() {
		include_once MDP_ABSPATH . 'includes/class-autoloader.php';
	}

	/**
	 * Include anything that only needs initiating for admins.
	 */
	public function admin_init() {
	}

	/**
	 * Defines constants.
	 *
	 * Checks if constant is already defined and if not defines it.
	 *
	 * @param  String $name  Name of new constant.
	 * @param  mixed  $value Value for new constant.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Fetches a singleton instance of this class.
	 *
	 * @return MDP_Main
	 */
	public static function get_instance() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
