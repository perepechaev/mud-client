<?php

function lang($text, $direction = 0){

    static $lang = null;

    if (empty($lang)){
        $lang = $_SERVER['LANG'];
        $lang = strstr($lang, '.');
        $lang = trim($lang, '.');
    }

    if ($lang === 'UTF-8'){
        return $text;
    }

    if ($direction){
        $text = iconv($lang, 'UTF-8', $text);
    }
    else {
        $text = iconv('UTF-8', $lang, $text);
    }
    return $text;
}

function explode_string($str) {
     $split=1;
     $array = array();
     for ( $i=0; $i < strlen( $str ); ){
         $value = ord($str[$i]);
         if($value > 127){
             if($value >= 192 && $value <= 223){
                 $split=2;
             }
             elseif($value >= 224 && $value <= 239){
                 $split=3;
             }
             elseif($value >= 240 && $value <= 247){
                 $split=4;
             }
         }
         else{
             $split=1;
         }
         $key = NULL;
         for ( $j = 0; $j < $split; $j++, $i++ ) {
             $key .= $str[$i];
         }
         array_push( $array, $key );
     }
     return $array;
 }