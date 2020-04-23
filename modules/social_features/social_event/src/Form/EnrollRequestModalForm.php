<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\EventEnrollmentInterface;

/**
 * SendToDestinationsForm class.
 */
class EnrollRequestModalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'request_enrollment_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = $node->id();

    $form['#prefix'] = '<div id="request_enrollment">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['event'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You can leave a message in your request. Only when your request is approved, you will receive a notification via email and notification center.'),
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#maxlength' => 250,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send request'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'] = [
      'core/drupal.dialog.ajax',
      'social_event/modal',
    ];
    $form['#attached']['drupalSettings']['eventEnrollmentRequest'] = [
      'closeDialog' => TRUE,
    ];

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      // If there are errors, we can show the form again with the errors in
      // the status_messages section.
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $form_state->setRebuild();

      return $response->addCommand(new OpenModalDialogCommand($this->t('Request to enroll'), $form, static::getDataDialogOptions()));
    }

    // Refactor this into a service or helper.
    $message = $form_state->getValue('message');

    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    $nid = $form_state->getValue('event');

    // Default event enrollment field set.
    $fields = [
      'user_id' => $uid,
      'field_event' => $nid,
      'field_enrollment_status' => '0',
      'field_account' => $uid,
      'field_request_or_invite_status' => EventEnrollmentInterface::REQUEST_PENDING,
      'field_request_message' => $message,
    ];

    // Create a new enrollment for the event.
    $enrollment = EventEnrollment::create($fields);
    $enrollment->save();

    // On success leave a message and reload the page.
    \Drupal::messenger()->addStatus(t('Your request has been sent successfully'));
    return $response->addCommand(new CloseDialogCommand());
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.request_enrollment_modal_form'];
  }

  /**
   * Helper method so we can have consistent dialog options.
   *
   * @return string[]
   *   An array of jQuery UI elements to pass on to our dialog form.
   */
  protected static function getDataDialogOptions() {
    return [
      'dialogClass' => 'form--default social_event-popup',
      'closeOnEscape' => TRUE,
      'width' => '582',
    ];
  }

}
