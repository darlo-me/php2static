<?php
abstract class ModuleBase implements ArrayAccess {
    /**
     * _process is used so that included files inside the Module class do not fuck with the internals of the module
     * e.g. included file could try using $this->inputFolder, expecting to use the inputFolder argument
     */
    abstract protected function _process(string $filename): string;

    /** @var array values shared with module using "$this->content", or Array/ObjectAccess */
    private $content = array();

    /** @var string path of the module */
	private $filename;

    /** @var string|null content of the processed module */
	private $moduleContent;

    /** @var string */
	private static $inputFolder = ".parts/";

    /**
     * Set folder where to find modules
     */
    public static function setInputFolder(string $inputFolder): void {
        if (substr( $inputFolder, -1 ) != '/') {
            $inputFolder .= '/';
        }

        self::$inputFolder = $inputFolder;
    }

    /**
     * Get folder where modules are searched
     */
    public static function getInputFolder(): string {
        return self::$inputFolder;
    }

    /**
     * @param string $filename Filename of the module
     * @param bool $directFilename Do not parse $filename or add inputFolder
     */
	function __construct( string $filename, bool $directFilename=false ) {
        if (!$directFilename) {
            $path = pathinfo($filename);

            if (!isset($path['extension'])) {
				$filename .= '.php';
			}

            // If it does not specify a folder and is not a hidden file, we assume .parts/
            // should be prepended.
            if (substr($filename, 0, 1) !== "/") {
                $filename = self::$inputFolder . $filename;
            }
		}

		$this->filename = $filename;
    }

    /**
     * Process the module
     * @param bool $forceReprocess process even if already processed
     * @return string Content of the processed module
     * @throws \Exception if the file cannot be processed
     */
    public function process(bool $forceReprocess = false): string {
        if ($forceReprocess || !$this->moduleContent) {
            $this->moduleContent = $this->_process($this->filename);
        }

        return $this->moduleContent;
    }

    /*
     * @return string Content of the module
     */
    public function __toString(): string {
        return $this->process();
    }

    /** ArrayAccess */
    public function offsetSet($offset, $value): void {
        if (is_null($offset)) {
            $this->content[] = $value;
        } else {
            $this->content[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool {
        return isset($this->content[$offset]);
    }

    public function offsetUnset($offset): void {
        unset($this->content[$offset]);
    }

    public function &offsetGet($offset) {
        if(!isset($this->content[$offset])) {
            throw new RuntimeException("Undefined offset {$offset}");
        }
        return $this->content[$offset]; // this does not return an error, since it is a reference
    }

    /** Access as object */
    public function &__get($offset) {
        return $this->offsetGet($offset);
    }

    public function __set($offset, $value): void {
        $this->offsetSet($offset, $value);
    }

    public function __isset($offset): bool {
        return $this->offsetExists($offset);
    }

    public function __unset($offset): void {
        $this->offsetUnset($offset);
    }
}

class Module extends ModuleBase {
    protected function _process(string $filename): string {
		if (substr($filename, -4) == '.php') {
			if (!ob_start( )) {
                throw new \Exception( "Could not start output buffering." );
            }

            // Possible values: whatever include returns, (usually 1), or FALSE if it breaks.
            // ob_get_contents returns FALSE on failure too.
            $r = (include($filename)) !== false ? ob_get_contents() : false;
			
			if (!ob_end_clean()) {
                throw new \Exception( "Could not clean the output buffer." );
            }
		} else {
			// Returns FALSE on failure.
			$r = file_get_contents( $filename, true );
		}

		if ($r === false) {
            throw new \Exception( "Could not include \"{$filename}\", does the file exist? If it is a php file, is output buffering supported?" );
        }

        return $r;
    }
}
