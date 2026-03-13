<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for updateEvent GraphQL mutation content tags features.
 *
 * @group social_event
 */
class UpdateEventContentTagsMutationTest extends SocialEventGraphQLKernelTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   *
   * Include social_tagging so the kernel boots with it for content tags tests.
   */
  protected static $modules = [
    'address',
    'datetime',
    'subgroup',
    'paragraphs',
    'image',
    'options',
    'file',
    'link',
    'entity_reference_revisions',
    'media',
    'node',
    'grequest',
    'state_machine',
    'social_group',
    'activity_logger',
    'activity_creator',
    'message',
    'dynamic_entity_reference',
    'social_group_flexible_group',
    'social_group_request',
    'social_media_system',
    'select2',
    'text',
    'pathauto',
    'smart_trim',
    'path',
    'path_alias',
    'token',
    'inline_entity_form',
    'workflows',
    'content_moderation',
    'better_exposed_filters',
    'filter',
    'views_bulk_operations',
    'gnode',
    'social_event',
    'social_event_type',
    'social_topic',
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',
    'profile',
    'social_profile',
    'views',
    'group_core_comments',
    'menu_ui',
    'comment',
    'editor',
    'ckeditor5',
    'responsive_table_filter',
    'social_editor',
    'social_node',
    'social_core',
    'field_group',
    'file_mdm',
    'image_effects',
    'image_widget_crop',
    'crop',
    'block',
    'block_content',
    'entity_access_by_field',
    'entity',
    'entity_test',
    'telephone',
    'lazy',
    'serialization',
    'group',
    'social_user',
    'consumers',
    'simple_oauth',
    'simple_oauth_static_scope',
    'social_oauth',
    'social_graphql',
    'graphql_oauth',
    'social_comment',
    'taxonomy',
    'role_delegation',
    'variationcache',
    'menu_link_content',
    'flag',
    'field',
    'social_group_invite',
    'ginvite',
    'layout_builder',
    'social_tagging',
    'layout_discovery',
    'flag_count',
    'hux',
    'taxonomy_access_fix',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getConfigToInstall(): array {
    return array_merge(parent::getConfigToInstall(), ['social_tagging']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts(): array {
    return [...parent::defaultCacheContexts(), 'languages:language_interface'];
  }

  /**
   * Test adding content tags to an event that has none.
   */
  public function testUpdateEventAddContentTags(): void {
    $eventType = $this->createEventType('Conference');
    $event = $this->createEvent($eventType);

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tag1->save();
    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Education',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tag2->save();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $event->getTitle(),
          'contentTags' => [$tag1->uuid(), $tag2->uuid()],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    $event = $this->reloadEvent($event);
    $this->assertTrue($event->hasField('social_tagging'));
    $values = $event->get('social_tagging')->getValue();
    $this->assertCount(2, $values);
    $ids = array_column($values, 'target_id');
    $this->assertContains($tag1->id(), $ids);
    $this->assertContains($tag2->id(), $ids);
  }

  /**
   * Test changing content tags on an event that already has tags.
   */
  public function testUpdateEventChangeContentTags(): void {
    $eventType = $this->createEventType('Conference');
    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Old Tag',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tag1->save();
    $event = $this->createEvent($eventType, ['social_tagging' => [$tag1->id()]]);

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'New Tag',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tag2->save();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $event->getTitle(),
          'contentTags' => [$tag2->uuid()],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    $event = $this->reloadEvent($event);
    $values = $event->get('social_tagging')->getValue();
    $this->assertCount(1, $values);
    $this->assertEquals($tag2->id(), (int) $values[0]['target_id']);
  }

  /**
   * Test clearing content tags by sending an empty array.
   */
  public function testUpdateEventClearContentTags(): void {
    $eventType = $this->createEventType('Conference');
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'To Remove',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tag->save();
    $event = $this->createEvent($eventType, ['social_tagging' => [$tag->id()]]);

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $event->getTitle(),
          'contentTags' => [],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    $event = $this->reloadEvent($event);
    $this->assertEmpty($event->get('social_tagging')->getValue());
  }

  /**
   * Test clearing content tags by passing explicit null (clear request).
   */
  public function testUpdateEventClearContentTagsWithNull(): void {
    $eventType = $this->createEventType('Conference');
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'To Remove',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tag->save();
    $event = $this->createEvent($eventType, ['social_tagging' => [$tag->id()]]);

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $event->getTitle(),
          'contentTags' => NULL,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    $event = $this->reloadEvent($event);
    $this->assertEmpty($event->get('social_tagging')->getValue(), 'Content tags should be cleared when contentTags: null');
  }

  /**
   * Test omitting contentTags leaves existing tags unchanged.
   */
  public function testUpdateEventLeaveContentTagsUnchanged(): void {
    $eventType = $this->createEventType('Conference');
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Unchanged Tag',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tag->save();
    $event = $this->createEvent($eventType, ['social_tagging' => [$tag->id()]]);

    $newTitle = 'Updated Title Only';
    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
          event { id title }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $newTitle,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'title' => $newTitle,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    $event = $this->reloadEvent($event);
    $this->assertEquals($newTitle, $event->getTitle());
    $values = $event->get('social_tagging')->getValue();
    $this->assertCount(1, $values);
    $this->assertEquals($tag->id(), (int) $values[0]['target_id']);
  }

  /**
   * Test validation error when providing an invalid content tag UUID.
   */
  public function testUpdateEventInvalidContentTagReturnsError(): void {
    $eventType = $this->createEventType('Conference');
    $event = $this->createEvent($eventType);
    $fakeTagUuid = '12345678-1234-1234-1234-123456789999';

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $event->getTitle(),
          'contentTags' => [$fakeTagUuid],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['CONTENT_TAG_NOT_FOUND:' . $fakeTagUuid],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when content tag is not valid for events.
   */
  public function testUpdateEventContentTagInvalidUsageReturnsError(): void {
    $eventType = $this->createEventType('Conference');
    $event = $this->createEvent($eventType);

    $topicOnlyTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Only Tag',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $topicOnlyTag->save();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $event->getTitle(),
          'contentTags' => [$topicOnlyTag->uuid()],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['CONTENT_TAG_INVALID_USAGE:' . $topicOnlyTag->uuid()],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when too many content tags are provided on update.
   */
  public function testUpdateEventTooManyContentTags(): void {
    $eventType = $this->createEventType('Conference');
    $event = $this->createEvent($eventType);

    $tags = [];
    for ($i = 0; $i < 51; $i++) {
      $tag = Term::create([
        'vid' => 'social_tagging',
        'name' => "Event Tag $i",
        'field_category_usage' => serialize(['node_event']),
        'status' => 1,
      ]);
      $tag->save();
      $tags[] = $tag->uuid();
    }

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $event->getTitle(),
          'contentTags' => $tags,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['CONTENT_TAGS_LIMIT_EXCEEDED'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
