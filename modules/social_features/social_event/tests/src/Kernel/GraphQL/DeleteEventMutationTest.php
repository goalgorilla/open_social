<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the deleteEvent GraphQL mutation.
 *
 * @group social_event
 */
class DeleteEventMutationTest extends SocialEventGraphQLKernelTestBase {

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
   * Creates a minimal event node for delete tests.
   *
   * @param array $overrides
   *   Optional field overrides.
   *
   * @return \Drupal\node\NodeInterface
   *   The created event node.
   */
  protected function createEventNode(array $overrides = []): NodeInterface {
    $eventType = $this->createEventType();
    return $this->createEvent($eventType, array_merge(['title' => 'Test Event to Delete'], $overrides));
  }

  /**
   * Test deleting an event successfully.
   */
  public function testDeleteEventSuccess(): void {
    $event = $this->createEventNode();
    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $event_uuid = $event->uuid();
    assert(is_string($event_uuid));

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            clientMutationId
            errors
          }
        }
        GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'id' => $event_uuid,
        ],
      ],
      [
        'deleteEvent' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify the event was actually removed from storage.
    $entity_repository = $this->container->get('entity.repository');
    $loaded_node = $entity_repository->loadEntityByUuid('node', $event_uuid);
    $this->assertNull($loaded_node, 'Event should be deleted.');
  }

  /**
   * Test validation error for missing event ID.
   */
  public function testDeleteEventMissingId(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => '',
        ],
      ],
      [
        'deleteEvent' => [
          'errors' => ['EVENT_ID_REQUIRED'],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for non-existent event.
   */
  public function testDeleteEventNotFound(): void {
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => $fakeUuid,
        ],
      ],
      [
        'deleteEvent' => [
          'errors' => ['EVENT_NOT_FOUND'],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test attempting to delete a non-event node.
   *
   * Passing a topic UUID returns EVENT_NOT_FOUND and the topic is unchanged.
   */
  public function testDeleteNonEventNode(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Test Topic',
      'field_topic_type' => $topicType->id(),
      'field_content_visibility' => 'public',
      'uid' => 2,
      'status' => 1,
    ]);
    $topic->save();

    $topic_uuid = $topic->uuid();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic_uuid,
        ],
      ],
      [
        'deleteEvent' => [
          'errors' => ['EVENT_NOT_FOUND'],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    // Topic must still exist.
    $topic_id = $topic->id();
    assert(is_string($topic_id));
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node_storage->resetCache([$topic_id]);
    $loaded_node = $node_storage->load($topic_id);
    $this->assertNotNull($loaded_node, 'Topic should still exist.');
  }

  /**
   * Test that deleting an event also removes related comments.
   *
   * Verifies cascade delete behavior: when an event is deleted, comments
   * are automatically deleted by Drupal's entity implementation.
   */
  public function testDeleteEventWithComments(): void {
    $event = $this->createEventNode(['title' => 'Test Event with Comments']);

    $comment = Comment::create([
      'entity_type' => 'node',
      'entity_id' => $event->id(),
      'field_name' => 'field_event_comments',
      'comment_type' => 'comment',
      'subject' => 'Test Comment',
      'comment_body' => [
        'value' => 'This is a test comment',
        'format' => 'plain_text',
      ],
      'uid' => 2,
      'status' => 1,
    ]);
    $comment->save();

    $event_uuid = $event->uuid();
    assert(is_string($event_uuid));
    $comment_id = $comment->id();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event_uuid,
        ],
      ],
      [
        'deleteEvent' => [
          'errors' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    // Event and its comments must be removed.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $comment_storage = $this->container->get('entity_type.manager')->getStorage('comment');
    $node_storage->resetCache();
    $comment_storage->resetCache();

    $entity_repository = $this->container->get('entity.repository');
    $loaded_event = $entity_repository->loadEntityByUuid('node', $event_uuid);
    $this->assertNull($loaded_event, 'Event should be deleted.');

    $loaded_comment = $comment_storage->load($comment_id);
    $this->assertNull($loaded_comment, 'Comment should be deleted when event is deleted.');
  }

  /**
   * Test that deleting an event requires the event:write scope.
   */
  public function testDeleteEventRequiresEventWriteScope(): void {
    $event = $this->createEventNode(['title' => 'Test Event']);
    $event_uuid = $event->uuid();

    $this->actAsClientCredentialsWithScopes([]);
    $this->assertErrors(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event_uuid,
        ],
      ],
      [
        "Missing scope 'event:write' on 'deleteEvent'.",
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

}
