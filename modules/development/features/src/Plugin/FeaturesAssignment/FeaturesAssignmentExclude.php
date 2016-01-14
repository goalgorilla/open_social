<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentExclude.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\component\Utility\Unicode;
use Drupal\features\FeaturesAssignmentMethodBase;
use Drupal\features\FeaturesManagerInterface;

/**
 * Class for excluding configuration from packages.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentExclude::METHOD_ID,
 *   weight = -5,
 *   name = @Translation("Exclude"),
 *   description = @Translation("Exclude configuration items from packaging by various methods including by configuration type."),
 *   config_route_name = "features.assignment_exclude"
 * )
 */
class FeaturesAssignmentExclude extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'exclude';

  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $current_bundle = $this->assigner->getBundle();
    $settings = $current_bundle->getAssignmentSettings(self::METHOD_ID);

    $config_collection = $this->featuresManager->getConfigCollection();

    // Exclude by configuration type.
    $exclude_types = $settings['types']['config'];
    if (!empty($exclude_types)) {
      foreach ($config_collection as $item_name => $item) {
        if (in_array($item->getType(), $exclude_types)) {
          unset($config_collection[$item_name]);
        }
      }
    }

    // Exclude configuration already provided by modules.
    $exclude_module = $settings['module'];
    if (!empty($exclude_module['enabled'])) {
      $install_list = $this->featuresManager->getExtensionStorages()->listAll();

      // There are two settings that can limit what's included.
      // First, we can skip configuration provided by the install profile.
      $module_profile = !empty($exclude_module['profile']);
      // Second, we can skip configuration provided by namespaced modules.
      $module_namespace = !empty($exclude_module['namespace']);
      if ($module_profile || $module_namespace) {
        $profile_list = [];
        $extension_list = [];
        // Load the names of any configuration objects provided by the install
        // profile.
        if ($module_profile) {
          $all_modules = $this->featuresManager->getAllModules();
          // FeaturesBundleInterface::getProfileName() would return the profile
          // for the current bundle, if any. We want the profile that was
          // installed.
          $profile_name = drupal_get_profile();
          if (isset($all_modules[$profile_name])) {
            $profile_list = $this->featuresManager->listExtensionConfig($all_modules[$profile_name]);
            // If the configuration has been assigned to a feature that's
            // present on the file system, don't make an exception for it.
            foreach ($all_modules as $name => $extension) {
              if ($name != $profile_name && $this->featuresManager->isFeatureModule($extension)) {
                $profile_list = array_diff($profile_list, $this->featuresManager->listExtensionConfig($extension));
              }
            }
          }
        }
        // Load the names of any configuration objects provided by modules
        // having the namespace of the current package set.
        if ($module_namespace) {
          $modules = $this->featuresManager->getFeaturesModules($current_bundle);
          foreach ($modules as $extension) {
            $extension_list = array_merge($extension_list, $this->featuresManager->listExtensionConfig($extension));
          }
        }
        // If any configuration was found, remove it from the list.
        $install_list = array_diff($install_list, $profile_list, $extension_list);
      }
      foreach ($install_list as $item_name) {
        if (isset($config_collection[$item_name])) {
          // Flag extension-provided configuration, which should not be added
          // to regular features but can be added to an install profile.
          $config_collection[$item_name]->setExtensionProvided(TRUE);
        }
      }
    }

    // Exclude configuration items on a curated list of site-specific
    // configuration.
    if ($settings['curated']) {
      $item_names = [
        'core.extension',
        'field.settings',
        'field_ui.settings',
        'filter.settings',
        'forum.settings',
        'image.settings',
        'node.settings',
        'system.authorize',
        'system.date',
        'system.file',
        'system.diff',
        'system.logging',
        'system.maintenance',
        'system.performance',
        'system.site',
        'update.settings',
      ];
      foreach ($item_names as $item_name) {
        unset($config_collection[$item_name]);
      }
      // Unset role-related actions that are automatically created by the
      // User module.
      // @see user_user_role_insert()
      $prefixes = [
        'system.action.user_add_role_action.',
        'system.action.user_remove_role_action.',
      ];
      foreach (array_keys($config_collection) as $item_name) {
        foreach ($prefixes as $prefix) {
          if (strpos($item_name, $prefix) === 0) {
            unset($config_collection[$item_name]);
          }
        }
      }
    }

    // Register the updated data.
    $this->featuresManager->setConfigCollection($config_collection);
  }

}
