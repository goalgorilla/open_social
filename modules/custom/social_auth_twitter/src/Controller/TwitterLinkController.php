<?php

namespace Drupal\social_auth_twitter\Controller;

use Drupal\social_auth_extra\Controller\SocialAuthExtraLinkControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class TwitterLinkController.
 *
 * @package Drupal\social_auth_twitter\Controller
 */
class TwitterLinkController extends SocialAuthExtraLinkControllerBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $controller */
    $controller = parent::create($container);

    $controller->request = $container->get('request_stack')->getCurrentRequest();

    return $controller;
  }

  /**
   * Makes joining between account on this site and account on social network.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A RedirectResponse pointing to the user edit form.
   */
  public function linkAccountCallback() {
    $sdk = $this->getSdk();

    if ($sdk instanceof RedirectResponse) {
      return $sdk;
    }

    /** @var \Drupal\social_auth_extra\Plugin\Network\NetworkExtraInterface $network_manager */
    $network_manager = $this->networkManager->createInstance($this->getModuleName());

    $data_handler = $network_manager->getDataHandler();

    // Get the Twitter OAuth token from storage.
    if (
      !($oauth_token = $data_handler->get('oauth_token')) ||
      !($oauth_token_secret = $data_handler->get('oauth_token_secret'))
    ) {
      $this->messenger()->addError($this->t('Twitter login failed. Token or Token Secret is not valid.'));

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
    if (!($this->authManager->getProfile()) || !($account_id = $this->authManager->getAccountId())) {
      $this->messenger()->addError($this->t('@network login failed, could not load @network profile. Contact site administrator.', [
        '@network' => $this->t('Twitter'),
      ]));

      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    $storage = $this->entityTypeManager()->getStorage('user');

    // Check whether any another user account already connected.
    $accounts = $storage->loadByProperties(['twitter_id' => $account_id]);

    if ($accounts) {
      $account = current($accounts);

      // Check whether another account was connected to this Twitter account.
      if ((int) $account->id() !== (int) $this->currentUser()->id()) {
        $this->messenger()->addWarning($this->t('Your @network account has already connected to another account on this site.', [
          '@network' => $this->t('Twitter'),
        ]));

        return $this->redirect('entity.user.edit_form', [
          'user' => $this->currentUser()->id(),
        ]);
      }
    }

    $account = $storage->load($this->currentUser()->id());
    $account->get('twitter_id')->setValue($account_id);
    $account->save();

    $this->messenger()->addStatus($this->t('You are now able to log in with @network', [
      '@network' => $this->t('Twitter'),
    ]));

    return $this->redirect('entity.user.edit_form', [
      'user' => $account->id(),
    ]);
  }

}
