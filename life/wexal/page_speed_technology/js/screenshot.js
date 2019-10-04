if ( process.argv[2] ) {
	var url = process.argv[2];
} else {
	var url = 'https://hkx.monster/';
}

const puppeteer = require( '/usr/local/lib/node_modules/puppeteer-core' );
const devices = require( '/usr/local/lib/node_modules/puppeteer-core/DeviceDescriptors' );

(async () => {
	const browser = await puppeteer.launch({
		headless: true,
		executablePath: '/usr/bin/chromium-browser',
		args: [ '--no-sandbox' ]
	});
	const page = await browser.newPage();

	await page.setViewport({ width: 1280, height: 800 });
//	await page.emulate(devices['Nexus 10']);

	await page.goto( url );
	await page.screenshot({
		path: '/home/kusanagi/life/wexal/userdir/out/screenshot.png',
		fullPage: true
	});
	await browser.close();
})();

