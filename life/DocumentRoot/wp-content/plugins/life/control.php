<?php 
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
	return false;
  }
?>
