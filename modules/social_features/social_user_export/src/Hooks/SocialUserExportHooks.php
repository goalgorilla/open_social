<?php

declare(strict_types=1);

namespace Drupal\social_user_export\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\hux\Attribute\Hook;
use Drupal\user_segments\Entity\UserSegment;

/**
 * Implements hooks for the Social User Export module.
 */
final class SocialUserExportHooks {

  /**
   * Implements hook_entity_operation().
   *
   * Adds CSV export operation to user segment entities.
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity): array {
    $operations = [];

    // User segments are lists of users, these can be exported using this module
    // if the user_segment module is installed.
    if (class_exists(UserSegment::class) && $entity instanceof UserSegment) {
      $operations['export_csv'] = [
        'title' => t('Export CSV'),
        'weight' => 15,
        'url' => Url::fromRoute('entity.user_segment.export_csv', [
          'user_segment' => $entity->id(),
        ]),
      ];
    }

    return $operations;
  }

}

