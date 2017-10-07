<?php

// Contains base class for handling complex data that spans to several tables.

namespace DisEngine;

// Autoload user classes located in ./classes dir
spl_autoload_register(function($class_name){
    require_once 'classes/'.$class_name.'.php';
});

require_once 'engine_config.php';

// Represents a class for handling data with multiple values for a field.
class DBRecordGroup {
    // Create new instance
    // $main_name - the name of the base class for this group
    function __construct($main_name){
        $this->data = [];
        $this->rec_name = $main_name;
        $this->cat_classes = [];
    }
    
    /* Private/protected */
    
    protected $data;        // Class instances by categories. Categories represents multiple value fields.
    protected $main;        // Main record for this group
    protected $rec_name;    // Main record name
    protected $cat_classes; // Class name for each category.
    
    // Add category.
    // $info should contain:
    // - ['table'] => <Table's name>
    // - ['class'] => <Class' name>
    // - ['dep_field'] => <Dependency field (foreign key)>
    protected function addCat($cat, $info){
        $cat_classes[$cat] = $info;
    }
    
    // Add to category
    protected function addInCat($cat, $cl){
        if (!isset($this->data[$cat])){
            return false;
        }
        $this->data[$cat][] = $cl;
    }
    
    // Return 2 table join
    private function joinTable($firstJOIN, $table, $fk){
        $mainTable = $this->main->getTableName();
        return "($firstJOIN INNER JOIN `$table` ON `$mainTable`.`id` = `$table`.`$fk`)";
    }
    
    /* Public */
    // Returns JOIN operaion on all tables to later insert after FROM statement
    public function getJOIN(){
        $res = "`{$this->main-getTableName()}`";
        foreach($cat_classes as $cat => $info){
            $res = joinTable($res, $info['table'], $info['dep_field']);
        }
        return $res;
    }
        
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
        foreach($this->data as $cat => $arr){
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
    
    /* To be overridden by child classes */
    
    // Fills data from SELECT query result. 
    // $assoc_data should contain a pair: ['main'] => <assoc_array>, where assoc_array is the result from MYSLQI_RESULT::fetch_assoc().
    // Fo each caterogy $assoc_data should contain a pair: ['<category>'] => <array of assoc>
    public function fillFromSelRes($sel_data){
        $main_class_name = $this->rec_name;
        $this->main = new $main_class_name();
        $table_name = $this->main->getTableName();
        if (!isset($sel_data['main']) || !$this->main->fillFromSelRes($sel_data['main'])){
            return false;
        }
        $this->exists = true;
        
        // Clear all data
        $this->data = [];
        // Unsetting used data to reduce number of calls in the loop.
        unset($sel_data['main']);
        // Looping through categories
        foreach($sel_data as $cat => $arr){
            $cl_name = $this->cat_classes[$cat]['class'];
            foreach($arr as $assoc_data){
                $cl = new $cl_name();   // Preloaded user class
                $cl->fillFromSelRes($assoc_data);
                $this->addInCat($cat, $cl);
            }
        }
    }
}

?>