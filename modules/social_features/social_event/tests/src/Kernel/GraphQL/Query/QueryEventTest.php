<?php

namespace Drupal\Tests\social_event\Kernel\GraphQL\Query;

use Drupal\comment\Entity\Comment;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
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
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'social_event',
    'social_event_type',
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

    // Meeting API modules required by social_event configurations.
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',

    'address',
    'profile',
    'social_profile',
    'social_event_managers',
    'views_bulk_operations',
    'variationcache',
    'better_exposed_filters',
    'smart_trim',
    'social_tagging',
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
    $this->installEntitySchema('taxonomy_term');

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installSchema('file', 'file_usage');

    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_event',
      'social_event_managers',
      'social_event_type',
      'filter',
      'comment',
      'social_comment',
      'social_tagging',
    ]);
  }

  /**
   * Test that the fields provided by the module can be queried.
   */
  public function testSupportsFieldsIncludedInModule() : void {
    $event_types = Vocabulary::load('event_types');
    $this->assertNotNull($event_types, 'The event_types vocabulary must exist.');
    $event_type_term = $this->createTerm($event_types);

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
      'field_event_type' => $event_type_term->id(),
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
          eventType {
            id
            label
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
          'eventType' => [
            'id' => $event_type_term->uuid(),
            'label' => $event_type_term->label(),
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
            'url' => is_null($event_image->getFileUri()) ? 'core/misc/druplicon.png' : \Drupal::service('file_url_generator')->generateAbsoluteString($event_image->getFileUri()),
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
        ->addCacheableDependency($event_type_term)
        ->addCacheTags(['taxonomy_term:' . $event_type_term->id(), 'config:filter.format.plain_text', 'config:filter.settings', 'comment:1'])
        ->addCacheContexts(['languages:language_interface', 'url.site'])
    );
  }

  /**
   * Test that it respects the access events permission.
   */
  public function testRequiresAccessEventsPermission(): void {
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

  /**
   * Test that event with event type returns correct eventType field via API.
   *
   * Tests that the eventType field on the Event type correctly resolves to
   * the associated taxonomy term when an event has an event type assigned.
   * This validates that the field resolver properly handles entity reference
   * fields and returns the expected data structure.
   */
  public function testEventWithEventTypeReturnsCorrectType(): void {
    $event_types = Vocabulary::load('event_types');
    $this->assertNotNull($event_types, 'The event_types vocabulary must exist.');
    $event_type_term = $this->createTerm($event_types);

    $event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'field_event_type' => $event_type_term->id(),
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser([], array_merge([
      'view node.event.field_content_visibility:public content',
    ], $this->userPermissions()));

    $this->assertResults('
        query ($id: ID!) {
          event(id: $id) {
            id
            eventType {
              id
              label
            }
          }
        }
      ',
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'eventType' => [
            'id' => $event_type_term->uuid(),
            'label' => $event_type_term->label(),
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheableDependency($event_type_term)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that event without event type returns null via GraphQL API.
   *
   * Tests that the eventType field correctly returns null when an event
   * does not have an event type assigned. This validates that the field
   * resolver handles the absence of an entity reference gracefully.
   */
  public function testEventWithoutEventTypeReturnsNull(): void {
    $event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser([], array_merge([
      'view node.event.field_content_visibility:public content',
    ], $this->userPermissions()));

    $this->assertResults('
        query ($id: ID!) {
          event(id: $id) {
            id
            eventType {
              id
              label
            }
          }
        }
      ',
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'eventType' => NULL,
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheTags(['taxonomy_term_list'])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that unpublished event type is filtered based on permissions.
   *
   * Tests that the eventType field correctly filters out unpublished
   * taxonomy terms for API consumers without 'administer taxonomy' permission.
   * This ensures that draft/unpublished event types are not exposed through
   * the GraphQL API to regular consumers, protecting unpublished content.
   */
  public function testUnpublishedEventTypeNotVisibleWithoutPermissions(): void {
    $unpublished_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Unpublished Type',
      'status' => 0,
    ]);
    $unpublished_term->save();

    $event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'field_event_type' => $unpublished_term->id(),
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Set up API consumer with standard content access permissions but without
    // taxonomy administration scope.
    $this->setUpCurrentUser([], array_merge([
      'view node.event.field_content_visibility:public content',
      'access content',
    ], $this->userPermissions()));

    $this->assertResults('
        query ($id: ID!) {
          event(id: $id) {
            id
            eventType {
              id
              label
            }
          }
        }
      ',
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'eventType' => NULL,
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheableDependency($unpublished_term)
        ->addCacheTags(['taxonomy_term_list'])
        ->addCacheContexts(['languages:language_interface', 'user.permissions'])
    );
  }

  /**
   * Test that unpublished event type is visible with correct permissions.
   *
   * Tests that the eventType field correctly includes unpublished taxonomy
   * terms for API consumers with 'administer taxonomy' permission. This
   * validates that the access control mechanism properly allows administrative
   * access to draft/unpublished event types through the GraphQL API.
   */
  public function testUnpublishedEventTypeIsVisibleWithPermissions(): void {
    $unpublished_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Unpublished Type',
      'status' => 0,
    ]);
    $unpublished_term->save();

    $event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'field_event_type' => $unpublished_term->id(),
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Set up context with 'administer taxonomy' permission to test access
    // control logic. This validates that the API correctly handles
    // administrative access to unpublished taxonomy terms.
    $permissions = array_merge([
      'view node.event.field_content_visibility:public content',
      'administer taxonomy',
      'access content',
    ], $this->userPermissions());
    $this->setUpCurrentUser([], $permissions);

    $this->assertResults('
        query ($id: ID!) {
          event(id: $id) {
            id
            eventType {
              id
              label
            }
          }
        }
      ',
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'eventType' => [
            'id' => $unpublished_term->uuid(),
            'label' => $unpublished_term->label(),
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheableDependency($unpublished_term)
        ->addCacheContexts(['languages:language_interface', 'user.permissions'])
    );
  }

  /**
   * Test that event with address returns the address via GraphQL API.
   */
  public function testEventWithAddressReturnsAddress(): void {
    $address_value = [
      'country_code' => 'NL',
      'administrative_area' => 'NH',
      'locality' => 'Amsterdam',
      'dependent_locality' => '',
      'postal_code' => '1012 AB',
      'address_line1' => 'Dam Square 1',
      'address_line2' => '',
    ];
    $event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'field_event_address' => [$address_value],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser([], array_merge([
      'view node.event.field_content_visibility:public content',
    ], $this->userPermissions()));

    $this->assertResults('
        query ($id: ID!) {
          event(id: $id) {
            id
            address {
              countryCode
              administrativeArea
              locality
              postalCode
              addressLine1
            }
          }
        }
      ',
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'address' => [
            'countryCode' => 'NL',
            'administrativeArea' => 'NH',
            'locality' => 'Amsterdam',
            'postalCode' => '1012 AB',
            'addressLine1' => 'Dam Square 1',
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that event without address returns null for address via GraphQL API.
   */
  public function testEventWithoutAddressReturnsNull(): void {
    $event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser([], array_merge([
      'view node.event.field_content_visibility:public content',
    ], $this->userPermissions()));

    $this->assertResults('
        query ($id: ID!) {
          event(id: $id) {
            id
            address {
              countryCode
            }
          }
        }
      ',
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'address' => NULL,
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
