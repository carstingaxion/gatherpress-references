/**
 * Jest configuration for GatherPress References block.
 *
 * @see https://jestjs.io/docs/configuration
 */

const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	roots: [ '<rootDir>/test/js/' ],
	testPathIgnorePatterns: [ '/node_modules/', '/test/php/' ],
	setupFilesAfterEnv: defaultConfig.setupFilesAfterEnv || [],
	transform: {
		...( defaultConfig.transform || {} ),
		'^.+\\.[jt]sx?$': 'babel-jest',
	},
	moduleNameMapper: {
		...( defaultConfig.moduleNameMapper || {} ),
		'\\.(css|scss)$': '<rootDir>/test/js/__mocks__/style-mock.js',
	},
};
