# Base theme #
The Social Base theme is designed as a base theme for Open Social. This base
theme holds has a lot of sensible defaults. It doesn't however contain much
styling. We expect developers to want to change this for their own project.
As you can see there is also a theme called **socialblue**. Socialblue is the
demo/styling sub theme for Open Social.


# Changing the style of the theme  #
If you are a Drupal themer you know how to create a (sub)theme. There are no
tricks in our setup you need to know for Open Social. The easiest way to start
is to grab a copy of Social Blue and rename all instances of Social Blue to your
new theme name. Social Base and Social Blue will get updates each release, so it
is best not to make changes in here you want to keep.

Make sure that Social Base is always set as the base theme and inherit the
regions from social Base if you do not change the page template.

# Development of your theme #
Before you start theming there are a few things that might be convenient. In
html/sites/default there is a development.services.yml file. Make sure to set
* twig.config -> debug: true
* twig.config -> auto_reload: true
* twig.config -> cache: false

Also in sites default you will find an example.settings.local.php file.
Duplicate this file and remove the example part from the filename. 
After you have configured these files you need to rebuild the caches. 
This will speed up you development process.

### Goodies used

- [Gulp](http://gulpjs.com/) 
- [Yarn](https://yarnpkg.com)
- [Sass](http://sass-lang.com/)
- [KSS Node](https://github.com/kss-node/kss-node)
- [Twig](https://www.drupal.org/docs/8/theming/twig)

## Structure

<dl>
<dt># assets</dt>
<dd>Our gulp tasks will generate our CSS, JS and images that Drupal uses.</dd>
<dt># components</dt>
<dd>This is source folder. The folder is categorised following atomic design
principles. Most re-usable css values are turned into variables for consistency.
</dd>
<dt># config</dt>
<dd>Drupal installation files</dd>
<dt># node_modules</dt>
<dd>Yarn will install all devDependencies in this folder. 
What gets installed is listed in package.json</dd>
<dt># src</dt>
<dd>Drupal plugins - contains most functions and template suggestions.  
[Drupal Bootstrap documentation](https://drupal-bootstrap.org/api/bootstrap/docs%21plugins%21README.md/group/plugins/8)
on how to extend this in your subtheme.</dd>
<dt># Templates</dt>
<dd>This folder contains all twig templates for the theme.</dd>



# Contributing to socialbase #

Social Base uses Gulp.js as a task runner, so that it can do many tasks
automatically:
 - Build CSS from your Sass.
 - Add vendor prefixes for the browsers you want to support using Autoprefixer.
 - Watch all of your files as you develop and re-build everything on the fly.

Set up your front-end development build tools:

1. [Install Yarn](https://yarnpkg.com/en/docs/install), see their website for
documentation

2. Install the gulp-cli tool globally. Normally, installing a Node.js globally
  is not recommended, which is why both Gulp and Grunt have created wrapper
  commands that will allow you to run "gulp" or "grunt" from anywhere, while
  using the local version of gulp or grunt that is installed in your project.
  To install gulp's global wrapper, run:
    ```
    npm install -g gulp-cli
    ```

3. The package.json file contains the versions of all the node packages you
need. To install them run from the theme:
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

# Notice
We are constantly improving and updating the theme setup for Open Social. It
might be the readme is not always up to date. Also check the changelog file for
changes. You can report issues via the drupal.org
[issue queue](https://www.drupal.org/project/issues/social?categories=All).
The following items are on our roadmap:
* Add components library module and create twig templates in the components
folder.
* Provide a starterkit in favor a manual copying Social Blue to start a new
theme.
