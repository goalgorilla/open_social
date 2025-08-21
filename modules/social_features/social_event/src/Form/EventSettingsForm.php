<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventSettingsForm.
 *
 * @package Drupal\social_event\Form
 */
class EventSettingsForm extends ConfigFormBase {

  /**
   * The configuration settings for social events.
   */
  const string SETTINGS = 'social_event.settings';

  /**
   * Maximum allowed concurrent BigBlueButton meetings attendees.
   */
  public const int MAX_CONCURRENT_BBB_ATTENDEES = 200;

  /**
   * The default number of attendees.
   */
  public const int DEFAULT_MEETING_ATTENDEES = 2;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * EventSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_event.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the config.
    $social_event_config = $this->configFactory->getEditable('social_event.settings');

    $form['event_display'] = [
      '#type' => 'details',
      '#title' => $this->t('Event display settings'),
      '#open' => TRUE,
    ];

    $form['event_display']['enroll'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enroll user which is not group member'),
      '#description' => $this->t('Enroll button should be visible for users that are not in the group and automatic enroll people to groups when they enroll to events that are part of the group.'),
      '#default_value' => $social_event_config->get('enroll') ?: [],
      '#states' => [
        'visible' => [
          ':input[name="enroll"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types*/
    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();
    foreach ($group_types as $group_type) {
      // Check if this group type uses events.
      if ($group_type->hasPlugin('group_node:event')) {
        // Add to the option array.
        $form['event_display']['enroll']['#options'][$group_type->id()] = $group_type->label();
      }
    }

    $form['event_display']['address_visibility_settings'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Address visibility settings'),
      '#options' => [
        'street_code_private' => $this->t('Only show street and postal code to event enrollees'),
      ],
      '#default_value' => $social_event_config->get('address_visibility_settings') ?: [],
    ];

    $form['event_display']['show_user_timezone'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display user’s time zone in events'),
      '#description' => $this->t('If enabled, user’s own time zone will be displayed after the event date and time.'),
      '#default_value' => $social_event_config->get('show_user_timezone'),
    ];

    $form['event_enrolment'] = [
      '#type' => 'details',
      '#title' => $this->t('Event enrollment settings'),
      '#open' => TRUE,
    ];

    $form['event_enrolment']['disable_event_enroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable all event enrollments on your community.'),
      '#description' => $this->t('If disabled, event organizers can decide to disable or enable event enrollments when creating or editing an event.'),
      '#default_value' => $social_event_config->get('disable_event_enroll'),
    ];

    // Online Meetings section.
    $this->buildOnlineMeetingsSection($form, $form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $group_type_ids = [];

    foreach ($form_state->getValue('enroll') as $group_type_id => $enable) {
      if ($enable) {
        $group_type_ids[] = $group_type_id;
      }
    }

    $this->configFactory->getEditable('social_event.settings')
      ->set('enroll', $group_type_ids)
      ->set('address_visibility_settings', $form_state->getValue('address_visibility_settings'))
      ->set('show_user_timezone', $form_state->getValue('show_user_timezone'))
      ->set('disable_event_enroll', $form_state->getValue('disable_event_enroll'))
      ->set('online_meeting',  $form_state->getValue('online_meeting'))
      ->save();

    // Invalidate cache tags to refresh blocks of list of events.
    $this->cacheTagsInvalidator->invalidateTags(['node_list']);
  }

  /**
   * Get available meeting types.
   *
   * @return array
   *   Array of meeting type options keyed by ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAvailableMeetingTypes(): array {
    $meeting_type_storage = $this->entityTypeManager
      ->getStorage('meeting_api_meeting_type');

    $meeting_types = $meeting_type_storage->loadMultiple();

    foreach ($meeting_types as $meeting_type_id => $meeting_type_entity) {
      $options[$meeting_type_id] = $meeting_type_entity->label();
    }

    return $options ?? [];
  }

  /**
   * Checks if BigBlueButton backend is properly configured.
   *
   * @return bool
   *   TRUE if BigBlueButton backend has a non-empty URL and key,
   *   FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function isBigBlueButtonServerConfigured(): bool {
    $meeting_api_servers = \Drupal::entityTypeManager()
      ->getStorage('meeting_api_server')
      ->loadMultiple();

    $bbb_servers = array_filter($meeting_api_servers, fn ($server) => $server->get('backend') === 'bigbluebutton');
    if (empty($bbb_servers)) {
      return FALSE;
    }

    foreach ($bbb_servers as $bbb_server) {
      if ($configuration = $bbb_server->get('backend_config')) {
        if (!empty($configuration['url']) && !empty($configuration['key'])) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Builds the online meetings configuration section form.
   *
   * @param array $form
   *   The form to which the online meetings section will be added.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   *   No explicit return as the modifications are applied directly
   *   to the $form array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildOnlineMeetingsSection(array &$form, FormStateInterface $form_state): void {
    $event_settings = $this->configFactory->getEditable(self::SETTINGS);

    $form['online_meeting'] = [
      '#type' => 'details',
      '#title' => $this->t('Online Meeting'),
      '#tree' => TRUE,
    ];

    $online_meeting =&  $form['online_meeting'];

    $online_meeting['default_meeting_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default meeting type'),
      '#description' => $this->t('Select the default meeting type for new events.'),
      '#options' =>  $this->getAvailableMeetingTypes(),
      '#default_value' => $event_settings->get('online_meeting.default_meeting_type'),
    ];

    // Check if the BigBlueButton is properly configured.
    $bbb_configured = $this->isBigBlueButtonServerConfigured();
    // Display warning if BigBlueButton is not properly configured.
    if (!$bbb_configured) {
      $online_meeting['bbb_warning'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' . $this->t('BigBlueButton backend is not properly configured. Please ensure the URL and Key are set @url.', [
          '@url' => Link::fromTextAndUrl($this->t('here'), Url::fromRoute('entity.meeting_api_server.collection'))->toString(),
          ]) . '</div>',
        '#weight' => -10,
      ];
    }

    $online_meeting['max_attendees'] = [
      '#type' => 'number',
      '#title' => $this->t('Max attendees'),
      '#description' => $this->t('Default maximum number of attendees for online meeting.'),
      '#default_value' => $event_settings->get('online_meeting.max_attendees') ?: self::MAX_CONCURRENT_BBB_ATTENDEES,
      '#min' => 1,
      '#max' => self::MAX_CONCURRENT_BBB_ATTENDEES,
    ];
  }

}
