<?php

namespace Drupal\social_event\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\meeting_api\MeetingEntityInterface;
use Drupal\social_event\Form\EventSettingsForm;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'event_meeting' widget.
 */
#[FieldWidget(
  id: 'event_meeting',
  label: new TranslatableMarkup('Event Meeting'),
  description: new TranslatableMarkup('Display meeting entity in event form mode.'),
  field_types: ['entity_reference'],
)]
final class EventMeetingWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The default number of attendees.
   */
  public const int DEFAULT_ATTENDEES = 2;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected ConfigFactoryInterface $configFactory,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('logger.factory')->get('social_event'),
    );
  }

  /**
   * Builds the meeting form for the provided entity.
   *
   * @param string $selected_bundle
   *   The selected bundle of the meeting entity being processed.
   * @param array $wrapper
   *   A reference to the wrapper array where the meeting form will be appended.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item list of the meeting entity.
   * @param int|string $delta
   *   The delta index of the current item in the field.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state to pass to the meeting form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildMeetingForm(string $selected_bundle, array &$wrapper, FieldItemListInterface $items, int|string $delta, FormStateInterface $form_state): void {
    $field_name = $items->getName();

    $meeting_entity = $this->getMeetingEntity($selected_bundle,$wrapper, $items, $delta);

    // Get the form display for the 'event' form mode.
    $form_display = $this->entityDisplayRepository
      ->getFormDisplay('meeting_api_meeting', $meeting_entity->bundle(), 'event');

    // Build the entity form.
    $meeting_form = ['#parents' => [$field_name, $delta, 'meeting_form_wrapper']];
    $form_display->buildForm($meeting_entity, $meeting_form, $form_state);

    // Add validation to make sure the all values for meeting were provided
    // correctly.
    $wrapper['#element_validate'][] = [$this, 'validateMeetingValues'];

    // Hide revision for the moment.
    if (isset($meeting_form['revision_log'])) {
      $meeting_form['revision_log']['#access'] = FALSE;
    }

    // Make sure the max attendees value is synchronized with global settings.
    if (isset($meeting_form['max_attendees']['widget'][0]['value'])) {
      $event_settings = $this->configFactory->get(EventSettingsForm::SETTINGS);
      $max_attendees = $event_settings->get('online_meeting.max_attendees') ?: 200;

      $meeting_form['max_attendees']['widget'][0]['value']['#max'] = $max_attendees;
      $meeting_form['max_attendees']['widget'][0]['value']['#min'] = self::DEFAULT_ATTENDEES;
    }

    // Add the meeting form to the wrapper.
    $wrapper[$selected_bundle]['meeting_form'] = $meeting_form;
  }

  /**
   * Retrieves or creates a meeting entity based on the selected bundle.
   *
   * @param string $selected_bundle
   *   The bundle of the meeting entity to retrieve or create.
   * @param array &$meeting_form_wrapper
   *   The form wrapper holding temporary data for meeting entities.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The items array which contains the entity data.
   * @param int|string $delta
   *   The index of the item to process.
   *
   * @return \Drupal\meeting_api\MeetingEntityInterface
   *   The retrieved or newly created meeting entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getMeetingEntity(string $selected_bundle, array &$meeting_form_wrapper, FieldItemListInterface $items, int|string $delta): MeetingEntityInterface {
    // If no existing entity, check if we have a temporary entity stored
    // for this bundle.
    if (isset($meeting_form_wrapper[$selected_bundle]['#meeting_entity'])) {
      $meeting_entity = $meeting_form_wrapper[$selected_bundle]['#meeting_entity'];
    }
    else {
      // First, try to load an existing entity if we have a target_id.
      if (!$items[$delta]->isEmpty() && $items[$delta]->target_id) {
        $existing_entity = $this->entityTypeManager
          ->getStorage('meeting_api_meeting')
          ->load($items[$delta]->target_id);

        // Only use the existing entity if it matches the selected bundle.
        if ($existing_entity && $existing_entity->bundle() === $selected_bundle) {
          $meeting_entity = $existing_entity;

          // Store the entity by bundle for reuse during AJAX calls.
          $meeting_form_wrapper[$selected_bundle]['#meeting_entity'] = $meeting_entity;
        }
      }
    }

    // If still no entity, create a new one and store it by bundle.
    if (empty($meeting_entity)) {
      $meeting_entity = $this->entityTypeManager
        ->getStorage('meeting_api_meeting')
        ->create([
            'bundle' => $selected_bundle,
          ] + $this->getMeetingDefaultValues());

      // Store the entity by bundle for reuse during AJAX calls.
      $meeting_form_wrapper[$selected_bundle]['#meeting_entity'] = $meeting_entity;
    }

    return $meeting_entity;
  }

  /**
   * Retrieves the default values for a meeting.
   *
   * @return array
   *   An associative array containing default values for a meeting, such as:
   *   - 'max_attendees': The default maximum number of attendees.
   *   - 'title': The default title for the meeting.
   */
  protected function getMeetingDefaultValues(): array {
    return [
      'max_attendees' => self::DEFAULT_ATTENDEES,
      'title' => $this->t('Event Meeting'),
    ];
  }

  /**
   * Updates the meeting entity with form values.
   *
   * This method assigns values collected from the form state and additional
   * computed values related to the event's start and end dates to the
   * meeting entity fields.
   * If any field values are updated, it returns TRUE.
   *
   * @param \Drupal\meeting_api\MeetingEntityInterface $meeting
   *   The meeting entity to be updated.
   * @param array $values
   *   An associative array of field values to update the meeting entity with.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object containing submitted form values.
   *
   * @return bool
   *   TRUE if any field values in the meeting entity were changed;
   *   otherwise, FALSE.
   */
  private function putFormValuesToMeeting(MeetingEntityInterface $meeting, array $values, FormStateInterface $form_state): bool {
    $event_start = $form_state->getValue(['field_event_date', 0, 'value']);
    $event_end = $form_state->getValue(['field_event_date_end', 0, 'value']);
    if (empty($event_start) || empty($event_end)) {
      return FALSE;
    }

    assert($event_start instanceof DrupalDateTime);
    assert($event_end instanceof DrupalDateTime);

    $values_from_event = [
      'label' => $this->t('Meeting for @title event', [
        '@title' => $form_state->getValue(['title', 0, 'value']) ?: 'Meeting',
      ]),
      'datetime' => [
        'value' => $event_start->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'end_value' => $event_end->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'timezone' => date_default_timezone_get(),
      ]
    ];

    $is_changed = FALSE;
    foreach ($values + $values_from_event as $field_name => $field_value) {
      if ($meeting->hasField($field_name)) {
        $meeting->set($field_name, $field_value);
        $is_changed = TRUE;
      }
    }

    return $is_changed;
  }

  /**
   * Get available meeting bundles for the field.
   *
   * @return array
   *   Array of bundle options keyed by bundle ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAvailableMeetingBundles(): array {
    $handler_settings = $this->fieldDefinition->getSetting('handler_settings');
    $target_bundles = $handler_settings['target_bundles'] ?? [];

    if (!$target_bundles) {
      return [];
    }

    // Get bundle labels from the configured target bundles.
    $meeting_type_storage = $this->entityTypeManager
      ->getStorage('meeting_api_meeting_type');

    foreach ($target_bundles as $bundle_id) {
      $bundle_entity = $meeting_type_storage->load($bundle_id);
      if ($bundle_entity) {
        $options[$bundle_id] = $bundle_entity->label();
      }
    }

    return $options ?? [];
  }

  /**
   * Get the default bundle for creating new meeting entities.
   *
   * @return string|null
   *   The default bundle name or NULL if none is found.
   */
  protected function getDefaultBundle(): ?string {
    // Verify that the configured default type is valid and available.
    $handler_settings = $this->fieldDefinition->getSetting('handler_settings');
    $target_bundles = $handler_settings['target_bundles'] ?? [];

    // Get default meeting type from configuration.
    $event_settings = $this->configFactory->get(EventSettingsForm::SETTINGS);
    $default_meeting_type = $event_settings->get('online_meeting.default_meeting_type');

    if (in_array($default_meeting_type, $target_bundles)) {
      return $default_meeting_type;
    }

    // Fallback to field configuration or the first available bundle.
    // If target bundles are configured, use the first one.
    if (!empty($target_bundles)) {
      return reset($target_bundles);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get available meeting bundles.
    $meeting_bundles = $this->getAvailableMeetingBundles();

    if (empty($meeting_bundles)) {
      // No valid bundles found - return an empty element.
      $element += [
        '#type' => 'container',
        '#attributes' => ['class' => ['event-meeting-widget']],
        '#markup' => '<p>' . $this->t('No meeting bundles are available for this field.') . '</p>',
      ];

      return $element;
    }

    $field_name = $items->getName();
    $wrapper_id = "meeting-type-wrapper-{$field_name}-{$delta}";

    // Get a selected bundle from form state or default.
    $selected_bundle = $form_state->getValue([$field_name, $delta, 'meeting_type']);
    if (!$selected_bundle) {
      // Check if there's an existing entity.
      if (!$items[$delta]->isEmpty() && $items[$delta]->target_id) {
        $existing_entity = $this->entityTypeManager
          ->getStorage('meeting_api_meeting')
          ->load($items[$delta]->target_id);
        if ($existing_entity) {
          $selected_bundle = $existing_entity->bundle();
        }
      }
      
      // Fallback to default bundle.
      if (!$selected_bundle) {
        $selected_bundle = $this->getDefaultBundle();
      }
    }

    $element += [
      '#type' => 'container',
      '#attributes' => ['class' => ['event-meeting-widget']],
      '#attached' => [
        'library' => ['social_event/event_meeting_widget'],
      ],
      '#tree' => TRUE,
    ];

    // Add a checkbox to indicate if the event is online.
    $element['is_online'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Online'),
      '#default_value' => !$items[$delta]->isEmpty(),
    ];

    // Add a meeting type selector.
    $element['meeting_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Meeting Type'),
      '#options' => $meeting_bundles,
      '#default_value' => $selected_bundle,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'meetingTypeAjaxCallback'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
      '#attributes' => [
        'class' => ['hidden', 'meeting-type-selector'],
      ],
    ];

    // Add the hidden target ID field with the existing meeting.
    $element['target_id'] = [
      '#type' => 'hidden',
      '#value' => $items[$delta]->target_id ?? NULL,
      '#parents' => [$field_name, $delta, 'target_id'],
    ];

    // Container for the meeting form that will be updated via AJAX.
    $element['meeting_form_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $wrapper_id],
      '#states' => [
        'visible' => [
          ':input[name="' . $field_name . "[$delta]" . '[is_online]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $meeting_form_element =& $element['meeting_form_wrapper'];

    foreach ($meeting_bundles as $meeting_bundle => $meeting_bundle_label) {
      $meeting_form_element[$meeting_bundle] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['meeting-type-wrapper'],
        ],
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'data-meeting-type' => $meeting_bundle,
            'data-is-selected' => $meeting_bundle === $selected_bundle ? 'true' : 'false',
            'class' => ['meeting-type-label'],
          ],
          '#value' => $meeting_bundle_label,
        ]
      ];
    }

    // Only build the meeting form if a bundle is selected.
    if ($selected_bundle) {
      $this->buildMeetingForm($selected_bundle, $meeting_form_element, $items, $delta, $form_state);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    foreach ($values as $delta => $value) {
      $target_ids[$delta] = ['target_id' => $value['target_id']];
    }

    return $target_ids ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#type'])) {
      return;
    }

    // This method should be triggered only when the user submits the
    // event form.
    if ($triggering_element['#type'] !== 'submit') {
      return;
    }

    $field_name = $items->getName();
    $path = array_merge($form['#parents'], [$field_name]);
    $widget_state = $form_state->getValue($path);

    if (!$widget_state) {
      return;
    }

    // @todo The methods triggered a few times after event submit,
    //   check the reason.
    if (empty($widget_state[0]['meeting_form_wrapper'])) {
      return;
    }

    foreach ($widget_state as $delta => &$values) {
      if (!$values['is_online']) {
        if (NULL !== $values['target_id']) {
          $previous_meeting = $this->entityTypeManager
            ->getStorage('meeting_api_meeting')
            ->load($values['target_id']);

          if ($previous_meeting instanceof MeetingEntityInterface) {
            $previous_meeting->set('status', FALSE);
            $previous_meeting->save();
          }
        }

        $values['target_id'] = NULL;
        continue;
      }

      $meeting = NestedArray::getValue($form[$field_name]['widget'][$delta], ['meeting_form_wrapper', $values['meeting_type'], '#meeting_entity']);
      if (!$meeting instanceof MeetingEntityInterface) {
        continue;
      }

      $default_value = $values['target_id'] ?? NULL;

      // Extract values from the meeting form wrapper.
      $meeting_form_values = $values['meeting_form_wrapper'] ?? [];

      if ($meeting->isNew() || $meeting->id() != $default_value) {
        $this->putFormValuesToMeeting($meeting, $meeting_form_values, $form_state);
        // We always save a new meeting.
        $meeting->save();

        // Unpublish previous meeting.
        if (NULL !== $default_value) {
          $previous_meeting = $this->entityTypeManager
            ->getStorage('meeting_api_meeting')
            ->load($default_value);

          if ($previous_meeting instanceof MeetingEntityInterface) {
            $previous_meeting->set('status', FALSE);
            $previous_meeting->save();
          }
        }
      }
      else {
        // Save the meeting entity if there are changes, otherwise skip.
        if ($this->putFormValuesToMeeting($meeting, $meeting_form_values, $form_state)) {
          $meeting->save();
        }
      }

      NestedArray::setValue($form[$field_name]['widget'][$delta], ['meeting_form_wrapper', $values['meeting_type'], '#meeting_entity'], NULL);

      $values['target_id'] = $meeting->id();
    }

    // Set back the widget state.
    $form_state->setValue($path, $widget_state);

    // Let's parent method do the rest.
    parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * AJAX callback for meeting type selection.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element to be replaced.
   */
  public function meetingTypeAjaxCallback(array &$form, FormStateInterface $form_state): array {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $parents[] = 'meeting_form_wrapper';

    return NestedArray::getValue($form, $parents);
  }

  /**
   * Validates the meeting form wrapper.
   *
   * @param array $element
   *   The form element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $form
   *   The complete form structure.
   */
  public function validateMeetingValues(array $element, FormStateInterface $form_state, array $form): void {
    $parents = $element['#parents'];
    $field_name = $parents[0];
    $delta = $parents[1];

    // Validate only if the event is online.
    $is_online = $form_state->getValue([$field_name, $delta, 'is_online']);
    if (!$is_online) {
      return;
    }

    // Get the selected meeting type from the form state.
    $meeting_values = $form_state->getValue($parents);
    if (!$meeting_values) {
      return;
    }

    $meeting_type = $form_state->getValue([$field_name, $delta, 'meeting_type']);
    // Get the current meeting ID to exclude it from count (for edits).
    $current_meeting = NestedArray::getValue($form[$field_name]['widget'][$delta], ['meeting_form_wrapper', $meeting_type, '#meeting_entity']);
    if (!$current_meeting instanceof MeetingEntityInterface) {
      return;
    }

    // Use a cloned meeting for validation because the values state of the
    // meeting object should be untouched until the form is submitted.
    // On form submitting (technically, when ::extractFormValues() is called),
    // we want to know if the values have changed, and if so, we want to save
    // the meeting, otherwise won't touch it.
    $cloned_meeting = clone $current_meeting;

    $this->putFormValuesToMeeting($cloned_meeting, $meeting_values, $form_state);

    $violations = $cloned_meeting->validate();
    if ($violations->count() <= 0) {
      return;
    }

    foreach ($violations as $violation) {
      // Attach all errors to the "Online" checkbox.
      $form_state->setError($form[$field_name]['widget'][$delta]['is_online'], $violation->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    // This widget is very specific to an Event node type and
    // should not be used on other entity types.
    if (
      $field_definition->getTargetBundle() !== 'event' &&
      $field_definition->getTargetEntityTypeId() !== 'node'
    ) {
      return FALSE;
    }

    // Only apply to entity_reference fields.
    if ($field_definition->getType() !== 'entity_reference') {
      return FALSE;
    }

    // Only apply to fields that reference meeting_api_meeting entities.
    $target_type = $field_definition->getSetting('target_type');
    if ($target_type !== 'meeting_api_meeting') {
      return FALSE;
    }

    return TRUE;
  }

}
