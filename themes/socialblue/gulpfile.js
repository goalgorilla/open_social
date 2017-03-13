/* eslint-env node, es6 */
/* global Promise */
/* eslint-disable key-spacing, one-var, no-multi-spaces, max-nested-callbacks, quote-props */

'use strict';

// ################################
// Load Gulp and tools we will use.
// ################################

var importOnce  = require('node-sass-import-once'),
    path        = require('path'),
    gulp        = require('gulp'),
    $           = require('gulp-load-plugins')(),
    browserSync = require('browser-sync').create(),
    del         = require('del'),
    // gulp-load-plugins will report "undefined" error unless you load gulp-sass manually.
    sass        = require('gulp-sass'),
    kss         = require('kss'),
    postcss     = require('gulp-postcss'),
    autoprefixer= require('autoprefixer'),
    mqpacker    = require('css-mqpacker'),
    concat      = require('gulp-concat'),
    runSequence = require('run-sequence');

var options = {};

// #############################
// Edit these paths and options.
// #############################

// The root paths are used to construct all the other paths in this
// configuration. The "project" root path is where this gulpfile.js is located.
// While Open Social distributes this in the theme root folder, you can also put this
// (and the package.json) in your project's root folder and edit the paths
// accordingly.
options.rootPath = {
  project     : __dirname + '/',
  styleGuide  : __dirname + '/styleguide/',
  theme       : __dirname + '/',
  basetheme   : __dirname + '/../socialbase/',
  drupal      : __dirname + '/../../../../../core/'
};

options.theme = {
  name       : 'socialblue',
  root       : options.rootPath.theme,
  components : options.rootPath.theme + 'components/',
  build      : options.rootPath.theme + 'assets/',
  css        : options.rootPath.theme + 'assets/css/',
  js         : options.rootPath.theme + 'assets/js/'
};

options.basetheme = {
  name       : 'socialbase',
  root       : options.rootPath.basetheme,
  components : options.rootPath.basetheme + 'components/',
  build      : options.rootPath.basetheme + 'assets/',
  css        : options.rootPath.basetheme + 'assets/css/',
  js         : options.rootPath.basetheme + 'assets/js/'
};


// Set the URL used to access the Drupal website under development. This will
// allow Browser Sync to serve the website and update CSS changes on the fly.
options.drupalURL = '';
// options.drupalURL = 'http://social.dev:32799';

// Define the node-sass configuration. The includePaths is critical!
options.sass = {
  importer: importOnce,
  includePaths: [
    options.theme.components,
  ],
  outputStyle: 'expanded'
};

var sassFiles = [
  options.theme.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.theme.components + '**/_*.scss'
];


// On screen notification for errors while performing tasks
var onError = function(err) {
  notify.onError({
    title:    "Gulp error in " + err.plugin,
    message:  "<%= error.message %>",
    sound: "Beep"
  })(err);
  this.emit('end');
};


