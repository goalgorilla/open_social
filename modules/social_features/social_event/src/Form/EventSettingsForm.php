<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventSettingsForm.
 *
 * @package Drupal\social_event\Form
 */
class EventSettingsForm extends ConfigFormBase {

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
      if ($group_type->hasContentPlugin('group_node:event')) {
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
      ->save();

    // Invalidate cache tags to refresh blocks of list of events.
    $this->cacheTagsInvalidator->invalidateTags(['node_list']);
  }

}
