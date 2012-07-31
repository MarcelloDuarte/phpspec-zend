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

use Zend_Controller_Dispatcher_Standard as StandardDispatcher;
use Zend_Controller_Request_Abstract as Request;
use Zend_Controller_Response_Abstract as Response;
use Zend_Controller_Dispatcher_Exception as DispatcherException;
use Zend_Controller_Action as ActionController;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class Dispatcher extends StandardDispatcher
{
    /**
     * Current controller
     *
     * @var Zend_Controller_Action
     */
    protected $_currentController;
    
    /**
     * Observers attached to controller and view
     *
     * @var array
     */
    protected $_observers = array();
    
    /**
     * @inheritdoc
     */
    public function dispatch(Request $request, Response $response)
    {
        $this->setResponse($response);

        /**
         * Get controller class
         */
        if (!$this->isDispatchable($request)) {
            $controller = $request->getControllerName();
            if (!$this->getParam('useDefaultControllerAlways') &&
                !empty($controller)) {
                require_once 'Zend/Controller/Dispatcher/Exception.php';
                throw new DispatcherException(
                    'Invalid controller specified (' .
                    $request->getControllerName() . ')'
                );
            }

            $className = $this->getDefaultControllerClass($request);
        } else {
            $className = $this->getControllerClass($request);
            if (!$className) {
                $className = $this->getDefaultControllerClass($request);
            }
        }

        /**
         * Load the controller class file
         */
        $className = $this->loadClass($className);

        /**
         * Instantiate controller with request, response, and invocation
         * arguments; throw exception if it's not an action controller
         */
        $controller = $this->_createController($request, $className);

        /**
         * Retrieve the action name
         */
        $action = $this->getActionMethod($request);

        /**
         * Dispatch the method call
         */
        $request->setDispatched(true);

        // by default, buffer output
        $disableOb = $this->getParam('disableOutputBuffering');
        $obLevel   = ob_get_level();
        if (empty($disableOb)) {
            ob_start();
        }

        try {
            $controller->dispatch($action);
        } catch (Exception $e) {
            // Clean output buffer on error
            $curObLevel = ob_get_level();
            if ($curObLevel > $obLevel) {
                do {
                    ob_get_clean();
                    $curObLevel = ob_get_level();
                } while ($curObLevel > $obLevel);
            }
            throw $e;
        }

        if (empty($disableOb)) {
            $content = ob_get_clean();
            $response->appendBody($content);
        }

        // Destroy the page controller instance and reflection objects
        $controller = null;
    }
    
    /**
     * Creates the controller and attaches observers to it
     * 
     * @param Request $request
     * @param string $className
     */
    protected function _createController(Request $request, $className)
    {
        $template = new \Text_Template(
            __DIR__ . DIRECTORY_SEPARATOR .
            'Spy'   . DIRECTORY_SEPARATOR . 'Controller.php.dist'
        );
        
        $spyControllerName = 'SpyController' . rand(0, time());
        
        $template->setVar(
            array(
                'spyController' =>  $spyControllerName,
                'userController' => $className
            )
        );
        eval($template->render());
        
        $this->_currentController = new $spyControllerName(
            $request, $this->getResponse(), $this->getParams()
        );
        
        foreach ($this->_observers as $observer) {
            $this->_currentController->attach($observer);
        }
        
        if (!$this->_currentController instanceof ActionController) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new DispatcherException(
                'Controller "' . $className .
                '" is not an instance of Zend_Controller_Action_Interface'
            );
        }
        return $this->_currentController;
    }
    
    /**
     * Attaches a observer
     *
     * @param Observer $observer
     */
    public function attach($observer)
    {
        $this->_observers[] = $observer;
    }
    
    /**
     * Returns the current controller
     *
     * @return Zend_Controller_Action 
     */
    public function getCurrentController()
    {
        return $this->_currentController;
    }
    
    /**
     * Sets the current controller
     *
     * @param Zend_Controller_Action $controller
     */
    public function setCurrentController($controller)
    {
        $this->_currentController = $controller;
    }
}