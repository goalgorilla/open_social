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
    $config = $this->configFactory->getEditable('social_event.settings');

    $form['enroll'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enroll user which is not group member'),
      '#description' => $this->t('Enroll button should be visible for users that are not in the group and automatic enroll people to groups when they enroll to events that are part of the group.'),
      '#default_value' => $config->get('enroll'),
      '#states' => [
        'visible' => [
          ':input[name="enroll"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    // Group the settings for visibility options.
    $form['visibility_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event visibility settings'),
    ];
    $form['visibility_settings']['available_visibility_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select available visibility options'),
      '#description' => $this->t('Determines which visibility options should be available when creating a new event.<br /><strong>Note:</strong> this is only applied for creating events outside of groups.'),
      '#default_value' => $config->get('available_visibility_options'),
      '#options' => [
        'public' => $this->t('Public'),
        'community' => $this->t('Community'),
        'group' => $this->t('Group'),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $available_visibility_options = $form_state->getValue('available_visibility_options');
    $public = $available_visibility_options['public'];
    $community = $available_visibility_options['community'];
    $group = $available_visibility_options['group'];

    if ($public === 0 && $community === 0 && $group === 0) {
      $form_state->setError($form['visibility_settings']['available_visibility_options'], $this->t('Select at least one visibility'));
    }
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
      ->set('available_visibility_options', $form_state->getValue('available_visibility_options'))
      ->save();
  }

}
