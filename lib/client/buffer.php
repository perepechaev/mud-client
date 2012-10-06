<?php

class Buffer
{
    private $last_color;

    private $buffer = array();

    private $row;
    private $col;

    public function __construct($row, $col){
        $this->row = $row;
        $this->col = $col;
    }

    public function setColor($color){
        $this->last_color = $color;
    }

    public function getLines($start, $length){
        df('getLines', "Start: $start, Length: $length");
        return array_slice($this->buffer, $start, $length);
    }

    public function getCountLines(){
        return count($this->buffer);
    }

    public function add($text){
        static $last_symbol = "\n";
        $text = str_replace("\x00", "", $text);

        if (empty($text)){
            $this->last_color = "\033[0m";
        }

        if (substr($text, 0, 1) === "\n"){
            $text = substr($text, 1);
            $last_symbol = "\n";
        }

        if ($last_symbol !== "\n"){
            if (strpos($text, "\n") === false){
                $last_buffer = &$this->buffer[count($this->buffer) - 1];
                if (Color::strlen($last_buffer . $text) > $this->col){
                    $pos = $this->col - Color::strlen($last_buffer);
                    $last_buffer .= $this->last_color . substr($text, 0, $pos);
                    $text = substr($text, $pos);
                    foreach (str_split($text, $this->col) as $str){
                        $this->buffer[] = $this->last_color . $str;
                    }
                }
                else {
                    $last_buffer .= $this->last_color . $text;
                }
                $this->last_color = "\033[0m";
                $last_symbol = '';
                return;
            }
            if (isset($this->buffer[count($this->buffer) - 1]) === false){
                $this->buffer[] = '';
            }
            $this->buffer[count($this->buffer) - 1] .= $this->last_color . substr($text, 0, strpos($text, "\n"));
            $this->last_color = "\033[0m";
            $text = substr($text, strpos($text, "\n") + 1);
            if (empty($text) || $text === "\n"){
                $this->buffer[] = '';
                $last_symbol = "\n";
                return;
            }
            if (strpos($text, "\n") === false){
                $this->buffer[] = $this->last_color . $text;
                $this->last_color = "\033[0m";
                return;
            }
        }

        $last_symbol = substr($text, -1);

        if ($last_symbol === "\n"){
            $text = "\033[0;31m" . substr($text, 0, -1);
        }

        $lines = explode("\n", $text);
        foreach ($lines as $line){
            foreach (str_split($line, $this->col) as $str){
                $this->buffer[] = $this->last_color . $str;
            }
        }
        $this->last_color = "\033[0m";
    }
}
