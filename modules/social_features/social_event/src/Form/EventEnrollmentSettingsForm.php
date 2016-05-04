<?php

/**
 * @file
 * Contains \Drupal\social_event\Form\EventEnrollmentSettingsForm.
 */

namespace Drupal\social_event\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EventEnrollmentSettingsForm.
 *
 * @package Drupal\social_event\Form
 *
 * @ingroup social_event
 */
class EventEnrollmentSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'EventEnrollment_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }


  /**
   * Defines the settings form for Event enrollment entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['EventEnrollment_settings']['#markup'] = 'Settings form for Event enrollment entities. Manage field settings here.';
    return $form;
  }

}
