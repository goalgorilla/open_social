<?php

namespace Drupal\social_magic_login\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * MagicLoginController constructor.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
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
   * @see \Drupal\user\Controller\UserController::resetPassLogin
   */
  public function login($uid, $timestamp, $hash) {
    // The current user is not logged in, so check the parameters.
    $current = \Drupal::time()->getRequestTime();

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    // Verify that the user exists and is active.
    if (NULL === $user || !$user->isActive()) {
      throw new AccessDeniedHttpException();
    }

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    // No time out for first time login.
    if ($current - $timestamp > $timeout && $user->getLastLoginTime()) {
      $this->messenger()->addError($this->t('You have tried to use a magic login link that has expired.'));
      return $this->redirect('user.pass');
    }

    if (($timestamp <= $current)
      && ($timestamp >= $user->getLastLoginTime())
      && $user->isAuthenticated()
      && Crypt::hashEquals($hash, user_pass_rehash($user, $timestamp))) {
      user_login_finalize($user);
      $this->logger->notice('User %name used one-time login link at time %timestamp.', ['%name' => $user->getDisplayName(), '%timestamp' => $timestamp]);
      $this->messenger()->addStatus($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in.'));
    }

    return [
      '#markup' => $uid . ' - ' . $timestamp . ' - ' . $hash,
    ];
  }
}
