<?php
//https://github.com/phpseclib/phpseclib
namespace PHPErrorLogParser;

require_once __DIR__."/ErrorException.php";

class PHPErrorLog {

	private $file;
	private $errors = [];

	public function loadFile($filename){
		if(!($this->file = @fopen($filename,'rb'))){
			throw new \Exception("File '".$filename."' not found.");
		}
	}

	/*public function loadFileBySftp($filename){
		if(!($this->file = @fopen($filename,'rb'))){
			throw new \Exception("File '".$filename."' not found.");
		}
	}*/

	public function render(){
		$error = end($this->parse());
		print_r($error);
	}

	public function parse($limit = 1000):array {
		if($this->errors) return $this->errors;
		if(!$this->file){
			throw new \Exception("File is not loaded.");
		}
		while(!feof($this->file)){
			$currentLine = str_replace(PHP_EOL,'',fgets($this->file));
			if('[' === $currentLine[0]){
				if(count($this->errors) === $limit){
					return $this->errors;
				}

				$errorDateTime = null;
				try {
					$dateArr = [];
					preg_match('~^\[(.*?)\]~', $currentLine, $dateArr);
					$currentLine = str_replace($dateArr[0], '', $currentLine);
					$currentLine = trim($currentLine);
					$errorDateTime = \DateTime::createFromFormat('D M d H:i:s.u Y',$dateArr[1]);
					$errorDateTime = $errorDateTime->getTimestamp();
				} catch (\Exception $e){
					$errorDateTime = '';
					echo $e->getMessage();
				}

				if(false !== strpos($currentLine, 'PHP Warning')){
					$currentLine = explode('PHP Warning:', $currentLine)[1];
					$currentLine = trim($currentLine);
					$errorType = 'WARNING';
				}
				else if(false !== strpos($currentLine, 'PHP Notice')){
					$currentLine = explode('PHP Notice:', $currentLine)[1];
					$currentLine = trim($currentLine);
					$errorType = 'NOTICE';
				}
				else if(false !== strpos($currentLine, 'PHP Fatal error')){
					$currentLine = explode('PHP Fatal error:', $currentLine)[1];
					$currentLine = trim($currentLine);
					$errorType = 'FATAL';
				}
				else if(false !== strpos($currentLine, 'PHP Parse error')){
					$currentLine = explode('PHP Parse error:', $currentLine)[1];
					$currentLine = trim($currentLine);
					$errorType = 'SYNTAX';
				}
				else if(false !== strpos($currentLine, 'PHP Exception')){
					$currentLine = explode('PHP Exception:', $currentLine)[1];
					$currentLine = trim($currentLine);
					$errorType = 'EXCEPTION';
				}
				else {
					continue;
				}

				$errorFile = null;
				$errorLine = null;
				if(false !== strpos($currentLine, ' on line ')){
					preg_match('/in ([a-zA-Z0-9\/\\.]*) on line (\d+)/', $currentLine, $line);
					$errorFile = trim($line[1]);
					$errorLine = $line[2];
					$currentLine = str_replace('in '.$errorFile.' on line '.$errorLine, '', $currentLine);
				}
				else {
					continue;
				}

				$stackTrace = null;
				if(false !== strpos($currentLine, '\nStack trace:')){
					$stackTrace = explode('\nStack trace:',$currentLine)[1];
					$currentLine = explode('\nStack trace:',$currentLine)[0];
				}
				$currentLine = str_replace(' in '.$errorFile.':'.$errorLine, '', $currentLine);

				if($stackTrace){
					$stackLines = explode('\n',$stackTrace);
					$stackTrace = [];
					foreach($stackLines AS $stackLine){
						if(!empty($stackLine)){
							if(strpos($stackLine, '{main}') !== false) break;
							preg_match('/#\d+ (.*)\((\d+)\): (.*\((.*)\))/', $stackLine, $stack);
							$stack = [
								'file' => $stack[1],
								'line' => $stack[2],
								'function' => explode('(',$stack[3])[0],
								'args' => []
							];
							$stackTrace[] = $stack;
						}
					}
				}

				$errorMessage = trim($currentLine);

				$this->errors[] = [
					'timestamp'   => $errorDateTime,
					'type'       => $errorType,
					'file'       => $errorFile,
					'line'       => (int)$errorLine,
					'message'    => $errorMessage,
					'stack' => $stackTrace,
				];
			}
		}
		return $this->errors;
	}
}