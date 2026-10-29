/* Generates translation JSON for blocks */

/* eslint-disable no-console */
/* eslint no-process-exit: 0, no-undef: 0, strict: 0 */
'use strict';
const { exec, mv, ls } = require( 'shelljs' );
const colors = require( 'colors' );
const path = require( 'path' );

const pluginDir = path.resolve( __dirname, '..' );
const languagesDir = path.join( pluginDir, 'languages' );
const mapFile = path.join( languagesDir, 'build-map.json' );

console.log( colors.blue( 'Generating JSON translations with WP-CLI...' ) );

const wpCliCmd = `wp i18n make-json "${languagesDir}" "${languagesDir}" --use-map="${mapFile}" --no-purge`;
const result = exec( wpCliCmd );

if ( result.code !== 0 ) {
    console.error( colors.red( 'WP-CLI make-json failed.' ) );
    process.exit( result.code );
}

// Rename generated MD5 files to handle-based filenames
const md5Hash = '957d4dcbaf21620e3376c07b579b27ad';
const generatedFiles = ls( path.join( languagesDir, `*-${md5Hash}.json` ) );

generatedFiles.forEach( ( file ) => {
    const targetFile = file.replace( `-${md5Hash}.json`, '-monri-wc-payments-blocks.json' );
    mv( '-f', file, targetFile );
    console.log( colors.green( `Updated ${path.basename( targetFile )}` ) );
} );

console.log( colors.green( 'Translation JSON files updated successfully.' ) );
