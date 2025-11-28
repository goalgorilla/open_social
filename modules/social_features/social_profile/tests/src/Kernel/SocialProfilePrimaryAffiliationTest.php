<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupMembership;
use Drupal\group\Entity\GroupType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\Entity\Bundle\SocialProfile;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\GroupAffiliation;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the getPrimaryAffiliation() method of SocialProfile.
 *
 * @group social_profile
 */
class SocialProfilePrimaryAffiliationTest extends KernelTestBase {

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
    'social_profile',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // We don't need views in this test so no sense in enabling the views
    // module or validating the schema.
    "views.view.newest_users",
    "views.view.user_information",
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
    $this->installConfig(['group', 'social_profile']);
  }

  /**
   * Tests getPrimaryAffiliation() when affiliation feature is disabled.
   */
  public function testGetPrimaryAffiliationWhenFeatureDisabled(): void {
    // Disable affiliation feature.
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', FALSE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $user = $this->createUser();
    $this->assertNotFalse($user);
    $profile = Profile::create([
      'type' => 'profile',
      'uid' => $user->id(),
      'status' => 1,
    ]);
    $profile->save();

    assert($profile instanceof ProfileAffiliationInterface);
    $affiliation = $profile->getPrimaryAffiliation();

    // @phpstan-ignore-next-line method.alreadyNarrowedType
    $this->assertIsArray($affiliation);
    $this->assertEmpty($affiliation, 'Should return empty array when affiliation feature is disabled');
  }

  /**
   * Tests getPrimaryAffiliation() with platform affiliation (group).
   */
  public function testGetPrimaryAffiliationWithPlatformAffiliation(): void {
    // Enable affiliation feature.
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $profile = $this->createProfileWithAffiliation(
      'platform',
      'test_group',
      'Test Group Type',
      'Test Group'
    );

    $affiliation = $profile->getPrimaryAffiliation();

    // @phpstan-ignore-next-line method.alreadyNarrowedType
    $this->assertIsArray($affiliation);
    $this->assertNotEmpty($affiliation, 'Should return affiliation when platform affiliation exists');
    $this->assertEquals('Test Group', $affiliation['affiliation_name']);
    $this->assertEquals('Test Group Type', $affiliation['affiliation_type']);
    $this->assertArrayNotHasKey('affiliation_function', $affiliation, 'Should not have function when membership has no function field');
  }

  /**
   * Tests getPrimaryAffiliation() with platform affiliation and function.
   */
  public function testGetPrimaryAffiliationWithPlatformAffiliationAndFunction(): void {
    // Enable affiliation feature.
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    // Create group membership with function field.
    $profile = $this->createProfileWithAffiliation(
      'platform',
      'test_group',
      'Test Group Type',
      'Test Group',
      NULL,
      NULL,
      'Test Function'
    );

    $affiliation = $profile->getPrimaryAffiliation();

    // @phpstan-ignore-next-line method.alreadyNarrowedType
    $this->assertIsArray($affiliation);
    $this->assertNotEmpty($affiliation);
    $this->assertEquals('Test Group', $affiliation['affiliation_name']);
    $this->assertEquals('Test Group Type', $affiliation['affiliation_type']);
    $this->assertArrayHasKey('affiliation_function', $affiliation, 'Should have function when membership has function field');
    $this->assertEquals('Test Function', $affiliation['affiliation_function']);
  }

  /**
   * Tests getPrimaryAffiliation() with non-platform affiliation fallback.
   */
  public function testGetPrimaryAffiliationWithNonPlatformAffiliation(): void {
    // Enable affiliation feature.
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $profile = $this->createProfileWithAffiliation(
      'non-platform',
      NULL,
      NULL,
      NULL,
      'External Organization',
      'External Function'
    );

    $affiliation = $profile->getPrimaryAffiliation();

    // @phpstan-ignore-next-line method.alreadyNarrowedType
    $this->assertIsArray($affiliation);
    $this->assertNotEmpty($affiliation, 'Should return affiliation when non-platform affiliation exists');
    $this->assertEquals('External Organization', $affiliation['affiliation_name']);
    $this->assertEquals('External Function', $affiliation['affiliation_function']);
    $this->assertEquals('External', $affiliation['affiliation_type']);
  }

  /**
   * Tests getPrimaryAffiliation() prioritizes platform over non-platform.
   */
  public function testGetPrimaryAffiliationPlatformOverNonPlatform(): void {
    // Enable affiliation feature.
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    // Create both platform and non-platform affiliations.
    $group = $this->createGroupTypeAndGroup('test_group', 'Test Group Type', 'Platform Group');
    $paragraph = $this->createNonPlatformAffiliation('External Organization', 'External Function');

    $user = $this->createUser();
    $this->assertNotFalse($user);
    $profile = Profile::create([
      'type' => 'profile',
      'uid' => $user->id(),
      'status' => 1,
      'field_group_affiliation' => $group->id(),
      'field_enable_other_affiliations' => TRUE,
      'field_other_affiliations' => $paragraph,
    ]);
    $profile->save();

    assert($profile instanceof ProfileAffiliationInterface);
    $affiliation = $profile->getPrimaryAffiliation();

    // @phpstan-ignore-next-line method.alreadyNarrowedType
    $this->assertIsArray($affiliation);
    $this->assertNotEmpty($affiliation);
    // Platform affiliation should take priority.
    $this->assertEquals('Platform Group', $affiliation['affiliation_name']);
    $this->assertEquals('Test Group Type', $affiliation['affiliation_type']);
    $this->assertNotEquals('External Organization', $affiliation['affiliation_name']);
    $this->assertNotEquals('External', $affiliation['affiliation_type']);
  }

  /**
   * Tests getPrimaryAffiliation() when no affiliation exists.
   */
  public function testGetPrimaryAffiliationWhenNoAffiliation(): void {
    // Enable affiliation feature.
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $user = $this->createUser();
    $this->assertNotFalse($user);
    $profile = Profile::create([
      'type' => 'profile',
      'uid' => $user->id(),
      'status' => 1,
    ]);
    $profile->save();

    assert($profile instanceof ProfileAffiliationInterface);
    $affiliation = $profile->getPrimaryAffiliation();

    // @phpstan-ignore-next-line method.alreadyNarrowedType
    $this->assertIsArray($affiliation);
    $this->assertEmpty($affiliation, 'Should return empty array when no affiliation exists');
  }

  /**
   * Tests non-platform affiliation when enable field is FALSE.
   */
  public function testNonPlatformAffiliationWhenDisabled(): void {
    // Enable affiliation feature.
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    // Create paragraph for non-platform affiliation.
    $paragraph = $this->createNonPlatformAffiliation('External Organization', 'External Function');

    $user = $this->createUser();
    $this->assertNotFalse($user);
    // Set enable_other_affiliations to FALSE.
    $profile = Profile::create([
      'type' => 'profile',
      'uid' => $user->id(),
      'status' => 1,
      'field_enable_other_affiliations' => FALSE,
      'field_other_affiliations' => $paragraph,
    ]);
    $profile->save();

    assert($profile instanceof ProfileAffiliationInterface);
    $affiliation = $profile->getPrimaryAffiliation();

    // @phpstan-ignore-next-line method.alreadyNarrowedType
    $this->assertIsArray($affiliation);
    $this->assertEmpty($affiliation, 'Should return empty array when other affiliations are disabled');
  }

  /**
   * Data provider for helper method tests.
   *
   * @return array
   *   Array of test cases with:
   *   - method: The method name to test
   *   - setup_type: 'platform', 'non-platform', or 'empty'
   *   - expected: Expected return value
   *   - group_type_id: (optional) Group type ID for platform setup
   *   - group_type_label: (optional) Group type label for platform setup
   *   - group_label: (optional) Group label for platform setup
   *   - org_name: (optional) Organization name for non-platform setup
   *   - org_function: (optional) Organization function for non-platform setup
   */
  public function providerHelperMethods(): array {
    return [
      'getPrimaryAffiliationName with platform affiliation' => [
        'method' => 'getPrimaryAffiliationName',
        'setup_type' => 'platform',
        'expected' => 'Test Group Name',
        'group_type_id' => 'test_group',
        'group_type_label' => 'Test Group Type',
        'group_label' => 'Test Group Name',
        'org_name' => NULL,
        'org_function' => NULL,
      ],
      'getPrimaryAffiliationName with non-platform affiliation' => [
        'method' => 'getPrimaryAffiliationName',
        'setup_type' => 'non-platform',
        'expected' => 'External Organization',
        'group_type_id' => NULL,
        'group_type_label' => NULL,
        'group_label' => NULL,
        'org_name' => 'External Organization',
        'org_function' => 'External Function',
      ],
      'getPrimaryAffiliationName with empty affiliation' => [
        'method' => 'getPrimaryAffiliationName',
        'setup_type' => 'empty',
        'expected' => '',
        'group_type_id' => NULL,
        'group_type_label' => NULL,
        'group_label' => NULL,
        'org_name' => NULL,
        'org_function' => NULL,
      ],
      'getPrimaryAffiliationFunction with non-platform affiliation' => [
        'method' => 'getPrimaryAffiliationFunction',
        'setup_type' => 'non-platform',
        'expected' => 'External Function',
        'group_type_id' => NULL,
        'group_type_label' => NULL,
        'group_label' => NULL,
        'org_name' => 'External Organization',
        'org_function' => 'External Function',
      ],
      'getPrimaryAffiliationFunction with empty affiliation' => [
        'method' => 'getPrimaryAffiliationFunction',
        'setup_type' => 'empty',
        'expected' => '',
        'group_type_id' => NULL,
        'group_type_label' => NULL,
        'group_label' => NULL,
        'org_name' => NULL,
        'org_function' => NULL,
      ],
      'getPrimaryAffiliationType with platform affiliation' => [
        'method' => 'getPrimaryAffiliationType',
        'setup_type' => 'platform',
        'expected' => 'Test Group Type',
        'group_type_id' => 'test_group',
        'group_type_label' => 'Test Group Type',
        'group_label' => 'Test Group',
        'org_name' => NULL,
        'org_function' => NULL,
      ],
      'getPrimaryAffiliationType with non-platform affiliation' => [
        'method' => 'getPrimaryAffiliationType',
        'setup_type' => 'non-platform',
        'expected' => 'External',
        'group_type_id' => NULL,
        'group_type_label' => NULL,
        'group_label' => NULL,
        'org_name' => 'External Organization',
        'org_function' => 'External Function',
      ],
      'getPrimaryAffiliationType with empty affiliation' => [
        'method' => 'getPrimaryAffiliationType',
        'setup_type' => 'empty',
        'expected' => '',
        'group_type_id' => NULL,
        'group_type_label' => NULL,
        'group_label' => NULL,
        'org_name' => NULL,
        'org_function' => NULL,
      ],
    ];
  }

  /**
   * Tests helper methods with various scenarios.
   *
   * @param string $method
   *   The method name to test.
   * @param string $setup_type
   *   The setup type: 'platform', 'non-platform', or 'empty'.
   * @param string $expected
   *   Expected return value.
   * @param string|null $group_type_id
   *   (optional) Group type ID for platform setup.
   * @param string|null $group_type_label
   *   (optional) Group type label for platform setup.
   * @param string|null $group_label
   *   (optional) Group label for platform setup.
   * @param string|null $org_name
   *   (optional) Organization name for non-platform setup.
   * @param string|null $org_function
   *   (optional) Organization function for non-platform setup.
   *
   * @dataProvider providerHelperMethods
   */
  public function testHelperMethods(
    string $method,
    string $setup_type,
    string $expected,
    ?string $group_type_id = NULL,
    ?string $group_type_label = NULL,
    ?string $group_label = NULL,
    ?string $org_name = NULL,
    ?string $org_function = NULL,
  ): void {
    // Enable affiliation feature.
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $profile = $this->createProfileWithAffiliation(
      $setup_type,
      $group_type_id,
      $group_type_label,
      $group_label,
      $org_name,
      $org_function
    );

    $result = $profile->$method();

    $this->assertEquals($expected, $result);
  }

  /**
   * Creates a group type and group.
   *
   * @param string $group_type_id
   *   Group type ID.
   * @param string $group_type_label
   *   Group type label.
   * @param string $group_label
   *   Group label.
   *
   * @return \Drupal\group\Entity\Group
   *   Created group entity.
   */
  protected function createGroupTypeAndGroup(string $group_type_id, string $group_type_label, string $group_label): Group {
    // Check if group type already exists (e.g., from previous test cases).
    $group_type_storage = $this->container->get('entity_type.manager')->getStorage('group_type');
    $group_type = $group_type_storage->load($group_type_id);

    if (!$group_type) {
      $group_type = GroupType::create([
        'id' => $group_type_id,
        'label' => $group_type_label,
      ]);
      // Enable affiliation for this group type so cache invalidation hooks
      // work.
      $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_CANDIDATE_CONFIG_KEY, TRUE);
      $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, TRUE);
      $group_type->save();
    }

    $group = Group::create([
      'type' => $group_type_id,
      'label' => $group_label,
    ]);
    $group->save();

    return $group;
  }

  /**
   * Creates a non-platform affiliation paragraph.
   *
   * @param string $org_name
   *   Organization name (required, cannot be empty).
   * @param string $org_function
   *   Organization function (required, cannot be empty).
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   Created paragraph entity.
   *
   * @throws \InvalidArgumentException
   *   If either org_name or org_function is empty.
   */
  protected function createNonPlatformAffiliation(string $org_name, string $org_function): Paragraph {
    if (empty($org_name) || empty($org_function)) {
      throw new \InvalidArgumentException('Non-platform affiliations require both organization name and function to be non-empty.');
    }

    $paragraph = Paragraph::create([
      'type' => 'other_affiliations',
      'field_affiliation_org_name' => $org_name,
      'field_affiliation_org_function' => $org_function,
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Creates a profile with affiliation based on setup type.
   *
   * @param string $setup_type
   *   Setup type: 'platform', 'non-platform', or 'empty'.
   * @param string|null $group_type_id
   *   (optional) Group type ID for platform setup.
   * @param string|null $group_type_label
   *   (optional) Group type label for platform setup.
   * @param string|null $group_label
   *   (optional) Group label for platform setup.
   * @param string|null $org_name
   *   (optional) Organization name for non-platform setup.
   * @param string|null $org_function
   *   (optional) Organization function for non-platform setup.
   * @param string|null $membership_function
   *   (optional) Membership function for platform setup.
   *
   * @return \Drupal\social_profile\Entity\Bundle\SocialProfile
   *   Created profile entity.
   */
  protected function createProfileWithAffiliation(
    string $setup_type,
    ?string $group_type_id = NULL,
    ?string $group_type_label = NULL,
    ?string $group_label = NULL,
    ?string $org_name = NULL,
    ?string $org_function = NULL,
    ?string $membership_function = NULL,
  ): SocialProfile {
    $user = $this->createUser();
    $this->assertNotFalse($user);
    $profile_data = [
      'type' => 'profile',
      'uid' => $user->id(),
      'status' => 1,
    ];

    if ($setup_type === 'platform' && $group_type_id && $group_type_label && $group_label) {
      $group = $this->createGroupTypeAndGroup($group_type_id, $group_type_label, $group_label);
      $profile_data['field_group_affiliation'] = $group->id();

      // Add user as member and set function field if provided.
      $group->addMember($user);
      if ($membership_function !== NULL) {
        // Ensure the field exists on the membership bundle.
        $this->ensureMembershipFunctionField($group_type_id);
        $membership = GroupMembership::loadSingle($group, $user);
        if ($membership instanceof GroupMembership && $membership->hasField('field_affiliation_function')) {
          $membership->set('field_affiliation_function', $membership_function);
          $membership->save();
        }
      }
    }
    elseif ($setup_type === 'non-platform') {
      // Non-platform affiliations require both org_name and org_function to be
      // non-empty.
      if ($org_name === NULL || $org_function === NULL || $org_name === '' || $org_function === '') {
        throw new \InvalidArgumentException('Non-platform affiliations require both organization name and function to be non-empty.');
      }
      $profile_data['field_enable_other_affiliations'] = TRUE;
    }

    $profile = Profile::create($profile_data);

    // For non-platform affiliations, append the paragraph after profile
    // creation (matching how addNonPlatformAffiliation() does it).
    if ($setup_type === 'non-platform' && $org_name !== NULL && $org_function !== NULL) {
      $paragraph = $this->createNonPlatformAffiliation($org_name, $org_function);
      $profile->get('field_other_affiliations')->appendItem([
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ]);
    }

    $profile->save();

    assert($profile instanceof SocialProfile);
    return $profile;
  }

  /**
   * Ensures field_affiliation_function exists on group membership bundle.
   *
   * @param string $group_type_id
   *   The group type ID.
   */
  protected function ensureMembershipFunctionField(string $group_type_id): void {
    $bundle = $group_type_id . '-group_membership';
    $field_id = "group_content.{$bundle}.field_affiliation_function";

    // Check if field already exists.
    $field_config = FieldConfig::load($field_id);
    if ($field_config) {
      return;
    }

    // Ensure field storage exists.
    $field_storage = FieldStorageConfig::load('group_content.field_affiliation_function');
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => 'field_affiliation_function',
        'entity_type' => 'group_content',
        'type' => 'string',
        'settings' => [
          'max_length' => 255,
        ],
      ]);
      $field_storage->save();
    }

    // Create field instance.
    $field_config = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => 'Function',
    ]);
    $field_config->save();
  }

}