// Define the style guide paths and options.
options.styleGuide = {
  source: [
    options.theme.components,
    options.basetheme.components
  ],
  mask: /\.less|\.sass|\.scss|\.styl|\.stylus/,
  destination: options.rootPath.styleGuide,

  builder: 'os-builder',
  namespace: options.theme.name + ':' + options.theme.components,
  'extend-drupal8': true,

  // The css and js paths are URLs, like '/misc/jquery.js'.
  // The following paths are relative to the generated style guide.
  css: [
    // Base stylesheets
    'kss-assets/base/base.css',
    'kss-assets/css/base.css',
    // Atom stylesheets
    'kss-assets/base/alerts.css',
    'kss-assets/css/alerts.css',
    'kss-assets/base/badges.css',
    'kss-assets/css/badges.css',
    'kss-assets/base/button.css',
    'kss-assets/css/button.css',
    'kss-assets/base/cards.css',
    'kss-assets/css/cards.css',
    'kss-assets/base/form-controls.css',
    'kss-assets/css/form-controls.css',
    'kss-assets/base/labels.css',
    'kss-assets/css/list-group.css',
    'kss-assets/base/list-group.css',
    'kss-assets/css/labels.css',
    'kss-assets/base/close-icon.css',
    'kss-assets/css/waves.css',
    // Molecule stylesheets
    'kss-assets/base/dropdown.css',
    'kss-assets/css/dropdown.css',
    'kss-assets/base/file.css',
    'kss-assets/css/file.css',
    'kss-assets/base/form-elements.css',
    'kss-assets/css/form-elements.css',
    'kss-assets/base/datepicker.css',
    'kss-assets/css/datepicker.css',
    'kss-assets/base/input-groups.css',
    'kss-assets/css/input-groups.css',
    'kss-assets/base/password.css',
    'kss-assets/css/password.css',
    'kss-assets/base/timepicker.css',
    'kss-assets/css/timepicker.css',
    'kss-assets/base/media.css',
    'kss-assets/base/mention.css',
    'kss-assets/css/mention.css',
    'kss-assets/base/breadcrumb.css',
    'kss-assets/css/breadcrumb.css',
    'kss-assets/base/nav-book.css',
    'kss-assets/css/nav-book.css',
    'kss-assets/base/nav-tabs.css',
    'kss-assets/css/nav-tabs.css',
    'kss-assets/base/navbar.css',
    'kss-assets/css/navbar.css',
    'kss-assets/base/pagination.css',
    'kss-assets/css/pagination.css',
    'kss-assets/base/popover.css',
    'kss-assets/css/popover.css',
    'kss-assets/base/teaser.css',
    'kss-assets/css/teaser.css',
    'kss-assets/base/tour.css',
    'kss-assets/css/tour.css',
    // Organisms stylesheets
    'kss-assets/base/comment.css',
    'kss-assets/css/comment.css',
    'kss-assets/base/hero.css',
    'kss-assets/css/hero.css',
    'kss-assets/base/meta.css',
    'kss-assets/css/meta.css',
    'kss-assets/base/offcanvas.css',
    'kss-assets/css/offcanvas.css',
    'kss-assets/css/site-footer.css',
    'kss-assets/base/stream.css',
    'kss-assets/css/stream.css',
    // Template stylesheets
    'kss-assets/base/layout.css',
    'kss-assets/base/page-node.css',
    'kss-assets/css/page-node.css',
    // Javascript stylesheets
    'kss-assets/base/morrisjs.css',
    // Styleguide stylesheets
    'kss-assets/css/styleguide.css'
  ],
  js: [
    'kss-assets/js/waves.min.js'
  ],
  homepage: 'homepage.md',
  title: 'Style Guide for Open Social Blue'
};



// If your files are on a network share, you may want to turn on polling for
// Gulp watch. Since polling is less efficient, we disable polling by default.
options.gulpWatchOptions = {};
// options.gulpWatchOptions = {interval: 1000, mode: 'poll'};


var sassProcessors = [
  autoprefixer({browsers: ['> 1%', 'last 2 versions']}),
  mqpacker({sort: true})
];



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

gulp.task('styles', function () {
  return gulp.src(sassFiles)
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.plumber({ errorHandler: onError }) )
    // run autoprefixer and media-query packer
    .pipe($.postcss(sassProcessors) )
    // run rucksack @see https://simplaio.github.io/rucksack/
    .pipe($.rucksack() )
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.theme.css))
    .pipe($.if(browserSync.active, browserSync.stream({match: '**/*.css'})));
});


// ##################
// Build style guide.
// ##################

// Compile and copy the socialbase styles to the style guide
gulp.task('styleguide-assets-base', function () {
  return gulp.src(options.basetheme.css +'*.css')
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/base/'))
});

// Compile and copy the subtheme assets to the style guide
gulp.task('styleguide-assets', function () {
  return gulp.src(options.theme.build +'**/*')
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/'))
});

// Copy the mime icons from the components folder to the styleguide assets folder (manual task)
gulp.task('styleguide-mime-image-icons', function () {
  return gulp.src(options.basetheme.components + '06-libraries/icons/source/mime-icons/*.png')
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/'))
});

// Main styleguide task
gulp.task('styleguide', ['clean:styleguide'], function () {
  return kss(options.styleGuide);
});

