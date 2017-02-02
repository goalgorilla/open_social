<?php

namespace Drupal\social_auth_linkedin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\social_auth_linkedin\LinkedInAuthManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class LinkedInAuthController
 * @package Drupal\social_auth_linkedin\Controller
 */
class LinkedInAuthController extends ControllerBase {

  protected $networkManager;
  protected $authManager;

  /**
   * Contains access token to work with API.
   */
  protected $accessToken;

  /**
   * LinkedInAuthController constructor.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   * @param \Drupal\social_auth_linkedin\LinkedInAuthManager $auth_manager
   */
  public function __construct(NetworkManager $network_manager, LinkedInAuthManager $auth_manager) {
    $this->networkManager = $network_manager;
    $this->authManager = $auth_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth_linkedin.auth_manager')
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
   * Response for path 'user/login/linkedin'.
   *
   * Redirects the user to FB for authentication.
   */
  public function userLogin() {
    return $this->getRedirectResponse('login');
  }

  /**
   * Authorizes the user after redirect from LinkedIn.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function userLoginCallback() {
    $sdk = $this->getSdk('login');

    if ($sdk instanceof RedirectResponse) {
      return $sdk;
    }

    $this->authManager->setSdk($sdk);
    $profile = $this->getProfile('login');

    if ($profile instanceof RedirectResponse) {
      return $profile;
    }

    // Check whether user account exists.
    $account = $this->entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
        'linkedin_id' => $profile['id'],
      ]);

    if (!$account) {
      return $this->redirect('social_auth_linkedin.user_login_notice');
    }

    $account = current($account);

    if (!$account->get('status')->value) {
      drupal_set_message($this->t('Your account is blocked. Contact the site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Authorize the user and redirect him to the account page.
    user_login_finalize($account);

    return $this->redirect('entity.user.canonical', [
      'user' => $account->id(),
    ]);
  }

  /**
   * Response for path 'user/register/linkedin'.
   *
   * Redirects the user to LinkedIn for registration.
   */
  public function userRegister() {
    return $this->getRedirectResponse('register');
  }

  /**
   * Registers the new account after redirect from LinkedIn.
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
        'linkedin_id' => $profile['id']
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

    // Save email and name to session to use for auto fill the registration form.
    $data_handler = $this->networkManager->createInstance('social_auth_linkedin')->getDataHandler();
    $data_handler->set('access_token', $this->accessToken);
    $data_handler->set('mail', $profile['emailAddress']);
    $data_handler->set('name', $profile['formattedName']);

    drupal_set_message($this->t('You are now connected with @network, please continue registration', [
      '@network' => $this->t('LinkedIn'),
    ]));

    return $this->redirect('user.register', [
      'provider' => 'linkedin',
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
    $network_manager = $this->networkManager->createInstance('social_auth_linkedin');

    if (!$network_manager->isActive()) {
      drupal_set_message($this->t('@network is disabled. Contact the site administrator', [
        '@network' => $this->t('LinkedIn'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    if (!$sdk = $network_manager->getSdk()) {
      drupal_set_message($this->t('@network Auth not configured properly. Contact the site administrator.', [
        '@network' => $this->t('LinkedIn'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    return $sdk;
  }

  /**
   * Loads access token, then loads profile.
   *
   * @param string $type
   *
   * @return object
   */
  public function getProfile($type) {
    // Get the OAuth token from LinkedIn.
    if (!$access_token = $this->authManager->getAccessToken($type)) {
      drupal_set_message($this->t('@network login failed. Token is not valid.', [
        '@network' => $this->t('LinkedIn'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    // Get user's LinkedIn profile from LinkedIn API.
    if (!($profile = $this->authManager->getProfile()) || empty($profile['id'])) {
      drupal_set_message($this->t('@network login failed, could not load @network profile. Contact the site administrator.', [
        '@network' => $this->t('LinkedIn'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    $this->accessToken = $access_token;

    return $profile;
  }

}