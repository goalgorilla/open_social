/* eslint-env node, es6 */
/* global Promise */
/* eslint-disable key-spacing, one-var, no-multi-spaces, max-nested-callbacks, quote-props */

'use strict';

var importOnce = require('node-sass-import-once'),
    path = require('path');

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
  project     : __dirname + '/',
  styleGuide  : __dirname + '/styleguide/',
  theme       : __dirname + '/',
  basetheme   : __dirname + '/../socialbase/',
  drupal      : __dirname + '/../../../../../core/'
};

options.theme = {
  name       : 'socialblue',
  root       : options.rootPath.theme,
  components : options.rootPath.theme + 'components/',
  build      : options.rootPath.theme + 'assets/',
  css        : options.rootPath.theme + 'assets/css/',
  js         : options.rootPath.theme + 'assets/js/'
};

options.basetheme = {
  name       : 'socialbase',
  root       : options.rootPath.basetheme,
  components : options.rootPath.basetheme + 'components/',
  build      : options.rootPath.basetheme + 'assets/',
  css        : options.rootPath.basetheme + 'assets/css/',
  js         : options.rootPath.basetheme + 'assets/js/'
};


// Set the URL used to access the Drupal website under development. This will
// allow Browser Sync to serve the website and update CSS changes on the fly.
//options.drupalURL = '';
options.drupalURL = 'http://social.dev:32791';

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

// Define icons source and destination paths
options.icons = {
  src   : options.theme.components + '01-base/icons/source/',
  dest  : options.theme.build + 'icons/'
};


// Define the style guide paths and options.
options.styleGuide = {
  source: [
    options.theme.components,
    options.basetheme.components
  ],
  mask: /\.less|\.sass|\.scss|\.styl|\.stylus/,
  destination: options.rootPath.styleGuide,

  builder: 'os-builder',
  namespace: options.theme.name + ':' + options.theme.components,
  'extend-drupal8': true,

  // The css and js paths are URLs, like '/misc/jquery.js'.
  // The following paths are relative to the generated style guide.
  css: [
    // Base stylesheets
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'base.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'base.css'),
    // Atom stylesheets
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'alerts.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'alerts.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'badges.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'badges.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'button.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'button.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'cards.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'cards.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'form-controls.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'form-controls.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'labels.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'list-group.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'list-group.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'labels.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'close-icon.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'waves.css'),
    // Molecule stylesheets
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'dropdown.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'dropdown.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'file.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'file.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'form-elements.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'form-elements.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'datepicker.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'datepicker.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'input-groups.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'input-groups.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'password.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'password.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'timepicker.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'timepicker.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'media.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'mention.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'mention.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'breadcrumb.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'breadcrumb.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'nav-book.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'nav-book.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'nav-tabs.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'nav-tabs.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'navbar.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'navbar.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'pagination.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'pagination.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'popover.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'popover.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'teaser.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'teaser.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'tour.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'tour.css'),
    // Organisms stylesheets
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'comment.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'comment.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'hero.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'hero.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'meta.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'meta.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'offcanvas.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'offcanvas.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'site-footer.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'stream.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'stream.css'),
    // Template stylesheets
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'layout.css'),
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'page-node.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'page-node.css'),
    // Javascript stylesheets
    path.relative(options.rootPath.styleGuide, options.basetheme.css + 'morrisjs.css'),
    // Styleguide stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'styleguide.css'),

  ],
  js: [
    path.relative(options.rootPath.styleGuide, options.theme.js + 'waves.min.js')
  ],
  homepage: 'homepage.md',
  title: 'Style Guide for Open Social Blue'
};


// Define the paths to the JS files to lint.
options.eslint = {
  files  : [
    options.rootPath.project + 'gulpfile.js',
    options.theme.components + '**/*.js',
    '!' + options.theme.components + '**/*.min.js',
    '!' + options.theme.build + '**/*.js'
  ]
};

// If your files are on a network share, you may want to turn on polling for
// Gulp watch. Since polling is less efficient, we disable polling by default.
options.gulpWatchOptions = {};
// options.gulpWatchOptions = {interval: 1000, mode: 'poll'};


// ################################
// Load Gulp and tools we will use.
// ################################
var gulp      = require('gulp'),
  $           = require('gulp-load-plugins')(),
  browserSync = require('browser-sync').create(),
  del         = require('del'),
  // gulp-load-plugins will report "undefined" error unless you load gulp-sass manually.
  sass        = require('gulp-sass'),
  kss         = require('kss'),
  postcss     = require('gulp-postcss'),
  autoprefixer= require('autoprefixer'),
  mqpacker    = require('css-mqpacker'),
  concat      = require('gulp-concat');

