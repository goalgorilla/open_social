<?php

namespace Drupal\social_event_type\Plugin\ContentExportPlugin;

use Drupal\node\NodeInterface;
use Drupal\social_content_export\Plugin\ContentExportPluginBase;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a 'ContentEventType' content export row.
 *
 * @ContentExportPlugin(
 *   id = "content_event_type",
 *   label = @Translation("Event Type"),
 *   weight = -130,
 * )
 */
class ContentEventType extends ContentExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue(NodeInterface $entity): string {
    if ($entity->getType() == 'event' && $entity->hasField('field_event_type')) {
      $taxonomy = $entity->get('field_event_type')->entity;
      if ($taxonomy instanceof TermInterface) {
        return $taxonomy->getName();
      }
      else {
        return '';
      }
    }

    return '';
  }

}
