/* jshint node: true, browser: false */
/* eslint-env node */

/**
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Grunt configuration
 */

"use strict";

module.exports = function(grunt) {
    var path = require('path'),
        tasks = {},
        cwd = process.env.PWD || process.cwd(),
        semver = require('semver');

    // Verify the node version is new enough.
    var expected = semver.validRange(grunt.file.readJSON('package.json').engines.node);
    var actual = semver.valid(process.version);
    if (!semver.satisfies(actual, expected)) {
        grunt.fail.fatal('Node version too old. Require ' + expected + ', version installed: ' + actual);
    }

    // Windows users can't run grunt in a subdirectory, so allow them to set
    // the root by passing --root=path/to/dir.
    if (grunt.option('root')) {
        var root = grunt.option('root');
        if (grunt.file.exists(__dirname, root)) {
            cwd = path.join(__dirname, root);
            grunt.log.ok('Setting root to ' + cwd);
        } else {
            grunt.fail.fatal('Setting root to ' + root + ' failed - path does not exist');
        }
    }

    var inAMD = path.basename(cwd) === 'amd';

    // Globbing pattern for matching all AMD JS source files.
    var amdSrc = [inAMD ? cwd + '/src/*.js' : '**/amd/src/*.js'];

    /**
     * Function to generate the destination for the uglify task
     * (e.g. build/file.min.js). This function will be passed to
     * the rename property of files array when building dynamically:
     * http://gruntjs.com/configuring-tasks#building-the-files-object-dynamically
     *
     * @param {String} destPath the current destination
     * @param {String} srcPath the  matched src path
     * @return {String} The rewritten destination path.
     */
    var uglifyRename = function(destPath, srcPath) {
        destPath = srcPath.replace('src', 'build');
        destPath = destPath.replace('.js', '.min.js');
        destPath = path.resolve(cwd, destPath);
        return destPath;
    };

    // Project configuration.
    grunt.initConfig({
        eslint: {
            // Even though warnings dont stop the build we don't display warnings by default because
            // at this moment we've got too many core warnings.
            options: {quiet: !grunt.option('show-lint-warnings')},
            amd: {
              src: amdSrc,
              // Check AMD with some slightly stricter rules.
              rules: {
                'no-unused-vars': 'error',
                'no-implicit-globals': 'error'
              }
            }
        },
        uglify: {
            amd: {
                files: [{
                    expand: true,
                    src: amdSrc,
                    rename: uglifyRename
                }],
                options: {report: 'none'}
            }
        },
        watch: {
            options: {
                nospawn: true // We need not to spawn so config can be changed dynamically.
            },
            amd: {
                files: ['**/amd/src/**/*.js'],
                tasks: ['amd']
            }
        }
    });

    tasks.startup = function() {
        // Are we in an AMD directory?
        if (inAMD) {
            grunt.task.run('amd');
        } else {
            // Run them all!.
            grunt.task.run('js');
        }
    };

    // On watch, we dynamically modify config to build only affected files. This
    // method is slightly complicated to deal with multiple changed files at once (copied
    // from the grunt-contrib-watch readme).
    var changedFiles = Object.create(null);
    var onChange = grunt.util._.debounce(function() {
          var files = Object.keys(changedFiles);
          grunt.config('eslint.amd.src', files);
          grunt.config('uglify.amd.files', [{expand: true, src: files, rename: uglifyRename}]);
          changedFiles = Object.create(null);
    }, 200);

    grunt.event.on('watch', function(action, filepath) {
          changedFiles[filepath] = action;
          onChange();
    });

    // Register NPM tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-eslint');

    // Register JS tasks.
    grunt.registerTask('amd', ['eslint:amd', 'uglify']);
    grunt.registerTask('js', ['amd']);

    // Register the startup task.
    grunt.registerTask('startup', 'Run the correct tasks for the current directory', tasks.startup);

    // Register the default task.
    grunt.registerTask('default', ['startup']);
};
