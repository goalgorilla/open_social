<?php

namespace Drupal\social_event_managers\Form;

use Drupal\views_bulk_operations\Form\ConfirmAction;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default action execution confirmation form.
 */
class SocialEventManagersViewsBulkOperationsConfirmAction extends ConfirmAction {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = 'event_manage_enrollments', $display_id = 'page_manage_enrollments') {
    $form = parent::buildForm($form, $form_state, $view_id, $display_id);
    $form_data = $this->getFormData($view_id, $display_id);

    // Show a descriptive message in the confirm action form.
    if (isset($form_data['action_id'])) {
      $form['description'] = [
        '#markup' => $this->formatPlural($form_data['selected_count'],
        'Are you sure you want to "%action" the following enrollee?',
        'Are you sure you want to "%action" the following %count enrollees?',
        [
          '%action' => $form_data['action_label'],
          '%count' => $form_data['selected_count'],
        ]),
        '#weight' => -10,
      ];

      if (strpos($form_data['action_id'], 'mail') !== FALSE) {
        $form['description'] = [
          '#markup' => $this->formatPlural($form_data['selected_count'],
            'Are you sure you want to send your email to the following enrollee?',
            'Are you sure you want to send your email to to the following %count enrollees?',
            [
              '%action' => $form_data['action_label'],
              '%count' => $form_data['selected_count'],
            ]),
          '#weight' => -10,
        ];
      }
    }

    $form['actions']['submit']['#attributes']['class'] = ['button button--primary js-form-submit form-submit btn js-form-submit btn-raised btn-primary waves-effect waves-btn waves-light'];
    $form['actions']['cancel']['#attributes']['class'] = ['button button--danger btn btn-flat waves-effect waves-btn'];

    return $form;
  }

}
