#!/usr/bin/env node
/**
 * Assemble Free / Pro plugin folders and create distributable zip files.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execSync } from 'node:child_process';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const root = path.resolve( __dirname, '..' );
const config = JSON.parse( fs.readFileSync( path.join( __dirname, 'config.json' ), 'utf8' ) );

const args = process.argv.slice( 2 );
const only = args.find( ( a ) => a.startsWith( '--edition=' ) )?.split( '=' )[1];
const editionsToBuild = only ? [ only ] : Object.keys( config.editions );

/**
 * @param {string} filePath
 * @return {boolean}
 */
function shouldSkip( filePath ) {
	const base = path.basename( filePath );
	if ( base === '.DS_Store' || base === 'Thumbs.db' ) {
		return true;
	}
	return false;
}

/**
 * @param {string} src
 * @param {string} dest
 * @param {string[]} excludeRelative
 */
function copyDir( src, dest, excludeRelative = [] ) {
	if ( ! fs.existsSync( src ) ) {
		throw new Error( `Missing source directory: ${ src }` );
	}
	fs.mkdirSync( dest, { recursive: true } );
	for ( const entry of fs.readdirSync( src, { withFileTypes: true } ) ) {
		if ( shouldSkip( entry.name ) ) {
			continue;
		}
		const srcPath = path.join( src, entry.name );
		const rel = path.relative( src, srcPath );
		if ( excludeRelative.some( ( ex ) => rel === ex || rel.startsWith( ex + path.sep ) ) ) {
			continue;
		}
		const destPath = path.join( dest, entry.name );
		if ( entry.isDirectory() ) {
			copyDir( srcPath, destPath, excludeRelative );
		} else {
			fs.copyFileSync( srcPath, destPath );
		}
	}
}

/**
 * @param {string} editionKey
 */
function readVersion( editionKey ) {
	const overlay = path.join( root, 'packages', config.editions[ editionKey ].overlay );
	const mainFiles = fs.readdirSync( overlay ).filter( ( f ) => f.endsWith( '.php' ) );
	for ( const file of mainFiles ) {
		const content = fs.readFileSync( path.join( overlay, file ), 'utf8' );
		const match = content.match( /\* Version:\s*([0-9.]+)/ );
		if ( match ) {
			return match[1];
		}
	}
	throw new Error( `Could not read version for edition: ${ editionKey }` );
}

/**
 * @param {string} editionKey
 */
function assembleEdition( editionKey ) {
	const edition = config.editions[ editionKey ];
	const coreSrc = path.join( root, config.corePackage );
	const overlaySrc = path.join( root, 'packages', edition.overlay );
	const outDir = path.join( root, config.distDir, edition.folder );

	fs.rmSync( outDir, { recursive: true, force: true } );
	fs.mkdirSync( outDir, { recursive: true } );

	copyDir( coreSrc, outDir, edition.excludeFromCore || [] );
	copyDir( overlaySrc, outDir );

	console.log( `Assembled ${ editionKey } → ${ path.relative( root, outDir ) }` );
	return { outDir, edition, version: readVersion( editionKey ) };
}

/**
 * @param {string} folderName
 * @param {string} zipFileName
 */
function createZip( folderName, zipFileName ) {
	const distRoot = path.join( root, config.distDir );
	const zipDir = path.join( root, config.zipDir );
	fs.mkdirSync( zipDir, { recursive: true } );
	const zipPath = path.join( zipDir, zipFileName );
	if ( fs.existsSync( zipPath ) ) {
		fs.unlinkSync( zipPath );
	}
	execSync( `zip -r -q ${ JSON.stringify( zipPath ) } ${ JSON.stringify( folderName ) }`, {
		cwd: distRoot,
		stdio: 'inherit',
	} );
	console.log( `Created ${ path.relative( root, zipPath ) }` );
}

for ( const editionKey of editionsToBuild ) {
	if ( ! config.editions[ editionKey ] ) {
		console.error( `Unknown edition: ${ editionKey }` );
		process.exit( 1 );
	}
	const { edition, version } = assembleEdition( editionKey );
	const zipName = edition.zipNameTemplate.replace( '{version}', version );
	createZip( edition.folder, zipName );
}

console.log( 'Done.' );
