<?php
/**
 * Plugin Name: EPA Media View
 * Description: EPA Media View wordpress plugin
 * Version: 1.0.0
 * Author: Cortney Ray
 * Author URI: cortneyray.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package epa-media-view
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied' );
}

/**
 * Define constants used in the plugin
 */
define( 'EPA_MEDIA_VIEW_DIR', plugin_dir_path( __FILE__ ) );
define( 'EPA_MEDIA_VIEW_URL', plugin_dir_url( __FILE__ ) );
define( 'EPA_MEDIA_VIEW_TEXTDOMAIN', 'epa-media-view' );

/**
 * Include the core file of the plugin
 */
require_once EPA_MEDIA_VIEW_DIR . 'classes/class-epa-media-view.php';

if ( ! function_exists( 'epa_media_view_init' ) ) {

	/**
	 * Function to initialize the plugin.
	 *
	 * @since 1.0
	 *
	 * @return class object
	 */
	function epa_media_view_init() {
		/* Initialize the base class of the plugin */
		return epa_media_view::instance();
	}
}

/**
 * Create the main object of the plugin when the plugins are loaded
 */
add_action( 'plugins_loaded', 'epa_media_view_init' );

// Plugin activated hook.
register_activation_hook( __FILE__, 'epa_media_view_activate' );
/**
 * Plugin activate function
 */
function epa_media_view_activate() {

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$table_epa_media_view = $wpdb->prefix . 'epa_media_view';
	if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', $table_epa_media_view ) ) != $table_epa_media_view ) {
		$sql = 'CREATE TABLE ' . $table_epa_media_view . ' (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(250) NOT NULL,
        `parent` int(11) NOT NULL DEFAULT 0,
        `type` int(2) NOT NULL DEFAULT 0,
        `ord` int(11) NULL DEFAULT 0,
        `created_by` int(11) NULL DEFAULT 0,
        PRIMARY KEY (id),
		UNIQUE KEY `id` (id)) ' . 'ENGINE = InnoDB ' . $charset_collate . ';';
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}




