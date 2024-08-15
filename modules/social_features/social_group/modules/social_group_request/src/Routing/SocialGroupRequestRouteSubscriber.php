<?php

namespace Drupal\social_group_request\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_group_request\Controller\GroupRequestController;
use Drupal\social_group_request\Form\GroupRequestMembershipRejectForm;
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
      ...$request_membership_route->getDefaults(),
      '_title_callback' => GroupRequestController::class . "::requestMembershipTitle",
    ]);

    if ($route = $collection->get('grequest.group_request_membership_approve')) {
      $route->setDefaults([
        '_title_callback' => GroupRequestController::class . '::getTitleApproveRequest',
        '_controller' => GroupRequestController::class . '::approveRequest',
      ]);
    }

    if ($route = $collection->get('grequest.group_request_membership_reject')) {
      $route->setDefaults([
        '_title_callback' => GroupRequestController::class . '::getTitleRejectRequest',
        '_form' => GroupRequestMembershipRejectForm::class,
      ]);
    }

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
