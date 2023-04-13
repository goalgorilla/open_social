<?php

declare(strict_types = 1);

namespace Drupal\social_core\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Determines if the route is owned by an entities link template.
 */
class EntityRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Map of route matches to entity keyed by route name.
   *
   * @var array<string, \Drupal\Core\Entity\EntityInterface|null>
   */
  protected array $routeMatchedEntity = [];

  /**
   * Name of context variable.
   */
  protected const CANONICAL_ENTITY = 'canonical_entity';

  /**
   * Name of context variable.
   */
  protected const CANONICAL_CONTENT_ENTITY = 'canonical_content_entity';

  /**
   * Constructs a new GroupRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids): array {
    $contexts = [];

    foreach ($unqualified_context_ids as $unqualified_context_id) {
      $filter_for_content_entity = $unqualified_context_id === self::CANONICAL_CONTENT_ENTITY;
      $entity = $this->getRouteMatchEntity($this->currentRouteMatch, $filter_for_content_entity);
      $context_definition = (
      $entity
        ? EntityContextDefinition::create($entity->getEntityTypeId())
        : (new ContextDefinition('entity'))
      )
        ->setRequired(FALSE);

      $cacheability = new CacheableMetadata();
      if ($entity !== NULL) {
        $cacheability->addCacheableDependency($entity);
      }
      $cacheability->setCacheContexts(['route']);

      $contexts[$unqualified_context_id] = (new Context($context_definition, $entity))
        ->addCacheableDependency(clone $cacheability);
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts(): array {
    $contexts = [];
    // \Drupal\Core\Plugin\Context\ContextDefinition::dataTypeMatches allows us
    // to provide a generic 'entity', it will match on both 'entity' and more
    // specific types like 'entity:node'.
    $contextDefinition = new ContextDefinition('entity', 'Entity from URL', FALSE);
    $context = new Context($contextDefinition);
    $contexts[static::CANONICAL_ENTITY] = $context;

    // We duplicate the context but allow matching only on content entities.
    $contextDefinition = new ContextDefinition('entity', 'Content Entity from URL', FALSE);
    $context = new Context($contextDefinition);
    $contexts[static::CANONICAL_CONTENT_ENTITY] = $context;

    return $contexts;
  }

  /**
   * Determines entity for a route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   A route match.
   * @param bool $filter_for_content_entity
   *   If TRUE will filter on content entities only, otherwise will allow any
   *   entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity for the provided route match, or NULL if the route is note an
   *   entity template.
   */
  protected function getRouteMatchEntity(RouteMatchInterface $routeMatch, bool $filter_for_content_entity = FALSE): ?EntityInterface {
    $routeName = $routeMatch->getRouteName();
    if (!$routeName) {
      return NULL;
    }

    if (array_key_exists($routeName, $this->routeMatchedEntity)) {
      return $this->routeMatchedEntity[$routeName];
    }

    // We want to be able to work on routes that may not be part of an entity
    // template (e.g. a view filtering on a group) so we just return the first
    // matched entity.
    $match_for = $filter_for_content_entity ? ContentEntityInterface::class : EntityInterface::class;
    foreach ($this->currentRouteMatch->getParameters() as $parameter) {
      if ($parameter instanceof $match_for) {
        return $this->routeMatchedEntity[$routeName] = $parameter;
      }
    }

    return $this->routeMatchedEntity[$routeName] = NULL;
  }

}
