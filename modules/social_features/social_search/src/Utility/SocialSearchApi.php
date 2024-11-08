<?php

declare(strict_types=1);

namespace Drupal\social_search\Utility;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Query\ConditionGroupInterface;

/**
 * Contains utility methods for the Social Search.
 */
class SocialSearchApi {

  /**
   * Helps to find a tagged query condition group in a search api query.
   *
   * @param string $tag
   *   The target query conditions group tag.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $conditions
   *   The search api query conditions.
   *
   * @return \Drupal\search_api\Query\ConditionGroupInterface|null
   *   The tagged conditions group.
   */
  public static function findTaggedQueryConditionsGroup(string $tag, ConditionGroupInterface $conditions): ?ConditionGroupInterface {
    if ($conditions->hasTag($tag)) {
      return $conditions;
    }

    $conditions = $conditions->getConditions();

    do {
      $current = $conditions;
      $conditions = [];

      foreach ($current as $condition) {
        if (!$condition instanceof ConditionGroupInterface) {
          continue;
        }

        if ($condition->hasTag($tag)) {
          return $condition;
        }

        $nested_condition_groups = array_filter($condition->getConditions(), fn($group) => $group instanceof ConditionGroupInterface);
        if (!$nested_condition_groups) {
          continue;
        }

        $conditions = [...$conditions, ...$nested_condition_groups];
      }
    }
    while ($conditions);

    return NULL;
  }

  /**
   * Finds a certain field in the index.
   *
   * @param string|null $datasource_id
   *   The ID of the field's datasource, or NULL for a datasource-independent
   *   field.
   * @param string $property_path
   *   The field's property path on the datasource.
   * @param string|null $type
   *   (optional) If set, only return a field if it has this type.
   *
   * @return \Drupal\search_api\Item\FieldInterface|null
   *   A field on the index with the desired properties, or NULL if none could
   *   be found.
   */
  public static function searchApiFindField(IndexInterface $search_api_index, ?string $datasource_id, string $property_path, ?string $type = NULL): ?FieldInterface {
    foreach ($search_api_index->getFieldsByDatasource($datasource_id) as $field) {
      if ($field->getPropertyPath() === $property_path) {
        if ($type === NULL || $field->getType() === $type) {
          return $field;
        }
      }
    }

    return NULL;
  }

}
