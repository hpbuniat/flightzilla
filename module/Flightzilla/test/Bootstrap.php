<?php

namespace FlightzillaTest;

error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', true);// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__)));

chdir(__DIR__);

$_SERVER['REMOTE_ADDR'] = 'cli';
$_SERVER['HTTP_USER_AGENT'] = 'unit-test';

require_once __DIR__ . '/../../../init_autoloader.php';