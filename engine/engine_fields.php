<?php

// Defines the base class that represents a single DB field
// and different data type classes.

namespace DisEngine;

// Settings
define('MAX_INT', PHP_MAX_INT);
define('DEFAULT_CAN_NULL', false);
define('DEFAULT_CAN_CHANGE', true);
define('DEFAULT_CHECK_F_VALUE', null);
define('DEFAULT_INT_TYPE', 'INT');
define('DEFAULT_STR_TYPE', 'VARCHAR(45)');
define('DEFAULT_STR_MINLENGTH', 0);
define('DEFAULT_STR_MAXLENGTH', PHP_MAX_INT);
define('DEFAULT_STR_PATTERN', '/^.*$/');
define('DEFAULT_DATETIME_TYPE', 'DATETIME');

// Base class for representing different types of data.
class DBField {
    function __construct($name){
        $this->name = $name;
        $this->canChange = DEFAULT_CAN_CHANGE;
        $this->canNull = DEFAULT_CAN_NULL;
        $this->checkF = DEFAULT_CHECK_F_VALUE;
    }
    // Parameters.
    public $name;  // Field name (string)
    public $type;  // Field type (string)
    
    //
    protected $value;     // Field value
    protected $checkF;    // Custom check function (defined separately). Should return true if value passes the check.
    
    // Allowed operations.
    protected $canChange; // (bool) If true - value can change without any additional checks
    protected $canNull;   // (bool) If true - value can be NULL
    
    // Flags
    protected $initFlag;  // (bool) If true - value was assigned, false - all operations are allowed once
    
    // Sets external function as a custom check run on the value before assigning.
    public function setCustomCheck($val){
        $checkF = $val;
    }
    
    // Set $canChange property.
    public function allowChange($val){
        $this->canChange = $val;
    }
    
    // Set $canNull property.
    public function allowNull($val){
        $this->canNull = $val;
    }
    
    // Set value 
    public function setValue($val){
        if (!$this->canChange && $this->initFlag || !$this->canNull && !isset($val)) return false;
        // Wasn't initialized with value or change is allowed.
        if (isset($val) && isset($this->checkF) && !$this->checkF($val)) {
            // Check failed
            return false;
        }
        // Value is correct
        $this->value = $val;
        $this->initFlag = true;
    }
    
    // Return string representation of value
    public function getValue(){
        return $this->value;
    }
}

// Decimal field (INT)
class NumData extends DBField {
    function __construct($name, $type){
        parent::__construct($name);
        
        $this->type = $type ?? DEFAULT_INT_TYPE;
        $this->min = -MAX_INT;
        $this->max = MAX_INT;
    }
    
    // Type specific properties.
    private $min;       // Minimum value
    private $max;       // Maximum value
    
    // Set value after running checks
    public function setValue($val){
        if (isset($val)){
            if (($val < $this->min || $val > $this->max)) {
                // Check failed
                return false;
            }
        }
        return parent::setValue($val);
    }
    
    // Set range
    public function setRange($min, $max){
        $this->min = $min;
        $this->max = $max;
    }
}

// String field (VARCHAR/TEXT)
class StrData extends DBField {
    function __construct($name, $type){
        parent::__construct($name);
        
        $this->type = $type ?? DEFAULT_STR_TYPE;
        $this->minLength = DEFAULT_STR_MINLENGTH;
        $this->maxLength = DEFAULT_STR_MAXLENGTH;
        $this->pattern = null;
    }
    
    // Type specific properties.
    private $minLength;     // Minimum length (0 - ignored)
    private $maxLength;     // Maximum length (0 - ignored)
    private $pattern;       // Specific pattern to match
    
    // Set value after running checks
    public function setValue($val){
        if (isset($val)){
            $len = mb_strlen($val);
            if ($len < $minLength || $len > $maxlength || !preg_match($pattern, $val)){
                // Check failed
                return false;
            }
        }
        return parent::setValue($val);
    }
    
    // Methods for setting restrictions.
    public function setLengthRange($min, $max){
        $this->minLength = $min;
        $this->maxLength = $max;
    }
    
    // Sets pattern to match
    public function setPattern($pattern){
        $this->pattern = $pattern;
    }
    
    // String representation
    public function getValue(){
        $strSafeFormat = preg_replace("/'/", "\'", $this->value);
        $strSafeFormat = preg_replace("/%/", "\%", $strSafeFormat);
        $strSafeFormat = preg_replace("/_/", "\_", $strSafeFormat);
        return "'$strSafeFormat'";
    }
}

// DateTime field
class DateTimeData extends DBField {
    function __construct($name, $type){
        parent::__construct($name);
        
        $this->$type = $type ?? DEFAULT_DATETIME_TYPE;
        $this->low = DEFAULT_DATETIME_LOW;
        $this->high = DEFAULT_DATETIME_HIGH;
    }
    
    // Datetime restrictions
    private $low;   // Low limit
    private $high;  // High limit
    
    // Set value
    public function setValue($val){
        if (isset($val)){
            $low_timestamp = strtotime($low);
            $high_timestamp = strtotime($high);
            $val_timestamp = strtotime($val);
            if ($val_timestamp < $low_timestamp || $val_timestamp > $high_timestamp){
                return false;
            }
        }
        return parent::setValue($val);
    }
    
    // Methods for setting restrictions.
    public function setRange($low, $high){
        $this->low = $low;
        $this->high = $high;
    }
    
    // String representation
    public function getValue(){
        return "'{$this->value}'";
    }
}

?>