# Style Guide for the Open Social <span class="text-primary">Blue</span> theme

__Open Social is a Drupal 8 distribution for building social communities and intranets.__

A Style Guide is a set of standards to ensure a consistent design and identity. Additionally, from a development point of view it serves to improve the speed and ease of code deployment. A style guide consolidates the front-end code while comprehensively documenting the visual language, such as color palettes and fonts. To ensure it is always up-to-date, this style guide is automatically generated from comments in the Sass files.

The goals for creating this style guide are:

1. Streamline the workflow for designers and developers
2. Maintain a consistency of the theme styles and components
3. Create a focal point for our Design and User Experience work, both for ourselves and for the community

This theme is built upon [Drupal Bootstrap](https://www.drupal.org/project/bootstrap), which means Bootstrap is used as the base theme. Furthermore it is enhanced with a default brand styling and extra components. For the extra components mostly [Material Design](https://www.google.com/design/spec/material-design/introduction.html) principles are used.

### Organisation

Design components are reusable designs that can be applied using just the CSS class names specified in the component. We categorize our components to make them easy to find.

<dl>
<dt>**Base**</dt>
<dd>`components/01-base` — The default “base” components apply to HTML elements. Since all of the rulesets in this class of styles are HTML elements, the styles apply automatically.</dd>
<dt>**Atoms**</dt>
<dd>`components/02-atoms` — Smallest building blocks for our components.</dd>
<dt>**Molecules**</dt>
<dd>`components/03-molecules` — Small reusable components. Usually consists of smaller atoms. </dd>
<dt>**Organisms**</dt>
<dd>`components/04-organisms` — Organisms are larger blocks to can be placed in a template and do now rely on other components.</dd>
<dt>**Templates**</dt>
<dd>`components/05-templates` — Templates consist mostly of groups of organisms put together.</dd>
<dt>**Libraries**</dt>
<dd>`components/06-libraries` — External libraries that can be used by the other components.</dd>
</dl>

In addition to the components, our component library also contains these folders:

<dl>
<dt>**Configuration**</dt>
<dd>`components/00-config` — This Sass documents the colors used throughout the site and various Sass variables, functions and mixins. </dd>
<dt>**Style guide helper files**</dt>
<dd>`components/styleguide` — The files needed to build this style guide; includes some CSS overrides for the default KSS style guide</dd>
<dt>**Generated files**</dt>
<dd>`assets` — location of the generated CSS, images and javascript; don't alter these files directly</dd>
</dl>
