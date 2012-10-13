#!/usr/local/bin/php
<?php

require_once __DIR__ . '/../lib/client/iostream.php';
require_once __DIR__ . '/../lib/client/iowindow.php';

require_once __DIR__ . '/../lib/client/color.php';
require_once __DIR__ . '/../lib/client/buffer.php';
require_once __DIR__ . '/../lib/client/window.php';
require_once __DIR__ . '/../lib/client/output.php';
require_once __DIR__ . '/../lib/client/prompt.php';
require_once __DIR__ . '/../lib/client/input.php';
require_once __DIR__ . '/../lib/client/history.php';
require_once __DIR__ . '/../lib/client/commands.php';


$colors = array();
for ($i = 30; $i < 38; $i++){
    $colors[$i] = $i;
}

Color::init($colors);


// ======================================
$color = new Color(37);
$some = array(
    array(
        'color' => 37,
        'bold'  => 0,
        'text'  => 'Some text',
    )
);
eq($some, $color->parse("Some text"), __FILE__ . ":" . __LINE__);


// ======================================
$color = new Color(37);
$value = "Some \033[0;36mtext";
$expect = array(
    array(
        'color'     => 37,
        'bold'      => 0,
        'text'      => 'Some ',
    ),
    array(
        'color'     => 36,
        'bold'      => 0,
        'clicolor'  => "\033[0;36m", 
        'text'      => 'text',
    )
);
eq($expect, $color->parse($value), __FILE__ . ":" . __LINE__);

// ======================================
$color = new Color(37);
$value = "Some \033[0;36m\ntext";
$expect = array(
    array(
        'color'     => 37,
        'bold'      => 0,
        'text'      => 'Some ',
    ),
    array(
        'color'     => 36,
        'bold'      => 0,
        'clicolor'  => "\033[0;36m", 
        'text'      => "\ntext",
    )
);
eq($expect, $color->parse($value), __FILE__ . ":" . __LINE__);

// ======================================
$color = new Color(37);
$value1 = "Some \033[0;3";
$value2 = "6mtext";
$expect = array(
    array(
        'color'     => 37,
        'bold'      => 0,
        'text'      => 'Some ',
    ),
    array(
        'color'     => 36,
        'bold'      => 0,
        'clicolor'  => "\033[0;36m", 
        'text'      => "text",
    )
);
//eq($expect, $color->parse($value1), __FILE__ . ":" . __LINE__);
//eq($expect, $color->parse($value2), __FILE__ . ":" . __LINE__);
eq($expect, $r = array_merge($color->parse($value1), $color->parse($value2)), __FILE__ . ":" . __LINE__);
foreach ($r as $i => $line){
    foreach ($line as $key => $value){
        if ($value !== $expect[$i][$key]){
            echo "Not equal for line line[$i][$key]\n";
            var_dump($expect[$i][$key], $value);
        }
    }
}



echo "\n";








function eq($expection, $value, $msg){
    if ($expection !== $value){
        echo "ERR: $msg\n";
        echo str_replace("\033", "\033[0;31m\\x1b\033[0m", print_r($expection, true));
        echo str_replace("\033", "\033[0;31m\\x1b\033[0m", print_r($value, true));
        return;
    }
    echo ".";
}

function df($filename, $message){
    echo "\033[0;31mError: \033[0m $filename\n$message\n";
}
