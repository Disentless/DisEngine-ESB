<?php

// Contains general functions used by the engine.

namespace DisEngine;

// Joins properties of elements in a string separated by commas
// $arr - array of assoc elements
// $prop - property or method name
// $encase - each element will be encased in front and back with this symbol
// $func - use propery or method
// $param - what to send to method call
function joinAssoc($arr, $prop, $encase, $func = false, $param = null){
    $res = '';
    $tmp_comma = false;
    if (!$func){
        // Join properties
        foreach($arr as $el){
            $res .= ($tmp_comma ? ',' : '').$encase.$el->{$prop}.$encase;
            $tmp_comma = true;
        }
    } else {
        // Join method results
        foreach($arr as $el){
            $res .= ($tmp_comma ? ',' : '').$encase.$el->{$prop}($param).$encase;
            $tmp_comma = true;
        }
    }
    return $res;
}

// Joins elements in a string separated by commas
// $arr - array of elements
// $encase - special symbol to encase each element with
function joinArr($arr, $encase){
    $res = '';
    $tmp_comma = false;
    foreach($arr as $el){
        $res .= ($tmp_comma ? ',' : '').$encase.$el.$encase;
        $tmp_comma = true;
    }
    return $res;
}

?>