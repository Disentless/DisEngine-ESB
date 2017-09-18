<?php

// Engine EventSystem.

namespace DisEngine;

// Controls server event raising system.
abstract class ServerEngine {
    public static function raiseEvent($className, $eventType){
        $event = new SyncEvent($className.'-'.$eventType);
        $event->fire();
    }
}

?>