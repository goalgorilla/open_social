<?php

namespace Drupal\socialbase\Plugin\Form;

use Drupal\bootstrap\Plugin\Form\FormBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @ingroup plugins_form
 *
 * @BootstrapForm("gdpr_consent_data_policy_agreement")
 */
class GdprConsentDataPolicyAgreement extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    $form->addClass('form--default');
    $form->actions->submit->addClass('btn-primary');
  }

}
