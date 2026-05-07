<?php

declare(strict_types=1);

namespace Drupal\Tests\social_graphql\Kernel\GraphQL;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test the integration of Signed File Upload into our GraphQL API.
 */
class GraphQLFileUploadTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'options',
    'simple_oauth',
    'simple_oauth_static_scope',
    'social_oauth',
    'consumers',
    'node',
    'image',
    'signed_file_upload',
    'test_social_graphql_file_upload',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('consumer');
    $this->installEntitySchema('oauth2_token');
    $this->installEntitySchema('signed_file_upload_session');
    $this->installConfig([
      'signed_file_upload',
      'test_social_graphql_file_upload',
    ]);

    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container->register('stream_wrapper.private', PrivateStream::class)
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem(): void {
    parent::setUpFilesystem();

    mkdir($this->siteDirectory . '/private', 0775, TRUE);
    $this->setSetting('file_private_path', $this->siteDirectory . '/private');
  }

  /**
   * Test that the stagedUploadsCreate mutation requires the upload scope.
   *
   * Uses 'graphql:use' (a real, discoverable scope that does NOT grant the
   * staged-upload permission) instead of an empty scope set: under
   * simple_oauth ^6 / league/oauth2-server ^9, empty-scope tokens issue but
   * have no token-derived roles, which makes the assertion path fragile.
   * Using a non-matching scope preserves the original intent: confirm the
   * GraphQL `@oauth(scope: "graphql:staged_upload:create")` directive
   * rejects callers that don't carry the required scope.
   */
  public function testUploadRequiresAuth() : void {
    $this->actAsClientCredentialsWithScopes(['graphql:use']);
    $this->assertErrors(
      <<<GQL
      mutation RequestFileUpload(\$input : StagedUploadsCreateInput!) {
        stagedUploadsCreate(input: \$input) {
          errors
          stagedUploadGrants {
            target
            filename
            fileSize
            sizeDeferred
            uploadUrl
            finalizationToken
            constraints {
              maxBytes
              allowedExtensions
              dimensionsMaximum {
                width
                height
              }
              dimensionsMinimum {
                width
                height
              }
            }
          }
        }
      }
      GQL,
      [
        'input' => [
          'requests' => [
            [
              'target' => 'TEST_TARGET',
              'filename' => 'test.png',
              'fileSize' => 50,
            ],
          ],
        ],
      ],
      ["Missing scope 'graphql:staged_upload:create' on 'stagedUploadsCreate'."],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface']),
    );
  }

  /**
   * Test that stagedUploadsCreate works for client credentials.
   */
  public function testUploadSuccessWithClientCredentialsAuth() : void {
    $this->actAsClientCredentialsWithScopes(['graphql:staged_upload:create']);

    $this->assertResults(
      <<<GQL
      mutation RequestFileUpload(\$input : StagedUploadsCreateInput!) {
        stagedUploadsCreate(input: \$input) {
          errors
          stagedUploadGrants {
            filename
          }
        }
      }
      GQL,
      [
        'input' => [
          'requests' => [
            [
              'target' => 'TEST_TARGET',
              'filename' => 'test.png',
              'fileSize' => 50,
            ],
          ],
        ],
      ],
      [
        'stagedUploadsCreate' => [
          'errors' => NULL,
          'stagedUploadGrants' => [
            ['filename' => 'test.png'],
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface']),
    );
  }

  /**
   * Test that stagedUploadsCreate works for authorization code.
   */
  public function testUploadSuccessWithAuthorizationCodeAuth() : void {
    $user = $this->createUser();
    assert($user !== FALSE);
    $this->actAsAuthorizationCodeWithScopes(['graphql:staged_upload:create'], $user);

    $this->assertResults(
      <<<GQL
      mutation RequestFileUpload(\$input : StagedUploadsCreateInput!) {
        stagedUploadsCreate(input: \$input) {
          errors
          stagedUploadGrants {
            filename
          }
        }
      }
      GQL,
      [
        'input' => [
          'requests' => [
            [
              'target' => 'TEST_TARGET',
              'filename' => 'test.png',
              'fileSize' => 50,
            ],
          ],
        ],
      ],
      [
        'stagedUploadsCreate' => [
          'errors' => NULL,
          'stagedUploadGrants' => [
            ['filename' => 'test.png'],
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface']),
    );
  }

}
