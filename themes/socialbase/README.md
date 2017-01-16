# Base theme #
The Socialbase theme is designed as a base theme for Open Social. This base theme holds has a lot of sensible defaults. It doesn't however contain much styling. We expect every developer to want to change this for his/her project. As you can see there is also a theme called **socialblue**. Socialblue is the demo/styling sub theme for Open Social.

# Changing the style of the theme  #
If you are an experienced Drupal themer you know how to create a theme. There are no tricks in our setup you need to know to prevent errors. The easiest way to start is to grab a copy of Socialblue and rename all instances of socialblue to your new theme name. Socialbase and socialblue will get updates each release, so it is best not to make changes in here you want to keep.

 Make sure that socialbase is always set as the base theme and inherit the regions from socialbase if you do not change the page template.

 # Development of your theme #
 Before you start theming there are a few things that might be convenient. In html/sites/default there is a services.yml file. Make sure to set
 * twig.config -> debug: true
 * twig.config -> auto_reload: true
 * twig.config -> cache: false

 Also in sites default you will find an example.settings.local.php file. Duplicate this file and remove the example part. After you have configured these files you need to rebuild the caches. This will speed up you development process.

### Techniques

- Gulp
- Yarn
- Sass
- Pugjs (will be replaced later with twig)
- Twig

## Structure

### Assets
Our gulp tasks will generate our CSS, JS and images that Drupal uses.
### Components
This is working folder. The folder is categorised following atomic design principles. Most re-usable css values are turned into variables for consistency.
### Config
Drupal installation files
### Content
Images that are used in our style guide, not used by Drupal.
### JS
Old javascript folder, needs to cleaned up (all JS should be in components are generated to assets folder)
### Pug
Holds our current style guide. This one is published at http://styleguide.getopensocial.com/
### Template
This folder contains all twig templates for the theme.



# Contributing #

Socialbase uses Gulp.js as a task runner, so that it can do many different tasks automatically:
 - Build CSS from your Sass using libSass and node-sass.
 - Add vendor prefixes for the browsers you want to support using Autoprefixer.
 - Build a style guide of your components with pug templates.
 - Watch all of your files as you develop and re-build everything on the fly.

Set up your front-end development build tools:

1. [Install Yarn](https://yarnpkg.com/en/docs/install), see their website for documentation

2. The package.json file contains the versions of all the node packages you need. To install them run:
    ```
    yarn install
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

# Notice
We are constantly improving and updating the theme setup for Open Social. It might be the readme is not always up to date. You can report issues for the drupal.org issue queue. The following items are on our roadmap:
* Remove JS folder and make sure all scripts are placed in their corresponding component.
* Add components library module and create twig templates in the components folder.
* Remove the pug style guide in favor of a twig based KSS style guide
* Provide a starterkit in favor a manual copying socialblue to start a new theme.
