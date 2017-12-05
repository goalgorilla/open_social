### Release 1.5
* Updated Gulptasks. 
* All 3rd party libraries are removed from the theme and need to be included via
composer (or drush). Libraries now refer to files in html/libraries folder. 
* Gulp task `minify-js` has been removed and the `scripts` task has been added.
* All gulp plugins are updated to the latest version.  


### Release 1.4
* Moved rounded avatars style from socialbase to socialblue. For existing
installations: Add border-radius properties to base/utilities/images, atoms/list
and molecules/navigation/navbar components in your subtheme to keep rounded
styles.

### v2.1.4 -- May 2017
* Added method for autogrow behaviour and changed event triggers.
* Changed overflow styles for autogrow form elements.
* Update class on icons in navbar https://www.drupal.org/node/2872929
* Update instruction: In your sub theme provide a fill color for either
`.navbar-nav__icon` which is the new icon class. Or like we did in social blue
provide a fill for `.navbar-nav > li > a`. The default color is white. The size
of the icon is in socialbase. The float class is removed and not needed.
* Provide a condition before printing the *topic type* in
`node--topic--teaser.html.twig`. Although topic type in a required field by
default, this can be changed in an installation.

### v2.1.3 -- Apr 2017
* Changed wave-effect styles.
* Changed book navigation styles.
* Made image widget crop being collapsed by default.

### v2.1.2 -- Mar 2017
* Replaced Bootstrap list-group component with custom list component
* Merge label component with badges and extend badge modifiers
* Improve card documentation with separation of `card__block` and `card__body`
* Document and rename alert, badge, list in style guide
* Add Bar chart and Donut chart to MorrisJs documentation
* Optimise small teaser templates
* Add two columns and three columns layout, with sidebar_first and
sidebar_second regions.
* Renamed in page-full twig block nodefull_header to metainfo and added twig
block metaengage.

### v2.1.1 -- Feb 2017
* Removed pug style guide files
* Removed content folder which contained assets for the old style guide
* Updated readme file
* Updated gulpfile and added more documentation
* Revised and updated all base components
* Revised and updated all mixins

### v2.1.0 -- Feb 2017

Complete overhaul of theme_hooks and clean up of templates

* All theme_hooks_suggestions and theme_hook_preprocess functions are moved to
individual files in the includes folder
* Cleaned up form hooks
* Renamed classes in `card` component
* Renamed classes in `teaser` component
* Removed Pug version of styleguide -> styleguide is moved to socialblue theme
* Inserted documentation in component scss files for existing components in
styleguide
* Added range slider to atoms > form-controls
* Moved layout from base component to template component folder
* Added Javascript library folder to components for better abstraction of
javascript libraries/enhancements
* Added Morris.js dependency via bower (gulp plugin)
* Removed theme settings options that are default
* Removed bootstrap function to colorize and iconize buttons. This is now done
via form hooks
* Updated the way we override the bootstrap panel implementation for details and
fieldsets. Updated corresponding template files
* Merged container templates
* Merged views templates
* Merged form templates
* Updated teaser templates
