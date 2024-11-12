<?php

declare(strict_types=1);

namespace Drupal\social_event_managers\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityFieldManagerInterface;
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
 *   This processor takes care of displaying nodes for event managers.
 *
 * @SearchApiProcessor(
 *   id = "social_event_managers_query_alter",
 *   label = @Translation("Social Event Managers: Search Api query alter for nodes"),
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
        'field_event_managers' => ['type' => 'integer'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query): void {
    /* @see \Drupal\social_search\Plugin\search_api\processor\TaggingQuery::preprocessSearchQuery() */
    $or = SocialSearchApi::findTaggedQueryConditionsGroup('social_entity_type_node_access', $query->getConditionGroup());
    if (!$or instanceof ConditionGroupInterface) {
      $this->getLogger()->error(sprintf('Required search api query tag "%s" can not be found in %s', 'social_entity_type_node_access', __METHOD__));
      return;
    }

    $account = $query->getOption('social_search_access_account');

    // Don't do anything if the user can access all content.
    if ($account->hasPermission('bypass node access')) {
      return;
    }

    /* @see \Drupal\social_event_managers\Plugin\search_api\processor\AddNodeFields */
    $field_event_managers = $this->findField('entity:node', 'field_event_managers');
    if (!$field_event_managers) {
      // The field doesn't exist in the index.
      return;
    }

    $or->addCondition($field_event_managers->getFieldIdentifier(), $account->id());
  }

}
