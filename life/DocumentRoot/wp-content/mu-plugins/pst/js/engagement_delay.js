(function() {
	var e = _wexal_pst.en;
	var d = document;
	var w = window;
	var c = d.cookie.split(';');
	var cE = d.createElement.bind(d);
	var gE = d.getElementsByTagName.bind(d);
	var log = function(m) { if (e.dbg) { console.log(m); } };
	var lz = function(t,z) {
		if (e.dbg) {
			t = t + ': c=' + z.c;
			if ( 1 == z.x ) {
				t = t + ': P1 ';
			} else {
				t = t + ': P2 ';
			}
			if ( 1 == z.s ) {
				t = t + ': sync';
			} else {
				t = t + ': as';
			}
			console.log(t,z);
		}
	};
	var sT = setTimeout;
	e.ssc = 0;
	e.psc = 0;
	e.start = new Date();
	c.some(function(v) {
		var c=v.split('=');
		if ( c[0].trim() == '_wexal_ssc' ) {
			e.ssc = Number(c[1]);
			return true;
		}
	});
	e.ssc = 80 + e.ssc;
	e.stck = function() {
		d.cookie = '_wexal_ssc=' + e.ssc + '; max-age=' + e.ma + '; path=/;';
	}
	var evt=false;
	if ( e.sc < e.ssc ) {
		if ( 'body' != e.hg ) {
			evt = e.hg;
		}
	} else {
		if ( 'body' != e.lw ) {
			evt = e.lw;
		}
		e.stck();
		var es = [ 'beforeunload', 'scroll', 'keydown', 'mousemove', 'click', 'touchstart' ];
		var f = function(evt) {
			if ( 'beforeunload' == evt.type ) {
				e.end = new Date();
				var t = e.end.getTime() - e.start.getTime();
				t = Math.floor( t / 1000 )
				e.ssc = Number(e.psc) + e.ssc + t;
				e.stck();
			} else if ( 'scroll' == evt.type ) {
				e.psc = 4 + e.psc;
			} else if ( 'mousemove' != evt.type ) {
				e.psc = 24 + e.psc;
			}
			e.psc = 1 + e.psc;
			if ( e.sc < Number(e.psc) + e.ssc ) {
				for ( var i = 0, len = es.length; i<len; i++ ) {
					w.removeEventListener( es[i], f, false );
				}
				e.ssc = Number(e.psc) + e.ssc;
				e.stck();
			}
			if ( e.ps < e.psc && true != e.fired ) {
					engd();
			}
			log( evt.type + ': ' + e.psc );
		}
		for ( var i = 0, len = es.length; i<len; i++ ) {
			w.addEventListener( es[i], f, false );
		}
	}

	var engd = function() {
		if ( true == e.fired ) { return; }
		e.fired = true;
		var rs = [ 'js', 'css', 'fn' ];
		var sn = { p1:[], p2:[] };
		var as = { p1:[], p2:[] };
		
		Object.keys( e ).forEach( function( j ) {
			if ( rs.indexOf( j ) >= 0 ) {	
				Object.keys( e[j] ).forEach( function( k ) {
					var z = e[j][k];
					var tz = { t: j, z: z};
					if ( 1 == z.s ) {
						if ( 1 == z.x ) {
							sn.p1[ z.c ] = tz;
						} else {
							sn.p2[ z.c ] = tz;
						}
					} else {
						if ( 1 == z.x ) {
							as.p1[ z.c ] = tz;
						} else {
							as.p2[ z.c ] = tz;
						}
					}
				});
			}
		});

		log( { as: as, sync: sn } );

		var sync = function (p) {
			for ( var i=0; i < sn[p].length; i++ ) {
				if ( sn[p][i] ) {
					q.push( sn[p][i] );
				}
			}
			r(q.shift());
		}

		var r = function (a) {
			if ( undefined == a ) {
				if ( 'p1' == p ) {
					p = 'p2';
					log( '[Phase 2]' );
					async(p);
				}
				return;
			}
			var ret = false;
			var done = false;
			if ( 'css' == a.t ) {
				ret = css(a.z );
			} else if ( 'js' == a.t ) {
				ret = js( a.z );
			} else if ( 'fn' == a.t ) {
				ret = fn( a.z );
			}
			var rr = function() {
				if ( false == done ) {
					done = true;
					r( q.shift() );
				}
			}
			if ( ret ) {
 				ret.onload = rr;
				sT(rr, 3000);
			} else {
				rr();
			}
		}
		
		var async = function (p) {
			for ( var i=0; i < as[p].length; i++ ) {
				var a = as[p][i];
				if ( a ) {
					if ( 'css' == a.t ) {
						sT( css, 0, a.z );
					} else if ( 'js' == a.t ) {
						sT( js, 0, a.z );
					} else if ( 'fn' == a.t ) {
						sT( fn, 0, a.z );
					}
				}
			}
			sync(p);
		}

		var js = function (z) {
			var l = cE('script');
			l.src = z.url;
			if ( z.attr ) {
				Object.keys( z.attr ).forEach( function (k) {
					l.setAttribute( k, z.attr[k] );
				});
			}
			var s = gE('script');
			s = s[ s.length -1 ];
			s.parentNode.insertBefore(l, s);
			lz( 'js', z );
			return l;
		}

		var css = function (z) {
			var l = cE('link');
			l.rel = 'stylesheet';
			l.href = z.url;
			var s = gE('script')[0];
			s.parentNode.insertBefore(l, s);
			lz( 'css', z );
			return l;
		}

		var fn = function (z) {
			try {
				z.f.apply(this, z.p);
				if ( Array.isArray( z.cmd ) ) {
					var cmd = z.cmd;
					for( var i=0, len = cmd.length; i < len; i++ ) {
						var f = cmd[i].shift();
						eval( 'f=' + f );
						f.apply( this, cmd[i] );
						log( cmd[i] );
					}
				}
			} catch( err ) {
				log( err );
			} finally {
				lz( 'fn', z );
				return false;
			}
		}

		log( '[Phase 1]' );
		var q = [];
		var p = 'p1';
		async( p );

	}

	var init = function() {
		var dl;
		if ( 'pc' == _wexal_pst.ua ) {
			dl = e.dl;
		} else {
			dl = e.dl * e.rt;
		}
		dl = dl * ( e.sc - e.ssc + 100 ) / e.sc;
		if ( e.sc < e.ssc ) {
			dl = 0;
		}
		log( 'site score : ' + e.ssc + ' / ' + e.sc + ' delay: ' + dl );
		sT( engd, dl );
	}

	if ( evt ) {
		w.addEventListener( evt, init );
	} else {
		init();
	}
	
})();

