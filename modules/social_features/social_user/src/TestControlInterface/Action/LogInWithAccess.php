<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Action;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_user\TestControlInterface\Error\UserBlocked;
use Drupal\social_user\TestControlInterface\Error\UserNotFound;
use Drupal\social_user\TestControlInterface\Success\LoginSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\Entity\User;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoints that log in with a given level of access rather than identity.
 *
 * The counterpart to UserSession::login(), which logs in as one specific,
 * already-existing account. These operations exist for the very
 * common test that doesn't care *who* it is, only what it may do: "somebody
 * with the sitemanager role", "somebody who may edit organization tags". They
 * arrange whatever that takes — a throwaway account, a throwaway role holding
 * exactly the requested permissions — and then log the request's session in as
 * it, in a single call.
 *
 * Doing all of that server-side is the point: the Behat steps these replaced
 * generated the random username, split the role list, and filtered the
 * implicit "authenticated" role in the test runner, over several driver-level
 * round trips into a separately bootstrapped Drupal kernel. None of that is a
 * browser concern, so none of it belongs in the test runner.
 */
final class LogInWithAccess {

  /**
   * Role names that name the implicit role every authenticated account has.
   *
   * Drupal grants "authenticated" to every logged-in account by definition;
   * it isn't a role entity you can add to an account, and trying to resolve
   * and grant it fails. A user asking for "a user with the authenticated
   * role" is asking for a plain logged-in account, so these are dropped from
   * a requested role list rather than rejected.
   */
  private const IMPLICIT_ROLE_NAMES = ['authenticated', 'authenticated user'];

  /**
   * How many times to retry generating a name that is not already taken.
   */
  private const UNIQUE_NAME_ATTEMPTS = 10;

