<?php
return array(
    'modules' => array(
        'Flightzilla',
        'ZendDeveloperTools'
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            __DIR__ .  '/../config/autoload/{,*.}{global,local}.php',
        ),
        'module_paths' => array(
            __DIR__ .  '/../module',
            __DIR__ .  '/../vendor/zendframework',
        ),
    ),
);
