<?php

require_once 'PHPSpec/Context/Zend/Filter/Pluralize.php';

use \PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;

class DescribePluralize extends \PHPSpec\Context
{
    protected $pluralize;
    
    function before()
    {
        $this->pluralize = $this->spec(new Pluralize);
    }
    
    function itConvertsSimpleCasesToPlural()
    {
        $word = 'Author';
        $this->pluralize->filter($word)->should->be('Authors');
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
        
        foreach ($words as $word => $plural) {
            $this->pluralize->filter($word)->should->be($plural);
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
        
        foreach ($irregular as $word => $plural) {
            $this->pluralize->filter($word)->should->be($plural);
        }
    }
    
    function itConvertsUncountableCases()
    {
        $uncountable = array(
            'equipment', 'information', 'rice', 'money', 'species', 'series',
            'fish', 'sheep'
        );
        
        foreach ($uncountable as $word) {
            $this->pluralize->filter($word)->should->be($word);
        }
    }
}