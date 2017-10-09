<?php

// Contains base class for handling complex data that spans to several tables.

namespace DisEngine;

// Autoload user classes located in ./classes dir
spl_autoload_register(function($class_name){
    require_once 'classes/'.$class_name.'.php';
});

require_once 'engine_config.php';
require_once 'engine_lib.php';

define("TABLE_FIELD_SEPARATOR", '_%|%_');   // Used to create alias for table fields

// Represents a class for handling data with multiple values for a field.
// Works with 1:n relations.
class DBRecordGroup {
    /* Static properties and methods */
    static protected $main_class;    // Main class name
    static protected $sub_classes;   // Dependant classes
    
    // Static init
    static protected function _init($mainClass){
        static::$main_class = $mainClass;
        static::$sub_classes = [];
    }
    
    // Add new subclass as assoc array
    // - ['table'] => <Table's name>
    // - ['class'] => <Class' name>
    // - ['fk'] => <Dependency field (foreign key)>
    static protected function addSub($info){
        static::$sub_classes[$info['table']] = $info;
    }
    
    // Return a table join with main table
    static protected function joinTable($firstJOIN, $table, $fk){
        $mainTable = static::$main_class::getTableName();
        return "($firstJOIN LEFT JOIN `$table` ON `$mainTable`.`id` = `$table`.`$fk`)";
    }
    
    // Returns the result of SELECT query on all tables with given WHERE clause
    // Result will be an array of class instances or false on fail
    static protected function select($whereClause){
        // Tables projection
        $mainTable = static::$main_class::getTableName();
        $table_selection = "`$mainTable`";
        foreach(static::$sub_classes as $subcl){
            $table_selection = joinTable($table_selection, $subcl['table'], $subcl['fk']);
        }
        // Run query to get ID's of main records that satisfy conditions
        $query = "SELECT DISTINCT `$mainTable`.`id` FROM $table_selection WHERE $whereClause";
        
        // Executing query
        global $db;
        if ($result = $db->query($query)){
            // List of all IDs to use later
            $mainrec_idlist = $result->fetch_all(MYSQLI_NUM);
            
            // Output array
            $out = [];
            
            // Running though each ID and
            // - taking info from main table
            // - taking info from each child table
            // - adding class instance to output
            foreach($mainrec_idlist as $recID){
                $query = "SELECT * FROM `$mainTable` WHERE `id` = $recID";
                if ($result = $db->query($query)){
                    $main_data = $result->fetch_assoc();
                    
                    // Rounding up each vector table
                    $vectors = [];
                    foreach(static::$sub_classes as $table_name => $sub_info){
                        $cl_name = $sub_info['class'];
                        $fk = $sub_info['fk'];
                        // Executing query to get vector info
                        $query = "SELECT * FROM `$table_name` WHERE `$fk` = $recID";
                        if ($result = $db->query($query)){
                            $list = [];
                            while($list[] = $result->fetch_assoc());
                            $vectors[$table_name] = $list;
                        } else {
                            return false;
                        }
                    }
                    // Creating DBRecordGroup's child instance
                    $main_clname = static::class;
                    $main_cl = new $main_clname();
                    $main_cl->fillMain($main_data, true);
                    $main_cl->fillData($vectors);
                    $out[] = $main_cl;
                } else {
                    // Something went wrong
                    // TO-DO: Make a log system btw (before i forget)
                    return false;
                }
            }
            
            // All is okay
            return $out;
        } else {
            // Something went wrong
            return false;
        }
    }
    
    /* Instance properties and methods */
    protected $data;    // Class instances by tables
    protected $main;    // Main record for this group
    
    // Constructor
    function __construct(){
        $this->resetSub();
        $main_clname = static::$main_class;
        $this->main = new $main_clname();
    }
    
    // Reset subclasses
    protected function resetSub(){
        $this->data = [];
        foreach(static::$sub_classes as $subcl){
            $this->data[$subcl::getTableName()] = [];
        }
    }
    
    // Rewrites current object's main record properties
    protected function fillMain($data, $exists = false){
        $mainTable = static::$main_class::getTableName();
        return $this->main->fillData($data, true);
    }
    
    // Rewrites child info
    // $data - assoc array formatted as follows:
    // [<sub table name>] - array of assoc arrays with child class properties
    protected function fillData($data){
        $this->resetSub();
        foreach($data as $table => $records){
            foreach($records as $rec){
                // Creating new instance
                $cl_name = static::$sub_classes[$table]['class'];
                $cl = new $cl_name();
                $cl->fillData($rec, true);
                // Adding to the list for according table
                $this->data[$table][] = $cl;
            }
        }
    }
    
    // Works the same as fillData but does not reset the object's state
    // or main record properties. Only appends sub classes.
    protected function appendData($data){
        // Sub instances
        foreach($data as $table => $records){
            foreach($records as $rec){
                // Creating new instance
                $cl_name = static::$sub_classes[$table]['class'];
                $cl = new $cl_name();
                $cl->fillData($rec, true);
                // Adding to the list for according table
                $this->data[$table][] = $cl;
            }
        }
    }
    
    /* Public */
    
    // Flush changes to database in a single transaction
    public function update(){
        if (!isset($this->main)) return false;
        // Starting database transation
        global $db;
        $db->startTransaction();
        
        // Updating main record first
        if (!$this->main->update()){
            $db->rollback();
            return false;
        }
        
        // Updating each category
        foreach($this->data as $table => $arr){
            // Each instance
            foreach($arr as $cl){
                if (!$cl->update()){
                    // Update for one instance failed - revert all changes
                    $db->rollback();
                    return false;
                }
            }
        }
        // Commiting changes
        $db->finishTransaction();
        
        return true;
    }
    
    // Delete main record from db. What to do with other records should be decided on DB level (CASCADE)
    // or by overridding this method.
    public function delete(){
        if (!$this->main->delete()){
            // Database restrictions on main record (probably RESTRICT)
            return false;
        }
        return true;
    }
    
    // Get main record instance
    public function getMain(){
        return $this->main;
    }
    
    // Get child classes by table or all
    public function getChild($table = null){
        if (isset($table)){
            return $this->data[$table];
        } else {
            return $this->data;
        }
    }
}

?>