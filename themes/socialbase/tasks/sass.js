// ===================================================
// Build CSS.
// ===================================================

var gulp          = require('gulp'),
    postcss       = require('gulp-postcss'),
    sass          = require('gulp-sass'),
    sourcemaps    = require('gulp-sourcemaps'),
    autoprefixer  = require('autoprefixer'),
    mqpacker      = require('css-mqpacker'),
    rucksack      = require('gulp-rucksack'),
    importOnce    = require('node-sass-import-once'),
    notify        = require('gulp-notify'),
    rename        = require('gulp-rename'),
    path          = require('path'),
    plumber       = require('gulp-plumber');

var config        = require('./config.json');
var options       = {};

var onError = function(err) {
  notify.onError({
    title:    "Gulp error in " + err.plugin,
    message:  "<%= error.message %>",
    sound: "Beep"
  })(err);
  this.emit('end');
};

// Define the node-sass configuration. The includePaths is critical!
options.sass = {
  importer: importOnce,
  includePaths: [
    config.components
  ],
  outputStyle: 'expanded'
};

var sassFiles = [
  config.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + config.components + '**/_*.scss'
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
    .pipe( gulp.dest(config.css) )
    .pipe( gulp.dest(config.dist + 'css/components/asset-builds') );
});

gulp.task('watch:styles', ['styles'], function () {
  return gulp.watch(config.components + '**/*.scss', ['styles']);
});
