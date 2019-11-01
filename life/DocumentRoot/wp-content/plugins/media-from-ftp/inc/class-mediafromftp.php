<?php
/**
 * Media from FTP
 *
 * @package    Media from FTP
 * @subpackage MediaFromFtp Main Functions
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

/** ==================================================
 * Main Functions
 */
class MediaFromFtp {

	/** ==================================================
	 * Path
	 *
	 * @var $plugin_dir  plugin_dir.
	 */
	private $plugin_dir;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_dir  upload_dir.
	 */
	private $upload_dir;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_url  upload_url.
	 */
	private $upload_url;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_path  upload_path.
	 */
	private $upload_path;

	/** ==================================================
	 * Path
	 *
	 * @var $plugin_tmp_url  plugin_tmp_url.
	 */
	private $plugin_tmp_url;

	/** ==================================================
	 * Path
	 *
	 * @var $plugin_tmp_dir  plugin_tmp_dir.
	 */
	private $plugin_tmp_dir;

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
		$this->plugin_dir = untrailingslashit( rtrim( $plugin_base_dir, $slug ) );

		list($this->upload_dir, $this->upload_url, $this->upload_path) = $this->upload_dir_url_path();
		$this->plugin_tmp_url = $this->upload_url . '/media-from-ftp-tmp';
		$this->plugin_tmp_dir = $this->upload_dir . '/media-from-ftp-tmp';

		$exif_active = false;
		if ( function_exists( 'media_from_ftp_add_on_exif_load_textdomain' ) ) {
			$exif_active = true;
		}

		$cli_active = false;
		if ( function_exists( 'media_from_ftp_add_on_cli_load_textdomain' ) ) {
			$cli_active = true;
		}

