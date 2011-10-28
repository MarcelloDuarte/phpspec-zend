<?php

require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';

use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst,
    Zend_Filter_Word_DashToCamelCase as DashToCamelCase,
    Zend_Filter_Word_CamelCaseToDash as CamelCaseToDash;

class PHPSpec_Context_Zend_Tool_Provider_ControllerSpec
    extends Zend_Tool_Project_Provider_Controller
    implements Zend_Tool_Framework_Provider_Pretendable
{
    public function create($name, $commaSeparatedActions = '', $module = null)
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);
                
        if (!is_dir('spec')) {
            throw new Zend_Tool_Project_Provider_Exception('Please run zf generate phpspec, to create the environment');
        }
        
        $response = $this->_registry->getResponse();
        $request = $this->_registry->getRequest();
        
        $originalName = $name;
        $name = UCFirst::apply($name);
        
        // Check that there is not a dash or underscore, return if doesnt match regex
        if (preg_match('#[_-]#', $name)) {
            throw new Zend_Tool_Project_Provider_Exception('Controller names should be camel cased.');
        }
        
        // alert the user about inline converted names
        $tense = (($request->isPretend()) ? 'would be' : 'is');
        
        if ($name !== $originalName) {
            $response->appendContent(
                'Note: The canonical controller name that ' . $tense
                    . ' used with other providers is "' . $name . '";'
                    . ' not "' . $originalName . '" as supplied',
                array('color' => array('yellow'))
                );
        }
        
        $actions = array();
        if (strlen(trim($commaSeparatedActions)) > 0) {
            $actions = array_unique(explode(',', $commaSeparatedActions));
        }
        
        try {
            $controllerResource = self::createResource($this->_loadedProfile, $name, $module);
                                   
        } catch (Exception $e) {
            $response->setException($e);
            return;
        }
        
        //controller spec
        $controllerPath = str_replace(basename($controllerResource->getContext()->getPath()), '', $controllerResource->getContext()->getPath());
        $basePath = realpath($controllerPath . '/../..');
        $controllerSpecPath = realpath($basePath . '/spec/controllers') . '/' . $name . 'Spec.php';
        $specContent = $this->_getSpecContent($name, $actions);
        
        if ($request->isPretend()) {
            $response->appendContent('Would create a controller at ' . $controllerResource->getContext()->getPath());
            foreach ($actions as $action) {
                $response->appendContent('Would create an ' . $action . ' action method in controller ' . $name);
            }
            $response->appendContent('Would create a spec at ' . $controllerSpecPath);
        } else {
            $response->appendContent('Creating a controller at ' . $controllerResource->getContext()->getPath());
            $controllerResource->create();
            foreach ($actions as $action) {
                $actionResource = Zend_Tool_Project_Provider_Action::createResource($this->_loadedProfile, $action, $name, $module);
                $response->appendContent('Creating an ' . $action . ' action method in controller ' . $name);
                $actionResource->create();
            }
            $response->appendContent('Creating a spec at ' . $controllerSpecPath);
            file_put_contents($controllerSpecPath, $specContent);
        }
    }
    
    protected function _getSpecContent($name, $actions)
    {
        $dashToCamelCase = new DashToCamelCase;
        $CamelCaseTDash = new CamelCaseToDash;
        $examples = array();
        foreach ($actions as $action) {
            $examples[] =
    'function itShouldBeSuccessfulToGet' . UCFirst::apply($dashToCamelCase->filter($action)) . '()
    {
        $this->get(\'' . strtolower($CamelCaseTDash->filter($name)) . '/' . $action . '\');
        $this->response->should->beSuccess();
    }';
        }
        
        $examples = implode(PHP_EOL . PHP_EOL . '        ', $examples);
        return <<<CONTENT
<?php

require_once __DIR__ . '/../SpecHelper.php';

class Describe{$name} extends \PHPSpec\Context\Zend\Controller
{
    $examples
}
CONTENT;
    }
}