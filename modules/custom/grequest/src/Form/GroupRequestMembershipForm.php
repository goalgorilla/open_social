<?php

namespace Drupal\grequest\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a form for requesting a group membership.
 */
class GroupRequestMembershipForm extends ConfirmFormBase {

  /**
   * Related group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_request_membership_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t("Are you sure you want to request membership the group @group", ['@group' => $this->group->label()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL) {
    $this->group = $group;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $relation_type_id = $this->entityTypeManager()
      ->getStorage('group_content_type')
      ->getRelationshipTypeId($group->getGroupType()->id(), 'group_membership_request');

    $group_content = GroupRelationship::create([
      'type' => $relation_type_id,
      'gid' => $this->group->id(),
      'entity_id' => $this->currentUser()->id(),
      'grequest_status' => GroupMembershipRequest::REQUEST_PENDING,
    ]);
    $result = $group_content->save();
    if ($result) {
      $this->messenger()->addStatus($this->t("Your request is waiting for Group Administrator's approval"));
    }
    else {
      $this->messenger()->addError($this->t('Error creating request'));
    }
  }

}
