<?php

namespace Drupal\social_user\ContextProvider;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\user\UserInterface;

/**
 * Class UserRouteContext.
 *
 * @package Drupal\social_user\ContextProvider
 */
class UserRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The current route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new UserRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRouteMatch = $current_route_match;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Create an optional context definition for group entities.
    $context_definition = EntityContextDefinition::fromEntityTypeId('user')
      ->setRequired(FALSE);

    // Cache this context per group on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    // Create a context from the definition and retrieved or created group.
    $context = new Context($context_definition, $this->getUserFromRoute());
    $context->addCacheableDependency($cacheability);

    return [
      'user' => $context,
    ];
  }

  /**
   * Retrieves the user entity from the current route.
   *
   * This will try to load the user entity from the route if present.
   *
   * @return \Drupal\user\UserInterface|null
   *   A user entity if one could be found, NULL otherwise.
   */
  public function getUserFromRoute() {
    $route_match = $this->currentRouteMatch;

    // See if the route has a user parameter and try to retrieve it.
    if (($account = $route_match->getParameter('user'))) {
      if ($account instanceof UserInterface) {
        return $account;
      }
      elseif (is_numeric($account)) {
        $account = $this->userStorage->load($account);

        if ($account instanceof UserInterface) {
          return $account;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return [
      'social_user' => EntityContext::fromEntityTypeId('user', $this->t('Social User entity from URL')),
    ];
  }

}
