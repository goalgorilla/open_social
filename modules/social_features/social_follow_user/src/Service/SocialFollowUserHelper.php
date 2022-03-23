<?php

namespace Drupal\social_follow_user\Service;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the helper service.
 */
class SocialFollowUserHelper implements SocialFollowUserHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function preview(
    ProfileInterface $profile,
    array &$variables,
    $path = 'attributes'
  ): void {
    if ($profile->access('view')) {
      if (!NestedArray::keyExists($variables, $path = (array) $path)) {
        NestedArray::setValue($variables, $path, []);
      }

      $attributes = &NestedArray::getValue($variables, $path);

      $attributes['id'] = Html::getUniqueId('profile-preview');
      $attributes['data-profile'] = $profile->id();

      $variables['#attached']['library'][] = 'social_follow_user/preview';
    }
  }

}
