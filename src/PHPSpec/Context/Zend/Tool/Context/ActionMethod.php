<?php

require_once 'PHPSpec/Context/Zend/Filter/LCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/Pluralize.php';

use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst;
use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;
use PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;

use Zend_Filter_Word_CamelCaseToSeparator as CamelCaseToSeparator;
use Zend_Filter_Word_CamelCaseToDash as CamelCaseToDash;

class PHPSpec_Context_Zend_Tool_Context_ActionMethod
{
    public static function create($name, $entity, $controllerPath)
    {
        $controllerContent = file_get_contents($controllerPath);
            
        $getActionContentMethod = "_get{$name}ActionContent";
        file_put_contents(
            $controllerPath,
            str_replace(
                "{$name}Action()\n    {\n        // action body",
                "{$name}Action()\n    {\n        " . self::$getActionContentMethod($entity),
                $controllerContent));
    }
    
    protected static function _getIndexActionContent($entity)
    {
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        
        return "\${$lc}Mapper = new {$entity}Mapper;
        \$this->view->{$plural} = \${$lc}Mapper->fetchAll();";
    }
    
    protected static function _getAddActionContent($entity)
    {
        $camelCaseToDash = new CamelCaseToDash;
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        $lowerDashedPlural = $camelCaseToDash->filter($plural);
        
        return "\${$lc}Form = new {$entity}Form;
        \${$lc}Mapper = new {$entity}Mapper;
        
        if (\$this->_request->isPost()) {
            \$params = \$this->_request->getPost();
            if (\${$lc}Form->isValid(\$params)) {
                \${$lc}Mapper->save({$entity}::create(\$params));
                \$this->_redirect('/{$lowerDashedPlural}');
            }
        }
        \$this->view->form = \${$lc}Form;";
    }
    
    protected static function _getNewActionContent($entity)
    {
        return "\$this->view->form = new {$entity}Form;";
    }
    
    protected static function _getEditActionContent($entity)
    {
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        
        return "\${$lc}Mapper = new {$entity}Mapper;
        \${$lc}Form = new {$entity}Form;
        
        \${$lc} = \${$lc}Mapper->find(\$this->_request->id);
        
        if (!\${$lc}) {
            \$this->_redirect('/error/error');
        }
        
        \${$lc}Form->populate(\${$lc}->toArray());
        
        \$this->view->id = \$this->_request->id;
        \$this->view->form = \${$lc}Form;";
    }
    
    protected static function _getUpdateActionContent($entity)
    {
        $camelCaseToDash = new CamelCaseToDash;
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        $lowerDashedPlural = $camelCaseToDash->filter($plural);
        
        return "\${$lc}Form = new {$entity}Form;
        
        if (\$this->_request->isPost()) {
            \$params = \$this->_request->getPost();
            \${$lc}Mapper = new {$entity}Mapper;
            
            if (\${$lc}Form->isValid(\$params)) {
                \$params['id'] = (int)\$this->_request->id;
                \${$lc}Mapper->save({$entity}::create(\$params));
                \$this->_redirect('/{$lowerDashedPlural}/show/id/' . (int)\$this->_request->id);
            }
        }
        \$this->view->form = \$postForm;";
    }
    
    protected static function _getDeleteActionContent($entity)
    {
        $camelCaseToDash = new CamelCaseToDash;
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        $lowerDashedPlural = $camelCaseToDash->filter($plural);
        
        return "\${$lc}Mapper = new {$entity}Mapper;
        \${$lc} = \${$lc}Mapper->find(\$this->_request->id);
        \${$lc}Mapper->delete(\${$lc});
        \$this->_redirect('/{$lowerDashedPlural}');";
    }
    
    protected static function _getShowActionContent($entity)
    {
        $lcFirst = new LCFirst;
        $lc = $lcFirst->filter($entity);
        
        return "\${$lc}Mapper = new {$entity}Mapper;
        \$this->view->{$lc} = \${$lc}Mapper->find(\$this->_request->id);";
    }
}