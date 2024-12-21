<?php

namespace Drupal\social_profile\Plugin\views\access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control to user information page.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "profile_pages_access",
 *   title = @Translation("Profile pages access"),
 *   help = @Translation("Access to any profile page."),
 * )
 */
class SocialProfilePagesAccess extends AccessPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    // Get user from view context.
    $user = $this->view->getUser();
    if (!$user instanceof AccountProxyInterface) {
      return FALSE;
    }

    // Load profile from user and check the access for logged user.
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    $profile = $profile_storage->loadByUser($user->getAccount(), 'profile');
    if (!$profile instanceof ProfileInterface) {
      return FALSE;
    }

    return $profile->access('view', $account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route): void {
    $route->setRequirement('_custom_access', '\Drupal\social_profile\Service\SocialProfileAccessService::access');
  }

}
