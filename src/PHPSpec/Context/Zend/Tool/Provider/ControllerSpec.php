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
 
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';

use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst,
    Zend_Filter_Word_DashToCamelCase as DashToCamelCase,
    Zend_Filter_Word_CamelCaseToDash as CamelCaseToDash,
    Zend_Tool_Project_Provider_Exception as ProviderException,
    Zend_Tool_Project_Provider_Action as ActionProvider;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Provider_ControllerSpec
    extends Zend_Tool_Project_Provider_Controller
    implements Zend_Tool_Framework_Provider_Pretendable
{
    /**
     * Implements the create action for the controller-spec provider
     *
     * @param string $name 
     * @param string $commaSeparatedActions 
     * @param string $module 
     * @return void
     */
    public function create($name, $commaSeparatedActions = '', $module = null)
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
        $name = UCFirst::apply($name);
        
        // Check that there is not a dash or underscore, return if doesnt
        // match regex
        if (preg_match('#[_-]#', $name)) {
            throw new Zend_Tool_Project_Provider_Exception(
                'Controller names should be camel cased.'
            );
        }
        
        // alert the user about inline converted names
        $tense = (($request->isPretend()) ? 'would be' : 'is');
        
        if ($name !== $originalName) {
            $response->appendContent(
                'Note: The canonical controller name that ' . $tense .
                ' used with other providers is "' . $name . '";' .
                ' not "' . $originalName . '" as supplied',
                array('color' => array('yellow'))
            );
        }
        
        $actions = array();
        if (strlen(trim($commaSeparatedActions)) > 0) {
            $actions = array_unique(explode(',', $commaSeparatedActions));
        }
        
        try {
            $controllerResource = self::createResource(
                $this->_loadedProfile, $name, $module
            );
                                   
        } catch (Exception $e) {
            $response->setException($e);
            return;
        }
        
        //controller spec
        $controllerPath = str_replace(
            basename($controllerResource->getContext()->getPath()),
            '',
            $controllerResource->getContext()->getPath()
        );
        $basePath = realpath($controllerPath . '/../..');
        $controllerSpecPath = realpath($basePath . '/spec/controllers') .
                              '/' . $name . 'ControllerSpec.php';
        $specContent = $this->_getSpecContent($name, $actions);
        
        if ($request->isPretend()) {
            $response->appendContent(
                'Would create a controller at ' .
                $controllerResource->getContext()->getPath()
            );
            foreach ($actions as $action) {
                $vowel = in_array($action[0], array('a', 'e', 'i', 'o', 'u'));
                $response->appendContent(
                    'Would create a' . ($vowel ? 'n' : '') . ' ' . $action .
                    ' action method in controller ' . $name
                );
            }
            $response->appendContent(
                'Would create a spec at ' . $controllerSpecPath
            );
        } else {
            $response->appendContent(
                'Creating a controller at ' .
                $controllerResource->getContext()->getPath()
            );
            $controllerResource->create();
            foreach ($actions as $action) {
                $actionResource = ActionProvider::createResource(
                    $this->_loadedProfile, $action, $name, $module
                );
                $response->appendContent(
                    'Creating an ' . $action .
                    ' action method in controller ' . $name
                );
                $actionResource->create();
            }
            $response->appendContent(
                'Creating a spec at ' . $controllerSpecPath
            );
            file_put_contents($controllerSpecPath, $specContent);
        }
    }
    
    /**
     * Gets the content of the controller spec file
     *
     * @param string $name 
     * @param string $actions 
     * @return string
     */
    protected function _getSpecContent($name, $actions)
    {
        $dashToCamelCase = new DashToCamelCase;
        $camelCaseTDash = new CamelCaseToDash;
        $examples = array();
        foreach ($actions as $action) {
            $examples[] =
    'function itShouldBeSuccessfulToGet' . UCFirst::apply(
        $dashToCamelCase->filter($action)
    ) . '()
    {
        $this->get(\'' . strtolower($camelCaseTDash->filter($name)) . '/' .
        $action . '\');
        $this->response->should->beSuccess();
    }';
        }
        
        $examples = implode(PHP_EOL . PHP_EOL . '    ', $examples);
        return <<<CONTENT
<?php

require_once __DIR__ . '/../SpecHelper.php';

class Describe{$name}Controller extends \PHPSpec\Context\Zend\Controller
{
    $examples
}
CONTENT;
    }
}