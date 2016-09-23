// ===================================================
// Deploy
// ===================================================

var gulp          = require('gulp'),
    rsync         = require('gulp-rsync'),
    prompt        = require('gulp-prompt'),
    gutil         = require('gulp-util'),
    gulpif        = require('gulp-if'),
    argv          = require('minimist')(process.argv);

var config        = require('../secret/config.json');


// Generate an error for deploy if something goes wrong
function throwError(taskName, msg) {
  throw new gutil.PluginError({
    plugin: taskName,
    message: msg
  });
}

gulp.task('build', ['styles', 'styleguide' , 'scripts', 'font', 'images', 'content']);

gulp.task('deploy', function() {
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
  return gulp.src(config.dist + '**/*')
  .pipe(gulpif(
      argv.production,
      prompt.confirm({
        message: 'Heads Up! Are you SURE you want to push to PRODUCTION?',
        default: false
      })
  ))
  .pipe(rsync(rsyncConf));

});
