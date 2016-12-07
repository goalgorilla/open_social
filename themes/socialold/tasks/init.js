// ===================================================
// Import Bootstrap assets
// ===================================================

var gulp          = require('gulp');
var config        = require('./config.json');


gulp.task('bootstrap-sass', function() {
  return gulp.src(config.bootstrap + 'stylesheets/bootstrap/' + '**/*.scss' )
    .pipe( gulp.dest(config.components + '/contrib/bootstrap') );
});

gulp.task('bootstrap-js', function() {
  return gulp.src(config.bootstrap + 'javascripts/bootstrap/*.js')
    .pipe( gulp.dest(config.js + '/contrib/bootstrap') );
});
