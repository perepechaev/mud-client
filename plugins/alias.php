<?php

class AliasPlugin implements IInputPlugin
{
    private $db;

    private $aliases;

    public function init(){

        $commands = command_include_file(APP_PATH . '/etc/alias.php');

        if ($commands === true){
            return;
        }

        if ($commands === false){
            return;
        }

        $this->aliases = array();
        foreach ($commands as $key => $value){
            $this->aliases[iconv('UTF-8', 'KOI8-R', $key)] = iconv('UTF-8', 'KOI8-R', $value);
        }
    }

    public function command($text){
        
        file_put_contents('command.log', iconv('KOI8-R', 'UTF-8', $text) . "\n", FILE_APPEND);
        $pos = strpos($text, ' ');
        if ($pos !== false){
            $key = substr($text, 0, $pos);
        }
        else {
            $key = $text;
        }

        file_put_contents('command.log', "KEY: " . iconv('KOI8-R', 'UTF-8', $key) . "\n", FILE_APPEND);
        file_put_contents('command.log', print_r($this->aliases, true), FILE_APPEND);
        if (isset($this->aliases[$key])){
            file_put_contents('command.log', 'ALIAS COMPLETE' . "\n" , FILE_APPEND);
            return $this->aliases[$key];
        }
        return false;
    }
    
    static public function create(){
        static $instance;
        if (empty($instance)){
            $instance = new self();
        }
        $instance->init();
        return $instance;
    }
}
