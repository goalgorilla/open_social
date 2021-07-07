<?php

namespace Drupal\social_user;

use Drupal\Core\Session\AccountProxy;
use Drupal\oauth2_server\OAuth2HelperInterface;
use Drupal\oauth2_server\OAuth2StorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for adding new methods to the user account to check if this user comes
 * from Oauth2.
 *
 * Decorates the current_user service.
 */
class SocialAccountProxy extends AccountProxy {

  /**
   * The token data.
   *
   * @var array
   */
  private array $token;

  /**
   * The OAuth2Helper service.
   *
   * @var \Drupal\oauth2_server\OAuth2HelperInterface
   */
  protected $oauth2Helper;

  /**
   * The related request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The OAuth2Storage.
   *
   * @var \Drupal\oauth2_server\OAuth2StorageInterface
   */
  protected $oauth2Storage;

  /**
   * SocialAccountProxy constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface|null $eventDispatcher
   *   Event dispatcher.
   * @param \Drupal\oauth2_server\OAuth2HelperInterface $oauth2_helper
   *   The OAuth2Helper service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\oauth2_server\OAuth2StorageInterface $oauth2_storage
   *   The OAuth2 storage service.
   */
  public function __construct(
    EventDispatcherInterface $eventDispatcher = NULL,
    OAuth2HelperInterface $oauth2_helper,
    RequestStack $request_stack,
    OAuth2StorageInterface $oauth2_storage
  ) {
    parent::__construct($eventDispatcher);

    $this->oauth2Helper = $oauth2_helper;
    $this->request = $request_stack->getCurrentRequest();
    $this->oauth2Storage = $oauth2_storage;
  }

  /**
   * Checks if the user authorized via Oauth2.
   *
   * @return bool
   *   TRUE if the user authorized via Oauth2, FALSE otherwise.
   */
  public function isValidOauth2Account(): bool {
    $token = $this->oauth2Helper->getTokenFromRequest($this->request);
    $valid_authentication = $this->oauth2Helper->hasValidOauth2Authentication($this->request);

    if ($valid_authentication) {
      $access_token = $this->oauth2Storage->getAccessToken($token);

      if ($access_token) {
        $this->setAccountAccessToken($access_token);
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Gets the token data.
   *
   * @return array|null
   *   An array of token data.
   */
  public function getAccountAccessToken(): ?array {
    if ($this->isValidOauth2Account()) {
      return $this->token;
    }

    return NULL;
  }

  /**
   * Set the token data for a specific user.
   *
   * @param $token
   *   An array of token data.
   */
  private function setAccountAccessToken($token) {
    $this->token = $token;
  }

}
