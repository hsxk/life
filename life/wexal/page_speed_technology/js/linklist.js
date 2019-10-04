if ( process.argv[2] ) {
	var url = process.argv[2];
} else {
	var url = 'https://hkx.monster/';
}
//process.env.NODE_TLS_REJECT_UNAUTHORIZED = 0; 

const client = require( '/usr/local/lib/node_modules/cheerio-httpcli' );
const URL = require( 'url' );

//const constants = require( 'constants' ); 
//client.set( 'agentOptions', { secureOptions: constants.SSL_OP_NO_TLSv1_2 } );

const urlReg = new RegExp( '^' + escapeRegExp( url ) );

var hash = {};
hash[ url ] = 1;

client.set( 'browser', 'android' );
client.fetch( url )
.then( function ( ret ) {
//	console.log( client.headers );
	get_links( ret.$ );
	client.set( 'browser', 'chrome' );
	return client.fetch( url );
} )
.then( function ( ret ) {
//	console.log( client.headers );
	get_links( ret.$ );
	console.log( hash );
} );

return;

function get_links( $ ) {
	$( 'a' ).each( function ( idx ) {
		var src = $( this ).attr( 'href' );
		src = URL.resolve( url, src );
		src = src.replace( /#.*$/, '' );
		if ( src.match( urlReg ) ) {
			if ( src in hash ) {
				hash[ src ]++;
			} else {
				hash[ src ] = 1;
			}
		}
	});
}

function escapeRegExp( string ) {
	var reRegExp = /[\\^$.*+?()[\]{}|]/g, reHasRegExp = new RegExp( reRegExp.source );
	return ( string && reHasRegExp.test( string ) )
		? string.replace( reRegExp, '\\$&' )
		: string;
}

