module.exports = {
	plugins: [
		require('/usr/local/lib/node_modules/postcss-uncss')({
			html: [ 
				'https://hkx.monster/',
				'https://hkx.monster/sample-page/',
				'https://hkx.monster/chat/hello-world/',
				'https://hkx.monster/2019/09/',
				'https://hkx.monster/category/chat/'
			],
			ignore: [ new RegExp('toggle|open') ]
		})
	]
};
