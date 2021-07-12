<?php

namespace Drupal\Tests\social_user\Kernel\GraphQL;

use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the users endpoint added to the Open Social schema by this module.
 *
 * Users require the `access user profiles` permission to view users which is
 * granted to the anonymous role by default. To ensure anonymous users can not
 * list users an extra permission is introduced to user listing.
 *
 * @todo Add test for blocked users.
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
  ];

  /**
   * Test the filter for the users query.
   */
  public function testUsersQueryFilter(): void {
    // Create a set of 10 test users that we can query. The data of the users
    // shouldn't matter.
    for ($i = 0; $i < 10; ++$i) {
      $users[] = $this->createUser();
    }

    // We must include the current user in the test data because it'll also be
    // listed.
    $users[] = $this->setUpCurrentUser([], ['access user profiles', 'list user']);
    $this->assertEndpointSupportsPagination(
      'users',
      array_map(static fn (UserInterface $user) => $user->uuid(), $users)
    );
  }

  /**
   * Ensure that the fields for the user endpoint properly resolve.
   */
  public function testUserFieldsPresence() : void {
    $this->setUpCurrentUser([], ['administer users']);
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $query = '
      query ($id: ID!) {
        user(id: $id) {
          id
          displayName
          mail
          created
          updated
          status
          roles
        }
      }
    ';
    $expected_data = [
      'user' => [
        'id' => $test_user->uuid(),
        'displayName' => $test_user->getDisplayName(),
        'mail' => $test_user->getEmail(),
        'created' => $test_user->getCreatedTime(),
        'updated' => $test_user->getChangedTime(),
        'status' => 'ACTIVE',
        'roles' => ['authenticated'],
      ],
    ];

    $this->assertResults(
      $query,
      ['id' => $test_user->uuid()],
      $expected_data,
      $this->defaultCacheMetaData()
        ->addCacheableDependency($test_user)
        // @todo It's unclear why this cache context is added.
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that a user without permission can not load a user.
   */
  public function testPermissionlessUserCannotLoadUser() : void {
    $this->setUpCurrentUser();
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $this->assertResults(
      '
        query ($id: ID!) {
          user(id: $id) {
            id
          }
        }
      ',
      ['id' => $test_user->uuid()],
      [
        'user' => NULL,
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($test_user)
        // @todo It's unclear why this cache context is added.
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that permissions are needed to list all users on a platform.
   *
   * This limits access for pages like all-members to authenticated users.
   */
  public function testUsersNotEnumerableWithoutListPermission() : void {
    // Create some test users.
    for ($i = 0; $i < 10; ++$i) {
      $users[] = $this->createUser();
    }

    $this->setUpCurrentUser([], ['access user profiles']);
    $this->assertResults(
      '
        query {
          users(last: 5) {
            pageInfo {
              hasNextPage
              hasPreviousPage
              startCursor
              endCursor
            }
            edges {
              node {
                id
              }
            }
            nodes {
              id
            }
          }
        }
      ',
      [],
      [
        'users' => [
          'pageInfo' => [
            'hasNextPage' => FALSE,
            'hasPreviousPage' => FALSE,
            'startCursor' => NULL,
            'endCursor' => NULL,
          ],
          'edges' => [],
          'nodes' => [],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that permissions are needed to list all users on a platform.
   *
   * This tests that we can list users but since we're not allowed to see
   * individual users we still don't see anything.
   */
  public function testUsersEnumerableInvisibleWithoutAccessPermission() : void {
    // Create some test users.
    for ($i = 0; $i < 10; ++$i) {
      $users[] = $this->createUser();
    }

    $this->setUpCurrentUser([], ['list user']);
    $this->assertResults(
      '
        query {
          users(last: 5) {
            pageInfo {
              hasNextPage
              hasPreviousPage
              startCursor
              endCursor
            }
            edges {
              node {
                id
              }
            }
            nodes {
              id
            }
          }
        }
      ',
      [],
      [
        'users' => [
          'pageInfo' => [
            'hasNextPage' => FALSE,
            'hasPreviousPage' => FALSE,
            'startCursor' => NULL,
            'endCursor' => NULL,
          ],
          'edges' => [],
          'nodes' => [],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test that everyone can access a user's id.
   */
  public function testIdAlwaysVisible() : void {
    $test_user = $this->createUser([], "test_user");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $this->setUpCurrentUser([], ['access user profiles']);
    $this->assertResults(
      '
        query ($id: ID!) {
          user(id: $id) {
            id
          }
        }
      ',
      ['id' => $test_user->uuid()],
      [
        'user' => [
          'id' => $test_user->uuid(),
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that everyone can see a user's display name.
   */
  public function testDisplayNameAlwaysVisible() : void {
    $test_user = $this->createUser([], "test_user");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $this->setUpCurrentUser([], ['access user profiles']);
    $this->assertResults(
      '
        query ($id: ID!) {
          user(id: $id) {
            displayName
          }
        }
      ',
      ['id' => $test_user->uuid()],
      [
        'user' => [
          'displayName' => 'test_user',
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test users without permission can't see the email.
   */
  public function testMailNotVisibleWithoutPermission() : void {
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $this->setUpCurrentUser([], ['access user profiles']);
    $this->assertResults(
      '
        query ($id: ID!) {
          user(id: $id) {
            mail
          }
        }
      ',
      ['id' => $test_user->uuid()],
      [
        'user' => [
          'mail' => NULL,
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test users that can view profiles can see the mail.
   */
  public function testMailVisibleForViewProfilePermission() : void {
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $this->setUpCurrentUser([], ['access user profiles', 'SOME EXTRA PERMISSION']);
    $this->assertResults(
      '
        query ($id: ID!) {
          user(id: $id) {
            mail
          }
        }
      ',
      ['id' => $test_user->uuid()],
      [
        'user' => [
          'mail' => $test_user->getEmail(),
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
