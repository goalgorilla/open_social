<?php

namespace Drupal\dynamic_entity_reference\Plugin\DataType;

use Drupal\Core\Entity\Plugin\DataType\EntityReference;

/**
 * Defines a 'dynamic_entity_reference' data type.
 *
 * This serves as 'entity' property of dynamic entity reference field items and
 * gets its value set from the parent, i.e. DynamicEntityReferenceItem.
 *
 * The plain value of this reference is the entity object, i.e. an instance of
 * \Drupal\Core\Entity\EntityInterface. For setting the value the entity object
 * or the entity ID may be passed.
 *
 * Note that the definition of the referenced entity's type is required. A
 * reference defining the type of the referenced entity can be created as
 * following:
 * @code
 * $definition = \Drupal\Core\Entity\EntityDefinition::create($entity_type);
 * \Drupal\Core\TypedData\DataReferenceDefinition::create('entity')
 *   ->setTargetDefinition($definition);
 * @endcode
 *
 * @property int id
 * @property \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem parent
 * @property \Drupal\Core\Entity\Plugin\DataType\EntityAdapter target
 *
 * @DataType(
 *   id = "dynamic_entity_reference",
 *   label = @Translation("Dynamic entity reference"),
 *   definition_class = "\Drupal\dynamic_reference\DataDynamicReferenceDefinition"
 * )
 */
class DynamicEntityReference extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    // If we have a valid reference, return the entity's TypedData adapter.
    if (!isset($this->target) && isset($this->id)) {
      // For \Drupal\Core\Entity\Plugin\DataType\EntityReference
      // $this->getTargetDefinition()->getEntityTypeId() will always be set
      // because $target_type exists in EntityReferenceItem storage settings but
      // for
      // \Drupal\dynamic_entity_reference\Plugin\DataType\DynamicEntityReference
      // $target_type will be NULL because it doesn't exist in
      // DynamicEntityReferenceItem storage settings it is selected dynamically
      // so it exists in DynamicEntityReferenceItem::values['target_type'].
      $target_type = $this->parent->getValue()['target_type'];
      $entity = entity_load($target_type, $this->id);
      $this->target = isset($entity) ? $entity->getTypedData() : NULL;
    }
    return $this->target;
  }

}
