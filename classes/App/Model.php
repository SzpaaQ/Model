<?php
/**
 * @author Łukasz Szpak ( szpaaaaq@gmail.com )
 * @Copyright 2018 SzpaQ
 * @license ALL RIGHTS RESERVED
 * */

namespace App;
class Model
{
    public static $data = false;
    public static $pref = '';
    public static $errors = array();
    public function __construct(int $id = null) {
        self::initModelsData();
        if(null !== $id) {
            $class = get_called_class();
            $result = $class::findFirst([Model::_getModelsGlobalData()->$class->schema->auto_increment .'= :id', 'bind'=>['id'=>$id]]);
            if($result) {
                foreach($result as $k => $v) {
                    $this->$k = $v;
                }
            } else {
                return false;
            }
        }
        if(method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }
    /**
     * hasMany()
     * */
    public function hasMany(string $fieldHere, string $model, string $fieldThere = null) {
        if(null === $fieldThere) {
            $fieldThere = $fieldHere;
        }
        Model::_getModelsGlobalData()
        ->{get_called_class()}->many[$model] = array($fieldHere, $fieldThere);
    }
    public static function transaction()
    {
        Model::_getModelsGlobalData()->transation = true;
        Model::_getModelsGlobalData()->transation_error = false;
        self::_getDB()->beginTransaction();
    }
    public static function commit()
    {
        Model::_getModelsGlobalData()->transation = false;
        if (Model::_getModelsGlobalData()->transation_error !== false) {
            Model::_getModelsGlobalData()->transation_error = false;
            self::_getDB()->rollback();
            return false;
        } else {
            return (bool) self::_getDB()->commit();
        }
    }
    public function hasOne(string $fieldHere, string $model, string $fieldThere = null) {
        if(null === $fieldThere) {
            $fieldThere = $fieldHere;
        }
        Model::_getModelsGlobalData()
        ->{get_called_class()}->one[$model] = array($fieldHere, $fieldThere);
    }
    public static function getErrors()
    {
        return Model::$errors;
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
    public static function count( $array = null) : int
    {
        $class = get_called_class();
        $query = "SELECT COUNT(*) count FROM ". $class::getSource();

        if(is_numeric($array)) {
            $query .= ' WHERE '. self::_getModelsGlobalData()->$class->ai.' = '. (int) $array;
        } elseif (is_array($array)) {
            if(isset($array[0])) {
                $query .= ' WHERE '. $array[0];
            }
            if(isset($array['group'])) {
                $query .= ' GROUP BY '. $array['group'];
            }
            if(isset($array['order'])) {
                $query .= ' ORDER BY '. $array['order'];
            }
            if(isset($array['bind'])) {
                $result = $class::_query($query, $array['bind']);
                return isset($result[0]) ? (int) $result[0]->count : 0;
            }
        } elseif(is_string($array)) {
            $query .= " WHERE 1 AND $array";
        }
        $result = $class::_query($query);
        return isset($result[0]) ? (int) $result[0]->count : 0;
    }
    public static function findFirst($array) {
        if(is_numeric($array)) {
            $class= get_called_class();
            if (!property_exists(Model::_getModelsGlobalData(), $class)) {
                $tmp = new $class;
            }
            $result = $class::findFirst(
                [
                    Model::_getModelsGlobalData()
                        ->$class
                        ->schema
                        ->auto_increment .'= :id',
                    'bind'=>
                    [
                        'id'=>$array
                    ]
                ]
            );
            return $result ?: false;
        }
        $array['limit'] = 1;
        $results = get_called_class()::find($array);
        return isset($results[0]) ? $results[0] : false;
    }
    public function delete()
    {
        $ai =  $this->getSchema()->auto_increment;
        if($this->$ai) {
            $query = 'DELETE FROM '.$this->getSource().' WHERE '. $ai .' = :'.$ai;
            $query = self::_getDB()->prepare($query);
            return (bool) $query->execute(array($ai => $this->$ai));
        }
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
    public static function _setDB($pdo){
        if (is_array($pdo)) {
            if (isset($pdo['prefix'])) {
                Model::$pref = $pdo['prefix'];
            }
            if (
                !isset($pdo['host']) ||
                !isset($pdo['pass']) ||
                !isset($pdo['name']) ||
                !isset($pdo['user'])
            ) {
                throw new \Exception('If use array as parameter for function _setDB it must contain: host, name, user, pass.');
            } else {
                $charset = isset($pdo['charset']) ? $pdo['charset'] : 'utf8';
                $port = isset($pdo['port']) ? 'port:'.$pdo['port'].';' : '';
                $pdo = new \PDO('mysql:host='.$pdo['host'].';dbname='.$pdo['name'].';'.$port.'charset='.$pdo['charset'], $pdo['user'], $pdo['pass']);
            }
        }
        if ($pdo instanceof \PDO) {
            Model::_getModelsGlobalData()-> connection = $pdo;
        } else {
            Throw new Exception('Something wrong with connection data');
        }
        Model::_getModelsGlobalData()
            -> connection = $pdo;
        Model::_getModelsGlobalData()
            -> database_name = $pdo->query('select database()')->fetchColumn();
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
        $sql = self::_getDB()->query("
            SELECT * FROM `information_schema`.`COLUMNS`
            WHERE TABLE_NAME = '". $class::getSource() ."'
            AND TABLE_SCHEMA = '". Model::_getModelsGlobalData() -> database_name  ."'
        ");
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
        $ex = explode('\\', get_called_class());
        $class = array_pop($ex);
        return Model::$pref.strtolower($class);
    }
    public function save() {
        if (!isset($this->date_add) || !$this->date_add || $this->date_add == '0000-00-00 00:00:00') {
            $this->date_add = date('Y-m-d H:i:s');
        }
        $this->date_update = date('Y-m-d H:i:s');
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
                if($key == 'date_update') {
                    return date('Y-m-d H:i:s');
                }
                if($key == 'date_add') {
                    return isset($use->$key) ? $use->$key : date('Y-m-d H:i:s');
                }
                return isset($use->$key) ? $use->$key : null;
            }, $keys);
            $bind[] = $this->{$this->getSchema()->auto_increment};
            $sql = Model::_getDB()->prepare($query);
            if (
                property_exists(Model::_getModelsGlobalData(), 'transaction') &&
                Model::_getModelsGlobalData()->transation === true &&
                Model::_getModelsGlobalData()->transation_error === true
            ) {
                return false;
            }
            if($sql->execute($bind)){
                return true;
            } else {
                Model::$errors[] = $sql->errorInfo();
                if (property_exists(Model::_getModelsGlobalData(), 'transaction') && Model::_getModelsGlobalData()->transation === true)
                {
                    Model::_getModelsGlobalData()->transation_error = true;
                }
                Model::_getModelsGlobalData()->transation_error = true;

                return $sql->errorInfo();
            }
        } else {
            //insert
            $query = 'INSERT INTO '. $this->getSource() .' ('.implode(',', $keys).') VALUES (?'.str_repeat(',?', count($keys)-1) .')';
            $use = $this;
            $bind = array_map(function($key) use($use){
                if($key == 'date_add') {
                    return isset($use->$key) ? $use->$key : date('Y-m-d H:i:s');
                }
                if($key == 'date_update') {
                    return isset($use->$key) ? $use->$key : date('Y-m-d H:i:s');
                }
                return isset($use->$key) ? $use->$key : null;
            }, $keys);
            $sql = Model::_getDB()->prepare($query);
            if (
                property_exists(Model::_getModelsGlobalData(), 'transaction') &&
                Model::_getModelsGlobalData()->transation === true &&
                Model::_getModelsGlobalData()->transation_error === true
            ) {
                return false;
            }
            if($sql->execute($bind)){
                $this->{$this->getSchema()->auto_increment} = Model::_getDB()->lastInsertId();
                return $this->{$this->getSchema()->auto_increment};
            }
            else{
                Model::$errors[] = $sql->errorInfo();
                Model::_getModelsGlobalData()->transation_error = true;
                return false;
            }
        }
    }
    public static function _query($query, $bind = array()) {
        $rebind = array();
        foreach ($bind as $k => $v){
            if(!preg_match('/:/', $k)) {
                $k = ':'.$k;
            }
            if (is_array($v)) {
                continue;
            }
            $rebind[$k] = $v;
        }
        $bind = $rebind;
        $query = self::_getDB()->prepare($query);


        $query->execute($bind);
        return $query->fetchAll(\PDO::FETCH_CLASS, get_called_class());
    }
    public function __get($key) {
        if(property_exists($this, $key)) {
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
    /** Twig engine template fix
     * since Twig check if property exists in class we ned to retur true if model has many or one related model
     * */
    public function __isset($key) : bool {
        if(isset($this->$key)) {
            return true;
        }
        $thisclass = get_called_class();
        if(!isset(Model::_getModelsGlobalData()->$thisclass->many[$key]) && !isset(Model::_getModelsGlobalData()->$thisclass->one[$key])) {
            return false;
        } else {
            return true;
        }
        return false;
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
                        $args[0][0] = $data->$thisclass->one[$model][1] .' = :'.$data->$thisclass->one[$model][1];
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
    /**
     * isExists
     * @description check if row exists by auto_increment column or array
     * @param int $id auto increment numeric value or array (same as findfirst)
     * @return bool
     * */
    public static function isExists($array) : bool
    {
        return (bool) self::findFirst($array);
    }
}
