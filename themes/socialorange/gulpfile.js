/* eslint-env node, es6 */
/* global Promise */
/* eslint-disable key-spacing, one-var, no-multi-spaces, max-nested-callbacks, quote-props */

'use strict';

// ################################
// Load Gulp and tools we will use.
// ################################

var importOnce  = require('node-sass-import-once'),
  path        = require('path'),
  notify      = require('gulp-notify'),
  gulp        = require('gulp'),
  $           = require('gulp-load-plugins')(),
  browserSync = require('browser-sync').create(),
  del         = require('del'),
  sass        = require('gulp-sass'),
  kss         = require('kss'),
  postcss     = require('gulp-postcss'),
  rename      = require('gulp-rename'),
  autoprefixer= require('autoprefixer'),
  mqpacker    = require('css-mqpacker'),
  concat      = require('gulp-concat'),
  runSequence = require('run-sequence');

var options = {};

// #############################
// Edit these paths and options.
// #############################

// The root paths are used to construct all the other paths in this
// configuration. The "project" root path is where this gulpfile.js is located.
// While Open Social distributes this in the theme root folder, you can also put this
// (and the package.json) in your project's root folder and edit the paths
// accordingly.
options.rootPath = {
  theme           : __dirname + '/',
  styleGuide      : __dirname + '/styleguide/',
  basetheme       : __dirname + '/../../../profiles/contrib/social/themes/socialbase/',
  drupal          : __dirname + '/../../../core/'
};

options.theme = {
  name       : 'socialorange',
  root       : options.rootPath.theme,
  components : options.rootPath.theme + 'components/',
  build      : options.rootPath.theme + 'assets/',
  css        : options.rootPath.theme + 'assets/css/',
  js         : options.rootPath.theme + 'assets/js/',
  icons      : options.rootPath.theme + 'assets/icons/',
  images     : options.rootPath.theme + 'assets/images/'
};

options.basetheme = {
  name       : 'socialbase',
  root       : options.rootPath.basetheme,
  components : options.rootPath.basetheme + 'components/',
  build      : options.rootPath.basetheme + 'assets/',
  css        : options.rootPath.basetheme + 'assets/css/',
  js         : options.rootPath.basetheme + 'assets/js/'
};

// Define the node-sass configuration. The includePaths is critical!
options.sass = {
  importer: importOnce,
  includePaths: [
    options.theme.components,
  ],
  outputStyle: 'expanded'
};

var sassFiles = [
  options.theme.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.theme.components + '**/_*.scss'
];

// On screen notification for errors while performing tasks
var onError = function(err) {
  notify.onError({
    title:    "Gulp error in " + err.plugin,
    message:  "<%= error.message %>",
    sound: "Beep"
  })(err);
  this.emit('end');
};

// If your files are on a network share, you may want to turn on polling for
// Gulp watch. Since polling is less efficient, we disable polling by default.
options.gulpWatchOptions = {};
// options.gulpWatchOptions = {interval: 1000, mode: 'poll'};

var sassProcessors = [
  autoprefixer({browsers: ['> 1%', 'last 2 versions']}),
  mqpacker({sort: true})
];

// #################
//
// Compile the Sass
//
// #################
//
// This task will look for all scss files and run postcss and rucksack.
// For performance review we will display the file sizes
// Then the files will be stored in the assets folder
// At the end we check if we should inject new styles in the browser
// ===================================================

gulp.task('styles', function () {
  return gulp.src(sassFiles)
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.plumber({ errorHandler: onError }) )
    // run autoprefixer and media-query packer
    .pipe($.postcss(sassProcessors) )
    // run rucksack @see https://simplaio.github.io/rucksack/
    .pipe($.rucksack() )
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.theme.css))
});

// Copy drupal scripts from drupal to make them available for the styleguide
gulp.task('scripts-drupal', function() {
  return gulp.src([
    options.rootPath.drupal + 'assets/vendor/domready/ready.min.js',
    options.rootPath.drupal + 'assets/vendor/jquery/jquery.min.js',
    options.rootPath.drupal + 'assets/vendor/jquery-once/jquery.once.min.js',
    options.rootPath.drupal + '/misc/drupalSettingsLoader.js',
    options.rootPath.drupal + '/misc/drupal.js',
    options.rootPath.drupal + '/misc/debounce.js',
    options.rootPath.drupal + '/misc/forms.js',
    options.rootPath.drupal + '/misc/tabledrag.js',
    options.rootPath.drupal + '/modules/user/user.js',
    options.rootPath.drupal + '/modules/file/file.js'
  ])
    .pipe( concat('drupal-core.js') )
    .pipe( gulp.dest(options.rootPath.styleGuide + 'kss-assets/') );
});

// ##############################
//
// Watch for changes and rebuild.
//
// ##############################

// #################
//
// Minify JS
//
// #################
//
// First clean the JS folder, then search all components for js files.
// Then compress the files, give them an explicit .min filename and
// save them to the assets folder.
// ===================================================

gulp.task('watch:js', function () {
  return gulp.src(options.theme.components + '**/*.js')
    .pipe($.uglify())
    .pipe($.flatten())
    .pipe($.rename({
      suffix: ".min"
    }))
    .pipe(gulp.dest(options.theme.js));
});

// #################
//
// Sprite icons
//
// #################
//
// svgmin minifies our SVG files and strips out unnecessary
// code that you might inherit from your graphics editor.
// svgstore binds them together in one giant SVG container called
// icons.svg. Then cheerio gives us the ability to interact with
// the DOM components in this file in a jQuery-like way. cheerio
// in this case is removing any fill attributes from the SVG
// elements (you’ll want to use CSS to manipulate them)
// and adds a class of .hide to our parent SVG. It gets
// deposited into our inc directory with the rest of the HTML partials.
// ===================================================

var svgmin        = require('gulp-svgmin'),
  svgstore      = require('gulp-svgstore'),
  cheerio       = require('gulp-cheerio');

gulp.task('icons', function () {
  return gulp.src(options.theme.images + '**/*.svg')
    .pipe(svgmin())
    .pipe(svgstore({inlineSvg: true}))
    .pipe($.rename('icons.svg') )
    .pipe(cheerio({
      run: function ($, file) {
        $('svg').addClass('hidden');
      },
      parserOptions: { xmlMode: true }
    }))
    .pipe(gulp.dest(options.theme.icons))
});

gulp.task('watch:icons', ['icons'], function () {
  return gulp.watch(options.theme.images + '**/*.svg', ['icons'] );
});

// ######################
//
// Clean all directories.
//
// ######################

gulp.task('clean', ['clean:css']);

// Clean CSS files.
gulp.task('clean:css', function () {
  return del([
    options.theme.css + '**/*.css',
    options.theme.css + '**/*.map'
  ], {force: true});
});

gulp.task('watch', ['styles', 'watch:icons', 'watch:js'], function () {
  gulp.watch(options.theme.components + '**/*.scss', ['styles']);
  gulp.watch(options.theme.images + '**/*.svg', ['watch:icons']);
  gulp.watch(options.theme.components + '**/*.js', ['watch:js']);
});

// ######################
//
// Default task (no watching)
//
// ######################
gulp.task('default', ['styles']);