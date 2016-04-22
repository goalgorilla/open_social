<?php

namespace Drupal\Tests\search_api\Kernel\Processor;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Utility;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the "Content access" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\ContentAccess
 */
class ContentAccessTest extends ProcessorTestBase {

  use CommentTestTrait;

  /**
   * The nodes created for testing.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes;

  /**
   * The comments created for testing.
   *
   * @var \Drupal\comment\Entity\Comment[]
   */
  protected $comments;

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp('content_access');

    // The parent method already installs most needed node and comment schemas,
    // but here we also need the comment statistics.
    $this->installSchema('comment', array('comment_entity_statistics'));

    // Create a node type for testing.
    $type = NodeType::create(array('type' => 'page', 'name' => 'page'));
    $type->save();

    // Create anonymous user role.
    $role = Role::create(array(
      'id' => 'anonymous',
      'label' => 'anonymous',
    ));
    $role->save();

    // Insert the anonymous user into the database, as the user table is inner
    // joined by \Drupal\comment\CommentStorage.
    User::create(array(
      'uid' => 0,
      'name' => '',
    ))->save();

    // Create a node with attached comment.
    $values = array(
      'status' => NODE_PUBLISHED,
      'type' => 'page',
      'title' => 'test title',
    );
    $this->nodes[0] = Node::create($values);
    $this->nodes[0]->save();

    $comment_type = CommentType::create(array(
      'id' => 'comment',
      'target_entity_type_id' => 'node',
    ));
    $comment_type->save();

    $this->installConfig(array('comment'));
    $this->addDefaultCommentField('node', 'page');

    $comment = Comment::create(array(
      'entity_type' => 'node',
      'entity_id' => $this->nodes[0]->id(),
      'field_name' => 'comment',
      'body' => 'test body',
      'comment_type' => $comment_type->id(),
    ));
    $comment->save();

    $this->comments[] = $comment;

    $values = array(
      'status' => NODE_PUBLISHED,
      'type' => 'page',
      'title' => 'some title',
    );
    $this->nodes[1] = Node::create($values);
    $this->nodes[1]->save();

    $values = array(
      'status' => NODE_NOT_PUBLISHED,
      'type' => 'page',
      'title' => 'other title',
    );
    $this->nodes[2] = Node::create($values);
    $this->nodes[2]->save();

    // Also index users, to verify that they are unaffected by the processor.
    $manager = \Drupal::getContainer()
      ->get('plugin.manager.search_api.datasource');
    $datasources['entity:comment'] = $manager->createInstance('entity:comment', array('index' => $this->index));
    $datasources['entity:node'] = $manager->createInstance('entity:node', array('index' => $this->index));
    $datasources['entity:user'] = $manager->createInstance('entity:user', array('index' => $this->index));
    $this->index->setDatasources($datasources);
    $this->index->save();

