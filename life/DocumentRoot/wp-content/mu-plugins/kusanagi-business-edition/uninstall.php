<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
/**
 * @link       https://www.prime-strategy.co.jp/
 * @since      1.0.0
 *
 * @package    KUSANAGI_Business_Edition
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
} else {
	global $wpdb;

	delete_option( 'KUSANAGI_BE_message' );
	delete_option( 'KUSANAGI_BE_error' );
	delete_option( 'KUSANAGI_BE_agreement' );

	$results = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_KUSANAGI_BE_%';" );
	foreach ( $results as $result ) {
		$result = preg_replace( '/^_transient_/', '', $result );
		delete_transient( $result );
	}
}
