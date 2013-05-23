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
                command: 'bower update',
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
                    './components/jquery-ui/ui/minified/jquery.ui.core.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.widget.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.mouse.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.position.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.draggable.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.droppable.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.selectable.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.autocomplete.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.datepicker.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.menu.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.progressbar.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.slider.min.js',
                    './components/jquery-ui/ui/minified/jquery.ui.tooltip.min.js'
                ],
                dest: 'components/jquery-ui/jquery.ui.custom.min.js'
            }
        },
        copy: {
            bower: {
                files: [
                    {src: [
                        './components/bootstrap/docs/assets/js/bootstrap.min.js',
                        './components/bootstrap/docs/assets/css/bootstrap-responsive.css' ,
                        './components/bootstrap/docs/assets/css/bootstrap.css'
                    ], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./components/highcharts/highcharts.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./components/jquery/jquery.min.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: [
                        './components/jquery-ui/themes/smoothness/*.min.css',
                        './components/jquery-ui/themes/smoothness/*.theme.css'
                    ], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./components/jquery-ui/jquery.ui.custom.min.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true},
                    {src: ['./components/jquery.tablesorter/js/jquery.tablesorter.min.js'], dest: './public/vendor/', filter: 'isFile', expand: true, flatten: true}
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
