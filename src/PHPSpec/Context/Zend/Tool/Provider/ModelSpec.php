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
 
require_once 'PHPSpec/Context/Zend/Tool/Context/ModelFile.php';
require_once 'PHPSpec/Context/Zend/Filter/LCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/UCFirst.php';
require_once 'PHPSpec/Context/Zend/Filter/Pluralize.php';

use PHPSpec_Context_Zend_Filter_LCFirst as LCFirst,
    PHPSpec_Context_Zend_Filter_UCFirst as UCFirst,
    PHPSpec_Context_Zend_Filter_Pluralize as Pluralize,
    Zend_Tool_Project_Provider_Exception as ProviderException,
    Zend_Tool_Project_Provider_DbTable as DbTableProvider;
    
/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Provider_ModelSpec
    extends Zend_Tool_Project_Provider_Model
    implements Zend_Tool_Framework_Provider_Pretendable
{
    public static function createResource(Zend_Tool_Project_Profile $profile,
        $modelName, $fields = array(), $moduleName = null)
    {
        if (!is_string($modelName)) {
            throw new ProviderException(
                'Zend_Tool_Project_Provider_Model::createResource() expects' .
                ' \"modelName\" is the name of a model resource to create.'
            );
        }

        $modelsDirectory = self::_getModelsDirectoryResource(
            $profile, $moduleName
        );
        if (!$modelsDirectory) {
            if ($moduleName) {
                $exceptionMessage = 'A model directory for module "' .
                                    $moduleName . '" was not found.';
            } else {
                $exceptionMessage = 'A model directory was not found.';
            }
            throw new Zend_Tool_Project_Provider_Exception($exceptionMessage);
        }

        $newModel = $modelsDirectory->createResource(
            new PHPSpec_Context_Zend_Tool_Context_ModelFile,
            array(
                'modelName' => $modelName,
                'moduleName' => $moduleName,
                'fields' => $fields
            )
        );

        return $newModel;
    }

    /**
     * hasResource()
     *
     * @param Zend_Tool_Project_Profile $profile
     * @param string $modelName
     * @param string $moduleName
     * @return Zend_Tool_Project_Profile_Resource
     */
    public static function hasResource(
        Zend_Tool_Project_Profile $profile, $modelName, $moduleName = null)
    {
        if (!is_string($modelName)) {
            throw new ProviderException(
                'Zend_Tool_Project_Provider_Model::createResource() ' .
                'expects \"modelName\" is the name of a model resource to ' .
                'check for existence.'
            );
        }

        $modelsDirectory = self::_getModelsDirectoryResource(
            $profile, $moduleName
        );
        
        if (!$modelsDirectory instanceof Zend_Tool_Project_Profile_Resource) {
            return false;
        }
        
        return (($modelsDirectory->search(
            array('modelFile' => array('modelName' => $modelName))
        )) instanceof Zend_Tool_Project_Profile_Resource);
    }

    /**
     * _getModelsDirectoryResource()
     *
     * @param Zend_Tool_Project_Profile $profile
     * @param string $moduleName
     * @return Zend_Tool_Project_Profile_Resource
     */
    protected static function _getModelsDirectoryResource(
        Zend_Tool_Project_Profile $profile, $moduleName = null)
    {
        $profileSearchParams = array();

        if ($moduleName != null && is_string($moduleName)) {
            $profileSearchParams = array(
                'modulesDirectory',
                'moduleDirectory' => array('moduleName' => $moduleName)
            );
        }

        $profileSearchParams[] = 'modelsDirectory';

        return $profile->search($profileSearchParams);
    }

    /**
     * Create a new model
     *
     * @param string $name
     * @param string $module
     */
    public function create($name, $commaSeparatedFields = '', $module = null)
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);

        if (!is_dir('spec')) {
            throw new ProviderException(
                'Please run zf generate phpspec, to create the environment'
            );
        }

        $originalName = $name;

        $name = UCFirst::apply($name);
        $tableName = Pluralize::apply($name);
        $dbTableName = strtolower($tableName);

        // determine if testing is enabled in the project
        // Zend_Tool_Project_Provider_Test::isTestingEnabled(
        //    $this->_loadedProfile
        // );
        $testingEnabled = false; 
        $testModelResource = null;

        // Check that there is not a dash or underscore,
        // return if doesnt match regex
        if (preg_match('#[_-]#', $name)) {
            throw new ProviderException('Model names should be camel cased.');
        }

        if (self::hasResource($this->_loadedProfile, $name, $module)) {
            throw new ProviderException(
                'This project already has a model named ' . $name
            );
        }

        // get request/response object
        $request = $this->_registry->getRequest();
        $response = $this->_registry->getResponse();

        // alert the user about inline converted names
        $tense = (($request->isPretend()) ? 'would be' : 'is');

        if ($name !== $originalName) {
            $response->appendContent(
                'Note: The canonical model name that ' . $tense .
                ' used with other providers is "' . $name . '";' .
                ' not "' . $originalName . '" as supplied',
                array('color' => array('yellow'))
            );
        }


        $commaSeparatedFields = trim($commaSeparatedFields);
        $fields = empty($commaSeparatedFields) ?
                  array() :
                  explode(',', $commaSeparatedFields);

        try {
            $modelResource = self::createResource(
                $this->_loadedProfile, $name, $fields, $module
            );
            $mapperResource = parent::createResource(
                $this->_loadedProfile, $name . "Mapper", $module
            );
            $dbTableResource = DbTableProvider::createResource(
                $this->_loadedProfile,
                $tableName,
                strtolower($tableName),
                $module
            );
           
        } catch (Exception $e) {
            $response->setException($e);
            return;
        }
        
        //model spec
        $modelPath = str_replace(
            basename($modelResource->getContext()->getPath()),
            '',
            $modelResource->getContext()->getPath()
        );
        $basePath = realpath($modelPath . '/../..');
        $modelSpecPath = realpath($basePath . '/spec/models') . '/' . $name .
                         'Spec.php';
        $specContent = $this->_getSpecContent($name, $fields);
        
        // migrations
        if (!is_dir($basePath . "/db")) {
            mkdir($basePath . "/db");
        }
        if (!is_dir($basePath . "/db/migrate")) {
            mkdir($basePath . "/db/migrate");
        }
        $files = glob($basePath . "/db/migrate/*.php");
        natsort($files);
        $nextVersion = empty($files) ?
                       1 :
                       1 + (int)substr(basename(array_pop($files)), 0, 3);
        $migrationClass = "Create{$tableName}Table";
        $fileName = sprintf("%1$03d", $nextVersion, $migrationClass) .
                    "-{$migrationClass}";
        $migrationPath = $basePath . "/db/migrate/" . $fileName . ".php";
        $migrationContent = $this->_getMigrationContent(
            $migrationClass, $dbTableName, $fields
        );

        // do the creation
        if ($request->isPretend()) {

            $response->appendContent(
                'Would create a model at ' .
                $modelResource->getContext()->getPath()
            );
            $response->appendContent(
                'Would create a db table at ' .
                $dbTableResource->getContext()->getPath()
            );
            $response->appendContent(
                'Would create a mapper at ' .
                $mapperResource->getContext()->getPath()
            );
            $response->appendContent(
                'Would create a spec at ' . $modelSpecPath
            );
            $response->appendContent(
                'Would create migration scripts at ' . $migrationPath
            );

        } else {

            $response->appendContent(
                'Creating a model at ' .
                $modelResource->getContext()->getPath()
            );
            $modelResource->create();
            $response->appendContent(
                'Creating a db table at ' .
                $dbTableResource->getContext()->getPath()
            );
            $dbTableResource->create();
            $response->appendContent(
                'Creating a mapper at ' .
                $mapperResource->getContext()->getPath()
            );
            $mapperContent = $this->_getMapperContent($name);
            file_put_contents(
                $mapperResource->getContext()->getPath(), $mapperContent
            );
            
            $response->appendContent('Creating a spec at ' . $modelSpecPath);
            file_put_contents($modelSpecPath, $specContent);
            
            $response->appendContent(
                'Creating migration scripts at ' . $migrationPath
            );
            file_put_contents($migrationPath, $migrationContent);

            $this->_storeProfile();
        }

    }
    
    /**
     * undocumented function
     *
     * @param string $name 
     * @param array  $fields 
     * @return string
     */
    protected function _getSpecContent($name, $fields)
    {
        $attributes = '';
        foreach ($fields as $field) {
            $varAndType = explode(':', $field);
            list($varname, $type) = count($varAndType) > 1 ?
                                    $varAndType :
                                    array($varAndType[0], 'mixed');
            $attributes .= "            '$varname' => 'value for $varname'," .
                           PHP_EOL;
        }
        $thisSpec = '$this->spec(' . $name .
                    '::create($this->validAttributes));';
        $modelVariable = LCFirst::apply($name);
        return <<<SPEC
<?php

require_once __DIR__ . '/../SpecHelper.php';

use Application_Model_$name as $name;

class Describe$name extends \PHPSpec\Context
{
    function before()
    {
        \$this->validAttributes = array(
$attributes        );
    }
    
    function itShouldCreateANewInstanceGivenValidAttributes()
    {
        \$this->$modelVariable = $thisSpec
        \$this->{$modelVariable}->should->beValid();
    }
}

SPEC;
    }
    
    /**
     * Creates mapper content
     *
     * @param string $name 
     * @return string
     */
    protected function _getMapperContent($name)
    {
        $property = LCFirst::apply($name);
        $tableName = Pluralize::apply($name);
        return <<<MAPPER
<?php

use Application_Model_{$name} as $name;
use Application_Model_DbTable_{$tableName} as {$tableName}Dao;

class Application_Model_{$name}Mapper
{
    /**
     * Data access object. Typically a db table, but can be any data provider
     * object.
     */
    protected \$_{$property}Dao;
    
    /**
     * Gets the data access object
     */
    protected function getDao()
    {
        if (\$this->_{$property}Dao === null) {
            \$this->_{$property}Dao = new {$tableName}Dao;
        }
        return \$this->_{$property}Dao;
    }
    
    /**
     * Sets the data access object (for tests purposes) 
     *
     */
     protected function setDao(\$dao)
     {
         \$this->_{$property}Dao = \$dao;
     }
    
    /**
     * Retrives a model given entity primary key value
     */
    public function find(\$primaryKey)
    {
        \$records = \$this->getDao()->find(\$primaryKey);
        if (count(\$records)) {
            return $name::create(\$records->current()->toArray());
        }
    }
    
    /**
     * Retrives a collection (using arrays) given a condition, order, count
     * and offset
     */
    public function fetchAll(
        \$condition = null, \$order = null, \$count = null, \$offset = null)
    {
        \$records = \$this->getDao()->fetchAll(
            \$condition, \$order, \$count, \$offset
        );
        \${$property}s = array();
        while (\$records->valid()) {
            \${$property}s[] = $name::create(\$records->current()->toArray());
            \$records->next();
        }
        return \${$property}s;
    }
    
    /**
     * Persists the value of the entity in the data source
     */
    public function save($name \${$property})
    {
        if (\${$property}->getId()) {
            return \$this->getDao()->update(
                \${$property}->toArray(), 'id=' . (int)\${$property}->getId()
            );
        }
        return \$this->getDao()->insert(\${$property}->toArray());
    }
    
    /**
     * Deletes the entity from the data source
     */
    public function delete($name \${$property})
    {
        return \$this->getDao()->delete('id=' . (int)\${$property}->getId());
    }
}
MAPPER;
    }
    
    /**
     * Creates migration class content 
     *
     * @param string $migrationClass
     * @param string $tableName
     * @param array  $fields
     * @return string 
     */
    protected function _getMigrationContent(
        $migrationClass, $tableName, $fields)
    {
        $fieldsSQL = array('id int(11) PRIMARY KEY AUTO_INCREMENT');
        $tables = array();
        foreach ($fields as $field) {
             $varAndType = explode(':', $field);
             list($varname, $type) = count($varAndType) > 1 ?
                                     $varAndType :
                                     array($varAndType[0], 'mixed');
             if (strtoupper($type[0]) === $type[0]) {
                 if ($type === "DateTime") {
                     $fieldsSQL[] = "$varname datetime";
                 } else {
                     $fieldsSQL[] = "{$varname}Id int(11)";
                 }
             }
             switch ($type) {
                 case 'integer':
                     $fieldsSQL[] = "$varname int(11)";
                     break;
                 case 'string':
                     $fieldsSQL[] = "$varname varchar(255)";
                     break;
             }
        }
        
        $fieldsSQL = implode(',' . PHP_EOL . '            ', $fieldsSQL);
        
        return <<<MIGRATION
<?php

class $migrationClass  extends Akrabat_Db_Schema_AbstractChange
{
    public function up()
    {
        \$sql = 'CREATE TABLE IF NOT EXISTS $tableName (
            $fieldsSQL
        )';
        \$this->_db->query(\$sql);
    }
    
    public function down()
    {
        \$this->_db->query('DROP TABLE IF EXISTS $tableName');
    }
}
MIGRATION;
    }
}