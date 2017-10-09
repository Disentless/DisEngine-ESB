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

define("TABLE_FIELD_SEPARATOR", '_%|%_');
define('MAX_INT', PHP_MAX_INT);
define('DEFAULT_CAN_NULL', false);
define('DEFAULT_CAN_CHANGE', true);
define('DEFAULT_CHECK_F_VALUE', null);
define('DEFAULT_INT_TYPE', 'INT');
define('DEFAULT_STR_TYPE', 'VARCHAR(45)');
define('DEFAULT_STR_MINLENGTH', 0);
define('DEFAULT_STR_MAXLENGTH', PHP_MAX_INT);
define('DEFAULT_STR_PATTERN', '/^.*$/');
define('DEFAULT_DATETIME_TYPE', 'DATETIME');

// Including necessary engine files
require_once "engine_es.php";       // Event system
require_once "engine_fields.php";   // Base class for fields
require_once "engine_lib.php";      // Functionality library
require_once "engine_record.php";   // Base class for table records
require_once "engine_rg.php";       // Base class for record groups
