<?php

namespace Drupal\social_auth_extra\Controller;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SocialAuthExtraLinkControllerBase.
 *
 * @package Drupal\social_auth_extra\Controller
 */
class SocialAuthExtraLinkControllerBase extends SocialAuthExtraControllerBase {

  /**
   * Response for path 'social-api/link/*'.
   *
   * Redirects the user to LinkedIn for joining accounts.
   */
  public function linkAccount() {
    $this->sdk = $this->getSdk();

    if ($this->sdk instanceof RedirectResponse) {
      return $this->sdk;
    }

    $this->authManager->setSdk($this->sdk);
    $url = $this->authManager->getAuthenticationUrl('link');

    return new TrustedRedirectResponse($url);
  }

  /**
   * Makes joining between account on this site and account on social network.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A RedirectResponse pointing to the user edit form.
   */
  public function linkAccountCallback() {
    $this->sdk = $this->getSdk();

    if ($this->sdk instanceof RedirectResponse) {
      return $this->sdk;
    }

    $this->authManager->setSdk($this->sdk);

    /** @var \Drupal\social_auth_extra\Plugin\Network\NetworkExtraInterface $network_manager */
    $network_manager = $this->networkManager->createInstance($this->getModuleName());

    $definition = $network_manager->getPluginDefinition();
    $args = ['@network' => $definition['social_network']];

    // Get the OAuth token.
    if (!$this->authManager->getAccessToken('link')) {
      $this->messenger()->addError($this->t('@network login failed. Token is not valid.', $args));

      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    // Get user's LinkedIn profile from LinkedIn API.
    if (!($profile = $this->authManager->getProfile()) || !isset($profile['id'])) {
      $this->messenger()->addError($this->t('@network login failed, could not load @network profile. Contact site administrator.', $args));

      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    $storage = $this->entityTypeManager()->getStorage('user');
    $field_name = explode('_', $definition['id']) . '_id';

    // Check whether any another user account already connected.
    $accounts = $storage->loadByProperties([$field_name => $profile['id']]);

    if ($accounts) {
      $account = current($accounts);

      // Check whether another account was connected to this LinkedIn account.
      if ((int) $account->id() !== (int) $this->currentUser()->id()) {
        $this->messenger()->addWarning($this->t('Your @network account has already connected to another account on this site.', $args));

        return $this->redirect('entity.user.edit_form', [
          'user' => $this->currentUser()->id(),
        ]);
      }
    }

    $account = $storage->load($this->currentUser()->id());
    $account->get($field_name)->setValue($profile['id']);
    $account->save();

    $this->messenger()->addStatus($this->t('You are now able to log in with @network', $args));

    return $this->redirect('entity.user.edit_form', [
      'user' => $account->id(),
    ]);
  }

  /**
   * Returns the SDK instance or RedirectResponse when error occurred.
   *
   * @return object|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Can return an SDK instance or a RedirectResponse to the user edit form.
   */
  public function getSdk() {
    if ($this->sdk) {
      return $this->sdk;
    }

    /** @var \Drupal\social_auth_extra\Plugin\Network\NetworkExtraInterface $network_manager */
    $network_manager = $this->networkManager->createInstance($this->getModuleName());

    if (!$network_manager->isActive()) {
      $this->messenger()->addError($this->t('@network is disallowed. Contact site administrator.', [
        '@network' => $network_manager->getPluginDefinition()['social_network'],
      ]));

      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    if (!$this->sdk = $network_manager->getSdk()) {
      $this->messenger()->addError($this->t('@network Auth not configured properly. Contact site administrator.', [
        '@network' => $network_manager->getPluginDefinition()['social_network'],
      ]));

      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    return $this->sdk;
  }

}
