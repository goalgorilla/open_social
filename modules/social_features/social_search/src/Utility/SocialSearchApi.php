<?php

declare(strict_types=1);

namespace Drupal\social_search\Utility;

use Drupal\search_api\Query\ConditionGroupInterface;

/**
 * Contains utility methods for the Social Search.
 */
class SocialSearchApi {

  /**
   * The tag for marking condition that allows access to any content.
   *
   * @var string
   */
  const BYPASS_ACCESS_TAG = 'bypass access';

  /**
   * The value indicating unrestricted access or bypassing restrictions.
   *
   * @var int
   */
  const BYPASS_VALUE = 0;

  /**
   * Applies a bypass access tag to the specified condition.
   *
   *   There could be cases when we want to allow access to all entities,
   *   for example, for CM+. When we build the conditions, we can't just skip
   *   query building for CM+, so we need to apply some conditions that will
   *   return all content (like "nid != 0", or "visibility != '0'", etc.).
   *
   *   Use this method very carefully. Other modules can check if the access
   *   is granted and skip their own query building (as redundant).
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition
   *   The condition to which the bypass access tag will be applied.
   */
  public static function applyBypassAccessTag(ConditionGroupInterface $condition): void {
    if ($condition->hasTag(self::BYPASS_ACCESS_TAG)) {
      return;
    }

    $condition->getTags()[self::BYPASS_ACCESS_TAG] = self::BYPASS_ACCESS_TAG;
  }

  /**
   * Determines if access checks should be bypassed based on a given condition.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition
   *   The condition to evaluate for bypassing access checks.
   *
   * @return bool
   *   True if the condition contains the bypass access tag, otherwise false.
   */
  public static function skipAccessCheck(ConditionGroupInterface $condition): bool {
    return $condition->hasTag(self::BYPASS_ACCESS_TAG);
  }

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
    // Check if the current condition group has the target tag.
    if ($conditions->hasTag($tag)) {
      return $conditions;
    }

    // Get all conditions from the current group.
    $all_conditions = $conditions->getConditions();

    // Process each condition in the current group.
    foreach ($all_conditions as $condition) {
      // Skip non-condition group items (like regular Condition objects).
      if (!$condition instanceof ConditionGroupInterface) {
        continue;
      }

      // Check if this nested condition group has the target tag.
      if ($condition->hasTag($tag)) {
        return $condition;
      }

      // Recursively search deeper into this condition group.
      $result = self::findTaggedQueryConditionsGroup($tag, $condition);
      if ($result !== NULL) {
        return $result;
      }
    }

    return NULL;
  }

}
