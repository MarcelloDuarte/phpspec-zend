<?php

namespace PHPSpec\Context\Zend\Spy;

class View implements Subject, \Zend_View_Interface
{
    protected $_observers = array();
    
    public function render($name) {
        $this->notify(array(
            'method' => 'renderView',
            'name' => $name
        ));
    }
    
    public function attach(Observer $observer)
    {
        $this->_observers[] = $observer;
    }
    
    public function notify($event)
    {
        foreach ($this->_observers as $observer) {
            $observer->update($event);
        }
    }
    
    public function __set($viewVariable, $value)
    {
        $this->notify(array(
            'method' => 'assign',
            'viewVariable' => $viewVariable,
            'value' => $value
        ));
    }
    public function getEngine(){}
    public function setScriptPath($path){}
    public function getScriptPaths(){
        return array();
    }
    public function setBasePath($path, $classPrefix = 'Zend_View'){}
    public function addBasePath($path, $classPrefix = 'Zend_View'){}
    public function __isset($key){}
    public function __unset($key){}
    public function assign($spec, $value = null){}
    public function clearVars(){}
}
