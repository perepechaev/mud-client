<?php

class Window 
{
    protected $window;
    protected $colors;

    protected $row;
    protected $col;

    public function __construct($row = null, $col = null, $y, $x){
        if (empty($row) && empty($col)){
            ncurses_getmaxyx(STDSCR, $row, $col);
        }
        $this->row = $row - 1;
        $this->col = $col;
        $this->window = ncurses_newwin($this->row, $this->col, $y, $x);

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
    
    public function getWindow(){
        return $this->window;
    }

    public function display_cursor(){
        ncurses_wrefresh($this->window);
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
            ncurses_waddstr($this->window, $line['text']);
            $this->hookAddColorString($line);
        }
        ncurses_wrefresh($this->window);
    }

    protected function hookAddColorString(array $color){
    }

    public function scroll($direction = -1){
    }
    
    private function strlen($color_string){
        return Color::strlen($color_string);
    }

    protected function parseColorString($text){
        static $tail = '';

        if ($tail){
            $text = $tail . $text;
            $tail = '';
        }

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

            if ($e === false){
                break;
            }

            $pair   = substr($buffer, $p + 2, $e - $p - 2);
            $line = array();
            $line['clicolor'] = "\033[" . $pair . "m";
            $pair   = explode(';', $pair);
            $color  = count($pair) > 1 ? $pair[1] : '37';

            if (isset($colors[$color]) === false){
                ncurses_end();
                var_dump("Incorrect color: ", var_export($color, true), get_class($this));
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

        if (strpos($buffer, "\033") !== false){
            $tail = substr($buffer, strpos($buffer, "\033"));
            $buffer = substr($buffer, 0, strpos($buffer, "\033") - 1);
        }

        $line['text'] = $buffer;
        $result[] = $line;
        return $result;
    }

    public function erase(){
        ncurses_werase($this->window);
        ncurses_wrefresh($this->window);
    }
}
