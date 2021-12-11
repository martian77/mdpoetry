<?php
/**
Plugin Name: MartianDaze Poetry
Plugin URI:  https://martiandaze.net
Description: A poetry store for semi-private use
Version:     0.0.4
Author:      Eleanor Martin
Author URI:  https://martiandaze.net/about

@package MDPoetry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define the plugin file location.
if ( ! defined( 'MDP_PLUGIN_FILE' ) ) {
	define( 'MDP_PLUGIN_FILE', __FILE__ );
}

// Define the translation domain.
if ( ! defined( 'MDP_TRANSLATE_DOMAIN' ) ) {
	define( 'MDP_TRANSLATE_DOMAIN', 'plugin-template' );
}

// Include the main plugin class.
if ( ! class_exists( 'MDPoetry\Main' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-main.php';
}

// Initialise the main plugin class on init.
add_action(
	'init',
	function() {
		MDPoetry\Main::get_instance();
	}
);

/**
 * Checks module dependencies.
 *
 * @since 0.0.1
 * @return void
 */
function mdp_activate_plugin() {
	$module_dependencies = array(
		// Put any dependencies in here.
		// 'classname' => 'Module display title',
		// e.g. 'acf' => 'Advanced Custom Fields',.
	);

	foreach ( $module_dependencies as $classname => $title ) {
		if ( ! class_exists( $classname ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( sprintf( __( 'Please install and activate %s.', MDP_TRANSLATE_DOMAIN ), $title ) ); //phpcs:ignore
		}
	}
}

register_activation_hook( __FILE__, 'mdp_activate_plugin' );

/**
 * Cleans up the database on uninstall.
 *
 * @since 0.0.2
 * @return void
 */
function mdp_uninstall_plugin() {
	global $wpdb;
	$option_name = MDP_PLUGIN_SHORTNAME . '_%';
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;",
			$option_name
		)
	);
}

register_uninstall_hook( __FILE__, 'mdp_uninstall_plugin' );
