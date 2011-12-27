<?php
/**
 * PHPSpec
 *
 * LICENSE
 *
 * This file is subject to the GNU Lesser General Public License Version 3
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/lgpl-3.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@phpspec.net so we can send you a copy immediately.
 *
 * @category  PHPSpec
 * @package   PHPSpec
 * @copyright Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright Copyright (c) 2010-2011 Pádraic Brady, Travis Swicegood,
 *                                    Marcello Duarte
 * @license   http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
 
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';

use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst,
    PHPSpec_Context_Zend_Filter_LCFirst as LCFirst,
    Zend_CodeGenerator_Php_Property as PropertyGenerator,
    Zend_Filter_Word_UnderscoreToCamelCase as UnderscoreToCamelCase;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2011 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Context_ModelFile
    extends Zend_Tool_Project_Context_Zf_AbstractClassFile
{

    /**
     * @var string
     */
    protected $_modelName = 'Base';

    /**
     * @var array
     */
    protected $_fields = array();

    /**
     * @var string
     */
    protected $_filesystemName = 'modelName';
    
    /**
     * getName()
     *
     * @return string
     */
    public function getName()
    {
        return 'ModelFile';
    }
    
    /**
     * init()
     *
     */
    public function init()
    {
        $this->_modelName = $this->_resource->getAttribute('modelName');
        $this->_fields = $this->_resource->getAttribute('fields');
        $this->_filesystemName = $this->_modelName . '.php';
        parent::init();
    }
    
    /**
     * Accessor for the model name
     *
     * @return string
     */
    public function getModelName()
    {
        return $this->_modelName;
    }

    /**
     * Generates the content of the model file
     *
     * @return void
     * @author Marcello Duarte  
     */
    public function getContents()
    {
        $className  = $this->getFullClassName($this->_modelName, 'Model');
        $properties = count($this->_fields) ?
                      $this->getProperties($this->_fields) :
                      array();
        $methods    = count($this->_fields) ?
                      $this->getMethods($this->_fields, $this->_modelName) :
                      array();

        $codeGenFile = new Zend_CodeGenerator_Php_File(
            array(
                'fileName' => $this->getPath(),
                'classes' => array(
                    new Zend_CodeGenerator_Php_Class(
                        array(
                            'name' => $className,
                            'properties' => $properties,
                            'methods' => $methods
                        )
                    )
                )
            )
        );
        return $codeGenFile->generate();
    }
    
    /**
     * Generates the properties code
     *
     * @param array $fields 
     * @return string
     */
    protected function getProperties(array $fields, $addId = true)
    {
        $properties = array();
        $camelCase = new UnderscoreToCamelCase;
        
        if ($addId) {
            $this->addId($fields);
        }
        
        foreach ($fields as $field) {
            $varAndType = explode(':', $field);
            list($varname, $type) = count($varAndType) > 1 ?
                                    $varAndType :
                                    array($varAndType[0], 'mixed');
            $varname = LCFirst::apply($camelCase->filter($varname));
            $properties[] = new PropertyGenerator(
                array(
                    'name' => "_$varname",
                    'visibility' => PropertyGenerator::VISIBILITY_PROTECTED,
                    'docblock' => "@var $type \$$varname"
                )
            );
        }
        return $properties;
    }
    
    /**
     * Creates the method generators
     *
     * @param array  $fields
     * @param string $class
     * @return array<Zend_CodeGenerator_Php_Method> 
     */
    protected function getMethods(array $fields, $class, $addId = true)
    {
        $methods = array();
        $camelCase = new UnderscoreToCamelCase;
        
        if ($addId) {
            $this->addId($fields);
        }
        
        $constructorParameter = array();
        $contructorDocblock = "Creates the $class model" . PHP_EOL . PHP_EOL;
        $constructorBody = '';
        foreach ($fields as $field) {
            $varAndType = explode(':', $field);
            list($varname, $type) = count($varAndType) > 1 ?
                                    $varAndType :
                                    array($varAndType[0], 'mixed');
            $varname = LCFirst::apply($camelCase->filter($varname));
            $methods[] = $this->_generateGetter($varname, $type);
            $methods[] = $this->_generateSetter($varname, $type);
            $constructorParameter[] = new Zend_CodeGenerator_Php_Parameter(
                array('name' => $varname, 'defaultValue' => null)
            );
            $contructorDocblock .= '@param ' . $type .
                                   ' $' . $varname . PHP_EOL;
            $constructorBody .= '$this->_' . $varname . ' = $' .
                                $varname . ';' . PHP_EOL;
        }
        
        $methods[] = $this->_generateFactory($class);
        $methods[] = $this->_generateIsValid();
        $methods[] = $this->_generateToArray($fields);
        
        if (!empty($constructorParameter)) {
            $constructor = $this->_generateConstructor(
                $constructorParameter, $contructorDocblock, $constructorBody
            );
        }
        
        array_unshift($methods, $constructor);
        
        return $methods;
    }
    
    /**
     * Creates the factory method generator
     *
     * @param string $name 
     * @return Zend_CodeGenerator_Php_Method
     */
    protected function _generateFactory($name)
    {
        return new Zend_CodeGenerator_Php_Method(
            array(
                'name' => 'create',
                'parameters' => array(new Zend_CodeGenerator_Php_Parameter(
                    array(
                        'name' => 'attributes',
                        'type' => 'array',
                        'defaultValue' => array()
                    )
                )),
                'static' => true,
                'docblock' => "Creates (as a factory) the model" . PHP_EOL .
                              PHP_EOL . "@param array \$attributes",
                'body' => '$model = new Application_Model_' . $name . ';' .
                          PHP_EOL .
                          'foreach ($attributes as $attribute => $value) {' .
                          PHP_EOL .
                          '    $setter = "set$attribute";' . PHP_EOL .
                          '    if (method_exists($model, $setter)) {' .
                          PHP_EOL .
                          '        $model->$setter($value);' .
                          PHP_EOL .
                          '    }' . PHP_EOL .
                          '}' . PHP_EOL .
                          'return $model;' . PHP_EOL
            )
        );
    }
    
    /**
     * Creates the isValid method generator
     *
     * @return Zend_CodeGenerator_Php_Method
     */
    protected function _generateIsValid()
    {
        return new Zend_CodeGenerator_Php_Method(
            array(
                'name' => 'isValid',
                'docblock' => 'Checks whether current attributes are valid',
                'body' => 'return true;'
            )
        );
    }
    
    /**
     * Creates the constructor
     *
     * @return Zend_CodeGenerator_Php_Method
     */
    protected function _generateConstructor($constructorParameter,
        $contructorDocblock, $constructorBody)
    {
        return new Zend_CodeGenerator_Php_Method(
            array(
                'name' => '__construct',
                'parameters' => $constructorParameter,
                'docblock' => $contructorDocblock,
                'body' => $constructorBody                
            )
        );
    }
    
    /**
     * Creates a setter generator
     *
     * @param string $varname 
     * @param string $type 
     * @return Zend_CodeGenerator_Php_Method
     */
    protected function _generateSetter($varname, $type)
    {
        return new Zend_CodeGenerator_Php_Method(
            array(
                'name' => "set" . UCFirst::apply($varname),
                'parameters' => array(new Zend_CodeGenerator_Php_Parameter(
                    array(
                        'name' => $varname
                    )
                )),
                'body' => '$this->_' . $varname . ' = $' . $varname . ';' .
                          PHP_EOL . 'return $this;',
                'docblock' => "Sets the $varname" . PHP_EOL . PHP_EOL .
                              "@param $type $varname"
            )
        );
    }
    
    /**
     * Creates a getter generator
     *
     * @param string $varname 
     * @param string $type 
     * @return Zend_CodeGenerator_Php_Method
     */
    protected function _generateGetter($varname, $type)
    {
        return new Zend_CodeGenerator_Php_Method(
            array(
                'name' => "get" . UCFirst::apply($varname),
                'body' => 'return $this->_' . $varname . ';',
                'docblock' => "Gets the $varname" . PHP_EOL . PHP_EOL .
                              "@return $type"
            )
        );
    }
    
    /**
     * Creates a toArray() generator
     * 
     * @param array $fields
     * @return Zend_CodeGenerator_Php_Method
     */
    protected function _generateToArray(array $fields)
    {
        $camelCase = new UnderscoreToCamelCase;
        $body = 'return array(' . PHP_EOL;
        foreach ($fields as $field) {
            $varAndType = explode(':', $field);
            list($varname, $type) = count($varAndType) > 1 ?
                                    $varAndType :
                                    array($varAndType[0], 'mixed');
            $key = $varname;
            $body .= '    \'' . $varname . '\' => $this->_' .
                     LCFirst::apply($camelCase->filter($varname)) . ',' . PHP_EOL;
        }
        if (!empty($fields)) {
            $body = substr($body, 0, strlen($body) - 2) . PHP_EOL;
        }
        $body .= ');';
        return new Zend_CodeGenerator_Php_Method(
            array(
                'name' => "toArray",
                'body' => $body,
                'docblock' => "Returns the model serialized as an array" .
                              PHP_EOL . PHP_EOL .
                              "@return array"
            )
        );
    }
    
    /**
     * Adds the id field to fields array
     *
     * @param array &$fields 
     * @return void
     */
    private function addId(array &$fields)
    {

        if (!in_array('id:integer', $fields) &&
            !in_array('id:int', $fields)) {
                $fields = array_merge(array('id:int'), $fields);
            }
        if ($key = array_search('id', $fields)) {
            $fields[$key] = 'id:int';
        }

    }
}