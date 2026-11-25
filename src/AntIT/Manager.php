<?php
namespace AntIT;

class Manager{
    /**
	 * The prefix used for the database tables managed by this manager.
	 * Will be added just after the wpdb->prefix when constructing the full table name.
	 * This allows for multiple managers to share the same database with distinct table prefixes.
	 * 
     * @var String
     */
	public $prefix;

    /**
	 * The name of the database table managed by this manager, without the prefix.
	 * 
     * @var String
     */
	public $table;

	/**
	 * The fully qualified class name of the model associated with this manager.
	 * 
     * @var String
     */
	public $model;

	/**
	 * SQL filepath associated with the manager
	 * 
	 * @var String
	 */
	public $sql;
    
    /**
	 * The list of attributes managed by this manager, with short keys mapped to full attribute names.
	 * 
     * @var array
    */
	public $attributes = [
        'i'=>'id',
    ];

	/**
	 * Array of upgrades, key is the version and value is a method of the manager that performs the upgrade.
	 * @var array<string, callable>
	 */
	public $upgrades = [];

	/**
	 * The current version of the database schema managed by this manager.
	 * @var String
	 */
	public $current_version;

	/**
	 * The available version of the database schema managed by this manager.
	 * @var String
	 */
	public $available_version;
    
    /**
	 * The fully qualified name of the database table, including the prefix.
	 *
     * @var String
     */
    protected $table_name;

    /**
	 * The cache for storing retrieved items to avoid redundant database queries.
	 * 
     * @var array
     */
	protected $cache = [];

	/**
	 * The Queen instance associated with this manager.
	 * 
	 * @var \AntIT\Queen
	 */
	private $queen;

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
		if(!file_exists($this->sql)){
			Throw new \Exception("SQL file not found: " . $this->sql);
		}

        require_once __DIR__ . '/Queen.php';
        $this->queen = getQueen($this->prefix);
        $this->table_name = $this->queen->sql_table($this->table, $this->sql);
		$info = $this->get_sql_info();
		if($info){
			$this->current_version = $info['current_version'] ?? null;
			$this->available_version = $info['available_version'] ?? null;
		}
		$this->manage_upgrades();
	}

	/**
	 * Parse a database item into a model instance.
	 * Child classes can override this method to customize how database items are parsed into model instances.
	 *
	 * @param object $item The database item to parse.
	 * @param int $key The key/index of the item in the result set.
	 * @return \AntIT\Model The parsed model instance.
	 */
	public function parse_item(mixed $item, mixed $key): \AntIT\Model {
		return new $this->model((array)$item);
	}

	/**
	 * Generate a SQL WHERE clause for a specific attribute and value.
	 *
	 * @param string $key The attribute key.
	 * @param mixed $value The attribute value.
	 * @return string The SQL WHERE clause fragment.
	 */
	public function where_attr(string $key, string $value) : string{
		return " AND {$this->table_name}.`{$key}`='{$value}'";
	}

	/**
	 * Generate a SQL WHERE clause based on the provided attributes.
	 *
	 * @param array $atts The attributes to generate the WHERE clause for.
	 * @return string The SQL WHERE clause.
	 */
	public function where(array $atts): string{
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

	/**
	 * Retrieve items from the database based on the provided attributes.
	 *
	 * @param array $atts The attributes to filter the items by.
	 * @return array The list of parsed model instances.
	 */
	public function get(array $atts): array{
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

	/**
	 * Count the number of items in the database that match the provided attributes.
	 *
	 * @param array $atts The attributes to filter the items by.
	 * @return int The count of matching items.
	 */
	public function count(array $atts): int{
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

	/**
	 * Get the name of the database table associated with this manager.
	 *
	 * @return string The table name.
	 */
    public function get_table_name(): string{
        return $this->table_name;
    }

	/**
	 * Get the Queen instance associated with this manager.
	 *
	 * @return \AntIT\Queen The Queen instance.
	 */
	public function get_queen(): \AntIT\Queen{
		return $this->queen;
	}

	/**
	 * Get the SQL file information for the current manager's SQL file.
	 *
	 * @return array|null The SQL file information, or null if not found.
	 */
	public function get_sql_info(): ?array{
		$files = $this->queen->get_sql_files();
		if(isset($files[$this->sql])){
			return $files[$this->sql];
		}
		return null;
	}

	/**
	 * Manage the upgrades for the current manager by applying any pending upgrade functions.
	 * This method checks the current version against the available version and executes
	 * the corresponding upgrade callbacks if necessary.
	 */
	private function manage_upgrades(): void{
		global $wpdb;
		foreach ($this->upgrades as $version => $upgrade) {
			$option = $this->queen->get_prefix() .'sql_' . basename($this->sql).'_upgrade_' . $version;
			$applied = get_site_option($option);
			if($applied){
				continue;
			}
			if(defined('WP_DEBUG') && WP_DEBUG) {
				error_log("[AntIT] Upgrade detected in manager: {$this->table} for version: {$version}");
			}
			$callable = [$this, $upgrade];
			if (method_exists($this, $upgrade)) {
				try{
					$result = call_user_func($callable);
					if(defined('WP_DEBUG') && WP_DEBUG) {
						error_log("[AntIT] Upgrade applied successfully in manager: {$this->table} for version: {$version}" . print_r($result, true));
					}
					update_site_option($option, date('Y-m-d H:i:s'));
				}catch(\Exception $e){
					if(defined('WP_DEBUG') && WP_DEBUG) {
						error_log("[AntIT] Upgrade failed in manager: {$this->table} for version: {$version} with error: " . $e->getMessage());
					}
				}
			}
			else{
				if(defined('WP_DEBUG') && WP_DEBUG) {
					error_log("[AntIT] Upgrade function {$upgrade} is not callable in manager: {$this->table} for version: {$version}");
				}
			}
		}
	}
}