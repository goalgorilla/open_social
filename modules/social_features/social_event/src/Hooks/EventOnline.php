<?php

declare(strict_types=1);

namespace Drupal\social_event\Hooks;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;
use Drupal\meeting_api\MeetingEntityInterface;
use Drupal\social_event\Entity\Node\Event;
use Drupal\social_event\Form\EventSettingsForm;
use Drupal\social_event\PluginForm\ManualMeetingConfigurationForm;
use Drupal\social_event\Service\EventOnline as EventOnlineService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides hooks related to node event online feature.
 */
final class EventOnline implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Construct the EventOnline object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\social_event\Service\EventOnline $eventOnlineService
   *   The event online service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer service.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EventOnlineService $eventOnlineService,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ModuleInstallerInterface $moduleInstaller,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get(EventOnlineService::class),
      $container->get('module_handler'),
      $container->get('module_installer'),
    );
  }

  /**
   * Fix the path to the fullcalendar library for meeting_api_scheduler.
   *
   * @param array $libraries
   *   An array of libraries.
   * @param string $extension
   *   The extension name.
   *
   * @see hook_library_info_alter()
   */
  #[Alter('library_info')]
  public function fixFullCalendarJsPath(array &$libraries, string $extension): void {
    if ($extension === 'meeting_api_scheduler' && isset($libraries['fullcalendar']['js'])) {
      foreach ($libraries['fullcalendar']['js'] as $path => $options) {
        // Because the "meeting_api_scheduler" module expects the "fullcalendar"
        // library load by a specific repository rather than npm library,
        // the path to the main source file is broken.
        // Fix it by removing the "dist/" part of the path.
        unset($libraries['fullcalendar']['js'][$path]);
        $libraries['fullcalendar']['js'][str_replace('/dist/', '/', $path)] = $options;
      }
    }
  }

  /**
   * Enables the Meeting API Scheduler module when BigBlueButton is installed.
   *
   * Implements hook_modules_installed() to automatically install the
   * meeting_api_scheduler module when the meeting_api_bbb module is installed.
   * This ensures that scheduling functionality is available for BigBlueButton
   * meetings.
   *
   * @param array $modules
   *   An array of the names of the modules being installed.
   * @param bool $is_syncing
   *   TRUE if the installation is part of a configuration synchronization,
   *   FALSE otherwise.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\ExtensionNameReservedException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *
   * @see hook_modules_installed()
   */
  #[Hook('modules_installed')]
  public function enableSchedulerForBigBlueButton(array $modules, bool $is_syncing): void {
    if ($is_syncing) {
      return;
    }

    if (in_array('meeting_api_bbb', $modules)) {
      // Install "Meeting API Scheduler" module.
      $this->moduleInstaller->install(['meeting_api_scheduler']);

      // Grant a verified user role with a permission to see the scheduler.
      user_role_grant_permissions('verified', ['view any meeting_api server schedule']);
    }
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
   * Exposes the scheduler extra field for meeting_api_meeting entity.
   *
   * @return array
   *   An array of extra field definitions.
   *
   * @see hook_entity_extra_field_info()
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    // Add a scheduler extra field for meeting_api_meeting entity.
    $extra['meeting_api_meeting']['big_blue_button']['form']['scheduler'] = [
      'label' => $this->t('Scheduler'),
      'description' => $this->t('Scheduler field for meeting management'),
      'weight' => 100,
    ];

    return $extra;
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
    // Only require Address if the Events map is enabled.
    if (!$this->isEventsMapEnabled()) {
      return;
    }

    if (!isset($form['field_event_address'], $form['field_event_meeting'])) {
      return;
    }

    $form['field_event_address']['#element_validate'][] = [static::class, 'validateAddress'];
    // Add after_build to set the required indicator on the Country field.
    $form['field_event_address']['#after_build'][] = [static::class, 'setCountryRequired'];
  }

  /**
   * After build callback to add required indicator to the Country field.
   *
   * @param array $element
   *   The address field element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified element.
   */
  public static function setCountryRequired(array $element, FormStateInterface $form_state): array {
    if (!isset($element['widget'][0]['address']['country_code']['country_code'])) {
      return $element;
    }

    $country_select = &$element['widget'][0]['address']['country_code']['country_code'];
    if (!isset($country_select['#title'])) {
      return $element;
    }

    // Check initial online state to set correct visibility.
    $user_input = $form_state->getUserInput();
    $is_online = $user_input['field_event_meeting']['meeting_form']['is_online'] ?? NULL;

    // For the initial form load, check if event is online.
    if ($is_online === NULL) {
      $form_object = $form_state->getFormObject();
      if ($form_object instanceof EntityFormInterface) {
        $event = $form_object->getEntity();
        $is_online = $event instanceof Event && $event->isOnline();
      }
    }

    // Add the required * marker. Visibility toggled by JS.
    $hidden = $is_online ? ' hidden' : '';
    $country_select['#title'] = Markup::create($country_select['#title'] . ' <span class="form-required country-required-marker' . $hidden . '">*</span>');
    $element['#attached']['library'][] = 'social_event/event_meeting_widget';

    return $element;
  }

  /**
   * Specific for online events form adjustments.
   *
   * Alters the event form to add AJAX callbacks for refreshing the scheduler
   * upon changes in event dates or related meeting data.
   *
   * @param array $form
   *   A structured array representing the form. It is modified in-place by
   *   adding AJAX callbacks to specific form elements like event dates and
   *   meeting-related fields.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An object that contains the current state of the form. Used for managing
   *   form processing and interactivity.
   */
  #[Alter('form_node_event_form')]
  #[Alter('form_node_event_edit_form')]
  public function meetingApiEntityScheduler(array &$form, FormStateInterface $form_state): void {
    // Add AJAX callback to form elements to refresh the scheduler.
    if (!isset($form['field_event_date'], $form['field_event_date_end'], $form['field_event_meeting'])) {
      return;
    }

    $dates_ajax = [
      'callback' => [static::class, 'refreshOnEventDatesChange'],
      'event' => 'blur',
      // Disable refocusing on blur to prevent form field value loss if
      // a user submits the form immediately.
      'disable-refocus' => TRUE,
    ];

    // Add ajax callback to update the scheduler on event dates changing.
    $form['field_event_date']['widget'][0]['value']['#ajax'] = $dates_ajax;
    $form['field_event_date_end']['widget'][0]['value']['#ajax'] = $dates_ajax;

    $meeting_form =& $form['field_event_meeting']['widget']['meeting_form'];
    foreach (Element::children($meeting_form) as $name) {
      if (isset($meeting_form[$name]['entity_form']['max_attendees'])) {
        // Add ajax callback to update the scheduler on attendees changing.
        $meeting_form[$name]['entity_form']['max_attendees']['widget'][0]['value']['#ajax'] = $dates_ajax;
      }
    }
  }

  /**
   * AJAX callback to refresh the scheduler when event dates change.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response with commands to update the scheduler.
   */
  public static function refreshOnEventDatesChange(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Check if the meeting form exists.
    if (!isset($form['field_event_meeting']['widget']['meeting_form'])) {
      return $response;
    }

    $meeting_form = &$form['field_event_meeting']['widget']['meeting_form'];

    // Get the selected meeting type.
    $selected_bundle = $form_state->getValue(['field_event_meeting', 'meeting_type']);

    // Check if a scheduler exists for the selected bundle.
    if ($selected_bundle && isset($meeting_form[$selected_bundle]['entity_form']['scheduler'])) {
      $scheduler_element = $meeting_form[$selected_bundle]['entity_form']['scheduler'];

      // Add a replace command to update the scheduler.
      $response->addCommand(new ReplaceCommand(
        '#meeting-scheduler-wrapper',
        $scheduler_element
      ));
    }

    return $response;
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
   * if the event is not online and the events map is enabled.
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

  /**
   * Determines whether the events map feature is enabled.
   */
  private function isEventsMapEnabled(): bool {
    if (!$this->moduleHandler->moduleExists('social_geolocation_maps')) {
      return FALSE;
    }

    $maps_config = $this->configFactory->get('social_geolocation_maps.settings');
    return (bool) ($maps_config->get('events_map') ?? FALSE);
  }

}
