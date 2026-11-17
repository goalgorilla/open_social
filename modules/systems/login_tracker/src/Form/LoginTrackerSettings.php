<?php

namespace Drupal\login_tracker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Logger settings.
 */
class LoginTrackerSettings extends FormBase {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new SystemStateEdit object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_tracker_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $settings = $this->state->get('login_tracker_settings', []);
    $options = [
      0 => $this->t('Never'),
      3600 => $this->t('1 hour'),
      86400 => $this->t('1 day'),
      604800 => $this->t('1 week'),
      2678400 => $this->t('1 month'),
      5097600 => $this->t('2 months'),
      10364400 => $this->t('4 months'),
      15634800 => $this->t('6 months'),
    ];

    $form['purge_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Periodically purge records older than'),
      '#description' => $this->t('Records older than this interval will be deleted via Cron.'),
      '#default_value' => $settings['purge_interval'] ?? 0,
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->messenger()->addMessage($this->t('The configuration has been saved'), 'status');

    $values = $form_state->getValues();
    $new_values['purge_interval'] = $values['purge_interval'];

    // Set State.
    $this->state->set('login_tracker_settings', $new_values);
  }

}
