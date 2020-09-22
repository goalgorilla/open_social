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
   * Expect the first three users in the dataset to be loaded.
   */
  public function testUsersQueryFirst() {
    $query = <<<GQL
      query {
        users(first: 3) {
          edges {
            cursor
            node {
              display_name
            }
          }
        }
      }
GQL;
    $expected = [
      "data" => [
        "users" => [
          "edges" => [
            [
              "cursor" => (new EntityEdge($this->users[0]))->getCursor(),
              "node" => [
                "display_name" => $this->users[0]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[1]))->getCursor(),
              "node" => [
                "display_name" => $this->users[1]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[2]))->getCursor(),
              "node" => [
                "display_name" => $this->users[2]->getDisplayName(),
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertQuery($query, $expected);
  }

  /**
   * Expect the last three users in the dataset to be loaded for reverse search.
   */
  public function testUsersQueryFirstReverse() {
    $last = count($this->users) - 1;
    $query = <<<GQL
      query {
        users(first: 3, reverse: true) {
          edges {
            cursor
            node {
              display_name
            }
          }
        }
      }
GQL;
    $expected = [
      "data" => [
        "users" => [
          "edges" => [
            [
              "cursor" => (new EntityEdge($this->users[$last]))->getCursor(),
              "node" => [
                "display_name" => $this->users[$last]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[$last - 1]))->getCursor(),
              "node" => [
                "display_name" => $this->users[$last - 1]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[$last - 2]))->getCursor(),
              "node" => [
                "display_name" => $this->users[$last - 2]->getDisplayName(),
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertQuery($query, $expected);
  }

  /**
   * Expect the last three users in the dataset to be loaded.
   */
  public function testUsersQueryLast() {
    $last = count($this->users) - 1;
    $query = <<<GQL
      query {
        users(last: 3) {
          edges {
            cursor
            node {
              display_name
            }
          }
        }
      }
GQL;
    $expected = [
      "data" => [
        "users" => [
          "edges" => [
            [
              "cursor" => (new EntityEdge($this->users[$last - 2]))->getCursor(),
              "node" => [
                "display_name" => $this->users[$last - 2]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[$last - 1]))->getCursor(),
              "node" => [
                "display_name" => $this->users[$last - 1]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[$last]))->getCursor(),
              "node" => [
                "display_name" => $this->users[$last]->getDisplayName(),
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertQuery($query, $expected);
  }

  /**
   * Expect the first three users to be loaded for reverse search.
   */
  public function testUsersQueryLastReverse() {
    $query = <<<GQL
      query {
        users(last: 3, reverse: true) {
          edges {
            cursor
            node {
              display_name
            }
          }
        }
      }
GQL;
    $expected = [
      "data" => [
        "users" => [
          "edges" => [
            [
              "cursor" => (new EntityEdge($this->users[2]))->getCursor(),
              "node" => [
                "display_name" => $this->users[2]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[1]))->getCursor(),
              "node" => [
                "display_name" => $this->users[1]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[0]))->getCursor(),
              "node" => [
                "display_name" => $this->users[0]->getDisplayName(),
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertQuery($query, $expected);
  }

  /**
   * Expect the last two users before a cursor.
   */
  public function testUsersQueryLastBefore() {
    $before = (new EntityEdge($this->users[4]))->getCursor();
    $query = "
      query {
        users(last: 2, before: ${before}) {
          edges {
            cursor
            node {
              display_name
            }
          }
        }
      }
    ";
    $expected = [
      "data" => [
        "users" => [
          "edges" => [
            [
              "cursor" => (new EntityEdge($this->users[2]))->getCursor(),
              "node" => [
                "display_name" => $this->users[2]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[3]))->getCursor(),
              "node" => [
                "display_name" => $this->users[3]->getDisplayName(),
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertQuery($query, $expected);
  }

  /**
   * Expect the first two users after a cursor.
   */
  public function testUsersQueryFirstAfter() {
    $after = (new EntityEdge($this->users[4]))->getCursor();
    $query = "
      query {
        users(first: 2, after: ${after}) {
          edges {
            cursor
            node {
              display_name
            }
          }
        }
      }
    ";
    $expected = [
      "data" => [
        "users" => [
          "edges" => [
            [
              "cursor" => (new EntityEdge($this->users[5]))->getCursor(),
              "node" => [
                "display_name" => $this->users[5]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[6]))->getCursor(),
              "node" => [
                "display_name" => $this->users[6]->getDisplayName(),
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertQuery($query, $expected);
  }

  /**
   * Expect the last two users before a cursor in a reversed search.
   */
  public function testUsersQueryLastBeforeReversed() {
    $before = (new EntityEdge($this->users[4]))->getCursor();
    $query = "
      query {
        users(last: 2, before: ${before}, reverse: true) {
          edges {
            cursor
            node {
              display_name
            }
          }
        }
      }
    ";
    $expected = [
      "data" => [
        "users" => [
          "edges" => [
            [
              "cursor" => (new EntityEdge($this->users[6]))->getCursor(),
              "node" => [
                "display_name" => $this->users[6]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[5]))->getCursor(),
              "node" => [
                "display_name" => $this->users[5]->getDisplayName(),
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertQuery($query, $expected);
  }

  /**
   * Expect the first two users after a cursor in a reversed search.
   */
  public function testUsersQueryFirstAfterReversed() {
    $after = (new EntityEdge($this->users[4]))->getCursor();
    $query = "
      query {
        users(first: 2, after: ${after}, reverse: true) {
          edges {
            cursor
            node {
              display_name
            }
          }
        }
      }
    ";
    $expected = [
      "data" => [
        "users" => [
          "edges" => [
            [
              "cursor" => (new EntityEdge($this->users[3]))->getCursor(),
              "node" => [
                "display_name" => $this->users[3]->getDisplayName(),
              ],
            ],
            [
              "cursor" => (new EntityEdge($this->users[2]))->getCursor(),
              "node" => [
                "display_name" => $this->users[2]->getDisplayName(),
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertQuery($query, $expected);
  }

  /**
   * Asserts that a GQL query executes correctly and matches an expected result.
   *
   * @param string $query
   *   The GraphQL query to test.
   * @param array $expected
   *   The expected output.
   */
  protected function assertQuery(string $query, array $expected): void {
    $result = $this->query($query);
    self::assertSame(200, $result->getStatusCode());
    self::assertSame($expected, json_decode($result->getContent(), TRUE));
  }

}
