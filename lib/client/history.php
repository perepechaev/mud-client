<?php

class History
{
	private $history = array();
	private $cursor = false;

	public function add($command){
		$exist = array_search($command, $this->history);
		if ($exist !== false){
			$slice = array_slice($this->history, $exist + 1);
			array_splice($this->history, $exist, count($slice), $slice);
			$this->history[count($this->history) - 1] = $command;
		}
		else {
			$this->history[] = $command;
		}

		$this->cursor = count($this->history);
	}

	public function search($prefix, $direction = -1){
		global $iostream;

		// DEBUG
		// $iostream->get('output')->addstr(print_r($this->history, true));

	    $cursor = $this->cursor;
	    $history = $this->history;

	    if ($cursor === false || $cursor < 0){
	        $cursor = count($history);
	    }

	    if ($cursor + $direction > count($history) - 1){
	        $cursor = -1; 
	    }

	    for ($i = $cursor + $direction; $i >= 0 && $i < count($history); $i += $direction){
	        if ( substr($history[$i], 0, strlen($prefix)) === $prefix && $history[$i] !== $prefix){
	            $this->cursor = $i;
	            return $history[$i];
	        }
	    }

	    for ($i = count($history) - 1 ; $i > max($cursor, 0) && $i < count($history); $i += $direction){
	        if ( substr($history[$i], 0, strlen($prefix)) === $prefix && $history[$i] !== $prefix){
	            $this->cursor = $i;
	            return $history[$i];
	        }
	    }
	    $this->cursor += $direction;

	    return $prefix;
	}

	static public function instance(){
		static $instance;
		if (empty($instance)){
			$instance = new self();
		}
		return $instance;
	}
}