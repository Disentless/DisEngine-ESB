<?php

// Class for handling DB connections.
// Requires MySQL or compatible database server.

namespace DisEngine;

class Database {
    /* Private */
    
    // Database connection.
    private $mysqli;
    // Connection settings.
    private $host;
    private $user;
    private $psw;
    private $schema;
    
    // Connection info.
    private $init;          // Initialization flag
    private $conLost;       // Connection to the server was lost
    private $transaction;   // Transaction is on the way
    
    // Creates new instance.
    function __construct(){
        $this->init = false;
        $this->conLost = false;
        $this->transaction = false;
    }
    
    // Checks whether connections are possible.
    private function checkCon(){
        if (!$this->init || $this->conLost) return false;
        
        return true;
    }
    
    /*Public methods*/
    
    // Set connection info
    public function setConInfo($host, $user, $psw, $schema){
        $this->host = $host;
        $this->user = $user;
        $this->psw = $psw;
        $this->schema = $schema;
    }
    
    // Try to connect
    public function connect(){
        $this->mysqli = new mysqli($host, $user, $psw, $schema);
        
        if ($this->mysqli->connect_error){
            return false;
        }
        
        // Okay to make requests
        $this->init = true;
        
        return true;
    }
    
    // Open transaction
    public function beginTransaction(){
        if (!$this->checkCon() || $this->transaction) return false;
        
        if (!$this->mysqli->query("START TRANSACTION")){
            $this->conLost = true;  // The only explanation to why this query can fail.
            return false;
        }
        
        $this->transaction = true;
        
        return true;
    }
    
    // Close transaction
    public function finishTransaction(){
        if (!$this->checkCon() || !$this->transaction) return false;
        
        if (!$this->mysqli->query("COMMIT")){
            $this->conLost = true;  // The only explanation to why this query can fail.
            return false;
        }
        
        $this->transaction = false;
        
        return true;
    }
    
    // Rollback changes
    public function rollback(){
        if (!$this->checkCon() || !$this->transaction) return false;
        
        if (!this->mysqli->query("ROLLBACK")){
            $this->conLost = true;  // The only explanation to why this query can fail.
            return false;
        }
        
        $this->transaction = false;
        
        return true;
    }
    
    // Execute query
    public function query($query){
        if (!$this->checkCon()) return false;
        
        if ($result = $this->mysqli->query($query)){
            // Return mysqli_result
            return $result;
        } else {
            return false;
        }
    }
    
    // Execute multi query, outputResuls specifies whether or not result of each statement should be returned after function succeeds
    public function multiQuery($query, $outputResult = false){
        // Start transaction
        $this->beginTransaction();
        
        if ($outputResult){
            // Output array
            $out = [];
        }
        // Execute statements
        if ($this->mysqli->multi_query($query)){
            do {
                if ($result = $this->mysqli->store_result()){
                    if ($outputResult){
                        $out[] = $result;
                    }
                }
            } while ($this->mysqli->next_result());
            
            if ($this->mysqli->more_results()){
                // Some statements failed
                $this->rollback();
                return false;
            } else {
                // All is okay
                $this->finishTransaction();
                return $outputResult ? $out : true;
            }
        } else {
            return false;
        }
        
        return $out;
    }
}
    
?>