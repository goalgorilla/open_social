<?php

namespace Drupal\social_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;

/**
 * Class LayoutService.
 *
 * @package Drupal\social_core\Service
 */
class LayoutService {

  use LayoutEntityHelperTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHander;

  /**
   * The LayoutService constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHander = $module_handler;
  }

  /**
   * Determines if an entity can have a layout.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity can have a layout otherwise FALSE.
   */
  public function isTrueLayoutCompatibleEntity(EntityInterface $entity): bool {
    if (!$this->moduleHander->moduleExists('layout_builder')) {
      return FALSE;
    }

    return $this->isLayoutCompatibleEntity($entity);
  }

}
