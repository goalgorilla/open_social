<?php

namespace Drupal\social_event_invite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupType;

/**
 * Class EnrollInviteForm.
 */
class EventInviteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_invite_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $social_event_config = $this->configFactory->getEditable('social_event_invite.settings');

    // Add an introduction text to explain what can be done here.
    $form['introduction']['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Be aware that when disabling invites altogether or for a specific group type, the outstanding invites in question <em>are cancelled</em>. Invitees will no longer be able to use their invite link.'),
    ];

    $form['invite_enroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable invite enrollment to events'),
      '#description' => $this->t('Enabling this feature provides the possibility to let event managers to invite people to their events.'),
      '#default_value' => $social_event_config->get('invite_enroll'),
    ];

    $group_types = [];
    /** @var \Drupal\group\Entity\GroupType $group_type */
    foreach (GroupType::loadMultiple() as $group_type) {
      $group_types[$group_type->id()] = $group_type->label();
    }

    $form['invite_group_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable event invite per group type'),
      '#description' => $this->t('Select the group types for which you want to enable the event invite feature.'),
      '#options' => $group_types,
      '#default_value' => $social_event_config->get('invite_group_types'),
    ];

    $form['invite_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $social_event_config->get('invite_subject'),
      '#required' => TRUE,
    ];

    $form['invite_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $social_event_config->get('invite_message'),
      '#required' => TRUE,
    ];

    $form['invite_helper'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Helper'),
      '#default_value' => $social_event_config->get('invite_helper'),
      '#required' => TRUE,
      '#rows' => '2',
    ];

    $form['email_verification'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow invited user to skip email verification'),
      '#default_value' => $social_event_config->get('email_verification'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#button_level' => 'raised',
      '#value' => $this->t('Save configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('social_event_invite.settings');
    $config->set('invite_enroll', $form_state->getValue('invite_enroll'));
    $config->set('invite_group_types', $form_state->getValue('invite_group_types'));
    $config->set('invite_message', $form_state->getValue('invite_message'));
    $config->set('invite_subject', $form_state->getValue('invite_subject'));
    $config->set('invite_helper', $form_state->getValue('invite_helper'));
    $config->set('email_verification', $form_state->getValue('email_verification'));
    $config->save();
  }

  /**
   * Gets the configuration names that will be editable.
   */
  protected function getEditableConfigNames() {
    // @todo Implement getEditableConfigNames() method.
  }

}
