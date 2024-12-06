<?php

namespace Drupal\social_profile\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\social_profile\Service\SocialProfileAccessService;
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
   * Profile access service.
   *
   * @var \Drupal\social_profile\Service\SocialProfileAccessService
   */
  private SocialProfileAccessService $profileAccessService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    SocialProfileAccessService $profile_access_service,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->profileAccessService = $profile_access_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_profile.profile_pages_access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    return $this->profileAccessService->access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route): void {
    $route->setRequirement('_custom_access', 'social_profile.profile_access::access');
  }

}
