<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event_managers\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\social_node\Kernel\NodeQueryAccessTestBase;
use Drupal\user\UserInterface;

/**
 * Defines tests for node query access with event managers.
 *
 *   This class provides automated tests to validate the correct behavior of
 *   query access logic for nodes, specifically in the context of
 *   event managers.
 *
 * @group social_event_managers
 * @group social_node_query_access
 */
class SocialEventQueryAccessTest extends NodeQueryAccessTestBase {

  /**
   * A node with event managers.
   */
  protected NodeInterface $eventManagersNode;

  /**
   * Even manager user.
   */
  protected UserInterface $eventManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    $this->installModule('social_event_managers');

    // Create 'field_event_managers' field storage.
    FieldStorageConfig::create([
      'field_name' => 'field_event_managers',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'user',
      ],
    ])->save();

    // Attach field to "field_event_managers" node type.
    FieldConfig::create([
      'field_name' => 'field_event_managers',
      'entity_type' => 'node',
      'bundle' => $this->nodeTypeWithVisibility->id(),
      'label' => 'Event managers',
    ])->save();

    $this->eventManager = $this->createUser();

    // Create node accessible for event managers.
    $this->eventManagersNode = Node::create([
      'type' => $this->nodeTypeWithVisibility->id(),
      'title' => $this->randomString(),
      // "Event manager" don't have a permission to see a "community" content.
      // So, we make it "community" to ensure that this visibility will not be
      // an obstacle in accessing to node.
      'field_content_visibility' => 'community',
      'field_event_managers' => $this->eventManager->id(),
      'status' => TRUE,
    ]);

    $this->eventManagersNode->save();
  }

  /**
   * Tests node query access for event managers.
   *
   *   This method validates that anonymous users and event managers have
   *   appropriate access restrictions/enforcements to nodes based on their
   *   visibility settings and defined access rules. It ensures no regressions
   *   are introduced in the query access logic for nodes.
   *
   * @covers \Drupal\social_event_managers\EventSubscriber\NodeQueryAccessAlterSubscriber::alterQueryAccess()
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testNodeAlterQueryAccess(): void {
    // Check access to content without appropriate permissions.
    // Make sure there are no any regressions.
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertNotContains($this->publicNode->id(), $query_results, 'Anonymous does not have access to content with "public" visibility.');
    $this->assertNotContains($this->communityNode->id(), $query_results, 'Anonymous does not have access to content with "community" visibility.');
    $this->assertNotContains($this->nodeWithoutVisibility->id(), $query_results, 'Anonymous does not have access to content without visibility.');
    $this->assertNotContains($this->eventManagersNode->id(), $query_results, 'Anonymous does not have access to content with event managers.');

    // Check access to content for event managers.
    // Make sure there are no any regressions for other access rules.
    $this->setCurrentUser($this->eventManager);
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertNotContains($this->publicNode->id(), $query_results, 'EventManager does not have access to content with "public" visibility.');
    $this->assertNotContains($this->communityNode->id(), $query_results, 'EventManager does not have access to content with "community" visibility.');
    $this->assertNotContains($this->nodeWithoutVisibility->id(), $query_results, 'EventManager does not have access to content without visibility.');
    $this->assertContains($this->eventManagersNode->id(), $query_results, 'EventManager has access to content with event managers.');
  }

}
