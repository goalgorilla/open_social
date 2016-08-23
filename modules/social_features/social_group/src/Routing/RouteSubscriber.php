<?php

namespace Drupal\social_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_group\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Route the group view page to group/{group}/timeline.
    if ($route = $collection->get('entity.group.canonical')) {
      $route->setPath('/group/{group}/stream');
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_group\Controller\SocialGroupController::groupStreamTitle';
      $route->setDefaults($defaults);
    }

    // Route the group members page to the group/{group}/membership.
    if ($route = $collection->get('entity.group_content.collection')) {
      // Override default title for Group Membership page.
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_group\Controller\SocialGroupController::groupMembersTitle';
      $route->setDefaults($defaults);
      // Override default path for Group Membership page.
      $route->setPath('/group/{group}/membership');
    }

    // Override default title for Group Members page.
    if ($route = $collection->get('view.group_members.page_group_members')) {
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_group\Controller\SocialGroupController::groupMembersTitle';
      $route->setDefaults($defaults);
    }

    // Override default title for Groups "Add Member" page.
    if ($route = $collection->get('entity.group_content.add_form')) {
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_group\Controller\SocialGroupController::groupAddMemberTitle';
      $route->setDefaults($defaults);
    }
  }

}
