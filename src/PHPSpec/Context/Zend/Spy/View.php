<?php

namespace PHPSpec\Context\Zend\Spy;

class View implements Subject
{
    protected $_observers = array();
    
    public function render($name) {
        $this->notify(array(
            'method' => 'render',
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
}
