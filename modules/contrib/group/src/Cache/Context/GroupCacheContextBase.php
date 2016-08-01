<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupCacheContextBase.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Context\GroupRouteContextTrait;

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
   * Constructs a new GroupCacheContextBase class.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   */
  public function __construct(RouteMatchInterface $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
    $this->group = $this->getGroupFromRoute();
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

}
