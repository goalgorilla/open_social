<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserEmail' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_email",
 *  label = @Translation("Email"),
 *  weight = -440,
 * )
 */
class UserEmail extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): string {
    return $this->t('Email');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    return $entity->getEmail();
  }

}
