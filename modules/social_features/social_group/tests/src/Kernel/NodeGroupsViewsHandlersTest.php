<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Kernel;

use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\comment\CommentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the views handlers that expose the groups of a content item.
 *
 * Cross-posted content is related to several groups. It should be listed once,
 * with all of its groups.
 *
 * @group social_group
 */
class NodeGroupsViewsHandlersTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'entity',
    'flag',
    'flexible_permissions',
    'gnode',
    'group',
    'node',
    'options',
    'social_group',
    'views',
    'views_bulk_operations',
  ];

  /**
   * The node that is published in every group.
   */
  protected NodeInterface $node;

  /**
   * The groups the node is published in.
   *
   * @var \Drupal\group\Entity\GroupInterface[]
   */
  protected array $groups = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);
    // Flag looks up the flaggings of a node when it is deleted.
    $this->installEntitySchema('flagging');

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

    $group_type = $this->createGroupType(['id' => 'example']);
    $relationship_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($relationship_type_storage instanceof GroupRelationshipTypeStorageInterface);
    $relationship_type_storage->createFromPlugin($group_type, 'group_node:page')->save();

    // Everybody may see the content of the groups, so the group query access
    // does not hide it from the view.
    foreach (['outsider', 'insider'] as $scope) {
      $this->createGroupRole([
        'id' => 'example-' . $scope,
        'group_type' => 'example',
        'scope' => $scope,
        'global_role' => 'authenticated',
        'permissions' => ['view group_node:page entity'],
      ]);
    }

    $this->node = $this->createPage('Cross-posted content');

    foreach (['First group', 'Second group', 'Third group'] as $label) {
      $group = $this->createGroup(['type' => 'example', 'label' => $label]);
      $group->addRelationship($this->node, 'group_node:page');
      $this->groups[] = $group;
    }

    $this->createTestView();
  }

  /**
   * Tests that content is listed once, with all groups it is published in.
   */
  public function testContentIsListedOnceWithAllItsGroups(): void {
    $view = Views::getView('social_group_node_groups_test');
    $this->assertNotNull($view);
    $view->execute();

    $this->assertCount(1, $view->result);

    $view->field['groups']->preRender($view->result);
    $groups = (string) $view->field['groups']->render($view->result[0]);

    $positions = [];
    foreach ($this->groups as $group) {
      $position = strpos($groups, (string) $group->label());
      $this->assertNotFalse($position, "The group {$group->label()} is missing.");
      $positions[] = $position;
    }

    // The group the content was published in first is listed first, the groups
    // it was cross-posted to follow in the order they were added.
    $sorted = $positions;
    sort($sorted);
    $this->assertSame($sorted, $positions);
  }

  /**
   * Tests that content without groups has no groups to show.
   */
  public function testContentWithoutGroupsIsListedWithoutGroups(): void {
    $node = $this->createPage('Content outside of a group');

    $view = Views::getView('social_group_node_groups_test');
    $this->assertNotNull($view);
    $view->execute();

    $this->assertCount(2, $view->result);

    $view->field['groups']->preRender($view->result);

    $rows = [];
    foreach ($view->result as $row) {
      $rows[(int) $row->_entity->id()] = (string) $view->field['groups']->render($row);
    }

    $this->assertSame('', $rows[(int) $node->id()]);
    $this->assertNotSame('', $rows[(int) $this->node->id()]);
  }

  /**
   * Tests that a listing without any content in a group can be built.
   */
  public function testContentIsListedWhenNothingBelongsToAnyGroup(): void {
    // The cross-posted node is the only content that is in a group.
    $this->node->delete();
    $node = $this->createPage('Content outside of a group');

    $view = Views::getView('social_group_node_groups_test');
    $this->assertNotNull($view);
    $view->execute();

    $this->assertCount(1, $view->result);

    $view->field['groups']->preRender($view->result);

    $this->assertSame((int) $node->id(), (int) $view->result[0]->_entity->id());
    $this->assertSame('', (string) $view->field['groups']->render($view->result[0]));
  }

  /**
   * Tests that filtering on a group returns its content once.
   */
  public function testFilteringOnOneGroupReturnsItsContentOnce(): void {
    $other_node = $this->createPage('Content of another group');
    $other_group = $this->createGroup(['type' => 'example', 'label' => 'Another group']);
    $other_group->addRelationship($other_node, 'group_node:page');

    // The cross-posted node is in every group of the test, so filtering on any
    // of them returns it once and leaves the content of the other group out.
    foreach ($this->groups as $group) {
      $view = Views::getView('social_group_node_groups_test');
      $this->assertNotNull($view);
      $view->initHandlers();
      $view->filter['groups']->value = [$group->id()];
      $view->execute();

      $this->assertCount(1, $view->result, "Filtering on {$group->label()} returns one row.");
      $this->assertSame(
        (int) $this->node->id(),
        (int) $view->result[0]->_entity->id(),
        "Filtering on {$group->label()} returns the content of that group."
      );
    }

    $view = Views::getView('social_group_node_groups_test');
    $this->assertNotNull($view);
    $view->initHandlers();
    $view->filter['groups']->value = [$other_group->id()];
    $view->execute();

    $this->assertCount(1, $view->result);
    $this->assertSame((int) $other_node->id(), (int) $view->result[0]->_entity->id());
  }

  /**
   * Tests that the column can be sorted on the group content was posted in.
   */
  public function testSortingOnTheOriginalGroup(): void {
    // A node in a group that sorts before the groups of the cross-posted node.
    $node = $this->createPage('Content of another group');
    $this->createGroup(['type' => 'example', 'label' => 'A first group'])
      ->addRelationship($node, 'group_node:page');

    $ascending = [$node, $this->node];

    foreach (['asc' => $ascending, 'desc' => array_reverse($ascending)] as $direction => $expected) {
      $view = Views::getView('social_group_node_groups_test');
      $this->assertNotNull($view);
      // Clicking the column header of a table links to the view with these
      // query arguments.
      $view->setRequest(Request::create('/', 'GET', [
        'order' => 'groups',
        'sort' => $direction,
      ]));
      $view->execute();

      $this->assertCount(2, $view->result, "Sorting $direction returns every node once.");
      foreach ($expected as $delta => $expected_node) {
        $this->assertSame(
          (int) $expected_node->id(),
          (int) $view->result[$delta]->_entity->id(),
          "Sorting $direction puts {$expected_node->label()} on position $delta."
        );
      }
    }
  }

  /**
   * Tests the handlers on a view that reaches the content over a relationship.
   *
   * The comment overview has no content of its own, it uses the handlers with
   * a relationship to the commented content.
   */
  public function testHandlersOverTheRelationshipToTheContent(): void {
    $this->addCommentFieldToPages();
    $comment = $this->createComment($this->node, 'A comment on cross-posted content');
    $other_node = $this->createPage('Content of another group');
    $other_group = $this->createGroup(['type' => 'example', 'label' => 'A first group']);
    $other_group->addRelationship($other_node, 'group_node:page');
    $other_comment = $this->createComment($other_node, 'A comment on other content');

    $this->createCommentTestView();

    // The comment on the cross-posted content is listed once, with every group
    // that content is published in.
    $view = Views::getView('social_group_comment_groups_test');
    $this->assertNotNull($view);
    $view->execute();

    $this->assertCount(2, $view->result);
    $view->field['groups']->preRender($view->result);

    $rendered = [];
    foreach ($view->result as $row) {
      $rendered[(int) $row->_entity->id()] = (string) $view->field['groups']->render($row);
    }

    $this->assertCount(1, array_keys($rendered, $rendered[(int) $comment->id()], TRUE));
    foreach ($this->groups as $group) {
      $this->assertStringContainsString((string) $group->label(), $rendered[(int) $comment->id()]);
    }
    $this->assertStringContainsString(
      (string) $other_group->label(),
      $rendered[(int) $other_comment->id()]
    );

    // Filtering on a group returns the comments on the content of that group.
    $view = Views::getView('social_group_comment_groups_test');
    $this->assertNotNull($view);
    $view->initHandlers();
    $first_group = reset($this->groups);
    $this->assertInstanceOf(GroupInterface::class, $first_group);
    $view->filter['groups']->value = [$first_group->id()];
    $view->execute();

    $this->assertCount(1, $view->result);
    $this->assertSame((int) $comment->id(), (int) $view->result[0]->_entity->id());

    // Sorting on the column orders the comments by the group of their content.
    $ascending = [$other_comment, $comment];
    foreach (['asc' => $ascending, 'desc' => array_reverse($ascending)] as $direction => $expected) {
      $view = Views::getView('social_group_comment_groups_test');
      $this->assertNotNull($view);
      $view->setRequest(Request::create('/', 'GET', [
        'order' => 'groups',
        'sort' => $direction,
      ]));
      $view->execute();

      $this->assertCount(2, $view->result, "Sorting $direction returns every comment once.");
      foreach ($expected as $delta => $expected_comment) {
        $this->assertSame(
          (int) $expected_comment->id(),
          (int) $view->result[$delta]->_entity->id(),
          "Sorting $direction puts comment {$expected_comment->id()} on position $delta."
        );
      }
    }
  }

  /**
   * Adds a comment field to the page content type.
   */
  protected function addCommentFieldToPages(): void {
    CommentType::create([
      'id' => 'comment',
      'label' => 'Comment',
      'target_entity_type_id' => 'node',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'comment',
      'entity_type' => 'node',
      'type' => 'comment',
      'settings' => ['comment_type' => 'comment'],
    ])->save();

    FieldConfig::create([
      'field_name' => 'comment',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Comments',
    ])->save();
  }

  /**
   * Creates a published comment on a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The commented node.
   * @param string $subject
   *   The subject of the comment.
   *
   * @return \Drupal\comment\CommentInterface
   *   The created comment.
   */
  protected function createComment(NodeInterface $node, string $subject): CommentInterface {
    $comment = Comment::create([
      'entity_type' => 'node',
      'entity_id' => $node->id(),
      'field_name' => 'comment',
      'comment_type' => 'comment',
      'subject' => $subject,
      'status' => CommentInterface::PUBLISHED,
      'uid' => $this->getCurrentUser()->id(),
    ]);
    $comment->save();

    return $comment;
  }

  /**
   * Creates a published page.
   *
   * @param string $title
   *   The title of the page.
   *
   * @return \Drupal\node\NodeInterface
   *   The created page.
   */
  protected function createPage(string $title): NodeInterface {
    $node = Node::create([
      'type' => 'page',
      'title' => $title,
      'status' => NodeInterface::PUBLISHED,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Creates a view that lists nodes with the groups they are published in.
   */
  protected function createTestView(): void {
    View::create([
      'id' => 'social_group_node_groups_test',
      'label' => 'Node groups test',
      'base_table' => 'node_field_data',
      'base_field' => 'nid',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_title' => 'Default',
          'display_plugin' => 'default',
          'position' => 0,
          'display_options' => [
            'query' => [
              'type' => 'views_query',
              'options' => ['distinct' => TRUE],
            ],
            // A table lets its sortable columns be sorted by clicking their
            // header, like the content and comment overviews do.
            'style' => [
              'type' => 'table',
              'options' => [
                'columns' => [
                  'title' => 'title',
                  'groups' => 'groups',
                ],
                'info' => [
                  'title' => ['sortable' => TRUE],
                  'groups' => ['sortable' => TRUE],
                ],
              ],
            ],
            'sorts' => [
              'nid' => [
                'id' => 'nid',
                'table' => 'node_field_data',
                'field' => 'nid',
                'plugin_id' => 'standard',
                'order' => 'ASC',
              ],
            ],
            'row' => ['type' => 'fields'],
            'fields' => [
              'title' => [
                'id' => 'title',
                'table' => 'node_field_data',
                'field' => 'title',
                'plugin_id' => 'field',
                'entity_type' => 'node',
                'entity_field' => 'title',
              ],
              'groups' => [
                'id' => 'groups',
                'table' => 'node_field_data',
                'field' => 'social_group_node_groups',
                'plugin_id' => 'social_group_node_groups',
              ],
            ],
            'filters' => [
              'groups' => [
                'id' => 'groups',
                'table' => 'node_field_data',
                'field' => 'social_group_node_groups',
                'plugin_id' => 'social_group_node_groups',
                'operator' => 'in',
                'value' => [],
              ],
            ],
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Creates a view listing comments with the groups of the commented content.
   */
  protected function createCommentTestView(): void {
    View::create([
      'id' => 'social_group_comment_groups_test',
      'label' => 'Comment groups test',
      'base_table' => 'comment_field_data',
      'base_field' => 'cid',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_title' => 'Default',
          'display_plugin' => 'default',
          'position' => 0,
          'display_options' => [
            'query' => [
              'type' => 'views_query',
              'options' => ['distinct' => TRUE],
            ],
            'style' => [
              'type' => 'table',
              'options' => [
                'columns' => [
                  'subject' => 'subject',
                  'groups' => 'groups',
                ],
                'info' => [
                  'subject' => ['sortable' => TRUE],
                  'groups' => ['sortable' => TRUE],
                ],
              ],
            ],
            'sorts' => [
              'cid' => [
                'id' => 'cid',
                'table' => 'comment_field_data',
                'field' => 'cid',
                'plugin_id' => 'standard',
                'order' => 'ASC',
              ],
            ],
            'row' => ['type' => 'fields'],
            'relationships' => [
              'node' => [
                'id' => 'node',
                'table' => 'comment_field_data',
                'field' => 'node',
                'plugin_id' => 'standard',
                'required' => FALSE,
              ],
            ],
            'fields' => [
              'subject' => [
                'id' => 'subject',
                'table' => 'comment_field_data',
                'field' => 'subject',
                'plugin_id' => 'field',
                'entity_type' => 'comment',
                'entity_field' => 'subject',
              ],
              'groups' => [
                'id' => 'groups',
                'table' => 'node_field_data',
                'field' => 'social_group_node_groups',
                'relationship' => 'node',
                'plugin_id' => 'social_group_node_groups',
              ],
            ],
            'filters' => [
              'groups' => [
                'id' => 'groups',
                'table' => 'node_field_data',
                'field' => 'social_group_node_groups',
                'relationship' => 'node',
                'plugin_id' => 'social_group_node_groups',
                'operator' => 'in',
                'value' => [],
              ],
            ],
          ],
        ],
      ],
    ])->save();
  }

}
