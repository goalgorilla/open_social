<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserFirstName' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_first_name",
 *  label = @Translation("First name"),
 *  weight = -480,
 * )
 */
class UserFirstName extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('First name');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    return $this->profileGetFieldValue('field_profile_first_name', $this->getProfile($entity));
  }

}
