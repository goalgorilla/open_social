<?php

declare(strict_types=1);

namespace Drupal\social_node\Plugin\SocialEntityQueryAlter;

use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\social_core\Attribute\SocialEntityQueryAlter;
use Drupal\social_core\SocialEntityQueryAlterPluginBase;

/**
 * Plugin implementation of the social_entity_query_alter.
 */
#[SocialEntityQueryAlter(
  id: 'node_published_or_author',
  search_api_query_tags: [
    'social_entity_type_node',
  ],
  apply_on: [
    'node' => [
      'fields' => [
        'status',
        'uid',
      ],
    ],
  ],
)]
class NodePublishedOrAuthor extends SocialEntityQueryAlterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function searchApiEntityQueryAlter(QueryInterface $query, ConditionGroupInterface $and, AccountInterface $account, string $entity_type_id, string $datasource_id, IndexInterface $search_api_index): void {
    // Don't do anything if the user can access all content.
    if ($account->hasPermission('bypass node access')) {
      return;
    }

    if ($account->hasPermission('view any unpublished content')) {
      return;
    }

    if ($entity_type_id !== 'node' && $entity_type_id !== 'comment') {
      return;
    }

    $denie_all_node_access = $entity_type_id === 'node' && !$account->hasPermission('access content');
    $denie_all_comment_access = $entity_type_id === 'comment' && !$account->hasPermission('access comments');

    $conditions = $query->createConditionGroup('OR', ['content_access_enabled']);

    // If this is a comment datasource, or users cannot view their own
    // unpublished nodes, a simple filter on "status" is enough. Otherwise,
    // it's a bit more complicated.
    $field_in_index = $this->searchApiFindField($search_api_index, $datasource_id, 'status', 'boolean');
    $status = $field_in_index ? $field_in_index->getFieldIdentifier() : 'status';
    $conditions->addCondition($status, TRUE);

    if ($entity_type_id === 'node' && $account->hasPermission('view own unpublished content')) {
      $field_in_index = $this->searchApiFindField($search_api_index, $datasource_id, 'uid', 'integer');
      $author = $field_in_index ? $field_in_index->getFieldIdentifier() : 'uid';
      $conditions->addCondition($author, $account->id());
    }

    if ($conditions->isEmpty()) {
      return;
    }

    $and->addConditionGroup($conditions);
  }

}
