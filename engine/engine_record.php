<?php

// Contains base class for a table record.

namespace DisEngine;

// A single record in a table.
class DBRecord 
{
    /*
    ----------------------------------------------------------------------------
    Static properties and methods 
    ----------------------------------------------------------------------------
    */
    protected static $tableName;    // Table name
    protected static $fields;       // Sample fields (DBRecord)
    private static $whereClause;    // WHERE clause generated by setSelectParams
    
    // Static init with table name
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
        $query = "
            SELECT $fields
            FROM `$table`
            $where
        ";
        global $db;
        
        // Executing query
        if ($result = $db->query($query)) {
            $out = [];
            while($assoc = $result->fetch_assoc()) {
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
    
    // Runs SELECT and returns the result of it
    public static function getSelectResult()
    {
        return self::select(static::$whereClause);
    }
    
    /* To be overridden by child classes */
    // Prepares WHERE clause for select operation
    abstract public static function setSelectParams();
    
    /*
    ----------------------------------------------------------------------------
    Separate class instance properties and methods
    ----------------------------------------------------------------------------
    */
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
        foreach (static::$fields as $field_sample) {
            $field = clone $field_sample;
            $this->values[$field->name] = $field;
        }
    }
    
    // Get field value by field name
    public function getField(string $name)
    {
        if ($name == 'id') return $this->idField->getValue();
        if (!isset($this->values[$name])) {
            throw new MissingPropertyEx($name);
        }
        return $this->values[$name]->getValue();
    }
    
    // Fill data from assoc array, exists specifies if data was taken from DB
    public function fillData(array $arr, bool $exists = false)
    {
        foreach ($arr as $field => $value) {
            if ($field == 'id') {
                $this->idField->setValue($value);
            } else {
                if (!isset($this->values[$field])) {
                    // Field doesn't exist
                    throw new MissingPropertyEx($field);
                }
                $this->values[$field]->setValue($value);
            }
        }
        $this->exists = $exists;
        return true;
    }
    
    // Push changes to the DB
    public function update()
    {
        $table = static::$tableName;
        
        if ($this->exists) {
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
            $query = "
                UPDATE `{$table}` 
                SET {$fields}
                WHERE `id` = {$this->idField->getValueRaw()};
            ";
            $eventType = 'changed';
        } else {
            // INSERT query
            $columns = joinAssoc($this->values, 'name', false, '`');
            $values = joinAssoc($this->values, 'getValue', true, '');
            $query = "
                INSERT INTO `{$table}`({$columns})
                    VALUES ({$values})
            ";
            $eventType = 'added';
        }
        // Query is ready
        // Making request
        global $db;
        $db->query($query);
        
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
        $query = "
            DELETE FROM `{$table}` 
            WHERE `id` = {$this->idField->getValue()}
        ";
        global $db;
        $db->query($query);
        
        return true;
    }
    
    /* To be overridden by child classes [optional] */
    // Accepts data from client in any form and calls parent's method 
    // fillData(<data>) where <data> is a proper assoc array
    // If $input_data structure does not need to be changed
    // this method is not required to be overridden
    public function fillInputData(array $input_data)
    {
        $this->fillData($input_data);
    }
}
