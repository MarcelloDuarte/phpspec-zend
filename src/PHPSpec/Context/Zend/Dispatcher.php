<?php

namespace PHPSpec\Context\Zend;

use Zend_Controller_Dispatcher_Standard as StandardDispatcher;
use Zend_Controller_Request_Abstract as Request;
use Zend_Controller_Response_Abstract as Response;
use Zend_Controller_Dispatcher_Exception as DispatcherException;
use Zend_Controller_Action as ActionController;

class Dispatcher extends StandardDispatcher
{
    protected $_currentController;
    protected $_observers = array();
    
    public function dispatch(Request $request, Response $response)
    {
        $this->setResponse($response);

        /**
         * Get controller class
         */
        if (!$this->isDispatchable($request)) {
            $controller = $request->getControllerName();
            if (!$this->getParam('useDefaultControllerAlways') && !empty($controller)) {
                require_once 'Zend/Controller/Dispatcher/Exception.php';
                throw new DispatcherException('Invalid controller specified (' . $request->getControllerName() . ')');
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
    
    protected function _createController(Request $request, $className)
    {
        $template = new \Text_Template(
            __DIR__ . DIRECTORY_SEPARATOR . 'Spy' .
                      DIRECTORY_SEPARATOR . 'Controller.php.dist');
        
        $spyControllerName = 'SpyController' . rand(0, time());
        
        $template->setVar(
            array(
                'spyController' =>  $spyControllerName,
                'userController' => $className
            )
        );
        eval($template->render());
        
        $this->_currentController = new $spyControllerName($request, $this->getResponse(), $this->getParams());
        
        foreach ($this->_observers as $observer) {
            $this->_currentController->attach($observer);
        }
        
        if (!$this->_currentController instanceof ActionController) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new DispatcherException(
            'Controller "' . $className . '" is not an instance of Zend_Controller_Action_Interface'
            );
        }
        return $this->_currentController;
    }
    
    public function attach($observer)
    {
        $this->_observers[] = $observer;
    }
    
    public function getCurrentController()
    {
        return $this->_currentController;
    }
    
    public function setCurrentController($controller)
    {
        return $this->_currentController = $controller;
    }
}