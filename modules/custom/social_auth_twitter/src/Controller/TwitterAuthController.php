<?php

namespace Drupal\social_auth_twitter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_auth_twitter\TwitterAuthManager;
use Drupal\social_api\Plugin\NetworkManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Manages requests to Twitter API.
 */
class TwitterAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The Twitter authentication manager.
   *
   * @var \Drupal\social_auth_twitter\TwitterAuthManager
   */
  private $authManager;

  /**
   * The current request.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $request;

  /**
   * Contains access token to work with API.
   */
  protected $accessToken;

  /**
   * Contains instance of PHP Library.
   *
   * @var \Abraham\TwitterOAuth\TwitterOAuth
   */
  protected $sdk;

  /**
   * TwitterAuthController constructor.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   * @param \Drupal\social_auth_twitter\TwitterAuthManager $auth_manager
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(NetworkManager $network_manager, TwitterAuthManager $auth_manager, RequestStack $request_stack) {
    $this->networkManager = $network_manager;
    $this->authManager = $auth_manager;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth_twitter.auth_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Returns the redirect response.
   *
   * @param $type
   *   Type of action. "login" or "register".
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  protected function getRedirectResponse($type) {
    $sdk = $this->getSdk($type);

    if ($sdk instanceof RedirectResponse) {
      return $sdk;
    }

    $this->authManager->setSdk($sdk);
    $url = $this->authManager->getAuthenticationUrl($type);

    return new TrustedRedirectResponse($url);
  }

  /**
   * Response for path 'user/login/twitter'.
   *
   * Redirects the user to Twitter for authentication.
   */
  public function userLogin() {
    return $this->getRedirectResponse('login');
  }

  /**
   * Authorizes the user after redirect from Twitter.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function userLoginCallback() {
    $sdk = $this->getSdk('login');

    if ($sdk instanceof RedirectResponse) {
      return $sdk;
    }

    $this->authManager->setSdk($sdk);
    $profile = $this->getProfile('register');

    if ($profile instanceof RedirectResponse) {
      return $profile;
    }

    // Check whether user account exists. If account already exists,
    // authorize the user and redirect him to the account page.
    $account = $this->entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
        'twitter_id' => $profile->id,
      ]);

    if (!$account) {
      return $this->redirect('social_auth_twitter.user_login_notice');
    }

    $account = current($account);

    if (!$account->get('status')->value) {
      drupal_set_message($this->t('Your account is blocked. Contact the site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    user_login_finalize($account);
    drupal_set_message(t('You are logged in'));

    return $this->redirect('entity.user.canonical', [
      'user' => $account->id(),
    ]);
  }

  /**
   * Response for path 'user/register/twitter'.
   *
   * Redirects the user to Twitter for registration.
   */
  public function userRegister() {
    return $this->getRedirectResponse('register');
  }

  /**
   * Registers the new account after redirect from Twitter.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function userRegisterCallback() {
    $sdk = $this->getSdk('register');

    if ($sdk instanceof RedirectResponse) {
      return $sdk;
    }

    $this->authManager->setSdk($sdk);
    $profile = $this->getProfile('register');

    if ($profile instanceof RedirectResponse) {
      return $profile;
    }

    // Check whether user account exists. If account already exists,
    // authorize the user and redirect him to the account page.
    $account = $this->entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
        'twitter_id' => $profile->id,
      ]);

    if ($account) {
      $account = current($account);

      if (!$account->get('status')->value) {
        drupal_set_message($this->t('You already have account on this site, but your account is blocked. Contact the site administrator.'), 'error');
        return $this->redirect('user.register');
      }

      user_login_finalize($account);

      return $this->redirect('entity.user.canonical', [
        'user' => $account->id(),
      ]);
    }

    // Save email and name to storage to use for auto fill the registration form.
    $data_handler = $this->networkManager->createInstance('social_auth_twitter')->getDataHandler();
    $data_handler->set('access_token', $this->accessToken);
    $data_handler->set('mail', NULL);
    $data_handler->set('name', $this->authManager->getUsername());

    drupal_set_message($this->t('You are now connected with @network, please continue registration', [
      '@network' => $this->t('Twitter'),
    ]));

    return $this->redirect('user.register', [
      'provider' => 'twitter',
    ]);
  }

  /**
   * Returns the SDK instance or RedirectResponse when error occurred.
   *
   * @param string $type
   *   Type of action. "login" or "register".
   *
   * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function getSdk($type) {
    if ($this->sdk) {
      return $this->sdk;
    }

    $network_manager = $this->networkManager->createInstance('social_auth_twitter');

    if (!$network_manager->isActive()) {
      drupal_set_message($this->t('@network is disabled. Contact the site administrator', [
        '@network' => $this->t('Twitter'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    if (!$this->sdk = $network_manager->getSdk()) {
      drupal_set_message($this->t('@network Auth not configured properly. Contact the site administrator.', [
        '@network' => $this->t('Twitter'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    return $this->sdk;
  }

  public function getProfile($type) {
    $sdk = $this->getSdk($type);
    $data_handler = $this->networkManager->createInstance('social_auth_twitter')->getDataHandler();

    // Get the OAuth token from Twitter.
    if (!($oauth_token = $data_handler->get('oauth_token')) || !($oauth_token_secret = $data_handler->get('oauth_token_secret'))) {
      drupal_set_message($this->t('@network login failed. Token is not valid.', [
        '@network' => $this->t('Twitter'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    $this->authManager->setAccessToken([
      'oauth_token' => $oauth_token,
      'oauth_token_secret' => $oauth_token_secret,
    ]);

    // Gets the permanent access token.
    $this->accessToken = $sdk->oauth('oauth/access_token', [
      'oauth_verifier' => $this->request->get('oauth_verifier'),
    ]);

    $this->authManager->setAccessToken([
      'oauth_token' => $this->accessToken['oauth_token'],
      'oauth_token_secret' => $this->accessToken['oauth_token_secret'],
    ]);

    // Get user's profile from Twitter API.
    if (!($profile = $this->authManager->getProfile()) || !$this->authManager->getAccountId()) {
      drupal_set_message($this->t('@network login failed, could not load @network profile. Contact the site administrator.', [
        '@network' => $this->t('Twitter'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    return $profile;
  }

}
