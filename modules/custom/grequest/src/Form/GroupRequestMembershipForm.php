<?php

namespace Drupal\grequest\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupContent;
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
    $contentTypeConfigId = $this->group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $group_content = GroupContent::create([
      'type' => $contentTypeConfigId,
      'gid' => $this->group->id(),
      'entity_id' => $this->currentUser()->id(),
      'grequest_status' => GroupMembershipRequest::REQUEST_PENDING,
    ]);
    $result = $group_content->save();
    if ($result) {
      $this->messenger()->addMessage($this->t("Your request is waiting for Group Administrator's approval"));
    }
    else {
      $this->messenger()->addMessage($this->t("Error creating request"), self::TYPE_ERROR);
    }
  }

}
