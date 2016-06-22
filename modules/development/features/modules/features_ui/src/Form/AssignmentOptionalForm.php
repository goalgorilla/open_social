<?php

/**
 * @file
 * Contains \Drupal\features_ui\Form\AssignmentOptionalForm.
 */

namespace Drupal\features_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the selected configuration assignment method for this site.
 */
class AssignmentOptionalForm extends AssignmentFormBase {

  const METHOD_ID = 'optional';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'features_assignment_optional_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bundle_name = NULL) {
    $this->currentBundle = $this->assigner->loadBundle($bundle_name);
    $settings = $this->currentBundle->getAssignmentSettings(self::METHOD_ID);

    $this->setConfigTypeSelect($form, $settings['types']['config'], $this->t('optional'));
    $this->setActions($form);

    return $form;
  }

 /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('types', array_map('array_filter', $form_state->getValue('types')));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Merge in types selections.
    $settings = $this->currentBundle->getAssignmentSettings(self::METHOD_ID);
    $settings['types'] = $form_state->getValue('types');
    $this->currentBundle->setAssignmentSettings(self::METHOD_ID, $settings)->save();
    $this->setRedirect($form_state);

    drupal_set_message($this->t('Package assignment configuration saved.'));
  }

}
