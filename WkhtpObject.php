<?php

class WkhtpObject {
    
    public function &__get($name)
	{
        $class = get_class($this);
        $uname = ucfirst($name);
        if (method_exists($class, $m = 'get' . $uname)) {
            $val = $this->$m();
			return $val;
        } elseif (property_exists($class, $name)) {
			return $this->$name;
        } else {
            throw new Apex_Exception("Cannot read a class '$class' property without name.");
        }
	}

	public function __set($name, $value)
	{
        $class = get_class($this);
        $uname = ucfirst($name);
        if (method_exists($class, $m = 'set' . $uname)) {
            $this->$m($value);
        } elseif (property_exists($class, $name)) {
			$this->$name = $value;
        } else {
            throw new Apex_Exception("Cannot read a class '$class' property without name.");
        }
	}
    
    function setVars(array $vars) {
        
        foreach ($vars as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }
}