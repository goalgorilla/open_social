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
    // Get the config.
    $social_event_config = $this->configFactory->getEditable('social_event.settings');

    $form['enroll'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enroll user which is not group member'),
      '#description' => $this->t('Enroll button should be visible for users that are not in the group and automatic enroll people to groups when they enroll to events that are part of the group.'),
      '#default_value' => $social_event_config->get('enroll'),
      '#states' => [
        'visible' => [
          ':input[name="enroll"]' => [
            'checked' => TRUE,
          ],
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

    $form['address_visibility_settings'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Address visibility settings'),
      '#options' => [
        'street_code_private' => $this->t('Only show street and postal code to event enrolees'),
      ],
      '#default_value' => $social_event_config->get('address_visibility_settings'),
    ];

    $form['show_user_timezone'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display user’s time zone in events'),
      '#description' => $this->t('If enabled, user’s own time zone will be displayed after the event date and time.'),
      '#default_value' => $social_event_config->get('show_user_timezone'),
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
      ->save();

    // Invalidate cache tags to refresh blocks of list of events.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['node_list']);
  }

}
