module.exports = {
	plugins: [
		require('/usr/local/lib/node_modules/autoprefixer')({
			overrideBrowserslist: 'ie >= 10',
		  	grid: true
		})
	]
};
