<?php

namespace Drupal\grequest\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\MembershipRequestManager;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides group membership request route controllers.
 */
class GroupMembershipRequestController extends ControllerBase {

  /**
   * Membership request manager.
   *
   * @var \Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Group Membership request controller constructor.
   *
   * @param \Drupal\grequest\MembershipRequestManager $membership_request_manager
   *   Membership request manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(MembershipRequestManager $membership_request_manager, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->membershipRequestManager = $membership_request_manager;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('grequest.membership_request_manager'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Provides the form for request a group membership.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group in which a membership request will be submitted.
   *
   * @return array
   *   A group request membership form.
   */
  public function requestMembership(GroupInterface $group) {
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $group_relationship = $this->membershipRequestManager->create($group, $user);
    return $this->entityFormBuilder()->getForm($group_relationship, 'group-request-membership');
  }

  /**
   * Provides the form for approval a group membership.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_content
   *   The group relationship.
   *
   * @return array
   *   A group approval membership form.
   */
  public function approveMembership(GroupRelationshipInterface $group_content) {
    return $this->entityFormBuilder()->getForm($group_content, 'group-approve-membership');
  }

  /**
   * Provides the form for rejection a group membership.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_content
   *   The group relationship.
   *
   * @return array
   *   A group rejection membership form.
   */
  public function rejectMembership(GroupRelationshipInterface $group_content) {
    return $this->entityFormBuilder()->getForm($group_content, 'group-reject-membership');
  }

  /**
   * The _title_callback for the request membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to request membership of.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function requestMembershipTitle(GroupInterface $group) {
    return $this->t('Request membership for group %label', ['%label' => $group->label()]);
  }

}
