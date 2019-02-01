<?php

namespace Drupal\social_event_managers\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_bulk_operations\Form\ConfigureAction;

/**
 * Action configuration form.
 */
class SocialEventManagementViewsBulkOperationsConfigureAction extends ConfigureAction {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = 'event_manage_enrollments', $display_id = 'page_manage_enrollments') {
    return parent::buildForm($form, $form_state, 'event_manage_enrollments', 'page_manage_enrollments');
  }

}
