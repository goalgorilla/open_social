<?php

namespace Drupal\social_group\Traits;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\user_segments\DataObject\Condition;
use Drupal\user_segments\DataObject\Property;
use Drupal\user_segments\Enum\PropertyMatch;
use Drupal\user_segments\Enum\PropertyRelationship;

/**
 * Trait for applying user segment group membership conditions.
 *
 * This trait is a temporary, non-final abstraction intended to consolidate
 * shared business rules across user segment plugins involving group membership.
 * The decision to implement this as a shared trait was made due to resource
 * constraints, tight deadlines, and the current uniformity of group membership
 * logic across segments (see PROD-33544 for future abstraction plans).
 *
 * IMPORTANT:
 * While all membership conditions and properties currently share the same
 * business rules, this may change. If a future use case requires different
 * logic per group type, DO NOT modify this trait directly. Instead, copy the
 * relevant code into the specific plugin and adjust it as needed to avoid tight
 * coupling and maintain flexibility.
 *
 * Until a proper abstraction is introduced, it is acceptable to apply shared
 * logic changes here, but with caution and awareness of potential divergence.
 */
trait UserSegmentGroupTrait {

  /**
   * Apply group type membership condition.
   *
   * @param string $group_type
   *   The group type ID.
   * @param \Drupal\user_segments\DataObject\Condition $condition
   *   Condition item to be applied.
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to which the condition should be applied.
   * @param string $alias
   *   The alias of the user table in the query.
   * @param \Drupal\Core\Database\Query\ConditionInterface $condition_target
   *   The condition group or query object where conditions should be added.
   * @param int $index
   *   Unique index that can be used to prefix/suffix aliases etc.
   *
   * @return void
   *   Return void.
   */
  protected function applyGroupTypeMembershipCondition(
    string $group_type,
    Condition $condition,
    SelectInterface &$query,
    string $alias,
    ConditionInterface $condition_target,
    int $index,
  ): void {
    $sub_conditions = $query->andConditionGroup();

    $rule_id = $this->configuration['id'];

    $suffix = '_' . $index . '_' . $rule_id;
    $grfd_alias = "grfd$suffix";
    $g_alias = "g$suffix";
    $gcgr_alias = "gcgr$suffix";

    // Join group relationships.
    $query->join('group_relationship_field_data', $grfd_alias, "$grfd_alias.entity_id = {$alias}.uid");
    $sub_conditions->condition("$grfd_alias.plugin_id", 'group_membership');

    $query->join('groups', $g_alias, "$grfd_alias.gid = $g_alias.id");
    $sub_conditions->condition("$g_alias.type", $group_type);

    foreach ($condition->properties as $condition_property) {
      $this->applyGroupMembershipProperty($condition_property, $sub_conditions, $query, $alias, $suffix, $grfd_alias, $gcgr_alias);
    }

    $condition_target->condition($sub_conditions);
  }

