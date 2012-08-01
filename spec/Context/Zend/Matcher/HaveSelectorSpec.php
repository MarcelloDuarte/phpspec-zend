<?php

namespace Spec\PHPSpec\Context\Zend\Matcher;

use \PHPSpec\Context\Zend\Matcher\HaveSelector;

class DescribeHaveSelector extends \PHPSpec\Context
{
    function itReturnsFalseIfItDoesntMatch()
    {
        $emptyMarkup = '<html></html>';
        $divSelector = 'div';
        $haveDivSelector = $this->spec(new HaveSelector($divSelector));
        $haveDivSelector->matches($emptyMarkup)->should->beFalse();
    }
    
    function itReturnsTrueIfItExists()
    {
        $markupWithDiv = '<html><div></div></html>';
        $divSelector = 'div';
        $haveDivSelector = $this->spec(new HaveSelector($divSelector));
        $haveDivSelector->matches($markupWithDiv)->should->beTrue();
    }
    
    function itUsesCSSSelectorsForAttributes()
    {
        $markupWithDiv = '<html><div id="foo"></div></html>';
        $divSelector = 'div[id="foo"]';
        $haveDivSelector = $this->spec(new HaveSelector($divSelector));
        $haveDivSelector->matches($markupWithDiv)->should->beTrue();
    }
    
    function itLetsYouPassCSSSelectorsAsAnArray()
    {
        $markupWithDiv = '<html><div id="foo"></div></html>';
        $haveDivSelector = $this->spec(new HaveSelector('div', array('id' => 'foo')));
        $haveDivSelector->matches($markupWithDiv)->should->beTrue();
    }
    
    function itUsesTextAttributeForInnerHtml()
    {
        $markupWithDiv = '<html><div>foo</div></html>';
        $divSelector = 'div';
        $haveDivSelector = $this->spec(new HaveSelector($divSelector, array(
            'text' => 'foo'
        )));
        $haveDivSelector->matches($markupWithDiv)->should->beTrue();
    }
}