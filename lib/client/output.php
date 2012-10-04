<?php

class Output
{
    protected $window;
    private $colors;
    protected $cursor = 0;

    protected $row;
    protected $col;

    private $buffer = array();
    private $buffer_last_color = '';

    private $buffer_line = 0;

    public function __construct(){
        ncurses_getmaxyx(STDSCR, $row, $col);
        $this->row = $row - 1;
        $this->col = $col;
        $this->window = ncurses_newwin($this->row, $this->col, 0, 0);

        ncurses_scrollok($this->window, 1);
        ncurses_wattroff($this->window, 1);

        ncurses_assume_default_colors(NCURSES_COLOR_WHITE, -1);
        $this->colors = array(
            '30'  => NCURSES_COLOR_BLACK,
            '31'  => NCURSES_COLOR_RED,
            '32'  => NCURSES_COLOR_GREEN,
            '33'  => NCURSES_COLOR_YELLOW,
            '34'  => NCURSES_COLOR_BLUE,
            '35'  => NCURSES_COLOR_MAGENTA,
            '36'  => NCURSES_COLOR_CYAN,
            '37'  => NCURSES_COLOR_WHITE,
        );
        foreach ($this->colors as $color) {
            ncurses_init_pair($color, $color, -1);
        }
    }
    public function addstr($text){
        $this->add($text);
    }


    public function add($text){
        $lines = $this->parseColorString($text);

        foreach ($lines as $line){
            if (isset($line['bold'])){
                $line['bold'] ? ncurses_wattron($this->window, NCURSES_A_BOLD)
                              : ncurses_wattroff($this->window, NCURSES_A_BOLD);

            }
            if (isset($line['stand'])){
                // DONT USED
                $line['stand'] ? ncurses_wstandend($this->window)
                               : ncurses_wattroff($this->window, NCURSES_A_BOLD);
            }
            if (isset($line['color'])){
                ncurses_wcolor_set($this->window, $line['color']);
            }
            if (isset($line['clicolor'])){
                $this->buffer_last_color = $line['clicolor'];
            }
            ncurses_waddstr($this->window, $line['text']);
            $this->addToBuffer($line['text']);
        }
        ncurses_wrefresh($this->window);
    }

    public function scroll($direction = -1){
        $current = $this->buffer_line - $this->row;
        $start   = $current + $direction ;
        if ($start < 0){
            $direction -= $start;
            $start = 0;
        }

        if ($start > count($this->buffer) - $this->row){
            $start = count($this->buffer) - $this->row;
            $direction = $start - $current;
        }

        ncurses_wscrl($this->window, $direction);
        ncurses_scrollok($this->window, 0);

        $lines = min($this->row, abs($direction));
        if ($direction > 0){
            // Down
            ncurses_wmove($this->window, $this->row - $direction, 0);
            $strings = array_slice($this->buffer, $start + $this->row - $direction, $lines);
        }
        else {
            // Up
            ncurses_wmove($this->window, 0, 0);
            $strings = array_slice($this->buffer, $start, $lines);
        }

        $text = '';
        foreach ($strings as $string){
            $last =  ($this->strlen($string) >= $this->col) ? "" : "\n";
            $text .= $string . $last;
        }
        $strings = rtrim($text);

        $this->buffer_line = $start + $this->row;
        $lines = $this->parseColorString($strings);

        foreach ($lines as $line){
            if (isset($line['bold'])){
                $line['bold'] ? ncurses_wattron($this->window, NCURSES_A_BOLD)
                              : ncurses_wattroff($this->window, NCURSES_A_BOLD);
            }
            if (isset($line['color'])){
                ncurses_wcolor_set($this->window, $line['color']);
            }
            if (isset($line['clicolor'])){
                $this->buffer_last_color = $line['clicolor'];
            }
            ncurses_waddstr($this->window, $line['text']);
        }
        ncurses_wrefresh($this->window);
        ncurses_scrollok($this->window, 1);
    }
    
