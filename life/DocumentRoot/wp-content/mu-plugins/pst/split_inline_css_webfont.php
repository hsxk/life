<?php
$query_string = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
$query_array = preg_split( '#__wexal__#', $query_string );
$query_hash = array(
	'mode' => 'base'
);
foreach ( $query_array as $query_line ) {
	if ( preg_match( '#^([^=]+?)=(.+)$#', $query_line, $matches ) ) {
		$query_hash[ $matches[1] ] = urldecode( $matches[2] );
	}
}
$url  = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
$mode = $query_hash['mode'];
$html = '';
$css  = '';

//$start_time = microtime( TRUE );
if ( ! $url ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit;
} else {
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		'X-PST-DISABLE: on'
	) );
	$ret = curl_exec( $ch );
	$info = curl_getinfo( $ch );
	curl_close( $ch );
	if ( '200' != $info['http_code'] || ! preg_match( '#^text/html#', $info['content_type'] ) ) {
		header( 'HTTP/1.0 400 Bad Request' );
		exit;
	}
	$html = $ret;
}
//$get_time = sprintf( "%d", ( microtime( TRUE ) - $start_time ) * 1000 );

if ( preg_match_all( '#<style[^>]*?>([^<]*?)</style>#', $html, $matches, PREG_SET_ORDER ) ) {
	foreach ( $matches as $match ) {
		$css .= $match[1];
	}
}

$args = array(
	'href' => $url,
	'mode' => $mode,
	'css' => $css
);
$json = json_encode( $args );

//$start_time = microtime( TRUE );
$ch = curl_init( 'http://127.0.0.1:3000/split-css-webfont-json' );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 ); 
curl_setopt( $ch, CURLOPT_FAILONERROR, true );
curl_setopt( $ch, CURLINFO_HEADER_OUT, true );
curl_setopt( $ch, CURLOPT_POST, true );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
	'Content-Type: application/json',
	'Content-Length: ' . strlen( $json )
) );

$output_buf = curl_exec( $ch );
$info = curl_getinfo( $ch );
curl_close( $ch );
//$post_time = sprintf( "%d", ( microtime( TRUE ) - $start_time ) * 1000 );

switch ( $info['http_code'] ) {
	case '200' :
		$ret = json_decode( $output_buf );
		header( 'Cache-Control: max-age=7200' );
		header( 'Content-Type: text/css' );
		echo $ret->css;
		//echo "/* GET : $get_time */";
		//echo "/* POST: $post_time */";
		break;
	case '400' :
	case '404' :
		header( 'Cache-Control: max-age=7200' );
		header( 'Content-Type: text/css' );
		echo $css;
		break;
	default :
		header( 'HTTP/1.0 400 Bad Request' );
		break;
}
exit;

