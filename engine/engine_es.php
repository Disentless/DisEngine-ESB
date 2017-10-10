<?php

// Engine EventSystem.

namespace DisEngine;

// Controls server event raising system. Cannot be inherited or instantiated.
final class ServerEngine 
{
    // Enforcing abstraction of this class
    private function __construct()
    {
        
    }
    
    // Creates and raises new SyncEvent for specific action
    public static function raiseEvent(
        string $className,
        string $eventType
    ) {
        $event = new SyncEvent($className.'-'.$eventType);
        $event->fire();
    }
}
