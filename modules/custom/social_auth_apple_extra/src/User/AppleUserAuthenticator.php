<?php

namespace Drupal\social_auth_apple_extra\User;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\User\UserAuthenticator;
use Drupal\social_auth\User\UserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages Drupal authentication tasks for Social Auth Apple Extra.
 *
 * @package Drupal\social_auth_apple_extra\User
 */
class AppleUserAuthenticator extends UserAuthenticator {

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * AppleUserAuthenticator constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Used to get current active user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Used to display messages to user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   * @param \Drupal\social_auth\User\UserManager $user_manager
   *   The Social API user manager.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   Used to interact with session.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Used for accessing Drupal configuration.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   Used to check if route path exists.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Used for dispatching social auth events.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    UserManager $user_manager,
    SocialAuthDataHandler $data_handler,
    ConfigFactoryInterface $config_factory,
    RouteProviderInterface $route_provider,
    EventDispatcherInterface $event_dispatcher,
    EmailValidatorInterface $email_validator
  ) {
    parent::__construct(
      $current_user,
      $messenger,
      $logger_factory,
      $user_manager,
      $data_handler,
      $config_factory,
      $route_provider,
      $event_dispatcher
    );

    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticateUser($name, $email, $provider_user_id, $token, $picture_url = NULL, $data = NULL) {
    if ($this->emailValidator->isValid($name)) {
      $name = str_replace('@', '-', $name);
    }

    return parent::authenticateUser($name, $email, $provider_user_id, $token, $picture_url, $data);
  }

}
