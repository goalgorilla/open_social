<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAccountName' user export row.
 *
 * @UserExportPlugin(
 *  id = "account_name",
 *  label = @Translation("Username"),
 *  weight = -460,
 * )
 */
class UserAccountName extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Username');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $entity->getAccountName();
  }

}
