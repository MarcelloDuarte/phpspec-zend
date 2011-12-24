<?php

require_once 'Mockery/Loader.php';
require_once 'Hamcrest/hamcrest.php';
$loader = new \Mockery\Loader;
$loader->register();

require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();