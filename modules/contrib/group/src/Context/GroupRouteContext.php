<?php

/**
 * @file
 * Contains \Drupal\group\Context\GroupRouteContext.
 */

namespace Drupal\group\Context;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
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

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new GroupRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $value = NULL;

    // Create an optional context definition for group entities.
    $context_definition = new ContextDefinition('entity:group', NULL, FALSE);

    // See if the route has a group parameter and try to retrieve it.
    if (($group = $this->routeMatch->getParameter('group')) && $group instanceof GroupInterface) {
      $value = $group;
    }
    // Create a new group to use as context if on the group add form.
    elseif ($this->routeMatch->getRouteName() == 'entity.group.add_form') {
      $group_type = $this->routeMatch->getParameter('group_type');
      $value = Group::create(['type' => $group_type->id()]);
    }

    // Cache this context on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    // Create a context from the definition and retrieved or created group.
    $context = new Context($context_definition, $value);
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
