<?php

namespace App;
class Model
{
	
	public static $data = false;
	public static $pref = '';
	public function __construct(int $id = null) {
		self::initModelsData();
		if(null !== $id) {
			$class = get_called_class();
			
			$result = $class::findFirst([Model::_getModelsGlobalData()->$class->schema->auto_increment .'= :id', 'bind'=>['id'=>$id]]);
			if($result) {
				foreach($result as $k => $v) {
					$this->$k = $v;
				}
			}
		}
		if(method_exists($this, 'initialize')) {
			$this->initialize();
		}
	}
	
	public function hasMany(string $fieldHere, string $model, string $fieldThere = null) {
		if(null === $fieldThere) {
			$fieldThere = $fieldHere;
		}
		Model::_getModelsGlobalData()
		->{get_called_class()}->many[$model] = array($fieldHere, $fieldThere);
	}
	
	public function hasOne(string $fieldHere, string $model, string $fieldThere = null) {
		if(null === $fieldThere) {
			$fieldThere = $fieldHere;
		}
		Model::_getModelsGlobalData()
		->{get_called_class()}->one[$model] = array($fieldHere, $fieldThere);
	}

	public static function find( $array = null) {
		$class = get_called_class();
		if(is_numeric($array)) {
			$query = "SELECT * FROM ";
			$query .= $class::getSource();
			$query .= ' WHERE '. self::_getModelsGlobalData()->$class->ai.' = '. (int) $array;
		} elseif (is_array($array)) {
			$query = "SELECT ";
			$query .= isset($array['columns']) ? is_array($array['columns']) ? implode(',', $array['columns']) : $array['columns'] : '*';
			$query .= ' FROM '. $class::getSource();
			if(isset($array[0])) {
				$query .= ' WHERE '. $array[0];
			}
			if(isset($array['group'])) {
				$query .= ' GROUP BY '. $array['group'];
			}
			if(isset($array['order'])) {
				$query .= ' ORDER BY '. $array['order'];
			}
			if(isset($array['limit'])) {
				$query .= ' LIMIT '.$array['limit'];
			} elseif(isset($array['page'])) {
				$perpage = isset($array['perpage']) ? $array['perpage'] : 50;
				$offset = $array['page'] > 0 ? ($array['page'] - 1) * $perpage : 0;
				$query .= " LIMIT $offset, $perpage";
			}
			if(isset($array['bind'])) {
				return $class::_query($query, $array['bind']);
			}
		} elseif(is_string($array)) {
			$query = "SELECT * FROM ". $class::getSource() ." WHERE 1 AND $array";
		} elseif(null === $array) {
			$query = "SELECT * FROM ". $class::getSource();

		} else {
			return array();
		}
		return $class::_query($query);
	}
	
	public static function findFirst($array) {
		if(is_numeric($array)) {
			$class= get_called_class();
			return new $class((int) $array);
		}
		$array['limit'] = 1;
		$results = get_called_class()::find($array);
		return isset($results[0]) ? $results[0] : false;
	}
	
	public static function _getModelsGlobalData() {
		$class = get_called_class();
		if(false === self::$data) {
			self::$data = (object) array('prefix' => '');
		}
		if(!property_exists(self::$data, $class)) {
			self::$data->$class = (object) array(
				'many' => array(),
				'one' => array(),
				'source' => get_called_class()::getSource(),
				'schema' => false,
				'ai' => 'id',
			);
		}
		return self::$data;
	}
	
	public static function _setDB(\PDO $pdo){
		Model::_getModelsGlobalData()
		-> connection = $pdo;
		return;
	}
	
	public static function _getDB() {
		return Model::_getModelsGlobalData()->connection ?: false;
	}
	public static function setPrefix($prefix = ''){
		Model::$pref = $prefix;
		return;
	}

	public static function getSchema() {
		$class = get_called_class();
		if(isset(Model::_getModelsGlobalData()->$class->schema)) {
			return Model::_getModelsGlobalData()->$class->schema;
		}
		$schema = array('columns' => array(), 'auto_increment' => false);
		$sql = self::_getDB()->query("SELECT * FROM `information_schema`.`COLUMNS` WHERE TABLE_NAME = '". $class::getSource() ."'");
		if($sql) {
			$result = $sql->fetchAll(\PDO::FETCH_ASSOC);
			foreach($result as $v) {
				$schema['columns'][$v['COLUMN_NAME']] = $v['DATA_TYPE'];
				if(preg_match('/auto_increment/', $v['EXTRA'])) {
					$schema['auto_increment'] = $v['COLUMN_NAME'];
				}
			}
			if(isset($result[0]) && false === $schema['auto_increment']) {
				$schema['auto_increment'] = $result[0]['COLUMN_NAME'];
			}
		}
		if(!isset(Model::_getModelsGlobalData()->$class)) {
			Model::_getModelsGlobalData()->$class = (object) array(
				'many' => array(),
				'one' => array(),
				'source' => $class::getSource(),
				'schema' => (object) $schema,
				'ai' => 'id',
			);
		} else {
			Model::_getModelsGlobalData()->$class->schema = (object) $schema;
		}
		return Model::_getModelsGlobalData()->$class->schema;
	}
	public static function initModelsData() {
		$class = get_called_class();
		if(!isset(Model::_getModelsGlobalData()->$class)) {
			Model::_getModelsGlobalData()->$class = (object) array(
				'many' => array(),
				'one' => array(),
				'source' => $class::getSource(),
				'schema' => self::getSchema(),
				'ai' => 'id',
			);
		}
	}
	