// Must be defined after plugins are called.
var sassProcessors = [
  autoprefixer({browsers: ['> 1%', 'last 2 versions']}),
  mqpacker({sort: true})
];


// The default task.
gulp.task('default', ['build']);

// #################
// Build everything.
// #################
gulp.task('build', ['styles:production', 'styleguide', 'lint']);


gulp.task('styles', ['clean:css'], function () {
  return gulp.src(sassFiles)
    .pipe($.sourcemaps.init())
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.plumber({ errorHandler: onError }) )
    .pipe($.postcss(sassProcessors) )
    .pipe($.rucksack() )
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe($.sourcemaps.write('./'))
    .pipe(gulp.dest(options.theme.css))
    .pipe($.if(browserSync.active, browserSync.stream({match: '**/*.css'})));
});

gulp.task('styles:production', ['clean:css'], function () {
  return gulp.src(sassFiles)
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.plumber({ errorHandler: onError }) )
    .pipe($.postcss(sassProcessors) )
    .pipe($.rucksack() )
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.theme.css));
});



// ##################
// Build style guide.
// ##################
gulp.task('styleguide', ['clean:styleguide'], function () {
  return kss(options.styleGuide);
});

// Debug the generation of the style guide with the --verbose flag.
gulp.task('styleguide:debug', ['clean:styleguide', 'scripts-drupal'], function () {
  options.styleGuide.verbose = true;
  return kss(options.styleGuide);
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

// #########################
// Lint Sass and JavaScript.
// #########################
gulp.task('lint', ['lint:sass', 'lint:js']);

// Lint JavaScript.
gulp.task('lint:js', function () {
  return gulp.src(options.eslint.files)
    .pipe($.eslint())
    .pipe($.eslint.format());
});

// Lint JavaScript and throw an error for a CI to catch.
gulp.task('lint:js-with-fail', function () {
  return gulp.src(options.eslint.files)
    .pipe($.eslint())
    .pipe($.eslint.format())
    .pipe($.eslint.failOnError());
});

// Lint Sass.
gulp.task('lint:sass', function () {
  return gulp.src(options.theme.components + '**/*.scss')
    .pipe($.sassLint())
    .pipe($.sassLint.format());
});

// Lint Sass and throw an error for a CI to catch.
gulp.task('lint:sass-with-fail', function () {
  return gulp.src(options.theme.components + '**/*.scss')
    .pipe($.sassLint())
    .pipe($.sassLint.format())
    .pipe($.sassLint.failOnError());
});

// ##############################
// Watch for changes and rebuild.
// ##############################
gulp.task('watch', ['browser-sync', 'watch:lint-and-styleguide', 'watch:js']);

gulp.task('browser-sync', ['watch:css'], function () {
  if (!options.drupalURL) {
    return Promise.resolve();
  }
  return browserSync.init({
    proxy: options.drupalURL,
    noOpen: false
  });
});

gulp.task('watch:css', ['styles'], function () {
  return gulp.watch(options.theme.components + '**/*.scss', ['styles']);
});

gulp.task('watch:lint-and-styleguide', ['styleguide:debug'], function () {
  return gulp.watch([
    options.basetheme.components + '**/*.scss',
    options.theme.components + '**/*.scss',
    options.basetheme.components + '**/*.twig',
    options.theme.components + '**/*.twig',
    options.theme.components + '**/*.md'
  ], options.gulpWatchOptions, ['styleguide']);
});

gulp.task('watch:js', function () {
  return gulp.src(options.theme.components + '**/*.js')
    .pipe($.uglify())
    .pipe($.flatten())
    .pipe($.rename({
      suffix: ".min"
    }))
    .pipe(gulp.dest(options.theme.js));
});

// ######################
// Clean all directories.
// ######################
gulp.task('clean', ['clean:css', 'clean:styleguide']);

// Clean style guide files.
gulp.task('clean:styleguide', function () {
  // You can use multiple globbing patterns as you would with `gulp.src`
  return del([
    options.styleGuide.destination + '*.html',
    options.styleGuide.destination + 'kss-assets',
    options.theme.build + 'twig/*.twig'
  ], {force: true});
});

// Clean CSS files.
gulp.task('clean:css', function () {
  return del([
    options.theme.css + '**/*.css',
    options.theme.css + '**/*.map'
  ], {force: true});
});


// This gulpfile is based on the Drupal 8 Zen theme starterkit
