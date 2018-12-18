<?php

namespace Drupal\social_group\Form;

use Drupal\views_bulk_operations\Form\ConfirmAction;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default action execution confirmation form.
 */
class SocialGroupViewsBulkOperationsConfirmAction extends ConfirmAction {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = 'group_manage_members', $display_id = 'page_group_manage_members') {
    $form = parent::buildForm($form, $form_state, $view_id, $display_id);
    $form_data = $this->getFormData($view_id, $display_id);

    if (isset($form_data['action_id'])) {
      $form['#title'] = $this->formatPlural(
        $form_data['selected_count'],
        'Are you sure you wish to perform "%action" action on 1 member?',
        'Are you sure you wish to perform "%action" action on %count members?',
        [
          '%action' => $form_data['action_label'],
          '%count' => $form_data['selected_count'],
        ]
      );
    }

    return $form;
  }

}
