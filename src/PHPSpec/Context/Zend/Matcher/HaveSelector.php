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
use \PHPSpec\Matcher,
    \PHPSpec\Util\Validate,
    \PHPSpec\Specification\Interceptor\InterceptorFactory,
    \Zend_Dom_Query as DomQuery;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class HaveSelector implements Matcher
{
    /**
     * The CSS selector used to query the dom
     *
     * @var string
     */
    protected $_selector;
    
    /**
     * Css selector attributes
     *
     * @var array
     */
    protected $_conditions = array();
    
    /**
     * Whether the selector has attribute filters
     *
     * @var boolean
     */
    protected $_hasContentCondition = false;
    
    /**
     * Creates the matcher
     *
     * @param string   $expected
     * @param array    $conditions OPTIONAL
     * @param \Closure $block OPTIONAL
     * @throws \PHPSpec\Exception
     */
    public function __construct($expected)
    {
        switch (func_num_args()) {
            case 1:
                $this->_selector = $expected;
                break;
            case 2:
                $this->_selector = $expected;
                $this->_conditions = Validate::isArray(
                    func_get_arg(1), '2nd', 'Have selector'
                );
                break;
            default:
                throw new \PHPSpec\Exception('Wrong number of arguments');
        }
        
        $this->_expected = $expected;
    }
    
    /**
     * Checks whether value is somewhere in the body
     * 
     * @param Response $response
     * @return boolean
     */
    public function matches($content)
    {
        $this->_actual = $content;
        
        if ($this->hasConditions()) {
            $this->buildSelector($content);
            
            if ($this->hasContentCondition()) {
                return $this->matchesContent($content);
            }
        }
        
        return $this->query($content);
    }
    
    /**
     * Queries the DOM for a content
     *
     * @param string $content 
     * @return boolean
     */
    private function query($content)
    {
        $domQuery = new DomQuery($content);
        $result = $domQuery->query($this->_expected);
        return (0 < count($result));
    }
    
    /**
     * Checks whether there are extra conditions for matching a selector
     *
     * @return boolean
     */
    private function hasConditions()
    {
        return is_array($this->_conditions) && !empty($this->_conditions);
    }
    
    /**
     * Tries to Build a css selector
     *
     * @param string $content
     *
     * @return boolean
     */
    private function buildSelector($content)
    {
        $selector = $this->_expected;
        foreach ($this->_conditions as $condition => $value) {
            if ($condition == 'content') {
                $this->_hasContentCondition = true;
                continue;
            }
            $selector .= "[$condition=\"$value\"]";
        }
        $this->_expected = $selector;
    }
    
    /**
     * Gets the flag the tells whether the selector has attribute filters
     *
     * @return boolean
     */
    protected function hasContentCondition()
    {
        return $this->_hasContentCondition;
    }
    
    /**
     * Checks the special case where the css attribute "content" is used
     *
     * @param string $content 
     * @return boolean
     */
    private function matchesContent($content)
    {
        foreach ($this->_conditions as $condition => $value) {
            if ($condition == 'content') {
                if ($this->contentIsNotPresent($content, $value)) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Checks whether the content is empty
     *
     * @param string $content
     * @param string $value
     * @return boolean
     */
    private function contentIsNotPresent($content, $value)
    {
        $domQuery = new DomQuery($content);
        $result = $domQuery->query($this->_expected);
        
        if (count($result) == 0) {
            return true;
        }
        
        foreach ($result as $node) {
            $nodeContent = $this->getNodeContent($node);
            if (strstr($nodeContent, $value)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get node content, minus node markup tags
     *
     * @param  DOMNode $node
     * @return string
     */
    protected function getNodeContent(\DOMNode $node)
    {
        if ($node instanceof \DOMAttr) {
            return $node->value;
        }
        $regex   = '|</?' . $node->nodeName . '[^>]*>|';
        return preg_replace($regex, '', $node->ownerDocument->saveXML($node));
    }
    
    /**
     * Returns failure message in case we are using should
     * 
     * @return string
     */
    public function getFailureMessage()
    {
        return 'expected ' . var_export($this->_actual, true) .
               ' to have selector ' .
               var_export($this->_expected, true) .
               ', found no match (using haveSelector())';
    }

    /**
     * Returns failure message in case we are using should not
     * 
     * @return string
     */
    public function getNegativeFailureMessage()
    {
        return 'expected not to have selector ' .
               var_export($this->_expected, true) .
               ', but found a match (using haveSelector())';
    }

    /**
     * Returns the matcher description
     * 
     * @return string
     */
    public function getDescription()
    {
        return 'have selector ' . $this->_expected;
    }
}