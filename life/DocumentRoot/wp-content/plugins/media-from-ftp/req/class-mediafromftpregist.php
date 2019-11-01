<?php
/**
 * Media from FTP
 *
 * @package    Media from FTP
 * @subpackage MediaFromFtpRegist registered in the database
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

$mediafromftpregist = new MediaFromFtpRegist();

/** ==================================================
 * Register Database
 */
class MediaFromFtpRegist {

	/** ==================================================
	 * Path
	 *
	 * @var $upload_path  upload_path.
	 */
	private $upload_path;

	/** ==================================================
	 * Construct
	 *
	 * @since 9.81
	 */
	public function __construct() {

		$plugin_base_dir = untrailingslashit( plugin_dir_path( __DIR__ ) );

		if ( ! class_exists( 'MediaFromFtp' ) ) {
			include_once $plugin_base_dir . '/inc/class-mediafromftp.php';
		}
		$mediafromftp = new MediaFromFtp();
		list($upload_dir, $upload_url, $this->upload_path) = $mediafromftp->upload_dir_url_path();
		$plugin_tmp_dir = $upload_dir . '/media-from-ftp-tmp';
		/* Make tmp dir */
		if ( ! is_dir( $plugin_tmp_dir ) ) {
			wp_mkdir_p( $plugin_tmp_dir );
		}

		register_activation_hook( $plugin_base_dir . '/mediafromftp.php', array( $this, 'log_settings' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

	}

	/** ==================================================
	 * Settings Log Settings
	 *
	 * @since 10.05
	 */
	public function log_settings() {

		if ( ! is_multisite() ) {
			$this->log_write();
		} else { /* For Multisite */
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$original_blog_id = get_current_blog_id();
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->log_write();
			}
			switch_to_blog( $original_blog_id );
		}

	}

	/** ==================================================
	 * Settings Log Write
	 *
	 * @since 9.19
	 */
	private function log_write() {

		$mediafromftp_log_db_version = '3.0';
		$installed_ver = get_option( 'mediafromftp_log_version' );

		if ( $installed_ver != $mediafromftp_log_db_version ) {
			global $wpdb;
			$wpdb->log_name = $wpdb->prefix . 'mediafromftp_log';
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			if ( ! $installed_ver ) {
				/* from version 9.57 */
				$sql = 'CREATE TABLE ' . $wpdb->log_name . " (
				meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				id bigint(20),
				user text,
				title text,
				permalink text,
				url text,
				filename text,
				time datetime,
				filetype text,
				filesize text,
				exif text,
				length text,
				thumbnail longtext,
				mlccategories longtext,
				emlcategories longtext,
				mlacategories longtext,
				mlatags longtext,
				UNIQUE KEY meta_id (meta_id)
				)
				CHARACTER SET 'utf8';";
				dbDelta( $sql );
			} else {
				$table_search = $wpdb->get_row( "SHOW TABLES FROM DB_NAME LIKE $wpdb->log_name" );
				if ( 1 == $wpdb->num_rows ) { /* db_version 1.0, 2.0 */
					$records = $wpdb->get_results( "SELECT * FROM $wpdb->log_name" );
					$wpdb->query( "DELETE FROM $wpdb->log_name" );

					$wpdb->query( "ALTER TABLE $wpdb->log_name DROP thumbnail1, DROP thumbnail2, DROP thumbnail3, DROP thumbnail4, DROP thumbnail5, DROP thumbnail6" );
					$wpdb->query( "ALTER TABLE $wpdb->log_name ADD thumbnail longtext" );
					$wpdb->query( "ALTER TABLE $wpdb->log_name ADD mlccategories longtext" );
					$wpdb->query( "ALTER TABLE $wpdb->log_name ADD emlcategories longtext" );
					$wpdb->query( "ALTER TABLE $wpdb->log_name ADD mlacategories longtext" );
					$wpdb->query( "ALTER TABLE $wpdb->log_name ADD mlatags longtext" );

					foreach ( $records as $record ) {
						$thumbnail = null;
						$thumbnails = array();
						if ( ! empty( $record->thumbnail1 ) ) {
							$thumbnails[0] = $record->thumbnail1; }
						if ( ! empty( $record->thumbnail2 ) ) {
							$thumbnails[1] = $record->thumbnail2; }
						if ( ! empty( $record->thumbnail3 ) ) {
							$thumbnails[2] = $record->thumbnail3; }
						if ( ! empty( $record->thumbnail4 ) ) {
							$thumbnails[3] = $record->thumbnail4; }
						if ( ! empty( $record->thumbnail5 ) ) {
							$thumbnails[4] = $record->thumbnail5; }
						if ( ! empty( $record->thumbnail6 ) ) {
							$thumbnails[5] = $record->thumbnail6; }
						if ( ! empty( $thumbnails ) ) {
							$thumbnail = json_encode( $thumbnails );
							$thumbnail = str_replace( '\\', '', $thumbnail );
						} else {
							$thumbnail = $record->thumbnail;
						}

						$log_arr = array(
							'id' => $record->id,
							'user' => $record->user,
							'title' => $record->title,
							'permalink' => $record->permalink,
							'url' => $record->url,
							'filename' => $record->filename,
							'time' => $record->time,
							'filetype' => $record->filetype,
							'filesize' => $record->filesize,
							'exif' => $record->exif,
							'length' => $record->length,
							'thumbnail' => $thumbnail,
							'mlccategories' => null,
							'emlcategories' => null,
							'mlacategories' => null,
							'mlatags' => null,
						);
						$wpdb->insert( $wpdb->log_name, $log_arr );
						$wpdb->show_errors();
					}
				}
			}
			update_option( 'mediafromftp_log_version', $mediafromftp_log_db_version );
		}

	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 2.3
	 */
	public function register_settings() {

		$user = wp_get_current_user();
		$cron_mail = $user->user_email;
		$cron_user = $user->ID;

		$wp_options_name = 'mediafromftp_settings_' . $cron_user;
		$wp_cron_events_name = 'mediafromftp_add_on_wpcron_events_' . $cron_user;

		$pagemax = 20;
		$basedir = $this->upload_path;
		$searchdir = $this->upload_path;
		$ext2typefilter = 'all';
		$extfilter = 'all';
		$search_display_metadata = true;
		$dateset = 'new';
		if ( function_exists( 'wp_date' ) ) {
			$datefixed = wp_date( 'Y-m-d H:i' );
		} else {
			$datefixed = date_i18n( 'Y-m-d H:i' );
		}
		$datetimepicker = 1;
		$max_execution_time = 300;
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' && get_locale() === 'ja' ) { /* Japanese Windows */
			$character_code = 'CP932';
		} else {
			$character_code = 'UTF-8';
		}
		$exclude = '(.ktai.)|(.backwpup_log.)|(.ps_auto_sitemap.)|\.php|\.js|(.wpcf7_captcha.)|\.htaccess|(.woocommerce_uploads.)|(.wc-logs.)';
		$recursive_search = true;
		$thumb_deep_search = false;
		$search_limit_number = 100000;
		$cron_apply = false;
		$cron_schedule = 'hourly';
		$cron_limit_number = false;
		$cron_mail_apply = true;

		$caption_apply = false;
		$exif_text = '%title% %credit% %camera% %caption% %created_timestamp% %copyright% %aperture% %shutter_speed% %iso% %focal_length% %white_balance%';
		$log = false;

		/* for media-from-ftp-add-on-category */
		$mlcc = null;
		$emlc = null;
		$mlac = null;
		$mlat = null;

		/* for media-from-ftp-add-on-wpcron */
		if ( ! get_option( 'mediafromftp_event_intervals' ) ) {
			update_option( 'mediafromftp_event_intervals', array() );
		}
		if ( ! get_option( $wp_cron_events_name ) ) {
			update_option( $wp_cron_events_name, array() );
		}

		/* << version 2.35 */
		if ( get_option( 'mediafromftp_exclude_file' ) ) {
			$exclude = get_option( 'mediafromftp_exclude_file' );
			delete_option( 'mediafromftp_exclude_file' );
		}

		if ( ! get_option( $wp_options_name ) ) {
			if ( get_option( 'mediafromftp_settings' ) ) { /* old settings */
				$mediafromftp_settings = get_option( 'mediafromftp_settings' );
				if ( array_key_exists( 'pagemax', $mediafromftp_settings ) ) {
					$pagemax = $mediafromftp_settings['pagemax'];
				}
				if ( array_key_exists( 'basedir', $mediafromftp_settings ) ) {
					$basedir = $mediafromftp_settings['basedir'];
				}
				if ( array_key_exists( 'searchdir', $mediafromftp_settings ) ) {
					$searchdir = $mediafromftp_settings['searchdir'];
				}
				if ( array_key_exists( 'ext2typefilter', $mediafromftp_settings ) ) {
					$ext2typefilter = $mediafromftp_settings['ext2typefilter'];
				}
				if ( array_key_exists( 'extfilter', $mediafromftp_settings ) ) {
					$extfilter = $mediafromftp_settings['extfilter'];
				}
				if ( array_key_exists( 'search_display_metadata', $mediafromftp_settings ) ) {
					$search_display_metadata = $mediafromftp_settings['search_display_metadata'];
				}
				if ( array_key_exists( 'dateset', $mediafromftp_settings ) ) {
					$dateset = $mediafromftp_settings['dateset'];
				}
				if ( array_key_exists( 'datefixed', $mediafromftp_settings ) ) {
					$datefixed = $mediafromftp_settings['datefixed'];
				}
				if ( array_key_exists( 'datetimepicker', $mediafromftp_settings ) ) {
					$datetimepicker = $mediafromftp_settings['datetimepicker'];
				}
				if ( array_key_exists( 'max_execution_time', $mediafromftp_settings ) ) {
					$max_execution_time = $mediafromftp_settings['max_execution_time'];
				}
				if ( array_key_exists( 'character_code', $mediafromftp_settings ) ) {
					$character_code = $mediafromftp_settings['character_code'];
				}
				if ( array_key_exists( 'exclude', $mediafromftp_settings ) ) {
					$exclude = $mediafromftp_settings['exclude'];
				}
				if ( array_key_exists( 'recursive_search', $mediafromftp_settings ) ) {
					$recursive_search = $mediafromftp_settings['recursive_search'];
				}
				if ( array_key_exists( 'thumb_deep_search', $mediafromftp_settings ) ) {
					$thumb_deep_search = $mediafromftp_settings['thumb_deep_search'];
				}
				if ( array_key_exists( 'search_limit_number', $mediafromftp_settings ) ) {
					$search_limit_number = $mediafromftp_settings['search_limit_number'];
				}
				if ( array_key_exists( 'apply', $mediafromftp_settings['cron'] ) ) {
					$cron_apply = $mediafromftp_settings['cron']['apply'];
				}
				if ( array_key_exists( 'schedule', $mediafromftp_settings['cron'] ) ) {
					$cron_schedule = $mediafromftp_settings['cron']['schedule'];
				}
				if ( array_key_exists( 'limit_number', $mediafromftp_settings['cron'] ) ) {
					$cron_limit_number = $mediafromftp_settings['cron']['limit_number'];
				}
				if ( array_key_exists( 'mail_apply', $mediafromftp_settings['cron'] ) ) {
					$cron_mail_apply = $mediafromftp_settings['cron']['mail_apply'];
				}
				if ( array_key_exists( 'apply', $mediafromftp_settings['caption'] ) ) {
					$caption_apply = $mediafromftp_settings['caption']['apply'];
				}
				if ( array_key_exists( 'exif_text', $mediafromftp_settings['caption'] ) ) {
					$exif_text = $mediafromftp_settings['caption']['exif_text'];
				}
				if ( array_key_exists( 'log', $mediafromftp_settings ) ) {
					$log = $mediafromftp_settings['log'];
				}

				/* for media-from-ftp-add-on-category */
				if ( array_key_exists( 'mlcc', $mediafromftp_settings ) ) {
					$mlcc = $mediafromftp_settings['mlcc'];
				}
				if ( array_key_exists( 'emlc', $mediafromftp_settings ) ) {
					$emlc = $mediafromftp_settings['emlc'];
				}
				if ( array_key_exists( 'mlac', $mediafromftp_settings ) ) {
					$mlac = $mediafromftp_settings['mlac'];
				}
				if ( array_key_exists( 'mlat', $mediafromftp_settings ) ) {
					$mlat = $mediafromftp_settings['mlat'];
				}

				delete_option( 'mediafromftp_settings' );
			}
		} else {
			$mediafromftp_settings = get_option( $wp_options_name );
			if ( array_key_exists( 'pagemax', $mediafromftp_settings ) ) {
				$pagemax = $mediafromftp_settings['pagemax'];
			}
			if ( array_key_exists( 'basedir', $mediafromftp_settings ) ) {
				$basedir = $mediafromftp_settings['basedir'];
			}
			if ( array_key_exists( 'searchdir', $mediafromftp_settings ) ) {
				$searchdir = $mediafromftp_settings['searchdir'];
			}
			if ( array_key_exists( 'ext2typefilter', $mediafromftp_settings ) ) {
				$ext2typefilter = $mediafromftp_settings['ext2typefilter'];
			}
			if ( array_key_exists( 'extfilter', $mediafromftp_settings ) ) {
				$extfilter = $mediafromftp_settings['extfilter'];
			}
			if ( array_key_exists( 'search_display_metadata', $mediafromftp_settings ) ) {
				$search_display_metadata = $mediafromftp_settings['search_display_metadata'];
			}
			if ( array_key_exists( 'dateset', $mediafromftp_settings ) ) {
				$dateset = $mediafromftp_settings['dateset'];
			}
			if ( array_key_exists( 'datefixed', $mediafromftp_settings ) ) {
				$datefixed = $mediafromftp_settings['datefixed'];
			}
			if ( array_key_exists( 'datetimepicker', $mediafromftp_settings ) ) {
				$datetimepicker = $mediafromftp_settings['datetimepicker'];
			}
			if ( array_key_exists( 'max_execution_time', $mediafromftp_settings ) ) {
				$max_execution_time = $mediafromftp_settings['max_execution_time'];
			}
			if ( array_key_exists( 'character_code', $mediafromftp_settings ) ) {
				$character_code = $mediafromftp_settings['character_code'];
			}
			if ( array_key_exists( 'exclude', $mediafromftp_settings ) ) {
				$exclude = $mediafromftp_settings['exclude'];
			}
			if ( array_key_exists( 'recursive_search', $mediafromftp_settings ) ) {
				$recursive_search = $mediafromftp_settings['recursive_search'];
			}
			if ( array_key_exists( 'thumb_deep_search', $mediafromftp_settings ) ) {
				$thumb_deep_search = $mediafromftp_settings['thumb_deep_search'];
			}
			if ( array_key_exists( 'search_limit_number', $mediafromftp_settings ) ) {
				$search_limit_number = $mediafromftp_settings['search_limit_number'];
			}
			if ( array_key_exists( 'apply', $mediafromftp_settings['cron'] ) ) {
				$cron_apply = $mediafromftp_settings['cron']['apply'];
			}
			if ( array_key_exists( 'schedule', $mediafromftp_settings['cron'] ) ) {
				$cron_schedule = $mediafromftp_settings['cron']['schedule'];
			}
			if ( array_key_exists( 'limit_number', $mediafromftp_settings['cron'] ) ) {
				$cron_limit_number = $mediafromftp_settings['cron']['limit_number'];
			}
			if ( array_key_exists( 'mail_apply', $mediafromftp_settings['cron'] ) ) {
				$cron_mail_apply = $mediafromftp_settings['cron']['mail_apply'];
			}
			if ( array_key_exists( 'apply', $mediafromftp_settings['caption'] ) ) {
				$caption_apply = $mediafromftp_settings['caption']['apply'];
			}
			if ( array_key_exists( 'exif_text', $mediafromftp_settings['caption'] ) ) {
				$exif_text = $mediafromftp_settings['caption']['exif_text'];
			}
			if ( array_key_exists( 'log', $mediafromftp_settings ) ) {
				$log = $mediafromftp_settings['log'];
			}
			/* for media-from-ftp-add-on-category */
			if ( array_key_exists( 'mlcc', $mediafromftp_settings ) ) {
				$mlcc = $mediafromftp_settings['mlcc'];
			}
			if ( array_key_exists( 'emlc', $mediafromftp_settings ) ) {
				$emlc = $mediafromftp_settings['emlc'];
			}
			if ( array_key_exists( 'mlac', $mediafromftp_settings ) ) {
				$mlac = $mediafromftp_settings['mlac'];
			}
			if ( array_key_exists( 'mlat', $mediafromftp_settings ) ) {
				$mlat = $mediafromftp_settings['mlat'];
			}
		}

		$mediafromftp_tbl = array(
			'pagemax' => $pagemax,
			'basedir' => $basedir,
			'searchdir' => $searchdir,
			'ext2typefilter' => $ext2typefilter,
			'extfilter' => $extfilter,
			'search_display_metadata' => $search_display_metadata,
			'dateset' => $dateset,
			'datefixed' => $datefixed,
			'datetimepicker' => $datetimepicker,
			'max_execution_time' => $max_execution_time,
			'character_code' => $character_code,
			'exclude' => $exclude,
			'recursive_search' => $recursive_search,
			'thumb_deep_search' => $thumb_deep_search,
			'search_limit_number' => $search_limit_number,
			'cron' => array(
				'apply' => $cron_apply,
				'schedule' => $cron_schedule,
				'limit_number' => $cron_limit_number,
				'mail_apply' => $cron_mail_apply,
				'mail' => $cron_mail,
				'user' => $cron_user,
			),
			'caption' => array(
				'apply' => $caption_apply,
				'exif_text' => $exif_text,
			),
			'log' => $log,
			'mlcc' => $mlcc,
			'emlc' => $emlc,
			'mlac' => $mlac,
			'mlat' => $mlat,
		);
		update_option( $wp_options_name, $mediafromftp_tbl );

	}

}


