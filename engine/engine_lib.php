<?php

// Contains general functions used by the engine.

namespace DisEngine;

// Joins properties of elements in a string separated by commas
// $arr - array of assoc elements
// $prop - property or method name
// $func - use propery or method
// $encase - each element will be encased in front and back with this symbol
// $param - what to send to method call
function joinAssoc(
    array $arr, 
    string $prop, 
    bool $func, 
    string $encase, 
    $param = null
) {
    $res = '';
    $tmp_comma = false;
    if (!$func){
        // Join properties
        foreach($arr as $el){
            $res .= ($tmp_comma ? ',' : '');
            $res .= $encase.$el->{$prop}.$encase;
            $tmp_comma = true;
        }
    } else {
        // Join method results
        foreach($arr as $el){
            $res .= ($tmp_comma ? ',' : '');
            $res .= $encase.$el->{$prop}($param).$encase;
            $tmp_comma = true;
        }
    }
    return $res;
}

// Joins elements in a string separated by commas
// $arr - array of elements
// $encase - special symbol to encase each element with
function joinArr(array $arr, string $encase)
{
    $res = '';
    $tmp_comma = false;
    foreach($arr as $el){
        $res .= ($tmp_comma ? ',' : '');
        $res .= $encase.$el.$encase;
        $tmp_comma = true;
    }
    return $res;
}

// Joins properties from elements in 2 numeric arrays of same length.
// $arr1 - array 1
// $arr2 - array 2
// $prop1 - property in 1st array
// $prop2 - property in 2nd array
// $func_flag1 - treat 1st property as function
// $func_flag2 - treat 2nd property as function
// $enc1 - string to encase 1st properties in the new string
// $enc2 - string to encase 2nd properties in the new string
// $middle - string to put between 2 elements
function join2Assoc(
    array $arr1, 
    string $prop1,
    bool $func_flag1,
    string $enc1,
    array $arr2,
    string $prop2,
    bool $func_flag2,
    string $enc2,
    string $middle
) {
    if (count($arr1) != count($arr2)) return false;
    $amount = count($arr1);
    $res = '';
    $tmp_comma = false;
    for($i = 0; $i < $count; ++$i){
        $part1 = ($func_flag1 ? $arr1[$i]->$prop1() : $arr1[$i][$prop1]);
        $part2 = ($func_flag2 ? $arr2[$i]->$prop2() : $arr2[$i][$prop2]);
        $res .= ($tmp_comma ? ',' : '');
        $res .= $enc1.$part1.$enc1.$middle.$enc2.$part2.$enc;
        $tmp_comma = true;
    }
    return $res;
}
