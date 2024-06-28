<?php

namespace Drupal\social_event\Plugin\ContentExportPlugin;

use Drupal\node\NodeInterface;
use Drupal\social_content_export\Plugin\ContentExportPluginBase;

/**
 * Provides a 'ContentEventType' content export row.
 *
 * @ContentExportPlugin(
 *   id = "content_event_enrollees",
 *   label = @Translation("Enrollees"),
 *   weight = -120,
 * )
 */
class ContentEventEnrollees extends ContentExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue(NodeInterface $entity): string {
    if ($entity->getType() == 'event') {
      $storage = $this->entityTypeManager->getStorage('event_enrollment');
      $enrollments_count = $storage->getQuery()
        ->condition('field_event', $entity->id())
        ->condition('field_enrollment_status', 1)
        ->accessCheck()
        ->count()
        ->execute();

      return (string) $enrollments_count;
    }

    return '';
  }

}
