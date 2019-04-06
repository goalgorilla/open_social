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

    $reported_entity = $flagging->getFlaggable();
    if ($reported_entity->getEntityTypeId() === 'node') {
      /** @var \Drupal\node\NodeInterface $reported_entity */
      return node_get_type_label($reported_entity);
    }
    else {
      return $reported_entity->getEntityType()->getLabel();
    }
  }

}
