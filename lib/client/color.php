<?php

class Color
{
    static private $colors = array();
    static private $cli    = array();

    private $tail_color;
    private $last_color;
    private $default_color;

    public function __construct($default_color){
        $this->last_color = $default_color;
        $this->default_color = $default_color;
    }

    public static function init($colors){
        self::$colors = $colors;
        self::$cli    = array_flip($colors);
    }

    public function parse($text){

        $result = array();

        $line   = array();
        $line['color'] = $this->last_color;
        $line['bold']  = 0;

        if ($this->tail_color){
            $text = $this->tail_color . $text;
            $line = $this->getColorByCliPair(substr($text, 2, strpos($text, 'm') - 2));

            $this->tail_color = '';
            $text = substr($text, strpos($text, 'm') + 1);
        }

        $buffer = $text;
        $output = '';

        while ( false !== ($p = strpos($buffer, "\x1b"))){

            $e = strpos($buffer, "m", $p);
            if ($e === false){
                break;
            }

            $output = substr($buffer, 0, $p);
            $line['text'] = $output;
            $result[] = $line;

            $pair   = substr($buffer, $p + 2, $e - $p - 2);
            $line   = $this->getColorByCliPair($pair);

            $buffer = substr($buffer, $e + 1 );
        }

        if (strpos($buffer, "\033") !== false){
            $this->tail_color = substr($buffer, strpos($buffer, "\033"));
            $buffer = substr($buffer, 0, strpos($buffer, "\033"));
        }

        $line['text'] = $buffer;
        $result[] = $line;
        $this->last_color = $line['color'];
        return $result;
    }

    /**
     * @var $pair string Cli-color without \x1b symbol, for example $pair = "1;36"
     * @return array
     */
    private function getColorByCliPair($pair){

        if ($pair === "0"){
            return array(
                'color' => $this->default_color,
                'bold'  => 0,
                'clicolor' => "\033[0;" . self::$cli[$this->default_color] . "m",
            );
        }

        $line   = array();

        $pair   = explode(';', $pair);
        $color  = count($pair) > 1 ? $pair[1] : self::$cli[$this->default_color];

        if (isset(self::$colors[$color]) === false){
            df('incorrect.color', __FILE__ . ":" . __LINE__ . "\n" . print_r($pair, true));
            return array();
        }

        if (count($pair) === 1 && $pair[0] === '0'){
            df('info', 'dont use');
            $line['stand'] =  0;
            $line['color'] =  $this->default_color;
        }
        else{
            $line['color'] = self::$colors[$color];
        }

        if ($pair[0]){
            $line['bold'] = 1;
        }
        else{
            $line['bold'] = 0;
        }

        $line['clicolor'] = "\033[" . implode(";", $pair) . "m";

        return $line;
    }

    static public function strlen($text){
        $text = preg_replace("/(\033\[\d+(;\d+)?m)/", '', $text);
        return strlen($text);
    }
}
