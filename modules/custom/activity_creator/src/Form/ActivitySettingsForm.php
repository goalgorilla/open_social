<?php

namespace Drupal\activity_creator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ActivitySettingsForm.
 *
 * @package Drupal\activity_creator\Form
 *
 * @ingroup activity_creator
 */
class ActivitySettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   *   The unique string identifying the form.
   */
  public function getFormId(): string {
    return 'Activity_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Empty implementation of the abstract submit class.
  }

  /**
   * Defines the settings form for Activity entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['Activity_settings']['#markup'] = 'Settings form for Activity entities. Manage field settings here.';
    return $form;
  }

}
