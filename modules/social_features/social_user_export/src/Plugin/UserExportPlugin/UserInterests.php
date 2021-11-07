<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserInterests' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_interests",
 *  label = @Translation("Interests"),
 *  weight = -290,
 * )
 */
class UserInterests extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Interests');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    return $this->profileGetTaxonomyFieldValue('field_profile_interests', $this->getProfile($entity));
  }

}
