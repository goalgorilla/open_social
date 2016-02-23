Composer Manager allows contributed modules to depend on PHP libraries managed via Composer.

Installation
------------
- Install the Composer Manager module.
- Run the module's init.php script on the command line
  (`php scripts/init.php` from inside the composer_manager module directory).
  This registers the module's Composer command for Drupal core.
- Run `composer drupal-update` from the root of your Drupal directory.

Workflow
--------
- Download the desired modules (such as Commerce).
- Run `composer drupal-update` from the root of your Drupal directory.
  This rebuilds composer.json and downloads the new module's requirements.
- Install the modules.

If you're using Drush to download/install modules, then composer drupal-update
will be run automatically for you after drush dl completes.

Documentation: https://www.drupal.org/node/2405789
