<?php
/**
 * MDPoetry: Autoloader class.
 *
 * Manages the autoloading of files for the MDPoetry plugin.
 *
 * @package MDPoetry
 */

namespace MDPoetry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloads classes on demand for the plugin.
 *
 * Generates class filename from the classname.
 *
 * @category class
 * @author Eleanor Martin
 * @since 0.0.1
 */
class Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The constructor.
	 */
	public function __construct() {
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( MDP_PLUGIN_FILE ) ) . '/includes/';
	}

	/**
	 * Autoloads the class files.
	 *
	 * @param  String $class Name of the class to autoload.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );

		if ( 0 !== strpos( $class, MDP_PLUGIN_SHORTNAME ) ) {
			return;
		}

		$class_array = explode( '\\', $class );
		unset( $class_array[0] );

		$file = $this->get_filename_from_classname( array_pop( $class_array ) );
		$path = trailingslashit( implode( '/', $class_array ) );

		$this->load_file( $this->include_path . $path . $file );
	}

	/**
	 * Returns a filename from a given class.
	 *
	 * @param  String $classname lowercase name of class to be found.
	 * @return String            name of file.
	 * */
	private function get_filename_from_classname( $classname ) {
		return 'class-' . str_replace( '_', '-', $classname ) . '.php';
	}

	/**
	 * Includes the file.
	 *
	 * @param  String $path Filepath to include.
	 * @return Boolean      True if successful, false otherwise.
	 */
	private function load_file( $path ) {
		if ( ! empty( $path ) && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}
}

// Create the autoloader.
new Autoloader();
