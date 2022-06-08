<?php

namespace Drupal\Tests\graphql_oauth\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\consumers\Entity\Consumer;
use Drupal\graphql\Entity\Server;
use Drupal\graphql\Entity\ServerInterface;
use Drupal\simple_oauth\Authentication\TokenAuthUser;
use Drupal\simple_oauth\Entity\Oauth2Scope;
use Drupal\simple_oauth\Entity\Oauth2Token;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Tests OAuth directives.
 *
 * @group graphql_oauth
 */
class GraphqlOauthDirectiveTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'consumers',
    'entity',
    'file',
    'image',
    'options',
    'serialization',
    'simple_oauth',
    'graphql_oauth',
    'graphql_oauth_test',
  ];

  /**
   * The current user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Scope 1 entity.
   *
   * @var \Drupal\simple_oauth\Entity\Oauth2Scope
   */
  protected $scope1;

  /**
   * Scope 2 entity.
   *
   * @var \Drupal\simple_oauth\Entity\Oauth2Scope
   */
  protected $scope2;

  /**
   * Access token entity.
   *
   * @var \Drupal\simple_oauth\Entity\Oauth2Token
   */
  protected $accessToken;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installConfig('graphql_oauth_test');
    $this->installEntitySchema('oauth2_scope');
    $this->installEntitySchema('consumer');
    $this->installEntitySchema('oauth2_token');
    $this->installConfig(['simple_oauth']);

    $server = Server::load("graphql_oauth_test");
    assert($server instanceof ServerInterface);
    $this->server = $server;

    $this->user = $this->setUpCurrentUser([], $this->userPermissions());

    $client = Consumer::create([
      'label' => $this->getRandomGenerator()->name(),
      'secret' => $this->getRandomGenerator()->string(),
      'grant_types' => [
        'authorization_code',
        'client_credentials',
      ],
      'user_id' => $this->user,
    ]);
    $client->save();

    $this->scope1 = Oauth2Scope::create([
      'name' => 'test:scope1',
      'description' => $this->getRandomGenerator()->sentences(5),
      'grant_types' => [
        'authorization_code' => [
          'status' => TRUE,
        ],
        'client_credentials' => [
          'status' => TRUE,
        ],
      ],
      'permission' => 'access content',
    ]);
    $this->scope1->save();

    $this->scope2 = Oauth2Scope::create([
      'name' => 'test:scope2',
      'description' => $this->getRandomGenerator()->sentences(5),
      'grant_types' => [
        'authorization_code' => [
          'status' => TRUE,
        ],
        'client_credentials' => [
          'status' => TRUE,
        ],
      ],
      'permission' => 'debug simple_oauth tokens',
    ]);
    $this->scope2->save();

    $this->accessToken = Oauth2Token::create([
      'bundle' => 'access_token',
      'auth_user_id' => NULL,
      'client' => $client,
      'scopes' => [$this->scope1->id()],
      'value' => $this->getRandomGenerator()->string(),
      'expire' => \Drupal::time()->getRequestTime() + 1000,
    ]);
    $this->accessToken->save();
  }

  /**
   * Test allow user/all access using single scope.
   */
  public function testAccessUserSingleScope(): void {
    $query = $this->buildGraphqlQuery(
      '
        allowUserSingleScope
        allowAllSingleScope
      ',
      '
        allowUserSingleScope {
          test
        }
        allowAllSingleScope {
          test
        }
      ',
    );
    $error = "Missing scope 'test:scope1' on 'allowUserSingleScope'.";
    $this->setAccountProxy([$this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'Bot' does not have access on 'allowUserSingleScope'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $expected_fields = [
      'allowUserSingleScope' => 'test',
      'allowAllSingleScope' => 'test',
    ];
    $expected_types = [
      'allowUserSingleScope' => ['test' => 'test'],
      'allowAllSingleScope' => ['test' => 'test'],
    ];
    $this->assertResults(
      $query,
      [],
      $this->buildExpectedResults($expected_fields, $expected_types),
      $this->getCacheMetaData()
    );
  }

  /**
   * Test allow user/all access using multiple scopes.
   */
  public function testAccessUserMultipleScopes(): void {
    $query = $this->buildGraphqlQuery(
      '
        allowUserMultipleScopes
        allowAllMultipleScopes
      ',
      '
        allowUserMultipleScopes {
          test
        }
        allowAllMultipleScopes {
          test
        }
      ',
    );
    $error = "Missing scope 'test:scope1' on 'allowUserMultipleScopes'.";
    $this->setAccountProxy([$this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'Bot' does not have access on 'allowUserMultipleScopes'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $expected_fields = [
      'allowUserMultipleScopes' => 'test',
      'allowAllMultipleScopes' => 'test',
    ];
    $expected_types = [
      'allowUserMultipleScopes' => ['test' => 'test'],
      'allowAllMultipleScopes' => ['test' => 'test'],
    ];
    $this->assertResults(
      $query,
      [],
      $this->buildExpectedResults($expected_fields, $expected_types),
      $this->getCacheMetaData()
    );
  }

  /**
   * Test allow user/all access using nested scopes.
   */
  public function testAccessUserSingleNestedScope(): void {
    $query = $this->buildGraphqlQuery(
      '
        allowUserSingleScope
      ',
      '
        allowUserSingleScope {
          fieldUser
          fieldAll
        }
      ',
    );
    $error = "Missing scope 'test:scope1' on 'allowUserSingleScope'.";
    $this->setAccountProxy([$this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'Bot' does not have access on 'allowUserSingleScope'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $expected_fields = [
      'allowUserSingleScope' => 'test',
    ];
    $expected_types = [
      'allowUserSingleScope' => [
        'fieldUser' => 'test',
        'fieldAll' => 'test',
      ],
    ];
    $this->assertResults(
      $query,
      [],
      $this->buildExpectedResults($expected_fields, $expected_types),
      $this->getCacheMetaData()
    );
  }

  /**
   * Test allow bot/all access using single scope.
   */
  public function testAccessBotSingleScope(): void {
    $query = $this->buildGraphqlQuery(
      '
        allowBotSingleScope
        allowAllSingleScope
      ',
      '
        allowBotSingleScope {
          test
        }
        allowAllSingleScope {
          test
        }
      ',
    );
    $error = "Missing scope 'test:scope1' on 'allowBotSingleScope'.";
    $this->setAccountProxy([$this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'User' does not have access on 'allowBotSingleScope'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $expected_fields = [
      'allowBotSingleScope' => 'test',
      'allowAllSingleScope' => 'test',
    ];
    $expected_types = [
      'allowBotSingleScope' => ['test' => 'test'],
      'allowAllSingleScope' => ['test' => 'test'],
    ];
    $this->assertResults(
      $query,
      [],
      $this->buildExpectedResults($expected_fields, $expected_types),
      $this->getCacheMetaData()
    );
  }

  /**
   * Test allow bot/all access using multiple scopes.
   */
  public function testAccessBotMultipleScopes(): void {
    $query = $this->buildGraphqlQuery(
      '
        allowBotMultipleScopes
        allowAllMultipleScopes
      ',
      '
        allowBotMultipleScopes {
          test
        }
        allowAllMultipleScopes {
          test
        }
      ',
    );
    $error = "Missing scope 'test:scope1' on 'allowBotMultipleScopes'.";
    $this->setAccountProxy([$this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'User' does not have access on 'allowBotMultipleScopes'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $expected_fields = [
      'allowBotMultipleScopes' => 'test',
      'allowAllMultipleScopes' => 'test',
    ];
    $expected_types = [
      'allowBotMultipleScopes' => ['test' => 'test'],
      'allowAllMultipleScopes' => ['test' => 'test'],
    ];
    $this->assertResults(
      $query,
      [],
      $this->buildExpectedResults($expected_fields, $expected_types),
      $this->getCacheMetaData()
    );
  }

  /**
   * Test multiple user/bot scope directives.
   */
  public function testAccessMultipleDirectives(): void {
    $query = $this->buildGraphqlQuery(
      '
        allowMultipleDirectiveScopes
      ',
      '
        allowMultipleDirectiveScopes {
          test
        }
      ',
    );
    // Test error when a scope is not granted on the associated grant type.
    $error = "Missing scope 'test:scope1' on 'allowMultipleDirectiveScopes'.";
    $this->setAccountProxy();
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      ["Missing scope 'test:scope2' on 'allowMultipleDirectiveScopes'."],
      $this->getCacheMetaData(TRUE)
    );
    // Test results by proper application type and scopes.
    $this->setAccountProxy([$this->scope1->id()]);
    $this->assertResults(
      $query,
      [],
      $this->buildExpectedResults(
        [
          'allowMultipleDirectiveScopes' => 'test',
        ],
        [
          'allowMultipleDirectiveScopes' => ['test' => 'test'],
        ],
      ),
      $this->getCacheMetaData()
    );
    $this->setAccountProxy([$this->scope2->id()], FALSE);
    $this->assertResults(
      $query,
      [],
      $this->buildExpectedResults(
        [
          'allowMultipleDirectiveScopes' => 'test',
        ],
        [
          'allowMultipleDirectiveScopes' => ['test' => 'test'],
        ],
      ),
      $this->getCacheMetaData()
    );
  }

  /**
   * Test allowUser field access on query.
   */
  public function testQueryFieldAccessUser(): void {
    $query = '
      query {
        testQueryAccessFieldUser {
          allowUserSingleScope
        }
      }
    ';
    $error = "Missing scope 'test:scope2' on 'testQueryAccessFieldUser'.";
    $this->setAccountProxy([$this->scope1->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Missing scope 'test:scope1' on 'allowUserSingleScope'.";
    $this->setAccountProxy([$this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'Bot' does not have access on 'testQueryAccessFieldUser'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $this->assertResults(
      $query,
      [],
      [
        'testQueryAccessFieldUser' => [
          'allowUserSingleScope' => 'test',
        ],
      ],
      $this->getCacheMetaData()
    );
  }

  /**
   * Test allowBot field access on query.
   */
  public function testQueryFieldAccessBot(): void {
    $query = '
      query {
        testQueryAccessFieldBot {
          allowBotSingleScope
        }
      }
    ';
    $error = "Missing scope 'test:scope2' on 'testQueryAccessFieldBot'.";
    $this->setAccountProxy([$this->scope1->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Missing scope 'test:scope1' on 'allowBotSingleScope'.";
    $this->setAccountProxy([$this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'User' does not have access on 'testQueryAccessFieldBot'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $this->assertResults(
      $query,
      [],
      [
        'testQueryAccessFieldBot' => [
          'allowBotSingleScope' => 'test',
        ],
      ],
      $this->getCacheMetaData()
    );
  }

  /**
   * Test allowUser type access on query.
   */
  public function testQueryAccessTypeUser(): void {
    $query = '
      query {
        testQueryAccessTypeUser {
          test
        }
      }
    ';
    $error = "Missing scope 'test:scope2' on 'testQueryAccessTypeUser'.";
    $this->setAccountProxy([$this->scope1->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Missing scope 'test:scope1' on 'TestQueryAccessTypeUser'.";
    $this->setAccountProxy([$this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'Bot' does not have access on 'testQueryAccessTypeUser'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $this->assertResults(
      $query,
      [],
      [
        'testQueryAccessTypeUser' => [
          'test' => 'test',
        ],
      ],
      $this->getCacheMetaData()
    );
  }

  /**
   * Test allowBot type access on query.
   */
  public function testQueryAccessTypeBot(): void {
    $query = '
      query {
        testQueryAccessTypeBot {
          test
        }
      }
    ';
    $error = "Missing scope 'test:scope2' on 'testQueryAccessTypeBot'.";
    $this->setAccountProxy([$this->scope1->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Missing scope 'test:scope1' on 'TestQueryAccessTypeBot'.";
    $this->setAccountProxy([$this->scope2->id()], FALSE);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $error = "Application type 'User' does not have access on 'testQueryAccessTypeBot'.";
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()]);
    $this->assertErrors(
      $query,
      [],
      [$error],
      $this->getCacheMetaData(TRUE)
    );
    $this->setAccountProxy([$this->scope1->id(), $this->scope2->id()], FALSE);
    $this->assertResults(
      $query,
      [],
      [
        'testQueryAccessTypeBot' => [
          'test' => 'test',
        ],
      ],
      $this->getCacheMetaData()
    );
  }

  /**
   * Sets the account proxy.
   *
   * @param array $scopes
   *   Scopes to set on the access token.
   * @param bool $user
   *   Authorizes on behalf of a user (target: USER).
   */
  private function setAccountProxy(array $scopes = [], bool $user = TRUE): void {
    $token = clone $this->accessToken;
    $token->set('scopes', $scopes);

    if ($user) {
      $token->set('auth_user_id', $this->user);
    }

    $this->setCurrentUser(new TokenAuthUser($token));
  }

  /**
   * Get the cache meta data.
   *
   * @param bool $error
   *   Set to true if expecting an error.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The cache metadata object.
   */
  private function getCacheMetaData(bool $error = FALSE): CacheableMetadata {
    $max_age = $error ? 0 : -1;
    return $this->defaultCacheMetaData()
      ->setCacheMaxAge($max_age)
      ->addCacheContexts(['languages:language_interface']);
  }

  /**
   * Build the GraphQL query.
   *
   * @param string $fields
   *   The query fields.
   * @param string $types
   *   The query types.
   *
   * @return string
   *   The GraphQL query.
   */
  private function buildGraphqlQuery(string $fields, string $types): string {
    return '
      query {
        testAccessField { ' . $fields . ' }
        testAccessFieldNonNull { ' . $fields . ' }
        testAccessFieldArray { ' . $fields . ' }
        testAccessFieldNonNullArray { ' . $fields . ' }
        testAccessFieldNonNullArrayItem { ' . $fields . ' }
        testAccessFieldNonNullArrayAndItem { ' . $fields . ' }
        testAccessType { ' . $types . ' }
        testAccessTypeNonNull { ' . $types . ' }
        testAccessTypeArray { ' . $types . ' }
        testAccessTypeNonNullArray { ' . $types . ' }
        testAccessTypeNonNullArrayItem { ' . $types . ' }
        testAccessTypeNonNullArrayAndItem { ' . $types . ' }
      }
    ';
  }

  /**
   * Builds the expected GraphQL results.
   *
   * @param array $fields
   *   The fields result.
   * @param array $types
   *   The types result.
   *
   * @return array
   *   Returns the expected results.
   */
  private function buildExpectedResults(array $fields, array $types): array {
    return [
      'testAccessField' => $fields,
      'testAccessFieldNonNull' => $fields,
      'testAccessFieldArray' => [$fields],
      'testAccessFieldNonNullArray' => [$fields],
      'testAccessFieldNonNullArrayItem' => [$fields],
      'testAccessFieldNonNullArrayAndItem' => [$fields],
      'testAccessType' => $types,
      'testAccessTypeNonNull' => $types,
      'testAccessTypeArray' => [$types],
      'testAccessTypeNonNullArray' => [$types],
      'testAccessTypeNonNullArrayItem' => [$types],
      'testAccessTypeNonNullArrayAndItem' => [$types],
    ];
  }

  /**
   * Returns the default cache maximum age for the test.
   */
  protected function defaultCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

  /**
   * Provides the user permissions that the test user is set up with.
   *
   * @return string[]
   *   List of user permissions.
   */
  protected function userPermissions(): array {
    return ['access content', 'bypass graphql access'];
  }

}
