<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Defines the 'social_external_identifier' field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
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
    // Business rules:
    // - Field is considered empty if all subfield values are empty.
    // - Field is considered invalid (by constraints) if subfield values are
    // just partly provided (except if all subfield values are empty).
    // - Field is considered valid if all subfield values are provided (but it
    // is also valid if none of the subfield value is provided)
    //
    // Subfields:
    // - external_id,
    // - external_owner_target_type
    // - external_owner_id
    //
    // Note, even that external_owner_id is int, we are checking is as string
    // because of some Drupal bugs that int values ane not matching type hints.
    // See: https://www.drupal.org/project/drupal/issues/3441689
    // See: https://www.drupal.org/project/drupal/issues/3224376
    //
    // Also note that the ExternalIdentifierEmptySubfieldsConstraintValidator
    // prevents the field from being saved if all subfields do not have set
    // values.
    foreach ($this->getValue() as $value) {
      if ($value !== '' && $value !== NULL) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['external_id'] = DataDefinition::create('string')
      ->setLabel(t('External ID'))
      ->setRequired(TRUE);
    $properties['external_owner_target_type'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(t('Target Entity Type'))
      ->setRequired(TRUE);
    $properties['external_owner_id'] = DataDefinition::create('integer')
      ->setLabel(t('External Owner'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsSummary(FieldStorageDefinitionInterface $storage_definition): array {
    $summary = [];
    $target_types = $storage_definition->getSetting('target_types');
    if (!empty($target_types)) {
      $entity_type_labels = [];
      foreach ($target_types as $target_type) {
        $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);
        if (!empty($target_type_info)) {
          $entity_type_labels[] = $target_type_info->getLabel();
        }
      }
      $summary[] = new TranslatableMarkup('Reference type: @entity_type_labels', [
        '@entity_type_labels' => implode(', ', $entity_type_labels),
      ]);

    }

    return $summary;
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
      $this->set('external_id', $values['external_id'] ?? NULL);
      $this->set('external_owner_target_type', $values['external_owner_target_type'] ?? NULL);
      $this->set('external_owner_id', $values['external_owner_id'] ?? NULL);
    }
    else {
      parent::setValue($values, $notify);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_types' => [],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element['target_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Allowed external owner target types'),
      '#description' => $this->t('Entity type of item to reference (Example: consumer)'),
      '#default_value' => $this->getSetting('target_types'),
      '#multiple' => TRUE,
      // For field to work properly, at least one target type must be available,
      // as this is hard requirement, If requirement can not be fulfilled, field
      // will return an error if non-empty value will try to be applied.
      // Despite all those facts, we still keep this option as optional, because
      // by default, no target types are predefined.
      '#required' => FALSE,
      '#disabled' => $has_data,
      '#size' => 10,
    ];

    // Only allow the field to target entity types that have an ID key. This
    // is enforced in ::propertyDefinitions().
    $entity_type_manager = \Drupal::entityTypeManager();
    $filter = function (string $entity_type_id) use ($entity_type_manager): bool {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      assert($entity_type instanceof EntityTypeInterface, "EntityTypeManager::getDefinition should throw an exception if the entity type does not exist rather than returning NULL");
      return $entity_type->hasKey('id');
    };
    $options = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);
    foreach ($options as $group_name => $group) {
      $element['target_types']['#options'][$group_name] = array_filter($group, $filter, ARRAY_FILTER_USE_KEY);
    }
    return $element;
  }

  /**
   * Loads the external owner entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns external owner entity if exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getExternalOwnerEntity(): ?EntityInterface {
    $entity_type = $this->get('external_owner_target_type')->getValue();
    $entity_id = $this->get('external_owner_id')->getValue();

    if ($entity_type && $entity_id) {
      return \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    }
    return NULL;
  }

}
