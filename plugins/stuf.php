<?php

class StufPlugin implements IInputPlugin
{
    private $db;

    private $fields;

    public function __construct(){


        $this->initdb();

        $filename = APP_PATH . '/data/stuf.csv';
        $fp = fopen($filename, 'r');
        $stuf = array();

        $fields = array();
        $l = new SQLite3(':memory:');
        while ($row = fgetcsv($fp, 0, ',', '"')){
            $row = array_map('trim', $row);

            $row = array_map(array($l, 'escapeString'), $row);

            if (empty($fields)){
                foreach ($row as $field){
                    if ($field){
                        $fields[] = str_replace(' ', '_', $field);
                    }
                }
                $fields_sql = '`' . implode("` STRING,`", $fields). '` STRING';

                $result = $this->db->query("CREATE TABLE stuf ($fields_sql)");
                if (!$result){
                    global $iostream;
                    $iostream->get('output')->addstr("\033[0;31m" . iconv('UTF-8', 'KOI8-R', $this->db->lastErrorMsg()) . "\033[0m\n");
                    return;
                }

                $value_sql = array_fill(0, count($fields), '?');
                $value_sql = implode(',', $value_sql);

                continue;
            }

            while (count($row) < count($fields)){
                $row[] = '';
            }

            foreach ($row as &$value){
                if (empty($value)){
                    $value = 'NULL';
                }
                else{
                    $value = "'$value'";
                }
            }
            
            $fields_sql = '`' . implode("`,`", $fields). '`';
            $value_sql  = implode(",", $row);
            try {
                $result = $this->db->exec("INSERT INTO stuf ($fields_sql) VALUES ($value_sql)");
            }
            catch (Exception $e){
                global $iostream;
                $iostream->get('output')->addstr('eeeeeee');
            }

            if (!$result){
                global $iostream;
                $iostream->get('output')->addstr("SQLite error: " . $this->db->lastErrorMsg(). "\n");
                $iostream->get('output')->addstr(iconv('UTF-8', 'KOI8-R', $fields_sql) . "\n");
                $iostream->get('output')->addstr(iconv('UTF-8', 'KOI8-R', $value_sql) . "\n");
                return;
            }

        }
    }

    public function command($text){
        
        if (empty($this->db)){
            global $iostream;
            $iostream->get('output')->addstr('DB Not initialized');
            return;
        }
        $output = '';
        $text = trim($text);
        if (strpos($text, iconv('UTF-8', 'KOI8-R', 'запрос')) !== false){
            $text = substr($text, 7);
            $text = iconv("KOI8-R", 'UTF-8', $text);
            $sql = sprintf(
                'SELECT * FROM stuf WHERE %s',
                $text
            );
        }
        else {
            $text = iconv("KOI8-R", 'UTF-8', $text);
            $sql = sprintf(
                'SELECT * FROM stuf WHERE Назв like "%%%1$s%%" OR `К.Наз` like "%%%1$s%%"',
                $text
            );
        }
        $result = $this->db->query($sql); 
        if (empty($result)){
            return iconv('UTF-8', 'KOI8-R', "Ничего не найдено");
        }
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            $output .= "=================\n";
            foreach ($row as $key => $value){
                if ($value){
                    $key = iconv("UTF-8", 'KOI8-R', $key);
                    $value = iconv("UTF-8", "KOI8-R", $value);
                    $key = str_pad($key, 20);
                    $output .= $key . $value . "\n";
                }
            }
        }
        return $output;
    }
    
    private function initdb(){
        $this->db = new SQLite3(':memory:');
    }

    static public function create(){
        static $instance;
        if (empty($instance)){
            $instance = new self();
        }
        return $instance;
    }
}
