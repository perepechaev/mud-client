<?php

$GLOBALS['history']['cursor'] = false;

function command_manager($command){
    global $commands;
    static $cache;
    if ( !$cache ){
        foreach ( $commands as $key => $function ){
            $key = iconv( 'UTF-8', 'KOI8-R', $key );
            foreach ( explode(' ', $key ) as $k ){
                $cache[$k] = $function;
            }
        }
    }

    write_log(sprintf( "KEY: 0x%x 0d%d %s", $command, $command, chr($command) ));

	if ( isset( $cache[$command]) ){
		return $cache[$command]();
	}

    if ( isset( $cache[chr($command)] ) ){
        return $cache[chr($command)]();
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
    $iostream->get('output')->scroll(-38);
}

function command_scrolldown(){
	global $iostream;
    $iostream->get('output')->scroll(+38);
}

function command_scrollup_one(){
	global $iostream;
    $iostream->get('output')->scroll(-1);
}

function command_scrolldown_one(){
	global $iostream;
    $iostream->get('output')->scroll(+1);
}
