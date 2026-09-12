<?php

declare(strict_types=1);

namespace Drupal\social_group\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\Group;
use Drupal\social_core\TestControlInterface\EntityFieldNormalizer;
use Drupal\social_group\TestControlInterface\Dto\CreateGroupRequest;
use Drupal\social_group\TestControlInterface\Success\GroupsBulkCreatedSuccess;
use Drupal\social_group\TestControlInterface\Success\GroupsWithOwnerCreatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Creates groups.
 */
final class CreateGroup {

  /**
   * Converts table values into the shape entity storage expects.
   */
  private readonly EntityFieldNormalizer $fieldNormalizer;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
  ) {
    $this->fieldNormalizer = new EntityFieldNormalizer($entityTypeManager);
  }

  /**
   * Creates groups, one per row, each owned by the `author` it names.
   *
   * Group bundles differ field by field, so CreateGroupRequest declares the
   * union of the fields the groups under test are created with.
   *
   * @param list<CreateGroupRequest> $body
   *   The list of groups to create.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupsBulkCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'groups_create_bulk',
    patterns: ['groups:'],
    type: OperationType::Write,
  )]
  public function createGroups(
    #[Example([
      [
        'label' => 'My group',
        'author' => 'admin',
        'type' => 'flexible_group',
        'langcode' => 'en',
        'field_flexible_group_visibility' => 'public',
      ],
    ])]
    array $body,
  ): GroupsBulkCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No group rows in request body; refusing to return success with zero groups created.');
    }

    $gids = [];
    foreach ($body as $row) {
      if ($row->author === NULL) {
        throw new BadRequestHttpException("You must specify an `author` when creating a group. Specify the `author` field if using `groups:` or use `groups with non-anonymous owner:` instead.");
      }
      $gids[] = (int) $this->createGroupFromRow($row)->id();
    }

    return new GroupsBulkCreatedSuccess(gids: $gids);
  }

  /**
   * Creates groups, one per row, all owned by one newly created user.
   *
   * The owner is created for this request, so the groups are guaranteed to
   * have an owner that is neither anonymous nor an account the test set up
   * for another purpose.
   *
   * @param list<CreateGroupRequest> $body
   *   The list of groups to create.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupsWithOwnerCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'groups_create_with_owner',
    patterns: ['groups with non-anonymous owner:'],
    type: OperationType::Write,
  )]
  public function createGroupsWithOwner(
    #[Example([
      [
        'label' => 'My group',
        'type' => 'flexible_group',
        'langcode' => 'en',
        'field_flexible_group_visibility' => 'public',
      ],
    ])]
    array $body,
  ): GroupsWithOwnerCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No group rows in request body; refusing to return success with zero groups created.');
    }
    foreach ($body as $row) {
      if ($row->author !== NULL) {
        throw new BadRequestHttpException("Can not specify an author when using the 'groups with non-anonymous owner:' step, use 'groups:' instead.");
      }
    }

    $owner = $this->createRandomAuthenticatedUser();
    $owner_name = (string) $owner->getAccountName();

    $gids = [];
    foreach ($body as $row) {
      $gids[] = (int) $this->createGroupFromRow($row->withAuthor($owner_name))->id();
    }

    return new GroupsWithOwnerCreatedSuccess(gids: $gids, owner: $owner_name);
  }

  /**
   * Creates groups, one per row, all owned by the current user.
   *
   * An anonymous current user is allowed and owns the groups as the account
   * named `anonymous`.
   *
   * @param list<CreateGroupRequest> $body
   *   The list of groups to create.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupsBulkCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'groups_create_by_current_user',
    patterns: ['groups owned by current user:'],
    type: OperationType::Write,
  )]
  public function createGroupsOwnedByCurrentUser(
    #[Example([
      [
        'label' => 'My group',
        'type' => 'flexible_group',
        'langcode' => 'en',
        'field_flexible_group_visibility' => 'public',
      ],
    ])]
    array $body,
  ): GroupsBulkCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No group rows in request body; refusing to return success with zero groups created.');
    }
    foreach ($body as $row) {
      if ($row->author !== NULL) {
        throw new BadRequestHttpException("Can not specify an author when using the 'groups owned by current user:' step, use 'groups:' instead.");
      }
    }

    $author_name = $this->currentUser->isAuthenticated() ? $this->currentUser->getAccountName() : 'anonymous';

    $gids = [];
    foreach ($body as $row) {
      $gids[] = (int) $this->createGroupFromRow($row->withAuthor($author_name))->id();
    }

    return new GroupsBulkCreatedSuccess(gids: $gids);
  }

  /**
   * Create a group from a row.
   */
  private function createGroupFromRow(CreateGroupRequest $row): Group {
    $account = $this->loadUserByName((string) $row->author);
    if ($account === NULL) {
      throw new BadRequestHttpException(sprintf("User with username '%s' does not exist.", $row->author));
    }
    $group = $row->toRowArray();
    $group['uid'] = $account->id();
    unset($group['author']);

    // Allow using multiple values.
    if (isset($group['field_group_allowed_visibility']) && is_string($group['field_group_allowed_visibility']) && str_contains($group['field_group_allowed_visibility'], ',')) {
      $group['field_group_allowed_visibility'] = explode(',', $group['field_group_allowed_visibility']);
    }

    $group = $this->fieldNormalizer->normalize('group', $group);
    $group_object = Group::create($group);
    $violations = $group_object->validate();
    if ($violations->count() !== 0) {
      $messages = [];
      foreach ($violations as $violation) {
        $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
      }
      throw new BadRequestHttpException('The group you tried to create is invalid: ' . implode("\n", $messages));
    }
    $group_object->save();

    return $group_object;
  }

  /**
   * Creates a random authenticated user for group ownership.
   */
  private function createRandomAuthenticatedUser(): User {
    $name = bin2hex(random_bytes(8));
    $user = User::create([
      'name' => $name,
      'mail' => $name . '@example.com',
      'status' => 1,
    ]);
    $violations = $user->validate();
    if ($violations->count() > 0) {
      $messages = [];
      foreach ($violations as $violation) {
        $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
      }
      throw new BadRequestHttpException('Invalid user: ' . implode("\n", $messages));
    }
    $user->save();
    return $user;
  }

  /**
   * Load a user by name.
   *
   * @param string $name
   *   The username.
   *
   * @return \Drupal\user\Entity\User|null
   *   The user or NULL if not found.
   */
  private function loadUserByName(string $name): ?User {
    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
    $first = reset($users);
    return $first instanceof User ? $first : NULL;
  }

}
