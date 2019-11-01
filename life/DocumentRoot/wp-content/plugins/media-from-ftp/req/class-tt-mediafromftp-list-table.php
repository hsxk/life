<?php
/**
 * Media from FTP
 *
 * @package    Media from FTP
 * @subpackage MediaFromFtp List Table
 * reference   Custom List Table Example
 *             https://wordpress.org/plugins/custom-list-table-example/
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

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

/** ==================================================
 * List table
 */
class TT_MediaFromFtp_List_Table extends WP_List_Table {

	/** ==================================================
	 * Max items
	 *
	 * @var $max_items  max_items.
	 */
	public $max_items;

	/** ==================================================
	 * Add on active
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

		$category_active = false;
		if ( function_exists( 'media_from_ftp_add_on_category_load_textdomain' ) ) {
			include_once $plugin_dir . '/media-from-ftp-add-on-category/inc/MediaFromFtpAddOnCategory.php';
			$category_active = true;
		}
		if ( function_exists( 'media_from_ftp_add_on_exif_load_textdomain' ) ) {
			include_once $plugin_dir . '/media-from-ftp-add-on-exif/inc/MediaFromFtpAddOnExif.php';
		}
		if ( function_exists( 'media_from_ftp_add_on_cli_load_textdomain' ) ) {
			require_once( $plugin_dir . '/media-from-ftp-add-on-cli/req/MediaFromFtpCli.php' );
		}
		if ( function_exists( 'media_from_ftp_add_on_wpcron_load_textdomain' ) ) {
			include_once $plugin_dir . '/media-from-ftp-add-on-wpcron/inc/MediaFromFtpAddOnWpcron.php';
		}

		if ( ! class_exists( 'MediaFromFtp' ) ) {
			include_once $plugin_base_dir . '/inc/class-mediafromftp.php';
		}

		/* Media Library Categories */
		$mlc_active = false;
		if ( class_exists( 'wpMediaLibraryCategories' ) ) {
			$mlc_active = true;
		}

		/* Enhanced Media Library */
		$eml_active = false;
		if ( function_exists( 'wpuxss_eml_on_plugins_loaded' ) ) {
			$eml_active = true;
		}

		/* Media Library Assistant */
		$mla_active = false;
		if ( function_exists( 'mla_name_conflict_reporting_action' ) ) {
			$mla_active = true;
		}

		$this->is_add_on_activate = array(
			'category'  => $category_active,
			'mlc'       => $mlc_active,
			'eml'       => $eml_active,
			'mla'       => $mla_active,
		);

