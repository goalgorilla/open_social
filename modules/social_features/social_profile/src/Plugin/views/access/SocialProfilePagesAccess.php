<?php

namespace Drupal\social_profile\Plugin\views\access;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_user\Controller\SocialUserController;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control to user information page.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "social_profile_pages_access",
 *   title = @Translation("Social profile's pages access"),
 *   help = @Translation("Access to any profile page."),
 * )
 */
class SocialProfilePagesAccess extends AccessPluginBase {

  /**
   * Constructs a new SocialProfilePagesAccess instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ClassResolverInterface $classResolver,
    protected RouteMatchInterface $routeMatch
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('class_resolver'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    $access = $this->classResolver
      ->getInstanceFromDefinition(SocialUserController::class)
      ->accessUsersPages($account, $this->routeMatch->getCurrentRouteMatch());

    return $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route): void {
    $route->setRequirement('_custom_access', SocialUserController::class . '::accessUsersPages');
  }

}
