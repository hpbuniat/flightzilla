<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the flightzilla root now.
 */
chdir(dirname(__DIR__));
ini_set('display_errors', true);// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__)));

define('REQUEST_MICROTIME', microtime(true));
define('STARTTIME', microtime(true));

ini_set('max_execution_time', 120);
ini_set('memory_limit', '256M');

// Setup autoloading
include 'init_autoloader.php';

/*
define('XHPROF_ACTIVE', true);
require_once(APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'xhgui' . DIRECTORY_SEPARATOR . 'external' . DIRECTORY_SEPARATOR . 'header.php');
/**/

// Run flightzilla!
Zend\Mvc\Application::init(include 'config/application.config.php')->run();


