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
ini_set('memory_limit', '512M');

// Setup autoloading
include 'init_autoloader.php';


include_once '/usr/share/php/xhprof_lib/utils/xhprof_lib.php';
include_once '/usr/share/php/xhprof_lib/utils/xhprof_runs.php';
xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY); // start function
/**/

// Run flightzilla!
Zend\Mvc\Application::init(include 'config/application.config.php')->run();


$profiler_namespace = 'flightzilla';  // namespace for your application
$xhprof_data = xhprof_disable();  // stop function
$xhprof_runs = new XHProfRuns_Default();
$run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);  // save
/**/


