<?php

namespace Drupal\social_profile\Service;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a service for Profile Access service.
 *
 * @package Drupal\social_profile\Service
 */
class SocialProfileAccessService implements ContainerInjectionInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * SocialProfileAccessService constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Validation permission for profile pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Logged user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Return TRUE when user has access.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(AccountInterface $account): AccessResultInterface {
    // Load the user entity.
    $user_id = $this->routeMatch->getRawParameter('user');
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($user_id);
    if (!$user instanceof UserInterface) {
      return AccessResult::forbidden();
    }

    // If user is blocked, check special permission.
    if ($user->isBlocked()) {
      return AccessResult::allowedIfHasPermissions($account, ['view blocked user']);
    }

    // Load the profile to check access.
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    $profile = $profile_storage->loadByUser($user, 'profile');
    if (!$profile instanceof ProfileInterface) {
      return AccessResult::forbidden();
    }

    return $profile->access('view', $account, TRUE);
  }

}
