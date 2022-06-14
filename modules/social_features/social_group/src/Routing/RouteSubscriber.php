<?php

namespace Drupal\social_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_group\Controller\SocialGroupController;
use Drupal\social_group\Form\SocialGroupAddForm;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Route the group view page to group/{group}/timeline.
    if ($route = $collection->get('entity.group.canonical')) {
      $route
        ->setPath('/group/{group}/stream')
        ->setDefault('_entity_view', 'group.stream')
        ->setDefault(
          '_title_callback',
          SocialGroupController::class . '::groupStreamTitle',
        );
    }

    // Override default title for Group Members page.
    if ($route = $collection->get('view.group_members.page_group_members')) {
      $route->setDefault(
        '_title_callback',
        SocialGroupController::class . '::groupMembersTitle',
      );
    }

    // Override default title for Groups "Add Member" page.
    if ($route = $collection->get('entity.group_content.add_form')) {
      $route->setDefault(
        '_title_callback',
        SocialGroupController::class . '::groupAddMemberTitle',
      );
    }

    // Override default title for Groups "Delete Content" page.
    if ($route = $collection->get('entity.group_content.delete_form')) {
      $route->setDefault(
        '_title_callback',
        SocialGroupController::class . '::groupRemoveContentTitle',
      );
    }

    if ($route = $collection->get('entity.group.add_page')) {
      $defaults = $route->getDefaults();
      unset($defaults['_controller']);
      $defaults['_form'] = SocialGroupAddForm::class;
      $route->setDefaults($defaults);
    }

    if ($route = $collection->get('view.groups.page_user_groups')) {
      $route->setRequirement(
        '_custom_access',
        SocialGroupController::class . '::myGroupAccess',
      );
    }

  }

}
