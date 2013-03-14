<?php

class IOWindow
{
    private $name;
    private $stream;

    public function __construct($name, IOStream $stream){
        $this->name   = $name;
        $this->stream = $stream;
    }

    public function addstr($str){
        $this->stream->send($this->name, $str);
    }

    public function move($x, $y){
        $this->stream->event($this->name, "move|$x|$y");
    }

    public function erase(){
        $this->stream->event($this->name, 'erase');
    }

    public function scroll($direction = -1){
        $this->stream->event($this->name, "scroll|$direction");
    }
}
