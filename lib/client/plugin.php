<?php

interface IPlugin
{
    public function prepare($text);
    public function run();
    public function execute(&$text);
}

class PluginManager
{
    private $plugins = array();

    private function __construct(){
    }

    public function register(IPlugin $plugin){
        $this->plugins[] = $plugin;
    }

    public function prepare($text){
        foreach ($this->plugins as $plugin){
            $plugin->prepare($text);
        }
    }

    public function run(){
        foreach ($this->plugins as $plugin){
            $plugin->run();
        }
    }

    public function execute(&$text){
        foreach ($this->plugins as $plugin){
            $plugin->execute($text);
        }
    }

    public function reload(){
        $this->plugins = include __DIR__ . '/../../etc/plugins.php';
        $this->run();
    }

    static public function instance(){
        static $instance;
        if (empty($instance)){
            $instance = new self();
            $instance->reload();
        }
        return $instance;
    }
}
