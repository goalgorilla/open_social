<?php

namespace Drupal\social_private_message\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsPrivateMessage' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_private_message",
 *  label = @Translation("Number of Private messages"),
 *  weight = -180,
 * )
 */
class UserAnalyticsPrivateMessage extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): string|TranslatableMarkup {
    return $this->t('Number of Private messages');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): int|string {
    $value = '';

    try {
      $storage = $this->entityTypeManager->getStorage('private_message');
      if ($storage instanceof ContentEntityStorageInterface) {
        $value = (int) $storage->getQuery()
          ->accessCheck()
          ->condition('owner', $entity->id())
          ->count()
          ->execute();
      }
    }
    catch (\Exception $e) {
    }
    return $value;
  }

}
