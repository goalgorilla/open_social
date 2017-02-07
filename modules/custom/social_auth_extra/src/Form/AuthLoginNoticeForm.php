<?php

namespace Drupal\social_auth_extra\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class AuthLoginNoticeForm
 * @package Drupal\social_auth_extra\Form
 */
class AuthLoginNoticeForm extends ConfirmFormBase {

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
    return $this->t('Create new account');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->socialNetwork['id'] . '_auth_login_notice_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Log in');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('There is no account connected to this @network account. You can create new account.', [
      '@network' => $this->socialNetwork['social_network'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('user.login');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->socialNetwork['id'] . '.user_register');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $network = NULL) {
    $network_manager = \Drupal::service('plugin.network.manager');
    $definitions = $network_manager->getDefinitions();

    foreach ($definitions as $definition) {
      $instance = $network_manager->createInstance($definition['id']);

      if ($network == $instance->getSocialNetworkKey()) {
        $this->socialNetwork = $definition;
        break;
      }
    }

    return parent::buildForm($form, $form_state);
  }

}
