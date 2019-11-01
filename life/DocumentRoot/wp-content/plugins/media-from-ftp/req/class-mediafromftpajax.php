<?php
/**
 * Media from FTP
 *
 * @package    Media from FTP
 * @subpackage MediafromFtpAjax
/*
	Copyright (c) 2013- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$mediafromftpajax = new MediaFromFtpAjax();

/** ==================================================
 * Register Ajax
 */
class MediaFromFtpAjax {

	/** ==================================================
	 * Path
	 *
	 * @var $upload_dir  upload_dir.
	 */
	private $upload_dir;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_url  $upload_url.
	 */
	private $upload_url;

	/** ==================================================
	 * Add on bool
	 *
	 * @var $is_add_on_activate  is_add_on_activate.
	 */
	private $is_add_on_activate;

	/** ==================================================
	 * Construct
	 *
	 * @since 9.81
	 */
	public function __construct() {

		$plugin_base_dir = untrailingslashit( plugin_dir_path( __DIR__ ) );
		$slugs = explode( '/', $plugin_base_dir );
		$slug = end( $slugs );
		$plugin_dir = untrailingslashit( rtrim( $plugin_base_dir, $slug ) );

		if ( ! class_exists( 'MediaFromFtp' ) ) {
			include_once $plugin_base_dir . '/inc/class-mediafromftp.php';
		}
		$mediafromftp = new MediaFromFtp();
		list($this->upload_dir, $this->upload_url, $upload_path) = $mediafromftp->upload_dir_url_path();

		$category_active = false;
		if ( function_exists( 'media_from_ftp_add_on_category_load_textdomain' ) ) {
			include_once $plugin_dir . '/media-from-ftp-add-on-category/inc/MediaFromFtpAddOnCategory.php';
			$category_active = true;
		}

		$this->is_add_on_activate = array(
			'category'  => $category_active,
		);

		$action1 = 'mediafromftp-update-ajax-action';
		$action3 = 'mediafromftp_message';
		$action2 = 'mediafromftp-import-ajax-action';
		$action4 = 'mediafromftp_medialibraryimport_message';
		add_action( 'wp_ajax_' . $action1, array( $this, 'mediafromftp_update_callback' ) );
		add_action( 'wp_ajax_' . $action3, array( $this, 'mediafromftp_message_callback' ) );
		add_action( 'wp_ajax_' . $action2, array( $this, 'mediafromftp_medialibraryimport_update_callback' ) );
		add_action( 'wp_ajax_' . $action4, array( $this, 'mediafromftp_medialibraryimport_message_callback' ) );

	}

