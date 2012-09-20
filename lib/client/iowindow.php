<?php

class IOWindow
{
	private $name;
	private $stream;

	public function __construct($name, IOStream $stream){
		$this->name 	= $name;
		$this->stream 	= $stream;
	}

	public function addstr($str){
		$this->stream->send($this->name, $str);
	}

	public function erase(){
		$this->stream->event($this->name, 'erase');
	}
}
