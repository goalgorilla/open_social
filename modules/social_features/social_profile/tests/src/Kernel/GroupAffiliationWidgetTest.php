<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupType;
use Drupal\group\PermissionScopeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\GroupAffiliation;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Tests the group options offered by the group affiliation widget.
 *
 * @group social_profile
 *
 * @coversDefaultClass \Drupal\social_profile\Plugin\Field\FieldWidget\GroupAffiliationWidget
 */
class GroupAffiliationWidgetTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'profile',
    'entity',
    'flexible_permissions',
    'group',
    'file',
    'image',
    'address',
    'telephone',
    'taxonomy',
    'field_group',
    'crop',
    'image_widget_crop',
    'hux',
    'paragraphs',
    'entity_reference_revisions',
    'field',
    'text',
    'options',
    'smart_trim',
    'social_profile',
    'config_update',
    'update_helper',
    'config_modify',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // We don't need views in this test so no sense in enabling the views
    // module or validating the schema.
    "views.view.newest_users",
    "views.view.user_information",
    // config_modify tracks applied modifications in its own config object which
    // doesn't have a schema definition bundled with the module.
    "config_modify.applied",
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('profile_type');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('paragraphs_type');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['config_modify', 'group', 'social_profile']);

    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $this->createFlexibleGroupType();
  }

  /**
   * A member of a secret group can pick that group as their affiliation.
   *
   * Groups that are only visible to their members are still offered as
   * affiliation options to their own members: every option is a group the user
   * is a member of, and rendering the affiliation elsewhere is guarded by a
   * "view" access check on the group.
   *
   * @covers ::formElement
   */
  public function testSecretGroupIsOfferedAsAffiliationOption(): void {
    $user = $this->createUser();
    $this->assertInstanceOf(UserInterface::class, $user);

    $public_group = $this->createFlexibleGroup('Public group', 'public');
    $secret_group = $this->createFlexibleGroup('Secret group', 'members');
    $public_group->addMember($user);
    $secret_group->addMember($user);

    $options = $this->getAffiliationOptions($user, $public_group->id());

    $this->assertArrayHasKey(
      (int) $secret_group->id(),
      $options,
      'A group that is only visible to its members is offered to its own members.',
    );
    $this->assertArrayHasKey((int) $public_group->id(), $options);
  }

  /**
   * A stored secret group affiliation is selected instead of left blank.
   *
   * When the stored value is missing from the option list, the select renders
   * without a selection and a plain form save silently drops the affiliation.
   *
   * @covers ::formElement
   */
  public function testStoredSecretGroupAffiliationIsSelected(): void {
    $user = $this->createUser();
    $this->assertInstanceOf(UserInterface::class, $user);

    $secret_group = $this->createFlexibleGroup('Secret group', 'members');
    $secret_group->addMember($user);

    $options = $this->getAffiliationOptions($user, $secret_group->id());

    $this->assertArrayHasKey(
      (int) $secret_group->id(),
      $options,
      'The stored affiliation is among the options, so the select is not rendered blank.',
    );
  }

  /**
   * Builds the widget and returns its group options, keyed by group ID.
   *
   * @param \Drupal\user\UserInterface $user
   *   The owner of the profile that is being edited.
   * @param string|int|null $affiliation_group_id
   *   The group ID stored in the affiliation field.
   *
   * @return array<int, string>
   *   The selectable groups, keyed by group ID.
   */
  protected function getAffiliationOptions(UserInterface $user, string|int|null $affiliation_group_id): array {
    $profile = Profile::create([
      'type' => 'profile',
      'uid' => $user->id(),
      'status' => 1,
      GroupAffiliation::AFFILIATION_FIELD_NAME => $affiliation_group_id,
    ]);
    $profile->save();

    $items = $profile->get(GroupAffiliation::AFFILIATION_FIELD_NAME);
    $widget = $this->container->get('plugin.manager.field.widget')->getInstance([
      'field_definition' => $items->getFieldDefinition(),
      'form_mode' => 'default',
      'configuration' => [
        'type' => 'group_affiliation_widget',
        'settings' => [],
        'third_party_settings' => [],
      ],
    ]);
    $this->assertInstanceOf(WidgetInterface::class, $widget);

    $form = [];
    $form_state = new FormState();
    $element = $widget->formElement($items, 0, [], $form, $form_state);

    $options = [];
    foreach ($element['container']['group']['#options'] ?? [] as $groups) {
      foreach ((array) $groups as $group_id => $label) {
        $options[$group_id] = $label;
      }
    }

    return $options;
  }

  /**
   * Creates the flexible group type with its visibility field.
   */
  protected function createFlexibleGroupType(): void {
    $group_type = GroupType::create([
      'id' => 'flexible_group',
      'label' => 'Group',
    ]);
    $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_CANDIDATE_CONFIG_KEY, TRUE);
    $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, TRUE);
    $group_type->save();

    GroupRole::create([
      'id' => 'flexible_group-member',
      'label' => 'Member',
      'group_type' => 'flexible_group',
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view group'],
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_flexible_group_visibility',
      'entity_type' => 'group',
      'type' => 'list_string',
      'settings' => [
        // Storage settings hold the simple form; FieldStorageConfig turns it
        // into the structured config form on save.
        // @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::storageSettingsToConfigData()
        'allowed_values' => [
          'public' => 'Public',
          'community' => 'Community',
          'members' => 'Group members only (secret)',
        ],
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_flexible_group_visibility',
      'entity_type' => 'group',
      'bundle' => 'flexible_group',
      'label' => 'Group visibility',
      'required' => TRUE,
    ])->save();

    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);
  }

  /**
   * Creates a published flexible group with the given visibility.
   *
   * @param string $label
   *   The group label.
   * @param string $visibility
   *   The value for the group visibility field.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group.
   */
  protected function createFlexibleGroup(string $label, string $visibility): Group {
    $group = Group::create([
      'type' => 'flexible_group',
      'label' => $label,
      'status' => 1,
      'field_flexible_group_visibility' => $visibility,
    ]);
    $group->save();

    return $group;
  }

}
