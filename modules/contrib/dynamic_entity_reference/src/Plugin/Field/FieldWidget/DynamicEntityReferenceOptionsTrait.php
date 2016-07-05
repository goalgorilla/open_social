<?php

namespace Drupal\dynamic_entity_reference\Plugin\Field\FieldWidget;


use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;

/**
 * The common functionality between DynamicEntityReferenceOptionsWidgets.
 */
trait DynamicEntityReferenceOptionsTrait {

  /**
   * {@inheritdoc}
   *
   * This widget only support single target type dynamic entity reference
   * fields. Select list Check boxes and radio buttons don't make sense for
   * multiple target_types.
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return count(DynamicEntityReferenceItem::getTargetTypes($field_definition->getSettings())) == 1;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSelectedOptions(FieldItemListInterface $items, $delta = 0) {
    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($this->getOptions($items->getEntity()));

    $selected_options = array();
    foreach ($items as $item) {
      $value = "{$item->target_type}-{$item->target_id}";
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($flat_options[$value])) {
        $selected_options[] = $value;
      }
    }

    return $selected_options;
  }

  /**
   * {@inheritdoc}
   *
   * To save both target_type and target_id the option value is split into
   * target_type and target_id.
   *
   * @see \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem::getSettableOptions()
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $index => $value) {
      list($values[$index]['target_type'], $values[$index]['target_id']) = explode('-', $value['target_id']);
    }
    return $values;
  }

}
