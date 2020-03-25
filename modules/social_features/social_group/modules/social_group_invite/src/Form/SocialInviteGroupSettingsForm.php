<?php

namespace Drupal\social_group_invite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupType;

/**
 * Class SocialInviteGroupSettingsForm.
 */
class SocialInviteGroupSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_group_invite_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    foreach (GroupType::loadMultiple() as $group_type) {
      if ($group_type->hasContentPlugin('group_invitation')) {

      }
    }

    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }

    $this->configuration['invitation_subject'] = $form_state->getValue('invitation_subject');
    $this->configuration['invitation_body'] = $form_state->getValue('invitation_body');
    $this->configuration['existing_user_invitation_subject'] = $form_state->getValue('existing_user_invitation_subject');
    $this->configuration['existing_user_invitation_body'] = $form_state->getValue('existing_user_invitation_body');
    $this->configuration['send_email_existing_users'] = $form_state->getValue('send_email_existing_users');


  }

}
