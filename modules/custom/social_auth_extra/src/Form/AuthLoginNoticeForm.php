<?php

namespace Drupal\social_auth_extra\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_api\Plugin\NetworkManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AuthLoginNoticeForm.
 *
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
   * The network manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * AuthLoginNoticeForm constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   The network manager.
   */
  public function __construct(NetworkManager $network_manager) {
    $this->networkManager = $network_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager')
    );
  }

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
    $definitions = $this->networkManager->getDefinitions();

    foreach ($definitions as $definition) {
      $instance = $this->networkManager->createInstance($definition['id']);

      if ($network == $instance->getSocialNetworkKey()) {
        $this->socialNetwork = $definition;
        break;
      }
    }

    return parent::buildForm($form, $form_state);
  }

}
