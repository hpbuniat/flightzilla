module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    install: {
	shell: {
	    composer: {
		command: "php composer.phar install",
		stdout: true
	    },
	    bower: {
		command: "bower install",
		stdout: true
	    }
	}
    },
    update: {
	    composer: {
		command: "php composer.phar update",
		stdout: true
	    },
	    bower: {
		command: "bower update",
		stdout: true
	    }
    },
    dev: {
    }

  });

  // This is what gets run when you don't specify an argument for grunt.
  grunt.registerTask('default', 'install');

};
