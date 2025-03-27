<?php

declare(strict_types=1);

namespace Drupal\Tests\social_node\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Base class for node query access tests.
 *
 * Provides helper methods and properties to set up tests involving nodes with
 * content visibility settings and corresponding access levels.
 */
abstract class NodeQueryAccessTestBase extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'node',
    'options',
    'entity',
    'node',
    'file',
    'entity_access_by_field',
    'social_node',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A node type with content visibility enabled.
   */
  protected NodeType $nodeTypeWithVisibility;

  /**
   * A node type without content visibility.
   */
  protected NodeType $nodeTypeWithoutVisibility;

  /**
   * User with "bypass node access" permission.
   */
  protected UserInterface $privilegedUser;

  /**
   * User to check access to "public" content.
   */
  protected UserInterface $publicVisibilityUser;

  /**
   * User to check access to "community" content.
   */
  protected UserInterface $communityVisibilityUser;

  /**
   * A node with "public" visibility.
   */
  protected NodeInterface $publicNode;

  /**
   * A node with "community" visibility.
   */
  protected NodeInterface $communityNode;

  /**
   * A node without visibility.
   */
  protected NodeInterface $nodeWithoutVisibility;

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');

    $this->installConfig(['social_node']);
    // This schema isn't used in Social anymore, but tests requires
    // to install it.
    $this->installSchema('node', ['node_access']);

    // Create node types.
    $this->nodeTypeWithVisibility = NodeType::create([
      'type' => 'content_with_visibility',
      'name' => 'Content type with visibility',
    ]);
    $this->nodeTypeWithVisibility->save();

    // Create node types.
    $this->nodeTypeWithoutVisibility = NodeType::create([
      'type' => 'content_without_visibility',
      'name' => 'Content type without visibility',
    ]);
    $this->nodeTypeWithoutVisibility->save();

    // Attach field to "content_with_visibility" node type.
    FieldConfig::create([
      'field_name' => 'field_content_visibility',
      'entity_type' => 'node',
      'bundle' => 'content_with_visibility',
      'label' => 'Visibility',
      'required' => TRUE,
    ])->save();

    // Create users with different permissions.
    $this->privilegedUser = $this->createUser([
      'bypass node access',
    ]);

    $this->publicVisibilityUser = $this->createUser([
      'view node.content_with_visibility.field_content_visibility:public content',
    ]);

    $this->communityVisibilityUser = $this->createUser([
      'view node.content_with_visibility.field_content_visibility:community content',
    ]);

    // Create nodes with different visibility settings.
    $this->publicNode = Node::create([
      'type' => $this->nodeTypeWithVisibility->id(),
      'title' => 'Public content',
      'field_content_visibility' => 'public',
      'status' => TRUE,
    ]);
    $this->publicNode->save();

    $this->communityNode = Node::create([
      'type' => $this->nodeTypeWithVisibility->id(),
      'title' => 'Community content',
      'field_content_visibility' => 'community',
      'status' => TRUE,
    ]);
    $this->communityNode->save();

    $this->nodeWithoutVisibility = Node::create([
      'type' => $this->nodeTypeWithoutVisibility->id(),
      'title' => 'Content without visibility field',
      'status' => TRUE,
    ]);
    $this->nodeWithoutVisibility->save();
  }

}
