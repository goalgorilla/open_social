<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserDisplayName' user export row.
 *
 * @UserExportPlugin(
 *  id = "display_name",
 *  label = @Translation("Display name"),
 *  weight = -450,
 * )
 */
class UserDisplayName extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Display name');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $entity->getDisplayName();
  }

}
