<?php

namespace Drupal\social_group_gvbo\Form;

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

    $form['actions']['submit']['#attributes']['class'] = ['button button--primary js-form-submit form-submit btn js-form-submit btn-raised btn-primary waves-effect waves-btn waves-light'];
    $form['actions']['cancel']['#attributes']['class'] = ['button button--danger btn btn-flat waves-effect waves-btn'];

    // Show a descriptive message in the confirm action form.
    if (!isset($form_data['action_id'])) {
      return $form;
    }

    $remove_memberships_page = $form_data['action_id'] === 'social_group_delete_group_content_action' &&
      $this->getRouteMatch()->getRouteName() === 'social_group_gvbo.views_bulk_operations.confirm';

    if ($remove_memberships_page) {
      $form['#title'] = $this->formatPlural(
        $form_data['selected_count'],
        'Are you sure you wish to remove 1 member?',
        'Are you sure you wish to remove %count members?',
        [
          '%action' => $form_data['action_label'],
          '%count' => $form_data['selected_count'],
        ]
      );

      if (!empty($form['list']['#title'])) {
        $form['list']['#title'] = $this->t('Member(s) selected:');
      }
    }
    else {
      $form['description'] = [
        '#markup' => $this->formatPlural($form_data['selected_count'],
          'Are you sure you want to "%action" the following member?',
          'Are you sure you want to "%action" the following %count members?',
          [
            '%action' => $form_data['action_label'],
            '%count' => $form_data['selected_count'],
          ]),
        '#weight' => -10,
      ];
    }

    if (str_contains($form_data['action_id'], 'mail')) {
      $form['description'] = [
        '#markup' => $this->formatPlural($form_data['selected_count'],
          'Are you sure you want to send your email to the following member?',
          'Are you sure you want to send your email to the following %count members? ',
          [
            '%action' => $form_data['action_label'],
            '%count' => $form_data['selected_count'],
          ]),
        '#weight' => -10,
      ];
    }

    return $form;
  }

}
