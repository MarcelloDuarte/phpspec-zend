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
 
require_once 'PHPSpec/Context/Zend/Filter/LCFirst.php';

require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';

use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst,
    Zend_Filter_Word_CamelCaseToDash as CamelCaseToDash,
    PHPSpec_Context_Zend_Filter_UCFirst as UCFirst,
    Zend_Filter_Word_DashToCamelCase as DashToCamelCase,
    Zend_Tool_Project_Provider_Exception as ProviderException,
    Zend_Tool_Project_Provider_Controller as ControllerProvider;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Provider_ActionSpec
    extends Zend_Tool_Project_Provider_Action
    implements Zend_Tool_Framework_Provider_Pretendable
{
    /**
     * Implements the create action for the action-spec provider
     *
     * @param string $name 
     * @param string $controllerName 
     * @param string $module 
     * @return void
     */
    public function create($name, $controllerName, $module = null)
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);
        
        if (!is_dir('spec')) {
            throw new ProviderException(
                'Please run zf generate phpspec, to create the environment'
            );
        }
        
        $response = $this->_registry->getResponse();
        $request = $this->_registry->getRequest();
        
        $originalName = $name;
        $name = LCFirst::apply($name);
        $camelCaseToDashFilter = new CamelCaseToDash();
        $name = strtolower($camelCaseToDashFilter->filter($name));
        
        // alert the user about inline converted names
        $tense = (($request->isPretend()) ? 'would be' : 'is');
        
        if ($name !== $originalName) {
            $response->appendContent(
                'Note: The canonical action name that ' . $tense .
                ' used with other providers is "' . $name . '";' .
                ' not "' . $originalName . '" as supplied',
                array('color' => array('yellow'))
            );
        }
        
        try {
            $controllerResource = ControllerProvider::createResource(
                $this->_loadedProfile, $controllerName, $module
            ); 
            $actionResource = self::createResource(
                $this->_loadedProfile, $name, $controllerName, $module
            );    
        } catch (Exception $e) {
            $response->setException($e);
            return;
        }
        
        // action spec
        $controllerPath = str_replace(
            basename($controllerResource->getContext()->getPath()),
            '',
            $controllerResource->getContext()->getPath()
        );
        $basePath = realpath($controllerPath . '/../..');
        $controllerSpecPath = realpath($basePath . '/spec/controllers') .
                              '/' . $controllerName . 'Spec.php';
        $specContent = $this->_getSpecContent($name, $controllerName);
        
        if ($request->isPretend()) {
            $response->appendContent(
                'Would create an action named ' . $name .
                ' inside controller at ' .
                $controllerResource->getContext()->getPath()
            );
            $response->appendContent(
                'Would create an action spec at ' . $controllerSpecPath
            );
        } else {
            $response->appendContent(
                'Creating an action named ' . $name .
                ' inside controller at ' .
                $controllerResource->getContext()->getPath()
            );
            $actionResource->create();
            $response->appendContent(
                'Creating an action spec at ' .
                $controllerSpecPath
            );
            $content = file_get_contents($controllerSpecPath);
            file_put_contents(
                $controllerSpecPath,
                str_replace("\n}", $specContent, $content)
            );
        }
        
    }
    
    /**
     * Creates the content for the spec file
     *
     * @param string $name 
     * @param string $controllerName 
     * @return string 
     */
    protected function _getSpecContent($name, $controllerName)
    {
        $dashToCamelCase = new DashToCamelCase;
        $camelCaseToDash = new CamelCaseToDash;
        return'

    function itShouldBeSuccessfulToGet' .
    UCFirst::apply($dashToCamelCase->filter($name)) . '()
    {
        $this->get(\'' .
        strtolower($camelCaseToDash->filter($controllerName)) .
        '/' . $name . '\');
        $this->response->should->beSuccess();
    }
}';
    }
}