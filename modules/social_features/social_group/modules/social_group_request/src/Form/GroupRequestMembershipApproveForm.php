<?php

namespace Drupal\social_group_request\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\Entity\Form\GroupMembershipApproveForm;

/**
 * Provides a form for approving a group membership request.
 */
class GroupRequestMembershipApproveForm extends GroupMembershipApproveForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
    $group_relationship = $this->getEntity();
    /** @var \Drupal\user\UserInterface $user */
    $user = $group_relationship->getEntity();
    return $this->t('Are you sure you want to approve the membership request for %user?', ['%user' => $user->getDisplayName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#attributes']['class'][] = 'form--default';
    $form['actions']['cancel']['#attributes']['class'] = [
      'button',
      'button--flat',
      'btn',
      'btn-flat',
      'waves-effect',
      'waves-btn',
    ];

    // Remove possibility to select roles when membership request is approved.
    if (isset($form['roles'])) {
      unset($form['roles']);
    }

    return $form;
  }

}
