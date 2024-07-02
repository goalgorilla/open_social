<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'Summary' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_summary",
 *  label = @Translation("Summary"),
 *  weight = -285,
 *  dependencies = @PluginDependency(
 *    config = {
 *      "field.field.profile.profile.field_profile_summary",
 *    },
 *  )
 * )
 */
class UserSummary extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Summary');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetFieldValue('field_profile_summary', $this->getProfile($entity));
  }

}
