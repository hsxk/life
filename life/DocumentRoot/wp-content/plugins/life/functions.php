<?php
            /*--------------管理画面checkboxbutton----------------*/
  function control_switch_block( $option_name, $option_value, $title ) {
   if ( isset( $option_name ) && isset( $option_value ) && isset( $title ) ) {
	 if ( !$option_value ) {
	    $checkbox = '<input class="switch-checkbox" 
							name="'.$option_name.'"
							id="onoffswitch'.$option_name.'"
							type="checkbox" value="0">';
							}
	 else {
	    $checkbox = '<input class="switch-checkbox" 
							name="'.$option_name.'" 
							id="onoffswitch'.$option_name.'" 
							checked="checked"  
							type="checkbox" 
							value="1">';
		  }
	 $switch = '<div class="switch">'
	           .$checkbox.
			   '<label class="switch-label" for="onoffswitch'.$option_name.'">
			    <span class="switch-inner" data-on="ON" data-off="OFF"></span>
			    <span class="switch-switch"></span>
			    </label>
			    </div>';
	 $block = '<div class="control_switch_block"><p>'.$title.'</p>'.$switch.'</div>';
	 return $block;
	}
	else{
 	 return false;
	}
  }
     /*---------------分数化簡-------------*/
  function exif_data($str){
  	if( isset ( $str ) ) {
		$pattern = '/(.*)?\/(.*)?/';
		preg_match( $pattern, $str, $number );
		$a = $number[1];
		$b = $number[2];
		while( $b != 0 ) {   //最大公約数
		   $gcd = $a % $b;
		   $a = $b;
		   $b = $gcd;
		}
		$str = ( $number[1] / $a ).'/'.( $number[2] / $a );
		return $str;
	}else{
		return false;
	}
  }
      /*-------------計算--------------*/
  function gps_data($str){
  	if( isset ( $str ) ) {
  		if(is_array($str)){
			$size = sizeof($str);
			$i = 0;
			for($i ; $i < $size ; $i++){
				$pattern = '/(.*)?\/(.*)?/';
				preg_match( $pattern, $str[$i], $number );
				if($number[2] != 0){
					$string = (double)$number[1]/(double)$number[2];
				}else{ 
					return false;
				}
			$str[$i]=$string;
			}
		$str=implode(':',$str);
		return $str;
		}
		else{
			$pattern = '/(.*)?\/(.*)?/';
			preg_match( $pattern, $str, $number );
			if($number[2] != 0){
				$str = (double)$number[1]/(double)$number[2];
			}else{
				return false;
			}
			return $str;
			}
		}else{
			return false;
	}
  }

?>
