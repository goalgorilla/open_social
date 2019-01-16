<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupType;

/**
 * Class EventSettingsForm.
 *
 * @package Drupal\social_event\Form
 */
class EventSettingsForm extends ConfigFormBase {

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
    $event_config = $this->configFactory->getEditable('social_event.settings');

    $form['enroll'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enroll user which is not group member'),
      '#description' => $this->t('Enroll button should be visible for users that are not in the group and automatic enroll people to groups when they enroll to events that are part of the group.'),
      '#default_value' => $event_config->get('enroll'),
      '#states' => [
        'visible' => [
          ':input[name="enroll"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['max_enroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable maximum number of event enrollments'),
      '#description' => $this->t('Enabling this feature provides event organisers with the possibility to set a limit for event enrollments.'),
      '#default_value' => $event_config->get('max_enroll'),
    ];

    $form['max_enroll_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Maximum event enrollments field is required'),
      '#default_value' => $event_config->get('max_enroll_required'),
      '#states' => [
        'visible' => [
          'input[name="max_enroll"]' => ['checked' => TRUE],
        ],
      ],
    ];

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    foreach (GroupType::loadMultiple() as $group_type) {
      // Check if this group type uses events.
      if ($group_type->hasContentPlugin('group_node:event')) {
        // Add to the option array.
        $form['enroll']['#options'][$group_type->id()] = $group_type->label();
      }
    }

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
      ->set('max_enroll', $form_state->getValue('max_enroll'))
      ->set('max_enroll_required', $form_state->getValue('max_enroll_required'))
      ->save();
  }

}
