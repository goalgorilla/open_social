'use strict';

// ===================================================
// Required packages
// ===================================================

var gulp          = require('gulp'),
    postcss       = require('gulp-postcss'),
    sass          = require('gulp-sass'),
    sourcemaps    = require('gulp-sourcemaps'),
    autoprefixer  = require('autoprefixer'),
    mqpacker      = require('css-mqpacker'),
    rucksack      = require('gulp-rucksack'),
    pug           = require('gulp-pug'),
    importOnce    = require('node-sass-import-once'),
    path          = require('path'),
    rename        = require('gulp-rename'),
    fs            = require('fs'),
    concat        = require('gulp-concat'),
    notify        = require('gulp-notify'),
    gutil         = require('gulp-util'),
    connect       = require('gulp-connect'),
    changed       = require('gulp-changed'),
    cached        = require('gulp-cached'),
    gulpif        = require('gulp-if'),
    filter        = require('gulp-filter'),
    plumber       = require('gulp-plumber'),
    deploy        = require('gulp-gh-pages'),
    svgmin        = require('gulp-svgmin'),
    svgstore      = require('gulp-svgstore'),
    cheerio       = require('gulp-cheerio');

    var options = {};

// ===================================================
// CONFIG
// Edit these paths and options
// ===================================================

// The root paths are used to construct all the other paths in this
// configuration. The "theme" root path is where this gulpfile.js is located.

options.rootPath = {
  theme       : __dirname + '/',
  dist        : __dirname + '/dist/',
  drupalcore  : '../../../../../core/'
};

options.theme = {
  name       : 'socialbase',
  root       : options.rootPath.theme,
  bootstrap  : options.rootPath.theme + 'node_modules/bootstrap-sass/assets/',
  build      : options.rootPath.theme + 'components/asset-builds/',
  components : options.rootPath.theme + 'components/',
  content    : options.rootPath.theme + 'content/',
  css        : options.rootPath.theme + 'components/asset-builds/css/',
  font       : options.rootPath.theme + 'font/',
  icons      : options.rootPath.theme + 'images/icons/',
  images     : options.rootPath.theme + 'images/',
  js         : options.rootPath.theme + 'js/',
  styleguide : options.rootPath.theme + 'pug/'
};

// Set the URL used to access the Drupal website under development. This will
// allow Browser Sync to serve the website and update CSS changes on the fly.
options.drupalURL = '';
// options.drupalURL = 'http://localhost';

