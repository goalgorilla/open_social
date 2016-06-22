<?php

/**
 * @file
 * Contains \Drupal\Tests\token\Kernel\NodeTest.
 */

namespace Drupal\Tests\token\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;

/**
 * Test the node and content type tokens.
 *
 * @group token
 */
class NodeTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'field', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
      'description' => "Use <em>basic pages</em> for your static content, such as an 'About us' page.",
    ]);
    $node_type->save();
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'description' => "Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.",
    ]);
    $node_type->save();
  }

  function testNodeTokens() {
    $page = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'revision_log' => $this->randomMachineName(),
      'path' => array('alias' => '/content/source-node')
    ]);
    $page->save();
    $tokens = array(
      'log' => $page->revision_log->value,
      'url:path' => '/content/source-node',
      'url:absolute' => \Drupal::url('entity.node.canonical', ['node' => $page->id()], array('absolute' => TRUE)),
      'url:relative' => \Drupal::url('entity.node.canonical', ['node' => $page->id()], array('absolute' => FALSE)),
      'url:unaliased:path' => "/node/{$page->id()}",
      'content-type' => 'Basic page',
      'content-type:name' => 'Basic page',
      'content-type:machine-name' => 'page',
      'content-type:description' => "Use <em>basic pages</em> for your static content, such as an 'About us' page.",
      'content-type:node-count' => 1,
      'content-type:edit-url' => \Drupal::url('entity.node_type.edit_form', ['node_type' => 'page'], array('absolute' => TRUE)),
      // Deprecated tokens.
      'type' => 'page',
      'type-name' => 'Basic page',
      'url:alias' => '/content/source-node',
    );
    $this->assertTokens('node', array('node' => $page), $tokens);

    $article = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName()
    ]);
    $article->save();
    $tokens = array(
      'log' => '',
      'url:path' => "/node/{$article->id()}",
      'url:absolute' => \Drupal::url('entity.node.canonical', ['node' => $article->id()], array('absolute' => TRUE)),
      'url:relative' => \Drupal::url('entity.node.canonical', ['node' => $article->id()], array('absolute' => FALSE)),
      'url:unaliased:path' => "/node/{$article->id()}",
      'content-type' => 'Article',
      'content-type:name' => 'Article',
      'content-type:machine-name' => 'article',
      'content-type:description' => "Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.",
      'content-type:node-count' => 1,
      'content-type:edit-url' => \Drupal::url('entity.node_type.edit_form', ['node_type' => 'article'], array('absolute' => TRUE)),
      // Deprecated tokens.
      'type' => 'article',
      'type-name' => 'Article',
      'url:alias' => "/node/{$article->id()}",
    );
    $this->assertTokens('node', array('node' => $article), $tokens);
  }
}
