<?php

// Represents base class for a table record.

namespace DisEngine;

require_once 'engine_fields.php';
require_once 'engine_es.php';
require_once 'engine_config.php';
require_once 'engine_lib.php';

// A single record in a table.
class DBRecord {
    /* Static properties and methods */
    protected static $tableName;    // Table name
    protected static $fields;       // Table fields (DBRecord) to act as samples
    
    // Static init
    protected static function _init($tableName){
        static::$tableName = $tableName;
        static::$fields = [];
    }
    
    // Adds new field sample to generate fields for instances upon
    // or use in SELECT queries
    protected static function addFieldSample($field){
        static::$fields[] = $field;
    }
    
    // Returns result of SELECT query
    protected static function select($whereClause){
        $query = 'SELECT '.joinAssoc(static::$fields, 'name', '`').' FROM `'.(static::$tableName)."` WHERE $whereClause";
        global $db;
        
        // Executing query
        if ($result = $db->query($query)){
            $out = [];
            while($assoc = $result->fetch_assoc()){
                $cl_name = static::class;   // Class name
                $new_record = new $cl_name();
                $new_record->fillFromSelRes($assoc);
                $out[] = $new_record;
            }
            return $out;
        }
        return false;
    }
    
    // Returns table name
    public static function getTableName(){
        return static::$tableName;
    }
    
    // Returns fields (DBField array of sample instances)
    public static function getFields(){
        return static::$fields;
    }
        
    /* Separate class instance properties and methods */
    protected $idField;  // Primary key
    protected $values;   // Table fields (DBField)
    
    public $exists; // true - record was created as a result of SELECT query and update will use UPDATE query
                    // false - update will use INSERT query
    
    // Constructor
    function __construct(){
        $this->idField = new NumData('id');
        $this->idField->allowChange(false);
        $this->idField->allowNull(false);
        $this->values = [];
        $this->exists = false;
        
        // Generating fields using samples
        foreach($fields as $field_sample){
            $field = clone $field_sample;
            $this->values[$field->name] = $field;
        }
    }
    
    // *Methods*
        
    // Get field value by field name
    public function getField($name){
        if ($name == 'id') return $this->idField->getValue();
        if (!isset($this->values[$name])) return false;
        return $this->values[$name]->getValue();
    }
    
    // Fill data from assoc array, exists specifies whether or not data was taken from DB
    public function fillData($arr, $exists = false){
        foreach($arr as $field => $value){
            if ($field == 'id'){
                $this->idField->setValue($value);
            } else {
                if (!isset($this->values[$field])){
                    // Field doesn't exist
                    return false;
                }
                if(!$this->values[$field]->setValue($value)){
                    // For some reason value conditions are not met
                    return false;
                }
            }
        }
        $this->exists = $exists;
        return true;
    }
    
    // Push changes to the DB
    public function update(){
        $table = static::$tableName;
        
        if ($this->exists){
            // UPDATE query
            $query = "UPDATE `{$table}` SET ";
            $tmp_comma = false;
            foreach($this->values as $value){
                $query .= ($tmp_comma ? ',' : '')."`{$value->name}`={$value->getValue()}";
                $tmp_comma = true;
            }
            $query .= " WHERE `id` = {$this->idField->getValue()}";
            $eventType = 'changed';
        } else {
            // INSERT query
            $columns = '';
            $tmp_comma = false;
            foreach($this->values as $value){
                $columns .= ($tmp_comma ? ',' : '')."`{$value->name}`";
                $tmp_comma = true;
            }
            
            $values = '';
            $tmp_comma = false;
            foreach($this->values as $value){
                $values .= ($tmp_comma ? ',' : '')."{$value->getValue()}";
                $tmp_comma = true;
            }
            
            $query = "INSERT INTO `{$table}` ({$columns}) VALUES ({$values})";
            $eventType = 'added';
        }
        // Query is ready
        // Making request
        global $db;
        if (!$db->query($query)){
            // Query failed
            return false;
        }
        
        if (!$this->exists) {
            // Setting id
            $this->idField->setValue($db->insert_id);
            $this->exists = true;
        }
        
        // Query is a success
        ServerEngine::raiseEvent(static::$tableName, $eventType);
    }
    
    // Deletes record from DB
    public function delete(){
        $table = static::$tableName;
        // Can't delete non-existent record
        if (!$this->exists) return false;
        
        // Making request
        $query = "DELETE FROM `{$table}` WHERE `id` = {$this->idField->getValue()}";
        global $db;
        if (!$db->query($query)){
            // Something went wrong: foreign key restrictions, non-existent record
            return false;
        }
        
        return true;
    }
    
    // Sets fields based on SELECT query result. $assoc_data should be the result from MYSQLI_RESULT::fetch_assoc()
    public function fillFromSelRes($assoc_data){
        if (!$this->idField->setValue($assoc_data['id'])){
            // No id field or something's wrong
            return false;
        }
        unset($assoc_data['id']);   // To remove extra check from the loop below
        foreach($assoc_data as $field_name => $value){
            if (!isset($this->values[$field_name])){
                // Field doesn't exist - wrong input
                return false;
            }
            if (!$this->values[$field_name]->setValue($value)){
                // Value was not set
                return false;
            }
        }
        $this->exists = true;
        return true;
    }
    
}

?>
