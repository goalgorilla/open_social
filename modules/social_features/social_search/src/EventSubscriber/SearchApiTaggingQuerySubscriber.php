<?php

declare(strict_types=1);

namespace Drupal\social_search\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tagging the search api queries for each entity type data source.
 */
class SearchApiTaggingQuerySubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new SearchApiTaggingQuerySubscriber object.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Create and tag search api query conditions for each entity type.
   *
   *   This method adds two query conditions group per entity type:
   *    1. "social_entity_type_{entity_type_id}" with AND clause;
   *    2. "social_entity_type_{entity_type_id}_access" with OR clause;
   *
   *   The first condition group allows you to alter condition per entity type.
   *   For example, you want to display entities for specific field value, so
   *   you can add appropriate condition, then AND clause will do the work.
   *
   *   The second condition group is a part of the first one. So, only items
   *   filtered by first condition will be additionally filtered by second one.
   *   We call it "access" conditions as it allows you to combine different
   *   access rules under one condition with OR clause.
   *
   *   The filter structure will looks like this:
   *    [any other search conditions]
   *    AND
   *    (
   *      (
   *        search_api_datasource = "node"
   *        AND
   *        (
   *          visibility = "public" if anonymous
   *          OR
   *          visibility = "community" if verified
   *           OR
   *          ... other access rules
   *         )
   *      )
   *      OR
   *      (
   *        search_api_datasource = "group"
   *        AND
   *        (
   *          visibility = "public" if anonymous
   *          OR
   *          visibility = "community" if verified
   *          OR
   *          ... other access rules
   *        )
   *      )
   *    )
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The Search API event.
   */
  public function addTaggedQueryConditions(QueryPreExecuteEvent $event): void {
    $query = $event->getQuery();
    // Skip any filtering when bypass access applied.
    if ($query->getOption('search_api_bypass_access')) {
      return;
    }

    $account = $query->getOption('search_api_access_account', $this->currentUser);
    if (is_numeric($account)) {
      $account = User::load($account);
    }

    if (!$account instanceof AccountInterface) {
      $account = $query->getOption('search_api_access_account');
      $this->logger
        ->warning('An illegal user UID was given for node access: @uid.', [
          '@uid' => is_scalar($account) ? $account : var_export($account, TRUE),
        ]);

      return;
    }

    // Put user to query, so farther alters could use it.
    $query->setOption('social_search_access_account', $account);

    $main_condition_group = $query->createAndAddConditionGroup('OR', ['social_search']);

    foreach ($query->getIndex()->getDatasources() as $datasource_id => $datasource) {
      /** @var \Drupal\search_api\Plugin\search_api\datasource\ContentEntity $datasource */
      // This condition group will be applied to each entity type.
      $entity_type_id = $datasource->getEntityTypeId();
      $by_datasource = $query->createConditionGroup('AND', [$entity_type_tag = "social_entity_type_$entity_type_id"]);
      $by_datasource->addCondition('search_api_datasource', $datasource_id);
      // Add tag to allow altering the query.
      $query->addTag($entity_type_tag);

      $entity_access_tag = "social_entity_type_{$entity_type_id}_access";
      // This condition group will be applied to each entity type access.
      $access = $query->createConditionGroup('OR', [$entity_access_tag]);
      $by_datasource->addConditionGroup($access);
      // Add tag to allow altering the query.
      $query->addTag($entity_access_tag);

      $main_condition_group->addConditionGroup($by_datasource);
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
      SearchApiEvents::QUERY_PRE_EXECUTE => 'addTaggedQueryConditions',
    ];

  }

}
