<?php

namespace Drupal\social_profile\Plugin\UserSegmentRule;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user_segments\Attribute\UserSegmentRule;
use Drupal\user_segments\DataObject\Condition;
use Drupal\user_segments\DataObject\ConditionGroup;
use Drupal\user_segments\DataObject\Property;
use Drupal\user_segments\Enum\PropertyMatch;
use Drupal\user_segments\Enum\PropertyRelationship;
use Drupal\user_segments\UserSegmentRulePluginBase;

/**
 * Plugin implementation of the Global role segment condition.
 *
 * Provides conditions to filter users by global roles.
 */
#[UserSegmentRule(
  id: 'profile',
  label: new TranslatableMarkup('User profile'),
  description: new TranslatableMarkup('User profile rule with segment conditions')
)]
final class ProfileUserSegmentRule extends UserSegmentRulePluginBase implements PluginFormInterface {

  public const string PLUGIN_ID = 'profile';

  /**
   * Builds the plugin configuration form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The altered form array.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // @todo Implement form elements for user profile and other config.
    return $form;
  }

  /**
   * Handles form submission for the plugin configuration.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // @todo Implement form submission handler.
  }

  /**
   * Validates the configuration form input.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // @todo Implement form validation logic.
  }

  /**
   * {@inheritDoc}
   */
  public function applyToQuery(SelectInterface &$query, string $alias, ConditionInterface $condition_target): void {
    $plugin_configuration = $this->getConfiguration();
    $condition_groups = $plugin_configuration['condition_groups'] ?? [];

    /**
     * @var int $index
     * @var  \Drupal\user_segments\DataObject\ConditionGroup $condition_group
     */
    foreach ($condition_groups as $index => $condition_group) {
      $this->applyConditionItem($condition_group, $query, $alias, $condition_target, $index);
    }
  }