		global $status, $page;
		/* Set parent defaults */
		parent::__construct(
			array(
				'singular'  => 'new_url_attaches',
				'ajax'      => false,
			)
		);

	}

	/** ==================================================
	 * Read data
	 *
	 * @since 9.50
	 */
	private function read_data() {

		$mediafromftp = new MediaFromFtp();
		$mediafromftp_settings = get_option( $this->wp_options_name() );
		$pagemax = $mediafromftp_settings['pagemax'];
		$searchdir = $mediafromftp_settings['searchdir'];
		$ext2typefilter = $mediafromftp_settings['ext2typefilter'];
		$extfilter = $mediafromftp_settings['extfilter'];
		$max_execution_time = $mediafromftp_settings['max_execution_time'];

		$mlccs = explode( ',', $mediafromftp_settings['mlcc'] );
		$emlcs = explode( ',', $mediafromftp_settings['emlc'] );
		$mlacs = explode( ',', $mediafromftp_settings['mlac'] );
		$mlats = explode( ',', $mediafromftp_settings['mlat'] );

		$document_root = ABSPATH . $searchdir;

		$mediafromftp->mb_initialize( $mediafromftp_settings['character_code'] );
		$document_root = $mediafromftp->mb_encode_multibyte( $document_root, $mediafromftp_settings['character_code'] );

		if ( strstr( $searchdir, '../' ) ) {
			$document_root = realpath( $document_root );
		}

		global $wpdb;
		$attachments = $wpdb->get_results(
			"
						SELECT ID
						FROM $wpdb->posts
						WHERE post_type = 'attachment'
						"
		);

		$extpattern = $mediafromftp->extpattern( $extfilter );
		$files = $mediafromftp->scan_file( $document_root, $extpattern, $mediafromftp_settings );

		$count = 0;
		$listtable_array = array();
		foreach ( $files as $file ) {
			/* Input URL */
			list($new_file, $ext, $new_url) = $mediafromftp->input_url( $file, $attachments, $mediafromftp_settings['character_code'], $mediafromftp_settings['thumb_deep_search'] );
			if ( $new_file ) {
				++$count;
				list($inputhtml, $datetimehtml) = $mediafromftp->input_html( $ext, $file, $new_url, $count, $mediafromftp_settings );

				$mlccategoryhtml = null;
				$emlcategoryhtml = null;
				$mlacategoryhtml = null;
				if ( $this->is_add_on_activate['category'] ) {
					$mediafromftpaddoncategory = new MediaFromFtpAddOnCategory();
					$mlccategoryhtml = $mediafromftpaddoncategory->mlc_category_check_html( $count, $mlccs );
					$emlcategoryhtml = $mediafromftpaddoncategory->eml_category_check_html( $count, $emlcs );
					$mlacategoryhtml = $mediafromftpaddoncategory->mla_category_check_html( $count, $mlacs, $mlats );
					unset( $mediafromftpaddoncategory );
				}

				$listtable_array[] = array(
					'ID'            => $count,
					'title'         => $inputhtml,
					'filename'      => $file,
					'new_url'       => $new_url,
					'datetime'      => $datetimehtml,
					'mlccategories' => $mlccategoryhtml,
					'emlcategories' => $emlcategoryhtml,
					'mlacategories' => $mlacategoryhtml,
				);

			}
		}
		unset( $files, $mediafromftp, $attachments );

		return $listtable_array;

	}

	/** ==================================================
	 * Column default
	 *
	 * @param array  $item  item.
	 * @param string $column_name  column_name.
	 * @since 9.81
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'datetime':
			case 'mlccategories':
			case 'emlcategories':
			case 'mlacategories':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); /* Show the whole array for troubleshooting purposes */
		}
	}

	/** ==================================================
	 * Column title
	 *
	 * @param array $item  item.
	 * @since 9.81
	 */
	public function column_title( $item ) {
		/* Return the title contents */
		return sprintf(
			'%1$s <span style="color:silver"></span>',
			/*$1%s*/ $item['title']
		);
	}

	/** ==================================================
	 * Column checkbox
	 *
	 * @param array $item  item.
	 * @since 9.81
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[%2$s][url]" value="%3$s" form="mediafromftp_ajax_update" />',
			/*%1$s*/ $this->_args['singular'],
			/*%2$s*/ $item['ID'],
			/*%3$s*/ $item['filename']
		);
	}

	/** ==================================================
	 * Get Columns
	 *
	 * @since 9.81
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />', /* Render a checkbox instead of text */
			'title'         => 'URL',
			'datetime'      => __( 'Edit date and time' ),
			'mlccategories' => '<div>' . __( 'Categories' ) . '<div></div>Media Library Categories</div>',
			'emlcategories' => '<div>' . __( 'Categories' ) . '<div></div>Enhanced Media Library</div>',
			'mlacategories' => '<div>' . __( 'Categories' ) . ' & ' . __( 'Tags' ) . '<div></div>Media Library Assistant</div>',
		);
		$mediafromftp_settings = get_option( $this->wp_options_name() );
		if ( 'new' === $mediafromftp_settings['dateset'] || 'fixed' === $mediafromftp_settings['dateset'] ) {
			unset( $columns['datetime'] );
		}

		if ( $this->is_add_on_activate['category'] ) {
			if ( ! $this->is_add_on_activate['mlc'] ) {
				unset( $columns['mlccategories'] );
			}
			if ( ! $this->is_add_on_activate['eml'] ) {
				unset( $columns['emlcategories'] );
			}
			if ( ! $this->is_add_on_activate['mla'] ) {
				unset( $columns['mlacategories'] );
			}
		} else {
			unset( $columns['mlccategories'] );
			unset( $columns['emlcategories'] );
			unset( $columns['mlacategories'] );
		}

		return $columns;
	}

	/** ==================================================
	 * Get Sortable Columns
	 *
	 * @since 9.81
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title'     => array( 'title', false ),
		);
		return $sortable_columns;
	}

	/** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 *
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	public function prepare_items() {

		/**
		 * First, lets decide how many records per page to show
		 */
		$mediafromftp_settings = get_option( $this->wp_options_name() );
		$per_page = $mediafromftp_settings['pagemax'];

		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example
		 * package slightly different than one you might build on your own. In
		 * this example, we'll be using array manipulation to sort and paginate
		 * our data. In a real-world implementation, you will probably want to
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
		$data = $this->read_data();

		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 *
		 * In a real-world situation involving a database, you would probably want
		 * to handle sorting by passing the 'orderby' and 'order' values directly
		 * to a custom query. The returned data will be pre-sorted, and this array
		 * sorting technique would be unnecessary.
		 *
		 * @param array $a  a.
		 * @param array $b  b.
		 */
		function usort_reorder( $a, $b ) {
			/* If no sort, default to title */
			if ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) {
				$orderby = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
			} else {
				$orderby = 'title';
			}
			/* If no order, default to asc */
			if ( isset( $_REQUEST['order'] ) && ! empty( $_REQUEST['order'] ) ) {
				$order = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
			} else {
				$order = 'asc';
			}
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] ); /* Determine sort order */
			return ( 'asc' === $order ) ? $result : -$result; /* Send final sort direction to usort */
		}
		usort( $data, 'usort_reorder' );

		/***********************************************************************
		 * ---------------------------------------------------------------------
		 * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
		 *
		 * In a real-world situation, this is where you would place your query.
		 *
		 * For information on making queries in WordPress, see this Codex entry:
		 * http://codex.wordpress.org/Class_Reference/wpdb
		 *
		 * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
		 * ---------------------------------------------------------------------
		 */

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		$total_items = count( $data );
		$this->max_items = $total_items;

		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                  /* WE have to calculate the total number of items */
				'per_page'    => $per_page,                     /* WE have to determine how many items to show on a page */
				'total_pages' => ceil( $total_items / $per_page ),   /* WE have to calculate the total number of pages */
			)
		);
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


