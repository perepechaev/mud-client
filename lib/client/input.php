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
            $this->plugins[lang($key)] = $value;
        }

        file_put_contents('plugins.log', print_r($this->plugins, true), FILE_APPEND);
    }

    public function get(){

        global $iostream;

        $buffer = '';
        $output = '';
        $input = array();
        $position = 0;

        while ( true ){
            $iostream->get('prompt')->erase();
            $iostream->get('prompt')->addstr('> ' . $output);
            $iostream->get('prompt')->move(2 + $position, 0);

            $key = getch();

            if ($key > 0xff){
                $char = pack('n', $key);
            }
            else {
                $char = chr($key);
            }

            if ( $key === KEY_ENTER ){
                $this->history->add($output);
                if ($this->handle($output)){
                    $output = '';
                    $buffer = '';
                    continue;
                }
                return $output;
            }

            if ( $key === KEY_ESC ){
                return false;
            }

            if ( $key === KEY_BACKSPACE ){
                $input = array_merge(array_slice($input, 0, $position - 1), array_slice($input, $position));
                $output = implode('', $input);
                $position = max($position - 1, 0);
                $buffer = $output;
                continue;
            }

            if ( $key === KEY_DELETE){
                $input = array_merge(array_slice($input, 0, $position), array_slice($input, $position + 1));
                $output = implode('', $input);
                $buffer = $output;
                continue;
            }

            if ( $key === KEY_UP ){
                $output = $this->history->search($buffer, -1);
                $input = explode_string($output);
                continue;
            }

            if ( $key === KEY_DOWN ){
                $output = $this->history->search($buffer, +1);
                $input = explode_string($output);
                continue;
            }

            if ( $key === KEY_LEFT){
                $position--;
                $position = max($position, 0);

                continue;
            }

            if ( $key === KEY_RIGHT){
                $position++;
                $position = min(count($input), $position);
                continue;
            }

            if ( $key === KEY_ALT_LEFT){
                for ($i = $position - 1; $i >= 0; $i--){
                    if ($input[$i] === ' '){
                        write_log("Find space: " . $i);
                        break;
                    }
                }
                write_log('new position' . $i);
                $position = $i;
                $position = max(0, $position);
                continue;
            }

            if ( $key === KEY_ALT_RIGHT){
                for ($i = $position + 1; $i < count($input); $i++){
                    if ($input[$i] === ' '){
                        break;
                    }
                }
                $position = $i;
                $position = min(count($input), $position);
                continue;
            }

            if ( $key === KEY_HOME){
                $position = 0;
                continue;
            }

            if ( $key === KEY_END){
                $position = count($input);
                continue;
            }

            if ( $key === KEY_CTRL_K ){
                $input = array_slice($input, 0, $position);
                $output = implode('', $input);
                $buffer = $output;
                continue;
            }

            $position++;

            if ($position === count($input) + 1){
                $input[] = $char;
                $output = $buffer = implode('', $input);
            }
            else {
                $input = array_merge(array_slice($input, 0, $position - 1), array($char), array_slice($input, $position - 1));
                $output = $buffer = implode('', $input);
            }
        }
    }

    public function handle($command){

        global $iostream;

        foreach ($this->plugins as $info => $value){
            $text = '';
            if (is_numeric($info)){
                $text = $command;
            }
            elseif ( strpos($command, $info . ' ') === 0){
                $text = substr($command, strlen($info) + 1);
            }

            if ($text){
                $plugin = $value::create();
                $result = $plugin->command($text);
        
                if ($result === false){
                    continue;
                }
                
                $iostream->get('output')->addstr($result);
                return true;
            }
        }

        return false;
    }
}
