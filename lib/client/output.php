<?php

class Output extends Window
{
    /* @var Buffer */
    private $buffer;
    private $buffer_last_color = '';

    private $buffer_line = 0;

    private $scrolling = 0;

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

    public function init(){
        $this->buffer = new Buffer($this->row, $this->col);
    }

    protected function hookAddColorString($line){
        if (isset($line['clicolor'])){
            $this->buffer->setColor($line['clicolor']);
        }
        $this->buffer->add($line['text']);
        $this->buffer_line = $this->buffer->getCountLines();
    }

    public function scroll($direction = -1){
        $current = $this->buffer_line - $this->row;
        $start   = $current + $direction ;
        if ($start < 0){
            $direction -= $start;
            $start = 0;
        }

        if ($direction === 0){
            return;
        }

        if ($start > $this->buffer->getCountLines() - $this->row){
            $start = $this->buffer->getCountLines() - $this->row;
            $direction = $start - $current;
        }

        ncurses_wscrl($this->window, $direction);
        ncurses_scrollok($this->window, 0);

        $lines = min($this->row, abs($direction));
        if ($direction > 0){
            // Down
            ncurses_wmove($this->window, $this->row - $direction, 0);
            $strings = $this->buffer->getLines($start + $this->row - $direction, $lines);
        }
        else {
            // Up
            ncurses_wmove($this->window, 0, 0);
            $strings = $this->buffer->getLines($start, $lines);
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
        ncurses_scrollok($this->window, 1);
        ncurses_wrefresh($this->window);
    }
    
    private function strlen($color_string){
        return Color::strlen($color_string);
    }

    public function dump(){
        //file_put_contents('buffer', getmypid() . "\n" . print_r($this->buffer, true), FILE_APPEND);
        df('buffer', print_r($this->buffer->getLines(0, $this->buffer->getCountLines() - 1), true));
    }

    public function erase(){
        ncurses_werase($this->window);
        ncurses_wrefresh($this->window);
    }
}
