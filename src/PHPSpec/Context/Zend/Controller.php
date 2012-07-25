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
namespace PHPSpec\Context\Zend;

use \PHPSpec\Context,
    \PHPSpec\Context\Zend\ZendTest;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class Controller extends Context
{
    
    /**
     * The base test class
     *
     * @var \PHPSpec\Context\Zend\ZendTest
     */
    protected $_zendTest;
    
    /**
     * Current module name
     *
     * @var string
     */
    public $module;
    
    /**
     * Current controller name
     *
     * @var string
     */
    public $controller;
    
    /**
     * Current action name
     *
     * @var string
     */
    public $action;
    
    /**
     * Front controller
     *
     * @var Zend_Controller_Front
     */
    public $frontController;
    
    public function before()
    {
        $this->reset();
    }
    
    /**
     * Dispatches a get request to a given url
     * 
     * @param string $url
     */
    public function get($url = null)
    {
        $this->_dispatch($url);
    }
    
    /**
     * Dispatches a post request to a given url with given params
     * 
     * @param string $url
     * @param array $params
     */
    public function post($url, array $params = array())
    {
        $this->_getZendTest()->request->setMethod('POST')
             ->setPost($params);
        $this->_dispatch($url);
    }
    
    /**
     * Dispatches a put request to a given url with given params
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
     * Dispatches a delete request to a given url with given params
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
     * Dispatches a head request to a given url with given params
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
     * Gets the route url for a given set of route options
     * (module, controller, action)
     * 
     * @param array $options
     * @return \PHPSpec\Specification\Interceptor
     */
    public function routeFor(array $options)
    {
        return $this->spec($this->_getZendTest()->url($options));
    }
    
    /**
     * Retrieves the intercepted value assigned to a view variable in the
     * controller, during the exising dispatch
     *
     * @param string $variable 
     * @return \PHPSpec\Specification\Interceptor 
     */
    public function assigns($variable)
    {
        if ($this->frontController === null) {
            throw new \PHPSpec\Exception(
                'You must send a request before using assigns'
            );
        }
        $bootstrap = $this->frontController
                          ->getActualValue()->getParam('bootstrap');
        try {
            $bootstrap->bootstrap('view');
        } catch (\Zend_Application_Bootstrap_Exception $e) {
            $view = new \Zend_View;
        }
        $view = $bootstrap->getResource('view');
        if (!isset($view->$variable)) {
            throw new \PHPSpec\Specification\Result\Failure(
                "$variable is not assigned"
            );
        }
        return $this->spec($view->$variable);
    }
    
    /**
     * Dispatches from zend test and fetch results into local variables
     * 
     * @param string $url
     */
    protected function _dispatch($url = null)
    {
        $zendTest = $this->_getZendTest();
        $zendTest->dispatch($url);
        $this->module = $this->spec($zendTest->request->getModuleName());
        $this->controller = $this->spec(
            $zendTest->request->getControllerName()
        );
        $this->action = $this->spec($zendTest->request->getActionName());
        $this->response = $this->spec($zendTest->response);
        $this->request = $this->spec($zendTest->request);
        $this->frontController = $this->spec($zendTest->getFrontController());
    }
    
    /**
     * Gets zend test base class
     * 
     * @return \PHPSpec\Context\Zend\ZendTest
     */
    protected function _getZendTest()
    {
        if ($this->_zendTest === null) {
            $this->_zendTest = new ZendTest;
        }
        return $this->_zendTest;
    }
    
    /**
     * Resets MVC for a new fresh request
     *
     * @return void
     */
    public function reset()
    {
        $this->module = null;
        $this->controller = null;
        $this->action = null;
        $this->response = null;
        $this->request = null;
        $this->frontController = null;
    }
}