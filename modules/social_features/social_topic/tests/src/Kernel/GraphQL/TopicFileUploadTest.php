<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileExists;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\graphql\GraphQL\Execution\ExecutionResult;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_topic\Kernel\SocialTopicGraphQLKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kernel tests for topic hero image uploads via GraphQL.
 */
class TopicFileUploadTest extends SocialTopicGraphQLKernelTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'signed_file_upload',
    'secret_file_system',
  ];

  /**
   * GD-generated minimal PNG (11×11), base64-encoded.
   */
  private const PNG_11X11_B64 = 'iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAIAAAAmzuBxAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADUlEQVQYlWNgGAX0BwABdgABDVW5ZgAAAABJRU5ErkJggg==';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('signed_file_upload_session');
    $this->installConfig(['signed_file_upload']);
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
   * Minimal valid Rich Text JSON body (paragraph with text).
   */
  private static function minimalRichTextBody(): array {
    return [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Test that an image can be uploaded to a newly created topic.
   */
  public function testCreateTopicWithImage(): void {
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $this->actAsClientCredentialsWithScopes(['graphql:staged_upload:create', 'topic:write']);

    // Request an upload.
    $png = base64_decode(self::PNG_11X11_B64, TRUE);
    $this->assertNotFalse($png);
    $filesize = strlen($png);
    $result = $this->executeGraphQlQuery(
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
              'target' => 'TOPIC_HERO_IMAGE',
              'filename' => 'test.png',
              'fileSize' => $filesize,
            ],
          ],
        ],
      ],
    );

    $this->assertResultErrors($result, []);
    $this->assertIsArray($result->data);
    $this->assertIsArray($result->data['stagedUploadsCreate']);
    $data = $result->data['stagedUploadsCreate'];
    $this->assertNull($data['errors']);
    $this->assertIsArray($data['stagedUploadGrants']);
    $this->assertCount(1, $data['stagedUploadGrants']);
    $grant = $data['stagedUploadGrants'][0];
    $this->assertEquals('TOPIC_HERO_IMAGE', $grant['target']);
    $this->assertEquals('test.png', $grant['filename']);
    $this->assertEquals($filesize, $grant['fileSize']);
    $this->assertEquals(FALSE, $grant['sizeDeferred']);
    // @todo Figure out actual.
    $this->assertEquals([
      'maxBytes' => 0,
      'allowedExtensions' => ['png', 'gif', 'jpg', 'jpeg'],
      'dimensionsMaximum' => [
        'width' => 4096,
        'height' => 4096,
      ],
      'dimensionsMinimum' => NULL,
    ], $grant['constraints']);

    $uploadUrl = $grant['uploadUrl'];
    $finalizationToken = $grant['finalizationToken'];

    // Perform the upload.
    $request = Request::create($uploadUrl, 'PATCH', [], [], [], [], $png);
    $request->headers->add([
      'Content-Type' => 'application/offset+octet-stream',
      'Tus-Resumable' => '1.0.0',
      'Upload-Offset' => 0,
    ]);
    $response = $this->container->get('http_kernel')->handle($request);

    $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string) $response->getContent());
    $this->assertEquals($filesize, $response->headers->get('Upload-Offset'));

    // Create the topic with the uploaded image.
    $createResult = $this->executeGraphQlQuery(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Upload Test',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'heroImage' => [
            'stagedUpload' => $finalizationToken,
          ],
        ],
      ],
    );

    $this->assertResultErrors($createResult, []);
    $this->assertIsArray($createResult->data);
    $this->assertIsArray($createResult->data['createTopic']);
    $data = $createResult->data['createTopic'];
    $this->assertNull($data['errors']);
    $this->assertIsString($data['topic']['id']);

    $topics = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['uuid' => $data['topic']['id']]);
    $this->assertCount(1, $topics);
    $topic = reset($topics);

    $image = $topic->field_topic_image->entity;
    $this->assertInstanceOf(FileInterface::class, $image);
  }

  /**
   * Test that an image can be uploaded to an existing topic.
   */
  public function testUpdateTopicWithImage(): void {
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'field_topic_type' => $topicType->id(),
      'title' => 'Upload Test',
      'field_content_visibility' => 'public',
      'body' => [['value' => ' ']],
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['graphql:staged_upload:create', 'topic:write']);

    // Request an upload.
    $png = base64_decode(self::PNG_11X11_B64, TRUE);
    $this->assertNotFalse($png);
    $filesize = strlen($png);
    $result = $this->executeGraphQlQuery(
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
              'target' => 'TOPIC_HERO_IMAGE',
              'filename' => 'test.png',
              'fileSize' => $filesize,
            ],
          ],
        ],
      ],
    );

    $this->assertResultErrors($result, []);
    $this->assertIsArray($result->data);
    $this->assertIsArray($result->data['stagedUploadsCreate']);
    $data = $result->data['stagedUploadsCreate'];
    $this->assertNull($data['errors']);
    $this->assertIsArray($data['stagedUploadGrants']);
    $this->assertCount(1, $data['stagedUploadGrants']);
    $grant = $data['stagedUploadGrants'][0];
    $this->assertEquals('TOPIC_HERO_IMAGE', $grant['target']);
    $this->assertEquals('test.png', $grant['filename']);
    $this->assertEquals($filesize, $grant['fileSize']);
    $this->assertEquals(FALSE, $grant['sizeDeferred']);
    // @todo Figure out actual.
    $this->assertEquals([
      'maxBytes' => 0,
      'allowedExtensions' => ['png', 'gif', 'jpg', 'jpeg'],
      'dimensionsMaximum' => [
        'width' => 4096,
        'height' => 4096,
      ],
      'dimensionsMinimum' => NULL,
    ], $grant['constraints']);

    $uploadUrl = $grant['uploadUrl'];
    $finalizationToken = $grant['finalizationToken'];

    // Perform the upload.
    $request = Request::create($uploadUrl, 'PATCH', [], [], [], [], $png);
    $request->headers->add([
      'Content-Type' => 'application/offset+octet-stream',
      'Tus-Resumable' => '1.0.0',
      'Upload-Offset' => 0,
    ]);
    $response = $this->container->get('http_kernel')->handle($request);

    $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string) $response->getContent());
    $this->assertEquals($filesize, $response->headers->get('Upload-Offset'));

    // Create the topic with the uploaded image.
    $createResult = $this->executeGraphQlQuery(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'heroImage' => [
            'stagedUpload' => $finalizationToken,
          ],
        ],
      ],
    );

    $this->assertResultErrors($createResult, []);
    $this->assertIsArray($createResult->data);
    $this->assertIsArray($createResult->data['updateTopic']);
    $this->assertNull($createResult->data['updateTopic']['errors']);

    // Reload the topic.
    $topic_id = $topic->id();
    $this->assertNotNull($topic_id);
    $topic = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->load($topic_id);
    $this->assertInstanceOf(Node::class, $topic);

    $image = $topic->field_topic_image->entity;
    $this->assertInstanceOf(FileInterface::class, $image);
  }

  /**
   * Test that heroImage: null on updateTopic clears the image field.
   */
  public function testUpdateTopicClearsHeroImageWithNull(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $png = base64_decode(self::PNG_11X11_B64, TRUE);
    $this->assertNotFalse($png);
    $uri = $this->container->get('file_system')->saveData(
      $png,
      'private://topic_clear_hero_test.png',
      FileExists::Replace,
    );
    $file = File::create([
      'uid' => 1,
      'filename' => 'topic_clear_hero_test.png',
      'uri' => $uri,
      'filemime' => 'image/png',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();

    $topic = Node::create([
      'type' => 'topic',
      'field_topic_type' => $topicType->id(),
      'title' => 'Clear image test',
      'field_content_visibility' => 'public',
      'body' => [['value' => ' ']],
      'field_topic_image' => $file->id(),
    ]);
    $topic->save();

    $this->assertInstanceOf(FileInterface::class, $topic->field_topic_image->entity);

    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $clearResult = $this->executeGraphQlQuery(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'heroImage' => NULL,
        ],
      ],
    );
    $this->assertResultErrors($clearResult, []);
    $this->assertIsArray($clearResult->data);
    $this->assertIsArray($clearResult->data['updateTopic']);
    $this->assertNull($clearResult->data['updateTopic']['errors']);

    $topic_id = $topic->id();
    $this->assertNotNull($topic_id);
    $reloaded = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->load($topic_id);
    $this->assertInstanceOf(Node::class, $reloaded);
    $this->assertTrue($reloaded->get('field_topic_image')->isEmpty());
  }

  /**
   * Placeholder for rejecting mismatched upload targets.
   */
  public function testIncorrectDestinationRejected(): void {
    $this->markTestIncomplete('We should implement this when we have renewed energy.');
  }

  /**
   * Test that the finalization token can be reused after failed validation.
   *
   * The finalization process moves the file and updates the upload session
   * record. However, if the topic has validation errors then the file is
   * finalized but it's not attached to the topic, in that case we should be
   * able to roll-back or have another way to reuse the finalization token to
   * re-attempt.
   */
  public function testTopicValidationErrorsAllowRetry(): void {
    $this->markTestIncomplete('We should implement this when we have renewed energy.');
  }

  /**
   * Executes a GraphQL operation against the test server.
   *
   * @param string $query
   *   The GraphQL query or mutation document.
   * @param array $variables
   *   Variables for the operation.
   *
   * @return \Drupal\graphql\GraphQL\Execution\ExecutionResult
   *   The execution result.
   */
  protected function executeGraphQlQuery(string $query, array $variables): ExecutionResult {
    $context = new RenderContext();
    return $this->container->get('renderer')->executeInRenderContext(
      $context,
      function () use ($query, $variables) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => $query,
            'variables' => $variables,
          ])
        );
      }
    );
  }

  /**
   * Assert that the result contains contains a certain set of errors.
   *
   * @param \Drupal\graphql\GraphQL\Execution\ExecutionResult $result
   *   The query result object.
   * @param array $expected
   *   The list of expected error messages. Also allows regular expressions.
   *
   * @internal
   */
  private function assertResultErrors(ExecutionResult $result, array $expected): void {
    // Initialize the status.
    $unexpected = [];
    $matchCount = array_fill_keys($expected, 0);

    // Iterate through error messages.
    // Collect unmatched errors and count pattern hits.
    foreach ($result->errors as $error) {
      $error_message = $error->getMessage();
      $match = FALSE;
      foreach ($expected as $pattern) {
        if (@preg_match($pattern, $error_message) === FALSE) {
          $match = $match || $pattern == $error_message;
          $matchCount[$pattern]++;
        }
        else {
          $match = $match || preg_match($pattern, $error_message);
          $matchCount[$pattern]++;
        }
      }

      if (!$match) {
        // Add error location information of the original error in the chain to
        // show developers where to look.
        $original_error = $error;
        while ($original_error->getPrevious() !== NULL) {
          $original_error = $original_error->getPrevious();
        }
        $unexpected[] = "Error message: {$error_message}\n  Originated in: {$original_error->getFile()}:{$original_error->getLine()}";
      }
    }

    // Create a list of patterns that never matched.
    $missing = array_keys(array_filter($matchCount, function ($count) {
      return $count === 0;
    }));

    $this->assertEmpty($missing, "Missing errors:\n* " . implode("\n* ", $missing));
    $this->assertEmpty($unexpected, "Unexpected errors:\n* " . implode("\n* ", $unexpected));
  }

}
