<?php
/**
 * Media from FTP
 *
 * @package    Media from FTP
 * @subpackage MediafromFTPAdmin Main & Management screen
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

$mediafromftpadmin = new MediaFromFtpAdmin();
add_filter( 'plugin_action_links', array( $mediafromftpadmin, 'settings_link' ), 10, 2 );
add_action( 'admin_footer', array( $mediafromftpadmin, 'custom_bulk_admin_footer' ) );
add_action( 'admin_notices', array( $mediafromftpadmin, 'notices' ) );

/** ==================================================
 * Management screen
 */
class MediaFromFtpAdmin {

	/** ==================================================
	 * Path
	 *
	 * @var $plugin_base_url  plugin_base_url.
	 */
	private $plugin_base_url;

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
	 * Path
	 *
	 * @var $plugin_disallow_tmp_dir  plugin_disallow_tmp_dir.
	 */
	private $plugin_disallow_tmp_dir;

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
		$this->plugin_base_url = untrailingslashit( plugin_dir_url( __DIR__ ) );
		$this->plugin_dir = untrailingslashit( rtrim( $plugin_base_dir, $slug ) );

		if ( ! class_exists( 'MediaFromFtp' ) ) {
			include_once $plugin_base_dir . '/inc/class-mediafromftp.php';
		}
		if ( ! class_exists( 'TT_MediaFromFtp_List_Table' ) ) {
			require_once( $plugin_base_dir . '/req/class-tt-mediafromftp-list-table.php' );
		}
		$mediafromftp = new MediaFromFtp();
		list($this->upload_dir, $this->upload_url, $this->upload_path) = $mediafromftp->upload_dir_url_path();

		$this->plugin_tmp_url = $this->upload_url . '/media-from-ftp-tmp';
		$this->plugin_tmp_dir = $this->upload_dir . '/media-from-ftp-tmp';
		$this->plugin_disallow_tmp_dir = str_replace( home_url(), '', $mediafromftp->siteurl() ) . '/' . $this->upload_path . '/media-from-ftp-tmp/';

