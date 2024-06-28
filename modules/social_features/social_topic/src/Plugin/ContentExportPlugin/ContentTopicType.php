<?php

namespace Drupal\social_topic\Plugin\ContentExportPlugin;

use Drupal\node\NodeInterface;
use Drupal\social_content_export\Plugin\ContentExportPluginBase;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a 'ContentTopicType' content export row.
 *
 * @ContentExportPlugin(
 *   id = "content_topic_type",
 *   label = @Translation("Topic Type"),
 *   weight = -140,
 * )
 */
class ContentTopicType extends ContentExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue(NodeInterface $entity): string {
    if ($entity->getType() == 'topic') {
      $taxonomy = $entity->get('field_topic_type')->entity;
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
