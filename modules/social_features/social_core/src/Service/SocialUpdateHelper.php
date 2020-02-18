<?php

namespace Drupal\social_core\Service;

use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Entity\EntityDisplayRepository;


/**
 * Class SocialUpdateHelper.
 *
 * @package Drupal\social_core
 */
class SocialUpdateHelper {

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\CachedStorage
   */
  protected $storage;

  /**
   * The entity repository to get the view display from.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $repository;

  /**
   * Constructor for SocialUpdateHelper.
   *
   * @param \Drupal\Core\Config\CachedStorage $storage
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $repository
   *   The entity display repository.
   */
  public function __construct(CachedStorage $storage, EntityDisplayRepository $repository) {
    $this->storage = $storage;
    $this->repository = $repository;
  }

  /**
   * Able to update config based on a list of configuration.
   *
   * @param $config
   *   An array containing configuration objects.
   *
   * @throws
   */
  public function updateFieldsDisplaysAndConfig(array $config) {
    // We need all the names so our service updates/inserts them correctly.
    $names = array_keys($config['configs']);
    $name = $names[$config['current']++];
    // Grab all the config data, so we know what to insert/update.
    $data = $config['configs'][$name];
    // Explode the name, so we know what config to work with, either
    // 1. field.storage
    // 2. field.fields
    // 3. core.entity_view_display
    // @TODO check SocialUpdateHelper.php if we need more specific config updates
    // the rest will default to:
    // \Drupal::service('config.storage')->write($name, $data);
    $parts = explode('.', $name);

    switch ($parts[0] . '.' . $parts[1]) {
      case 'field.storage':
        $this->updateFieldStorage($data);
        break;

      case 'field.field':
        $this->updateFieldConfig($data, $parts);
        break;

      case 'core.entity_view_display':
        $this->UpdateEntityViewDisplay($data, $parts);
        break;

      default:
        // Use the Drupal config storage service to write the data
        // if it doesn't fit the normal workflow.
        $this->storage->write($name, $data);
    }
  }

  /**
   * Update individual Field Storage item.
   *
   * @param $data
   *  Array containing the full FieldStorage configuration.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateFieldStorage($data) {
    FieldStorageConfig::create($data)->save();
  }

  /**
   * @param $data
   *  Array containing the full FieldConfig configuration.
   * @param $parts
   *  Array containing the field config items.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateFieldConfig($data, $parts) {
    $field_config = FieldConfig::loadByName($parts[2], $parts[3], $parts[4]);

    if ($field_config instanceof FieldConfigInterface) {
      $field_config->setDescription($data);
    }
    else {
      $field_config = FieldConfig::create($data);
    }

    $field_config->save();
  }

  /**
   * Update Entity View display.
   *
   * @param $data
   *  Array containing the full FieldConfig configuration.
   * @param $parts
   *  Array containing the field config items.
   */
  public function updateEntityViewDisplay($data, $parts) {
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = \Drupal::service('entity_display.repository')
      ->getViewDisplay($parts[2], $parts[3], $parts[4]);

    foreach ($data as $field) {
      $view_display->removeComponent($field);
    }
  }

}
