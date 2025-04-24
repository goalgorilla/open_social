<?php

namespace Drupal\social_group_request\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_group_request\Controller\GroupRequestController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class SocialGroupRequestRouteSubscriber.
 */
class SocialGroupRequestRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $request_membership_route = $collection->get('entity.group.group_request_membership');
    assert($request_membership_route !== NULL, "The group request membership route in grequest changed or the grequest module was not added to the social_group_request.info.yml file.");
    $request_membership_route->setDefaults([
      '_title_callback' => GroupRequestController::class . "::requestMembershipTitle",
      ...$request_membership_route->getDefaults(),
    ]);

    $approve_membership_route = $collection->get('entity.group_content.group_approve_membership');
    assert($approve_membership_route !== NULL, "The group approve membership route in grequest changed or the grequest module was not added to the social_group_request.info.yml file.");
    $approve_membership_route->setDefaults([
      '_title_callback' => GroupRequestController::class . "::getTitleApproveRequest",
      ...$approve_membership_route->getDefaults(),
    ]);

    $reject_membership_route = $collection->get('entity.group_content.group_reject_membership');
    assert($reject_membership_route !== NULL, "The group reject membership route in grequest changed or the grequest module was not added to the social_group_request.info.yml file.");
    $reject_membership_route->setDefaults([
      '_title_callback' => GroupRequestController::class . "::getTitleRejectRequest",
      ...$reject_membership_route->getDefaults(),
    ]);

    if ($route = $collection->get('view.group_pending_members.page_1')) {
      $route->setRequirements([
        '_custom_access' => GroupRequestController::class . '::routeAccess',
      ]);
    }

    if ($route = $collection->get('view.group_pending_members.membership_requests')) {
      $route->setRequirements([
        '_custom_access' => GroupRequestController::class . '::routeAccess',
      ]);
    }
  }

}
