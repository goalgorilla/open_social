<?php

namespace Drupal\social_profile_preview\Service;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Template\Attribute;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the helper service.
 */
class SocialProfilePreviewHelper implements SocialProfilePreviewHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(
    ProfileInterface $profile,
    array &$variables,
    $path = 'attributes'
  ): void {
    if ($profile->access('view')) {
      if (!NestedArray::keyExists($variables, $path = (array) $path)) {
        NestedArray::setValue($variables, $path, []);
      }

      $attributes = &NestedArray::getValue($variables, $path);

      if ($is_object = $attributes instanceof Attribute) {
        $attributes = $attributes->toArray();
      }

      $attributes['class'][] = 'profile-preview';
      $attributes['data-profile'] = $profile->id();

      if ($is_object) {
        $attributes = new Attribute($attributes);
      }

      $variables['#attached']['library'][] = 'social_profile_preview/base';
    }
  }

}
