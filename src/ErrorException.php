<?php
namespace PHPErrorLogParser;

class ErrorException {

	private $timestamp;
	private $type;
	private $file;
	private $line;
	private $message;
	private $stack;

	public function __construct($args){
		$this->timestamp = $args['timestamp'];
		$this->type = $args['type'];
		$this->file = $args['file'];
		$this->line = $args['line'];
		$this->message = $args['message'];
		$this->stack = $args['stack'];
	}

	public function getTimestamp(){
		return $this->timestamp;
	}

	public function getType(){
		return $this->type;
	}

	public function getFile(){
		return $this->file;
	}

	public function getLine(){
		return $this->line;
	}

	public function getMessage(){
		return $this->message;
	}

	public function getTrace():array {
		return $this->stack;
	}
}