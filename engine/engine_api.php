<?php

// API part of the engine.
// Handles incoming requests.

// Autoload user classes located in ./classes dir
spl_autoload_register(function($class_name){
    require_once 'classes/'.$class_name.'.php';
});

// Continue session.
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
$group = $reqArr['type']['group'];      // Data group to affect (accounts, schedules, lists, etc.)
$action = $reqArr['type']['action'];    // Action to take (add, change, delete, select)

if (!isset($classMapping[$group])){
    // Wrong request
    die;
}

if ($action != 'select'){
    // Creating class instance
    $className = $classMapping[$group];
    $cl = new $className();
    // Setting data
    $cl->fillData($reqArr['data']);
    $cl->exists = ($action == 'delete');
    // Performing action
    $requestSuccess = ($cl->exists && $cl->delete() || !$cl->exists && $cl->update());
} else {
    // Accessing manager class
    $className = $classMapping[$group].'Manager';
    $manager = new $className();
    // Getting data
    $requestSuccess = ($resultData = $manager->select($reqArr['data']));
}

if ($requestSuccess){
    // success
} else {
    // fail
}


?>