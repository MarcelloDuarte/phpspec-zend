<?php

namespace Spec\PHPSpec\Context\Zend\Matcher;

use \PHPSpec\Context\Zend\Matcher\BeSuccess;

class DescribeBeSuccess extends \PHPSpec\Context
{
    function itPassesIfHttpStatusIs200()
    {
        $matcher = $this->spec(new BeSuccess());
        $response = $this->mock('Response');
        $response->shouldReceive('getHttpResponseCode')
                 ->andReturn(200);
        
        $matcher->matches($response)->should->beTrue();
    }
    
    function itFailsIfHttpStatusIsNot200()
    {
        $matcher = $this->spec(new BeSuccess());
        $response = $this->mock('Response');
        $response->shouldReceive('getHttpResponseCode')
                 ->andReturn(404);
        
        $matcher->matches($response)->should->beFalse();
    }
}