	/** ==================================================
	 * Update Files Callback
	 *
	 * @since 9.30
	 */
	public function mediafromftp_update_callback() {

		$action1 = 'mediafromftp-update-ajax-action';
		if ( check_ajax_referer( $action1, 'nonce', false ) ) {
			if ( current_user_can( 'upload_files' ) ) {
				$maxcount = 0;
				$new_url_attach = null;
				$new_url_datetime = null;
				if ( ! empty( $_POST['maxcount'] ) ) {
					$maxcount = intval( $_POST['maxcount'] );
				}

				$mediafromftp_settings = get_option( $this->wp_options_name() );
				$mediafromftp = new MediaFromFtp();

				if ( ! empty( $_POST['new_url'] ) ) {
					$filename = sanitize_text_field( wp_unslash( $_POST['new_url'] ) );
					$upload_path = wp_normalize_path( $this->upload_dir );
					$new_url_attach = $this->upload_url . str_replace( $upload_path, '', $filename );
					$new_url_attach = $mediafromftp->mb_utf8( $new_url_attach, $mediafromftp_settings['character_code'] );
				}
				if ( ! empty( $_POST['new_datetime'] ) ) {
					$new_url_datetime = sanitize_text_field( wp_unslash( $_POST['new_datetime'] ) );
				}

				$new_url_mlccategory = null;
				$new_url_emlcategory = null;
				$new_url_mlacategory = null;
				$new_url_mlatags = null;
				if ( $this->is_add_on_activate['category'] ) {
					if ( ! empty( $_POST['new_mlccategory'] ) ) {
						$new_url_mlccategory = sanitize_text_field( wp_unslash( $_POST['new_mlccategory'] ) );
					}
					if ( ! empty( $_POST['new_emlcategory'] ) ) {
						$new_url_emlcategory = sanitize_text_field( wp_unslash( $_POST['new_emlcategory'] ) );
					}
					if ( ! empty( $_POST['new_mlacategory'] ) ) {
						$new_url_mlacategory = sanitize_text_field( wp_unslash( $_POST['new_mlacategory'] ) );
					}
					if ( ! empty( $_POST['new_mlatags'] ) ) {
						$new_url_mlatags = sanitize_text_field( wp_unslash( $_POST['new_mlatags'] ) );
					}
				}

				if ( ! empty( $new_url_attach ) ) {

					$dateset = $mediafromftp_settings['dateset'];
					$datefixed = $mediafromftp_settings['datefixed'];
					$yearmonth_folders = get_option( 'uploads_use_yearmonth_folders' );
					$exif_text_tag = null;
					if ( $mediafromftp_settings['caption']['apply'] ) {
						$exif_text_tag = $mediafromftp_settings['caption']['exif_text'];
					}

					$exts = explode( '.', wp_basename( $new_url_attach ) );
					$ext = end( $exts );

					/* Delete Cash */
					$mediafromftp->delete_cash( $ext, $new_url_attach );

					/* Regist */
					list($attach_id, $new_attach_title, $new_url_attach, $metadata) = $mediafromftp->regist( $filename, $ext, $new_url_attach, $new_url_datetime, $dateset, $datefixed, $yearmonth_folders, $mediafromftp_settings['character_code'], $mediafromftp_settings['cron']['user'] );

					$cat_html = null;
					$mlccategory = null;
					$emlcategory = null;
					$mlacategory = null;
					$mlatag = null;
					if ( $this->is_add_on_activate['category'] ) {
						$mediafromftpaddoncategory = new MediaFromFtpAddOnCategory();
						list($cat_html, $cat_text, $mlccategory, $emlcategory, $mlacategory, $mlatag) = $mediafromftpaddoncategory->regist_term( $attach_id, $new_url_mlccategory, $new_url_emlcategory, $new_url_mlacategory, $new_url_mlatags );
						unset( $mediafromftpaddoncategory );
					}

					if ( -1 == $attach_id || -2 == $attach_id ) { /* error */
						$error_title = $mediafromftp->mb_utf8( $new_attach_title, $mediafromftp_settings['character_code'] );
						$error_url = $mediafromftp->mb_utf8( $new_url_attach, $mediafromftp_settings['character_code'] );
						if ( -1 == $attach_id ) {
							/* translators: error message */
							echo '<div class="notice notice-error is-dismissible"><ul><li><div>' . wp_kses_post( __( 'File name:' ) . $error_title . '</div><div>' . __( 'Directory name:', 'media-from-ftp' ) . $error_url . '</div>' . sprintf( __( '<div>You need to make this directory writable before you can register this file. See <a href="%1$s" target="_blank">the Codex</a> for more information.</div><div>Or, filename or directoryname must be changed of illegal. Please change Character Encodings for Server of <a href="%2$s">Settings</a>.</div>', 'media-from-ftp' ), 'https://codex.wordpress.org/Changing_File_Permissions', admin_url( 'admin.php?page=mediafromftp-settings' ) ) ) . '</li></div>';
						} elseif ( -2 == $attach_id ) {
							echo '<div class="notice notice-error is-dismissible"><ul><li><div>' . wp_kses_post( __( 'Title' ) . ': ' . $error_title . '</div><div>URL: ' . $error_url . '</div><div>' . __( 'This file could not be registered in the database.', 'media-from-ftp' ) ) . '</div></li></ul></div>';
						}
					} else {
						/* Outputdata */
						list($imagethumburls, $mimetype, $length, $stamptime, $file_size, $exif_text) = $mediafromftp->output_metadata( $ext, $attach_id, $metadata, $mediafromftp_settings['character_code'], $exif_text_tag );

						$image_attr_thumbnail = wp_get_attachment_image_src( $attach_id, 'thumbnail', true );

						$output_html = $mediafromftp->output_html_and_log( $ext, $attach_id, $new_attach_title, $new_url_attach, $imagethumburls, $mimetype, $length, $stamptime, $file_size, $exif_text, $image_attr_thumbnail, $mediafromftp_settings, $cat_html, $mlccategory, $emlcategory, $mlacategory, $mlatag );

						header( 'Content-type: text/html; charset=UTF-8' );
						$allowed_output_html = array(
							'a'   => array(
								'href' => array(),
								'target' => array(),
								'style' => array(),
							),
							'img'   => array(
								'src' => array(),
								'width' => array(),
								'height' => array(),
								'style' => array(),
							),
							'div'   => array(
								'style' => array(),
								'class' => array(),
							),
							'font'   => array(
								'color' => array(),
							),
							'ul' => array(),
							'li' => array(),
							'span'   => array(
								'class' => array(),
							),
						);
						echo wp_kses( $output_html, $allowed_output_html );

					}
					unset( $mediafromftp );

				}
			}
		} else {
			status_header( '403' );
			echo 'Forbidden';
		}

		wp_die();

	}