    private function strlen($color_string){
        $color_string = preg_replace("/(\033\[\d+(;\d+)?m)/", '', $color_string);
        
        return strlen($color_string);
    }

    protected function addToBuffer($text){
        static $last_symbol = "\n";

        if (empty($text)){
            $this->buffer_last_color = "\033[0m";
        }

        if (substr($text, 0, 1) === "\n"){
            $text = substr($text, 1);
            $last_symbol = "\n";
        }

        if ($last_symbol !== "\n"){
            if (strpos($text, "\n") === false){
                $last_buffer = &$this->buffer[count($this->buffer) - 1];
                if ($this->strlen($last_buffer . $text) > $this->col){
                    $pos = $this->col - $this->strlen($last_buffer);
                    $last_buffer .= $this->buffer_last_color . substr($text, 0, $pos);
                    $text = substr($text, $pos);
                    foreach (str_split($text, $this->col) as $str){
                        $this->buffer[] = $this->buffer_last_color . $str;
                    }
                }
                else {
                    $last_buffer .= $this->buffer_last_color . $text;
                }
                $this->buffer_last_color = "\033[0m";
                $last_symbol = '';
                $this->buffer_line = count($this->buffer);
                return;
            }
            $this->buffer[count($this->buffer) - 1] .= $this->buffer_last_color . substr($text, 0, strpos($text, "\n"));
            $this->buffer_last_color = "\033[0m";
            $text = substr($text, strpos($text, "\n") + 1);
            if (empty($text) || $text === "\n"){
                $last_symbol = "\n";
                return;
            }
            if (strpos($text, "\n") === false){
                $this->buffer[] = $this->buffer_last_color . $text;
                $this->buffer_last_color = "\033[0m";
                $this->buffer_line = count($this->buffer);
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
                $this->buffer[] = $this->buffer_last_color . $str;
            }
        }
        $this->buffer_last_color = "\033[0m";
        $this->buffer_line = count($this->buffer);
    }

    private function parseColorString($text){

        $result = array();
        $colors = $this->colors;

        $buffer = $text;
        $output = '';
        $line   = array();
        $line['color'] = NCURSES_COLOR_WHITE;
        $line['bold']  = 0;
        while ( false !== ($p = strpos($buffer, "\x1b"))){
            $output = substr($buffer, 0, $p);

            $line['text'] = $output;
            $result[] = $line;

            $e      = strpos($buffer, "m", $p);

            $pair   = substr($buffer, $p + 2, $e - $p - 2);
            $line = array();
            $line['clicolor'] = "\033[" . $pair . "m";
            $pair   = explode(';', $pair);
            $color  = count($pair) > 1 ? $pair[1] : '37';

            if (isset($colors[$color]) === false){
                ncurses_end();
                var_dump("Incorrect color: ", var_export($color, true));
                var_dump($pair);
                die;
            }

            if ($pair[0]){
                $line['bold'] = 1;
                #ncurses_wattron($this->window, NCURSES_A_BOLD);
            }
            else{
                $line['bold'] = 0;
                #ncurses_wattroff($this->window, NCURSES_A_BOLD);
            }


            if (count($pair) === 1 && $pair[0] === '0'){
                $line['stand'] =  0;
                $line['color'] =  NCURSES_COLOR_WHITE;
                #ncurses_wstandend($this->window);
                #ncurses_wcolor_set($this->window, NCURSES_COLOR_WHITE);
            }
            else{
                $line['color'] = $colors[$color];
                #ncurses_wcolor_set($this->window, $colors[$color]);
            }

            $buffer = substr($buffer, $e + 1 );
        }

        #$this->addToBuffer($buffer);
        $line['text'] = $buffer;
        $result[] = $line;
        #ncurses_waddstr($this->window, $buffer);
        #ncurses_wrefresh($this->window);
        return $result;
    }


    public function dump(){
        file_put_contents('buffer', getmypid() . "\n" . print_r($this->buffer, true), FILE_APPEND);
    }

    public function erase(){
        ncurses_werase($this->window);
        ncurses_wrefresh($this->window);
    }
}
