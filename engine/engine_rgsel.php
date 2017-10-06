<?php

// Contains base class for SELECT operation on record group

namespace DisEngine;

// Autoload user classes located in ./classes dir
spl_autoload_register(function($class_name){
    require_once 'classes/'.$class_name.'.php';
});

require_once 'engine_config.php';

// Represents a class for handling SELECT queries for multiple tables (DBRecordGroup based).
class DBRGSelector {
    /* Private */
    function __construct($rec_name, $rg_name){
        $this->rec_class = $rec_name;
        $this->rg_class = $rg_name;
    }
    
    private $rec_class; // Record class name
    private $rg_class;  // Record group class name
    
    // Select records using a JOIN query
    // $fields - assoc array with these fields:
    // - ['name'] => <Field's name>
    // - ['table'] => <Table for this field>
    // $from_clause - composed JOIN operation for selected tables given by a child class
    // $where_clause - WHERE filter given by a child class
    protected function select($fields, $from_clause, $where_clause){
        $query = 'SELECT ';
        $tmp_comma = false;
        foreach ($fields as $fieldInfo){
            $query .= ($tmp_comma ? ',':'')."`{$fieldInfo['table']}`.`{$fieldInfo['name']}` AS `{$fieldInfo['table']}_{$fieldInfo['name']}`";
            $tmp_comma = true;
        }
        $query .= " FROM $from_clause $where_clause";
        global $db;
        
        // Executing query
        if ($result = $db->query($query)){
            $out = [];
            $last_id = -1;
            while($assoc = $result->fetch_assoc()){
                $rg_data = [];
                // Check each field in input to get table names
                foreach($fields as $fieldInfo){
                    $table_name = $fieldInfo['table'];
                    $rg_data[$table_name] = [];
                    // Check each field in output to take only for specific table
                    foreach($assoc as $field_name => $value){
                        if (preg_match($field_name, "/^{$table_name}_.*/")){
                            $rg_data[$table_name][] = [
                                'field' => $field_name,
                                'value' => $value
                            ];
                        }
                    }
                }
                // Branching
                $new_id = $assoc['id'];
                if ($new_id != $last_id){
                    // create new
                    $cl_name = $this->class();
                    $new_rg = new $cl_name();   // Pre-loaded
                    if (!$new_rg->fillFromSelRes($rg_data)){
                        // Something went wrong
                        return false;
                    }
                    $out[] = $new_rg;
                } else {
                    // append data
                    $new_rg->appendFromSelRes($rg_data);
                }
                $last_id = $new_id;
            }
            return $out;
        }
        return false;
    }
}

?>