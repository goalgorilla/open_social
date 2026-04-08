<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileExists;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\graphql\GraphQL\Execution\ExecutionResult;
use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kernel tests for event hero image uploads via GraphQL.
 *
 * @group social_event
 */
class EventFileUploadTest extends SocialEventGraphQLKernelTestBase {

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
   * GD-generated minimal PNG (11×11), base64.
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
   * Test that an image can be uploaded to a newly created event.
   */
  public function testCreateEventWithImage(): void {
    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->actAsClientCredentialsWithScopes(['event:write']);

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
              'target' => 'EVENT_HERO_IMAGE',
              'filename' => 'test.png',
              'fileSize' => $filesize,
            ],
          ],
        ],
      ],
    );

    $this->assertResultErrors($result, []);
    $data = $result->data['stagedUploadsCreate'];
    $this->assertNull($data['errors']);
    $this->assertIsArray($data['stagedUploadGrants']);
    $this->assertCount(1, $data['stagedUploadGrants']);
    $grant = $data['stagedUploadGrants'][0];
    $this->assertEquals('EVENT_HERO_IMAGE', $grant['target']);
    $this->assertEquals('test.png', $grant['filename']);
    $this->assertEquals($filesize, $grant['fileSize']);
    $this->assertEquals(FALSE, $grant['sizeDeferred']);
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

    $request = Request::create($uploadUrl, 'PATCH', [], [], [], [], $png);
    $request->headers->add([
      'Content-Type' => 'application/offset+octet-stream',
      'Tus-Resumable' => '1.0.0',
      'Upload-Offset' => 0,
    ]);
    $response = $this->container->get('http_kernel')->handle($request);

    $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string) $response->getContent());
    $this->assertEquals($filesize, $response->headers->get('Upload-Offset'));

    $createResult = $this->executeGraphQlQuery(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          errors
          event {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'Upload Test',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'location' => 'Test location',
          'heroImage' => [
            'stagedUpload' => $finalizationToken,
          ],
        ],
      ],
    );

    $this->assertResultErrors($createResult, []);
    $data = $createResult->data['createEvent'];
    $this->assertNull($data['errors']);
    $this->assertIsString($data['event']['id']);

    $events = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['uuid' => $data['event']['id']]);
    $this->assertCount(1, $events);
    $event = reset($events);

    $image = $event->get('field_event_image')->entity;
    $this->assertInstanceOf(FileInterface::class, $image);
  }

  /**
   * Test that an image can be uploaded to an existing event.
   */
  public function testUpdateEventWithImage(): void {
    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $this->actAsClientCredentialsWithScopes(['event:write']);

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
              'target' => 'EVENT_HERO_IMAGE',
              'filename' => 'test.png',
              'fileSize' => $filesize,
            ],
          ],
        ],
      ],
    );

    $this->assertResultErrors($result, []);
    $data = $result->data['stagedUploadsCreate'];
    $this->assertNull($data['errors']);
    $this->assertIsArray($data['stagedUploadGrants']);
    $this->assertCount(1, $data['stagedUploadGrants']);
    $grant = $data['stagedUploadGrants'][0];
    $this->assertEquals('EVENT_HERO_IMAGE', $grant['target']);
    $this->assertEquals('test.png', $grant['filename']);
    $this->assertEquals($filesize, $grant['fileSize']);
    $this->assertEquals(FALSE, $grant['sizeDeferred']);
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

    $request = Request::create($uploadUrl, 'PATCH', [], [], [], [], $png);
    $request->headers->add([
      'Content-Type' => 'application/offset+octet-stream',
      'Tus-Resumable' => '1.0.0',
      'Upload-Offset' => 0,
    ]);
    $response = $this->container->get('http_kernel')->handle($request);

    $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string) $response->getContent());
    $this->assertEquals($filesize, $response->headers->get('Upload-Offset'));

    $updateResult = $this->executeGraphQlQuery(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'heroImage' => [
            'stagedUpload' => $finalizationToken,
          ],
        ],
      ],
    );

    $this->assertResultErrors($updateResult, []);
    $this->assertNull($updateResult->data['updateEvent']['errors']);

    $event = $this->reloadEvent($event);

    $image = $event->get('field_event_image')->entity;
    $this->assertInstanceOf(FileInterface::class, $image);
  }

  /**
   * Test that heroImage: null on updateEvent clears the image field.
   */
  public function testUpdateEventClearsHeroImageWithNull(): void {
    $eventType = $this->createEventType();

    $png = base64_decode(self::PNG_11X11_B64, TRUE);
    $this->assertNotFalse($png);
    $uri = $this->container->get('file_system')->saveData(
      $png,
      'private://event_clear_hero_test.png',
      FileExists::Replace,
    );
    $file = File::create([
      'uid' => 1,
      'filename' => 'event_clear_hero_test.png',
      'uri' => $uri,
      'filemime' => 'image/png',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();

    $event = $this->createEvent($eventType, [
      'field_event_image' => $file->id(),
    ]);
    $this->assertInstanceOf(FileInterface::class, $event->get('field_event_image')->entity);

    $this->actAsClientCredentialsWithScopes(['event:write']);

    $clearResult = $this->executeGraphQlQuery(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'heroImage' => NULL,
        ],
      ],
    );

    $this->assertResultErrors($clearResult, []);
    $this->assertNull($clearResult->data['updateEvent']['errors']);

    $event = $this->reloadEvent($event);
    $this->assertTrue($event->get('field_event_image')->isEmpty());
  }

  /**
   * Placeholder for rejecting mismatched upload targets.
   */
  public function testIncorrectDestinationRejected(): void {
    $this->markTestIncomplete('We should implement this when we have renewed energy.');
  }

  /**
   * Placeholder for retry behaviour when validation fails after finalization.
   */
  public function testEventValidationErrorsAllowRetry(): void {
    $this->markTestIncomplete('We should implement this when we have renewed energy.');
  }

  /**
   * Executes a GraphQL operation against the test server.
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
   * Asserts the GraphQL result contains exactly the expected error patterns.
   *
   * @param \Drupal\graphql\GraphQL\Execution\ExecutionResult $result
   *   The query result object.
   * @param array $expected
   *   The list of expected error messages. Also allows regular expressions.
   *
   * @internal
   */
  private function assertResultErrors(ExecutionResult $result, array $expected): void {
    $unexpected = [];
    $matchCount = array_fill_keys($expected, 0);

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
        $original_error = $error;
        while ($original_error->getPrevious() !== NULL) {
          $original_error = $original_error->getPrevious();
        }
        $unexpected[] = "Error message: {$error_message}\n  Originated in: {$original_error->getFile()}:{$original_error->getLine()}";
      }
    }

    $missing = array_keys(array_filter($matchCount, function ($count) {
      return $count === 0;
    }));

    $this->assertEmpty($missing, "Missing errors:\n* " . implode("\n* ", $missing));
    $this->assertEmpty($unexpected, "Unexpected errors:\n* " . implode("\n* ", $unexpected));
  }

}
