const baseConfig = require('@wordpress/jest-preset-default/jest-preset');

module.exports = {
    preset: '@wordpress/jest-preset-default',
    transform: {
        '\\.[jt]sx?$': [
            'babel-jest',
            { presets: [ '@wordpress/babel-preset-default' ] },
        ],
    },
    testMatch: [
        '<rootDir>/tests/blocks/**/*.test.js',
    ],
    setupFiles: [
        ...(baseConfig.setupFiles || []),
        '<rootDir>/tests/blocks/setup-globals.js',
    ],
    moduleNameMapper: {
        ...(baseConfig.moduleNameMapper || {}),
        '^@woocommerce/settings$': '<rootDir>/tests/blocks/mocks/woocommerce-settings.js',
        '^@woocommerce/blocks-registry$': '<rootDir>/tests/blocks/mocks/woocommerce-blocks-registry.js',
        '^@woocommerce/block-data$': '<rootDir>/tests/blocks/mocks/woocommerce-block-data.js',
        '^@wordpress/data$': '<rootDir>/tests/blocks/mocks/wordpress-data.js',
    },
};
