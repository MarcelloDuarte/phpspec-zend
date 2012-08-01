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
require_once 'PHPSpec/Context/Zend/Filter/Pluralize.php';

use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst;
use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;
use PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;

use Zend_Filter_Word_CamelCaseToSeparator as CamelCaseToSeparator;
use Zend_Filter_Word_CamelCaseToDash as CamelCaseToDash;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Context_ActionMethod
{
    /**
     * Updates the controller with the action body
     *
     * @param string $name 
     * @param string $entity 
     * @param string $controllerPath
     */
    public static function create($name, $entity, $controllerPath)
    {
        $controllerContent = file_get_contents($controllerPath);
            
        $getActionContentMethod = "_get{$name}ActionContent";
        file_put_contents(
            $controllerPath,
            str_replace(
                "{$name}Action()\n    {\n        // action body",
                "{$name}Action()\n    {\n        " .
                self::$getActionContentMethod($entity),
                $controllerContent
            )
        );
    }
    
    /**
     * Creates the scaffolded index action
     *
     * @param string $entity 
     * @return string
     */
    protected static function _getIndexActionContent($entity)
    {
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        
        return "\${$lc}Mapper = \$this->get('Model.{$entity}Mapper');
        \$this->view->{$plural} = \${$lc}Mapper->fetchAll();";
    }
    
    /**
     * Creates the scaffolded add action
     *
     * @param string $entity 
     * @return string
     */
    protected static function _getAddActionContent($entity)
    {
        $camelCaseToDash = new CamelCaseToDash;
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        $lowerDashedPlural = $camelCaseToDash->filter($plural);
        
        return "\${$lc}Form = \$this->get('Form.{$entity}Form');
        \${$lc}Mapper = \$this->get('Model.{$entity}Mapper');
        
        if (\$this->_request->isPost()) {
            \$params = \$this->_request->getPost();
            if (\${$lc}Form->isValid(\$params)) {
                \${$lc}Mapper->save({$entity}::create(\$params));
                \$this->_redirect('/{$lowerDashedPlural}');
            }
        }
        \$this->view->form = \${$lc}Form;";
    }
    
    /**
     * Creates the scaffolded new action
     *
     * @param string $entity 
     * @return string
     */
    protected static function _getNewActionContent($entity)
    {
        return "\$this->view->form = \$this->get('Form.{$entity}Form');";
    }
    
    /**
     * Creates the scaffolded edit action
     *
     * @param string $entity 
     * @return string
     */
    protected static function _getEditActionContent($entity)
    {
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        
        return "\${$lc}Mapper = \$this->get('Model.{$entity}Mapper');
        \${$lc}Form = \$this->get('Form.{$entity}Form');
        
        \${$lc} = \${$lc}Mapper->find(\$this->_request->id);
        
        if (!\${$lc}) {
            \$this->_redirect('/error/error');
        }
        
        \${$lc}Form->populate(\${$lc}->toArray());
        
        \$this->view->id = \$this->_request->id;
        \$this->view->form = \${$lc}Form;";
    }
    
    /**
     * Creates the scaffolded update action
     *
     * @param string $entity 
     * @return string
     */
    protected static function _getUpdateActionContent($entity)
    {
        $camelCaseToDash = new CamelCaseToDash;
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        $lowerDashedPlural = $camelCaseToDash->filter($plural);
        
        return "\${$lc}Form = \$this->get('Form.{$entity}Form');
        
        if (\$this->_request->isPost()) {
            \$params = \$this->_request->getPost();
            \${$lc}Mapper = \$this->get('Model.{$entity}Mapper');
            
            if (\${$lc}Form->isValid(\$params)) {
                \$params['id'] = (int)\$this->_request->id;
                \${$lc}Mapper->save({$entity}::create(\$params));
                \$this->_redirect('/{$lowerDashedPlural}/show/id/' . " .
                "(int)\$this->_request->id);
            }
        }
        \$this->view->form = \${$lc}Form;";
    }
    
    /**
     * Creates the scaffolded delete action
     *
     * @param string $entity 
     * @return string
     */
    protected static function _getDeleteActionContent($entity)
    {
        $camelCaseToDash = new CamelCaseToDash;
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        $lowerDashedPlural = $camelCaseToDash->filter($plural);
        
        return "\${$lc}Mapper = \$this->get('Model.{$entity}Mapper');
        \${$lc} = \${$lc}Mapper->find(\$this->_request->id);
        \${$lc}Mapper->delete(\${$lc});
        \$this->_redirect('/{$lowerDashedPlural}');";
    }
    
    /**
     * Creates the scaffolded show action
     *
     * @param string $entity 
     * @return string
     */
    protected static function _getShowActionContent($entity)
    {
        $lcFirst = new LCFirst;
        $lc = $lcFirst->filter($entity);
        
        return "\${$lc}Mapper = \$this->get('Model.{$entity}Mapper');
        \$this->view->{$lc} = \${$lc}Mapper->find(\$this->_request->id);";
    }
}