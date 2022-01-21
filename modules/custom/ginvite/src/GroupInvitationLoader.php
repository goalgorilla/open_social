<?php

namespace Drupal\ginvite;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation;
use Drupal\ginvite\GroupInvitation as GroupInvitationWrapper;

/**
 * Loader for wrapped GroupContent entities using the 'group_invitation' plugin.
 */
class GroupInvitationLoader implements GroupInvitationLoaderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new GroupTypeController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Gets the group content storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupContentStorageInterface
   *   The group_content storage class.
   */
  protected function groupContentStorage() {
    return $this->entityTypeManager->getStorage('group_content');
  }

  /**
   * Wraps GroupContent entities in a GroupInvitation object.
   *
   * @param \Drupal\group\Entity\GroupContentInterface[] $entities
   *   An array of GroupContent entities to wrap.
   *
   * @return \Drupal\ginvite\GroupInvitation[]
   *   A list of GroupInvitation wrapper objects.
   */
  protected function wrapGroupContentEntities(array $entities) {
    $group_invitations = [];
    foreach ($entities as $group_content) {
      $group_invitations[] = new GroupInvitationWrapper($group_content);
    }
    return $group_invitations;
  }

  /**
   * {@inheritdoc}
   */
  public function load(GroupInterface $group, AccountInterface $account) {
    $filters = ['entity_id' => $account->id()];
    $group_contents = $this->groupContentStorage()->loadByGroup($group, 'group_invitation', $filters);
    $group_invitations = $this->wrapGroupContentEntities($group_contents);
    return $group_invitations ? reset($group_invitations) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL, $mail = NULL, $status = GroupInvitation::INVITATION_PENDING) {
    $filters = [
      'invitation_status' => $status,
    ];

    if (isset($roles)) {
      $filters['group_roles'] = (array) $roles;
    }
    if (isset($mail)) {
      $filters['invitee_mail'] = $mail;
    }

    $group_contents = $this->groupContentStorage()->loadByGroup($group, 'group_invitation', $filters);
    return $this->wrapGroupContentEntities($group_contents);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUser(AccountInterface $account = NULL, $roles = NULL, $status = GroupInvitation::INVITATION_PENDING) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }
    if ($account->isAnonymous()) {
      return [];
    }

    // Load all group content types for the invitation content enabler plugin.
    $group_content_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByProperties(['content_plugin' => 'group_invitation']);

    // If none were found, there can be no invitations either.
    if (empty($group_content_types)) {
      return [];
    }

    // Try to load all possible invitation group content for the user.
    $group_content_type_ids = [];
    foreach ($group_content_types as $group_content_type) {
      $group_content_type_ids[] = $group_content_type->id();
    }

    $properties = [
      'type' => $group_content_type_ids,
      'entity_id' => $account->id(),
      'invitation_status' => $status,
      'invitee_mail' => $account->getEmail(),
    ];
    if (isset($roles)) {
      $properties['group_roles'] = (array) $roles;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $this->groupContentStorage()->loadByProperties($properties);
    return $this->wrapGroupContentEntities($group_contents);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values) {
    // Load all group content types for the invitation content enabler plugin.
    $group_content_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByProperties(['content_plugin' => 'group_invitation']);

    // If none were found, there can be no invitations either.
    if (empty($group_content_types)) {
      return [];
    }

    // Try to load all possible invitation group content for the user.
    $group_content_type_ids = [];
    foreach ($group_content_types as $group_content_type) {
      $group_content_type_ids[] = $group_content_type->id();
    }

    $values['type'] = $group_content_type_ids;

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $this->groupContentStorage()->loadByProperties($values);
    return $this->wrapGroupContentEntities($group_contents);
  }

}
