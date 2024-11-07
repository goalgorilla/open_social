<?php

declare(strict_types=1);

namespace Drupal\social_node\EventSubscriber;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Event\GatheringPluginInfoEvent;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\social_search\Utility\SocialSearchApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add search api query alters for "node" entity type.
 */
class SearchApiNodeQueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a SearchApiQueryNodeAccessSubscriber object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Alter a search api query for "node" entity type.
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The Search API event.
   */
  public function searchApiNodeQuery(QueryPreExecuteEvent $event): void {
    $query = $event->getQuery();
    $and = SocialSearchApi::findTaggedQueryConditionsGroup('social_entity_type_node', $query->getConditionGroup());
    if (!$and instanceof ConditionGroupInterface) {
      $this->logger->error(sprintf('Required search api query tag "%s" can not be found in %s', 'social_entity_type_node', __METHOD__));
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

    /* @see \Drupal\social_node\Plugin\search_api\processor\AddNodeFields */
    $author = SocialSearchApi::searchApiFindField($query->getIndex(), 'entity:node', 'uid', 'integer');
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
    /* @see \Drupal\social_node\Plugin\search_api\processor\AddNodeFields */
    $status = SocialSearchApi::searchApiFindField($query->getIndex(), 'entity:node', 'status', 'boolean');
    $published_or_owner->addCondition($status->getFieldIdentifier(), TRUE);

    if ($account->hasPermission('view own unpublished content')) {
      $published_or_owner->addCondition($author, $account->id());
    }

    $and->addConditionGroup($published_or_owner);
  }

  /**
   * Alter a search api query for "node" entity type access.
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The Search API event.
   */
  public function searchApiNodeQueryAccess(QueryPreExecuteEvent $event): void {
    $query = $event->getQuery();

    $or = SocialSearchApi::findTaggedQueryConditionsGroup('social_entity_type_node_access', $query->getConditionGroup());
    if (!$or instanceof ConditionGroupInterface) {
      $this->logger->error(sprintf('Required search api query tag "%s" can not be found in %s', 'social_entity_type_node', __METHOD__));
      return;
    }

    $account = $query->getOption('social_search_access_account');

    // Don't do anything if the user can access all content.
    if ($account->hasPermission('bypass node access')) {
      return;
    }

    $type = SocialSearchApi::searchApiFindField($query->getIndex(), 'entity:node', 'type');
    $visibility_field = SocialSearchApi::searchApiFindField($query->getIndex(), 'entity:node', 'field_content_visibility');
    if (!$type || !$visibility_field) {
      // The required fields don't exist in the index.
      return;
    }

    // Get all node types where we have visibility field.
    $field_storage = FieldStorageConfig::loadByName('node', 'field_content_visibility');
    $bundles = $field_storage->getBundles();

    foreach ($bundles as $bundle) {
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

  /**
   * Hide "Content Access" search api processor.
   *
   *   Use query alters instead.
   *   @todo
   *
   * @param \Drupal\search_api\Event\GatheringPluginInfoEvent $event
   *   The processor plugin info alters event.
   */
  public function hideContentAccessProcessor(GatheringPluginInfoEvent $event): void {
    $processor_info = &$event->getDefinitions();
    if (!empty($processor_info['content_access'])) {
      $processor_info['content_access']['hidden'] = 'true';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Workaround to avoid a fatal error during site install in some cases.
    // @see https://www.drupal.org/project/facets/issues/3199156
    if (!class_exists(SearchApiEvents::class)) {
      return [];
    }

    return [
      SearchApiEvents::GATHERING_PROCESSORS => 'hideContentAccessProcessor',
      /* @see \Drupal\social_search\EventSubscriber\SearchApiTaggingQuerySubscriber::addTaggedQueryConditions() */
      SearchApiEvents::QUERY_PRE_EXECUTE . '.social_entity_type_node' => 'searchApiNodeQuery',
      SearchApiEvents::QUERY_PRE_EXECUTE . '.social_entity_type_node_access' => 'searchApiNodeQueryAccess',
    ];

  }

}
