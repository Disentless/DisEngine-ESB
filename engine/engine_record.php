<?php

// Represents base class for a table record.

namespace DisEngine;

require_once 'engine_fields.php';
require_once 'engine_es.php';
require_once 'engine_config.php';

// A single record in a table.
class DBRecord {
    // Properties.
    private $tableName;   // Table name
    private $idField;       // primary key
    public $fields;        // Table fields (DBField)
    public $exists;         // if true - record was created as a result of SELECT query and update will use UPDATE query, false - update will use INSERT query
    
    // Constructor
    function __construct($tableName){
        $this->tableName = $tableName;
        
        $this->idField = new NumData('id');
        $this->idField->allowChange(false);
        $this->idField->allowNull(false);
        
        $this->fields = [];
        $this->exists = false;
    }
    
    // *Methods*
    
    // Fill data from assoc array, exists specifies whether or not data was taken from DB
    public function fillData($arr, $exists = false){
        foreach($arr as $field=>$value){
            if ($field == 'id'){
                $this->idField->setValue($value);
            } else {
                if (!isset($this->fields[$field])){
                    // Field doesn't exist
                    return false;
                }
                if(!$this->fields[$field]->setValue($value)){
                    // For some reason value conditions are not met
                    return false;
                }
            }
        }
        $this->exists = $exists;
        return true;
    }
    
    // Adds new field (DBField)
    public function addField($field){
        if (!isset($field->name)) return false;
        
        $fields[$field->name] = $field;
    }
    
    // Push changes to the DB
    public function update(){
        if ($this->exists){
            // UPDATE query
            $query = "UPDATE `{$this->tableName}` SET ";
            $tmp_comma = false;
            foreach($fields as $field){
                $query .= ($tmp_comma ? ',' : '')."`{$field->name}`={$field->getValue()}";
                $tmp_comma = true;
            }
            $query .= " WHERE `id` = {$this->idField->getValue()}";
            $eventType = 'changed';
        } else {
            // INSERT query
            $columns = '';
            $tmp_comma = false;
            foreach($fields as $field){
                $columns .= ($tmp_comma ? ',' : '')."`{$field->name}`";
                $tmp_comma = true;
            }
            
            $values = '';
            $tmp_comma = false;
            foreach($fields as $field){
                $values .= ($tmp_comma ? ',' : '')."{$field->getValue()}";
                $tmp_comma = true;
            }
            
            $query = "INSERT INTO `{$this->tableName}` ({$columns}) VALUES ({$values})";
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
        ServerEngine::raiseEvent($this->tableName, $eventType);
    }
    
    // Deletes record from DB
    public function delete(){
        // Can't delete non-existent record
        if (!$this->exists) return false;
        
        // Making request
        $query = "DELETE FROM `{$this->tableName}` WHERE `id` = {$this->idField->getValue()}";
        global $db;
        if (!$db->query($query)){
            // Something went wrong: foreign key restrictions, non-existent record
            return false;
        }
        
        return true;
    }
}

?>