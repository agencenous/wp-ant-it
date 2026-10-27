<?php

namespace AntIT;

$GLOBALS['ant_it_queens'] = [];
function getQueen(string $prefix){
    if(!isset($GLOBALS['ant_it_queens'][$prefix])){
        $GLOBALS['ant_it_queens'][$prefix] = new Queen($prefix);
    }
    return $GLOBALS['ant_it_queens'][$prefix];
}

class Queen{

    /**
     * The prefix used for SQL tables managed by the Queen class.
     * @var string
     */
    private $prefix;

    /**
     * @var array
     * List of registered SQL files
     */
    private $sql_files = [];

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function sql_table(string $table, string $sql_file)
    {
        global $wpdb;
        $tablename = $wpdb->prefix . $this->prefix . $table;
        $this->register_sql_file($sql_file);
        return $tablename;
    }

    /**
     * Registers an SQL file to be managed by the Queen class.
     * 
     * @param string $file The path to the SQL file to register.
     * @throws \Exception If the SQL file does not exist.
     * @return void
     */
    public function register_sql_file(string $file)
    {
        if(!file_exists($file)) {
            Throw new \Exception("SQL file not found: " . $file);
        }
        $this->sql_files[$file] = $this->check_sql($file);
    }
    
    /**
     * Checks and applies the SQL file if its version has changed.
     *
     * @param string $file The name of the SQL file to check.
     */
    public function check_sql(string $file)
    {
        global $wpdb;
        $sql_path = $file;
        if(!file_exists($sql_path)) {
            Throw new \Exception("SQL file not found: " . $sql_path);
        }
        $sql_infos = get_file_data($sql_path, array('Author' => '-- Author', 'Version' => '-- Version'));
        $option = $this->prefix .'sql_' . basename($sql_path);
        if (!empty($sql_infos['Version'])) {
            $current_db_version = get_site_option($option);
            if ($current_db_version != $sql_infos['Version']) {
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                $sql_content = sprintf(file_get_contents($sql_path), $wpdb->prefix);
                dbDelta($sql_content);
                update_site_option($option, $sql_infos['Version']);
                return $sql_infos['Version'];
            }
            return $current_db_version;
        }
        return null;
    }
}


