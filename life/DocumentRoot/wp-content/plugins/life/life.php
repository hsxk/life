<?php
/*-AIzaSyB3HNBEo_ND6z7s3ethaRA0lPxikOUqjwU-*/
include_once "functions.php";
/*-------------------------------------------------------------------------------
                                    Webp
-------------------------------------------------------------------------------*/
if(get_option('life_options_webp')){  //管理画面コントロール用
                   /*----------Allow upload-----------*/
   function add_webp_upload( $array ) {
       $array['webp'] = 'image/webp';
       return $array; 
   }
add_filter( 'mime_types', 'add_webp_upload', 10, 1 );
#$mime_types = wp_get_mime_types();   //アップロードできるファイルタイプ一覧用

                 /*-------Show webp in medialist---------*/
   function media_show_webp($result, $path) {
       $info = @getimagesize( $path );
	   if($info['mime'] == 'image/webp') {
		   $result = true;
	   }
   return $result;
   }
add_filter( 'file_is_displayable_image', 'media_show_webp', 10, 2 );
}


/*-------------------------------------------------------------------------------
                                  Exif
-------------------------------------------------------------------------------*/
if( get_option( 'life_options_exif' ) ) {  //管理画面コントロール用
     if ( !get_option( 'exif_loaded_post_id' ) ) {
         add_option( 'exif_loaded_post_id', '1', '', 'yes' );
     }
                        /*------Get exif-------*/
   function exif(){
       $exif_loaded_post_id = get_option( 'exif_loaded_post_id' );
       $args = array(
                  'post_type' => 'attachment',
				  'post_status'	=> 'inherit',
				  'post_mime_type' => array( 'image/jpeg',
				                             'image/tiff' ),
				  'post__not_in' => range( 1, (int)$exif_loaded_post_id ),
				  'orderby' => 'ID',
				  'order' => 'ASC',
       );
       $query = new WP_Query( $args );
       if( $query -> have_posts() ):
             while ( $query -> have_posts() ) : $query -> the_post();
                   $id = get_the_id();
				   //Jpeg/Tiff画像URLを取得
  	               $img_url = get_the_guid();
				   //URLから wp-content より後の部分を取得
	               $pattern = '/.*wp-content(.*)/';
	               preg_match( $pattern, $img_url, $matches_url );
	               $path_rear = $matches_url[1];
				   //サーバーパスを取得して wp-content 以前の部分を取得
	               $pattern = '/(.*wp-content)/';
	               $path_sv = __FILE__;
	               preg_match( $pattern, $path_sv, $matches_sv );
	               $path_front = $matches_sv[1];
				   //画像のサーバーパスを生成
	               $img_path = $path_front . $path_rear;
				   //Metaデータを取得
	               $metadata = exif_read_data($img_path,0,true);
				   //EXIF情報を取得,加工
				   if ( $metadata['EXIF'] ) {
				   $exif[ 'Make' ] = $metadata['IFD0']['Make'].' '.$metadata['IFD0']['Model'];
				   $exif[ 'ExposureTime' ] = exif_data( $metadata['EXIF']['ExposureTime'] );
				   $exif[ 'FNumber' ] = gps_data( $metadata['EXIF']['FNumber'] );
				   $exif[ 'ISOSpeedRatings' ] = $metadata['EXIF']['ISOSpeedRatings'];
				   $exif[ 'DateTimeOriginal' ] = $metadata['EXIF']['DateTimeOriginal'];
				   $exif[ 'ShutterSpeedValue' ] = $metadata['EXIF']['ShutterSpeedValue'];
				   $exif[ 'ApertureValue' ] = $metadata['EXIF']['ApertureValue'];
				   $exif[ 'BrightnessValue' ] = $metadata['EXIF']['BrightnessValue'];
				   $exif[ 'ColorSpace' ] = $metadata['EXIF']['ColorSpace'];
				   $exif[ 'InteroperabilityOffset' ] = $metadata['EXIF']['InteroperabilityOffset'];
				   $exif[ 'WhiteBalance' ] = $metadata['EXIF']['WhiteBalance'];
				   $exif[ 'ExposureMode' ] = $metadata['EXIF']['ExposureMode'];
				   $exif[ 'DigitalZoomRatio' ] = $metadata['EXIF']['DigitalZoomRatio'];
				   $exif[ 'FocalLengthIn35mmFilm' ] = $metadata['EXIF']['FocalLengthIn35mmFilm'];
				   add_post_meta( $id, 'exif', $exif, true );
				   }
				   //GPS情報を取得,加工
				   if ( $metadata['GPS'] ){
				   if ( $metadata['GPS']['GPSLatitudeRef'] == "S" )//南緯Sはマイナス
				   $latitudeRef = '-';
				   else
				   $latitudeRef = '';
				   if ( $metadata['GPS']['GPSLongitudeRef'] == "W" )//西経Wはマイナス
				   $longitudeRef = '-';
				   else 
				   $longitudeRef = '';
				   if( $metadata['GPS']['GPSAltitudeRef'] == "1" )//1は海拔以下
				   $altitudeRef = '-';
				   else 
				   $altitudeRef = '';
				   $longitude = gps_data( $metadata['GPS']['GPSLongitude'][0] ) + gps_data( $metadata['GPS']['GPSLongitude'][1] ) / 60 + gps_data( $metadata['GPS']['GPSLongitude'][2] ) / 60 / 60;
				   $latitude = gps_data( $metadata['GPS']['GPSLatitude'][0] ) + gps_data( $metadata['GPS']['GPSLatitude'][1] ) / 60 + gps_data( $metadata['GPS']['GPSLatitude'][2] ) / 60 / 60;
				   $GPS['longitude'] = $longitudeRef.$longitude;
				   $GPS['latitude'] = $latitudeRef.$latitude;
				   $GPS['coordinate'] = $latitudeRef.$latitude.','.$longitudeRef.$longitude;
				   $GPS['altitude'] = $altitudeRef.$metadata['GPS']['GPSAltitude']/100;
				   add_post_meta( $id, 'gps', $GPS, true );
                   }
             endwhile;
             update_option( 'exif_loaded_post_id', $id );
      endif;
    }
	add_image_size('map-icon',50,50,true);
add_action( 'shutdown' , 'exif' );
}

