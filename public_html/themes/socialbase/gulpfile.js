// ===================================================
// Required packages
// ===================================================

var gulp          = require('gulp'),
    postcss       = require('gulp-postcss'),
    sass          = require('gulp-sass'),
    sourcemaps    = require('gulp-sourcemaps'),
    autoprefixer  = require('autoprefixer'),
    mqpacker      = require('css-mqpacker'),
    precss        = require('precss'),
    lost          = require('lost'),
    rucksack      = require('gulp-rucksack'),
    jade          = require('gulp-jade'),
    path          = require('path'),
    fs            = require('fs'),
    concat        = require('gulp-concat'),
    notify        = require('gulp-notify'),
    gutil         = require('gulp-util'),
    uglify        = require('gulp-uglify'),
    nano          = require('gulp-cssnano'),
    connect       = require('gulp-connect'),
    plumber       = require('gulp-plumber'),
    deploy        = require('gulp-gh-pages');

// ===================================================
// Config
// ===================================================

var folder = {
  css: 'css',
  scss: 'css/src',
  bootstrap_scss: 'node_modules/bootstrap-sass/assets/stylesheets/bootstrap',
  bootstrap_js: 'node_modules/bootstrap-sass/assets/javascripts',
  js: 'js',
  js_comp: 'js/components',
  js_materialize: 'js/materialize',
  js_vendor: '../../core/assets/vendor',
  js_drupal: '../../core',
  jade: 'jade',
  dist: 'dist'
}

