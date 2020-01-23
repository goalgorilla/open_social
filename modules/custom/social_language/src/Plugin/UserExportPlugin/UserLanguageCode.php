<?php

namespace Drupal\social_language\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserLanguageCode' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_language_code",
 *  label = @Translation("Language code"),
 *  weight = -340,
 * )
 */
class UserLanguageCode extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Language code');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity):string {
    return $entity->getPreferredLangcode();
  }

}
