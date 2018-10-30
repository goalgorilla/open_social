<?php

namespace Drupal\social_group;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class GroupContentVisibilityUpdate.
 *
 * @package Drupal\social_group
 */
class GroupContentVisibilityUpdate {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Update Group content after Group changed
   *
   * @return bool|true
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function updateGroupContentVisibility($group, $new_type) {

    return TRUE;
  }
}
