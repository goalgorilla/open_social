// ===================================================
// Template files (Pug)
// ===================================================

var gulp          = require('gulp'),
    pug           = require('gulp-pug'),
    connect       = require('gulp-connect'),
    concat        = require('gulp-concat'),
    notify        = require('gulp-notify'),
    path          = require('path'),
    plumber       = require('gulp-plumber');

var config        = require('./config.json');

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
    .pipe(plumber({
      handleError: onError
    }))

    .pipe(pug({
      pretty: true
    })) // pipe to pug plugin

    .pipe(gulp.dest(config.dist)) // tell gulp our output folder

});

gulp.task('watch:styleguide', function () {
  return gulp.watch(config.patterns + '**/*', ['styleguide'] );
});

// ===================================================
// Scripts
// ===================================================

//copy drupal scripts from drupal to make them available for the styleguide
gulp.task('script-drupal', function() {
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

gulp.task('watch:js' , function () {
  return gulp.watch([config.js + '**/*.js', config.components + '**/*.js'], ['copy-scripts', 'script-drupal'] );
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
