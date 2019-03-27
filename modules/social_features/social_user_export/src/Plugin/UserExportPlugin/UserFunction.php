<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserFunction' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_function",
 *  label = @Translation("Function"),
 *  weight = -310,
 * )
 */
class UserFunction extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Function');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetFieldValue('field_profile_function', $this->getProfile($entity));
  }

}
