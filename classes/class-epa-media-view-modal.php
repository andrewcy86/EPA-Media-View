<?php
/**
 * Class to manage columns in media view.
 *
 * @package epa-media-view
 */

if ( ! class_exists( 'Epa_Media_View_Modal' ) ) {
	/**
	 * Class to manage columns in media view.
	 */
	class Epa_Media_View_Modal {

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

			add_action( 'admin_enqueue_scripts', __CLASS__ . '::epa_media_view_script' );
			add_action( 'wp_ajax_media_create_folder', __CLASS__ . '::media_create_folder' );
			add_action( 'wp_ajax_get_folder_list', __CLASS__ . '::get_folder_list' );
			add_action( 'wp_ajax_media_edit_folder', __CLASS__ . '::media_edit_folder' );
			add_action( 'wp_ajax_media_delete_folder', __CLASS__ . '::media_delete_folder' );
			add_action( 'wp_ajax_save_folder_files', __CLASS__ . '::save_folder_files' );
			add_filter( 'ajax_query_attachments_args', __CLASS__ . '::media_library_attachments' );
		}

		/**
		 * Reload Media Library
		 *
		 * @param Array $query query as array.
		 */
		public static function media_library_attachments( $query ) {

			if ( isset( $_POST['query']['folder_data'] ) && '' !== $_POST['query']['folder_data'] ) {

				if ( '-1' === $_POST['query']['folder_data'] ) {
					// For All files.
					$query = $query;

				} else if ( '0' === $_POST['query']['folder_data'] ) {
					// For Uncategorized folder files.
					$query['meta_query'] = array(
						'relation' => 'OR',
						array(
							'key' => 'folder',
							'compare' => 'NOT EXISTS',
						),
						array(
							'value' => '',
							'compare' => '=',
						),
					);
				} else {
					// For Specific folder files.
					$query['meta_query'] = array(
						array(
							'key' => 'folder',
							'value' => ( $_POST['query']['folder_data'] ) ? sanitize_text_field( wp_unslash( $_POST['query']['folder_data'] ) ) : '',
							'compare' => '=',
						),
					);

				}
			}

			// Add new variable to posts for undragable files.
			$query = new WP_Query( $query );
			$posts = array_map( 'wp_prepare_attachment_for_js', $query->posts );
			$posts = array_filter( $posts );
			array_walk(
				$posts,
				function( $value, $key ) use ( &$posts ) {

					$post_id = $value['id'];
					$meta_data = get_post_meta( $post_id, 'folder', true );
					$class = 'draggable-true';
					if ( '' !== $meta_data && ! is_numeric( $meta_data ) ) {
						$class = 'draggable-false';
					}
					$posts[ $key ]['draggable_flag'] = $class;
				}
			);

			wp_send_json_success( $posts );

		}


		/**
		 * Get Folder List
		 *
		 * @param String $request request as string.
		 */
		public static function get_folder_list( $request = '' ) {

			global $wpdb;

			$table = self::$folder_table;
			$table_name = $wpdb->prefix . $table;

			$folders = apply_filters( 'media_folder_list', array() );

			$folder_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} ORDER BY id DESC" ), ARRAY_A );

			$folders = array_merge( $folder_list, $folders );

			if ( is_array( $folders ) ) {

				$args = array(
					'post_type'   => 'attachment',
					'post_status' => array( 'inherit', 'private' ),
				);
				$all_files_query = new WP_Query( $args );

				$args = array(
					'post_type'   => 'attachment',
					'post_status' => array( 'inherit', 'private' ),
					'meta_query'  => array(
						'relation' => 'OR',
						array(
							'key' => 'folder',
							'compare' => 'NOT EXISTS',
						),
						array(
							'value' => '',
							'compare' => '=',
						),
					),
				);

				$uncategorized_files_query = new WP_Query( $args );

				$folder_html = '';

				$folder_html .= '<ul>';
					$folder_html .= '<li data-id="-1"><a href="#" class="current-active-folder folder-list-item"><i class="fa fa-home"></i>All Files <span>' . $all_files_query->found_posts . '</span></a></li>';
					$folder_html .= '<li data-id="0" class="dropable-folder"><a href="#" class="folder-list-item"><i class="fa fa-exclamation-circle"></i>Uncategorized <span>' . $uncategorized_files_query->found_posts . '</span></a></li>';
				$folder_html .= '</ul>';
				$folder_html .= '<div class="search-folder">';
					$folder_html .= '<input type="text" name="folder_name" placeholder="Enter folder name..."><i class="fa fa-search"></i>';
				$folder_html .= '</div>';
				$folder_html .= '<ul id="folder-list">';

				foreach ( $folders as $key => $value ) {

					$folder_id = isset( $value['id'] ) ? $value['id'] : $value['name'];
					$folder_name = isset( $value['name'] ) ? $value['name'] : '';

					$total_folder_files = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key = 'folder' AND meta_value = '$folder_id'" );

					$readable_class = ( ! is_numeric( $folder_id ) ) ? 'readable-folder' : 'dropable-folder';

					if ( '' !== $folder_name ) {
						$folder_html .= '<li data-id="' . $folder_id . '" class="' . $readable_class . '"><a href="#" class="folder-list-item"><i class="fa fa-folder"></i>' . $folder_name . '<span>' . $total_folder_files . '</span></a></li>';
					}
				}

				$folder_html .= '<ul>';

				if ( 'not_ajax_request' === $request ) {
					return $folder_html;
				} else {
					$response = array(
						'sucess_status' => 1,
						'messege'       => 'Folder Listed Successfully.',
						'data' => $folder_html,
					);

					echo wp_json_encode( $response );
					die();
				}
			}

		}

		/**
		 * New folder create
		 */
		public static function media_create_folder() {

			global $wpdb;

			$table = self::$folder_table;
			$table_name = $wpdb->prefix . $table;

			if ( isset( $_POST['name'] ) && '' !== $_POST['name'] ) {

				$data = array(
					'name' => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
					'parent' => isset( $_POST['parent'] ) ? sanitize_text_field( wp_unslash( $_POST['parent'] ) ) : 0,
					'type' => 0,
				);

				$check_folder = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s AND parent = %d", array( $data['name'], $data['parent'] ) ) );

				if ( empty( $check_folder ) ) {

					$wpdb->insert( $wpdb->prefix . $table, $data );

					if ( isset( $wpdb->insert_id ) ) {

						$folder_list_html = self::get_folder_list( 'not_ajax_request' );

						$response = array(
							'sucess_status' => 1,
							'messege'       => 'Folder Created Successfully.',
							'folder_html'   => $folder_list_html,
						);
					}
				} else {
					$response = array(
						'sucess_status' => 0,
						'messege'       => 'Please choose another name.',
					);
				}
			} else {
				$response = array(
					'sucess_status' => 0,
					'messege'       => 'Please enter folder name.',
				);
			}

			echo wp_json_encode( $response );
			die();

		}

		/**
		 * Rename Folder name
		 */
		public static function media_edit_folder() {

			global $wpdb;
			$table = self::$folder_table;
			$table_name = $wpdb->prefix . $table;

			if ( isset( $_POST['name'] ) && '' !== $_POST['name'] ) {

				$folder_id = isset( $_POST['folder_id'] ) ? sanitize_text_field( wp_unslash( $_POST['folder_id'] ) ) : '';
				$new_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
				$parent = isset( $_POST['parent'] ) ? sanitize_text_field( wp_unslash( $_POST['parent'] ) ) : 0;

				$check_folder = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id != %d AND name = %s AND parent = %d", array( $folder_id, $new_name, $parent ) ) );

				if ( empty( $check_folder ) ) {
					$wpdb->update( $wpdb->prefix . $table, array( 'name' => $new_name ), array( 'id' => $folder_id ), array( '%s' ), array( '%d' ) );

					$folder_list_html = self::get_folder_list( 'not_ajax_request' );

					$response = array(
						'sucess_status' => 1,
						'messege'       => 'Folder rename Successfully.',
						'folder_id'     => $folder_id,
						'folder_html'   => $folder_list_html,
					);

				} else {
					$response = array(
						'sucess_status' => 0,
						'messege'       => 'Please choose another name.',
					);
				}
			} else {
				$response = array(
					'sucess_status' => 0,
					'messege'       => 'Please enter folder name.',
				);
			}

			echo wp_json_encode( $response );
			die();
		}

		/**
		 * Folder Delete
		 */
		public static function media_delete_folder() {

			global $wpdb;
			$table = self::$folder_table;

			if ( isset( $_POST['folder_id'] ) && $_POST['folder_id'] > 0 ) {

				$wpdb->delete( $wpdb->prefix . $table, array( 'id' => sanitize_text_field( wp_unslash( $_POST['folder_id'] ) ) ), array( '%d' ) );

				$folders_file_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = 'folder' AND meta_value= %d ", $_POST['folder_id'] ), ARRAY_A );

				array_walk(
					$folders_file_list,
					function ( $value, $key ) {
						delete_post_meta( $value['post_id'], 'folder', $value['meta_value'] );
					}
				);

				$folder_list_html = self::get_folder_list( 'not_ajax_request' );

				$response = array(
					'sucess_status' => 1,
					'messege'       => 'Folder Deleted Successfully.',
					'folder_html'   => $folder_list_html,
				);
			} else {
				$response = array(
					'sucess_status' => 1,
					'messege'       => 'Folder Delete Error!',
				);
			}

			echo wp_json_encode( $response );
			die();

		}

		/**
		 * Save files in folder
		 */
		public static function save_folder_files() {

			global $wpdb;
			$folder_table = self::$folder_table;
			$folder_table_name = $wpdb->prefix . $folder_table;

			$folder_id = isset( $_POST['folder_id'] ) ? sanitize_text_field( wp_unslash( $_POST['folder_id'] ) ) : '';
			$attachment_id = isset( $_POST['attachment_id'] ) ? sanitize_text_field( wp_unslash( $_POST['attachment_id'] ) ) : '';

			if ( '' !== $folder_id && $folder_id > 0 && '' !== $attachment_id ) {

				update_post_meta( $attachment_id, 'folder', $folder_id );
				$status_flag = 1;

			} else if ( '0' === $folder_id && '' !== $attachment_id ) {

				delete_post_meta( $attachment_id, 'folder' );
				$status_flag = 1;

			} else {
				$status_flag = 0;
			}

			if ( 1 === $status_flag ) {

				$folder_list_html = self::get_folder_list( 'not_ajax_request' );
				$response = array(
					'sucess_status' => 1,
					'messege'       => 'Successfully Moved.',
					'folder_html'   => $folder_list_html,
				);

			} else {
				$response = array(
					'sucess_status' => 0,
					'messege'       => 'Error in moved file',
				);
			}

			echo wp_json_encode( $response );
			die();

		}

		/**
		 * Epa media view script
		 */
		public static function epa_media_view_script() {

			wp_enqueue_script( 'epa-media-view-js', EPA_MEDIA_VIEW_URL . 'js/epa_media_view.js', array( 'jquery' ) );

			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-droppable' );

			wp_enqueue_style( 'epa-media-view-css', EPA_MEDIA_VIEW_URL . 'css/epa_media_view.css', array() );

			wp_localize_script(
				'epa-media-view-js',
				'epa_media_ajax_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					/*'nonce' => wp_create_nonce( 'ajax-nonce' ),*/
				)
			);

			wp_localize_script(
				'epa-media-view-js',
				'epa_media_view_loader',
				array( 'image' => EPA_MEDIA_VIEW_URL . 'images/ajax-loader@2x.gif' )
			);
		}
	}

	Epa_Media_View_Modal::init();
}
