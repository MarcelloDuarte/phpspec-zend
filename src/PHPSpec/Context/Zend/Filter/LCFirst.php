<?php

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

class PHPSpec_Context_Zend_Filter_LCFirst implements Zend_Filter_Interface
{
     
    /**
     * Applies a similar function like ucfirst, only it maitains the rest of
     * the string in the same case as it was
     *
     * @param string $value String to be filtered
     * 
     * @return string String filtered
     */
    public function filter($value)
    {
        if (!empty($value)) {
            $value[0] = strtolower($value[0]) ;
        }
        return $value;
    }
    
    public static function apply($value)
    {
        $filter = new static;
        return $filter->filter($value);
    }
}