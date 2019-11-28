<?php
            /*--------------管理画面checkboxbutton----------------*/
  function control_switch_block( $option_name, $option_value, $title ) {
   if ( isset( $option_name ) && isset( $option_value ) && isset( $title ) ) {
	 if ( !$option_value ) {
	    $checkbox = '<input class="testswitch-checkbox" 
							name="'.$option_name.'"
							id="onoffswitch'.$option_name.'"
							type="checkbox" value="0">';
							}
	 else {
	    $checkbox = '<input class="testswitch-checkbox" 
							name="'.$option_name.'" 
							id="onoffswitch'.$option_name.'" 
							checked="checked"  
							type="checkbox" 
							value="1">';
		  }
	 $switch = '<div class="testswitch">'
	           .$checkbox.
			   '<label class="testswitch-label" for="onoffswitch'.$option_name.'">
			    <span class="testswitch-inner" data-on="ON" data-off="OFF"></span>
			    <span class="testswitch-switch"></span>
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
	}
	else{
		return false;
	}
  }
      /*-------------計算--------------*/
  function gps_data($str){
  	if( isset ( $str ) ) {
		$pattern = '/(.*)?\/(.*)?/';
		preg_match( $pattern, $str, $number );
		$str = (double)$number[1]/(double)$number[2];
		return $str;
	}
	else{
		return false;
	}
  }

?>
