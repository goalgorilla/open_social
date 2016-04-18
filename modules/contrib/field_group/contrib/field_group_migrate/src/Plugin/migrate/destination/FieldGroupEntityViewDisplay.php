<?php

/**
 * @file
 * Contains \Drupal\field_group_migrate\Plugin\migrate\destination\FieldGroupEntityViewDisplay.
 */

namespace Drupal\field_group_migrate\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\PerComponentEntityDisplay;
use Drupal\migrate\Row;

/**
 * This class imports one field_group of an entity form display.
 *
 * @MigrateDestination(
 *   id = "field_group_entity_view_display"
 * )
 */
class FieldGroupEntityViewDisplay extends PerComponentEntityDisplay {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $values = array();
    // array_intersect_key() won't work because the order is important because
    // this is also the return value.
    foreach (array_keys($this->getIds()) as $id) {
      $values[$id] = $row->getDestinationProperty($id);
    }

    foreach ($row->getSourceProperty('view_modes') as $view_mode => $settings) {
      $entity = $this->getEntity($values['entity_type'], $values['bundle'], $view_mode);
      if (!$entity->isNew()) {
        $settings = array_merge($row->getDestinationProperty('field_group'), $settings);
        $entity->setThirdPartySetting('field_group', $row->getDestinationProperty('id'), $settings);
        if (isset($settings['format_type']) && ($settings['format_type'] == 'no_style' || $settings['format_type'] == 'hidden')) {
          $entity->unsetThirdPartySetting('field_group', $row->getDestinationProperty('id'));
        }
        $entity->save();
      }
    }

    return array_values($values);
  }

}
