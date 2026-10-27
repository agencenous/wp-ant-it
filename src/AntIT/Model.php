<?php

namespace AntIT;

class Model{
	public $id;

	public $_manager = null;

	/**
	 * Constructor for the Model class.
	 * If a numeric value is provided, it will be treated as an ID and the corresponding record will be fetched from the manager.
	 * If an array is provided, it will be used to populate the model's attributes.
	 * If null is provided, the model will be initialized with default values and create a new item.
	 * 
	 * @param array|int|null $atts Attributes to populate the model with, or an ID to fetch from the manager.
	 */
	function __construct($atts=null){
		$current_classname = get_class($this);
		if($current_classname === 'AntIT\Model'){
			Throw new \Exception("The AntIT\Model should not be instanciated directly. Please extend this class and set the manager.");
		}
		if(empty($this->_manager)){
			Throw new \Exception("Manager not set for Model. Please set the manager before instanciating the model {$current_classname}.");
		}
		if($atts!=null){
			$reps = false;
			if(is_numeric($atts)){
				$reps = $this->_manager->get(['id'=>$atts]);
			}
			if(is_array($reps) && isset($reps[0])){
				// Populate
				foreach($reps[0] as $key=>$value){
					if(!empty($value) && substr($value, 0, 2) == 'a:'){
						$value=unserialize($value);
					}
					$this->$key = $value;
				}
			}
			else{
				$this->fill_with($atts);
			}
		}
		$this->populate();
	}

	/**
	 * Fill the model with the provided attributes.
	 *
	 * @param array $atts Attributes to populate the model with.
	 * @return $this
	 */
	function fill_with(array $atts){
		foreach($this->_manager->attributes as $short=>$key){
			$value = '';
			if(isset($atts[$short])){
				$value = $atts[$short];
			}
			if(isset($atts[$key])){
				$value = $atts[$key];
			}
			$this->$key = $value;
		}
		return $this;
	}

	/**
	 * Populate the model with default values or perform any additional initialization.
	 *
	 * @return $this
	 */
	function populate(){
		return $this;
	}

	/**
	 * Save the current model to the database. If the model has an ID, it will perform an update; otherwise, it will insert a new record.
	 *
	 * @return int|false The ID of the saved model on success, or false on failure.
	 */
	function save(){
		global $wpdb;
		$datas = array();

		foreach($this as $key=>$value){
			if(substr($key, 0, 1)=='_'){
				continue;
			}
			if(is_array($value)){
				$datas[$key]=serialize($value);
			}
			else{
				$datas[$key]=$value;
			}
		}

		if(!empty($this->id) && is_numeric($this->id) && $this->id>0){
			if(false!==$wpdb->update( $this->_manager->get_table_name(), $datas, array('id'=>$this->id))){
				return $this->id;
			}
			error_log("Failed UPDATE {$this->_manager->get_table_name()}\t".json_encode($datas));
		}
		else{
			if($wpdb->insert( $this->_manager->get_table_name(), $datas)){
				return $wpdb->insert_id;
			}
			error_log("Fail INSERT {$this->_manager->get_table_name()}\t".json_encode($datas));

		}
		$wpdb->print_error();
		error_log('Failed SAVE '.$this->_manager->get_table_name().': '.$wpdb->last_error);
		return false;
	}

	/**
	 * Remove the current model from the database.
	 *
	 * @return bool True on success, false on failure.
	 */
	function remove(){
		global $wpdb;
		if(is_numeric($this->id)){
			if(false !== $wpdb->delete( $this->_manager->get_table_name(), array('id'=>$this->id))){
				return true;
			}
		}
		return false;
	}

}
