<?php

class Hightlight implements IPlugin
{
    protected $rules  = array();
    private $regexp = array();
    private $lines  = array();

    protected $filename = 'hightlight.php';

    public function prepare($text){
        $rules = command_include_file( __DIR__ . "/../etc/" . $this->filename);

        if ($rules === true){
            return;
        }

        if ($rules === false){
            return;
        }

        $this->loadConfig($rules);
    }

    public function run(){
        //$this->loadRules();
    }

    protected function loadConfig($rules){

        $this->rules  = array();
        $this->regexp = array();
        $this->lines  = array();

        $noline = array();
        foreach ($rules as $rule => $value){

            if ( is_array($value) === false ) {
                $value = array(
                    'color'  => $value,
                    'regexp' => 0,
                    'line'   => 0,
                );
            }

            $name = lang($rule);
            $name = strtolower($name);
            //$name = strtolower($name);

            $this->lines[$name] = empty($value['line']) === false;

            if ( isset($value['regexp']) && $value['regexp']){
                $this->regexp[$name] = $value['color'];
            }
            else {
                if ($this->lines[$name]){
                    $this->rules[$name] = $value['color'];
                }
                else {
                    $noline[$name] = $value['color'];
                }
            }

        }
        $this->rules = array_merge($this->rules, $noline);

        //global $iostream;
        //$iostream->get('output')->send(print_r($this->rules, true)); 
    } 

    public function execute(&$text){
        //$text = print_r($this->rules, true);
        //return;
        $lines = explode("\n", $text);
        foreach ($lines as &$line){
            $line = $this->replace($line);
        }
        $text = implode("\n", $lines);
    }

    protected function replace($text){
        $lower = strtolower($text);
        foreach ($this->rules as $rule => $color){

            $start = strpos($lower, $rule);
            if ($start === false){
                continue;
            }

            if ($this->lines[$rule]){
                $replace = preg_quote("\033[");
                $text = preg_replace("/{$replace}\d{0,2}(;\d{0,2})?m/", "", $text);
                $text = "\033[{$color}m$text\033[0m";
            }
            else {
                $prev = strrpos($text, "\033", $start - strlen($text));
                $prev = $prev !== false ? substr($text, $prev + 2 , strpos($text, 'm', $prev) - $prev - 2) : "0"; 
                $rule = substr($text, $start, strlen($rule));
                $text = str_replace($rule, "\033[{$color}m$rule\033[{$prev}m", $text);
            }
        }
        return $text;
    }
}
