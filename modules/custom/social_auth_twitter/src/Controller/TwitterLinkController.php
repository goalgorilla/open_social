<?php

namespace Drupal\social_auth_twitter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth_twitter\TwitterAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TwitterLinkController extends ControllerBase {

  protected $networkManager;
  protected $authManager;
  protected $request;

  /**
   * TwitterLinkController constructor.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   * @param \Drupal\social_auth_twitter\TwitterAuthManager $auth_manager
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
   * Response for path 'social-api/link/linkedin'.
   *
   * Redirects the user to Twitter for joining accounts.
   */
  public function linkAccount() {
    $sdk = $this->getSdk();

    if ($sdk instanceof RedirectResponse) {
      return $sdk;
    }

    $this->authManager->setSdk($sdk);
    $url = $this->authManager->getAuthenticationUrl('link');

    return new TrustedRedirectResponse($url);
  }

  /**
   * Makes joining between account on this site and account on social network.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function linkAccountCallback() {
    $sdk = $this->getSdk();

    if ($sdk instanceof RedirectResponse) {
      return $sdk;
    }

    $network_manager = $this->networkManager->createInstance('social_auth_twitter');
    $data_handler = $network_manager->getDataHandler();

    // Get the Twitter OAuth token from storage.
    if (!($oauth_token = $data_handler->get('oauth_token')) || !($oauth_token_secret = $data_handler->get('oauth_token_secret'))) {
      drupal_set_message($this->t('Twitter login failed. Token or Token Secret is not valid.'), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    $this->authManager->setSdk($sdk);
    $this->authManager->setAccessToken([
      'oauth_token' => $oauth_token,
      'oauth_token_secret' => $oauth_token_secret,
    ]);

    // Get the permanent access token.
    $access_token = $sdk->oauth('oauth/access_token', [
      'oauth_verifier' => $this->request->get('oauth_verifier'),
    ]);

    $this->authManager->setAccessToken([
      'oauth_token' => $access_token['oauth_token'],
      'oauth_token_secret' => $access_token['oauth_token_secret'],
    ]);

    // Get user's profile from Twitter API.
    if (!($profile = $this->authManager->getProfile()) || !($account_id = $this->authManager->getAccountId())) {
      drupal_set_message($this->t('@network login failed, could not load @network profile. Contact site administrator.', [
        '@network' => $this->t('Twitter'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    // Check whether any another user account already connected.
    $account = $this->entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['twitter_id' => $account_id]);
    $account = current($account);

    // Check whether another account was connected to this Twitter account.
    if ($account && (int) $account->id() !== (int) $this->currentUser()->id()) {
      drupal_set_message($this->t('Your @network account has already connected to another account on this site.', [
        '@network' => $this->t('Twitter'),
      ]), 'warning');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    $account = User::load($this->currentUser()->id());
    $account->get('twitter_id')->setValue($account_id);
    $account->save();

    drupal_set_message($this->t('You are now able to log in with @network', [
      '@network' => $this->t('Twitter'),
    ]));
    return $this->redirect('entity.user.edit_form', [
      'user' => $account->id(),
    ]);
  }

  /**
   * Returns the SDK instance or RedirectResponse when error occurred.
   *
   * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function getSdk() {
    $network_manager = $this->networkManager->createInstance('social_auth_twitter');

    if (!$network_manager->isActive()) {
      drupal_set_message($this->t('@network is disallowed. Contact site administrator.', [
        '@network' => $this->t('Twitter'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    if (!$sdk = $network_manager->getSdk()) {
      drupal_set_message($this->t('@network Auth not configured properly. Contact site administrator.', [
        '@network' => $this->t('Twitter'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    return $sdk;
  }

}
