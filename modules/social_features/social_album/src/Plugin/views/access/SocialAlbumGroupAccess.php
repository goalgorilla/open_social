<?php

namespace Drupal\social_album\Plugin\views\access;

use Drupal\group\Plugin\views\access\GroupPermission;
use Drupal\social_album\Controller\SocialAlbumController;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control to the group albums page.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "social_album_group",
 *   title = @Translation("Group albums")
 * )
 */
class SocialAlbumGroupAccess extends GroupPermission {

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    parent::alterRouteDefinition($route);

    $route->setRequirement(
      '_custom_access',
      SocialAlbumController::class . '::checkGroupAlbumsAccess'
    );
  }

}