// Before deploying create a fresh build
gulp.task('build-styleguide', function(done) {
  runSequence('styleguide',
    ['styleguide-assets-base', 'styleguide-assets', 'styleguide-mime-image-icons'],
    done);
});

// Debug the generation of the style guide with the --verbose flag.
gulp.task('styleguide:debug', ['clean:styleguide', 'scripts-drupal'], function () {
  options.styleGuide.verbose = true;
  return kss(options.styleGuide);
});

// Copy drupal scripts from drupal to make them available for the styleguide
gulp.task('scripts-drupal', function() {
  return gulp.src([
    options.rootPath.drupal + 'assets/vendor/domready/ready.min.js',
    options.rootPath.drupal + 'assets/vendor/jquery/jquery.min.js',
    options.rootPath.drupal + 'assets/vendor/jquery-once/jquery.once.min.js',
    options.rootPath.drupal + '/misc/drupalSettingsLoader.js',
    options.rootPath.drupal + '/misc/drupal.js',
    options.rootPath.drupal + '/misc/debounce.js',
    options.rootPath.drupal + '/misc/forms.js',
    options.rootPath.drupal + '/misc/tabledrag.js',
    options.rootPath.drupal + '/modules/user/user.js',
    options.rootPath.drupal + '/modules/file/file.js'
  ])
    .pipe( concat('drupal-core.js') )
    .pipe( gulp.dest(options.rootPath.styleGuide + 'kss-assets/') );
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
//
// Watch for changes and rebuild.
//
// ##############################

gulp.task('watch', ['browser-sync', 'watch:styleguide', 'watch:js']);

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
  return gulp.watch(options.theme.components + '**/*.scss', ['styles']);
});

gulp.task('watch:styleguide', ['styleguide:debug', 'build-styleguide'], function () {
  return gulp.watch([
    options.basetheme.components + '**/*.scss',
    options.theme.components + '**/*.scss',
    options.basetheme.components + '**/*.twig',
    options.theme.components + '**/*.twig',
    options.theme.components + '**/*.md'
  ], options.gulpWatchOptions, 'build-styleguide');
});

// #################
//
// Minify JS
//
// #################
//
// First clean the JS folder, then search all components for js files.
// Then compress the files, give them an explicit .min filename and
// save them to the assets folder.
// ===================================================

gulp.task('watch:js', function () {
  return gulp.src(options.theme.components + '**/*.js')
    .pipe($.uglify())
    .pipe($.flatten())
    .pipe($.rename({
      suffix: ".min"
    }))
    .pipe(gulp.dest(options.theme.js));
});





// ######################
//
// Clean all directories.
//
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


// ######################
// Deploy
// ######################
//
// We use rsync to publish the style guide online
// ===================================================

var rsync         = require('gulp-rsync'),
    prompt        = require('gulp-prompt');

try {
  var deploy    = require('./deploy_config.json');
} catch(error) {
  console.log('Deploy config file missing');
}

// Before deploying create a fresh build
gulp.task('deploy', ['build-styleguide'], function() {

  // Default options for rsync
  var rsyncConf = {
    progress: true,
    incremental: true,
    emptyDirectories: true
    //clean: true,
  };

  // Load the settings from our deploy_config.json file
  // This file can be made from the deploy_config-example.json
  // and should be in gitignore
  rsyncConf.hostname = deploy.hostname; // hostname
  rsyncConf.username = deploy.username; // ssh username
  rsyncConf.destination = deploy.destination; // path where uploaded files go
  rsyncConf.root = options.rootPath.styleGuide;

  // Use gulp-rsync to sync the files
  // and perform a final check before pushing
  return gulp.src(options.rootPath.styleGuide + '/**/*')
    .pipe(
      prompt.confirm({
        message: 'Heads Up! Are you SURE you want to push to PRODUCTION?',
        default: false
      })
    )
    .pipe(rsync(rsyncConf));

});



// ######################
//
// Default task (no watching)
//
// ######################
gulp.task('default', ['styles', 'styleguide']);