  /**
   * Generates the throwaway user names, role ids, and labels.
   */
  private readonly Random $random;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly PermissionHandlerInterface $permissionHandler,
  ) {
    $this->random = new Random();
  }

  /**
   * Creates a throwaway user holding the given role(s) and logs in as it.
   *
   * Accepts a comma-separated list, and both role IDs and role labels. Any
   * mention of the implicit "authenticated" role is dropped, so asking for it
   * gives you a plain logged-in account rather than an error.
   *
   * @param string $role
   *   One role ID or label, or a comma-separated list of them.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\LoginSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'login_new_user_with_roles',
    patterns: [
      'I am logged in as a user with the :role role(s)',
      'I am logged in as a/an :role',
    ],
    type: OperationType::Write,
    changesSession: TRUE,
  )]
  public function logInAsNewUserWithRoles(
    #[Example('sitemanager')]
    #[Example('contentmanager, verified', name: 'multiple_roles')]
    string $role,
  ): LoginSuccess {
    $account = $this->createThrowawayUser($this->resolveRoleIds($role));

    return $this->logIn($account);
  }

  /**
   * Creates a throwaway user holding the given permission(s) and logs in.
   *
   * The permissions are granted through a throwaway role created for this
   * call alone, so the account holds exactly them and nothing else.
   *
   * @param string $permissions
   *   One permission, or a comma-separated list. Both machine names and
   *   human-readable permission titles are accepted.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\LoginSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'login_new_user_with_permissions',
    patterns: ['I am logged in as a user with the :permissions permission(s)'],
    type: OperationType::Write,
    changesSession: TRUE,
  )]
  public function logInAsNewUserWithPermissions(
    #[Example('social profile privacy always show full name')]
    string $permissions,
  ): LoginSuccess {
    $account = $this->createThrowawayUser([$this->createThrowawayRole($permissions)]);

    return $this->logIn($account);
  }

  /**
   * Grants an existing user the given permission(s) and logs in as them.
   *
   * For tests that need a *specific* account — one that already owns content,
   * or is a member of a group — to additionally be allowed to do something.
   * The permissions are granted through a throwaway role created for this
   * call alone, leaving the account's own roles untouched.
   *
   * @param string $name
   *   The username of the existing account to log in as.
   * @param string $permissions
   *   One permission, or a comma-separated list. Both machine names and
   *   human-readable permission titles are accepted.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\LoginSuccess|\Drupal\social_user\TestControlInterface\Error\UserNotFound|\Drupal\social_user\TestControlInterface\Error\UserBlocked
   *   The operation result. Unlike the two operations above, which log in as
   *   an account they just created themselves, this one loads an account the
   *   site already has — so it can find no such account, or find a blocked
   *   one.
   */
  #[Operation(
    id: 'login_user_with_permissions',
    patterns: ['I am logged in as :name with the :permissions permission(s)'],
    type: OperationType::Write,
    changesSession: TRUE,
  )]
  public function logInAsUserWithPermissions(
    #[Example('behatsitemanager')]
    string $name,
    #[Example('without consent')]
    string $permissions,
  ): LoginSuccess|UserNotFound|UserBlocked {
    $account = $this->loadUserByName($name);
    if ($account === NULL) {
      return new UserNotFound($name);
    }
    if (!$account->isActive()) {
      // user_login_finalize() doesn't check account status itself — that's
      // the login form's job normally, which this bypasses. Without this
      // check a test could log in as a blocked account, a session the real
      // application never allows to exist.
      return new UserBlocked($name);
    }

    $account->addRole($this->createThrowawayRole($permissions));
    $account->save();

    return $this->logIn($account);
  }

  /**
   * Logs the request's session in as the given account.
   *
   * Callers are responsible for only passing an active account; see
   * logInAsUserWithPermissions(), the only one that can be handed a blocked
   * one.
   *
   * @see \Drupal\social_user\TestControlInterface\Action\UserSession::login()
   */
  private function logIn(UserInterface $account): LoginSuccess {
    user_login_finalize($account);

    return new LoginSuccess();
  }

  /**
   * Loads the account with the given username, or NULL when there is none.
   */
  private function loadUserByName(string $name): ?UserInterface {
    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
    $account = reset($accounts);

    return $account instanceof UserInterface ? $account : NULL;
  }

  /**
   * Creates an active account with a random name holding the given roles.
   *
   * @param list<string> $roleIds
   *   Role IDs to grant, already resolved and filtered.
   */
  private function createThrowawayUser(array $roleIds): UserInterface {
    $name = $this->generateUnusedUserName();
    $account = User::create([
      'name' => $name,
      'mail' => $name . '@example.com',
      'pass' => $this->random->name(16),
      'status' => 1,
    ]);
    $account->save();

    if ($roleIds !== []) {
      foreach ($roleIds as $roleId) {
        $account->addRole($roleId);
      }
      $account->save();
    }

    return $account;
  }

  /**
   * Creates a role holding exactly the given permissions; returns its ID.
   *
   * @param string $permissions
   *   One permission, or a comma-separated list, as machine names or titles.
   */
  private function createThrowawayRole(string $permissions): string {
    $requested = $this->splitList($permissions);
    if ($requested === []) {
      throw new BadRequestHttpException('No permissions were requested; refusing to create a role that grants nothing.');
    }

    $roleId = $this->generateUnusedRoleId();
    $role = $this->entityTypeManager->getStorage('user_role')->create([
      'id' => $roleId,
      'label' => $this->random->name(8, TRUE),
    ]);
    foreach ($this->resolvePermissions($requested) as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();

    return $roleId;
  }

  /**
   * Resolves a requested role list to role IDs, dropping implicit roles.
   *
   * @return list<string>
   *   The role IDs to grant. May be empty, when only the implicit
   *   "authenticated" role was asked for.
   */
  private function resolveRoleIds(string $roles): array {
    $roleIds = [];
    foreach ($this->splitList($roles) as $role) {
      if (in_array(strtolower($role), self::IMPLICIT_ROLE_NAMES, TRUE)) {
        continue;
      }
      $roleIds[] = $this->resolveRoleId($role);
    }

    return $roleIds;
  }

  /**
   * Resolves a role ID or label to its role ID.
   */
  private function resolveRoleId(string $role): string {
    $storage = $this->entityTypeManager->getStorage('user_role');
    if ($storage->load($role) !== NULL) {
      return $role;
    }
    $matches = $storage->loadByProperties(['label' => $role]);
    $match = reset($matches);
    if ($match === FALSE) {
      throw new BadRequestHttpException(sprintf("Role '%s' does not exist.", $role));
    }

    return (string) $match->id();
  }

  /**
   * Resolves permission machine names or titles to machine names.
   *
   * @param list<string> $permissions
   *   The requested permissions.
   *
   * @return list<string>
   *   The permission machine names.
   */
  private function resolvePermissions(array $permissions): array {
    $available = $this->permissionHandler->getPermissions();
    $byTitle = [];
    foreach ($available as $machineName => $definition) {
      // Titles are translatable markup objects, not plain strings.
      $byTitle[(string) $definition['title']] = $machineName;
    }

    $resolved = [];
    foreach ($permissions as $permission) {
      if (isset($available[$permission])) {
        $resolved[] = $permission;
        continue;
      }
      if (isset($byTitle[$permission])) {
        $resolved[] = $byTitle[$permission];
        continue;
      }
      throw new BadRequestHttpException(sprintf("Permission '%s' does not exist; is the module that provides it installed?", $permission));
    }

    return $resolved;
  }

  /**
   * Splits a comma-separated step argument into its trimmed, non-empty parts.
   *
   * @return list<string>
   *   The individual values.
   */
  private function splitList(string $value): array {
    $parts = array_map('trim', explode(',', $value));

    return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
  }

  /**
   * Generates a random username that no account currently uses.
   */
  private function generateUnusedUserName(): string {
    $storage = $this->entityTypeManager->getStorage('user');
    for ($attempt = 0; $attempt < self::UNIQUE_NAME_ATTEMPTS; $attempt++) {
      // Random's own uniqueness tracking is per PHP process, and every TCI
      // call is a fresh request, so it can't rule out a name an earlier call
      // in the same scenario already used. Ask storage instead.
      $name = $this->random->name(8, TRUE);
      if ($storage->loadByProperties(['name' => $name]) === []) {
        return $name;
      }
    }

    throw new \RuntimeException(sprintf('Could not generate an unused username in %d attempts.', self::UNIQUE_NAME_ATTEMPTS));
  }

  /**
   * Generates a random role ID that no role currently uses.
   */
  private function generateUnusedRoleId(): string {
    $storage = $this->entityTypeManager->getStorage('user_role');
    for ($attempt = 0; $attempt < self::UNIQUE_NAME_ATTEMPTS; $attempt++) {
      $roleId = $this->random->machineName(8, TRUE);
      if ($storage->load($roleId) === NULL) {
        return $roleId;
      }
    }

    throw new \RuntimeException(sprintf('Could not generate an unused role ID in %d attempts.', self::UNIQUE_NAME_ATTEMPTS));
  }

}
