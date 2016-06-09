<?php

namespace Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter;

/**
 * Trait to override EntityReferenceFormatterBase::prepareView().
 */
trait DynamicEntityReferenceFormatterTrait {

  /**
   * Overrides EntityReferenceFormatterBase::prepareView().
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed.
   *
   * @param \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList[] $entities_items
   *   Array of field values, keyed by entity ID.
   */
  public function prepareView(array $entities_items) {
    // Load the existing (non-autocreate) entities. For performance, we want to
    // use a single "multiple entity load" to load all the entities for the
    // multiple "entity reference item lists" that are being displayed. We thus
    // cannot use
    // \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList::referencedEntities().
    $ids = array();
    foreach ($entities_items as $items) {
      /** @var \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem $item */
      foreach ($items as $item) {
        // To avoid trying to reload non-existent entities in
        // getEntitiesToView(), explicitly mark the items where $item->entity
        // contains a valid entity ready for display. All items are initialized
        // at FALSE.
        $item->_loaded = FALSE;
        if (!$item->hasNewEntity()) {
          $ids[$item->target_type][] = $item->target_id;
        }
      }
    }
    if ($ids) {
      foreach (array_keys($ids) as $target_type) {
        $target_entities[$target_type] = \Drupal::entityManager()->getStorage($target_type)->loadMultiple($ids[$target_type]);
      }
    }

    // For each item, pre-populate the loaded entity in $item->entity, and set
    // the 'loaded' flag.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if (isset($target_entities[$item->target_type]) && isset($target_entities[$item->target_type][$item->target_id])) {
          $item->entity = $target_entities[$item->target_type][$item->target_id];
          $item->_loaded = TRUE;
        }
        elseif ($item->hasNewEntity()) {
          $item->_loaded = TRUE;
        }
      }
    }
  }

}
