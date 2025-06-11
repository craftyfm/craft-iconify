/* jshint esversion: 6 */
/* globals module, require, webpack */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
    context: __dirname,
    config: {
        entry: {
            app: './main.js',
        },
        output: {
            filename: 'js/app.js',
            chunkFilename: 'js/[name].js',
        },
    },
});