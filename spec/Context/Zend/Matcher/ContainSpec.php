<?php

namespace Spec\PHPSpec\Context\Zend\Matcher;

use PHPSpec\Context\Zend\Matcher\Contain;

class DescribeContain extends \PHPSpec\Context
{
    function itReturnsTrueIfTheRenderedContainAString()
    {
        $rendered = 'Hello, World!';
        $contain = $this->spec(new Contain('Hello'));
        $contain->matches($rendered)->should->beTrue();
    }
    
    function itReturnsFalseIfTheRenderedDoesNotContainAString()
    {
        $rendered = 'Hello, World!';
        $contain = $this->spec(new Contain('Chuck Norris'));
        $contain->matches($rendered)->should->beFalse();
    }
}