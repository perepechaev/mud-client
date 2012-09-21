<?php

$GLOBALS['history']['cursor'] = false;

function command_manager($command){
    global $commands;
    static $cache;
    if ( !$cache ){
        foreach ( $commands as $key => $function ){
            $key = iconv( 'UTF-8', 'KOI8-R', $key );
            foreach ( str_split( $key ) as $k ){
                $cache[$k] = $function;
            }
        }
    }

    write_log(sprintf( "KEY: %x", ( int ) ord( $command ) ));

    if ( isset( $cache[$command] ) ){
        $cache[$command]();
        return true;
    }
    return false;
}

function command_exit(  ){
    global $socket, $address, $port, $pid;

    posix_kill( $pid, SIGUSR1);
    pcntl_wait($status); //Protect against Zombie children
    ncurses_end();

    exit;
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
