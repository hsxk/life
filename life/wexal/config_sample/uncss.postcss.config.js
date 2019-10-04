module.exports = {
	plugins: [
		require('/usr/local/lib/node_modules/postcss-uncss')({
			html: [ 
				'https://column.prime-strategy.co.jp/',
				'https://column.prime-strategy.co.jp/mailmag',
				'https://column.prime-strategy.co.jp/archives/column_category/mailmag',
				'https://column.prime-strategy.co.jp/archives/column_3244',
				'https://column.prime-strategy.co.jp/archives/column_3172',
				'https://column.prime-strategy.co.jp/archives/column_date/2016/03'
			],
			ignore: [ new RegExp('toggled-on|owl') ]
		}),
		require('/usr/local/lib/node_modules/cssnano')({})
	]

};
