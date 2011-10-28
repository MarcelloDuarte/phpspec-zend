<?php
require_once 'PHPSpec/Context/Zend/Filter/LCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';

use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst,
    Zend_Filter_Word_CamelCaseToDash as CamelCaseToDash,
    PHPSpec_Context_Zend_Filter_UCFirst as UCFirst,
    Zend_Filter_Word_DashToCamelCase as DashToCamelCase;

class PHPSpec_Context_Zend_Tool_Provider_ViewSpec
    extends Zend_Tool_Project_Provider_View
    implements Zend_Tool_Framework_Provider_Pretendable
{
    public function create($name, $controllerName, $module = null)
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);
        
        if (!is_dir('spec')) {
            throw new Zend_Tool_Project_Provider_Exception('Please run zf generate phpspec, to create the environment');
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
                'Note: The canonical view name that ' . $tense
                    . ' used with other providers is "' . $name . '";'
                    . ' not "' . $originalName . '" as supplied',
                array('color' => array('yellow'))
                );
        }
        
        try {
            $viewResource = self::createResource($this->_loadedProfile, $name, $controllerName, $module);    
                       
        } catch (Exception $e) {
            $response->setException($e);
            return;
        }
        
        // view spec
        $inflectedController = strtolower($camelCaseToDashFilter->filter($controllerName));
        $viewPath = str_replace(
            basename($viewResource->getContext()->getPath()),
            '', $viewResource->getContext()->getPath());
        
        $basePath = str_replace("application/views/scripts/$inflectedController", '', $viewPath);
        $specNameFilter = new DashToCamelCase();
        $specName = UCFirst::apply($specNameFilter->filter($name));
        
        $moduleController = $inflectedController;
        if ($module !== null) {
            $moduleController .= strtolower($camelCaseToDashFilter->filter($module));
        }
        $viewSpecPath = realpath($basePath . '/spec/views') . '/' .
                        $moduleController . '/' .
                        $specName . 'Spec.php';
        $specContent = $this->_getSpecContent($name, $controllerName, $module);
        
        if ($request->isPretend()) {
            $response->appendContent('Would create a view script in location ' . $viewResource->getContext()->getPath());
            $response->appendContent('Would create a spec at ' . $viewSpecPath);
        } else {
            $response->appendContent('Creating a view script in location ' . $viewResource->getContext()->getPath());
            $viewResource->create();
            $response->appendContent('Creating a spec at ' . $viewSpecPath);
            if (!is_dir($basePath . '/spec/views')) {
                mkdir($basePath . '/spec/views');
            }
            if (!is_dir($basePath . '/spec/views/' . $inflectedController)) {
                mkdir($basePath . '/spec/views/' . $inflectedController);
            }
            file_put_contents($viewSpecPath, $specContent);
        }
        
    }
    
    protected function _getSpecContent($name, $controllerName, $module)
    {
        
        $namespace = $controllerName;
        $helperDir = "/../../";
        if ($module !== null) {
            $namespace = UCFirst::apply($module) . "\\$controllerName";
            $helperDir = "/../../../";
        }
        $specNameFilter = new DashToCamelCase();
        $inflectedView = $specNameFilter->filter($name);
        return <<<CONTENT
<?php

namespace $namespace;

require_once __DIR__ . '{$helperDir}SpecHelper.php';

use \PHPSpec\Context\Zend\View as ViewContext;

class Describe{$inflectedView} extends ViewContext
{
    function itRendersTheDefaultContent()
    {
        \$this->render();
        \$this->rendered->should->contain('$controllerName');
        \$this->rendered->should->contain('$name');
    }
}
CONTENT;
    }
}