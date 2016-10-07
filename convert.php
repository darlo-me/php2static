#!/usr/bin/php
<?php
error_reporting( E_ALL );
//umask( 022 ); user should set that

function error( $str ) {
	echo( $str );
	return true;
}

function ExitWrapper( $status ) {
	if( $status == 0 )
		echo "Script exited successfully." . PHP_EOL;
	else
		echo "Script exited with exit status $status." . PHP_EOL;
	
	exit( $status );
}

// Check arguments
if( $argc != 3 ) {
	error( "[ERROR] Unrecognized arguments, see README." . PHP_EOL . PHP_EOL );
	ExitWrapper( 3 );
}

$input = $argv[1];
$dest = $argv[2];

// Security measure, we don't want to overwrite important sutff
if( file_exists( $dest ) ) {
	error( "[ERROR] \"$dest\" already exists, please remove it first." . PHP_EOL . PHP_EOL );
	ExitWrapper( 2 );
}

// Provided to php/Module.php
if( substr( $input, -1 ) != '/' )
	$input .= '/';
if( substr( $dest, -1 ) != '/' )
	$dest .= '/';

$config = array( 'input_folder' => $input );

// Strip $input for $dir, we don't want to output pages/ to output/pages for example
function GetOutputDir( $dir ) {
	global $dest, $input;
	
	// This will let the last character of input, which should be /
	if( strlen( $input ) == strlen( $dir ) ) {
		$tmpdir = "";
	} elseif( ( $tmpdir = substr( $dir, strlen( $input ) ) ) === FALSE ) {
		error( "Skipping \"$dir\", cannot extract \"$input\"." . PHP_EOL . PHP_EOL );
		return false;
	}

	return $dest . $tmpdir;
}

function Directory_Scan( $dir ) {
	global $config;

	$r = true;

	if( substr( $dir, -1 ) != '/' )
		$dir .= '/';
	
	if( !is_dir( $dir ) ) {
		error( "Skipping \"$dir\", not a directory." . PHP_EOL . PHP_EOL );
		return false;
	}

	if( ( $files = scandir( $dir ) ) === FALSE ) {
		error( "Skipping \"$dir\", files in directory cannot be listed." . PHP_EOL . PHP_EOL );
		return false;
	}

	if( ( $outputdir = GetOutputDir( $dir ) ) === FALSE )
		return false;

	if( !@mkdir( $outputdir ) ) {
		error( "Skipping \"$dir\", could not make corresponding folder \"$outputdir\"." . PHP_EOL . PHP_EOL );
		return false;
	}
	
	foreach( $files as $p ) {
		$tmp = substr( $p, 0, 1 );
		if( $tmp == '.' ) {
			continue;
		} elseif( $tmp === FALSE ) {
			$r = false;
			error( "Skipping \"$p\", could not determine if file should be hidden." . PHP_EOL . PHP_EOL );
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
				error( "Skipping $f, file cannot be opened for writting." . PHP_EOL . PHP_EOL );
				$r = false;
				continue;
			}

			echo "Executing/reading \"$f\"..." . PHP_EOL;
			echo "---------------------------------------------------------" . PHP_EOL;
			// Strip the input folder (completely), as it won't be recognized by the templating engine.
			if( ( $shortf = substr( $f, strlen( $config['input_folder'] ) ) ) === FALSE ) {
				error( "Skipping $f, cannot extract \"" . $config['input_folder'] . "\"" . PHP_EOL );

				if( !fclose( $file ) )
					error( "[ERROR] Could not close \"$f\", file may have random content." . PHP_EOL );
				elseif( !unlink( $output ) )
					error( "[ERROR] Could not delete \"$f\", file may have random content." . PHP_EOL );

				$r = false;

				// continue; // We want fancy output.
			} else {
				$mod = array( );
				$return = -1;

# #################
# note:
# Module::input_folder is optional, as it could be set from the
# executed script. Setting it here seems like a sane default
# #################
				$command = '
require_once( "' . addslashes( stream_resolve_include_path( "php/Module.php" ) ) . '" );
if( FALSE == Module::input_folder( "' . addslashes( stream_resolve_include_path( $config['input_folder'] ) ) . '" ) || !chdir( "' . addslashes( $dir ) . '" ) ) {
	exit( 1 );
}
echo new Module( "' . addslashes( $shortf ) . '", true );';
				
				// Like eval, but isolated scope.
				exec( 'php -r ' . escapeshellarg( $command ), $mod, $return );
				
				if( $return != 0 || @fwrite( $file, implode( PHP_EOL, $mod ) ) === FALSE ) {
					error( "Skipping \"$f\", " . ( $return == 0 ? "file could not be written to." : "file returned with an error (return value: $return)." ) . PHP_EOL );
	
					if( !fclose( $file ) )
						error( "[ERROR] Could not close \"$f\", file may have random content." . PHP_EOL );
					elseif( !unlink( $output ) )
						error( "[ERROR] Could not delete \"$f\", file may have random content." . PHP_EOL );
					
					$r = false;
					// continue; // We want fancy output
				} else {
					echo "File \"$output\" has been written from \"$f\"." . PHP_EOL;
				}
			}
			echo "---------------------------------------------------------" . PHP_EOL . PHP_EOL;
		} else {
			error( "Skipping \"$f\", not a file or a directory." . PHP_EOL );
			$r = false;
		}
	}

	return $r;
}

echo "Scanning directory $input for files..." . PHP_EOL . PHP_EOL;
if( Directory_Scan( $input ) === false )
	ExitWrapper( -1 );

if( is_dir( $input . '.nophp' ) ) {
	$output = array( );
	$return = 0;
	
	echo "Copying $input.nophp to $dest..." . PHP_EOL;
	echo "---------------------------------------------------------" . PHP_EOL;
	echo exec( 'cp -Rv --no-preserve=mode ' . escapeshellarg( $input . '.nophp/.' ) . ' ' . escapeshellarg( $dest ), $output, $return ); // -av might be better, but we assume the user will want his files accessible.

	foreach( $output as $line ) {
		echo( $line . PHP_EOL );
	}

	if( $return != 0 ) {
		error( "[ERROR] Could not copy .nophp to \"$input\", it might have been partially done." . PHP_EOL . PHP_EOL );
		ExitWrapper( -2 );
	}

	echo "---------------------------------------------------------" . PHP_EOL . PHP_EOL;
}

ExitWrapper( 0 );
