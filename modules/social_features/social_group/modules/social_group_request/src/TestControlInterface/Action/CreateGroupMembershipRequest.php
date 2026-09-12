<?php

declare(strict_types=1);

namespace Drupal\social_group_request\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\social_core\TestControlInterface\EntityFieldNormalizer;
use Drupal\social_group_request\TestControlInterface\Dto\GroupMembershipRequestRow;
use Drupal\social_group_request\TestControlInterface\Dto\GroupRequestRow;
use Drupal\social_group_request\TestControlInterface\Success\GroupMembershipRequestsCreatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Creates requests to join a group.
 */
final class CreateGroupMembershipRequest {

  private const PLUGIN_ID = 'group_membership_request';

  private const ALLOWED_STATUSES = ['pending', 'approved', 'rejected'];

  /**
   * Converts table values into the shape entity storage expects.
   */
  private readonly EntityFieldNormalizer $fieldNormalizer;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->fieldNormalizer = new EntityFieldNormalizer($entityTypeManager);
  }

  /**
   * Creates requests to join a group, one per row.
   *
   * A request is created in a `pending` state if no status is given. This
   * does not create group memberships: an approved request still needs
   * `group members:` to add the membership itself, which keeps it possible
   * to describe a user who left after their request was approved.
   *
   * @param list<GroupMembershipRequestRow> $body
   *   The list of membership requests to create.
   *
   * @return \Drupal\social_group_request\TestControlInterface\Success\GroupMembershipRequestsCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'group_membership_requests_create',
    patterns: ['group membership requests:'],
    type: OperationType::Write,
  )]
  public function createGroupMembershipRequests(
    #[Example([['group' => 'My group', 'user' => 'Jane Doe', 'status' => 'pending']])]
    array $body,
  ): GroupMembershipRequestsCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No membership request rows in request body; refusing to return success with zero requests created.');
    }

    $ids = [];
    foreach ($body as $row) {
      $ids[] = $this->createRequestFromRow($row);
    }

    return new GroupMembershipRequestsCreatedSuccess(ids: $ids);
  }

  /**
   * Creates a single request from a `group membership requests:` row.
   */
  private function createRequestFromRow(GroupMembershipRequestRow $row): int {
    $status = $row->status ?? 'pending';
    if (!in_array($status, self::ALLOWED_STATUSES, TRUE)) {
      throw new BadRequestHttpException(sprintf("status must be one of %s, got '%s'.", implode(', ', self::ALLOWED_STATUSES), $status));
    }

    $group = $this->loadGroupByTitle($row->group);
    $user = $this->loadUserByName($row->user);

    $request_object = GroupRelationship::create([
      'type' => $this->getRelationshipTypeId($group),
      'gid' => $group->id(),
      'entity_id' => $user->id(),
      'grequest_status' => $status,
    ]);
    $request_object->save();

    return (int) $request_object->id();
  }

  /**
   * Creates requests to join a group, one per row, with arbitrary fields.
   *
   * Unlike `group membership requests:`, a row may set any field on the
   * relationship (for example `field_grequest_message`), and names the
   * request's state through `grequest_status` rather than `status`.
   *
   * @param list<GroupRequestRow> $body
   *   The list of membership requests to create.
   *
   * @return \Drupal\social_group_request\TestControlInterface\Success\GroupMembershipRequestsCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'group_requests_create',
    patterns: ['group requests:'],
    type: OperationType::Write,
  )]
  public function createGroupRequests(
    #[Example([['group' => 'My group', 'user' => 'Jane Doe', 'grequest_status' => 'pending']])]
    array $body,
  ): GroupMembershipRequestsCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No membership request rows in request body; refusing to return success with zero requests created.');
    }

    $ids = [];
    foreach ($body as $row) {
      $ids[] = $this->createRequestWithFieldsFromRow($row);
    }

    return new GroupMembershipRequestsCreatedSuccess(ids: $ids);
  }

  /**
   * Creates a single membership request from a `group requests:` row.
   */
  private function createRequestWithFieldsFromRow(GroupRequestRow $row): int {
    $group = $this->loadGroupByTitle($row->group);
    if (!$group->getGroupType()->hasPlugin(self::PLUGIN_ID)) {
      throw new BadRequestHttpException(sprintf("Group '%s' doesn't have request system.", $row->group));
    }

    $user = $this->loadUserByName($row->user);

    $values = $row->toFieldsArray();
    $values['gid'] = $group->id();
    $values['entity_id'] = $user->id();
    $values['type'] = $this->getRelationshipTypeId($group);

    $values = $this->fieldNormalizer->normalize('group_content', $values);
    $request_object = GroupRelationship::create($values);
    $violations = $request_object->validate();
    if ($violations->count() !== 0) {
      $messages = [];
      foreach ($violations as $violation) {
        $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
      }
      throw new BadRequestHttpException('The group request you tried to create is invalid: ' . implode("\n", $messages));
    }
    $request_object->save();

    return (int) $request_object->id();
  }

  /**
   * Resolve the group_membership_request relationship type ID for a group.
   */
  private function getRelationshipTypeId(GroupInterface $group): string {
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);

    return $storage->getRelationshipTypeId((string) $group->getGroupType()->id(), self::PLUGIN_ID);
  }

  /**
   * Load a group entity by its title.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if no group, or more than one group, shares the given title.
   */
  private function loadGroupByTitle(string $label): GroupInterface {
    $group_ids = $this->entityTypeManager->getStorage('group')->getQuery()
      ->accessCheck(FALSE)
      ->condition('label', $label)
      ->execute();

    if ($group_ids === []) {
      throw new BadRequestHttpException(sprintf("Group '%s' not found.", $label));
    }
    if (count($group_ids) > 1) {
      throw new BadRequestHttpException(sprintf("Group title '%s' is ambiguous: %d groups share that title.", $label, count($group_ids)));
    }

    $group = Group::load((int) reset($group_ids));
    if (!$group instanceof GroupInterface) {
      throw new BadRequestHttpException(sprintf("Group '%s' not found.", $label));
    }

    return $group;
  }

  /**
   * Load a user entity by username.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the user is not found.
   */
  private function loadUserByName(string $username): UserInterface {
    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username]);
    $user = reset($users);

    if (!$user instanceof UserInterface) {
      throw new BadRequestHttpException(sprintf('User %s not found.', $username));
    }

    return $user;
  }

}
