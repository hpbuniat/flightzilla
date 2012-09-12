<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 *
 * See http://code.google.com/p/minify/wiki/CustomSource for other ideas
 **/

return array(
    'js' => array(
        '//resource/js/jquery-1.8.1.min.js',
        '//resource/base.js',
        '//resource/js/bootstrap.js',
        '//resource/js/highcharts.js',
        '//resourcejs/jquery.gantt.js',
        '//resource/js/jquery.tablesorter.js',
        '//resource/js/jquery.metadata.js',
    ),
    'css' => array(
        '//resource/base.css',
        '//resource/css/bootstrap.css',
        '//resource/css/gantt.css',
        '//resource/silk-sprite.css',
    ),
);