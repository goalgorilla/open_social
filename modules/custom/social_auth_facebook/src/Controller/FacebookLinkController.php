<?php

namespace Drupal\social_auth_facebook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth_facebook\FacebookAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class FacebookLinkController
 * @package Drupal\social_auth_facebook\Controller
 */
class FacebookLinkController extends ControllerBase {

  protected $networkManager;
  protected $authManager;

  /**
   * FacebookAuthController constructor.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   * @param \Drupal\social_auth_facebook\FacebookAuthManager $auth_manager
   */
  public function __construct(NetworkManager $network_manager, FacebookAuthManager $auth_manager) {
    $this->networkManager = $network_manager;
    $this->authManager = $auth_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth_facebook.auth_manager')
    );
  }

  /**
   * Response for path 'social-api/link/facebook'.
   *
   * Redirects the user to Facebook for joining accounts.
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

    $this->authManager->setSdk($sdk);

    // Get the OAuth token from Facebook.
    if (!$access_token = $this->authManager->getAccessToken('link')) {
      drupal_set_message($this->t('@network login failed. Token is not valid.', [
        '@network' => $this->t('Facebook'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    // Get user's Facebook profile from Facebook API.
    if (!($profile = $this->authManager->getProfile()) || !($account_id = $profile->getField('id'))) {
      drupal_set_message($this->t('@network login failed, could not load @network profile. Contact site administrator.', [
        '@network' => $this->t('Facebook'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    // Check whether any another user account already connected.
    $account = $this->entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['facebook_id' => $account_id]);
    $account = current($account);

    // Check whether another account was connected to this Facebook account.
    if ($account && (int) $account->id() !== (int) $this->currentUser()->id()) {
      drupal_set_message($this->t('Your @network account has already connected to another account on this site.', [
        '@network' => $this->t('Facebook'),
      ]), 'warning');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    $account = User::load($this->currentUser()->id());
    $account->get('facebook_id')->setValue($account_id);
    $account->save();

    drupal_set_message($this->t('You are now able to log in with @network', [
      '@network' => $this->t('Facebook'),
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
    $network_manager = $this->networkManager->createInstance('social_auth_facebook');

    if (!$network_manager->isActive()) {
      drupal_set_message($this->t('@network is disallowed. Contact site administrator.', [
        '@network' => $this->t('Facebook'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    if (!$sdk = $network_manager->getSdk()) {
      drupal_set_message($this->t('@network Auth not configured properly. Contact site administrator.', [
        '@network' => $this->t('Facebook'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    return $sdk;
  }

}
