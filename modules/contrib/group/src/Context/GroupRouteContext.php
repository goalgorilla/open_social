<?php

/**
 * @file
 * Contains \Drupal\group\Context\GroupRouteContext.
 */

namespace Drupal\group\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current group as a context on group routes.
 */
class GroupRouteContext implements ContextProviderInterface {

  use GroupRouteContextTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new GroupRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   */
  public function __construct(RouteMatchInterface $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Create an optional context definition for group entities.
    $context_definition = new ContextDefinition('entity:group', NULL, FALSE);

    // Cache this context on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    // Create a context from the definition and retrieved or created group.
    $context = new Context($context_definition, $this->getGroupFromRoute());
    $context->addCacheableDependency($cacheability);

    return ['group' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:group', $this->t('Group from URL')));
    return ['group' => $context];
  }

}
