<?php

namespace Drupal\grequest\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class GroupRequestMembershipRejectForm extends ConfirmFormBase {

  /**
   * Group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Group membership request.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  protected $groupContent;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grequest_group_request_membership_reject';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to Reject this request?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromUserInput(\Drupal::destination()->get());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL, GroupContentInterface $group_content = NULL) {
    $this->group = $group;
    $this->groupContent = $group_content;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->groupContent
      ->set('grequest_status', GroupMembershipRequest::REQUEST_REJECTED)
      // Who created request will become an 'approver' for Membership request.
      ->set('grequest_updated_by', $this->currentUser()->id());
    $result = $this->groupContent->save();

    if ($result) {
      $this->messenger()->addStatus($this->t('Membership Request rejected'));
    }
    else {
      $this->messenger()->addError($this->t('Error updating Request'));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
