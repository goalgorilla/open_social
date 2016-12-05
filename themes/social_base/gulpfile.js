'use strict';

// ===================================================
// Global packages
// ===================================================

var importOnce    = require('node-sass-import-once'),
    path          = require('path');

var options = {};

options.basetheme = {
  root       : __dirname,
  components : __dirname + '/components/',
  build      : __dirname + '/assets/',
  css        : __dirname + '/assets/css/',
  js         : __dirname + '/js/'
};


// Set the URL used to access the Drupal website under development. This will
// allow Browser Sync to serve the website and update CSS changes on the fly.
options.drupalURL = '';
// options.drupalURL = 'http://social.dev';

// Define the node-sass configuration. The includePaths is critical!
options.sass = {
  importer: importOnce,
  includePaths: [
    options.basetheme.components,
  ],
  outputStyle: 'expanded'
};

var sassFiles = [
  options.basetheme.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.basetheme.components + '**/_*.scss'
];

var onError = function(err) {
  notify.onError({
    title:    "Gulp error in " + err.plugin,
    message:  "<%= error.message %>",
    sound: "Beep"
  })(err);
  this.emit('end');
};

options.icons = {
  src   : options.basetheme.components + '01-base/icons/source/',
  dest  : options.basetheme.components + '01-base/icons/'
};

// ===================================================
// Build CSS.
// ===================================================

var gulp          = require('gulp'),
    $             = require('gulp-load-plugins')(),
    browserSync   = require('browser-sync').create(),
    del           = require('del'),
    // gulp-load-plugins will report "undefined" error unless you load gulp-sass manually.
    sass          = require('gulp-sass'),
    postcss       = require('gulp-postcss'),
    autoprefixer  = require('autoprefixer'),
    mqpacker      = require('css-mqpacker');

// Must be defined after plugins are called.
var sassProcessors = [
  autoprefixer({browsers: ['> 1%', 'last 2 versions']}),
  mqpacker({sort: true})
];

gulp.task('styles', ['clean:css'], function () {
  return gulp.src(sassFiles)
    .pipe($.sourcemaps.init() )
    .pipe($.sass(options.sass).on('error', sass.logError))
    .pipe($.plumber({ errorHandler: onError }) )
    .pipe($.postcss(sassProcessors) )
    .pipe($.rucksack() )
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe($.sourcemaps.write('./'))
    .pipe(gulp.dest(options.basetheme.css))
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
    .pipe(gulp.dest(options.basetheme.css));
});



// ===================================================
// Icons
// svgmin minifies our SVG files and strips out unnecessary code that you might inherit from your graphics editor. svgstore binds them together in one giant SVG container called icons.svg. Then cheerio gives us the ability to interact with the DOM components in this file in a jQuery-like way. cheerio in this case is removing any fill attributes from the SVG elements (you’ll want to use CSS to manipulate them) and adds a class of .hide to our parent SVG. It gets deposited into our inc directory with the rest of the HTML partials.
// ===================================================

var svgmin        = require('gulp-svgmin'),
    svgstore      = require('gulp-svgstore'),
    cheerio       = require('gulp-cheerio');

gulp.task('icons', function () {
  return gulp.src(options.icons.src + '*.svg')
    .pipe(svgmin())
    .pipe(svgstore({inlineSvg: true}))
    .pipe($.rename('icons.svg') )
    .pipe(cheerio({
      run: function ($, file) {
        $('svg').addClass('hide');
      },
      parserOptions: { xmlMode: true }
    }))
    .pipe(gulp.dest(options.icons.dest))
});



// ##############################
// Watch for changes and rebuild.
// ##############################

gulp.task('watch', ['browser-sync']);

gulp.task('browser-sync', ['watch:css', 'watch:icons'], function () {
  if (!options.drupalURL) {
    return Promise.resolve();
  }
  return browserSync.init({
    proxy: options.drupalURL,
    noOpen: false
  });
});

gulp.task('watch:css', ['styles'], function () {
  return gulp.watch(options.basetheme.components + '**/*.scss', ['styles']);
});

gulp.task('watch:icons', function () {
  return gulp.watch(options.icons.src + '**/*.svg', ['icons'] );
});



// ######################
// Clean all directories.
// ######################

// Clean CSS files.
gulp.task('clean:css', function () {
  return del([
    options.basetheme.css + '**/*.css',
    options.basetheme.css + '**/*.map'
  ], {force: true});
});
