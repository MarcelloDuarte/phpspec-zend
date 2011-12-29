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
 
/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

class PHPSpec_Context_Zend_Filter_UCFirst implements Zend_Filter_Interface
{
     
    /**
     * Applies a similar function like ucfirst, only it maitains the rest of
     * the string in the same case as it was
     *
     * @param string $value String to be filtered
     * 
     * @return string String filtered
     */
    public function filter($value)
    {
        if (!empty($value)) {
            $value[0] = strtoupper($value[0]);
        }
        return $value;
    }
    
    public static function apply($value)
    {
        $filter = new static;
        return $filter->filter($value);
    }
}