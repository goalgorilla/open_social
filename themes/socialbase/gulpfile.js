'use strict';

// ===================================================
// Global packages
// ===================================================

var importOnce    = require('node-sass-import-once'),
    path          = require('path'),
    notify        = require("gulp-notify"),
    bower         = require("gulp-bower");

var options = {};

options.basetheme = {
  root       : __dirname,
  bowerDir   : __dirname + '/libraries/',
  components : __dirname + '/components/',
  build      : __dirname + '/assets/',
  css        : __dirname + '/assets/css/',
  js         : __dirname + '/assets/js/'
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
  dest  : options.basetheme.build + 'icons/'
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
    .pipe($.sass(options.sass).on('error', sass.logError))
    .pipe($.plumber({ errorHandler: onError }) )
    .pipe($.postcss(sassProcessors) )
    .pipe($.rucksack() )
    .pipe($.rename({dirname: ''}))
    .pipe($.size({showFiles: true}))
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
// Run bower
// ===================================================
gulp.task('bower', function () {
  return bower()
    .pipe(gulp.dest(options.basetheme.bowerDir))
});

// ===================================================
// Move and minify JS.
// ===================================================
gulp.task('minify-scripts', ['clean:js'], function () {
  return gulp.src([
    options.basetheme.components + '**/*.js',
    options.basetheme.bowerDir + '**/raphael.js',
    options.basetheme.bowerDir + '**/morris.js'
    ]
  )
    .pipe($.uglify())
    .pipe($.flatten())
    .pipe($.rename({
      suffix: ".min"
    }))
    .pipe(gulp.dest(options.basetheme.js));
});


// ===================================================
// Icons
// svgmin minifies our SVG files and strips out unnecessary code that you might inherit from your graphics editor. svgstore binds them together in one giant SVG container called icons.svg. Then cheerio gives us the ability to interact with the DOM components in this file in a jQuery-like way. cheerio in this case is removing any fill attributes from the SVG elements (you’ll want to use CSS to manipulate them) and adds a class of .hide to our parent SVG. It gets deposited into our inc directory with the rest of the HTML partials.
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

gulp.task('image-icons', function () {
  return gulp.src(options.icons.src + '*.svg')
    .pipe(svgmin())
    .pipe(gulp.dest(options.basetheme.build + 'images/icons/'))
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
  return gulp.watch(options.icons.src + '**/*.svg', ['sprite-icons'] );
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

// Clean JS files.
gulp.task('clean:js', function () {
  return del([
    options.basetheme.js + '**/*.js'
  ], {force: true});
});


// ##############################################################################################################

// Old style guide -- needs to be removed when twig styleguide is set up

var config = {
  "dist"        : "dist/",
  "build"       : "assets/",
  "components"  : "components/",
  "content"     : "content/",
  "css"         : "assets/css/",
  "font"        : "../socialblue/assets/font/",
  "icons"       : "assets/icons/",
  "images"      : "assets/images/",
  "js"          : "assets/js/",
  "patterns"    : "pug/",
  "drupal"      : "../../../../../core/"
}

var pug           = require('gulp-pug'),
    connect       = require('gulp-connect'),
    concat        = require('gulp-concat'),
    notify        = require('gulp-notify'),
    path          = require('path'),
    plumber       = require('gulp-plumber');


gulp.task('styleguide', function() {
  return gulp.src(config.patterns + '**/*.pug')
  .pipe(plumber({ handleError: onError }))
  .pipe(pug({ pretty: true })) // pipe to pug plugin
  .pipe(gulp.dest(config.dist)) // tell gulp our output folder
});

gulp.task('watch:styleguide', ['styleguide'], function () {
  return gulp.watch(config.patterns + '**/*', ['styleguide'] );
});

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


// ===================================================
// Copy assets to dist folder
// ===================================================

gulp.task('sg-images', function() {
  return gulp.src(config.images + '**/*')
  .pipe( gulp.dest(config.dist + 'assets/images') );
});

gulp.task('sg-content', function() {
  return gulp.src(config.content + '**/*')
  .pipe( gulp.dest(config.dist + 'assets/content') );
});

gulp.task('sg-icons', function() {
  return gulp.src(config.icons + '**/*')
  .pipe( gulp.dest(config.dist + 'assets/icons') );
});

gulp.task('sg-font', function() {
  return gulp.src(config.font + '**/*')
  .pipe( gulp.dest(config.dist + 'assets/font') );
});

gulp.task('sg-stylesheets', function() {
  return gulp.src(config.css + '**/*')
  .pipe( gulp.dest(config.dist + 'assets/css') );
});

gulp.task('watch:sg-stylesheets', ['styles'], function () {
  return gulp.watch(config.css + '**/*', ['sg-stylesheets'] );
});

gulp.task('sg-scripts', function() {
  return gulp.src(config.js + '/**/*')
  .pipe( gulp.dest(config.dist + 'assets/js') );
});

gulp.task('blue-styleguide-stylesheets', function() {
  return gulp.src('../socialblue/components/styleguide/**/*')
  .pipe( gulp.dest(config.dist + 'assets/js') );
});

gulp.task('css-assets-blue', function () {
  return gulp.src('../socialblue/assets/css/*.css')
  .pipe(gulp.dest(config.dist + 'assets/css/blue') );
});

gulp.task('js-assets-blue', function () {
  return gulp.src('../socialblue/assets/js/*.js')
  .pipe(gulp.dest(config.dist + 'assets/js/blue') );
});


gulp.task('watch:sg-scripts', function () {
  return gulp.watch(config.js + '**/*', ['sg-scripts'] );
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
// Deploy
// ===================================================

var rsync         = require('gulp-rsync'),
    prompt        = require('gulp-prompt'),
    gutil         = require('gulp-util'),
    gulpif        = require('gulp-if'),
    argv          = require('minimist')(process.argv);

  try {
    var deploy    = require('./deploy_config.json');
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

gulp.task('build', ['styleguide', 'sg-stylesheets', 'sg-scripts', 'sg-font', 'sg-images', 'sg-icons', 'sg-content']);

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
    rsyncConf.root = config.dist;
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

gulp.task('default', ['watch:css', 'watch:styleguide', 'watch:sg-stylesheets', 'watch:sg-scripts', 'connect']);
