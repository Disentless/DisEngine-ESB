<?php

// Configuration parameters.
$config = [
    'class_dir' => 'classes'
];

// Database
$config['db'] = [
    'host' => 'localhost',
    'user' => '',
    'psw' => '',
    'schema' => ''
];

// Field parameters
$config['defaults'] = [
    'TABLE_FIELD_SEPARATOR' => '_%|%_',     // Separator inside SQL to use
    'MAX_INT' => PHP_MAX_INT,               // Max value for int fields
    'DB_BOOL_TYPE' => 'BOOL',               // Default type for boolean
    
    /* Defaults for DBField class */
    'DEFAULT_CAN_NULL' => false,
    'DEFAULT_CAN_CHANGE' => true,
    'DEFAULT_CHECK_F_VALUE' => null,
    'DEFAULT_INT_TYPE' => 'INT',
    'DEFAULT_STR_TYPE' => 'VARCHAR(45)',
    'DEFAULT_STR_MINLENGTH' => 0,
    'DEFAULT_STR_MAXLENGTH' => PHP_MAX_INT,
    'DEFAULT_STR_PATTERN' => '/^.*$/',
    'DEFAULT_DATETIME_TYPE' => 'DATETIME'
];

return $config;
