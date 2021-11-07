<?php

namespace Drupal\social_post\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Post Settings Form.
 *
 * @package Drupal\social_post\Form
 *
 * @ingroup social_post
 */
class PostSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   *   The unique string identifying the form.
   */
  public function getFormId(): string {
    return 'Post_settings';
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
   * Defines the settings form for Post entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['Post_settings']['#markup'] = $this->t('Settings form for Post entities. Manage field settings here.');
    return $form;
  }

}
