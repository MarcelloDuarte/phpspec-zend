<?php

namespace PHPSpec\Context\Zend\Spy;

interface Subject
{
    public function attach(Observer $observer);
    public function notify($event);
}