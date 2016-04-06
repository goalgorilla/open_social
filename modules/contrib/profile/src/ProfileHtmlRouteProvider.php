<?php

/**
 * @file
 * Contains \Drupal\profile\ProfileHtmlRouteProvider.
 */

namespace Drupal\profile;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\profile\Entity\ProfileType;
use Symfony\Component\Routing\Route;

/**
 * Provides HTML routes for the profile entity type.
 */
class ProfileHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    foreach (ProfileType::loadMultiple() as $profile_type) {
      $route = (new Route(
        "/user/{user}/{profile_type}",
        ['_controller' => '\Drupal\profile\Controller\ProfileController::userProfileForm'],
        ['_profile_access_check' => 'add'],
        [
          'parameters' => [
            'user' => ['type' => 'entity:user'],
            'profile_type' => ['type' => 'entity:profile_type'],
          ],
        ])
      );
      $collection->add("entity.profile.type.{$profile_type->id()}.user_profile_form", $route);

      // If the profile type supports multiple, we need an additional route for
      // adding new profiles.
      if ($profile_type->getMultiple()) {
        $route = (new Route(
          "/user/{user}/{profile_type}/add",
          ['_controller' => '\Drupal\profile\Controller\ProfileController::addProfile'],
          ['_profile_access_check' => 'add'],
          [
            'parameters' => [
              'user' => ['type' => 'entity:user'],
              'profile_type' => ['type' => 'entity:profile_type'],
            ],
          ])
        );
        $collection->add("entity.profile.type.{$profile_type->id()}.user_profile_form.add", $route);
      }
    }

    return $collection;
  }

}
