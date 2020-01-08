!function() {
	var w = window;
	var d = document;
	var x = _wexal_pst;
	if ( 'pc' == x.ua ) {
		var p = x.lazy_youtube.pc;
	} else {
		var p = x.lazy_youtube.mobile;	
	}
	var f = function(){
		var y = d.getElementsByClassName( 'wexal-youtube' );
		for (var i=0; i< y.length; i++) {
			var f = y[ i ].children;
			f = f[0];
			var url = f.getAttribute('data-wexal-src');
			f.setAttribute('src', url);

			var load = function(f) {
				var count = 0;
				var fadein = function() {
					count++;
					var op = count / 100;
					var style = f.getAttribute('style');
					style = style.replace( /opacity:.*?;/g, '' );
					f.setAttribute( 'style', style+'opacity: '+op+';' );
					var id = setTimeout(fadein, 3);
					if ( 100 <= count ) {
						clearTimeout(id);
					}
				}
				fadein();
			}
			if ( 'mobile' == x.ua ) {
				var to = 2000;
			} else {
				var to = 1000;
			}
			setTimeout(load, to, f);
		};
	};

	if ( 'loading' != d.readyState ) {
		f();
	} else {
		addEventListener( 'load', f );
	}

}();

