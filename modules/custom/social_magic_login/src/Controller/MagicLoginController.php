<?php

namespace Drupal\social_magic_login\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class MagicLoginController.
 */
class MagicLoginController extends ControllerBase {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * MagicLoginController constructor.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(UserStorageInterface $user_storage, LoggerInterface $logger) {
    $this->userStorage = $user_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('logger.factory')->get('user')
    );
  }

  /**
   * Login.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   * @param string $destination
   *   The final destination the user needs to end up as an encoded string.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   *
   * @see \Drupal\user\Controller\UserController::resetPassLogin
   */
  public function login($uid, $timestamp, $hash, $destination) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);
    // Verify that the user exists and is active.
    if ($user === NULL || !$user->isActive() || $user->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    // Get the current user and check if this user is authenticated and same as
    // the user for the login link.
    $current_user = $this->currentUser();
    if ($current_user->isAuthenticated() && $current_user->id() != $uid) {
      $this->messenger()
        ->addWarning($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
          [
            '%other_user' => $current_user->getAccountName(),
            '%resetting_user' => $user->getAccountName(),
            ':logout' => Url::fromRoute('user.logout'),
          ]));
      throw new AccessDeniedHttpException();
    }
    // Get the destination for the redirect result.
    $destination = base64_decode($destination);

    // The current user is not logged in, so check the parameters.
    $currentTime = \Drupal::time()->getRequestTime();

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    // If the user has logged in before then the link may have timed out.
    // Also check that we don't have an invalid link.
    if (
      ($user->getLastLoginTime() && $currentTime - $timestamp > $timeout) ||
      ($timestamp > $currentTime || $timestamp < $user->getLastLoginTime())
    ) {
      $this->messenger()->addError($this->t('You have tried to use a one-time link that has expired.'));

      return $this->redirect('user.login', [], ['query' => ['destination' => $destination]]);
    }

    // When the user hasn't set a password, redirect the user to
    // the set passwords page.
    if (NULL === $user->getPassword()) {
      $this->messenger()->addStatus($this->t('You need to set your passwords in order to log in.'));
      $this->logger->notice('User %name used magic login link at time %timestamp but needs to set a password.', ['%name' => $user->getDisplayName(), '%timestamp' => $timestamp]);
      user_login_finalize($user);

      // This mirrors the UserController::resetPassLogin redirect which
      // allows a user to set a password without the current password check.
      $token = Crypt::randomBytesBase64(55);
      $_SESSION['pass_reset_' . $user->id()] = $token;
      return $this->redirect(
        'entity.user.edit_form',
        ['user' => $user->id()],
        [
          'query' => [
            'pass-reset-token' => $token,
            'destination' => $destination,
          ],
          'absolute' => TRUE,
        ]
      );
    }

    // The user already had a password, check the hash.
    if (Crypt::hashEquals($hash, user_pass_rehash($user, $timestamp))) {
      user_login_finalize($user);
      $this->logger->notice('User %name used one-time login link at time %timestamp.', ['%name' => $user->getDisplayName(), '%timestamp' => $timestamp]);
      $this->messenger()->addStatus($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in.'));

      return new RedirectResponse($destination);
    }
  }

}