	public static function getAI() {
		return $this->getSchema()->auto_increment;
	}
	
	public static function getSource() {
		return Model::$pref.strtolower(get_called_class());
	}
	public function save() {
		$columns = $this->getSchema()->columns;
		$keys = array_keys($columns);
		if(property_exists($this, $this->getSchema()->auto_increment) && (int) $this->{$this->getSchema()->auto_increment}){
			// update
			$query = array(); 
			
			foreach($keys as $v) {
				$query[] = " $v = ?";
			}
			$query = 'UPDATE '. $this->getSource() .' SET ' . implode(',', $query) .' WHERE '. $this->getSchema()->auto_increment .' = ?';
		
			$use = $this;
			$bind = array_map(function($key) use($use){
				return isset($use->$key) ? $use->$key : null;
			}, $keys);
			$bind[] = $this->{$this->getSchema()->auto_increment};
			$sql = Model::_getDB()->prepare($query);
			if($sql->execute($bind)){
				return Model::_getDB()->lastInsertId();
			}
			else{ 
				return $sql->errorInfo();
				
			}
		} else {
			//insert
			$query = 'INSERT INTO '. $this->getSource() .' ('.implode(',', $keys).') VALUES (?'.str_repeat(',?', count($keys)-1) .')';
			$use = $this;
			$bind = array_map(function($key) use($use){
				return isset($use->$key) ? $use->$key : null;
			}, $keys);
			$sql = Model::_getDB()->prepare($query);
			if($sql->execute($bind)){
				return Model::_getDB()->lastInsertId();
			}
			else{ 
				return $sql->errorInfo();
			}
		}
	}
	public static function _query($query, $bind = array()) {
		$query = self::_getDB()->prepare($query);
		$query->execute($bind);
		return $query->fetchAll(\PDO::FETCH_CLASS, get_called_class());
	}
	public function __get($key) {
		if(isset($this->$key)) {
			return $this->$key;
		}
		$thisclass = get_called_class();
		
		if(!isset(Model::_getModelsGlobalData()->$thisclass->many[$key]) && !isset(Model::_getModelsGlobalData()->$thisclass->one[$key])) {
			return false;
		} else {
			
			$this->$key =  $this->{"get$key"}();
			return $this->$key;
		}
	}
	
	public function __call($func, $args) {
		$thisclass = get_called_class();
		if(preg_match('/get([a-zA-Z0-9_]+)/', $func)) {
			$model = preg_replace('/get([a-zA-Z0-9_]+)/', '$1', $func);
			$data = Model::_getModelsGlobalData();
			if(isset($data->$thisclass)) {
				if(isset($data->$thisclass->many) && isset($data->$thisclass->many[$model])) {
					if(!is_array($args)) {
						return false;
					}
					if(isset($args[0][0])) {
						$args[0][0] .= ' AND ' . $data->$thisclass->many[$model][1] .' = :'.$data->$thisclass->many[$model][1];
					} else {
						$args[0][0] = $data->$thisclass->many[$model][1] .' = :'.$data->$thisclass->many[$model][1];
					}
					if(isset($args[0]['bind'])) {
						$args[0]['bind'][$data->$thisclass->many[$model][1]] = $this->{$data->$thisclass->many[$model][0]};
					} else {
						$args[0]['bind'] = array($data->$thisclass->many[$model][1] => $this->{$data->$thisclass->many[$model][0]});
					}
					return $model::find($args[0]);
					
				} elseif (isset($data->$thisclass->one) && isset($data->$thisclass->one[$model])) {
					
					if(!is_array($args)) {
						return false;
					}
					
					if(isset($args[0][0])) {
						$args[0][0] .= ' AND ' . $data->$thisclass->one[$model][1] .' = :'.$data->$thisclass->one[$model][1];
					} else {
						$args[0][0] = $data->$thisclass->one[$function][1] .' = :'.$data->$thisclass->one[$model][1];
					}
					
					if(isset($args[0]['bind'])) {
						$args[0]['bind'][$data->$thisclass->one[$model][1]] = $this->{$data->$thisclass->one[$model][0]};
					} else {
						$args[0]['bind'] = array($data->$thisclass->one[$model][1] => $this->{$data->$thisclass->one[$model][0]});
					}
					
					return $model::findFirst($args[0]);
					
				}
			}
		} 
	}
}
