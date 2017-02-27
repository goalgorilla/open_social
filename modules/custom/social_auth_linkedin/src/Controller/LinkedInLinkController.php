<?php

namespace Drupal\social_auth_linkedin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth_linkedin\LinkedInAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LinkedInLinkController extends ControllerBase {

  protected $networkManager;
  protected $authManager;

  /**
   * LinkedInLinkController constructor.
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
   * Response for path 'social-api/link/linkedin'.
   *
   * Redirects the user to LinkedIn for joining accounts.
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

    // Get the OAuth token from LinkedIn.
    if (!$access_token = $this->authManager->getAccessToken('link')) {
      drupal_set_message($this->t('@network login failed. Token is not valid.', [
        '@network' => $this->t('LinkedIn'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    // Get user's LinkedIn profile from LinkedIn API.
    if (!($profile = $this->authManager->getProfile()) || !isset($profile['id'])) {
      drupal_set_message($this->t('@network login failed, could not load @network profile. Contact site administrator.', [
        '@network' => $this->t('LinkedIn'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    // Check whether any another user account already connected.
    $account = $this->entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['linkedin_id' => $profile['id']]);
    $account = current($account);

    // Check whether another account was connected to this LinkedIn account.
    if ($account && (int) $account->id() !== (int) $this->currentUser()->id()) {
      drupal_set_message($this->t('Your @network account has already connected to another account on this site.', [
        '@network' => $this->t('LinkedIn'),
      ]), 'warning');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    $account = User::load($this->currentUser()->id());
    $account->get('linkedin_id')->setValue($profile['id']);
    $account->save();

    drupal_set_message($this->t('You are now able to log in with @network', [
      '@network' => $this->t('LinkedIn'),
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
    $network_manager = $this->networkManager->createInstance('social_auth_linkedin');

    if (!$network_manager->isActive()) {
      drupal_set_message($this->t('@network is disallowed. Contact site administrator.', [
        '@network' => $this->t('LinkedIn'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    if (!$sdk = $network_manager->getSdk()) {
      drupal_set_message($this->t('@network Auth not configured properly. Contact site administrator.', [
        '@network' => $this->t('LinkedIn'),
      ]), 'error');
      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    return $sdk;
  }

}
