# Social Blue
Social Blue is made to provide as a demo as well as a default style for Open
Social. This is a sub theme of socialbase.

Social Blue uses Gulp.js as a task runner, so that it can do many tasks
automatically:
 - Build CSS from your Sass using libSass and node-sass.
 - Add vendor prefixes for the browsers you want to support using Autoprefixer.
 - Build a style guide of your components with KSS-Node.
 - Watch all of your files as you develop and re-build everything on the fly.
 - Set up to deploy your style guide to a custom location

# What can I do with this theme?
The safest and fastest way to get started is to duplicate this theme and rename
it to your custom theme name. You need to make sure all instances of
'socialblue' are renamed to guarantee a proper working theme. Socialblue itself
will be updated in the future with new features, so it is best not to make
changes. You might lose it when updating.

If you want to utilise the gulp plugins we have provided you need to install the
plugins again, via `yarn install` (which will read the package.json file).

As you can see in the info file, we are mostly extending the socialbase
libraries with the socialblue variant. This means there is a relation between
the two and because we load some libraries via twig files conditionally this
ensure we are not forgetting to load the 'styling' layer for a component.

You can override template files just like in any other theme. Just create a
`templates` folder and put you new template files there.



Any questions or feedback?
[Create an issue on drupal.org](https://www.drupal.org/project/issues/social)


# Getting started with your sub theme 

### Drupal settings
Before you start theming there are a few things that might be convenient. In
html/sites/default there is a services.yml file. Make sure to set
* twig.config -> debug: true
* twig.config -> auto_reload: true
* twig.config -> cache: false

Also in sites default you will find an example.settings.local.php file.
Duplicate this file and remove the example part. After you have configured these
files you need to rebuild the caches. This will speed up you development
process.

### Working with Gulp

1. [Install Yarn](https://yarnpkg.com/en/docs/install), see their website for
documentation

2. Install the gulp-cli tool globally. Normally, installing a Node.js globally
  is not recommended, which is why both Gulp and Grunt have created wrapper
  commands that will allow you to run "gulp" or "grunt" from anywhere, while
  using the local version of gulp or grunt that is installed in your project.
  To install gulp's global wrapper, run:
    ```
    npm install -g gulp-cli

3. The package.json file contains the versions of all the node packages you
need. To install them run:
    ```
    yarn install
    ```
    
4. Set the URL used to access the Drupal website under development. Edit your
    gulpfile.js file and change the options.drupalURL setting:
    ```
    options.drupalURL = 'http://localhost';
    ```

4. There are different gulp tasks. What each gulp task does is described in the
gulpfile with the task itself. To run a gulp task, type:
    ```
    gulp [taskname]
    ```
