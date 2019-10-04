<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class KUSANAGI_Replace {
	static function replace( $content ) {
		$replace_login = '0';
		$replaces = array(

		);

		if ( ! $replace_login && 'wp-login.php' == basename( $_SERVER['SCRIPT_FILENAME'] ) ) {
			return $content;
		}

		if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
			foreach ( $replaces as $reg => $val ) {
				$reg = preg_quote( $reg, '#' );
				$content = preg_replace( "#$reg#", $val, $content );
			}
		}
		return $content;
	}
}
