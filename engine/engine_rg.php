<?php

// Contains base class for handling complex data that spans to several tables.

namespace DisEngine;

// Represents a class for handling data with multiple values for a field.
// Works with 1:n relations.
class DBRecordGroup 
{
    /* Static properties and methods */
    protected static $main_class;    // Main class name
    protected static $sub_classes;   // Dependant classes
    
    // Static init
    protected static function _init(string $mainClass)
    {
        static::$main_class = $mainClass;
        static::$sub_classes = [];
    }
    
    // Add new subclass as assoc array
    // - ['table'] => <Table's name>
    // - ['class'] => <Class' name>
    // - ['fk'] => <Dependency field (foreign key)>
    protected static function addSub(array $info)
    {
        static::$sub_classes[$info['table']] = $info;
    }
    
    // Return a table join with main table
    protected static function joinTable(
        string $firstJOIN, 
        string $table, 
        string $fk
    ) {
        $mt = static::$main_class::getTableName();
        return "($firstJOIN LEFT JOIN `$table` ON `$mt`.`id`=`$table`.`$fk`)";
    }
    
    // Returns the result of SELECT query on all tables with given WHERE clause
    // Result will be an array of class instances or false on fail
    protected static function select(string $whereClause = '')
    {
        // Tables projection
        $mainTable = static::$main_class::getTableName();
        $table_selection = "`$mainTable`";
        foreach(static::$sub_classes as $subcl){
            $table_selection = self::joinTable(
                $table_selection,
                $subcl['table'],
                $subcl['fk']
            );
        }
        $where = (strlen($whereClause) != 0) ? "WHERE $whereClause" : '';
        // Run query to get ID's of main records that satisfy conditions
        $query = <<<SQL
            SELECT DISTINCT `$mainTable`.`id` 
            FROM $table_selection 
            $whereClause
        SQL;
        
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
                    foreach(static::$sub_classes as $table_name => $sub_info) {
                        $cl_name = $sub_info['class'];
                        $fk = $sub_info['fk'];
                        // Executing query to get vector info
                        $query = <<<SQL
                            SELECT * 
                            FROM `$table_name` 
                            WHERE `$fk` = $recID
                        SQL;
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
    function __construct()
    {
        $this->resetSub();
        $main_clname = static::$main_class;
        $this->main = new $main_clname();
    }
    
    // Reset subclasses
    protected function resetSub()
    {
        $this->data = [];
        foreach(static::$sub_classes as $subcl){
            $this->data[$subcl::getTableName()] = [];
        }
    }
    
    // Rewrites current object's main record properties
    public function fillMain(array $data, bool $exists = false)
    {
        return $this->main->fillData($data, true);
    }
    
    // Rewrites child info
    // $data - assoc array formatted as follows:
    // [<sub table name>] - array of assoc arrays with child class properties
    public function fillData(array $data)
    {
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
    public function appendData(array $data)
    {
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
    public function update()
    {
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
    
    // Delete main record from db. 
    // What to do with other records should be decided on DB level (CASCADE)
    // or by overridding this method.
    public function delete()
    {
        if (!$this->main->delete()){
            // Database restrictions on main record (probably RESTRICT)
            return false;
        }
        return true;
    }
    
    // Get main record instance
    public function getMain()
    {
        return $this->main;
    }
    
    // Get child classes by table or all
    public function getChild(string $table = null)
    {
        if (isset($table)){
            return $this->data[$table];
        } else {
            return $this->data;
        }
    }
    
    /* To be overridden by child classes */
    // Accepts data from client in any form and calls parent's methods:
    // fillMain($data, $exists) and fillData($data)
    public function fillInputData(array $input_data)
    {
        $this->fillMain($input_data['main'], false);
        $this->fillData($input_data['sub']);
    }
}
