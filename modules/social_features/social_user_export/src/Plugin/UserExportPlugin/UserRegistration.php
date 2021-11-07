<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserRegistration' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_registration",
 *  label = @Translation("Registration date"),
 *  weight = -410,
 * )
 */
class UserRegistration extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Registration date');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    return $this->format($entity);
  }

  /**
   * Formats a date, using a date type or a custom date format string.
   *
   * @param mixed $entity
   *   The entity object.
   *
   *   A translated date string in the requested format. Since the format may
   *   contain user input, this value should be escaped when output.
   */
  public function format($entity): string {
    return $this->dateFormatter->format($entity->getCreatedTime(), 'short');
  }

}
