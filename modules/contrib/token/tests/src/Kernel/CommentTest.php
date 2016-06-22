<?php

/**
 * @file
 * Contains \Drupal\Tests\token\Kernel\CommentTest.
 */

namespace Drupal\Tests\token\Kernel;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\comment\Entity\Comment;

/**
 * Tests comment tokens.
 *
 * @group token
 */
class CommentTest extends KernelTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'comment', 'field', 'text', 'entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);

    $node_type = NodeType::create(['type' => 'page', 'name' => t('Page')]);
    $node_type->save();

    $this->installConfig(['comment']);

    $this->addDefaultCommentField('node', 'page');
  }

  function testCommentTokens() {
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName()
    ]);
    $node->save();

    $parent_comment = Comment::create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'name' => 'anonymous user',
      'mail' => 'anonymous@example.com',
      'subject' => $this->randomMachineName(),
      'body' => $this->randomMachineName(),
    ]);
    $parent_comment->save();

    // Fix http://example.com/index.php/comment/1 fails 'url:path' test.
    $parent_comment_path = $parent_comment->url();

    $tokens = array(
      'url' => $parent_comment->urlInfo('canonical', ['fragment' => "comment-{$parent_comment->id()}"])->setAbsolute()->toString(),
      'url:absolute' => $parent_comment->urlInfo('canonical', ['fragment' => "comment-{$parent_comment->id()}"])->setAbsolute()->toString(),
      'url:relative' => $parent_comment->urlInfo('canonical', ['fragment' => "comment-{$parent_comment->id()}"])->toString(),
      'url:path' => $parent_comment_path,
      'parent:url:absolute' => NULL,
    );
    $this->assertTokens('comment', array('comment' => $parent_comment), $tokens);

    $comment = Comment::create([
      'entity_id' => $node->id(),
      'pid' => $parent_comment->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'uid' => 1,
      'name' => 'anonymous user',
      'mail' => 'anonymous@example.com',
      'subject' => $this->randomMachineName(),
      'body' => $this->randomMachineName(),
    ]);
    $comment->save();

    // Fix http://example.com/index.php/comment/1 fails 'url:path' test.
    $comment_path = \Drupal::url('entity.comment.canonical', array('comment' => $comment->id()));

    $tokens = array(
      'url' => $comment->urlInfo('canonical', ['fragment' => "comment-{$comment->id()}"])->setAbsolute()->toString(),
      'url:absolute' => $comment->urlInfo('canonical', ['fragment' => "comment-{$comment->id()}"])->setAbsolute()->toString(),
      'url:relative' => $comment->urlInfo('canonical', ['fragment' => "comment-{$comment->id()}"])->toString(),
      'url:path' => $comment_path,
      'parent:url:absolute' => $parent_comment->urlInfo('canonical', ['fragment' => "comment-{$parent_comment->id()}"])->setAbsolute()->toString(),
    );
    $this->assertTokens('comment', array('comment' => $comment), $tokens);
  }

}
