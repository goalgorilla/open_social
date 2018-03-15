<?php

namespace Drupal\social_event_an_enroll\Form;

use Drupal\social_event\Form\EnrollActionForm;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\Component\Utility\Crypt;

/**
 * Class EventAnEnrollForm.
 *
 * @package Drupal\social_event_an_enroll\Form
 */
class EventAnEnrollForm extends EnrollActionForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_an_enroll_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nid = $this->routeMatch->getRawParameter('node');

    // Load node object.
    if (!empty($nid)) {
      if (!is_object($nid) && !is_null($nid)) {
        $node = $this->entityTypeManager
          ->getStorage('node')
          ->load($nid);
      }
    }

    // Set hidden values.
    $form['field_event'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    // Display form.
    // @todo: Add form to socialbase/includes/form.inc and fix title and submit.
    $form['#attributes']['class'][] = 'card';

    $form['field_first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
    ];

    $form['field_last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
    ];

    $form['field_email'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => $this->t('Email address'),
      '#description' => $this->t('Enter your email, so we can send you event updates.'),
    ];

    $submit_text = $this->t('Enroll in event');
    $enrollment_open = TRUE;

    // Add the enrollment closed label.
    if ($this->eventHasBeenFinished($node)) {
      $submit_text = $this->t('Event has passed');
      $enrollment_open = FALSE;
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#button_level' => 'raised',
      '#value' => $submit_text,
      '#disabled' => !$enrollment_open,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = $this->currentUser;
    $uid = $current_user->id();

    if ($uid === 0) {
      $nid = $form_state->getValue('field_event');
      $values = $form_state->getValues();

      $values['user_id'] = $uid;
      $values['field_account'] = $uid;
      $values['field_enrollment_status'] = '1';
      $values['field_token'] = Crypt::randomBytesBase64();

      // Create a new enrollment for the event.
      $enrollment = EventEnrollment::create($values);
      $enrollment->save();

      // Invalidate cache for our enrollment cache tag in
      // social_event_node_view_alter().
      $cache_tag = 'enrollment:' . $nid . '-' . $uid;
      Cache::invalidateTags([$cache_tag]);

      // Redirect anonymous use to login page before enrolling to an event.
      $form_state->setRedirect('entity.node.canonical',
        ['node' => $nid],
        ['query' => ['token' => $values['field_token']]]
      );

      $message = $this->t('You have successfully enrolled to this event. You have also received a notification via email.');
      drupal_set_message($message);

      // Send email.
      social_event_an_enroll_send_mail($values);

    }
  }

}
