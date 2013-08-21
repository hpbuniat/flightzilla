module.exports = function (grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        meta: {
            banner: '/* <%= pkg.description %>, v<%= pkg.version %> <%= pkg.homepage %>\n' +
                'Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>, <%= pkg.license.type %> license ' +
                '<%= pkg.license.url %>*/\n'
        },
        jshint: {
            options: {
                curly: true,
                eqeqeq: true,
                forin: true,
                indent: 2,
                latedef: false,
                newcap: true,
                noarg: true,
                noempty: true,
                white: false,
                sub: true,
                undef: true,
                unused: true,
                loopfunc: true,
                expr: true,
                evil: true,
                eqnull: true
            }
        },
        shell: {
            composer: {
                command: 'php composer.phar update',
                stdout: true,
                stderr: true
            },
            bower: {
                command: 'bower install',
                stdout: true,
                stderr: true
            }
        },
        concat: {
            options: {
                separator: '\n'
            },
            dist: {
                src: [
                    './bower_components/jquery-ui/ui/minified/jquery.ui.core.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.widget.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.mouse.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.position.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.draggable.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.droppable.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.selectable.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.autocomplete.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.datepicker.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.menu.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.progressbar.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.slider.min.js',
                    './bower_components/jquery-ui/ui/minified/jquery.ui.tooltip.min.js'
                ],
                dest: 'bower_components/jquery-ui/jquery.ui.custom.min.js'
            }
        },
        copy: {
            bower: {
                files: [
                    {src: [
                        './bower_components/bootstrap/dist/js/bootstrap.min.js',
                        './bower_components/bootstrap/dist/css/bootstrap.css'
                    ], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./bower_components/d3/d3.min.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./bower_components/lodash/dist/lodash.min.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./bower_components/highcharts.com/js/highcharts.src.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./bower_components/jquery/jquery.min.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: [
                        './bower_components/jquery-ui/themes/smoothness/*.min.css',
                        './bower_components/jquery-ui/themes/smoothness/*.theme.css'
                    ], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./bower_components/jquery-ui/jquery.ui.custom.min.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./bower_components/jquery.tablesorter/js/jquery.tablesorter.min.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./bower_components/bootstrap/dist/fonts/*'], dest: './public/fonts/', filter: 'isFile', expand: true, flatten: true}
                ]
            }
        }
    });


    // load tasks
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-shell');

    // Default task(s).
    grunt.registerTask('default', ['shell', 'concat', 'copy']);
};
