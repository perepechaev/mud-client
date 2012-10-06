<?php

class Output extends Window
{
    /* @var Buffer */
    private $buffer;
    private $buffer_last_color = '';

    private $buffer_line = 0;

    private $withoutBuffer = false;

    private $scrolling = false;

    private $output;

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

        if ($this->withoutBuffer){
            return;
        }

        if (isset($line['clicolor'])){
            $this->buffer->setColor($line['clicolor']);
        }
        $this->buffer->add($line['text']);
    }

    public function scroll($direction = -1){

        static $buffer_win, $buffer;

        $height = 10;

        if ($direction === BUFFER_UP){
            $direction = $height - $this->row + 1;
        }

        if ($direction === BUFFER_DOWN){
            $direction = $this->row - $height - 1;
        }

        if ($this->scrolling === false){
            $this->buffer_line = $this->buffer->getCountLines() - $height;
        }

        $current = $this->buffer_line - $this->row + $height + 1;

        $start   = $current + $direction ;
        if ($start < 0){
            $direction -= $start;
            $start = 0;
        }

        if ($direction === 0){
            return;
        }

        if ($this->scrolling && $start > $this->buffer->getCountLines() - $this->row){
            $this->window = $this->output;
            $this->scrolling = false;
            ncurses_wmove($this->window, 0, 0);
            $lines = $this->buffer->getLines( $this->buffer->getCountLines() - $this->row, $this->row);
            $this->withoutBuffer = true;
            $this->add(implode("\n", $lines));
            $this->withoutBuffer = false;
            return;
        }
        elseif ($start > $this->buffer->getCountLines() - $this->row){
            return;
        }

        if ($this->scrolling === false){
            $this->output = $this->window;
            $this->window = ncurses_newwin($height - 1, $this->col, $this->row - $height + 1, 0);
            $buffer       = new Window($this->row - $height, $this->col, 0, 0);
            $buffer_win   = $buffer->getWindow();

            $hr           = ncurses_newwin(1, $this->col, $this->row - $height - 1, 0);
            ncurses_whline($hr, ord('~' /* ─ */), $this->col);
            ncurses_wrefresh($hr);

            ncurses_scrollok($this->window, 1);
            ncurses_wattroff($this->window, 1);

            $buffer_start = $this->buffer->getCountLines() - $this->row + $direction + 1;
            $buffer_count = $this->row - $height - 1;
            $lines = $this->buffer->getLines($buffer_start, $buffer_count); 
            $buffer->add(implode("\n", $lines));
            $this->buffer_line += $direction;

            $this->scrolling = true;
            return;
        }

        ncurses_wscrl($buffer_win, $direction);
        ncurses_scrollok($buffer_win, 0);

        $lines = min($this->row - $height, abs($direction));
        if ($direction > 0){
            // Down
            ncurses_wmove($buffer_win, $this->row - $height - $direction - 1, 0);
            $strings = $this->buffer->getLines($start - $direction + $this->row - $height - 1, $lines);
        }
        else {
            // Up
            ncurses_wmove($buffer_win, 0, 0);
            $strings = $this->buffer->getLines($start, $lines);
        }

        $text = '';
        foreach ($strings as $string){
            $last =  ($this->strlen($string) >= $this->col) ? "" : "\n";
            $text .= $string . $last;
        }
        $strings = rtrim($text);
        $buffer->add($text);

        $this->buffer_line = $start + $this->row - $height - 1;
        ncurses_scrollok($buffer_win, 1);
        ncurses_wrefresh($buffer_win);
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
