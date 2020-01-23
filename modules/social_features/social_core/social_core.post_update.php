<?php

/**
 * @file
 * Contains post-update hooks for the Social Core module.
 */

use Drupal\social_core\FeaturesRemoval\FeaturesInstallStorage;
use Drupal\social_core\FeaturesRemoval\FeaturesManager;

/**
 * Enable the queue storage entity module.
 */
function social_core_post_update_8701_enable_queue_storage() {
  \Drupal::service('module_installer')->install([
    'social_queue_storage',
  ]);
}

/**
 * Perform features revert for the last time.
 */
function social_core_post_update_8800_features_revert() {
  // This function goes through all the installed modules and finds the
  // `features_removal` folder. Any configuration inside of that folder will be
  // imported as if `features-revert` was performed. This has to be done because
  // configuration changes in 8.0 may not yet have an update hook at the time
  // features was removed. This would cause required configuration changes not
  // to be applied.
  $removed_features = [];

  // Go through the directories of enabled modules and find our special
  // `config/features_removal` folder.
  $drupal_root = \Drupal::root();
  $active_modules = \Drupal::moduleHandler()->getModuleList();
  foreach ($active_modules as $name => $module) {
    $snapshot_dir = $drupal_root . DIRECTORY_SEPARATOR . $module->getPath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'features_removal';
    if (is_dir($snapshot_dir)) {
      $removed_features[] = $name;
    }
  }

  $container = \Drupal::getContainer();

  // Store the original services so they can be restored later.
  $original_fm = $container->get('features.manager');
  $original_fis = $container->get('features.extension_storage');

  // Overwrite the services, we'll have to manually instantiate them.
  // This is according to the original features.services.yml.
  $container->set(
    'features.manager',
    new FeaturesManager(
      $container->get('app.root'),
      $container->get('entity.manager'),
      $container->get('config.factory'),
      $container->get('config.storage'),
      $container->get('config.manager'),
      $container->get('module_handler'),
      $container->get('features.config_update')
    )
  );
  $container->set(
    'features.extension_storage',
    new FeaturesInstallStorage($container->get('config.storage'))
  );

  // Let features import our modules one last time. We tell it to import
  // non-features modules because the *.features.yaml file is already removed.
  $updated_config = \Drupal::service('features.manager')->import($removed_features, TRUE);

  // Restore the services in case other things in the request use them.
  $container->set('features.maanger', $original_fm);
  $container->set('features.extension_storage', $original_fis);

  // TODO: Output which config has been imported/updated?
}
