<?php

class PHPSpec_Context_Zend_Tool_Provider_PHPSpec
    extends Zend_Tool_Project_Provider_Abstract
{
    
    public function generate()
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);
        
        if (is_dir('spec')) {
            throw new Zend_Tool_Project_Provider_Exception(
                'You have already generated PHPSpec\'s necessary files.'
            );
        }
        
        $response = $this->_registry->getResponse();
        
        $response->appendContent("      create", array('separator' => false, 'color' => 'green'));
        $response->appendContent("  spec");
        mkdir('spec');
        
        $response->appendContent("      create", array('separator' => false, 'color' => 'green'));
        $response->appendContent("  spec/SpecHelper.php");
        file_put_contents('spec/SpecHelper.php', $this->_getSpecHelperText());
        
        $response->appendContent("      create", array('separator' => false, 'color' => 'green'));
        $response->appendContent("  spec/.phpspec");
        touch('spec/.phpspec');
        
        $response->appendContent("      create", array('separator' => false, 'color' => 'green'));
        $response->appendContent("  spec/models");
        mkdir('spec/models');
        
        $response->appendContent("      create", array('separator' => false, 'color' => 'green'));
        $response->appendContent("  spec/views");
        mkdir('spec/views');
        
        $response->appendContent("      create", array('separator' => false, 'color' => 'green'));
        $response->appendContent("  spec/controllers");
        mkdir('spec/controllers');
    }
    
    protected function _getSpecHelperText()
    {
        return <<<HELPER
<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();

\$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
\$application->bootstrap();

// require_once 'Yadif/Container.php';
require_once 'PHPUnit/Autoload.php';

require_once 'Mockery/Loader.php';
require_once 'Hamcrest/hamcrest.php';
\$loader = new \Mockery\Loader;
\$loader->register();
HELPER;
    }
    
}