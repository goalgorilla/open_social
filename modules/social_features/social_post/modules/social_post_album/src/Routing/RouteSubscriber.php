<?php

namespace Drupal\social_post_album\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_post_album\Controller\SocialPostAlbumAjaxCommentsController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if (!($parent_route = $collection->get('social_ajax_comments.add'))) {
      return;
    }

    $route = clone $parent_route;

    $route->setPath('/social-post-album' . $route->getPath());

    $route->setDefault(
      '_controller',
      SocialPostAlbumAjaxCommentsController::class . '::socialAdd'
    );

    $collection->add('social_post_album.ajax_comments.add', $route);
  }

}
