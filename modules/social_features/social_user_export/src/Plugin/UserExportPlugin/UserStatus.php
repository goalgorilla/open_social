<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserStatus' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_status",
 *  label = @Translation("Status"),
 *  weight = -400,
 * )
 */
class UserStatus extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Status');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): TranslatableMarkup {
    return $entity->isActive() ? $this->t('Active') : $this->t('Blocked');
  }

}
