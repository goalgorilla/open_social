<?php

namespace Drupal\Tests\social_comment\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\user\UserInterface;
use Drupal\group\Entity\GroupRelationshipType;
use Drupal\group\Entity\GroupType;
use Drupal\node\Entity\Node;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Tests comment visibility for group-only content.
 *
 * For the GraphQL comments list, access is driven by node query access:
 * CommentQueryAccessHandler restricts comments to nodes returned by a node
 * query with accessCheck(TRUE). NodeQueryAccessAlterSubscriber (social_group)
 * then allows a node with field_content_visibility = 'group' only when the
 * user has a group membership (join on group_relationship + group_membership).
 * It does not check group visibility (field_flexible_group_visibility); that
 * is used only for route/UI access (FlexibleGroupContentAccessCheck). So the
 * same logic applies to any group type: content visibility on the node +
 * membership is what matters for comment list access.
 *
 * This test uses a minimal custom group type (not flexible_group) to avoid the
 * social_group_flexible_group dependency chain (social_event, meeting_api,
 * daterange_timezone) which fails in the test environment.
 *
 * @group social_graphql
 * @group social_comment
 */
class QueryCommentsGroupAccessTest extends SocialGraphQLTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'address',
    'better_exposed_filters',
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'entity',
    'entity_access_by_field',
    'options',
    'social_user',
    'role_delegation',
    'social_node',
    'social_comment',
    'comment',
    'group',
    'gnode',
    'social_group',
    'social_topic',
    'views',
    'views_bulk_operations',
    'variationcache',
    'flexible_permissions',
    'menu_ui',
    'path',
    'path_alias',
    'taxonomy',
    'file',
    'image',
    'social_core',
    'block',
    'block_content',
    'field_group',
    'image_effects',
    'file_mdm',
    'image_widget_crop',
    'crop',
    'group_core_comments',
    'hux',
    'social_tagging',
    'flag',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('comment', ['comment_entity_statistics']);

    $this->installConfig([
      'node',
      'filter',
      'social_core',
      'social_node',
      'social_topic',
      'social_tagging',
      'group',
      'social_group',
      'social_comment',
    ]);

    $this->setUpTestGroupType();
  }

  /**
   * Group type ID for comment group access tests.
   *
   * Max 14 chars so that relationship type id stays
   * <= 32 (e.g. {id}-group_membership).
   */
  private const TEST_GROUP_TYPE_ID = 'comment_grp';

  /**
   * Creates a minimal group type with topic content.
   *
   * Group module auto-installs enforced plugins (e.g. group_membership) when
   * a group type is created. We only add the group_node:topic relationship.
   */
  private function setUpTestGroupType(): void {
    $group_type = GroupType::create([
      'id' => self::TEST_GROUP_TYPE_ID,
      'label' => 'Comment access test group',
      'creator_membership' => TRUE,
    ]);
    $group_type->save();

    GroupRelationshipType::create([
      'id' => self::TEST_GROUP_TYPE_ID . '-group_node-topic',
      'group_type' => self::TEST_GROUP_TYPE_ID,
      'content_plugin' => 'group_node:topic',
      'plugin_config' => [
        'group_cardinality' => 1,
        'entity_cardinality' => 1,
        'use_creation_wizard' => FALSE,
      ],
    ])->save();
  }

  /**
   * Comment on group-only content is visible only to group members.
   *
   * Topic with field_content_visibility = 'group' in a group. Only the group
   * member sees the comment in the GraphQL comments query (node query access
   * restricts group nodes to members).
   */
  public function testCommentOnGroupOnlyContentVisibleOnlyToGroupMember(): void {
    $group = Group::create([
      'type' => self::TEST_GROUP_TYPE_ID,
      'label' => 'Secret group',
    ]);
    $group->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Group-only topic',
      'field_content_visibility' => 'group',
      'status' => 1,
    ]);
    $topic->save();

    $group->addRelationship($topic, 'group_node:topic');

    $comment = $this->createComment($topic, NULL, [
      'field_name' => 'field_topic_comments',
      'status' => 1,
    ]);

    $member = $this->createUser(['access comments']);
    $non_member = $this->createUser(['access comments']);
    $this->assertInstanceOf(UserInterface::class, $member);
    $this->assertInstanceOf(UserInterface::class, $non_member);

    $group->addMember($member);

    $query = '
      query {
        comments(first: 10) {
          nodes { id }
        }
      }
    ';

    $this->setCurrentUser($non_member);
    $context = new RenderContext();
    $result = $this->container->get('renderer')->executeInRenderContext(
      $context,
      fn () => $this->server->executeOperation(OperationParams::create(['query' => $query, 'variables' => []]))
    );
    $this->assertEmpty($result->errors ?? [], 'Non-member query should resolve without GraphQL errors.');
    $this->assertNotNull($result->data, 'Response must include data.');
    $this->assertArrayHasKey('comments', $result->data, 'Response must include comments.');
    $this->assertSame(
      [],
      $result->data['comments']['nodes'] ?? NULL,
      'Non-member must receive an empty comments list for group-only content.'
    );

    $this->setCurrentUser($member);
    $this->assertResults(
      $query,
      [],
      [
        'comments' => [
          'nodes' => [
            ['id' => $comment->uuid()],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheableDependency($comment)
    );
  }

  /**
   * Creates a comment on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The commented entity.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   Optional comment author.
   * @param array $values
   *   Extra comment values (e.g. field_name, status).
   *
   * @return \Drupal\comment\Entity\Comment
   *   The saved comment.
   */
  private function createComment(EntityInterface $entity, ?AccountInterface $user = NULL, array $values = []): Comment {
    if ($user !== NULL) {
      $values += ['uid' => $user->id()];
    }
    $comment = Comment::create(
      $values + [
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'comment_type' => 'comment',
        'field_name' => 'field_topic_comments',
      ]
    );
    $comment->save();
    return $comment;
  }

}
