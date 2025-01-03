<?php

namespace Drupal\social_group_invite\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_group_invite\Controller\SocialGroupInvitationController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_group_invite\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('ginvite.invitation.bulk')) {
      $defaults = $route->getDefaults();
      $requirements = $route->getRequirements();
      $defaults['_form'] = '\Drupal\social_group_invite\Form\SocialBulkGroupInvitation';
      // Add custom access check for the invite page.
      $requirements['_custom_access'] = '\Drupal\social_group_invite\Form\SocialBulkGroupInvitation::inviteAccess';
      unset($requirements['_group_permission']);
      $route->setDefaults($defaults);
      $route->setRequirements($requirements);
    }

    // Do not allow to accept invitation without "join group" permission.
    if ($route = $collection->get('ginvite.invitation.accept')) {
      $route->setRequirement(
        '_custom_access',
        SocialGroupInvitationController::class . '::checkAccess',
      );
    }

    // @todo This should use a View access handler rather than a route alter.
    if ($route = $collection->get('view.social_group_user_invitations.page_1')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = 'social_group_invite.access::userInviteAccess';
      $route->setRequirements($requirements);
    }
  }

}
