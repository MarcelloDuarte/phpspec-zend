<?php

use PHPSpec_Context_Zend_Filter_UCFirst as UCFirst;

use Zend_Tool_Project_Provider_Form as FormProvider;

use Zend_Filter_Word_CamelCaseToSeparator as CamelCaseToSeparator;

class PHPSpec_Context_Zend_Tool_Context_Form
{
    
    public static function create($registry, $profile, $entity, $commaSeparatedFields = '', $module = null) {
        $form = new FormProvider;
        $form->setRegistry($registry);
        $form->create("{$entity}Form");

        $formResource = FormProvider::createResource($profile, "{$entity}Form", $module);
        $formPath = $formResource->getContext()->getPath();
        $formContent = file_get_contents($formPath);
        file_put_contents($formPath, str_replace(
            '        /* Form Elements & Other Definitions Here ... */',
            self::_getFormElements($commaSeparatedFields),
            $formContent
        ));
    }
    
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
                                 "'{$name}', array('label' => '{$label}'));" . PHP_EOL;
                    break;
                default:
                    $elements .= "        \$this->addElement('text', '{$name}', array('label' => '{$label}'));" . PHP_EOL;
                    break;
            }
        }
        
        if (!empty($elements)) {
            $elements .= "        \$this->addElement('submit', 'Save');" . PHP_EOL;
        }
        return $elements;
    }
}