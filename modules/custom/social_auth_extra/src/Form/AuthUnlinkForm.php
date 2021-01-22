<?php

namespace Drupal\social_auth_extra\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AuthUnlinkForm.
 *
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
   * The network manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * AuthUnlinkForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   The network manager.
   */
  public function __construct(ConfigFactory $config_factory, NetworkManager $network_manager) {
    $this->networkManager = $network_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.network.manager')
    );
  }

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
    $is_connected = FALSE;

    foreach ($this->networkManager->getDefinitions() as $definition) {
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
      $this->messenger()->addStatus($this->t('Your @network account is unlinked. You can still log in with your @community_name account.', [
        '@network' => $this->socialNetwork['social_network'],
        '@community_name' => $this->configFactory->get('system.site')->get('name'),
      ]));
    }
    else {
      $this->messenger()->addWarning($this->t('Your @network account is unlinked. Make sure you set a password or connect another social platform. Please enter a password to be able to continue using @community_name.', [
        '@network' => $this->socialNetwork['social_network'],
        '@community_name' => $this->configFactory->get('system.site')->get('name'),
      ]));
    }

    $form_state->setRedirect('entity.user.edit_form', [
      'user' => $this->currentUser()->id(),
    ]);
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
