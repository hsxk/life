<?php
if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
	exit;
}
$pst_control_file = dirname( $_SERVER['DOCUMENT_ROOT'] ) . '/wexal/page_speed_technology/pst.control.php';
require_once( $pst_control_file );

$allowed_hosts = array();
$allowed_content_type = array(
	'text/css',
	'image/gif',
	'image/jpeg',
	'application/javascript',
	'image/png',
	'image/svg+xml',
	'image/webp',
	'font/woff',
	'font/woff2',
);
$content_type_ext = array(
	'css' => 'text/css',
	'gif' => 'image/gif',
	'jpe' => 'image/jpeg',
	'jpeg' => 'image/jpeg',	
	'js' => 'application/javascript',
	'png' => 'image/png',
	'svg' => 'image/svg+xml',
	'svgz' => 'image/svg+xml',
	'woff' => 'font/woff',
	'woff2' => 'font/woff2',
);

if ( isset( $wexal_pst_control->config['options'] ) && $wexal_pst_control->config['options'] ) {
	foreach ( $wexal_pst_control->config['options'] as $mode ) {
		switch ( $mode ) {
			case 'wp' :
				$key = 'wexal_flush';
				break;
			case 'lua' :
				$key = 'body_filter';
				break;
			default :
				$key = false;
		}
		if ( $key && isset( $wexal_pst_control->config[$mode][$key] ) ) {
			foreach ( $wexal_pst_control->config[$mode][$key] as $cmds ) {
				if ( 'proxy' == $cmds['cmd'] ) {
					foreach ( $cmds['args'] as $host ) {
						if ( ! in_array( $host['proxy-host'], $allowed_hosts ) ) {
							$allowed_hosts[] = $host['proxy-host'];
						}
					}
				}
			}
		}
	}
}

if ( preg_match( '#^/_wxpdir/([^\?]+).*$#', $_SERVER['REQUEST_URI'], $m ) ) {
	$_wxpdir_path = $m[1];
} else {
	header( 'HTTP/1.0 404 Not Found' );
	exit;
}

$ext = preg_replace( '/^.*\.([0-9a-zA-Z]{2,4})$/', '$1', $_wxpdir_path );

$url = array();
if ( preg_match( '#^(s/)(.*)$#', $_wxpdir_path, $m ) ) {
	$url['protocol'] = 'https://';
	$proto_path = $m[1];
	$host_path = $m[2];
} else {
	$url['protocol'] = 'http://';
	$proto_path = '';
	$host_path = $_wxpdir_path;
}
if ( preg_match( '#^([^/]+)(/.+)$#', $host_path, $m ) ) {
	$url['host'] = $m[1];
	$url['path'] = $m[2];
} else {
	header( 'HTTP/1.0 400 Bad Request' );
	exit;
}

if ( ! in_array( $url['host'], $allowed_hosts ) ) {
	header( 'HTTP/1.0 400 Bad Request' );
	exit;
}

$host_dir = dirname( dirname( dirname( __FILE__ ) ) ) . '/_wxpdir/' . $proto_path . $url['host'];
$file_path = $host_dir . $url['path'];

$url = implode( '', $url );

// redirect
if ( file_exists( $file_path . '.redirect' ) ) {
	$redirect = parse_url( trim( file_get_contents( $file_path . '.redirect' ) ) );
	if ( $redirect && isset( $redirect['host'] ) ) {
		if ( in_array( $redirect['host'], $allowed_hosts ) ) {
			$path = '/_wxpdir/';
			if ( isset( $redirect['scheme'] ) && 'https' == $redirect['scheme'] ) {
				$path .= 's/';
			}
			$path .= $redirect['host'];
			$path .= $redirect['path'];
			if ( isset( $redirect['query'] ) && $redirect['query'] ) {
				$path .= '?' . $redirect['query'];
			}
			if ( isset( $redirect['fragment'] ) && $redirect['fragment'] ) {
				$path .= '#' . $redirect['fragment'];
			}
			header( 'Location: ' . $path );
			exit;
		} else {
		}
	} else {
		header( 'HTTP/1.0 404 Not Found' );
		exit;
	}
} elseif ( file_exists($file_path . '.original' ) ) {
    $original = trim( file_get_contents( $file_path . '.original' ) );
    header( 'Location: ' . $original );
    exit;
}

$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_HEADER, true );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_ENCODING, '');
$source = curl_exec($ch);
$curl_info = curl_getinfo($ch);
curl_close( $ch );

if ( in_array( $curl_info['http_code'], array( 301, 302 ) ) && isset( $curl_info['redirect_url'] ) ) {
	mkdir( dirname( $file_path ), 0777, true );
	file_put_contents( $file_path . '.redirect', $curl_info['redirect_url'] );
	$redirect = parse_url( $curl_info['redirect_url'] );
	if ( in_array( $redirect['host'], $allowed_hosts ) ) {
		$path = '/_wxpdir/';
		if ( isset( $redirect['scheme'] ) && 'https' == $redirect['scheme'] ) {
			$path .= 's/';
		}
		$path .= $redirect['host'];
		$path .= $redirect['path'];
		if ( isset( $redirect['query'] ) && $redirect['query'] ) {
			$path .= '?' . $redirect['query'];
		}
		if ( isset( $redirect['fragment'] ) && $redirect['fragment'] ) {
			$path .= '#' . $redirect['fragment'];
		}
	} else {
		$path = $curl_info['redirect_url'];
	}
	header( 'Location: ' . $path );
	exit;
} elseif ( $content_type_ext[$ext] != $curl_info['content_type'] ) { 
    mkdir( dirname( $file_path ), 0777, true );
    file_put_contents( $file_path . '.original', $curl_info['url'] );
    header( 'Location: ' . $url );
    exit;
} elseif ( 200 != $curl_info['http_code'] ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit;
}
// TODO redirect check
$content_type_check = false;
foreach ( $allowed_content_type as $content_type ) {
	if ( preg_match( '/^' . preg_quote( $content_type, '/' ) . '/', $curl_info['content_type'] ) ) {
		$content_type_check = true;
		break;
	}
}
if ( ! $content_type_check ) {
	header( 'HTTP/1.0 400 Bad Request' );
	exit;
}


$headerSize = 0;
if ( isset( $curl_info["header_size"] ) && $curl_info["header_size"] != "" ) {
	$headerSize = $curl_info["header_size"];
}
$strHead = substr( $source, 0, $headerSize );
$strBody = substr( $source, $headerSize );

if ( preg_match( '/Last-Modified:(.*)/', $strHead, $m ) ) {
	$modified = strtotime( $m[1] );
} else {
	$modified = time();
}
header( 'Content-Type: ' . $curl_info['content_type'] );
echo $strBody;
mkdir( dirname( $file_path ), 0777, true );
file_put_contents( $file_path, $strBody );
