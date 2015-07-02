<?php
class Module {
	public $Arguments;
	private $filename;

	function __construct( $filename, $ignore_short=false ) {
		$this->Arguments = array( );
		
		// If only a filename is provided.
		if( !$ignore_short ) {
			if( strpos( $filename, '.' ) === FALSE ) {
				$filename .= '.php';
			}

			if( preg_match( '/^[^\/]+$/', $filename ) && substr( $filename, 0, 1 ) != '.' ) {
				$filename = '.parts/' . $filename;
			}
		}

		$this->filename = $GLOBALS['config']['input_folder'] . $filename;
	}

	public function __toString( ) {
		$Arguments = $this->Arguments;
		if( substr( $this->filename, -4 ) == '.php' ) {
			ob_start( );
			include( $this->filename );
			$r = ob_get_clean( );
		} else {
			$r = file_get_contents( $this->filename, true );
		}

		return $r;
	}
}
