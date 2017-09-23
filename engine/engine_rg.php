<?php

// Contains base class for handling complex data that spans to several tables.

namespace DisEngine;

// Autoload user classes located in ./classes dir
spl_autoload_register(function($class_name){
    require_once 'classes/'.$class_name.'.php';
});

require_once 'engine_config.php';

class DBRecordGroup {
    // Create new instance, supply with array of class names where 1st name is the main class
    // data contains classes
    function __construct($mainRecord){
        $this->main = $mainRecord;
        $this->data = [];
    }
    /* Private */
    
    protected $data;    // Class instances by categories
    protected $main;    // Main record for this group
    
    // Add to category
    protected function addInCat($cat, $cl){
        $this->data[$cat][] = $cl;
    }
    
    /* Public */
    
    // Flush changes to database in a single transaction
    public function update(){
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
    
    // Delete main record from db. What to do with other records should be decided on DB level (preferably CASCADE)
    public function delete(){
        if (!$this->data['main']->delete()){
            // Database restrictions on main record (probably RESTRICT)
            return false;
        }
        return true;
    }
}

?>