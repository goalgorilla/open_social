<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipCacheContextBase.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Context\GroupRouteContextTrait;

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
   * Instead of relying on the Group context provider, we re-use some of its
   * logic for retrieving a group entity from the route. This is because cache
   * contexts need to be really fast and loading the whole context service is
   * slower than simply using the 'current_route_match' service.
   */
  use GroupRouteContextTrait;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupMembershipCacheContextBase class.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $current_route_match, AccountInterface $user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRouteMatch = $current_route_match;
    $this->group = $this->getGroupFromRoute();
    $this->user = $user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks whether this context got an existing group from the route.
   *
   * @return bool
   *   Whether we've got an existing group.
   */
  protected function hasExistingGroup() {
    return !empty($this->group) && $this->group->id();
  }

  /**
   * Gets the group role storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupRoleStorageInterface
   */
  protected function groupRoleStorage() {
    return $this->entityTypeManager->getStorage('group_role');
  }

}
