<?php

namespace Drupal\social_event_invite\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_core\Form\InviteUserBaseForm;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\user\UserInterface;

/**
 * Class EnrollInviteForm.
 */
class EnrollInviteUserForm extends InviteUserBaseForm {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_invite_user_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form =  parent::buildForm($form, $form_state);
    $nid = $this->routeMatch->getRawParameter('node');

    $form['event'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    $form['actions']['submit_cancel']['#value'] = $this->t('Back to event');

    return $form;
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

    $users = $this->getUsers($form_state);
    $nid = $form_state->getValue('event');

    foreach ($users as $user) {
      if ($user instanceof UserInterface) {
        // Default values.
        $fields = [
          'field_event' => $nid,
          'field_enrollment_status' => '0',
          'field_request_or_invite_status' => '3',
          'user_id' => $user->id(),
          'field_account' => $user->id(),
        ];

        // Create a new enrollment for the event.
        $enrollment = EventEnrollment::create($fields);
        $enrollment->save();
      }
    }
  }
}
