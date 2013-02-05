<?php

class Input 
{
    private $window;
    private $history;
    public function __construct($window){
        $this->window = $window;
        $this->history = History::instance();
    }

    function get(){

        global $iostream;

        static $cnt = 0;


        $buffer = '';
        $output = '';
        while ( true ){
            $iostream->get('prompt')->erase();
            $iostream->get('prompt')->addstr('> ' . $output);

            $key    = ncurses_getch( );
            $char   = chr($key);

            if ( $char === KEY_ENTER ){
                /*
                $GLOBALS['history']['cursor'] = array_search($output, $history);
                if ($GLOBALS['history']['cursor'] === false){
                    $history[] = $output;
                }
                else {
                    $GLOBALS['history']['cursor']++;
                }
                */
                $this->history->add($output);
                return $output;
            }

            if ( $char === KEY_ESC ){
                return false;
            }

            if ( $char === KEY_BACKSPACE ){
                $output = substr( $output, 0, -1 );
                $buffer = $output;
                continue;
            }

            if ( $key === KEY_UP ){
                $output = $this->history->search($buffer, -1);
                continue;
            }

            if ( $key === KEY_DOWN ){
                $output = $this->history->search($buffer, +1);
                continue;
            }

            $buffer .= $char;
            $output .= $char;
        }
    }
}