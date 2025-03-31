<?php

namespace Drupal\social_node\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\social_search\Plugin\search_api\SocialSearchSearchApiProcessorTrait;
use Drupal\social_search\Utility\SocialSearchApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Altering a search api query for nodes.
 *
 *   This processor takes care of displaying only "published" nodes or nodes
 *   with the current user as an owner (when appropriate permission is granted).
 *   Additionally, the processor handles access to nodes with
 *   "public" and "community" visibilities.
 *
 * @SearchApiProcessor(
 *   id = "social_node_query_alter",
 *   label = @Translation("Social Node: Search Api query alter for nodes"),
 *   description = @Translation("Alter node type and node type access query conditions groups."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_query" = 100,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SearchApiQueryAlter extends ProcessorPluginBase {

  use LoggerTrait;
  use SocialSearchSearchApiProcessorTrait;

  /**
   * Constructs an "SearchApiQueryAlter" object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityFieldManagerInterface $entityFieldManager,
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
      $container->get('entity_field.manager'),
    );
  }

  /**
   * Returns the entity type field names list should be added to the index.
   *
   * @return array
   *   The field names list with additional settings (type, etc.) associated
   *   by entity type (node, post, etc.).
   */
  public static function getIndexData(): array {
    return [
      'node' => [
        'status' => ['type' => 'boolean'],
        'uid' => ['type' => 'integer'],
        'type' => ['type' => 'string'],
        'field_content_visibility' => ['type' => 'string'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query): void {
    $this->searchApiNodeQueryAlter($query);
    $this->searchApiNodeQueryAccessAlter($query);
  }

  /**
   * Alter a search api query for "node" entity type.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The object representing the query to be executed.
   */
  protected function searchApiNodeQueryAlter(QueryInterface $query): void {
    /* @see \Drupal\social_search\Plugin\search_api\processor\TaggingQuery::preprocessSearchQuery() */
    $and = SocialSearchApi::findTaggedQueryConditionsGroup('social_entity_type_node', $query->getConditionGroup());
    if (!$and instanceof ConditionGroupInterface) {
      return;
    }

    $account = $query->getOption('social_search_access_account');

    // Don't do anything if the user can access all content.
    if ($account->hasPermission('bypass node access')) {
      return;
    }

    if ($account->hasPermission('view any unpublished content')) {
      return;
    }

    $author = $this->findField('entity:node', 'uid', 'integer');
    $status = $this->findField('entity:node', 'status', 'boolean');
    if (!$author instanceof FieldInterface || !$status instanceof FieldInterface) {
      // The required fields don't exist in the index.
      return;
    }

    if (!$account->hasPermission('access content')) {
      // User doesn't have permission to see content.
      // Denied access to all nodes.
      $and->addCondition($author->getFieldIdentifier(), -1);
      return;
    }

    // Either published or nodes with the current user ownership.
    $published_or_owner = $query->createConditionGroup('OR');

    // If this is a comment datasource, or users cannot view their own
    // unpublished nodes, a simple filter on "status" is enough. Otherwise,
    // it's a bit more complicated.
    $published_or_owner->addCondition($status->getFieldIdentifier(), TRUE);

    if ($account->hasPermission('view own unpublished content')) {
      $published_or_owner->addCondition($author->getFieldIdentifier(), $account->id());
    }

    $and->addConditionGroup($published_or_owner);
  }

  /**
   * Alter a search api query for "node" entity type access.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The object representing the query to be executed.
   */
  protected function searchApiNodeQueryAccessAlter(QueryInterface $query): void {
    /* @see \Drupal\social_search\Plugin\search_api\processor\TaggingQuery::preprocessSearchQuery() */
    $or = SocialSearchApi::findTaggedQueryConditionsGroup('social_entity_type_node_access', $query->getConditionGroup());
    if (!$or instanceof ConditionGroupInterface) {
      return;
    }

    // Check if we can skip access check for this condition.
    if (SocialSearchApi::skipAccessCheck($or)) {
      return;
    }

    $type = $this->findField('entity:node', 'type');
    $visibility_field = $this->findField('entity:node', 'field_content_visibility');
    if (!$type instanceof FieldInterface || !$visibility_field instanceof FieldInterface) {
      // The required fields don't exist in the index.
      return;
    }

    $account = $query->getOption('social_search_access_account');
    // Allow access to all content if user has appropriate permission.
    if ($account->hasPermission('bypass node access')) {
      // We need explicitly to allow access to any content otherwise
      // other modules can add their own sub-conditions,
      // and our specific bypass check will be ignored.
      $or->addCondition($visibility_field->getFieldIdentifier(), (string) SocialSearchApi::BYPASS_VALUE, '<>');
      // Add bypass tag to allow other modules check and skip conditions
      // building for grand users.
      SocialSearchApi::applyBypassAccessTag($or);
      return;
    }

    // Get all node types where we have visibility field.
    $field_storage = FieldStorageConfig::loadByName('node', 'field_content_visibility');
    $bundles = (array) $field_storage?->getBundles();

    foreach ($bundles as $bundle) {
      if ($account->hasPermission("edit any $bundle content")) {
        $or->addCondition($type->getFieldIdentifier(), $bundle);
      }

      foreach (['public', 'community'] as $visibility) {
        if ($account->hasPermission("view node.$bundle.field_content_visibility:$visibility content")) {
          $condition = $query->createConditionGroup()
            ->addCondition($type->getFieldIdentifier(), $bundle)
            ->addCondition($visibility_field->getFieldIdentifier(), $visibility);

          $or->addConditionGroup($condition);
        }
      }
    }
  }

}
