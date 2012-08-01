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
namespace PHPSpec\Context;

use PHPSpec\Context;
use PHPSpec\Context\Zend\ZendTest;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class Zend extends Context
{
    /**
     * Zend Test adaptee
     *
     * @var \Zend_Test_PHPUnit_ControllerTestCase
     */
    protected $_zendTest;
    
    /**
     * Intercepted module name
     *
     * @var \PHPSpec\Specification\Interceptor\Scalar
     */
    public $module;
    
    /**
     * Intercepted controller name
     *
     * @var \PHPSpec\Specification\Interceptor\Scalar
     */
    public $controller;
    
    /**
     * Intercepted action name
     *
     * @var \PHPSpec\Specification\Interceptor\Scalar
     */
    public $action;
    
    /**
     * Dispatches a get request
     *
     * @param string $url 
     */
    public function get($url = null)
    {
        $this->_dispatch($url);
    }
    
    /**
     * Dispatches a post request
     *
     * @param string $url 
     * @param array  $params 
     */
    public function post($url, array $params = array())
    {
        $this->_getZendTest()->request->setMethod('POST')
             ->setPost($params);
        $this->_dispatch($url);
    }
    
    /**
     * Dispatches a put request
     *
     * @param string $url 
     * @param array $params  
     */
    public function put($url, array $params = array())
    {
        $this->_getZendTest()->request->setMethod('PUT')
             ->setPost($params);
        $this->_dispatch($url);
    }
    
    /**
     * Dispatches a delete request
     *
     * @param string $url 
     * @param array $params 
     */
    public function delete($url, array $params = array())
    {
        $this->_getZendTest()->request->setMethod('DELETE')
             ->setPost($params);
        $this->_dispatch($url);
    }
    
    /**
     * Dispatches a head request
     *
     * @param string $url 
     * @param array $params 
     */
    public function head($url, array $params = array())
    {
        $this->_getZendTest()->request->setMethod('HEAD')
             ->setPost($params);
        $this->_dispatch($url);
    }
    
    /**
     * Constroi route e intercepta 
     *
     * @param string $options 
     * @return \PHPSpec\Specification\Interceptor\Scalar  
     */
    public function routeFor($options)
    {
        return $this->spec($this->_getZendTest()->url($options));
    }
    
    /**
     * Proxies the dispatch to zend test and fetch module, controller, action,
     * response and request
     *
     * @param string $url 
     */
    protected function _dispatch($url = null)
    {
        $this->_getZendTest()->dispatch($url);
        $this->module = $this->spec(
            $this->_getZendTest()->request->getModuleName()
        );
        $this->controller = $this->spec(
            $this->_getZendTest()->request->getControllerName()
        );
        $this->action = $this->spec(
            $this->_getZendTest()->request->getActionName()
        );
        $this->response = $this->spec($this->_getZendTest()->response);
        $this->request = $this->spec($this->_getZendTest()->request); 
    }
    
    /**
     * Gets the adaptee
     *
     * @return \Zend_Test_PHPUnit_ControllerTestCase  
     */
    protected function _getZendTest()
    {
        if ($this->_zendTest === null) {
            $this->_zendTest = new ZendTest;
        }
        return $this->_zendTest;
    }
}