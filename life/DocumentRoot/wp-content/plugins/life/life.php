<?php 
/*
Plugin Name: life
Version: 0.1
Author: haokexin
Author URI: https://hkx.monster
*/

/*-------------------------------------------------------------------------------
                  Upload webp and Show webp in medialist
-------------------------------------------------------------------------------*/

/*---------------------------Allow upload--------------------------------------*/
function add_webp_upload( $array ) {
	$array['webp'] = 'image/webp';
		return $array; 
}
add_filter( 'mime_types', 'add_webp_upload', 10, 1 );
/*--------------------------Show webp in medialist-----------------------------*/
function media_show_webp($result, $path) {
	$info = @getimagesize( $path );
		if($info['mime'] == 'image/webp') {
				$result = true;
		}
	return $result;
}
add_filter( 'file_is_displayable_image', 'media_show_webp', 10, 2 );

/*-------------------------------------------------------------------------------
                               Get exif
-------------------------------------------------------------------------------*/

/*-----------------------------Get exif----------------------------------------*/
function get_exif(){
    global $wpdb;
	$table_name = $wpdb -> prefix . "exif";
}


?>
