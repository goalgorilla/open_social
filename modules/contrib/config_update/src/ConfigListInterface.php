<?php

namespace Drupal\config_update;

/**
 * Defines an interface for config listings.
 */
interface ConfigListInterface {

  /**
   * Lists the types of configuration available on the system.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   Array of entity type definitions, keyed by machine name of the type.
   */
  public function listTypes();

  /**
   * Returns the entity type object for a given config type name.
   *
   * @param string $name
   *   Config entity type machine name.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   Entity type object with machine name $name.
   */
  public function getType($name);

  /**
   * Returns the entity type object for a given config prefix.
   *
   * @param string $prefix
   *   Config prefix.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   Entity type object corresponding to $prefix.
   */
  public function getTypeByPrefix($prefix);

  /**
   * Returns the entity type object for a given config object.
   *
   * @param string $name
   *   Name of the config object.
   *
   * @return string
   *   Name of the entity type that this is an instance of, determined by
   *   prefix. NULL for simple configuration.
   */
  public function getTypeNameByConfigName($name);

  /**
   * Lists the config objects in active and extension storage.
   *
   * @param string $list_type
   *   Type of list to make: 'type', 'module', 'theme', or 'profile'.
   * @param string $name
   *   Machine name of a configuration type, module, or theme to generate the
   *   list for. Ignored for profile, since that uses the active profile. Use
   *   type 'system.simple' for simple config, and 'system.all' to list all
   *   config items.
   *
   * @return array
   *   Array whose first element is the list of config objects in active
   *   storage, second is the list of config objects in extension storage,
   *   and third is the list of optional config objects in extension storage
   *   (the ones with dependencies from config/optional directories).
   *   Note that for everything except 'type' lists, the active storage list
   *   includes all configuration items in the system, not limited to ones from
   *   this module, theme, or profile.
   */
  public function listConfig($list_type, $name);

}