		$this->is_add_on_activate = array(
			'exif'  => $exif_active,
			'cli'   => $cli_active,
		);

	}

	/** ==================================================
	 * Scan file
	 *
	 * @param string $dir  dir.
	 * @param string $extpattern  extpattern.
	 * @param array  $mediafromftp_settings  mediafromftp_settings.
	 * @return array $list
	 * @since 1.0
	 */
	public function scan_file( $dir, $extpattern, $mediafromftp_settings ) {

		/* for media-from-ftp-add-on-wpcron and mediafromftpcmd.php */
		$cmdoptions = array();
		if ( $this->is_add_on_activate['cli'] ) {
			$cmdoptions = getopt( 's:d:a:e:t:x:p:f:c:i:b:r:y:hgmo' );
		}

		if ( isset( $cmdoptions['f'] ) ) {
			$search_limit_number = $cmdoptions['f'];
		} else {
			$search_limit_number = $mediafromftp_settings['search_limit_number'];
		}

		$thumb_deep_search = false;
		if ( isset( $cmdoptions['m'] ) ) {
			$thumb_deep_search = true;
		} else {
			$thumb_deep_search = $mediafromftp_settings['thumb_deep_search'];
		}
		if ( $thumb_deep_search ) {
			$excludefile = 'media-from-ftp-tmp';    /* tmp dir file */
		} else {
			$excludefile = '-[0-9]+x[0-9]+\.|media-from-ftp-tmp';   /* thumbnail & tmp dir file */
		}

		$recursive_search = true;
		if ( isset( $cmdoptions['o'] ) ) {
			$recursive_search = false;
		} else {
			$recursive_search = $mediafromftp_settings['recursive_search'];
		}

		global $blog_id;
		if ( is_multisite() && is_main_site( $blog_id ) ) {
			$excludefile .= '|\/sites\/';
		}
		if ( isset( $cmdoptions['e'] ) ) {
				$excludefile .= '|' . $cmdoptions['e'];
		} else {
			if ( ! empty( $mediafromftp_settings['exclude'] ) ) {
				$excludefile .= '|' . $mediafromftp_settings['exclude'];
			}
		}

		$ext2typefilter = $mediafromftp_settings['ext2typefilter'];
		if ( isset( $cmdoptions['t'] ) ) {
			$ext2typefilter = $cmdoptions['t'];
		} else {
			if ( isset( $_POST['ext2type'] ) && ! empty( $_POST['ext2type'] ) ) {
				if ( check_admin_referer( 'mff_search', 'media_from_ftp_search' ) ) {
					$ext2typefilter = sanitize_text_field( wp_unslash( $_POST['ext2type'] ) );
				}
			}
		}

		unset( $cmdoptions );

		$searchtext = '.*';
		if ( isset( $_POST['searchtext'] ) && ! empty( $_POST['searchtext'] ) ) {
			if ( check_admin_referer( 'mff_search', 'media_from_ftp_search' ) ) {
				$searchtext = $this->mb_encode_multibyte( sanitize_text_field( wp_unslash( $_POST['searchtext'] ) ), $mediafromftp_settings['character_code'] );
			}
		} elseif ( isset( $_GET['searchtext'] ) && ! empty( $_GET['searchtext'] ) ) {
			if ( check_admin_referer( 'mff_search', 'media_from_ftp_search' ) ) {
				$searchtext = $this->mb_encode_multibyte( sanitize_text_field( wp_unslash( $_GET['searchtext'] ) ), $mediafromftp_settings['character_code'] );
			}
		}

		$files = scandir( $dir );
		$list = array();
		$count = 0;
		foreach ( $files as $file ) {
			if ( '.' == $file || '..' == $file ) {
				continue;
			}
			$fullpath = rtrim( $dir, '/' ) . '/' . $file;
			if ( is_file( $fullpath ) ) {
				if ( ! preg_match( '/' . $excludefile . '/', $fullpath ) ) {
					$exts = explode( '.', $file );
					$ext = end( $exts );
					$searchflag = @preg_match( '/' . $searchtext . '/', $fullpath );
					if ( ! $searchflag ) {
						/* for "preg_match error: Compilation failed: missing terminating ] for character class" */
						$searchflag = preg_match( '/' . preg_quote( $searchtext, '/' ) . '/', $fullpath );
					}
					if ( $searchflag ) {
						if ( preg_match( '/' . $extpattern . '/', $ext ) ) {
							if ( wp_ext2type( $ext ) === $ext2typefilter || 'all' === $ext2typefilter ) {
								++$count;
								if ( $count > $search_limit_number ) {
									break;
								}
								$list[] = $fullpath;
							}
						}
					}
				}
			}
			if ( is_dir( $fullpath ) && $recursive_search ) {
				$list = array_merge( $list, $this->scan_file( $fullpath, $extpattern, $mediafromftp_settings ) );
			}
		}

		return $list;

	}

	/** ==================================================
	 * Scan directory
	 *
	 * @param string $dir  dir.
	 * @return array $dirlist
	 * @since 2.1
	 */
	private function scan_dir( $dir ) {

		$excludedir = 'media-from-ftp-tmp'; /* tmp dir */
		global $blog_id;
		if ( is_multisite() && is_main_site( $blog_id ) ) {
			$excludedir .= '|\/sites\/';
		}

		$files = scandir( $dir );
		$list = array();
		foreach ( $files as $file ) {
			if ( '.' == $file || '..' == $file ) {
				continue;
			}
			$fullpath = rtrim( $dir, '/' ) . '/' . $file;
			if ( is_dir( $fullpath ) ) {
				if ( ! preg_match( '/' . $excludedir . '/', $fullpath ) ) {
					$list[] = $fullpath;
				}
				$list = array_merge( $list, $this->scan_dir( $fullpath ) );
			}
		}

		arsort( $list );
		return $list;

	}

	/** ==================================================
	 * Extention filter
	 *
	 * @param string $extfilter  extfilter.
	 * @return string $extpattern
	 * @since 2.2
	 */
	public function extpattern( $extfilter ) {

		$extpattern = null;

		if ( 'all' === $extfilter ) {
			global $user_ID;
			$mimes = get_allowed_mime_types( $user_ID );
			foreach ( $mimes as $ext => $mime ) {
				$extpattern .= $ext . '|' . strtoupper( $ext ) . '|';
			}
			$extpattern = substr( $extpattern, 0, -1 );
		} else {
			$extpattern = $extfilter . '|' . strtoupper( $extfilter );
		}

		return $extpattern;

	}

	/** ==================================================
	 * Mime type
	 *
	 * @param string $suffix  suffix.
	 * @return string $mimetype
	 * @since 1.0
	 */
	private function mime_type( $suffix ) {

		$suffix = str_replace( '.', '', $suffix );

		global $user_ID;
		$mimes = get_allowed_mime_types( $user_ID );

		foreach ( $mimes as $ext => $mime ) {
			if ( preg_match( '/' . $ext . '/i', $suffix ) ) {
				$mimetype = $mime;
			}
		}

		return $mimetype;

	}

	/** ==================================================
	 * Create cache
	 *
	 * @param string $ext  ext.
	 * @param string $file  file.
	 * @param string $new_url  new_url.
	 * @return string $view_thumb_url
	 * @since 2.36
	 */
	private function create_cash( $ext, $file, $new_url ) {

		$cash_thumb_key = md5( $new_url );

		$ext = strtolower( $ext );

		if ( 'pdf' === $ext ) {
			$cash_thumb_filename = $this->plugin_tmp_dir . '/' . $cash_thumb_key . '-pdf.jpg';
		} else {
			$cash_thumb_filename = $this->plugin_tmp_dir . '/' . $cash_thumb_key . '.' . $ext;
		}

		$value_cash = get_transient( $cash_thumb_key );
		if ( false <> $value_cash ) {
			if ( ! file_exists( $cash_thumb_filename ) ) {
				delete_transient( $cash_thumb_key );
				$value_cash = false;
			}
		}
		if ( ! $value_cash ) {
			$filetype2 = wp_ext2type( $ext );
			if ( empty( $filetype2 ) ) {
				$filetype2 = 'default';
			}
			if ( ! file_exists( $cash_thumb_filename ) ) {
				$cash_thumb = wp_get_image_editor( $file );
				if ( ! is_wp_error( $cash_thumb ) ) {
					if ( 'pdf' === $ext ) {
						$cash_thumb->generate_filename( 'image', null, 'jpg' );
						$cash_thumb->save( $cash_thumb_filename );
						$cash_thumb2 = wp_get_image_editor( $cash_thumb_filename );
						if ( ! is_wp_error( $cash_thumb2 ) ) {
							$cash_thumb2->resize( 40, 40, true );
							$cash_thumb2->save( $cash_thumb_filename );
							$view_thumb_url = $this->plugin_tmp_url . '/' . $cash_thumb_key . '-pdf.jpg';
						} else {
							$view_thumb_url = $this->siteurl() . '/' . WPINC . '/images/media/' . $filetype2 . '.png';
						}
					} else {
						$cash_thumb->resize( 40, 40, true );
						$cash_thumb->save( $cash_thumb_filename );
						$view_thumb_url = $this->plugin_tmp_url . '/' . $cash_thumb_key . '.' . $ext;
					}
				} else {
					$view_thumb_url = $this->siteurl() . '/' . WPINC . '/images/media/' . $filetype2 . '.png';
				}
			} else {
				if ( file_exists( $cash_thumb_filename ) ) {
					$view_thumb_url = $this->plugin_tmp_url . '/' . $cash_thumb_key . '.' . $ext;
				} else {
					$view_thumb_url = $this->siteurl() . '/' . WPINC . '/images/media/' . $filetype2 . '.png';
				}
			}
			set_transient( $cash_thumb_key, $view_thumb_url, DAY_IN_SECONDS );
		} else {
			$view_thumb_url = $value_cash;
			set_transient( $cash_thumb_key, $value_cash, DAY_IN_SECONDS );
		}

		return $view_thumb_url;

	}

	/** ==================================================
	 * Delete cache
	 *
	 * @param string $ext  ext.
	 * @param string $new_url_attach  new_url_attach.
	 * @since 2.36
	 */
	public function delete_cash( $ext, $new_url_attach ) {

		$ext = strtolower( $ext );

		if ( 'image' === wp_ext2type( $ext ) || 'pdf' === $ext ) {
			$del_cash_thumb_key = md5( $new_url_attach );
			if ( 'pdf' === strtolower( $ext ) ) {
				$del_cash_thumb_filename = $this->plugin_tmp_dir . '/' . $del_cash_thumb_key . '-pdf.jpg';
			} else {
				$del_cash_thumb_filename = $this->plugin_tmp_dir . '/' . $del_cash_thumb_key . '.' . $ext;
			}
			$value_del_cash = get_transient( $del_cash_thumb_key );
			if ( false <> $value_del_cash ) {
				delete_transient( $del_cash_thumb_key );
				if ( file_exists( $del_cash_thumb_filename ) ) {
					unlink( $del_cash_thumb_filename );
				}
			}
		}

	}

	/** ==================================================
	 * Delete all cache
	 *
	 * @return int $del_cash_count(int)
	 * @since 7.5
	 */
	public function delete_all_cash() {

		global $wpdb;
		$del_transients = $wpdb->get_results(
			$wpdb->prepare(
				"
						SELECT	option_value
						FROM	$wpdb->options
						WHERE	option_value LIKE %s
						",
				'%' . $wpdb->esc_like( $this->plugin_tmp_url ) . '%'
			)
		);

		$del_cash_count = 0;
		foreach ( $del_transients as $del_transient ) {
			$delfile = pathinfo( $del_transient->option_value );
			$del_cash_thumb_key = $delfile['filename'];
			$value_del_cash = get_transient( $del_cash_thumb_key );
			if ( false <> $value_del_cash ) {
				delete_transient( $del_cash_thumb_key );
				++$del_cash_count;
			}
		}

		$del_cash_thumb_filename = $this->plugin_tmp_dir . '/*.*';
		foreach ( glob( $del_cash_thumb_filename ) as $val ) {
			unlink( $val );
			++$del_cash_count;
		}

		return $del_cash_count;

	}

	/** ==================================================
	 * Date check for exif
	 *
	 * @param string $file  file.
	 * @param string $dateset  dateset.
	 * @return string $date
	 * @since 2.36
	 */
	public function get_date_check( $file, $dateset ) {

		$date = get_date_from_gmt( date( 'Y-m-d H:i:s', filemtime( $file ) ) );

		if ( 'exif' === $dateset ) {
			$exifdata = @exif_read_data( $file, FILE, true );
			if ( isset( $exifdata['EXIF']['DateTimeOriginal'] ) && ! empty( $exifdata['EXIF']['DateTimeOriginal'] ) ) {
				$shooting_date_time = $exifdata['EXIF']['DateTimeOriginal'];
				$shooting_date = str_replace( ':', '-', substr( $shooting_date_time, 0, 10 ) );
				$shooting_time = substr( $shooting_date_time, 10 );
				$date = $shooting_date . $shooting_time;
			}
		}

		$date = substr( $date, 0, strlen( $date ) - 3 );

		return $date;

	}

	/** ==================================================
	 * Input url
	 *
	 * @param string $file  file.
	 * @param array  $attachments  attachments.
	 * @param string $character_code  character_code.
	 * @param bool   $thumb_deep_search  thumb_deep_search.
	 * @return array $new_file(bool), $ext(string), $new_url(string)
	 * @since 2.36
	 */
	public function input_url( $file, $attachments, $character_code, $thumb_deep_search ) {

		$ext = null;
		$new_url = null;

		if ( is_dir( $file ) ) { /* dirctory */
			$new_file = false;
		} else {
			$exts = explode( '.', wp_basename( $file ) );
			$ext = end( $exts );
			$suffix_file = '.' . $ext;
			$file = wp_normalize_path( $file );
			$upload_path = wp_normalize_path( $this->upload_dir );
			$new_url = $this->upload_url . str_replace( $upload_path, '', $file );
			$new_titles = explode( '/', $new_url );
			$new_title = str_replace( $suffix_file, '', end( $new_titles ) );
			$new_title_md5 = md5( $new_title );
			$new_url_md5 = str_replace( $new_title . $suffix_file, '', $new_url ) . $new_title_md5 . $suffix_file;
			$new_file = true;
			$new_url = $this->mb_utf8( $new_url, $character_code );
			foreach ( $attachments as $attachment ) {
				$attach_url = $this->upload_url . '/' . get_post_meta( $attachment->ID, '_wp_attached_file', true );
				$pdf_thumb_url = rtrim( $attach_url, '.pdf' ) . '-pdf.jpg'; /* for pdf thumbnail by imagick */
				if ( $attach_url === $new_url || $attach_url === $new_url_md5 || $pdf_thumb_url === $new_url || $pdf_thumb_url === $new_url_md5 ) {
					$new_file = false;
				} else {
					if ( $thumb_deep_search ) {
						$exts_attach_url = explode( '.', wp_basename( $attach_url ) );
						$delete_ext = '.' . end( $exts_attach_url );
						$attach_url_base = rtrim( $attach_url, $delete_ext );
						if ( strstr( $new_url, $attach_url_base ) || strstr( $new_url_md5, $attach_url_base ) ) {
							$thumb_pattern = '-[0-9]+x[0-9]+\.';
							if ( preg_match( '/' . $thumb_pattern . '/', $new_url ) || preg_match( '/' . $thumb_pattern . '/', $new_url_md5 ) ) {
								$new_file = false;
							}
						}
					}
				}
			}
		}

		return array( $new_file, $ext, $new_url );

	}

	/** ==================================================
	 * Input html
	 *
	 * @param string $ext  ext.
	 * @param string $file  file.
	 * @param string $new_url  new_url.
	 * @param string $postcount  postcount.
	 * @param array  $mediafromftp_settings  mediafromftp_settings.
	 * @return array $input_html(string), $date_time_html(string)
	 * @since 9.30
	 */
	public function input_html( $ext, $file, $new_url, $postcount, $mediafromftp_settings ) {

		$input_html = null;

		if ( $mediafromftp_settings['search_display_metadata'] ) {
			$file_size = size_format( filesize( $file ) );
			$mimetype = $ext . '(' . $this->mime_type( $ext ) . ')';
			if ( 'image' === wp_ext2type( $ext ) || 'pdf' === strtolower( $ext ) ) {
				$view_thumb_url = $this->create_cash( $ext, $file, $new_url );
			} else if ( 'audio' === wp_ext2type( $ext ) ) {
				$view_thumb_url = $this->siteurl() . '/' . WPINC . '/images/media/audio.png';
				$metadata_audio = wp_read_audio_metadata( $file );
				$file_size = size_format( $metadata_audio['filesize'] );
				$mimetype = $metadata_audio['fileformat'] . '(' . $metadata_audio['mime_type'] . ')';
				$length = $metadata_audio['length_formatted'];
			} else if ( 'video' === wp_ext2type( $ext ) ) {
				$view_thumb_url = $this->siteurl() . '/' . WPINC . '/images/media/video.png';
				$metadata_video = wp_read_video_metadata( $file );
				$file_size = size_format( $metadata_video['filesize'] );
				$mimetype = $metadata_video['fileformat'] . '(' . $metadata_video['mime_type'] . ')';
				$length = $metadata_video['length_formatted'];
			} else {
				$filetype2 = wp_ext2type( $ext );
				if ( empty( $filetype2 ) ) {
					$filetype2 = 'default'; }
				$view_thumb_url = $this->siteurl() . '/' . WPINC . '/images/media/' . $filetype2 . '.png';
			}
			$input_html .= '<img width="40" height="40" src="' . $view_thumb_url . '" style="float: left; margin: 5px;">';
		}

		$input_html .= '<div style="overflow: hidden;">';
		$input_html .= '<div><a href="' . $new_url . '" target="_blank" style="text-decoration: none; word-break: break-all;">' . $new_url . '</a></div>';

		if ( $mediafromftp_settings['search_display_metadata'] ) {
			$input_html .= '<div>' . __( 'File type:' ) . ' ' . $mimetype . '</div>';
			$input_html .= '<div>' . __( 'File size:' ) . ' ' . $file_size . '</div>';
			if ( 'audio' === wp_ext2type( $ext ) || 'video' === wp_ext2type( $ext ) ) {
				$input_html .= '<div>' . __( 'Length:' ) . ' ' . $length . '</div>';
			}
		}
		$input_html .= '</div>';

		$date_time_html = null;
		$date = $this->get_date_check( $file, $mediafromftp_settings['dateset'] );
		if ( 'new' === $mediafromftp_settings['dateset'] || 'fixed' === $mediafromftp_settings['dateset'] ) {
			$input_html .= '<input type="hidden" name="new_url_attaches[' . $postcount . '][datetime]" value="' . $date . '" form="mediafromftp_ajax_update" >';
		} else {
			$date_time_html .= '<input type="text" id="datetimepicker-mediafromftp' . $postcount . '" name="new_url_attaches[' . $postcount . '][datetime]" value="' . $date . '" form="mediafromftp_ajax_update" style="width: 160px;">';
		}

		return array( $input_html, $date_time_html );

	}

	/** ==================================================
	 * Search Option html
	 *
	 * @param array $mediafromftp_settings  mediafromftp_settings.
	 * @return string $html
	 * @since 9.98
	 */
	public function search_option_html( $mediafromftp_settings ) {

		$html = '<div class="item-mediafromftp-settings">';
		$html .= '<h3>' . __( 'Pagination' ) . '</h3>';
		$html .= '<label>' . __( 'Number of items per page:' ) . '</label>';
		$html .= '<input type="number" step="1" min="1" max="999" class="screen-per-page" name="mediafromftp_pagemax" maxlength="3" value="' . $mediafromftp_settings['pagemax'] . '">';
		$html .= '</div>';

		$html .= '<div class="item-mediafromftp-settings">';
		$html .= '<h3>' . __( 'Display of search results', 'media-from-ftp' ) . '</h3>';
		$html .= '<div style="display: block;padding:5px 5px">';
		if ( true == $mediafromftp_settings['search_display_metadata'] ) {
			$html .= '<input type="radio" name="search_display_metadata" value="1" checked>';
		} else {
			$html .= '<input type="radio" name="search_display_metadata" value="1">';
		}
		$html .= __( 'Usual selection. It is user-friendly. It displays a thumbnail and metadata. It is low speed.', 'media-from-ftp' );
		$html .= '</div>';
		$html .= '<div style="display: block;padding:5px 5px">';
		if ( false == $mediafromftp_settings['search_display_metadata'] ) {
			$html .= '<input type="radio" name="search_display_metadata" value="0" checked>';
		} else {
			$html .= '<input type="radio" name="search_display_metadata" value="0">';
		}
		$html .= __( 'Unusual selection. Only the file name and output. It is suitable for the search of large amounts of data. It is hi speed.', 'media-from-ftp' );
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="item-mediafromftp-settings">';
		$html .= '<h3>' . __( 'Exclude file', 'media-from-ftp' ) . '</h3>';
		$html .= '<div style="display: block;padding:5px 5px">';
		$html .= '<div>' . __( 'Regular expression is possible.', 'media-from-ftp' ) . '</div>';
		$html .= '<textarea id="mediafromftp_exclude" name="mediafromftp_exclude" rows="3" style="width: 100%;">' . $mediafromftp_settings['exclude'] . '</textarea>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="item-mediafromftp-settings">';
		$html .= '<h3>' . __( 'Search method of files', 'media-from-ftp' ) . '</h3>';
		$html .= '<div style="display: block;padding:5px 5px">';
		if ( true == $mediafromftp_settings['recursive_search'] ) {
			$html .= '<input type="radio" name="mediafromftp_recursive_search" value="1" checked>';
		} else {
			$html .= '<input type="radio" name="mediafromftp_recursive_search" value="1">';
		}
		$html .= __( 'Recursively search files below specified directory.', 'media-from-ftp' );
		$html .= '</div>';
		$html .= '<div style="display: block;padding:5px 5px">';
		if ( false == $mediafromftp_settings['recursive_search'] ) {
			$html .= '<input type="radio" name="mediafromftp_recursive_search" value="0" checked>';
		} else {
			$html .= '<input type="radio" name="mediafromftp_recursive_search" value="0">';
		}
		$html .= __( 'Search files only for specified directories.', 'media-from-ftp' );
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="item-mediafromftp-settings">';
		$html .= '<h3>' . __( 'Search method for the exclusion of the thumbnail', 'media-from-ftp' ) . '</h3>';
		$html .= '<div style="display: block;padding:5px 5px">';
		if ( false == $mediafromftp_settings['thumb_deep_search'] ) {
			$html .= '<input type="radio" name="mediafromftp_thumb_deep_search" value="0" checked>';
		} else {
			$html .= '<input type="radio" name="mediafromftp_thumb_deep_search" value="0">';
		}
		$html .= __( 'Usual selection. It is hi speed.', 'media-from-ftp' );
		$html .= '</div>';
		$html .= '<div style="display: block;padding:5px 5px">';
		if ( true == $mediafromftp_settings['thumb_deep_search'] ) {
			$html .= '<input type="radio" name="mediafromftp_thumb_deep_search" value="1" checked>';
		} else {
			$html .= '<input type="radio" name="mediafromftp_thumb_deep_search" value="1">';
		}
		$html .= __( 'Unusual selection. if you want to search for filename that contains such -0x0. It is low speed.', 'media-from-ftp' );
		$html .= '</div>';
		$html .= '</div>';

		return $html;

	}

	/** ==================================================
	 * Directory select box
	 *
	 * @param string $searchdir  searchdir.
	 * @param string $character_code  character_code.
	 * @param string $wordpress_path  wordpress_path.
	 * @return string $linkselectbox
	 * @since 9.98
	 */
	public function dir_select_box( $searchdir, $character_code, $wordpress_path ) {

		$linkselectbox = null;
		$linkselectbox .= '<div style="font-size: small; font-weight: bold;"><code>' . $wordpress_path . '</code></div>';
		$linkselectbox .= '<select name="searchdir" style="width: 250px">';

		$dirs = $this->scan_dir( $this->upload_dir );
		foreach ( $dirs as $linkdir ) {
			if ( strstr( $linkdir, $wordpress_path ) ) {
				$linkdirenc = $this->mb_utf8( str_replace( $wordpress_path, '', $linkdir ), $character_code );
			} else {
				$linkdirenc = $this->upload_path . $this->mb_utf8( str_replace( $this->upload_dir, '', $linkdir ), $character_code );
			}
			if ( $searchdir === $linkdirenc ) {
				$linkdirs = '<option value="' . urlencode( $linkdirenc ) . '" selected>' . $linkdirenc . '</option>';
			} else {
				$linkdirs = '<option value="' . urlencode( $linkdirenc ) . '">' . $linkdirenc . '</option>';
			}
			$linkselectbox = $linkselectbox . $linkdirs;
		}
		if ( $searchdir === $this->upload_path ) {
			$linkdirs = '<option value="' . urlencode( $this->upload_path ) . '" selected>' . $this->upload_path . '</option>';
		} else {
			$linkdirs = '<option value="' . urlencode( $this->upload_path ) . '">' . $this->upload_path . '</option>';
		}
		$linkselectbox = $linkselectbox . $linkdirs . '</select>';

		return $linkselectbox;

	}

	/** ==================================================
	 * Type select box
	 *
	 * @param string $ext2typefilter  ext2typefilter.
	 * @param string $extfilter  extfilter.
	 * @return string $typeextselectbox
	 * @since 10.01
	 */
	public function type_ext_select_box( $ext2typefilter, $extfilter ) {

		$typeextselectbox = null;
		$typeextselectbox .= '<select name="ext2type" style="width: 110px;">';
		$typeextselectbox .= '<option value="all"';
		if ( 'all' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>' . esc_attr( __( 'All types', 'media-from-ftp' ) ) . '</option>';
		$typeextselectbox .= '<option value="image"';
		if ( 'image' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>image</option>';
		$typeextselectbox .= '<option value="audio"';
		if ( 'audio' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>audio</option>';
		$typeextselectbox .= '<option value="video"';
		if ( 'video' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>video</option>';
		$typeextselectbox .= '<option value="document"';
		if ( 'document' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>document</option>';
		$typeextselectbox .= '<option value="spreadsheet"';
		if ( 'spreadsheet' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>spreadsheet</option>';
		$typeextselectbox .= '<option value="interactive"';
		if ( 'interactive' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>interactive</option>';
		$typeextselectbox .= '<option value="text"';
		if ( 'text' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>text</option>';
		$typeextselectbox .= '<option value="archive"';
		if ( 'archive' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>archive</option>';
		$typeextselectbox .= '<option value="code"';
		if ( 'code' === $ext2typefilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>code</option>';
		$typeextselectbox .= '</select>';
		$typeextselectbox .= '<select name="extension" style="width: 120px;">';
		$typeextselectbox .= '<option value="all"';
		if ( 'all' === $extfilter ) {
			$typeextselectbox .= ' selected';
		}
		$typeextselectbox .= '>' . esc_attr( __( 'All extensions', 'media-from-ftp' ) ) . '</option>';

		$extensions = $this->scan_extensions( $ext2typefilter );
		foreach ( $extensions as $extselect ) {
			$typeextselectbox .= '<option value="' . $extselect . '"';
			if ( $extfilter === $extselect ) {
				$typeextselectbox .= ' selected';
			}
			$typeextselectbox .= '>' . $extselect . '</option>';
		}
		$typeextselectbox .= '</select>';

		return $typeextselectbox;

	}

	/** ==================================================
	 * Form html
	 *
	 * @param array $mediafromftp_settings  mediafromftp_settings.
	 * @since 9.50
	 */
	public function form_html( $mediafromftp_settings ) {

		$scriptname = admin_url( 'admin.php?page=mediafromftp-search-register' );

		$searchtext = null;
		if ( isset( $_POST['searchtext'] ) && ! empty( $_POST['searchtext'] ) ) {
			if ( check_admin_referer( 'mff_search', 'media_from_ftp_search' ) ) {
				$searchtext = sanitize_text_field( wp_unslash( $_POST['searchtext'] ) );
			}
		} elseif ( isset( $_GET['searchtext'] ) && ! empty( $_GET['searchtext'] ) ) {
			if ( check_admin_referer( 'mff_search', 'media_from_ftp_search' ) ) {
				$searchtext = sanitize_text_field( wp_unslash( $_GET['searchtext'] ) );
			}
		}

		$pagemax = $mediafromftp_settings['pagemax'];
		$searchdir = $mediafromftp_settings['searchdir'];
		$character_code = $mediafromftp_settings['character_code'];
		$ext2typefilter = $mediafromftp_settings['ext2typefilter'];
		$extfilter = $mediafromftp_settings['extfilter'];
		$wordpress_path = wp_normalize_path( ABSPATH );

		$linkselectbox = $this->dir_select_box( $searchdir, $character_code, $wordpress_path );
		$linkselectbox2 = $this->type_ext_select_box( $ext2typefilter, $extfilter );

		$allowed_select_html = array(
			'div' => array(
				'style' => array(),
			),
			'code' => array(),
			'select' => array(
				'name' => array(),
				'style' => array(),
			),
			'option' => array(
				'value' => array(),
				'selected' => array(),
			),
		);
		?>
		<div class="wp-filter" style="margin: 0px;"> <!-- wp-admin/css/list-tables.css -->
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
			<?php wp_nonce_field( 'mff_search', 'media_from_ftp_search' ); ?>
				<?php echo wp_kses( $linkselectbox, $allowed_select_html ); ?>
				<?php submit_button( __( 'Search' ), 'large', '', false ); ?>
				<span style="margin-right: 1em;"></span>
				<?php echo wp_kses( $linkselectbox2, $allowed_select_html ); ?>
				<?php
				if ( empty( $searchtext ) ) {
					?>
					<input name="searchtext" type="text" value="" placeholder="<?php echo esc_attr__( 'Search' ); ?>">
					<?php
				} else {
					?>
					<input name="searchtext" type="text" value="" placeholder="<?php echo esc_attr( $searchtext ); ?>">
					<?php
				}
				submit_button( __( 'Filter' ), 'large', '', false );
				?>
		</form>
		</div>
		<?php

	}

	/** ==================================================
	 * Regist
	 *
	 * @param string $filename  filename.
	 * @param string $ext  ext.
	 * @param string $new_url_attach  .
	 * @param string $new_url_datetime  .
	 * @param string $dateset  dateset.
	 * @param string $datefixed  datefixed.
	 * @param bool   $yearmonth_folders  yearmonth_folders.
	 * @param string $character_code  character_code.
	 * @param string $cron_user  cron_user.
	 * @return array $attach_id(int), $new_attach_title(string), $new_url_attach(string), $metadata(array)
	 * @since 2.36
	 */
	public function regist( $filename, $ext, $new_url_attach, $new_url_datetime, $dateset, $datefixed, $yearmonth_folders, $character_code, $cron_user ) {

		/* Rename and Move file */
		$suffix_attach_file = '.' . $ext;
		$new_attach_titlenames = explode( '/', $new_url_attach );
		$new_attach_title = str_replace( $suffix_attach_file, '', end( $new_attach_titlenames ) );
		/* for utf8mb4 charcter */
		$new_attach_title = $this->utf8mb4_html_numeric_encode( $new_attach_title, $character_code );

		$upload_path = wp_normalize_path( $this->upload_dir );

		$err_copy = true;
		$copy_file_org1 = null;
		$copy_file_org2 = null;
		$copy_file_org3 = null;
		$copy_file_new1 = null;
		$copy_file_new2 = null;
		$copy_file_new3 = null;
		if ( function_exists( 'wp_date' ) ) {
			$postdategmt = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
		} else {
			$postdategmt = date_i18n( 'Y-m-d H:i:s', false, true );
		}
		if ( 'server' === $dateset || 'exif' === $dateset ) {
			$postdategmt = get_gmt_from_date( $new_url_datetime . ':00' );
		}

		$path_parts = pathinfo( $filename );
		$currentdir = $path_parts['dirname'];

		/* sanitize */
		$orgfilename = $filename;
		$filename = $currentdir . '/' . sanitize_file_name( wp_basename( $filename ) );
		rename( $orgfilename, $filename );

		if ( function_exists( 'mb_check_encoding' ) ) {
			if ( ! mb_check_encoding( $filename, 'ASCII' ) ) {
				$currentfile = wp_basename( $filename, $suffix_attach_file );
				$oldfilename = $currentdir . '/' . $currentfile . $suffix_attach_file;
				$filename = $currentdir . '/' . md5( $currentfile ) . $suffix_attach_file;
				$copy_file_org2 = $this->mb_encode_multibyte( $oldfilename, $character_code );
				$copy_file_new2 = $this->mb_encode_multibyte( $filename, $character_code );
				$err_copy = @copy( $copy_file_org2, $copy_file_new2 );
				if ( ! $err_copy ) {
					if ( ! empty( $copy_file_new1 ) ) {
						$copy_file_org2 = $copy_file_org1;
						unlink( $copy_file_new1 );
					}
					return array( -1, $this->mb_utf8( $copy_file_org2, $character_code ), $this->mb_utf8( $currentdir, $character_code ), null );
				}
			}
		}

		/* Move YearMonth Folders */
		if ( 1 == $yearmonth_folders ) {
			$y = substr( $postdategmt, 0, 4 );
			$m = substr( $postdategmt, 5, 2 );
			$subdir = "/$y/$m";
			$filename_base = wp_basename( $filename );
			if ( $filename <> $this->upload_dir . $subdir . '/' . $filename_base ) {
				if ( ! file_exists( $this->mb_encode_multibyte( $this->upload_dir . $subdir, $character_code ) ) ) {
					wp_mkdir_p( $this->mb_encode_multibyte( $this->upload_dir . $subdir, $character_code ) );
				}
				if ( file_exists( $this->mb_encode_multibyte( $this->upload_dir . $subdir . '/' . $filename_base, $character_code ) ) ) {
					if ( function_exists( 'wp_date' ) ) {
						$regist_date = wp_date( 'dHis' );
					} else {
						$regist_date = date_i18n( 'dHis' );
					}
					$filename_base = wp_basename( $filename, $suffix_attach_file ) . $regist_date . $suffix_attach_file;
				}
				$copy_file_org3 = $this->mb_encode_multibyte( $filename, $character_code );
				$copy_file_new3 = $this->mb_encode_multibyte( $this->upload_dir . $subdir . '/' . $filename_base, $character_code );
				$err_copy = @copy( $copy_file_org3, $copy_file_new3 );
				if ( ! $err_copy ) {
					if ( ! empty( $copy_file_new1 ) ) {
						$copy_file_org3 = $copy_file_org1;
						unlink( $copy_file_new1 );
					} else if ( ! empty( $copy_file_new2 ) ) {
						$copy_file_org3 = $copy_file_org2;
						unlink( $copy_file_new2 );
					}
					return array( -1, $this->mb_utf8( $copy_file_org3, $character_code ), $this->mb_utf8( $this->upload_dir . $subdir, $character_code ), null );
				}
				$filename = $this->upload_dir . $subdir . '/' . $filename_base;
			}
		}

		$new_url_attach = $this->mb_utf8( $this->upload_url . str_replace( $upload_path, '', $filename ), $character_code );
		$filename = $this->mb_utf8( $filename, $character_code );

		/* File Regist */
		$newfile_post = array(
			'post_title' => $new_attach_title,
			'post_content' => '',
			'post_author' => $cron_user,
			'guid' => $new_url_attach,
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => $this->mime_type( $suffix_attach_file ),
		);
		$attach_id = wp_insert_attachment( $newfile_post, $filename );

		if ( 0 == $attach_id ) { /* error */
			if ( ! empty( $copy_file_new1 ) ) {
				unlink( $copy_file_new1 ); }
			if ( ! empty( $copy_file_new2 ) ) {
				unlink( $copy_file_new2 ); }
			if ( ! empty( $copy_file_new3 ) ) {
				unlink( $copy_file_new3 ); }
			return array( -2, $new_attach_title, $new_url_attach, null );
		} else {
			if ( ! empty( $copy_file_org1 ) ) {
				unlink( $copy_file_org1 ); }
			if ( ! empty( $copy_file_org2 ) ) {
				unlink( $copy_file_org2 ); }
			if ( ! empty( $copy_file_org3 ) ) {
				unlink( $copy_file_org3 ); }
		}

		/* Date Time Regist */
		if ( 'new' <> $dateset ) {
			if ( 'fixed' === $dateset ) {
				$postdategmt = get_gmt_from_date( $datefixed . ':00' );
			}
			$postdate = get_date_from_gmt( $postdategmt );
			$up_post = array(
				'ID' => $attach_id,
				'post_date' => $postdate,
				'post_date_gmt' => $postdategmt,
				'post_modified' => $postdate,
				'post_modified_gmt' => $postdategmt,
			);
			wp_update_post( $up_post );
		}

		/* for wp_read_audio_metadata and wp_read_video_metadata */
		include_once( ABSPATH . 'wp-admin/includes/media.php' );
		/* for wp_generate_attachment_metadata */
		include_once( ABSPATH . 'wp-admin/includes/image.php' );

		/* Meta data Regist */
		$metadata = null;
		$fullpath_filename = $this->mb_encode_multibyte( get_attached_file( $attach_id ), $character_code );
		if ( 'image' === wp_ext2type( $ext ) || 'pdf' === strtolower( $ext ) ) {
			$metadata = wp_generate_attachment_metadata( $attach_id, $fullpath_filename );
		} else if ( 'video' === wp_ext2type( $ext ) ) {
			$metadata = wp_read_video_metadata( $fullpath_filename );
		} else if ( 'audio' === wp_ext2type( $ext ) ) {
			$metadata = wp_read_audio_metadata( $fullpath_filename );
		}
		wp_update_attachment_metadata( $attach_id, $metadata );

		return array( $attach_id, $new_attach_title, $new_url_attach, $metadata );

	}

	/** ==================================================
	 * Output metadata
	 *
	 * @param string $ext  ext.
	 * @param int    $attach_id  attach_id.
	 * @param array  $metadata  metadata.
	 * @param string $character_code  character_code.
	 * @param string $exif_text_tag  exif_text_tag.
	 * @return array $imagethumburls(string), $mimetype(string), $length(string), $stamptime(string), $file_size(string), $exif_text(string)
	 * @since 7.4
	 */
	public function output_metadata( $ext, $attach_id, $metadata, $character_code, $exif_text_tag ) {

		$imagethumburls = array();
		$mimetype = null;
		$length = null;
		$exif_text = null;
		$filetype = wp_check_filetype( $this->mb_encode_multibyte( get_attached_file( $attach_id ), $character_code ) );
		if ( 'image' === wp_ext2type( $ext ) || 'pdf' === strtolower( $ext ) ) {
			$wp_attached_file = get_post_meta( $attach_id, '_wp_attached_file', true );
			$imagethumburl_base = $this->upload_url . '/' . rtrim( $wp_attached_file, wp_basename( $wp_attached_file ) );
			if ( ! empty( $metadata ) ) {
				foreach ( $metadata as $key1 => $key2 ) {
					if ( 'sizes' === $key1 ) {
						foreach ( $metadata[ $key1 ] as $key2 => $key3 ) {
							$imagethumburls[ $key2 ] = $imagethumburl_base . $metadata['sizes'][ $key2 ]['file'];
						}
					}
				}
			}
			$mimetype = $filetype['ext'] . '(' . $filetype['type'] . ')';
		} else if ( 'video' === wp_ext2type( $ext ) || 'audio' === wp_ext2type( $ext ) ) {
			$mimetype = $metadata['fileformat'] . '(' . $metadata['mime_type'] . ')';
			$length = $metadata['length_formatted'];
		} else {
			$metadata = null;
			$mimetype = $filetype['ext'] . '(' . $filetype['type'] . ')';
		}

		$stamptime = get_the_time( 'Y-n-j ', $attach_id ) . get_the_time( 'G:i', $attach_id );
		if ( isset( $metadata['filesize'] ) ) {
			$file_size = $metadata['filesize'];
		} else {
			$file_size = @filesize( $this->mb_encode_multibyte( get_attached_file( $attach_id ), $character_code ) );
		}

		if ( $this->is_add_on_activate['exif'] ) {
			if ( 'image/jpeg' === $filetype['type'] || 'image/tiff' === $filetype['type'] ) {
				if ( ! empty( $exif_text_tag ) ) {
					include_once $this->plugin_dir . '/media-from-ftp-add-on-exif/inc/MediaFromFtpAddOnExif.php';
					$mediafromftpaddonexif = new MediaFromFtpAddOnExif();
					$exif_text = $mediafromftpaddonexif->exifcaption( $attach_id, $metadata, $exif_text_tag );
					unset( $mediafromftpaddonexif );
				}
			}
		}

		return array( $imagethumburls, $mimetype, $length, $stamptime, $file_size, $exif_text );

	}

	/** ==================================================
	 * Output html and log
	 *
	 * @param string $ext  ext.
	 * @param int    $attach_id  attach_id.
	 * @param string $new_attach_title  new_attach_title.
	 * @param string $new_url_attach  new_url_attach.
	 * @param array  $imagethumburls  imagethumburls.
	 * @param string $mimetype  mimetype.
	 * @param string $length  length.
	 * @param string $stamptime  stamptime.
	 * @param string $file_size  file_size.
	 * @param string $exif_text  exif_text.
	 * @param array  $image_attr_thumbnail  image_attr_thumbnail.
	 * @param array  $mediafromftp_settings  mediafromftp_settings.
	 * @param string $cat_html  cat_html.
	 * @param string $mlccategory  mlccategory.
	 * @param string $emlcategory  emlcategory.
	 * @param string $mlacategory  mlacategory.
	 * @param string $mlatag  mlatag.
	 * @return string $output_html
	 * @since 9.30
	 */
	public function output_html_and_log( $ext, $attach_id, $new_attach_title, $new_url_attach, $imagethumburls, $mimetype, $length, $stamptime, $file_size, $exif_text, $image_attr_thumbnail, $mediafromftp_settings, $cat_html, $mlccategory, $emlcategory, $mlacategory, $mlatag ) {

		$thumbnails = array();

		$output_html = '<div style="border-bottom: 1px solid; padding-top: 5px; padding-bottom: 5px;">';
		$output_html .= '<img width="40" height="40" src="' . $image_attr_thumbnail[0] . '" style="float: left; margin: 5px;">';
		$output_html .= '<div style="overflow: hidden;">';
		$output_html .= '<div>ID: ' . $attach_id . '</div>';
		$output_html .= '<div>' . __( 'Title' ) . ': ' . $new_attach_title . '</div>';
		$output_html .= '<div>' . __( 'Permalink:' ) . ' <a href="' . get_attachment_link( $attach_id ) . '" target="_blank" style="text-decoration: none; word-break: break-all;">' . get_attachment_link( $attach_id ) . '</a></div>';
		$output_html .= '<div>URL: <a href="' . $new_url_attach . '" target="_blank" style="text-decoration: none; word-break: break-all;">' . $new_url_attach . '</a></div>';
		$new_url_attachs = explode( '/', $new_url_attach );
		$output_html .= '<div>' . __( 'File name:' ) . ' ' . end( $new_url_attachs ) . '</div>';

		$output_html .= '<div>' . __( 'Date/Time' ) . ': ' . $stamptime . '</div>';
		if ( ! $file_size ) {
			$file_size = '<font color="red">' . __( 'Could not retrieve.', 'media-from-ftp' ) . '</font>';
		} else {
			$file_size = size_format( $file_size );
		}
		$output_html .= '<div>' . __( 'File type:' ) . ' ' . $mimetype . '</div>';
		$output_html .= '<div>' . __( 'File size:' ) . ' ' . $file_size . '</div>';
		if ( 'image' === wp_ext2type( $ext ) || ( 'pdf' === strtolower( $ext ) && ! empty( $imagethumburls ) ) ) {
			$output_html .= '<div>' . __( 'Images' ) . ': ';
			$thumb_count = 0;
			foreach ( $imagethumburls as $thumbsize => $imagethumburl ) {
				$output_html .= '[<a href="' . $imagethumburl . '" target="_blank" style="text-decoration: none; word-break: break-all;">' . $thumbsize . '</a>]';
				++$thumb_count;
				$thumbnails[ $thumb_count ] = $imagethumburl;
			}
			$output_html .= '</div>';
			if ( ! empty( $exif_text ) ) {
				$output_html .= '<div>' . __( 'Caption' ) . '[Exif]: ' . $exif_text . '</div>';
			}
		} else {
			if ( 'video' === wp_ext2type( $ext ) || 'audio' === wp_ext2type( $ext ) ) {
				$output_html .= '<div>' . __( 'Length:' ) . ' ' . $length . '</div>';
			}
		}
		if ( ! empty( $cat_html ) ) {
			$output_html .= $cat_html;
		}
		$output_html .= '</div></div>';

		if ( $mediafromftp_settings['log'] ) {
			global $wpdb;
			$user = wp_get_current_user();
			$thumbnail = json_encode( $thumbnails );
			$thumbnail = str_replace( '\\', '', $thumbnail );
			/* Log */
			$log_arr = array(
				'id' => $attach_id,
				'user' => $user->display_name,
				'title' => $new_attach_title,
				'permalink' => get_attachment_link( $attach_id ),
				'url' => $new_url_attach,
				'filename' => end( $new_url_attachs ),
				'time' => $stamptime,
				'filetype' => $mimetype,
				'filesize' => $file_size,
				'exif' => $exif_text,
				'length' => $length,
				'thumbnail' => $thumbnail,
				'mlccategories' => $mlccategory,
				'emlcategories' => $emlcategory,
				'mlacategories' => $mlacategory,
				'mlatags' => $mlatag,
			);
			$table_name = $wpdb->prefix . 'mediafromftp_log';
			$wpdb->insert( $table_name, $log_arr );
			$wpdb->show_errors();
		}

		return $output_html;

	}

	/** ==================================================
	 * Real Url
	 *
	 * @param  string $base  base.
	 * @param  string $relationalpath relationalpath.
	 * @return string $realurl realurl.
	 * @since  7.7
	 */
	private function realurl( $base, $relationalpath ) {

		$parse = array(
			'scheme'   => null,
			'user'     => null,
			'pass'     => null,
			'host'     => null,
			'port'     => null,
			'query'    => null,
			'fragment' => null,
		);
		$parse = wp_parse_url( $base );

		if ( strpos( $parse['path'], '/', ( strlen( $parse['path'] ) - 1 ) ) !== false ) {
			$parse['path'] .= '.';
		}

		if ( preg_match( '#^https?://#', $relationalpath ) ) {
			return $relationalpath;
		} elseif ( preg_match( '#^/.*$#', $relationalpath ) ) {
			return $parse['scheme'] . '://' . $parse['host'] . $relationalpath;
		} else {
			$base_path = explode( '/', dirname( $parse['path'] ) );
			$rel_path  = explode( '/', $relationalpath );
			foreach ( $rel_path as $rel_dir_name ) {
				if ( '.' === $rel_dir_name ) {
					array_shift( $base_path );
					array_unshift( $base_path, '' );
				} elseif ( '..' === $rel_dir_name ) {
					array_pop( $base_path );
					if ( count( $base_path ) === 0 ) {
						$base_path = array( '' );
					}
				} else {
					array_push( $base_path, $rel_dir_name );
				}
			}
			$path = implode( '/', $base_path );
			return $parse['scheme'] . '://' . $parse['host'] . $path;
		}

	}

	/** ==================================================
	 * Upload Path
	 *
	 * @return array $upload_dir,$upload_url,$upload_path  uploadpath.
	 * @since 7.8
	 */
	public function upload_dir_url_path() {

		$wp_uploads = wp_upload_dir();

		$relation_path_true = strpos( $wp_uploads['baseurl'], '../' );
		if ( $relation_path_true > 0 ) {
			$relationalpath = substr( $wp_uploads['baseurl'], $relation_path_true );
			$basepath       = substr( $wp_uploads['baseurl'], 0, $relation_path_true );
			$upload_url     = $this->realurl( $basepath, $relationalpath );
			$upload_dir     = wp_normalize_path( realpath( $wp_uploads['basedir'] ) );
		} else {
			$upload_url = $wp_uploads['baseurl'];
			$upload_dir = wp_normalize_path( $wp_uploads['basedir'] );
		}

		if ( is_ssl() ) {
			$upload_url = str_replace( 'http:', 'https:', $upload_url );
		}

		if ( $relation_path_true > 0 ) {
			$upload_path = $relationalpath;
		} else {
			$upload_path = str_replace( site_url( '/' ), '', $upload_url );
		}

		$upload_dir  = untrailingslashit( $upload_dir );
		$upload_url  = untrailingslashit( $upload_url );
		$upload_path = untrailingslashit( $upload_path );

		return array( $upload_dir, $upload_url, $upload_path );

	}

	/** ==================================================
	 * Site url
	 *
	 * @return $siteurl
	 * @since 8.5
	 */
	public function siteurl() {
		if ( is_multisite() ) {
			global $blog_id;
			$siteurl = get_blog_details( $blog_id )->siteurl;
		} else {
			$siteurl = site_url();
		}
		return $siteurl;
	}

	/** ==================================================
	 * Scan extensions
	 *
	 * @param string $ext2typefilter  ext2typefilter.
	 * @return array $extensions
	 * @since 8.2
	 */
	private function scan_extensions( $ext2typefilter ) {

		$extensions = array();
		global $user_ID;
		$mimes = get_allowed_mime_types( $user_ID );

		foreach ( $mimes as $extselect => $mime ) {
			if ( strpos( $extselect, '|' ) ) {
				$extselects = explode( '|', $extselect );
				foreach ( $extselects as $extselect2 ) {
					if ( 'all' === $ext2typefilter || wp_ext2type( $extselect2 ) === $ext2typefilter ) {
						$extensions[] = $extselect2;
					}
				}
			} else {
				if ( 'all' === $ext2typefilter || wp_ext2type( $extselect ) === $ext2typefilter ) {
					$extensions[] = $extselect;
				}
			}
		}

		asort( $extensions );
		return $extensions;

	}

	/** ==================================================
	 * UTF8 mb4
	 *
	 * @param string $str  str.
	 * @param string $character_code  character_code.
	 * @return string $ret
	 * @since 9.02
	 */
	private function utf8mb4_html_numeric_encode( $str, $character_code ) {

		if ( function_exists( 'mb_language' ) && 'none' <> $character_code ) {
			$length = mb_strlen( $str, 'UTF-8' );
			$ret = '';

			for ( $i = 0; $i < $length; ++$i ) {
				$buf = mb_substr( $str, $i, 1, 'UTF-8' );

				if ( 4 === mb_strlen( $buf, '8bit' ) ) {
					$buf = mb_encode_numericentity( $buf, array( 0x10000, 0x10FFFF, 0, 0xFFFFFF ), 'UTF-8' );
				}

				$ret .= $buf;
			}
		} else {
			$ret = $str;
		}

		return $ret;

	}

	/** ==================================================
	 * Multibyte init
	 *
	 * @param string $character_code  character_code.
	 * @since 9.05
	 */
	public function mb_initialize( $character_code ) {

		if ( function_exists( 'mb_language' ) && 'none' <> $character_code ) {
			if ( 'ja' === get_locale() ) {
				mb_language( 'Japanese' );
			} else if ( 'en_US' === get_locale() ) {
				mb_language( 'English' );
			} else {
				mb_language( 'uni' );
			}
		}

	}

	/** ==================================================
	 * Multibyte encode
	 *
	 * @param string $str  str.
	 * @param string $character_code  character_code.
	 * @return string $str
	 * @since 9.10
	 */
	public function mb_encode_multibyte( $str, $character_code ) {

		if ( function_exists( 'mb_language' ) && 'none' <> $character_code ) {
			$str = mb_convert_encoding( $str, $character_code, 'auto' );
		}

		return $str;

	}

	/** ==================================================
	 * Multibyte UTF-8
	 *
	 * @param string $str  str.
	 * @param string $character_code  character_code.
	 * @return string $str
	 * @since 9.05
	 */
	public function mb_utf8( $str, $character_code ) {

		if ( function_exists( 'mb_convert_encoding' ) && 'none' <> $character_code ) {
			$str = mb_convert_encoding( $str, 'UTF-8', 'auto' );
		}

		return $str;

	}

	/** ==================================================
	 * Array replace for php < 5.3.0
	 *
	 * @return array $array
	 * @since 9.11
	 */
	private function array_replace() {

		$array = array();
		$n = func_num_args();

		while ( $n-- > 0 ) {
			$array += func_get_arg( $n );
		}
		return $array;
	}


	/** ==================================================
	 * Author select for Media Library Import
	 *
	 * @param string $filename  filename.
	 * @return array $authors
	 * @since 9.43
	 */
	public function author_select( $filename ) {

		$scriptname = admin_url( 'admin.php?page=mediafromftp-import' );

		$s = @file_get_contents( $filename );
		$control_code = array( "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\x0b", "\x0c", "\x0e", "\x0f" );
		$s = str_replace( $control_code, '', $s );
		$xml = simplexml_load_string( $s );

		$authors = array();
		$namespaces = $xml->getDocNamespaces();
		foreach ( $xml->xpath( '/rss/channel/wp:author' ) as $author_arr ) {
			$a = $author_arr->children( $namespaces['wp'] );
			$authors[] = array(
				'author_login' => (string) $a->author_login,
				'author_display_name' => (string) $a->author_display_name,
			);
		}

		$form_select = null;
		$count = 0;
		if ( current_user_can( 'manage_options' ) ) {
			$blogusers = get_users();
			foreach ( $authors as $key => $value ) {
				++$count;
				$form_select .= '<div style="display: block; padding: 5px 10px">' . $count . '.' . __( 'Import author:', 'media-from-ftp' ) . '<strong>' . $value['author_display_name'] . '(' . $value['author_login'] . ')</strong></div>';
				$form_select .= '<div style="display: block; padding: 5px 30px">' . __( 'Assign posts to an existing user:', 'media-from-ftp' ) . '<select name="' . $value['author_login'] . '">';
				$form_select .= '<option value="-1" select>' . __( 'Select' ) . '</option>';
				foreach ( $blogusers as $user ) {
					$form_select .= '<option value="' . $user->ID . '">' . $user->display_name . '(' . $user->user_login . ')</option>';
				}
				$form_select .= '</select></div>';
			}
			$current_user = wp_get_current_user();
			$current_user_html = '<strong>' . $current_user->display_name . '(' . $current_user->user_login . ')</strong>';
			/* translators: Current user */
			$form_select .= '<div style="display: block; padding: 10px 0px">' . sprintf( __( 'If not selected, assign posts to %1$s.', 'media-from-ftp' ), $current_user_html ) . '</div>';
		} else {
			$user = wp_get_current_user();
			foreach ( $authors as $key => $value ) {
				++$count;
				$form_select .= '<div style="display: block; padding: 5px 10px">' . $count . '.' . __( 'Import author:', 'media-from-ftp' ) . '<strong>' . $value['author_display_name'] . '(' . $value['author_login'] . ')</strong></div>';
				$current_user_html = '<strong>' . $user->display_name . '(' . $user->user_login . ')</strong>';
				/* translators: Current user */
				$form_select .= '<div style="display: block; padding: 5px 30px">' . sprintf( __( 'Assign posts to %1$s', 'media-from-ftp' ), $current_user_html ) . '</div>';

			}
		}
		$button_value = get_submit_button( __( 'Apply' ), 'large', 'select_author', false );
		$nonce_field = wp_nonce_field( 'mff_select_author', 'media_from_ftp_select_author' );

		$author_form = <<<MEDIAFROMFTP_AUTHOR_SELECT

<!-- BEGIN: Media from FTP Media Library Import -->
<form method="post" action="$scriptname">
$nonce_field
$form_select
<div style="display: block; padding: 20px 0px">$button_value</div>
<input type="hidden" name="mediafromftp_select_author" value="1" />
<input type="hidden" name="mediafromftp_xml_file" value="$filename" />
</form>

<!-- END: Media from FTP Media Library Import -->

MEDIAFROMFTP_AUTHOR_SELECT;

		return $author_form;

	}

	/** ==================================================
	 * Make object for Media Library Import
	 *
	 * @param string $filename  filename.
	 * @param array  $select_author  select_author.
	 * @return string $add_js
	 * @since 9.40
	 */
	public function make_object( $filename, $select_author ) {

		$s = @file_get_contents( $filename );
		$control_code = array( "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\x0b", "\x0c", "\x0e", "\x0f" );
		$s = str_replace( $control_code, '', $s );
		$xml = simplexml_load_string( $s );
		$data = array();
		foreach ( $xml->channel->item as $item ) {
			$x = array();
			$x['title'] = (string) $item->title;
			$x['link'] = (string) $item->link;
			$x['pubDate'] = (string) $item->pubDate;
			$x['creator'] = (string) $item->children( 'dc', true )->creator;
			$x['guid'] = (string) $item->guid;
			$x['guid_atr'] = (string) $item->guid->attributes()->isPermaLink;
			$x['description'] = (string) $item->description;
			$x['content_encoded'] = (string) $item->children( 'content', true )->encoded;
			$x['excerpt_encoded'] = (string) $item->children( 'excerpt', true )->encoded;
			$x['post_id'] = (int) $item->children( 'wp', true )->post_id;
			$x['post_date'] = (string) $item->children( 'wp', true )->post_date;
			$x['post_date_gmt'] = (string) $item->children( 'wp', true )->post_date_gmt;
			$x['comment_status'] = (string) $item->children( 'wp', true )->comment_status;
			$x['ping_status'] = (string) $item->children( 'wp', true )->ping_status;
			$x['post_name'] = (string) $item->children( 'wp', true )->post_name;
			$x['status'] = (string) $item->children( 'wp', true )->status;
			$x['post_parent'] = (int) $item->children( 'wp', true )->post_parent;
			$x['menu_order'] = (int) $item->children( 'wp', true )->menu_order;
			$x['post_type'] = (string) $item->children( 'wp', true )->post_type;
			$x['post_password'] = (string) $item->children( 'wp', true )->post_password;
			$x['is_sticky'] = (int) $item->children( 'wp', true )->is_sticky;
			$x['attachment_url'] = (string) $item->children( 'wp', true )->attachment_url;

			$postmeta_count = count( $item->children( 'wp', true )->postmeta );
			for ( $i = 0; $i < $postmeta_count; $i++ ) {
				$wp_postmeta_node = $item->children( 'wp', true )->postmeta[ $i ];
				$post_meta_key = (string) $wp_postmeta_node->meta_key;
				$post_meta_value = (string) $wp_postmeta_node->meta_value;
				if ( '_wp_attached_file' === $post_meta_key ) {
					$x['postmeta_wp_attached_file'] = $post_meta_key;
					$x['postmeta_wp_attached_file_value'] = $post_meta_value;
				} else if ( '_thumbnail_id' === $post_meta_key ) {
					$x['postmeta_thumbnail_id'] = $post_meta_key;
					$x['postmeta_thumbnail_id_value'] = $post_meta_value;
				} else if ( '_cover_hash' === $post_meta_key ) {
					$x['postmeta_cover_hash'] = $post_meta_key;
					$x['postmeta_cover_hash_value'] = $post_meta_value;
				} else if ( '_wp_attachment_metadata' === $post_meta_key ) {
					$x['postmeta_wp_attachment_metadata'] = $post_meta_key;
					$x['postmeta_wp_attachment_metadata_value'] = $post_meta_value;
				} else if ( '_wp_attachment_image_alt' === $post_meta_key ) {
					$x['postmeta_wp_attachment_image_alt'] = $post_meta_key;
					$x['postmeta_wp_attachment_image_alt_value'] = $post_meta_value;
				}
			}

			$data[] = $x;
		}

		$count = 0;
		$file_array = array();
		$db_array = array();
		$db_wp_attachment_metadata_array = array();
		$db_thumbnail_id_array = array();
		$db_cover_hash_array = array();
		$db_wp_attachment_image_alt_array = array();
		foreach ( $data as $key => $value ) {
			if ( 'attachment' === $value['post_type'] ) {
				$file = $this->upload_dir . '/' . $value['postmeta_wp_attached_file_value'];
				$filetype = wp_check_filetype( basename( $file ), null );

				$user = wp_get_current_user();
				$loginuser = $user->ID;
				foreach ( $select_author as $authorkey => $authorvalue ) {
					if ( $value['creator'] === $authorkey ) {
						$loginuser = $authorvalue;
					}
				}

				$guid = $this->upload_url . '/' . $value['postmeta_wp_attached_file_value'];
				$db_array[ $count ] = array(
					'ID'                        => $value['post_id'],
					'post_author'               => $loginuser,
					'post_date'                 => $value['post_date'],
					'post_date_gmt'             => $value['post_date_gmt'],
					'post_content'              => $value['content_encoded'],
					'post_title'                => $value['title'],
					'post_excerpt'              => $value['excerpt_encoded'],
					'post_status'               => $value['status'],
					'comment_status'            => $value['comment_status'],
					'ping_status'               => $value['ping_status'],
					'post_password'             => $value['post_password'],
					'post_name'                 => $value['post_name'],
					'post_parent'               => $value['post_parent'],
					'guid'                      => $guid,
					'menu_order'                => $value['menu_order'],
					'post_type'                 => $value['post_type'],
					'post_mime_type'            => $filetype['type'],
				);

				$file_array[ $count ] = $file;

				if ( array_key_exists( 'postmeta_wp_attachment_metadata_value', $value ) ) {
					$db_wp_attachment_metadata_array[ $count ] = json_encode( $value['postmeta_wp_attachment_metadata_value'] );
					if ( strrpos( $value['postmeta_wp_attached_file_value'], '/' ) ) {
						$monthdir = '/' . substr( $value['postmeta_wp_attached_file_value'], 0, strrpos( $value['postmeta_wp_attached_file_value'], '/' ) );
						$dir = $this->upload_dir . $monthdir;
					}
					$thumbnails = maybe_unserialize( $value['postmeta_wp_attachment_metadata_value'] );
					if ( is_array( $thumbnails ) ) {
						foreach ( $thumbnails as $key1 => $value1 ) {
							if ( is_array( $value1 ) ) {
								foreach ( $value1 as $key2 => $value2 ) {
									if ( is_array( $value2 ) ) {
										foreach ( $value2 as $key3 => $value3 ) {
											if ( 'file' === $key3 ) {
												$thumbnail = $dir . '/' . $value3;
												++$count;
												$file_array[ $count ] = $thumbnail;
											}
										}
									}
								}
							}
						}
					}
				}
				if ( array_key_exists( 'postmeta_thumbnail_id_value', $value ) ) {
					$db_thumbnail_id_array[ $count ] = $value['postmeta_thumbnail_id_value'];
				}
				if ( array_key_exists( 'postmeta_cover_hash_value', $value ) ) {
					$db_cover_hash_array[ $count ] = $value['postmeta_cover_hash_value'];
				}
				if ( array_key_exists( 'postmeta_wp_attachment_image_alt_value', $value ) ) {
					$db_wp_attachment_image_alt_array[ $count ] = $value['postmeta_wp_attachment_image_alt_value'];
				}

				++$count;
			}
		}

		$file_obj = json_encode( $file_array );
		$db_array_obj = json_encode( $db_array );
		$db_wp_attachment_metadata_obj = json_encode( $db_wp_attachment_metadata_array );
		$db_thumbnail_id_obj = json_encode( $db_thumbnail_id_array );
		$db_cover_hash_obj = json_encode( $db_cover_hash_array );
		$db_wp_attachment_image_alt_obj = json_encode( $db_wp_attachment_image_alt_array );

		/* JS */
		$add_js = '
			medialibraryimport_maxcount = ' . $count . ';
			medialibraryimport_file = ' . $file_obj . ';
			medialibraryimport_db_array = ' . $db_array_obj . ';
			medialibraryimport_db_wp_attachment_metadata = ' . $db_wp_attachment_metadata_obj . ';
			medialibraryimport_db_thumbnail_id = ' . $db_thumbnail_id_obj . ';
			medialibraryimport_db_cover_hash = ' . $db_cover_hash_obj . ';
			medialibraryimport_db_wp_attachment_image_alt = ' . $db_wp_attachment_image_alt_obj . ';
			';

		return $add_js;

	}

}