    \Drupal::getContainer()->get('search_api.index_task_manager')->addItemsAll($this->index);
    $index_storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    $index_storage->resetCache([$this->index->id()]);
    $this->index = $index_storage->load($this->index->id());
  }

  /**
   * Tests searching when content is accessible to all.
   */
  public function testQueryAccessAll() {
    $permissions = array('access content', 'access comments');
    user_role_grant_permissions('anonymous', $permissions);
    $this->index->reindex();
    $this->index->indexItems();
    $this->assertEquals(5, $this->index->getTrackerInstance()->getIndexedItemsCount(), '5 items indexed, as expected.');

    $query = Utility::createQuery($this->index);
    $result = $query->execute();

    $expected = array(
      'user' => array(0),
      'comment' => array(0),
      'node' => array(0, 1),
    );
    $this->assertResults($result, $expected);
  }

  /**
   * Tests searching when only comments are accessible.
   */
  public function testQueryAccessComments() {
    user_role_grant_permissions('anonymous', array('access comments'));
    $this->index->reindex();
    $this->index->indexItems();
    $this->assertEquals(5, $this->index->getTrackerInstance()->getIndexedItemsCount(), '5 items indexed, as expected.');

    $query = Utility::createQuery($this->index);
    $result = $query->execute();

    $this->assertResults($result, array('user' => array(0), 'comment' => array(0)));
  }

  /**
   * Tests searching for own unpublished content.
   */
  public function testQueryAccessOwn() {
    // Create the user that will be passed into the query.
    $permissions = array(
      'access content',
      'access comments',
      'view own unpublished content',
    );
    $authenticated_user = $this->createUser($permissions);
    $uid = $authenticated_user->id();

    $values = array(
      'status' => NODE_NOT_PUBLISHED,
      'type' => 'page',
      'title' => 'foo',
      'uid' => $uid,
    );
    $this->nodes[3] = Node::create($values);
    $this->nodes[3]->save();
    $this->index->indexItems();
    $this->assertEquals(7, $this->index->getTrackerInstance()->getIndexedItemsCount(), '7 items indexed, as expected.');

    $query = Utility::createQuery($this->index);
    $query->setOption('search_api_access_account', $authenticated_user);
    $result = $query->execute();

    $expected = array('user' => array(0, $uid), 'node' => array(3));
    $this->assertResults($result, $expected);
  }

  /**
   * Tests building the query when content is accessible based on node grants.
   */
  public function testQueryAccessWithNodeGrants() {
    // Create the user that will be passed into the query.
    $permissions = array(
      'access content',
    );
    $authenticated_user = $this->createUser($permissions);

    Database::getConnection()->insert('node_access')
      ->fields(array(
        'nid' => $this->nodes[0]->id(),
        'langcode' => $this->nodes[0]->language()->getId(),
        'gid' => $authenticated_user->id(),
        'realm' => 'search_api_test',
        'grant_view' => 1,
      ))
      ->execute();

    $this->index->reindex();
    $this->index->indexItems();
    $query = Utility::createQuery($this->index);
    $query->setOption('search_api_access_account', $authenticated_user);
    $result = $query->execute();

    $expected = array(
      'user' => array(0, $authenticated_user->id()),
      'node' => array(0),
    );
    $this->assertResults($result, $expected);
  }

  /**
   * Tests comment indexing when all users have access to content.
   */
  public function testContentAccessAll() {
    user_role_grant_permissions('anonymous', array('access content', 'access comments'));
    $items = array();
    foreach ($this->comments as $comment) {
      $items[] = array(
        'datasource' => 'entity:comment',
        'item' => $comment->getTypedData(),
        'item_id' => $comment->id(),
        'text' => 'Comment: ' . $comment->id(),
      );
    }
    $items = $this->generateItems($items);

    $this->processor->preprocessIndexItems($items);

    foreach ($items as $item) {
      $this->assertEquals(array('node_access__all'), $item->getField('search_api_node_grants')->getValues());
    }
  }

  /**
   * Tests comment indexing when hook_node_grants() takes effect.
   */
  public function testContentAccessWithNodeGrants() {
    $items = array();
    foreach ($this->comments as $comment) {
      $items[] = array(
        'datasource' => 'entity:comment',
        'item' => $comment->getTypedData(),
        'item_id' => $comment->id(),
        'field_text' => 'Text: &' . $comment->id(),
      );
    }
    $items = $this->generateItems($items);

    $this->processor->preprocessIndexItems($items);

    foreach ($items as $item) {
      $this->assertEquals(array('node_access_search_api_test:0'), $item->getField('search_api_node_grants')->getValues());
    }
  }

  /**
   * Tests that acquiring node grants leads to re-indexing of that node.
   */
  public function testNodeGrantsChange() {
    $this->index->setOption('index_directly', FALSE)->save();
    $this->index->indexItems();
    $remaining = $this->index->getTrackerInstance()->getRemainingItems();
    $this->assertEquals(array(), $remaining, 'All items were indexed.');

    /** @var \Drupal\node\NodeAccessControlHandlerInterface $access_control_handler */
    $access_control_handler = \Drupal::entityTypeManager()
      ->getAccessControlHandler('node');
    $access_control_handler->acquireGrants($this->nodes[0]);

    $expected = array(
      'entity:comment/' . $this->comments[0]->id() . ':en',
      'entity:node/' . $this->nodes[0]->id() . ':en',
    );
    $remaining = $this->index->getTrackerInstance()->getRemainingItems();
    sort($remaining);
    $this->assertEquals($expected, $remaining, 'The expected items were marked as "changed" when changing node access grants.');
  }

  /**
   * Asserts that the search results contain the expected IDs.
   *
   * @param ResultSetInterface $result
   *   The search results.
   * @param int[][] $expected
   *   The expected entity IDs, grouped by entity type and with their indexes in
   *   this object's respective array properties as the values.
   */
  protected function assertResults(ResultSetInterface $result, array $expected) {
    $results = array_keys($result->getResultItems());
    sort($results);

    $ids = array();
    foreach ($expected as $entity_type => $items) {
      $datasource_id = "entity:$entity_type";
      foreach ($items as $i) {
        if ($entity_type == 'user') {
          $id = $i . ':en';
        }
        else {
          $id = $this->{"{$entity_type}s"}[$i]->id() . ':en';
        }
        $ids[] = Utility::createCombinedId($datasource_id, $id);
      }
    }
    sort($ids);

    $this->assertEquals($ids, $results);
  }

  /**
   * Creates a new user account.
   *
   * @param string[] $permissions
   *   The permissions to set for the user.
   *
   * @return \Drupal\user\UserInterface
   *   The new user object.
   */
  protected function createUser($permissions) {
    $role = Role::create(array('id' => 'role', 'name' => 'Role test'));
    $role->save();
    user_role_grant_permissions($role->id(), $permissions);

    $values = array(
      'uid' => 2,
      'name' => 'Test',
      'roles' => array($role->id()),
    );
    $authenticated_user = User::create($values);
    $authenticated_user->enforceIsNew();
    $authenticated_user->save();

    return $authenticated_user;
  }

}
