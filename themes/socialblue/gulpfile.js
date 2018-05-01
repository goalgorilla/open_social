/* eslint-env node, es6 */
/* global Promise */
/* eslint-disable key-spacing, one-var, no-multi-spaces, max-nested-callbacks, quote-props */

'use strict';

// ################################
// Load Gulp and tools we will use.
// ################################

var importOnce  = require('node-sass-import-once'),
    gulp        = require('gulp'),
    $           = require('gulp-load-plugins')(),
    browserSync = require('browser-sync').create(),
    del         = require('del'),
    // gulp-load-plugins will report "undefined" error unless you load gulp-sass manually.
    sass        = require('gulp-sass'),
    kss         = require('kss'),
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
  drupal      : __dirname + '/../../../../../core/',
  libraries   : __dirname + '/../../../../../libraries/'
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
//options.drupalURL = 'http://social.dev';

// Define the node-sass configuration. The includePaths is critical!
options.sass = {
  importer: importOnce,
  includePaths: [
    options.theme.components
  ],
  outputStyle: 'expanded'
};

var sassFiles = [
  options.theme.components + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.theme.components + '**/_*.scss'
];


// On screen notification for errors while performing tasks
var onError = function (err) {
  notify.onError({
    title:    'Gulp error in ' + err.plugin,
    message:  '<%= error.message %>',
    sound: 'Beep'
  })(err);
  this.emit('end');
};


// Define the style guide paths and options.
options.styleGuide = {
  'source': [
    options.theme.components,
    options.basetheme.components
  ],
  'mask': /\.less|\.sass|\.scss|\.styl|\.stylus/,
  destination: options.rootPath.styleGuide,

  'builder': 'os-builder',
  'namespace': options.theme.name + ':' + options.theme.components,
  'extend-drupal8': true,

  // The css and js paths are URLs, like '/misc/jquery.js'.
  // The following paths are relative to the generated style guide.
  'css': [
    // Base stylesheets
    'kss-assets/base/css/base.css',
    'kss-assets/blue/css/base.css',
    // Atom stylesheets
    'kss-assets/base/css/alert.css',
    'kss-assets/blue/css/alert.css',
    'kss-assets/base/css/badge.css',
    'kss-assets/blue/css/badge.css',
    'kss-assets/base/css/button.css',
    'kss-assets/blue/css/button.css',
    'kss-assets/base/css/cards.css',
    'kss-assets/blue/css/cards.css',
    'kss-assets/base/css/form-controls.css',
    'kss-assets/blue/css/form-controls.css',
    'kss-assets/blue/css/list.css',
    'kss-assets/base/css/list.css',
    'kss-assets/base/css/spinner.css',
    'kss-assets/blue/css/spinner.css',
    'kss-assets/lib/waves.css',
    'kss-assets/blue/css/waves.css',
    // Molecule stylesheets
    'kss-assets/base/css/dropdown.css',
    'kss-assets/blue/css/dropdown.css',
    'kss-assets/base/css/file.css',
    'kss-assets/blue/css/file.css',
    'kss-assets/base/css/form-elements.css',
    'kss-assets/blue/css/form-elements.css',
    'kss-assets/base/css/autocomplete.css',
    'kss-assets/lib/select2.min.css',
    'kss-assets/base/css/select2.css',
    'kss-assets/blue/css/select2.css',
    'kss-assets/base/css/datepicker.css',
    'kss-assets/blue/css/datepicker.css',
    'kss-assets/base/css/input-groups.css',
    'kss-assets/blue/css/input-groups.css',
    'kss-assets/base/css/password.css',
    'kss-assets/blue/css/password.css',
    'kss-assets/base/css/timepicker.css',
    'kss-assets/blue/css/timepicker.css',
    'kss-assets/base/css/media.css',
    'kss-assets/base/css/mention.css',
    'kss-assets/blue/css/mention.css',
    'kss-assets/base/css/breadcrumb.css',
    'kss-assets/blue/css/breadcrumb.css',
    'kss-assets/base/css/nav-book.css',
    'kss-assets/blue/css/nav-book.css',
    'kss-assets/base/css/nav-tabs.css',
    'kss-assets/blue/css/nav-tabs.css',
    'kss-assets/base/css/navbar.css',
    'kss-assets/blue/css/navbar.css',
    'kss-assets/base/css/pagination.css',
    'kss-assets/blue/css/pagination.css',
    'kss-assets/base/css/popover.css',
    'kss-assets/blue/css/popover.css',
    'kss-assets/base/css/teaser.css',
    'kss-assets/blue/css/teaser.css',
    'kss-assets/base/css/tour.css',
    'kss-assets/blue/css/tour.css',
    // Organisms stylesheets
    'kss-assets/base/css/comment.css',
    'kss-assets/blue/css/comment.css',
    'kss-assets/base/css/form-default.css',
    'kss-assets/base/css/form-horizontal.css',
    'kss-assets/base/css/hero.css',
    'kss-assets/blue/css/hero.css',
    'kss-assets/base/css/meta.css',
    'kss-assets/blue/css/meta.css',
    'kss-assets/base/css/offcanvas.css',
    'kss-assets/blue/css/offcanvas.css',
    'kss-assets/blue/css/site-footer.css',
    'kss-assets/base/css/stream.css',
    'kss-assets/blue/css/stream.css',
    // Template stylesheets
    'kss-assets/base/css/layout.css',
    'kss-assets/base/css/page-node.css',
    'kss-assets/blue/css/page-node.css',
    // Javascript stylesheets
    'kss-assets/base/css/morrisjs.css',
    // Styleguide stylesheets
    'kss-assets/blue/css/styleguide.css'
  ],
  'homepage': 'homepage.md',
  'title': 'Style Guide for Open Social Blue'
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
    .pipe($.plumber({errorHandler: onError}))
    // run autoprefixer and media-query packer
    .pipe($.postcss(sassProcessors))
    // run rucksack @see https://simplaio.github.io/rucksack/
    .pipe($.rucksack())
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.theme.css))
    .pipe($.if(browserSync.active, browserSync.stream({match: '**/*.css'})));
});



