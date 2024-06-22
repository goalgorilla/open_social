<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldType;

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
    // If at least one of 3 subfields is not empty, field is not empty.
    // Subfields are:
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
    $settings = $field_definition->getSettings();

    $properties['external_id'] = DataDefinition::create('string')
      ->setLabel(t('External ID'))
      ->setRequired(TRUE);
    $properties['external_owner_target_type'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(t('Target Entity Type'))
      ->setRequired(TRUE);
    // @todo See if we can improve this subfield to use
    //   DataReferenceDefinition or DataDynamicReferenceDefinition.
    $properties['external_owner_id'] = DataDefinition::create('integer')
      ->setLabel(t('External Owner'))
      ->setRequired(TRUE);

//    $properties['external_owner_id'] = DataReferenceDefinition::create('entity')
//      ->setLabel(t('External Owner'))
//      ->setRequired(TRUE)
//      ->setTargetDefinition(EntityDataDefinition::create($settings['target_types']))
//      // We can add a constraint for the target entity type. The list of
//      // referenceable bundles is a field setting, so the corresponding
//      // constraint is added dynamically in ::getConstraints().
//      ->addConstraint('EntityType', $settings['target_types']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsSummary(FieldStorageDefinitionInterface $storage_definition): array {
    $summary = parent::storageSettingsSummary($storage_definition);
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
      $this->set('external_id', $values['external_id']);
      $this->set('external_owner_target_type', $values['external_owner_target_type']);
      $this->set('external_owner_id', $values['external_owner_id']);
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
      '#description' => $this->t('Entity type of item to referenced (Example: consumer)'),
      '#default_value' => $this->getSetting('target_types'),
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#disabled' => $has_data,
      '#size' => 10,
    ];

    // Only allow the field to target entity types that have an ID key. This
    // is enforced in ::propertyDefinitions().
    $entity_type_manager = \Drupal::entityTypeManager();
    $filter = function (string $entity_type_id) use ($entity_type_manager): bool {
      return $entity_type_manager->getDefinition($entity_type_id)
        ->hasKey('id');
    };
    $options = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);
    foreach ($options as $group_name => $group) {
      $element['target_types']['#options'][$group_name] = array_filter($group, $filter, ARRAY_FILTER_USE_KEY);
    }
    return $element;
  }

}
