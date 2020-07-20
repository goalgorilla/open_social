<?php

namespace Drupal\social_auth_extra\Controller;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AuthControllerBase.
 *
 * @package Drupal\social_auth_extra\Controller
 */
abstract class AuthControllerBase extends SocialAuthExtraControllerBase {

  /**
   * Response for path 'user/register/*'.
   *
   * Redirects the user to social network for registration.
   */
  public function userRegister() {
    return $this->getRedirectResponse('register');
  }

  /**
   * Returns the redirect response.
   *
   * @param string $type
   *   Type of action, "login" or "register".
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a RedirectResponse.
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
   * Returns the SDK instance or RedirectResponse when error occurred.
   *
   * @param string $type
   *   Type of action, "login" or "register".
   *
   * @return object|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns an instance of the SDK or a Redirect Response.
   */
  public function getSdk($type) {
    if ($this->sdk) {
      return $this->sdk;
    }

    /** @var \Drupal\social_auth_extra\Plugin\Network\NetworkExtraInterface $network_manager */
    $network_manager = $this->networkManager->createInstance($this->getModuleName());

    if (!$network_manager->isActive()) {
      $this->messenger()->addError($this->t('@network is disabled. Contact the site administrator', [
        '@network' => $network_manager->getPluginDefinition()['social_network'],
      ]));

      return $this->redirect('user.' . $type);
    }

    if (!$this->sdk = $network_manager->getSdk()) {
      $this->messenger()->addError($this->t('@network Auth not configured properly. Contact the site administrator.', [
        '@network' => $network_manager->getPluginDefinition()['social_network'],
      ]));

      return $this->redirect('user.' . $type);
    }

    return $this->sdk;
  }

}