// #################
//
// Minify scripts
//
// #################
//
// First clean the JS folder, then search all components for js files.
// Then compress the files, give them an explicit .min filename and
// save them to the assets folder.
// ===================================================

gulp.task('scripts', function () {
  return gulp.src(options.theme.components + '**/*.js')
    .pipe($.uglify())
    .pipe($.flatten())
    .pipe($.rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest(options.theme.js))
    .pipe(browserSync.reload({stream:true}));
});



// ##################
// Build style guide.
// ##################

// Main styleguide task
gulp.task('styleguide', ['clean:styleguide'], function () {
  return kss(options.styleGuide);
});

// Before deploying create a fresh build
gulp.task('build-styleguide', function (callback) {
  runSequence('styleguide',
    ['base-assets', 'blue-assets', 'brand', 'libraries', 'kss', 'scripts-drupal'],
    callback);
});

// Compile and copy the socialbase styles to the style guide
gulp.task('base-assets', function () {
  return gulp.src(options.basetheme.build + '**/*')
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/base/'));
});

// Compile and copy the subtheme assets to the style guide
gulp.task('blue-assets', function () {
  return gulp.src(options.theme.build + '**/*')
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/blue/'));
});

gulp.task('brand', function () {
  return gulp.src([
    'logo.svg',
    'favicon.ico'
  ])
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/blue/'));
});

// Copy drupal scripts from drupal to make them available for the styleguide
gulp.task('scripts-drupal', function () {
  return gulp.src([
    options.rootPath.drupal + 'assets/vendor/domready/ready.min.js',
    options.rootPath.drupal + 'assets/vendor/jquery/jquery.min.js',
    options.rootPath.drupal + 'assets/vendor/jquery-once/jquery.once.min.js',
    options.rootPath.drupal + '/misc/drupalSettingsLoader.js',
    options.rootPath.drupal + '/misc/drupal.js',
    options.rootPath.drupal + '/misc/debounce.js',
    options.rootPath.drupal + '/misc/tabledrag.js',
    options.rootPath.drupal + '/modules/user/user.js',
    options.rootPath.drupal + '/modules/file/file.js'
  ])
    .pipe(concat('drupal-core.js'))
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/lib/'));
});

