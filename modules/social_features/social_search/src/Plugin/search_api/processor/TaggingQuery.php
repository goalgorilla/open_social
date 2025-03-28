<?php

declare(strict_types=1);

namespace Drupal\social_search\Plugin\search_api\processor;

use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a tagged conditions groups to make possible detect and alter them.
 *
 * @SearchApiProcessor(
 *   id = "social_search_tagging_query",
 *   label = @Translation("Tagging queries"),
 *   description = @Translation("Tagging queries by entity type and entity type access."),
 *   stages = {
 *     "preprocess_query" = -100,
 *   }
 * )
 */
class TaggingQuery extends ProcessorPluginBase {

  /**
   * Constructs an "TaggingQuery" object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logging channel.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user account.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerInterface $logger,
    protected AccountInterface $currentUser,
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
      $container->get('logger.channel.social_search'),
      $container->get('current_user'),
    );
  }

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
   *   The filter structure will look like this:
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
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The object representing the query to be executed.
   */
  public function preprocessSearchQuery(QueryInterface $query): void {
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
      $this->logger->warning('An illegal user UID was given for node access: @uid.', [
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

      // Search API doesn't allow altering tagged condition groups with hooks or
      // event subscribers.
      // Use "SocialSearchApi::findTaggedQueryConditionsGroup()" to find the
      // desired tagged condition group.
      /* @see \Drupal\social_search\Utility\SocialSearchApi::findTaggedQueryConditionsGroup() */
      $by_datasource = $query->createConditionGroup('AND', ["social_entity_type_$entity_type_id"]);
      $by_datasource->addCondition('search_api_datasource', $datasource_id);
      $main_condition_group->addConditionGroup($by_datasource);

      // This condition group will be applied to each entity type access.
      $access = $query->createConditionGroup('OR', ["social_entity_type_{$entity_type_id}_access"]);
      $by_datasource->addConditionGroup($access);
    }
  }

}
