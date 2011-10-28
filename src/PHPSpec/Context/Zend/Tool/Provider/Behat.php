<?php

class PHPSpec_Context_Zend_Tool_Provider_Behat
    extends Zend_Tool_Project_Provider_Abstract
{
    
    public function generate()
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);
        
        if (is_dir('features')) {
            throw new Zend_Tool_Project_Provider_Exception(
                'You have already generated Behat\'s necessary files.'
            );
        }
        
        system('behat --init');            
    }
    
}