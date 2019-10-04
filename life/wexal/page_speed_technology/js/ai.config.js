if ( process.argv[2] ) {
	var url = process.argv[2];
} else {
	var url = 'https://hkx.monster/';
}

const wexal_dir = '/home/kusanagi/life/wexal';

const puppeteer = require( '/usr/local/lib/node_modules/puppeteer-core' );
const devices = require( '/usr/local/lib/node_modules/puppeteer-core/DeviceDescriptors' );
const cheerio = require( '/usr/local/lib/node_modules/cheerio' );
const yaml = require( '/usr/local/lib/node_modules/js-yaml' );
const fs = require('fs');

var arr=[];
var html;

async function test() {

	const browser = await puppeteer.launch({
		headless: true,
//		devTools: true,
		executablePath: '/usr/bin/chromium-browser',
//		slowMo: 100,
		args: [ '--no-sandbox' ]
	});

	try {
		const page = await browser.newPage();

//		await page.setViewport({ width: 1280, height: 800 });
		await page.emulate(devices['Nexus 10']);
/*
		await page.coverage.startCSSCoverage({
			resetOnNavigation: false
		});
		await page.coverage.startJSCoverage({
			resetOnNavigation: false
		}); 
*/
//		await page.goto( url, {waitUntil: "networkidle0"} );
		await page.goto( url, {waitUntil: "load"} );
//		const csscv = await page.coverage.stopCSSCoverage();
//		const jscv = await page.coverage.stopJSCoverage();

		const scripts = await page.$$('script');

		for ( var script of scripts ) {
			var src = await (await script.getProperty('src')).jsonValue();
			var defer = await (await script.getProperty('defer')).jsonValue();
			var async = await (await script.getProperty('async')).jsonValue();
			var code = await (await script.getProperty('innerHTML')).jsonValue();
			arr.push( [ { src: src, defer: defer, async: async, code: code } ] );
//			console.log( src, defer, async, code );
		}
	
		var item = await page.$('html');
		html = await ( await item.getProperty('outerHTML')).jsonValue();
//		console.log( html );
//		console.log( arr );
	} catch( e ) {
		throw e;
	} finally {
		await browser.close();
	}

};

(async function(){
	var ret = await test();
	var $ = cheerio.load( html, {decodeEntities: false} );
	var filepath = wexal_dir + '/config_sample/default.pst.config.yaml';
	var str = fs.readFileSync(filepath, 'utf8');

	var conf = yaml.safeLoad(str);


	if ( html.match( /wp-emoji/ ) ) {
		conf.wp.wexal_enqueue_opt.push( { cmd: 'remove emoji' } );
	}

	if ( html.match( /<meta name="generator"/ ) ) {
		conf.wp.wexal_enqueue_opt.push( { cmd: 'remove meta' } );
	}

	conf.wp.wexal_init.push( { cmd: 'remove header' } );
	conf.wp.wexal_flush.push( { cmd: 'shorten url' } );
	conf.wp.wexal_flush.push( { cmd: 'server push external css' } );


	conf = yaml.dump( conf );
	console.log( conf );
//	console.log( $.html() );
//	console.log( html );
})();
