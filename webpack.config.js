const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const {resolve} = require("path");

module.exports = {
    ...defaultConfig,
    plugins: [
        ...defaultConfig.plugins.filter(
            ( plugin ) =>
                plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
        ),
        new DependencyExtractionWebpackPlugin(),
    ],
    entry: {
        index: './blocks/index.js',
        "google-pay": './blocks/google-pay.js',
        "keks-pay": './blocks/keks-pay.js',
        "pay-cek": './blocks/pay-cek.js',
    },
    output: {
        path: resolve( process.cwd(), 'assets/js/blocks' ),
    }
};