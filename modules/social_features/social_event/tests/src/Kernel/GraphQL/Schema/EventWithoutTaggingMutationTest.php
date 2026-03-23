<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL\Schema;

use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the event mutations without tagging.
 *
 * @group social_event
 */
class EventWithoutTaggingMutationTest extends SocialEventGraphQLKernelTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts(): array {
    return [...parent::defaultCacheContexts(), 'languages:language_interface'];
  }

  /**
   * Test that createEvent rejects contentTags when tagging is not enabled.
   */
  public function testCreateEventWithTaggingErrors(): void {
    $eventType = $this->createEventType();
    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertErrors(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          clientMutationId
          errors
          event {
            title
            eventType { id }
            bodyHtml
          }
        }
      }
      GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'type' => $eventType->uuid(),
          'title' => 'Annual Conference 2026',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'location' => 'Amsterdam',
          'contentTags' => [$clientMutationId],
        ],
      ],
      [
        'Variable "$input" got invalid value {"clientMutationId":"' . $clientMutationId . '","type":"' . $eventType->uuid() . '","title":"Annual Conference 2026","visibility":"PUBLIC","body":{"root":{"type":"root","version":1,"children":[{"type":"paragraph","version":1,"children":[{"type":"text","version":1,"text":"Hello"}]}]}},"startDate":' . $startTimestamp . ',"endDate":' . $endTimestamp . ',"location":"Amsterdam","contentTags":["' . $clientMutationId . '"]}; Field "contentTags" is not defined by type CreateEventInput.',
      ],
      $this->defaultMutationCacheMetaData()
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test that updateEvent rejects contentTags when tagging is not enabled.
   */
  public function testUpdateEventWithTaggingErrors(): void {
    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertErrors(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          clientMutationId
          errors
          event {
            id
            title
          }
        }
      }
      GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'id' => $event->uuid(),
          'title' => 'Updated Event Title',
          'contentTags' => [$clientMutationId],
        ],
      ],
      [
        'Variable "$input" got invalid value {"clientMutationId":"' . $clientMutationId . '","id":"' . $event->uuid() . '","title":"Updated Event Title","contentTags":["' . $clientMutationId . '"]}; Field "contentTags" is not defined by type UpdateEventInput.',
      ],
      $this->defaultMutationCacheMetaData()
        ->setCacheMaxAge(0)
    );
  }

}
