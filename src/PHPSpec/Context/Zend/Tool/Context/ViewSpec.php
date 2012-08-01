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
use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst;
use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;
use PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;

use Zend_Filter_Word_CamelCaseToSeparator as CamelCaseToSeparator;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Context_ViewSpec
{
    /**
     * Creates the view spec content for the scaffolded views
     *
     * @param string $viewSpecPath
     * @param string $entity
     * @param string $commaSeparatedFields
     * @param string $view
     * @param string $module
     */
    public static function create($viewSpecPath, $entity,
        $commaSeparatedFields, $view, $module)
    {
        $createMethod = "create{$view}View";
        if (!method_exists(
            'PHPSpec_Context_Zend_Tool_Context_ViewSpec',
            $createMethod
        )) {
            return;
        }
        $content = self::$createMethod($entity, $commaSeparatedFields);
        
        $ucFirst = new UCFirst;
        $viewSpecFileName = $ucFirst->filter($view) . 'Spec.php';
        
        $viewSpecPath .= DIRECTORY_SEPARATOR . $viewSpecFileName;
        file_put_contents($viewSpecPath, $content);
    }
    
    /**
     * Creates the edit view
     *
     * @param string $entity
     * @param array  $fields
     * @return string
     */
    protected static function createEditView($entity, $fields)
    {
        $pluralize = new Pluralize;
        $entityPlural = $pluralize->filter($entity);
        
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
            if ($fieldType === 'textarea') {
                $rendered .= "\$this->rendered->should->haveSelector(" .
                "'{$fieldType}#{$field}', array('text' => 'some $field'));" .
                PHP_EOL . "        ";
                continue;
            }           
            $rendered .= "\$this->rendered->should->haveSelector(" .
                         "'{$fieldType}#{$field}[value~=\"some\"]');" .
                         PHP_EOL . "        ";
        }
        
        return <<<EDITVIEW
<?php

namespace $entityPlural;

use Application_Form_{$entity}Form as {$entity}Form;

use \PHPSpec\Context\Zend\View as ViewContext;

class DescribeEdit extends ViewContext
{
    function before()
    {
        \$form = new {$entity}Form;
        \$form->setView(\$this->_view)->populate(array(
            $fieldsAndValues
        ));
        \$this->assign('form', \$form);
    }
    
    function itRendersTheEdit{$entity}Form()
    {
        \$this->render();
        $rendered
    }
}
EDITVIEW;
    }
    
    /**
     * Creates the index view
     *
     * @param string $entity
     * @param array  $fields
     * @return string
     */
    protected static function createIndexView($entity, $fields)
    {
        $pluralize = new Pluralize;
        $upperFirst = new UCFirst;
        $lowerFirst = new LCFirst;
        $camelCaseToSpace = new CamelCaseToSeparator;
        
        $entityPlural = $pluralize->filter($entity);
        
        $pluralLCFirst = strtolower($entityPlural[0]) .
                               substr($entityPlural, 1);
        
        $fieldOne = $fieldTwo = $rendered = '';
        $fields = explode(',', $fields);
        foreach ($fields as $field) {
            $fieldAndType = explode(':', $field);
            list($field, $type) = count($fieldAndType) === 2 ?
                                  $fieldAndType :
                                  array($field, 'string');
            $ucFirst = $upperFirst->filter($field);
            $lcFirst = $lowerFirst->filter($field);
            $field = $camelCaseToSpace->filter($ucFirst);
            
            switch (strtolower($type)) {
                case 'datetime':
                    $date = date("Y-m-d");
                    $fieldOne .= "'get{$ucFirst}' => '2011-09-12'," .
                               PHP_EOL . "        ";
                    $fieldTwo .= "'get{$ucFirst}' => '" . $date . "'," .
                               PHP_EOL . "        ";
                    $rendered .= '$this->rendered->should->haveSelector(' .
                                 '\'tr>th\', array(\'text\' => \'' . $field .
                                 '\'));
        $this->rendered->should->haveSelector(\'tr>td\', array(' .
                                 '\'text\' => \'2011-09-12\'));
        $this->rendered->should->haveSelector(\'tr>td\', array(' .
                                 '\'text\' => \'' . $date . '\'));';
                    break;
                case 'integer':
                case 'int':
                    $fieldOne .= "'get{$ucFirst}' => 1," . PHP_EOL . "        ";
                    $fieldTwo .= "'get{$ucFirst}' => 2," . PHP_EOL . "        ";
                    $rendered .= '$this->rendered->should->haveSelector(' .
                                 '\'tr>th\', array(\'text\' => \'' . $field .
                                 '\'));
        $this->rendered->should->haveSelector(' .
                                 '\'tr>td\', array(\'text\' => \'1\'));
        $this->rendered->should->haveSelector(' .
                                 '\'tr>td\', array(\'text\' => \'2\'));';
                    break;
                default:
                    $fieldOne .= "'get{$ucFirst}' => 'one {$field}'," .
                               PHP_EOL . "        ";
                    $fieldTwo .= "'get{$ucFirst}' => 'two {$field}'," .
                               PHP_EOL . "        ";
                    $rendered .= '$this->rendered->should->haveSelector(' .
                                 '\'tr>th\', array(\'text\' => \'' . $field .
                                 '\'));
        $this->rendered->should->haveSelector(' .
                                 '\'tr>td\', array(\'text\' => \'one ' .
                                 $field . '\'));
        $this->rendered->should->haveSelector(' .
                                 '\'tr>td\', array(\'text\' => \'one ' .
                                 $field . '\'));';
            }
        }

        return <<<INDEXVIEW
<?php

namespace $entityPlural;

use \PHPSpec\Context\Zend\View as ViewContext;

class DescribeIndex extends ViewContext
{
    function before()
    {
        \$this->{$pluralLCFirst} = array(
            \$this->stub('{$entity}', array(
                'getId' => 1,
                $fieldOne
            )),
            \$this->stub('{$entity}', array(
                'getId' => 2,
                $fieldTwo
            ))
        );
    }
    
    function itRendersAListOf{$entityPlural}()
    {
        \$this->assign('{$pluralLCFirst}', \$this->{$pluralLCFirst});
        \$this->render();
        $rendered
    }
}
INDEXVIEW;
    }
    
    /**
     * Creates the new view
     *
     * @param string $entity
     * @param array  $fields
     * @return string
     */
    protected static function createNewView($entity, $fields)
    {
        $pluralize = new Pluralize;
        $entityPlural = $pluralize->filter($entity);
        
        $fields = explode(',', $fields);
        foreach ($fields as $field) {
            $fieldAndType = explode(':', $field);
            list($field, $type) = count($fieldAndType) === 2 ?
                                  $fieldAndType :
                                  array($field, 'string');
            
            if ($type === 'text') {
                $rendered = '$this->rendered->should->haveSelector(' .
                            '\'textarea#' . $field . '\');';
            } else {
                $rendered = '$this->rendered->should->haveSelector(' .
                            '\'input#' . $field . '\');';                
            }
        }
        
        return <<<NEWVIEW
<?php

namespace {$entityPlural};

use \PHPSpec\Context\Zend\View as ViewContext;
use Application_Form_{$entity}Form as {$entity}Form;

class DescribeNew extends ViewContext
{
    function before()
    {
        \$form = new {$entity}Form;
        \$form->setView(\$this->_view);
        \$this->assign('form', \$form);
    }
    
    function itRendersTheNew{$entity}Form()
    {
        \$this->render();
        
        $rendered
    }
}
NEWVIEW;
    }
    
    /**
     * Creates the show view
     *
     * @param string $entity
     * @param array  $fields
     * @return string
     */
    protected static function createShowView($entity, $fields)
    {
        $upperFirst = new UCFirst;
        $lowerFirst = new LCFirst;
        $camelCaseToSpace = new CamelCaseToSeparator;
        $pluralize = new Pluralize;
        
        $entityPlural = $pluralize->filter($entity);
        $lowerEntity = $lowerFirst->filter($entity);
        
        $data = $rendered = '';
        $fields = explode(',', $fields);
        foreach ($fields as $field) {
            $fieldAndType = explode(':', $field);
            list($field, $type) = count($fieldAndType) === 2 ?
                                  $fieldAndType :
                                  array($field, 'string');
            $ucFirst = $upperFirst->filter($field);
            $field = $camelCaseToSpace->filter($ucFirst);
            
            $data .= "'get{$ucFirst}' => 'some $field'," . PHP_EOL .
                     "            ";
            $rendered .= '$this->rendered->should->haveSelector(\'p>b\', ' .
                            'array(\'text\' => \'' . $field .':\'));
        $this->rendered->should->contain(\'some ' . $field . '\');';
        }
        
        return <<<SHOWVIEW
<?php

namespace {$entityPlural};

use \PHPSpec\Context\Zend\View as ViewContext;

class DescribeShow extends ViewContext
{
    function before()
    {
        \$this->assign('{$lowerEntity}', \$this->stub('{$entity}', array(
            'getId' => '1',
            $data
        )));
    }
    
    function itRendersThe{$entity}()
    {
        \$this->render();
        $rendered
    }
}
SHOWVIEW;
    }
}