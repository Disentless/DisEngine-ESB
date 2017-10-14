<?php

// API part of the engine.
// Handles incoming requests.

// Autoload user classes located in ./classes dir
spl_autoload_register(
    function($class_name) 
    {
        global $config;
        $file_name = $class_name.'.php';
        $dir = $config['class_dir'].DIRECTORY_SEPARATOR;
        require_once $dir.$file_name;
    }
);

try {
    require_once 'engine_config.php';

    // Continue session or start a new one
    session_start();

    /*
    Request format:
    ['timestamp']- (int) Request timestamp on the client side (UNIX)
    ['data'] - (assoc) Request data (actual data to proccess)
    ['type'] - (assoc) Request type (used for routing)
    */
    $reqArr = $_POST['request'];

    // Request type mapping to classes that handle data
    $classMapping = [
        '<group1>' => '<class1>',
        '<group2>' => '<class2>'
    ];

    // Handling request
    $requestSuccess = false;
    
    // Routing to handling class
    if (!array_key_exists('type', $reqArr)) {
        throw new RequestFormatEx('Key \'type\' is missing');
    }
    if (!array_key_exists('action', $reqArr)) {
        throw new RequestFormatEx('Key \'action\' is missing');
    }
    $group = $reqArr['type']['group'];      // Data group to affect
    $action = $reqArr['type']['action'];    // Action to take
    if (!array_key_exists($group, $clasMapping)) {
        throw new InputMappingEx("Group '$group'' cannot be mapped");
    }
    $className = $classMapping[$group];
    $className::init();

    // Executing action
    switch($action) {
        case 'add':
        case 'update':
        case 'delete':
            // Creating class instance
            $cl = new $className();
            // Setting data
            $exists = $action != 'add';
            $cl->fillInputData($reqArr['data'], $exists);
            if ($action == 'delete') {
                $cl->delete();
            } else {
                $cl->update();
            }
            break;
        case 'select':
            // Accessing manager class
            $className = $classMapping[$group].'Selector';
            $selector = new $className();
            // Getting data
            $resultData = $selector->select($reqArr['data']);
            break;
    }
    
    // Request was handled without exceptions
    $requestSuccess = true;
} catch (DisException $e) {
    // Engine exceptions
    $errno = $e->getCode();
    $error = $e->getMessage();
} catch (Exception $e) {
    // System exceptions
    $errno = 999;
    $error = 'Unknown error';
}

// Output
echo json_encode([
    'timestamp' => time(),
    'data'      => $resultData,
    'success'   => $requestSuccess,
    'errno'     => $errno,
    'error'     => $error
]);
