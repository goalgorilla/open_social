<?php

namespace Drupal\Tests\social_user\Kernel\GraphQL;

use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the users endpoint added to the Open Social schema by this module.
 *
 * This uses the GraphQLTestBase which extends KernelTestBase since this class
 * is interested in testing the implementation of the GraphQL schema that's a
 * part of this module. We're not interested in the HTTP functionality since
 * that is covered by the graphql module itself. Thus BrowserTestBase is not
 * needed.
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
    $users[] = $this->setUpCurrentUser([], ['administer users', 'access content', 'bypass graphql access']);
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
   * Test that permissions are needed to list all users on a platform.
   *
   * This limits access for pages like all-members to authenticated users.
   */
  public function testUsersNotEnumerableWithoutPermission() : void {
    // Create some test users.
    for ($i = 0; $i < 10; ++$i) {
      $users[] = $this->createUser();
    }

    // Testing should be done with individual permissions rather than as
    // anonymous user but the correct permissions don't exist yet.
    // @todo Fix with DS-7613.
    $this->setUpCurrentUser([
      'uid' => 0,
      'status' => 0,
      'name' => '',
    ]);
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

}
