<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserSelfIntroduction' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_self_introduction",
 *  label = @Translation("Self Introduction"),
 *  weight = -280,
 * )
 */
class UserSelfIntroduction extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Self introduction');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetFieldValue('field_profile_self_introduction', $this->getProfile($entity));
  }

}
