<?php

declare(strict_types=1);

namespace Drupal\social_course;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;

/**
 * Helper for cleaning up group relationship data on module uninstall.
 */
class SocialCourseUninstallHelper {

  /**
   * Deletes group relationship records for group content types of a module.
   *
   * Scans the module's config/install and config/optional directories for
   * group.content_type.* configs and removes matching records from the
   * group_relationship and group_relationship_field_data tables.
   *
   * @param string $module
   *   The module machine name.
   *
   * @throws \Exception
   */
  public static function deleteGroupRelationships(string $module): void {
    $module_path = \Drupal::service('extension.list.module')->getPath($module);
    $database = \Drupal::database();

    $subdirs = [
      InstallStorage::CONFIG_INSTALL_DIRECTORY,
      InstallStorage::CONFIG_OPTIONAL_DIRECTORY,
    ];

    $types = [];
    foreach ($subdirs as $subdir) {
      $dir = $module_path . '/' . $subdir;
      if (!is_dir($dir)) {
        continue;
      }

      $storage = new FileStorage($dir);
      foreach ($storage->listAll('group.content_type.') as $config_name) {
        $data = $storage->read($config_name);
        if (!empty($data['id'])) {
          $types[] = $data['id'];
        }
      }
    }

    if (empty($types)) {
      return;
    }

    $tables = ['group_relationship', 'group_relationship_field_data'];
    foreach ($tables as $table) {
      if ($database->schema()->tableExists($table)) {
        $database->delete($table)
          ->condition('type', $types, 'IN')
          ->execute();
      }
    }
  }

  /**
   * Removes course-related dependencies from configs listed in config/modify.
   *
   * Reads the module's config/modify YAML files, collects all config names
   * from the 'items' keys, and strips any dependency entries containing
   * "course" so they don't become orphan references after uninstall.
   *
   * @param string $module
   *   The module machine name whose config/modify files to scan.
   */
  public static function cleanModifyConfigDependencies(string $module): void {
    $module_path = \Drupal::service('extension.list.module')->getPath($module);
    $modify_dir = $module_path . '/config/modify';

    if (!is_dir($modify_dir)) {
      return;
    }

    $modify_storage = new FileStorage($modify_dir);
    $config_names = [];

    foreach ($modify_storage->listAll() as $modify_name) {
      $data = $modify_storage->read($modify_name);
      if (!empty($data['items']) && is_array($data['items'])) {
        $config_names = [...$config_names, ...array_keys($data['items'])];
      }
    }

    $config_factory = \Drupal::configFactory();

    foreach ($config_names as $config_name) {
      $config = $config_factory->getEditable($config_name);

      if ($config->isNew()) {
        continue;
      }

      $dependencies = $config->get('dependencies');
      if (empty($dependencies)) {
        continue;
      }

      $changed = FALSE;

      foreach (['module', 'config', 'theme'] as $type) {
        if (empty($dependencies[$type])) {
          continue;
        }

        $filtered = array_values(array_filter(
          $dependencies[$type],
          fn (string $dependency): bool => !str_contains($dependency, 'course'),
        ));

        if (count($filtered) !== count($dependencies[$type])) {
          $dependencies[$type] = $filtered;
          $changed = TRUE;
        }
      }

      if ($changed) {
        $config->set('dependencies', $dependencies);
        $config->save();
      }
    }
  }

  /**
   * Adds enforced dependency on a module to all its config.
   *
   * Scans the module's config/install and config/optional directories and
   * adds the module as an enforced dependency to each active config.
   *
   * IMPORTANT: one-time method usage could be deleted easily on code cleanup.
   *
   * @param string $module
   *   The module machine name.
   */
  public static function addEnforcedDependency(string $module): void {
    $module_path = \Drupal::service('extension.list.module')->getPath($module);
    $config_factory = \Drupal::configFactory();

    $subdirs = [
      InstallStorage::CONFIG_INSTALL_DIRECTORY,
      InstallStorage::CONFIG_OPTIONAL_DIRECTORY,
    ];

    foreach ($subdirs as $subdir) {
      $dir = $module_path . '/' . $subdir;
      if (!is_dir($dir)) {
        continue;
      }

      $storage = new FileStorage($dir);

      foreach ($storage->listAll() as $config_name) {
        $config = $config_factory->getEditable($config_name);
        if ($config->isNew()) {
          continue;
        }

        $dependencies = $config->get('dependencies') ?? [];
        $enforced_modules = $dependencies['enforced']['module'] ?? [];
        if (!in_array($module, $enforced_modules)) {
          $enforced_modules[] = $module;
          sort($enforced_modules);
          $dependencies['enforced']['module'] = $enforced_modules;
          $config->set('dependencies', $dependencies);
          $config->save(TRUE);
        }
      }
    }
  }

}
