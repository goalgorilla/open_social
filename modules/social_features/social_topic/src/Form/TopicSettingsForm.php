<?php

namespace Drupal\social_topic\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TopicSettingsForm.
 *
 * @package Drupal\social_topic\Form
 */
class TopicSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_topic.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'topic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_topic.settings');

    $form['social_topic_type_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Topic types required'),
      '#description' => $this->t('Set wether topic types field is required or not.'),
      '#default_value' => $config->get('social_topic_type_required'),
    ];

    $form['social_topic_type_select_changer'] = [
      '#type' => 'number',
      '#title' => $this->t('Change input widget'),
      '#description' => $this->t('When the amount of available topic types reach this amount, on the topic edit and create page, the radio widget will be changed to a select widget for better usability.'),
      '#default_value' => $config->get('social_topic_type_select_changer'),
      '#min' => 2,
      '#step' => 1,
      '#size' => 2,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('social_topic.settings')
      ->set('social_topic_type_required', $form_state->getValue('social_topic_type_required'))
      ->set('social_topic_type_select_changer', $form_state->getValue('social_topic_type_select_changer'))
      ->save();
  }

}
