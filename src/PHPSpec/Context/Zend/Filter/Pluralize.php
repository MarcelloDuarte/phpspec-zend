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
class PHPSpec_Context_Zend_Filter_Pluralize implements Zend_Filter_Interface
{
	
	/**
	 * Regex used for the conversion
	 * 
	 * @var array
	 */
	protected static $_expressions = array (
            '/(quiz)$/i'               => '\1zes',
            '/^(ox)$/i'                => '\1en',
            '/([m|l])ouse$/i'          => '\1ice',
            '/(matr|vert|ind)ix|ex$/i' => '\1ices',
            '/(x|ch|ss|sh)$/i'         => '\1es',
            '/([^aeiouy]|qu)ies$/i'    => '\1y',
            '/([^aeiouy]|qu)y$/i'      => '\1ies',
            '/(hive)$/i'               => '\1s',
            '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
            '/sis$/i'                  => 'ses',
            '/([ti])um$/i'             => '\1a',
            '/(buffal|tomat)o$/i'      => '\1oes',
            '/(bu)s$/i'                => '\1ses',
            '/(alias|status)/i'        => '\1es',
            '/(octopus|virus)$/i'      => '\1es',
            '/(ax|test)is$/i'          => '\1es',
            '/s$/i'                    => 's',
            '/$/'                      => 's'
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
        'man' => 'men',
        'child' => 'children',
        'sex' => 'sexes',
        'move' => 'moves');
     
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
            if(substr($lowerCasedWord, (-1 * strlen($uncountable))) === $uncountable) {
                return $value;
            }
        }

        foreach (self::$_irregular as $plural => $singular) {
            if (preg_match('/(' . $plural . ')$/i', $value, $arr)) {
                return preg_replace('/(' . $plural . ')$/i', substr($arr[0], 0, 1) . substr($singular, 1), $value);
            }
        }

        foreach (self::$_expressions as $rule => $replacement) {
            if (preg_match($rule, $value)) {
                return preg_replace($rule, $replacement, $value);
            }
        }
        return false;
    }
    
    public static function apply($value)
    {
        $filter = new static;
        return $filter->filter($value);
    }
}