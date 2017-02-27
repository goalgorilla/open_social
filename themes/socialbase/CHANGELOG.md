### v2.1.1 -- Feb 2017
* Removed pug style guide files
* Removed content folder which contained assets for the old style guide
* Updated readme file
* Updated gulpfile and added more documentation
* Revised and updated all base components
* Revised and updated all mixins

### v2.1.0 -- Feb 2017

Complete overhaul of theme_hooks and clean up of templates

* All theme_hooks_suggestions and theme_hook_preprocess functions are moved to individual files in the includes folder
* Cleaned up form hooks
* Renamed classes in `card` component
* Renamed classes in `teaser` component
* Removed Pug version of styleguide -> styleguide is moved to socialblue theme
* Inserted documentation in component scss files for existing components in styleguide
* Added range slider to atoms > form-controls
* Moved layout from base component to template component folder
* Added Javascript library folder to components for better abstraction of javascript libraries/enhancements
* Added Morris.js dependency via bower (gulp plugin)
* Removed theme settings options that are default
* Removed bootstrap function to colorize and iconize buttons. This is now done via form hooks
* Updated the way we override the bootstrap panel implementation for details and fieldsets. Updated corresponding template files
* Merged container templates
* Merged views templates
* Merged form templates 
* Updated teaser templates
