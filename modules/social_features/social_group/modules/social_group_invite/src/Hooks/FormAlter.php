<?php

declare(strict_types=1);

namespace Drupal\social_group_invite\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;

/**
 * Provides alterations to specific forms.
 */
final class FormAlter {

  use StringTranslationTrait;

  /**
   * Alter the group membership invite form.
   *
   * @param array $form
   *   The form structure that is to be altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  #[Alter('form_social_bulk_group_invitation')]
  public function modifyGroupInviteForm(array &$form, FormStateInterface $form_state): void {
    if (isset($form['actions']['submit_cancel']['#value'])) {
      $form['actions']['submit_cancel']['#value'] = $this->t('Back');
    }
  }

}
