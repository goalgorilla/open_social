<?php

declare(strict_types=1);

namespace Drupal\social_event\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;
use Drupal\meeting_api\MeetingEntityInterface;
use Drupal\social_event\Form\EventSettingsForm;
use Drupal\social_event\PluginForm\ManualMeetingConfigurationForm;
use Drupal\social_event\Service\EventOnline as EventOnlineService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides hooks related to node event online feature.
 */
final class EventOnline implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Construct the EventOnline object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\social_event\Service\EventOnline $eventOnlineService
   *   The event online service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    private readonly EventOnlineService $eventOnlineService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get(EventOnlineService::class),
    );
  }

  /**
   * Provides theme suggestions based on the event flag status and view mode.
   *
   * @param array $variables
   *   An associative array containing the variables for determining the
   *   appropriate theme suggestions.
   *   It includes keys such as 'theme_hook_original' and 'view_mode'.
   *
   * @return array
   *   An array of theme suggestions constructed from the 'theme_hook_original'
   *   combined with the 'view_mode'.
   *
   * @see hook_theme_suggestions_HOOK()
   */
  #[Hook('theme_suggestions_event_flag_online')]
  #[Hook('theme_suggestions_event_flag_enrolled')]
  public function themeSuggestions(array $variables): array {
    return [
      $variables['theme_hook_original'] . '__' . $variables['view_mode'],
    ];
  }

  /**
   * Adds validation constraints to the Meeting entity type.
   *
   * @param array $entity_types
   *   An array of entity type definitions, keyed by entity type ID.
   *
   * @see hook_entity_type_alter()
   */
  #[Alter('entity_type')]
  public function addViolationsToMeetingEntity(array &$entity_types): void {
    if (isset($entity_types['meeting_api_meeting'])) {
      $entity_type = $entity_types['meeting_api_meeting'];
      assert($entity_type instanceof EntityTypeInterface);

      // Add validation constraint to a Meeting entity.
      $entity_types['meeting_api_meeting']->addConstraint('MeetingServerSettings');
    }
  }

  /**
   * Adds validation constraints to specific fields of meeting entities.
   *
   * @param array $fields
   *   An array of base fields for the entity.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @see hook_entity_base_field_info_alter()
   */
  #[Alter('entity_base_field_info')]
  public function addViolationsToMeetingFields(array &$fields, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() !== 'meeting_api_meeting') {
      return;
    }

    if (isset($fields['max_attendees'])) {
      $fields['max_attendees']->addConstraint('MeetingCapacity');
    }
  }

  /**
   * Alter the backend info definitions for the meeting API module.
   *
   *  We need to make the "URL" form element optional.
   *  In other words, users can create "Custom Link" meetings without
   *  a link and add it later.
   *
   * @param array $definitions
   *   An array of backend definitions for the meeting API.
   *
   * @see hook_meeting_api_backend_info_alter()
   */
  #[Alter('meeting_api_backend_info')]
  public function meetingApiBackendInfoAlter(array &$definitions): void {
    if (isset($definitions['manual'])) {
      // Replace the form class with our custom one.
      $definitions['manual']['forms']['meeting'] = ManualMeetingConfigurationForm::class;
    }
  }

  /**
   * Prevent users from adding a BBB meeting without backend configuration.
   *
   * @param array $form
   *   The form build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_meeting_api_meeting_big_blue_button_add_form')]
  #[Alter('form_meeting_api_meeting_big_blue_button_edit_form')]
  public function alterBigBlueButtonForm(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    assert($form_object instanceof EntityFormInterface);

    $meeting = $form_object->getEntity();
    if ($meeting->bundle() !== 'big_blue_button') {
      return;
    }

    // Change min and max attendees.
    if (isset($form['max_attendees']['widget'][0]['value'])) {
      $event_settings = $this->configFactory->get(EventSettingsForm::SETTINGS);
      $max = $event_settings->get('online_meeting.max_attendees') ?: EventOnlineService::MAX_CONCURRENT_BBB_ATTENDEES;
      $min = EventOnlineService::DEFAULT_MEETING_ATTENDEES;

      $form['max_attendees']['widget'][0]['value']['#max'] = $max;
      $form['max_attendees']['widget'][0]['value']['#min'] = $min;
      $form['max_attendees']['widget'][0]['value']['#description'] = $this->t('The maximum allowed number of attendees. Allowed value is from <em>@min</em> to <em>@max</em>.', [
        '@min' => $min,
        '@max' => $max,
      ]);
    }
  }

  /**
   * Enhance "Online" filter display on search page.
   *
   * @param array $form
   *   The form build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_views_exposed_form')]
  public function searchExposedFiltersAlter(array &$form, FormStateInterface $form_state): void {
    if ($form['#id'] !== 'views-exposed-form-search-content-page') {
      return;
    }

    /* @see social_search_alter_content_exposed_filter_block() */
    $form['field_event_meeting']['#group'] = 'settings';
    // Move the field to the top of the form element group.
    $form['field_event_meeting']['#weight'] = -100;
  }

  /**
   * Alters the event node form for specific adjustments for online feature.
   *
   * @param array $form
   *   The form build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_node_event_form')]
  #[Alter('form_node_event_edit_form')]
  public function eventFormAlter(array &$form, FormStateInterface $form_state): void {
    if (isset($form['field_event_address'], $form['field_event_meeting'])) {
      $form['field_event_address']['#element_validate'][] = [static::class, 'validateAddress'];
    }
  }

  /**
   * Converts specific backend settings to their proper data types.
   *
   * This is a workaround for a bug in the BigBlueButton meetings.
   * It converts the scalar values of the backend settings to boolean values.
   *
   * @param \Drupal\meeting_api\MeetingEntityInterface $meeting
   *   The meeting entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\meeting_api_bbb\PluginForm\BigBlueButtonMeetingConfigurationForm::submitConfigurationForm()
   * @see hook_ENTITY_TYPE_presave()
   */
  #[Hook('meeting_api_meeting_presave')]
  public function convertBackendSettingsToProperTypes(MeetingEntityInterface $meeting): void {
    $server_backend_id = $this->eventOnlineService->getMeetingBackendId($meeting);
    // We want to check the BigBlueButton meetings only.
    if ($server_backend_id !== 'bigbluebutton') {
      return;
    }

    // Convert all settings with the "allow" prefix to boolean.
    $settings = $meeting->getSettings();
    foreach ($settings as $key => $value) {
      // Value should be either 0 or 1.
      $value_is_valid = $value === 0 || $value === 1;
      // Key should start with "allow_" or be "auto_start_recording".
      $key_is_valid = str_starts_with($key, 'allow_') || $key === 'auto_start_recording';
      if ($key_is_valid && $value_is_valid) {
        $settings[$key] = (bool) $value;
        $is_changed = TRUE;
      }
    }

    if ($is_changed ?? FALSE) {
      $meeting->set('backend_settings', $settings);
    }
  }

  /**
   * Validates the event address field.
   *
   * Make the "field_event_address" form element required
   * if the event is offline.
   *
   * @param array $element
   *   The event address form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the entire form.
   */
  public static function validateAddress(array $element, FormStateInterface $form_state): void {
    $is_online = $form_state->getValue(['field_event_meeting', 'meeting_form', 'is_online']);
    $country_code = $form_state->getValue(['field_event_address', 0, 'address', 'country_code']);
    if (!$is_online && !$country_code) {
      $form_state->setErrorByName('field_event_address', t('The country is required.'));
    }
  }

}
