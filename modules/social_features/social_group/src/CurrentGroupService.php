<?php

namespace Drupal\social_group;

use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Centralized ways to get the current group.
 *
 * @package Drupal\social_group.
 */
class CurrentGroupService {

  /**
   * The context repository interface.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  private ContextRepositoryInterface $contextRepository;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   */
  public function __construct(
    ContextRepositoryInterface $context_repository,
  ) {
    $this->contextRepository = $context_repository;
  }

  /**
   * Get group from runtime contexts.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The current group or NULL.
   */
  public function fromRunTimeContexts(): ?GroupInterface {
    $group_runtime_context = $this->contextRepository->getRuntimeContexts(['@group.group_route_context:group']);

    $group = $group_runtime_context['@group.group_route_context:group']->getContextData()->getValue();
    if ($group === NULL) {
      return NULL;
    }

    assert($group instanceof GroupInterface, "The group context resolver returned a context value that is not a GroupInterface instance which violates the services contract.");
    return $group;
  }

}
