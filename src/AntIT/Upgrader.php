<?php
namespace AntIT;

class Upgrader{

	/**
	 * The list of SQL queries associated with each upgrade version.
	 * 
	 * @var array<string, string[]>
	 */
	private $queries = [];

	/**
	 * Add an SQL query to be executed as part of the upgrade process.
	 *
	 * @param string $query The SQL query to add.
	 */
	public function add(string $query): void{
		global $wpdb;
		$this->queries[] = $query;
	}

	/**
	 * Get the list of upgrade queries added for this manager.
	 *
	 * @return array The list of upgrade queries.
	 */
	public function get(): array{
		return $this->queries;
	}

	/**
	 * Execute all the upgrade queries added for this manager.
	 *
	 * @return bool True if all queries executed successfully, false otherwise.
	 */
	public function execute(): bool{
		global $wpdb;
		foreach ($this->queries as $query) {
			if(defined('WP_DEBUG') && WP_DEBUG) {
				error_log("[AntIT] Executing upgrade query: " . $query);
			}
			$wpdb->query($query);
			if ( ! empty($wpdb->last_error) ) {
				if(defined('WP_DEBUG') && WP_DEBUG) {
					error_log("[AntIT] Upgrade query failed with error: " . $wpdb->last_error);
				}
				return false;
			}
		}
		return true;
	}
}