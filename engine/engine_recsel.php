<?php

// Contains base class for single table select operations.

namespace DisEngine;

// Autoload user classes located in ./classes dir
spl_autoload_register(function($class_name){
    require_once 'classes/'.$class_name.'.php';
});

require_once 'engine_config.php';

// Represents a class for handling SELECT queries on a single DB table (DBRecord based).
class DBSelector {
    /* Private */
    function __construct($tableName, $className){
        $this->table = $tableName;
        $this->class = $className;
    }
    
    private $table; // Table name
    private $class; // Class name
    
    // Call from a child class.
    // $fields - fields to select
    // $whereClause - composed WHERE clause to use. If null - all records will be selected
    protected function select($fields, $whereClause){
        $query = "SELECT ";
        $tmp_comma = false;
        foreach($fields as $f){
            $query .= ($tmp_comma ? ',' : ''). "`{$f->name}`";
            $tmp_comma = true;
        }
        $query .= " FROM `{$this->table}` ".$whereClause;
        global $db;
        
        // Executing query
        if ($result = $db->query($query)){
            $out = [];
            while($assoc = $result->fetch_assoc()){
                $cl_name = $this->class;
                $new_record = new $cl_name();   // Pre-loaded
                $new_record->fillFromSelRes($assoc);
                $out[] = $new_record;
            }
            return $out;
        }
        return false;
    }
}

?>