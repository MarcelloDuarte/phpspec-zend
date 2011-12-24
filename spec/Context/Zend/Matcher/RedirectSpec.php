<?php

namespace Spec\PHPSpec\Context\Zend\Matcher;

use \PHPSpec\Context\Zend\Matcher\Redirect;

class DescribeRedirect extends \PHPSpec\Context
{
    function itShouldBeTrueIfResponseRedirects()
    {
        $matcher = $this->spec(new Redirect(null));
        $response = $this->mock('Response');
        $response->shouldReceive('isRedirect')
                 ->andReturn(true);
        
        $matcher->matches($response)->should->beTrue();
    }
    
    function itShouldBeFalseIfResponseDoesNotRedirect()
    {
        $matcher = $this->spec(new Redirect(null));
        $response = $this->mock('Response');
        $response->shouldReceive('isRedirect')
                 ->andReturn(false);
        
        $matcher->matches($response)->should->beFalse();
    }
}