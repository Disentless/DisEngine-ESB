<?php

// Initialization file.

require_once "engine_db.php";
if (!($config = include "config.php")){
    $config = require "config.default.php";
}

// Database
global $db;
$db = new DisEngine\Database();
$db->setConInfo(
    $config['db']['host'], 
    $config['db']['user'], 
    $config['db']['psw'], 
    $config['db']['schema']
);
if (!$db->connect()){
    // DB connection failed
    error_log('Failed to connect to database');
    die;
}

// Including necessary engine files
require_once "engine_es.php";       // Event system
require_once "engine_fields.php";   // Base class for fields
require_once "engine_lib.php";      // Functionality library
require_once "engine_record.php";   // Base class for table records
require_once "engine_rg.php";       // Base class for record groups
