### Release 1.8
We have added color configuration for the navbar and secondary navbar. 
Also the way this is stored is now done via the color module (core package),
previously it was in improved_theme_settings - social package.

You need to update:
* __color.inc__ - add the new fields and default scheme
* __themename_settings.yml__ - prefix the new field with _color_
 and replace hyphens with underscores. Hex values must 
 match exactly with you scheme in _color.inc_
* __brand.scss__ - This file is in the root of the 
components folder. This file contains the exactly 
and only the colors (as $vars is fine) you have defined 
in the _color.inc_. Gulp will generate the css for you.
* __brand.css__ - this file should be generated via gulp.
Check that it only contains the hex color values you have 
listed in the color file.

#### How does this color module work?
When you have implemented it correctly in your subtheme
the color module will take a copy of your __brand.css__ file
and replace the original hex values with the hex values that
are entered in the theme settings form. That copy is stored
in your public file directory. Now it can also be cached.
Each time a Site Manager saves the form, a new copy is generated. 


### Release 1.7
The border radius properties have been split into 
* Card border radius
* Form control border radius
* Button border radius

to allow for more flexibility between these elements.

You need to add the new options to your custom theme-settings.php

### v1.1.3 -- Apr 2017
* Changed styles for book navigation.

### v1.1.2 -- Mar 2017
* Contents of the complementary_bottom region will be hidden on mobile screens.

### v1.1.1 -- Feb 2017
* Listed all mixins and base components in style guide
* Update readme file
* Update gulp tasks for correct deployment of style guide

### v1.1.0 -- Feb 2017

Inserted KSS based styleguide

* See gulpfile for options that are passed on
* The folder os-builder holds the assets and layout for the styleguide
* Removed hook_theme_suggestions_fieldset_alter and
hook_theme_suggestions_details_alter overrides which are not needed anymore
because of social base theme update (2.1.0).
* Class name updates for changed components in socialbase update 2.1.0
* Set hover color of button links to remain primary color
* Remove grid mixins (deprecated)
