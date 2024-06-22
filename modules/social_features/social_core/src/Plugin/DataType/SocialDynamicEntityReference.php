<?php

namespace Drupal\social_core\Plugin\DataType;

use Drupal\dynamic_entity_reference\Plugin\DataType\DynamicEntityReference;

/**
 * Defines a 'social_dynamic_entity_reference' data type.
 *
 * Note: This was inspired by `dynamic_entity_reference` and we are using this
 * approach to not have dependency on dynamic_entity_reference in social_core.
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
 * @property \Drupal\social_core\Plugin\Field\FieldType\ExternalIdentifierItem parent
 * @property \Drupal\Core\Entity\Plugin\DataType\EntityAdapter target
 *
 * @DataType(
 *   id = "social_dynamic_entity_reference",
 *   label = @Translation("Social Dynamic entity reference"),
 *   definition_class = "\Drupal\dynamic_reference\DataDynamicReferenceDefinition"
 * )
 */
class SocialDynamicEntityReference extends DynamicEntityReference {

}
