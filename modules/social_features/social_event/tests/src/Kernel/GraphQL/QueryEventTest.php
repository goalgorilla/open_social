<?php

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the event field on the Query type.
 *
 * @group social_graphql
 * @group social_event
 */
class QueryEventTest extends SocialGraphQLTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'social_event',
    'entity',
    // For the event author and viewer.
    'social_user',
    'user',
    // User creation in social_user requires a service in role_delegation.
    "role_delegation",
    // social_comment configures events for nodes.
    'node',
    // The default event config contains a body text field.
    'field',
    'text',
    'filter',
    'file',
    'image',
    // For the comment functionality.
    'social_comment',
    'comment',
    'menu_ui',
    'entity_access_by_field',
    'options',
    'taxonomy',
    'path',
    'image_widget_crop',
    'crop',
    'field_group',
    'social_node',
    'social_core',
    'block',
    'block_content',
    'image_effects',
    'file_mdm',
    'group_core_comments',
    'views',
    'group',
    'datetime',
    'address',
    'profile',
    'social_profile',
    'social_event_managers',
    'views_bulk_operations',
    // @deprecated added since we use social_entity_url producer in the
    // EventSchemaExtension, but social_entity_url marked as deprecated so,
    // social_topic should be removed from $modules when
    // https://github.com/drupal-graphql/graphql/pull/1220 is merged.
    'social_topic',
  ];

  /**
   * An array of config object names that are excluded from schema checking.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = [
    'social_event_managers.settings',
    'views.view.event_manage_enrollments',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('profile');

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_event',
      'social_event_managers',
      'filter',
      'comment',
      'social_comment',
    ]);
  }

  /**
   * Test that the fields provided by the module can be queried.
   */
  public function testSupportsFieldsIncludedInModule() : void {
    $raw_event_body = "This is a link test: https://social.localhost";
    $html_event_body = "<div><p>This is a link test: <a href=\"https://social.localhost\">https://social.localhost</a></p>\n</div>";

    $event_image = File::create();
    $event_image->setFileUri('core/misc/druplicon.png');
    $event_image->setFilename(\Drupal::service('file_system')->basename($event_image->getFileUri()));
    $event_image->save();

    $event_manager = $this->createUser();

    $event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'field_event_image' => $event_image->id(),
      'field_event_managers' => [$event_manager->id()],
      'status' => NodeInterface::PUBLISHED,
      'body' => $raw_event_body,
    ]);

    // Set up a user that can view public events and create a published
    // comment and view it.
    $this->setUpCurrentUser([], array_merge([
      'skip comment approval',
      'access comments',
      'view node.event.field_content_visibility:public content',
    ], $this->userPermissions()));

    $comment = Comment::create([
      'entity_id' => $event->id(),
      'entity_type' => $event->getEntityTypeId(),
      'comment_type' => 'comment',
      'field_name' => 'comments',
    ]);
    $comment->save();

    $query = '
      query ($id: ID!) {
        event(id: $id) {
          id
          title
          author {
            id
            displayName
          }
          bodyHtml
          comments(first: 1) {
            nodes {
              id
            }
          }
          url
          created {
            timestamp
          }
          heroImage {
            url
          }
          managers(first: 1) {
            nodes {
              id
            }
          }
        }
      }
    ';

    $this->assertResults(
      $query,
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'title' => $event->label(),
          'author' => [
            'id' => $event->getOwner()->uuid(),
            'displayName' => $event->getOwner()->getDisplayName(),
          ],
          'bodyHtml' => $html_event_body,
          'comments' => [
            'nodes' => [
              ['id' => $comment->uuid()],
            ],
          ],
          'url' => $event->toUrl('canonical', ['absolute' => TRUE])->toString(),
          'created' => [
            'timestamp' => $event->getCreatedTime(),
          ],
          'heroImage' => [
            'url' => file_create_url($event_image->getFileUri()),
          ],
          'managers' => [
            'nodes' => [
              ['id' => $event_manager->uuid()],
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheableDependency($event)
        ->addCacheableDependency($event->getOwner())
        ->addCacheableDependency($event_manager)
        ->addCacheTags(['config:filter.format.plain_text', 'config:filter.settings', 'comment:1'])
        ->addCacheContexts(['languages:language_interface', 'user.node_grants:view', 'url.site'])
    );
  }

  /**
   * Test that it respects the access events permission.
   */
  public function testRequiresAccessEventsPermission() {
    $topic = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Create a user that is not allowed to view public topics and comments.
    $this->setUpCurrentUser();

    $this->assertResults('
        query ($id: ID!) {
          event(id: $id) {
            id
          }
        }
      ',
      ['id' => $topic->uuid()],
      ['event' => NULL],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
