<?php

// API part of the engine.
// Handles incoming requests.

// Autoload user classes located in ./classes dir
spl_autoload_register(
    function($class_name) 
    {
        require_once 'classes'.DIRECTORY_SEPARATOR.$class_name.'.php';
    }
);

require_once 'engine_config.php';

// Continue session or start a new one
session_start();

// Anonymous mode (client is not logged-in or connection expired)
$anon = !isset($_SESSION['id']);

/*
Request format:
['timestamp']- (int) Request timestamp on the client side (UNIX)
['data'] - (assoc) Request data (actual data to proccess)
['type'] - (assoc) Request type (used for routing)
*/
$reqArr = $_POST['request'];

// Request type mapping to classes
$classMapping = [
    // Data group affects a class (DBRecord) or a class group (DBRecordGroup)
    '<group1>' => '<class1>',
    '<group2>' => '<class2>'
];

// Routing
$group = $reqArr['type']['group'];      // Data group to affect
$action = $reqArr['type']['action'];    // Action to take

if (!isset($classMapping[$group])){
    // Wrong request
    die;
}

// Choosing which class to work with and initializing it
$className = $classMapping[$group];
$className::init();

// Executing action
switch($action){
    case 'add':
    case 'update':
    case 'delete':
        // Creating class instance
        $cl = new $className();
        // Setting data
        $cl->fillInputData($reqArr['data'], $action != 'add');
        // If exists
        $exs = $cl->exists;
        $requestSuccess = $exs && $cl->delete() || !$exs && $cl->update();
        break;
    case 'select':
        // Accessing manager class
        $className = $classMapping[$group].'Selector';
        $selector = new $className();
        // Getting data
        $resultData = $selector->select($reqArr['data']);
        $requestSuccess = $resultData != false;
        break;
}

// Output
echo json_encode([
    'timestamp' => time(),
    'data' => $resultData ?? null,
    'success' => $requestSuccess,
    'errno' => $errno,
    'error' => $error
]);
