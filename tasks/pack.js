/* eslint-disable no-console */
/* eslint no-process-exit: 0, no-undef: 0, strict: 0 */
'use strict';
const { mkdir, cp } = require( 'shelljs' );
const colors = require( 'colors' );
const archiver = require( 'archiver' );
const fs = require( 'fs' );
const { rimrafSync: rimraf } = require('rimraf');

const pluginSlug = 'monri-payments';

// some config
const releaseFolder = 'release';
const targetFolder = 'release/' + pluginSlug;
const filesToCopy = [
    'assets',
    'includes',
    'languages',
    'templates',
    'monri.php',
    'Monri_woocommerce_postavke.pdf',
    'Monri_woocommerce_postavke_wspay.pdf',
    'readme.txt',
    'version.php',
    'index.php',
];

// start with a clean release folder

rimraf(releaseFolder);
mkdir( releaseFolder );
mkdir( targetFolder );

// copy the directories to the release folder
cp( '-Rf', filesToCopy, targetFolder );

const output = fs.createWriteStream(
    releaseFolder + '/' + pluginSlug + '.zip'
);
const archive = archiver( 'zip', { zlib: { level: 9 } } );

output.on( 'close', () => {
    console.log(
        colors.green(
            'All done: Release is built in the ' + releaseFolder + ' folder.'
        )
    );
} );

archive.on( 'error', ( err ) => {
    console.error(
        colors.red(
            'An error occured while creating the zip: ' +
            err +
            '\nYou can still probably create the zip manually from the ' +
            targetFolder +
            ' folder.'
        )
    );
} );

archive.pipe( output );

archive.directory( targetFolder, pluginSlug );

archive.finalize().then(() => {
    console.log(
        colors.green(
            'Archiving finalized.'
        )
    )
});