<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipCacheContextBase.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;

/**
 * Base class for group membership-based cache contexts.
 *
 * This cache context retrieves the group from the active route by re-using the
 * logic in the injected context provider service, which defaults to
 * \Drupal\group\Context\GroupRouteContext.
 *
 * Subclasses need to implement either
 * \Drupal\Core\Cache\Context\CacheContextInterface or
 * \Drupal\Core\Cache\Context\CalculatedCacheContextInterface.
 */
abstract class GroupMembershipCacheContextBase {

  /**
   * The group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a new GroupMembershipCacheContextBase class.
   *
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(ContextProviderInterface $context_provider, AccountInterface $user) {
    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $contexts = $context_provider->getRuntimeContexts(['group']);
    $this->group = $contexts['group']->getContextValue();
    $this->user = $user;
  }

}
