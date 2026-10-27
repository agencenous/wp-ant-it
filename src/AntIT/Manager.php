<?php
namespace AntIT;

class Manager{
    /**
     * @var String
     */
	public $prefix;

    /**
     * @var String
     */
	public $table;

	/**
     * @var String
     */
	public $model;

	/**
	 * SQL filepath or query associated with the manager
	 * @var String
	 */
	public $sql;
    
    /**
     * @var array
    */
	public $attributes = [
        'i'=>'id',
    ];
    
    /**
     * @var String
     */
    protected $table_name;

    /**
     * @var array
     */
	protected $cache = [];

	public function __construct(){
		$current_classname = get_class($this);
		if($current_classname === 'AntIT\Manager'){
			Throw new \Exception("The AntIT\Manager should not be instanciated directly. Please extend this class and set the prefix and table.");
		}
		if(empty($this->prefix)){
			Throw new \Exception("Prefix {$this->prefix} not set for Manager. Please set the prefix in the extending class {$current_classname}.");
		}
		if(empty($this->table)){
			Throw new \Exception("Table not set for Manager. Please set the table in the extending class {$current_classname}.");
		}
		if(empty($this->model)){
			Throw new \Exception("Model not set for Manager. Please set the model in the extending class {$current_classname}.");
		}
		if(!class_exists($this->model)){
			Throw new \Exception("Model class {$this->model} does not exist. Please ensure it is defined and autoloaded.");
		}
		if(empty($this->sql)){
			Throw new \Exception("SQL not set for Manager. Please set the SQL in the extending class {$current_classname}.");
		}
		if(!strstr($this->sql, 'CREATE TABLE') && !file_exists($this->sql)){
			Throw new \Exception("SQL is nor a CREATE TABLE query nor a valid file path.");
		}

        require_once __DIR__ . '/Queen.php';
        $queen = getQueen($this->prefix);
        $this->table_name = $queen->sql_table($this->table, $this->sql);
	}

	public function parse_item($item, $key){
		return new $this->model((array)$item);
	}

	public function where_attr($key, $value){
		return " AND {$this->table_name}.`{$key}`='{$value}'";
	}

	public function where($atts){
		$where = " 1 ";
		$atts=wp_parse_args(
			$atts,
			array_fill_keys(
				array_keys($this->attributes),
				''
			)
		);
		if(!empty($atts['id'])){
			$where.=" AND `id`='".abs($atts['id'])."'";
		}
		else{
			foreach ($this->attributes as $short => $attribute) {
				if(!empty($atts[$short])) $where.=$this->where_attr($attribute, $atts[$short]);
				elseif(!empty($atts[$attribute])) $where.=$this->where_attr($attribute, $atts[$attribute]);
			}
		}
		return $where;
	}

	public function get($atts){
		$stat_name = 'get_'.md5(json_encode($atts));
		if (isset($this->cache[$stat_name])) {
			return $this->cache[$stat_name];
		}
		global $wpdb;
		$req = "SELECT * FROM `{$this->table_name}` WHERE ".$this->where($atts);
		if(isset($atts['GROUP_BY'])){
			$req.=" GROUP BY ".$atts['GROUP_BY'];
		}
		if(isset($atts['ORDER_BY'])){
			$req.=" ORDER BY ".$atts['ORDER_BY'];
		}
		if(isset($atts['LIMIT'])){
			$req.=" LIMIT ".$atts['LIMIT'];
		}
		$results = $wpdb->get_results($req);
		foreach ($results as $key => $item) {
			$results[$key] = $this->parse_item($item, $key);
		}
		$this->cache[$stat_name] = $results;
		return $this->cache[$stat_name];
	}

	public function count($atts){
		$stat_name = 'count_'.md5(json_encode($atts));
		if (isset($this->cache[$stat_name])) {
			return $this->cache[$stat_name];
		}
		global $wpdb;
		$req = "SELECT COUNT({$this->table_name}.`id`) as nbr FROM `{$this->table_name}` WHERE ".$this->where($atts);
		$res= $wpdb->get_row($req, ARRAY_A);
		$this->cache[$stat_name] = $res['nbr'];
		return $this->cache[$stat_name];
	}

    public function get_table_name(){
        return $this->table_name;
    }
}