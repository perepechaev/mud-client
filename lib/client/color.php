<?php

class Color
{
    static public function strlen($text){
        $text = preg_replace("/(\033\[\d+(;\d+)?m)/", '', $text);
        return strlen($text);
    }
}
