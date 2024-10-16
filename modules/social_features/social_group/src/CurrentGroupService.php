<?php

namespace Drupal\social_group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\group\Entity\GroupInterface;

class CurrentGroupService {

  private EntityTypeManagerInterface $entityTypeManager;
  private ContextProviderInterface $groupRouteContext;


  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ContextProviderInterface $group_route_context,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupRouteContext = $group_route_context;
  }

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
      /** @var GroupInterface $loadedGroup */
      $loadedGroup = $this->entityTypeManager->getStorage('group')->load($group);
      return $loadedGroup;
    }

    return NULL;
  }
}
