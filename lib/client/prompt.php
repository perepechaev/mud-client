<?php

class Prompt extends Output
{
    protected $row;    
    protected $col;
    protected $cursor = 1;

    /**
     * @var PromptInput
     */
    private $input;

    public function __construct(){
        ncurses_getmaxyx(STDSCR, $row, $col);
        $this->window = ncurses_newwin(1, $col, $row-1, 0);
        $this->row = $row-1;
        $this->col = 0;

        ncurses_wrefresh($this->window);

        $this->input = new Input($this->window);
    }

    public function getCommand(){
        return $this->input->get();
    }
}
