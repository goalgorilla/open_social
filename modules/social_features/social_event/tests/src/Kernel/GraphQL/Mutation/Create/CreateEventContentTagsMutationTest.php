<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL\Mutation\Create;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for createEvent GraphQL mutation content tags features.
 *
 * @group social_event
 */
class CreateEventContentTagsMutationTest extends SocialEventGraphQLKernelTestBase {

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
   * Test creating an event with content tags succeeds and stores tags.
   */
  public function testCreateEventWithContentTagsSuccess(): void {
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

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $max_nid_before = $this->getMaxNodeId($node_storage);

    [$startTimestamp, $endTimestamp] = self::eventTimestamps();
    $title = 'Event With Content Tags ' . __FUNCTION__;

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          errors
          event { title }
        }
      }
      GQL,
      [
        'input' => [
          'title' => $title,
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'contentTags' => [$tag1->uuid(), $tag2->uuid()],
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => ['title' => $title],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:' . ($max_nid_before + 1)])
        ->setCacheMaxAge(0)
        ->addCacheContexts(['languages:language_interface'])
    );

    $event = $this->getEventByTitle($title);
    $this->assertInstanceOf(NodeInterface::class, $event);
    $this->assertTrue($event->hasField('social_tagging'));
    $this->assertEquals(
      [
        ['target_id' => $tag1->id()],
        ['target_id' => $tag2->id()],
      ],
      $event->get('social_tagging')->getValue(),
      'Expected the created event to have the content tags in the correct order.',
    );
  }

  /**
   * Test creating an event with empty content tags succeeds.
   */
  public function testCreateEventWithEmptyContentTags(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $max_nid_before = $this->getMaxNodeId($node_storage);

    [$startTimestamp, $endTimestamp] = self::eventTimestamps();
    $title = 'Event With No Tags ' . __FUNCTION__;

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          errors
          event { title }
        }
      }
      GQL,
      [
        'input' => [
          'title' => $title,
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'contentTags' => [],
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => ['title' => $title],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:' . ($max_nid_before + 1)])
        ->setCacheMaxAge(0)
        ->addCacheContexts(['languages:language_interface'])
    );

    $event = $this->getEventByTitle($title);
    $this->assertInstanceOf(NodeInterface::class, $event);
    $this->assertTrue($event->hasField('social_tagging'));
    $this->assertEmpty($event->get('social_tagging')->getValue());
  }

  /**
   * Test validation error for invalid content tag UUID.
   */
  public function testCreateEventInvalidContentTagUuid(): void {
    $fakeTagUuid = '12345678-1234-1234-1234-123456789999';
    [$startTimestamp, $endTimestamp] = self::eventTimestamps();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'title' => 'Event With Invalid Tag',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'contentTags' => [$fakeTagUuid],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['CONTENT_TAG_NOT_FOUND:' . $fakeTagUuid],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for content tag with invalid usage (not for events).
   */
  public function testCreateEventContentTagInvalidUsageForEvent(): void {
    $topicOnlyTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Only Tag',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $topicOnlyTag->save();

    [$startTimestamp, $endTimestamp] = self::eventTimestamps();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'title' => 'Event With Topic Tag',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'contentTags' => [$topicOnlyTag->uuid()],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['CONTENT_TAG_INVALID_USAGE:' . $topicOnlyTag->uuid()],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when too many content tags are provided.
   */
  public function testCreateEventTooManyContentTags(): void {
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

    [$startTimestamp, $endTimestamp] = self::eventTimestamps();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'title' => 'Event With Too Many Tags',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'contentTags' => $tags,
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['CONTENT_TAGS_LIMIT_EXCEEDED'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
