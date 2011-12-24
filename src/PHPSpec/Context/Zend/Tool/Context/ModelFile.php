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

use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;

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
    
    public function getModelName()
    {
        return $this->_modelName;
    }

    public function getContents()
    {
        $className = $this->getFullClassName($this->_modelName, 'Model');
        $properties = count($this->_fields) ? $this->getProperties($this->_fields) : array();
        $methods = count($this->_fields) ? $this->getMethods($this->_fields, $this->_modelName) : array();

        $codeGenFile = new Zend_CodeGenerator_Php_File(array(
            'fileName' => $this->getPath(),
            'classes' => array(
                new Zend_CodeGenerator_Php_Class(array(
                    'name' => $className,
                    'properties' => $properties,
                    'methods' => $methods
                    ))
                )
            ));
        return $codeGenFile->generate();
    }
    
    protected function getProperties(array $fields)
    {
        $properties = array();
        foreach ($fields as $field) {
            $varAndType = explode(':', $field);
            list($varname, $type) = count($varAndType) > 1 ? $varAndType : array($varAndType[0], 'mixed');
            $properties[] = new Zend_CodeGenerator_Php_Property(
                array(
                    'name' => "_$varname",
                    'visibility' => Zend_CodeGenerator_Php_Property::VISIBILITY_PROTECTED,
                    'docblock' => "@var $type \$$varname"
                )
            );
        }
        return $properties;
    }
    
    protected function getMethods(array $fields, $class)
    {
        $methods = array();
        $constructorParameter = array();
        $contructorDocblock = "Creates the $class model" . PHP_EOL . PHP_EOL;
        $constructorBody = '';
        foreach ($fields as $field) {
            $varAndType = explode(':', $field);
            list($varname, $type) = count($varAndType) > 1 ? $varAndType : array($varAndType[0], 'mixed');
            $methods[] = $this->_generateGetter($varname, $type);
            $methods[] = $this->_generateSetter($varname, $type);
            $constructorParameter[] = new Zend_CodeGenerator_Php_Parameter(
                array('name' => $varname, 'defaultValue' => null)
            );
            $contructorDocblock .= '@param ' . $type . ' $' . $varname . PHP_EOL;
            $constructorBody .= '$this->_' . $varname . ' = $' . $varname . ';' . PHP_EOL;
        }
        
        $methods[] = $this->_generateFactory($class);
        $methods[] = $this->_generateIsValid();
        
        if (!empty($constructorParameter)) {
            $constructor = $this->_generateConstructor($constructorParameter, $contructorDocblock, $constructorBody);
        }
        
        array_unshift($methods, $constructor);
        
        return $methods;
    }
    
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
                'docblock' => "Creates (as a factory) the model".PHP_EOL.PHP_EOL."@param array \$attributes",
                'body' => '$model = new Application_Model_' . $name . ';' . PHP_EOL .
                          'foreach ($attributes as $attribute => $value) {' . PHP_EOL .
                          '    $setter = "set$attribute";' . PHP_EOL .
                          '    if (method_exists($model, $setter)) {' . PHP_EOL .
                          '        $model->$setter($attribute, $value);' . PHP_EOL .
                          '    }' . PHP_EOL .
                          '}' . PHP_EOL .
                          'return $model;' . PHP_EOL
            )
        );
    }
    
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
    
    protected function _generateConstructor($constructorParameter, $contructorDocblock, $constructorBody)
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
                'body' => '$this->_' . $varname . ' = $' . $varname . ';'.PHP_EOL.'return $this;',
                'docblock' => "Sets the $varname".PHP_EOL.PHP_EOL."@param $type $varname"
            )
        );
    }
    
    protected function _generateGetter($varname, $type)
    {
        return new Zend_CodeGenerator_Php_Method(
            array(
                'name' => "get" . UCFirst::apply($varname),
                'body' => 'return $this->_' . $varname . ';',
                'docblock' => "Gets the $varname".PHP_EOL.PHP_EOL."@return $type"
            )
        );
    }
}