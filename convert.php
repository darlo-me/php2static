#!/usr/bin/php
<?php
error_reporting( E_ALL );
umask( 022 );

function error( $str ) {
	echo( $str );
	return true;
}

// Check arguments
if( $argc != 3 ) {
	error( "[ERROR] Unrecognized arguments, see README." . PHP_EOL );
	exit( 3 );
}

$input = $argv[1];
$dest = $argv[2];

// Security measure, we don't want to overwrite important sutff
if( file_exists( $dest ) ) {
	error( "[ERROR] \"$dest\" already exists, please remove it first." . PHP_EOL );
	exit( 2 );
}

// Provided to php/Module.php
if( substr( $input, -1 ) != '/' )
	$input .= '/';
if( substr( $dest, -1 ) != '/' )
	$dest .= '/';

$config['input_folder'] = $input;

require_once( 'php/Module.php' );

// Strip $input for $dir, we don't want to output pages/ to output/pages for example
function GetOutputDir( $dir ) {
	global $dest, $input;
	
	// This will let the last character of input, which should be /
	if( strlen( $input ) == strlen( $dir ) ) {
		$tmpdir = "";
	} elseif( ( $tmpdir = substr( $dir, strlen( $input ) ) ) === FALSE ) {
		error( "Skipping \"$dir\", cannot extract \"$input\"." . PHP_EOL );
		return false;
	}

	return $dest . $tmpdir;
}

function Directory_Scan( $dir ) {
	$r = true;

	if( substr( $dir, -1 ) != '/' )
		$dir .= '/';
	
	if( !is_dir( $dir ) ) {
		error( "Skipping \"$dir\", not a directory." . PHP_EOL );
		return false;
	}

	if( ( $files = scandir( $dir ) ) === FALSE ) {
		error( "Skipping \"$dir\", files in directory cannot be listed." . PHP_EOL );
		return false;
	}

	if( ( $outputdir = GetOutputDir( $dir ) ) === FALSE )
		return false;

	if( !@mkdir( $outputdir ) ) {
		error( "Skipping \"$dir\", could not make corresponding folder \"$outputdir\"." . PHP_EOL );
		return false;
	}
	
	foreach( $files as $p ) {
		$tmp = substr( $p, 0, 1 );
		if( $tmp == '.' ) {
			continue;
		} elseif( $tmp === FALSE ) {
			$r = false;
			error( "Skipping \"$p\", could not determine if file should be hidden." . PHP_EOL );
			continue;
		}
		unset( $tmp );

		$f = $dir . $p;
		if( is_dir( $f ) ) {
			if( !Directory_Scan( $f ) )
				$r = false;
		} elseif( is_file( $f ) ) {
			$output = $outputdir . preg_replace( '/\.php$/', '', $p );

			if( ( $file = @fopen( $output, 'w+' ) ) === FALSE ) {
				error( "Skipping $f, file cannot be opened for writting." . PHP_EOL );
				$r = false;
				continue;
			}
			
			// Strip the input folder (completely), as it won't be recognized by the templating engine.
			if( ( $shortf = substr( $f, strlen( $GLOBALS['config']['input_folder'] ) ) ) === FALSE ) {
				error( "Skipping $f, cannot extract \"" . $GLOBALS['config']['input_folder'] . "\"" . PHP_EOL );
				continue;
			}
			// NOTE: We may have problems with global variables beind set/unset, we should probably exec( 'php' )
			
			// We want the errors from this.
		  	$mod = (string)new Module( $shortf, true ); // true is to ignore autocomplete
			
			if( @fwrite( $file, $mod ) === FALSE ) {
				error( "Skipping \"$f\", file could not be written to." . PHP_EOL );
				if( !fclose( $file ) ) {
					error( "[ERROR] Could not close \"$f\", file may have random text." . PHP_EOL );
				} elseif( !unlink( $output ) ) {
					error( "[ERROR] Could not delete \"$f\", file may have random text." . PHP_EOL );
				}
				
				$r = false;
				continue;
			} else
				echo( "File \"$output\" has been written from \"$f\"." . PHP_EOL );
		} else {
			error( "Skipping \"$f\", not a file or a directory." . PHP_EOL );
			$r = false;
		}
	}

	return $r;
}

if( Directory_Scan( $input ) === false ) {
	echo( "Error(s) occured during the script." );
	exit( -1 );
}

if( is_dir( $input . '.nophp' ) ) {
	$output = array( );
	$return = 0;

	echo exec( 'cp -Rv --no-preserve=mode ' . escapeshellarg( $input . '.nophp/.' ) . ' ' . escapeshellarg( $dest ), $output, $return ); // -av might be better, but we assume the user will want his files accessible.

	foreach( $output as $line ) {
		echo( $line . PHP_EOL );
	}

	if( $return != 0 ) {
		error( "[ERROR] Could not copy .nophp to \"$input\", it might have been partially done." );
		exit( -2 );
	}
}

exit( 0 );
