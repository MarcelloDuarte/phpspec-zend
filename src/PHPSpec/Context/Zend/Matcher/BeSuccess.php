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
namespace PHPSpec\Context\Zend\Matcher;
/**
 * @see \PHPSpec\Matcher
 */
use \PHPSpec\Matcher;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class BeSuccess implements Matcher
{
    /**
     * Success HTTP status code
     */
    const SUCCESS = 200;

    /**
     * The expected code
     *
     * @var integer
     */
    protected $_expectedCode;
    
    /**
     * The actual code
     *
     * @var integer
     */
    protected $_actualCode;

    /**
     * Be success is created with the success expected code
     *
     * @param irrelevant $unused
     */
    public function __construct($unused = 200)
    {
        $this->_expectedCode = self::SUCCESS;
    }
    
    /**
     * Checks whether actual value is true
     * 
     * @param Response $response
     * @return boolean
     */
    public function matches($response)
    {
        $this->_actualCode = $response->getHttpResponseCode();
        return $this->_actualCode === $this->_expectedCode;
    }
    
    /**
     * Returns failure message in case we are using should
     * 
     * @return string
     */
    public function getFailureMessage()
    {
        return 'expected to be success, got code ' . $this->_actualCode .
               ' (using beSuccess())';
    }

    /**
     * Returns failure message in case we are using should not
     * 
     * @return string
     */
    public function getNegativeFailureMessage()
    {
        return 'expected not to be success, ' .
               'but got success (using beSuccess())';
    }

    /**
     * Returns the matcher description
     * 
     * @return string
     */
    public function getDescription()
    {
        return 'be success';
    }
}