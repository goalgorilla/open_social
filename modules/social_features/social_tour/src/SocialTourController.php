<?php

namespace Drupal\social_tour;

use Drupal\Component\Serialization\Json;
use Drupal\user\UserData;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Social Group routes.
 */
class SocialTourController extends ControllerBase {

  /** @var \Drupal\user\UserData $this->userData */
  protected $userData;

  /** @var \Drupal\Core\Config\ConfigFactory $configFactory */
  protected $configFactory;

  /** @var \Drupal\Core\Session\AccountProxy $currentUser */
  protected $currentUser;

  /**
   *
   * @param \Drupal\user\UserData $user_data
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Session\AccountProxy $current_user
   */
  public function __construct(UserData $user_data, ConfigFactory $config_factory, AccountProxy $current_user) {
    // We needs it.
    $this->userData = $user_data;
    $this->configFactory = $config_factory->get('social_tour.settings');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.data'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * @return bool
   */
  public function onboardingEnabled() {
    // Check if tour is enabled by SM setting.
    if ($this->configFactory->get('social_tour_enabled') == FALSE) {
      return FALSE;
    }

    // Check permissions.
    if (!$this->currentUser->hasPermission('access tour')) {
      return FALSE;
    }

    // Check if current disabled it.
    if ($this->userData->get('social_tour', $this->currentUser->id(), 'onboarding_disabled') == TRUE) {
     return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $account
   */
  public function toggleOnboarding($account = NULL) {

    // No user given, then current user.
    $id = $this->currentUser->id();
    if ($account instanceof User) {
      $id = $account->id();
    }

    $new_value = TRUE;
    if ($this->userData->get('social_tour', $id, 'onboarding_disabled') == TRUE) {
      $new_value = FALSE;
    }

    // Save the value in the user_data.
    $this->setData($new_value);
  }

  /**
   * Enable onboarding for current_user by Ajax call.
   */
  public function enableOnboarding() {
    // Save the value in the user_data.
    $this->setData(FALSE);
    // Return 200.
    return new JsonResponse(['message' => $this->t('Onboarding has been enabled.')], 200, ['Content-Type'=> 'application/json']);
  }

  /**
   * Disable onboarding for current_user by Ajax call.
   */
  public function disableOnboarding() {
    // Save the value in the user_data.
    $this->setData(TRUE);
    // Return 200.
    return new JsonResponse(['message' => $this->t('Onboarding has been disabled.')], 200, ['Content-Type'=> 'application/json']);
  }

  /**
   * Set onboarding data value.
   * @param bool $disabled
   */
  private function setData($disabled = TRUE) {
    $this->userData->set('social_tour', $this->currentUser->id(), 'onboarding_disabled', $disabled);
  }
}