// Define the node-sass configuration. The includePaths is critical!
options.sass = {
  importer: importOnce,
  includePaths: [
    options.theme.components
  ],
  outputStyle: 'expanded'
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

options.styleguide = {
  files  : [
    options.theme.styleguide + '**/*.pug'
  ]
};

var onError = function(err) {
  notify.onError({
    title:    "Gulp error in " + err.plugin,
    message:  "<%= error.message %>",
    sound: "Beep"
  })(err);
  this.emit('end');
};

// ===================================================
// Build CSS.
// ===================================================

var sassFiles = [
  options.theme.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.theme.components + '**/_*.scss'
];

var sassProcessors = [
  autoprefixer({browsers: ['> 1%', 'last 2 versions']}),
  mqpacker({sort: true})
];

gulp.task('styles', function () {
  return gulp.src(sassFiles)
    .pipe( sourcemaps.init() )
    .pipe( plumber({ errorHandler: onError }) )
    .pipe( sass(options.sass) )
    .pipe( postcss(sassProcessors) )
    .pipe( rucksack() )
    .pipe( rename({dirname: ''}))
    .pipe( sourcemaps.write('.') )
    .pipe( gulp.dest(options.theme.css) )
    .pipe( gulp.dest(options.rootPath.dist + '/css/components/asset-builds') )
    .pipe( connect.reload() );
});

// ===================================================
// Template file (Pug)
// ===================================================

gulp.task('styleguide', function() {

  return gulp.src(options.styleguide.files)
    .pipe(plumber({
      handleError: onError
    }))

    //only pass changed *main* files and *all* the partials
    //.pipe(changed(options.rootPath.dist, {extension: '.html'}))

    //filter out unchanged partials, but it only works when watching
    //.pipe(gulpif(global.isWatching, cached('pug')))

    //filter out partials (folders and files starting with "_" )
    // .pipe(filter(function (file) {
    //  return !/\/_/.test(file.path) || !/^_/.test(file.relative);
    // }))

    .pipe(pug({
      pretty: true
    })) // pipe to pug plugin

    .pipe(gulp.dest(options.rootPath.dist)) // tell gulp our output folder

});

gulp.task('setWatch', function() {
    global.isWatching = true;
});

// ===================================================
// Scripts
// ===================================================

//copy drupal scripts from drupal to make them available for the styleguide
gulp.task('script-drupal', function() {
  return gulp.src([
    options.rootPath.drupalcore + 'assets/vendor/domready/ready.min.js',
    options.rootPath.drupalcore + 'assets/vendor/jquery/jquery.min.js',
    options.rootPath.drupalcore + 'assets/vendor/jquery-once/jquery.once.min.js',
    options.rootPath.drupalcore + '/misc/drupalSettingsLoader.js',
    options.rootPath.drupalcore + '/misc/drupal.js',
    options.rootPath.drupalcore + '/misc/debounce.js',
    options.rootPath.drupalcore + '/misc/forms.js',
    options.rootPath.drupalcore + '/misc/tabledrag.js',
    options.rootPath.drupalcore + '/modules/user/user.js',
    options.rootPath.drupalcore + '/modules/file/file.js'
  ])
  .pipe( concat('drupal-core.js') )
  .pipe( gulp.dest(options.rootPath.dist + '/js') );
});

//copy scripts to dist
gulp.task('copy-scripts', function() {
  return gulp.src(options.theme.js + "/**/*")
  .pipe( gulp.dest(options.rootPath.dist + '/js') );
});


// ===================================================
// Icons
// svgmin minifies our SVG files and strips out unnecessary code that you might inherit from your graphics editor. svgstore binds them together in one giant SVG container called icons.svg. Then cheerio gives us the ability to interact with the DOM components in this file in a jQuery-like way. cheerio in this case is removing any fill attributes from the SVG elements (you’ll want to use CSS to manipulate them) and adds a class of .hide to our parent SVG. It gets deposited into our inc directory with the rest of the HTML partials.
// ===================================================

gulp.task('icons', function () {
  return gulp.src(options.theme.icons + '*.svg')
    .pipe(svgmin())
    .pipe(svgstore({ fileName: 'icons.svg', inlineSvg: true}))
    .pipe(cheerio({
      run: function ($, file) {
        $('svg').addClass('hide');
      },
      parserOptions: { xmlMode: true }
    }))
    .pipe(gulp.dest(options.theme.images))
});

// ===================================================
// Copy assets to dist folder
// ===================================================

gulp.task('images', function() {
  return gulp.src(options.theme.images + '**/*')
  .pipe( gulp.dest(options.rootPath.dist + 'images') );
});

gulp.task('content', function() {
  return gulp.src(options.theme.content + '**/*')
  .pipe( gulp.dest(options.rootPath.dist + 'content') );
});

gulp.task('font', function() {
  return gulp.src(options.theme.font + '**/*')
  .pipe( gulp.dest(options.rootPath.dist + 'font') );
});

// ===================================================
// Import Bootstrap assets
// ===================================================

gulp.task('bootstrap-sass', function() {
  return gulp.src(options.theme.bootstrap + 'stylesheets/bootstrap/' + '**/*.scss' )
    .pipe( gulp.dest(options.theme.components + '/contrib/bootstrap') );
});

gulp.task('bootstrap-js', function() {
  return gulp.src(options.theme.bootstrap + 'javascripts/bootstrap.min.js')
    .pipe( gulp.dest(options.theme.js + '/contrib') );
});


// ===================================================
// Lint Sass and JavaScript
// ===================================================
var sassFilesToLint = [
  options.theme.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.theme.components + 'contrib/**/*.scss'
];


gulp.task('lint', ['lint:sass', 'lint:js']);

// Lint JavaScript.
gulp.task('lint:js', function () {
  return gulp.src(options.eslint.files)
    .pipe($.eslint())
    .pipe($.eslint.format());
});

// Lint Sass.
gulp.task('lint:sass', function () {
  return gulp.src(sassFilesToLint + '**/*.scss')
    .pipe($.sassLint())
    .pipe($.sassLint.format());
});

// ===================================================
// Set up a server
// ===================================================

gulp.task('connect', function() {
  connect.server({
    root: [options.rootPath.dist],
    livereload: false,
    port: 5000
  });
});


// ===================================================
// Watch and rebuild tasks
// ===================================================

gulp.task('default', ['watch:css', 'watch:styleguide', 'watch:content', 'watch:js', 'watch:icons', 'watch:images', 'connect']);

gulp.task('watch:css', ['styles'], function () {
  return gulp.watch(options.theme.components + '**/*.scss', ['styles']);
});

gulp.task('watch:styleguide', ['setWatch', 'styleguide'], function () {
  return gulp.watch([
    options.theme.root + '**/*.pug',
  ], ['styleguide']);
});

gulp.task('scripts', ['copy-scripts', 'script-drupal']);

gulp.task('watch:js', ['scripts'] , function () {
  return gulp.watch(options.eslint.files, ['scripts'] );
});

gulp.task('watch:icons', function () {
  return gulp.watch(options.theme.icons + '**/*.svg', ['icons'] );
});

gulp.task('watch:images', function () {
  return gulp.watch(options.theme.images + '**/*', ['images'] );
});

gulp.task('watch:content', ['content'], function () {
  return gulp.watch(options.theme.content + '**/*', ['content']);
});

// ===================================================
// Deploy to github pages branch
// ===================================================
gulp.task('build', ['styles', 'styleguide' , 'scripts', 'font', 'images', 'content']);

gulp.task('deploy', ['build'], function() {
  return gulp.src([options.rootPath.dist + '/**/*'])
    .pipe( deploy() );
});


// ===================================================
// Run this one time when you install the project so you have all files in the dist folder
// ===================================================
gulp.task('init', ['images', 'content', 'font', 'bootstrap-js', 'bootstrap-sass', 'scripts']);
