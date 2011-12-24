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
 * @copyright Copyright (c) 2010-2011 Pádraic Brady, Travis Swicegood,
 *                                    Marcello Duarte
 * @license   http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
 
/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2011 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Provider_Phpspec
    extends Zend_Tool_Project_Provider_Abstract
{
    
    /**
     * Generate action of the phpspec provider
     *
     * @return void 
     */
    public function generate()
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);
        
        if (is_dir('spec')) {
            throw new Zend_Tool_Project_Provider_Exception(
                'You have already generated PHPSpec\'s necessary files.'
            );
        }
        
        $response = $this->_registry->getResponse();
        
        $response->appendContent(
            "      create",
            array('separator' => false, 'color' => 'green')
        );
        $response->appendContent("  spec");
        mkdir('spec');
        
        $response->appendContent(
            "      create",
            array('separator' => false, 'color' => 'green')
        );
        $response->appendContent("  spec/SpecHelper.php");
        file_put_contents('spec/SpecHelper.php', $this->_getSpecHelperText());
        
        $response->appendContent(
            "      create",
            array('separator' => false, 'color' => 'green')
        );
        $response->appendContent("  spec/.phpspec");
        touch('spec/.phpspec');
        
        $response->appendContent(
            "      create",
            array('separator' => false, 'color' => 'green')
        );
        $response->appendContent("  spec/models");
        mkdir('spec/models');
        
        $response->appendContent(
            "      create",
            array('separator' => false, 'color' => 'green')
        );
        $response->appendContent("  spec/views");
        mkdir('spec/views');
        
        $response->appendContent(
            "      create",
            array('separator' => false, 'color' => 'green')
        );
        $response->appendContent("  spec/controllers");
        mkdir('spec/controllers');
    }
    
    /**
     * Creates the SpecHelper file text
     *
     * @return string
     */
    protected function _getSpecHelperText()
    {
        $path = "'APPLICATION_PATH', " .
                "realpath(dirname(__FILE__) . '/../application')";
        $env = "'APPLICATION_ENV', (getenv('APPLICATION_ENV') ? " .
               "getenv('APPLICATION_ENV') : 'testing')";
        return <<<HELPER
<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define($path);

// Define application environment
defined('APPLICATION_ENV')
    || define($env);

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