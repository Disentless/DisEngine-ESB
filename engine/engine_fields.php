<?php
/* 
--------------------------------------------------------------------------------
    Defines the base class that represents a single DB field
    and different data type classes.
--------------------------------------------------------------------------------
*/

namespace DisEngine;

/* 
--------------------------------------------------------------------------------
    Base class for representing different types of data.
--------------------------------------------------------------------------------
*/
class DBField 
{
    function __construct(string $name)
    {
        $this->name = $name;
        $this->canChange = $config['defaults']['DEFAULT_CAN_CHANGE'];
        $this->canNull = $config['defaults']['DEFAULT_CAN_NULL'];
        $this->checkF = $config['defaults']['DEFAULT_CHECK_F_VALUE'];
    }
    // Parameters.
    public $name;  // Field name (string)
    public $type;  // Field type (string)
    
    //
    protected $value;     // Field value
    protected $checkF;    // Custom check function (defined separately)
    
    // Allowed operations.
    protected $canChange; // (bool) If value can change
    protected $canNull;   // (bool) If value can be NULL
    
    // Flags
    protected $initFlag;  // (bool) If value was assigned
    
    // Sets external function as a custom check to 
    // run on the value before assigning.
    public function setCustomCheck(string $val)
    {
        $this->checkF = $val;
    }
    
    // Set $canChange property.
    public function allowChange(bool $val)
    {
        $this->canChange = $val;
    }
    
    // Set $canNull property.
    public function allowNull(bool $val)
    {
        $this->canNull = $val;
    }
    
    // Set value 
    public function setValue($val)
    {
        $cant_change = !$this->canChange && $this->initFlag;
        if ($cant_change) {
            $error_msg = "Field '{$this->name}' value cannot be changed";
            throw new NotChangeableEx($error_msg);
        }
        $cant_null = !$this->canNull && !isset($val);
        if ($cant_null) {
            $error_msg = "Field '{$this->name}' cannot be set to NULL";
            throw new NotNullEx($error_msg);
        }
        if (isset($val) && isset($this->checkF)) {
            $checkFName = $this->checkF;
            if (!$checkFName($val)) {
                $error_msg = "Custom check for field '{$this->name}' failed";
                throw new CustomCheckEx($error_msg);
            }
        }
        // Value is correct
        $this->value = $val;
        $this->initFlag = true;
    }
    
    // Return string representation of value
    public function getValue()
    {
        if (!$this->initFlag) {
            throw new NotInializedEx($this->name);
        }
        return $this->value;
    }
}

/* 
--------------------------------------------------------------------------------
    Decimal field (INT)
--------------------------------------------------------------------------------
*/
class NumData extends DBField 
{
    function __construct(string $name, string $type)
    {
        parent::__construct($name);
        
        $this->type = $type ?? $config['defaults']['DEFAULT_INT_TYPE'];
        $this->min = -$config['defaults']['MAX_INT'];
        $this->max = $config['defaults']['MAX_INT'];
    }
    
    // Type specific properties.
    private $min;       // Minimum value
    private $max;       // Maximum value
    
    // Set value after running checks
    public function setValue($val)
    {
        try {
            if (isset($val)
                && ($val < $this->min 
                    || $val > $this->max)
            ) {
                throw new OutOfRangeEx($val, $this->min, $this->max);
            }
            return parent::setValue($val);
        } catch (DisException $e) {
            throw new InvalidArgumentEx($val, $e);
        }
    }
    
    // Set range
    public function setRange(int $min, int $max)
    {
        $this->min = $min;
        $this->max = $max;
    }
}

/* 
-------------------------------------------------------------------------------
    String field (VARCHAR/TEXT)
-------------------------------------------------------------------------------
*/
class StrData extends DBField 
{
    function __construct(string $name, string $type)
    {
        parent::__construct($name);
        
        $this->type = $type ?? $config['defaults']['DEFAULT_STR_TYPE'];
        $this->minLength = $config['defaults']['DEFAULT_STR_MINLENGTH'];
        $this->maxLength = $config['defaults']['DEFAULT_STR_MAXLENGTH'];
        $this->pattern = null;
    }
    
    // Type specific properties.
    private $minLength;     // Minimum length (0 - ignored)
    private $maxLength;     // Maximum length (0 - ignored)
    private $pattern;       // Specific pattern to match
    
    // Set value after running checks
    public function setValue($val)
    {
        if (isset($val)) {
            $len = mb_strlen($val);
            if (
                $len < $minLength 
                || $len > $maxlength 
                || !preg_match($pattern, $val)
            ) {
                // Check failed
                return false;
            }
        }
        return parent::setValue($val);
    }
    
    // Methods for setting restrictions.
    public function setLengthRange(int $min, int $max)
    {
        $this->minLength = $min;
        $this->maxLength = $max;
    }
    
    // Sets pattern to match
    public function setPattern(string $pattern)
    {
        $this->pattern = $pattern;
    }
    
    // String representation
    public function getValue()
    {
        $strSafeFormat = preg_replace("/'/", "\'", $this->value);
        $strSafeFormat = preg_replace("/%/", "\%", $strSafeFormat);
        $strSafeFormat = preg_replace("/_/", "\_", $strSafeFormat);
        return "'$strSafeFormat'";
    }
}

/* 
--------------------------------------------------------------------------------
    DateTime field. Keeps DateTime in its string representation.
--------------------------------------------------------------------------------
*/
class DateTimeData extends DBField 
{
    function __construct(string $name, string $type)
    {
        parent::__construct($name);
        
        $this->$type = $type ?? $config['defaults']['DEFAULT_DATETIME_TYPE'];
        $this->low = $config['defaults']['DEFAULT_DATETIME_LOW'];
        $this->high = $config['defaults']['DEFAULT_DATETIME_HIGH'];
    }
    
    // Datetime restrictions
    private $low;   // Low limit
    private $high;  // High limit
    
    // Set value
    public function setValue($val)
    {
        if (isset($val)) {
            $low_timestamp = strtotime($low);
            $high_timestamp = strtotime($high);
            $val_timestamp = strtotime($val);
            if ($val_timestamp < $low_timestamp 
                || $val_timestamp > $high_timestamp
            ) {
                return false;
            }
        }
        return parent::setValue($val);
    }
    
    // Methods for setting restrictions.
    public function setRange(int $low, int $high)
    {
        $this->low = $low;
        $this->high = $high;
    }
    
    // String representation
    public function getValue()
    {
        return "'{$this->value}'";
    }
}

/* 
--------------------------------------------------------------------------------
    Boolean field
--------------------------------------------------------------------------------
*/
class BoolData extends DBField
{
    function __construct(string $name)
    {
        parent::__construct($name);
        
        $this->$type = $config['defaults']['DB_BOOL_TYPE'];
    }
}
