Configuration Update Reports module
-----------------------------------

CONTENTS OF THIS README FILE
- Introduction
- Installation
- Generating reports in the user interface
- Generating reports using Drush commands
- Important notes  *** Be sure to read this section ***


INTRODUCTION

This module provides a report that allows you to see the differences between the
default configuration items provided by the current versions of your installed
modules, themes, and install profile, and the active configuration of your
site. From this report, you can also import new configuration provided by
updates, and revert your site configuration to the provided values.

The main use case is: You update a module, and it has either changed default
configuration that it provides, or added new default configuration items that
you didn't get when you first installed the module. You want to be able to
import the new items, view the differences between the active site configuration
and the changed configuration, and possibly "revert" (or it may be an update) to
the newly-provided default configuration.


INSTALLATION

Install the module in the normal way for Drupal modules. The only dependencies
are the Configuration Manager module (Drupal core), and the Configuration Update
Base module (part of this same project download).


GENERATING REPORTS IN THE USER INTERFACE

You can generate configuration reports at Administration >> Configuration >>
Development >> Configuration management >> Update report (path:
admin/config/development/configuration/report ).

You can generate a report for a particular type of configuration object, such as
Actions, Tours, Views, etc. Or, you can generate a report for an installed
module, theme, or install profile. Finally, you can generate a report that
contains all configuration in one report.

The report has three sections, depending on what type you choose:

1. Missing configuration items: Configuration items that are provided as
   defaults by your currently-installed modules, themes, and install profile
   that are missing from your active site configuration.

   Any items listed here can be imported into your site.

2. Added configuration items: Configuration items that you added to the site
   (not provided by a currently-installed module, theme, or install
   profile). This section is only shown when you are running the report based on
   a configuration type.

   Items listed here can be exported, which is useful for developers or if you
   want to keep your site configuration in a version control system.

3. Changed configuration items: Configuration items that are in your active site
   configuration that differ from the same item currently being provided by an
   installed module, theme, or install profile.

   You can export these items, see the differences between what is on your site
   and what the module/theme/profile is currently providing, or "revert" to the
   version currently being provided by the module/theme/profile in its default
   configuration.

   Note that the differences may be a bit hard to read, but hopefully they'll
   give you the general idea of what has changed.


GENERATING REPORTS USING DRUSH COMMANDS

The reports detailed in the previous section can also be generated, in pieces,
using Drush commands (https://drupal.org/project/drush):

drush config-list-types (clt)
  Lists all the config types on your system. Reports can be run for
  'system.simple' (simple configuration), and 'system.all' (all types), in
  addition to the types listed by this command.

drush config-added-report (cra)
drush config-missing-report (crm)
drush config-different-report (crd)
  Run config reports (see below).

drush config-diff (cfd)
  Show config differences for one item between active and imported (see below).

The report commands run reports that tell what config has been added, is
missing, or is different between your active site configuration and the imported
default configuration from config/install directories of your installed profile,
modules, and themes.

For each report except "added", the first argument is one of:
- type: Runs the report for a configuration type; use drush config-list-types to
  list them.
- module: Runs the report for an installed module.
- theme: Runs the report for an installed theme.
- profile: Runs the report for the install profile.

The second argument for reports is the machine name of the configuration type,
module, theme, or install profile you want to run the report for. For the
"added" report, this is the only argument, as the added report is always by
configuration type.

These are the same as the reports you get in the UI, which is described above;
the only difference is that in Drush the report is separated into pieces.

Once you have found a configuration item with differences, you can view the
differences using the config-diff command. This is a normalized/formatted diff
like in the UI of this module, so see above for details.

Drush examples:

drush clt
drush crm module node
drush cra block
drush crd theme bartik
drush crd type system.all
drush crd type system.simple
drush crd profile standard
drush cfd block.block.bartik_search

Once you have figured out which configuration items are added, missing, or
different, you can:

- Export them - see drush config-export.

- Import missing configuration or revert to provided default values. To do this:

  (1) Locate the configuration file in the install profile, module, or theme
      config/install directory.

  (2) Copy this file to your configuration staging directory.

  (3) Run drush config-import. You might want to use the --preview option to see
      what differences you are about to import, before running the import, or
      use the drush config-diff command to look at individual differences.


IMPORTANT NOTES

Here are some notes about how this module functions:

* This module is always looking at the base configuration items, without
  overrides (from settings.php, for example) or translations.

* It is possible for an install profile on a site to provide configuration that
  overrides configuration from a module or theme. The install profile version
  always takes precedence. As an example, consider the case where module Foo
  provides a configuration item called foo.settings, and install profile Bar
  overrides this with its own file. Any reports that include foo.settings will
  be based on the differences between your site's active configuration and the
  version in the install profile. This is not usually a problem, but it can be
  confusing if you're looking at the Foo module report. The foo.settings item
  will be present, but the differences reported will be between the install
  profile's version and your site's active configuration, not the differences
  between the Foo module version and your site's active configuration.
