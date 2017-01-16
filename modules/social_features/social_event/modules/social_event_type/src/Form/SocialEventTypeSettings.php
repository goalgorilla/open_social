<?php

namespace Drupal\social_event_type\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialEventTypeSettings.
 *
 * @package Drupal\social_event_type\Form
 */
class SocialEventTypeSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_event_type.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_event_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_event_type.settings');

    $form['social_event_type_required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Event types required'),
      '#description' => $this->t('Set wether event types field is required or not.'),
      '#default_value' => $config->get('social_event_type_required'),
    );

    $form['social_event_type_select_changer'] = array(
      '#type' => 'number',
      '#title' => $this->t('Change input widget'),
      '#description' => $this->t('When the amount of available event types reach this amount, on the event edit and create page, the radio widget will be changed to a select widget for better usability.'),
      '#default_value' => $config->get('social_event_type_select_changer'),
      '#min' => 2,
      '#step' => 1,
      '#size' => 2,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('social_event_type.settings')
      ->set('social_event_type_required', $form_state->getValue('social_event_type_required'))
      ->set('social_event_type_select_changer', $form_state->getValue('social_event_type_select_changer'))
      ->save();
  }
}