	/** ==================================================
	 * Update Messages Callback
	 *
	 * @since 9.30
	 */
	public function mediafromftp_message_callback() {

		$action3 = 'mediafromftp_message';
		if ( check_ajax_referer( $action3, 'nonce', false ) ) {
			$error_count = 0;
			$error_update = null;
			$success_count = 0;
			if ( ! empty( $_POST['error_count'] ) ) {
				$error_count = intval( $_POST['error_count'] );
			}
			if ( ! empty( $_POST['error_update'] ) ) {
				$error_update = sanitize_text_field( wp_unslash( $_POST['error_update'] ) );
			}
			if ( ! empty( $_POST['success_count'] ) ) {
				$success_count = intval( $_POST['success_count'] );
			}

			$output_html = null;
			if ( $error_count > 0 ) {
				/* translators: error message */
				$error_message = sprintf( __( 'Errored to the registration of %1$d files.', 'media-from-ftp' ), $error_count );
				$output_html .= '<div class="notice notice-error is-dismissible"><ul><li><div>' . $error_message . '</div>' . $error_update . '</li></ul></div>';
			}
			/* translators: success message */
			$success_message = sprintf( __( 'Succeeded to the registration of %1$d files.', 'media-from-ftp' ), $success_count );
			$output_html .= '<div class="notice notice-success is-dismissible"><ul><li><div>' . $success_message . '</li></ul></div>';

			header( 'Content-type: text/html; charset=UTF-8' );
			$allowed_output_html = array(
				'div'   => array(
					'class' => array(),
				),
				'ul' => array(),
				'li' => array(),
			);
			echo wp_kses( $output_html, $allowed_output_html );
		}

		wp_die();

	}

	/** ==================================================
	 * Import Files Callback
	 *
	 * @since 9.40
	 */
	public function mediafromftp_medialibraryimport_update_callback() {

		$action2 = 'mediafromftp-import-ajax-action';
		if ( check_ajax_referer( $action2, 'nonce', false ) ) {
			if ( current_user_can( 'upload_files' ) ) {
				if ( ! empty( $_POST['file'] ) ) {
					$file = sanitize_text_field( wp_unslash( $_POST['file'] ) );
				} else {
					return;
				}
				$filepath = str_replace( $this->upload_dir . '/', '', $file );
				if ( is_file( $file ) ) {
					if ( ! empty( $_POST['db_array'] ) ) {
						$db_array = filter_var(
							wp_unslash( $_POST['db_array'] ),
							FILTER_CALLBACK,
							[
								'options' => function( $value ) {
									return sanitize_text_field( $value );
								},
							]
						);
						$db_id = intval( $db_array['ID'] );
						global $wpdb;
						$table_name   = $wpdb->prefix . 'posts';
						$wpdb->insert( $table_name, $db_array );
						update_attached_file( $db_id, $filepath );
						if ( ! empty( $_POST['db_wp_attachment_metadata'] ) ) {
							$metadata_json   = sanitize_text_field( wp_unslash( $_POST['db_wp_attachment_metadata'] ) );
							$metadata        = json_decode( $metadata_json );
							$table_meta_name = $wpdb->prefix . 'postmeta';
							$db_meta_array   = array(
								'post_id'    => $db_id,
								'meta_key'   => '_wp_attachment_metadata',
								'meta_value' => $metadata,
							);
							$wpdb->insert( $table_meta_name, $db_meta_array );
						}
						if ( ! empty( $_POST['db_thumbnail_id'] ) ) {
							update_post_meta( $db_id, '_thumbnail_id', intval( $_POST['db_thumbnail_id'] ) );
						}
						if ( ! empty( $_POST['db_cover_hash'] ) ) {
							update_post_meta( $db_id, '_cover_hash', sanitize_text_field( wp_unslash( $_POST['db_cover_hash'] ) ) );
						}
						if ( ! empty( $_POST['db_wp_attachment_image_alt'] ) ) {
							update_post_meta( $db_id, '_wp_attachment_image_alt', sanitize_text_field( wp_unslash( $_POST['db_wp_attachment_image_alt'] ) ) );
						}
						$msg = 'success_db';
						$output_html = $msg . ',<div>' . __( 'Media' ) . ': <a href="' . get_permalink( $db_id ) . '" target="_blank" style="text-decoration: none; color: green;">' . $this->esc_title( $db_array['post_title'] ) . '</a>: <a href="' . $this->upload_url . '/' . $filepath . '" target="_blank" style="text-decoration: none;">' . $filepath . '</a></div>';
					} else {
						$msg = 'success';
						$output_html = $msg . ',<div>' . __( 'Thumbnail' ) . ': <a href="' . $this->upload_url . '/' . $filepath . '" target="_blank" style="text-decoration: none;">' . $filepath . '</a></div>';
					}
				} else {
					$error_string = __( 'No file!', 'media-from-ftp' );
					$msg = '<div>' . $filepath . ': ' . $error_string . '</div>';
					$output_html = $msg . ',<div>' . $filepath . '<span style="color: red;"> &#8811; ' . $error_string . '</span></div>';
				}

				header( 'Content-type: text/html; charset=UTF-8' );
				$allowed_output_html = array(
					'a'   => array(
						'href' => array(),
						'target' => array(),
						'style' => array(),
					),
					'div'   => array(),
					'span'   => array(
						'class' => array(),
					),
				);
				echo wp_kses( $output_html, $allowed_output_html );
			}
		} else {
			status_header( '403' );
			echo 'Forbidden';
		}

		wp_die();

	}

