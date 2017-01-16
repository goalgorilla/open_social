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
  theme       : __dirname + '/'
};

options.theme = {
  name       : 'social_blue',
  root       : options.rootPath.theme,
  components : options.rootPath.theme + 'components/',
  build      : options.rootPath.theme + 'assets/',
  css        : options.rootPath.theme + 'assets/css/',
  js         : options.rootPath.theme + 'assets/js/'
};

// Set the URL used to access the Drupal website under development. This will
// allow Browser Sync to serve the website and update CSS changes on the fly.
options.drupalURL = '';
// options.drupalURL = 'http://social.dev';

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
    options.theme.components
  ],
  mask: /\.less|\.sass|\.scss|\.styl|\.stylus/,
  destination: options.rootPath.styleGuide,

  builder: 'builder/twig',
  namespace: options.theme.name + ':' + options.theme.components,
  'extend-drupal8': true,

  // The css and js paths are URLs, like '/misc/jquery.js'.
  // The following paths are relative to the generated style guide.
  css: [
    // Base stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'base.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'layouts.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'chroma-kss-styles.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'kss-only.css'),
    // Atom stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'box.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'clearfix.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'comment.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'footer.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'header.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'hidden.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'highlight-mark.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'inline-links.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'inline-sibling.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'messages.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'print-none.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'responsive-video.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'visually-hidden.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'watermark.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'wireframe.css'),
    // Molecule stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'autocomplete.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'collapsible-fieldset.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'form-item.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'form-table.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'progress-bar.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'progress-throbber.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'resizable-textarea.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'table-drag.css'),
    // Organisms stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'breadcrumb.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'more-link.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'nav-menu.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'navbar.css'),
    // Template stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'pager.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'skip-link.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'tabs.css')
  ],
  js: [
  ],

  homepage: 'homepage.md',
  title: 'Open Social Blue Style Guide'
};

// Define the paths to the JS files to lint.
options.eslint = {
  files  : [
    options.rootPath.project + 'gulpfile.js',
    options.theme.components + '**/*.js',
    '!' + options.theme.components + '**/*.min.js'
  ]
};


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
  postcss       = require('gulp-postcss'),
  autoprefixer  = require('autoprefixer'),
  mqpacker      = require('css-mqpacker');

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


// ===================================================
// Move and minify JS.
// ===================================================
gulp.task('minify-scripts', function () {
  return gulp.src(options.theme.components + '**/*.js')
    .pipe($.uglify())
    .pipe($.flatten())
    .pipe($.rename({
      suffix: ".min"
    }))
    .pipe(gulp.dest(options.theme.js));
});


// ##################
// Build style guide.
// ##################
gulp.task('styleguide', ['clean:styleguide'], function () {
  return kss(options.styleGuide);
});

// Debug the generation of the style guide with the --verbose flag.
gulp.task('styleguide:debug', ['clean:styleguide'], function () {
  options.styleGuide.verbose = true;
  return kss(options.styleGuide);
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

gulp.task('watch:lint-and-styleguide', ['styleguide', 'lint:sass'], function () {
  return gulp.watch([
    options.theme.components + '**/*.scss',
    options.theme.components + '**/*.twig'
  ], options.gulpWatchOptions, ['styleguide', 'lint:sass']);
});

gulp.task('watch:js', ['lint:js'], function () {
  return gulp.watch(options.eslint.files, ['lint:js']);
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


// Resources used to create this gulpfile.js:
// - https://github.com/google/web-starter-kit/blob/master/gulpfile.babel.js
// - https://github.com/dlmanning/gulp-sass/blob/master/README.md
// - http://www.browsersync.io/docs/gulp/