  /**
   * Apply group type membership role property.
   *
   * @param string $group_type
   *   The group type ID.
   * @param \Drupal\user_segments\DataObject\Property $condition_property
   *   Condition item to be applied.
   * @param \Drupal\Core\Database\Query\ConditionInterface $sub_conditions
   *   The condition group where individual property conditions will be added.
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to which the condition should be applied.
   * @param string $alias
   *   The alias of the user table in the query.
   * @param string $suffix
   *   Rule suffix, used to distinguish joins and conditions between rules.
   * @param string $grfd_alias
   *   Table alias for 'group_relationship_field_data'.
   * @param string $gcgr_alias
   *   Table alias for 'group_content__group_roles'.
   *
   * @return void
   *   Return void
   */
  protected function applyUserGroupSegmentGroupTypeRoleProperty(
    string $group_type,
    Property $condition_property,
    ConditionInterface &$sub_conditions,
    SelectInterface &$query,
    string $alias,
    string $suffix,
    string $grfd_alias,
    string $gcgr_alias,
  ): void {

    $config = $condition_property->config;

    if (!isset($config['value'])) {
      throw new \InvalidArgumentException('The "value" property is required for the role condition.');
    }

    $roles = $config['value'];
    if (!is_array($roles) || empty($roles)) {
      throw new \InvalidArgumentException('Role list must be a non-empty array.');
    }

    if (count($roles) !== count(array_unique($roles))) {
      throw new \InvalidArgumentException('Role list must contain unique values.');
    }

    if (!$condition_property->match instanceof PropertyMatch) {
      throw new \InvalidArgumentException('The "match" property is required for the role condition.');
    }

    if (!$condition_property->relationship instanceof PropertyRelationship) {
      throw new \InvalidArgumentException('The "relationship" property is required for the role condition.');
    }

    // Validate roles.
    $invalidRoles = array_diff($roles, $this->getGroupRoleOptions($group_type));
    if (!empty($invalidRoles)) {
      throw new \InvalidArgumentException(sprintf(
        'Invalid %s role(s) provided: %s.',
        $group_type,
        implode(', ', $invalidRoles)
      ));
    }

    // Group roles join.
    $query->leftJoin('group_content__group_roles', $gcgr_alias, "$grfd_alias.id = $gcgr_alias.entity_id");

    // Relationship / match cases.
    $match = $condition_property->match->value;
    $relationship = $condition_property->relationship->value;
    switch ("$relationship:$match") {

      // Relationship: include
      // Match: any.
      case PropertyRelationship::Include->value . ':' . PropertyMatch::Any->value:
        $sub_conditions->condition("$gcgr_alias.group_roles_target_id", $roles, 'IN');

        break;

      // Relationship: include
      // Match: all.
      case PropertyRelationship::Include->value . ':' . PropertyMatch::All->value:
        // Build subquery to find group_content entity IDs (i.e., group
        // memberships) that have all the specified roles.
        $subquery_gcgr_alias = "sq_gcgr$suffix";
        $subquery = $this->database->select('group_content__group_roles', $subquery_gcgr_alias);
        $subquery->distinct();
        $subquery->addField($subquery_gcgr_alias, 'entity_id');
        $subquery->condition("$subquery_gcgr_alias.group_roles_target_id", $roles, 'IN');
        // Group by entity_id and ensure all roles are matched.
        $subquery->groupBy("$subquery_gcgr_alias.entity_id");
        $subquery->having("COUNT(DISTINCT $subquery_gcgr_alias.group_roles_target_id) = :role_count", [
          ':role_count' => count(array_unique($roles)),
        ]);

        // Apply this as a filter in the condition subquery.
        $sub_conditions->condition("$gcgr_alias.entity_id", $subquery, 'IN');

        break;

      // Relationship: exclude
      // Match: any.
      case PropertyRelationship::Exclude->value . ':' . PropertyMatch::Any->value:
        // Goal: Find users who have at least one membership (of the given type)
        // where those memberships do NOT have any of the specified roles.
        //
        // This is a property-based exclusion (i.e., specific properties are
        // excluded within the rule itself). Therefore, we must first include
        // users who have memberships of the specified $group_type, and only
        // then apply the property-based exclusion logic.
        //
        // If this were a rule-level negation instead, we would also include
        // users who do not have any $group_type memberships.
        //
        // All users in this list are guaranteed to be members of the specified
        // group type before any condition-based exclusion is applied. This is
        // enforced by the `applyGroupMembershipCondition()` method, which adds
        // conditions like: `$sub_conditions->condition("$grfd_alias.plugin_id",
        // 'group_membership');` and `$sub_conditions->condition("$g_alias.type"
        // , $group_type);`.
        //
        // All related subqueries (e.g., $role_subquery and $wrapper_subquery)
        // follow this same pattern.
        //
        // Step 1: Build subquery to get membership IDs that have ANY of the
        // roles.
        $role_subquery_gcgr_alias = "rsq_gcgr$suffix";
        $role_subquery = $this->database->select('group_content__group_roles', $role_subquery_gcgr_alias);
        // entity_id = group membership ID.
        $role_subquery->addField($role_subquery_gcgr_alias, 'entity_id');
        $role_subquery->condition("$role_subquery_gcgr_alias.group_roles_target_id", $roles, 'IN');
        // Constrain to group type ($group_type) memberships.
        $role_subquery_grfd_alias = "rsq_grfd$suffix";
        $role_subquery_g_alias = "rsq_g$suffix";
        $role_subquery->join('group_relationship_field_data', $role_subquery_grfd_alias, "$role_subquery_gcgr_alias.entity_id = $role_subquery_grfd_alias.id");
        $role_subquery->condition("$role_subquery_grfd_alias.plugin_id", 'group_membership');
        $role_subquery->join('groups', $role_subquery_g_alias, "$role_subquery_grfd_alias.gid = $role_subquery_g_alias.id");
        $role_subquery->condition("$role_subquery_g_alias.type", $group_type);

        // Step 2: Find memberships not in that list, constrained by group type.
        $wrapper_subquery_grfd_alias = "wsq_grfd$suffix";
        $wrapper_subquery = $this->database->select('group_relationship_field_data', $wrapper_subquery_grfd_alias);
        $wrapper_subquery->distinct();
        $wrapper_subquery->addField($wrapper_subquery_grfd_alias, 'entity_id');
        $wrapper_subquery->condition("$wrapper_subquery_grfd_alias.id", $role_subquery, 'NOT IN');
        // Constrain to group type ($group_type) memberships.
        $wrapper_subquery_g_alias = "wsq_g$suffix";
        $wrapper_subquery->condition("$wrapper_subquery_grfd_alias.plugin_id", 'group_membership');
        $wrapper_subquery->join('groups', $wrapper_subquery_g_alias, "$wrapper_subquery_grfd_alias.gid = $wrapper_subquery_g_alias.id");
        $wrapper_subquery->condition("$wrapper_subquery_g_alias.type", $group_type);

        // Step 3: Apply subquery as condition to the main user query.
        $sub_conditions->condition("$alias.uid", $wrapper_subquery, 'IN');

        break;

      // Relationship: exclude
      // Match: all.
      case PropertyRelationship::Exclude->value . ':' . PropertyMatch::All->value:
        // Goal: Find users who have at least one membership (of the given type)
        // where those memberships do NOT have ALL the specified roles.
        //
        // This is a property-based exclusion (i.e., specific properties are
        // excluded within the rule itself). Therefore, we must first include
        // users who have memberships of the specified $group_type, and only
        // then apply the property-based exclusion logic.
        //
        // If this were a rule-level negation instead, we would also include
        // users who do not have any $group_type memberships.
        //
        // All users in this list are guaranteed to be members of the specified
        // group type before any condition-based exclusion is applied. This is
        // enforced by the `applyGroupMembershipCondition()` method, which adds
        // conditions like: `$sub_conditions->condition("$grfd_alias.plugin_id",
        // 'group_membership');` and `$sub_conditions->condition("$g_alias.type"
        // , $group_type);`.
        //
        // All related subqueries (e.g., $role_subquery and $wrapper_subquery)
        // follow this same pattern.
        //
        // Step 1: Build subquery to get membership IDs that have ALL the roles.
        $role_subquery_gcgr_alias = "rsq_gcgr$suffix";
        $role_subquery = $this->database->select('group_content__group_roles', $role_subquery_gcgr_alias);
        // entity_id = group membership ID.
        $role_subquery->addField($role_subquery_gcgr_alias, 'entity_id');
        $role_subquery->condition("$role_subquery_gcgr_alias.group_roles_target_id", $roles, 'IN');
        // Group by membership ID (the next 5 lines are the only difference
        // between exclude ALL roles and exclude ANY roles).
        $role_subquery->groupBy("$role_subquery_gcgr_alias.entity_id");
        // Only keep memberships that have all the roles assigned (count
        // distinct roles = number of roles).
        $role_subquery->having("COUNT(DISTINCT $role_subquery_gcgr_alias.group_roles_target_id) = :role_count", [
          ':role_count' => count(array_unique($roles)),
        ]);
        // Constrain to group type ($group_type) memberships.
        $role_subquery_grfd_alias = "rsq_grfd$suffix";
        $role_subquery_g_alias = "rsq_g$suffix";
        $role_subquery->join('group_relationship_field_data', $role_subquery_grfd_alias, "$role_subquery_gcgr_alias.entity_id = $role_subquery_grfd_alias.id");
        $role_subquery->condition("$role_subquery_grfd_alias.plugin_id", 'group_membership');
        $role_subquery->join('groups', $role_subquery_g_alias, "$role_subquery_grfd_alias.gid = $role_subquery_g_alias.id");
        $role_subquery->condition("$role_subquery_g_alias.type", $group_type);

        // Step 2: Find memberships not in that list, constrained by group type.
        $wrapper_subquery_grfd_alias = "wsq_grfd$suffix";
        $wrapper_subquery = $this->database->select('group_relationship_field_data', $wrapper_subquery_grfd_alias);
        $wrapper_subquery->addField($wrapper_subquery_grfd_alias, 'entity_id');
        $wrapper_subquery->condition("$wrapper_subquery_grfd_alias.id", $role_subquery, 'NOT IN');
        // Constrain to group type ($group_type) memberships.
        $wrapper_subquery_g_alias = "wsq_g$suffix";
        $wrapper_subquery->condition("$wrapper_subquery_grfd_alias.plugin_id", 'group_membership');
        $wrapper_subquery->join('groups', $wrapper_subquery_g_alias, "$wrapper_subquery_grfd_alias.gid = $wrapper_subquery_g_alias.id");
        $wrapper_subquery->condition("$wrapper_subquery_g_alias.type", $group_type);

        // Step 3: Apply subquery as condition to the main user query.
        $sub_conditions->condition("$alias.uid", $wrapper_subquery, 'IN');

        break;

      // Handle unexpected relationship/match combinations.
      // This error should be unreachable because all valid combinations
      // are explicitly handled above, and the input is restricted to
      // allowed relationship and match enum values.
      default:
        throw new \InvalidArgumentException(sprintf(
          'Unsupported relationship/match combination: %s:%s',
          $relationship,
          $match
        ));
    }

  }

  /**
   * Returns the list of role machine names for a given group type.
   *
   * @param string $group_type
   *   The group type ID.
   *
   * @return string[]
   *   An array of role machine names available for the given group type.
   */
  private function getGroupRoleOptions(string $group_type): array {
    $roles = $this->entityTypeManager
      ->getStorage('group_role')
      ->loadByProperties(['group_type' => $group_type]);

    return array_keys($roles);
  }

}
