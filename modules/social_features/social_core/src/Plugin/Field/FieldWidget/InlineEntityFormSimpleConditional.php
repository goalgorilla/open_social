<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\inline_entity_form\Element\InlineEntityForm;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormSimple;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates an inline entity only when configured fields are completed.
 */
#[FieldWidget(
  id: 'social_inline_entity_form_simple_conditional',
  label: new TranslatableMarkup('Inline entity form - Simple (conditional)'),
  field_types: ['entity_reference', 'entity_reference_revisions'],
  multiple_values: FALSE,
)]
final class InlineEntityFormSimpleConditional extends InlineEntityFormSimple {

  /**
   * Constructs a new conditional inline entity form widget.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $entity_type_bundle_info,
      $entity_type_manager,
      $entity_display_repository,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'] ?? [],
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return parent::defaultSettings() + [
      'required_fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);
    $options = $this->getAvailableFieldOptions();

    $element['required_fields'] = [
      '#type' => 'select',
      '#title' => $this->t('Fields required to keep the inline entity'),
      '#description' => $this->t('If any selected field is empty, the referenced entity will not be created. Existing referenced entities will be removed and deleted when any selected field is cleared.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('required_fields'),
      '#multiple' => TRUE,
      '#size' => min(max(count($options), 1), 8),
      '#required' => TRUE,
    ];

    if ($options === []) {
      $element['required_fields']['#disabled'] = TRUE;
      $element['required_fields']['#description'] = $this->t('No editable fields are available for the configured inline entity form.');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $options = $this->getAvailableFieldOptions();
    $required_fields = $this->getRequiredFields();

    if ($required_fields === []) {
      $summary[] = $this->t('No conditional fields selected.');
      return $summary;
    }

    $labels = [];
    foreach ($required_fields as $field_name) {
      $labels[] = $options[$field_name] ?? $field_name;
    }

    $summary[] = $this->t('Entity kept only when completed: @fields', [
      '@fields' => implode(', ', $labels),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $item = $items->get($delta);
    $item_value = $item?->getValue();

    if (
      $item !== NULL
      && is_array($item_value)
      && !empty($item_value['target_id'])
      && $items->getEntity()->get($items->getName())->referencedEntities() === []
    ) {
      // Stale references should behave like an empty widget so editors can
      // create a replacement instead of getting stuck behind an IEF warning.
      $items->setValue([]);
      // Ensure delta 0 exists so the parent widget can render an add form.
      $items->appendItem();
    }

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (!isset($element['inline_entity_form']) || $this->getRequiredFields() === []) {
      return $element;
    }

    $element['inline_entity_form']['#ief_required_fields'] = $this->getRequiredFields();
    $element['inline_entity_form']['#after_build'][] = [self::class, 'removeRequiredValidation'];
    $element['inline_entity_form']['#element_validate'] = [[self::class, 'validateConditionalEntityForm']];

    return $element;
  }

  /**
   * Removes Form API required validation from conditional fields.
   *
   * @param array $element
   *   The inline entity form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The processed form element.
   */
  public static function removeRequiredValidation(array $element, FormStateInterface $form_state): array {
    $required_fields = array_filter($element['#ief_required_fields'] ?? []);
    foreach ($required_fields as $field_name) {
      if (!isset($element[$field_name])) {
        continue;
      }

      self::unsetRequiredRecursively($element[$field_name]);
    }

    return $element;
  }

  /**
   * Skips inline entity validation when the trigger fields are empty.
   *
   * @param array $entity_form
   *   The inline entity form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateConditionalEntityForm(array &$entity_form, FormStateInterface $form_state): void {
    if (!self::hasSubmittedRequiredFieldValues($entity_form, $form_state)) {
      return;
    }

    InlineEntityForm::validateEntityForm($entity_form, $form_state);
  }

  /**
   * Removes required flags from a field subtree.
   *
   * @param array $element
   *   The form element to process.
   */
  private static function unsetRequiredRecursively(array &$element): void {
    if (isset($element['#required'])) {
      $element['#required'] = FALSE;
    }

    foreach ($element as $key => &$child) {
      if (!is_array($child) || str_starts_with((string) $key, '#')) {
        continue;
      }

      self::unsetRequiredRecursively($child);
    }
  }

