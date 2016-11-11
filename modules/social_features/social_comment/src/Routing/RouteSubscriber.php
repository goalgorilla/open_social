<?php

namespace Drupal\social_comment\Routing;

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
    // Redirect comment/comment page to entity if applicable.
    $config = \Drupal::config('social_comment.comment_settings');
    $redirect_comment_to_entity = $config->get('redirect_comment_to_entity');

    /** @var \Symfony\Component\Routing\Route $route */
    if ($redirect_comment_to_entity === TRUE && $route = $collection->get('entity.comment.canonical')) {
      $route->setDefaults(array(
        '_controller' => '\Drupal\social_comment\Controller\SocialCommentController::commentPermalink',
      ));
    }

    // Override default title for comment reply page.
    // @TODO: For some reason this doesn't work.
    if ($route = $collection->get('comment.reply')) {
      $defaults = $route->getDefaults();
      $defaults['_title'] = t('Add new reply');
      $route->setDefaults($defaults);
    }

  }

}
