<?php

require_once 'PHPSpec/Context/Zend/Filter/LCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/Pluralize.php';
require_once 'PHPSpec/Context/Zend/Tool/Provider/ViewSpec.php';

use PHPSpec_Context_Zend_Tool_Provider_ViewSpec as ViewSpecProvider;
use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst;
use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;
use PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;

use Zend_Tool_Project_Provider_View as ViewProvider;
use Zend_Filter_Word_CamelCaseToSeparator as CamelCaseToSeparator;
use Zend_Filter_Word_CamelCaseToDash as CamelCaseToDash;

class PHPSpec_Context_Zend_Tool_Context_ViewContent
{
    public static function create($registry, $profile, $name, $entity, $commaSeparatedFields, $module)
    {
        $view = new ViewSpecProvider;
        $pluralize = new Pluralize;
        $upperFirst = new UCFirst;
        
        $entityPlural = $upperFirst->filter($pluralize->filter($entity));
        
        $view->setRegistry($registry);
        
        $view->create($name, $entityPlural);
        $viewResource = ViewProvider::createResource($profile, $name, $entityPlural, $module);
        $path = $viewResource->getContext()->getPath();
        $getViewContentMethod = "_get{$name}ViewContent";
        file_put_contents(
            $path,
            self::$getViewContentMethod($entity, self::_explodeFields($commaSeparatedFields))
        );
    }
    
    private static function _explodeFields($fields)
    {
        $fields = explode(',', $fields);
        return array_map(function($each){
            return substr($each, 0, strpos($each, ':'));
        }, $fields);
    }
    
    protected static function _getIndexViewContent($entity, $fields)
    {
        $upperFirst = new UCFirst;
        $lowerFirst = new LCFirst;
        $camelCaseToSpace = new CamelCaseToSeparator;
        $camelCaseToDash = new CamelCaseToDash;
        $pluralize = new Pluralize;
        
        $entityLowerCase = $lowerFirst->filter($entity);
        $entityPlural = $upperFirst->filter($pluralize->filter($entity));
        $entityLowerCasePlural = $lowerFirst->filter($entityPlural);
        $entityLowerCasePluralDashed = $camelCaseToDash->filter($entityLowerCasePlural);
        
        $columns = $content = '';
        foreach ($fields as $field) {
            $ucFirst = $upperFirst->filter($field);
            $lcFirst = $lowerFirst->filter($field);
            $field = $camelCaseToSpace->filter($ucFirst);
            $columns .= PHP_EOL . "    <th>" . $field . "</th>";
            $content .= PHP_EOL . "    <td><?php echo \$this->escape(\${$entityLowerCase}->get{$ucFirst}()) ?></td>";
        }
        $columns = substr($columns, 5);
        $content = substr($content, 5);
        
        return "<h1>Listing $entityPlural</h1>

<table>
  <tr>
    $columns
    <th></th>
    <th></th>
    <th></th>
  </tr>

<?php foreach (\$this->{$entityLowerCasePlural} as \${$entityLowerCase}) : ?>
  <tr>
    $content
    <td><a href=\"<?php echo \$this->baseUrl('/{$entityLowerCasePluralDashed}/show/id/' . \${$entityLowerCase}->getId()) ?>\">Show</a></td>
    <td><a href=\"<?php echo \$this->baseUrl('/{$entityLowerCasePluralDashed}/edit/id/' . \${$entityLowerCase}->getId()) ?>\">Edit</a></td>
    <td><a href=\"<?php echo \$this->baseUrl('/{$entityLowerCasePluralDashed}/delete/id/' . \${$entityLowerCase}->getId()) ?>\">Delete</a></td>
  <tr>
<?php endforeach ?>
</table>

<a href=\"<?php echo \$this->baseUrl('{$entityLowerCasePluralDashed}/new') ?>\">New {$entity}</a>";
    }
    
    protected static function _getNewViewContent($entity)
    {
        $camelCaseToDash = new CamelCaseToDash;
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        $lowerDashedPlural = $camelCaseToDash->filter($plural);
        
        return "<h1>New {$entity}</h1>
<?php echo \$this->form->setAction('/{$lowerDashedPlural}/add') ?>

<a href=\"<?php echo \$this->baseUrl('{$lowerDashedPlural}') ?>\">Back</a>";
    }
    
    protected static function _getEditViewContent($entity, $fields)
    {
        $camelCaseToDash = new CamelCaseToDash;
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        $lowerDashedPlural = $camelCaseToDash->filter($plural);
        
        return "<h1>Edit {$entity}</h1>
<?php echo \$this->form->setAction('/{$lowerDashedPlural}/update/id/' . (int)\$this->id) ?>

<a href=\"<?php echo \$this->baseUrl('{$lowerDashedPlural}/show/id/' . (int)\$this->id) ?>\">Show</a> |
<a href=\"<?php echo \$this->baseUrl('{$lowerDashedPlural}') ?>\">Back</a>";
    }
    
    protected static function _getShowViewContent($entity, $fields)
    {
        $upperFirst = new UCFirst;
        $lowerFirst = new LCFirst;
        $camelCaseToSpace = new CamelCaseToSeparator;
        $camelCaseToDash = new CamelCaseToDash;
        $pluralize = new Pluralize;
        
        $lowerEntity = $lowerFirst->filter($entity);
        $lowerEntityPlural = $pluralize->filter($lowerEntity);
        $dashedEntityPlural = $camelCaseToDash->filter($lowerEntityPlural);
        
        $properties = '';
        foreach ($fields as $field) {
            $upper = $upperFirst->filter($field);
            $field = $camelCaseToSpace->filter($field);
            $field = $upperFirst->filter($field);
            
            $properties .= "  <p>
    <b>{$field}:</b>
    <?php echo \$this->escape(\$this->{$lowerEntity}->get{$upper}()) ?></h3>
  </p>" . PHP_EOL . PHP_EOL;
        }
        
        return "<h1>Show {$entity}</h1>

$properties

<a href=\"<?php echo \$this->baseUrl('{$dashedEntityPlural}/edit/id/' . (int)\$this->{$lowerEntity}->getId()) ?>\">Edit</a> |
<a href=\"<?php echo \$this->baseUrl('{$dashedEntityPlural}') ?>\">Back</a>";
    }
}