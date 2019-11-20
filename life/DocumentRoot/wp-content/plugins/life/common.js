$(document).ready(function() {
	$("input[id*='onoffswitch']").on('click', function(){
		clickSwitch()
	});
 
	var clickSwitch = function() {
		if ($("input[id*='onoffswitch']").is(':checked')) {
		   $("input[id*='onoffswitch']").attr("value","1");
		} else {
		   $("input[id*='onoffswitch']").attr("value","0");
		}
	};
});
