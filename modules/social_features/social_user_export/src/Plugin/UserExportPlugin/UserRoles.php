<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserRoles' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_roles",
 *  label = @Translation("Roles"),
 *  weight = -260,
 * )
 */
class UserRoles extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Roles');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return implode(', ', $entity->getRoles());
  }

}