		$category_active = false;
		if ( function_exists( 'media_from_ftp_add_on_category_load_textdomain' ) ) {
			include_once $this->plugin_dir . '/media-from-ftp-add-on-category/inc/MediaFromFtpAddOnCategory.php';
			$category_active = true;
		}
		$exif_active = false;
		if ( function_exists( 'media_from_ftp_add_on_exif_load_textdomain' ) ) {
			include_once $this->plugin_dir . '/media-from-ftp-add-on-exif/inc/MediaFromFtpAddOnExif.php';
			$exif_active = true;
		}
		$cli_active = false;
		if ( function_exists( 'media_from_ftp_add_on_cli_load_textdomain' ) ) {
			require_once( $this->plugin_dir . '/media-from-ftp-add-on-cli/req/MediaFromFtpCli.php' );
			$cli_active = true;
		}
		$wpcron_active = false;
		if ( function_exists( 'media_from_ftp_add_on_wpcron_load_textdomain' ) ) {
			include_once $this->plugin_dir . '/media-from-ftp-add-on-wpcron/inc/MediaFromFtpAddOnWpcron.php';
			$wpcron_active = true;
		}
		$this->is_add_on_activate = array(
			'category'  => $category_active,
			'exif'      => $exif_active,
			'cli'       => $cli_active,
			'wpcron'    => $wpcron_active,
		);

		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_wp_admin_style' ) );
		add_action( 'admin_footer', array( $this, 'load_custom_wp_admin_style2' ) );
		add_action( 'screen_settings', array( $this, 'search_register_show_screen_options' ), 10, 2 );
		add_filter( 'set-screen-option', array( $this, 'search_register_set_screen_options' ), 11, 3 );
		add_filter( 'contextual_help', array( $this, 'search_register_help_tab' ), 12, 3 );
		add_filter( 'robots_txt', array( $this, 'custom_robots_txt' ), 9999 );

	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'media-from-ftp/mediafromftp.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromftp' ) . '">Media from FTP</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromftp-search-register' ) . '">' . __( 'Search & Register', 'media-from-ftp' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromftp-settings' ) . '">' . __( 'Settings' ) . '</a>';
			if ( $this->is_add_on_activate['wpcron'] ) {
				$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
				$links[] = $mediafromftpaddonwpcron->mediafromftp_settings_link_html();
				unset( $mediafromftpaddonwpcron );
			}
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromftp-log' ) . '">' . __( 'Log', 'media-from-ftp' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromftp-import' ) . '">' . __( 'Import' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromftp-addons' ) . '">' . __( 'Add-Ons', 'media-from-ftp' ) . '</a>';
		}
			return $links;
	}

	/** ==================================================
	 * Add page
	 *
	 * @since 1.0
	 */
	public function add_pages() {
		add_menu_page(
			'Media from FTP',
			'Media from FTP',
			'upload_files',
			'mediafromftp',
			array( $this, 'manage_page' ),
			'dashicons-upload'
		);
		add_submenu_page(
			'mediafromftp',
			__( 'Search & Register', 'media-from-ftp' ),
			__( 'Search & Register', 'media-from-ftp' ),
			'upload_files',
			'mediafromftp-search-register',
			array( $this, 'search_register_page' )
		);
		add_submenu_page(
			'mediafromftp',
			__( 'Settings' ),
			__( 'Settings' ),
			'upload_files',
			'mediafromftp-settings',
			array( $this, 'settings_page' )
		);
		if ( $this->is_add_on_activate['wpcron'] ) {
			$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
			$mediafromftpaddonwpcron->mediafromftp_add_submenu();
			unset( $mediafromftpaddonwpcron );
		}
		add_submenu_page(
			'mediafromftp',
			__( 'Log', 'media-from-ftp' ),
			__( 'Log', 'media-from-ftp' ),
			'upload_files',
			'mediafromftp-log',
			array( $this, 'log_page' )
		);
		add_submenu_page(
			'mediafromftp',
			__( 'Import' ),
			__( 'Import' ),
			'upload_files',
			'mediafromftp-import',
			array( $this, 'medialibrary_import_page' )
		);
		add_submenu_page(
			'mediafromftp',
			__( 'Add-Ons', 'media-from-ftp' ),
			__( 'Add-Ons', 'media-from-ftp' ),
			'upload_files',
			'mediafromftp-addons',
			array( $this, 'addons_page' )
		);
	}

	/** ==================================================
	 * Show Screen Option Search & Register
	 *
	 * @param string $status  status.
	 * @param object $args  args.
	 * @since 9.52
	 */
	public function search_register_show_screen_options( $status, $args ) {

		$mediafromftp_settings = get_option( $this->wp_options_name() );

		$return = $status;
		if ( 'media-from-ftp_page_mediafromftp-search-register' == $args->base ) {
			$mediafromftp = new MediaFromFtp();
			$return = wp_nonce_field( 'mff_search', 'media_from_ftp_search' );
			$return .= '<div style="display: block; padding: 5px 15px">';
			$return .= $mediafromftp->search_option_html( $mediafromftp_settings );
			$return .= '<div style="display: block;padding:5px 5px">' . get_submit_button( __( 'Apply' ), 'primary', 'media-from-ftp-screen-options-apply', false ) . '</div>';
			$return .= '<input type="hidden" name="wp_screen_options[option]" value="media_from_ftp_show_screen" />';
			$return .= '<input type="hidden" name="wp_screen_options[value]" value="2" />';
			$return .= '</div>';
			unset( $mediafromftp );
		}

		return $return;

	}

	/** ==================================================
	 * Save Screen Option Search & Register
	 *
	 * @param string $status  status.
	 * @param string $option  option.
	 * @param int    $value  value.
	 * @since 9.52
	 */
	public function search_register_set_screen_options( $status, $option, $value ) {
		if ( 'media_from_ftp_show_screen' == $option ) {
			$this->options_updated( $value );
			return $value;
		}
		return $status;
	}

	/** ==================================================
	 * Help Tab
	 *
	 * @param string $help  help.
	 * @param string $screen_id  screen_id.
	 * @param object $screen  screen.
	 * @since 9.53
	 */
	public function search_register_help_tab( $help, $screen_id, $screen ) {

		if ( 'media-from-ftp_page_mediafromftp-search-register' === $screen_id || 'media-from-ftp_page_mediafromftp-settings' === $screen_id || 'media-from-ftp_page_mediafromftp-event' === $screen_id || 'media-from-ftp_page_mediafromftp-log' === $screen_id || 'media-from-ftp_page_mediafromftp-import' === $screen_id || 'media-from-ftp_page_mediafromftp-addons' === $screen_id ) {
			$sidebar = '<p><strong>' . __( 'For more information:' ) . '</strong></p>';
			$sidebar .= '<p><a href="' . __( 'https://wordpress.org/plugins/media-from-ftp/faq', 'media-from-ftp' ) . '" target="_blank">' . __( 'FAQ' ) . '</a></p>';
			$sidebar .= '<p><a href="https://wordpress.org/support/plugin/media-from-ftp" target="_blank">' . __( 'Support Forums' ) . '</a></p>';
			$sidebar .= '<p><a href="https://wordpress.org/support/view/plugin-reviews/media-from-ftp" target="_blank">' . __( 'Reviews', 'media-from-ftp' ) . '</a></p>';
			/* translators: Translator */
			$sidebar .= '<p><a href="https://translate.wordpress.org/projects/wp-plugins/media-from-ftp" target="_blank">' . sprintf( __( 'Translations for %s' ), 'Media from FTP' ) . '</a></p>';
			$sidebar .= '<p><a style="text-decoration: none;" href="https://www.facebook.com/katsushikawamori/" target="_blank"><span class="dashicons dashicons-facebook"></span></a> <a style="text-decoration: none;" href="https://twitter.com/dodesyo312" target="_blank"><span class="dashicons dashicons-twitter"></span></a> <a style="text-decoration: none;" href="https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w" target="_blank"><span class="dashicons dashicons-video-alt3"></span></a></p>';
			$sidebar .= '<p><a href="' . __( 'https://riverforest-wp.info/donate/', 'media-from-ftp' ) . '" target="_blank">' . __( 'Donate to this plugin &#187;' ) . '</a></p>';

			$tabs = $this->get_help_message( $screen_id );
			foreach ( $tabs as $tab ) {
				$screen->add_help_tab( $tab );
			}
			$screen->set_help_sidebar( $sidebar );
		}
	}

	/** ==================================================
	 * Help Tab for message
	 *
	 * @param string $screen_id  screen_id.
	 * @return array $tab  tab.
	 * @since 9.53
	 */
	private function get_help_message( $screen_id ) {

		$upload_dir_html = '<span style="color: red;">' . $this->upload_path . '</span>';

		switch ( $screen_id ) {
			case 'media-from-ftp_page_mediafromftp-search-register':
				/* translators: Upload directory */
				$outline = '<p>' . sprintf( __( 'Search the upload directory(%1$s) and display files that do not exist in the media library.', 'media-from-ftp' ), $upload_dir_html ) . '</p>';
				/* translators: Update media */
				$outline .= '<p>' . sprintf( __( 'Please check and press the "%1$s" button.', 'media-from-ftp' ), __( 'Update Media' ) ) . '</p>';
				/* translators: Screen option */
				$outline .= '<p>' . sprintf( __( 'Options for searching can be specified with "%1$s".', 'media-from-ftp' ), __( 'Screen Options' ) ) . '</p>';
				break;
			case 'media-from-ftp_page_mediafromftp-settings':
				/* translators: Register */
				$outline = '<p>' . sprintf( __( '"%1$s" sets options for %2$s registration.', 'media-from-ftp' ), __( 'Register' ), __( 'Media Library' ) ) . '</p>';
				/* translators: Register option */
				$outline .= '<p>' . sprintf( __( '"%1$s" sets other options.', 'media-from-ftp' ), __( 'Other', 'media-from-ftp' ) ) . '</p>';
				if ( $this->is_add_on_activate['cli'] ) {
					$mediafromftpcli = new MediaFromFtpCli();
					$outline .= $mediafromftpcli->mediafromftp_settings_helptab_html();
				}
				break;
			case 'media-from-ftp_page_mediafromftp-event':
				if ( $this->is_add_on_activate['wpcron'] ) {
					$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
					$outline = $mediafromftpaddonwpcron->mediafromftp_event_helptab_html();
				}
				break;
			case 'media-from-ftp_page_mediafromftp-log':
				$outline = '<p>' . __( 'Display history of registration.', 'media-from-ftp' ) . '</p>';
				$outline .= '<p>' . __( 'You can export to CSV format.', 'media-from-ftp' ) . '</p>';
				break;
			case 'media-from-ftp_page_mediafromftp-import':
				$outline = '<p>' . __( 'To Import the files to Media Library from a WordPress export file.', 'media-from-ftp' ) . '</p>';
				/* translators: Upload directory */
				$outline .= '<p>' . sprintf( __( 'In uploads directory(%1$s), that you need to copy the file to the same state as the import source by FTP.', 'media-from-ftp' ), $upload_dir_html ) . '</p>';
				break;
			case 'media-from-ftp_page_mediafromftp-addons':
				$outline = '<p>' . __( 'This page shows paid add-ons and their summaries.', 'media-from-ftp' ) . '</p>';
				$outline .= '<p>' . __( 'You can check whether it is installed or activated.', 'media-from-ftp' ) . '</p>';
				break;
		}

		$tabs = array(
			array(
				'title' => __( 'Overview' ),
				'id' => 'outline',
				'content' => $outline,
			),
		);

		return $tabs;
	}

	/** ==================================================
	 * Add Css and Script
	 *
	 * @since 2.23
	 */
	public function load_custom_wp_admin_style() {
		if ( $this->is_my_plugin_screen() ) {
			$mediafromftp_settings = get_option( $this->wp_options_name() );
			if ( $mediafromftp_settings['datetimepicker'] ) {
				wp_enqueue_style( 'jquery-datetimepicker', $this->plugin_base_url . '/css/jquery.datetimepicker.css', array(), '2.3.4' );
			}
			wp_enqueue_style( 'jquery-responsiveTabs', $this->plugin_base_url . '/css/responsive-tabs.css', array(), '1.4.0' );
			wp_enqueue_style( 'jquery-responsiveTabs-style', $this->plugin_base_url . '/css/style.css', array(), '1.4.0' );
			wp_enqueue_style( 'mediafromftp', $this->plugin_base_url . '/css/mediafromftp.css', array(), '1.00' );
			wp_enqueue_script( 'jquery' );
			if ( $mediafromftp_settings['datetimepicker'] ) {
				wp_enqueue_script( 'jquery-datetimepicker', $this->plugin_base_url . '/js/jquery.datetimepicker.js', null, '2.3.4' );
				wp_enqueue_script( 'jquery-mediafromftp-datetimepicker', $this->plugin_base_url . '/js/jquery.mediafromftp.datetimepicker.js', array( 'jquery' ), '1.00', false );
			}
			wp_enqueue_script( 'jquery-responsiveTabs', $this->plugin_base_url . '/js/jquery.responsiveTabs.min.js', array(), '1.4.0', false );

			$handle = 'mediafromftp-ajax-script';
			$action1 = 'mediafromftp-update-ajax-action';
			$action2 = 'mediafromftp-import-ajax-action';
			$action3 = 'mediafromftp_message';
			$action4 = 'mediafromftp_medialibraryimport_message';
			wp_enqueue_script( $handle, $this->plugin_base_url . '/js/jquery.mediafromftp.js', array( 'jquery' ), '1.00', false );
			wp_localize_script(
				$handle,
				'MEDIAFROMFTPUPDATE',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'action' => $action1,
					'nonce' => wp_create_nonce( $action1 ),
				)
			);
			wp_localize_script(
				$handle,
				'MEDIAFROMFTPIMPORT',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'action' => $action2,
					'nonce' => wp_create_nonce( $action2 ),
				)
			);
			wp_localize_script(
				$handle,
				'MEDIAFROMFTPMESSAGE',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'action' => $action3,
					'nonce' => wp_create_nonce( $action3 ),
				)
			);
			wp_localize_script(
				$handle,
				'MEDIAFROMFTPIMPORTMESSAGE',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'action' => $action4,
					'nonce' => wp_create_nonce( $action4 ),
				)
			);
		}
	}

	/** ==================================================
	 * Add Script on footer
	 *
	 * @since 1.0
	 */
	public function load_custom_wp_admin_style2() {
		if ( $this->is_my_plugin_screen2() ) {
			if ( isset( $_POST['media_from_ftp_select_author'] ) && ! empty( $_POST['media_from_ftp_select_author'] ) ) {
				if ( check_admin_referer( 'mff_select_author', 'media_from_ftp_select_author' ) ) {
					if ( ! empty( $_POST['mediafromftp_select_author'] ) && ! empty( $_POST['mediafromftp_xml_file'] ) ) {
						$filename = sanitize_text_field( wp_unslash( $_POST['mediafromftp_xml_file'] ) );
						if ( is_file( $filename ) ) {
							$select_author = array();
							foreach ( array_keys( $_POST ) as $key ) {
								if ( 'select_author' === $key || 'mediafromftp_select_author' === $key || 'mediafromftp_xml_file' === $key ) {
									/* skip */
									$dummy = 0;
								} else {
									if ( isset( $_POST[ $key ] ) && -1 <> $_POST[ $key ] ) {
										$select_author[ $key ] = intval( $_POST[ $key ] );
									}
								}
							}
							if ( ! validate_file( $filename ) ) {
								$filename = sanitize_text_field( wp_unslash( $_POST['mediafromftp_xml_file'] ) );
								$mediafromftp = new MediaFromFtp();
								$handle = 'mediafromftp-import-ajax-script';
								wp_enqueue_script( 'jquery' );
								wp_enqueue_script( $handle, $this->plugin_base_url . '/js/jquery.mediafromftp.js', array( 'jquery' ), '1.00', false );
								wp_add_inline_script( $handle, $mediafromftp->make_object( $filename, $select_author ) );
								unset( $mediafromftp );
								unlink( $filename );
							}
						}
					}
				}
			}
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 8.82
	 */
	private function is_my_plugin_screen() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'toplevel_page_mediafromftp' === $screen->id ) {
			return true;
		} else if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-settings' === $screen->id ) {
			return true;
		} else if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-search-register' === $screen->id ) {
			return true;
		} else if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-event' === $screen->id ) {
			return true;
		} else if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-log' === $screen->id ) {
			return true;
		} else if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-import' === $screen->id ) {
			return true;
		} else if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-addons' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 8.82
	 */
	private function is_my_plugin_screen2() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-import' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 9.63
	 */
	private function is_my_plugin_screen3() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-search-register' === $screen->id ) {
			return true;
		} else if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-settings' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * Main
	 *
	 * @since 1.00
	 */
	public function manage_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		?>

		<div class="wrap">

		<h2>Media from FTP
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-search-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Search & Register', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( $this->is_add_on_activate['wpcron'] ) {
				$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
				$mediafromftpaddonwpcron->mediafromftp_event_link_html();
				unset( $mediafromftpaddonwpcron );
			}
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Log', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-import' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Import' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-addons' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add-Ons', 'media-from-ftp' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

		<h3><?php esc_html_e( 'Register to media library from files that have been uploaded by FTP.', 'media-from-ftp' ); ?></h3>

		<?php $this->credit(); ?>
		<hr>
		<?php $this->server_path_status(); ?>

		</div>
		<?php

	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( $plugin_path );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( esc_html__( 'https://wordpress.org/plugins/%s/faq', '%s' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = sprintf( esc_html__( 'https://shop.riverforest-wp.info/donate/', '%s' ), $slug );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank"><?php esc_html_e( 'FAQ' ); ?></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank"><?php esc_html_e( 'Support Forums' ); ?></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank"><?php sprintf( esc_html_e( 'Reviews', '%s' ), $slug ); ?></a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank">
		<?php
		/* translators: Plugin translation link */
		echo sprintf( esc_html__( 'Translations for %s' ), esc_html( $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php sprintf( esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', '%s' ), $slug ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php

	}

	/** ==================================================
	 * Server Path Status
	 *
	 * @since 1.00
	 */
	private function server_path_status() {

		?>
		<h3><?php esc_html_e( 'Server path status', 'media-from-ftp' ); ?></h3>
		<h4><?php esc_html_e( 'If something goes wrong, please report the following to the support forum. If you do not want to show the Url or Path,  please hide it part.', 'media-from-ftp' ); ?></h4>
		<?php
		$wp_uploads = wp_upload_dir();
		$mediafromftp = new MediaFromFtp();
		$status = "\n";
		$status .= 'WordPress' . "\n";
		$status .= 'home_url: ' . home_url() . "\n";
		$status .= 'ABSPATH: ' . wp_normalize_path( ABSPATH ) . "\n";
		$status .= 'wp_upload_dir[basedir]: ' . $wp_uploads['basedir'] . "\n";
		$status .= 'wp_upload_dir[baseurl]: ' . $wp_uploads['baseurl'] . "\n";
		$status .= 'upload_path: ' . get_option( 'upload_path' ) . "\n";
		$status .= 'upload_url_path: ' . get_option( 'upload_url_path' ) . "\n";
		$status .= 'WPINC: ' . WPINC . "\n";
		$status .= "\n";
		$status .= 'Media from FTP' . "\n";
		$status .= 'Upload Dir: ' . $this->upload_dir . "\n";
		$status .= 'Upload Url: ' . $this->upload_url . "\n";
		$status .= 'Upload Path: ' . $this->upload_path . "\n";
		$status .= 'Plugin Disallow Tmp Dir: ' . $this->plugin_disallow_tmp_dir . "\n";
		$status .= 'Site Url: ' . $mediafromftp->siteurl() . "\n";
		?>
		<textarea readonly rows="16" style="font-size: 12px; width: 100%;">
		<?php echo esc_textarea( $status ); ?>
		</textarea>
		<?php

	}

	/** ==================================================
	 * Sub Menu
	 *
	 * @since 1.00
	 */
	public function settings_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated( 1 );
		$this->options_updated( 2 );
		$this->options_updated( 3 );
		$this->options_updated( 4 );
		$this->options_updated( 5 );
		$this->options_updated( 6 );
		$this->options_updated( 7 );

		$mediafromftp = new MediaFromFtp();

		$mediafromftp_addon_wpcron = false;
		if ( $this->is_add_on_activate['wpcron'] ) {
			$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
			$mediafromftp_addon_wpcron = true;
		}

		$mediafromftp_addon_category = false;
		if ( $this->is_add_on_activate['category'] ) {
			$mediafromftpaddoncategory = new MediaFromFtpAddOnCategory();
			$mediafromftp_addon_category = true;
		}

		$mediafromftp_addon_exif = false;
		if ( $this->is_add_on_activate['exif'] ) {
			$mediafromftpaddonexif = new MediaFromFtpAddOnExif();
			$mediafromftp_addon_exif = true;
		}

		$mediafromftp_settings = get_option( $this->wp_options_name() );

		$def_max_execution_time = ini_get( 'max_execution_time' );
		$scriptname = admin_url( 'admin.php?page=mediafromftp-settings' );

		?>

		<div class="wrap">

		<h2>Media from FTP <a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-settings' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Settings' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-search-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Search & Register', 'media-from-ftp' ); ?></a>
			<?php
			if ( $mediafromftp_addon_wpcron ) {
				$mediafromftpaddonwpcron->mediafromftp_event_link_html();
			}
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Log', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-import' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Import' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-addons' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add-Ons', 'media-from-ftp' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

		<div id="mediafromftp-settings-tabs">
			<ul>
			<li><a href="#mediafromftp-settings-tabs-1"><?php esc_html_e( 'Register' ); ?></a></li>
			<li><a href="#mediafromftp-settings-tabs-2"><?php esc_html_e( 'Search' ); ?></a></li>
			<li><a href="#mediafromftp-settings-tabs-3"><?php esc_html_e( 'Other', 'media-from-ftp' ); ?></a></li>
			<?php
			if ( $this->is_add_on_activate['cli'] ) {
				$mediafromftpcli = new MediaFromFtpCli();
				$mediafromftpcli->mediafromftp_settings_tab_menu_html();
			}
			?>
			</ul>

			<div id="mediafromftp-settings-tabs-1">
			<div style="display: block; padding: 5px 15px">
				<div class="item-mediafromftp-settings">
					<h3><?php esc_html_e( 'Date' ); ?></h3>
					<div style="display: block;padding:5px 5px">
					<input type="radio" name="mediafromftp_dateset" form="mediafromftp_settings_form" value="new" 
					<?php
					if ( 'new' === $mediafromftp_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Update to use of the current date/time.', 'media-from-ftp' ); ?>
					</div>
					<div style="display: block;padding:5px 5px">
					<input type="radio" name="mediafromftp_dateset" form="mediafromftp_settings_form" value="server" 
					<?php
					if ( 'server' === $mediafromftp_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Get the date/time of the file, and updated based on it. Change it if necessary.', 'media-from-ftp' ); ?>
					</div>
					<div style="display: block; padding:5px 5px">
					<input type="radio" name="mediafromftp_dateset" form="mediafromftp_settings_form" value="exif" 
					<?php
					if ( 'exif' === $mediafromftp_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php
					esc_html_e( 'Get the date/time of the file, and updated based on it. Change it if necessary.', 'media-from-ftp' );
					esc_html_e( 'Get by priority if there is date and time of the Exif information.', 'media-from-ftp' );
					?>
					</div>
					<div style="display: block; padding:5px 5px">
					<input type="radio" name="mediafromftp_dateset" form="mediafromftp_settings_form" value="fixed" 
					<?php
					if ( 'fixed' === $mediafromftp_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Update to use of fixed the date/time.', 'media-from-ftp' ); ?>
					</div>
					<div style="display: block; padding:5px 40px">
					<input type="text" id="datetimepicker-mediafromftp00" name="mediafromftp_datefixed" form="mediafromftp_settings_form" value="<?php echo esc_attr( $mediafromftp_settings['datefixed'] ); ?>">
					</div>
					<div style="display: block; padding:5px 5px">
					<?php
					if ( current_user_can( 'manage_options' ) ) {
						?>
						<input type="checkbox" name="move_yearmonth_folders" form="mediafromftp_settings_form" value="1" <?php checked( '1', get_option( 'uploads_use_yearmonth_folders' ) ); ?> />
						<?php
					} else {
						?>
						<input type="checkbox" form="mediafromftp_settings_form" disabled="disabled" value="1" <?php checked( '1', get_option( 'uploads_use_yearmonth_folders' ) ); ?> />
						<input type="hidden" name="move_yearmonth_folders" form="mediafromftp_settings_form" value="<?php echo esc_attr( get_option( 'uploads_use_yearmonth_folders' ) ); ?>">
						<?php
					}
					esc_html_e( 'Organize my uploads into month- and year-based folders' );
					?>
					</div>

					<div style="display: block; padding:5px 5px">
					<input type="checkbox" name="mediafromftp_datetimepicker" form="mediafromftp_settings_form" value="1" <?php checked( '1', $mediafromftp_settings['datetimepicker'] ); ?> />
					<a href="https://xdsoft.net/jqplugins/datetimepicker/" target="_blank" style="text-decoration: none;">Date Time Picker</a>(jQuery <?php esc_html_e( 'Plugin' ); ?>)
					<?php esc_html_e( 'Date and time input assistance', 'media-from-ftp' ); ?>
					</div>

				</div>

				<div class="item-mediafromftp-settings">
					<h3><?php esc_html_e( 'Log', 'media-from-ftp' ); ?></h3>
					<div style="display:block;padding:5px 0">
					<?php esc_html_e( 'Record the registration result.', 'media-from-ftp' ); ?>
					</div>
					<div style="display:block;padding:5px 0">
					<input type="checkbox" name="mediafromftp_apply_log" form="mediafromftp_settings_form" value="1" <?php checked( '1', $mediafromftp_settings['log'] ); ?> />
					<?php esc_html_e( 'Create log', 'media-from-ftp' ); ?>
					</div>
				</div>

				<div class="item-mediafromftp-settings">
					<h3><?php esc_html_e( 'Schedule', 'media-from-ftp' ); ?>(<?php esc_html_e( 'Cron Event', 'media-from-ftp' ); ?>)</h3>
					<div style="display:block;padding:5px 0">
					<?php esc_html_e( 'Set the schedule.', 'media-from-ftp' ); ?>
					</div>
					<?php
					if ( $mediafromftp_addon_wpcron ) {
						$mediafromftpaddonwpcron->mediafromftp_schedule_form( $scriptname, $mediafromftp_settings );
					} else {
						$add_on_url = '<a href="' . admin_url( 'admin.php?page=mediafromftp-addons' ) . '" style="text-decoration: none; word-break: break-all;"><strong>' . __( 'Add-Ons', 'media-from-ftp' ) . '(Media from FTP Add On Wp Cron)</strong></a>';
						/* translators: %1$s: add on url */
						$use_add_on_html = sprintf( __( 'This function requires %1$s.', 'media-from-ftp' ), $add_on_url );
						?>
						<div style="display:block;padding:5px 0">
						<?php echo wp_kses_post( $use_add_on_html ); ?>
						</div>
						<?php
					}
					?>
				</div>

				<div class="item-mediafromftp-settings">
					<h3><?php esc_html_e( 'Categories' ); ?></h3>
					<div style="display:block;padding:5px 0">
					<?php esc_html_e( 'Specify categories to register at the same time when registering.', 'media-from-ftp' ); ?>
					</div>
					<?php
					if ( $mediafromftp_addon_category ) {
						$mlccs = explode( ',', $mediafromftp_settings['mlcc'] );
						$emlcs = explode( ',', $mediafromftp_settings['emlc'] );
						$mlacs = explode( ',', $mediafromftp_settings['mlac'] );
						$mlats = explode( ',', $mediafromftp_settings['mlat'] );
						$allowed_category_admin_html = array(
							'div' => array(
								'style' => array(),
							),
							'strong' => array(),
							'input' => array(
								'type'  => array(),
								'name'  => array(),
								'value' => array(),
								'checked' => array(),
								'form' => array(),
							),
						);
						echo wp_kses( $mediafromftpaddoncategory->mlc_category_admin_html( $mlccs ), $allowed_category_admin_html );
						echo wp_kses( $mediafromftpaddoncategory->eml_category_admin_html( $emlcs ), $allowed_category_admin_html );
						echo wp_kses( $mediafromftpaddoncategory->mla_category_admin_html( $mlacs, $mlats ), $allowed_category_admin_html );
					} else {
						$add_on_url = '<a href="' . admin_url( 'admin.php?page=mediafromftp-addons' ) . '" style="text-decoration: none; word-break: break-all;"><strong>' . __( 'Add-Ons', 'media-from-ftp' ) . '(Media from FTP Add On Category)</strong></a>';
						/* translators: %1$s: add on url */
						$use_add_on_html = sprintf( __( 'This function requires %1$s.', 'media-from-ftp' ), $add_on_url );
						?>
						<div style="display:block;padding:5px 0">
						<?php echo wp_kses_post( $use_add_on_html ); ?>
						</div>
						<?php
					}
					?>
				</div>

				<div class="item-mediafromftp-settings">
					<h3>Exif <?php esc_html_e( 'Caption' ); ?></h3>
					<div style="display:block;padding:5px 0">
					<?php esc_html_e( 'Register the Exif data to the caption.', 'media-from-ftp' ); ?>
					</div>
					<?php
					if ( $mediafromftp_addon_exif ) {
						$mediafromftpaddonexif->mediafromftp_exif_form( $mediafromftp_settings );
					} else {
						$add_on_url = '<a href="' . admin_url( 'admin.php?page=mediafromftp-addons' ) . '" style="text-decoration: none; word-break: break-all;"><strong>' . __( 'Add-Ons', 'media-from-ftp' ) . '(Media from FTP Add On Exif)</strong></a>';
						/* translators: %1$s: add on url */
						$use_add_on_html = sprintf( __( 'This function requires %1$s.', 'media-from-ftp' ), $add_on_url );
						?>
						<div style="display:block;padding:5px 0">
						<?php echo wp_kses_post( $use_add_on_html ); ?>
						</div>
						<?php
					}
					?>
				</div>

				<div style="clear: both;"></div>

				<form method="post" id="mediafromftp_settings_form" action="<?php echo esc_url( $scriptname ); ?>">
					<?php wp_nonce_field( 'mff_settings', 'media_from_ftp_settings' ); ?>
					<div style="display: block;padding:5px 5px">
					<?php submit_button( __( 'Save Changes' ), 'large', 'media-from-ftp-settings-options-apply', false ); ?>
					</div>
				</form>

			</div>
			</div>

			<div id="mediafromftp-settings-tabs-2">
			<div style="display: block; padding: 5px 15px">
				<form method="post" id="mediafromftp_search_form" action="<?php echo esc_url( $scriptname ); ?>">
					<?php wp_nonce_field( 'mff_search', 'media_from_ftp_search' ); ?>
					<div class="item-mediafromftp-settings">
					<h3><?php echo esc_html( __( 'Search' ) . ' ' . __( 'directory', 'media-from-ftp' ) . ' - ' . __( 'type', 'media-from-ftp' ) . ' - ' . __( 'extension', 'media-from-ftp' ) ); ?></h3>
					<?php
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
					echo wp_kses( $mediafromftp->dir_select_box( $mediafromftp_settings['searchdir'], $mediafromftp_settings['character_code'], wp_normalize_path( ABSPATH ) ), $allowed_select_html );
					echo wp_kses( $mediafromftp->type_ext_select_box( $mediafromftp_settings['ext2typefilter'], $mediafromftp_settings['extfilter'] ), $allowed_select_html );
					?>
					</div>
					<?php
					$allowed_search_option_html = array(
						'h3' => array(),
						'div' => array(
							'class' => array(),
							'style' => array(),
						),
						'label' => array(),
						'input' => array(
							'type'  => array(),
							'step'  => array(),
							'min'  => array(),
							'max'  => array(),
							'maxlength' => array(),
							'class'  => array(),
							'name'  => array(),
							'id'    => array(),
							'class' => array(),
							'value' => array(),
							'checked' => array(),
						),
						'textarea' => array(
							'id'    => array(),
							'name'  => array(),
							'rows'  => array(),
							'style' => array(),
						),
					);
					echo wp_kses( $mediafromftp->search_option_html( $mediafromftp_settings ), $allowed_search_option_html );
					?>
					<div style="display: block;padding:5px 5px">
					<?php submit_button( __( 'Save Changes' ), 'large', 'media-from-ftp-search-options-apply', false ); ?>
					</div>
				</form>
			</div>
			</div>

			<div id="mediafromftp-settings-tabs-3">
			<div style="display: block; padding: 5px 15px">

				<div class="item-mediafromftp-settings">
					<h3><?php esc_html_e( 'Limit number of search files', 'media-from-ftp' ); ?></h3>
					<p>
					<?php esc_html_e( 'If you can not search because there are too many files, please reduce this number.', 'media-from-ftp' ); ?>
					</p>
					<div style="display:block;padding:5px 0">
					<input type="number" step="100" min="100" max="100000" name="mediafromftp_search_limit_number" value="<?php echo esc_attr( $mediafromftp_settings['search_limit_number'] ); ?>" form="mediafromftp_settings_form" >
					</div>
					<div style="clear: both;"></div>
				</div>

				<div class="item-mediafromftp-settings">
					<h3><?php esc_html_e( 'Execution time', 'media-from-ftp' ); ?></h3>
					<div style="display:block; padding:5px 5px">
						<?php
							$max_execution_time = $mediafromftp_settings['max_execution_time'];
						if ( ! @set_time_limit( $max_execution_time ) ) {
							$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'media-from-ftp' ) . '</font>';
							/* translators: %1$s: limit max execution time */
							echo wp_kses_post( sprintf( __( 'Execution time for this server is fixed at %1$s. If this limit is exceeded, the search times out&#40;%2$s, %3$s&#41;.', 'media-from-ftp' ), $limit_seconds_html, __( 'Search' ), __( 'Log', 'media-from-ftp' ) ) );
							?>
							<input type="hidden" name="mediafromftp_max_execution_time" form="mediafromftp_settings_form" value="<?php echo esc_attr( $def_max_execution_time ); ?>" />
							<?php
						} else {
							$max_execution_time_text = __( 'The number of seconds a script is allowed to run.', 'media-from-ftp' ) . '(' . __( 'The max_execution_time value defined in the php.ini.', 'media-from-ftp' ) . '[<font color="red">' . $def_max_execution_time . '</font>])';
							esc_html_e( 'This is to suppress the timeout when retrieving a large amount of data when displaying the search screen and log screen.', 'media-from-ftp' );
							esc_html_e( 'It does not matter on the registration screen.', 'media-from-ftp' );
							?>
								<div style="float: left;"><?php echo wp_kses_post( $max_execution_time_text ); ?>:<input type="number" step="1" min="1" max="999" class="screen-per-page" maxlength="3" name="mediafromftp_max_execution_time" form="mediafromftp_settings_form" value="<?php echo esc_attr( $max_execution_time ); ?>" /></div>
							<?php
						}
						?>
					</div>
					<div style="clear: both;"></div>
				</div>

				<?php
				if ( function_exists( 'mb_check_encoding' ) ) {
					?>
				<div class="item-mediafromftp-settings">
					<h3><?php esc_html_e( 'Character Encodings for Server', 'media-from-ftp' ); ?></h3>
					<p>
					<?php
					esc_html_e( 'It may fail to register if you are using a multi-byte name in the file name or folder name. In that case, please change.', 'media-from-ftp' );
					$characterencodings_none_html = '<a href="' . __( 'https://en.wikipedia.org/wiki/Variable-width_encoding', 'media-from-ftp' ) . '" target="_blank" style="text-decoration: none; word-break: break-all;">' . __( 'variable-width encoding', 'media-from-ftp' ) . '</a>';
					/* translators: %1$s: URL of Variable-width_encoding */
					echo wp_kses_post( sprintf( __( 'If you do not use the filename or directory name of %1$s, please choose "%2$s".', 'media-from-ftp' ), $characterencodings_none_html, '<font color="red">none</font>' ) );
					?>
					</p>
					<select name="mediafromftp_character_code" form="mediafromftp_settings_form" style="width: 210px">
					<?php
					if ( 'none' === $mediafromftp_settings['character_code'] ) {
						?>
						<option value="none" selected>none</option>
						<?php
					} else {
						?>
						<option value="none">none</option>
						<?php
					}
					foreach ( mb_list_encodings() as $chrcode ) {
						if ( 'pass' <> $chrcode && 'auto' <> $chrcode ) {
							if ( $chrcode === $mediafromftp_settings['character_code'] ) {
								?>
								<option value="<?php echo esc_attr( $chrcode ); ?>" selected><?php echo esc_html( $chrcode ); ?></option>
								<?php
							} else {
								?>
								<option value="<?php echo esc_attr( $chrcode ); ?>"><?php echo esc_html( $chrcode ); ?></option>
								<?php
							}
						}
					}
					?>
					</select>
					<div style="clear: both;"></div>
				</div>
					<?php
				}
				?>

				<div class="item-mediafromftp-settings">
					<h3><?php esc_html_e( 'Remove Thumbnails Cache', 'media-from-ftp' ); ?></h3>
					<div style="display:block;padding:5px 0">
						<?php esc_html_e( 'Remove the cache of thumbnail used in the search screen. Please try out if trouble occurs in the search screen. It might become normal.', 'media-from-ftp' ); ?>
					</div>
					<form method="post" action="<?php echo esc_url( $scriptname ); ?>" />
						<?php wp_nonce_field( 'mff_clear_cash', 'media_from_ftp_clear_cash' ); ?>
						<input type="hidden" name="mediafromftp_clear_cash" value="1" />
						<div>
						<?php submit_button( __( 'Remove Thumbnails Cache', 'media-from-ftp' ), 'delete', '', false ); ?>
						</div>
					</form>
				</div>

				<div style="clear: both;"></div>

				<div style="display: block;padding:5px 5px">
				<?php submit_button( __( 'Save Changes' ), 'large', 'media-from-ftp-settings-options-apply', false, array( 'form' => 'mediafromftp_settings_form' ) ); ?>
				</div>

			</div>
			</div>

			<?php
			if ( $this->is_add_on_activate['cli'] ) {
				$mediafromftpcli->mediafromftp_command_line_html( $this->wp_options_name() );
				unset( $mediafromftpcli );
			}
			?>

		</div>
		</div>
		<?php
		unset( $mediafromftpaddonwpcron );
		unset( $mediafromftpaddoncategory );
	}

	/** ==================================================
	 * Sub Menu
	 *
	 * @since 1.00
	 */
	public function search_register_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated( 2 );

		$mediafromftp_settings = get_option( $this->wp_options_name() );

		$def_max_execution_time = ini_get( 'max_execution_time' );
		$max_execution_time = $mediafromftp_settings['max_execution_time'];

		$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'media-from-ftp' ) . '</font>';

		if ( ! @set_time_limit( $max_execution_time ) ) {
			/* translators: %1$s: limit max execution time */
			echo '<div class="notice notice-info is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( 'Execution time for this server is fixed at %1$s. If this limit is exceeded, times out&#40;%2$s&#41;. Please note the "Number of items per page" so as not to exceed this limit.', 'media-from-ftp' ), $limit_seconds_html, __( 'Search' ) ) ) . '</li></ul></div>';
		}

		?>
		<div class="wrap">

			<h2>Media from FTP <a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-search-register' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Search & Register', 'media-from-ftp' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
				<?php
				$mediafromftp = new MediaFromFtp();
				if ( $this->is_add_on_activate['wpcron'] ) {
					$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
					$mediafromftpaddonwpcron->mediafromftp_event_link_html();
					unset( $mediafromftpaddonwpcron );
				}
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Log', 'media-from-ftp' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-import' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Import' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-addons' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add-Ons', 'media-from-ftp' ); ?></a>
			</h2>
			<div style="clear: both;"></div>

			<div id="mediafromftp-loading"><img src="<?php echo esc_url( $this->plugin_base_url . '/css/loading.gif' ); ?>"></div>
			<div id="mediafromftp-loading-container">
				<?php
				$media_from_ftp_list_table = new TT_MediaFromFtp_List_Table();
				$media_from_ftp_list_table->prepare_items( $mediafromftp_settings );
				if ( $media_from_ftp_list_table->max_items > 0 ) {
					$update_button = get_submit_button( __( 'Update Media' ), 'primary', '', false, array( 'form' => 'mediafromftp_ajax_update' ) );
					$update_upper_button = '<div style="padding: 15px 15px 0px;">' . $update_button . '</div>';
					$update_lower_button = '<div style="padding: 0px 15px;">' . $update_button . '</div>';
				} else {
					$update_upper_button = null;
					$update_lower_button = null;
				}
				$allowed_button_html = array(
					'input' => array(
						'type'  => array(),
						'name'  => array(),
						'id'    => array(),
						'class' => array(),
						'value' => array(),
						'form' => array(),
					),
					'div' => array(
						'style' => array(),
					),
				);
				$page = null;
				if ( isset( $_GET['page'] ) && ! empty( $_GET['page'] ) ) {
					$page = intval( $_GET['page'] );
				}
				$mediafromftp->form_html( $mediafromftp_settings );
				?>
				<form method="post" id="mediafromftp_ajax_update">
					<form id="media-from-ftp-filter" method="get">
						<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
						<?php echo wp_kses( $update_upper_button, $allowed_button_html ); ?>
						<?php $media_from_ftp_list_table->display(); ?>
						<?php echo wp_kses( $update_lower_button, $allowed_button_html ); ?>
					</form>
				</form>
			</div>
		</div>
		<?php
		unset( $mediafromftp );
	}

	/** ==================================================
	 * Bulk Change Date Time
	 *
	 * @since 9.63
	 */
	public function custom_bulk_admin_footer() {
		$mediafromftp_settings = get_option( $this->wp_options_name() );
		if ( 'server' === $mediafromftp_settings['dateset'] || 'exif' === $mediafromftp_settings['dateset'] ) {
			if ( $this->is_my_plugin_screen3() ) {
				if ( function_exists( 'wp_date' ) ) {
					$now_date_time = wp_date( 'Y-m-d H:i' );
				} else {
					$now_date_time = date_i18n( 'Y-m-d H:i' );
				}
				$html = '<div style="float: right;">' . __( 'Bulk Change', 'media-from-ftp' ) . '<input type="text" id="datetimepicker-mediafromftp0" name="bulk_mediafromftp_datetime" value="' . $now_date_time . '" style="width: 160px; height: 1.7em;" /></div>';
				$allowed_html = array(
					'div'  => array(
						'style'  => array(),
					),
					'input'  => array(
						'type'  => array(),
						'id'  => array(),
						'name'  => array(),
						'value'  => array(),
						'style'  => array(),
					),
				);

				?>
				<script type="text/javascript">
					jQuery('<?php echo wp_kses( $html, $allowed_html ); ?>').prependTo("#datetime");
				</script>
				<?php
			}
		}
	}

	/** ==================================================
	 * Sub Menu
	 * for media-from-ftp-add-on-wpcron
	 *
	 * @since 1.00
	 */
	public function event_page() {

		$mediafromftp_addon_wpcron = false;
		$mediafromftp = new MediaFromFtp();
		if ( $this->is_add_on_activate['wpcron'] ) {
			$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
			include_once $this->plugin_dir . '/media-from-ftp-add-on-wpcron/req/MediaFromFtpCron.php';
			$mediafromftpcron = new MediaFromFtpCron();
			$mediafromftp_addon_wpcron = true;
		}

		if ( ! current_user_can( 'upload_files' ) || ! $mediafromftp_addon_wpcron ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated( 4 );

		?>
		<div class="wrap">

		<h2>Media from FTP <?php echo wp_kses_post( $mediafromftpaddonwpcron->mediafromftp_event_top_link_html() ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-search-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Search & Register', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Log', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-import' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Import' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-addons' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add-Ons', 'media-from-ftp' ); ?></a>
		</h2>
		<div style="clear: both;"></div>
		<p>
		<?php

		if ( isset( $_POST['media_from_ftp_event'] ) && ! empty( $_POST['media_from_ftp_event'] ) ) {
			if ( check_admin_referer( 'mff_event', 'media_from_ftp_event' ) ) {
				if ( isset( $_POST['event-mediafromftp'] ) && ! empty( $_POST['event-mediafromftp'] ) ) {
					$events_mediafromftp = array_map( 'sanitize_text_field', wp_unslash( $_POST['event-mediafromftp'] ) );
					$events = get_option( $this->wp_add_on_wpcron_events_name() );
					$event_names = null;
					foreach ( $events_mediafromftp as $key => $event_id ) {
						$option_name = $events[ $event_id ];
						$mediafromftpcron->CronStop( $option_name );
						delete_option( $option_name );
						$event_names .= ' ' . $event_id . ' ';
						unset( $events[ $event_id ] );
						update_option( $this->wp_add_on_wpcron_events_name(), $events );
					}
					unset( $mediafromftpcron );
					$allowed_notice_html = array(
						'div'   => array(
							'class' => array(),
						),
						'ul' => array(),
						'li' => array(),
					);
					echo wp_kses( $mediafromftpaddonwpcron->mediafromftp_event_notice_html( $event_names ), $allowed_notice_html );
				}
			}
		}

		$scriptname = admin_url( 'admin.php?page=mediafromftp-event' );

		$allowed_event_html = array(
			'form' => array(
				'method' => array(),
				'id' => array(),
				'action' => array(),
			),
			'input' => array(
				'type'  => array(),
				'name'  => array(),
				'id'    => array(),
				'class' => array(),
				'value' => array(),
			),
			'table' => array(
				'class' => array(),
			),
			'thead' => array(),
			'tbody' => array(),
			'tr' => array(),
			'td' => array(),
			'th' => array(
				'scope' => array(),
				'align' => array(),
				'style' => array(),
			),
			'div' => array(),
			'strong' => array(),
			'button' => array(
				'name'  => array(),
				'value' => array(),
				'form'  => array(),
			),
		);
		echo wp_kses( $mediafromftpaddonwpcron->mediafromftp_event_html( $scriptname, get_option( $this->wp_add_on_wpcron_events_name() ) ), $allowed_event_html );
		?>
		</div>
		<?php

	}

	/** ==================================================
	 * Sub Menu
	 *
	 * @since 1.00
	 */
	public function log_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$mediafromftp_settings = get_option( $this->wp_options_name() );
		if ( ! $mediafromftp_settings['log'] ) {
			echo '<div class="notice notice-info is-dismissible"><ul><li>' . esc_html__( 'Current, log is not created. If you want to create a log, please put a check in the [Create log] in the settings.', 'media-from-ftp' ) . '</li></ul></div>';
		}
		$def_max_execution_time = ini_get( 'max_execution_time' );
		$max_execution_time = $mediafromftp_settings['max_execution_time'];

		$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'media-from-ftp' ) . '</font>';
		if ( ! @set_time_limit( $max_execution_time ) ) {
			/* translators: %1$s: default max execution time */
			echo '<div class="notice notice-info is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( 'Execution time for this server is fixed at %1$s. If this limit is exceeded, times out. Please run the frequently "Delete log" and "Export to CSV" so as not to exceed this limit.', 'media-from-ftp' ), $limit_seconds_html ) ) . '</li></ul></div>';
		}

		?>
		<div class="wrap">

		<h2>Media from FTP <a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Log', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-search-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Search & Register', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( $this->is_add_on_activate['wpcron'] ) {
				$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
				$mediafromftpaddonwpcron->mediafromftp_event_link_html();
				unset( $mediafromftpaddonwpcron );
			}
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-import' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Import' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-addons' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add-Ons', 'media-from-ftp' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

		<div id="mediafromftp-loading"><img src="<?php echo esc_url( $this->plugin_base_url . '/css/loading.gif' ); ?>"></div>
		<div id="mediafromftp-loading-container">
		<?php
		global $wpdb;

		$user = wp_get_current_user();

		$wpdb->log_table_name = $wpdb->prefix . 'mediafromftp_log';

		if ( isset( $_POST['media_from_ftp_clear_log'] ) && ! empty( $_POST['media_from_ftp_clear_log'] ) ) {
			if ( check_admin_referer( 'mff_clear_log', 'media_from_ftp_clear_log' ) ) {
				if ( ! empty( $_POST['mediafromftp_clear_log'] ) && 1 == $_POST['mediafromftp_clear_log'] ) {
					if ( current_user_can( 'administrator' ) ) {
						$wpdb->query( "DELETE FROM $wpdb->log_table_name" );
						echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Removed all of the log.', 'media-from-ftp' ) . '</li></ul></div>';
					} else {
						$delete_count = $wpdb->delete( $wpdb->log_table_name, array( 'user' => $user->display_name ) );
						if ( $delete_count > 0 ) {
							/* translators: %1$s: username %2$d: delete count */
							echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( sprintf( __( '%1$s of the log has been deleted %2$d.', 'media-from-ftp' ), $user->display_name, $delete_count ) ) . '</li></ul></div>';
						} else {
							/* translators: %1$s: username */
							echo '<div class="notice notice-info is-dismissible"><ul><li>' . esc_html( sprintf( __( '%1$s do not have a possible deletion log.', 'media-from-ftp' ), $user->display_name ) ) . '</li></ul></div>';
						}
					}
				}
			}
		}

		$records = $wpdb->get_results( "SELECT * FROM $wpdb->log_table_name" );

		$csv = null;
		$max_thumbnail_count = 0;
		$max_mlccategories_count = 0;
		$max_emlcategories_count = 0;
		$max_mlacategories_count = 0;
		$max_mlatags_count = 0;
		$html = '<table>';

		foreach ( $records as $record ) {
			$csvs = '"' . $record->id . '","' . $record->user . '","' . $record->title . '","' . $record->permalink . '","' . $record->url . '","' . $record->filename . '","' . $record->time . '","' . $record->filetype . '","' . $record->filesize . '","' . $record->exif . '","' . $record->length . '"';
			$html_thumbnail = null;
			if ( $record->thumbnail ) {
				$thumbnails = json_decode( $record->thumbnail, true );
				if ( $max_thumbnail_count < count( $thumbnails ) ) {
					$max_thumbnail_count = count( $thumbnails );
				}
				$count = 0;
				foreach ( $thumbnails as $thumbnail ) {
					++$count;
					$html_thumbnail .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Featured Image' ) . $count . ':</th><td>' . $thumbnail . '</td></tr>';
					$csvs .= ',"' . $thumbnail . '"';
				}
			}
			$html_mlccategory = null;
			if ( $record->mlccategories ) {
				$mlccategories = json_decode( $record->mlccategories, true );
				if ( $max_mlccategories_count < count( $mlccategories ) ) {
					$max_mlccategories_count = count( $mlccategories );
				}
				$count = 0;
				foreach ( $mlccategories as $mlccategory ) {
					++$count;
					$html_mlccategory .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Categories' ) . '[Media Library Categories]' . $count . ':</th><td>' . $mlccategory . '</td></tr>';
					$csvs .= ',"' . $mlccategory . '"';
				}
			}
			$html_emlcategory = null;
			if ( $record->emlcategories ) {
				$emlcategories = json_decode( $record->emlcategories, true );
				if ( $max_emlcategories_count < count( $emlcategories ) ) {
					$max_emlcategories_count = count( $emlcategories );
				}
				$count = 0;
				foreach ( $emlcategories as $emlcategory ) {
					++$count;
					$html_emlcategory .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Categories' ) . '[Enhanced Media Library]' . $count . ':</th><td>' . $emlcategory . '</td></tr>';
					$csvs .= ',"' . $emlcategory . '"';
				}
			}
			$html_mlacategory = null;
			if ( $record->mlacategories ) {
				$mlacategories = json_decode( $record->mlacategories, true );
				if ( $max_mlacategories_count < count( $mlacategories ) ) {
					$max_mlacategories_count = count( $mlacategories );
				}
				$count = 0;
				foreach ( $mlacategories as $mlacategory ) {
					++$count;
					$html_mlacategory .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Categories' ) . '[Media Library Assistant]' . $count . ':</th><td>' . $mlacategory . '</td></tr>';
					$csvs .= ',"' . $mlacategory . '"';
				}
			}
			$html_mlatag = null;
			if ( $record->mlatags ) {
				$mlatags = json_decode( $record->mlatags, true );
				if ( $max_mlatags_count < count( $mlatags ) ) {
					$max_mlatags_count = count( $mlatags );
				}
				$count = 0;
				foreach ( $mlatags as $mlatag ) {
					++$count;
					$html_mlatag .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Tags' ) . '[Media Library Assistant]' . $count . ':</th><td>' . $mlatag . '</td></tr>';
					$csvs .= ',"' . $mlatag . '"';
				}
			}
			$csvs .= "\n";
			$csv .= $csvs;
			$html .= '<tr><th>&nbsp;</th><td>&nbsp;</td></tr>';
			$html .= '<tr><th align="right" style="background-color: #cccccc;">ID:</th><td>' . $record->id . '</td></tr>';
			$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Author' ) . ':</th><td>' . $record->user . '</td></tr>';
			$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Title' ) . ':</th><td>' . $record->title . '</td></tr>';
			$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Permalink:' ) . '</th><td>' . $record->permalink . '</td></tr>';
			$html .= '<tr><th align="right" style="white-space: nowrap;">URL:</th><td>' . $record->url . '</td>';
			$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'File name:' ) . '</th><td>' . $record->filename . '</td></tr>';
			$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Date/Time' ) . ':</th><td>' . $record->time . '</td></tr>';
			$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'File type:' ) . '</th><td>' . $record->filetype . '</td></tr>';
			$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'File size:' ) . '</th><td>' . $record->filesize . '</td></tr>';
			if ( $record->exif ) {
				$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Caption' ) . '[Exif]:</th><td>' . $record->exif . '</td></tr>';
			}
			if ( $record->length ) {
				$html .= '<tr><th align="right" style="white-space: nowrap;">' . __( 'Length:' ) . '</th><td>' . $record->length . '</td></tr>';
			}
			$html .= $html_thumbnail . $html_mlccategory . $html_emlcategory . $html_mlacategory . $html_mlatag;
		}
		$html .= '</table>' . "\n";
		$csv_head = '"ID","' . __( 'Author' ) . '","' . __( 'Title' ) . ':","' . __( 'Permalink:' ) . '","URL:","' . __( 'File name:' ) . '","' . __( 'Date/Time' ) . ':","' . __( 'File type:' ) . '","' . __( 'File size:' ) . '","' . __( 'Caption' ) . '[Exif]:","' . __( 'Length:' ) . '"';
		for ( $i = 1; $i <= $max_thumbnail_count; $i++ ) {
			$csv_head .= ',"' . __( 'Featured Image' ) . $i . '"';
		}
		for ( $i = 1; $i <= $max_mlccategories_count; $i++ ) {
			$csv_head .= ',"' . __( 'Categories' ) . '[Media Library Categories]' . $i . '"';
		}
		for ( $i = 1; $i <= $max_emlcategories_count; $i++ ) {
			$csv_head .= ',"' . __( 'Categories' ) . '[Enhanced Media Library]' . $i . '"';
		}
		for ( $i = 1; $i <= $max_mlacategories_count; $i++ ) {
			$csv_head .= ',"' . __( 'Categories' ) . '[Media Library Assistant]' . $i . '"';
		}
		for ( $i = 1; $i <= $max_mlatags_count; $i++ ) {
			$csv_head .= ',"' . __( 'Tags' ) . '[Media Library Assistant]' . $i . '"';
		}

		$csv = $csv_head . "\n" . $csv;

		$csv_file_name = $this->plugin_tmp_dir . '/' . $wpdb->log_table_name . '.csv';
		if ( isset( $_POST['media_from_ftp_put_log'] ) && ! empty( $_POST['media_from_ftp_put_log'] ) ) {
			if ( check_admin_referer( 'mff_put_log', 'media_from_ftp_put_log' ) ) {
				if ( ! empty( $_POST['mediafromftp_put_log'] ) && 1 == $_POST['mediafromftp_put_log'] ) {
					file_put_contents( $csv_file_name, pack( 'C*', 0xEF, 0xBB, 0xBF ) ); /* UTF-8 BOM */
					file_put_contents( $csv_file_name, $csv, FILE_APPEND | LOCK_EX );
				}
			}
		} else {
			if ( file_exists( $csv_file_name ) ) {
				unlink( $csv_file_name );
			}
		}

		if ( ! empty( $records ) ) {
			?>
			<div style="display: block; padding: 10px 10px">
			<form style="float: left;" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" />
				<?php wp_nonce_field( 'mff_clear_log', 'media_from_ftp_clear_log' ); ?>
				<input type="hidden" name="mediafromftp_clear_log" value="1" />
				<div>
				<?php submit_button( __( 'Delete log', 'media-from-ftp' ), 'large', '', false ); ?>
				</div>
			</form>
			<form style="float: left; margin-left: 0.5em; margin-right: 0.5em;" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" />
				<?php wp_nonce_field( 'mff_put_log', 'media_from_ftp_put_log' ); ?>
				<input type="hidden" name="mediafromftp_put_log" value="1" />
				<div>
				<?php submit_button( __( 'Export to CSV', 'media-from-ftp' ), 'large', '', false ); ?>
				</div>
			</form>
			<?php
			if ( file_exists( $csv_file_name ) ) {
				?>
				<form method="post" action="<?php echo esc_url( $this->plugin_tmp_url . '/' . $wpdb->log_table_name . '.csv' ); ?>" />
					<?php wp_nonce_field( 'mff_download', 'media_from_ftp_download' ); ?>
					<div>
					<input type="hidden" name="mediafromftp_download" value="1" />
					<?php submit_button( __( 'Download CSV', 'media-from-ftp' ), 'large', '', false ); ?>
					</div>
				</form>
				<?php
			}
			?>
			</div>
			<div style="clear: both;"></div>
			<div style="display: block; padding: 10px 10px">
			<?php
			$allowed_html = array(
				'table' => array(),
				'tr'  => array(),
				'td'  => array(),
				'th'  => array(
					'align'  => array(),
					'style'  => array(),
				),
			);
			echo wp_kses( $html, $allowed_html );
			?>
			</div>
			<?php
		} else {
			if ( $mediafromftp_settings['log'] ) {
				echo '<div class="notice notice-info is-dismissible"><ul><li>' . esc_html__( 'There is no log.', 'media-from-ftp' ) . '</li></ul></div>';
			}
		}
		?>
		</div>

		</div>

		<?php

	}

	/** ==================================================
	 * Sub Menu
	 *
	 * @since 1.00
	 */
	public function medialibrary_import_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$scriptname = admin_url( 'admin.php?page=mediafromftp-import' );

		?>
		<div class="wrap">
		<h2>Media from FTP <a href="<?php echo esc_url( $scriptname ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Import' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-search-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Search & Register', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( $this->is_add_on_activate['wpcron'] ) {
				$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
				$mediafromftpaddonwpcron->mediafromftp_event_link_html();
				unset( $mediafromftpaddonwpcron );
			}
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Log', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-addons' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add-Ons', 'media-from-ftp' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

		<div id="mediafromftp-loading"><img src="<?php echo esc_url( $this->plugin_base_url ) . '/css/loading.gif'; ?>"></div>
		<div id="medialibraryimport-loading-container">

		<?php
		if ( isset( $_POST['media_from_ftp_file_load'] ) && ! empty( $_POST['media_from_ftp_file_load'] ) ) {
			if ( check_admin_referer( 'mff_file_load', 'media_from_ftp_file_load' ) ) {
				if ( isset( $_FILES['filename']['name'] ) && ! empty( $_FILES['filename']['name'] ) ) {
					if ( isset( $_FILES['filename']['tmp_name'] ) && ! empty( $_FILES['filename']['tmp_name'] ) ) {
						$filename = sanitize_text_field( wp_unslash( $_FILES['filename']['tmp_name'] ) );
					}
					$name = basename( $filename );
					move_uploaded_file( $filename, $this->plugin_tmp_dir . '/' . $name );

					$mediafromftp = new MediaFromFtp();
					?>
					<h4><?php esc_html_e( 'Assign Authors', 'media-from-ftp' ); ?></h4>
					<?php
					$allowed_author_select_html = array(
						'strong'    => array(),
						'div'    => array(
							'style'  => array(),
						),
						'form'  => array(
							'method'  => array(),
							'action'  => array(),
						),
						'select'  => array(
							'name'  => array(),
						),
						'option'  => array(
							'value'  => array(),
							'select'  => array(),
						),
						'input' => array(
							'name'  => array(),
							'type'  => array(),
							'size'  => array(),
							'id'    => array(),
							'class' => array(),
							'value' => array(),
						),
					);
					echo wp_kses( $mediafromftp->author_select( $this->plugin_tmp_dir . '/' . $name ), $allowed_author_select_html );
				}
			}
		} else if ( isset( $_POST['media_from_ftp_select_author'] ) && ! empty( $_POST['media_from_ftp_select_author'] ) ) {
			if ( check_admin_referer( 'mff_select_author', 'media_from_ftp_select_author' ) ) {
				if ( ! empty( $_POST['mediafromftp_select_author'] ) && ! empty( $_POST['mediafromftp_xml_file'] ) ) {
					if ( validate_file( wp_unslash( $_POST['mediafromftp_xml_file'] ) ) ) {
						echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'No such file exists! Double check the name and try again.' ) . '</li></ul></div>';
						?>
						<form method="post" action="<?php echo esc_url( $scriptname ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( 'mff_file_load', 'media_from_ftp_file_load' ); ?>
						<h4><?php esc_html_e( 'Select File' ); ?>[WordPress eXtended RSS (WXR)(.xml)]</h4>
						<div><input name="filename" type="file" size="80" /></div>
						<div>
						<?php submit_button( __( 'File Load', 'media-from-ftp' ), 'large', '', false ); ?>
						</div>
						</form>
						<?php
					} else {
						?>
						<h4><?php esc_html_e( 'Ready to import. Press the following button to start the import.', 'media-from-ftp' ); ?></h4>
						<form method="post" id="medialibraryimport_ajax_update">
							<?php submit_button( __( 'Import' ), 'primary', '', false ); ?>
						</form>
						<?php
					}
				}
			}
		} else {
			?>
			<form method="post" action="<?php echo esc_url( $scriptname ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'mff_file_load', 'media_from_ftp_file_load' ); ?>
			<h4><?php esc_html_e( 'Select File' ); ?>[WordPress eXtended RSS (WXR)(.xml)]</h4>
			<div><input name="filename" type="file" size="80" /></div>
			<div>
			<?php submit_button( __( 'File Load', 'media-from-ftp' ), 'large', '', false ); ?>
			</div>
			</form>
			<?php
		}
		?>

		</div>
		</div>

		<?php
	}

	/** ==================================================
	 * Sub Menu
	 *
	 * @since 1.00
	 */
	public function addons_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$scriptname = admin_url( 'admin.php?page=mediafromftp-addons' );

		?>
		<div class="wrap">

		<h2>Media from FTP <a href="<?php echo esc_url( $scriptname ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Add-Ons', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-search-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Search & Register', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( $this->is_add_on_activate['wpcron'] ) {
				$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
				$mediafromftpaddonwpcron->mediafromftp_event_link_html();
				unset( $mediafromftpaddonwpcron );
			}
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-log' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Log', 'media-from-ftp' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromftp-import' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Import' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

		<div style="width: 300px; height: 100%; margin: 10px; padding: 10px; border: #CCC 2px solid; float: left;">
		<h4>Media from FTP Add On Commandline</h4>
		<div style="margin: 5px; padding: 5px;"><?php esc_html_e( 'This add-on can use "Media from FTP" on the command-line.', 'media-from-ftp' ); ?></div>
		<div style="margin: 5px; padding: 5px;">
		<li><?php esc_html_e( 'The execution of the command line is supported.(lib/mediafromftpcmd.php)', 'media-from-ftp' ); ?></li>
		</div>
		<p>
		<?php
		if ( is_dir( $this->plugin_dir . '/media-from-ftp-add-on-cli' ) ) {
			?>
			<div style="margin: 5px; padding: 5px;"><strong>
			<?php
			esc_html_e( 'Installed', 'media-from-ftp' );
			?>
			 & 
			<?php
			if ( $this->is_add_on_activate['cli'] ) {
				esc_html_e( 'Activated', 'media-from-ftp' );
			} else {
				esc_html_e( 'Deactivated', 'media-from-ftp' );
			}
			?>
			</strong></div>
			<?php
		} else {
			?>
			<div>
			<a href="<?php echo esc_url( __( 'https://shop.riverforest-wp.info/media-from-ftp-add-on-cli/', 'media-from-ftp' ) ); ?>" target="_blank" class="page-title-action"><?php esc_html_e( 'BUY', 'media-from-ftp' ); ?></a>
			</div>
			<?php
		}
		?>
		</div>

		<div style="width: 300px; height: 100%; margin: 10px; padding: 10px; border: #CCC 2px solid; float: left;">
		<h4>Media from FTP Add On Wp Cron</h4>
		<div style="margin: 5px; padding: 5px;"><?php esc_html_e( 'This add-on can register and execute Cron Event with multiple settings by "Media from FTP".', 'media-from-ftp' ); ?></div>
		<div style="margin: 5px; padding: 5px;">
		<li><?php esc_html_e( 'Can start multiple Cron Events with multiple settings.', 'media-from-ftp' ); ?></li>
		<li><?php esc_html_e( 'Can add intervals of schedule.', 'media-from-ftp' ); ?></li>
		</div>
		<p>
		<?php
		if ( is_dir( $this->plugin_dir . '/media-from-ftp-add-on-wpcron' ) ) {
			?>
			<div style="margin: 5px; padding: 5px;"><strong>
			<?php
			esc_html_e( 'Installed', 'media-from-ftp' );
			?>
			 & 
			<?php
			if ( $this->is_add_on_activate['wpcron'] ) {
				esc_html_e( 'Activated', 'media-from-ftp' );
			} else {
				esc_html_e( 'Deactivated', 'media-from-ftp' );
			}
			?>
			</strong></div>
			<?php
		} else {
			?>
			<div>
			<a href="<?php echo esc_url( __( 'https://shop.riverforest-wp.info/media-from-ftp-add-on-wpcron/', 'media-from-ftp' ) ); ?>" target="_blank" class="page-title-action"><?php esc_html_e( 'BUY', 'media-from-ftp' ); ?></a>
			</div>
			<?php
		}
		?>
		</div>

		<div style="width: 300px; height: 100%; margin: 10px; padding: 10px; border: #CCC 2px solid; float: left;">
		<h4>Media from FTP Add On Category</h4>
		<div style="margin: 5px; padding: 5px;"><?php esc_html_e( 'This Add-on When registering by "Media from FTP", add Category to Media Library.', 'media-from-ftp' ); ?></div>
		<div style="margin: 5px; padding: 5px;">
		<li><?php esc_html_e( 'Works with next plugin.', 'media-from-ftp' ); ?> [<a style="text-decoration: none;" href="https://wordpress.org/plugins/wp-media-library-categories/" target="_blank">Media Library Categories</a>] [<a style="text-decoration: none;" href="https://wordpress.org/plugins/enhanced-media-library/" target="_blank">Enhanced Media Library</a>] [<a style="text-decoration: none;" href="https://wordpress.org/plugins/media-library-assistant/" target="_blank">Media Library Assistant</a>]</li>
		</div>
		<p>
		<?php
		if ( is_dir( $this->plugin_dir . '/media-from-ftp-add-on-category' ) ) {
			?>
			<div style="margin: 5px; padding: 5px;"><strong>
			<?php
			esc_html_e( 'Installed', 'media-from-ftp' );
			?>
			 & 
			<?php
			if ( $this->is_add_on_activate['category'] ) {
				esc_html_e( 'Activated', 'media-from-ftp' );
			} else {
				esc_html_e( 'Deactivated', 'media-from-ftp' );
			}
			?>
			</strong></div>
			<?php
		} else {
			?>
			<div>
			<a href="<?php echo esc_url( __( 'https://shop.riverforest-wp.info/media-from-ftp-add-on-category/', 'media-from-ftp' ) ); ?>" target="_blank" class="page-title-action"><?php esc_html_e( 'BUY', 'media-from-ftp' ); ?></a>
			</div>
			<?php
		}
		?>
		</div>

		<div style="width: 300px; height: 100%; margin: 10px; padding: 10px; border: #CCC 2px solid; float: left;">
		<h4>Media from FTP Add On Exif</h4>
		<div style="margin: 5px; padding: 5px;"><?php esc_html_e( 'This Add-on When registering by "Media from FTP", add Exif to Media Library Caption.', 'media-from-ftp' ); ?></div>
		<div style="margin: 5px; padding: 5px;">
		<li><?php esc_html_e( 'Sort each Exif data to an arbitrary position and insert it into the caption as text.', 'media-from-ftp' ); ?></li>
		<li><a style="text-decoration: none;" href="https://codex.wordpress.org/Function_Reference/wp_read_image_metadata#Return%20Values" target="_blank">Exif</a></li>
		</div>

		<p>
		<?php
		if ( is_dir( $this->plugin_dir . '/media-from-ftp-add-on-exif' ) ) {
			?>
			<div style="margin: 5px; padding: 5px;"><strong>
			<?php
			esc_html_e( 'Installed', 'media-from-ftp' );
			?>
			 & 
			<?php
			if ( $this->is_add_on_activate['exif'] ) {
				esc_html_e( 'Activated', 'media-from-ftp' );
			} else {
				esc_html_e( 'Deactivated', 'media-from-ftp' );
			}
			?>
			</strong></div>
			<?php
		} else {
			?>
			<div>
			<a href="<?php echo esc_url( __( 'https://shop.riverforest-wp.info/media-from-ftp-add-on-exif/', 'media-from-ftp' ) ); ?>" target="_blank" class="page-title-action"><?php esc_html_e( 'BUY', 'media-from-ftp' ); ?></a>
			</div>
			<?php
		}
		?>
		</div>

		<?php
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @param int $submenu  submenu.
	 * @since 2.36
	 */
	private function options_updated( $submenu ) {

		$mediafromftp = new MediaFromFtp();

		$mediafromftp_settings = get_option( $this->wp_options_name() );

		$addonwpcron = false;
		if ( $this->is_add_on_activate['wpcron'] ) {
			$mediafromftpaddonwpcron = new MediaFromFtpAddOnWpcron();
			$addonwpcron = true;
		}

		$allowed_notice_html = array(
			'div'   => array(
				'class' => array(),
			),
			'ul' => array(),
			'li' => array(),
		);

		switch ( $submenu ) {
			case 1:
				if ( isset( $_POST['media_from_ftp_settings'] ) && ! empty( $_POST['media_from_ftp_settings'] ) ) {

					if ( check_admin_referer( 'mff_settings', 'media_from_ftp_settings' ) ) {
						if ( ! empty( $_POST['mediafromftp_dateset'] ) ) {
							$mediafromftp_settings['dateset'] = sanitize_text_field( wp_unslash( $_POST['mediafromftp_dateset'] ) );
						}
						if ( ! empty( $_POST['mediafromftp_datefixed'] ) ) {
							$mediafromftp_settings['datefixed'] = sanitize_text_field( wp_unslash( $_POST['mediafromftp_datefixed'] ) );
						}
						if ( ! empty( $_POST['mediafromftp_datetimepicker'] ) ) {
							$mediafromftp_settings['datetimepicker'] = 1;
						} else {
							$mediafromftp_settings['datetimepicker'] = false;
						}
						if ( ! empty( $_POST['mediafromftp_max_execution_time'] ) ) {
							$mediafromftp_settings['max_execution_time'] = intval( $_POST['mediafromftp_max_execution_time'] );
						}
						if ( ! empty( $_POST['mediafromftp_character_code'] ) ) {
							$mediafromftp_settings['character_code'] = sanitize_text_field( wp_unslash( $_POST['mediafromftp_character_code'] ) );
						}
						if ( ! empty( $_POST['mediafromftp_cron_apply'] ) ) {
							$mediafromftp_settings['cron']['apply'] = 1;
						} else {
							$mediafromftp_settings['cron']['apply'] = false;
						}
						if ( ! empty( $_POST['mediafromftp_cron_schedule'] ) ) {
							$mediafromftp_settings['cron']['schedule'] = sanitize_text_field( wp_unslash( $_POST['mediafromftp_cron_schedule'] ) );
						}
						if ( ! empty( $_POST['mediafromftp_cron_limit_number'] ) ) {
							$mediafromftp_settings['cron']['limit_number'] = intval( $_POST['mediafromftp_cron_limit_number'] );
						} else {
							$mediafromftp_settings['cron']['limit_number'] = false;
						}
						if ( ! empty( $_POST['mediafromftp_cron_mail_apply'] ) ) {
							$mediafromftp_settings['cron']['mail_apply'] = 1;
						} else {
							$mediafromftp_settings['cron']['mail_apply'] = false;
						}
						if ( ! empty( $_POST['mediafromftp_caption_apply'] ) ) {
							$mediafromftp_settings['caption']['apply'] = 1;
						} else {
							$mediafromftp_settings['caption']['apply'] = false;
						}
						if ( ! empty( $_POST['mediafromftp_exif_text'] ) ) {
							$mediafromftp_settings['caption']['exif_text'] = wp_strip_all_tags( wp_unslash( $_POST['mediafromftp_exif_text'] ) );
						}
						if ( ! empty( $_POST['mediafromftp_exif_default'] ) ) {
							$mediafromftp_settings['caption']['exif_text'] = '%title% %credit% %camera% %caption% %created_timestamp% %copyright% %aperture% %shutter_speed% %iso% %focal_length% %white_balance%';
						}
						if ( ! empty( $_POST['mediafromftp_apply_log'] ) ) {
							$mediafromftp_settings['log'] = 1;
						} else {
							$mediafromftp_settings['log'] = false;
						}
						if ( ! empty( $_POST['mediafromftp_search_limit_number'] ) ) {
							$search_limit_number = intval( $_POST['mediafromftp_search_limit_number'] );
							if ( $search_limit_number > 0 ) {
								$mediafromftp_settings['search_limit_number'] = $search_limit_number;
								if ( $search_limit_number < 100 ) {
									$mediafromftp_settings['search_limit_number'] = 100;
								}
							} else {
								$mediafromftp_settings['search_limit_number'] = 100000;
							}
						} else {
							$mediafromftp_settings['search_limit_number'] = 100000;
						}
						if ( ! empty( $_POST['mlc_category'] ) ) {
							$mediafromftp_settings['mlcc'] = implode( ',', array_map( 'sanitize_text_field', wp_unslash( $_POST['mlc_category'] ) ) );
						} else {
							$mediafromftp_settings['mlcc'] = null;
						}
						if ( ! empty( $_POST['eml_category'] ) ) {
							$mediafromftp_settings['emlc'] = implode( ',', array_map( 'sanitize_text_field', wp_unslash( $_POST['eml_category'] ) ) );
						} else {
							$mediafromftp_settings['emlc'] = null;
						}
						if ( ! empty( $_POST['mla_category'] ) ) {
							$mediafromftp_settings['mlac'] = implode( ',', array_map( 'sanitize_text_field', wp_unslash( $_POST['mla_category'] ) ) );
						} else {
							$mediafromftp_settings['mlac'] = null;
						}
						if ( ! empty( $_POST['mla_tag'] ) ) {
							$mediafromftp_settings['mlat'] = implode( ',', array_map( 'sanitize_text_field', wp_unslash( $_POST['mla_tag'] ) ) );
						} else {
							$mediafromftp_settings['mlat'] = null;
						}
						update_option( $this->wp_options_name(), $mediafromftp_settings );
						if ( ! empty( $_POST['move_yearmonth_folders'] ) ) {
							update_option( 'uploads_use_yearmonth_folders', 1 );
						} else {
							update_option( 'uploads_use_yearmonth_folders', 0 );
						}
						echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
					}
				}
				break;
			case 2:
				if ( isset( $_POST['media_from_ftp_search'] ) && ! empty( $_POST['media_from_ftp_search'] ) ) {
					if ( check_admin_referer( 'mff_search', 'media_from_ftp_search' ) ) {
						if ( ! empty( $_POST['mediafromftp_pagemax'] ) ) {
							$mediafromftp_settings['pagemax'] = intval( $_POST['mediafromftp_pagemax'] );
						}
						if ( ! empty( $_POST['searchdir'] ) ) {
							$searchdir = urldecode( wp_strip_all_tags( wp_unslash( $_POST['searchdir'] ) ) );
							if ( strpos( realpath( wp_normalize_path( ABSPATH . $searchdir ) ), $this->upload_dir ) === false ) {
								$searchdir = $this->upload_path;
								$mediafromftp_settings['basedir'] = $this->upload_path;
							}
							$mediafromftp_settings['searchdir'] = $searchdir;
						} else {
							if ( $this->upload_path <> $mediafromftp_settings['basedir'] ) {
								$mediafromftp_settings['searchdir'] = $this->upload_path;
								$mediafromftp_settings['basedir'] = $this->upload_path;
							}
						}
						if ( ! empty( $_POST['ext2type'] ) ) {
							$ext2typefilter = sanitize_text_field( wp_unslash( $_POST['ext2type'] ) );
						} else {
							$ext2typefilter = $mediafromftp_settings['ext2typefilter'];
						}
						if ( ! empty( $_POST['extension'] ) ) {
							if ( 'all' === $_POST['extension'] ) {
								$mediafromftp_settings['extfilter'] = 'all';
							} else {
								if ( 'all' === $ext2typefilter || wp_ext2type( sanitize_text_field( wp_unslash( $_POST['extension'] ) ) ) === $ext2typefilter ) {
									$mediafromftp_settings['extfilter'] = sanitize_text_field( wp_unslash( $_POST['extension'] ) );
								} else {
									$mediafromftp_settings['extfilter'] = 'all';
								}
							}
						}
						$mediafromftp_settings['ext2typefilter'] = $ext2typefilter;
						if ( isset( $_POST['search_display_metadata'] ) ) {
							$mediafromftp_settings['search_display_metadata'] = sanitize_text_field( wp_unslash( $_POST['search_display_metadata'] ) );
						}
						if ( ! empty( $_POST['mediafromftp_exclude'] ) ) {
							$mediafromftp_settings['exclude'] = sanitize_text_field( wp_unslash( $_POST['mediafromftp_exclude'] ) );
						}
						if ( isset( $_POST['mediafromftp_recursive_search'] ) ) {
							$mediafromftp_settings['recursive_search'] = sanitize_text_field( wp_unslash( $_POST['mediafromftp_recursive_search'] ) );
						}
						if ( isset( $_POST['mediafromftp_thumb_deep_search'] ) ) {
							$mediafromftp_settings['thumb_deep_search'] = sanitize_text_field( wp_unslash( $_POST['mediafromftp_thumb_deep_search'] ) );
						}
						update_option( $this->wp_options_name(), $mediafromftp_settings );
						$screen = get_current_screen();
						if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-settings' === $screen->id ) {
							echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
						}
					}
				}
				break;
			case 3:
				if ( isset( $_POST['media_from_ftp_clear_cash'] ) && ! empty( $_POST['media_from_ftp_clear_cash'] ) ) {
					if ( check_admin_referer( 'mff_clear_cash', 'media_from_ftp_clear_cash' ) ) {
						if ( ! empty( $_POST['mediafromftp_clear_cash'] ) ) {
							$del_cash_count = $mediafromftp->delete_all_cash();
							if ( $del_cash_count > 0 ) {
								echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Thumbnails Cache', 'media-from-ftp' ) . ' --> ' . __( 'Delete' ) ) . '</li></ul></div>';
							} else {
								echo '<div class="notice notice-info is-dismissible"><ul><li>' . esc_html__( 'No Thumbnails Cache', 'media-from-ftp' ) . '</li></ul></div>';
							}
						}
					}
				}
				break;
			case 4:
				/* for media-from-ftp-add-on-wpcron */
				if ( isset( $_POST['media_from_ftp_run_cron'] ) && ! empty( $_POST['media_from_ftp_run_cron'] ) ) {
					if ( check_admin_referer( 'mff_run_cron', 'media_from_ftp_run_cron' ) ) {
						if ( $addonwpcron ) {
							if ( ! empty( $_POST['mediafromftp_run_cron'] ) ) {
								$mediafromftp_cron_events = get_option( $this->wp_add_on_wpcron_events_name() );
								if ( ! empty( $_POST['cron-run'] ) ) {
									$option_name = sanitize_text_field( wp_unslash( $_POST['cron-run'] ) );
									echo wp_kses( $mediafromftpaddonwpcron->CronRun( $option_name ), $allowed_notice_html );
								} elseif ( ! empty( $_POST['cron-start'] ) ) {
									$option_name = sanitize_text_field( wp_unslash( $_POST['cron-start'] ) );
									echo wp_kses( $mediafromftpaddonwpcron->CronRunStart( $option_name ), $allowed_notice_html );
								} elseif ( ! empty( $_POST['cron-stop'] ) ) {
									$option_name = sanitize_text_field( wp_unslash( $_POST['cron-stop'] ) );
									echo wp_kses( $mediafromftpaddonwpcron->CronRunStop( $option_name ), $allowed_notice_html );
								}
							}
						} else {
							$mediafromftp_settings['cron']['apply'] = false;
							update_option( $this->wp_options_name(), $mediafromftp_settings );
						}
					}
				}
				break;
			case 5:
				/* for media-from-ftp-add-on-wpcron */
				if ( isset( $_POST['media_from_ftp_add_schedule'] ) && ! empty( $_POST['media_from_ftp_add_schedule'] ) ) {
					if ( check_admin_referer( 'mff_add_schedule', 'media_from_ftp_add_schedule' ) ) {
						if ( ! empty( $_POST['mediafromftp_add_schedule'] ) ) {
							if ( ! empty( $_POST['mediafromftp_cron_schedule_innername'] ) && ! empty( $_POST['mediafromftp_cron_schedule_secounds'] ) && ! empty( $_POST['mediafromftp_cron_schedule_viewname'] ) ) {
								$mediafromftp_cron_intervals_tbl = get_option( 'mediafromftp_event_intervals' );
								$innername = sanitize_text_field( wp_unslash( $_POST['mediafromftp_cron_schedule_innername'] ) );
								$secounds = intval( $_POST['mediafromftp_cron_schedule_secounds'] );
								$viewname = sanitize_text_field( wp_unslash( $_POST['mediafromftp_cron_schedule_viewname'] ) );
								$mediafromftp_cron_intervals_tbl[ $innername ] = array(
									'interval' => $secounds,
									'display' => $viewname,
								);
								update_option( 'mediafromftp_event_intervals', $mediafromftp_cron_intervals_tbl );
								echo wp_kses( $mediafromftpaddonwpcron->mediafromftp_schedule_notice_html( $submenu ), $allowed_notice_html );
							}
						}
					}
				}
				break;
			case 6:
				/* for media-from-ftp-add-on-wpcron */
				if ( isset( $_POST['media_from_ftp_add_schedule_delete'] ) && ! empty( $_POST['media_from_ftp_add_schedule_delete'] ) ) {
					if ( check_admin_referer( 'mff_add_schedule_delete', 'media_from_ftp_add_schedule_delete' ) ) {
						if ( ! empty( $_POST['mediafromftp_add_schedule_delete'] ) ) {
							if ( ! empty( $_POST['mediafromftp_cron_schedule_delete'] ) ) {
								$delete_keys = array_map( 'sanitize_text_field', wp_unslash( $_POST['mediafromftp_cron_schedule_delete'] ) );
								$mediafromftp_cron_intervals_tbl = get_option( 'mediafromftp_event_intervals' );
								foreach ( $delete_keys as $key ) {
									unset( $mediafromftp_cron_intervals_tbl[ $key ] );
								}
								update_option( 'mediafromftp_event_intervals', $mediafromftp_cron_intervals_tbl );
								echo wp_kses( $mediafromftpaddonwpcron->mediafromftp_schedule_notice_html( $submenu ), $allowed_notice_html );
							}
						}
					}
				}
				break;
			case 7:
				/* for media-from-ftp-add-on-wpcron */
				if ( isset( $_POST['media_from_ftp_settings_cron_event_create'] ) && ! empty( $_POST['media_from_ftp_settings_cron_event_create'] ) ) {
					if ( check_admin_referer( 'mff_settings_cron_event_create', 'media_from_ftp_settings_cron_event_create' ) ) {
						if ( ! empty( $_POST['mediafromftp_cron_event_create'] ) ) {
							if ( function_exists( 'wp_date' ) ) {
								$event_id = wp_date( 'Y-m-d-H-i-s' );
							} else {
								$event_id = date_i18n( 'Y-m-d-H-i-s' );
							}
							$event_option_name = 'mediafromftp_cronevent-' . $event_id;
							$mediafromftp_cron_events = get_option( $this->wp_add_on_wpcron_events_name(), array() );
							$mediafromftp_cron_events[ $event_id ] = $event_option_name;
							update_option( $this->wp_add_on_wpcron_events_name(), $mediafromftp_cron_events );
							update_option( $event_option_name, $mediafromftp_settings );
							echo wp_kses( $mediafromftpaddonwpcron->mediafromftp_cronevent_create_html(), $allowed_notice_html );
						}
					}
				}
				break;
		}

	}

	/** ==================================================
	 * Robots txt
	 *
	 * @param string $output  output.
	 * @return string $output  output.
	 * @since 9.75
	 */
	public function custom_robots_txt( $output ) {

		$public = get_option( 'blog_public' );
		if ( '0' != $public ) {
			$output .= "\n" . 'Disallow: ' . $this->plugin_disallow_tmp_dir . "\n";
		}

		return $output;

	}

	/** ==================================================
	 * Notices
	 *
	 * @since 9.84
	 */
	public function notices() {

		$screen = get_current_screen();
		if ( is_object( $screen ) && 'media-from-ftp_page_mediafromftp-search-register' === $screen->id ) {
			$html1 = '<strong>' . __( 'Organize my uploads into month- and year-based folders' ) . '</strong>';
			$link_url1 = admin_url( 'options-media.php' );
			$link1 = '<a href="' . $link_url1 . '">' . __( 'Media Settings' ) . '</a>';
			$link_url2 = admin_url( 'admin.php?page=mediafromftp-settings' );
			$link2 = '<a href="' . $link_url2 . '">' . __( 'Settings' ) . '</a>';
			if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
				/* translators: %1$s: message %2$s: media settings %3$s: settings */
				echo '<div class="notice notice-warning is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( '"%1$s" is checked. This is the default setting for the WordPress Media Library. In this setting, the file is moved to month- and year-based folders. If you do not want to move, please uncheck.(%2$s,%3$s)', 'media-from-ftp' ), $html1, $link1, $link2 ) ) . '</li></ul></div>';
			}
		}
		if ( $this->is_my_plugin_screen() ) {
			if ( function_exists( 'extend_media_upload_load_textdomain' ) ) {
				$html2 = '<strong>Extend Media Upload</strong>';
				$html3 = '<strong>' . __( 'Update Media' ) . '</strong>';
				if ( is_multisite() ) {
					$link_url3 = network_admin_url( 'plugins.php' );
				} else {
					$link_url3 = admin_url( 'plugins.php' );
				}
				$link3 = '<strong><a href="' . $link_url3 . '">' . __( 'Plugins' ) . '</a></strong>';
				/* translators: %1$s: plugin %2$s: message %3$s: plugin menu */
				echo '<div class="notice notice-error is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( '"%1$s" is activate. When "%2$s", it will be overwritten with "%1$s" setting. Please deactivate.(%3$s)', 'media-from-ftp' ), $html2, $html3, $link3 ) ) . '</li></ul></div>';
			}
		}

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

	/** ==================================================
	 * Add on
	 *
	 * @return string $this->wp_add_on_wpcron_events_name()
	 * @since 10.09
	 */
	private function wp_add_on_wpcron_events_name() {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include_once( ABSPATH . 'wp-includes/pluggable.php' );
		}
		return 'mediafromftp_add_on_wpcron_events_' . get_current_user_id();
	}

}


