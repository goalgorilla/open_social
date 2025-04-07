<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'social_external_identifier_default_widget' field widget.
 *
 * @FieldWidget(
 *   id = "social_external_identifier_default_widget",
 *   label = @Translation("External Identifier Default"),
 *   field_types = {"social_external_identifier"},
 * )
 */
final class ExternalIdentifierDefaultWidget extends WidgetBase {

  /**
   * Constructs a new ExternalIdentifierDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['external_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('External ID'),
      '#default_value' => $items[$delta]->external_id ?? NULL,
      '#required' => FALSE,
    ];

    $element['external_owner_target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('External Owner Target Type'),
      '#options' => $this->getAllowedExternalOwnerTargetTypes(),
      '#empty_value' => '',
      '#default_value' => $items[$delta]->external_owner_target_type ?? NULL,
      '#required' => FALSE,
    ];

    // @todo Ideally use entity_autocomplete, or something like provided by
    //   dynamic_entity_reference. ATM this values will be mostly provided
    //   programmatically, this is why UI/UX is not polished yet. Also
    //   beside consumers entities, there are no other entity types to select
    //   from, but we need to build it in a way that we can support more than
    //   one entity type.
    $element['external_owner_id'] = [
      '#type' => 'number',
      '#title' => $this->t('External Owner'),
      '#default_value' => $items[$delta]->external_owner_id ?? NULL,
      '#min' => 1,
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * Returns list of allowed External Owner Entity Types.
   *
   * @return array
   *   Returns an array of allowed target types, where key is target type
   *   machine name and value is label.
   */
  protected function getAllowedExternalOwnerTargetTypes(): array {
    $allowed_target_types = [];

    $field_storage_definition = $this->fieldDefinition->getFieldStorageDefinition();
    $storage_settings = $field_storage_definition->getSettings();
    $target_types = $storage_settings['target_types'] ?? [];
    foreach ($target_types as $target_type) {
      $target_type_info = $this->entityTypeManager->getDefinition($target_type);
      if (!empty($target_type_info)) {
        $allowed_target_types[$target_type] = $target_type_info->getLabel();
      }
    }

    return $allowed_target_types;
  }

}
