# Socialbase theme #
The Socialbase theme is designed to provide a ready-made theme with the option to customize it easily in a subtheme.

# Changing the style of the theme  #
You can create a new sub theme like you normally would do and set Socialbase as the base theme. Now you can replace, extend or add your own libraries. We try to keep the Socialbase structured into components so they can be easily overridden in a sub theme.  

# Contributing #

Socialbase uses Gulp.js as a task runner, so that it can do many different tasks automatically:
 - Build CSS from your Sass using libSass and node-sass.
 - Add vendor prefixes for the browsers you want to support using Autoprefixer.
 - Build a style guide of your components with pug templates.
 - Watch all of your files as you develop and re-build everything on the fly.

Set up your front-end development build tools:

1. Install Node.js and npm, the Node.js package manager.

2. The package.json file contains the versions of all the Node.js software you need. To install them run:
    ```
    npm install
    ```

3. Install the gulp-cli tool globally. Normally, installing a Node.js globally
  is not recommended, which is why both Gulp and Grunt have created wrapper
  commands that will allow you to run "gulp" or "grunt" from anywhere, while
  using the local version of gulp or grunt that is installed in your project.
  To install gulp's global wrapper, run:
    ```
    npm install -g gulp-cli
    ```

4. The default gulp task will build the CSS, build the style guide, and lint
  your Sass and JavaScript. To run the default gulp task, type:
    ```
    gulp
    ```
  To get started the first time with the style guide you need to copy some files to the dist folder where the style guide lives, type:
    ```
    gulp init
    ```
