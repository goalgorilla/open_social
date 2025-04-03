<?php

namespace Drupal\social_group_flexible_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Session\AccountProxy;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Group general routes.
 */
class FlexibleGroupController extends EntityController {

  /**
   * The current user.
   */
  protected AccountProxy $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');

    return $instance;
  }

  /**
   * Access callback of the group pages.
   */
  public function access(GroupInterface $group): AccessResult {
    $account = $this->currentUser->getAccount();
    $access = AccessResult::forbidden();

    // Allow if group doesn't have field that regulates access or is published.
    if (!$group->hasField('status') || $group->get('status')->value) {
      $access = AccessResult::allowed();
    }
    // Allow if user has the 'access content overview' permission on group.
    elseif ($group->hasPermission('access content overview', $account)) {
      $access = AccessResult::allowed();
    }
    // Allow if user has access to all unpublished groups.
    elseif ($account->hasPermission('view unpublished groups')) {
      $access = AccessResult::allowed();
    }
    // Allow if user is an author of the group and has access to view
    // own unpublished groups.
    elseif ($account->hasPermission('view own unpublished groups')) {
      if ($group->getOwnerId() === $account->id()) {
        $access = AccessResult::allowed();
      }
    }

    return $access
      ->cachePerPermissions()
      ->cachePerUser();
  }

}