var glob = {
  css: folder.css + '/*.css',
  scss: folder.css + '/src/**/*.scss',
  bootstrap_scss: folder.bootstrap_scss + '/**/*.scss',
  js: folder.js + '/**/*.js',
  jade: folder.jade + '/*.jade',
  font: 'font/**/*',
  images: 'images/**/*',
  content: 'content/**/*',
  libs: 'libs/**/*'
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
// Styles
// ===================================================

gulp.task('css', function () {

  var processors = [
    autoprefixer({browsers: ['last 2 versions']}),
    mqpacker({sort: true}),
    lost()
  ];

  var stream = gulp.src(folder.scss + '/*.scss')
    .pipe(plumber({
      errorHandler: onError
    }))
    .pipe( sourcemaps.init() )
    .pipe( sass() )
    .pipe( nano( {
      mergeRules: true
    }) )
    .pipe( postcss(processors) )
    .pipe( rucksack() )
    .pipe( sourcemaps.write('.') )
    .pipe( gulp.dest(folder.css) )
    .pipe( gulp.dest(folder.dist + '/css') )
    .pipe( connect.reload() );
  return stream;

});

// ===================================================
// Template file (Jade)
// ===================================================

gulp.task('jade', function() {

  return gulp.src(glob.jade)
    .pipe(plumber({
      handleError: onError
    }))
    .pipe(jade({
      pretty: true
    })) // pip to jade plugin
    .pipe(gulp.dest(folder.dist)); // tell gulp our output folder
});



// ===================================================
// Scripts
// ===================================================

// get component scripts and make available for dist in one file
gulp.task('script-components', function() {
  return gulp.src([
    folder.js_comp + "/initial.js",
    folder.js_comp + "/jquery.easing.1.3.js",
    folder.js_comp + "/animation.js",
    folder.js_comp + "/velocity.min.js",
    folder.js_comp + "/hammer.min.js",
    folder.js_comp + "/jquery.hammer.js",
    folder.js_comp + "/global.js",
    folder.js_comp + "/collapsible.js",
    folder.js_comp + "/scrollspy.js",
    folder.js_comp + "/pushpin.js",
    folder.js_comp + "/sideNav.js",
    folder.js_comp + "/waves.js",
    folder.js_comp + "/offcanvas.js",
    folder.js_comp + "/forms.js"
    ])
    .pipe( concat('components.js') )
    .pipe( gulp.dest(folder.js) )
    //.pipe( uglify() )
    .pipe( gulp.dest(folder.dist + '/js') );
});

// get project scripts and make available for dist in one file
gulp.task('script-materialize', function() {
  return gulp.src([
    folder.js_materialize + "/navbar-search.js",
    ])
    .pipe( concat('materialize.js') )
    .pipe( gulp.dest(folder.js) )
    //.pipe( uglify() )
    .pipe( gulp.dest(folder.dist + '/js') );
});

//copy vendor scripts from drupal to make them available for the styleguide
gulp.task('script-vendor', function() {
  return gulp.src([
    folder.js_vendor + '/domready/ready.min.js',
    folder.js_vendor + '/jquery/jquery.min.js',
    folder.js_vendor + '/jquery-once/jquery.once.min.js',
    'js/vendor/jquery.touch-swipe.js'
  ])
  .pipe( concat('vendor.js') )
  .pipe( gulp.dest(folder.dist + '/js') );
});

gulp.task('jqueryminmap', function() {
  return gulp.src(folder.js_vendor + '/jquery/jquery.min.map')
  .pipe( gulp.dest(folder.dist + '/js') );
});

//copy vendor scripts from drupal to make them available for the styleguide
gulp.task('script-drupal', function() {
  return gulp.src([
    folder.js_drupal + '/misc/drupalSettingsLoader.js',
    folder.js_drupal + '/misc/drupal.js',
    folder.js_drupal + '/misc/debounce.js',
    folder.js_drupal + '/misc/forms.js',
    folder.js_drupal + '/modules/user/user.js',
    folder.js_drupal + '/modules/file/file.js'
  ])
  .pipe( concat('drupal-core.js') )
  .pipe( gulp.dest(folder.dist + '/js') );
});

//copy init script to the styleguide
gulp.task('script-init', function() {
  return gulp.src([folder.js + "/init.js"])
  .pipe( gulp.dest(folder.dist + '/js') );
});

// ===================================================
// Fonts
// ===================================================

gulp.task('font', function() {
  stream = gulp.src(glob.font)
    .pipe( gulp.dest(folder.dist + '/font') )
    .pipe( connect.reload() );
  return stream;
});

// ===================================================
// Images
// ===================================================

gulp.task('images', function() {
  stream = gulp.src(glob.images)
    .pipe( gulp.dest(folder.dist + '/images') )
    .pipe( connect.reload() );
  return stream;
});

gulp.task('content', function() {
  stream = gulp.src(glob.content)
    .pipe( gulp.dest(folder.dist + '/content') )
    .pipe( connect.reload() );
  return stream;
});

// ===================================================
// Extras
// ===================================================

gulp.task('libs', function() {
  stream = gulp.src(glob.libs)
    .pipe( gulp.dest(folder.dist + '/libs') )
  return stream;
});


// ===================================================
// Import Bootstrap assets
// ===================================================

gulp.task('bootstrap-sass', function() {
  stream = gulp.src(glob.bootstrap_scss)
    .pipe( gulp.dest(folder.scss + '/bootstrap') )
  return stream;
});

gulp.task('bootstrap-js', function() {
  stream = gulp.src(folder.bootstrap_js + '/bootstrap.min.js')
    .pipe( gulp.dest(folder.js) )
    .pipe( gulp.dest(folder.dist + "/js") )
  return stream;
});


// ===================================================
// Set up a server
// ===================================================

gulp.task('connect', function() {
  connect.server({
    root: [folder.dist],
    livereload: true,
    port: 5000
  });
});


// ===================================================
// Watch dev tasks
// ===================================================

gulp.task('watch', function() {
  gulp.watch([
    glob.scss
  ], ['css']);

  gulp.watch([
    folder.jade + '/**/*'
  ], ['jade']);

  gulp.watch([
    folder.js_comp + '/**/*.js'
  ], ['scripts']);

  gulp.watch([
    folder.js_materialize + '/**/*.js'
  ], ['script-materialize']);

  gulp.watch([
    folder.js + "/init.js"
  ], ['script-init']);

  gulp.watch([
    glob.font
  ], ['font']);

  gulp.watch([
    glob.images
  ], ['images']);

  gulp.watch([
    glob.content
  ], ['content']);

});

// ===================================================
// Deploy to github pages branch
// ===================================================

gulp.task('deploy', ['build'], function() {
  return gulp.src([folder.dist + '/**/*'])
    .pipe( deploy() );
});


// ===================================================
// Run this one time when you install the project so you have all files in the dist folder
// ===================================================
gulp.task('init', ['images', 'content', 'libs', 'font', 'jqueryminmap']);


gulp.task('scripts', ['script-components', 'script-materialize', 'script-vendor', 'script-drupal', 'script-init']);

gulp.task('build', ['css', 'jade' , 'scripts', 'font', 'images']);

gulp.task('default', ['css', 'jade' , 'scripts', 'connect', 'watch']);
