<?php

declare(strict_types=1);

namespace Drupal\social_node\Plugin\SocialEntityQueryAlter;

use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\social_core\Attribute\SocialEntityQueryAlter;
use Drupal\social_core\SocialEntityQueryAlterPluginBase;

/**
 * Plugin implementation of the social_entity_query_alter.
 */
#[SocialEntityQueryAlter(
  id: 'social_node_public_or_community',
  search_api_query_tags: [
    'social_entity_type_node_access',
  ],
  apply_on: [
    'node' => [
      'fields' => [
        'type',
        'field_content_visibility',
      ],
    ],
  ],
)]
class SocialNodePublicOrCommunity extends SocialEntityQueryAlterPluginBase {

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

    $field_in_index = $this->searchApiFindField($search_api_index, $datasource_id, 'type');
    $type_field = $field_in_index ? $field_in_index->getFieldIdentifier() : 'type';

    $field_in_index = $this->searchApiFindField($search_api_index, $datasource_id, 'field_content_visibility');
    $visibility_field = $field_in_index ? $field_in_index->getFieldIdentifier() : 'field_content_visibility';

    // Get all node types where we have visibility field.
    $field_storage = FieldStorageConfig::loadByName('node', 'field_content_visibility');
    $bundles = $field_storage->getBundles();

    foreach ($bundles as $bundle) {
      foreach (['public', 'community'] as $visibility) {
        if ($account->hasPermission("view node.$bundle.field_content_visibility:$visibility content")) {
          $condition = $query->createConditionGroup()
            ->addCondition($type_field, $bundle)
            ->addCondition($visibility_field, $visibility);

          $or->addConditionGroup($condition);
        }
      }
    }
  }

}
