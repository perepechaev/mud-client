<?php

class IOStream
{
	private $windows;

	private $writter;
	private $reader;
	private $pid;

	private $iowindow;
	
	public function addWindow($name, Output $window){
		$this->windows[$name] = $window;
		$this->iowindow[$name] = new IOWindow($name, $this);
	}

	public function send($name, $msg){
		socket_write($this->writter, $name . "." . $msg . "\x1b\x1b");
	}

	public function event($name, $event){
		socket_write($this->writter, "\x01$name" . "." . $event . "\x1b\x1b");
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
		$buffer = '';
		while (true){
			$buffer .= socket_read($this->reader, 1024);

			if (substr($buffer, -2) !== "\x1b\x1b"){
				continue;
			}

			$commands = explode("\x1b\x1b", $buffer);
	

			foreach ($commands as $buffer){

				if (substr($buffer, 0, 1) === "\x01"){
					$name = substr($buffer, 1, strpos($buffer, ".") - 1);	
					$method = substr($buffer, strpos($buffer, '.') + 1);
					$params = explode("|", $method);
					$method = $params[0];
					unset($params[0]);

					call_user_func_array(array($this->windows[$name], $method), $params);
					//$this->windows[$name]->{$method}();
					$buffer = '';
					continue;
				}

				if (empty($buffer)){
					continue;
				}

				
				$name 	= substr($buffer, 0, strpos($buffer, '.'));
				$text 	= substr($buffer, strpos($buffer, '.') + 1);

				if ($name !== 'prompt' && $name !== 'output'){
					ncurses_end();
					echo "NAME";
					var_dump($name);
					echo "BUFFER";
					var_dump($buffer);
					die;
				}

				$this->windows[$name]->add($text);

				$buffer = '';
			}
		}
	}

	public function __destruct(){
		posix_kill($this->pid, SIGUSR1);
		pcntl_wait($status);
	}
}
