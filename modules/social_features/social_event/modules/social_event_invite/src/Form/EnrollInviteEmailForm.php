<?php

namespace Drupal\social_event_invite\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_core\Form\InviteEmailBaseForm;
use Drupal\social_event\Entity\EventEnrollment;

/**
 * Class EnrollInviteForm.
 */
class EnrollInviteEmailForm extends InviteEmailBaseForm {
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

    $emails = $this->getSubmittedEmails($form_state);
    $nid = $form_state->getValue('event');

    foreach ($emails as $email) {
      // Default event enrollment field set.
      $fields = [
        'field_event' => $nid,
        'field_enrollment_status' => '0',
        'field_request_status' => 'invited',
        'field_email' => $email,
      ];

      // Create a new enrollment for the event.
      $enrollment = EventEnrollment::create($fields);
      $enrollment->save();
    }
  }
}
