<?php

// Describes exceptions used by this engine.

namespace DisEngine;

// Function argument is missing or has wrong format
class InvalidArgumentEx extends Exception
{
    
}

// Propery cannot accept this value
class InvalidPropertyValueEx extends Exception
{
    
}

// Usage of methods before instance initialization
class NotInitializedEx extends Exception
{
    
}

// Function doesn't exist (when called by its name)
class FunctionNotExistsEx extends Exception
{
    
}

// Value cannot be changed
class NotChangeableEx extends Exception
{
    
}

// Value cannot be empty
class NotNullEx extends Exception
{
    
}

// Custom check failed
class CustomCheckEx extends Exception
{
    
}

// Value out of range
class OutOfRangeEx extends Exception
{
    
}

// String doesn't match the pattern
class PatternMismatchEx extends Exception
{
    
}

// Connection to DB is non-existent
class NoConnectionEx extends Exception
{
    
}

// Connection creating error
class ConnectionErrorEx extends Exception
{
    
}

// Attempted to begin transaction when one is open
class ActiveTransactionEx extends Exception
{
    
}

// Attempted to finish or rollback transaction when none is open
class NoTransactionEx extends Exception
{
    
}

// Query syntax error
class QuerySyntaxEx extends Exception
{
    
}

// Query failed to execute
class QueryFailedEx extends Exception
{
    
}

// Multi-query failed to execute completely
class MultiQueryFailedEx extends Exception
{
    
}

// Property or method doesn't exist
class MissingPropertyEx extends Exception
{
    
}

// Input data is incorrectly formatted
class InputFormatEx extends Exception
{
    
}

// Class update failed
class RecordUpdateEx extends Exception
{
    
}

// Class deletion failed
class RecordDeleteEx extends Exception
{
    
}

// Access to data denied to client
class AccessDeniedEx extends Exception
{
    
}

// Input cannot be mapped
class InputMappingEx extends Exception
{
    
}

// Class doesn't exist
class ClassNotExistsEx extends Exception
{
    
}

// Request could not be completed
class RequestExecutionEx extends Exception
{
    
}
