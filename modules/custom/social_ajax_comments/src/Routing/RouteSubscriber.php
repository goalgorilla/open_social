<?php

namespace Drupal\social_ajax_comments\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Replace "ajax_comments.cancel" with our implementation.
    // We need this custom implementation because we need to remove and invoke
    // additional ajax commands due to our theme.
    if ($route = $collection->get('ajax_comments.cancel')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] = '\Drupal\social_ajax_comments\Controller\AjaxCommentsController::socialCancel';
      $route->setDefaults($defaults);
    }

    // Replace "ajax_comments.add" with our implementation.
    // We need this custom implementation because we don't use reply on a reply
    // the same way as comments do. We use reply on a reply by using the normal
    // add form with a mention. Therefor we need to override the message display
    // so ajax comments understands where to put the status message.
    if ($route = $collection->get('ajax_comments.add')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] = '\Drupal\social_ajax_comments\Controller\AjaxCommentsController::socialAdd';
      $route->setDefaults($defaults);
    }
  }

}
