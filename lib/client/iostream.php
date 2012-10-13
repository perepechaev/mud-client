<?php

class IOStream
{
    private $windows;

    private $writter;
    private $reader;
    private $pid;

    private $iowindow;

    public function addWindow($name, Window $window){
        $this->windows[$name] = $window;
        $this->iowindow[$name] = new IOWindow($name, $this);
    }

    public function send($name, $msg){
        if (strpos($msg, "\x01\x02") !== false){
            df('input.cross.log', "Control symbol \\x01\\x02 founded\n" . var_export($msg, true));
        }
        $msg = str_replace("|", "\x01\x02", $msg);
        socket_write($this->writter, "\x01\x01$name" . ".add|" . $msg . "\x02\x02");
    }

    public function event($name, $event){
        socket_write($this->writter, "\x01\x01$name" . "." . $event . "\x02\x02");
    }

    public function get($name){
        return $this->iowindow[$name];
    }

    public function run(){
        $socket = socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets);

        if ($socket === false){
            die("Socket error: " . socket_strerror(socket_last_error()));
        }

        $this->writter = $sockets[0];
        $this->reader  = $sockets[1];

        socket_set_block($this->writter);
        socket_set_block($this->reader);

        $pid = pcntl_fork();
        if ($pid == -1){
            die('could not fork');
        }
        if ($pid){
            $this->pid = $pid;
            #socket_accept($this->writter);
            // parent
        }
        else {
            $this->worker();
            exit;
        }
    }

    private function worker(){
        $input = '';
        while (true){
            $input .= socket_read($this->reader, 1024);

            if (substr($input, -2) !== "\x02\x02"){
                continue;
            }

            $input = substr($input, 0, strlen($input)-2);
            $commands = explode("\x02\x02", $input);
            $input = '';

            foreach ($commands as $key => $buffer){

                if (substr($buffer, 0, 2) === "\x01\x01"){
                    $name = substr($buffer, 2, strpos($buffer, ".") - 2);
                    $method = substr($buffer, strpos($buffer, '.') + 1);
                    $params = explode("|", $method);
                    $method = $params[0];
                    unset($params[0]);
                    foreach ($params as &$param){
                        $param = str_replace("\x01\x02", "|", $param);
                    }

                    call_user_func_array(array($this->windows[$name], $method), $params);
                    $buffer = '';
                    continue;
                }

                if (empty($buffer)){
                    df('error', __FILE__ . ":" . __LINE__ . " Empty buffer");
                    continue;
                }

                df('error', __FILE__  . ":" . __LINE__ . "\n" . bin2hex($buffer) . "\n" . $buffer);
            }
        }
    }

    public function __destruct(){
        posix_kill($this->pid, SIGUSR1);
        pcntl_wait($status);
    }
}
