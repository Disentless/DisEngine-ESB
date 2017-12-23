<?php

// Initialization file.

require_once "engine_lib.php";  // Engine library
loadConfigScript('config.php');

// Database
require_once "engine_db.php";
global $db;
$db = new DisEngine\Database();
$db->setConInfo(
    $config['db']['host'], 
    $config['db']['user'], 
    $config['db']['psw'], 
    $config['db']['schema']
);
$db->connect();

// Other engine files
require_once "engine_es.php";       // Event system
require_once "engine_fields.php";   // Base class for fields
require_once "engine_record.php";   // Base class for table records
require_once "engine_rg.php";       // Base class for record groups
