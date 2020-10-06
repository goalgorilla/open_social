<?php

namespace Drupal\social_event_addtocal\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_event_addtocal\Plugin\SocialAddToCalendarManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialAddToCalendarSettingsForm.
 */
class SocialAddToCalendarSettingsForm extends ConfigFormBase {

  /**
   * Add to calendar plugin manager.
   *
   * @var \Drupal\social_event_addtocal\Plugin\SocialAddToCalendarManager
   */
  protected $addToCalendarManager;

  /**
   * SocialAddToCalendarSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\social_event_addtocal\Plugin\SocialAddToCalendarManager $add_to_calendar_manager
   *   Add to calendar plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SocialAddToCalendarManager $add_to_calendar_manager) {
    parent::__construct($config_factory);

    $this->addToCalendarManager = $add_to_calendar_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.social_add_to_calendar')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_event_addtocal.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_add_to_calendar_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_event_addtocal.settings');

    // Enable the 'Add to calendar' feature.
    $form['enable_add_to_calendar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the <em>Add to calendar</em> button'),
      '#description' => $this->t('If enabled, logged-in users are allowed to add the event to own calendar'),
      '#default_value' => $config->get('enable_add_to_calendar'),
    ];

    // Get all calendar plugins and generate options.
    $addtocal_options = [];
    $addtocal_definitions = $this->addToCalendarManager->getDefinitions();
    foreach ($addtocal_definitions as $addtocal_definition) {
      $addtocal_options[$addtocal_definition['id']] = $addtocal_definition['label'];
    }

    // Allowed calendars.
    $form['allowed_calendars'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed calendars'),
      '#description' => $this->t('Enable calendars you want to allow users to use'),
      '#options' => $addtocal_options,
      '#states' => [
        'visible' => [
          ':input[name="enable_add_to_calendar"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => $config->get('allowed_calendars') ?: [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('social_event_addtocal.settings')
      ->set('enable_add_to_calendar', $form_state->getValue('enable_add_to_calendar'))
      ->set('allowed_calendars', $form_state->getValue('allowed_calendars'))
      ->save();
  }

}
