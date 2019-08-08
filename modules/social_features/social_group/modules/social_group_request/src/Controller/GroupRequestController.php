<?php

namespace Drupal\social_group_request\Controller;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group_request\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Group request routes.
 */
class GroupRequestController extends ControllerBase {

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheTagsInvalidator;

  /**
   * The controller constructor.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder, MessengerInterface $messenger, CacheTagsInvalidator $cache_tags_invalidator, TranslationInterface $string_translation) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->messenger = $messenger;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('messenger'),
      $container->get('cache_tags.invalidator'),
      $container->get('string_translation')
    );
  }

  /**
   * Return the title for approve request confirmation page.
   */
  public function getTitleApproveRequest(GroupInterface $group, GroupContentInterface $group_content) {
    return $this->t('Approve membership request to group @group_title', ['@group_title' => $group->label()]);
  }

  /**
   * Return the title for reject request confirmation page.
   */
  public function getTitleRejectRequest(GroupInterface $group, GroupContentInterface $group_content) {
    return $this->t('Reject membership request to group @group_title', ['@group_title' => $group->label()]);
  }

  /**
   * Builds the form to create new membership on membership request approve.
   */
  public function approveRequest(GroupInterface $group, GroupContentInterface $group_content) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');

    // Pre-populate a group membership from Membership request.
    $group_content = $this->entityTypeManager()->getStorage('group_content')->create([
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $group_content->get('entity_id')->getString(),
    ]);

    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $group->id()]);

    return $this->entityFormBuilder->getForm($group_content, 'add');
  }

  /**
   * Callback to request membership.
   */
  public function requestMembership(GroupInterface $group) {
    $contentTypeConfigId = $group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $group_content = $this->entityTypeManager()->getStorage('group_content')->create([
      'type' => $contentTypeConfigId,
      'gid' => $group->id(),
      'entity_id' => $this->currentUser()->id(),
      'grequest_status' => GroupMembershipRequest::REQUEST_PENDING,
    ]);
    $result = $group_content->save();

    if ($result) {
      $this->messenger()->addMessage($this->t("Your request is waiting for Group Administrator's approval"));
    }
    else {
      $this->messenger()->addError($this->t('Error creating request'));
    }

    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $group->id()]);

    return $this->redirect('social_group.stream', ['group' => $group->id()]);
  }

  /**
   * Callback to cancel the request of membership.
   */
  public function cancelRequest(GroupInterface $group) {
    $contentTypeConfigId = $group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $requests = $this->entityTypeManager()->getStorage('group_content')->loadByProperties([
      'type' => $contentTypeConfigId,
      'gid' => $group->id(),
      'entity_id' => $this->currentUser()->id(),
      'grequest_status' => GroupMembershipRequest::REQUEST_PENDING,
    ]);

    foreach ($requests as $request) {
      $request->delete();
    }

    $this->messenger()->addMessage($this->t('Cancel request of membership has been done successfully.'));

    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $group->id()]);

    return $this->redirect('social_group.stream', ['group' => $group->id()]);
  }

}
