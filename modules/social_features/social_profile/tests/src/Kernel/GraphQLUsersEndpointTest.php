<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_profile\Entity\Bundle\SocialProfile;
use Drupal\social_profile_privacy\Service\SocialProfilePrivacyHelperInterface;
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
    "group",
    "paragraphs",
    "entity_reference_revisions",
    "hux",
    // The actual module under test.
    "social_profile",
    "social_profile_privacy",
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
    // Paragraph schema is needed for affiliation data.
    $this->installEntitySchema('paragraph');

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
    assert($profile instanceof SocialProfile);
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
            'function' => $profile->getPrimaryAffiliationFunction(),
            'organization' => $profile->getPrimaryAffiliationName(),
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
   * Test that field level access is properly applied for profile fields.
   */
  public function testFieldLevelAccess() : void {
    // Test as a specific user with limited permissions so that they can not
    // view the fields.
    $this->setUpCurrentUser(
      [], [
        // The user is allowed to execute requests.
        'execute open_social_graphql arbitrary graphql requests',
        // They're also able to view basic user information.
        'access user profiles',
        // They're allowed to see the profiles of users.
        'view any profile',
      ]
    );
    // Ensure the firstName, lastName, and phone fields can not be seen.
    $this
      ->config('social_profile_privacy.settings')
      ->set(
        'fields',
        [
          'field_profile_first_name' => SocialProfilePrivacyHelperInterface::HIDE,
          'field_profile_last_name' => SocialProfilePrivacyHelperInterface::HIDE,
          'field_profile_phone_number' => SocialProfilePrivacyHelperInterface::HIDE,
        ]
      )
      ->save(TRUE);

    $test_user = $this->createUser();
    assert($test_user !== FALSE, "Could not create test user");
    $profile = $this->ensureTestProfile($test_user, 'profile');
    assert($profile instanceof SocialProfile);
    $query = "
      query {
        user(id: \"{$test_user->uuid()}\") {
          profile {
            firstName
            lastName
            phone
          }
        }
      }
    ";
    $expected_data = [
      'data' => [
        'user' => [
          'profile' => [
            'firstName' => NULL,
            'lastName' => NULL,
            'phone' => NULL,
          ],
        ],
      ],
    ];

    // @todo Move to QueryResultAssertionTrait::assertResults and add metadata.
    $result = $this->query($query);
    $response_body = $result->getContent();
    assert($response_body !== FALSE);
    self::assertSame(200, $result->getStatusCode(), 'Error executing the query');
    self::assertSame($expected_data, json_decode($response_body, TRUE), 'Profile fields did not return NULL as they should');
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
    assert($profile instanceof SocialProfile);
    $profile
      ->set('field_profile_first_name', $this->randomString())
      ->set('field_profile_last_name', $this->randomString())
      ->set(
        'field_profile_self_introduction',
        ['format' => 'basic_html', 'value' => $this->randomString()]
      )
      ->set('field_profile_phone_number', $this->randomString())
      ->set('field_enable_other_affiliations', TRUE)
      ->addNonPlatformAffiliation($this->randomString(), $this->randomString())
      ->save();

    return $profile;
  }

}
