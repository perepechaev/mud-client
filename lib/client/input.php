<?php

class Input 
{
    private $window;
    private $history;
    private $plugins = array();

    public function __construct($window){
        $this->window = $window;
        $this->history = History::instance();
        $commands = command_include_file(APP_PATH . '/etc/input.php');

        foreach ($commands as $key => $value){
            $this->plugins[iconv('UTF-8', 'KOI8-R', $key)] = $value;
        }
        file_put_contents('plugins.log', print_r($this->plugins, true), FILE_APPEND);
    }

    public function get(){

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
                if ($this->handle($output)){
                    $output = '';
                    $buffer = '';
                    continue;
                }
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

    public function handle($command){

        global $iostream;

        foreach ($this->plugins as $info => $value){
            if (strpos($command, $info . ' ') === 0){
                $plugin = $value::create();
                $result = $plugin->command(substr($command, strlen($info) + 1));
        
                
                $iostream->get('output')->addstr($result);
                return true;
            }
        }

        return false;
    }
}
