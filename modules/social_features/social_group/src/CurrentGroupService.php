<?php

namespace Drupal\social_group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Centralized ways to get the current group.
 *
 * @package Drupal\social_group.
 */
class CurrentGroupService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The group route context.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  private ContextProviderInterface $groupRouteContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $group_route_context
   *   The group route context.
   */
  public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        ContextProviderInterface $group_route_context,
    ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupRouteContext = $group_route_context;
  }

  /**
   * Get group from runtime contexts.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The current group or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function fromRunTimeContexts(): ?GroupInterface {
    $runtime_context = $this->groupRouteContext->getRuntimeContexts([]);
    if (isset($runtime_context['group']) === FALSE) {
      return NULL;
    }

    $group = $runtime_context['group']->getContextData()->getValue();
    if ($group instanceof GroupInterface) {
      return $group;
    }

    if (is_int($group) === TRUE) {
      /** @var \Drupal\group\Entity\GroupInterface $loadedGroup */
      $loadedGroup = $this->entityTypeManager
        ->getStorage('group')->load($group);
      return $loadedGroup;
    }

    return NULL;
  }

}
