<?php

namespace AntIT;

$GLOBALS['ant_it_queens'] = [];
function getQueen(string $prefix){
    if(!isset($GLOBALS['ant_it_queens'][$prefix])){
        $GLOBALS['ant_it_queens'][$prefix] = new Queen($prefix);
    }
    return $GLOBALS['ant_it_queens'][$prefix];
}

// Register a new section in site-health in order to list managed SQL files and their versions
add_filter('site_status_tests', function($tests){
    $tests['direct']['ant_it_sql_files'] = [
        'label' => __('Managed SQL Files', 'ant-it'),
        'test' => function() {
            $sql_files = [];
            foreach($GLOBALS['ant_it_queens'] as $queen){
                foreach($queen->get_sql_files() as $file => $info){
                    $sql_files[$file] = $info;
                }
            }
            $result = [
                'label' => __('Managed SQL Files', 'ant-it'),
                'status'=> 'good',
                'badge' => [
                    'label' => __('Features'),
                    'color' => 'blue',
                ],
                'description' => __('All tables are up to date.', 'ant-it'),
                'test' => 'ant_it_sql_files',
            ];
            foreach($sql_files as $file => $info){
                if(isset($info['version']['available_version']) && $info['version']['available_version'] !== $info['version']['current_version']){
                    $result['status'] = 'critical';
                    $result['badge']['color'] = 'red';
                }
            }
            if($result['status'] == 'critical'){
                $result['description'] = __('Some tables are not up to date.', 'ant-it') . $result['description'];
            }
            return $result;
        }
    ];
    return $tests;
});

add_filter( 'debug_information', function($info){
    $info['ant_it_sql_files'] = [
        'label' => __('Managed SQL File', 'ant-it'),
        'fields' => [],
    ];
    foreach($GLOBALS['ant_it_queens'] as $queen){
        foreach($queen->get_sql_files() as $file => $data){
            $info['ant_it_sql_files']['fields'][$file] = [
                'label' => $data['tablename'] ?? basename($file),
                'value' => esc_html($data['version']['current_version'] ?? 'N/A') . ' ('. esc_html($data['version']['available_version'] ?? 'N/A').')',
            ];
        }
    }
    return $info;
});

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
        $this->register_sql_file($sql_file, $tablename);
        return $tablename;
    }

    /**
     * Registers an SQL file to be managed by the Queen class.
     * 
     * @param string $file The path to the SQL file to register.
     * @throws \Exception If the SQL file does not exist.
     * @return void
     */
    public function register_sql_file(string $file, string $tablename)
    {
        if(!file_exists($file)) {
            Throw new \Exception("SQL file not found: " . $file);
        }
        $this->sql_files[$file] = [
            'tablename' => $tablename,
            'version' => $this->check_sql($file, $tablename)
        ];
    }
    
    /**
     * Checks and applies the SQL file if its version has changed.
     *
     * @param string $file The name of the SQL file to check.
     */
    public function check_sql(string $file, string $tablename)
    {
        global $wpdb;
        $sql_path = $file;
        if(!file_exists($sql_path)) {
            Throw new \Exception("SQL file not found: " . $sql_path);
        }
        $option = $this->prefix .'sql_' . basename($sql_path);
        $current_db_version = get_site_option($option);
        $sql_infos = get_file_data($sql_path, array('Author' => '-- Author', 'Version' => '-- Version'));
        $version_info = [
            'current_version' => $current_db_version,
            'available_version' => $sql_infos['Version'] ?? null,
        ];
        if (!empty($sql_infos['Version'])) {
            if ($current_db_version != $sql_infos['Version']) {
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                $sql_content = str_replace(
                    [
                        '%TABLE%',
                    ],
                    [
                        $tablename,
                    ],
                    file_get_contents($sql_path)
                );
                dbDelta($sql_content);
                update_site_option($option, $sql_infos['Version']);
                $version_info['current_version'] = $sql_infos['Version'];
                return $sql_infos['Version'];
            }
        }
        return $version_info;
    }

    public function get_sql_files(): array
    {
        return $this->sql_files;
    }
}


