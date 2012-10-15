<?php
// Composer autoloading
if (file_exists('vendor/autoload.php') === true) {
    $loader = require_once 'vendor/autoload.php';
}

$zf2Path = __DIR__ . '/vendor/zendframework/zendframework/library';
if (isset($loader) === true) {
    $loader->add('Zend', $zf2Path);
}
else {
    require_once $zf2Path . '/Zend/Loader/AutoloaderFactory.php';
    Zend\Loader\AutoloaderFactory::factory(
        array(
             'Zend\Loader\StandardAutoloader' => array(
                 'autoregister_zf' => true
             )
        )
    );
}

if (class_exists('Zend\Loader\AutoloaderFactory') !== true) {
    throw new RuntimeException('Unable to load ZF2. Run `php composer.phar install` or define a ZF2_PATH environment variable.');
}
