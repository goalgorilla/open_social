<?php

namespace Drupal\social_course\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Group general routes.
 */
class GroupController extends EntityController {

  /**
   * The course wrapper.
   *
   * @var \Drupal\social_course\CourseWrapperInterface
   */
  protected $courseWrapper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->courseWrapper = $container->get('social_course.course_wrapper');

    return $instance;
  }

  /**
   * Access callback of the group page.
   */
  public function access(GroupInterface $group) {
    $account = \Drupal::currentUser();
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
