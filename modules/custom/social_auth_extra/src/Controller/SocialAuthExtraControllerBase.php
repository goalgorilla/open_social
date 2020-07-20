<?php

namespace Drupal\social_auth_extra\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth_extra\AuthManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialAuthExtraControllerBase.
 *
 * @package Drupal\social_auth_extra\Controller
 */
abstract class SocialAuthExtraControllerBase extends ControllerBase {

  /**
   * The network manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * The auth manager.
   *
   * @var \Drupal\social_auth_extra\AuthManagerInterface
   */
  protected $authManager;

  /**
   * Contains instance of PHP Library.
   *
   * @var object
   */
  protected $sdk;

  /**
   * SocialAuthExtraControllerBase constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   The network manager.
   * @param \Drupal\social_auth_extra\AuthManagerInterface $auth_manager
   *   The auth manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    NetworkManager $network_manager,
    AuthManagerInterface $auth_manager,
    MessengerInterface $messenger
  ) {
    $this->networkManager = $network_manager;
    $this->authManager = $auth_manager;
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get(explode('\\', get_called_class())[1] . '.auth_manager'),
      $container->get('messenger')
    );
  }

  /**
   * Returns the module name.
   *
   * @return string
   *   The module name.
   */
  protected function getModuleName() {
    return explode('\\', get_called_class())[1];
  }

}
