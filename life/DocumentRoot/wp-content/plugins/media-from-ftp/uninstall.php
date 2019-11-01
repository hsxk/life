<?php
/**
 * Uninstall
 *
 * @package Media from FTP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

/* For Single site */
if ( ! is_multisite() ) {
	foreach ( media_from_ftp_uninstall_option_names() as $option_name ) {
		delete_option( $option_name );
		/* Delete log database */
		$wpdb->log_name = $wpdb->prefix . 'mediafromftp_log';
		$wpdb->query( "DROP TABLE IF EXISTS $wpdb->log_name" );
	}
} else {
	/* For Multisite */
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		foreach ( media_from_ftp_uninstall_option_names() as $option_name ) {
			delete_option( $option_name );
			/* Delete log database */
			$wpdb->log_name = $wpdb->prefix . 'mediafromftp_log';
			$wpdb->query( "DROP TABLE IF EXISTS $wpdb->log_name" );
		}
	}
	switch_to_blog( $original_blog_id );
	/* For site options. */
	foreach ( media_from_ftp_uninstall_option_names() as $option_name ) {
		delete_site_option( $option_name );
	}
}

/* Delete all cache */
$wp_uploads = wp_upload_dir();
$tmp_dir = $wp_uploads['basedir'] . '/media-from-ftp-tmp';
if ( is_ssl() ) {
	$tmp_url = str_replace( 'http:', 'https:', $wp_uploads['baseurl'] ) . '/media-from-ftp-tmp';
} else {
	$tmp_url = $wp_uploads['baseurl'] . '/media-from-ftp-tmp';
}
$del_transients = $wpdb->get_results(
	$wpdb->prepare(
		"
				SELECT	option_value
				FROM	$wpdb->options
				WHERE	option_value LIKE %s
				",
		'%' . $wpdb->esc_like( $tmp_url ) . '%'
	)
);
foreach ( $del_transients as $del_transient ) {
	$delfile = pathinfo( $del_transient->option_value );
	$del_cash_thumb_key = $delfile['filename'];
	$value_del_cash = get_transient( $del_cash_thumb_key );
	if ( false <> $value_del_cash ) {
		delete_transient( $del_cash_thumb_key );
	}
}
$del_cash_thumb_filename = $tmp_dir . '/*.*';
foreach ( glob( $del_cash_thumb_filename ) as $val ) {
	unlink( $val );
}
if ( is_dir( $tmp_dir ) ) {
	rmdir( $tmp_dir );
}

/** ==================================================
 * Uninstall option names
 *
 * @since 1.00
 */
function media_from_ftp_uninstall_option_names() {

	global $wpdb;
	$option_names = array();
	$wp_options = $wpdb->get_results(
		"
					SELECT option_name
					FROM $wpdb->options
					WHERE option_name LIKE '%%mediafromftp_%%'
					"
	);
	foreach ( $wp_options as $wp_option ) {
		$option_names[] = $wp_option->option_name;
	}

	return $option_names;

}


