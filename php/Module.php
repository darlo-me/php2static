<?php
class Module {
	public $Arguments;
	public $Output;

	private $filename;
	private $fileContent;
	private $processed;

	private static $input_folder;

	public static function input_folder( $ip = FALSE ) {
		if( $ip ) {
			if( substr( $ip, -1 ) != '/' )
				$ip .= '/';
			
			self::$input_folder = $ip;
		}

		return self::$input_folder;
	}

	function __construct( $filename, $ignore_short=false ) {
		$this->Arguments = array( );
		$this->Output = array( );

		$this->processed = false;
		
		// If only a filename is provided.
		if( !$ignore_short ) {
			if( strpos( $filename, '.' ) === FALSE ) {
				$filename .= '.php';
			}
			
			// If it does not specify a folder, we assume .parts/ should be prepended.
			// Or if specifies a hidden file (which will probably not be in .parts/
			// If you want to specify a file without this, you should probably use $ignore_short
			if( preg_match( '/^[^\/]+$/', $filename ) && substr( $filename, 0, 1 ) != '.' ) {
				$filename = '.parts/' . $filename;
			}
		}

		$this->filename = self::$input_folder . $filename;
	}

	public function Process( ) {
		$this->processed = true;

		$Arguments = $this->Arguments;

		if( substr( $this->filename, -4 ) == '.php' ) {
			if( !ob_start( ) )
				throw new Exception( "Could not start output buffering." );
			
			// Possible values: whatever include returns, (usually 1), or FALSE if it breaks.
			// ob_get_contents returns FALSE on failure too.
			$r = ( ( include( $this->filename ) ) !== FALSE ) ? ob_get_contents( ) : FALSE;
			
			if( !ob_end_clean( ) )
				throw new Exception( "Could not clean the output buffer." );
		} else {
			// Returns FALSE on failure.
			$r = file_get_contents( $this->filename );
		}

		if( $r === FALSE )
			throw new Exception( "Could not include \"" . $this->filename . "\", does the file exist? If it is a php file, is output buffering supported?" );

		$this->fileContent = $r;

		if( isset( $Output ) && is_array( $Output ) )
			$this->Output = $Output;
	}

	public function __toString( ) {
		if( !$this->processed ) {
			// PHP is not good with toString, so we do a workaround to throw error
			try {
				$this->Process( );
			} catch( Exception $e ) {
				trigger_error( $e->getMessage( ) );
				return false;
			}
		}

		return $this->fileContent ? $this->fileContent : FALSE;
	}
}
