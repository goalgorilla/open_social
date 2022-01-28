<?php

namespace Drupal\Tests\social_user\Kernel\GraphQL;

use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the root viewer endpoint.
 *
 * @group social_graphql
 */
class GraphQLViewerEndpointTest extends SocialGraphQLTestBase {

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
   * It loads the current user.
   */
  public function testViewerLoadsCurrentUser() : void {
    $user = $this->setUpCurrentUser();
    self::assertInstanceOf(UserInterface::class, $user, "Test setup failure: could not create test user.");

    $this->assertResults(
      "
        query {
          viewer {
            id
          }
        }
      ",
      [],
      [
        'viewer' => [
          'id' => $user->uuid(),
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheableDependency($user)
        // @todo It's unclear why this cache context is added.
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
