<?php

/**
 * @file
 * Contains \Drupal\profile\ProfileViewBuilder.
 */

namespace Drupal\profile;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for profile entities.
 */
class ProfileViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode);
    $defaults['#theme'] = 'profile';
    return $defaults;
  }

}
