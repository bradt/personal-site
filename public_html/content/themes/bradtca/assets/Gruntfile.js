module.exports = function(grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		sass: {
			dist: {
				files: {
					'css/style.css': 'sass/style.scss'
				}
			}
		},
		watch: {
			sass: {
				files: ['sass/*'],
				tasks: ['sass']
			},
			livereload: {
				files: ['css/*'],
				options: { livereload: 35730 }
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-sass');

	grunt.registerTask('default', ['sass']);

};
