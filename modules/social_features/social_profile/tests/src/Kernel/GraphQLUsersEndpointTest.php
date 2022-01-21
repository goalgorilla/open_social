<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;

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
    $profile = $this->ensureTestProfile($test_user, 'profile');
    $query = "
      query {
        user(id: \"{$test_user->uuid()}\") {
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
    ";
    $expected_data = [
      'data' => [
        'user' => [
          'profile' => [
            'firstName' => $profile->get('field_profile_first_name')->first()->getString(),
            'lastName' => $profile->get('field_profile_last_name')->first()->getString(),
            'introduction' => [
              'format' => [
                'name' => $profile->get('field_profile_self_introduction')->first()->get('format')->getString(),
              ],
              'raw' => $profile->get('field_profile_self_introduction')->first()->get('value')->getString(),
              'processed' => $profile->get('field_profile_self_introduction')->first()->get('processed')->getString(),
            ],
            'phone' => $profile->get('field_profile_phone_number')->first()->getString(),
            'function' => $profile->get('field_profile_function')->first()->getString(),
            'organization' => $profile->get('field_profile_organization')->first()->getString(),
          ],
        ],
      ],
    ];

    // @todo Move to QueryResultAssertionTrait::assertResults and add metadata.
    $result = $this->query($query);
    self::assertSame(200, $result->getStatusCode(), 'user fields are present');
    self::assertSame($expected_data, json_decode($result->getContent(), TRUE), 'user fields are present');
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
