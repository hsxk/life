<?php 
/*
Plugin Name: life
Author: haokexin
Author URI: https://hkx.monster
*/

/*
     upload webp   and  show webp in media
*/
function add_webp_upload( $array ) {
	$array['webp'] = 'image/webp';
		return $array; 
}
add_filter( 'mime_types', 'add_webp_upload', 10, 1 );

function media_show_webp($result, $path) {
	$info = @getimagesize( $path );
		if($info['mime'] == 'image/webp') {
				$result = true;
		}
	return $result;
}
add_filter( 'file_is_displayable_image', 'media_show_webp', 10, 2 );

/*
     get exit
*/
function get_exif(){

}


?>
