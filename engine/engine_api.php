<?php

/* -----------------------------------------------------------------------------
API script.
Requires all incoming requests to be in specific format.
Should not be modified.
----------------------------------------------------------------------------- */

namespace DisEngine;

// Autoload user classes
// Function is located in engine library
spl_autoload_register('loadUserScript');

try {
    $requestSuccess = false;
    
    require_once 'engine_config.php';

    // Continue session or start a new one
    session_start();
    
    // Getting request and checking format
    $reqArr = getRequestArray();
    if (!array_key_exists('timestamp', $reqArr)) {
        throw new RequestFormatEx('Key \'timestamp\' is missing');
    }
    if (!array_key_exists('info', $reqArr)) {
        throw new RequestFormatEx('Key \'info\' is missing');
    }
    
    // Request type mapping to classes that handle data
    $classMapping = loadConfigScript('class_map');
    
    // Routing to handling class
    $group = $reqArr['info']['group'];      // Data group to affect
    $action = $reqArr['info']['action'];    // Action to take
    if (!array_key_exists($group, $clasMapping)) {
        throw new InputMappingEx("Group '$group' cannot be mapped");
    }
    $className = $classMapping[$group];
    $className::init();
    
    // Executing
    switch ($action) {
        case 'add':
        case 'update':
        case 'delete':
            // Creating a new class instance
            $cl = new $className();
            // Setting data
            $exists = ($action != 'add');
            $cl->fillInputData($reqArr['data'], $exists);
            // Executing action
            if ($action == 'delete') {
                $resultData = $cl->delete();
            } else {
                $resultData = $cl->update();
            }
            break;
        case 'select':
            // Accessing static functions
            $className::setSelectParams($reqArr['params']);
            $resultData = $className::getSelectResult();
            break;
    }
    
    // Request was handled without exceptions
    $requestSuccess = true;
} catch (DisException $e) {
    // Engine exceptions
    error_log("Engine exception. Trace: \n".$e->getTraceAsString());
    $errno = $e->getCode();
    $error = $e->getUserMessage();
} catch (Exception $e) {
    // System exceptions
    error_log("System exception. Trace: \n".$e->getTraceAsString());
    $errno = 999;
    $error = 'Unknown error';
} finally {
    // Output
    echo json_encode([
        'timestamp' => time(),
        'data'      => $resultData,
        'success'   => $requestSuccess,
        'errno'     => $errno,
        'error'     => $error
    ]);
}