	/** ==================================================
	 * Import Messages Callback
	 *
	 * @since 9.40
	 */
	public function mediafromftp_medialibraryimport_message_callback() {

		$action4 = 'mediafromftp_medialibraryimport_message';
		if ( check_ajax_referer( $action4, 'nonce', false ) ) {
			$error_count = 0;
			$error_update = null;
			$success_count = 0;
			$db_success_count = 0;
			if ( ! empty( $_POST['error_count'] ) ) {
				$error_count = intval( $_POST['error_count'] );
			}
			if ( ! empty( $_POST['error_update'] ) ) {
				$error_update = sanitize_text_field( wp_unslash( $_POST['error_update'] ) );
			}
			if ( ! empty( $_POST['success_count'] ) ) {
				$success_count = intval( $_POST['success_count'] );
			}
			if ( ! empty( $_POST['db_success_count'] ) ) {
				$db_success_count = intval( $_POST['db_success_count'] );
			}

			$output_html = null;
			if ( $error_count > 0 ) {
				/* translators: error message */
				$error_message = sprintf( __( 'Errored to the registration of %1$d files.', 'media-from-ftp' ), $error_count );
				$output_html .= '<div class="notice notice-error is-dismissible"><ul><li><div>' . $error_message . '</div>' . $error_update . '</li></ul></div>';
			}
			/* translators: success message */
			$success_message = sprintf( __( 'Succeeded to the registration of %1$d files and %2$d items for MediaLibrary.', 'media-from-ftp' ), $success_count, $db_success_count );
			$output_html .= '<div class="notice notice-success is-dismissible"><ul><li><div>' . $success_message . '</li></ul></div>';

			header( 'Content-type: text/html; charset=UTF-8' );
			$allowed_output_html = array(
				'div'   => array(
					'class' => array(),
				),
				'ul' => array(),
				'li' => array(),
			);
			echo wp_kses( $output_html, $allowed_output_html );
		}

		wp_die();

	}

	/** ==================================================
	 * Escape Title
	 *
	 * @param string $str  str.
	 * @return string $str
	 * @since   9.41
	 */
	private function esc_title( $str ) {

		$str = esc_html( $str );
		$str = str_replace( ',', '&#44;', $str );

		return $str;
	}

	/** ==================================================
	 * Options name
	 *
	 * @return string $this->wp_options_name()
	 * @since 10.09
	 */
	private function wp_options_name() {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include_once( ABSPATH . 'wp-includes/pluggable.php' );
		}
		return 'mediafromftp_settings_' . get_current_user_id();
	}

}


