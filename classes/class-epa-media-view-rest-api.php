<?php
/**
 * Class to manage epa media view rest api.
 *
 * @package epa-media-view
 */

if ( ! class_exists( 'Epa_Media_View_Rest_Api' ) ) {
	/**
	 * Class to manage epa media view rest api.
	 */
	class Epa_Media_View_Rest_Api {

		/**
		 * Folder table name
		 *
		 * @var $folder_table  variable for get folder table name.
		 */
		private static $folder_table = 'epa_media_view';

		/**
		 * Call Init functiom
		 *
		 * @since 1.0
		 * @static
		 * @access public
		 */
		public static function init() {

			add_action( 'rest_api_init', __CLASS__ . '::get_media_list' );
			add_filter( "rest_attachment_query", __CLASS__ . '::rest_attachment_query', 10 , 2 );
		}

		/**
		 * Get media files of folder using folder name
		 */
		public static function get_media_list () {

			$media_controller = new WP_REST_Attachments_Controller( 'attachment' );

			$media_query   = $media_controller->get_collection_params();
			$media_query[] = array(
				'folder_name' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_string( $param );
				}
			) );

			register_rest_route( 'wp/v2', '/media/folder/(?<folder_name>[-_0-9a-zA-Z\S]+)', array(
			    'methods' => 'GET',
			    'callback' => __CLASS__ . '::get_media_list_callback',
			    'args' => $media_query,
			) );
		}

		/**
		 * add query for get list of particular folder's files.
		 */
		public static function rest_attachment_query( $args, $req ) {

			global $wpdb;
			$table = self::$folder_table;
			$table_name = $wpdb->prefix . $table;

			$folder_name = $req->get_param('folder_name');
			$folder_name = str_replace('%20', ' ', $folder_name);
				
			if ( '' !== $folder_name && null !== $folder_name ) {
				$check_folder = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE name = %s", array( $folder_name ) ), ARRAY_A );

				if ( is_array( $check_folder ) && '' !== $check_folder['id'] ) {
					$folder = $check_folder['id'];
				} else {
					$folder = $folder_name;
				}

				$args['meta_query'] = array(
					array(
						'key'     => 'folder',
						'value'   => $folder,
						'compare' => '=',
					),
				);
			}

			return $args;
		}

		/**
		 * Get media files of folder using folder name
		 */
		public static function get_media_list_callback( $data ) {

			$media_controller = new WP_REST_Attachments_Controller( 'attachment' );	
	
			$result = $media_controller->get_items( $data );

			$response = rest_ensure_response( $result );
			return $response;
		}
	}

	Epa_Media_View_Rest_Api::init();
}
