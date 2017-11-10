'use strict';

// ===================================================
// Load Gulp plugins
// ===================================================

var importOnce    = require('node-sass-import-once'),
    notify        = require("gulp-notify"),
    gulp          = require('gulp'),
    $             = require('gulp-load-plugins')(),
    browserSync   = require('browser-sync').create(),
    del           = require('del'),
    // gulp-load-plugins will report "undefined" error unless you load gulp-sass manually.
    sass          = require('gulp-sass'),
    autoprefixer  = require('autoprefixer'),
    mqpacker      = require('css-mqpacker');

var options = {};

options.basetheme = {
  root       : __dirname,
  components : __dirname + '/components/',
  build      : __dirname + '/assets/',
  css        : __dirname + '/assets/css/',
  js         : __dirname + '/assets/js/'
};

options.icons = {
  src   : options.basetheme.components + '06-libraries/icons/source/',
  dest  : options.basetheme.build + 'icons/'
};


// Set the URL used to access the Drupal website under development. This will
// allow Browser Sync to serve the website and update CSS changes on the fly.
options.drupalURL = '';
// options.drupalURL = 'http://social.dev';

// Define the node-sass configuration. The includePaths is critical!
options.sass = {
  importer: importOnce,
  includePaths: [
    options.basetheme.components
  ],
  outputStyle: 'expanded'
};

var sassFiles = [
  options.basetheme.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.basetheme.components + '**/_*.scss'
];

var sassProcessors = [
  autoprefixer({browsers: ['> 1%', 'last 2 versions']}),
  mqpacker({sort: true})
];

var onError = function(err) {
  notify.onError({
    title:    "Gulp error in " + err.plugin,
    message:  "<%= error.message %>",
    sound: "Beep"
  })(err);
  this.emit('end');
};


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

gulp.task('styles', ['clean:css'], function () {
  return gulp.src(sassFiles)
    .pipe($.sass(options.sass).on('error', sass.logError))
    .pipe($.plumber({ errorHandler: onError }) )
    .pipe($.postcss(sassProcessors) )
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.basetheme.css))
    .pipe(browserSync.reload({stream:true}));
});



// #################
//
// Compile the Javascript
//
// #################
//
// This task will look for all js files, minifies theme
// and puts them in assets folder. When browsersync is enabled
// the browser will fetch the changes.
// ===================================================

gulp.task('scripts', ['clean:js'], function () {
  return gulp.src(options.basetheme.components + '**/*.js')
    .pipe($.uglify())
    .pipe($.flatten())
    .pipe($.rename({
      suffix: ".min"
    }))
    .pipe(gulp.dest(options.basetheme.js))
    .pipe(browserSync.reload({stream:true}));
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

gulp.task('sprite-icons', function () {
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



// #################
//
// Image icons
//
// #################
//
// Besides the sprite we sometimes still need the individual svg files
// to load as a css background image. This task optimises and copies
// the icons to the assets folder.
// ===================================================

gulp.task('image-icons', function () {
  return gulp.src(options.icons.src + '*.svg')
    .pipe(svgmin())
    .pipe(gulp.dest(options.basetheme.build + 'images/icons/'))
});

// #################
//
// Image mime icons
//
// #################
//
// Copy the mime icons from the components folder to the assets folder (manual task)
// ===================================================

gulp.task('mime-image-icons', function () {
  return gulp.src(options.icons.src + 'mime-icons/*.png')
    .pipe(gulp.dest(options.basetheme.build + 'images/mime-icons/'))
});



// ##############################
//
// Watch for changes and rebuild.
//
// ##############################

gulp.task('watch', ['watch:css', 'watch:js', 'watch:icons'], function () {
  if (!options.drupalURL) {
    return Promise.resolve();
  }
  return browserSync.init({
    proxy: options.drupalURL,
    open: false
  });
});

gulp.task('watch:css', ['styles'], function () {
  return gulp.watch(options.basetheme.components + '**/*.scss', ['styles']);
});


gulp.task('watch:js', ['scripts'], function () {
  return gulp.watch(options.basetheme.components + '**/*.js', ['scripts']);
});


gulp.task('watch:icons', function () {
  return gulp.watch(options.icons.src + '**/*.svg', ['sprite-icons', 'image-icons'] );
});



// ######################
//
// Clean all directories.
//
// ######################

// Clean CSS files.
gulp.task('clean:css', function () {
  return del([
    options.basetheme.css + '**/*.css',
    options.basetheme.css + '**/*.map'
  ], {force: true});
});

// Clean JS files.
gulp.task('clean:js', function () {
  return del([
    options.basetheme.js + '**/*.js'
  ], {force: true});
});

// ######################
//
// Default task (no watching)
//
// ######################

gulp.task('default', ['styles', 'image-icons', 'sprite-icons', 'scripts']);