<?php

namespace Drupal\social_profile\Plugin\UserSegmentRule;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user_segments\Attribute\UserSegmentRule;
use Drupal\user_segments\DataObject\Condition;
use Drupal\user_segments\DataObject\ConditionGroup;
use Drupal\user_segments\DataObject\Property;
use Drupal\user_segments\Enum\PropertyMatch;
use Drupal\user_segments\Enum\PropertyRelationship;
use Drupal\user_segments\Ui\DataObject\Condition as UiCondition;
use Drupal\user_segments\Ui\DataObject\Scope;
use Drupal\user_segments\Ui\DataObject\UiStructure;
use Drupal\user_segments\Ui\Enum\ConditionTarget;
use Drupal\user_segments\Ui\FieldTypeDataObject\SelectProperty;
use Drupal\user_segments\UserSegmentRulePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
final class ProfileUserSegmentRule extends UserSegmentRulePluginBase {

  public const string PLUGIN_ID = 'profile';

  // User profile user role condition and properties.
  public const string CONDITION__USER_ROLES = 'user_roles';
  public const string CONDITION__USER_ROLES__PROPERTY_ROLE = 'role';

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    EntityTypeManagerInterface $entityTypeManager,
    private readonly CacheBackendInterface $cacheBackend,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $database,
      $entityTypeManager,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('cache.default'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function uiStructure(): UiStructure {
    return new UiStructure(
      scope: new Scope(
        machine_name: 'profile_information',
        label: $this->t('Profile information'),
      ),
      hub: NULL,
      conditions: [
        new UiCondition(
          condition_type: self::CONDITION__USER_ROLES,
          target: ConditionTarget::Scope,
          properties: [
            new SelectProperty(
              property_type: self::CONDITION__USER_ROLES__PROPERTY_ROLE,
              label: $this->t('Role'),
              cardinality: -1,
              allowed_values: $this->getUserRoleOptionsForUi(),
              supports_match_all: TRUE,
            ),
          ]
        ),
      ]
    );
  }

  /**
   * {@inheritDoc}
   */
  public function applyToQuery(SelectInterface &$query, string $alias, ConditionInterface $condition_target): void {
    $plugin_configuration = $this->configuration;
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
        case self::CONDITION__USER_ROLES:
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
      $property_type = $condition_property->property_type;

      switch ($property_type) {
        // Role property type.
        case self::CONDITION__USER_ROLES__PROPERTY_ROLE:
          $this->applyUserRoleProperty($condition_property, $sub_conditions, $query, $alias, $suffix);
          break;

        default:
          throw new \InvalidArgumentException(sprintf('Unsupported property type: "%s".', $property_type));
      }
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
  ): void {

    $property_id = $condition_property->property_type;
    $config = $condition_property->config;

    switch ($property_id) {
      case self::CONDITION__USER_ROLES__PROPERTY_ROLE:

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

        // Include all users, even those without roles.
        $user__roles_alias = "ur$suffix";
        $query->leftJoin('user__roles', $user__roles_alias, "$user__roles_alias.entity_id = {$alias}.uid AND $user__roles_alias.bundle = 'user'");

        // Relationship / match cases.
        $match = $condition_property->match->value;
        $relationship = $condition_property->relationship->value;
        switch ("$relationship:$match") {

          // Relationship: include
          // Match: any.
          case PropertyRelationship::Include->value . ':' . PropertyMatch::Any->value:
            $sub_conditions->condition("$user__roles_alias.roles_target_id", $roles, 'IN');

            break;

          // Relationship: include
          // Match: all.
          case PropertyRelationship::Include->value . ':' . PropertyMatch::All->value:
            $subquery_user__roles_alias = "sq_ur$suffix";

            $subquery = $this->database->select('user__roles', $subquery_user__roles_alias);
            $subquery->addField($subquery_user__roles_alias, 'entity_id');
            $subquery->condition("$subquery_user__roles_alias.roles_target_id", $roles, 'IN');
            $subquery->condition("$subquery_user__roles_alias.bundle", 'user');
            $subquery->groupBy("$subquery_user__roles_alias.entity_id");
            // We avoid using a placeholder for $role_count here to reduce
            // overhead and prevent potential naming conflicts in complex
            // dynamic queries. Since the value comes from count(), which always
            // returns an integer, it is safe to inline directly into the SQL
            // and does not pose an SQL injection risk.
            $role_count = (int) count(array_unique($roles));
            $subquery->having("COUNT(DISTINCT $subquery_user__roles_alias.roles_target_id) = $role_count");

            // Apply this as a filter in the condition subquery.
            $sub_conditions->condition("{$alias}.uid", $subquery, 'IN');

            break;

          // Relationship: exclude
          // Match: any.
          case PropertyRelationship::Exclude->value . ':' . PropertyMatch::Any->value:
            $subquery_user__roles_alias = "sq_ur$suffix";

            $subquery = $this->database->select('user__roles', $subquery_user__roles_alias);
            $subquery->addField($subquery_user__roles_alias, 'entity_id');
            $subquery->condition("$subquery_user__roles_alias.roles_target_id", $roles, 'IN');
            $subquery->condition("$subquery_user__roles_alias.bundle", 'user');

            // Apply this as a filter in the condition subquery.
            $sub_conditions->condition("{$alias}.uid", $subquery, 'NOT IN');

            break;

          // Relationship: exclude
          // Match: all.
          case PropertyRelationship::Exclude->value . ':' . PropertyMatch::All->value:

            $subquery_user__roles_alias = "sq_ur$suffix";

            $subquery = $this->database->select('user__roles', $subquery_user__roles_alias);
            $subquery->addField($subquery_user__roles_alias, 'entity_id');
            $subquery->condition("$subquery_user__roles_alias.roles_target_id", $roles, 'IN');
            $subquery->condition("$subquery_user__roles_alias.bundle", 'user');
            $subquery->groupBy("$subquery_user__roles_alias.entity_id");
            // We avoid using a placeholder for $role_count here to reduce
            // overhead and prevent potential naming conflicts in complex
            // dynamic queries. Since the value comes from count(), which always
            // returns an integer, it is safe to inline directly into the SQL
            // and does not pose an SQL injection risk.
            $role_count = (int) count(array_unique($roles));
            $subquery->having("COUNT(DISTINCT $subquery_user__roles_alias.roles_target_id) = $role_count");

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
    $roles = $this->getUserRoleOptionsForUi();

    return array_keys($roles);
  }

  /**
   * Retrieves a cached list of user role machine names and labels.
   *
   * This method loads all user roles and returns an associative array where
   * the keys are role machine names and the values are role labels.
   *
   * The result is cached permanently to avoid repeated entity loading and
   * will be automatically invalidated when user roles are changed.
   *
   * Method cache_id:
   *   profile_user_segment_rule.user_role_options_for_ui
   *
   * Cache tags:
   *   config:user_role_list
   *
   * @return array<string,string|\Drupal\Core\StringTranslation\TranslatableMarkup>
   *   An associative array of user role machine names and their corresponding
   *   translated labels.
   */
  public function getUserRoleOptionsForUi(): array {
    $cid = 'profile_user_segment_rule.user_role_options_for_ui';

    // Try to get from cache.
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $entity_type = $this->entityTypeManager->getDefinition('user_role');
    $list_cache_tags = $entity_type->getListCacheTags();

    $role_entities = $this->entityTypeManager
      ->getStorage('user_role')
      ->loadMultiple();

    $roles = [];
    foreach ($role_entities as $role_entity) {
      if ($role_entity->label() !== NULL) {
        $roles[(string) $role_entity->id()] = $role_entity->label();
      }
    }

    $this->cacheBackend->set(
      $cid,
      $roles,
      CacheBackendInterface::CACHE_PERMANENT,
      $list_cache_tags
    );

    return $roles;
  }

}
