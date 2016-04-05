CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * FAQ

 INTRODUCTION
 ------------
Todo

REQUIREMENTS
------------
No other modules required, we're supporting drupal core as a source for creating
facets. Though we recommend using Search API, as that integration is better
tested.

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module. See:
  https://drupal.org/documentation/install/modules-themes/modules-7
  for further information.

CONFIGURATION
-------------
Before adding a facet, there should be a facet source. Facet sources can be:
- Drupal core's search.
- A view based on a Search API index with a page display.
- A page from the search_api_page module.

After adding one of those, you can add a facet on the facets configuration page:
/admin/config/search/facets

If you're using Search API views, make sure to disable views cache when using
facets for that view.

FAQ
---

Q: Why do the facets disappear after a refresh.
A: We don't support cached views, change the view to disable caching.
