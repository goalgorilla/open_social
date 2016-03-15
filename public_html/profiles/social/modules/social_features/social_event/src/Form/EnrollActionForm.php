<?php

/**
 * @file
 * Contains \Drupal\social_event\Form\EnrollActionForm.
 */

namespace Drupal\social_event\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EnrollActionForm.
 *
 * @package Drupal\social_event\Form
 */
class EnrollActionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['enroll_for_this_event'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Enroll for this event'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
