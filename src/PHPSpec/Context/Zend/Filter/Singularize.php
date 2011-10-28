<?php

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

/**
* Inflector for pluralize and singularize English nouns.
* 
* This Inflector is a port of Ruby on Rails Inflector.
* 
* It can be really helpful for developers that want to
* create frameworks based on naming conventions rather than
* configurations.
* 
* It was ported to PHP for the Akelos Framework, a
* multilingual Ruby on Rails like framework for PHP that will
* be launched soon.
*
* I have adapted a bit as is was not passing my tests :) (-- Marcello Duarte)
* 
* @author Bermi Ferrer Martinez <bermi akelos com>
* @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
* @since 0.1
* @version $Revision 0.1 $
*/
class PHPSpec_Context_Zend_Filter_Singularize implements Zend_Filter_Interface
{
	
	/**
	 * Regex used for the conversion
	 * 
	 * @var array
	 */
	protected static $_expressions = array (
        '/(quiz)zes$/i'         => '\1',
        '/(matr)ices$/i'        => '\1ix',
        '/(vert|ind)ices$/i'    => '\1ex',
        '/^(ox)en/i'            => '\1',
        '/(alias|status)es$/i'  => '\1',
        '/(octopu|viru)ses$/i'   => '\1s',
        '/(cris|ax|test)es$/i'  => '\1is',
        '/(shoe)s$/i'           => '\1',
        '/(o)es$/i'             => '\1',
        '/(bus)es$/i'           => '\1',
        '/([m|l])ice$/i'        => '\1ouse',
        '/(x|ch|ss|sh)es$/i'    => '\1',
        '/(m)ovies$/i'          => '\1ovie',
        '/(s)eries$/i'          => '\1eries',
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/([lr])ves$/i'         => '\1f',
        '/(tive)s$/i'           => '\1',
        '/(hive)s$/i'           => '\1',
        '/([^f])ves$/i'         => '\1fe',
        '/(^analy)ses$/i'       => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
        '/([ti])a$/i'           => '\1um',
        '/(n)ews$/i'            => '\1ews',
        '/s$/i'                 => '',
        );
        
     /**
      * Don't inflect this
      * 
      * @var array
      */
     protected static $_uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');
	
     /**
      * Special inflection 
      * 
      * @var array
      */
     protected static $_irregular = array(
        'person' => 'people',
        'man'    => 'men',
        'child'  => 'children',
        'sex'    => 'sexes',
        'move'   => 'moves');
     
    /**
     * Inflects the value to its singular
     * Defined by Zend_Filter_Interface
     *
     * @param string $value String to be filtered
     * 
     * @return string String filtered
     */
    public function filter($value)
    {
        $lowerCasedWord = strtolower($value);
        foreach (self::$_uncountable as $uncountable) {
            if (substr($lowerCasedWord, (-1 * strlen($uncountable))) === $uncountable) {
                return $value;
            }
        }

        foreach (self::$_irregular as $plural => $singular) {
            if (preg_match('/(' . $singular . ')$/i', $value, $arr)) {
                return preg_replace('/(' . $singular . ')$/i', substr($arr[0], 0, 1) . substr($plural, 1), $value);
            }
        }

        foreach (self::$_expressions as $rule => $replacement) {
            if (preg_match($rule, $value)) {
                return preg_replace($rule, $replacement, $value);
            }
        }

        return $value;
    }
    
    public static function apply($value)
    {
        $filter = new static;
        return $filter->filter($value);
    }
}