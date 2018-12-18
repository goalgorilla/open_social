<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gvbo\Form\GroupViewsBulkOperationsConfigureAction;

/**
 * Action configuration form.
 */
class SocialGroupViewsBulkOperationsConfigureAction extends GroupViewsBulkOperationsConfigureAction {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = 'group_manage_members', $display_id = 'page_group_manage_members') {
    return parent::buildForm($form, $form_state, $view_id, $display_id);
  }

}
