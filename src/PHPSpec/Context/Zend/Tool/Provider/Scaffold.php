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

require_once 'PHPSpec/Context/Zend/Filter/LCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/Pluralize.php';
require_once 'PHPSpec/Context/Zend/Tool/Context/Form.php';
require_once 'PHPSpec/Context/Zend/Tool/Context/ViewContent.php';
require_once 'PHPSpec/Context/Zend/Tool/Context/ActionMethod.php';

use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst;
use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;
use PHPSpec_Context_Zend_Filter_Pluralize as Pluralize;
use PHPSpec_Context_Zend_Tool_Context_Form as FormResource;
use PHPSpec_Context_Zend_Tool_Context_ViewContent as ViewContent;
use PHPSpec_Context_Zend_Tool_Context_ActionMethod as ActionMethod;

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
        $controller = new PHPSpec_Context_Zend_Tool_Provider_ControllerSpec;
        $model = new PHPSpec_Context_Zend_Tool_Provider_ModelSpec;
        $pluralize = new Pluralize;
        
        $controller->setRegistry($this->_registry);
        $model->setRegistry($this->_registry);
        
        $entityPlural = $pluralize->filter($entity);
        
        $profile = $this->_loadProfile();
        
        $model->create($entity, $commaSeparatedFields);
        $controller->create($entityPlural, 'index,add,new,edit,update,delete,show', $module);
        FormResource::create($this->_registry, $profile, $entity, $commaSeparatedFields, $module);
        
        self::_createControllerViewsAndActions($this->_registry, $profile, $entity, $commaSeparatedFields, $module);
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
            "<?php\n\nclass",
            "<?php\n\n" .
            "use Application_Model_{$entity}Mapper as {$entity}Mapper;\n" .
            "use Application_Model_{$entity} as {$entity};\n" .
            "use Application_Form_{$entity}Form as {$entity}Form;\n\n" .
            "class",
            $controllerContent));
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