<?php
/**
 * Plugin Name: Media from FTP
 * Plugin URI:  https://wordpress.org/plugins/media-from-ftp/
 * Description: Register to media library from files that have been uploaded by FTP.
 * Version:     11.01
 * Author:      Katsushi Kawamori
 * Author URI:  https://riverforest-wp.info/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: media-from-ftp
 * Domain Path: /languages
 *
 * @package Media from FTP
 */

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

add_action( 'plugins_loaded', 'media_from_ftp_load_textdomain' );
/** ==================================================
 * i18n
 *
 * @since 1.00
 */
function media_from_ftp_load_textdomain() {
	load_plugin_textdomain( 'media-from-ftp' );
}

if ( ! class_exists( 'MediaFromFtpRegist' ) ) {
	require_once( dirname( __FILE__ ) . '/req/class-mediafromftpregist.php' );
}
if ( ! class_exists( 'MediaFromFtpAdmin' ) ) {
	require_once( dirname( __FILE__ ) . '/req/class-mediafromftpadmin.php' );
}
if ( ! class_exists( 'MediaFromFtpAjax' ) ) {
	require_once( dirname( __FILE__ ) . '/req/class-mediafromftpajax.php' );
}



