// ===================================================
// Lint Sass and JavaScript
// ===================================================

var eslint        = require('eslint');
var config        = require('./config.json');
var options       = {};

// Define the paths to the JS files to lint.
var jsFilesToLint = [
  '/gulpfile.js',
  config.js + '**/*.js',
  '!' + config.js + '**/*.min.js',
  config.components + '**/*.js',
  '!' + config.build + '**/*.js'
];

// Define the paths to the SASS files to lint.
var sassFilesToLint = [
  config.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + config.components + 'contrib/**/*.scss'
];


gulp.task('lint', ['lint:sass', 'lint:js']);

// Lint JavaScript.
gulp.task('lint:js', function () {
  return gulp.src(jsFilesToLint)
    .pipe($.eslint())
    .pipe($.eslint.format());
});

// Lint Sass.
gulp.task('lint:sass', function () {
  return gulp.src(sassFilesToLint + '**/*.scss')
    .pipe($.sassLint())
    .pipe($.sassLint.format());
});
