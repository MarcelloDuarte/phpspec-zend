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
namespace PHPSpec\Context\Zend\Tool;

require_once 'PHPSpec/Context/Zend/Tool/Provider/ActionSpec.php';
require_once 'PHPSpec/Context/Zend/Tool/Provider/Behat.php';
require_once 'PHPSpec/Context/Zend/Tool/Provider/ControllerSpec.php';
require_once 'PHPSpec/Context/Zend/Tool/Provider/ModelSpec.php';
require_once 'PHPSpec/Context/Zend/Tool/Provider/PHPSpec.php';
require_once 'PHPSpec/Context/Zend/Tool/Provider/ViewSpec.php';
require_once 'PHPSpec/Context/Zend/Tool/Provider/Scaffold.php';

use PHPSpec_Context_Zend_Tool_Provider_ActionSpec as ActionSpec;
use PHPSpec_Context_Zend_Tool_Provider_Behat as Behat;
use PHPSpec_Context_Zend_Tool_Provider_ControllerSpec as ControllerSpec;
use PHPSpec_Context_Zend_Tool_Provider_ModelSpec as ModelSpec;
use PHPSpec_Context_Zend_Tool_Provider_PHPSpec as PHPSpec;
use PHPSpec_Context_Zend_Tool_Provider_ViewSpec as ViewSpec;
use PHPSpec_Context_Zend_Tool_Provider_Scaffold as Scaffold;

use Zend_Tool_Framework_Manifest_ProviderManifestable as Manifestable;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class Manifest implements Manifestable
{
    /**
     * @inheritdoc
     */
    public function getProviders()
    {
        return array(
            new ActionSpec(),
            new Behat(),
            new ControllerSpec(),
            new ModelSpec(),
            new PHPSpec(),
            new ViewSpec(),
            new Scaffold()
        );
    }
}