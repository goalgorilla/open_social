<?php

namespace Drupal\social_tour;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SocialTourController.
 *
 * Returns responses for Social Group routes.
 *
 * @package Drupal\social_tour
 */
class SocialTourController extends ControllerBase {

  /**
   * The user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * SocialTourController constructor.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    UserDataInterface $user_data,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
    PathValidatorInterface $path_validator,
    RedirectDestinationInterface $redirect_destination,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    // We needs it.
    $this->userData = $user_data;
    $this->configFactory = $config_factory->get('social_tour.settings');
    $this->currentUser = $current_user;
    $this->pathValidator = $path_validator;
    $this->redirectDestination = $redirect_destination;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.data'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('path.validator'),
      $container->get('redirect.destination'),
      $container->get('entity_type.manager')
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
    if (!$this->configFactory->get('social_tour_enabled')) {
      return FALSE;
    }

    // Check permissions.
    if (!$this->currentUser->hasPermission('access tour')) {
      return FALSE;
    }

    // Check if current disabled it.
    if ($this->userData->get('social_tour', $this->currentUser->id(), 'onboarding_disabled')) {
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
    if ($account instanceof UserInterface) {
      $id = $account->id();
    }

    $new_value = TRUE;
    if ($this->userData->get('social_tour', $id, 'onboarding_disabled')) {
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
    $this->setData();

    $route_name = $this->pathValidator
      ->getUrlIfValid($this->redirectDestination->get())
      ->getRouteName();

    Cache::invalidateTags($this->getCacheTags($route_name));

    // Set a message that they can be turned on again.
    $this->messenger()->addStatus($this->t('You will not see tips like this anymore.'));

    // Return to previous page.
    return $this->redirect($route_name);
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

  /**
   * Returns tags based on tours of page.
   *
   * @param string $route_name
   *   A route name.
   *
   * @return array
   *   The cache tags.
   */
  public function getCacheTags($route_name) {
    $tours = $this->entityTypeManager->getStorage('tour')
      ->getQuery()
      ->condition('routes.*.route_name', $route_name)
      ->execute();

    return array_map(function ($tour) {
      return 'user:' . $this->currentUser->id() . ':tour:' . $tour;
    }, $tours);
  }

}
