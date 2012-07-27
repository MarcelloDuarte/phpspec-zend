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
 * @package   PHPSpec_Zend
 * @copyright Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                    Marcello Duarte
 * @license   http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */

use Zend_Tool_Framework_Provider_Abstract as ProviderAbstract;
use Zend_Filter_Word_CamelCaseToSeparator as CamelCaseToSeparator;
use Zend_Filter_Word_CamelCaseToDash as CamelCaseToDash;
use Zend_Tool_Project_Provider_View as ViewProvider;
use Zend_Tool_Project_Provider_Controller as ControllerProvider;
use Zend_Tool_Project_Provider_Form as FormProvider;

require_once 'PHPSpec/Context/Zend/Filter/LCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/Pluralize.php';

use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst;
use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;
use PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;


/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Provider_Scaffold extends ProviderAbstract
{
    const NO_PROFILE_THROW_EXCEPTION = true;
    /**
     * Generate action of the scaffold provider
     *
     * @return void 
     */
    public function generate($entity, $commaSeparatedFields = '', $module = null)
    {
        $controller = new PHPSpec_Context_Zend_Tool_Provider_ControllerSpec;
        $model = new PHPSpec_Context_Zend_Tool_Provider_ModelSpec;
        $view = new PHPSpec_Context_Zend_Tool_Provider_ViewSpec;
        $form = new Zend_Tool_Project_Provider_Form;
        
        $controller->setRegistry($this->_registry);
        $model->setRegistry($this->_registry);
        $view->setRegistry($this->_registry);
        $form->setRegistry($this->_registry);
        
        $pluralize = new Pluralize;
        $entityPlural = $pluralize->filter($entity);
        $model->create($entity, $commaSeparatedFields);
        $controller->create($entityPlural, 'index,add,new,edit,update,delete,show', $module);
        $form->create("{$entity}Form");
        
        $profile = $this->_loadProfile();
        
        $formResource = FormProvider::createResource($profile, "{$entity}Form", $module);
        $formPath = $formResource->getContext()->getPath();
        $formContent = file_get_contents($formPath);
        file_put_contents($formPath, str_replace(
            '        /* Form Elements & Other Definitions Here ... */',
            $this->_getFormElements($commaSeparatedFields),
            $formContent
        ));
        
        $controllerResource = ControllerProvider::createResource(
            $profile, $entityPlural, $module
        );
        $controllerPath = $controllerResource->getContext()->getPath();
        $controllerContent = file_get_contents($controllerPath);
        
        file_put_contents(
                $controllerPath, str_replace(
            "<?php\n\nclass",
            "<?php\n\n" .
            "use Application_Model_{$entity}Mapper as {$entity}Mapper;\n" .
            "use Application_Model_{$entity} as {$entity};\n" .
            "use Application_Form_{$entity}Form as {$entity}Form;\n\n" .
            "class",
            $controllerContent));
        
        $actions = array('index', 'add', 'new', 'edit', 'update', 'delete', 'show');
        foreach ($actions as $action) {
            
            if ($action !== 'add' && $action !== 'update' && $action !== 'delete') {
                $view->create($action, $entityPlural);
                $viewResource = ViewProvider::createResource($profile, $action, $entityPlural, $module);
                $path = $viewResource->getContext()->getPath();
                $getViewContentMethod = "_get{$action}ViewContent";
                file_put_contents(
                $path,
                $this->$getViewContentMethod($entity, $this->_explodeFields($commaSeparatedFields))
                );
            }
            
            $controllerContent = file_get_contents($controllerPath);
            
            $getActionContentMethod = "_get{$action}ActionContent";
            file_put_contents(
                $controllerPath,
                str_replace(
                    "{$action}Action()\n    {\n        // action body",
                    "{$action}Action()\n    {\n        " . $this->$getActionContentMethod($entity),
                    $controllerContent));
        }

    }
    
    protected function _getFormElements($fields)
    {
        $upperFirst = new UCFirst;
        $camelCaseToSpace = new CamelCaseToSeparator;
        
        $elements = '';
        $fields = explode(',', $fields);
        foreach ($fields as $field) {
            list($name, $type) = strpos($field, ':') ? explode(':', $field) : array($field, 'string');
            $ucFirst = $upperFirst->filter($name);
            $label = $camelCaseToSpace->filter($ucFirst);
            switch ($type) {
                case 'text':
                    $elements .= "        \$this->addElement('textarea', '{$name}', array('label' => '{$label}'));" . PHP_EOL;
                    break;
                default:
                    $elements .= "        \$this->addElement('text', '{$name}', array('label' => '{$label}'));" . PHP_EOL;
                    break;
            }
        }
        if (!empty($elements)) {
            $elements .= "        \$this->addElement('submit', 'Save');" . PHP_EOL;
        }
        return $elements;
    }
    
    protected function _getIndexActionContent($entity)
    {
        $lcFirst = new LCFirst;
        $pluralize = new Pluralize;
        $lc = $lcFirst->filter($entity);
        $plural = $pluralize->filter($lc);
        
        return "\${$lc}Mapper = new {$entity}Mapper;
        \$this->view->{$plural} = \${$lc}Mapper->fetchAll();";
    }
    
    protected function _getAddActionContent($entity)
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
    
    protected function _getNewActionContent($entity)
    {
        return "\$this->view->form = new {$entity}Form;";
    }
    
    protected function _getEditActionContent($entity)
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
    
    protected function _getUpdateActionContent($entity)
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
    
    protected function _getDeleteActionContent($entity)
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
    
    protected function _getShowActionContent($entity)
    {
        $lcFirst = new LCFirst;
        $lc = $lcFirst->filter($entity);
        
        return "\${$lc}Mapper = new {$entity}Mapper;
        \$this->view->{$lc} = \${$lc}Mapper->find(\$this->_request->id);";
    }
    
    protected function _getIndexViewContent($entity, $fields)
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
    
    protected function _getNewViewContent($entity)
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
    
    protected function _getEditViewContent($entity, $fields)
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
    
    protected function _getShowViewContent($entity, $fields)
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
    
    private function _explodeFields($fields)
    {
        $fields = explode(',', $fields);
        return array_map(function($each){
            return substr($each, 0, strpos($each, ':'));
        }, $fields);
    }
    
    protected function _loadProfile($loadProfileFlag = self::NO_PROFILE_THROW_EXCEPTION, $projectDirectory = null, $searchParentDirectories = true)
    {
        $foundPath = $this->_findProfileDirectory($projectDirectory, $searchParentDirectories);

        if ($foundPath == false) {
            if ($loadProfileFlag == self::NO_PROFILE_THROW_EXCEPTION) {
                throw new Zend_Tool_Project_Provider_Exception('A project profile was not found.');
            } else {
                return false;
            }
        }

        $profile = new Zend_Tool_Project_Profile();
        $profile->setAttribute('projectDirectory', $foundPath);
        $profile->loadFromFile();
        $this->_loadedProfile = $profile;
        return $profile;
    }
    
    protected function _findProfileDirectory($projectDirectory = null, $searchParentDirectories = true)
    {
        // use the cwd if no directory was provided
        if ($projectDirectory == null) {
            $projectDirectory = getcwd();
        } elseif (realpath($projectDirectory) == false) {
            throw new Zend_Tool_Project_Provider_Exception('The $projectDirectory supplied does not exist.');
        }

        $profile = new Zend_Tool_Project_Profile();

        $parentDirectoriesArray = explode(DIRECTORY_SEPARATOR, ltrim($projectDirectory, DIRECTORY_SEPARATOR));
        while ($parentDirectoriesArray) {
            $projectDirectoryAssembled = implode(DIRECTORY_SEPARATOR, $parentDirectoriesArray);

            if (DIRECTORY_SEPARATOR !== "\\") {
                $projectDirectoryAssembled = DIRECTORY_SEPARATOR . $projectDirectoryAssembled;
            }

            $profile->setAttribute('projectDirectory', $projectDirectoryAssembled);
            if ($profile->isLoadableFromFile()) {
                unset($profile);
                return $projectDirectoryAssembled;
            }

            // break after first run if we are not to check upper directories
            if ($searchParentDirectories == false) {
                break;
            }

            array_pop($parentDirectoriesArray);
        }

        return false;
    }
}