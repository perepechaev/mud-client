<?php

$GLOBALS['history']['cursor'] = false;

function command_manager($command){
    global $commands;

    write_log(sprintf( "KEY: 0x%x 0d%d %s", $command, $command, chr($command) ));

    $cache = load_key_binding();

    $func = false;
    if ( isset( $cache[$command]) ){
        $func = $cache[$command];
    }

    if ( isset( $cache[chr($command)] ) ){
        $func = $cache[chr($command)];
    }

    if ($func){
        return call_user_func($func[0], $func[1]);
    }

    return false;
}

function load_key_binding(){
    static $cache, $loaded;
    $old = $cache;

    clearstatcache();

    $filename = __DIR__ . '/../../etc/key-binding.php';
    write_log('Check: ' . filemtime($filename) . ': ' . date('Y-m-d H:i:s', filemtime($filename)));

    if ($loaded && filemtime($filename) !== $loaded){
        $cache = null;
    };

    if ($cache){
        return $cache;
    }

    $loaded = filemtime($filename);
    if (php_check_syntax($filename, $error)){
        $commands = include $filename;
        write_log('Loaded key binding');
    }
    else {
        $commands = array();
        $cache = $old;

        write_log('Syntax error: ' . implode("\n", $error));
    }

    foreach ( $commands as $key => $command){
        $key = iconv( 'UTF-8', 'KOI8-R', $key );
        $command = iconv('UTF-8', 'KOI8-R', $command);

        $function = array();
        $pos = strpos($command, '|');
        if ($pos){
            $function[] = substr($command, 0, $pos);
            $function[] = explode(";", substr($command, $pos + 1));
        }
        else {
            $function[] = $command;
            $function[] = array();
        }

        foreach ( explode(' ', $key ) as $k ){
            $cache[$k] = $function;
        }
    }

    return $cache;
}

function php_check_syntax($file, &$error) {
    exec("php -l $file", $error, $code);
    if( $code == 0 ){
        return true;
    }
    return false;
}

function command_exit(  ){
    return 0;
}

function command_debug(){
    $GLOBALS['debug'] -= 1;
    $GLOBALS['debug'] *= $GLOBALS['debug'];

    echo "DEBUG MODE " . ($GLOBALS['debug'] ? 'ENABLED' : 'DISABLED') . "\r\n";
}

function write_log($info){
    global $iostream;
    if ($GLOBALS['debug'] === 0){
        return;
    }

    $iostream->get('output')->addstr("\033[0;34m" . $info . "\033[0m\n");
}

function history_search($history, $prefix, $direction = -1){
    $cursor = & $GLOBALS['history']['cursor'];
    if ($cursor === false || $cursor < 0){
        $cursor = count($history);
    }

    if ($cursor + $direction > count($history) - 1){
        $cursor = -1; 
    }

    for ($i = $cursor + $direction; $i >= 0 && $i < count($history); $i += $direction){
        if ( substr($history[$i], 0, strlen($prefix)) === $prefix && $history[$i] !== $prefix){
            $cursor = $i;
            return $history[$i];
        }
    }

    for ($i = count($history) - 1 ; $i > max($cursor, 0) && $i < count($history); $i += $direction){
        if ( substr($history[$i], 0, strlen($prefix)) === $prefix && $history[$i] !== $prefix){
            $cursor = $i;
            return $history[$i];
        }
    }
    $cursor += $direction;

    return $prefix;
}


function command_prompt(  ){
    global $socket, $address, $port, $pid, $prompt, $iostream;

    while ( true ){
        $cmd = $prompt->getCommand();

        $iostream->get('prompt')->erase();
        if ( $cmd  === false ){
            break;
        }
        $iostream->get('output')->addstr("\033[1;33m\n$cmd\033[0m");

        $status = @socket_write( $socket, $cmd . "\n" );
        if ( $status === false){

            $text = "Disconnect from remote host {$address}[$port]: " .  socket_strerror( socket_last_error( )) . "\n";
            fwrite( STDOUT, $text);
            posix_kill( $pid, SIGUSR1);
            pcntl_wait($status); //Protect against Zombie children
            exit( 0 );
        }
    }
}

function command_scrollup(){
	global $iostream;
    $iostream->get('output')->scroll(BUFFER_UP);
}

function command_scrolldown(){
	global $iostream;
    $iostream->get('output')->scroll(BUFFER_DOWN);
}

function command_scrollup_one(){
	global $iostream;
    $iostream->get('output')->scroll(-1);
}

function command_scrolldown_one(){
	global $iostream;
    $iostream->get('output')->scroll(+1);
}

function command_scrolloff(){
    global $iostream;
    $iostream->get('output')->scroll(+10000);
}

function command_send($arg){
    global $iostream, $socket;

    $iostream->get('output')->addstr("\033[0;34m" . implode(";", $arg) . "\033[0m");
    foreach ($arg as $cmd){
        $status = @socket_write( $socket, $cmd . "\n" );
    }
}
