<?php

namespace Spec\PHPSpec\Context\Zend\Filter;

require_once 'PHPSpec/Context/Zend/Filter/Singularize.php';

use \PHPSpec_Context_Zend_Filter_Singularize as Singularize;

class DescribeSingularize extends \PHPSpec\Context
{
    protected $singularize;
    
    function before()
    {
        $this->singularize = $this->spec(new Singularize);
    }
    
    function itConvertsSimpleCasesToPlural()
    {
        $word = 'Authors';
        $this->singularize->filter($word)->should->be('Author');
    }
    
    function itConvertsMoreComplexCases()
    {
        $words = array(
            'quiz'    => 'quizzes',
            'ox'      => 'oxen',
            'mouse'   => 'mice',
            'house'   => 'houses',
            'virus'   => 'viruses',
            'fox'     => 'foxes',
            'tomato'  => 'tomatoes',
            'buffalo' => 'buffaloes',
            'bus'     => 'buses',
            'octopus' => 'octopuses',
            'status'  => 'statuses'
        );
        
        foreach ($words as $singular => $word) {
            $this->singularize->filter($word)->should->be($singular);
        }
    }
    
    function itConvertsIrregularCases()
    {
        $irregular = array(
            'person' => 'people',
            'man'    => 'men',
            'child'  => 'children',
            'sex'    => 'sexes',
            'move'   => 'moves'
        );
        
        foreach ($irregular as $singular => $word) {
            $this->singularize->filter($word)->should->be($singular);
        }
    }
    
    function itConvertsUncountableCases()
    {
        $uncountable = array(
            'equipment', 'information', 'rice', 'money', 'species', 'series',
            'fish', 'sheep'
        );
        
        foreach ($uncountable as $word) {
            $this->singularize->filter($word)->should->be($word);
        }
    }
}