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
// While Zen distributes this in the theme root folder, you can also put this
// (and the package.json) in your project's root folder and edit the paths
// accordingly.
options.rootPath = {
  project     : __dirname + '/',
  styleGuide  : __dirname + '/styleguide/',
  theme       : __dirname + '/'
};

options.theme = {
  name       : 'social_skeleton',
  root       : options.rootPath.theme,
  components : options.rootPath.theme + 'components/',
  build      : options.rootPath.theme + 'components/asset-builds/',
  css        : options.rootPath.theme + 'components/asset-builds/css/',
  js         : options.rootPath.theme + 'js/'
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
    options.rootPath.project + 'node_modules/breakpoint-sass/stylesheets',
    options.rootPath.project + 'node_modules/chroma-sass/sass',
    options.rootPath.project + 'node_modules/support-for/sass',
    options.rootPath.project + 'node_modules/typey/stylesheets',
    options.rootPath.project + 'node_modules/zen-grids/sass'
  ],
  outputStyle: 'expanded'
};

// Define which browsers to add vendor prefixes for.
options.autoprefixer = {
  browsers: [
    '> 1%',
    'ie 9'
  ]
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
    // base/special stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'base.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'layouts.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'chroma-kss-styles.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'kss-only.css'),
    // component stylesheets
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
    // form stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'autocomplete.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'collapsible-fieldset.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'form-item.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'form-table.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'progress-bar.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'progress-throbber.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'resizable-textarea.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'table-drag.css'),
    // navigation stylesheets
    path.relative(options.rootPath.styleGuide, options.theme.css + 'breadcrumb.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'more-link.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'nav-menu.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'navbar.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'pager.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'skip-link.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'tabs.css')
  ],
  js: [
  ],

  homepage: 'homepage.md',
  title: 'Open Social Style Guide'
};

// Define the paths to the JS files to lint.
options.eslint = {
  files  : [
    options.rootPath.project + 'gulpfile.js',
    options.theme.js + '**/*.js',
    '!' + options.theme.js + '**/*.min.js',
    options.theme.components + '**/*.js',
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
  kss         = require('kss');

// The default task.
gulp.task('default', ['build']);

// #################
// Build everything.
// #################
gulp.task('build', ['styles:production', 'styleguide', 'lint']);

// ##########
// Build CSS.
// ##########
var sassFiles = [
  options.theme.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.theme.components + '**/_*.scss',
  // Chroma markup has its own gulp task.
  '!' + options.theme.components + 'style-guide/kss-example-chroma.scss'
];

gulp.task('styles', ['clean:css'], function () {
  return gulp.src(sassFiles)
    .pipe($.sourcemaps.init())
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.autoprefixer(options.autoprefixer))
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe($.sourcemaps.write('./'))
    .pipe(gulp.dest(options.theme.css))
    .pipe($.if(browserSync.active, browserSync.stream({match: '**/*.css'})));
});

gulp.task('styles:production', ['clean:css'], function () {
  return gulp.src(sassFiles)
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.autoprefixer(options.autoprefixer))
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.theme.css));
});

// ##################
// Build style guide.
// ##################
gulp.task('styleguide', ['clean:styleguide', 'styleguide:kss-example-chroma'], function () {
  return kss(options.styleGuide);
});

gulp.task('styleguide:kss-example-chroma', function () {
  return gulp.src(options.theme.components + 'style-guide/kss-example-chroma.scss')
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.replace(/(\/\*|\*\/)\n/g, ''))
    .pipe($.rename('kss-example-chroma.twig'))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.theme.build + 'twig'));
});

// Debug the generation of the style guide with the --verbose flag.
gulp.task('styleguide:debug', ['clean:styleguide', 'styleguide:kss-example-chroma'], function () {
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
  return gulp.watch(options.theme.components + '**/*.scss', options.gulpWatchOptions, ['styles']);
});

gulp.task('watch:lint-and-styleguide', ['styleguide', 'lint:sass'], function () {
  return gulp.watch([
    options.theme.components + '**/*.scss',
    options.theme.components + '**/*.twig'
  ], options.gulpWatchOptions, ['styleguide', 'lint:sass']);
});

gulp.task('watch:js', ['lint:js'], function () {
  return gulp.watch(options.eslint.files, options.gulpWatchOptions, ['lint:js']);
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
