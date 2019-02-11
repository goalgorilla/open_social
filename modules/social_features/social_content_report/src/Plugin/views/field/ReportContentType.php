<?php

namespace Drupal\social_content_report\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present the entity type or bundle type for Nodes.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("flagging_entity_bundle_type")
 */
class ReportContentType extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    /** @var \Drupal\flag\FlaggingInterface $flagging */
    $flagging = $this
      ->getEntity($row);

    if ($flagging->getFlaggable()->getEntityTypeId() === 'node') {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $flagging->getFlaggable();
      return ucfirst($node->bundle());
    }
    else {
      return $flagging->getFlaggable()->getEntityType()->getLabel();
    }
  }

}
