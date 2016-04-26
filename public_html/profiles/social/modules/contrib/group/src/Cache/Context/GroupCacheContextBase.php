<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupCacheContextBase.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Plugin\Context\ContextProviderInterface;

/**
 * Base class for group-based cache contexts.
 *
 * This cache context retrieves the group from the active route by re-using the
 * logic in the injected context provider service, which defaults to
 * \Drupal\group\Context\GroupRouteContext.
 *
 * Subclasses need to implement either
 * \Drupal\Core\Cache\Context\CacheContextInterface or
 * \Drupal\Core\Cache\Context\CalculatedCacheContextInterface.
 */
abstract class GroupCacheContextBase {

  /**
   * The group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new GroupCacheContextBase class.
   *
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(ContextProviderInterface $context_provider) {
    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $contexts = $context_provider->getRuntimeContexts(['group']);
    $this->group = $contexts['group']->getContextValue();
  }

}
