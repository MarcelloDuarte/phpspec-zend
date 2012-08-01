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
 * @copyright Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                    Marcello Duarte
 * @license   http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
namespace PHPSpec\Context\Zend\Spy;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class View implements Subject, \Zend_View_Interface
{
    /**
     * The observers attached to the view
     *
     * @var array
     */
    protected $_observers = array();
    
    /**
     * Intercepts the render method
     *
     * @param string $name 
     */
    public function render($name)
    {
        $this->notify(
            array(
                'method' => 'renderView',
                'name' => $name
            )
        );
    }
    
    /**
     * @inheritdoc
     */
    public function attach(Observer $observer)
    {
        $this->_observers[] = $observer;
    }
    
    /**
     * @inheritdoc
     */
    public function notify($event)
    {
        foreach ($this->_observers as $observer) {
            $observer->update($event);
        }
    }
    
    /**
     * Intercepts assigning variables to the template
     *
     * @param string $viewVariable 
     * @param mixed $value 
     */
    public function __set($viewVariable, $value)
    {
        $this->assign($viewVariable, $value);
    }
    
    /**
     * Intercepts assigning variables to the template
     *
     * @param string $viewVariable 
     * @param mixed $value 
     */
    public function assign($spec, $value = null)
    {
        $this->notify(
            array(
                'method' => 'assign',
                'viewVariable' => $spec,
                'value' => $value
            )
        );
    }
    
    /**
     * @inheritdoc
     */
    public function getEngine()
    {
        
    }
    
    /**
     * @inheritdoc
     */
    public function setScriptPath($path)
    {
        
    }
    
    /**
     * @inheritdoc
     */
    public function getScriptPaths()
    {
        return array();
    }
    
    /**
     * @inheritdoc
     */
    public function setBasePath($path, $classPrefix = 'Zend_View')
    {
        
    }
    
    /**
     * @inheritdoc
     */
    public function addBasePath($path, $classPrefix = 'Zend_View')
    {
        
    }
    
    /**
     * @inheritdoc
     */
    public function __isset($key)
    {
        
    }
    
    /**
     * @inheritdoc
     */
    public function __unset($key)
    {
        
    }
    
    /**
     * @inheritdoc
     */
    public function clearVars()
    {
        
    }
}
