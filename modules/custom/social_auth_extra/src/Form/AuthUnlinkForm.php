<?php

namespace Drupal\social_auth_extra\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Class AuthUnlinkForm
 * @package Drupal\social_auth_extra\Form
 */
class AuthUnlinkForm extends ConfirmFormBase {

  /**
   * Social network definition.
   *
   * @var array
   */
  protected $socialNetwork;

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Unlink');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->socialNetwork['id'] . '_auth_unlink_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Unlink @network', [
      '@network' => $this->socialNetwork['social_network'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('If you unlink your @network account, you are no longer able to use @network for social log in.', [
      '@network' => $this->socialNetwork['social_network'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.user.edit_form', [
      'user' => $this->currentUser()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = User::load($this->currentUser()->id());
    $network_manager = \Drupal::service('plugin.network.manager');
    $is_connected = FALSE;

    foreach ($network_manager->getDefinitions() as $key => $definition) {
      /** @var \Drupal\social_auth_extra\UserManagerInterface $user_manager */
      $user_manager = \Drupal::service($definition['id'] . '.user_manager');
      $user_manager->setAccount($account);

      if ($definition['id'] !== $this->socialNetwork['id'] && $user_manager->getAccountId()) {
        $is_connected = TRUE;
        break;
      }
    }

    $user_manager = \Drupal::service($this->socialNetwork['id'] . '.user_manager');
    $user_manager->setAccount($account);
    $user_manager->setAccountId(NULL);
    $account->save();

    if ($is_connected) {
      drupal_set_message($this->t('Your @network account is unlinked. You can still log in with your @community_name account.', [
        '@network' => $this->socialNetwork['social_network'],
        '@community_name' => \Drupal::config('system.site')->get('name'),
      ]));
    }
    else {
      drupal_set_message($this->t('Your @network account is unlinked. Make sure you set a password or connect another social platform. Please enter a password to be able to continue using @community_name.', [
        '@network' => $this->socialNetwork['social_network'],
        '@community_name' => \Drupal::config('system.site')->get('name'),
      ]), 'warning');
    }

    $form_state->setRedirect('entity.user.edit_form', [
      'user' => $this->currentUser()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $network = NULL) {
    $network_manager = \Drupal::service('plugin.network.manager');
    $definitions = $network_manager->getDefinitions();

    foreach ($definitions as $definition) {
      $instance = $network_manager->createInstance($definition['id']);

      if ($network == $instance->getSocialNetworkKey())  {
        $this->socialNetwork = $definition;
        break;
      }
    }

    return parent::buildForm($form, $form_state);
  }

}
