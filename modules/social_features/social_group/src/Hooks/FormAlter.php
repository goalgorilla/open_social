<?php

declare(strict_types=1);

namespace Drupal\social_group\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;

/**
 * Specific form alter hooks for group entity forms.
 */
final class FormAlter {

  use StringTranslationTrait;

  /**
   * Alter the group memberships leave confirmation form.
   *
   * @param array $form
   *   The form structure that is to be altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The unique ID of the form being altered.
   */
  #[Alter('form')]
  public function modifyGroupMembershipLeaveForm(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!str_ends_with($form_id, '-group_membership_group-leave_form')) {
      return;
    }

    if (isset($form['actions']['submit']['#value'])) {
      $form['actions']['submit']['#value'] = $this->t('Leave');
    }
  }

  /**
   * Alters a group membership join form.
   *
   * @param array $form
   *   The form structure that is to be altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The unique ID of the form being altered.
   */
  #[Alter('form')]
  public function modifyGroupMembershipJoinForm(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!str_ends_with($form_id, '-group_membership_group-join_form')) {
      return;
    }

    if (isset($form['actions']['submit']['#value'])) {
      $form['actions']['submit']['#value'] = $this->t('Join');
    }
  }

}