// Copy libraries scripts from drupal libraries folder to make them available for the styleguide
gulp.task('libraries', function () {
  return gulp.src([
    options.rootPath.drupal + 'assets/vendor/jquery.ui/themes/base/menu.css',
    options.rootPath.drupal + 'assets/vendor/jquery.ui/themes/base/autocomplete.css',
    options.rootPath.libraries + 'bootstrap/dist/js/bootstrap.min.js',
    options.rootPath.libraries + 'morris.js/morris.min.js',
    options.rootPath.libraries + 'raphael/raphael.min.js',
    options.rootPath.libraries + 'waves/dist/waves.min.js',
    options.rootPath.libraries + 'waves/dist/waves.css',
    options.rootPath.libraries + 'autosize/dist/autosize.min.js',
    options.rootPath.libraries + 'select2/dist/css/select2.min.css',
    options.rootPath.libraries + 'select2/dist/js/select2.min.js'
  ])
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/lib/'));
});

gulp.task('kss', function () {
  return gulp.src([
    'node_modules/kss/builder/twig/kss-assets/kss-fullscreen.js',
    'node_modules/kss/builder/twig/kss-assets/kss-guides.js',
    'node_modules/kss/builder/twig/kss-assets/kss-markup.js',
    'node_modules/prismjs/prism.js',
    'node_modules/prismjs/components/prism-scss.min.js'
  ])
    .pipe(gulp.dest(options.rootPath.styleGuide + 'kss-assets/kss/'));
});

// #########################
// Color preview
//
// We generate a separate prefixed stylesheet for the preview
// so it will affect any element outside the preview.
// #########################

const prefixCss = require('gulp-prefix-css');

gulp.task('preview-base', function () {
  return gulp.src([
    options.basetheme.css + 'base.css',
    options.basetheme.css + 'badge.css',
    options.basetheme.css + 'button.css',
    options.basetheme.css + 'cards.css',
    options.basetheme.css + 'comment.css',
    options.basetheme.css + 'form-controls.css',
    options.basetheme.css + 'form-elements.css',
    options.basetheme.css + 'form-post-create.css',
    options.basetheme.css + 'hero.css',
    options.basetheme.css + 'layout.css',
    options.basetheme.css + 'media.css',
    options.basetheme.css + 'navbar.css',
    options.basetheme.css + 'nav-tabs.css',
    options.basetheme.css + 'stream.css',
    options.basetheme.css + 'teaser.css'
  ])
    .pipe(concat('preview-base.css'))
    .pipe(prefixCss('.color-preview'))
    .pipe(gulp.dest(options.theme.root + 'color/css/'));
});

gulp.task('preview-blue', function () {
  return gulp.src(options.theme.components + 'preview.scss')
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.plumber({errorHandler: onError}))
    // run autoprefixer and media-query packer
    .pipe($.postcss(sassProcessors))
    // run rucksack @see https://simplaio.github.io/rucksack/
    .pipe($.rucksack())
    .pipe(prefixCss('.color-preview'))
    .pipe(gulp.dest(options.theme.css));
});

gulp.task('preview', ['preview-base', 'preview-blue']);

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

gulp.task('watch', ['watch:css', 'watch:styleguide', 'watch:js'], function () {
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

gulp.task('watch:js', ['scripts'], function () {
  return gulp.watch(options.theme.components + '**/*.js', ['scripts']);
});

gulp.task('watch:styleguide', ['build-styleguide'], function () {
  return gulp.watch([
    options.basetheme.components + '**/*',
    options.theme.components + '**/*',
  ], options.gulpWatchOptions, ['build-styleguide']);
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
}
catch (error) {
  console.log('Deploy config file missing');
}

// Before deploying create a fresh build
gulp.task('deploy', ['build-styleguide'], function () {

  // Default options for rsync
  var rsyncConf = {
    progress: true,
    incremental: true,
    emptyDirectories: true
    // clean: true,
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
