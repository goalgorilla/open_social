<?php

namespace Drupal\social_auth_google\Controller;

use Drupal\social_auth_extra\Controller\AuthControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class GoogleAuthController.
 *
 * @package Drupal\social_auth_google\Controller
 */
class GoogleAuthController extends AuthControllerBase {

  /**
   * Contains access token to work with API.
   *
   * @var string
   */
  protected $accessToken;

  /**
   * Response for path 'user/login/google'.
   *
   * Redirects the user to Google for authentication.
   */
  public function userLogin() {
    return $this->getRedirectResponse('login');
  }

  /**
   * Authorizes the user after redirect from Google.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a RedirectResponse.
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
        'google_id' => $profile->getId(),
      ]);

    if (!$account) {
      return $this->redirect('social_auth_google.user_login_notice');
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
   * Registers the new account after redirect from Google.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a RedirectResponse.
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
        'google_id' => $profile->getId(),
      ]);

    if ($account) {
      $account = current($account);

      if (!$account->get('status')->value) {
        drupal_set_message($this->t('You already have account on this site, but your account is blocked. Contact the site administrator.'), 'error');
        return $this->redirect('user.register');
      }

      // Authorize the user and redirect him to the front page.
      user_login_finalize($account);

      return $this->redirect('<front>');
    }

    // Save email and name to session to use for auto fill the registration
    // form.
    $data_handler = $this->networkManager->createInstance('social_auth_google')->getDataHandler();
    $data_handler->set('access_token', $this->accessToken);
    $data_handler->set('mail', $profile->getEmail());
    $data_handler->set('name', $profile->getName());

    drupal_set_message($this->t('You are now connected with @network, please continue registration', [
      '@network' => $this->t('Google'),
    ]));

    return $this->redirect('user.register', [
      'provider' => 'google',
    ]);
  }

  /**
   * Loads access token, then loads profile.
   *
   * @param string $type
   *   The type.
   *
   * @return object
   *   Returns an object.
   */
  public function getProfile($type) {
    // Get the OAuth token from Google.
    if (!$access_token = $this->authManager->getAccessToken($type)) {
      drupal_set_message($this->t('@network login failed. Token is not valid.', [
        '@network' => $this->t('Google'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    // Get user's Google profile from Google API.
    if (!($profile = $this->authManager->getProfile()) || !($profile->getId())) {
      drupal_set_message($this->t('@network login failed, could not load @network profile. Contact the site administrator.', [
        '@network' => $this->t('Google'),
      ]), 'error');
      return $this->redirect('user.' . $type);
    }

    $this->accessToken = $access_token;

    return $profile;
  }

}
