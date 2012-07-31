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
use Zend_Tool_Project_Provider_Controller as ControllerProvider;
use Zend_Tool_Project_Provider_View as ViewProvider;
use Zend_Tool_Project_Provider_Exception as ProviderException;

require_once 'PHPSpec/Context/Zend/Filter/LCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/Pluralize.php';
require_once 'PHPSpec/Context/Zend/Tool/Context/Form.php';
require_once 'PHPSpec/Context/Zend/Tool/Context/ViewContent.php';
require_once 'PHPSpec/Context/Zend/Tool/Context/ActionMethod.php';
require_once 'PHPSpec/Context/Zend/Tool/Context/ViewSpec.php';
require_once 'PHPSpec/Context/Zend/Tool/Context/ControllerSpec.php';

use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst;
use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;
use PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;
use PHPSpec_Context_Zend_Tool_Context_Form as FormResource;
use PHPSpec_Context_Zend_Tool_Context_ViewContent as ViewContent;
use PHPSpec_Context_Zend_Tool_Context_ViewSpec as ViewSpec;
use PHPSpec_Context_Zend_Tool_Context_ActionMethod as ActionMethod;
use PHPSpec_Context_Zend_Tool_Provider_ControllerSpec as ControllerSpec;
use PHPSpec_Context_Zend_Tool_Provider_ModelSpec as ModelSpec;
use PHPSpec_Context_Zend_Tool_Context_ControllerSpec as ControllerSpecContext;

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
    
    protected static $_scaffoldActions = array('index', 'add', 'new', 'edit', 'update', 'delete', 'show');
    
    /**
     * Generate action of the scaffold provider
     *
     * @return void 
     */
    public function generate($entity, $commaSeparatedFields = '', $module = null)
    {
        $controller = new ControllerSpec;
        $model = new ModelSpec;
        $pluralize = new Pluralize;
        
        $controller->setRegistry($this->_registry);
        $model->setRegistry($this->_registry);
        $profile = $this->_loadProfile();
        
        $entityPlural = $pluralize->filter($entity);
        
        $model->create($entity, $commaSeparatedFields);
        $controller->create($entityPlural, implode(',', self::$_scaffoldActions), $module);
        FormResource::create($this->_registry, $profile, $entity, $commaSeparatedFields, $module);
        
        self::_createControllerViewsAndActions($this->_registry, $profile, $entity, $commaSeparatedFields, $module);
        self::_createViewSpecs($entity, $commaSeparatedFields, $module);
        self::_createControllerMacros($this->_registry->getResponse());
        self::_createControllerSpec($entity, $commaSeparatedFields, $module);
    }
    
    protected static function _createControllerSpec($entity, $fields, $module)
    {
        $ds = DIRECTORY_SEPARATOR;
        $module = $module === null ? '' : $module . $ds;
        $controllerSpecPath = ".{$ds}spec{$ds}{$module}controllers";
        if (!file_exists($controllerSpecPath)) {
            mkdir($controllerSpecPath);
        }
        
        ControllerSpecContext::create($controllerSpecPath, $entity, $fields, $module);
    }
    
    protected static function _createViewSpecs($entity, $commaSeparatedFields, $module)
    {
        $filter = new CamelCaseToSeparator('-');
        $pluralize = new Pluralize;
        $ds = DIRECTORY_SEPARATOR;
        $module = $module === null ? '' : $module . $ds;
        $controllerDir = strtolower($pluralize->filter($filter->filter($entity)));
        
        $viewSpecPath = ".{$ds}spec{$ds}views{$ds}$module$controllerDir";
        if (!file_exists($viewSpecPath)) {
            mkdir($viewSpecPath);
        }
        
        foreach (self::$_scaffoldActions as $view) {
            ViewSpec::create($viewSpecPath, $entity, $commaSeparatedFields, $view, $module);
        }
    }
    
    protected static function _createControllerMacros($response)
    {
        $ds = DIRECTORY_SEPARATOR;
        $specDir = ".{$ds}spec{$ds}";
        if (file_exists("{$specDir}ControllersMacros.php")) {
            return;
        }
        
        file_put_contents("{$specDir}ControllersMacros.php", <<<MACRO
<?php

class ControllersMacros extends \PHPSpec\Macro
{
    // here you can use your preferred IoC container
    public function inject(\$alias, \$object)
    {
        \$container = Zend_Registry::getInstance();
        \$container->\$alias = \$object;
    }
    
    // ah, the joy of static containers :~}
    public function clearContainer()
    {
        Zend_Registry::_unsetInstance();
    }
}
MACRO
);
        $specHelperPath = "{$specDir}SpecHelper.php";
        $specHelper = file_get_contents($specHelperPath);
        $specHelper = str_replace('<?php' . PHP_EOL, '<?php

$configure->includeMacros(__DIR__ . "/ControllersMacros.php");', $specHelper);
        file_put_contents($specHelperPath, $specHelper);

        $response->appendContent(
            'Creating a controllers\'s macro file in location ' .
            realpath (".{$ds}spec{$ds}ControllersMacros.php")
        );
    }
    
    protected static function _createControllerViewsAndActions($registry, $profile, $entity, $commaSeparatedFields, $module)
    {
        $pluralize = new Pluralize;
        $entityPlural = $pluralize->filter($entity);
        
        $controllerResource = ControllerProvider::createResource(
            $profile, $entityPlural, $module
        );
        $controllerPath = $controllerResource->getContext()->getPath();
        self::_addAliasesForTheController($entity, $controllerPath);
            
        foreach (self::$_scaffoldActions as $action) {
            if ($action !== 'add' && $action !== 'update' && $action !== 'delete') {
                ViewContent::create($registry, $profile, $action, $entity, $commaSeparatedFields, $module);
            }
            
            ActionMethod::create($action, $entity, $controllerPath);
        }
    }
    
    protected function _addAliasesForTheController($entity, $controllerPath)
    {
        $controllerContent = file_get_contents($controllerPath);
        
        file_put_contents(
                $controllerPath, str_replace(
            "<?php" . PHP_EOL . PHP_EOL . "class",
            "<?php" . PHP_EOL . PHP_EOL .
            "use Application_Model_{$entity} as {$entity};" . PHP_EOL . PHP_EOL .
            "class",
            $controllerContent));
            
        $controllerContent = file_get_contents($controllerPath);
        
        file_put_contents(
                $controllerPath, str_replace(
            PHP_EOL . "}",
            self::_getContainerHack() . PHP_EOL . "}",
            $controllerContent));
    }
    
    protected function _loadProfile($loadProfileFlag = self::NO_PROFILE_THROW_EXCEPTION, $projectDirectory = null, $searchParentDirectories = true)
    {
        $foundPath = $this->_findProfileDirectory($projectDirectory, $searchParentDirectories);

        if ($foundPath == false) {
            if ($loadProfileFlag == self::NO_PROFILE_THROW_EXCEPTION) {
                throw new ProviderException('A project profile was not found.');
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
    
        protected static function _getContainerHack()
    {
        return "
    // please replace the following with a proper IoC container :~)
    
    public function get(\$object, \$namespace = 'Application.')
    {
        \$container = Zend_Registry::getInstance();
        if (!isset(\$container[\$namespace . \$object]) ||
            \$container[\$namespace . \$object] === null) {
            \$classNameFilter = new \Zend_Filter_Word_SeparatorToSeparator('.', '_');
            \$className = \$classNameFilter->filter(\$namespace . \$object);
            \$container[\$namespace . \$object] = new \$className;
        }
        return \$container[\$namespace . \$object];
    }";
    }
}