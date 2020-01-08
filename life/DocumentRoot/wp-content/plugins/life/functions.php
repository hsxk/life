<?php
/*-------------------------------------------------------------------------------
									管理画面
-------------------------------------------------------------------------------*/
      /*--------------管理画面checkboxbutton----------------*/
  function control_switch_block( $option_name, $option_value, $title ) {
	if ( isset( $option_name ) && isset( $option_value ) && isset( $title ) ) {
		if ( !$option_value ) {
			$checkbox = '<input class="switch-checkbox" 
							name="' . $option_name . '"
							id="onoffswitch' . $option_name . '"
							type="checkbox" value="0">';
							}
		else {
	    	$checkbox = '<input class="switch-checkbox" 
							name="' . $option_name . '" 
							id="onoffswitch' . $option_name . '" 
							checked="checked"  
							type="checkbox" 
							value="1">';
		  				}
	 	$switch = '<div class="switch">'
	           . $checkbox .
			   '<label class="switch-label" for="onoffswitch' . $option_name . '">
			    <span class="switch-inner" data-on="ON" data-off="OFF"></span>
			    <span class="switch-switch"></span>
			    </label>
			    </div>';
	 	$block = '<div class="control_switch_block"><p>' . $title . '</p>' . $switch . '</div>';
	 	return $block;
	}
	else {
		return false;
	}
  }

/*-------------------------------------------------------------------------------
								写真データ処理
-------------------------------------------------------------------------------*/
     /*---------------分数化簡-------------*/
  function exif_data( $str ) {
  	if ( isset ( $str ) ) {
		$pattern = '/(.*)?\/(.*)?/';
		preg_match( $pattern, $str, $number );
		$a = $number[1];
		$b = $number[2];
		while( $b != 0 ) {   //最大公約数
		   $gcd = $a % $b;
		   $a = $b;
		   $b = $gcd;
		}
		$str = ( $number[1] / $a ) . '/' . ( $number[2] / $a );
		return $str;
	}
	else {
		return false;
	}
  }
      /*-------------計算--------------*/
  function gps_data( $str ) {
  	if( isset ( $str ) ) {
  		if ( is_array( $str ) ) {
			$size = sizeof( $str );
			$i = 0;
			for($i ; $i < $size ; $i++){
				$pattern = '/(.*)?\/(.*)?/';
				preg_match( $pattern, $str[$i], $number );
				if ( $number[2] != 0 ) {
					$string = (double)$number[1]/(double)$number[2];
				} else { 
					return false;
				}
			$str[$i]=$string;
			}
		$str = implode( ':', $str );
		return $str;
		}
		else {
			$pattern = '/(.*)?\/(.*)?/';
			preg_match( $pattern, $str, $number );
			if ( $number[2] != 0 ) {
				$str = (double)$number[1]/(double)$number[2];
				} 
			else {
				return false;
				}
			return $str;
			}
		} 
		else {
			return false;
	}
  }

/*-------------------------------------------------------------------------------
									Google maps
-------------------------------------------------------------------------------*/
  	/*-----------google maps content-----------*/
  function google_maps_content( $id ) {
  	$content = '';
	if ( isset( $id ) && $id != null ) {
		$metadata = get_post_meta( $id, 'exif' );
		if ( $metadata ) {
			$thumbnail = wp_get_attachment_image_src( $id, 'medium', true );
			$content .= 'var info'.$id.' = \'<div>';
			$content .= '<img src="'.$thumbnail[0].'">';
			$content .= '<p>maker: '.$metadata[0]['Make'].'</p>';
			$content .= '<p>ExposureTime: '.$metadata[0]['ExposureTime'].'</p>';
			$content .= '</div>\';'.PHP_EOL;
			$info = 'var infowindow'.$id.' = new google.maps.InfoWindow({ content: ';
			$info .= 'info'.$id.'});'.PHP_EOL;
			return $content.$info;
			} 
		else {
			return "<div>No exifinfo for this photo</div>";
			}
		} 
	else {
		$info = 'var infowindow= new google.maps.InfoWindow({ content: info});'.PHP_EOL;
		$content = 'var info = \'<div>Home</div>\';'.PHP_EOL;
		return $content.$info;
		}
  }

  /*-----------google maps markers------------*/
  function google_map_marker( $id ) {
  	if ( isset( $id ) && $id != null ) {
		$metadata = get_post_meta( $id, 'gps' );
		if ( $metadata ) {
			$locations = '{lat:' . $metadata[0]['latitude'] . ',lng:' . $metadata[0]['longitude'] . '}';
			$thumbnail = wp_get_attachment_image_src( $id, 'map-icon', true );
			$markers = 'var marker'.$id;
			$markers .= ' = new google.maps.Marker({position:' . $locations;
			$markers .= ',icon:\'' . $thumbnail[0] . '\', map:map});'.PHP_EOL;
			if ( get_post_meta( $id, 'exif' ) ) {
			$info_cilck = '';
			$info_cilck .= 'marker'.$id.'.addListener(\'click\', function() { ';
			$info_cilck .= 'infowindow'.$id.'.open(map, marker'.$id.');});'.PHP_EOL;
			}
			return $markers.$info_cilck;
			} 
		else {
			return "No gpsinfo for this photo";
			}
		} 
	else {
		$markers = 'var marker = new google.maps.Marker({position:{lat:42.4434914,lng:123.5403288},map: map});'.PHP_EOL;
		$info_cilck = 'marker.addListener(\'click\', function() { infowindow.open(map, marker);});'.PHP_EOL;
		return $markers.$info_cilck;
		}
  }

  /*---------google maps center-------------*/
  function google_map_center( $ids ) {
  	if ( isset( $ids ) && $ids != null ) {
		if ( is_array( $ids ) ){
		again:
			$id = array_rand( $ids, 1 );
			$center_info = get_post_meta( $ids[ $id ], 'gps' );
			if ( $center_info ) {
				$center = '{lat:'.$center_info[ 0 ][ 'latitude' ].',lng:'.$center_info[ 0 ][ 'longitude' ].'}';
				return $center;
				} 
			else {
				goto again;
				}
			} 
		else {
			return false;
			}
		} 
	else {
		$center = '{lat:42.4434914,lng:123.5403288}';
		return $center;
		}
  }

?>
