<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserId' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_id",
 *  label = @Translation("User ID"),
 *  weight = -500,
 * )
 */
class UserId extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('User ID');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $entity->id();
  }

}
