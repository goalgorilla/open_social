<?php

namespace Drupal\config_update;

use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Extension\Extension;

/**
 * Provides methods related to config listing.
 */
class ConfigLister implements ConfigListInterface {

  /**
   * List of current config entity types, keyed by prefix.
   *
   * This is not set up until ConfigLister::listTypes() has been called.
   *
   * @var string[]
   */
  protected $typesByPrefix = [];

  /**
   * List of current config entity type definitions, keyed by entity type.
   *
   * This is not set up until ConfigLister::listTypes() has been called.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $definitions = [];

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeConfigStorage;

  /**
   * The extension config storage.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionConfigStorage;

  /**
   * The extension config storage for optional config.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionOptionalConfigStorage;

  /**
   * Constructs a ConfigLister.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\StorageInterface $active_config_storage
   *   The active config storage.
   * @param \Drupal\Core\Config\ExtensionInstallStorage $extension_config_storage
   *   The extension config storage.
   * @param \Drupal\Core\Config\ExtensionInstallStorage $extension_optional_config_storage
   *   The extension config storage for optional config items.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, StorageInterface $active_config_storage, ExtensionInstallStorage $extension_config_storage, ExtensionInstallStorage $extension_optional_config_storage) {
    $this->entityManager = $entity_manager;
    $this->activeConfigStorage = $active_config_storage;
    $this->extensionConfigStorage = $extension_config_storage;
    $this->extensionOptionalConfigStorage = $extension_optional_config_storage;
  }

  /**
   * Sets up and returns the entity definitions list.
   */
  public function listTypes() {
    if (count($this->definitions)) {
      return $this->definitions;
    }

    foreach ($this->entityManager->getDefinitions() as $entity_type => $definition) {
      if ($definition->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
        $this->definitions[$entity_type] = $definition;
        $prefix = $definition->getConfigPrefix();
        $this->typesByPrefix[$prefix] = $entity_type;
      }
    }

    ksort($this->definitions);

    return $this->definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getType($name) {
    $definitions = $this->listTypes();
    return isset($definitions[$name]) ? $definitions[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeByPrefix($prefix) {
    $definitions = $this->listTypes();
    return isset($this->typesByPrefix[$prefix]) ? $definitions[$this->typesByPrefix[$prefix]] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeNameByConfigName($name) {
    $definitions = $this->listTypes();
    foreach ($this->typesByPrefix as $prefix => $entity_type) {
      if (strpos($name, $prefix) === 0) {
        return $entity_type;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function listConfig($list_type, $name) {
    $active_list = [];
    $install_list = [];
    $optional_list = [];
    $definitions = $this->listTypes();

    switch ($list_type) {
      case 'type':
        if ($name == 'system.all') {
          $active_list = $this->activeConfigStorage->listAll();
          $install_list = $this->extensionConfigStorage->listAll();
          $optional_list = $this->extensionOptionalConfigStorage->listAll();
        }
        elseif ($name == 'system.simple') {
          // Listing is done by prefixes, and simple config doesn't have one.
          // So list all and filter out all known prefixes.
          $active_list = $this->omitKnownPrefixes($this->activeConfigStorage->listAll());
          $install_list = $this->omitKnownPrefixes($this->extensionConfigStorage->listAll());
          $optional_list = $this->omitKnownPrefixes($this->extensionOptionalConfigStorage->listAll());
        }
        elseif (isset($this->definitions[$name])) {
          $definition = $this->definitions[$name];
          $prefix = $definition->getConfigPrefix();
          $active_list = $this->activeConfigStorage->listAll($prefix);
          $install_list = $this->extensionConfigStorage->listAll($prefix);
          $optional_list = $this->extensionOptionalConfigStorage->listAll($prefix);
        }
        break;

      case 'profile':
        $name = Settings::get('install_profile');
        // Intentional fall-through here to the 'module' or 'theme' case.
      case 'module':
      case 'theme':
        $active_list = $this->activeConfigStorage->listAll();
        $install_list = $this->listProvidedItems($list_type, $name);
        $optional_list = $this->listProvidedItems($list_type, $name, TRUE);
        break;
    }

    return [$active_list, $install_list, $optional_list];
  }

  /**
   * Returns a list of the install storage items for an extension.
   *
   * @param string $type
   *   Type of extension ('module', etc.).
   * @param string $name
   *   Machine name of extension.
   * @param bool $do_optional
   *   FALSE (default) to list config/install items, TRUE to list
   *   config/optional items.
   *
   * @return string[]
   *   List of config items provided by this extension.
   */
  protected function listProvidedItems($type, $name, $do_optional = FALSE) {
    $pathname = drupal_get_filename($type, $name);
    $component = new Extension(\Drupal::root(), $type, $pathname);
    if ($do_optional) {
      $names = $this->extensionOptionalConfigStorage->getComponentNames([$component]);
    }
    else {
      $names = $this->extensionConfigStorage->getComponentNames([$component]);
    }
    return array_keys($names);
  }

  /**
   * Omits config with known prefixes from a list of config names.
   */
  protected function omitKnownPrefixes($list) {
    $prefixes = array_keys($this->typesByPrefix);
    $list = array_combine($list, $list);
    foreach ($list as $name) {
      foreach ($prefixes as $prefix) {
        if (strpos($name, $prefix) === 0) {
          unset($list[$name]);
        }
      }
    }

    return array_values($list);
  }

}
