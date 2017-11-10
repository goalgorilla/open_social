<?php

namespace Drupal\social_tour;

use Drupal\user\UserData;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SocialTourController.
 *
 * Returns responses for Social Group routes.
 *
 * @package Drupal\social_tour
 */
class SocialTourController extends ControllerBase {

  /**
   * Protected var UserData.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * Protected var ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Protected var for the current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * SocialTourController constructor.
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
   * Check if onboarding is enabled.
   *
   * @return bool
   *   Returns either TRUE or FALSE.
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
   * Toggle onboarding.
   *
   * @param array $account
   *   Array containing the account.
   */
  public function toggleOnboarding(array $account = NULL) {

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
    return new JsonResponse(['message' => $this->t('Onboarding has been enabled.')], 200, ['Content-Type' => 'application/json']);
  }

  /**
   * Disable onboarding for current_user by Ajax call.
   */
  public function disableOnboarding() {
    // Save the value in the user_data.
    $this->setData(TRUE);
    $redirect = \Drupal::request()->get('destination') ?: '/stream';
    // Set a message that they can be turned on again.
    drupal_set_message($this->t('You will not see tips like this anymore.'));
    // Return to Profile.
    return new RedirectResponse($redirect);
  }

  /**
   * Set onboarding data value.
   *
   * @param bool $disabled
   *   Type of bool, either TRUE or FALSE.
   */
  private function setData($disabled = TRUE) {
    $this->userData->set('social_tour', $this->currentUser->id(), 'onboarding_disabled', $disabled);
  }

}
