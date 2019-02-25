<?php
//https://github.com/phpseclib/phpseclib
namespace PHPErrorLog;

use Exception;

require_once __DIR__."/Parser/Parser.php";
require_once __DIR__."/Renderer/Renderer.php";

class PHPErrorLog {

	private $parser;
	private $renderer;

	public function __construct(string $sourceRoot = ""){
		$this->parser = new Parser\Parser();
		$this->renderer = new Renderer\Renderer($sourceRoot);
	}

	public function loadFromFile(string $filename){
		try {
			$this->parser->loadFromFile($filename);
		} catch(Exception $e){
			$this->renderer->renderFileError($filename);
		}
	}

	public function loadFromSFTP(string $filename){
		//$this->parser->loadFromSFTP($filename);
	}

	public function render(){
		$this->renderer->renderLast($this->parser->getErrors());
	}
}