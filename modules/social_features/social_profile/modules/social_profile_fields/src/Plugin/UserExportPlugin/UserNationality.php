<?php

namespace Drupal\social_profile_fields\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserNationality' user export row.
 *
 * @UserExportPlugin(
 *   id = "user_nationality",
 *   label = @Translation("Nationality"),
 *   weight = -325,
 * )
 */
class UserNationality extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Nationality');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetTaxonomyFieldValue(
      'field_profile_nationality',
      $this->getProfile($entity)
    );
  }

}
