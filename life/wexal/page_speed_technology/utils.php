<?php
/**
 * ユティリティクラス
 */
class wexal_pst_utils {

	/**
	 * Get service enable status
	 * @param String Service name
	 */
	final public static function service_is_enabled( $service_name ) {
		$ret = shell_exec("systemctl is-enabled {$service_name} 2>/dev/null");
		return 'enabled' == trim($ret);
	}

	/**
	 * Restart service will on enabled
	 */
	final public static function restart_service_on_enabled( $service_name ) {
		if ( self::service_is_enabled( $service_name ) ) {
			return self::restart_service( $service_name );
		}
	}

	final public static function restart_service( $service_name ) {
		$method_name = "restart_{$service_name}";
		if ( method_exists ( __CLASS__, $method_name ) ) {
			$ret = self::$method_name( $service_name );
		} else {
			$ret = shell_exec( "systemctl restart {$service_name} 2>&1" );
		}
		return $ret;
	}

	/**
	 * Restart Nginx service
	 */
	final public static function restart_nginx() {
		return shell_exec( 'kusanagi nginx 2>&1' );
	}

	/**
	 * Restart httpd service
	 */
	final public static function restart_httpd() {
		return shell_exec( 'kusanagi httpd 2>&1' );
	}

	/**
	 * Overwrite shell_exec
	 */
	final public static function shell_exec( $cmd, $echo = false ) {
		$ret = shell_exec( $cmd );
		if ( $echo ) {
			echo $ret;
		} else {
			return $ret;
		}
	}

	/**
	 * Backup origin file and create new file
	 */
	final public static function create_new_pst_file( $file, $new_content ) {
		if ( is_array( $new_content ) ) {
			$new_content = join( "\n", $new_content )."\n";
		}

		if ( file_exists( $file ) ) {
			rename( $file, $file . '.before.pst' );
		}
		file_put_contents( $file, $new_content );
		if ( file_exists( $file . '.before.pst' ) ) {
			self::shell_exec( "diff -u {$file}.before.pst {$file} > {$file}.pst.diff" );
		}
	}

	/**
	 * Restore form backup file
	 */
	final public static function restore_pst_file( $file ) {
		// if ( file_exists( "{$file}.pst.diff" ) ) {
		// 	$old_diff = file_get_contents( "{$file}.pst.diff" );
		// 	$new_diff = self::shell_exec( "diff -u {$file}.before.pst {$file}" );
			
		// 	if ( $new_diff == $old_diff ) {
		// 		rename( "{$file}.before.pst", $file );
		// 	} else {
		// 		self::print_error("Doesn't match [{$file}] pst backup file.");
		// 	}
		// } else
		if ( file_exists( "{$file}.before.pst" ) ) {
			rename( "{$file}.before.pst", $file );
		}
	}

	/**
	 * Output Info 
	 */
	final public static function print_info( $text ) {
		self::print_green( 'Info: '. $text."\n" );
	}

	/**
	 * Output Error
	 */
	final public static function print_error( $text ) {
		self::print_red( 'Error: '. $text."\n" );
	}

	/**
	 * Output Error
	 */
	final public static function print_notice( $text ) {
		self::print_yellow( 'Notice: '. $text."\n" );
	}

	/**
	 * Output red color text
	 */
	final public static function print_red( $text ) {
		echo "\033[31m{$text}\033[m";
	}

	/**
	 * Output green color text
	 */
	final public static function print_green( $text ) {
		echo "\033[32m{$text}\033[m";
	}

	/**
	 * Output yellow color text
	 */
	final public static function print_yellow( $text ) {
		echo "\033[33m{$text}\033[m";
	}

	/**
	 * Output blue color text
	 */
	final public static function print_blue( $text ) {
		echo "\03[34m{$text}\033[m";
	}

	/**
	 * Output purple color text
	 */
	final public static function print_purple( $text ) {
		echo "\033[35m{$text}\033[m";
	}

	/**
	 * Output light blue color text
	 */
	final public static function print_light_blue( $text ) {
		echo "\033[36m{$text}\033[m";
	}
	
} // end class
