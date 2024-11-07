<?php

declare(strict_types=1);

namespace Drupal\social_event_managers\EventSubscriber;

use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\social_search\Utility\SocialSearchApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add search api query alters for events with managers.
 */
class SearchApiNodeQueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a SearchApiQueryNodeSubscriber object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

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
      $this->logger->error(sprintf('Required search api query tag "%s" can not be found in %s', 'social_entity_type_node_access', __METHOD__));
      return;
    }

    $account = $query->getOption('social_search_access_account');

    // Don't do anything if the user can access all content.
    if ($account->hasPermission('bypass node access')) {
      return;
    }

    /* @see \Drupal\social_event_managers\Plugin\search_api\processor\AddNodeFields */
    $field_event_managers = SocialSearchApi::searchApiFindField($query->getIndex(), 'entity:node', 'field_event_managers');
    if (!$field_event_managers) {
      // The field doesn't exist in the index.
      return;
    }

    $or->addCondition($field_event_managers->getFieldIdentifier(), $account->id());
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
      /* @see \Drupal\social_node\EventSubscriber\SearchApiQueryNodeAccessSubscriber::addTaggedQueryConditions() */
      SearchApiEvents::QUERY_PRE_EXECUTE . '.social_entity_type_node_access' => 'searchApiNodeQueryAccess',
    ];
  }

}
