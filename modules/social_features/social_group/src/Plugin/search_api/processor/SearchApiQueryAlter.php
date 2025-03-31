<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\social_search\Plugin\search_api\SocialSearchSearchApiProcessorTrait;
use Drupal\social_search\Utility\SocialSearchApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Altering a search api query for nodes.
 *
 *   This processor takes care of displaying nodes with "group" visibility.
 *
 * @SearchApiProcessor(
 *   id = "social_group_query_alter",
 *   label = @Translation("Social Group: Search Api query alter for nodes"),
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
   * @param \Drupal\social_group\SocialGroupHelperService $groupHelper
   *   The group helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected SocialGroupHelperService $groupHelper,
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
      $container->get('social_group.helper_service'),
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
        'groups' => ['type' => 'integer'],
        'type' => ['type' => 'string'],
        'field_content_visibility' => ['type' => 'string'],
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
      return;
    }

    // Check if we can skip access check for this condition.
    if (SocialSearchApi::skipAccessCheck($or)) {
      return;
    }

    $groups = $this->findField('entity:node', 'groups');
    $visibility = $this->findField('entity:node', 'field_content_visibility');
    if (!$groups instanceof FieldInterface || !$visibility instanceof FieldInterface) {
      // The field doesn't exist in the index.
      return;
    }

    $account = $query->getOption('social_search_access_account');
    $user_groups = $this->groupHelper->getAllGroupsForUser((int) $account->id());

    $groups_with_membership = $query->createConditionGroup()
      ->addCondition($groups->getFieldIdentifier(), $user_groups ?: [0], 'IN')
      ->addCondition($visibility->getFieldIdentifier(), 'group');

    $or->addConditionGroup($groups_with_membership);
  }

}
