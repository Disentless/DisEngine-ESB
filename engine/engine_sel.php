<?php

// Contains base class for single table select operations.

namespace DisEngine;

// Autoload user classes located in ./classes dir
spl_autoload_register(function($class_name){
    require_once 'classes/'.$class_name.'.php';
});

require_once 'engine_db.php';

// Represents a class for handling SELECT operations on a specific DB table.
class DBSelector {
    /* Private */
    function __construct($tableName, $className){
        $this->table = $tableName;
        $this->class = $className;
    }
    
    private $table; // Table name
    private $class; // Class name
    
    // Call from a child class with composed WHERE clause for selection
    protected function select($whereClause){
        $query = "SELECT * FROM `{$this->table}` ".$whereClause;
        global $db;
        
        // Executing query
        if ($result = $db->query($query)){
            return $result;
        }
        return false;
    }
}

?>