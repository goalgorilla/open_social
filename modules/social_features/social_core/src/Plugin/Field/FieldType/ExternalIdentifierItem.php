<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Defines the 'social_external_identifier' field type.
 *
 * @FieldType(
 *   id = "social_external_identifier",
 *   label = @Translation("External Identifier"),
 *   description = @Translation("Define external Identifier and external Owner/Consumer."),
 *   default_widget = "social_external_identifier_default_widget",
 *   default_formatter = "social_external_identifier_default_formatter",
 *   constraints = {
 *     "ExternalIdentifierEmptySubfieldsConstraint" = {},
 *     "ExternalIdentifierExternalOwnerTargetTypeConstraint" = {},
 *     "ExternalIdentifierExternalOwnerIdConstraint" = {},
 *     "ComplexData" = {
 *       "external_id" = {
 *         "Length" = {"max" = 225}
 *        }
 *      }
 *   }
 * )
 */
final class ExternalIdentifierItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    foreach ($this->getValue() as $value) {
      if ($value !== '') {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    /** @var \Drupal\social_core\ExternalIdentifierManager\ExternalIdentifierManager $externalIdentifierManager */
    $externalIdentifierManager = \Drupal::service('social_core.external_identifier_manager');

    $properties['external_id'] = DataDefinition::create('string')
      ->setLabel($externalIdentifierManager->getSubfieldLabel('external_id'))
      ->setRequired(TRUE);
    $properties['external_owner_target_type'] = DataReferenceTargetDefinition::create('string')
      ->setLabel($externalIdentifierManager->getSubfieldLabel('external_owner_target_type'))
      ->setRequired(TRUE);
    $properties['external_owner_id'] = DataDefinition::create('string')
      ->setLabel($externalIdentifierManager->getSubfieldLabel('external_owner_id'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'external_id' => [
          'type' => 'varchar',
          'description' => 'External ID',
          'length' => 255,
          'not null' => TRUE,
        ],
        'external_owner_target_type' => [
          'type' => 'varchar',
          'description' => 'External Owner Target Type',
          'length' => 255,
          'not null' => TRUE,
        ],
        'external_owner_id' => [
          'type' => 'int',
          'description' => 'External Owner ID',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
      'indexes' => [
        'external_id' => ['external_id'],
        'external_owner_target_type' => ['external_owner_target_type'],
        'external_owner_id' => ['external_owner_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE): void {
    if (is_array($values)) {
      $this->set('external_id', $values['external_id']);
      $this->set('external_owner_target_type', $values['external_owner_target_type']);
      $this->set('external_owner_id', $values['external_owner_id']);
    }
    else {
      parent::setValue($values, $notify);
    }
  }

}
