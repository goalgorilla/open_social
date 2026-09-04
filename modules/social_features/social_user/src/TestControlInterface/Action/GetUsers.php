<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_user\TestControlInterface\Error\UserNotFound;
use Drupal\social_user\TestControlInterface\Error\UsersNotFound;
use Drupal\social_user\TestControlInterface\Success\UserInfoSuccess;
use Drupal\social_user\TestControlInterface\Success\UsersInfoSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\UserInterface;

/**
 * TCI endpoints for reading user identities: current, by name, or by names.
 *
 * Only getCurrentUser() isn't expected to be used from a feature file
 * directly (see UserSession for why) — getUserByName()/getUsersByName()
 * are genuine assertion steps in their own right, since a missing user
 * fails the step.
 */
final class GetUsers {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Returns the request's authenticated user's ID, UUID, and username.
   *
   * The request's current user is resolved the same way as any other
   * Drupal request (session cookie, or the TCI current-user header
   * override — see
   * Drupal\test_control_interface\EventSubscriber\CurrentUserSwitchSubscriber),
   * so this reflects whoever UserSession::login() last authenticated for
   * this session, without either side needing to track it separately. The
   * anonymous user is a legitimate result (uid 0, uuid and name NULL), not
   * an error: it means no session is currently authenticated.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\UserInfoSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'current_user',
    patterns: ['the TCI session current user'],
    type: OperationType::Read,
  )]
  public function getCurrentUser(): UserInfoSuccess {
    if ($this->currentUser->isAnonymous()) {
      return new UserInfoSuccess(uid: 0, uuid: NULL, name: NULL);
    }

    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    assert($account instanceof UserInterface);

    return new UserInfoSuccess(uid: (int) $account->id(), uuid: $account->uuid(), name: $account->getAccountName());
  }

  /**
   * Returns the given user's ID, UUID, and username.
   *
   * @param string $name
   *   The username to look up.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\UserInfoSuccess|\Drupal\social_user\TestControlInterface\Error\UserNotFound
   *   The operation result.
   */
  #[Operation(
    id: 'user_by_name',
    patterns: ['the user with :name exists'],
    type: OperationType::Read,
  )]
  public function getUserByName(
    #[Example('Jane')]
    string $name,
  ): UserInfoSuccess|UserNotFound {
    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
    $account = reset($accounts);
    if (!$account instanceof UserInterface) {
      return new UserNotFound($name);
    }

    return new UserInfoSuccess(uid: (int) $account->id(), uuid: $account->uuid(), name: $account->getAccountName());
  }

  /**
   * Returns the ID, UUID, and username of each given user, in order.
   *
   * Doubles as an assertion step: UsersNotFound fails the step if any
   * listed user doesn't exist.
   *
   * @param list<string> $body
   *   Usernames to look up, one per table row (no header needed, but any
   *   single-column header works — this table format doesn't inspect the
   *   column name, unlike a CreateXRequest-backed `:` step).
   *
   * @return \Drupal\social_user\TestControlInterface\Success\UsersInfoSuccess|\Drupal\social_user\TestControlInterface\Error\UsersNotFound
   *   The operation result.
   */
  #[Operation(
    id: 'users_by_name',
    patterns: ['the users exist:'],
    // A list body can't travel on a GET request — TciHttpExecutor only
    // ever sends a body for POST/PUT/PATCH — so this is Write-typed
    // despite being a pure read with no side effects.
    type: OperationType::Write,
  )]
  public function getUsersByName(
    #[Example(['sitemanager', 'verified'])]
    array $body,
  ): UsersInfoSuccess|UsersNotFound {
    // Trimmed, but a blank row is kept rather than dropped: this doubles
    // as an assertion step, and silently discarding a blank entry would
    // make a malformed table row (or a scenario outline substituting an
    // empty value by mistake) pass instead of correctly failing with
    // UsersNotFound for it.
    $requestedNames = array_map('trim', $body);
    if ($requestedNames === []) {
      return new UsersInfoSuccess(users: []);
    }

    // One bulk lookup for the unique names rather than one query per
    // requested name — loadByProperties() treats an array value as an IN
    // condition — then rebuilt in $requestedNames order below so input
    // order and duplicate names are preserved.
    $storage = $this->entityTypeManager->getStorage('user');
    $accounts = $storage->loadByProperties(['name' => array_values(array_unique($requestedNames))]);
    // Keyed by a case-normalized name, not the exact string: the database
    // column this queries typically compares case-insensitively, so a
    // request for "SiteManager" can load an account whose canonical name
    // is "sitemanager" — indexing by the account's own (canonical) name
    // and then looking up by the (possibly differently-cased) requested
    // name would wrongly miss it.
    $accountsByNormalizedName = [];
    foreach ($accounts as $account) {
      $accountsByNormalizedName[mb_strtolower($account->getAccountName())] = $account;
    }

    $users = [];
    $missing = [];
    foreach ($requestedNames as $name) {
      $account = $accountsByNormalizedName[mb_strtolower($name)] ?? NULL;
      if ($account === NULL) {
        $missing[] = $name;
        continue;
      }
      $users[] = [
        'uid' => (int) $account->id(),
        'uuid' => $account->uuid(),
        'name' => $account->getAccountName(),
      ];
    }

    if ($missing !== []) {
      return new UsersNotFound($missing);
    }

    return new UsersInfoSuccess(users: $users);
  }

}
