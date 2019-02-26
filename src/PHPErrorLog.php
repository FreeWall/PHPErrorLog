<?php
//https://github.com/phpseclib/phpseclib
//https://github.com/nufue/pecl-ssh2-windows
namespace PHPErrorLog;

use Exception;

require_once __DIR__."/Parser/Parser.php";
require_once __DIR__."/Renderer/Renderer.php";

class PHPErrorLog {

	private $parser;
	private $renderer;

	private $tmpFile;

	public function __construct(array $editorMapping = []){
		$this->parser = new Parser\Parser();
		$this->renderer = new Renderer\Renderer($editorMapping);
	}

	public function loadFromFile(string $filename){
		try {
			if(!($file = @fopen($filename,'rb'))){
				throw new \Exception("File '".$filename."' not found.");
			}
			$this->parser->setFile($file);
		} catch(Exception $e){
			$this->renderer->renderFileError($filename);
		}
	}

	public function loadFromSFTP(string $filename,string $host,string $username,string $password,string $pubkeyFile = null,string $privkeyFile = null,string $passphrase = null){
		try {
			$conn = ssh2_connect($host);
			if(!$pubkeyFile){
				if(!@ssh2_auth_password($conn,$username,$password)){
					throw new \Exception("Failed to authenticate over SSH.");
				}
			} else {
				if(!ssh2_auth_pubkey_file($conn,$username,$pubkeyFile,$privkeyFile,$passphrase)){
					throw new \Exception("Failed to authenticate over SSH using public key.");
				}
			}
			$this->tmpFile = tempnam(__DIR__,'log');
			if(!ssh2_scp_recv($conn,$filename,$this->tmpFile)){
				throw new \Exception("Failed to download file '".$filename."'.");
			}
			$this->parser->setFile(@fopen($this->tmpFile,'rb'));
		} catch(Exception $e){
			$this->renderer->renderException($e);
		}
	}

	public function render(){
		$this->renderer->renderLast($this->parser->getErrors());
	}

	public function __destruct(){
		if($this->tmpFile) @unlink($this->tmpFile);
	}
}