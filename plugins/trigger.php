<?php

class Trigger extends Hightlight implements IPlugin
{
    protected $filename = 'trigger.php';

    protected function loadConfig($rules){

        $this->rules  = array();
        foreach ($rules as $rule => $value){

            $name   = lang($rule);
            $value  = lang($value);
            
            $name   = strtolower($name);

            $this->rules[$name] = $value;
        }
    } 

    protected function replace($text){
        $lower = strtolower($text);
        foreach ($this->rules as $rule => $command){

            $start = strpos($lower, $rule);
            if ($start === false){
                continue;
            }

            $method = substr($command, 0, strpos($command, '|'));
            $args   = explode(';', substr($command, 1 + strpos($command, '|')));
            call_user_func($method, $args);
        }
        return $text;
    }
}
