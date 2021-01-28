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
      $defaults['_entity_view'] = 'group.stream';
      $defaults['_title_callback'] = '\Drupal\social_group\Controller\SocialGroupController::groupStreamTitle';
      $route->setDefaults($defaults);
    }

    // Override the node creation page.
    if ($route = $collection->get('entity.group_content.create_form')) {
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_group\Controller\SocialGroupController::createFormTitle';
      $route->setDefaults($defaults);
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

    // Override default title for Groups "Delete Content" page.
    if ($route = $collection->get('entity.group_content.delete_form')) {
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_group\Controller\SocialGroupController::groupRemoveContentTitle';
      $route->setDefaults($defaults);
    }

    if ($route = $collection->get('entity.group.add_page')) {
      $defaults = $route->getDefaults();
      unset($defaults['_controller']);
      $defaults['_form'] = '\Drupal\social_group\Form\SocialGroupAddForm';
      $route->setDefaults($defaults);
    }

    if ($route = $collection->get('view.groups.page_user_groups')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = "\Drupal\social_group\Controller\SocialGroupController::myGroupAccess";
      $route->setRequirements($requirements);
    }

  }

}
