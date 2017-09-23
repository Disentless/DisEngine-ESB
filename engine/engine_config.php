<?php

// Configuration file for the engine.

require_once "engine_db.php";
if (!($config = include "config.php")){
    $config = require "config.default.php";
}

// Database
global $db;
$db = new \DisEngine\Database();
$db->setConInfo($config['db']['host'], $config['db']['user'], $config['db']['psw'], $config['db']['schema']);
if (!$db->connect()){
    // DB connection failed
    error_log('Failed to connect to database');
    die;
}

?>