const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path          = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: async () => {
		const discovered =
			typeof defaultConfig.entry === 'function'
				? await defaultConfig.entry()
				: defaultConfig.entry ?? {};

		return {
			...discovered,
			'admin/index': path.resolve( __dirname, 'src/admin/index.js' ),
		};
	},
};
