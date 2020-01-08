<?php
$query_string = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
$query_array = preg_split( '#__wexal__#', $query_string );
$query_hash = array(
	'mode' => 'base',
	'url'  => ''
);
foreach ( $query_array as $query_line ) {
	if ( preg_match( '#^([^=]+?)=(.+)$#', $query_line, $matches ) ) {
		$query_hash[ $matches[1] ] = urldecode( $matches[2] );
	}
}
$url  = $query_hash['url'];
$mode = $query_hash['mode'];
$css  = '';

//$start_time = microtime( TRUE );
if ( ! $url ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit;
} else {
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	$ret = curl_exec( $ch );
	$info = curl_getinfo( $ch );
	curl_close( $ch );
	if ( '200' != $info['http_code'] || ! preg_match( '#^text/css#', $info['content_type'] ) ) {
		header( "Location: $url" );
		exit;
	}
	$css = $ret;
}
//$get_time = sprintf( "%d", ( microtime( TRUE ) - $start_time ) * 1000 );

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
		header( "Location: $url" );
		break;
	default :
		header( 'HTTP/1.0 400 Bad Request' );
		break;
}
exit;