  /**
   * Apply condition item.
   *
   * @param \Drupal\user_segments\DataObject\ConditionGroup $condition_group
   *   Condition group to be applied.
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
  private function applyConditionItem(
    ConditionGroup $condition_group,
    SelectInterface &$query,
    string $alias,
    ConditionInterface $condition_target,
    int $index,
  ): void {
    $conditions = $condition_group->conditions;
    foreach ($conditions as $condition) {
      $condition_id = $condition->condition_type;

      // Switch on the top-level rule (allows adding more in future).
      switch ($condition_id) {

        // User roles.
        case 'user_roles':
          $this->applyUserRoleCondition($condition, $query, $alias, $condition_target, $index);
          break;

        default:
          throw new \InvalidArgumentException(sprintf('Unsupported condition: "%s".', $condition_id));
      }
    }
  }

  /**
   * Apply user role condition.
   *
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
  private function applyUserRoleCondition(Condition $condition, SelectInterface &$query, string $alias, ConditionInterface $condition_target, int $index): void {
    $sub_conditions = $query->andConditionGroup();
    $rule_id = $this->configuration['id'];

    $suffix = '_' . $index . '_' . $rule_id;

    foreach ($condition->properties as $condition_property) {
      $this->applyUserRoleProperty($condition_property, $sub_conditions, $query, $alias, $suffix);
    }

    $condition_target->condition($sub_conditions);

  }

  /**
   * Apply user role property.
   *
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
   *
   * @return void
   *   Return void
   */
  private function applyUserRoleProperty(
    Property $condition_property,
    ConditionInterface &$sub_conditions,
    SelectInterface &$query,
    string $alias,
    string $suffix,
  ) {

    $property_id = $condition_property->property_type;
    $config = $condition_property->config;

    switch ($property_id) {
      case 'role':

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
        $invalidRoles = array_diff($roles, $this->getUserRoleOptions());
        if (!empty($invalidRoles)) {
          throw new \InvalidArgumentException(sprintf(
            'Invalid user role(s) provided: %s.',
            implode(', ', $invalidRoles)
          ));
        }

        // Include all users, event those without roles.
        $ur_alias = "ur$suffix";
        $query->leftJoin('user__roles', $ur_alias, "$ur_alias.entity_id = {$alias}.uid AND $ur_alias.bundle = 'user'");

        // Relationship / match cases.
        $match = $condition_property->match->value;
        $relationship = $condition_property->relationship->value;
        switch ("$relationship:$match") {

          // Relationship: include
          // Match: any.
          case PropertyRelationship::Include->value . ':' . PropertyMatch::Any->value:
            $sub_conditions->condition("$ur_alias.roles_target_id", $roles, 'IN');

            break;

          // Relationship: include
          // Match: all.
          case PropertyRelationship::Include->value . ':' . PropertyMatch::All->value:
            $subquery_ur_alias = "sq_ur$suffix";

            $subquery = $this->database->select('user__roles', $subquery_ur_alias);
            $subquery->addField($subquery_ur_alias, 'entity_id');
            $subquery->condition("$subquery_ur_alias.roles_target_id", $roles, 'IN');
            $subquery->condition("$subquery_ur_alias.bundle", 'user');
            $subquery->groupBy("$subquery_ur_alias.entity_id");
            // We avoid using a placeholder for $role_count here to reduce
            // overhead and prevent potential naming conflicts in complex
            // dynamic queries. Since the value comes from count(), which always
            // returns an integer, it is safe to inline directly into the SQL
            // and does not pose an SQL injection risk.
            $role_count = (int) count(array_unique($roles));
            $subquery->having("COUNT(DISTINCT $subquery_ur_alias.roles_target_id) = $role_count");

            // Apply this as a filter in the condition subquery.
            $sub_conditions->condition("{$alias}.uid", $subquery, 'IN');

            break;

          // Relationship: exclude
          // Match: any.
          case PropertyRelationship::Exclude->value . ':' . PropertyMatch::Any->value:
            $subquery_ur_alias = "sq_ur$suffix";

            $subquery = $this->database->select('user__roles', $subquery_ur_alias);
            $subquery->addField($subquery_ur_alias, 'entity_id');
            $subquery->condition("$subquery_ur_alias.roles_target_id", $roles, 'IN');
            $subquery->condition("$subquery_ur_alias.bundle", 'user');

            // Apply this as a filter in the condition subquery.
            $sub_conditions->condition("{$alias}.uid", $subquery, 'NOT IN');

            break;

          // Relationship: exclude
          // Match: all.
          case PropertyRelationship::Exclude->value . ':' . PropertyMatch::All->value:

            $subquery_ur_alias = "sq_ur$suffix";

            $subquery = $this->database->select('user__roles', $subquery_ur_alias);
            $subquery->addField($subquery_ur_alias, 'entity_id');
            $subquery->condition("$subquery_ur_alias.roles_target_id", $roles, 'IN');
            $subquery->condition("$subquery_ur_alias.bundle", 'user');
            $subquery->groupBy("$subquery_ur_alias.entity_id");
            // We avoid using a placeholder for $role_count here to reduce
            // overhead and prevent potential naming conflicts in complex
            // dynamic queries. Since the value comes from count(), which always
            // returns an integer, it is safe to inline directly into the SQL
            // and does not pose an SQL injection risk.
            $role_count = (int) count(array_unique($roles));
            $subquery->having("COUNT(DISTINCT $subquery_ur_alias.roles_target_id) = $role_count");

            // Apply this as a filter in the condition subquery.
            $sub_conditions->condition("{$alias}.uid", $subquery, 'NOT IN');
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

        break;

      default:
        throw new \InvalidArgumentException(sprintf('Unsupported property in user_roles: "%s".', $property_id));
    }
  }

  /**
   * Returns the list of available user role machine names.
   *
   * @return string[]
   *   An array of available user role machine names.
   */
  private function getUserRoleOptions(): array {
    $roles = $this->entityTypeManager
      ->getStorage('user_role')
      ->loadMultiple();

    return array_keys($roles);
  }

}
