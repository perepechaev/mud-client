<?php

class Window 
{
    protected $window;
    protected $colors;

    protected $row;
    protected $col;

    /** @var Color */
    protected $color;

    public function __construct($row = null, $col = null, $y, $x){
        if (empty($row) && empty($col)){
            ncurses_getmaxyx(STDSCR, $row, $col);
        }
        $this->row = $row - 1;
        $this->col = $col;
        $this->window = ncurses_newwin($this->row, $this->col, $y, $x);

        ncurses_scrollok($this->window, 1);
        ncurses_wattroff($this->window, 1);

        $this->color = new Color(NCURSES_COLOR_WHITE);
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
            if (isset($line['reverse'])){
                if ($line['reverse']){
                    ncurses_wattron($this->window, NCURSES_A_REVERSE);
                }
                else {
                    ncurses_wattroff($this->window, NCURSES_A_REVERSE);
                }
            }
            if (isset($line['color'])){
                ncurses_wcolor_set($this->window, $line['color']);
            }
            ncurses_waddstr($this->window, str_replace("\r", "", $line['text']));
            $this->hookAddColorString($line);
        }
        ncurses_wrefresh($this->window);
    }

    protected function hookAddColorString(array $color){
    }

    protected function parseColorString($text){

        if (empty($this->color)){
            ncurses_end();
            var_dump(get_class($this));
            die(1);
        }
        return $this->color->parse($text);
    }

    public function scroll($direction = -1){
    }
    
    private function strlen($color_string){
        return Color::strlen($color_string);
    }

    public function erase(){
        ncurses_werase($this->window);
        ncurses_wrefresh($this->window);
    }

    public function move($x, $y){
        ncurses_wmove($this->window, $y, $x);
        ncurses_wrefresh($this->window);
    }
}
