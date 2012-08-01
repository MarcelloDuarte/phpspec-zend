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
use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;

use Zend_Tool_Project_Provider_Form as FormProvider;
use Zend_Tool_Framework_Registry as Registry;
use Zend_Tool_Project_Profile as Profile;

use Zend_Filter_Word_CamelCaseToSeparator as CamelCaseToSeparator;

/**
 * @category   PHPSpec
 * @package    PHPSpec_Zend
 * @copyright  Copyright (c) 2007-2009 Pádraic Brady, Travis Swicegood
 * @copyright  Copyright (c) 2010-2012 Pádraic Brady, Travis Swicegood,
 *                                     Marcello Duarte
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public Licence Version 3
 */
class PHPSpec_Context_Zend_Tool_Context_Form
{
    /**
     * Updates the generated form with the properties of the model as fields
     *
     * @param Registry $registry 
     * @param Profile  $profile 
     * @param string   $entity 
     * @param string   $commaSeparatedFields 
     * @param string   $module 
     */
    public static function create(Registry $registry, Profile $profile,
        $entity, $commaSeparatedFields = '', $module = null)
    {
        $form = new FormProvider;
        $form->setRegistry($registry);
        $form->create("{$entity}Form");

        $formResource = FormProvider::createResource(
            $profile, "{$entity}Form", $module
        );
        $formPath = $formResource->getContext()->getPath();
        $formContent = file_get_contents($formPath);
        file_put_contents(
            $formPath, str_replace(
                '        /* Form Elements & Other Definitions Here ... */',
                self::_getFormElements($commaSeparatedFields),
                $formContent
            )
        );
    }
    
    /**
     * Gets the form elements code to add
     *
     * @param array $fields 
     * @return string
     */
    protected function _getFormElements($fields)
    {
        $upperFirst = new UCFirst;
        $camelCaseToSpace = new CamelCaseToSeparator;
        
        $elements = '';
        $fields = explode(',', $fields);
        
        foreach ($fields as $field) {
            list($name, $type) = strpos($field, ':') ?
                                 explode(':', $field) :
                                 array($field, 'string');
            $ucFirst = $upperFirst->filter($name);
            $label = $camelCaseToSpace->filter($ucFirst);
            
            switch ($type) {
                case 'text':
                    $elements .= "        \$this->addElement('textarea', " .
                                 "'{$name}', array('label' => '{$label}'));" .
                                 PHP_EOL;
                    break;
                default:
                    $elements .= "        \$this->addElement('text'," .
                                 " '{$name}', array('label' => " .
                                 "'{$label}'));" . PHP_EOL;
                    break;
            }
        }
        
        if (!empty($elements)) {
            $elements .= "        \$this->addElement('submit', 'Save');" .
                         PHP_EOL;
        }
        return $elements;
    }
}