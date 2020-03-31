jQuery(function($) {
	
	var sliderTimer;
	var sliderTop = $("#sliderTop");
	var slide_contents = sliderTop.find(".sliderTop-articles");
	var slide_content = slide_contents.children(".article");
	var slide_button = sliderTop.children(".sliderTop-button").children("li");
	
	// 子要素の数
	var slide_content_length = slide_content.length;

	// スライダー幅をウィンドウ幅に設定
	var windowWidth = $(window).width() * 0.7;
	var imageHeight = windowWidth * (9 / 16) + "px";
	var marginleft = windowWidth - ($(window).width() * 0.3 / 2);
	sliderTop.width($(window).width());
	sliderTop.find("img").height(imageHeight).css({ 'width' : 'auto' });
	slide_content.width(windowWidth);
	$("#sliderTop").css("opacity", "1");
	
	// スライダーの大枠に横幅を指定
	slide_contents.width( windowWidth * slide_content_length );

	function setWidth() {
		windowWidth = $(window).width() * 0.7;
		imageHeight = windowWidth * (9 / 16) + "px";
		marginleft = windowWidth - ($(window).width() * 0.3 / 2);
		sliderTop.width($(window).width());
		sliderTop.find("img").height(imageHeight);
		slide_content.width(windowWidth);  
		slide_contents.width( windowWidth * slide_content_length );
		slide_contents.css("margin-left", -marginleft + "px");
	}

	$(window).resize(function() {
		if ( windowWidth != $(window).width() ) {
			setWidth();
		}
	});

	$(".sliderTop-articles article:last-child").prependTo(slide_contents);
	$(".sliderTop-articles").css("margin-left", -marginleft + "px");
	slide_contents.find("a").click(function(){
		return false;
	}).on("touchstart", function(){
		slide_contents.data("href", $(this).attr("href"));
	});

	slide_contents.on("touchstart", function(){

		$(this)
			.data("startX", event.touches[0].pageX)
			.data("startY", event.touches[0].pageY)
			.data("moveX", 0)
			.data("moveY", 0);

		sliderTimer = setTimeout(function() {
//			alert('画像は保存できません');
			event.preventDefault();
			return false;
		}, 500);

	}).on("touchmove",function(){

		$(this)
			.data("moveX", event.touches[0].pageX-$(this).data("startX"))
			.data("moveY", event.touches[0].pageY-$(this).data("startY"))
			.css("margin-left",$(this).data("moveX") -marginleft + "px");
			
		if ( Math.abs($(this).data("moveY")) < 5 ) {
			event.preventDefault();
		}

		clearTimeout(sliderTimer);

	}).on("touchend", function() {

		if( $(this).data("moveX") > 10 ) {

			//右スワイプの場合
			$(this).animate({
				"margin-left": windowWidth - marginleft 
			}, function() {
				$(this).css("margin-left", -marginleft + "px");
				$(".sliderTop-articles article:last-child").prependTo(slide_contents);
			});

		} else if ( $(this).data("moveX") < -10 ) {

			//左スワイプの場合
			$(this).animate({
				"margin-left": -windowWidth - marginleft
			}, function() {
				$(this).css("margin-left", -marginleft + "px");
				$(".sliderTop-articles article:first-child").appendTo(slide_contents);
			});
		} else if ( $(this).data("moveY") > -10 && $(this).data("moveY") < 10 ) {
			//タップ
			location.href = $(this).data("href");
		} else {
			$(this).animate({
				"margin-left": -marginleft
			});
		}

		clearTimeout(sliderTimer);

	});

	// ボタンをクリックしたらスライド
	slide_button.click(function() {

		if ( $(this).hasClass("sliderTop-button-left") ) {
			
			slide_contents.animate({
				"margin-left": windowWidth - marginleft 
			}, function(){
				slide_contents.css("margin-left", -marginleft + "px");
				$(".sliderTop-articles article:last-child").prependTo(slide_contents);
			});

		} else {

			slide_contents.animate({
				"margin-left": -windowWidth - marginleft
			}, function(){
				slide_contents.css("margin-left", -marginleft + "px");
				$(".sliderTop-articles article:first-child").appendTo(slide_contents);
			});
		}
	});
});

