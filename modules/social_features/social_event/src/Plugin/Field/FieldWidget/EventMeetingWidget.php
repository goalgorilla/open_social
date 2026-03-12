<?php

namespace Drupal\social_event\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\meeting_api\MeetingEntityInterface;
use Drupal\meeting_api_scheduler\Enum\PeriodScheduleCalendarView;
use Drupal\meeting_api_scheduler\Service\TimeConstraintManager;
use Drupal\meeting_api_scheduler\ValueObject\MeetingRequest;
use Drupal\social_event\Entity\Node\Event;
use Drupal\social_event\Form\EventSettingsForm;
use Drupal\social_event\Service\EventOnline;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'event_meeting' widget.
 */
#[FieldWidget(
  id: 'event_meeting',
  label: new TranslatableMarkup('Event Meeting'),
  description: new TranslatableMarkup('Display meeting entity in event form mode.'),
  field_types: ['entity_reference'],
  multiple_values: TRUE,
)]
final class EventMeetingWidget extends WidgetBase implements ContainerFactoryPluginInterface {

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
    protected EventOnline $eventOnline,
    protected ModuleHandlerInterface $moduleHandler,
    protected AccountProxyInterface $currentUser,
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
      $container->get(EventOnline::class),
      $container->get('module_handler'),
      $container->get('current_user'),
    );
  }

  /**
   * Get the meeting start and end dates from the parent event form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   *   The parent form state containing event date values.
   *
   * @return array
   *   An array with 'start_date' and 'end_date' keys containing
   *   DateTimeImmutable values, or NULL when unavailable.
   */
  protected function getMeetingDateTime(FormStateInterface $parent_form_state): array {
    $empty_value = [
      'start_date' => NULL,
      'end_date' => NULL,
    ];

    // Get the dates from the event entity form.
    $start_date = $parent_form_state->getValue(['field_event_date', 0, 'value']);
    $end_date = $parent_form_state->getValue(['field_event_date_end', 0, 'value']);

    if (!$start_date instanceof DrupalDateTime || !$end_date instanceof DrupalDateTime) {
      $form_object = $parent_form_state->getFormObject();
      if (!$form_object instanceof EntityFormInterface) {
        return $empty_value;
      }

      $event = $form_object->getEntity();
      // Try to get the dates from the event node.
      if (!$event instanceof Event) {
        return $empty_value;
      }

      // If the event is new, we just use "now" and "+ 12 hours".
      if ($event->isNew()) {
        $start_date = new DrupalDateTime('now');
        $end_date = new DrupalDateTime('+12 hours');
      }
      else {
        $start_date = $event->get('field_event_date')->date;
        $end_date = $event->get('field_event_date_end')->date;
      }
    }

    if (!$start_date instanceof DrupalDateTime || !$end_date instanceof DrupalDateTime) {
      // Not possible to detect start and end dates.
      return $empty_value;
    }

    // Compatibility with the "All day" event option. In event, "All day" means
    // both start and end dates have the same time.
    if ($start_date->getTimestamp() === $end_date->getTimestamp()) {
      $end_date->modify('23 hours 59 minutes 59 seconds');
    }

    // We need to make sure the user can see the calendar in
    // the preferred timezone.
    $timezone = $this->currentUser->getTimeZone() ?: date_default_timezone_get();
    try {
      $start_date->setTimezone(new \DateTimeZone($timezone));
      $end_date->setTimezone(new \DateTimeZone($timezone));
    }
    catch (\Exception $e) {
      return $empty_value;
    }

    return [
      'start_date' => \DateTimeImmutable::createFromFormat(
        format: \DateTimeInterface::ATOM,
        datetime: $start_date->format(\DateTimeInterface::ATOM)
      ),
      'end_date' => \DateTimeImmutable::createFromFormat(
        format: \DateTimeInterface::ATOM,
        datetime: $end_date->format(\DateTimeInterface::ATOM)
      ),
    ];
  }

  /**
   * Determine whether the meeting scheduler should be displayed.
   *
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   *   The parent form state containing event date values.
   * @param \Drupal\meeting_api\MeetingEntityInterface $meeting_entity
   *   The meeting entity used to derive scheduling constraints.
   *
   * @return bool
   *   TRUE when a schedule needs to be shown; otherwise, FALSE.
   */
  protected function displayScheduler(FormStateInterface $parent_form_state, MeetingEntityInterface $meeting_entity): bool {
    if (!$this->moduleHandler->moduleExists('meeting_api_scheduler')) {
      return FALSE;
    }

    $dates = $this->getMeetingDateTime($parent_form_state);
    $start_date = $dates['start_date'] ?? NULL;
    $end_date = $dates['end_date'] ?? NULL;
    // Both dates are required for the scheduler.
    if (!$start_date || !$end_date) {
      return FALSE;
    }

    // Prevent displaying the scheduler if the end date is greater than
    // the start date. Otherwise, we get a fatal error in TimeConstraintManager.
    $interval = $start_date->diff($end_date);
    if ($interval->invert === 1) {
      return FALSE;
    }

    $attendees = (int) $parent_form_state->getValue(['field_event_meeting', 'meeting_form', 'max_attendees', 0, 'value']);

    $meeting_request = new MeetingRequest(
      startTime: $start_date,
      endTime: $end_date,
      attendeesCount: $attendees ?: (int) $meeting_entity->get('max_attendees')->getString(),
      serverId: $meeting_entity->getServerId(),
      meetingId: !$meeting_entity->isNew() ? $meeting_entity->id() : NULL,
    );

    return !\Drupal::service(TimeConstraintManager::class)
      ->collect($meeting_request)
      ->isEmpty();
  }

  /**
   * Build the scheduler form element for the meeting.
   *
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   *   The parent form state containing event date values.
   * @param \Drupal\meeting_api\MeetingEntityInterface $meeting_entity
   *   The meeting entity used to build the schedule.
   *
   * @return array
   *   The scheduler form element render array, or an empty array when
   *   scheduling is not possible.
   */
  protected function buildScheduler(FormStateInterface $parent_form_state, MeetingEntityInterface $meeting_entity): array {
    $dates = $this->getMeetingDateTime($parent_form_state);
    $start_date = $dates['start_date'] ?? NULL;
    $end_date = $dates['end_date'] ?? NULL;

    // Both dates are required for the scheduler.
    if (!$start_date || !$end_date) {
      return [];
    }

    $attendees = (int) $parent_form_state->getValue(['field_event_meeting', 'meeting_form', 'max_attendees', 0, 'value']);

    // Add the scheduler field to the entity form.
    return [
      '#type' => 'meeting_api_period_schedule',
      '#meeting_request' => new MeetingRequest(
        startTime: $start_date,
        endTime: $end_date,
        attendeesCount: $attendees ?: (int) $meeting_entity->get('max_attendees')->getString(),
        serverId: $meeting_entity->getServerId(),
        meetingId: !$meeting_entity->isNew() ? $meeting_entity->id() : NULL,
      ),
      '#calendar_view' => PeriodScheduleCalendarView::Week,
      '#show_prev_next_controls' => FALSE,
      '#prefix' => '<div id="meeting-scheduler-wrapper">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => ['social_event/event_meeting_scheduler'],
      ],
    ];
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state to pass to the meeting form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function buildMeetingForm(string $selected_bundle, array &$wrapper, FieldItemListInterface $items, FormStateInterface $form_state): void {
    $field_name = $items->getName();

    $meeting_entity = $this->getMeetingEntity($selected_bundle, $wrapper, $items);

    // Get the form display for the 'event' form mode.
    $form_display = $this->entityDisplayRepository
      ->getFormDisplay('meeting_api_meeting', $meeting_entity->bundle(), 'event');

    // Build the entity form.
    $entity_form = ['#parents' => [$field_name, 'meeting_form']];
    $form_display->buildForm($meeting_entity, $entity_form, $form_state);

    // Add validation to make sure the all values for meeting were provided
    // correctly.
    $wrapper['#element_validate'][] = [$this, 'validateMeetingValues'];

    // Hide revision for the moment.
    if (isset($entity_form['revision_log'])) {
      $entity_form['revision_log']['#access'] = FALSE;
    }

    // Make sure the max attendees value is synchronized with global settings.
    if (isset($entity_form['max_attendees']['widget'][0]['value'])) {
      $event_settings = $this->configFactory->get(EventSettingsForm::SETTINGS);

      $max = $event_settings->get('online_meeting.max_attendees') ?: EventOnline::MAX_CONCURRENT_BBB_ATTENDEES;
      $min = $this->getMeetingDefaultValues()['max_attendees'];

      $entity_form['max_attendees']['widget'][0]['value']['#max'] = $max;
      $entity_form['max_attendees']['widget'][0]['value']['#min'] = $min;
      $entity_form['max_attendees']['widget'][0]['value']['#description'] = $this->t('The maximum allowed number of attendees. Allowed value is from <em>@min</em> to <em>@max</em>.', [
        '@min' => $min,
        '@max' => $max,
      ]);
    }

    // Add a scheduler if needed.
    if ($component = $form_display->getComponent('scheduler')) {
      if (
        $this->displayScheduler($form_state, $meeting_entity) &&
        ($scheduler_widget = $this->buildScheduler($form_state, $meeting_entity))
      ) {
        // Get the widget position from the form display configuration.
        $entity_form['scheduler'] = $scheduler_widget + ['#weight' => $component['weight'] ?? 0];
      }
      else {
        // We should return the wrapper without the scheduler, otherwise it
        // will not be possible to display it when the selected timeslot
        // will be unavailable.
        $entity_form['scheduler'] = [
          '#prefix' => '<div id="meeting-scheduler-wrapper">',
          '#suffix' => '</div>',
        ];

        CacheableMetadata::createFromRenderArray($entity_form)
          ->setCacheMaxAge(0)
          ->applyTo($entity_form);
      }
    }

    // Add the meeting form to the wrapper.
    $wrapper[$selected_bundle]['entity_form'] = $entity_form;
  }

  /**
   * Retrieves or creates a meeting entity based on the selected bundle.
   *
   * @param string $selected_bundle
   *   The bundle of the meeting entity to retrieve or create.
   * @param array &$meeting_form
   *   The form wrapper holding temporary data for meeting entities.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The items array which contains the entity data.
   *
   * @return \Drupal\meeting_api\MeetingEntityInterface
   *   The retrieved or newly created meeting entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getMeetingEntity(string $selected_bundle, array &$meeting_form, FieldItemListInterface $items): MeetingEntityInterface {
    // If no existing entity, check if we have a temporary entity stored
    // for this bundle.
    if (isset($meeting_form[$selected_bundle]['#meeting_entity'])) {
      $meeting_entity = $meeting_form[$selected_bundle]['#meeting_entity'];
    }
    else {
      // First, try to load an existing entity if we have a target_id.
      $field_item = $items->first();
      if ($field_item instanceof EntityReferenceItem && !$field_item->isEmpty() && $field_item->target_id) {
        $existing_entity = $this->entityTypeManager
          ->getStorage('meeting_api_meeting')
          ->load($field_item->target_id);

        // Only use the existing entity if it matches the selected bundle.
        if ($existing_entity && $existing_entity->bundle() === $selected_bundle) {
          $meeting_entity = $existing_entity;

          // Store the entity by bundle for reuse during AJAX calls.
          $meeting_form[$selected_bundle]['#meeting_entity'] = $meeting_entity;
        }
      }
    }

    // If still no entity, create a new one and store it by bundle.
    if (empty($meeting_entity)) {
      $meeting_entity = $this->entityTypeManager
        ->getStorage('meeting_api_meeting')
        ->create(['bundle' => $selected_bundle] + $this->getMeetingDefaultValues());

      // Store the entity by bundle for reuse during AJAX calls.
      $meeting_form[$selected_bundle]['#meeting_entity'] = $meeting_entity;
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
      'max_attendees' => EventOnline::DEFAULT_MEETING_ATTENDEES,
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
  protected function putFormValuesToMeeting(MeetingEntityInterface $meeting, array $values, FormStateInterface $form_state): bool {
    $event_start = $form_state->getValue(['field_event_date', 0, 'value']);
    $event_end = $form_state->getValue(['field_event_date_end', 0, 'value']);
    if (!$event_start instanceof DrupalDateTime || !$event_end instanceof DrupalDateTime) {
      return FALSE;
    }

    // Compatibility with the "All day" event option. In event, "All day" means
    // both start and end dates have the same time.
    if ($event_start->getTimestamp() === $event_end->getTimestamp()) {
      $event_end->modify('23 hours 59 minutes 59 seconds');
    }

    $values_from_event = [
      'label' => $this->t('Meeting for @title event', [
        '@title' => $form_state->getValue(['title', 0, 'value']) ?: 'Meeting',
      ]),
      'datetime' => [
        'value' => $event_start->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'end_value' => $event_end->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'timezone' => date_default_timezone_get(),
      ],
    ];

    $is_changed = FALSE;
    foreach ($values_from_event + $values as $field_name => $field_value) {
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
   * This method gets the meeting types configured for the field and
   * validates they are properly configured using the EventOnline service.
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

    // We need to make sure the meeting types are valid.
    return array_intersect_key(
      $this->eventOnline->getMeetingTypesAsOptionsList(),
      $target_bundles
    );
  }

  /**
   * Get the default bundle for creating new meeting entities.
   *
   * @return string|null
   *   The default bundle name or NULL if none is found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getDefaultBundle(): ?string {
    // Verify that the configured default type is valid and available.
    $meeting_types = array_keys($this->getAvailableMeetingBundles());

    // Get default meeting type from configuration.
    $event_settings = $this->configFactory->get(EventSettingsForm::SETTINGS);
    $default_meeting_type = $event_settings->get('online_meeting.default_meeting_type');

    if (in_array($default_meeting_type, $meeting_types)) {
      return $default_meeting_type;
    }

    // If the default value isn't available, use the first one from available.
    $first_meeting_type = (string) reset($meeting_types);

    return $first_meeting_type ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['meeting_form']['is_online'];
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

    $field_item = $items->first();

    $field_name = $items->getName();
    $wrapper_id = "meeting-type-wrapper-{$field_name}";

    // Get a selected bundle from form state or default.
    $selected_bundle = $form_state->getValue([$field_name, 'meeting_type']);
    if (!$selected_bundle) {
      // Check if there's an existing entity.
      if ($field_item instanceof EntityReferenceItem && !$field_item->isEmpty() && $field_item->target_id) {
        $default_value_entity = $field_item->entity;

        if ($default_value_entity && $this->eventOnline->validateMeetingTypeUsage($default_value_entity->bundle())) {
          $selected_bundle = $default_value_entity->bundle();
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
      '#value' => $field_item instanceof EntityReferenceItem && !$field_item->isEmpty() && $field_item->entity && $this->eventOnline->validateMeetingTypeUsage($field_item->entity->bundle())
        ? $field_item->target_id
        : NULL,
    ];

    // Container for the meeting form that will be updated via AJAX.
    $element['meeting_form'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $wrapper_id],
    ];

    $meeting_form_element =& $element['meeting_form'];

    // Add a checkbox to indicate if the event is online.
    $meeting_form_element['is_online'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Online'),
      '#default_value' => $field_item instanceof EntityReferenceItem && !$field_item->isEmpty(),
      '#ajax' => [
        'callback' => [$this, 'meetingTypeAjaxCallback'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
    ];

    $is_online = $form_state->getValue([$field_name, 'meeting_form', 'is_online']);
    if ($is_online === NULL && !($field_item instanceof EntityReferenceItem)) {
      return $element;
    }

    if ($is_online !== NULL && !$is_online) {
      return $element;
    }

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
        ],
      ];
    }

    // Only build the meeting form if a bundle is selected.
    if ($selected_bundle) {
      $this->buildMeetingForm($selected_bundle, $meeting_form_element, $items, $form_state);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    return ['target_id' => $values['target_id']];
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state): void {
    if (!$form_state->isValidationComplete()) {
      return;
    }

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
    if (empty($widget_state['meeting_form'])) {
      return;
    }

    $values =& $widget_state;

    if (!$values['meeting_form']['is_online']) {
      if (NULL !== $values['target_id']) {
        $previous_meeting = $this->entityTypeManager
          ->getStorage('meeting_api_meeting')
          ->load($values['target_id']);

        if ($previous_meeting instanceof MeetingEntityInterface) {
          // Delete the previous meeting as we can't use it anywhere.
          $previous_meeting->delete();
        }
      }

      $values['target_id'] = NULL;
    }
    else {
      $meeting = NestedArray::getValue($form[$field_name]['widget'], ['meeting_form', $values['meeting_type'], '#meeting_entity']);

      if (!$meeting instanceof MeetingEntityInterface) {
        $values['target_id'] = NULL;
      }
      else {
        $default_value = $values['target_id'] ?? NULL;

        // Extract values from the meeting form wrapper.
        $meeting_form_values = $values['meeting_form'] ?? [];

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
              // Delete the previous meeting as we can't use it anywhere.
              $previous_meeting->delete();
            }
          }
        }
        else {
          // Save the meeting entity if there are changes, otherwise skip.
          if ($this->putFormValuesToMeeting($meeting, $meeting_form_values, $form_state)) {
            $meeting->save();
          }
        }

        NestedArray::setValue($form[$field_name]['widget'], ['meeting_form', $values['meeting_type'], '#meeting_entity'], NULL);

        $values['target_id'] = $meeting->id();
      }
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
    if (!isset($triggering_element['#array_parents'])) {
      return [];
    }

    $parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $parents[] = 'meeting_form';

    return NestedArray::getValue($form, $parents) ?? [];
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

    // Validate only if the event is online.
    $is_online = $form_state->getValue([$field_name, 'meeting_form', 'is_online']);
    if (!$is_online) {
      return;
    }

    // Get the selected meeting type from the form state.
    $meeting_values = $form_state->getValue($parents);
    if (!$meeting_values) {
      return;
    }

    $meeting_type = $form_state->getValue([$field_name, 'meeting_type']);
    // Get the current meeting ID to exclude it from count (for edits).
    $current_meeting = NestedArray::getValue($form[$field_name]['widget'], ['meeting_form', $meeting_type, '#meeting_entity']);
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
      $form_state->setError($form[$field_name]['widget']['meeting_form']['is_online'], $violation->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    // This widget is very specific to an Event node type and
    // should not be used on other entity types.
    if (
      $field_definition->getTargetBundle() !== 'event' ||
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
