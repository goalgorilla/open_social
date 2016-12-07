
// ===================================================
// Icons
// svgmin minifies our SVG files and strips out unnecessary code that you might inherit from your graphics editor. svgstore binds them together in one giant SVG container called icons.svg. Then cheerio gives us the ability to interact with the DOM components in this file in a jQuery-like way. cheerio in this case is removing any fill attributes from the SVG elements (you’ll want to use CSS to manipulate them) and adds a class of .hide to our parent SVG. It gets deposited into our inc directory with the rest of the HTML partials.
// ===================================================

var gulp          = require('gulp'),
    svgmin        = require('gulp-svgmin'),
    svgstore      = require('gulp-svgstore'),
    cheerio       = require('gulp-cheerio');
var config        = require('./config.json');


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
