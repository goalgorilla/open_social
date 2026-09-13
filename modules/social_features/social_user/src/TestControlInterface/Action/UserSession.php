<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_user\TestControlInterface\Error\UserBlocked;
use Drupal\social_user\TestControlInterface\Error\UserNotFound;
use Drupal\social_user\TestControlInterface\Success\LoginSuccess;
use Drupal\social_user\TestControlInterface\Success\LogoutSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\UserInterface;

/**
 * TCI endpoints that log the request's session in or out.
 */
final class UserSession {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Logs the request's session in as the named user.
   *
   * If the session is already authenticated as another account it is
   * switched directly; no logout is performed first.
   *
   * @param string $name
   *   The username to log in as.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\LoginSuccess|\Drupal\social_user\TestControlInterface\Error\UserNotFound|\Drupal\social_user\TestControlInterface\Error\UserBlocked
   *   The operation result.
   */
  #[Operation(
    id: 'login_user',
    patterns: ['I am logged in as :name'],
    type: OperationType::Write,
    changesSession: TRUE,
  )]
  public function login(
    #[Example('sitemanager')]
    string $name,
  ): LoginSuccess|UserNotFound|UserBlocked {
    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
    $account = reset($accounts);
    if (!$account instanceof UserInterface) {
      return new UserNotFound($name);
    }
    if (!$account->isActive()) {
      // user_login_finalize() below doesn't check account status itself —
      // it's the login *form*'s job normally, which this bypasses. Without
      // this check a test could log in as a blocked account, a session the
      // real application never allows to exist.
      return new UserBlocked($name);
    }

    // No user_logout() first, even when another account is already
    // authenticated: user_login_finalize() migrates the session itself
    // (Session::migrate(), via SessionManager::regenerate()), and
    // regenerate() only acts on an *active* session. user_logout() destroys
    // the session outright (SessionManager::destroy(), calling the native
    // session_destroy()), so a logout first would leave regenerate() with an
    // inactive session to silently no-op on — the response would then carry
    // only user_logout()'s cookie-expiry header and never a fresh one for
    // this login, handing the browser an expired cookie instead of an
    // authenticated one.
    user_login_finalize($account);

    return new LoginSuccess();
  }

  /**
   * Logs the request's session out. A no-op if already anonymous.
   */
  #[Operation(
    id: 'logout_user',
    patterns: [
      'logout',
      'I logout',
      'I log out',
      'I am an anonymous user',
      'I am not logged in',
    ],
    type: OperationType::Write,
    changesSession: TRUE,
  )]
  public function logout(): LogoutSuccess {
    if ($this->currentUser->isAuthenticated()) {
      user_logout();
    }

    return new LogoutSuccess();
  }

}
