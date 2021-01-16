<?php

namespace Drupal\social_album\Plugin\views\access;

use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control to the user albums page.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "social_album_user",
 *   title = @Translation("User albums")
 * )
 */
class SocialAlbumUserAccess extends SocialAlbumGroupAccess {

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    parent::alterRouteDefinition($route);

    $route->setRequirement('_permission', 'access user profiles');
  }

}
