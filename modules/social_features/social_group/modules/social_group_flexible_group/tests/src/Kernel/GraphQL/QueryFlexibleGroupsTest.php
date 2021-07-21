<?php

namespace Drupal\Tests\social_group_flexible_group\Kernel\GraphQL;

use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the flexibleGroups field on the Query type.
 *
 * @group social_graphql
 * @group social_group_flexible_group
 */
class QueryFlexibleGroupsTest extends SocialGraphQLTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;
  use FieldGroupTestTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity',
    // For the topic author and viewer.
    'social_user',
    'user',
    // User creation in social_user requires a service in role_delegation.
    "role_delegation",
    // The default topic config contains a body text field.
    'field',
//    'text',
    'filter',
    'file',
    // For the comment functionality.
//    'social_comment',
    'comment',
    // node.type.topic has a configuration dependency on the menu_ui module.
//    'menu_ui',
    'entity_access_by_field',
//    'options',
    'taxonomy',
//    'path',
//    'image_widget_crop',
//    'crop',
    'field_group',
    'social_node',
    'social_core',
//    'block',
    'block_content',
    'image_effects',
    'file_mdm',
    'group_core_comments',
    'views',

    'address',
    'field',
    'field_group',
    'gnode',
    'group',
    'image',
    'crop',
    'image_widget_crop',
    'node',
    'options',
    'path',
    'social_core',
    'social_event',
    'social_group',
    'social_topic',
    'text',
    'user',
    'social_group_flexible_group',
    'datetime',
    'profile',
    'social_profile',
    'views_bulk_operations',
    'social_comment',
  ];

  /**
   * An array of config object names that are excluded from schema checking.
   *
   * @var string[]
   */
  protected static array $configSchemaCheckerExclusions = [
    'node.type.event',
    'block_content.type.basic',
    'block_content.type.hero_call_to_action_block',
    'block_content.type.platform_intro',
    'views.view.event_manage_enrollment_requests',
    'node.type.topic',
    'social_topic.settings',
    'views.view.group_manage_members',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('taxonomy_term');

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

//    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_event',
      'social_topic',
      'filter',

      'group',
      'social_group',
      'social_group_flexible_group',
    ]);
  }

  /**
   * Test that platform topics can be fetched using platform pagination.
   */
  public function testSupportsRelayPagination(): void {
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->setUpCurrentUser([], []);

    for ($i = 0; $i < 10; ++$i) {
      $flexible_groups[] = $this->createGroup();
    }

    $flexible_group_uuids = array_map(
      static fn($flexible_group) => $flexible_group->uuid(),
      $flexible_groups ?? []
    );

    $this->assertEndpointSupportsPagination(
      'flexibleGroups',
      $flexible_group_uuids
    );
  }

//  /**
//   * Test that a anonymous user can only see public topics.
//   */
//  public function testAnonymousUserCanViewOnlyPublicTopics() {
//    $public_topic = $this->createNode([
//      'type' => 'topic',
//      'field_content_visibility' => 'public',
//      'status' => NodeInterface::PUBLISHED,
//    ]);
//    $this->createNode([
//      'type' => 'topic',
//      'field_content_visibility' => 'community',
//      'status' => NodeInterface::PUBLISHED,
//    ]);
//    $this->createNode([
//      'type' => 'topic',
//      'field_content_visibility' => 'group',
//      'status' => NodeInterface::PUBLISHED,
//    ]);
//
//    $this->setUpCurrentUser([], ['view node.topic.field_content_visibility:public content']);
//
//    $this->assertResults('
//          query {
//            topics(last: 3) {
//              pageInfo {
//                hasNextPage
//                hasPreviousPage
//              }
//              nodes {
//                id
//              }
//            }
//          }
//        ',
//      [],
//      [
//        'topics' => [
//          'pageInfo' => [
//            'hasNextPage' => FALSE,
//            'hasPreviousPage' => FALSE,
//          ],
//          'nodes' => [
//            ['id' => $public_topic->uuid()],
//          ],
//        ],
//      ],
//      $this->defaultCacheMetaData()
//        ->setCacheMaxAge(0)
//        ->addCacheableDependency($public_topic)
//        ->addCacheContexts(['languages:language_interface'])
//    );
//  }
//
//  /**
//   * Test that a anonymous user can not see unpublished topics.
//   */
//  public function testAnonymousUserCanNotViewUnpublishedTopics() {
//    $this->createNode([
//      'type' => 'topic',
//      'field_content_visibility' => 'public',
//      'status' => NodeInterface::NOT_PUBLISHED,
//    ]);
//    $published_topic = $this->createNode([
//      'type' => 'topic',
//      'field_content_visibility' => 'public',
//      'status' => NodeInterface::PUBLISHED,
//    ]);
//
//    $this->setUpCurrentUser([], ['view node.topic.field_content_visibility:public content']);
//
//    $this->assertResults('
//          query {
//            topics(last: 3) {
//              nodes {
//                id
//              }
//            }
//          }
//        ',
//      [],
//      [
//        'topics' => [
//          'nodes' => [
//            ['id' => $published_topic->uuid()],
//          ],
//        ],
//      ],
//      $this->defaultCacheMetaData()
//        ->setCacheMaxAge(0)
//        ->addCacheableDependency($published_topic)
//        ->addCacheContexts(['languages:language_interface'])
//    );
//  }
//
//  /**
//   * Test that a user without permission can not see any topics.
//   */
//  public function testAnonymousUserCanNotViewTopicsWithoutPermission() {
//    $this->createNode([
//      'type' => 'topic',
//      'field_content_visibility' => 'public',
//      'status' => NodeInterface::NOT_PUBLISHED,
//    ]);
//    $this->createNode([
//      'type' => 'topic',
//      'field_content_visibility' => 'public',
//      'status' => NodeInterface::PUBLISHED,
//    ]);
//
//    $this->setUpCurrentUser();
//
//    $this->assertResults('
//          query {
//            topics(last: 2) {
//              nodes {
//                id
//              }
//            }
//          }
//        ',
//      [],
//      [
//        'topics' => [
//          'nodes' => [],
//        ],
//      ],
//      $this->defaultCacheMetaData()
//        ->setCacheMaxAge(0)
//        ->addCacheContexts(['languages:language_interface'])
//    );
//  }
  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createGroup(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('group');
    $group = $storage->create($values + [
        'type' => 'flexible_group',
        'label' => $this->randomString(),
      ]);
    $group->enforceIsNew();
    $storage->save($group);

    return $group;
  }
}
