<?php
/**
* @file
* Contains \Drupal\social_post\Routing\RouteSubscriber.
*/

namespace Drupal\social_post\Routing;

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
    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get('comment.reply')) {
      $route->setDefaults(array(
        '_controller' => '\Drupal\social_post\Controller\PostCommentController::getReplyForm',
        '_title' => 'Add new comment',
        'pid' => NULL,
      ));
    }
  }

}
