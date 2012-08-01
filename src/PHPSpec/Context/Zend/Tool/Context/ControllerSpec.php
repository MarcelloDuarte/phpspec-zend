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
use PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;
use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst;
use PHPSpec_Context_Zend_Filter_LCFirst as UCFirst;

use Zend_Filter_Word_CamelCaseToSeparator as CamelCaseToSeparator;
use Zend_Filter_Word_DashToCamelCase as DashToCamelCase;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Context_ControllerSpec
{
    /**
     * Creates the scaffolded Controller spec
     *
     * @param string $path 
     * @param string $entity 
     * @param array  $fields 
     * @param string $module 
     */
    public static function create($path, $entity, $fields, $module)
    {
        $pluralize = new Pluralize;
        $entityPlural = $pluralize->filter($entity);
        $controllerSpecPath = $path . DIRECTORY_SEPARATOR .
                              $entityPlural . 'ControllerSpec.php';
        
        file_put_contents(
            $controllerSpecPath, self::content($entity, $fields, $module)
        );
    }
    
    /**
     * Renders the content of the scaffolded controller spec
     *
     * @param string $entity 
     * @param array  $fields 
     * @param string $module 
     * @return string  
     */
    protected static function content($entity, $fields, $module)
    {
        $pluralize = new Pluralize;
        $dashIt = new CamelCaseToSeparator;
        $camelize = new DashToCamelCase;
        $lcFirst = new LCFirst;
        $ucFirst = new UCFirst;
        
        if ($module === null) {
            $module = $camelize->filter($module);
            $namespace = 'namespace ' . $ucFirst->filter($module) . ';' .
            PHP_EOL;
        } else {
            $namespace = '';
        }
        
        $entityPlural = $pluralize->filter($entity);
        $lcFirstEntity = $lcFirst->filter($entity);
        $lcFirstEntityPlural = $lcFirst->filter($entityPlural);
        $entityPluralDashed = $dashIt->filter($entityPlural);
        $smallCasedDashedPlural = strtolower($entityPluralDashed);
        
        $fieldsAndValues = $rendered = '';
        $fields = explode(',', $fields);
        foreach ($fields as $field) {
            $fieldAndType = explode(':', $field);
            list($field, $type) = count($fieldAndType) === 2 ?
                                  $fieldAndType :
                                  array($field, 'string');
            $fieldType = $type === 'text' ? 'textarea' : 'input';
            $fieldsAndValues .= "'$field' => 'some $field'," . PHP_EOL .
                                "            ";
        }
        
        $appForm = "Application_Form_{$entity}Form";
        
        return <<<CONTROLLER
<?php
$namespace

class Describe{$entityPlural}Controller extends \PHPSpec\Context\Zend\Controller
{
    // GET index
    function itAssignsAll{$entityPlural}ToList()
    {
        \$mapper = \$this->stub('Application_Model_{$entity}Mapper');
        \$mapper->shouldReceive('fetchAll')->andReturn(array());
        \$this->inject('Application.Model.{$entity}Mapper', \$mapper);
        
        \$this->get('{$smallCasedDashedPlural}/index');
        \$this->assigns('{$lcFirstEntityPlural}')->should->be(array());
    }
    
    // GET show
    function itAssignsTheRequested{$entity}ToBeShown()
    {
        \${$lcFirstEntity} = \$this->stub('Application_Model_{$entity}');
        \$mapper = \$this->stub('Application_Model_{$entity}Mapper');
        \$mapper->shouldReceive('find')->andReturn(\${$lcFirstEntity});
        \$this->inject('Application.Model.{$entity}Mapper', \$mapper);
        
        \$this->get('{$smallCasedDashedPlural}/show');
        \$this->assigns('{$lcFirstEntity}')->should->be(\${$lcFirstEntity});
    }
    
    // GET edit
    function itPopulatesTheEditFormWithA{$entity}()
    {
        \${$lcFirstEntity}Array = array(
            'id' => 1,
            $fieldsAndValues
        );
        \${$lcFirstEntity} = \$this->stub('Application_Model_{$entity}');
        \${$lcFirstEntity}->shouldReceive('toArray')
             ->andReturn(\${$lcFirstEntity}Array);
        
        \$mapper = \$this->stub('Application_Model_{$entity}Mapper');
        \$mapper->shouldReceive('find')->andReturn(\${$lcFirstEntity});
        \$this->inject('Application.Model.{$entity}Mapper', \$mapper);
        
        \$form = \$this->stub('Application_Form_{$entity}Form');
        \$form->shouldReceive('populate')->with(\${$lcFirstEntity}Array);
        \$this->inject('Application.Form.{$entity}Form', \$form);
        \$this->get('{$smallCasedDashedPlural}/edit/id/1');
        
        \$this->assigns('id')->should->be('1');
        \$this->assigns('form')->should->be(\$form);
    }
    
    // GET new
    function itDisplaysThe{$entity}Form()
    {
        \$this->get('{$smallCasedDashedPlural}/new');
        \$this->assigns('form')->should->beAnInstanceOf('{$appForm}');
    }

    // POST add
    function itAddsTheValid{$entity}()
    {
        \$mapper = \$this->stub('Application_Model_{$entity}Mapper');
        \$mapper->shouldReceive('save')->once();
        \$this->inject('Application.Model.{$entity}Mapper', \$mapper);
        
        \$form = \$this->stub('Application_Form_{$entity}Form');
        \$form->shouldReceive('isValid')->andReturn(true);
        \$this->inject('Application.Form.{$entity}Form', \$form);
        
        \$this->post('{$smallCasedDashedPlural}/add', array(
            $fieldsAndValues
        ));
    }
    
    // POST update
    function itUpdatesTheValid{$entity}()
    {
        \$mapper = \$this->stub('Application_Model_{$entity}Mapper');
        \$mapper->shouldReceive('save');
        \$this->inject('Application.Model.{$entity}Mapper', \$mapper);
        
        \$form = \$this->stub('Application_Form_{$entity}Form');
        \$form->shouldReceive('isValid')->andReturn(true);
        \$this->inject('Application.Form.{$entity}Form', \$form);
        
        \$this->post('{$smallCasedDashedPlural}/update/id/1', array(
            $fieldsAndValues
        ));
    }
    
    // GET delete
    function itDeletesThe{$entity}()
    {
        \$mapper = \$this->stub('Application_Model_{$entity}Mapper');
        \$mapper->shouldReceive('delete');
        \$this->inject('Application.Model.{$entity}Mapper', \$mapper);
        
        \$this->get('{$smallCasedDashedPlural}/delete/id/1');
        \$this->response->should->redirectTo('/{$lcFirstEntityPlural}');
    }
    
    // this is because we are using the registry as container
    // not needed with a proper non-static IoC container
    function after()
    {
        \$this->clearContainer();
    }

}
CONTROLLER;
    }
}