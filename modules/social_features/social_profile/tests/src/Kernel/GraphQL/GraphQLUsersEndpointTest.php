<?php

namespace Drupal\Tests\social_profile\Kernel\GraphQL;

use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests the additions made to the user endpoint by this module.
 *
 * @group social_graphql
 */
class GraphQLUsersEndpointTest extends SocialGraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    "social_user",
    // User creation in social_user requires a service in role_delegation.
    // @todo Possibly untangle this?
    "role_delegation",
    // Profile is needed for the profile storage.
    "profile",
    // Required for third party config schema.
    "field_group",
    // Modules needed for profile fields.
    "file",
    "image",
    "address",
    "taxonomy",
    "telephone",
    "text",
    "options",
    "filter",
    "lazy",
    "image_widget_crop",
    "crop",
    // The actual module under test.
    "social_profile",
  ];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // @todo when https://www.drupal.org/project/social/issues/3238713 is fixed.
    "core.entity_form_display.profile.profile.default",
    // We don't need views in the GraphQL API so no sense in enabling the views
    // module or validating the schema.
    "views.view.newest_users",
    "views.view.user_information",
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('profile_type');
    $this->installEntitySchema('profile');
    $this->installConfig('social_profile');
  }

  /**
   * Ensure that the profile fields are properly added to the user endpoint.
   *
   * This test does not test the validity of the resolved data but merely that
   * the API contract is adhered to.
   *
   * @todo This test does not test the profile image functionality.
   */
  public function testUserProfileFieldsPresence() : void {
    // Test as the admin users, this allows us to test all the fields that are
    // available in an all-access scenario.
    $this->setUpCurrentUser([], [], TRUE);
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->ensureTestProfile($test_user, 'profile');

    $first_name = $profile->get('field_profile_first_name')->first();
    $last_name = $profile->get('field_profile_last_name')->first();
    /** @var \Drupal\Core\Field\FieldItemInterface|NULL $self_introduction */
    $self_introduction = $profile->get('field_profile_self_introduction')->first();
    $phone_number = $profile->get('field_profile_phone_number')->first();
    $function = $profile->get('field_profile_function')->first();
    $organization = $profile->get('field_profile_organization')->first();

    self::assertNotNull($first_name);
    self::assertNotNull($last_name);
    self::assertNotNull($self_introduction);
    self::assertNotNull($phone_number);
    self::assertNotNull($function);
    self::assertNotNull($organization);

    $self_introduction_format = $self_introduction->get('format');
    $self_introduction_value = $self_introduction->get('value');
    $self_introduction_processed = $self_introduction->get('processed');

    self::assertNotNull($self_introduction_format);
    self::assertNotNull($self_introduction_value);
    self::assertNotNull($self_introduction_processed);

    $this->assertResults(
      '
        query ($id: ID!) {
          user(id: $id) {
            profile {
              firstName
              lastName
              introduction {
                format {
                  name
                }
                raw
                processed
              }
              phone
              function
              organization
            }
          }
        }
      ',
      ['id' => $test_user->uuid()],
      [
        'user' => [
          'profile' => [
            'firstName' => $first_name->getString(),
            'lastName' => $last_name->getString(),
            'introduction' => [
              'format' => [
                'name' => $self_introduction_format->getString(),
              ],
              'raw' => $self_introduction_value->getString(),
              'processed' => $self_introduction_processed->getString(),
            ],
            'phone' => $phone_number->getString(),
            'function' => $function->getString(),
            'organization' => $organization->getString(),
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($test_user)
        ->addCacheableDependency($profile)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['profile_list'])
    );
  }

  /**
   * Test that additional permissions are required for profile fields.
   */
  public function testRequiresPermissionForProfileFields() : void {
    $this->setUpCurrentUser([], ['access user profiles']);
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $this->ensureTestProfile($test_user, 'profile');

    $this->assertResults(
      '
        query ($id: ID!) {
          user(id: $id) {
            profile {
              firstName
              lastName
              introduction {
                format {
                  name
                }
                raw
                processed
              }
              phone
              function
              organization
            }
          }
        }
      ',
      ['id' => $test_user->uuid()],
      [
        'user' => [
          'profile' => [
            'firstName' => NULL,
            'lastName' => NULL,
            'introduction' => NULL,
            'phone' => NULL,
            'function' => NULL,
            'organization' => NULL,
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['profile_list'])
    );
  }

  /**
   * Ensures a test profile exists for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to create or modify a profile for.
   * @param string $profile_type
   *   The type of profile to create or modify.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The profile that was updated or created.
   */
  protected function ensureTestProfile(AccountInterface $user, string $profile_type): ProfileInterface {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');
    $profile = $profile_storage->loadByUser($user, $profile_type);
    self::assertInstanceOf(ProfileInterface::class, $profile, "Test set-up failed: could not load profile.");
    $profile
      ->set('field_profile_first_name', $this->randomString())
      ->set('field_profile_last_name', $this->randomString())
      ->set(
        'field_profile_self_introduction',
        ['format' => 'basic_html', 'value' => $this->randomString()]
      )
      ->set('field_profile_phone_number', $this->randomString())
      ->set('field_profile_function', $this->randomString())
      ->set('field_profile_organization', $this->randomString())
      ->save();

    return $profile;
  }

}
