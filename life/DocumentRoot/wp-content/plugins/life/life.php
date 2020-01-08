<?php
include_once "functions.php";
/*-------------------------------------------------------------------------------
                                    Webp
-------------------------------------------------------------------------------*/
if( get_option ( 'life_options_webp' ) ) {  //管理画面コントロール用
                   /*----------Allow upload-----------*/
   function add_webp_upload( $array ) {
       $array[ 'webp' ] = 'image/webp';
       return $array; 
   }
add_filter( 'mime_types', 'add_webp_upload', 10, 1 );
#$mime_types = wp_get_mime_types();   //アップロードできるファイルタイプ一覧用

                 /*-------Show webp in medialist---------*/
   function media_show_webp( $result, $path ) {
       $info = @getimagesize( $path );
	   if( $info['mime'] == 'image/webp' ) {
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
	               $metadata = exif_read_data( $img_path, 0, true );
				   //EXIF情報を取得,加工
				   if ( isset( $metadata['EXIF'] ) ) {
						$exif['Make'] = $metadata['IFD0']['Make'].' '.$metadata['IFD0']['Model'];
						$exif['ExposureTime'] = exif_data( $metadata['EXIF']['ExposureTime'] );
				   		$exif['FNumber'] = gps_data( $metadata['EXIF']['FNumber'] );
				   		$exif['ISOSpeedRatings'] = $metadata['EXIF']['ISOSpeedRatings'];
				   		$exif['DateTimeOriginal'] = $metadata['EXIF']['DateTimeOriginal'];
				   		$exif['ShutterSpeedValue'] = $metadata['EXIF']['ShutterSpeedValue'];
				   		$exif['ApertureValue'] = $metadata['EXIF']['ApertureValue'];
				   		$exif['BrightnessValue'] = $metadata['EXIF']['BrightnessValue'];
				   		$exif['ColorSpace'] = $metadata['EXIF']['ColorSpace'];
				   		$exif['InteroperabilityOffset'] = $metadata['EXIF']['InteroperabilityOffset'];
				   		$exif['WhiteBalance'] = $metadata['EXIF']['WhiteBalance'];
				   		$exif['ExposureMode'] = $metadata['EXIF']['ExposureMode'];
				   		$exif['DigitalZoomRatio'] = $metadata['EXIF']['DigitalZoomRatio'];
				   		$exif['FocalLengthIn35mmFilm'] = $metadata['EXIF']['FocalLengthIn35mmFilm'];
				   add_post_meta( $id, 'exif', $exif, true );
				   }
				   //GPS情報を取得,加工
				   if ( isset( $metadata['GPS'] ) ) {
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
				   		$GPS['longitude'] = $longitudeRef . $longitude;
				   		$GPS['latitude'] = $latitudeRef . $latitude;
				   		$GPS['coordinate'] = $latitudeRef . $latitude . ',' . $longitudeRef . $longitude;
				   		$GPS['altitude'] = $altitudeRef . ( gps_data( $metadata['GPS']['GPSAltitude'] ) );
				   		$GPS['datestamp'] = $metadata['GPS']['GPSDateStamp'];
				   		$GPS['timestamp'] = gps_data( $metadata['GPS']['GPSTimeStamp'] );
				   		add_post_meta( $id, 'gps', $GPS, true );
                   	}
             endwhile;
             update_option( 'exif_loaded_post_id', $id );
      endif;
    }
	add_image_size( 'map-icon', 50, 50, true );
	add_action( 'shutdown', 'exif' );
}

/*-------------------------------------------------------------------------------
                              Post's title must
--------------------------------------------------------------------------------*/
if ( get_option( 'life_options_title' ) ) {
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
<?php 		}
	add_action( 'admin_head-post-new.php', 'required_title' );
	add_action( 'admin_head-post.php', 'required_title' );
}
/*-------------------------------------------------------------------------------
                             Remove_updates
--------------------------------------------------------------------------------*/
if( get_option( 'life_options_updates' ) ) {
#$user = wp_get_current_user();    //更新可能ユーザー追加
#if ( !($user->user_login == 'psuser')){   
     function remove_updates(){
          global $wp_version;
          return ( object ) array(
          'last_checked'    => time(),
          'updates'         => array(),
          'version_checked' => $wp_version
          );  
     }
	add_filter( 'pre_site_transient_update_core', 'remove_updates' );
	add_filter( 'pre_site_transient_update_plugins', 'remove_updates' );
	add_filter( 'automatic_updater_disabled', '__return_true' );
	remove_action( 'load-plugins.php', 'wp_update_plugins' );
	remove_action( 'load-update.php', 'wp_update_plugins' );
	remove_action( 'load-update-core.php', 'wp_update_plugins' );
#}
}
/*------------------------------------------------------------------------------
                       Disable F12, Copy, paste, cut
------------------------------------------------------------------------------*/
if ( get_option( 'life_options_copy' ) ) {
     function disable_f12_copy_paste() {
         if ( !current_user_can( 'manage_options' ) ) { ?>
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
	add_action( 'wp_footer', 'disable_f12_copy_paste' );
}

/*--------------------------------------------------------------------------------
                        Allow original photo upload 
---------------------------------------------------------------------------------*/
if ( get_option('life_options_photo' ) ) {
		add_filter( 'big_image_size_threshold', '__return_false' );
}

/*-------------------------------------------------------------------------------
                         img type to webp
-------------------------------------------------------------------------------*/
if ( get_option( 'life_options_img_type' ) ) {
	if ( !get_option( 'webp_loaded_post_id' ) ) {
		add_option( 'webp_loaded_post_id', '1', '', 'yes' );
		}
	function get_webp_img() {
		$webp_loaded_post_id = get_option( 'webp_loaded_post_id' );
		$args = array(
					'post_type' => 'attachment',
					'post_status' => 'inherit',
					'post_mime_type' => array( 'image/jpeg',
												'image/tiff',
												'image/gif',
												'image/png',
												'image/bmp',
												'image/tif',
												'image/jpg'),
					'post__not_in' => range( 1, (int)$webp_loaded_post_id ),
					'orderby' => 'ID',
					'order' => 'ASC',
					);
		$query = new WP_Query( $args );
		if( $query -> have_posts() ):
			while ( $query -> have_posts() ) : $query -> the_post();
				$id = get_the_id();
				$img_url = get_the_guid();
				$pattern = '/.*wp-content(.*)/';
				preg_match( $pattern, $img_url, $matches_url );
				$path_rear = $matches_url[1];
				$pattern = '/(.*wp-content)/';
				$path_sv = __FILE__;
				preg_match( $pattern, $path_sv, $matches_sv );
				$path_front = $matches_sv[1];
				$img_path = $path_front . $path_rear;
				$metadata = exif_read_data( $img_path, 0, true );
				
			endwhile;
		endif;
	}
}
/*-------------------------------------------------------------------------------
                          Show all image sizes
-------------------------------------------------------------------------------*/
if ( get_option( 'life_options_size' ) ) {
if ( is_admin() ) {
	function get_all_image_sizes() {
		global $_wp_additional_image_sizes;
		$image_size_list = '';
	 	foreach( $_wp_additional_image_sizes as $name => $size ) {
	 		$imgcrop = $size['crop'] ? '>>>>>crop' : '';
	    	$image_size_list .= '<p data-height="'.$size['height'].'" data-width="'.$size['width'].'">';
			$image_size_list .= $name . '>>>>>';
			$image_size_list .= $size['width'] . 'X' . $size['height'];
			$image_size_list .= $imgcrop . '</p>';
	 		}
	 	$default = get_intermediate_image_sizes();
	 	$default_sizes    = array( 'thumbnail', 'medium', 'medium_large', 'large' );
	 	$strc = '_crop';
	 	$strw = '_size_w';
	 	$strh = '_size_h';
	 	foreach ( $default_sizes as $name ) {
			$optionc = $name.$strc;
			$optionw = $name.$strw;
			$optionh = $name.$strh;
			$gcrop = get_option( $optionc );
			$width = get_option( $optionw );
			$height = get_option( $optionh );
			$imgcrop = $gcrop ? '>>>>>crop' : '';
	 		$image_size_list .= '<p data-height="'.$height.'" data-width="'.$width.'">';
			$image_size_list .= $name . '>>>>>';
			$image_size_list .= $width . 'X' . $height;
			$image_size_list .= $imgcrop . '</p>';
	 		}
	 	global $wp_admin_bar;
	 	$wp_admin_bar->add_node( array(
			'id' => 'show_all_image_size',
			'title' => 'Show all image sizes',
			) );
	 	$wp_admin_bar->add_menu( array (
	 		'parent' => 'show_all_image_size',
			'id' => 'image_size',
			'title' => '<div id="image_sizes_list">' . $image_size_list . '</div>', 
			) );
		/*$wp_admin_bar->add_menu( array (
			'parent' => 'image_size',
			'id' => 'image_size_div',
			'title' => '<div id="image_size_div"></div>',
			) );*/
	 	}
	add_action( 'admin_bar_menu', 'get_all_image_sizes', 999 );
	}
}

/*-------------------------------------------------------------------------------
                          Remove admin bar
-------------------------------------------------------------------------------*/
if ( get_option( 'life_options_adminbar' ) ) {
	#$user = wp_get_current_user();
	#if ( !($user->user_login == 'haokexin')){ 
		add_filter( 'show_admin_bar', '__return_false', 1000 );
		#add_action('after_setup_theme','remove_admin_bar_space');
		#function remove_admin_bar_space(){
		#add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );
		#}
	#	}
}

/*-------------------------------------------------------------------------------
							js追加
-------------------------------------------------------------------------------*/
#if( is_front_page() ){
#	$Key = 'AIzaSyB3HNBEo_ND6z7s3ethaRA0lPxikOUqjwU';
#	wp_enqueue_script('map',"https://maps.googleapis.com/maps/api/js?key='.$Key.'&callback=initMap",array(),'1.0.0', true);
#}

/*-------------------------------------------------------------------------------
							投稿分肢
-------------------------------------------------------------------------------*/
if ( get_option( 'life_options_post_branch' ) ) {
	function wpbs_post_submitbox_start() {
		global $post;
		if ( in_array( $post->post_status, array('publish', 'future', 'private') ) && 0 != $post->ID ) {
			echo '<div id="branch-action" style="margin-bottom:5px;">';
			echo '<input type="submit" class="button-primary" name="life_post" value="副本を作る" />';
			echo '</div>';
			}
		}
	add_action( 'post_submitbox_start', 'wpbs_post_submitbox_start' );
	
	function wpbs_pre_post_update( $id ) {
		if ( isset( $_POST['life_post'] ) ) {
			$pub = get_post( $id, ARRAY_A );
			unset( $pub['ID'] );
			$user = wp_get_current_user();
			$pub['post_status'] = 'draft';
			$pub['post_name']   = $pub['post_name'] . '-fukuhon';
			$pub = apply_filters( 'wpbs_pre_publish_to_draft_post', $pub );
			$draft_id = wp_insert_post( $pub );
			$keys = get_post_custom_keys( $id );
			$custom_field = array();
			foreach ( (array) $keys as $key ) {
				if ( preg_match( '/^_feedback_/', $key ) )
					continue;
				if ( preg_match( '/_wp_old_slug/', $key ) )
					continue;
				$values = get_post_custom_values($key, $id );
				foreach ( $values as $value ) {
					add_post_meta( $draft_id, $key, $value );
				}
			}
			$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $id );
			$attachments = get_posts( $args );
			if ($attachments) {
				foreach ( $attachments as $attachment ) {
					$new = array(
						'post_author' => $attachment->post_author,
						'post_date' => $attachment->post_date,
						'post_date_gmt' => $attachment->post_date_gmt,
						'post_content' => $attachment->post_content,
						'post_title' => $attachment->post_title,
						'post_excerpt' => $attachment->post_excerpt,
						'post_status' => $attachment->post_status,
						'comment_status' => $attachment->comment_status,
						'ping_status' => $attachment->ping_status,
						'post_password' => $attachment->post_password,
						'post_name' => $attachment->post_name,
						'to_ping' => $attachment->to_ping,
						'pinged' => $attachment->pinged,
						'post_modified' => $attachment->post_modified,
						'post_modified_gmt' => $attachment->post_modified_gmt,
						'post_content_filtered' => $attachment->post_content_filtered,
						'post_parent' => $draft_id,
						'guid' => $attachment->guid,
						'menu_order' => $attachment->menu_order,
						'post_type' => $attachment->post_type,
						'post_mime_type' => $attachment->post_mime_type,
						'comment_count' => $attachment->comment_count
					);
					$attachment_newid = wp_insert_post( $new );
					$keys = get_post_custom_keys( $attachment->ID );
					$custom_field = array();
					foreach ( (array) $keys as $key ) {
						$value = get_post_meta( $attachment->ID, $key, true );
						add_post_meta( $attachment_newid, $key, $value );
						}
					}
				}
				$taxonomies = get_object_taxonomies( $pub['post_type'] );
				foreach ($taxonomies as $taxonomy) {
					$post_terms = wp_get_object_terms($id, $taxonomy, array( 'orderby' => 'term_order' ));
					$terms = array();
					for ($i=0; $i<count($post_terms); $i++) {
						$terms[] = $post_terms[$i]->slug;
					}
					wp_set_object_terms($draft_id, $terms, $taxonomy);
				}
				add_post_meta($draft_id, '_wpbs_pre_post_id', $id);
				if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
					wp_safe_redirect( admin_url( 'post.php?post=' . $draft_id . '&action=edit' ) );
					exit;
				}
			}
	}
	add_filter( 'pre_post_update', 'wpbs_pre_post_update' );
	
	function wpbs_admin_notice() {
		if ( isset($_REQUEST['post']) ) {
			$id = $_REQUEST['post'];
			if ( $old_id = get_post_meta( $id, '_wpbs_pre_post_id', true ) ) {
				echo '<div id="wpbs_notice" class="updated fade"><p>' . sprintf( "This post is a copy of the post id <a href='%s' target='__blank' >%s</a> Overwrite the original post by pressing the publish button.",  get_permalink($old_id), $old_id ) . '</p></div>';
			}
		}
	}
	add_action( 'admin_notices', 'wpbs_admin_notice' );

	function add_wpbs_save_post_hooks() {
		$additional_post_types = get_post_types( array( '_builtin' => false, 'show_ui' => true ) );
		foreach ( $additional_post_types as $post_type ) {
			add_action( 'publish_' . $post_type, 'wpbs_save_post', 9999, 2 );
		}
	}
	add_action( 'init', 'add_wpbs_save_post_hooks', 9999 );

	function wpbs_save_post( $id, $post ) {
		if ( $org_id = get_post_meta( $id, '_wpbs_pre_post_id', true ) ) {
			$new = array(
            	'ID' => $org_id,
           		'post_author' => $post->post_author,
            	'post_date' => $post->post_date,
            	'post_date_gmt' => $post->post_date_gmt, 
            	'post_content' => $post->post_content,
            	'post_title' => $post->post_title,
            	'post_excerpt' => $post->post_excerpt,
            	'post_status' => 'publish',
            	'comment_status' => $post->comment_status,
            	'ping_status' => $post->ping_status,
            	'post_password' => $post->post_password,
            	'to_ping' => $post->to_ping,
            	'pinged' => $post->pinged,
            	'post_modified' => $post->post_modified,
            	'post_modified_gmt' => $post->post_modified_gmt,
            	'post_content_filtered' => $post->post_content_filtered, 
            	'post_parent' => $post->post_parent,
            	'guid' => $post->guid,
            	'menu_order' => $post->menu_order,
            	'post_type' => $post->post_type,
            	'post_mime_type' => $post->post_mime_type
        	);
			wp_update_post( $new );
			$keys = get_post_custom_keys( $id );
			$custom_field = array();
			foreach ( (array) $keys as $key ) {
				if ( preg_match( '/^_feedback_/', $key ) )
					continue;
				if ( preg_match( '/_wpbs_pre_post_id/', $key ) )
					continue;
				if ( preg_match( '/_wp_old_slug/', $key ) )
					continue;
			delete_post_meta( $org_id, $key );
			$values = get_post_custom_values($key, $id );
			foreach ( $values as $value ) {
				add_post_meta( $org_id, $key, $value );
			}
		}
		$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $id );
        	$attachments = get_posts( $args );
        	if ($attachments) {
            	foreach ( $attachments as $attachment ) {
                	$new = array(
                    	'post_author' => $attachment->post_author,
                    	'post_date' => $attachment->post_date,
                   		'post_date_gmt' => $attachment->post_date_gmt,
                    	'post_content' => $attachment->post_content,
                    	'post_title' => $attachment->post_title,
                    	'post_excerpt' => $attachment->post_excerpt,
                    	'post_status' => $attachment->post_status,
                    	'comment_status' => $attachment->comment_status,
                    	'ping_status' => $attachment->ping_status,
                    	'post_password' => $attachment->post_password,
                    	'post_name' => $attachment->post_name,
                    	'to_ping' => $attachment->to_ping,
                    	'pinged' => $attachment->pinged,
                    	'post_modified' => $attachment->post_modified,
                    	'post_modified_gmt' => $attachment->post_modified_gmt,
                    	'post_content_filtered' => $attachment->post_content_filtered,
                    	'post_parent' => $draft_id,
                    	'guid' => $attachment->guid,
                    	'menu_order' => $attachment->menu_order,
                    	'post_type' => $attachment->post_type,
                    	'post_mime_type' => $attachment->post_mime_type,
                    	'comment_count' => $attachment->comment_count
                	);
                	$attachment_newid = wp_insert_post( $new );
                	$keys = get_post_custom_keys( $attachment->ID );
					$custom_field = array();
                	foreach ( (array) $keys as $key ) {
                    	$value = get_post_meta( $attachment->ID, $key, true );
                    	delete_post_meta( $org_id, $key );
                    	add_post_meta( $org_id, $key, $value );
                	}
            	}        
			}
			$taxonomies = get_object_taxonomies( $post->post_type );
			foreach ($taxonomies as $taxonomy) {
				$post_terms = wp_get_object_terms($id, $taxonomy, array( 'orderby' => 'term_order' ));
				$terms = array();
				for ($i=0; $i<count($post_terms); $i++) {
					$terms[] = $post_terms[$i]->slug;
				}
				wp_set_object_terms($org_id, $terms, $taxonomy);
			}
			wp_delete_post( $id );
			wp_safe_redirect( admin_url( '/post.php?post=' . $org_id . '&action=edit&message=1' ) );
			exit;
 		}
}	
add_action( 'publish_page', 'wpbs_save_post', 9999, 2 );
add_action( 'publish_post', 'wpbs_save_post', 9999, 2 );

function wpbs_admin_notice_saved_init() {
    if ( isset($_REQUEST['message']) && $_REQUEST['message'] == 'wpbs_msg' )
        add_action( 'admin_notices', 'wpbs_admin_notice_saved' );
}

function wpbs_admin_notice_saved() {
    echo '<div id="wpbs_notice" class="updated fade"><p></p></div>';

}

function wpbs_display_branch_stat( $stat ) {
    global $post;
    if ( $org_id = get_post_meta( $post->ID, '_wpbs_pre_post_id', true ) ) {
		$user = wp_get_current_user();
        $stat[] = sprintf( '%dの副本 作成者: %s', $org_id , $user->user_login );
    }
    return $stat;
}
add_filter( 'display_post_states', 'wpbs_display_branch_stat' );
}
