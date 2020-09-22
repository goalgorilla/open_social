<?php

namespace Drupal\Tests\social_user\Kernel;

use Drupal\graphql\Entity\Server;
use Drupal\social_graphql\Wrappers\EntityEdge;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\user\Entity\User;

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
class GraphQLUsersEndpointTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    "social_graphql",
    "social_user",
    // User creation in social_user requires a service in role_delegation.
    // TODO: Possibly untangle this?
    "role_delegation",
  ];

  /**
   * An array of test users that serves as test data.
   *
   * @var \Drupal\user\Entity\User[]
   */
  private $users = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // We need the configuration for social_graphql module to be loaded as it
    // contains the Open Social GraphQL server.
    $this->installConfig("social_graphql");

    // TODO: Abstract this to an OpenSocialGraphQLTestBase.
    // Set up schema.
    $this->server = Server::load("open_social_graphql");

    // Load the existing non-anonymous users as they're part of the dataset that
    // we want to verify test output against.
    $this->users = array_values(
      array_filter(
        User::loadMultiple(),
        static function (User $u) {
          return !$u->isAnonymous();
        }
      )
    );
    // Create a set of 10 test users that we can query. The data of the users
    // shouldn't matter.
    for ($i = 0; $i < 10; ++$i) {
      $this->users[] = $this->createUser();
    }
  }

  /**
   * Test the filter for the users query.
   */
  public function testUsersQueryFilter(): void {
    // We call the test data in a single test because the source doesn't change.
    // This is faster than having individual tests per query.
    foreach ($this->provideUsersQueryFilterData() as [$filter, $users]) {
      // Create a query for the filter under test. Include some data that allow
      // verifying the results.
      $query = "
        query {
          users(${filter}) {
            edges {
              cursor
              node {
                display_name
              }
            }
          }
        }
      ";
      // Create our expectation array.
      $expected = [
        "data" => [
          "users" => [
            "edges" => array_map(
              static function ($user) {
                return [
                  "cursor" => (new EntityEdge($user))->getCursor(),
                  "node" => [
                    "display_name" => $user->getDisplayName(),
                  ],
                ];
              },
              $users
            ),
          ],
        ],
      ];

      $this->assertQuery($query, $expected, "users(${filter})");
    }
  }

  /**
   * Provides filters and matching expected users.
   */
  public function provideUsersQueryFilterData() {
    $last = count($this->users) - 1;

    yield [
      'first: 3',
      [$this->users[0], $this->users[1], $this->users[2]],
    ];

    yield [
      "first: 3, reverse: true",
      [$this->users[$last], $this->users[$last - 1], $this->users[$last - 2]],
    ];

    yield [
      "last: 3",
      [$this->users[$last - 2], $this->users[$last - 1], $this->users[$last]],
    ];

    yield [
      "last: 3, reverse: true",
      [$this->users[2], $this->users[1], $this->users[0]],
    ];

    $cursor = (new EntityEdge($this->users[4]))->getCursor();
    yield [
      "last: 2, before: ${cursor}",
      [$this->users[2], $this->users[3]],
    ];

    yield [
      "first: 2, after: ${cursor}",
      [$this->users[5], $this->users[6]],
    ];

    yield [
      "last: 2, before: ${cursor}, reverse: true",
      [$this->users[6], $this->users[5]],
    ];

    yield [
      "first: 2, after: ${cursor}, reverse: true",
      [$this->users[3], $this->users[2]],
    ];
  }

  /**
   * Asserts that a GQL query executes correctly and matches an expected result.
   *
   * @param string $query
   *   The GraphQL query to test.
   * @param array $expected
   *   The expected output.
   * @param string $message
   *   An optional message to provide context in case of assertion failure.
   */
  protected function assertQuery(string $query, array $expected, string $message = ''): void {
    $result = $this->query($query);
    self::assertSame(200, $result->getStatusCode(), $message);
    self::assertSame($expected, json_decode($result->getContent(), TRUE), $message);
  }

}
