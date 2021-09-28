<?php

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\graphql\Traits\DataProducerExecutionTrait;
use Drupal\Tests\graphql\Traits\MockingTrait;
use Drupal\Tests\graphql\Traits\HttpRequestTrait;
use Drupal\Tests\graphql\Traits\QueryFileTrait;
use Drupal\Tests\graphql\Traits\QueryResultAssertionTrait;
use Drupal\Tests\graphql\Traits\SchemaPrinterTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Modified version of graphql's GraphQLTestBase class.
 *
 * For GraphQL tests in Open Social use `SocialGraphQLTestBase` instead.
 *
 * This version of the class removes the language and user creation from the
 * setUp step. This allows code in Open Social to rely on its own configuration.
 *
 * In the previous version of the class it could happen that a module was
 * installed causing its hooks to be invoked but its configuration was not yet
 * present, causing errors. This is not a scenario that would occur in an actual
 * Drupal installation since config is installed when the module is enabled
 * (before content is created).
 *
 * With the exception of the above outlined modification, this class should be
 * kept in sync with the grahpql module's version of this class.
 *
 * @internal
 */
abstract class GraphQLTestBase extends KernelTestBase {
  use DataProducerExecutionTrait;
  use HttpRequestTrait;
  use QueryFileTrait;
  use QueryResultAssertionTrait;
  use SchemaPrinterTrait;
  use MockingTrait;
  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * The test runner will merge the $modules lists from this class, the class
   * it extends, and so on up the class hierarchy. It is not necessary to
   * include modules in your list that a parent class has already declared.
   *
   * @var string[]
   *
   * @see \Drupal\Tests\KernelTestBase::enableModules()
   * @see \Drupal\Tests\KernelTestBase::bootKernel()
   */
  protected static $modules = [
    'system',
    'user',
    'language',
    'node',
    'graphql',
    'content_translation',
    'entity_reference_test',
    'field',
    'menu_link_content',
    'link',
    'typed_data',
  ];

  /**
   * The resolver builder used for our server's schema.
   *
   * @var \Drupal\graphql\GraphQL\ResolverBuilder
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('system');
    $this->installConfig('graphql');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('graphql_server');
    $this->installEntitySchema('configurable_language');
    $this->installConfig(['language']);

    // Since `setupCurrentUser` is not called, extending tests may call
    // `createUser` before calling `setupCurrentUser`. That function doesn't
    // install the sequences table automatically causing failing tests, so we do
    // it here.
    $this->installSchema('system', ['sequences']);

    $this->builder = new ResolverBuilder();
  }

  /**
   * Returns the default cache maximum age for the test.
   */
  protected function defaultCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

  /**
   * Returns the default cache tags used in assertions for this test.
   *
   * @return string[]
   *   The list of cache tags.
   */
  protected function defaultCacheTags(): array {
    $tags = ['graphql_response'];
    if (isset($this->server)) {
      array_push($tags, "config:graphql.graphql_servers.{$this->server->id()}");
    }

    return $tags;
  }

  /**
   * Returns the default cache contexts used in assertions for this test.
   *
   * @return string[]
   *   The list of cache contexts.
   */
  protected function defaultCacheContexts(): array {
    return ['user.permissions'];
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
