'use strict';

// ===================================================
// Global packages
// ===================================================

var gulp          = require('gulp');

// Global configuration
var config        = require('./gulp_config.json');

// ===================================================
// Initialize files
// ===================================================

// Run this one time when you install the project so you have all files in the dist folder
gulp.task('init', ['images', 'content', 'font', 'bootstrap-js', 'bootstrap-sass', 'scripts']);


gulp.task('bootstrap-sass', function() {
  return gulp.src(config.bootstrap + 'stylesheets/bootstrap/' + '**/*.scss' )
    .pipe( gulp.dest(config.components + '/contrib/bootstrap') );
});

gulp.task('bootstrap-js', function() {
  return gulp.src(config.bootstrap + 'javascripts/bootstrap/*.js')
    .pipe( gulp.dest(config.js + '/contrib/bootstrap') );
});



// ===================================================
// Styleguide
// ===================================================

var pug           = require('gulp-pug'),
    connect       = require('gulp-connect'),
    concat        = require('gulp-concat'),
    notify        = require('gulp-notify'),
    path          = require('path'),
    plumber       = require('gulp-plumber');



var onError = function(err) {
  notify.onError({
    title:    "Gulp error in " + err.plugin,
    message:  "<%= error.message %>",
    sound: "Beep"
  })(err);
  this.emit('end');
};

gulp.task('styleguide', function() {
  return gulp.src(config.patterns + '**/*.pug')
  .pipe(plumber({ handleError: onError }))
  .pipe(pug({ pretty: true })) // pipe to pug plugin
  .pipe(gulp.dest(config.dist)) // tell gulp our output folder
});

gulp.task('watch:styleguide', function () {
  return gulp.watch(config.patterns + '**/*', ['styleguide'] );
});

// ===================================================
// Scripts
// ===================================================

//copy drupal scripts from drupal to make them available for the styleguide
gulp.task('scripts-drupal', function() {
  return gulp.src([
    config.drupal + 'assets/vendor/domready/ready.min.js',
    config.drupal + 'assets/vendor/jquery/jquery.min.js',
    config.drupal + 'assets/vendor/jquery-once/jquery.once.min.js',
    config.drupal + '/misc/drupalSettingsLoader.js',
    config.drupal + '/misc/drupal.js',
    config.drupal + '/misc/debounce.js',
    config.drupal + '/misc/forms.js',
    config.drupal + '/misc/tabledrag.js',
    config.drupal + '/modules/user/user.js',
    config.drupal + '/modules/file/file.js'
  ])
  .pipe( concat('drupal-core.js') )
  .pipe( gulp.dest(config.dist + '/js') );
});

//copy scripts to dist
gulp.task('scripts', function() {
  return gulp.src(config.js + '/**/*')
  .pipe( gulp.dest(config.dist + '/js') );
});

gulp.task('watch:js', function () {
  return gulp.watch([config.js + '**/*.js', config.components + '**/*.js'], ['scripts', 'scripts-drupal'] );
});

// ===================================================
// Copy assets to dist folder
// ===================================================

gulp.task('images', function() {
  return gulp.src(config.images + '**/*')
  .pipe( gulp.dest(config.dist + 'images') );
});

gulp.task('watch:images', function () {
  return gulp.watch(config.images + '**/*', ['images'] );
});

gulp.task('content', function() {
  return gulp.src(config.content + '**/*')
  .pipe( gulp.dest(config.dist + 'content') );
});

gulp.task('watch:content', ['content'], function () {
  return gulp.watch(config.content + '**/*', ['content']);
});

gulp.task('font', function() {
  return gulp.src(config.font + '**/*')
  .pipe( gulp.dest(config.dist + 'font') );
});

gulp.task('watch:font', ['font'], function () {
  return gulp.watch(config.font + '**/*', ['font']);
});


// ===================================================
// Set up a server
// ===================================================

gulp.task('connect', function() {
  connect.server({
    root: [config.dist],
    livereload: false,
    port: 5000
  });
});


// ===================================================
// Build CSS.
// ===================================================

var postcss       = require('gulp-postcss'),
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

var options       = {};

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
    .pipe( gulp.dest(config.dist + '/css/components/asset-builds') );
});

gulp.task('watch:styles', ['styles'], function () {
  return gulp.watch(config.components + '**/*.scss', ['styles']);
});



// ===================================================
// Icons
// svgmin minifies our SVG files and strips out unnecessary code that you might inherit from your graphics editor. svgstore binds them together in one giant SVG container called icons.svg. Then cheerio gives us the ability to interact with the DOM components in this file in a jQuery-like way. cheerio in this case is removing any fill attributes from the SVG elements (you’ll want to use CSS to manipulate them) and adds a class of .hide to our parent SVG. It gets deposited into our inc directory with the rest of the HTML partials.
// ===================================================

var svgmin        = require('gulp-svgmin'),
    svgstore      = require('gulp-svgstore'),
    cheerio       = require('gulp-cheerio');


gulp.task('icons', function () {
  return gulp.src(config.icons + '*.svg')
    .pipe(svgmin())
    .pipe(svgstore({ fileName: 'icons.svg', inlineSvg: true}))
    .pipe(cheerio({
      run: function ($, file) {
        $('svg').addClass('hide');
      },
      parserOptions: { xmlMode: true }
    }))
    .pipe(gulp.dest(config.images))
});

gulp.task('watch:icons', function () {
  return gulp.watch(config.icons + '**/*.svg', ['icons'] );
});



// ===================================================
// Deploy
// ===================================================

var rsync         = require('gulp-rsync'),
    prompt        = require('gulp-prompt'),
    gutil         = require('gulp-util'),
    gulpif        = require('gulp-if'),
    argv          = require('minimist')(process.argv);

  try {
    var deploy        = require('./deploy-config.json');
  } catch(error) {
    console.log('Deploy config file missing');
  }


// Generate an error for deploy if something goes wrong
function throwError(taskName, msg) {
  throw new gutil.PluginError({
    plugin: taskName,
    message: msg
  });
}

gulp.task('build', ['styles', 'styleguide' , 'scripts', 'font', 'images', 'content']);

gulp.task('deploy', ['build'], function() {
  // Dirs and Files to sync

  // Default options for rsync
  var rsyncConf = {
    progress: true,
    incremental: true,
    relative: true,
    emptyDirectories: true,
    recursive: true,
    clean: true,
    exclude: [],
  };

  if (argv.production) {
    rsyncConf.hostname = deploy.hostname; // hostname
    rsyncConf.username = deploy.username; // ssh username
    rsyncConf.destination = deploy.destination; // path where uploaded files go
    rsyncConf.root = 'dist/';
  // Missing/Invalid Target
  } else {
    throwError('deploy', gutil.colors.red('Missing or invalid target'));
  }

  // Use gulp-rsync to sync the files
  return gulp.src(config.dist + '/**/*')
  .pipe(gulpif(
      argv.production,
      prompt.confirm({
        message: 'Heads Up! Are you SURE you want to push to PRODUCTION?',
        default: false
      })
  ))
  .pipe(rsync(rsyncConf));

});

// ===================================================
// Watch and rebuild tasks
// ===================================================

gulp.task('default', ['watch:styles', 'watch:styleguide', 'watch:content', 'watch:js', 'watch:icons', 'watch:images', 'connect']);
