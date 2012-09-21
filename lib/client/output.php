<?php

class Output
{
    protected $window;
    private $colors;
    protected $cursor = 0;

    public function __construct(){
        ncurses_getmaxyx(STDSCR, $row, $col);
        $this->window = ncurses_newwin($row - 1, $col, 0, 0);

        ncurses_wrefresh($this->window);
        ncurses_scrollok($this->window);
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

        $colors = $this->colors;

        $buffer = $text;
        $output = '';
        ncurses_curs_set($this->cursor);
        while ( false !== ($p = strpos($buffer, "\x1b"))){
            $output = substr($buffer, 0, $p);
            ncurses_waddstr($this->window, $output);

            $e      = strpos($buffer, "m", $p);

            $pair   = substr($buffer, $p + 2, $e - $p - 2);
            $pair   = explode(';', $pair);
            $color  = count($pair) > 1 ? $pair[1] : '37';

            if (isset($colors[$color]) === false){
                ncurses_end();
                var_dump("Incorrect color: ", var_export($color, true));
                var_dump($pair);
                die;
            }

            if ($pair[0]){
                ncurses_wattron($this->window, NCURSES_A_BOLD);
            }
            else{
                ncurses_wattroff($this->window, NCURSES_A_BOLD);
            }

            if (count($pair) === 1 && $pair[0] === '0'){
                ncurses_wstandend($this->window);
                ncurses_wcolor_set($this->window, NCURSES_COLOR_WHITE);
            }
            else{
                ncurses_wcolor_set($this->window, $colors[$color]);
            }

            $buffer = substr($buffer, $e + 1 );
        }

        ncurses_waddstr($this->window, $buffer);
        ncurses_wrefresh($this->window);
    }

    public function erase(){
        ncurses_werase($this->window);
        ncurses_wrefresh($this->window);
    }
}
