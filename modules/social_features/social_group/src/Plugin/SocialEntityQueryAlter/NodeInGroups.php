<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\SocialEntityQueryAlter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\social_core\Attribute\SocialEntityQueryAlter;
use Drupal\social_core\SocialEntityQueryAlterPluginBase;
use Drupal\social_group\SocialGroupHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the social_entity_query_alter.
 */
#[SocialEntityQueryAlter(
  id: 'node_in_groups',
  search_api_query_tags: [
    'social_entity_type_node_access',
  ],
  apply_on: [
    'node' => [
      'fields' => [
        'type',
        'groups',
        'field_content_visibility',
      ],
    ],
  ],
)]
class NodeInGroups extends SocialEntityQueryAlterPluginBase {

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
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
    protected SocialGroupHelperService $groupHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityFieldManager);
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
   * {@inheritdoc}
   */
  public function searchApiEntityQueryAlter(QueryInterface $query, ConditionGroupInterface $or, AccountInterface $account, string $entity_type_id, string $datasource_id, IndexInterface $search_api_index): void {
    // Don't do anything if the user can access all content.
    if ($account->hasPermission('bypass node access')) {
      return;
    }

    if ($entity_type_id !== 'node') {
      return;
    }

    $user_groups = $this->groupHelper->getAllGroupsForUser((int) $account->id());

    $field_in_index = $this->searchApiFindField($search_api_index, $datasource_id, 'groups');
    $groups = $field_in_index ? $field_in_index->getFieldIdentifier() : 'groups';

    $field_in_index = $this->searchApiFindField($search_api_index, $datasource_id, 'field_content_visibility');
    $visibility = $field_in_index ? $field_in_index->getFieldIdentifier() : 'field_content_visibility';

    $condition = $query->createConditionGroup()
      ->addCondition($groups, $user_groups ?: [0], 'IN')
      ->addCondition($visibility, 'group');

    $or->addConditionGroup($condition);
  }

}
