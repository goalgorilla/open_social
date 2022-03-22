<?php

namespace Drupal\social_group_invite\Form;

use Drupal\social_group_gvbo\Form\SocialGroupViewsBulkOperationsConfirmAction;
use Drupal\Core\Form\FormStateInterface;

/**
 * Send reminder action execution confirmation form.
 */
class SocialGroupInviteVBOConfirmAction extends SocialGroupViewsBulkOperationsConfirmAction {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = 'social_group_invitations', $display_id = 'page_1'): array {
    $form = parent::buildForm($form, $form_state, $view_id, $display_id);
    $form_data = $this->getFormData($view_id, $display_id);

    // Show a descriptive message in the confirm action form.
    if (isset($form_data['action_id'])) {
      if ($form_data['action_id'] === 'social_group_invite_resend_action') {
        $form['actions']['submit']['#value'] = $this->t('Send');
      }

      if ($form_data['action_id'] === 'social_group_delete_group_content_action') {
        $form['actions']['submit']['#value'] = $this->t('Remove');
      }
    }

    return $form;
  }

}
