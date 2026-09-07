<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupType;
use Drupal\group\PermissionScopeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\GroupAffiliation;
use Drupal\social_profile_privacy\Service\SocialProfilePrivacyHelperInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Tests that the affiliation token obeys the profile privacy settings.
 *
 * The display gate never looks at social_profile_privacy itself. It asks the
 * field item lists for view access, and that module answers through
 * hook_entity_field_access(). This test exists because that indirection is the
 * whole argument: it proves the answer actually changes when a viewer is not
 * allowed to see the field.
 *
 * @see social_profile_privacy_entity_field_access()
 * @see \Drupal\social_profile\Service\AffiliationDisplay::getDisplayableAffiliation()
 *
 * @group social_profile
 */
class AffiliationPrivacyTest extends KernelTestBase {

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
    'filter',
    'smart_trim',
    'token',
    'social_profile',
    'config_update',
    'update_helper',
    'config_modify',
    'social_profile_fields',
    'social_profile_privacy',
    'language',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // Views are not needed here, so the modules providing them are not enabled
    // and their schema cannot be validated.
    "views.view.newest_users",
    "views.view.user_information",
    // config_modify tracks applied modifications in its own config object which
    // does not ship a schema definition.
    "config_modify.applied",
  ];

  /**
   * The user the affiliation belongs to.
   */
  protected User $author;

  /**
   * Another member of the affiliated group, who reads the stream.
   */
  protected User $viewer;

  /**
   * The group the author is affiliated with.
   */
  protected Group $group;

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
    // Per-user field visibility is stored through the user.data service.
    $this->installSchema('user', ['users_data']);
    // config_modify must come first: its config installer reads
    // config_modify.applied while installing any other module's config.
    $this->installConfig([
      'config_modify',
      'system',
      'filter',
      'language',
      'group',
      'social_profile',
      'social_profile_fields',
      'social_profile_privacy',
    ]);

    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $this->createAffiliationGroupType('affiliated_group');

    $this->group = Group::create([
      'type' => 'affiliated_group',
      'label' => 'Acme Corp',
    ]);
    $this->group->save();

    $author = $this->createUser();
    $this->assertNotFalse($author);
    $this->author = $author;
    $this->group->addMember($this->author);

    // Joining the group already creates the default profile, so reuse it
    // rather than adding a second, non-default one the token would not load.
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    $profile = $profile_storage->loadByUser($this->author, 'profile') ?? Profile::create([
      'type' => 'profile',
      'uid' => $this->author->id(),
      'status' => 1,
    ]);
    $profile->set('field_group_affiliation', $this->group->id());
    $profile->save();

    $viewer = $this->createUser();
    $this->assertNotFalse($viewer);
    $this->viewer = $viewer;
    // The viewer has to be a member, otherwise the group itself is invisible
    // and every assertion below would pass for the wrong reason.
    $this->group->addMember($this->viewer);

    $this->setCurrentUser($this->viewer);
  }

  /**
   * A viewer who may see the field gets the affiliation.
   *
   * Positive control for every other test in this class.
   */
  public function testVisibleFieldIsShownToAnotherMember(): void {
    $this->assertSame(' @Acme Corp', $this->replace());
  }

  /**
   * A field hidden site-wide is not shown to another member.
   */
  public function testGloballyHiddenFieldEmitsNothing(): void {
    $this->hideAffiliationField(SocialProfilePrivacyHelperInterface::HIDE);

    $this->assertSame('', $this->replace());
  }

  /**
   * The owner keeps seeing their own hidden affiliation.
   */
  public function testOwnerStillSeesTheirHiddenAffiliation(): void {
    $this->hideAffiliationField(SocialProfilePrivacyHelperInterface::HIDE);
    $this->setCurrentUser($this->author);
    drupal_static_reset();

    $this->assertSame(' @Acme Corp', $this->replace());
  }

  /**
   * A field the user chose to hide is not shown to another member.
   */
  public function testFieldHiddenByTheUserEmitsNothing(): void {
    $this->hideAffiliationField(SocialProfilePrivacyHelperInterface::CONFIGURABLE);
    \Drupal::service('user.data')->set(
      'social_profile_privacy',
      (int) $this->author->id(),
      'fields',
      ['field_group_affiliation' => FALSE],
    );
    drupal_static_reset();

    $this->assertSame('', $this->replace());
  }

  /**
   * A configurable field the user left alone is still shown.
   */
  public function testConfigurableFieldLeftVisibleIsShown(): void {
    $this->hideAffiliationField(SocialProfilePrivacyHelperInterface::CONFIGURABLE);

    $this->assertSame(' @Acme Corp', $this->replace());
  }

  /**
   * The permission to view hidden fields also reveals the affiliation.
   */
  public function testPermissionToViewHiddenFieldsShowsTheAffiliation(): void {
    $this->hideAffiliationField(SocialProfilePrivacyHelperInterface::HIDE);
    $privileged = $this->createUser(['social profile privacy view hidden fields']);
    $this->assertNotFalse($privileged);
    $this->group->addMember($privileged);
    $this->setCurrentUser($privileged);
    drupal_static_reset();

    $this->assertSame(' @Acme Corp', $this->replace());
  }

  /**
   * Replaces the affiliation token for the author, as the current user.
   *
   * @return string
   *   The replaced text.
   */
  protected function replace(): string {
    return (string) \Drupal::token()->replace(
      '[user:primary-affiliation:raw]',
      ['user' => $this->author],
      ['activity_view_mode' => 'default', 'clear' => TRUE],
    );
  }

  /**
   * Marks the group affiliation field as hidden or configurable.
   *
   * @param int $visibility
   *   One of the SocialProfilePrivacyHelperInterface constants.
   */
  protected function hideAffiliationField(int $visibility): void {
    $this->config('social_profile_privacy.settings')
      ->set('fields', [GroupAffiliation::AFFILIATION_FIELD_NAME => $visibility])
      ->save();
    drupal_static_reset();
  }

  /**
   * Creates a group type that is an affiliation candidate.
   *
   * @param string $group_type_id
   *   The group type id.
   */
  protected function createAffiliationGroupType(string $group_type_id): void {
    $group_type = GroupType::create([
      'id' => $group_type_id,
      'label' => 'Affiliated group',
    ]);
    $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_CANDIDATE_CONFIG_KEY, TRUE);
    $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, TRUE);
    $group_type->save();

    // Members must be able to view the group, the affiliation is only shown to
    // users who may see it.
    GroupRole::create([
      'id' => $group_type_id . '-member',
      'label' => 'Member',
      'group_type' => $group_type_id,
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view group'],
    ])->save();
  }

}
