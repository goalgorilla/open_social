<?php

namespace Drupal\social_core\Plugin\search_api\processor;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\social_core\SocialEntityQueryAlterPluginManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds entity queries modifiers (including access checks).
 *
 * @SearchApiProcessor(
 *   id = "social_core_entity_query",
 *   label = @Translation("Social Entity Query"),
 *   description = @Translation("Adds additional queries to entity types source (including access)"),
 *   stages = {
 *     "add_properties" = 0,
 *     "pre_index_save" = -10,
 *     "preprocess_query" = -30,
 *   },
 * )
 */
class SocialCoreEntityQueryAlter extends ProcessorPluginBase {

  use LoggerTrait;

  /**
   * The current_user service used by this plugin.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The social entity query alter manager.
   */
  protected SocialEntityQueryAlterPluginManager $socialEntityQueryManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->logger = $container->get('logger.channel.social_core');
    $processor->currentUser = $container->get('current_user');
    $processor->socialEntityQueryManager = $container->get('plugin.manager.social_entity_query_alter');

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index): bool {
    /** @var \Drupal\social_core\SocialEntityQueryAlterInterface[] $plugins  */
    $plugins = \Drupal::service('plugin.manager.social_entity_query_alter')->loadAll();
    if (empty($plugins)) {
      return FALSE;
    }

    foreach ($index->getDatasources() as $datasource) {
      foreach ($plugins as $plugin) {
        if (in_array($datasource->getEntityTypeId(), $plugin->getSupportedEntityTypeIds())) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Retrieves the entity related to an indexed search object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity related to that search object.
   */
  protected function getEntity(ComplexDataInterface $item): ?ContentEntityInterface {
    $item = $item->getValue();

    // For comments, we want to take commented entity.
    if ($item instanceof CommentInterface) {
      $item = $item->getCommentedEntity();
    }

    if ($item instanceof ContentEntityInterface) {
      return $item;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL): array {
    if ($datasource) {
      return [];
    }

    $plugins = $this->socialEntityQueryManager->loadAll();
    if (empty($plugins)) {
      return [];
    }

    $properties = [];
    foreach ($plugins as $plugin) {
      $properties += $plugin->searchApiFieldProperties();
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function addFieldValues(ItemInterface $item): void {
    $plugins = $this->socialEntityQueryManager->loadAll();
    if (empty($plugins)) {
      return;
    }

    // Only run for node and comment items.
    $entity_type_id = $item->getDatasource()->getEntityTypeId();

    $supported_plugins = array_filter($plugins, fn ($plugin) => $plugin->applicableOnEntityType($entity_type_id));

    if (!$supported_plugins) {
      return;
    }

    // Get the entity object.
    $entity = $this->getEntity($item->getOriginalObject());
    if (!$entity instanceof EntityInterface) {
      // Apparently, we were active for a wrong item.
      return;
    }

    // Get all fields for the entity we should index.
    $supported_entity_fields = [];
    foreach ($supported_plugins as $plugin) {
      $supported_entity_fields = [
        ...$supported_entity_fields,
        ...$plugin->getSupportedFieldsByEntityType($entity_type_id),
      ];
    }
    // Exclude duplicates.
    $supported_entity_fields = array_unique($supported_entity_fields);

    $fields = $item->getFields();

    foreach ($supported_entity_fields as $field_name) {
      if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
        continue;
      }

      $index_fields = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, $field_name);

      foreach ($index_fields as $field) {
        /** @var \Drupal\Core\Field\FieldItemListInterface $field_object */
        $field_object = $entity->get($field_name);
        $field_definition_class = \Drupal::service('plugin.manager.field.field_type')->getPluginClass($field_object->getFieldDefinition()->getType());
        $field_main_property = class_exists($field_definition_class) ? $field_definition_class::mainPropertyName() : 'value';
        foreach (array_column($field_object->getValue(), $field_main_property) as $value) {
          $field->addValue($value);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function preIndexSave(): void {
    $plugins = $this->socialEntityQueryManager->loadAll();
    if (empty($plugins)) {
      return;
    }

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $entity_type_id = $datasource->getEntityTypeId();
      $supported_plugins = array_filter($plugins, fn ($plugin) => $plugin->applicableOnEntityType($entity_type_id));

      foreach ($supported_plugins as $plugin) {
        foreach ($plugin->getSupportedFieldsByEntityType($entity_type_id) as $field_name) {
          $field = $this->ensureField($datasource_id, $field_name);
          $field->setHidden();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query): void {
    if ($query->getOption('search_api_bypass_access')) {
      return;
    }

    $account = $query->getOption('search_api_access_account', $this->currentUser);
    if (is_numeric($account)) {
      $account = User::load($account);
    }

    if (!$account instanceof AccountInterface) {
      $account = $query->getOption('search_api_access_account');
      $this->getLogger()
        ->warning('An illegal user UID was given for node access: @uid.', [
          '@uid' => is_scalar($account) ? $account : var_export($account, TRUE),
        ]);

      return;
    }

    // Alter search api query for current user.
    $this->addQueryAccess($query, $account);
  }

  /**
   * Adds an entity access filter to a search query, if applicable.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to which a node access filter should be added, if applicable.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for whom the search is executed.
   */
  protected function addQueryAccess(QueryInterface $query, AccountInterface $account): void {
    $plugins = $this->socialEntityQueryManager->loadAll();

    // Gather the affected datasources, grouped by entity type, as well as the
    // unaffected ones.
    $affected_datasources = [];
    $unaffected_datasources = [];
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $entity_type_id = $datasource->getEntityTypeId();

      /** @var \Drupal\social_core\SocialEntityQueryAlterPluginBase[] $supported_plugins  */
      $supported_plugins = array_filter($plugins, fn ($plugin) => in_array($entity_type_id, $plugin->getSupportedEntityTypeIds()));
      if ($supported_plugins) {
        $affected_datasources[$entity_type_id]['datasource_id'] = $datasource_id;
        $affected_datasources[$entity_type_id]['supported_plugins'] = $supported_plugins;
      }
      else {
        $unaffected_datasources[] = $datasource_id;
      }
    }

    // If there are no "other" datasources, we don't need the nested OR,
    // however, and can add the inner conditions directly to the query.
    if ($unaffected_datasources) {
      $outer_conditions = $query->createAndAddConditionGroup('OR', ['datasources_separation']);
      foreach ($unaffected_datasources as $datasource_id) {
        $outer_conditions->addCondition('search_api_datasource', $datasource_id);
      }

      $access_conditions = $query->createConditionGroup();
      $outer_conditions->addConditionGroup($access_conditions);
    }
    else {
      $access_conditions = $query;
    }

    // If the user does not have the permission to see any content at all, deny
    // access to all items from affected datasources.
    if (!$affected_datasources) {
      // If there were "other" datasources, the existing filter will already
      // remove all results of node or comment datasources. Otherwise, we should
      // not return any results at all.
      if (!$unaffected_datasources) {
        $query->abort($this->t('You have no access to any results in this search.'));
      }
      return;
    }

    // General condition contains all conditions groups per datasource.
    $or = $query->createConditionGroup('OR', ['social_core_entity_query_access']);

    foreach ($affected_datasources as $entity_type_id => $data) {
      $datasource_id = $data['datasource_id'];
      $supported_plugins = $data['supported_plugins'];

      $datasource_condition = $query->createConditionGroup('AND', [$query_tag = "social_entity_type_$entity_type_id"]);
      $datasource_condition->addCondition('search_api_datasource', $datasource_id);
      foreach ($supported_plugins as $plugin) {
        if ($plugin->applicableOnSearchApiQueryTag($query_tag)) {
          $plugin->searchApiEntityQueryAlter($query, $datasource_condition, $account, $entity_type_id, $datasource_id, $this->index);
        }
      }

      $datasource_or_condition = $query->createConditionGroup('OR', [$access_query_tag = "social_entity_type_{$entity_type_id}_access"]);
      foreach ($supported_plugins as $plugin) {
        if ($plugin->applicableOnSearchApiQueryTag($access_query_tag)) {
          $plugin->searchApiEntityQueryAlter($query, $datasource_or_condition, $account, $entity_type_id, $datasource_id, $this->index);
        }
      }

      if (!$datasource_or_condition->isEmpty()) {
        $datasource_condition->addConditionGroup($datasource_or_condition);
      }

      $or->addConditionGroup($datasource_condition);
    }

    if ($or->isEmpty()) {
      // If no access rule was met, we should hardly restrict access.
      return;
    }

    $access_conditions->addConditionGroup($or);
  }

}
