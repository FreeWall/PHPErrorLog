<?php
namespace PHPErrorLog\Parser;

class Parser {

	private $file;
	private $errors;

	public function setFile($file){
		$this->file = $file;
	}

	public function getErrors():array {
		if(!$this->errors) $this->errors = $this->parse();
		return $this->errors;
	}

	private function parse():array {
		$errors = [];
		if($this->file){
			while(!feof($this->file)){
				$currentLine = str_replace(PHP_EOL,'',fgets($this->file));
				if('[' === $currentLine[0]){
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
						preg_match('/in ([a-zA-Z0-9\/\\\.]*) on line (\d+)/', $currentLine, $line);
						$errorFile = trim($line[1]);
						$errorLine = $line[2];
						$currentLine = str_replace('in '.$errorFile.' on line '.$errorLine, '', $currentLine);
					}
					else {
						continue;
					}

					$stackTrace = [];
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
								if(strpos($stackLine, '{main}') !== false || strpos($stackLine, '{m ') !== false) break;
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
					if(strpos($errorMessage,'Uncaught Error:') !== false) $errorMessage = trim(explode('Uncaught Error:',$currentLine)[1]);

					$errors[] = [
						'timestamp' => $errorDateTime,
						'type'      => $errorType,
						'file'      => $errorFile,
						'line'      => (int)$errorLine,
						'message'   => $errorMessage,
						'stack'     => $stackTrace,
					];
				}
			}
			fclose($this->file);
		}
		return $errors;
	}
}