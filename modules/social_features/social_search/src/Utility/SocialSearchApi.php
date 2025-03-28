<?php

declare(strict_types=1);

namespace Drupal\social_search\Utility;

use Drupal\search_api\Query\ConditionGroupInterface;

/**
 * Contains utility methods for the Social Search.
 */
class SocialSearchApi {

  /**
   * Helps to find a tagged query condition group in a search api query.
   *
   *   If a query has multiple groups with the same tag, the method returns
   *   a group with the first entry of a desired tag.
   *
   * @param string $tag
   *   The target query conditions group tag.
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
    } while ($conditions);

    return NULL;
  }

}
