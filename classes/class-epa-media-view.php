<?php
/**
 * Class to manage Epa Media View
 *
 * @package epa-media-view
 */

if ( ! class_exists( 'Epa_Media_View' ) ) {
	/**
	 * Base class of the plugin
	 */
	class Epa_Media_View {
		/**
		 * Call the single instance of the class
		 *
		 * @since 1.0
		 * @var Epa Media View the single instance of the class
		 */
		protected static $instance = null;

		/**
		 * Instantiates the plugin and include all the files needed for the plugin.
		 *
		 * @since 1.0
		 * @access public
		 */
		public function __construct() {

			self::include_plugin_files();
		}

		/**
		 * Main Epa Media View Plugin instance
		 *
		 * Ensures only one instance of Epa Media View is loaded or can be loaded.
		 *
		 * @since 1.0
		 * @static
		 * @access public
		 * @return Epa Media View - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Include all the files needed for the plugin.
		 *
		 * @since 1.0
		 * @static
		 * @access private
		 */
		private static function include_plugin_files() {
			require_once EPA_MEDIA_VIEW_DIR . 'classes/class-epa-media-view-modal.php';
			require_once EPA_MEDIA_VIEW_DIR . 'classes/class-epa-media-view-rest-api.php';
		}
	}
}
