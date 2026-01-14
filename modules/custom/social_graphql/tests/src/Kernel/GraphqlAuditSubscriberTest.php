<?php

declare(strict_types=1);

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\graphql\Entity\Server;
use Drupal\graphql\Plugin\GraphQL\PersistedQuery\AutomaticPersistedQuery;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\test_social_graphql_example_audit\Logger\InMemoryLogger;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Test the GraphqlAuditSubscriber class.
 */
class GraphqlAuditSubscriberTest extends KernelTestBase {

  use UserCreationTrait;
  use OAuthTestTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'entity',
    'file',
    'image',
    'options',
    'serialization',
    'graphql',
    'typed_data',
    'consumers',
    'simple_oauth_static_scope',
    'simple_oauth',
    "social_graphql",
    "test_social_graphql_example_audit",
  ];

  /**
   * The logger service that the audit logger logs to.
   */
  protected InMemoryLogger $auditLogger;

  /**
   * The GraphQL server to execute queries again.
   */
  protected Server $server;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('system');
    $this->installConfig('graphql');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('consumer');
    $this->installEntitySchema('oauth2_token');
    $this->installEntitySchema('oauth2_scope');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('graphql_server');

    $this->installConfig("test_social_graphql_example_audit");

    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();

    $this->auditLogger = new InMemoryLogger();
    $this->container->set('logger.channel.social_graphql_audit', $this->auditLogger);

    $server = $this->container->get('entity_type.manager')
      ->getStorage('graphql_server')
      ->load('test');
    assert($server instanceof Server);
    $this->server = $server;

    // Create a user with UID 1 so that our tests use other UIDs. This makes our
    // tests invariant to the "User 1 accidentally has all permissions" setting.
    $this->setUpCurrentUser();
  }

  /**
   * Set up the content to execute queries.
   *
   * @return array
   *   The created node and query to run.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setupContent() : array {
    $author = $this->createUser([], "John");
    assert($author !== FALSE, "Could not create user");

    $node = Node::create([
      'type' => 'article',
      'uid' => $author->id(),
      'title' => 'Hello World',
    ]);
    $node->save();

    $query = <<<'EOF'
query Test($id: ID!) {
  article(id: $id) {
    id
    label
    author {
      id
      displayName
    }
  }
}
EOF;

    return [$node, $query];
  }

  /**
   * Test that a query executed with login session is logged.
   */
  public function testAuditLoggerLogsSessionExecutedQuery() : void {
    $viewer = $this->setUpCurrentUser();

    [$node, $query] = $this->setupContent();

    $executionResult = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    self::assertQuerySuccessful($executionResult->errors);

    $loggedMessage = $this->auditLogger->getLastMessage();
    self::assertIsArray($loggedMessage);
    self::assertTrue(isset($loggedMessage['context']['performance']['duration_ms']));
    self::assertEquals(
      [
        'query' => [
          'type' => 'query',
          'server_entity_id' => 'test',
          'server_entity_uuid' => $this->server->uuid(),
          'operation' => NULL,
          'query' => $query,
          'query_id' => NULL,
          'readonly' => FALSE,
        ],
        'auth' => [
          'type' => 'cookie',
          'mode' => NULL,
          'user_id' => $viewer->id(),
          'consumer_id' => NULL,
        ],
        'success' => TRUE,
        'from_cache' => FALSE,
        'performance' => [
          // This is a somewhat weird assertion, but the duration is always
          // slightly different.
          'duration_ms' => $loggedMessage['context']['performance']['duration_ms'],
          'query_count' => 4,
        ],
      ],
      $loggedMessage['context']
    );
  }

  /**
   * Test that a query cache hit with login session is logged.
   */
  public function testAuditLoggerLogsSessionCachedQuery() : void {
    $viewer = $this->setUpCurrentUser();

    [$node, $query] = $this->setupContent();

    // Execute to cache.
    $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    $executionResult = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    self::assertQuerySuccessful($executionResult->errors);

    $loggedMessage = $this->auditLogger->getLastMessage();
    self::assertIsArray($loggedMessage);
    self::assertEquals(
      [
        'query' => [
          'type' => 'query',
          'server_entity_id' => 'test',
          'server_entity_uuid' => $this->server->uuid(),
          'operation' => NULL,
          'query' => $query,
          'query_id' => NULL,
          'readonly' => FALSE,
        ],
        'auth' => [
          'type' => 'cookie',
          'mode' => NULL,
          'user_id' => $viewer->id(),
          'consumer_id' => NULL,
        ],
        'success' => TRUE,
        'from_cache' => TRUE,
        // Cached queries don't have performance data.
        'performance' => NULL,
      ],
      $loggedMessage['context']
    );
  }

  /**
   * Test that a readonly operation query executed with login session is logged.
   */
  public function testAuditLoggerLogsSessionExecutedReadonlyOperationQuery() : void {
    $viewer = $this->setUpCurrentUser();

    [$node, $query] = $this->setupContent();

    $executionResult = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'operationname' => 'Test',
      'variables' => ['id' => $node->uuid()],
    ], TRUE));

    self::assertQuerySuccessful($executionResult->errors);

    $loggedMessage = $this->auditLogger->getLastMessage();
    self::assertIsArray($loggedMessage);
    self::assertTrue(isset($loggedMessage['context']['performance']['duration_ms']));
    self::assertEquals(
      [
        'query' => [
          'type' => 'query',
          'server_entity_id' => 'test',
          'server_entity_uuid' => $this->server->uuid(),
          'operation' => 'Test',
          'query' => $query,
          'query_id' => NULL,
          'readonly' => TRUE,
        ],
        'auth' => [
          'type' => 'cookie',
          'mode' => NULL,
          'user_id' => $viewer->id(),
          'consumer_id' => NULL,
        ],
        'success' => TRUE,
        'from_cache' => FALSE,
        'performance' => [
          // This is a somewhat weird assertion, but the duration is always
          // slightly different.
          'duration_ms' => $loggedMessage['context']['performance']['duration_ms'],
          'query_count' => 4,
        ],
      ],
      $loggedMessage['context']
    );
  }

  /**
   * Test that a persisted query with login session is logged.
   *
   * At the time of writing we do not support persisted queries but we may want
   * to adopt them in the future and should ensure this works.
   */
  public function testAuditLoggerLogsSessionExecutedPersistedQuery() : void {
    // Add automatic persisted query support.
    $plugin = $this->container->get('plugin.manager.graphql.persisted_query')->createInstance('automatic_persisted_query');
    assert($plugin instanceof AutomaticPersistedQuery);
    $this->server->addPersistedQueryInstance($plugin);
    $this->server->save();

    $viewer = $this->setUpCurrentUser();

    [$node, $query] = $this->setupContent();

    $queryId = hash('sha256', $query);
    // Persist the query.
    $persistResult = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'operationname' => 'Test',
      'variables' => ['id' => $node->uuid()],
      'extensions' => [
        'persistedQuery' => [
          'sha256Hash' => $queryId,
        ],
      ],
    ]));

    self::assertQuerySuccessful($persistResult->errors);

    // Execute the query.
    $executionResult = $this->server->executeOperation(OperationParams::create([
      'variables' => ['id' => $node->uuid()],
      'extensions' => [
        'persistedQuery' => [
          'sha256Hash' => $queryId,
        ],
      ],
    ], TRUE));

    self::assertQuerySuccessful($executionResult->errors);

    $loggedMessage = $this->auditLogger->getLastMessage();
    self::assertIsArray($loggedMessage);
    self::assertTrue(isset($loggedMessage['context']['performance']['duration_ms']));
    self::assertEquals(
      [
        'query' => [
          'type' => 'query',
          'server_entity_id' => 'test',
          'server_entity_uuid' => $this->server->uuid(),
          'operation' => NULL,
          'query' => NULL,
          'query_id' => $queryId,
          'readonly' => TRUE,
        ],
        'auth' => [
          'type' => 'cookie',
          'mode' => NULL,
          'user_id' => $viewer->id(),
          'consumer_id' => NULL,
        ],
        'success' => TRUE,
        'from_cache' => FALSE,
        'performance' => [
          // This is a somewhat weird assertion, but the duration is always
          // slightly different.
          'duration_ms' => $loggedMessage['context']['performance']['duration_ms'],
          'query_count' => 1,
        ],
      ],
      $loggedMessage['context']
    );
  }

  /**
   * Test that a query executed with OAuth client credentials is logged.
   */
  public function testAuditLoggerLogsOauthClientCredentialsExecutedQuery() : void {
    $viewer = $this->actAsClientCredentialsWithScopes([]);

    [$node, $query] = $this->setupContent();

    $executionResult = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    self::assertQuerySuccessful($executionResult->errors);

    $loggedMessage = $this->auditLogger->getLastMessage();
    self::assertIsArray($loggedMessage);
    self::assertTrue(isset($loggedMessage['context']['performance']['duration_ms']));
    self::assertEquals(
      [
        'query' => [
          'type' => 'query',
          'server_entity_id' => 'test',
          'server_entity_uuid' => $this->server->uuid(),
          'operation' => NULL,
          'query' => $query,
          'query_id' => NULL,
          'readonly' => FALSE,
        ],
        'auth' => [
          'type' => 'oauth',
          'mode' => 'client_credentials',
          'user_id' => $viewer->id(),
          'consumer_id' => $viewer->getConsumer()->id(),
        ],
        'success' => TRUE,
        'from_cache' => FALSE,
        'performance' => [
          // This is a somewhat weird assertion, but the duration is always
          // slightly different.
          'duration_ms' => $loggedMessage['context']['performance']['duration_ms'],
          'query_count' => 3,
        ],
      ],
      $loggedMessage['context']
    );
  }

  /**
   * Test that a query cache hit with OAuth client credentials is logged.
   */
  public function testAuditLoggerLogsOauthClientCredentialsCachedQuery() : void {
    $viewer = $this->actAsClientCredentialsWithScopes([]);

    [$node, $query] = $this->setupContent();

    // Execute to cache.
    $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    $executionResult = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    self::assertQuerySuccessful($executionResult->errors);

    $loggedMessage = $this->auditLogger->getLastMessage();
    self::assertIsArray($loggedMessage);
    self::assertEquals(
      [
        'query' => [
          'type' => 'query',
          'server_entity_id' => 'test',
          'server_entity_uuid' => $this->server->uuid(),
          'operation' => NULL,
          'query' => $query,
          'query_id' => NULL,
          'readonly' => FALSE,
        ],
        'auth' => [
          'type' => 'oauth',
          'mode' => 'client_credentials',
          'user_id' => $viewer->id(),
          'consumer_id' => $viewer->getConsumer()->id(),
        ],
        'success' => TRUE,
        'from_cache' => TRUE,
        // Cached queries don't have performance data.
        'performance' => NULL,
      ],
      $loggedMessage['context']
    );
  }

  /**
   * Test that a query executed with OAuth authorization code flow is logged.
   */
  public function testAuditLoggerLogsOauthAuthorizationCodeExecutedQuery() : void {
    $viewer = $this->createUser(['grant simple_oauth codes']);
    assert($viewer !== FALSE, "Could not create viewer");
    $viewer = $this->actAsAuthorizationCodeWithScopes(['test'], $viewer);

    [$node, $query] = $this->setupContent();

    $executionResult = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    self::assertQuerySuccessful($executionResult->errors);

    $loggedMessage = $this->auditLogger->getLastMessage();
    self::assertIsArray($loggedMessage);
    self::assertTrue(isset($loggedMessage['context']['performance']['duration_ms']));
    self::assertEquals(
      [
        'query' => [
          'type' => 'query',
          'server_entity_id' => 'test',
          'server_entity_uuid' => $this->server->uuid(),
          'operation' => NULL,
          'query' => $query,
          'query_id' => NULL,
          'readonly' => FALSE,
        ],
        'auth' => [
          'type' => 'oauth',
          'mode' => 'authorization_code',
          'user_id' => $viewer->id(),
          'consumer_id' => $viewer->getConsumer()->id(),
        ],
        'success' => TRUE,
        'from_cache' => FALSE,
        'performance' => [
          // This is a somewhat weird assertion, but the duration is always
          // slightly different.
          'duration_ms' => $loggedMessage['context']['performance']['duration_ms'],
          'query_count' => 3,
        ],
      ],
      $loggedMessage['context']
    );
  }

  /**
   * Test that a query cache hit with OAuth authorization code flow is logged.
   */
  public function testAuditLoggerLogsOauthAuthorizationCodeCachedQuery() : void {
    $viewer = $this->createUser(['grant simple_oauth codes']);
    assert($viewer !== FALSE, "Could not create viewer");
    $viewer = $this->actAsAuthorizationCodeWithScopes(['test'], $viewer);

    [$node, $query] = $this->setupContent();

    // Execute to cache.
    $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    $executionResult = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => ['id' => $node->uuid()],
    ]));

    self::assertQuerySuccessful($executionResult->errors);

    $loggedMessage = $this->auditLogger->getLastMessage();
    self::assertIsArray($loggedMessage);
    self::assertEquals(
      [
        'query' => [
          'type' => 'query',
          'server_entity_id' => 'test',
          'server_entity_uuid' => $this->server->uuid(),
          'operation' => NULL,
          'query' => $query,
          'query_id' => NULL,
          'readonly' => FALSE,
        ],
        'auth' => [
          'type' => 'oauth',
          'mode' => 'authorization_code',
          'user_id' => $viewer->id(),
          'consumer_id' => $viewer->getConsumer()->id(),
        ],
        'success' => TRUE,
        'from_cache' => TRUE,
        // Cached queries don't have performance data.
        'performance' => NULL,
      ],
      $loggedMessage['context']
    );
  }

  /**
   * Assert that a query was successful by checking the errors is empty.
   *
   * @param \GraphQL\Error\Error[] $errors
   *   The errors array from the execution result.
   *
   * @throws \GraphQL\Error\Error
   *   The first error in case there is one so you can debug it.
   */
  public static function assertQuerySuccessful(array $errors) : void {
    if ($errors !== []) {
      /** @var \GraphQL\Error\Error $error */
      $error = $errors[0];
      throw $error;
    }
  }

}
