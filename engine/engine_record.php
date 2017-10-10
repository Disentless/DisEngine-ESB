<?php

// Contains base class for a table record.

namespace DisEngine;

// A single record in a table.
class DBRecord 
{
    /* Static properties and methods */
    protected static $tableName;    // Table name
    protected static $fields;       // Sample fields (DBRecord)
    
    // Static init
    protected static function _init(string $tableName)
    {
        static::$tableName = $tableName;
        static::$fields = [];
    }
    
    // Adds new field sample to generate fields for instances upon
    // or use in SELECT queries
    protected static function addFieldSample(DBField $field)
    {
        static::$fields[] = $field;
    }
    
    // Returns result of SELECT query
    // Optional: specify a condition
    protected static function select(string $whereClause = '')
    {
        $fields = joinAssoc(static::$fields, 'name', false, '`');
        $table = static::$tableName);
        $where = (strlen($whereClause) != 0) ? " WHERE $whereClause" : '';
        $query = <<<SQL
            SELECT $fields
            FROM `$table`
            $where
        SQL;
        global $db;
        
        // Executing query
        if ($result = $db->query($query)){
            $out = [];
            while($assoc = $result->fetch_assoc()){
                $cl_name = static::class;   // Class name
                $new_record = new $cl_name();
                $new_record->fillData($assoc);
                $out[] = $new_record;
            }
            return $out;
        }
        return false;
    }
    
    // Returns table name
    public static function getTableName()
    {
        return static::$tableName;
    }
    
    // Returns fields (DBField array of sample instances)
    public static function getFields()
    {
        return static::$fields;
    }
        
    /* Separate class instance properties and methods */
    protected $idField; // Primary key
    protected $values;  // Table fields (DBField)
    public $exists;     // This record exists in database
    
    // Constructor
    function __construct()
    {
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
    public function getField(string $name)
    {
        if ($name == 'id') return $this->idField->getValue();
        if (!isset($this->values[$name])) return false;
        return $this->values[$name]->getValue();
    }
    
    // Fill data from assoc array, exists specifies if data was taken from DB
    public function fillData(array $arr, bool $exists = false)
    {
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
    public function update()
    {
        $table = static::$tableName;
        
        if ($this->exists){
            // UPDATE query
            $fields = join2Assoc(
                $this->values, 
                'name', 
                false, 
                '`',
                $this->values, 
                'getValue', 
                true, 
                '',
                '='
            );
            $query = <<<SQL
                UPDATE `{$table}` 
                SET {$fields}
                WHERE `id` = {$this->idField->getValue()};
            SQL;
            $eventType = 'changed';
        } else {
            // INSERT query
            $columns = joinAssoc($this->values, 'name', false, '`');
            $values = joinAssoc($this->values, 'getValue', true, '');
            $query = <<<SQL
                INSERT INTO `{$table}`({$columns})
                    VALUES ({$values})
            SQL;
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
    public function delete()
    {
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
    
    /* To be overridden by child classes */
    // Accepts data from client in any form and calls parent's method 
    // fillData(<data>) where <data> is a proper assoc array
    public function fillInputData(array $input_data)
    {
        $this->fillData($input_data);
    }
}
