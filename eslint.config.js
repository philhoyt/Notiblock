/*
 * ESLint flat config for Notiblock.
 *
 * Extends the @wordpress/scripts default and teaches the import plugin that
 * WordPress packages are provided at runtime — they are webpack externals
 * resolved to the `wp.*` globals, not project dependencies, so
 * `import/no-unresolved` and `import/no-extraneous-dependencies` would
 * otherwise flag every one of them.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

/**
 * WordPress packages consumed as build-time externals. Keep in sync with the
 * imports under src/ — `npm run build` writes the resulting handles into each
 * *.asset.php dependency array.
 */
const WORDPRESS_EXTERNALS = [
	'@wordpress/a11y',
	'@wordpress/api-fetch',
	'@wordpress/block-editor',
	'@wordpress/blocks',
	'@wordpress/components',
	'@wordpress/element',
	'@wordpress/i18n',
	'@wordpress/rich-text',
];

module.exports = [
	{
		ignores: [
			'**/build/**',
			'**/node_modules/**',
			'**/vendor/**',
			// Vendored third-party library — not ours to lint.
			'lib/**',
		],
	},

	...defaultConfig,

	{
		settings: {
			'import/core-modules': WORDPRESS_EXTERNALS,
		},
	},
];
