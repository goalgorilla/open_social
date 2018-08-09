<?php

namespace Drupal\social_private_message\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsPrivateMessage' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_private_message",
 *  label = @Translation("Number of Private messages"),
 *  weight = -190,
 * )
 */
class UserAnalyticsPrivateMessage extends UserExportPluginBase {

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Number of Private messages');
  }

  /**
   * Returns the value.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The User entity to get the value from.
   *
   * @return string
   *   The value.
   */
  public function getValue(UserInterface $entity) {
    $value = '';

    /** @var \Drupal\private_message\Entity\PrivateMessage $storage */
    try {
      $storage = $this->entityTypeManager->getStorage('private_message');
      if (!empty($storage)) {
        $query = $storage->getQuery();
        $value = (string) $query->condition('owner', $entity->id())
          ->count()
          ->execute();
      }
    }
    catch (\Exception $e) {
    }
    return $value;
  }

}
