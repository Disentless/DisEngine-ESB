<?php

// Describes exceptions used by this engine.

namespace DisEngine;

// Base class to distinguish between engine exceptions and system ones
class DisException extends Exception 
{
    // Returns a message to display on the client's side
    public function getUserMessage()
    {
        return $this->getMessage();
    }
}

/* 
--------------------------------------------------------------------------------
    Function/Method argument exception
--------------------------------------------------------------------------------
*/

// Function argument is missing or has wrong format
class InvalidArgumentEx extends DisException
{
    function __construct($arg = null, Exception $prev = null){
        $msg = "Argument $arg value cannot be accepted";
        parent::__construct($msg, 0, $prev);
    }
}

// Propery cannot accept this value
class InvalidPropertyValueEx extends DisException
{
    
}

// Usage of methods before instance initialization
class NotInitializedEx extends DisException
{
    function __construct($name = null, Exception $prev = null){
        $msg = "Accessing a non-initialized value: '$name'";
        parent::__construct($msg, 2, $prev);
    }
}

// Function doesn't exist (when called by its name)
class FunctionNotExistsEx extends DisException
{
    
}

// Value cannot be changed
class NotChangeableEx extends DisException
{
    
}

// Value cannot be empty
class NotNullEx extends DisException
{
    
}

// Custom check failed
class CustomCheckEx extends DisException
{
    
}

// Value out of range
class OutOfRangeEx extends DisException
{
    function __construct($val, $min, $max, $Exception $prev = null) {
        $msg = "Value $val is out of required range: (min)$min - (max)$max";
        parent::__construct($msg, 7, $prev);
    }
}

// String doesn't match the pattern
class PatternMismatchEx extends DisException
{
    
}

// Property or method doesn't exist
class MissingPropertyEx extends DisException
{
    
}

/* 
--------------------------------------------------------------------------------
    Database exceptions
--------------------------------------------------------------------------------
*/

// Connection to DB is non-existent
class NoConnectionEx extends DisException
{
    
}

// Connection creating error
class ConnectionErrorEx extends DisException
{
    
}

// Attempted to begin transaction when one is open
class ActiveTransactionEx extends DisException
{
    
}

// Attempted to finish or rollback transaction when none is open
class NoTransactionEx extends DisException
{
    
}

// Query syntax error
class QuerySyntaxEx extends DisException
{
    
}

// Query failed to execute
class QueryFailedEx extends DisException
{
    
}

// Multi-query failed to execute completely
class MultiQueryFailedEx extends DisException
{
    
}

/* 
--------------------------------------------------------------------------------
   Input handling exceptions
--------------------------------------------------------------------------------
*/

// Request format is wrong
class RequestFormatEx extends DisException
{
    
}

// Input data is incorrectly formatted
class InputFormatEx extends DisException
{
    
}

// Class update failed
class RecordUpdateEx extends DisException
{
    
}

// Class deletion failed
class RecordDeleteEx extends DisException
{
    
}

// Access to data denied to client
class AccessDeniedEx extends DisException
{
    
}

// Input cannot be mapped
class InputMappingEx extends DisException
{
    
}

// Class doesn't exist
class ClassNotExistsEx extends DisException
{
    
}

// Request could not be completed
class RequestExecutionEx extends DisException
{
    
}
