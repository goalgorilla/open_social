<?php

namespace Drupal\social_event_invite\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_core\Form\InviteBaseForm;

/**
 * Class EnrollInviteForm.
 */
class EnrollInviteForm extends InviteBaseForm {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_invite_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form =  parent::buildForm($form, $form_state);
    $entity = $this->routeMatch->getRawParameter('node');

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

  }
}
