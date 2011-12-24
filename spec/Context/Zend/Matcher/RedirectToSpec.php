<?php

namespace Spec\PHPSpec\Context\Zend\Matcher;

use \PHPSpec\Context\Zend\Matcher\RedirectTo;

class DescribeRedirectTo extends \PHPSpec\Context
{
    function itShoultBeFalseIfItIsNotARedirect()
    {
        $matcher = $this->spec(new RedirectTo('itdoesntmatter'));
        $response = $this->mock('Response');
        $response->shouldReceive('isRedirect')->andReturn(false);
        
        $matcher->matches($response)->should->beFalse();
    }
    
    function itShouldBeTrueIfResponseRedirectToGivenUrl()
    {
        $matcher = $this->spec(new RedirectTo('someurl'));
        $response = $this->mock('Response');
        $response->shouldReceive('isRedirect')->andReturn(true);
        $response->shouldReceive('sendHeaders')
                 ->andReturn(array(
                    'location' => 'Location: someurl'
                 ));
        
        $matcher->matches($response)->should->beTrue();
    }
    
    function itShouldBeFalseIfResponseRedirectToADifferentUrlThanExpected()
    {
        $matcher = $this->spec(new RedirectTo('someurl'));
        $response = $this->mock('Response');
        $response->shouldReceive('isRedirect')->andReturn(true);
        $response->shouldReceive('sendHeaders')
                 ->andReturn(array(
                    'location' => 'Location: wrongurl'
                 ));
        
        $matcher->matches($response)->should->beFalse();
    }
}