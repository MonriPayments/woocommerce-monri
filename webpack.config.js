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
        "apple-pay": './blocks/apple-pay.js',
        "keks-pay": './blocks/keks-pay.js',
        "pay-cek": './blocks/pay-cek.js',
        "air-cash": './blocks/air-cash.js',
        "flik-pay": './blocks/flik-pay.js',
        "ips-rs": './blocks/ips-rs.js',
    },
    output: {
        path: resolve( process.cwd(), 'assets/js/blocks' ),
    }
};