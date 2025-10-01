<?php

namespace Drupal\Tests\your_module\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\social_profile\Plugin\UserSegmentRule\ProfileUserSegmentRule;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user_segments\DataObject\Condition;
use Drupal\user_segments\DataObject\ConditionGroup;
use Drupal\user_segments\DataObject\Property;
use Drupal\user_segments\DataObject\Rule;
use Drupal\user_segments\DataObject\RuleGroup;
use Drupal\user_segments\Enum\ConditionGroupConjunction;
use Drupal\user_segments\Enum\PropertyMatch;
use Drupal\user_segments\Enum\PropertyRelationship;
use Drupal\user_segments\Enum\RuleGroupConjunction;
use Drupal\user_segments\UserSegmentQueryBuilder;

/**
 * Tests the ProfileUserSegmentRule plugin.
 *
 * @group user_segments
 *
 * Scenarios TOC:
 * 1. Negative flows
 *   1.a. Test profile rule with a non-existent role.
 *   1.b. Test profile rule with an empty role.
 *   1.c. Test profile rule without a role property provided.
 *   1.d. Test profile rule with an empty match property on a role condition.
 *   1.e. Test profile rule with an empty relationship property on a role
 *        condition.
 *   1.f. Test profile rule with a wrong value type on a role condition.
 *   1.g. Test profile rule with a duplicated role.
 *  2. Empty flow
 *   2.a. Test profile rule with an empty result returned.
 *  3. Segment membership changes based on role assignments.
 *   3.a. User is added to a segment when a role is assigned.
 *   3.b. User is removed from a segment when a role is removed.
 * 4. Tests role rules without conjunctions (8 scenarios).
 * 5. Tests role rules with conjunctions (54 scenarios).
 */
class ProfileUserSegmentRuleTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * User segment query builder service.
   *
   * @var \Drupal\user_segments\UserSegmentQueryBuilder
   */
  private UserSegmentQueryBuilder $userSegmentQueryBuilder;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Core requirements.
    'system',
    'user',
    'user_segments',
    'social_profile',

    // Required by social_organization.
    'social_group',
    'social_core',
    'ginvite',

    // Required by group.
    'entity',
    'flexible_permissions',
    'group',
    'options',

    // Required by social_group.
    'better_exposed_filters',
    'views_bulk_operations',
    'flag',
    'gnode',
    'social_event',
    'social_topic',
    'image',
    'file',

    // Required by gnode.
    'node',

    // Required by social_event.
    'profile',
    'views',
    'group_core_comments',
    'menu_ui',
    'comment',
    'social_node',
    'datetime',
    'select2',

    // Required by social_profile.
    'telephone',
    'paragraphs',
    'entity_reference_revisions',
    'address',

    // Required by social_core.
    'field_group',
    'file_mdm',
    'image_effects',
    'image_widget_crop',
    'crop',
    'block',
    'block_content',
    'entity_access_by_field',
    'link',

    // Other requirements.
    'media',
    'taxonomy',
    'field',
    'text',
    'social_media_system',
  ];

  /**
   * An array of config object names that are excluded from schema checking.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = [
    // Exception: Exception when installing config for module profile, message
    // was: No schema for views.view.profiles.
    'views.view.profiles',
    // Exception: Exception when installing config for module social_profile,
    // message was: No schema for
    // field.storage.group_content.field_affiliation_function.
    'field.storage.group_content.field_affiliation_function',
    // Exception: Exception when installing config for module field, message
    // was: The configuration property settings.allowed_values_function.0
    // doesn't exist.
    'field.storage.group.field_segment_visibility',
    'field.storage.node.field_segment_visibility',
    // Exception: Exception when installing config for module group, message
    // was: Schema errors for views.view.group_manage_members with the following
    // errors: views.view.group_manage_members:display.page_group_manage_members.display_options.display_extenders.views_ef_fieldset
    // missing schema.
    'views.view.group_manage_members',
    // Exception: Exception when installing config for module social_group,
    // message was: Schema errors for social_group.settings with the following
    // errors: social_group.settings:cross_posting missing schema.
    'social_group.settings',
    // Exception: Exception when installing config for module social_group,
    // message was: Schema errors for views.view.group_per_type with the
    // following errors: views.view.group_per_type:display.default.display_options.fields.created.settings.time_diff.description
    // missing schema.
    'views.view.group_per_type',
    // Exception: Exception when installing config for module social_group,
    // message was: Schema errors for views.view.groups with the following
    // errors: views.view.groups:display.page_user_groups.display_options.exposed_form.options.bef.flagged
    // missing schema, views.view.groups:display.page_user_groups.display_options.filters.flagged.null_is_false
    // missing schema.
    'views.view.groups',
    // Exception: Exception when installing config for module social_group,
    // message was: Schema errors for views.view.groups_overview with the
    // following errors: views.view.groups_overview:display.default.display_options.fields.created.settings.time_diff.description
    // missing schema.
    'views.view.groups_overview',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_config_wrapper');
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('taxonomy_term');

    $this->installSchema('file', ['file_usage']);

    $this->installConfig([
      'node',
      'user',
      'profile',
      'social_profile',
      'social_node',
      'social_core',
      'group',
      'social_event',
      'social_topic',
      'ginvite',
      'social_group',
      'social_media_system',
      'file',
      'media',
      'taxonomy',
      'text',
      'system',
    ]);

    $this->userSegmentQueryBuilder = $this->container->get('user_segments.user_segment_query_builder');
  }

  /**
   * Test profile rule with a non-existent role.
   *
   * Scenario: 1.a.
   *
   * Mistake: Try to build a query using a profile rule with a non-existent
   *          role.
   * Error: InvalidArgumentException.
   */
  public function testProfileRuleWithNonExistentRole(): void {

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid user role(s) provided: role-that-does-not-exist.');

    $rules = new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: PropertyRelationship::Include,
                  match: PropertyMatch::Any,
                  config: [
                    'value' => [
                      'role-that-does-not-exist',
                    ],
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );

    $this->userSegmentQueryBuilder->getUserIds($rules);
  }

  /**
   * Test profile rule with an empty role.
   *
   * Scenario: 2.b.
   *
   * Mistake: Try to build a query using a profile rule without a role.
   * Error: InvalidArgumentException.
   */
  public function testProfileRuleWithoutRole(): void {

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Role list must be a non-empty array.');

    $rules = new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: PropertyRelationship::Include,
                  match: PropertyMatch::Any,
                  config: [
                    'value' => [],
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );

    $this->userSegmentQueryBuilder->getUserIds($rules);
  }

  /**
   * Test profile rule without a role property provided.
   *
   * Scenario: 1.c.
   *
   * Mistake: Try to build a query using a profile rule without a role property.
   * Error: InvalidArgumentException.
   */
  public function testProfileRuleWithoutRoleProperty(): void {

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "value" property is required for the role condition.');

    $rules = new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: PropertyRelationship::Include,
                  match: PropertyMatch::Any,
                  config: []
                ),
              ]
            ),
          ]
        ),
      ]
    );

    $this->userSegmentQueryBuilder->getUserIds($rules);
  }

  /**
   * Test profile rule with an empty match property on a role condition.
   *
   * Scenario: 1.d.
   *
   * Mistake: Test profile rule with an empty match property on a role
   *          condition.
   * Error: InvalidArgumentException.
   */
  public function testProfileRuleWithoutMatchProperty(): void {

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "match" property is required for the role condition.');

    $rules = new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: PropertyRelationship::Include,
                  match: NULL,
                  config: [
                    'value' => [
                      'administrator',
                    ],
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );

    $this->userSegmentQueryBuilder->getUserIds($rules);
  }

  /**
   * Test profile rule with an empty relationship property on a role condition.
   *
   * Scenario: 1.e.
   *
   * Mistake: Test profile rule with an empty relationship property on a role
   *          condition.
   * Error: InvalidArgumentException.
   */
  public function testProfileRuleWithoutRelationshipProperty(): void {

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "relationship" property is required for the role condition.');

    $rules = new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: NULL,
                  match: PropertyMatch::Any,
                  config: [
                    'value' => [
                      'administrator',
                    ],
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );

    $this->userSegmentQueryBuilder->getUserIds($rules);
  }

  /**
   * Test profile rule with a wrong value type on a role condition.
   *
   * Scenario: 1.f.
   *
   * Mistake: Test profile rule with a wrong value type on a role condition.
   * Error: InvalidArgumentException.
   */
  public function testProfileRuleWithWrongValueTypeProperty(): void {

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Role list must be a non-empty array.');

    $rules = new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: PropertyRelationship::Include,
                  match: PropertyMatch::Any,
                  config: [
                    'value' => 'administrator',
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );

    $this->userSegmentQueryBuilder->getUserIds($rules);
  }

  /**
   * Test profile rule with a duplicated role.
   *
   * Scenario: 1.g.
   *
   * Mistake: List the same role twice as a role property value.
   *  Error: InvalidArgumentException.
   */
  public function testProfileRuleWithDuplicatedRole(): void {

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Role list must contain unique values.');

    $rules = new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: PropertyRelationship::Include,
                  match: PropertyMatch::Any,
                  config: [
                    'value' => [
                      'administrator',
                      'administrator',
                    ],
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );

    $this->userSegmentQueryBuilder->getUserIds($rules);
  }

  /**
   * Test profile rule with an empty result returned.
   *
   * Scenario: 2.a.
   */
  public function testGetEmptyResults(): void {
    $rules = new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: PropertyRelationship::Include,
                  match: PropertyMatch::All,
                  config: [
                    'value' => [
                      'sitemanager',
                    ],
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );

    $user_ids = $this->userSegmentQueryBuilder->getUserIds($rules);
    $this->assertCount(0, $user_ids, 'Should return exactly 0 users being site managers, because none of the users have this role assigned.');
  }

  /**
   * User is added to a segment when a role is assigned.
   *
   * Scenario: 3.a.
   *
   * This test verifies that when a user is assigned a role, the system
   * automatically adds the user to the corresponding segment.
   */
  public function testUserIsAddedToSegmentWhenUserRoleAssigned(): void {
    // Define the rule that matches users with a site manager role.
    $rules = $this->getUserSegmentSiteManagerRule();
    $user_ids = $this->userSegmentQueryBuilder->getUserIds($rules);
    // Create a user without the 'sitemanager' role assigned.
    $alice = $this->createUser(
      name: 'alice',
      values: [
        'roles' => [],
      ],
    );
    $this->assertInstanceOf(User::class, $alice);

    // Test initial state: Alice should NOT match, since they do not have the
    // role yet.
    $this->assertNotContains($alice->id(), $user_ids, 'Should not return Alice, they are not site manager.');
    $this->assertCount(0, $user_ids, 'Should return exactly 0 users being site managers, because none have the role.');

    // Assign the site manager role to Alice.
    $alice->addRole('sitemanager');
    $alice->save();

    // Test updated state: Alice SHOULD NOT match, since they do have the role.
    $user_ids = $this->userSegmentQueryBuilder->getUserIds($rules);
    $this->assertContains($alice->id(), $user_ids, 'Should return Alice, they are now site manager.');
    $this->assertCount(1, $user_ids, 'Should return exactly 1 user being site manager.');
  }

  /**
   * User is removed from a segment when a role is removed.
   *
   * Scenario: 3.b.
   *
   * This test ensures that when a role is removed, the user is also removed
   * from the corresponding segment.
   */
  public function testUserIsRemovedFromSegmentWhenUserRoleRemoved(): void {
    // Define the rule that matches users with a site manager role.
    $rules = $this->getUserSegmentSiteManagerRule();
    // Create a user with the 'sitemanager' role assigned.
    $alice = $this->createUser(
      name: 'alice',
      values: [
        'roles' => ['sitemanager'],
      ],
    );
    $this->assertInstanceOf(User::class, $alice);

    // Test initial state: Alice SHOULD match, since they do have the role.
    $user_ids = $this->userSegmentQueryBuilder->getUserIds($rules);
    $this->assertContains($alice->id(), $user_ids, 'Should return Alice, they are site manager.');
    $this->assertCount(1, $user_ids, 'Should return exactly 1 user being site manager.');

    // Remove the 'sitemanager' role from Alice.
    $alice->removeRole('sitemanager');
    $alice->save();

    // Test updated state: Alice SHOULD NOT match, since they do not have the
    // role anymore.
    $user_ids = $this->userSegmentQueryBuilder->getUserIds($rules);
    $this->assertNotContains($alice->id(), $user_ids, 'Should not return Alice, they are no longer site manager.');
    $this->assertCount(0, $user_ids, 'Should return exactly 0 users being site managers.');
  }

  /**
   * Returns a user segment with a site manager rule.
   *
   * @return \Drupal\user_segments\DataObject\Rule
   *   Returns a use segment with a site manager rule.
   */
  private function getUserSegmentSiteManagerRule(): Rule {
    return new Rule(
      id: 1,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: PropertyRelationship::Include,
                  match: PropertyMatch::All,
                  config: [
                    'value' => [
                      'sitemanager',
                    ],
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );
  }

  /**
   * Tests role rules without conjunctions.
   *
   * Test all single rule permutations (8 rules) with:
   * - relationship Include/Exclude
   * - match Any/All
   * - roles administrator/administrator+sitemanager.
   *
   * Legend:
   * R = Relationship
   * I = Include
   * E = Exclude
   * M = Match
   * An = Any
   * Al = All
   * UR = User roles
   * A = Administrator
   * SM = Site manager
   *
   * | Rule | R  | M  | UR   |
   * | ---- | -- | -- | ---- |
   * | R1   | I  | An | A    |
   * | R2   | I  | Al | A    |
   * | R3   | I  | An | A,SM |
   * | R4   | I  | Al | A,SM |
   * | R5   | E  | An | A    |
   * | R6   | E  | Al | A    |
   * | R7   | E  | An | A,SM |
   * | R8   | E  | Al | A,SM |
   *
   * @param string $rule
   *   Rules configuration data.
   * @param array $expected
   *   Expected output data.
   *
   * @return void
   *   Return void.
   *
   * @dataProvider roleRulesProvider
   */
  public function testBaseRules(string $rule, array $expected) {
    // Create users and rules.
    $users = $this->createUsers();
    $rule = $this->buildRule(1, $rule);

    $results = $this->userSegmentQueryBuilder->getUserIds($rule);
    $segmented_users = array_map(
      fn ($uid) => $users[$uid],
      $results
    );
    $this->assertEqualsCanonicalizing($expected, $segmented_users);
  }

  /**
   * Tests role rules with conjunctions.
   *
   * @param array $rules
   *   Rules configuration data.
   * @param string $rules_conjunction
   *   Rule conjunction data.
   * @param array $expected
   *   Expected output data.
   *
   * @return void
   *   Return void.
   *
   * @dataProvider roleRuleConjunctionsProvider
   */
  public function testRoleRulesConjunctions(array $rules, string $rules_conjunction, array $expected) {
    // Create users.
    $users = $this->createUsers();

    // Create group conjunction data object.
    $conjunction = match ($rules_conjunction) {
      'AND' => RuleGroupConjunction::And,
      'OR'  => RuleGroupConjunction::Or,
      default => throw new \InvalidArgumentException("Unknown conjunction: $rules_conjunction"),
    };

    // Create a rule group from rules.
    $rule_1 = $this->buildRule(1, $rules[0]);
    $rule_2 = $this->buildRule(2, $rules[1]);
    $rule_group = new RuleGroup(
      id: 3,
      conjunction: $conjunction,
      rules: [$rule_1, $rule_2]
    );

    // Execute query.
    $results = $this->userSegmentQueryBuilder->getUserIds($rule_group);
    $segmented_users = array_map(
      fn ($uid) => $users[$uid],
      $results
    );

    // Test results.
    $this->assertEqualsCanonicalizing($expected, $segmented_users);
  }

  /**
   * Provides Rule data object from config.
   *
   * @param int $rule_id
   *   Unique rule id.
   * @param string $rule_label
   *   Rule label like R1, R2, etc.
   *
   * @return \Drupal\user_segments\DataObject\Rule
   *   Returns rule data object.
   */
  private function buildRule(int $rule_id, string $rule_label): Rule {
    $rule_config = $this->baseRulesConfig()[$rule_label];
    return new Rule(
      id: $rule_id,
      plugin: ProfileUserSegmentRule::PLUGIN_ID,
      conditionGroups: [
        new ConditionGroup(
          conjunction: ConditionGroupConjunction::And,
          conditions: [
            new Condition(
              condition_type: 'user_roles',
              properties: [
                new Property(
                  property_type: 'role',
                  relationship: $rule_config['relationship'],
                  match: $rule_config['match'],
                  config: [
                    'value' => $rule_config['value'],
                  ]
                ),
              ]
            ),
          ]
        ),
      ]
    );
  }

  /**
   * Generate test users with their roles.
   *
   * Generated users
   * A: No roles
   * B: Administrator
   * C: Site manager
   * D: Administrator and site manager.
   *
   * @return array $users<string, \Drupal\user\Entity\User>
   *   Returns list of users.
   */
  private function createUsers(): array {
    $user_config = [
      'A' => [],
      'B' => ['administrator'],
      'C' => ['sitemanager'],
      'D' => ['administrator', 'sitemanager'],
    ];

    // Create all users and all permutations of memberships.
    foreach ($user_config as $label => $permissions) {
      $user = $this->createUser(
        name: $label,
        values: [
          'roles' => $permissions,
        ],
      );
      $this->assertInstanceOf(User::class, $user, "Failed to create user: $label");
      $users[$user->id()] = $label;
    }

    return $users;
  }

  /**
   * The base rules configuration.
   *
   * Legend:
   * R = Relationship
   * I = Include
   * E = Exclude
   * M = Match
   * An = Any
   * Al = All
   * UR = User roles
   * A = Administrator
   * SM = Site manager.
   *
   * | Rule | R  | M  | UR   |
   * | ---- | -- | -- | ---- |
   * | R1   | I  | An | A    |
   * | R2   | I  | Al | A    |
   * | R3   | I  | An | A,SM |
   * | R4   | I  | Al | A,SM |
   * | R5   | E  | An | A    |
   * | R6   | E  | Al | A    |
   * | R7   | E  | An | A,SM |
   * | R8   | E  | Al | A,SM |
   *
   * @return array[]
   *   Returns list of rules configuration.
   */
  private function baseRulesConfig(): array {
    return [
      'R1' => [
        'relationship' => PropertyRelationship::Include,
        'match' => PropertyMatch::Any,
        'value' => [
          'administrator',
        ],
      ],
      'R2' => [
        'relationship' => PropertyRelationship::Include,
        'match' => PropertyMatch::All,
        'value' => [
          'administrator',
        ],
      ],
      'R3' => [
        'relationship' => PropertyRelationship::Include,
        'match' => PropertyMatch::Any,
        'value' => [
          'administrator',
          'sitemanager',
        ],
      ],
      'R4' => [
        'relationship' => PropertyRelationship::Include,
        'match' => PropertyMatch::All,
        'value' => [
          'administrator',
          'sitemanager',
        ],
      ],
      'R5' => [
        'relationship' => PropertyRelationship::Exclude,
        'match' => PropertyMatch::Any,
        'value' => [
          'administrator',
        ],
      ],
      'R6' => [
        'relationship' => PropertyRelationship::Exclude,
        'match' => PropertyMatch::All,
        'value' => [
          'administrator',
        ],
      ],
      'R7' => [
        'relationship' => PropertyRelationship::Exclude,
        'match' => PropertyMatch::Any,
        'value' => [
          'administrator',
          'sitemanager',
        ],
      ],
      'R8' => [
        'relationship' => PropertyRelationship::Exclude,
        'match' => PropertyMatch::All,
        'value' => [
          'administrator',
          'sitemanager',
        ],
      ],
    ];
  }

  /**
   * Generates the test data for the testBaseRules() test.
   *
   * Generates simple rules, with different relationships, matches and roles.
   * For all rules, calculate based on boolean algebra the set of users that
   * should be included in the segment. This can then be matched with the
   * implementation queried against the database.
   *
   * Scenario: 4.
   *
   * @return array
   *   List of rules with expected results.
   */
  public function roleRulesProvider(): array {
    return [
      'R1' => [
        'rule' => 'R1',
        'expected' => ['B', 'D'],
      ],
      'R2' => [
        'rule' => 'R2',
        'expected' => ['B', 'D'],
      ],
      'R3' => [
        'rule' => 'R3',
        'expected' => ['B', 'C', 'D'],
      ],
      'R4' => [
        'rule' => 'R4',
        'expected' => ['D'],
      ],
      'R5' => [
        'rule' => 'R5',
        'expected' => ['A', 'C'],
      ],
      'R6' => [
        'rule' => 'R6',
        'expected' => ['A', 'C'],
      ],
      'R7' => [
        'rule' => 'R7',
        'expected' => ['A'],
      ],
      'R8' => [
        'rule' => 'R8',
        'expected' => ['A', 'B', 'C'],
      ],
    ];
  }

  /**
   * Generates the test data for the roleRuleConjunctionsProvider() test.
   *
   * Generates all permutations on two rules conjunctions (based on eight simple
   * rules defined in baseRulesConfig()). For all rules, calculate based on
   * boolean algebra the set of users that should be included in the segment.
   * This can then be matched with the implementation queried against the
   * database.
   *
   * Scenario: 5.
   *
   * @return array[]
   *   List of rule conjunctions with expected results.
   */
  public function roleRuleConjunctionsProvider(): array {
    return [
      'R1 AND R2' => [
        'rules' => ['R1', 'R2'],
        'conjunction' => 'AND',
        'expected' => ['B', 'D'],
      ],
      'R1 OR R2' => [
        'rules' => ['R1', 'R2'],
        'conjunction' => 'OR',
        'expected' => ['B', 'D'],
      ],
      'R1 AND R3' => [
        'rules' => ['R1', 'R3'],
        'conjunction' => 'AND',
        'expected' => ['B', 'D'],
      ],
      'R1 OR R3' => [
        'rules' => ['R1', 'R3'],
        'conjunction' => 'OR',
        'expected' => ['B', 'C', 'D'],
      ],
      'R1 AND R4' => [
        'rules' => ['R1', 'R4'],
        'conjunction' => 'AND',
        'expected' => ['D'],
      ],
      'R1 OR R4' => [
        'rules' => ['R1', 'R4'],
        'conjunction' => 'OR',
        'expected' => ['B', 'D'],
      ],
      'R1 AND R5' => [
        'rules' => ['R1', 'R5'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R1 OR R5' => [
        'rules' => ['R1', 'R5'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R1 AND R6' => [
        'rules' => ['R1', 'R6'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R1 OR R6' => [
        'rules' => ['R1', 'R6'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R1 AND R7' => [
        'rules' => ['R1', 'R7'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R1 OR R7' => [
        'rules' => ['R1', 'R7'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'D'],
      ],
      'R1 AND R8' => [
        'rules' => ['R1', 'R8'],
        'conjunction' => 'AND',
        'expected' => ['B'],
      ],
      'R1 OR R8' => [
        'rules' => ['R1', 'R8'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R2 AND R3' => [
        'rules' => ['R2', 'R3'],
        'conjunction' => 'AND',
        'expected' => ['B', 'D'],
      ],
      'R2 OR R3' => [
        'rules' => ['R2', 'R3'],
        'conjunction' => 'OR',
        'expected' => ['B', 'C', 'D'],
      ],
      'R2 AND R4' => [
        'rules' => ['R2', 'R4'],
        'conjunction' => 'AND',
        'expected' => ['D'],
      ],
      'R2 OR R4' => [
        'rules' => ['R2', 'R4'],
        'conjunction' => 'OR',
        'expected' => ['B', 'D'],
      ],
      'R2 AND R5' => [
        'rules' => ['R2', 'R5'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R2 OR R5' => [
        'rules' => ['R2', 'R5'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R2 AND R6' => [
        'rules' => ['R2', 'R6'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R2 OR R6' => [
        'rules' => ['R2', 'R6'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R2 AND R7' => [
        'rules' => ['R2', 'R7'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R2 OR R7' => [
        'rules' => ['R2', 'R7'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'D'],
      ],
      'R2 AND R8' => [
        'rules' => ['R2', 'R8'],
        'conjunction' => 'AND',
        'expected' => ['B'],
      ],
      'R2 OR R8' => [
        'rules' => ['R2', 'R8'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R3 AND R4' => [
        'rules' => ['R3', 'R4'],
        'conjunction' => 'AND',
        'expected' => ['D'],
      ],
      'R3 OR R4' => [
        'rules' => ['R3', 'R4'],
        'conjunction' => 'OR',
        'expected' => ['B', 'C', 'D'],
      ],
      'R3 AND R5' => [
        'rules' => ['R3', 'R5'],
        'conjunction' => 'AND',
        'expected' => ['C'],
      ],
      'R3 OR R5' => [
        'rules' => ['R3', 'R5'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R3 AND R6' => [
        'rules' => ['R3', 'R6'],
        'conjunction' => 'AND',
        'expected' => ['C'],
      ],
      'R3 OR R6' => [
        'rules' => ['R3', 'R6'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R3 AND R7' => [
        'rules' => ['R3', 'R7'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R3 OR R7' => [
        'rules' => ['R3', 'R7'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R3 AND R8' => [
        'rules' => ['R3', 'R8'],
        'conjunction' => 'AND',
        'expected' => ['B', 'C'],
      ],
      'R3 OR R8' => [
        'rules' => ['R3', 'R8'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R4 AND R5' => [
        'rules' => ['R4', 'R5'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R4 OR R5' => [
        'rules' => ['R4', 'R5'],
        'conjunction' => 'OR',
        'expected' => ['A', 'C', 'D'],
      ],
      'R4 AND R6' => [
        'rules' => ['R4', 'R6'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R4 OR R6' => [
        'rules' => ['R4', 'R6'],
        'conjunction' => 'OR',
        'expected' => ['A', 'C', 'D'],
      ],
      'R4 AND R7' => [
        'rules' => ['R4', 'R7'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R4 OR R7' => [
        'rules' => ['R4', 'R7'],
        'conjunction' => 'OR',
        'expected' => ['A', 'D'],
      ],
      'R4 AND R8' => [
        'rules' => ['R4', 'R8'],
        'conjunction' => 'AND',
        'expected' => [],
      ],
      'R4 OR R8' => [
        'rules' => ['R4', 'R8'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C', 'D'],
      ],
      'R5 AND R6' => [
        'rules' => ['R5', 'R6'],
        'conjunction' => 'AND',
        'expected' => ['A', 'C'],
      ],
      'R5 OR R6' => [
        'rules' => ['R5', 'R6'],
        'conjunction' => 'OR',
        'expected' => ['A', 'C'],
      ],
      'R5 AND R7' => [
        'rules' => ['R5', 'R7'],
        'conjunction' => 'AND',
        'expected' => ['A'],
      ],
      'R5 OR R7' => [
        'rules' => ['R5', 'R7'],
        'conjunction' => 'OR',
        'expected' => ['A', 'C'],
      ],
      'R5 AND R8' => [
        'rules' => ['R5', 'R8'],
        'conjunction' => 'AND',
        'expected' => ['A', 'C'],
      ],
      'R5 OR R8' => [
        'rules' => ['R5', 'R8'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C'],
      ],
      'R6 AND R7' => [
        'rules' => ['R6', 'R7'],
        'conjunction' => 'AND',
        'expected' => ['A'],
      ],
      'R6 OR R7' => [
        'rules' => ['R6', 'R7'],
        'conjunction' => 'OR',
        'expected' => ['A', 'C'],
      ],
      'R6 AND R8' => [
        'rules' => ['R6', 'R8'],
        'conjunction' => 'AND',
        'expected' => ['A', 'C'],
      ],
      'R6 OR R8' => [
        'rules' => ['R6', 'R8'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C'],
      ],
      'R7 AND R8' => [
        'rules' => ['R7', 'R8'],
        'conjunction' => 'AND',
        'expected' => ['A'],
      ],
      'R7 OR R8' => [
        'rules' => ['R7', 'R8'],
        'conjunction' => 'OR',
        'expected' => ['A', 'B', 'C'],
      ],
    ];
  }

}