  /**
   * Determines whether the inline entity should be retained.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The inline entity to evaluate.
   *
   * @return bool
   *   TRUE when all required fields have values, FALSE otherwise.
   */
  private function shouldPersistEntity(EntityInterface $entity): bool {
    if (!$entity instanceof FieldableEntityInterface) {
      return FALSE;
    }

    foreach ($this->getRequiredFields() as $field_name) {
      if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks whether the submitted trigger fields contain user input.
   *
   * @param array $entity_form
   *   The inline entity form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if all required fields have submitted values, FALSE otherwise.
   */
  private static function hasSubmittedRequiredFieldValues(array $entity_form, FormStateInterface $form_state): bool {
    $required_fields = array_filter($entity_form['#ief_required_fields'] ?? []);
    if ($required_fields === []) {
      return TRUE;
    }

    $user_input = $form_state->getUserInput();
    foreach ($required_fields as $field_name) {
      $value = NestedArray::getValue($user_input, array_merge($entity_form['#parents'], [$field_name]));
      if (self::isSubmittedValueEmpty($value)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Deletes existing discarded entities once per request.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities to delete.
   */
  private static function deleteEntitiesImmediately(FormStateInterface $form_state, array $entities): void {
    if ($entities === []) {
      return;
    }

    $deleted_entities = $form_state->get('social_core_conditional_inline_entity_deleted') ?? [];
    foreach ($entities as $entity) {
      if ($entity->isNew()) {
        continue;
      }

      $entity_type_id = $entity->getEntityTypeId();
      $entity_id = $entity->id();
      if (!empty($deleted_entities[$entity_type_id][$entity_id])) {
        continue;
      }

      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
      $loaded_entity = $storage->load($entity_id);
      $loaded_entity?->delete();

      $deleted_entities[$entity_type_id][$entity_id] = TRUE;
    }

    $form_state->set('social_core_conditional_inline_entity_deleted', $deleted_entities);
  }

  /**
   * Returns the selected required fields without unchecked checkbox values.
   *
   * @return string[]
   *   The configured field machine names.
   */
  private function getRequiredFields(): array {
    return array_values(array_filter($this->getSetting('required_fields')));
  }

  /**
   * Returns editable fields from the inline entity form display.
   *
   * @return array<string, string>
   *   An array of field labels keyed by field machine name.
   */
  private function getAvailableFieldOptions(): array {
    $bundle = $this->getBundle();
    if ($bundle === NULL) {
      return [];
    }

    $target_type = $this->getFieldSetting('target_type');
    $form_mode = $this->getSetting('form_mode');
    $display_storage = $this->entityTypeManager->getStorage('entity_form_display');
    $display = $display_storage->load($target_type . '.' . $bundle . '.' . $form_mode);
    if ($display === NULL && $form_mode !== 'default') {
      $display = $display_storage->load($target_type . '.' . $bundle . '.default');
    }

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($target_type, $bundle);
    $options = [];

    foreach (($display?->getComponents() ?? []) as $field_name => $component) {
      if (($component['region'] ?? 'content') === 'hidden' || !isset($field_definitions[$field_name])) {
        continue;
      }

      $options[$field_name] = sprintf('%s (%s)', $field_definitions[$field_name]->getLabel(), $field_name);
    }

    return $options;
  }

  /**
   * Determines whether a submitted field value is effectively empty.
   *
   * @param mixed $value
   *   The submitted value to inspect.
   *
   * @return bool
   *   TRUE when the value is NULL, an empty string, or an array of empties.
   */
  private static function isSubmittedValueEmpty(mixed $value): bool {
    if (is_array($value)) {
      foreach ($value as $key => $child_value) {
        if (self::isIgnorableSubmittedValueKey((string) $key)) {
          continue;
        }

        if (!self::isSubmittedValueEmpty($child_value)) {
          return FALSE;
        }
      }

      return TRUE;
    }

    if ($value === NULL) {
      return TRUE;
    }

    return trim((string) $value) === '';
  }

  /**
   * Filters out Form API metadata in submitted multi-value field arrays.
   *
   * Keys such as "_weight" are always present for cardinality -1 fields and
   * must not count as actual user input when deciding whether an inline entity
   * should be created.
   */
  private static function isIgnorableSubmittedValueKey(string $key): bool {
    return str_starts_with($key, '_') || str_ends_with($key, '_button');
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $kept_values = [];
    $discarded_entities = [];

    foreach ($values as $value) {
      $entity = $value['entity'] ?? NULL;
      if (!$entity instanceof EntityInterface) {
        continue;
      }

      if ($this->shouldPersistEntity($entity)) {
        $kept_values[] = $value;
        continue;
      }

      if (!$entity->isNew()) {
        $discarded_entities[] = $entity;
      }
    }

    self::deleteEntitiesImmediately($form_state, $discarded_entities);

    return $kept_values;
  }

}
