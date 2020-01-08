/*----------管理画面checkboxbutton値変更用------------*/
jQuery(document).ready(function($) {
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

/*------------Image sizes list------------------------*/
/*
jQuery(document).ready(function($) {
	$("#image_sizes_list").children("p").hover( function(){
		console.log($(this).attr('data-height'));
		$("#image_size_div").height($(this).attr('data-height'));
		$("#image_size_div").width($(this).attr('data-width'));
	},
	function(){
		$("#image_sizes_list p").hide();
	});
});
*/