/*-------------------------------------------------------------------------------
                              Post's title must
--------------------------------------------------------------------------------*/
if(get_option('life_options_title')){
     function required_title() { ?>
        <script type="text/javascript">
             jQuery(document).ready(function($){
                 if('post' == $('#post_type').val()){
                       $("#post").submit(function(e){
                            if('' == $('#title').val()) {
                                 alert('Title must be entered');
                                 $('#ajax-loading').css('visibility', 'hidden');
                                 $('#publish').removeClass('button-primary-disabled');
                                 $('#title').focus();
                                 return false;
                            }
                       });
	              }  
             });
        </script>
<?php }
add_action('admin_head-post-new.php', 'required_title');
add_action('admin_head-post.php', 'required_title');
}
/*-------------------------------------------------------------------------------
                             Remove_updates
--------------------------------------------------------------------------------*/
if(get_option('life_options_updates')){
#$user = wp_get_current_user();    //更新可能ユーザー追加
#if ( !($user->user_login == 'psuser')){   
     function remove_updates(){
          global $wp_version;
          return (object) array(
          'last_checked'    => time(),
          'updates'         => array(),
          'version_checked' => $wp_version
          );  
     }
add_filter('pre_site_transient_update_core', 'remove_updates');
add_filter('pre_site_transient_update_plugins', 'remove_updates');
add_filter('automatic_updater_disabled', '__return_true');
remove_action('load-plugins.php', 'wp_update_plugins');
remove_action('load-update.php', 'wp_update_plugins');
remove_action('load-update-core.php', 'wp_update_plugins');
#}
}
/*------------------------------------------------------------------------------
                       Disable F12, Copy, paste, cut
------------------------------------------------------------------------------*/
if(get_option('life_options_copy')){
     function disable_f12_copy_paste(){
         if(!current_user_can('manage_options')){ ?>
             <script type="text/javascript">
                  //Disable right click menu
                  document.oncontextmenu = new Function("return false;");
                  //Disable F12
                  document.onkeydown = document.onkeyup = document.onkeypress = function(event) {
                         var e = event || window.event || arguments.callee.caller.arguments[0];
	                     if (e && e.keyCode == 123) {
	                            e.returnValue = false;
			                    return (false);
			             }
                  };
                  //Disable text selection
                  document.onselectstart = function(){ return false; };
                  //Disable copy
                  document.oncopy = function(){ return false; };
                  //Disable cut
                  document.oncut = function(){ return false; };
                  //Disable paste
                  document.onpaste = function(){ return false; };
             </script>
         <?php }
      }
add_action('wp_footer', 'disable_f12_copy_paste');
}

/*--------------------------------------------------------------------------------
                        Allow original photo upload 
---------------------------------------------------------------------------------*/
if(get_option('life_options_photo')){
		add_filter( 'big_image_size_threshold', '__return_false' );
}

/*-------------------------------------------------------------------------------
                         img type to webp
-------------------------------------------------------------------------------*/
if(get_option('life_options_img_type')){
	function get_webp_img(){
		
	}
}
/*-------------------------------------------------------------------------------
                          Show all image sizes
-------------------------------------------------------------------------------*/
if(get_option('life_options_size')){
function get_all_image_sizes(){
	global $_wp_additional_image_sizes;
	$image_size_list = '';
	 foreach($_wp_additional_image_sizes as $name => $size){
	 	$imgcrop = $size['crop']?'crop':'';
	    $image_size_list .= '<li style="float:left;line-height: 1.5">'.$name.'>>>>>'.$size['width'].'X'.$size['height'].'&nbsp;&nbsp;&nbsp;'.$imgcrop.'</li>';
	 }
	 $default = get_intermediate_image_sizes();
	 $default_sizes    = array( 'thumbnail', 'medium', 'medium_large', 'large' );
	 $strc = '_crop';
	 $strw = '_size_w';
	 $strh = '_size_h';
	 foreach($default_sizes as $name){
		$optionc = $name.$strc;
		$optionw = $name.$strw;
		$optionh = $name.$strh;
		$gcrop = get_option($optionc);
		$width = get_option($optionw);
		$height = get_option($optionh);
		$imgcrop = $gcrop?'crop':'';
	 	$image_size_list .= '<li style="float:left;line-height: 1.5">'.$name.'>>>>>'.$width.'X'.$height.'&nbsp;&nbsp;&nbsp;'.$imgcrop.'</li>';
	 }
	 global $wp_admin_bar;
	 $args = array(
	 	'id'     => 'show_all_image_size',
		'title' => 'Show all image sizes',
	 );
	 $wp_admin_bar->add_node( $args );
	 $wp_admin_bar->add_menu( array(
	 	'parent' => 'show_all_image_size',
		'id' => 'image_size',
		'title' => '<ul id="included-files-list">'.$image_size_list.'</ul>',));
	 }
add_action('admin_bar_menu','get_all_image_sizes',999);
}

/*-------------------------------------------------------------------------------
                          Remove admin bar
-------------------------------------------------------------------------------*/
if(get_option('life_options_adminbar')){
	add_filter('show_admin_bar','__return_false',1000);
	#add_action('after_setup_theme','remove_admin_bar_space');
	#function remove_admin_bar_space(){
	#add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );
	#}
}
?>
