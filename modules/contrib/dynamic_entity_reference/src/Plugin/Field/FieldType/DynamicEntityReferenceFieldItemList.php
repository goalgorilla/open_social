<?php

namespace Drupal\dynamic_entity_reference\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a item list class for dynamic entity reference fields.
 *
 * @property DynamicEntityReferenceItem[] list
 */
class DynamicEntityReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    // Remove the 'ValidReferenceConstraint' validation constraint because
    // dynamic entity reference fields already use the 'ValidDynamicReference'
    // constraint.
    foreach ($constraints as $key => $constraint) {
      if ($constraint instanceof ValidReferenceConstraint) {
        unset($constraints[$key]);
      }
    }
    $constraints = array_values($constraints);
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if (empty($this->list)) {
      return array();
    }

    // Collect the IDs of existing entities to load, and directly grab the
    // "autocreate" entities that are already populated in $item->entity.
    $target_entities = $ids = array();
    foreach ($this->list as $delta => $item) {
      if ($item->target_id !== NULL && $item->target_type !== NULL) {
        $ids[$item->target_type][$delta] = $item->target_id;
      }
      elseif ($item->hasNewEntity()) {
        $target_entities[$delta] = $item->entity;
      }
    }

    // Load and add the existing entities.
    if ($ids) {
      foreach ($ids as $target_type => $entity_type_ids) {
        $entities = \Drupal::entityManager()
          ->getStorage($target_type)
          ->loadMultiple($entity_type_ids);
        foreach ($entity_type_ids as $delta => $target_id) {
          if (isset($entities[$target_id])) {
            $target_entities[$delta] = $entities[$target_id];
          }
        }
      }
      // Ensure the returned array is ordered by deltas.
      ksort($target_entities);
    }

    return $target_entities;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $manager = \Drupal::entityManager();
    // We want to bypass the EntityReferenceItem::processDefaultValue() so
    // we duplicate FieldItemList::processDefaultValue() here which just returns
    // $default_values.
    if ($default_value) {
      // Convert UUIDs to numeric IDs.
      $all_uuids = array();
      foreach ($default_value as $delta => $properties) {
        if (isset($properties['target_uuid'])) {
          $target_type = $properties['target_type'];
          $all_uuids[$target_type][$delta] = $properties['target_uuid'];
        }
      }
      $entity_uuids = array();
      foreach ($all_uuids as $target_type => $uuids) {
        if ($uuids) {
          $entities = $manager
            ->getStorage($target_type)
            ->loadByProperties(array('uuid' => $uuids));
          $entity_uuids[$target_type] = array();
          foreach ($entities as $id => $entity) {
            $entity_uuids[$target_type][$entity->uuid()] = $id;
          }
          foreach ($uuids as $delta => $uuid) {
            if (isset($entity_uuids[$target_type]) && isset($entity_uuids[$target_type][$uuid])) {
              $default_value[$delta]['target_id'] = $entity_uuids[$target_type][$uuid];
              unset($default_value[$delta]['target_uuid']);
            }
            else {
              unset($default_value[$delta]);
            }
          }
        }
      }

      // Ensure we return consecutive deltas, in case we removed unknown UUIDs.
      $default_value = array_values($default_value);
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $manager = \Drupal::entityManager();
    $default_value = [];
    // We want to bypass the EntityReferenceItem::defaultValuesFormSubmit() so
    // we duplicate FieldItemList::defaultValuesFormSubmit() here.
    // Extract the submitted value, and store it as an array.
    if ($widget = $this->defaultValueWidget($form_state)) {
      $widget->extractFormValues($this, $element, $form_state);
      $default_value = $this->getValue();
    }

    // Convert numeric IDs to UUIDs to ensure config deployability.
    $all_ids = array();
    foreach ($default_value as $delta => $properties) {
      if (isset($properties['entity']) && $properties['entity']->isNew()) {
        // This may be a newly created term.
        $properties['entity']->save();
        $default_value[$delta]['target_id'] = $properties['entity']->id();
        $default_value[$delta]['target_type'] = $properties['entity']->getEntityTypeId();
        unset($default_value[$delta]['entity']);
      }
      $all_ids[$default_value[$delta]['target_type']][] = $default_value[$delta]['target_id'];
    }
    $entities = array();
    foreach ($all_ids as $target_type => $ids) {
      $entities[$target_type] = $manager
        ->getStorage($target_type)
        ->loadMultiple($ids);
    }

    foreach ($default_value as $delta => $properties) {
      unset($default_value[$delta]['target_id']);
      $default_value[$delta]['target_uuid'] = $entities[$properties['target_type']][$properties['target_id']]->uuid();
    }
    return $default_value;
  }

}
